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

$uploadBase = 'uploads/informasi/';

$dpo = [];
$oh = [];
$lain = [];

$sql = "SELECT *
        FROM informasi
        WHERE is_active = 1
        ORDER BY kategori ASC, urutan ASC, updated_at DESC, created_at DESC, id DESC";
$rs = $conn->query($sql);
if ($rs) {
  while ($row = $rs->fetch_assoc()) {
    if (($row['kategori'] ?? '') === 'dpo')
      $dpo[] = $row;
    elseif (($row['kategori'] ?? '') === 'orang_hilang')
      $oh[] = $row;
    else
      $lain[] = $row;
  }
  $rs->free();
}

function tipeLabel(?string $t): string
{
  return match ($t) {
    'himbauan' => 'HIMBAUAN',
    'pengumuman' => 'PENGUMUMAN',
    'layanan' => 'LAYANAN',
    default => 'INFO'
  };
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Informasi - Polresta Padang</title>
  <link rel="stylesheet" href="style.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="page-informasi">

  <?php require __DIR__ . '/partials/header.php'; ?>

  <main class="informasi-wrap">


    <!-- DPO & Orang Hilang 2 Column Grid -->
    <div class="two-column-grid">

      <!-- DPO -->
      <section id="dpo" class="info-section" style="margin-top:0;">
        <div class="info-header">
          <h2>DPO (Daftar Pencarian Orang)</h2>
          <p>Daftar orang yang sedang dalam pencarian.</p>
        </div>

        <div class="info-grid" style="grid-template-columns: 1fr;"> <!-- Override to 1 column inside grid -->
          <?php if (count($dpo) === 0): ?>
            <p class="info-empty">Belum ada data DPO.</p>
          <?php else: ?>
            <?php foreach ($dpo as $it): ?>
              <?php
              $img = '';
              if (!empty($it['gambar'])) {
                $img = $uploadBase . $it['gambar'];
                if (!is_file(__DIR__ . '/' . $img))
                  $img = '';
              }
              $btnText = trim((string) ($it['tombol_text'] ?? 'Laporkan'));
              $btnUrl = trim((string) ($it['tombol_url'] ?? '#'));
              $desc = trim((string) ($it['deskripsi'] ?? ''));
              ?>
              <div class="info-card">
                <div class="info-photo"
                  style="<?= $img ? "background-image:url('" . h($img) . "'); background-size:cover; background-position:center;" : "" ?>">
                </div>
                <div class="info-card-body">
                  <div class="info-meta">
                    <h3><?= h((string) ($it['nama'] ?? '-')) ?></h3>
                    <p><strong>Kasus:</strong> <?= h((string) ($it['subjudul'] ?? '-')) ?></p>
                    <?php if ($desc !== ''): ?>
                      <p><?= h($desc) ?></p>
                    <?php endif; ?>
                    <p><strong>Terakhir:</strong> <?= h((string) ($it['terakhir_terlihat'] ?? '-')) ?></p>
                  </div>
                  <div class="info-card-actions">
                    <a class="info-btn" href="<?= h($btnUrl !== '' && $btnUrl !== '-' ? $btnUrl : '#') ?>" target="_blank"
                      rel="noopener">
                      <?= h($btnText) ?>
                    </a>
                    <a class="info-btn info-btn--cc" href="https://wa.me/628116693110" target="_blank" rel="noopener">
                      CALL CENTER
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <!-- Orang Hilang -->
      <section id="orang-hilang" class="info-section" style="margin-top:0;">
        <div class="info-header">
          <h2>Orang Hilang</h2>
          <p>Informasi orang hilang.</p>
        </div>

        <div class="info-grid" style="grid-template-columns: 1fr;"> <!-- Override to 1 column inside grid -->
          <?php if (count($oh) === 0): ?>
            <p class="info-empty">Belum ada data Orang Hilang.</p>
          <?php else: ?>
            <?php foreach ($oh as $it): ?>
              <?php
              $img = '';
              if (!empty($it['gambar'])) {
                $img = $uploadBase . $it['gambar'];
                if (!is_file(__DIR__ . '/' . $img))
                  $img = '';
              }
              $btnText = trim((string) ($it['tombol_text'] ?? 'Hubungi'));
              $btnUrl = trim((string) ($it['tombol_url'] ?? '#'));
              $desc = trim((string) ($it['deskripsi'] ?? ''));
              ?>
              <div class="info-card">
                <div class="info-photo"
                  style="<?= $img ? "background-image:url('" . h($img) . "'); background-size:cover; background-position:center;" : "" ?>">
                </div>
                <div class="info-card-body">
                  <div class="info-meta">
                    <h3><?= h((string) ($it['nama'] ?? '-')) ?></h3>
                    <p><strong>Ciri:</strong> <?= h((string) ($it['subjudul'] ?? '-')) ?></p>
                    <?php if ($desc !== ''): ?>
                      <p><?= h($desc) ?></p>
                    <?php endif; ?>
                    <p><strong>Terakhir:</strong> <?= h((string) ($it['terakhir_terlihat'] ?? '-')) ?></p>
                  </div>
                  <div class="info-card-actions">
                    <a class="info-btn" href="<?= h($btnUrl !== '' && $btnUrl !== '-' ? $btnUrl : '#') ?>" target="_blank"
                      rel="noopener">
                      <?= h($btnText) ?>
                    </a>
                    <a class="info-btn info-btn--cc" href="https://wa.me/628116693110" target="_blank" rel="noopener">
                      CALL CENTER
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

    </div>


    <!-- Informasi Lainnya -->
    <section id="informasi-lainnya" class="info-section">
      <div class="info-header">
        <h2>Informasi Lainnya</h2>
        <p>Pengumuman, himbauan, atau informasi pelayanan publik lainnya.</p>
      </div>

      <div class="info-list">
        <?php if (count($lain) === 0): ?>
          <p class="info-empty" style="padding:12px 16px;">Belum ada Informasi Lainnya.</p>
        <?php else: ?>
          <?php foreach ($lain as $it): ?>
            <?php
            $tag = tipeLabel($it['tipe'] ?? null);
            $judul = trim((string) ($it['judul'] ?? '-'));
            $tgl = (string) ($it['updated_at'] ?? $it['created_at'] ?? '');
            ?>
            <div class="info-list-item">
              <div class="tag"><?= h($tag) ?></div>
              <div class="title"><?= h($judul) ?></div>
              <div class="meta"><?= h($tgl) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

  </main>

  <?php require __DIR__ . '/partials/footer.php'; ?>

  <script src="main.js"></script>
</body>

</html>