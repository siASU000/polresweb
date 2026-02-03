<?php
require __DIR__ . "/auth_guard.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard Admin - Polresta Padang</title>

  <link rel="stylesheet" href="admin.css" />
  <link rel="stylesheet" href="dashboard.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-dashboard-page">

  <header class="topbar">
    <div class="topbar-left">
      <span class="badge">ADMIN</span>
      <span class="brand">Polresta Padang</span>
    </div>
    <div class="topbar-right">
      <span id="topUsername" class="top-username">-</span>
      <a href="logout.php" id="logoutBtn" class="btn-logout">Logout</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-title">Menu</div>

      <a class="nav-item active" href="dashboard.php">Profil Admin</a>

      <!-- Link ke file kelola yang ada di ROOT (sesuai screenshot kamu) -->
      <div class="sidebar-title mt">Kelola Konten</div>
      <a class="nav-item" href="kelola-home.php">Kelola Home</a>
      <a class="nav-item" href="kelola-berita.php">Kelola Berita</a>
      <a class="nav-item" href="kelola-galeri.php">Kelola Galeri</a>
      <a class="nav-item" href="kelola-informasi.php">Kelola Informasi</a>
      <a class="nav-item" href="kelola-kontak.php">Kelola Kontak</a>
      <a class="nav-item" href="kelola-header.php">Kelola Header</a>
      <a class="nav-item" href="kelola-footer.php">Kelola Footer</a>

    </aside>

    <main class="content">
      <h1 class="page-title">Profil Admin</h1>
      <p class="page-subtitle">Kelola biodata admin dan foto profil.</p>

      <section class="card profile-card">
        <div class="profile-left">
          <div class="avatar-wrap">
            <img id="avatar" class="avatar" src="../assets/logo_sim.png" alt="Foto Profil">
          </div>

          <div class="upload-wrap">
            <input type="file" id="fotoInput" accept="image/png,image/jpeg,image/webp" hidden>
            <button class="btn" id="btnPilihFoto" type="button">Pilih Foto</button>
            <button class="btn-outline" id="btnUploadFoto" type="button">Upload</button>
            <div class="hint">Format: JPG/PNG/WEBP, maks 2MB.</div>
          </div>

          <div class="mini">
            <div><b>Role:</b> <span id="roleText">-</span></div>
            <div><b>Username:</b> <span id="usernameText">-</span></div>
          </div>
        </div>

        <div class="profile-right">
          <form id="profileForm" class="form-grid">
            <div class="field">
              <label>Nama</label>
              <input type="text" name="nama" id="nama" placeholder="Masukkan Nama lengkap">
            </div>

            <div class="field">
               <label>NRP</label>
  <input
    type="text"
    id="nrp"
    name="nrp"
    placeholder="Masukkan NRP"
    required
    inputmode="numeric"
    autocomplete="off"
    maxlength="20"
    pattern="[0-9]+"
  >
</div>

            <div class="field">
              <label>Email</label>
              <input type="email" name="email" id="email" placeholder="Masukkan nama@domain.com">
            </div>

            <div class="field">
             <label>Nomor HP</label>
  <input
    type="text"
    id="no_hp"
    name="no_hp"
    placeholder="Masukkan Nomor HP"
    required
    inputmode="numeric"
    autocomplete="off"
    maxlength="15"
    pattern="[0-9]+"
  >
</div>

            <div class="field">
              <label>Jabatan</label>
              <input type="text" name="jabatan" id="jabatan" placeholder="Masukkan Jabatan">
            </div>

            <div class="field field-full">
              <label>Alamat</label>
              <textarea name="alamat" id="alamat" rows="4" placeholder="Masukkan Alamat lengkap"></textarea>
            </div>

            <div class="actions field-full">
              <button class="btn" type="submit">Simpan Profil</button>
              <span id="saveStatus" class="status"></span>
            </div>
          </form>
        </div>
      </section>
<section class="card profile-card" style="margin-top:16px;">
  <div class="profile-right" style="width:100%;">
    <h2 style="margin:0 0 10px;">Ubah Kata Sandi</h2>

    <form id="passwordForm" class="form-grid">
      <div class="field">
        <label>Password Lama</label>
        <input type="password" id="old_password" name="old_password" placeholder="Masukkan password lama" required>
      </div>

      <div class="field">
        <label>Password Baru</label>
        <input type="password" id="new_password" name="new_password" placeholder="Minimal 8 karakter" required minlength="8">
      </div>

      <div class="field">
        <label>Konfirmasi Password Baru</label>
        <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password baru" required minlength="8">
      </div>

      <div class="actions field-full">
        <button class="btn" type="submit">Simpan Password</button>
        <span id="passStatus" class="status"></span>
      </div>
    </form>
  </div>
</section>

      <!-- Bagian bawah “kategori/search/footer” yang tidak relevan sengaja dihilangkan -->
    </main>
  </div>

  <script src="dashboard.js"></script>
  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const logoutBtn = document.getElementById('logoutBtn');
    if (!logoutBtn) return;

    logoutBtn.addEventListener('click', function (e) {
      const ok = confirm('Yakin ingin logout?');
      if (!ok) e.preventDefault();
    });
  });
</script>
</body>
</html>
