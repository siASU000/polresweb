<?php
header('Content-Type: application/json; charset=UTF-8');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'OK']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit();
}

require __DIR__ . '/db_connection.php';

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (is_array($data)) {
    $username = trim($data['username'] ?? '');
    $password = $data['password'] ?? '';
    $role     = trim($data['role'] ?? '');
} else {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? '');
}

if ($username === '' || $password === '' || $role === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit();
}

$allowedRoles = ['admin', 'editor'];
if (!in_array($role, $allowedRoles, true)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid role']);
    exit();
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$query = "SELECT id FROM admin WHERE username = ? LIMIT 1";
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    $stmt->close();
    $conn->close();
    exit();
}
$stmt->close();

$query = "INSERT INTO admin (username, password, role) VALUES (?, ?, ?)";
$stmt = $conn->prepare($query);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error]);
    $conn->close();
    exit();
}

$stmt->bind_param("sss", $username, $hashedPassword, $role);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Account created successfully']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to create account: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
