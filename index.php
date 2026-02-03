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

// Ambil 12 berita terbaru untuk home (3 terkini + 9 lainnya)
$beritaHome = [];
$sql = "SELECT id, judul, isi, tanggal, gambar, kategori
        FROM berita
        ORDER BY tanggal DESC, id DESC
        LIMIT 12";
$rs = $conn->query($sql);
if ($rs) {
  while ($r = $rs->fetch_assoc()) $beritaHome[] = $r;
  $rs->free();
}

$beritaTerkini = array_slice($beritaHome, 0, 3);
$beritaLainnya = array_slice($beritaHome, 3, 9);
?>

<?php
// Ambil kategori yang unik dari database
$kategoriList = [];
$rsKat = $conn->query("SELECT DISTINCT kategori FROM berita ORDER BY kategori ASC");

// Cek apakah query berhasil
if ($rsKat) {
    while ($k = $rsKat->fetch_assoc()) {
        // Menambahkan kategori ke dalam array
        $kategoriList[] = $k['kategori'];
    }
    $rsKat->free(); // Bebaskan hasil query
} else {
    // Jika query gagal
    echo "Query gagal: " . $conn->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Polresta Padang</title>

  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body>

  <?php require __DIR__ . '/partials/header.php'; ?>

  <!-- BANNER -->
  <section class="main-banner" id="homeBanner">
    <div class="banner-content">
      <h3 id="bannerTopText">POLRI PRESISI</h3>
      <h1 id="bannerTitleText">Polresta Padang</h1>
      <h3 id="bannerBottomText">POLRI UNTUK MASYARAKAT</h3>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="services" aria-label="Layanan utama">
    <a href="konten.php#sim" class="service-item" data-service="sim" id="serviceSimLink">
      <img src="assets/logo sim.png" alt="SIM Icon" class="service-icon" id="serviceSimIcon" />
      <div>
        <h4 id="serviceSimTitle">SIM</h4>
        <p id="serviceSimDesc">
          Polresta Padang melayani penerbitan Surat Izin Mengemudi (SIM) untuk masyarakat di Kota Padang.
        </p>
      </div>
    </a>

    <a href="konten.php#skck" class="service-item" data-service="skck" id="serviceSkckLink">
      <img src="assets/logo SKCK.png" alt="SKCK Icon" class="service-icon" id="serviceSkckIcon" />
      <div>
        <h4 id="serviceSkckTitle">SKCK</h4>
        <p id="serviceSkckDesc">
          Polresta Padang melayani penerbitan Surat Keterangan Catatan Kepolisian (SKCK) untuk masyarakat di Kota Padang.
        </p>
      </div>
    </a>

    <a href="konten.php#spkt" class="service-item" data-service="spkt" id="serviceSpktLink">
      <img src="assets/logo SPKT.png" alt="SPKT Icon" class="service-icon" id="serviceSpktIcon" />
      <div>
        <h4 id="serviceSpktTitle">SPKT</h4>
        <p id="serviceSpktDesc">
          Polresta Padang juga bertindak sebagai Sentra Pelayanan Kepolisian Terpadu bagi masyarakat di Kota Padang.
        </p>
      </div>
    </a>
  </section>

  <!-- BERITA TERKINI -->
  <section class="berita-terkini">
    <h2>BERITA <span>TERKINI</span></h2>

    <div class="berita-grid">
      <?php if (count($beritaTerkini) === 0): ?>
        <p style="text-align:center; width:100%;">Belum ada berita.</p>
      <?php else: ?>
        <?php foreach ($beritaTerkini as $b): ?>
          <?php
            $img = !empty($b['gambar']) ? ($uploadBase . $b['gambar']) : 'assets/berita1.jpg';
          ?>
          <div class="berita-main-item">
            <img src="<?= h($img) ?>" alt="<?= h((string)$b['judul']) ?>">
            <button class="btn-read" type="button"
              onclick="window.location.href='berita-detail.php?id=<?= (int)$b['id'] ?>'">
              BACA SELENGKAPNYA
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </section>

  <!-- BERITA LAINNYA + SIDEBAR PIMPINAN -->
<section class="container-split">
  <div class="berita-lainnya">
    <h2 class="section-title">BERITA LAINNYA</h2>

    <!-- Kategori Lalu Lintas -->
    <div class="kategori">
      <div class="kategori-item">
        <h3>Lalu Lintas</h3>
        <div class="grid-lainnya">
          <?php
            $sqlLaluLintas = "SELECT id, judul, isi, tanggal, gambar, kategori FROM berita WHERE kategori = 'Lalu Lintas' ORDER BY tanggal DESC LIMIT 6";
            $resultLaluLintas = $conn->query($sqlLaluLintas);
            if ($resultLaluLintas) {
              while ($row = $resultLaluLintas->fetch_assoc()) {
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(h($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php
            }
          }
          ?>
        </div>
      </div>

      <!-- Kategori Kriminal -->
      <div class="kategori-item">
        <h3>Kriminal</h3>
        <div class="grid-lainnya">
          <?php
            $sqlKriminal = "SELECT id, judul, isi, tanggal, gambar, kategori FROM berita WHERE kategori = 'Kriminal' ORDER BY tanggal DESC LIMIT 6";
            $resultKriminal = $conn->query($sqlKriminal);
            if ($resultKriminal) {
              while ($row = $resultKriminal->fetch_assoc()) {
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(h($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php
            }
          }
          ?>
        </div>
      </div>

      <!-- Kategori HUMAS -->
      <div class="kategori-item">
        <h3>HUMAS</h3>
        <div class="grid-lainnya">
          <?php
            $sqlHumas = "SELECT id, judul, isi, tanggal, gambar, kategori FROM berita WHERE kategori = 'HUMAS' ORDER BY tanggal DESC LIMIT 6";
            $resultHumas = $conn->query($sqlHumas);
            if ($resultHumas) {
              while ($row = $resultHumas->fetch_assoc()) {
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(h($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php
            }
          }
          ?>
        </div>
      </div>

      <!-- Kategori SDM -->
      <div class="kategori-item">
        <h3>SDM</h3>
        <div class="grid-lainnya">
          <?php
            $sqlSdm = "SELECT id, judul, isi, tanggal, gambar, kategori FROM berita WHERE kategori = 'SDM' ORDER BY tanggal DESC LIMIT 6";
            $resultSdm = $conn->query($sqlSdm);
            if ($resultSdm) {
              while ($row = $resultSdm->fetch_assoc()) {
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(h($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php
            }
          }
          ?>
        </div>
      </div>
    </div>

    <!-- Tombol untuk melihat semua berita -->
<a href="berita.php" class="btn-all-link">Lihat Semua Berita</a>

  </div>
  

  <!-- Sidebar Pimpinan -->
<aside class="sidebar-pimpinan">
    <div class="pimpinan-container">
        <!-- Foto dan Nama Pimpinan -->
        <div class="pimpinan-item">
            <div class="pimpinan-photo">
                <img src="assets/kapolres padang.jpg" alt="Foto Kapolres" />
            </div>
            <p class="pimpinan-nama">(Kapolres) Kombes Pol. Apri Wibowo, S.I.K., M.H.</p>
        </div>

        <div class="pimpinan-item">
            <div class="pimpinan-photo">
                <img src="assets/wakapolres padang.jpg" alt="Foto Wakapolres" />
            </div>
            <p class="pimpinan-nama">(Wakapolres) AKBP FAIDIL ZIKRI, S.H., S.I.K., M.Si.</p>
        </div>

        <!-- Berita Populer (Scroll) -->
        <div class="berita-populer">
            <h3>Berita Populer</h3>
            <div class="berita-populer-list">
                <?php
                $sqlPopuler = "SELECT id, judul, isi, tanggal, gambar FROM berita ORDER BY pembaca DESC LIMIT 5"; 
                $resultPopuler = $conn->query($sqlPopuler);
                if ($resultPopuler) {
                    while ($row = $resultPopuler->fetch_assoc()) {
                        $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
                ?>
                    <div class="berita-populer-item">
                        <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" />
                        <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-populer-judul"><?= h($row['judul']) ?></a>
                    </div>
                <?php
                    }
                }
                ?>
            </div>
        </div>
    </div>
</aside>

</section>


  <?php require __DIR__ . '/partials/footer.php'; ?>

  <script src="home-config.js"></script>
  <script src="main.js"></script>
</body>
</html>
