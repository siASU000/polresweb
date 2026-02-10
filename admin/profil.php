<?php
require __DIR__ . '/auth_guard.php'; // Pastikan pengguna sudah login

// Koneksi ke database
require __DIR__ . '/db_connection.php';

// Ambil data user yang sedang login
$userId = $_SESSION['admin_id'];
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "User not found!";
    exit;
}

$userData = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Profil - Polresta Padang</title>

    <link rel="stylesheet" href="admin.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-profile-page">

  <!-- Menu Navigasi -->
  <nav>
    <!-- Kamu bisa menambahkan link ke halaman lain di sini -->
    <ul>
      <li><a href="dashboard.php">Dashboard</a></li>
      <li><a href="kelola-home.php">Kelola Home</a></li>
      <li><a href="kelola-berita.php">Kelola Berita</a></li>
      <li><a href="kelola-galeri.php">Kelola Galeri</a></li>
      <li><a href="kelola-informasi.php">Kelola Informasi</a></li>
      <li><a href="kelola-kontak.php">Kelola Kontak</a></li>
      <li><a href="logout.php">Logout</a></li>
    </ul>
  </nav>

  <div class="profile-container">
    <h1>Profil Saya</h1>

    <!-- Tampilkan Data Profil Admin -->
    <div class="profile-card">
      <img src="<?php echo !empty($userData['foto']) ? '../' . htmlspecialchars($userData['foto']) : '../assets/default-avatar.png'; ?>" alt="Foto Profil" class="profile-img" id="profileImage">
      
      <form id="profileForm">
        <div class="form-group">
          <label for="nama">Nama</label>
          <input type="text" id="nama" name="nama" value="<?php echo htmlspecialchars($userData['nama'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="nrp">NRP</label>
          <input
            type="text"
            id="nrp"
            name="nrp"
            value="<?php echo htmlspecialchars($userData['nrp'] ?? ''); ?>"
            required
            inputmode="numeric"
            autocomplete="off"
            maxlength="20"
            pattern="[0-9]+"
          >
        </div>

        <div class="form-group">
          <label for="no_hp">Nomor HP</label>
          <input
            type="text"
            id="no_hp"
            name="no_hp"
            value="<?php echo htmlspecialchars($userData['no_hp'] ?? ''); ?>"
            required
            inputmode="numeric"
            autocomplete="off"
            maxlength="15"
            pattern="[0-9]+"
          >
        </div>

        <div class="form-group">
          <label for="jabatan">Jabatan</label>
          <input type="text" id="jabatan" name="jabatan" value="<?php echo htmlspecialchars($userData['jabatan'] ?? ''); ?>" required>
        </div>

        <div class="form-group">
          <label for="alamat">Alamat</label>
          <textarea id="alamat" name="alamat" required><?php echo htmlspecialchars($userData['alamat'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
          <label for="foto">Foto Profil</label>
          <input type="file" id="foto" name="foto" accept="image/*" style="display:none;">
          <button type="button" id="btnPilihFoto" style="margin-bottom: 10px;">Pilih Foto</button>
          <button type="button" id="btnUploadFoto" style="margin-bottom: 10px;">Upload Foto</button>
          <div id="uploadStatus" style="margin-top: 10px; font-size: 14px;"></div>
        </div>

        <button type="submit">Simpan Profil</button>
        <div id="saveStatus" style="margin-top: 10px; font-size: 14px;"></div>
      </form>
    </div>
  </div>

  <script>
    const fotoInput = document.getElementById('foto');
    const btnPilihFoto = document.getElementById('btnPilihFoto');
    const btnUploadFoto = document.getElementById('btnUploadFoto');
    const profileForm = document.getElementById('profileForm');
    const saveStatus = document.getElementById('saveStatus');
    const uploadStatus = document.getElementById('uploadStatus');
    const profileImage = document.getElementById('profileImage');

    function setStatus(element, msg, isError = false) {
      element.textContent = msg;
      element.style.color = isError ? '#b91c1c' : '#0f766e';
      if (msg) setTimeout(() => (element.textContent = ''), 3000);
    }

    // Fungsi untuk hanya membolehkan angka
    function onlyNumbers(el) {
      el.addEventListener('input', () => {
        el.value = el.value.replace(/[^0-9]/g, '');
      });
      el.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        el.value = text.replace(/[^0-9]/g, '');
        el.dispatchEvent(new Event('input'));
      });
    }

    // Panggil validasi pada NRP dan Nomor HP saat halaman dimuat
    window.onload = function () {
      const nrp = document.getElementById('nrp');
      const hp = document.getElementById('no_hp');
      if (nrp) onlyNumbers(nrp);
      if (hp) onlyNumbers(hp);
    };

    // Pilih foto
    btnPilihFoto.addEventListener('click', () => fotoInput.click());

    // Preview foto yang dipilih
    fotoInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          profileImage.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });

    // Upload foto
    btnUploadFoto.addEventListener('click', async () => {
      if (!fotoInput.files || !fotoInput.files[0]) {
        setStatus(uploadStatus, 'Pilih foto terlebih dahulu.', true);
        return;
      }

      const fd = new FormData();
      fd.append('foto', fotoInput.files[0]);

      try {
        const res = await fetch('profile_upload.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });

        const json = await res.json();
        if (!res.ok || json.status !== 'success') {
          setStatus(uploadStatus, json.message || 'Upload gagal.', true);
          return;
        }

        profileImage.src = '../' + json.foto;
        setStatus(uploadStatus, 'Foto berhasil diupload!');
        fotoInput.value = ''; // Reset input
      } catch (err) {
        setStatus(uploadStatus, 'Terjadi error saat upload.', true);
      }
    });

    // Simpan profil
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const fd = new FormData(profileForm);

      try {
        const res = await fetch('profile_update.php', {
          method: 'POST',
          body: fd,
          credentials: 'same-origin'
        });

        const json = await res.json();
        if (!res.ok || json.status !== 'success') {
          setStatus(saveStatus, json.message || 'Gagal menyimpan profil.', true);
          return;
        }

        setStatus(saveStatus, 'Profil berhasil disimpan!');
      } catch (err) {
        setStatus(saveStatus, 'Terjadi error saat menyimpan.', true);
      }
    });
  </script>
</body>
</html>
