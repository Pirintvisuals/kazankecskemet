<?php
if (!defined('APP')) exit;

/**
 * Általános segédfüggvények: kimenet-escapelés, slug, dátum, olvasási idő.
 */

/** HTML-escape kimenethez (XSS ellen). */
function e($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Ékezetes magyar szöveg -> URL-barát slug. */
function slugify($text) {
    $text = (string)$text;
    $map = array(
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ö'=>'o','ő'=>'o','ú'=>'u','ü'=>'u','ű'=>'u',
        'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ö'=>'o','Ő'=>'o','Ú'=>'u','Ü'=>'u','Ű'=>'u',
    );
    $text = strtr($text, $map);
    // bármi más nem-ASCII jelet is próbáljunk átírni
    if (function_exists('iconv')) {
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($t !== false) $text = $t;
    }
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    if ($text === '') $text = 'cikk';
    return substr($text, 0, 80);
}

/** ISO dátum -> "2026. június 14." */
function hu_date($iso) {
    $ts = strtotime((string)$iso);
    if (!$ts) return '';
    $months = array(1=>'január','február','március','április','május','június',
        'július','augusztus','szeptember','október','november','december');
    return date('Y', $ts) . '. ' . $months[(int)date('n', $ts)] . ' ' . (int)date('j', $ts) . '.';
}

/** ISO dátum -> rövid forma "2026. jún. 14." */
function hu_date_short($iso) {
    $ts = strtotime((string)$iso);
    if (!$ts) return '';
    $m = array(1=>'jan.','febr.','márc.','ápr.','máj.','jún.','júl.','aug.','szept.','okt.','nov.','dec.');
    return date('Y', $ts) . '. ' . $m[(int)date('n', $ts)] . ' ' . (int)date('j', $ts) . '.';
}

/** Becsült olvasási idő HTML tartalomból. */
function reading_time($html) {
    $words = str_word_count(strip_tags((string)$html), 0, 'áéíóöőúüűÁÉÍÓÖŐÚÜŰ');
    $min = (int)ceil($words / 180);
    if ($min < 1) $min = 1;
    return $min;
}

/** Rövid kivonat HTML-ből (ha nincs külön megadva). */
function excerpt_from_html($html, $len = 160) {
    $text = trim(preg_replace('/\s+/', ' ', strip_tags((string)$html)));
    if (mb_strlen($text, 'UTF-8') <= $len) return $text;
    $cut = mb_substr($text, 0, $len, 'UTF-8');
    $sp = mb_strrpos($cut, ' ', 0, 'UTF-8');
    if ($sp !== false) $cut = mb_substr($cut, 0, $sp, 'UTF-8');
    return $cut . '…';
}
