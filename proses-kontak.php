<?php
require __DIR__ . '/db_connection.php';

$name    = trim($_POST['nama'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subjek'] ?? '');
$message = trim($_POST['pesan'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $message === '') {
  header("Location: hubungi-kami.php?status=error");
  exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header("Location: hubungi-kami.php?status=invalid_email");
  exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? null;
$ua = $_SERVER['HTTP_USER_AGENT'] ?? null;

$stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent)
                        VALUES (?,?,?,?,?,?)");
$stmt->bind_param("ssssss", $name, $email, $subject, $message, $ip, $ua);
$stmt->execute();
$stmt->close();

header("Location: hubungi-kami.php?status=sent");
exit;
