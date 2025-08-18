<?php
declare(strict_types=1);

// ------------ KONFIG ------------
$toEmail   = 'coding.account@gmx.de';      // wohin die Anfrage geht
$toName    = 'Test GMX';
$fromEmail = 'coding.account@gmx.de';      // GMX verlangt, dass Absender = Konto ist
$sitename  = 'Deine Website';

// GMX SMTP
$smtp = [
  'host'   => 'mail.gmx.net',
  'user'   => 'coding.account@gmx.de',
  'pass'   => 'Witchesgm.89',
  'port'   => 587,
  'secure' => 'tls',
];

// ------------ HELPERS ------------
function respond(int $code, string $message): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=UTF-8');
  echo json_encode(['ok' => $code < 400, 'message' => $message], JSON_UNESCAPED_UNICODE);
  exit;
}
function clean(string $v): string {
  return str_replace(["\r","\n"], ' ', trim($v));
}

// ------------ VALIDIERUNG ------------
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') respond(405, 'Methode nicht erlaubt.');
if (!empty($_POST['website'] ?? '')) respond(200, 'Danke!'); // Honeypot

$name    = clean($_POST['name'] ?? '');
$email   = clean($_POST['email'] ?? '');
$phone   = clean($_POST['phone'] ?? '');
$message = trim($_POST['message'] ?? '');
$consent = ($_POST['consent'] ?? '') === '1';

$errors = [];
if (mb_strlen($name) < 2)                       $errors[] = 'Name ist zu kurz.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-Mail ist ungültig.';
if (mb_strlen($message) < 5)                    $errors[] = 'Nachricht ist zu kurz.';
if (!$consent)                                   $errors[] = 'Bitte den Datenschutz bestätigen.';
if ($errors) respond(422, implode(' ', $errors));

// ------------ MAIL TEXT ------------
$subject  = 'Neue Kontaktanfrage – ' . $sitename;
$bodyText = "Neue Kontaktanfrage\n\n"
          . "Name:    {$name}\n"
          . "E-Mail:  {$email}\n"
          . "Telefon: " . ($phone !== '' ? $phone : '—') . "\n\n"
          . "Nachricht:\n{$message}\n\n"
          . "Zeit: " . date('Y-m-d H:i:s') . "\n"
          . "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'n/a');
$bodyHtml = nl2br(htmlspecialchars($bodyText, ENT_QUOTES, 'UTF-8'));

// ------------ VERSAND MIT PHPMailer ------------
require __DIR__ . '/vendor/autoload.php';

$sent = false;
$error = null;

try {
  $mail = new PHPMailer\PHPMailer\PHPMailer(true);
  $mail->isSMTP();
  $mail->Host       = $smtp['host'];
  $mail->SMTPAuth   = true;
  $mail->Username   = $smtp['user'];
  $mail->Password   = $smtp['pass'];
  $mail->SMTPSecure = $smtp['secure'];
  $mail->Port       = $smtp['port'];

  $mail->CharSet = 'UTF-8';
  $mail->setFrom($fromEmail, $sitename);
  $mail->addAddress($toEmail, $toName);
  $mail->addReplyTo($email, $name);

  $mail->Subject = $subject;
  $mail->isHTML(true);
  $mail->Body    = $bodyHtml;
  $mail->AltBody = $bodyText;

  $mail->send();
  $sent = true;
} catch (Throwable $e) {
  $error = $e->getMessage();
}

// ------------ ANTWORT ------------
if ($sent) {
  respond(200, 'Danke! Deine Nachricht wurde erfolgreich versendet.');
} else {
  respond(500, 'Versand fehlgeschlagen: ' . ($error ?: 'Unbekannter Fehler'));
}
