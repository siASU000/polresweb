<?php
// partials/footer.php
$fsRes = $conn->query("SELECT * FROM footer_settings WHERE id=1");
$fs = $fsRes ? $fsRes->fetch_assoc() : [];

$contact  = $fs['contact_text'] ?? 'Kontak kami: 110 - WA +62 811 6693 110';
$fax      = $fs['fax_text'] ?? 'Fax: (0751) 33724';
$copy     = $fs['copyright_text'] ?? '© 2026 Polresta Padang';
$topBg    = $fs['top_bg'] ?? '#ef4444';
$botBg    = $fs['bottom_bg'] ?? '#991b1b';
?>
<footer style="color: #fff; font-family: sans-serif;">
    <div style="background: <?= e($topBg) ?>; padding: 25px 20px;">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <p style="margin: 0; font-weight: bold; font-size: 1.1rem;"><?= e($contact) ?></p>
            <?php if(trim((string)$fax) !== ''): ?>
                <p style="margin: 8px 0 0 0; opacity: 0.8;"><?= e($fax) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div style="background: <?= e($botBg) ?>; padding: 15px 20px; text-align: center; font-size: 0.9rem;">
        <div class="container">
            <?= e($copy) ?>
        </div>
    </div>
</footer>