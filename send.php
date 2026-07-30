<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit;
}
header('Content-Type: application/json; charset=utf-8');

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$email   = trim($_POST['email']   ?? '');
$service = trim($_POST['service'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $phone === '' || $message === '') {
    echo json_encode(['ok' => false, 'error' => 'Hiányzó mezők']); exit;
}

$to      = 'info@kazanszervizkecskemet.hu';
$subject = 'Kapcsolatfelvétel – ' . ($service ?: 'Kazán szerviz') . ' (' . $name . ')';

$body  = "Név: $name\n";
$body .= "Telefon: $phone\n";
$body .= "E-mail: $email\n";
$body .= "Szolgáltatás: " . ($service ?: '–') . "\n\n";
$body .= "Üzenet:\n$message\n";

$headers  = "From: noreply@kazanszervizkecskemet.hu\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

echo json_encode(['ok' => $sent]);
