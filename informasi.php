<?php
declare(strict_types=1);

ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/admin/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
  die("Koneksi database tidak valid.");
}
$conn->set_charset('utf8mb4');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

$uploadBase = 'uploads/informasi/';

$dpo  = [];
$oh   = [];
$lain = [];

$sql = "SELECT *
        FROM informasi
        WHERE is_active = 1
        ORDER BY kategori ASC, urutan ASC, updated_at DESC, created_at DESC, id DESC";
$rs = $conn->query($sql);
if ($rs) {
  while ($row = $rs->fetch_assoc()) {
    if (($row['kategori'] ?? '') === 'dpo') $dpo[] = $row;
    elseif (($row['kategori'] ?? '') === 'orang_hilang') $oh[] = $row;
    else $lain[] = $row;
  }
  $rs->free();
}

function tipeLabel(?string $t): string {
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

<!-- PENTING: tambahkan class ini supaya CSS modern jalan -->
<body class="page-informasi">

<?php require __DIR__ . '/partials/header.php'; ?>

<!-- PENTING: hapus inline style, biar semua di-handle style.css -->
<main>

  <!-- DPO -->
  <section id="dpo">
    <h2>DPO (Daftar Pencarian Orang)</h2>
    <p style="margin-top:-8px;color:#666;">Daftar orang yang sedang dalam pencarian. Jika Anda memiliki informasi, silakan hubungi Call Center.</p>

    <div class="info-card-grid">
      <?php if (count($dpo) === 0): ?>
        <p style="color:#777;">Belum ada data DPO.</p>
      <?php else: ?>
        <?php foreach ($dpo as $it): ?>
          <?php
            $img = '';
            if (!empty($it['gambar'])) {
              $img = $uploadBase . $it['gambar'];
              if (!is_file(__DIR__ . '/' . $img)) $img = '';
            }
            $btnText = trim((string)($it['tombol_text'] ?? 'Laporkan'));
            $btnUrl  = trim((string)($it['tombol_url'] ?? '#'));
            $desc    = trim((string)($it['deskripsi'] ?? ''));
          ?>
          <div class="info-card">
            <div class="info-photo" style="<?= $img ? "background-image:url('".h($img)."');" : "" ?>"></div>

            <div class="info-meta">
              <div><b>Nama:</b> <?= h((string)($it['nama'] ?? '-')) ?></div>
              <div class="muted"><b>Kasus:</b> <?= h((string)($it['subjudul'] ?? '-')) ?></div>

              <?php if ($desc !== ''): ?>
                <div class="muted info-desc"><b>Deskripsi:</b> <?= h($desc) ?></div>
              <?php endif; ?>

              <div class="muted"><b>Terakhir terlihat:</b> <?= h((string)($it['terakhir_terlihat'] ?? '-')) ?></div>

              <div class="info-actions">
  <a class="info-btn" href="<?= h($btnUrl !== '' ? $btnUrl : '#') ?>" target="_blank" rel="noopener">
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
  <section id="orang-hilang" style="margin-top:38px;">
    <h2>Orang Hilang</h2>
    <p style="margin-top:-8px;color:#666;">Informasi orang hilang. Mohon bantuan masyarakat untuk penyebaran informasi.</p>

    <div class="info-card-grid">
      <?php if (count($oh) === 0): ?>
        <p style="color:#777;">Belum ada data Orang Hilang.</p>
      <?php else: ?>
        <?php foreach ($oh as $it): ?>
          <?php
            $img = '';
            if (!empty($it['gambar'])) {
              $img = $uploadBase . $it['gambar'];
              if (!is_file(__DIR__ . '/' . $img)) $img = '';
            }
            $btnText = trim((string)($it['tombol_text'] ?? 'Hubungi'));
            $btnUrl  = trim((string)($it['tombol_url'] ?? '#'));
            $desc    = trim((string)($it['deskripsi'] ?? ''));
          ?>
          <div class="info-card">
            <div class="info-photo" style="<?= $img ? "background-image:url('".h($img)."');" : "" ?>"></div>

            <div class="info-meta">
              <div><b>Nama:</b> <?= h((string)($it['nama'] ?? '-')) ?></div>
              <div class="muted"><b>Ciri-ciri:</b> <?= h((string)($it['subjudul'] ?? '-')) ?></div>

              <?php if ($desc !== ''): ?>
                <div class="muted info-desc"><b>Deskripsi:</b> <?= h($desc) ?></div>
              <?php endif; ?>

              <div class="muted"><b>Terakhir terlihat:</b> <?= h((string)($it['terakhir_terlihat'] ?? '-')) ?></div>

              <div class="info-actions">
  <a class="info-btn" href="<?= h($btnUrl !== '' ? $btnUrl : '#') ?>" target="_blank" rel="noopener">
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

  <!-- Informasi Lainnya -->
  <section id="informasi-lainnya" style="margin-top:38px;">
    <h2>Informasi Lainnya</h2>
    <p style="margin-top:-8px;color:#666;">Pengumuman, himbauan, atau informasi pelayanan publik lainnya.</p>

    <div class="info-table">
      <?php if (count($lain) === 0): ?>
        <p style="color:#777; padding:12px 16px;">Belum ada Informasi Lainnya.</p>
      <?php else: ?>
        <?php foreach ($lain as $it): ?>
          <?php
            $tag   = tipeLabel($it['tipe'] ?? null);
            $judul = trim((string)($it['judul'] ?? '-'));
            $tgl   = (string)($it['updated_at'] ?? $it['created_at'] ?? '');
          ?>
          <div class="info-row">
            <div class="info-tag"><?= h($tag) ?></div>
            <div class="info-title"><?= h($judul) ?></div>
            <div class="info-date"><?= h($tgl) ?></div>
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
