<?php
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
  exit;
}

$userId  = $_SESSION['admin_id'] ?? null;
if (!$userId) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit;
}

// Ambil field sesuai dashboard.php (name attribute)
$nama    = trim($_POST['nama'] ?? '');
$nrp     = trim($_POST['nrp'] ?? '');
$email   = trim($_POST['email'] ?? '');
$no_hp   = trim($_POST['no_hp'] ?? '');
$jabatan = trim($_POST['jabatan'] ?? '');
$alamat  = trim($_POST['alamat'] ?? '');

// Validasi angka: NRP & No HP hanya digit
if ($nrp !== '' && !ctype_digit($nrp)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'NRP harus angka saja.']);
  exit;
}
if ($no_hp !== '' && !ctype_digit($no_hp)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Nomor HP harus angka saja.']);
  exit;
}

// Update tabel admin (sesuaikan nama kolom dengan tabel Anda)
$sql = "UPDATE admin
        SET nama = ?, nrp = ?, email = ?, no_hp = ?, jabatan = ?, alamat = ?
        WHERE id = ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Prepare statement gagal.']);
  exit;
}

$stmt->bind_param("ssssssi", $nama, $nrp, $email, $no_hp, $jabatan, $alamat, $userId);

if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan profil.']);
  exit;
}

echo json_encode(['status' => 'success', 'message' => 'Profil tersimpan.']);
