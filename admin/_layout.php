<?php
if (!defined('APP')) exit;

/** Admin felület közös fejléc/lábléc. */
function admin_head($title, $active = '') {
    $u = e(current_user());
    ?><!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> – Kazán Admin</title>
<link rel="icon" type="image/png" href="../logo%20transparent.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700&family=Poppins:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<header class="adm-top">
  <div class="adm-top-inner">
    <a class="adm-brand" href="index.php">Kazán<span>Admin</span></a>
    <nav class="adm-nav" aria-label="Admin menü">
      <a href="index.php"<?= $active === 'list' ? ' class="active"' : '' ?>>Cikkek</a>
      <a href="edit.php"<?= $active === 'new' ? ' class="active"' : '' ?>>Új cikk</a>
      <a href="../blog.php" target="_blank" rel="noopener">Blog megtekintése ↗</a>
    </nav>
    <div class="adm-user">
      <span><?= $u ?></span>
      <a class="adm-logout" href="logout.php">Kijelentkezés</a>
    </div>
  </div>
</header>
<main class="adm-main"><?php
}

function admin_foot() {
    ?></main>
<footer class="adm-foot">Kazán Szerviz Kecskemét – tartalomkezelő · Polyák Zoltán</footer>
</body>
</html><?php
}

/** Egyszeri flash üzenet a munkamenetben. */
function flash_set($type, $msg) {
    secure_session_start();
    $_SESSION['flash'] = array('type' => $type, 'msg' => $msg);
}
function flash_render() {
    secure_session_start();
    if (empty($_SESSION['flash'])) return;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    echo '<div class="adm-flash adm-flash-' . e($f['type']) . '">' . e($f['msg']) . '</div>';
}
