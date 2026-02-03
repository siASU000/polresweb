<?php
$conn = new mysqli("localhost", "root", "", "polresta_padang_db");
if ($conn->connect_error) {
  die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>
