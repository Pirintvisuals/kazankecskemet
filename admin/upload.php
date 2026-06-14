<?php
/**
 * Soron belüli (TinyMCE) képfeltöltés végpontja. JSON-t ad vissza: {location:"..."} vagy {error:"..."}.
 * Csak bejelentkezett admin használhatja, CSRF-tokennel.
 */
define('APP', true);
require_once __DIR__ . '/../inc/bootstrap.php';
require_once INC_DIR . '/auth.php';
require_once INC_DIR . '/uploads.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(array('error' => 'Nincs bejelentkezve.'));
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('error' => 'Csak POST.'));
    exit;
}

// CSRF (FormData mezőből)
$token = isset($_POST['csrf']) ? $_POST['csrf'] : '';
secure_session_start();
if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
    http_response_code(400);
    echo json_encode(array('error' => 'Érvénytelen kérés (CSRF).'));
    exit;
}

if (!isset($_FILES['image'])) {
    http_response_code(400);
    echo json_encode(array('error' => 'Nincs kép.'));
    exit;
}

$res = save_uploaded_image($_FILES['image']);
if ($res['ok']) {
    // TinyMCE a 'location'-t várja; abszolút útvonal a gyökértől, hogy a /blog/<slug> alól is jó legyen.
    echo json_encode(array('location' => '/' . $res['url']));
} else {
    http_response_code(415);
    echo json_encode(array('error' => $res['error']));
}
