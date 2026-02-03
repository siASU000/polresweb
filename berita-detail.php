<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/admin/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan admin/db_connection.php menghasilkan \$conn (mysqli).");
}
$conn->set_charset('utf8mb4');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadBase = 'uploads/berita/';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
  http_response_code(400);
  die("ID berita tidak valid.");
}

$stmt = $conn->prepare("SELECT id, judul, isi, tanggal, gambar FROM berita WHERE id=? LIMIT 1");
if (!$stmt) die("Prepare gagal: " . $conn->error);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$data) {
  http_response_code(404);
  die("Berita tidak ditemukan.");
}

$img = !empty($data['gambar']) ? ($uploadBase . $data['gambar']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= h((string)$data['judul']) ?> - Polresta Padang</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-berita">

<?php require __DIR__ . '/partials/header.php'; ?>


<main style="max-width: 980px; margin: 40px auto; padding: 0 20px;">
  <a href="berita.php" style="display:inline-block; margin-bottom: 16px; text-decoration:none;">
    ← Kembali ke Berita
  </a>

  <h1 style="margin: 0 0 10px 0;"><?= h((string)$data['judul']) ?></h1>
  <p style="margin: 0 0 20px 0; opacity: .75;">Tanggal: <?= h((string)$data['tanggal']) ?></p>

  <?php if ($img): ?>
    <img src="<?= h($img) ?>" alt="<?= h((string)$data['judul']) ?>" style="width:100%; max-height:460px; object-fit:cover; border-radius: 12px; margin-bottom: 18px;">
  <?php endif; ?>

  <article style="line-height:1.9; font-size: 16px;">
    <?= nl2br(h((string)$data['isi'])) ?>
  </article>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="main.js"></script>
</body>
</html>
