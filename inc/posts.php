<?php
if (!defined('APP')) exit;

/**
 * Flat-file (adatbázis nélküli) bejegyzés-tárolás.
 * Minden cikk egy JSON fájl a content/posts/ mappában, <id>.json néven.
 * Az id formátuma: YYYYMMDDHHMMSS-xxxxxx (idő + véletlen) — útvonal-manipuláció ellen validált.
 */

/** Új, egyedi bejegyzés-azonosító. */
function new_post_id() {
    return date('YmdHis') . '-' . bin2hex(random_bytes(3));
}

function valid_post_id($id) {
    return is_string($id) && preg_match('/^[0-9]{14}-[0-9a-f]{6}$/', $id) === 1;
}

function post_path($id) {
    if (!valid_post_id($id)) return null;
    return POSTS_DIR . '/' . $id . '.json';
}

/** Összes bejegyzés (vázlat + publikált), legújabb elöl. */
function posts_all() {
    $out = array();
    foreach (glob(POSTS_DIR . '/*.json') as $f) {
        $raw = @file_get_contents($f);
        if ($raw === false) continue;
        $d = json_decode($raw, true);
        if (is_array($d) && isset($d['id'])) $out[] = $d;
    }
    usort($out, function ($a, $b) {
        $ka = !empty($a['published_at']) ? $a['published_at'] : $a['created'];
        $kb = !empty($b['published_at']) ? $b['published_at'] : $b['created'];
        return strcmp($kb, $ka);
    });
    return $out;
}

/** Csak a publikált, már esedékes bejegyzések. */
function posts_published() {
    $now = date('c');
    return array_values(array_filter(posts_all(), function ($p) use ($now) {
        return isset($p['status']) && $p['status'] === 'published'
            && (empty($p['published_at']) || $p['published_at'] <= $now);
    }));
}

function post_get($id) {
    $path = post_path($id);
    if (!$path || !is_file($path)) return null;
    $d = json_decode(file_get_contents($path), true);
    return is_array($d) ? $d : null;
}

function post_get_by_slug($slug) {
    foreach (posts_all() as $p) {
        if (isset($p['slug']) && $p['slug'] === $slug) return $p;
    }
    return null;
}

/** Egyedi slug biztosítása (a megadott id-t kihagyva). */
function unique_slug($slug, $excludeId = null) {
    $base = $slug !== '' ? $slug : 'cikk';
    $slug = $base;
    $i = 2;
    while (true) {
        $existing = post_get_by_slug($slug);
        if (!$existing || ($excludeId && $existing['id'] === $excludeId)) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

/** Bejegyzés mentése (a tömb 'id' mezője alapján). */
function post_save(array $post) {
    if (!valid_post_id($post['id'])) return false;
    $path = post_path($post['id']);
    $json = json_encode($post, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return file_put_contents($path, $json, LOCK_EX) !== false;
}

function post_delete($id) {
    $path = post_path($id);
    if ($path && is_file($path)) return unlink($path);
    return false;
}
