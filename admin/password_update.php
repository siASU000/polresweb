<?php
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  exit;
}

$userId = $_SESSION['admin_id'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

$old = $_POST['old_password'] ?? '';
$new = $_POST['new_password'] ?? '';
$conf = $_POST['confirm_password'] ?? '';

$old = trim($old);
$new = trim($new);
$conf = trim($conf);

if ($old === '' || $new === '' || $conf === '') {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
  exit;
}

if ($new !== $conf) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Konfirmasi password baru tidak sama.']);
  exit;
}

if (strlen($new) < 8) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Password baru minimal 8 karakter.']);
  exit;
}

// Ambil password hash lama dari DB
$stmt = $conn->prepare("SELECT password FROM admin WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
  http_response_code(404);
  echo json_encode(['status' => 'error', 'message' => 'User tidak ditemukan.']);
  exit;
}

$row = $res->fetch_assoc();
$hash = $row['password'] ?? '';

// Verifikasi password lama
if (!password_verify($old, $hash)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Password lama salah.']);
  exit;
}

// Update password baru
$newHash = password_hash($new, PASSWORD_DEFAULT);

$up = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
$up->bind_param("si", $newHash, $userId);

if (!$up->execute()) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal memperbarui password.']);
  exit;
}

echo json_encode(['status' => 'success', 'message' => 'Password berhasil diubah.']);
if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Password baru harus mengandung huruf dan angka.']);
  exit;
}
