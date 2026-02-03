<?php
// partials/footer.php
require_once __DIR__ . '/helpers.php';

$fsRes = $conn->query("SELECT * FROM footer_settings ORDER BY id ASC LIMIT 1");
$fs = $fsRes ? $fsRes->fetch_assoc() : [];

$contact = $fs['contact_text'] ?? 'Kontak kami: 110 - WA +62 811 6693 110';
$fax     = $fs['fax_text'] ?? 'Fax: (0751) 33724';
$copy    = $fs['copyright_text'] ?? '© 2026 Polresta Padang - Kepolisian Negara Republik Indonesia';

$topBg    = $fs['top_bg'] ?? '#ef4444';
$bottomBg = $fs['bottom_bg'] ?? '#991b1b';
?>
<footer>
  <div class="footer-top" style="background: <?= e($topBg) ?>;">
    <div class="footer-info">
      <p><?= e($contact) ?></p>
      <?php if (trim((string)$fax) !== ''): ?>
        <p><?= e($fax) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <div class="footer-bottom" style="background: <?= e($bottomBg) ?>;">
    <p><?= e($copy) ?></p>
  </div>
</footer>
