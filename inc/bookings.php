<?php
if (!defined('APP')) exit;

/**
 * Időpontfoglalás – adatbázis nélküli, flat-file tárolás.
 * Minden foglalás egy JSON fájl a content/bookings/ mappában, <id>.json néven.
 * A publikus book.php ír ide, az admin/bookings.php olvassa/kezeli.
 */

define('BOOKINGS_DIR', CONTENT_DIR . '/bookings');
if (!is_dir(BOOKINGS_DIR)) { @mkdir(BOOKINGS_DIR, 0775, true); }

/* A mappa web-hozzáférés elleni védelme (PII: név, telefon, cím). */
$__bk_ht = BOOKINGS_DIR . '/.htaccess';
if (!is_file($__bk_ht)) {
    @file_put_contents($__bk_ht, "Require all denied\n<IfModule !mod_authz_core.c>\nOrder allow,deny\nDeny from all\n</IfModule>\n");
}

/* ── Beállítások ──────────────────────────────────────────────── */

/** Szolgáltatások, amikre foglalni lehet (érték => megjelenített név). */
function booking_services() {
    return array(
        'karbantartas' => 'Kazán karbantartás',
        'javitas'      => 'Kazán javítás',
        'csere'        => 'Kazáncsere',
    );
}

/** Foglalható idősávok hét napja szerint (1=hétfő … 7=vasárnap). */
function booking_slot_map() {
    $week = array('08:00', '10:00', '12:00', '14:00', '16:00');
    $sat  = array('08:00', '10:00', '12:00');
    return array(1 => $week, 2 => $week, 3 => $week, 4 => $week, 5 => $week, 6 => $sat);
}

define('BOOKING_LEAD_DAYS', 1);   // legkorábban ennyi nappal előre lehet foglalni
define('BOOKING_WINDOW_DAYS', 42); // ennyi napra előre nyitott a naptár
define('BOOKING_SLOT_HOURS', 2);   // egy idősáv hossza órában (naptárbejegyzéshez)

function booking_min_date() { return date('Y-m-d', strtotime('+' . BOOKING_LEAD_DAYS . ' day')); }
function booking_max_date() { return date('Y-m-d', strtotime('+' . BOOKING_WINDOW_DAYS . ' day')); }

/* ── Tárolás ──────────────────────────────────────────────────── */

/** Biztonságos hossz-vágás (mbstring nélküli hosztokon is működik). */
function booking_cut($s, $n) {
    $s = (string)$s;
    if (function_exists('mb_substr')) return mb_substr($s, 0, $n, 'UTF-8');
    return substr($s, 0, $n);
}

function new_booking_id() {
    return date('YmdHis') . '-' . bin2hex(random_bytes(3));
}

function valid_booking_id($id) {
    return is_string($id) && preg_match('/^[0-9]{14}-[0-9a-f]{6}$/', $id) === 1;
}

function booking_path($id) {
    if (!valid_booking_id($id)) return null;
    return BOOKINGS_DIR . '/' . $id . '.json';
}

/** Összes foglalás, legkorábbi időpont elöl. */
function bookings_all() {
    $out = array();
    foreach (glob(BOOKINGS_DIR . '/*.json') as $f) {
        $raw = @file_get_contents($f);
        if ($raw === false) continue;
        $d = json_decode($raw, true);
        if (is_array($d) && isset($d['id'])) $out[] = $d;
    }
    usort($out, function ($a, $b) {
        return strcmp($a['date'] . $a['time'], $b['date'] . $b['time']);
    });
    return $out;
}

function booking_get($id) {
    $path = booking_path($id);
    if (!$path || !is_file($path)) return null;
    $d = json_decode((string)file_get_contents($path), true);
    return is_array($d) ? $d : null;
}

