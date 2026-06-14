<?php
/**
 * Egyszeri beállítás: létrehozza az admin felhasználót és jelszót (inc/config.php).
 * Ha a konfiguráció már létezik, nem csinál semmit (biztonság).
 * Sikeres beállítás után TÖRÖLJE EZT A FÁJLT.
 */
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';

$done = false;
$error = '';

if (IS_CONFIGURED) {
    // Már be van állítva – ne lehessen felülírni.
    http_response_code(403);
    $alreadySet = true;
} else {
    $alreadySet = false;
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = trim(isset($_POST['username']) ? $_POST['username'] : '');
        $pass = isset($_POST['password']) ? $_POST['password'] : '';
        $pass2 = isset($_POST['password2']) ? $_POST['password2'] : '';

        if (strlen($user) < 3) {
            $error = 'A felhasználónév legyen legalább 3 karakter.';
        } elseif (strlen($pass) < 10) {
            $error = 'A jelszó legyen legalább 10 karakter.';
        } elseif ($pass !== $pass2) {
            $error = 'A két jelszó nem egyezik.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $php = "<?php\n"
                 . "if (!defined('APP')) exit;\n"
                 . "// Automatikusan generálva: admin/setup.php — kézzel ne szerkessze.\n"
                 . "define('ADMIN_USER', " . var_export($user, true) . ");\n"
                 . "define('ADMIN_HASH', " . var_export($hash, true) . ");\n";
            if (file_put_contents(CONFIG_FILE, $php, LOCK_EX) === false) {
                $error = 'Nem sikerült írni az inc/config.php fájlt. Ellenőrizze az írási jogokat.';
            } else {
                @chmod(CONFIG_FILE, 0640);
                $done = true;
            }
        }
    }
}
?><!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Beállítás – Kazán Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>Kazán<span>Admin</span></h1>
    <p class="sub">Első beállítás – adminisztrátor létrehozása</p>

    <?php if ($alreadySet): ?>
      <div class="adm-flash adm-flash-info">A felület már be van állítva. Biztonsági okból ez az oldal le van tiltva.<br>Lépjen be: <a href="login.php">Belépés</a></div>
      <p style="color:#dc2626;font-size:.85rem;text-align:center">Ha még megvan, <strong>törölje az <code>admin/setup.php</code> fájlt</strong>.</p>
    <?php elseif ($done): ?>
      <div class="adm-flash adm-flash-success">Kész! Az adminisztrátor létrejött.</div>
      <p style="font-size:.88rem;text-align:center;color:#991b1b"><strong>Fontos:</strong> most törölje az <code>admin/setup.php</code> fájlt a szerverről.</p>
      <a class="btn btn-primary" href="login.php" style="width:100%;justify-content:center;margin-top:8px">Tovább a belépéshez</a>
    <?php else: ?>
      <?php if ($error): ?><div class="adm-flash adm-flash-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="field">
          <label for="u">Felhasználónév</label>
          <input type="text" id="u" name="username" required minlength="3" value="<?= e(isset($_POST['username']) ? $_POST['username'] : '') ?>">
        </div>
        <div class="field">
          <label for="p">Jelszó <span class="hint">(min. 10 karakter)</span></label>
          <input type="password" id="p" name="password" required minlength="10">
        </div>
        <div class="field">
          <label for="p2">Jelszó újra</label>
          <input type="password" id="p2" name="password2" required minlength="10">
        </div>
        <button class="btn btn-primary" type="submit">Adminisztrátor létrehozása</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
