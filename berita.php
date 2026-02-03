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

// Ambil 12 berita terbaru
$items = [];
$sql = "SELECT id, judul, tanggal, gambar
        FROM berita
        ORDER BY tanggal DESC, id DESC
        LIMIT 12";
$rs = $conn->query($sql);
if ($rs) {
  while ($r = $rs->fetch_assoc()) $items[] = $r;
  $rs->free();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Berita - Polresta Padang</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  
  <style>
    /* Berita Grid */
    .berita-grid-page {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 1.5rem;
    }

    .berita-box-container {
      position: relative;
      overflow: hidden;
      border-radius: 2rem;
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
    }

    .berita-box {
      display: block;
      height: 200px;
      background-color: rgba(0, 0, 0, 0.4);
      transition: transform 0.3s ease;
    }

    .berita-box:hover {
      transform: scale(1.05);
    }

    .berita-info {
      position: absolute;
      bottom: 10px;
      left: 10px;
      color: white;
      z-index: 10;
    }

    .berita-title {
      font-size: 1.25rem;
      font-weight: bold;
    }

    .berita-date {
      font-size: 0.875rem;
      margin-top: 5px;
    }

    .cta-berita {
      text-align: center;
      margin-top: 3rem;
    }

    .cta-btn {
      background-color: #007bff;
      color: white;
      padding: 10px 20px;
      border-radius: 2rem;
      text-decoration: none;
    }

    .cta-btn:hover {
      background-color: #0056b3;
    }
  </style>
</head>


<body class="page-berita">

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="berita-wrap">

  <section class="berita-grid-page">
    <?php
      if (count($items) === 0) {
        echo '<p style="text-align:center;width:100%;">Belum ada berita.</p>';
      }

      $shown = 0;
      foreach ($items as $b):
        $shown++;
        $img = !empty($b['gambar']) ? ($uploadBase . $b['gambar']) : '';
        $bgStyle = $img ? "style=\"background-image:url('".h($img)."'); background-size:cover; background-position:center;\"" : "";
    ?>
      <div class="berita-box-container">
        <a class="berita-box" href="berita-detail.php?id=<?= (int)$b['id'] ?>" <?= $bgStyle ?> aria-label="<?= h((string)$b['judul']) ?>"></a>
        <div class="berita-info">
          <h3 class="berita-title"><?= h($b['judul']) ?></h3>
          <p class="berita-date"><?= date('l, d F Y', strtotime($b['tanggal'])) ?></p>
        </div>
      </div>
    <?php endforeach; ?>

    <?php
      // jika kurang dari 12, isi placeholder agar grid tetap rapi
      for ($i = $shown; $i < 12; $i++) {
        echo '<div class="berita-box-container"><a class="berita-box" href="#"></a></div>';
      }
    ?>
  </section>

  <section class="cta-berita">
    <h3>Jadilah Mitra Kami dalam Menjaga Keamanan</h3>
    <p>
      Informasi sekecil apapun dari Anda sangat berarti. Jangan ragu untuk menghubungi kami
      melalui WhatsApp. Bersama, kita wujudkan lingkungan yang lebih aman.
    </p>
    <a class="cta-btn" href="https://wa.me/628116693110" target="_blank" rel="noopener">
      Hubungi kami via Whatsapp
    </a>
  </section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="main.js"></script>
</body>
</html>
