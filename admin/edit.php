<?php
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';
require_once INC_DIR . '/uploads.php';
require_once __DIR__ . '/_layout.php';

if (!IS_CONFIGURED) { header('Location: setup.php'); exit; }
require_login();

$error = '';

// Meglévő bejegyzés betöltése (szerkesztés), vagy üres (új).
$editing = null;
if (isset($_GET['id']) && valid_post_id($_GET['id'])) {
    $editing = post_get($_GET['id']);
}

// Alapértelmezett űrlap-értékek
$form = array(
    'id'             => $editing ? $editing['id'] : '',
    'title'          => $editing ? $editing['title'] : '',
    'slug'           => $editing ? $editing['slug'] : '',
    'excerpt'        => $editing ? (isset($editing['excerpt']) ? $editing['excerpt'] : '') : '',
    'content'        => $editing ? $editing['content'] : '',
    'featured_image' => $editing ? (isset($editing['featured_image']) ? $editing['featured_image'] : '') : '',
    'status'         => $editing ? $editing['status'] : 'draft',
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $form['title']   = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $form['slug']    = trim(isset($_POST['slug']) ? $_POST['slug'] : '');
    $form['excerpt'] = trim(isset($_POST['excerpt']) ? $_POST['excerpt'] : '');
    $form['content'] = isset($_POST['content']) ? $_POST['content'] : '';
    $form['status']  = (isset($_POST['status']) && $_POST['status'] === 'published') ? 'published' : 'draft';

    $postId = (isset($_POST['id']) && valid_post_id($_POST['id'])) ? $_POST['id'] : '';
    $existing = $postId ? post_get($postId) : null;
    $form['featured_image'] = $existing ? (isset($existing['featured_image']) ? $existing['featured_image'] : '') : $form['featured_image'];

    // Kiemelt kép: törlés kérése
    if (!empty($_POST['remove_featured'])) {
        $form['featured_image'] = '';
    }
    // Kiemelt kép: új feltöltés
    if (isset($_FILES['featured']) && $_FILES['featured']['error'] !== UPLOAD_ERR_NO_FILE) {
        $up = save_uploaded_image($_FILES['featured']);
        if ($up['ok']) {
            $form['featured_image'] = $up['url'];
        } else {
            $error = 'Kiemelt kép: ' . $up['error'];
        }
    }

    if ($error === '') {
        if ($form['title'] === '') {
            $error = 'A cím megadása kötelező.';
        }
    }

    if ($error === '') {
        $now = date('c');
        $id = $postId !== '' ? $postId : new_post_id();

        $slug = $form['slug'] !== '' ? slugify($form['slug']) : slugify($form['title']);
        $slug = unique_slug($slug, $id);
        $form['slug'] = $slug;

        $content = sanitize_html($form['content']);
        $excerpt = $form['excerpt'] !== '' ? $form['excerpt'] : excerpt_from_html($content);

        // published_at: az első publikáláskor rögzítjük
        $publishedAt = '';
        if ($existing && !empty($existing['published_at'])) {
            $publishedAt = $existing['published_at'];
        }
        if ($form['status'] === 'published' && $publishedAt === '') {
            $publishedAt = $now;
        }

        $post = array(
            'id'             => $id,
            'title'          => $form['title'],
            'slug'           => $slug,
            'excerpt'        => $excerpt,
            'content'        => $content,
            'featured_image' => $form['featured_image'],
            'status'         => $form['status'],
            'author'         => current_user(),
            'created'        => $existing && !empty($existing['created']) ? $existing['created'] : $now,
            'updated'        => $now,
            'published_at'   => $publishedAt,
        );

        if (post_save($post)) {
            flash_set('success', $form['status'] === 'published'
                ? 'A cikk mentve és publikálva.'
                : 'A cikk vázlatként elmentve.');
            header('Location: index.php');
            exit;
        }
        $error = 'Nem sikerült menteni a cikket (írási jog?).';
    }
}

$isNew = ($form['id'] === '');
admin_head($isNew ? 'Új cikk' : 'Cikk szerkesztése', $isNew ? 'new' : '');
?>
<div class="adm-pagehead">
  <div>
    <h1><?= $isNew ? 'Új cikk' : 'Cikk szerkesztése' ?></h1>
    <p><a href="index.php">← Vissza a cikkekhez</a></p>
  </div>
</div>

