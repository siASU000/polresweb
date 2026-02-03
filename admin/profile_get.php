<?php
// admin/profile_get.php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['admin_id'])) {
  http_response_code(401);
  echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
  exit();
}

require __DIR__ . "/db_connection.php";

$adminId = (int)$_SESSION['admin_id'];

$stmt = $conn->prepare("SELECT id, username, role, nama, nrp, email, alamat, no_hp, jabatan, foto FROM admin WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

echo json_encode([
  'status' => 'success',
  'data' => $row
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$conn->close();
