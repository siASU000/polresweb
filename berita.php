<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/admin/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

// Helper tanggal Indo sederhana
function tanggal_indo($tanggal)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int) $split[1]] . ' ' . $split[0];
}

$uploadBase = 'uploads/berita/';


// Ambil berita populer (5 terbanyak dilihat)
$popular = [];
$popSql = "SELECT id, judul, slug, gambar, views, tanggal FROM berita ORDER BY views DESC, tanggal DESC LIMIT 5";
$popRs = $conn->query($popSql);
if ($popRs) {
    while ($p = $popRs->fetch_assoc())
        $popular[] = $p;
    $popRs->free();
}

// Ambil berita terbaru
$items = [];
$sql = "SELECT id, judul, slug, tanggal, gambar 
        FROM berita 
        ORDER BY tanggal DESC, id DESC 
        LIMIT 50";
$rs = $conn->query($sql);
if ($rs) {
    while ($r = $rs->fetch_assoc())
        $items[] = $r;
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-gold: #f1c40f;
            /* Warna Polisi */
            --dark-blue: #2c3e50;
        }

        body.page-berita {
            background-color: #f4f6f8;
            font-family: 'Poppins', sans-serif;
        }

        .berita-wrap {
            max-width: 1600px;
            margin: 40px auto;
            padding: 0 40px;
        }

        /* Header Section */
        .page-header {
            text-align: center;
            margin-bottom: 50px;
        }

        .page-header h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--dark-blue);
            margin-bottom: 10px;
        }

        .page-header p {
            color: #7f8c8d;
        }

        /* Grid System - 2 columns per row */
        .berita-grid-page {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 35px; 
        }

        @media (max-width: 768px) {
            .berita-grid-page {
                grid-template-columns: 1fr;
            }
        }

        /* Card Design */
        .news-card {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            height: 100%;
        }

        .news-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        /* Image Wrapper */
        .card-thumb {
            width: 100%;
            height: 220px;
            position: relative;
            overflow: hidden;
            background: #eee;
        }

        .card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .news-card:hover .card-thumb img {
            transform: scale(1.1);
        }

        /* Content */
        .card-body {
            padding: 25px;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-date {
            font-size: 13px;
            color: #95a5a6;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #2c3e50;
            line-height: 1.5;
            margin: 0 0 5px 0;
            /* Batasi judul max 3 baris */
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .read-more {
            /* Dorong ke bawah */
            font-size: 14px;
            font-weight: 600;
            color: #e67e22;
            /* Aksen warna */
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* CTA Section */
        .cta-berita {
            background: linear-gradient(135deg, #2c3e50, #000000);
            color: #fff;
            text-align: center;
            padding: 60px 20px;
            border-radius: 20px;
            margin-top: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .cta-btn {
            display: inline-block;
            background: #25D366;
            /* Warna WA */
            color: white;
            padding: 12px 30px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
            transition: 0.3s;
        }

        .cta-btn:hover {
            background: #1ebe57;
            transform: scale(1.05);
        }

        /* Empty State */
        .empty-msg {
            grid-column: 1 / -1;
            text-align: center;
            padding: 50px;
            color: #999;
        }
    </style>
</head>

<body class="page-berita">

    <?php require __DIR__ . '/partials/header.php'; ?>

    <main class="berita-wrap">

        <div class="page-header">
            <h1>Arsip Berita & Informasi</h1>
            <p>Pembaruan terkini seputar kegiatan Polresta Padang</p>
            <?php if (!empty($hs['tagline'])): ?>
                <p style="margin-top: 10px; font-weight: 600; color: #fcc236; font-size: 18px; letter-spacing: 1px;">
                    <?= h($hs['tagline']) ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="news-page-layout">

            <!-- Kolom Kiri: Daftar Berita -->
            <div class="news-main-content">
                <section class="berita-grid-page">
                    <?php if (count($items) === 0): ?>
                        <div class="empty-msg">
                            <i class="far fa-newspaper" style="font-size: 40px; margin-bottom: 15px;"></i>
                            <p>Belum ada berita yang diterbitkan saat ini.</p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($items as $b):
                        $img = !empty($b['gambar']) ? ($uploadBase . $b['gambar']) : 'assets/no-image.jpg';
                        $beritaUrl = !empty($b['slug']) ? ('berita/' . h($b['slug'])) : ('berita-detail?id=' . (int) $b['id']);
                        ?>
                        <a href="<?= $beritaUrl ?>" class="news-card">
                            <div class="card-thumb">
                                <img src="<?= h($img) ?>" alt="<?= h($b['judul']) ?>"
                                    onerror="this.src='assets/placeholder.jpg'">
                            </div>
                            <div class="card-body">
                                <div class="card-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?= tanggal_indo($b['tanggal']) ?>
                                </div>
                                <h3 class="card-title"><?= h($b['judul']) ?></h3>
                                <div class="read-more">
                                    Baca Selengkapnya <i class=""></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </section>
            </div>

            <!-- Kolom Kanan: Berita Populer -->
            <aside class="news-sidebar">
                <div class="sidebar-title">
                    <i class="fas fa-fire" style="color:#e67e22;"></i> Terpopuler
                </div>
                <div class="popular-news-list">
                    <?php if (empty($popular)): ?>
                        <p style="color:#999;font-size:13px;">Belum ada berita populer.</p>
                    <?php else: ?>
                        <?php $rank = 1;
                        foreach ($popular as $pop):
                            $pImg = !empty($pop['gambar']) ? ($uploadBase . $pop['gambar']) : 'assets/no-image.jpg';
                            $pUrl = !empty($pop['slug']) ? ('berita/' . h($pop['slug'])) : ('berita-detail?id=' . (int) $pop['id']);
                            ?>
                            <a href="<?= h($pUrl) ?>" class="popular-news-item">
                                <div class="popular-news-rank"><?= $rank++ ?></div>
                                <img src="<?= h($pImg) ?>" alt="Thumb" class="popular-news-thumb"
                                    onerror="this.src='assets/placeholder.jpg'">
                                <div class="popular-news-info">
                                    <h4><?= h($pop['judul']) ?></h4>
                                    <span><i class="far fa-eye"></i> <?= number_format((int) $pop['views']) ?> views</span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </aside>

        </div>


        <section class="cta-berita">
            <h3>Butuh Bantuan Kepolisian?</h3>
            <p>Jangan ragu untuk melaporkan kejadian mencurigakan atau meminta bantuan darurat. Kami siap melayani 24
                Jam.</p>
            <a class="cta-btn" href="https://wa.me/6281234567890" target="_blank">
                <i class="fab fa-whatsapp"></i> Hubungi via WhatsApp
            </a>
        </section>

    </main>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="main.js"></script>
</body>

</html>