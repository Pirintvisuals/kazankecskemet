<?php
/**
 * Közös bootstrap: útvonalak, tárolómappák, segédfüggvények betöltése.
 * Minden belépési pont (blog.php, post.php, admin/*) ezt tölti be először.
 */
if (!defined('APP')) define('APP', true);

define('ROOT_DIR', dirname(__DIR__));
define('INC_DIR', __DIR__);
define('CONTENT_DIR', ROOT_DIR . '/content');
define('POSTS_DIR', CONTENT_DIR . '/posts');
define('UPLOADS_DIR', ROOT_DIR . '/uploads');
define('UPLOADS_URL', 'uploads'); // nyilvános URL-előtag a képekhez

date_default_timezone_set('Europe/Budapest');

// Tárolómappák biztosítása
foreach (array(CONTENT_DIR, POSTS_DIR, UPLOADS_DIR) as $d) {
    if (!is_dir($d)) { @mkdir($d, 0775, true); }
}

// Konfiguráció (admin/setup.php hozza létre az első indításkor)
define('CONFIG_FILE', INC_DIR . '/config.php');
define('IS_CONFIGURED', is_file(CONFIG_FILE));
if (IS_CONFIGURED) { require_once CONFIG_FILE; }

require_once INC_DIR . '/helpers.php';
require_once INC_DIR . '/sanitize.php';
require_once INC_DIR . '/posts.php';
