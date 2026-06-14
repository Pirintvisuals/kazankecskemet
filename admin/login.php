<?php
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';

if (!IS_CONFIGURED) { header('Location: setup.php'); exit; }
if (is_logged_in()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $res = login_attempt(
        isset($_POST['username']) ? $_POST['username'] : '',
        isset($_POST['password']) ? $_POST['password'] : ''
    );
    if ($res === true) {
        header('Location: index.php');
        exit;
    } elseif ($res === 'locked') {
        $mins = (int)ceil(login_lockout_remaining() / 60);
        $error = 'Túl sok sikertelen próbálkozás. Próbálja újra kb. ' . $mins . ' perc múlva.';
    } else {
        $error = 'Hibás felhasználónév vagy jelszó.';
    }
}
?><!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Belépés – Kazán Admin</title>
<link rel="icon" type="image/png" href="../logo%20transparent.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>Kazán<span>Admin</span></h1>
    <p class="sub">Jelentkezzen be a blog kezeléséhez</p>
    <?php if ($error): ?><div class="adm-flash adm-flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <div class="field">
        <label for="u">Felhasználónév</label>
        <input type="text" id="u" name="username" required autofocus>
      </div>
      <div class="field">
        <label for="p">Jelszó</label>
        <input type="password" id="p" name="password" required>
      </div>
      <button class="btn btn-primary" type="submit">Belépés</button>
    </form>
  </div>
</div>
</body>
</html>
