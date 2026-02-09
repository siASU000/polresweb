<?php
// admin/kelola-footer.php
declare(strict_types=1);

$ALLOWED_ROLES = ['admin','editor'];
require __DIR__ . '/auth_guard.php';
require __DIR__ . '/db_connection.php';

function e(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact = trim($_POST['contact_text'] ?? '');
    $fax = trim($_POST['fax_text'] ?? '');
    $copy = trim($_POST['copyright_text'] ?? '');
    $top = trim($_POST['top_bg'] ?? '#ef4444');
    $bottom = trim($_POST['bottom_bg'] ?? '#991b1b');

    if ($contact === '' || $copy === '') {
        $flash = ['type'=>'err','msg'=>'Contact text & copyright wajib diisi.'];
    } else {
        $stmt = $conn->prepare("UPDATE footer_settings SET contact_text=?, fax_text=?, copyright_text=?, top_bg=?, bottom_bg=? WHERE id=1");
        $stmt->bind_param("sssss", $contact, $fax, $copy, $top, $bottom);
        $stmt->execute();
        $stmt->close();
        $flash = ['type'=>'ok','msg'=>'Footer berhasil diperbarui!'];
    }
}

$fsRes = $conn->query("SELECT * FROM footer_settings WHERE id=1");
$fs = $fsRes ? $fsRes->fetch_assoc() : [];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Footer | Polresta Padang</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #f5b400; --dark: #1e293b; --bg: #f1f5f9; }
        body { background: var(--bg); font-family: 'Inter', sans-serif; color: #334155; }
        .wrap { max-width: 1000px; margin: 40px auto; padding: 0 20px; }
        
        .header-title { margin-bottom: 25px; border-left: 5px solid var(--primary); padding-left: 15px; }
        .header-title h2 { margin: 0; font-weight: 800; color: var(--dark); }

        .card { background: #fff; border-radius: 16px; padding: 25px; margin-bottom: 24px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: span 2; }

        label { font-weight: 700; font-size: 13px; display: block; margin-bottom: 8px; color: #475569; text-transform: uppercase; }
        input[type="text"] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 10px; outline: none; transition: 0.2s; box-sizing: border-box; }
        input[type="text"]:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(245, 180, 0, 0.1); }
        
        /* Styling Color Picker */
        .color-input-wrapper { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 8px; border-radius: 10px; border: 1px solid #cbd5e1; }
        input[type="color"] { border: none; width: 40px; height: 40px; cursor: pointer; background: none; }
        
        .btn-save { background: var(--dark); color: #fff; padding: 14px 24px; border-radius: 10px; border: 0; cursor: pointer; font-weight: 700; width: 100%; transition: 0.3s; display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px; }
        .btn-save:hover { background: #000; transform: translateY(-2px); }

        .alert { padding: 15px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .alert-ok { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-err { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        /* Preview Box */
        .preview-label { margin-bottom: 15px; display: flex; align-items: center; gap: 8px; font-weight: 800; color: var(--dark); }
        .preview-container { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .pTop { padding: 30px; color: #fff; transition: background 0.3s ease; }
        .pBot { padding: 15px; color: #fff; text-align: center; font-size: 14px; transition: background 0.3s ease; }
        
        @media (max-width: 768px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header-title">
            <h2>Kelola Footer</h2>
            <p style="color: #64748b; margin-top: 5px;">Sesuaikan teks kontak, copyright, dan skema warna footer utama.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] ?>">
                <i class="fa-solid <?= $flash['type']==='ok' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post" id="footerForm">
                <div class="form-grid">
                    <div class="full-width">
                        <label><i class="fa-solid fa-address-book"></i> Contact Text (Bar Atas)</label>
                        <input type="text" name="contact_text" id="inContact" value="<?= e((string)($fs['contact_text'] ?? '')) ?>" placeholder="Contoh: Kontak kami: 110 - WA +62...">
                    </div>

                    <div class="full-width">
                        <label><i class="fa-solid fa-print"></i> Fax Text</label>
                        <input type="text" name="fax_text" id="inFax" value="<?= e((string)($fs['fax_text'] ?? '')) ?>" placeholder="Contoh: Fax: (0751) 33724">
                    </div>

                    <div class="full-width">
                        <label><i class="fa-solid fa-copyright"></i> Copyright Text (Bar Bawah)</label>
                        <input type="text" name="copyright_text" id="inCopy" value="<?= e((string)($fs['copyright_text'] ?? '')) ?>">
                    </div>

                    <div>
                        <label>Warna Bar Atas</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="top_bg" id="inTopBg" value="<?= e((string)($fs['top_bg'] ?? '#ef4444')) ?>">
                            <span id="topHex"><?= e((string)($fs['top_bg'] ?? '#ef4444')) ?></span>
                        </div>
                    </div>

                    <div>
                        <label>Warna Bar Bawah</label>
                        <div class="color-input-wrapper">
                            <input type="color" name="bottom_bg" id="inBotBg" value="<?= e((string)($fs['bottom_bg'] ?? '#991b1b')) ?>">
                            <span id="botHex"><?= e((string)($fs['bottom_bg'] ?? '#991b1b')) ?></span>
                        </div>
                    </div>
                </div>

                <button class="btn-save" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> SIMPAN PERUBAHAN FOOTER
                </button>
            </form>
        </div>

        <div class="preview-label">
            <i class="fa-solid fa-eye"></i> LIVE PREVIEW
        </div>
        <div class="preview-container">
            <div class="pTop" id="pvTop" style="background:<?= e((string)($fs['top_bg'] ?? '#ef4444')) ?>">
                <div id="pvContact" style="font-weight: 600; font-size: 18px;"><?= e((string)($fs['contact_text'] ?? '')) ?></div>
                <div id="pvFax" style="margin-top:8px; opacity: 0.9;"><?= e((string)($fs['fax_text'] ?? '')) ?></div>
            </div>
            <div class="pBot" id="pvBot" style="background:<?= e((string)($fs['bottom_bg'] ?? '#991b1b')) ?>">
                <div id="pvCopy"><?= e((string)($fs['copyright_text'] ?? '')) ?></div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="dashboard.php" style="color: #64748b; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script>
        // Script untuk Live Preview tanpa reload
        const inputs = [
            {in: 'inContact', pv: 'pvContact', type: 'text'},
            {in: 'inFax', pv: 'pvFax', type: 'text'},
            {in: 'inCopy', pv: 'pvCopy', type: 'text'},
            {in: 'inTopBg', pv: 'pvTop', type: 'color', hex: 'topHex'},
            {in: 'inBotBg', pv: 'pvBot', type: 'color', hex: 'botHex'}
        ];

        inputs.forEach(item => {
            const el = document.getElementById(item.in);
            el.addEventListener('input', () => {
                if(item.type === 'text') {
                    document.getElementById(item.pv).innerText = el.value;
                } else {
                    document.getElementById(item.pv).style.background = el.value;
                    document.getElementById(item.hex).innerText = el.value.toUpperCase();
                }
            });
        });
    </script>
</body>
</html>