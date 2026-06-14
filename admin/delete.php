<?php
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';
require_once __DIR__ . '/_layout.php';

if (!IS_CONFIGURED) { header('Location: setup.php'); exit; }
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$id = isset($_POST['id']) ? $_POST['id'] : '';
if (valid_post_id($id) && post_delete($id)) {
    flash_set('success', 'A cikk törölve.');
} else {
    flash_set('error', 'A cikket nem sikerült törölni.');
}
header('Location: index.php');
exit;
