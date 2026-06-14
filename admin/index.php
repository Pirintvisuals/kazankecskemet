<?php
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';
require_once __DIR__ . '/_layout.php';

if (!IS_CONFIGURED) { header('Location: setup.php'); exit; }
require_login();

$posts = posts_all();
admin_head('Cikkek', 'list');
?>
<?php flash_render(); ?>
<div class="adm-pagehead">
  <div>
    <h1>Cikkek</h1>
    <p><?= count($posts) ?> bejegyzés összesen</p>
  </div>
  <a class="btn btn-accent" href="edit.php">+ Új cikk írása</a>
</div>

<?php if (!$posts): ?>
  <div class="adm-card adm-empty">
    <h2>Még nincs egyetlen cikk sem</h2>
    <p>Kezdje az első bejegyzés megírásával – pár perc az egész.</p>
    <a class="btn btn-primary" href="edit.php" style="margin-top:14px">Első cikk megírása</a>
  </div>
<?php else: ?>
  <div class="adm-card">
    <table class="adm-table">
      <thead>
        <tr>
          <th style="width:84px">Kép</th>
          <th>Cím</th>
          <th style="width:120px">Állapot</th>
          <th style="width:140px">Dátum</th>
          <th style="width:170px"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p): ?>
          <?php
            $isPub = $p['status'] === 'published';
            $date = !empty($p['published_at']) ? $p['published_at'] : $p['created'];
          ?>
          <tr>
            <td>
              <?php if (!empty($p['featured_image'])): ?>
                <img class="adm-thumb" src="../<?= e($p['featured_image']) ?>" alt="">
              <?php else: ?>
                <div class="adm-thumb"></div>
              <?php endif; ?>
            </td>
            <td>
              <div class="adm-row-title"><a href="edit.php?id=<?= e($p['id']) ?>"><?= e($p['title']) ?></a></div>
              <div class="adm-row-slug">/blog/<?= e($p['slug']) ?></div>
            </td>
            <td>
              <span class="badge <?= $isPub ? 'badge-published' : 'badge-draft' ?>"><?= $isPub ? 'Publikálva' : 'Vázlat' ?></span>
            </td>
            <td><?= e(hu_date_short($date)) ?></td>
            <td>
              <div class="adm-actions">
                <?php if ($isPub): ?>
                  <a class="btn btn-ghost btn-sm" href="../post.php?slug=<?= e($p['slug']) ?>" target="_blank" rel="noopener">Megnéz</a>
                <?php endif; ?>
                <a class="btn btn-ghost btn-sm" href="edit.php?id=<?= e($p['id']) ?>">Szerkeszt</a>
                <form method="post" action="delete.php" onsubmit="return confirm('Biztosan törli ezt a cikket? Ez nem vonható vissza.');" style="display:inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Töröl</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php admin_foot(); ?>
