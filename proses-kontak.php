<?php
// Paksa tampilkan error jika ada masalah server
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/admin/db_connection.php';

// === STEP 1: CEK APAKAH DATA DARI BROWSER MASUK? ===
if (empty($_POST)) {
    die("KATA PHP: SAYA TIDAK MENERIMA DATA APAPUN. Masalah ada di Browser Anda. Solusi: Tekan CTRL+F5 di halaman form.");
}

// === STEP 2: AMBIL DATA DARI FORM ===
$nama    = $_POST['nama'] ?? '';
$email   = $_POST['email'] ?? '';
$subjek  = $_POST['subjek'] ?? '';
$pesan   = $_POST['pesan'] ?? '';

// Hapus var_dump dan die untuk melanjutkan proses

// === STEP 3: SIMPAN KE DATABASE ===
$stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nama, $email, $subjek, $pesan);

if (!$stmt->execute()) {
    die("KATA DATABASE: Gagal simpan data. Error: " . $conn->error);
}

// === STEP 4: KIRIM EMAIL ===
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'andribee9204@gmail.com';
    $mail->Password   = 'sjkr msav wjfw obsc'; // Pastikan app password valid
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Pengirim dan penerima
    $mail->setFrom('andribee9204@gmail.com', 'Notifikasi Polresta');
    $mail->addAddress('andribee9204@gmail.com'); // Kirim email ke alamat ini

    $mail->isHTML(true);
    $mail->Subject = "Pesan Baru: $subjek";

    // Agar pesan lebih rapi, saya sarankan pakai nl2br di pesan
    $safeNama = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePesan = nl2br(htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    $mail->Body    = "Nama: $safeNama <br> Email: $safeEmail <br> Pesan: $safePesan";

    $mail->send();

    // Jika semua sukses, redirect ke halaman form dengan status sent
    header("Location: hubungi-kami.php?status=sent");
    exit; // pastikan exit setelah header redirect
} catch (Exception $e) {
    // Jika email gagal tapi database sudah masuk
    die("KATA EMAIL: Data SUDAH MASUK DATABASE, tapi email GAGAL terkirim. Error: " . $mail->ErrorInfo);
}
?>