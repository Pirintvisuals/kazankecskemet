<?php
if (!defined('APP')) exit;

/**
 * Biztonságos képfeltöltés kezelése.
 * - méretkorlát
 * - valódi kép-ellenőrzés (getimagesize), nem csak kiterjesztés
 * - csak JPG/PNG/WebP/GIF
 * - véletlen fájlnév, kényszerített kép-kiterjesztés (nincs .php feltöltés)
 */
function save_uploaded_image(array $file, $maxBytes = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return array('ok' => false, 'error' => 'Hibás feltöltési kérés.');
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return array('ok' => false, 'error' => 'A kép túl nagy.');
        case UPLOAD_ERR_NO_FILE:
            return array('ok' => false, 'error' => 'Nincs kiválasztott fájl.');
        default:
            return array('ok' => false, 'error' => 'Feltöltési hiba.');
    }
    if ($file['size'] > $maxBytes) {
        return array('ok' => false, 'error' => 'A kép túl nagy (max ' . round($maxBytes / 1048576) . ' MB).');
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return array('ok' => false, 'error' => 'A fájl nem érvényes kép.');
    }
    $map = array(
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    );
    $mime = isset($info['mime']) ? strtolower($info['mime']) : '';
    if (!isset($map[$mime])) {
        return array('ok' => false, 'error' => 'Csak JPG, PNG, WebP vagy GIF tölthető fel.');
    }

    $name = date('Ymd') . '-' . bin2hex(random_bytes(8)) . '.' . $map[$mime];
    $dest = UPLOADS_DIR . '/' . $name;

    $moved = is_uploaded_file($file['tmp_name'])
        ? move_uploaded_file($file['tmp_name'], $dest)
        : false;
    if (!$moved) {
        return array('ok' => false, 'error' => 'Nem sikerült menteni a képet.');
    }
    @chmod($dest, 0644);

    return array('ok' => true, 'url' => UPLOADS_URL . '/' . $name);
}
