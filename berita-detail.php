<?php
declare(strict_types=1);
require __DIR__ . '/admin/db_connection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadBase = 'uploads/berita/';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    http_response_code(400);
    die("ID berita tidak valid.");
}

$stmt = $conn->prepare("SELECT id, judul, isi, tanggal, gambar FROM berita WHERE id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_assoc();
$stmt->close();

if (!$data) {
    http_response_code(404);
    die("Berita tidak ditemukan.");
}

$img = !empty($data['gambar']) ? ($uploadBase . $data['gambar']) : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= h((string)$data['judul']) ?> - Polresta Padang</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Kita hanya tambahkan sedikit bumbu kerapian tanpa mengubah font family */
        body {
            background-color: #f8f9fa;
        }

        .content-wrapper {
            max-width: 900px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        }

        .back-nav {
            margin-bottom: 25px;
        }

        .back-nav a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            font-size: 14px;
        }

        .back-nav a:hover {
            color: #000;
        }

        .news-header h1 {
            margin-bottom: 10px;
            line-height: 1.3;
            color: #222;
        }

        .news-meta {
            color: #888;
            font-size: 13px;
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .news-image {
            width: 100%;
            border-radius: 8px;
            margin-bottom: 30px;
            display: block;
        }

        .news-body {
            line-height: 1.8;
            font-size: 16px;
            color: #444;
            text-align: justify;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .content-wrapper {
                margin: 0;
                padding: 20px;
                border-radius: 0;
            }
        }
    </style>
</head>
<body class="page-berita">

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="content-wrapper">
    <div class="back-nav">
        <a href="berita.php">← Kembali ke Daftar Berita</a>
    </div>

    <div class="news-header">
        <h1><?= h((string)$data['judul']) ?></h1>
        <div class="news-meta">
            Dipublikasikan pada: <strong><?= date('d M Y', strtotime($data['tanggal'])) ?></strong> | Humas Polresta Padang
        </div>
    </div>

    <?php if ($img): ?>
        <img src="<?= h($img) ?>" alt="<?= h((string)$data['judul']) ?>" class="news-image">
    <?php endif; ?>

    <article class="news-body">
        <?= nl2br(h((string)$data['isi'])) ?>
    </article>

    <div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #aaa;">
        &copy; <?= date('Y') ?> Polresta Padang - Bidang Humas
    </div>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script src="main.js"></script>
</body>
</html>