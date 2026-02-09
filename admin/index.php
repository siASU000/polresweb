<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin - Polresta Padang</title>
  
  <link rel="stylesheet" href="admin.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-dashboard-page">
  <main class="admin-shell">
    <section class="admin-card">

      <div class="admin-top">
        <img src="../assets/Logo Polresta Padang.png" alt="Polresta Padang" class="admin-logo">
        <div class="admin-top-right">
          <p class="admin-subtitle">Dashboard Admin</p>
          <p class="admin-small">Kelola konten website Polresta Padang</p>
        </div>
      </div>

      <div class="admin-grid">
        <a class="admin-menu" href="kelola-home.php">
          <i class="fa-solid fa-house-chimney"></i>
          <h3>Kelola Home</h3>
          <p>Banner & layanan utama.</p>
        </a>

        <a class="admin-menu" href="kelola-profil.php">
          <i class="fa-solid fa-id-card"></i>
          <h3>Kelola Profil</h3>
          <p>Visi, Misi, Sejarah & Pimpinan.</p>
        </a>

        <a class="admin-menu" href="kelola-berita.php">
          <i class="fa-solid fa-newspaper"></i>
          <h3>Kelola Berita</h3>
          <p>Update informasi berita terbaru.</p>
        </a>

        <a class="admin-menu" href="kelola-galeri.php">
          <i class="fa-solid fa-images"></i>
          <h3>Kelola Galeri</h3>
          <p>Foto kegiatan kepolisian.</p>
        </a>

        <a class="admin-menu" href="kelola-informasi.php">
          <i class="fa-solid fa-circle-info"></i>
          <h3>Kelola Informasi</h3>
          <p>DPO, Orang Hilang, & Layanan.</p>
        </a>

        <a class="admin-menu" href="kelola-kontak.php">
          <i class="fa-solid fa-phone"></i>
          <h3>Kelola Kontak</h3>
          <p>Nomor telpon & alamat map.</p>
        </a>

        <a class="admin-menu" href="kelola-footer.php">
          <i class="fa-solid fa-window-maximize"></i>
          <h3>Kelola Footer</h3>
          <p>Atur copyright & bar bawah.</p>
        </a>

        <a class="admin-menu danger" href="logout.php" onclick="return confirm('Keluar?');">
          <i class="fa-solid fa-right-from-bracket"></i>
          <h3>Logout</h3>
          <p>Keluar dari panel admin.</p>
        </a>
      </div>

      <div class="admin-footer">
        <p>© 2026 Polresta Padang - Admin Panel</p>
      </div>
    </section>
  </main>
</body>
</html>