function booking_save(array $b) {
    if (!valid_booking_id($b['id'])) return false;
    $json = json_encode($b, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    return file_put_contents(booking_path($b['id']), $json, LOCK_EX) !== false;
}

function booking_delete($id) {
    $path = booking_path($id);
    if ($path && is_file($path)) return unlink($path);
    return false;
}

/**
 * Foglalás létrehozása versenyhelyzet-biztosan (zárolt szabad-ellenőrzés + mentés).
 * Így két egyidejű foglalás sem viheti el ugyanazt az idősávot.
 * Visszaad: true | 'taken' | 'error'.
 */
function booking_create(array $booking) {
    $fp = @fopen(CONTENT_DIR . '/.booking.lock', 'c');
    if ($fp) flock($fp, LOCK_EX);
    clearstatcache();
    if (!booking_slot_is_free($booking['date'], $booking['time'])) {
        if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
        return 'taken';
    }
    $ok = booking_save($booking);
    if ($fp) { flock($fp, LOCK_UN); fclose($fp); }
    return $ok ? true : 'error';
}

/** Egy foglalás státusza. Az 'aktív' (new/confirmed) foglalás foglalja az idősávot. */
function booking_set_status($id, $status) {
    $b = booking_get($id);
    if (!$b) return false;
    if (!in_array($status, array('new', 'confirmed', 'cancelled'), true)) return false;
    $b['status'] = $status;
    return booking_save($b);
}

/* ── Idősáv-logika ────────────────────────────────────────────── */

function booking_is_valid_date($date) {
    if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) return false;
    $ts = strtotime($date);
    if ($ts === false) return false;
    if ($date < booking_min_date() || $date > booking_max_date()) return false;
    $dow = (int)date('N', $ts);
    $map = booking_slot_map();
    return isset($map[$dow]);
}

/** Egy adott napra tartozó aktív foglalások (idősáv-foglaltsághoz). */
function bookings_active_for_date($date) {
    $out = array();
    foreach (bookings_all() as $b) {
        if ($b['date'] === $date && $b['status'] !== 'cancelled') $out[] = $b;
    }
    return $out;
}

/**
 * Egy nap idősávjai foglaltsági jelöléssel.
 * Visszaad: [ ['time'=>'08:00','available'=>true], ... ]  vagy [] ha a nap nem foglalható.
 */
function booking_slots_for_date($date) {
    if (!booking_is_valid_date($date)) return array();
    $dow = (int)date('N', strtotime($date));
    $map = booking_slot_map();
    $all = isset($map[$dow]) ? $map[$dow] : array();

    $taken = array();
    foreach (bookings_active_for_date($date) as $b) $taken[$b['time']] = true;

    $out = array();
    foreach ($all as $t) {
        $out[] = array('time' => $t, 'available' => !isset($taken[$t]));
    }
    return $out;
}

function booking_slot_is_free($date, $time) {
    foreach (booking_slots_for_date($date) as $s) {
        if ($s['time'] === $time) return $s['available'];
    }
    return false;
}

/* ── Egyszerű spam-korlátozás IP alapján ──────────────────────── */
define('BOOKING_THROTTLE_FILE', CONTENT_DIR . '/.booking_throttle.json');
define('BOOKING_THROTTLE_MAX', 5);       // max foglalás
define('BOOKING_THROTTLE_WINDOW', 3600); // 1 óra alatt

function booking_throttle_ok() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
    $now = time();
    $data = array();
    if (is_file(BOOKING_THROTTLE_FILE)) {
        $d = json_decode((string)file_get_contents(BOOKING_THROTTLE_FILE), true);
        if (is_array($d)) $data = $d;
    }
    // takarítás
    foreach ($data as $k => $v) {
        if (($now - $v['first']) >= BOOKING_THROTTLE_WINDOW) unset($data[$k]);
    }
    $count = isset($data[$ip]) ? $data[$ip]['count'] : 0;
    if ($count >= BOOKING_THROTTLE_MAX) {
        file_put_contents(BOOKING_THROTTLE_FILE, json_encode($data), LOCK_EX);
        return false;
    }
    if (!isset($data[$ip])) $data[$ip] = array('count' => 1, 'first' => $now);
    else $data[$ip]['count']++;
    file_put_contents(BOOKING_THROTTLE_FILE, json_encode($data), LOCK_EX);
    return true;
}

/* ── .ics naptárfájl + értesítő e-mail ────────────────────────── */

