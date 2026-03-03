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
$nama = $_POST['nama'] ?? '';
$email = $_POST['email'] ?? '';
$subjek = $_POST['subjek'] ?? '';
$pesan = $_POST['pesan'] ?? '';

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
    $mail->Host = 'smtp-relay.brevo.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'a3b5e2001@smtp-brevo.com'; // Brevo login
    $mail->Password = 'KwM1qB4AT2h7FRdj'; // Brevo password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Pengirim dan penerima harus rafifn02@gmail.com karena SMTP relay
    $mail->setFrom('andribee9204@gmail.com', 'Laporan Warga Sumatera Barat');
    $mail->addAddress('andribee9204@gmail.com'); // Kirim notifikasi ke email admin

    // === TAMBAHAN PENTING: AGAR ADMIN BISA LANGSUNG BALAS KE PENGIRIM ===
    // Parameter: email pengirim dari form, nama pengirim dari form
    $mail->addReplyTo($email, $nama);

    $mail->isHTML(true);
    $mail->Subject = "Pesan Baru: $subjek";

    // Agar pesan lebih rapi, saya sarankan pakai nl2br di pesan
    $safeNama = htmlspecialchars($nama, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safePesan = nl2br(htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8'));

    // Desain Email HTML Profesional
    $mail->Body = "
    <!DOCTYPE html>
    <html lang='id'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                background-color: #f4f7fa;
                margin: 0;
                padding: 0;
                color: #333333;
            }
            .email-container {
                max-width: 600px;
                margin: 40px auto;
                background-color: #ffffff;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            }
            .email-header {
                background-color: #1a56db;
                color: #ffffff;
                padding: 30px 20px;
                text-align: center;
            }
            .email-header h1 {
                margin: 0;
                font-size: 24px;
                font-weight: 600;
                letter-spacing: 0.5px;
            }
            .email-body {
                padding: 30px;
                background-color: #ffffff;
            }
            .email-body p {
                font-size: 15px;
                line-height: 1.6;
                margin-bottom: 25px;
                color: #555555;
            }
            .info-box {
                background-color: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 6px;
                padding: 20px;
                margin-bottom: 25px;
            }
            .info-row {
                margin-bottom: 12px;
                display: flex;
            }
            .info-row:last-child {
                margin-bottom: 0;
            }
            .info-label {
                font-weight: 600;
                color: #475569;
                width: 100px;
                display: inline-block;
            }
            .info-value {
                color: #1e293b;
                display: inline-block;
                width: calc(100% - 110px);
            }
            .message-box {
                background-color: #ffffff;
                border-left: 4px solid #f1c40f;
                padding: 15px 20px;
                font-style: italic;
                color: #444444;
                margin-bottom: 25px;
                line-height: 1.6;
            }
            .email-footer {
                background-color: #f8fafc;
                padding: 20px;
                text-align: center;
                font-size: 13px;
                color: #64748b;
                border-top: 1px solid #e2e8f0;
            }
            .action-button {
                display: inline-block;
                background-color: #1a56db;
                color: #ffffff;
                text-decoration: none;
                padding: 12px 25px;
                border-radius: 4px;
                font-weight: 600;
                font-size: 14px;
                margin-top: 10px;
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='email-header'>
                <h1>Laporan / Keluhan Baru</h1>
            </div>
            <div class='email-body'>
                <p>Halo Admin,</p>
                <p>Anda menerima pesan baru dari form Hubungi Kami di website. Berikut adalah detail informasi pengirim:</p>
                
                <div class='info-box'>
                    <div class='info-row'>
                        <span class='info-label'>Nama</span>
                        <span class='info-value'>: $safeNama</span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Email</span>
                        <span class='info-value'>: <a href='mailto:$safeEmail' style='color: #1a56db; text-decoration: none;'>$safeEmail</a></span>
                    </div>
                    <div class='info-row'>
                        <span class='info-label'>Subjek</span>
                        <span class='info-value'>: <strong>$subjek</strong></span>
                    </div>
                </div>

                <p style='margin-bottom: 10px; font-weight: 600; color: #333;'>Isi Pesan:</p>
                <div class='message-box'>
                    $safePesan
                </div>

                <p style='text-align: center; margin-top: 40px;'>
                    <a href='mailto:$safeEmail?subject=RE: $subjek' class='action-button' style='color: #ffffff;'>Balas Pesan Ini</a>
                </p>
            </div>
            <div class='email-footer'>
                <p>Pesan ini dikirim secara otomatis dari sistem website Sumatera Barat.</p>
                <p>&copy; " . date('Y') . " Baznas Sumatera Barat. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $mail->send();

    // Jika semua sukses, redirect ke halaman form dengan status sent
    header("Location: hubungi-kami.php?status=sent");
    exit; // pastikan exit setelah header redirect
} catch (Exception $e) {
    // Jika email gagal tapi database sudah masuk
    die("KATA EMAIL: Data SUDAH MASUK DATABASE, tapi email GAGAL terkirim. Error: " . $mail->ErrorInfo);
}
?>