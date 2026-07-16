<?php
/**
 * Publikus időpontfoglalás API (JSON).
 *   GET  ?action=slots&date=YYYY-MM-DD  → az adott nap szabad idősávjai + friss token
 *   GET  ?action=config                 → naptár-korlátok, szolgáltatások, token
 *   POST (foglalás beküldése)           → validálás, mentés, e-mail értesítés
 *
 * Nincs bejelentkezés. Védelem: munkamenet-token, honeypot mező, IP-korlát.
 */
define('APP', true);
require_once __DIR__ . '/inc/bootstrap.php';
require_once INC_DIR . '/auth.php';   // secure_session_start()
require_once INC_DIR . '/bookings.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function bk_json($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* Külön, foglaláshoz tartozó munkamenet-token (nem ütközik az admin CSRF-fel). */
function bk_token() {
    secure_session_start();
    if (empty($_SESSION['bk_csrf'])) $_SESSION['bk_csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['bk_csrf'];
}
function bk_token_ok($t) {
    secure_session_start();
    return !empty($_SESSION['bk_csrf']) && is_string($t) && hash_equals($_SESSION['bk_csrf'], $t);
}

$method = $_SERVER['REQUEST_METHOD'];

/* ── GET ──────────────────────────────────────────────────────── */
if ($method === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'config';

    if ($action === 'slots') {
        $date = isset($_GET['date']) ? $_GET['date'] : '';
        if (!booking_is_valid_date($date)) {
            bk_json(array('ok' => false, 'error' => 'Érvénytelen dátum.', 'slots' => array(), 'token' => bk_token()));
        }
        bk_json(array('ok' => true, 'date' => $date, 'slots' => booking_slots_for_date($date), 'token' => bk_token()));
    }

    // alapértelmezés: config
    bk_json(array(
        'ok'       => true,
        'minDate'  => booking_min_date(),
        'maxDate'  => booking_max_date(),
        'workDays' => array_keys(booking_slot_map()), // 1=hétfő … 6=szombat
        'services' => booking_services(),
        'token'    => bk_token(),
    ));
}

/* ── POST (foglalás) ──────────────────────────────────────────── */
if ($method === 'POST') {
    // Honeypot: rejtett mező, amit csak botok töltenek ki.
    if (trim((string)($_POST['company'] ?? '')) !== '') {
        bk_json(array('ok' => true)); // csendben elnyeljük
    }

    if (!bk_token_ok($_POST['token'] ?? '')) {
        bk_json(array('ok' => false, 'error' => 'Lejárt munkamenet. Töltse újra az oldalt.'), 400);
    }

    if (!booking_throttle_ok()) {
        bk_json(array('ok' => false, 'error' => 'Túl sok foglalási kísérlet. Kérjük, próbálja később, vagy hívjon telefonon.'), 429);
    }

    $date    = trim((string)($_POST['date'] ?? ''));
    $time    = trim((string)($_POST['time'] ?? ''));
    $service = trim((string)($_POST['service'] ?? ''));
    $name    = trim((string)($_POST['name'] ?? ''));
    $phone   = trim((string)($_POST['phone'] ?? ''));
    $email   = trim((string)($_POST['email'] ?? ''));
    $address = trim((string)($_POST['address'] ?? ''));
    $note    = trim((string)($_POST['note'] ?? ''));

    $services = booking_services();
    if (!isset($services[$service])) $service = 'karbantartas';

    if ($name === '' || $phone === '') {
        bk_json(array('ok' => false, 'error' => 'Kérjük, adja meg a nevét és telefonszámát.'), 422);
    }
    if (!booking_is_valid_date($date)) {
        bk_json(array('ok' => false, 'error' => 'Válasszon érvényes napot.'), 422);
    }
    if (!booking_slot_is_free($date, $time)) {
        bk_json(array('ok' => false, 'error' => 'Ez az időpont időközben elkelt. Kérjük, válasszon másikat.'), 409);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        bk_json(array('ok' => false, 'error' => 'Az e-mail cím formátuma nem megfelelő.'), 422);
    }

    // Hossz-korlátok (tárolás védelme)
    $name    = booking_cut($name, 120);
    $phone   = booking_cut($phone, 40);
    $email   = booking_cut($email, 160);
    $address = booking_cut($address, 200);
    $note    = booking_cut($note, 1000);

    $booking = array(
        'id'      => new_booking_id(),
        'created' => date('c'),
        'date'    => $date,
        'time'    => $time,
        'service' => $service,
        'name'    => $name,
        'phone'   => $phone,
        'email'   => $email,
        'address' => $address,
        'note'    => $note,
        'status'  => 'new',
        'ip'      => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
    );

    $result = booking_create($booking);
    if ($result === 'taken') {
        bk_json(array('ok' => false, 'error' => 'Ez az időpont időközben elkelt. Kérjük, válasszon másikat.'), 409);
    }
    if ($result !== true) {
        bk_json(array('ok' => false, 'error' => 'Nem sikerült menteni a foglalást. Kérjük, hívjon telefonon.'), 500);
    }

    booking_notify_owner($booking); // e-mail hiba esetén sem buktatjuk el a foglalást

    bk_json(array(
        'ok'      => true,
        'message' => 'Foglalását rögzítettük. Hamarosan telefonon visszaigazoljuk.',
        'date'    => $date,
        'time'    => $time,
        'service' => $services[$service],
    ));
}

bk_json(array('ok' => false, 'error' => 'Nem támogatott kérés.'), 405);
