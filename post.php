<?php
define('APP', true);
require_once __DIR__ . '/inc/bootstrap.php';

$slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$post = $slug !== '' ? post_get_by_slug($slug) : null;

// Csak publikált, esedékes cikk látszik nyilvánosan
$visible = $post && $post['status'] === 'published'
    && (empty($post['published_at']) || $post['published_at'] <= date('c'));

function img_src($path) { return $path !== '' ? '/' . ltrim($path, '/') : ''; }

if (!$visible) {
    http_response_code(404);
    $post = null;
}

$pageTitle = $visible ? ($post['title'] . ' | Kazán Szerviz Kecskemét') : 'A cikk nem található | Kazán Szerviz Kecskemét';
$metaDesc  = $visible ? ($post['excerpt'] !== '' ? $post['excerpt'] : excerpt_from_html($post['content'])) : 'A keresett cikk nem található.';
$canonical = 'https://gazszerelokecskemet.hu/post.php?slug=' . rawurlencode($slug);
$ogImage   = ($visible && !empty($post['featured_image'])) ? 'https://gazszerelokecskemet.hu/' . ltrim($post['featured_image'], '/') : '';
$pubDate   = $visible ? ($post['published_at'] ?: $post['created']) : '';
?><!DOCTYPE html>
<html lang="hu">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="<?= e($metaDesc) ?>" />
  <meta name="robots" content="<?= $visible ? 'index, follow' : 'noindex, follow' ?>" />
  <link rel="canonical" href="<?= e($canonical) ?>" />
  <meta property="og:title" content="<?= e($visible ? $post['title'] : 'A cikk nem található') ?>" />
  <meta property="og:type" content="article" />
  <meta property="og:url" content="<?= e($canonical) ?>" />
  <?php if ($ogImage): ?><meta property="og:image" content="<?= e($ogImage) ?>" /><?php endif; ?>
  <meta name="theme-color" content="#0F172A" />
  <link rel="icon" type="image/png" href="logo%20transparent.png" />
  <link rel="apple-touch-icon" href="logo%20transparent.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet" />
  <?php if ($visible): ?>
  <script type="application/ld+json">
  <?= json_encode(array(
      '@context' => 'https://schema.org',
      '@type' => 'BlogPosting',
      'headline' => $post['title'],
      'description' => $metaDesc,
      'image' => $ogImage ?: null,
      'datePublished' => $pubDate,
      'dateModified' => isset($post['updated']) ? $post['updated'] : $pubDate,
      'author' => array('@type' => 'Person', 'name' => 'Polyák Zoltán'),
      'publisher' => array(
          '@type' => 'Organization',
          'name' => 'Kazán Szerviz Kecskemét – Polyák Zoltán',
      ),
      'mainEntityOfPage' => array('@type' => 'WebPage', '@id' => $canonical),
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <script type="application/ld+json">
  <?= json_encode(array(
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => array(
          array('@type'=>'ListItem','position'=>1,'name'=>'Főoldal','item'=>'https://gazszerelokecskemet.hu/'),
          array('@type'=>'ListItem','position'=>2,'name'=>'Blog','item'=>'https://gazszerelokecskemet.hu/blog.php'),
          array('@type'=>'ListItem','position'=>3,'name'=>$post['title'],'item'=>$canonical),
      ),
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
  </script>
  <?php endif; ?>
  <link rel="stylesheet" href="/styles.css" />
</head>
<body>

<!-- ═══ NAV ═══════════════════════════════════════════════════ -->
<nav id="nav" role="navigation" aria-label="Főmenü">
  <div class="container">
    <div class="nav-inner">
      <a href="index.html" class="nav-logo" aria-label="Kazán Szerviz Kecskemét – Főoldal">
        <div class="nav-logo-img-wrap"><img src="logo%20transparent.png" alt="Kazán Szerviz Kecskemét logó" /></div>
        <div class="nav-logo-text-wrap"><span class="nav-logo-name">Kazán<span>Szerviz</span></span></div>
      </a>
      <ul class="nav-links" role="list">
        <li><a href="szolgaltatasok.html">Szolgáltatások</a></li>
        <li><a href="markak.html">Márkák</a></li>
        <li><a href="munkaim.html">Munkáim</a></li>
        <li><a href="blog.php" aria-current="page">Blog</a></li>
        <li><a href="rolam.html">Rólam</a></li>
        <li><a href="contact.html">Kapcsolat</a></li>
      </ul>
      <div class="nav-actions">
        <a href="tel:+36302605756" class="nav-phone" aria-label="Hívjon most: +36 30 260 57 56">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          +36 30 260 57 56
        </a>
        <button type="button" class="nav-cta js-open-quote" aria-label="Azonnali árajánlat"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Azonnali árajánlat</button>
      </div>
      <button class="nav-hamburger" aria-label="Menü" aria-expanded="false" onclick="toggleNav(this)"><span></span><span></span><span></span></button>
    </div>
  </div>
</nav>

<div id="mobile-nav" role="dialog" aria-label="Mobilmenü">
  <a href="szolgaltatasok.html" class="mobile-nav-link" onclick="closeNav()">Szolgáltatások</a>
  <a href="markak.html" class="mobile-nav-link" onclick="closeNav()">Márkák</a>
  <a href="munkaim.html" class="mobile-nav-link" onclick="closeNav()">Munkáim</a>
  <a href="blog.php" class="mobile-nav-link" onclick="closeNav()">Blog</a>
  <a href="rolam.html" class="mobile-nav-link" onclick="closeNav()">Rólam</a>
  <a href="contact.html" class="mobile-nav-link" onclick="closeNav()">Kapcsolat</a>
  <a href="tel:+36302605756" class="btn-primary" style="margin-top:28px; justify-content:center;" onclick="closeNav()">Hívjon most!</a>
</div>

<?php if (!$visible): ?>
<!-- ═══ 404 ══════════════════════════════════════════════════ -->
<main class="svc-main" style="min-height:50vh">
  <div class="container" style="text-align:center;padding:80px 0">
    <div class="svc-eyebrow" style="color:#1A56E8">404</div>
    <h1 style="font-family:Poppins,sans-serif;color:#0F172A;margin:10px 0 14px">A cikk nem található</h1>
    <p style="color:#64748b;max-width:46ch;margin:0 auto 26px">Lehet, hogy a bejegyzés időközben megszűnt, vagy elírás van a hivatkozásban.</p>
    <a class="btn-primary" href="blog.php">Vissza a blogra</a>
  </div>
</main>
<?php else: ?>
<!-- ═══ HERO ══════════════════════════════════════════════════ -->
<header class="svc-hero">
  <img class="svc-hero-photo" src="<?= e(!empty($post['featured_image']) ? img_src($post['featured_image']) : 'k%C3%A9pek/707125326_918619997869657_1747506858485559298_n.webp') ?>" alt="<?= e($post['title']) ?>" loading="eager" decoding="async" />
  <div class="container">
    <nav class="svc-breadcrumb" aria-label="Morzsamenü">
      <a href="index.html">Főoldal</a><span class="sep">/</span>
      <a href="blog.php">Blog</a><span class="sep">/</span>
      <span aria-current="page"><?= e($post['title']) ?></span>
    </nav>
    <div class="svc-eyebrow">Blog</div>
    <h1><?= e($post['title']) ?></h1>
    <div class="blog-meta" style="margin-top:14px;color:rgba(255,255,255,.85)">
      <time datetime="<?= e(date('Y-m-d', strtotime($pubDate))) ?>"><?= e(hu_date($pubDate)) ?></time>
      <span class="dot" aria-hidden="true"></span>
      <span><?= reading_time($post['content']) ?> perc olvasás</span>
    </div>
  </div>
</header>

<!-- ═══ ARTICLE ═══════════════════════════════════════════════ -->
<main class="svc-main">
  <div class="container">
    <div class="svc-grid">
      <article class="svc-prose">
        <?= $post['content'] /* már fertőtlenített HTML mentéskor */ ?>
      </article>
      <aside class="svc-aside">
        <div class="svc-card svc-card-navy">
          <h3>Kérdése van a kazánjáról?</h3>
          <p>Hívjon, és Kecskeméten vagy környékén gyorsan, garanciával segítek.</p>
          <a href="tel:+36302605756" class="svc-card-phone">+36 30 260 57 56</a>
          <a href="tel:+36302605756" class="btn-primary" style="width:100%;justify-content:center;">Hívás most</a>
          <a href="tel:+36302605756" class="btn-outline js-open-quote" style="width:100%;justify-content:center;margin-top:10px;">Árajánlat kérése</a>
        </div>
        <div class="svc-card">
          <h3>További cikkek</h3>
          <p style="margin-bottom:14px">Nézze meg a blog többi bejegyzését is.</p>
          <a class="btn-outline" href="blog.php" style="width:100%;justify-content:center;">Vissza a blogra</a>
        </div>
      </aside>
    </div>
  </div>
</main>
<?php endif; ?>

<!-- ═══ CTA BAND ══════════════════════════════════════════════ -->
<section class="svc-ctaband">
  <div class="container">
    <h2>Inkább kérdezne, mint olvasna?</h2>
    <p>Mondja el a problémát telefonon – Kecskeméten és környékén gyorsan, garanciával segítek.</p>
    <div class="svc-cta-row">
      <a href="tel:+36302605756" class="btn-primary">Hívjon most: +36 30 260 57 56</a>
      <a href="tel:+36302605756" class="btn-outline js-open-quote">Árajánlat kérése</a>
    </div>
  </div>
</section>

<!-- ═══ FOOTER ════════════════════════════════════════════════ -->
<footer role="contentinfo">
  <div class="container">
    <div class="footer-inner">
      <div>
        <div class="footer-logo"><img src="logo%20transparent.png" alt="Kazán Szerviz Kecskemét logó" style="height:40px;width:auto;" /></div>
        <div class="footer-logo-text">Kazán<span>Szerviz</span> Kecskemét</div>
        <p class="footer-desc">Polyák Zoltán szakképzett gázszerelő és gázbiztonsági felülvizsgáló. Kazán javítás, szerviz és karbantartás Kecskeméten – 15+ év tapasztalattal.</p>
        <div class="footer-contact-mini">
          <a href="tel:+36302605756"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>+36 30 260 57 56</a>
          <a href="mailto:info@kazanszervizkecskemet.hu"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg>info@kazanszervizkecskemet.hu</a>
          <a href="https://maps.google.com/?q=6000+Kecskemét,+Számadó+utca+25" target="_blank" rel="noopener noreferrer"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>6000 Kecskemét, Számadó u. 25.</a>
        </div>
      </div>
      <nav aria-label="Szolgáltatások">
        <div class="footer-col-title">Szolgáltatások</div>
        <ul class="footer-links" role="list">
          <li><a href="kazan-javitas.html">Kazán javítás</a></li>
          <li><a href="kazan-szerviz.html">Kazán szerviz</a></li>
          <li><a href="kazancsere.html">Kazáncsere</a></li>
          <li><a href="kazan-beuzemeles.html">Kazán beüzemelés</a></li>
          <li><a href="kemenybeleles.html">Kéménybélelés</a></li>
          <li><a href="gaz-biztonsagi-felulvizsgalat.html">Gáz felülvizsgálat</a></li>
        </ul>
      </nav>
      <nav aria-label="Navigáció">
        <div class="footer-col-title">Navigáció</div>
        <ul class="footer-links" role="list">
          <li><a href="szolgaltatasok.html">Szolgáltatások</a></li>
          <li><a href="munkaim.html">Munkáim</a></li>
          <li><a href="blog.php">Blog</a></li>
          <li><a href="rolam.html">Rólam</a></li>
          <li><a href="contact.html">Kapcsolat</a></li>
        </ul>
      </nav>
    </div>
    <div class="footer-bottom">
      <div class="footer-copy">© 2025 Hírös Webáruház Kft. – Polyák Zoltán. Minden jog fenntartva.</div>
      <nav class="footer-legal" aria-label="Jogi linkek">
        <a href="adatkezelesi-tajekoztato.html">Adatkezelési tájékoztató</a>
        <a href="aszf.html">ÁSZF</a>
      </nav>
    </div>
  </div>
</footer>

<div id="float-call" class="mobile-bar">
  <a href="tel:+36302605756" class="mbar-btn mbar-call" aria-label="Hívás">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg> Hívás
  </a>
  <button type="button" class="mbar-btn mbar-quote js-open-quote" aria-label="Azonnali árajánlat"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg> Azonnali árajánlat</button>
</div>

<script>
  const nav = document.getElementById('nav');
  window.addEventListener('scroll', () => { nav.classList.toggle('scrolled', window.scrollY >= 12); }, { passive: true });
  document.querySelectorAll('.js-open-quote').forEach(el => {
    el.addEventListener('click', e => { const l = document.querySelector('.faq-chat-launcher'); if (l) { e.preventDefault(); l.click(); } });
  });
  function toggleNav(btn){const mn=document.getElementById('mobile-nav');const open=mn.style.display==='flex';mn.style.display=open?'none':'flex';btn.setAttribute('aria-expanded',!open);document.body.style.overflow=open?'':'hidden';}
  function closeNav(){const mn=document.getElementById('mobile-nav');mn.style.display='none';document.body.style.overflow='';const b=document.querySelector('.nav-hamburger');if(b)b.setAttribute('aria-expanded','false');}
  window.addEventListener('resize', () => { if (window.innerWidth > 980) closeNav(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape') closeNav(); });
</script>
<script>
  window.KAZAN_CONFIG = { apiUrl: "https://kecskemet-kazan-widget.vercel.app/api/faq-agent", assetsUrl: "https://kecskemet-kazan-widget.vercel.app" };
</script>
<script src="https://kecskemet-kazan-widget.vercel.app/widget.js" defer></script>
<script>
  (function () {
    if (window.matchMedia('(max-width: 600px)').matches) return;
    if (sessionStorage.getItem('kazanChatAutoOpened')) return;
    var tries = 0, iv = setInterval(function () {
      var l = document.querySelector('.faq-chat-launcher');
      if (l) { clearInterval(iv); sessionStorage.setItem('kazanChatAutoOpened', '1'); l.click(); }
      else if (++tries > 60) { clearInterval(iv); }
    }, 100);
  })();
</script>
<script src="brand-enhance.js" defer></script>
<script src="cookie-banner.js" defer></script>
</body>
</html>
