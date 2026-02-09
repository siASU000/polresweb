<?php
declare(strict_types=1);

// 1. KONEKSI & SETTINGS
require __DIR__ . '/admin/db_connection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$uploadBase = 'uploads/galeri/';

// 2. AMBIL DATA GALERI
$items = [];
$sql = "SELECT id, judul, deskripsi, gambar 
        FROM galeri 
        WHERE is_active = 1 
        ORDER BY urutan ASC, created_at DESC";

$rs = $conn->query($sql);
if ($rs) {
    while ($row = $rs->fetch_assoc()) $items[] = $row;
    $rs->free();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Galeri - Polresta Padang</title>
    
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

    <style>
        /* CSS INTERNAL UNTUK GALERI PROFESIONAL */
        body {
            background-color: #f8f9fa; /* Abu-abu sangat muda agar kartu putih terlihat kontras */
            font-family: 'Poppins', sans-serif;
        }

        .galeri-section {
            padding: 60px 0;
        }

        .container-galeri {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .title-area {
            text-align: center;
            margin-bottom: 50px;
        }

        .title-area h2 {
            font-weight: 700;
            font-size: 2rem;
            color: #222;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .underline {
            width: 80px;
            height: 4px;
            background: #f1c40f; /* Kuning identitas Polri */
            margin: 0 auto;
        }

        /* Grid System */
        .galeri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        /* Card Galeri */
        .galeri-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #eee;
        }

        .galeri-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 25px rgba(0,0,0,0.15);
        }

        /* Frame Gambar */
        .galeri-img-wrapper {
            width: 100%;
            height: 240px; /* Tinggi seragam */
            overflow: hidden;
            position: relative;
        }

        .galeri-img-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover; /* Penting: agar gambar tidak gepeng */
            transition: transform 0.5s ease;
        }

        .galeri-card:hover .galeri-img-wrapper img {
            transform: scale(1.1);
        }

        /* Bagian Teks */
        .galeri-content {
            padding: 20px;
        }

        .galeri-card-title {
            font-size: 1.15rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            line-height: 1.4;
        }

        .galeri-card-desc {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 2; /* Potong teks jika lebih dari 2 baris */
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        /* Responsive HP */
        @media (max-width: 768px) {
            .galeri-grid {
                grid-template-columns: 1fr; /* Satu kolom saja di HP */
            }
            .title-area h2 {
                font-size: 1.6rem;
            }
            .galeri-img-wrapper {
                height: 200px; /* Sedikit lebih pendek di HP */
            }
        }
    </style>
</head>
<body>

    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="galeri-section">
        <div class="container-galeri">
            
            <div class="title-area">
                <h2>Galeri Kegiatan</h2>
                <div class="underline"></div>
            </div>

            <section class="galeri-grid">
                <?php if (empty($items)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 50px;">
                        <p>Belum ada foto yang diunggah ke galeri.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($items as $g): 
                        // Cek file gambar
                        $imgPath = !empty($g['gambar']) ? ($uploadBase . $g['gambar']) : 'assets/placeholder.jpg';
                    ?>
                        <article class="galeri-card">
                            <div class="galeri-img-wrapper">
                                <img src="<?= h($imgPath) ?>" alt="<?= h($g['judul']) ?>" loading="lazy">
                            </div>
                            <div class="galeri-content">
                                <h3 class="galeri-card-title"><?= h($g['judul']) ?></h3>
                                <p class="galeri-card-desc"><?= h($g['deskripsi']) ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </section>

        </div>
    </main>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="main.js"></script>
</body>
</html>