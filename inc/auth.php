<?php
if (!defined('APP')) exit;

/**
 * Hitelesítés: biztonságos munkamenet, CSRF-védelem, belépés-korlátozás (throttle).
 * Egy adminisztrátor (Polyák Zoltán). A jelszó-hash az inc/config.php-ban van.
 */

define('THROTTLE_FILE', CONTENT_DIR . '/.login_throttle.json');
define('THROTTLE_MAX', 6);          // megengedett sikertelen próbálkozás
define('THROTTLE_WINDOW', 900);     // 15 perc (másodperc)

function secure_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    session_set_cookie_params(array(
        'lifetime' => 0, 'path' => '/', 'domain' => '',
        'secure' => $https, 'httponly' => true, 'samesite' => 'Lax',
    ));
    session_name('kazan_admin');
    session_start();
}

function is_logged_in() {
    secure_session_start();
    return !empty($_SESSION['uid']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function current_user() {
    secure_session_start();
    return isset($_SESSION['uid']) ? $_SESSION['uid'] : '';
}

/* ── CSRF ─────────────────────────────────────────────────────── */
function csrf_token() {
    secure_session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check($token = null) {
    secure_session_start();
    if ($token === null) $token = isset($_POST['csrf']) ? $_POST['csrf'] : '';
    if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(400);
        exit('Érvénytelen vagy lejárt kérés (CSRF). Töltse újra az oldalt.');
    }
}

/* ── Belépés-korlátozás ───────────────────────────────────────── */
function _throttle_load() {
    if (!is_file(THROTTLE_FILE)) return array();
    $d = json_decode((string)file_get_contents(THROTTLE_FILE), true);
    return is_array($d) ? $d : array();
}
function _throttle_save($data) {
    file_put_contents(THROTTLE_FILE, json_encode($data), LOCK_EX);
}
function _client_ip() {
    return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
}

/** Le van-e tiltva most ez az IP? Visszaadja a hátralévő másodperceket (0 = nincs tiltás). */
function login_lockout_remaining() {
    $data = _throttle_load();
    $ip = _client_ip();
    if (!isset($data[$ip])) return 0;
    $rec = $data[$ip];
    if ($rec['count'] < THROTTLE_MAX) return 0;
    $elapsed = time() - $rec['first'];
    if ($elapsed >= THROTTLE_WINDOW) return 0;
    return THROTTLE_WINDOW - $elapsed;
}

function _throttle_register_fail() {
    $data = _throttle_load();
    $ip = _client_ip();
    $now = time();
    if (!isset($data[$ip]) || ($now - $data[$ip]['first']) >= THROTTLE_WINDOW) {
        $data[$ip] = array('count' => 1, 'first' => $now);
    } else {
        $data[$ip]['count']++;
    }
    // régi bejegyzések takarítása
    foreach ($data as $k => $v) {
        if (($now - $v['first']) >= THROTTLE_WINDOW) unset($data[$k]);
    }
    _throttle_save($data);
}

function _throttle_reset() {
    $data = _throttle_load();
    unset($data[_client_ip()]);
    _throttle_save($data);
}

/**
 * Belépési kísérlet. Visszaad: true (siker) | 'locked' | false (hibás adatok).
 */
function login_attempt($user, $pass) {
    if (login_lockout_remaining() > 0) return 'locked';

    $ok = defined('ADMIN_USER') && defined('ADMIN_HASH')
        && is_string($user) && is_string($pass)
        && hash_equals(ADMIN_USER, $user)
        && password_verify($pass, ADMIN_HASH);

    if (!$ok) {
        _throttle_register_fail();
        return false;
    }

    _throttle_reset();
    secure_session_start();
    session_regenerate_id(true);
    $_SESSION['uid'] = $user;
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return true;
}

function logout() {
    secure_session_start();
    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
