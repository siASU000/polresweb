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
      <img src="uploads/admin/<?php echo htmlspecialchars($userData['photo'] ?? 'default.jpg'); ?>" alt="Foto Profil" class="profile-img">
      
      <form action="profile_update.php" method="POST" enctype="multipart/form-data">
        <div class="form-group">
          <label for="name">Nama</label>
          <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
        </div>

        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
        </div>

        <div class="form-group">
  <label for="nrp">NRP</label>
  <input
    type="text"
    id="nrp"
    name="nrp"
    value="<?php echo htmlspecialchars($userData['nrp']); ?>"
    required
    inputmode="numeric"
    autocomplete="off"
    maxlength="20"
    pattern="[0-9]+"
  >
</div>

        <div class="form-group">
  <label for="phone">Nomor HP</label>
  <input
    type="text"
    id="phone"
    name="phone"
    value="<?php echo htmlspecialchars($userData['phone']); ?>"
    required
    inputmode="numeric"
    autocomplete="off"
    maxlength="15"
    pattern="[0-9]+"
  >
</div>

        <div class="form-group">
          <label for="address">Alamat</label>
          <textarea id="address" name="address" required><?php echo htmlspecialchars($userData['address']); ?></textarea>
        </div>

        <div class="form-group">
          <label for="photo">Foto Profil</label>
          <input type="file" id="photo" name="photo" onchange="showImage(event)">
        </div>

        <button type="submit">Simpan Profil</button>
      </form>
    </div>
  </div>

  <script>
    // Fungsi untuk hanya membolehkan angka
    function onlyNumbers(el) {
      el.addEventListener('input', () => {
        el.value = el.value.replace(/[^0-9]/g, ''); // Menghapus karakter selain angka
      });
      el.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text');
        el.value = text.replace(/[^0-9]/g, ''); // Hapus karakter non-angka saat paste
        el.dispatchEvent(new Event('input')); // Trigger input event
      });
    }

    // Panggil validasi pada NRP dan Nomor HP saat halaman dimuat
    window.onload = function () {
      const nrp = document.getElementById('nrp');
      const hp = document.getElementById('phone');
      if (nrp) onlyNumbers(nrp); // Validasi untuk NRP
      if (hp) onlyNumbers(hp);   // Validasi untuk Nomor HP
    };

    // Menampilkan gambar setelah memilih file foto
    function showImage(event) {
      const file = event.target.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        const image = document.createElement('img');
        image.src = e.target.result;
        document.querySelector('.profile-img').src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  </script>
</body>
</html>