<?php if ($error): ?><div class="adm-flash adm-flash-error"><?= e($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" id="post-form">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= e($form['id']) ?>">

  <div class="adm-form">
    <div>
      <div class="adm-panel">
        <div class="field field-title">
          <label for="title">Cím</label>
          <input type="text" id="title" name="title" value="<?= e($form['title']) ?>" placeholder="Pl. Mikor érdemes kazánt karbantartani?" required>
        </div>
        <div class="field">
          <label for="content">Tartalom</label>
          <textarea id="content" name="content"><?= e($form['content']) ?></textarea>
        </div>
      </div>
    </div>

    <aside>
      <div class="adm-panel">
        <h3>Közzététel</h3>
        <div class="field">
          <div class="statuses">
            <label><input type="radio" name="status" value="draft" <?= $form['status'] !== 'published' ? 'checked' : '' ?>> Vázlat</label>
            <label><input type="radio" name="status" value="published" <?= $form['status'] === 'published' ? 'checked' : '' ?>> Publikálva</label>
          </div>
          <p class="hint" style="margin-top:8px">A „Vázlat” csak Önnek látszik. A „Publikálva” azonnal megjelenik a blogon.</p>
        </div>
      </div>

      <div class="adm-panel">
        <h3>Kiemelt kép</h3>
        <?php if ($form['featured_image']): ?>
          <img class="feat-preview" id="feat-preview" src="../<?= e($form['featured_image']) ?>" alt="">
          <label style="font-size:.85rem;display:flex;gap:8px;align-items:center;margin-bottom:12px">
            <input type="checkbox" name="remove_featured" value="1"> Kép eltávolítása
          </label>
        <?php else: ?>
          <img class="feat-preview feat-empty" id="feat-preview" alt="" style="display:none">
          <div class="feat-preview feat-empty" id="feat-empty">Nincs kép kiválasztva</div>
        <?php endif; ?>
        <div class="field">
          <input type="file" id="featured" name="featured" accept="image/jpeg,image/png,image/webp,image/gif">
          <p class="hint" style="margin-top:6px">JPG, PNG, WebP vagy GIF, max 5 MB.</p>
        </div>
      </div>

      <div class="adm-panel">
        <h3>Részletek</h3>
        <div class="field">
          <label for="slug">URL-rövidítés (slug) <span class="hint">– üresen hagyva a címből készül</span></label>
          <input type="text" id="slug" name="slug" value="<?= e($form['slug']) ?>" placeholder="kazan-karbantartas-mikor">
        </div>
        <div class="field">
          <label for="excerpt">Rövid kivonat <span class="hint">– a listában jelenik meg</span></label>
          <textarea id="excerpt" name="excerpt" placeholder="Üresen hagyva automatikusan készül a szövegből."><?= e($form['excerpt']) ?></textarea>
        </div>
      </div>
    </aside>
  </div>

  <div class="form-bar">
    <a class="btn btn-ghost" href="index.php">Mégse</a>
    <div class="spacer"></div>
    <button type="submit" class="btn btn-primary">Mentés</button>
  </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
  const CSRF = <?= json_encode(csrf_token()) ?>;
  tinymce.init({
    selector: '#content',
    license_key: 'gpl',
    height: 520,
    menubar: false,
    branding: false,
    promotion: false,
    language: 'hu_HU',
    language_url: 'https://cdn.jsdelivr.net/npm/tinymce-i18n@24/langs7/hu_HU.js',
    plugins: 'lists link image autolink autoresize code',
    toolbar: 'undo redo | blocks | bold italic | bullist numlist | link image blockquote | removeformat code',
    block_formats: 'Bekezdés=p; Címsor 2=h2; Címsor 3=h3; Idézet=blockquote',
    content_style: "body{font-family:'Open Sans',sans-serif;font-size:16px;line-height:1.7;color:#1e293b}",
    automatic_uploads: true,
    images_upload_handler: function (blobInfo, progress) {
      return new Promise(function (resolve, reject) {
        const fd = new FormData();
        fd.append('image', blobInfo.blob(), blobInfo.filename());
        fd.append('csrf', CSRF);
        fetch('upload.php', { method: 'POST', body: fd, credentials: 'same-origin' })
          .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
          .then(function (res) {
            if (res.ok && res.j && res.j.location) resolve(res.j.location);
            else reject({ message: (res.j && res.j.error) || 'Feltöltési hiba', remove: true });
          })
          .catch(function () { reject({ message: 'Hálózati hiba a feltöltéskor', remove: true }); });
      });
    }
  });

  // Kiemelt kép előnézet
  var featInput = document.getElementById('featured');
  if (featInput) {
    featInput.addEventListener('change', function () {
      var file = this.files && this.files[0];
      var prev = document.getElementById('feat-preview');
      var empty = document.getElementById('feat-empty');
      if (file && prev) {
        prev.src = URL.createObjectURL(file);
        prev.style.display = 'block';
        prev.classList.remove('feat-empty');
        if (empty) empty.style.display = 'none';
      }
    });
  }
</script>
<?php admin_foot(); ?>
