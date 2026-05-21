<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!empty($_POST['website'] ?? '')) {
    echo json_encode(['ok' => true]);
    exit;
}

$first   = trim($_POST['first']   ?? '');
$last    = trim($_POST['last']    ?? '');
$email   = trim($_POST['email']   ?? '');
$company = trim($_POST['company'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

$errors = [];
if ($first === '')                              $errors[] = 'Vorname fehlt';
if ($last === '')                               $errors[] = 'Nachname fehlt';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-Mail ungültig';
if ($message === '')                            $errors[] = 'Nachricht fehlt';
if (mb_strlen($message) > 8000)                 $errors[] = 'Nachricht zu lang';

if ($errors) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => implode(', ', $errors)]);
    exit;
}

foreach ([$first, $last, $email, $company, $subject] as $v) {
    if (preg_match('/[\r\n]/', $v)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ungültige Eingabe']);
        exit;
    }
}

$to          = 'schmidt@vetrexa.com';
$from        = 'schmidt@vetrexa.com';
$senderName  = $first . ' ' . $last;
$mailSubject = '[Website] ' . ($subject !== '' ? $subject : 'Anfrage') . ' – ' . $senderName;

$body  = "Neue Anfrage über das Kontaktformular auf vetrexa.com\n\n";
$body .= "Name:        $senderName\n";
$body .= "E-Mail:      $email\n";
$body .= "Unternehmen: " . ($company !== '' ? $company : '—') . "\n";
$body .= "Betreff:     " . ($subject !== '' ? $subject : '—') . "\n";
$body .= "IP:          " . ($_SERVER['REMOTE_ADDR'] ?? '—') . "\n";
$body .= "Zeit:        " . date('Y-m-d H:i:s') . "\n";
$body .= "\n---\n\n";
$body .= $message . "\n";

$headers   = [];
$headers[] = 'From: Vetrexa Website <' . $from . '>';
$headers[] = 'Reply-To: ' . $senderName . ' <' . $email . '>';
$headers[] = 'X-Mailer: PHP/' . phpversion();
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: text/plain; charset=UTF-8';

$encodedSubject = '=?UTF-8?B?' . base64_encode($mailSubject) . '?=';
$ok = mail($to, $encodedSubject, $body, implode("\r\n", $headers), '-f' . $from);

if (!$ok) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Versand fehlgeschlagen. Bitte schreiben Sie direkt an schmidt@vetrexa.com.']);
    exit;
}

echo json_encode(['ok' => true]);
