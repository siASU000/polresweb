<?php
declare(strict_types=1);

// ===== DB (WAJIB: pakai db_connection.php di ROOT project) =====
require_once __DIR__ . '/admin/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan db_connection.php membuat variabel \$conn (mysqli).");
}
$conn->set_charset("utf8mb4");

// ===== Helpers =====
function e($v): string {
  return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function normalize_wa_to_wa_me(string $whatsapp, string $default = 'https://wa.me/628116693110'): string {
  $wa = trim($whatsapp);
  if ($wa === '') return $default;

  $waNum = preg_replace('/[^0-9]/', '', $wa);
  if ($waNum === '') return $default;

  // kalau mulai 0 -> ubah jadi 62xxxxx
  if (strpos($waNum, '0') === 0) $waNum = '62' . substr($waNum, 1);

  return 'https://wa.me/' . $waNum;
}

/**
 * Terima input:
 * - iframe full dari google maps
 * - URL embed (https://www.google.com/maps?...&output=embed)
 * Keluaran:
 * - iframe yang aman (hanya iframe)
 */
function render_maps_embed(string $mapsEmbed): string {
  $mapsEmbed = trim($mapsEmbed);
  if ($mapsEmbed === '') {
    $mapsEmbed = "https://www.google.com/maps?q=Polresta%20Padang&output=embed";
  }

  // jika input berupa URL, buat iframe
  if (stripos($mapsEmbed, '<iframe') === false) {
    $src = e($mapsEmbed);
    return '<iframe title="Lokasi Polresta Padang" src="' . $src . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
  }

  // input berupa iframe: ambil hanya tag iframe pertama
  if (preg_match('/<iframe\b[^>]*>.*?<\/iframe>/is', $mapsEmbed, $m)) {
    $iframe = $m[0];

    // pastikan ada loading + referrerpolicy (kalau belum)
    if (stripos($iframe, 'loading=') === false) {
      $iframe = preg_replace('/<iframe\b/i', '<iframe loading="lazy"', $iframe, 1);
    }
    if (stripos($iframe, 'referrerpolicy=') === false) {
      $iframe = preg_replace('/<iframe\b/i', '<iframe referrerpolicy="no-referrer-when-downgrade"', $iframe, 1);
    }
    if (stripos($iframe, 'title=') === false) {
      $iframe = preg_replace('/<iframe\b/i', '<iframe title="Lokasi Polresta Padang"', $iframe, 1);
    }

    return $iframe;
  }

  // fallback kalau format aneh
  $src = e("https://www.google.com/maps?q=Polresta%20Padang&output=embed");
  return '<iframe title="Lokasi Polresta Padang" src="' . $src . '" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>';
}

// ===== Load settings from DB =====
$settingsRes = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
$settings = $settingsRes ? $settingsRes->fetch_assoc() : [];

// fallback default kalau DB kosong
$address   = $settings['address']    ?? 'Jl. Moh. Yamin, Belakang Pd., Kec. Padang Bar., Kota Padang, Sumatera Barat 25132';
$phone     = $settings['phone']      ?? '110';
$whatsapp  = $settings['whatsapp']   ?? '+62 811 6693 110';
$email     = $settings['email']      ?? 'polrestapadang0751@gmail.com';
$fax       = $settings['fax']        ?? '(0751) 33724';
$opHours   = $settings['op_hours']   ?? "Senin – Jumat: 08.00 – 16.00 WIB\nSabtu – Minggu: Layanan tertentu sesuai ketentuan";
$mapsEmbed = $settings['maps_embed'] ?? '';

// WA Link auto dari whatsapp
$waLink = normalize_wa_to_wa_me((string)$whatsapp);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hubungi Kami - Polresta Padang</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-hubungi">

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="hubungi-wrap">

  <h1 class="hubungi-title">HUBUNGI <span>KAMI</span></h1>

  <section class="hubungi-top">

    <div class="hubungi-card">
      <div class="hubungi-card-head">INFORMASI KONTAK</div>

      <div class="hubungi-kontak-box">

        <div class="kontak-row">
          <img src="assets/destinasi.png" alt="Destinasi" class="kontak-ico">
          <div class="kontak-content">
            <p class="kontak-label">Alamat</p>
            <p class="kontak-value"><?= nl2br(e($address)) ?></p>
          </div>
        </div>

        <div class="kontak-divider"></div>

        <div class="kontak-row">
          <img src="assets/telepon.png" alt="Telepon" class="kontak-ico">
          <div class="kontak-content">
            <p class="kontak-label">Telepon / WA</p>
            <p class="kontak-value"><?= e($phone) ?> • WA <?= e($whatsapp) ?></p>
            <?php if (!empty($email)): ?>
              <p class="kontak-small">Email: <?= e($email) ?></p>
            <?php endif; ?>
            <?php if (!empty($fax)): ?>
              <p class="kontak-small">Fax: <?= e($fax) ?></p>
            <?php endif; ?>
          </div>
        </div>

        <div class="kontak-divider"></div>

        <div class="kontak-row">
          <img src="assets/jamclock.png" alt="Jam" class="kontak-ico">
          <div class="kontak-content">
            <p class="kontak-label">Jam Operasional</p>
            <p class="kontak-value"><?= nl2br(e($opHours)) ?></p>
          </div>
        </div>

      </div>
    </div>

    <div class="hubungi-map">
      <?= render_maps_embed((string)$mapsEmbed) ?>
    </div>

  </section>

  <section class="hubungi-form-card">
    <div class="hubungi-card-head">KIRIMKAN PESAN PADA KAMI</div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'sent'): ?>
      <p style="margin:10px 0; color:green; font-weight:600;">Pesan berhasil dikirim. Terima kasih.</p>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'invalid_email'): ?>
      <p style="margin:10px 0; color:#b91c1c; font-weight:600;">Email tidak valid.</p>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
      <p style="margin:10px 0; color:#b91c1c; font-weight:600;">Lengkapi semua field.</p>
    <?php endif; ?>

    <form class="hubungi-form" action="proses-kontak.php" method="post">
      <input type="text" name="nama" placeholder="Nama" required>
      <input type="email" name="email" placeholder="Email" required>
      <input type="text" name="subjek" placeholder="Subjek" required>
      <textarea name="pesan" placeholder="Tulis pesan Anda..." rows="7" required></textarea>
      <button type="submit" class="hubungi-submit">KIRIM</button>
    </form>
  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="main.js"></script>
</body>
</html>
