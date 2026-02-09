<?php
// Declare strict types and enable error reporting
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Connect to the database
require __DIR__ . '/admin/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid. Pastikan admin/db_connection.php menghasilkan \$conn (mysqli).");
}
$conn->set_charset('utf8mb4');

// Function to escape HTML output
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadBase = 'uploads/berita/';

// 1. QUERY BERITA TERKINI (Hanya yang diset Berita Terkini)
$beritaTerkini = [];
$sqlTerkini = "SELECT id, judul, gambar FROM berita 
               WHERE display_category = 'Berita Terkini' 
               ORDER BY tanggal DESC, id DESC LIMIT 3";
$rsTerkini = $conn->query($sqlTerkini);
if ($rsTerkini) {
    while ($r = $rsTerkini->fetch_assoc()) $beritaTerkini[] = $r;
    $rsTerkini->free();
}

// 2. QUERY BERITA POPULER (Hanya yang diset Berita Populer)
$beritaPopuler = [];
$sqlPopuler = "SELECT id, judul, gambar, tanggal FROM berita 
               WHERE display_category = 'Berita Populer' 
               ORDER BY tanggal DESC, id DESC LIMIT 5";
$rsPopuler = $conn->query($sqlPopuler);
if ($rsPopuler) {
    while ($r = $rsPopuler->fetch_assoc()) $beritaPopuler[] = $r;
    $rsPopuler->free();
}

