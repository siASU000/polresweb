<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin - Polresta Padang</title>
  
  <!-- Pastikan link ke admin.css benar -->
  <link rel="stylesheet" href="admin/admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>


<body class="admin-dashboard-page">

  <main class="admin-shell">
    <section class="admin-card">

      <!-- Header Admin -->
      <div class="admin-top">
        <img src="../assets/Logo Polresta Padang.png" alt="Polresta Padang" class="admin-logo">

        <div class="admin-top-right">
          <p class="admin-subtitle">Dashboard Admin</p>
          <p class="admin-small">Kelola konten website Polresta Padang</p>
        </div>
      </div>

      <!-- Grid Menu -->
      <div class="admin-grid">
<a class="admin-menu" href="kelola-home.php">
  <h3>Kelola Home</h3>
  <p>Banner & layanan utama di halaman Home.</p>
</a>

        <a class="admin-menu" href="../berita.php" target="_blank">
          <h3>Lihat Website</h3>
          <p>Buka website publik untuk cek tampilan.</p>
        </a>

        <a class="admin-menu" href="kelola-berita.php">
          <h3>Kelola Berita</h3>
          <p>Tambah, edit, hapus berita (versi admin).</p>
        </a>

        <a class="admin-menu" href="kelola-galeri.php">
          <h3>Kelola Galeri</h3>
          <p>Tambah foto galeri dan atur urutan tampil.</p>
        </a>

        <a class="admin-menu" href="kelola-informasi.php">
          <h3>Kelola Informasi</h3>
          <p>DPO, Orang Hilang, dan informasi lainnya.</p>
        </a>

        <a class="admin-menu" href="kelola-kontak.php">
          <h3>Kelola Kontak</h3>
          <p>Alamat, nomor WA, jam layanan, email.</p>
        </a>

        <a class="admin-menu danger" href="login.php" onclick="return confirm('Keluar dari dashboard admin?');">
          <h3>Logout</h3>
          <p>Kembali ke halaman login admin.</p>
        </a>

      </div>

      <div class="admin-footer">
        <p>© 2026 Polresta Padang - Admin Panel</p>
      </div>

    </section>
  </main>

</body>
</html>