/** iCalendar (.ics) tartalom egy foglaláshoz – egy koppintással hozzáadható bármely naptárhoz. */
function booking_ics(array $b) {
    $services = booking_services();
    $svc = isset($services[$b['service']]) ? $services[$b['service']] : 'Kazán szerviz';

    $start = new DateTime($b['date'] . ' ' . $b['time'], new DateTimeZone('Europe/Budapest'));
    $end   = clone $start;
    $end->modify('+' . BOOKING_SLOT_HOURS . ' hours');
    $utc = new DateTimeZone('UTC');
    $start->setTimezone($utc);
    $end->setTimezone($utc);

    $fmt = function (DateTime $d) { return $d->format('Ymd\THis\Z'); };
    $esc = function ($s) {
        return addcslashes(str_replace(array("\r\n", "\n", "\r"), '\\n', (string)$s), ",;\\");
    };

    $desc = 'Ügyfél: ' . $b['name']
          . ' | Tel: ' . $b['phone']
          . ($b['email'] !== '' ? ' | E-mail: ' . $b['email'] : '')
          . ($b['note']  !== '' ? ' | Megjegyzés: ' . $b['note'] : '');

    $lines = array(
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Kazan Szerviz Kecskemet//Foglalas//HU',
        'CALSCALE:GREGORIAN',
        'METHOD:PUBLISH',
        'BEGIN:VEVENT',
        'UID:' . $b['id'] . '@gazszerelokecskemet.hu',
        'DTSTAMP:' . $fmt(new DateTime('now', $utc)),
        'DTSTART:' . $fmt($start),
        'DTEND:' . $fmt($end),
        'SUMMARY:' . $esc($svc . ' – ' . $b['name']),
        'DESCRIPTION:' . $esc($desc),
        'LOCATION:' . $esc($b['address']),
        'STATUS:CONFIRMED',
        'END:VEVENT',
        'END:VCALENDAR',
    );
    return implode("\r\n", $lines) . "\r\n";
}

/** Értesítő e-mail a tulajdonosnak, .ics melléklettel (egy koppintás = naptárba). */
function booking_notify_owner(array $b) {
    $to = 'info@kazanszervizkecskemet.hu';
    $services = booking_services();
    $svc = isset($services[$b['service']]) ? $services[$b['service']] : 'Kazán szerviz';

    $subject = 'Új foglalás: ' . $svc . ' – ' . hu_date($b['date']) . ' ' . $b['time'] . ' (' . $b['name'] . ')';

    $body  = "Új online időpontfoglalás érkezett.\n\n";
    $body .= "Szolgáltatás: $svc\n";
    $body .= "Időpont: " . hu_date($b['date']) . " $b[time]\n";
    $body .= "Név: $b[name]\n";
    $body .= "Telefon: $b[phone]\n";
    $body .= "E-mail: " . ($b['email'] !== '' ? $b['email'] : '–') . "\n";
    $body .= "Cím: " . ($b['address'] !== '' ? $b['address'] : '–') . "\n";
    $body .= "Megjegyzés: " . ($b['note'] !== '' ? $b['note'] : '–') . "\n\n";
    $body .= "A csatolt .ics fájllal egy koppintással felveheti a naptárába.\n";
    $body .= "Kezelés (visszaigazolás/lemondás): admin/bookings.php\n";

    $ics = booking_ics($b);
    $boundary = 'bk_' . bin2hex(random_bytes(8));

    $headers  = "From: noreply@kazanszervizkecskemet.hu\r\n";
    if ($b['email'] !== '') $headers .= "Reply-To: " . $b['email'] . "\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n";

    $msg  = "--$boundary\r\n";
    $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
    $msg .= $body . "\r\n";
    $msg .= "--$boundary\r\n";
    $msg .= "Content-Type: text/calendar; charset=UTF-8; method=PUBLISH; name=\"foglalas.ics\"\r\n";
    $msg .= "Content-Transfer-Encoding: base64\r\n";
    $msg .= "Content-Disposition: attachment; filename=\"foglalas.ics\"\r\n\r\n";
    $msg .= chunk_split(base64_encode($ics)) . "\r\n";
    $msg .= "--$boundary--";

    return @mail($to, $subject, $msg, $headers);
}
