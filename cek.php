<?php
// File: cek.php
require 'admin/db_connection.php';

echo "<h3>1. Cek Koneksi Database</h3>";
if ($conn) {
    echo "✅ Koneksi Berhasil!<br>";
} else {
    die("❌ Koneksi Gagal.");
}

echo "<h3>2. Daftar Nama Kolom di Tabel 'berita'</h3>";
$result = $conn->query("SHOW COLUMNS FROM berita");

if ($result) {
    echo "<table border='1' cellpadding='5'><tr><th>Nama Kolom (Field)</th><th>Tipe Data</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><b>" . $row['Field'] . "</b></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Gagal mengambil kolom. Pesan Error: " . $conn->error;
}

echo "<h3>3. Intip Isi Data (5 Teratas)</h3>";
$data = $conn->query("SELECT * FROM berita LIMIT 5");
if ($data) {
    while($r = $data->fetch_assoc()) {
        echo "<pre>";
        print_r($r);
        echo "</pre><hr>";
    }
} else {
    echo "Data kosong atau query gagal.";
}
?>