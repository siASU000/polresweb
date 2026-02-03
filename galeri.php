<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

// Pakai koneksi kamu (sesuaikan kalau beda)
require __DIR__ . '/admin/db_connection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan db_connection menghasilkan \$conn (mysqli).");
}
$conn->set_charset('utf8mb4');

function h(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$uploadBase = 'uploads/galeri/'; // lokasi file gambar galeri

$items = [];
$sql = "SELECT id, judul, deskripsi, gambar, alt_text
        FROM galeri
        WHERE is_active = 1
        ORDER BY urutan ASC, created_at DESC, id DESC";

$rs = $conn->query($sql);
if ($rs) {
  while ($row = $rs->fetch_assoc()) $items[] = $row;
  $rs->free();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Galeri - Polresta Padang</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-galeri">

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="galeri-wrap">
  <section class="galeri-grid-page">
    <?php if (count($items) === 0): ?>
      <p style="text-align:center; width:100%; padding:24px 0;">Belum ada foto galeri.</p>
    <?php else: ?>
      <?php foreach ($items as $g): ?>
        <?php
          $id    = (int)($g['id'] ?? 0);
          $judul = trim((string)($g['judul'] ?? ''));
          $desk  = trim((string)($g['deskripsi'] ?? ''));
          $file  = trim((string)($g['gambar'] ?? ''));

          $imgPath = $file !== '' ? ($uploadBase . $file) : '';
          $finalBg = $imgPath;

          // fallback kalau file tidak ada
          if ($finalBg === '' || !is_file(__DIR__ . '/' . $finalBg)) {
            $finalBg = 'assets/berita1.jpg'; // ganti kalau kamu punya placeholder lain
          }

          // kalau nanti mau detail, aktifkan ini:
          // $href = 'galeri-detail.php?id=' . $id;
          $href = '#';
        ?>

        <div class="galeri-item">
          <a
            class="galeri-box"
            href="<?= h($href) ?>"
            style="background-image:url('<?= h($finalBg) ?>');"
            title="<?= h($judul !== '' ? $judul : 'Galeri') ?>"
            aria-label="<?= h($judul !== '' ? $judul : 'Galeri') ?>"
          ></a>

          <div class="galeri-caption-bawah">
            <?php if ($judul !== ''): ?>
              <div class="galeri-title"><?= h($judul) ?></div>
            <?php endif; ?>

            <?php if ($desk !== ''): ?>
              <div class="galeri-desc"><?= h($desk) ?></div>
            <?php endif; ?>
          </div>
        </div>

      <?php endforeach; ?>
    <?php endif; ?>
  </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="main.js"></script>
</body>
</html>
