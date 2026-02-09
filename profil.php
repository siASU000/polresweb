<?php
// 1. KONEKSI DATABASE & AMBIL DATA
require __DIR__ . '/admin/db_connection.php';

// Ambil data dari tabel profil_settings (ID 1)
$query = "SELECT * FROM profil_settings WHERE id = 1";
$result = $conn->query($query);
$p = $result->fetch_assoc();

// Fungsi helper untuk keamanan output
function e($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

// Fallback jika gambar kosong
$foto_kapolres = !empty($p['foto_kapolres']) ? 'uploads/profil/' . $p['foto_kapolres'] : 'assets/kapolres padang.jpg';
$foto_wakapolres = !empty($p['foto_wakapolres']) ? 'uploads/profil/' . $p['foto_wakapolres'] : 'assets/wakapolres padang.jpg';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil - Polresta Padang</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* Reset & Base */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Poppins', sans-serif; background-color: #f8fafc; color: #334155; line-height: 1.6; }

    /* Main Container */
    main { max-width: 1200px; margin: 0 auto; padding: 40px 20px 80px; }

    /* Typography Polish */
    h2 { color: #1e293b; position: relative; padding-bottom: 10px; margin-bottom: 20px; }
    h2::after { content: ''; position: absolute; left: 0; bottom: 0; width: 50px; height: 4px; background: #FCC236; border-radius: 2px; }

    /* Grid Layout */
    .profil-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 40px;
      align-items: start;
    }

    /* Sidebar Pimpinan */
    .profil-sidebar { display: flex; flex-direction: column; gap: 30px; }
    
    .profil-card {
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      transition: transform 0.3s ease;
      border: 1px solid #e2e8f0;
    }
    .profil-card:hover { transform: translateY(-5px); }

    .profil-card-title {
      background: #1e293b;
      color: #FCC236;
      font-weight: 700;
      font-size: 12px;
      text-align: center;
      padding: 12px;
      letter-spacing: 1px;
    }
    
    .profil-card-photo {
      padding: 15px;
      background: #fff;
      display: flex;
      justify-content: center;
    }
    .profil-card-photo img {
      width: 100%;
      height: 380px;
      object-fit: cover;
      border-radius: 10px;
      filter: grayscale(10%);
    }
    
    .profil-card-name {
      font-size: 14px;
      font-weight: 700;
      text-align: center;
      padding: 15px;
      color: #1e293b;
      border-top: 1px solid #f1f5f9;
    }

    /* Visi Misi Style */
    .profil-content { display: flex; flex-direction: column; gap: 30px; }
    
    .vm-row { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
    
    .vm-box {
      background: #fff;
      padding: 30px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
      border-left: 5px solid #FCC236;
    }
    
    .vm-box h2 { font-size: 22px; margin-bottom: 15px; border: none; padding: 0; }
    .vm-box h2::after { display: none; }
    
    .vm-text { color: #64748b; font-size: 15px; white-space: pre-line; }

    /* Simple Section (Sejarah & Struktur) */
    .simple-section {
      margin-top: 40px;
      background: #fff;
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
    
    .content-text { font-size: 16px; color: #475569; line-height: 1.8; white-space: pre-line; }

    /* Navbar logic (Keep your header styles or use partials) */
    header { background-color: #222; padding: 20px 50px; position: sticky; top: 0; z-index: 1000; }
    .header-container { display: flex; justify-content: space-between; align-items: center; max-width: 1400px; margin: 0 auto; }
    .logo { width: 220px; height: auto; }
    nav ul { display: flex; list-style: none; gap: 20px; }
    nav ul li a { text-decoration: none; color: #fff; font-weight: 600; font-size: 14px; text-transform: uppercase; }
    nav ul li a.active { color: #FCC236; border-bottom: 2px solid #FCC236; }

    /* Footer Styles */
    footer { margin-top: 80px; }
    .footer-top { background-color: #f44336; padding: 40px 50px; color: white; display: flex; justify-content: space-between; align-items: center; }
    .footer-bottom { background-color: #b71c1c; padding: 20px; text-align: center; color: white; font-size: 14px; }

    /* Responsive */
    @media (max-width: 992px) {
      .profil-grid { grid-template-columns: 1fr; }
      .profil-sidebar { flex-direction: row; flex-wrap: wrap; justify-content: center; }
      .profil-card { width: 100%; max-width: 350px; }
    }
    @media (max-width: 768px) {
      .vm-row { grid-template-columns: 1fr; }
      .footer-top { flex-direction: column; text-align: center; gap: 20px; }
    }
  </style>
</head>

<body>

<?php 
// Pastikan file ini ada, jika tidak, kode header di bawah bisa diaktifkan
if(file_exists(__DIR__ . '/partials/header.php')) {
    require __DIR__ . '/partials/header.php'; 
} else {
?>
    <header>
      <div class="header-container">
        <img src="assets/Logo Polresta Padang.png" class="logo" alt="Logo">
        <nav>
          <ul>
            <li><a href="index.php">Home</a></li>
            <li><a href="profil.php" class="active">Profil</a></li>
            <li><a href="berita.php">Berita</a></li>
            <li><a href="galeri.php">Galeri</a></li>
            <li><a href="informasi.php">Informasi</a></li>
            <li><a href="kontak.php">Hubungi Kami</a></li>
          </ul>
        </nav>
      </div>
    </header>
<?php } ?>

<main>
  <section id="visi-misi">
    <div class="profil-grid">

      <aside class="profil-sidebar">
        <div class="profil-card" id="kapolres">
          <div class="profil-card-title"><i class="fa-solid fa-star"></i> KAPOLRESTA PADANG</div>
          <div class="profil-card-photo">
            <img src="<?= $foto_kapolres ?>" alt="Kapolres Padang">
          </div>
          <div class="profil-card-name"><?= e($p['nama_kapolres'] ?? 'Kombes Pol. Apri Wibowo, S.I.K., M.H.') ?></div>
        </div>

        <div class="profil-card" id="wakapolres">
          <div class="profil-card-title"><i class="fa-solid fa-star-half-stroke"></i> WAKAPOLRESTA PADANG</div>
          <div class="profil-card-photo">
            <img src="<?= $foto_wakapolres ?>" alt="Wakapolres Padang">
          </div>
          <div class="profil-card-name"><?= e($p['nama_wakapolres'] ?? 'AKBP Faidil Zikri, S.H., S.I.K., M.Si.') ?></div>
        </div>
      </aside>

      <div class="profil-content">
        <div class="vm-row">
          <div class="vm-box">
            <h2><i class="fa-solid fa-eye" style="color:#FCC236"></i> VISI</h2>
            <div class="vm-text">
                <?= !empty($p['visi']) ? e($p['visi']) : 'Visi Polresta Padang belum diatur.' ?>
            </div>
          </div>
          <div class="vm-box">
            <h2><i class="fa-solid fa-bullseye" style="color:#FCC236"></i> MISI</h2>
            <div class="vm-text">
                <?= !empty($p['misi']) ? e($p['misi']) : 'Misi Polresta Padang belum diatur.' ?>
            </div>
          </div>
        </div>

        <section id="sejarah" class="simple-section">
          <h2><i class="fa-solid fa-clock-rotate-left" style="color:#FCC236"></i> Sejarah</h2>
          <div class="content-text">
            <?= !empty($p['sejarah']) ? e($p['sejarah']) : 'Informasi sejarah belum tersedia.' ?>
          </div>
        </section>

        <section id="struktur" class="simple-section">
          <h2><i class="fa-solid fa-sitemap" style="color:#FCC236"></i> Struktur Organisasi</h2>
          <div class="content-text">
            <?= !empty($p['struktur_organisasi']) ? e($p['struktur_organisasi']) : 'Informasi struktur organisasi belum tersedia.' ?>
          </div>
        </section>
      </div>

    </div>
  </section>
</main>

<?php 
if(file_exists(__DIR__ . '/partials/footer.php')) {
    require __DIR__ . '/partials/footer.php'; 
} else {
?>
    <footer>
      <div class="footer-top">
        <div class="footer-info">
          <p>POLRESTA PADANG</p>
          <p>Jl. Moh. Hoesni Thamrin No.1, Alang Laweh, Padang Selatan</p>
        </div>
        <div class="footer-contact">
          <p>Call Center: 110</p>
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; 2026 Polresta Padang - Kepolisian Negara Republik Indonesia</p>
      </div>
    </footer>
<?php } ?>

<script>
  // Auto active menu berdasarkan nama file
  (function () {
    const current = window.location.pathname.split("/").pop() || "index.php";
    document.querySelectorAll("nav a").forEach(a => {
      const href = a.getAttribute("href");
      if (href === current) a.classList.add("active");
    });
  })();
</script>

</body>
</html>