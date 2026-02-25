<?php
declare(strict_types=1);
require __DIR__ . '/admin/db_connection.php';

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

$uploadBase = 'uploads/berita/';
$siteUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/Polresta_Padang/';

// Support both slug and legacy id parameter
$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$data = null;

if ($slug !== '') {
    // Lookup by slug
    $stmt = $conn->prepare("SELECT id, judul, slug, isi, meta_description, tanggal, gambar, kategori FROM berita WHERE slug=? LIMIT 1");
    $stmt->bind_param("s", $slug);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();
} elseif ($id > 0) {
    // Legacy: lookup by id, then redirect to slug URL
    $stmt = $conn->prepare("SELECT id, judul, slug, isi, meta_description, tanggal, gambar, kategori FROM berita WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    $stmt->close();

    // 301 redirect to slug URL if slug exists
    if ($data && !empty($data['slug'])) {
        header("Location: " . $siteUrl . "berita/" . $data['slug'], true, 301);
        exit;
    }
} else {
    http_response_code(400);
    die("URL berita tidak valid.");
}

if (!$data) {
    http_response_code(404);
    die("Berita tidak ditemukan.");
}

$img = !empty($data['gambar']) ? ($uploadBase . $data['gambar']) : '';
$pageTitle = h((string) $data['judul']) . ' - Polresta Padang';
$metaDesc = !empty($data['meta_description'])
    ? h((string) $data['meta_description'])
    : h(mb_substr(strip_tags((string) $data['isi']), 0, 160, 'UTF-8'));
$canonicalUrl = $siteUrl . 'berita/' . ($data['slug'] ?? $data['id']);
$ogImage = $img !== '' ? ($siteUrl . $img) : '';
$publishDate = date('c', strtotime($data['tanggal']));

// Increment Views
if (isset($data['id'])) {
    $conn->query("UPDATE berita SET views = views + 1 WHERE id = " . (int) $data['id']);
}

// Fetch related articles (same kategori, exclude current)
$related = [];
if (!empty($data['kategori'])) {
    $stmtR = $conn->prepare("SELECT id, judul, slug, gambar, tanggal FROM berita WHERE kategori=? AND id!=? ORDER BY tanggal DESC LIMIT 3");
    $stmtR->bind_param("si", $data['kategori'], $data['id']);
    $stmtR->execute();
    $resR = $stmtR->get_result();
    while ($r = $resR->fetch_assoc())
        $related[] = $r;
    $stmtR->close();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <base href="/Polresta_Padang/" />
    <title><?= $pageTitle ?></title>
    <meta name="description" content="<?= $metaDesc ?>" />
    <meta name="robots" content="index, follow" />
    <link rel="canonical" href="<?= h($canonicalUrl) ?>" />

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="article" />
    <meta property="og:title" content="<?= $pageTitle ?>" />
    <meta property="og:description" content="<?= $metaDesc ?>" />
    <meta property="og:url" content="<?= h($canonicalUrl) ?>" />
    <?php if ($ogImage): ?>
        <meta property="og:image" content="<?= h($ogImage) ?>" />
    <?php endif; ?>
    <meta property="og:site_name" content="Polresta Padang" />
    <meta property="article:published_time" content="<?= $publishDate ?>" />

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image" />
    <meta name="twitter:title" content="<?= $pageTitle ?>" />
    <meta name="twitter:description" content="<?= $metaDesc ?>" />
    <?php if ($ogImage): ?>
        <meta name="twitter:image" content="<?= h($ogImage) ?>" />
    <?php endif; ?>

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "NewsArticle",
        "headline": <?= json_encode($data['judul'], JSON_UNESCAPED_UNICODE) ?>,
        "description": <?= json_encode(strip_tags(mb_substr((string) $data['isi'], 0, 200, 'UTF-8')), JSON_UNESCAPED_UNICODE) ?>,
        "datePublished": "<?= $publishDate ?>",
        <?php if ($ogImage): ?>
            "image": "<?= h($ogImage) ?>",
        <?php endif; ?>
        "author": {
            "@type": "Organization",
            "name": "Humas Polresta Padang"
        },
        "publisher": {
            "@type": "Organization",
            "name": "Polresta Padang",
            "url": "<?= h($siteUrl) ?>"
        },
        "mainEntityOfPage": {
            "@type": "WebPage",
            "@id": "<?= h($canonicalUrl) ?>"
        }
    }
    </script>

    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            background-color: #f8f9fa;
        }

        .content-wrapper {
            max-width: 1200px;
            margin: 40px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
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

        /* Share buttons */
        .share-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }

        .share-section h4 {
            font-size: 14px;
            color: #666;
            margin-bottom: 12px;
        }

        .share-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .share-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            color: #fff;
            font-size: 13px;
            font-weight: 500;
            transition: transform 0.2s, opacity 0.2s;
        }

        .share-btn:hover {
            transform: translateY(-2px);
            opacity: 0.9;
            color: #fff;
        }

        .share-btn.wa {
            background: #25D366;
        }

        .share-btn.fb {
            background: #1877F2;
        }

        .share-btn.tw {
            background: #1DA1F2;
        }

        .share-btn.cp {
            background: #6b7280;
            cursor: pointer;
        }

        /* Related articles */
        .related-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .related-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
        }

        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .related-card {
            text-decoration: none;
            color: inherit;
            background: #f8f9fa;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .related-card:hover {
            transform: translateY(-3px);
        }

        .related-card img {
            width: 100%;
            height: 130px;
            object-fit: cover;
        }

        .related-card .related-info {
            padding: 12px;
        }

        .related-card .related-info h4 {
            font-size: 14px;
            margin: 0 0 6px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .related-card .related-info small {
            color: #888;
            font-size: 12px;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin: 0;
                padding: 20px;
                border-radius: 0;
            }

            .related-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
    </style>
</head>

<body class="page-berita">

    <?php require __DIR__ . '/partials/header.php'; ?>


    <main class="content-wrapper">
        <div class="back-nav">
            <a href="berita"><i class="fas fa-arrow-left"></i> Kembali ke Daftar Berita</a>
        </div>

        <div class="news-page-layout">

            <!-- Main Content -->
            <div class="news-main-content">
                <article>
                    <div class="news-header">
                        <h1><?= h((string) $data['judul']) ?></h1>
                        <div class="news-meta">
                            <i class="far fa-calendar-alt"></i> Dipublikasikan pada:
                            <strong><?= date('d M Y', strtotime($data['tanggal'])) ?></strong>
                            <?php if (!empty($data['kategori'])): ?>
                                | <i class="fas fa-folder"></i> <?= h($data['kategori']) ?>
                            <?php endif; ?>
                            | <i class="far fa-eye"></i> <?= number_format((int) ($data['views'] ?? 0)) ?>x Dilihat
                            | Humas Polresta Padang
                        </div>
                    </div>

                    <?php if ($img): ?>
                        <img src="<?= h($img) ?>" alt="<?= h((string) $data['judul']) ?>" class="news-image">
                    <?php endif; ?>

                    <div class="news-body">
                        <?= nl2br(h((string) $data['isi'])) ?>
                    </div>
                </article>

                <!-- Share Section -->
                <div class="share-section">
                    <h4><i class="fas fa-share-alt"></i> Bagikan Berita Ini</h4>
                    <div class="share-buttons">
                        <a class="share-btn wa"
                            href="https://wa.me/?text=<?= urlencode($data['judul'] . ' - ' . $canonicalUrl) ?>"
                            target="_blank" rel="noopener">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </a>
                        <a class="share-btn fb"
                            href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($canonicalUrl) ?>"
                            target="_blank" rel="noopener">
                            <i class="fab fa-facebook-f"></i> Facebook
                        </a>
                        <a class="share-btn tw"
                            href="https://twitter.com/intent/tweet?text=<?= urlencode($data['judul']) ?>&url=<?= urlencode($canonicalUrl) ?>"
                            target="_blank" rel="noopener">
                            <i class="fab fa-twitter"></i> Twitter
                        </a>
                        <button class="share-btn cp"
                            onclick="navigator.clipboard.writeText('<?= h($canonicalUrl) ?>');this.innerHTML='<i class=\'fas fa-check\'></i> Tersalin!';setTimeout(()=>{this.innerHTML='<i class=\'fas fa-link\'></i> Salin Link';},2000);">
                            <i class="fas fa-link"></i> Salin Link
                        </button>
                    </div>
                </div>

                <?php if (!empty($related)): ?>
                    <div class="related-section">
                        <h3><i class="fas fa-newspaper"></i> Berita Terkait</h3>
                        <div class="related-grid">
                            <?php foreach ($related as $r):
                                $rImg = !empty($r['gambar']) ? ($uploadBase . $r['gambar']) : 'assets/placeholder.jpg';
                                $rUrl = !empty($r['slug']) ? ('berita/' . $r['slug']) : ('berita-detail?id=' . $r['id']);
                                ?>
                                <a href="<?= h($rUrl) ?>" class="related-card">
                                    <img src="<?= h($rImg) ?>" alt="<?= h($r['judul']) ?>"
                                        onerror="this.src='assets/placeholder.jpg'">
                                    <div class="related-info">
                                        <h4><?= h($r['judul']) ?></h4>
                                        <small><i class="far fa-calendar-alt"></i>
                                            <?= date('d M Y', strtotime($r['tanggal'])) ?></small>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar Popular News -->
            <aside class="news-sidebar">
                <div class="sidebar-title">
                    <i class="fas fa-fire" style="color:#e67e22;"></i> Terpopuler
                </div>
                <div class="popular-news-list">
                    <?php
                    // Query Popular News (Local Scope)
                    $popSide = [];
                    $psSql = "SELECT id, judul, slug, gambar, views, tanggal FROM berita ORDER BY views DESC, tanggal DESC LIMIT 5";
                    $psRs = $conn->query($psSql);
                    if ($psRs) {
                        while ($p = $psRs->fetch_assoc())
                            $popSide[] = $p;
                        $psRs->close();
                    }
                    ?>

                    <?php if (empty($popSide)): ?>
                        <p style="color:#999;font-size:13px;">Belum ada berita populer.</p>
                    <?php else: ?>
                        <?php $rank = 1;
                        foreach ($popSide as $pop):
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

        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #aaa;">
            &copy; <?= date('Y') ?> Polresta Padang - Bidang Humas
        </div>
    </main>

    <?php require __DIR__ . '/partials/footer.php'; ?>

    <script src="main.js"></script>
</body>

</html>