// 3. QUERY KATEGORI (Hanya dari Tampilan Berita Utama)
// Kita buat fungsi agar query kategori lebih rapi dan tidak duplikat di Terkini
function getBeritaByKategori($conn, $kategori) {
    $sql = "SELECT id, judul, isi, tanggal, gambar, kategori 
            FROM berita 
            WHERE display_category = 'Tampilan Berita Utama' AND kategori = ? 
            ORDER BY tanggal DESC LIMIT 6";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $kategori);
    $stmt->execute();
    return $stmt->get_result();
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
  
  <style>
    /* CSS FIX Agar Gambar Tidak Terpotong/Gepeng */
    .berita-main-item img {
        width: 100%;
        height: 100%;
        object-fit: cover; /* Menjamin gambar memenuhi kotak tanpa merusak rasio */
        display: block;
    }
    .berita-main-item {
        overflow: hidden;
        position: relative;
        aspect-ratio: 4/3; /* Memastikan kotak seragam */
        background: #000;
    }
  </style>
</head>

<body>

  <?php require __DIR__ . '/partials/header.php'; ?>

  <section class="main-banner" id="homeBanner">
    <div class="banner-content">
      <h3 id="bannerTopText">POLRI PRESISI</h3>
      <h1 id="bannerTitleText">Polresta Padang</h1>
      <h3 id="bannerBottomText">POLRI UNTUK MASYARAKAT</h3>
    </div>
  </section>

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

  <section class="berita-terkini">
    <h2>BERITA <span>TERKINI</span></h2>

    <div class="berita-grid">
        <?php if (empty($beritaTerkini)): ?>
            <p style="text-align:center; width:100%; color: white;">Belum ada berita terkini.</p>
        <?php else: ?>
            <?php foreach ($beritaTerkini as $b): ?>
                <?php $img = !empty($b['gambar']) ? ($uploadBase . $b['gambar']) : 'assets/berita1.jpg'; ?>
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

  <section class="container-split">
  <div class="berita-lainnya">
    <h2 class="section-title">BERITA LAINNYA</h2>

    <div class="kategori">
      <div class="kategori-item">
        <h3>Lalu Lintas</h3>
        <div class="grid-lainnya">
          <?php
            $res = getBeritaByKategori($conn, 'Lalu Lintas');
            while ($row = $res->fetch_assoc()):
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar" style="object-fit: cover;">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(strip_tags($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div class="kategori-item">
        <h3>Kriminal</h3>
        <div class="grid-lainnya">
          <?php
            $res = getBeritaByKategori($conn, 'Kriminal');
            while ($row = $res->fetch_assoc()):
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar" style="object-fit: cover;">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(strip_tags($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div class="kategori-item">
        <h3>HUMAS</h3>
        <div class="grid-lainnya">
          <?php
            $res = getBeritaByKategori($conn, 'Humas');
            while ($row = $res->fetch_assoc()):
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
              <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar" style="object-fit: cover;">
              <div class="berita-info">
                <div class="kategori-tanggal">
                  <span class="kategori-box"><?= h($row['kategori']) ?></span>
                  <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                </div>
                <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                <p class="berita-short-desc"><?= substr(strip_tags($row['isi']), 0, 100) . '...' ?></p>
                <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>

      <div class="kategori-item">
        <h3>SDM</h3>
        <div class="grid-lainnya">
          <?php
            $res = getBeritaByKategori($conn, 'SDM'); // Pastikan parameter sesuai database (SDM besar)
            while ($row = $res->fetch_assoc()):
                $img = !empty($row['gambar']) ? ($uploadBase . $row['gambar']) : 'assets/default.jpg';
          ?>
            <div class="berita-item">
                <img src="<?= h($img) ?>" alt="<?= h($row['judul']) ?>" class="berita-gambar" style="object-fit: cover;">
                <div class="berita-info">
                    <div class="kategori-tanggal">
                        <span class="kategori-box"><?= h($row['kategori']) ?></span>
                        <span class="tanggal"><?= date('d M Y', strtotime($row['tanggal'])) ?></span>
                    </div>
                    <a href="berita-detail.php?id=<?= (int)$row['id'] ?>" class="berita-judul"><?= h($row['judul']) ?></a>
                    <p class="berita-short-desc"><?= substr(strip_tags($row['isi']), 0, 100) . '...' ?></p>
                    <p class="pembaca">Pembaca: <?= rand(50, 500) ?></p>
                </div>
            </div>
          <?php endwhile; ?>
        </div>
      </div>
    </div>

    <a href="berita.php" class="btn-all-link">Lihat Semua Berita</a>
  </div>
  

  <aside class="sidebar-pimpinan">
    <div class="sidebar-card">
        <h3 class="sidebar-header">PIMPINAN</h3>
        <div class="pimpinan-content">
            <div class="pimpinan-item">
                <div class="pimpinan-photo">
                    <img src="assets/kapolres padang.jpg" alt="Foto Kapolres" />
                </div>
                <p class="pimpinan-nama"><strong>(KAPOLRES)</strong><br>Kombes Pol. Apri Wibowo, S.I.K., M.H.</p>
            </div>

            <div class="pimpinan-item">
                <div class="pimpinan-photo">
                    <img src="assets/wakapolres padang.jpg" alt="Foto Wakapolres" />
                </div>
                <p class="pimpinan-nama"><strong>(WAKAPOLRES)</strong><br>AKBP FAIDIL ZIKRI, S.H., S.I.K., M.Si.</p>
            </div>
        </div>
    </div>

  <div class="berita-populer">
    <h3>BERITA POPULER</h3>
    <div class="berita-populer-list">
        <?php 
        // Ambil maksimal 3 berita saja agar tidak terlalu panjang ke bawah
        $count = 0;
        foreach ($beritaPopuler as $bp): 
            if($count >= 3) break; 
        ?>
        <div class="populer-item">
            <div class="populer-img-wrapper">
                <img src="<?= !empty($bp['gambar']) ? $uploadBase.$bp['gambar'] : 'assets/default.jpg' ?>" class="populer-img">
            </div>
            <div class="populer-info">
                <a href="berita-detail.php?id=<?= $bp['id'] ?>" class="populer-text-link">
                    <?= h(substr($bp['judul'], 0, 40)) ?>...
                </a>
                <div class="populer-date">
                    <i class="far fa-calendar-alt"></i> <?= date('d M Y', strtotime($bp['tanggal'])) ?>
                </div>
            </div>
        </div>
        <?php 
            $count++;
        endforeach; 
        ?>
        
        <?php if(empty($beritaPopuler)): ?>
            <p style="color:#888; font-size:12px; padding: 10px;">Belum ada berita populer.</p>
        <?php endif; ?>
    </div>
</div>
</aside>

</section>

  <?php require __DIR__ . '/partials/footer.php'; ?>

  <script src="home-config.js"></script>
  <script src="main.js"></script>
</body>
</html>