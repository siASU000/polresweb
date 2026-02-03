<?php
// admin/profile_upload.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

require __DIR__ . "/db_connection.php";

$adminId = (int)$_SESSION['admin_id'];

if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'File foto tidak valid']);
  exit();
}

$allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
$maxSize = 2 * 1024 * 1024; // 2MB

$tmpName = $_FILES['foto']['tmp_name'];
$origName = $_FILES['foto']['name'];
$size = (int)$_FILES['foto']['size'];

$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Format foto harus jpg/jpeg/png/webp']);
  exit();
}

if ($size > $maxSize) {
  http_response_code(400);
  echo json_encode(['status' => 'error', 'message' => 'Ukuran foto maksimal 2MB']);
  exit();
}

$baseDir = realpath(__DIR__ . "/../uploads");
if ($baseDir === false) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Folder uploads tidak ditemukan']);
  exit();
}

$targetDir = $baseDir . DIRECTORY_SEPARATOR . "admin";
if (!is_dir($targetDir)) {
  @mkdir($targetDir, 0777, true);
}

$newName = "admin_" . $adminId . "_" . time() . "." . $ext;
$targetPath = $targetDir . DIRECTORY_SEPARATOR . $newName;

if (!move_uploaded_file($tmpName, $targetPath)) {
  http_response_code(500);
  echo json_encode(['status' => 'error', 'message' => 'Gagal menyimpan foto']);
  exit();
}

// path yang disimpan ke DB (relative dari project)
$dbPath = "uploads/admin/" . $newName;

$stmt = $conn->prepare("UPDATE admin SET foto = ? WHERE id = ?");
$stmt->bind_param("si", $dbPath, $adminId);
$stmt->execute();

echo json_encode([
  'status' => 'success',
  'message' => 'Foto tersimpan',
  'foto' => $dbPath
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
