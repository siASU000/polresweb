<?php
declare(strict_types=1);

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

// AUTH
$ALLOWED_ROLES = ['admin', 'editor'];
require __DIR__ . '/auth_guard.php';

// DB (root/db_connection.php)
require __DIR__ . '/db_connection.php';
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Koneksi database tidak valid. Pastikan db_connection.php membuat variabel \$conn (mysqli).");
}
$conn->set_charset("utf8mb4");

function e(string $v): string {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
}

// ambil settings (row pertama)
$res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
$settings = $res ? $res->fetch_assoc() : null;

// kalau belum ada row, buat 1 row kosong
if (!$settings) {
    $conn->query("INSERT INTO contact_settings (address, phone, whatsapp, email, fax, op_hours, maps_embed)
                VALUES ('','','','','','','')");
    $res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
    $settings = $res ? $res->fetch_assoc() : [];
}

$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $address    = trim($_POST['address'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $whatsapp   = trim($_POST['whatsapp'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $fax        = trim($_POST['fax'] ?? '');
    $op_hours   = trim($_POST['op_hours'] ?? '');
    $maps_embed = trim($_POST['maps_embed'] ?? '');

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $flash = ['type' => 'danger', 'msg' => 'Format email tidak valid.'];
    } else {
        $id = (int)($settings['id'] ?? 0);

        $stmt = $conn->prepare("
            UPDATE contact_settings
            SET address=?, phone=?, whatsapp=?, email=?, fax=?, op_hours=?, maps_embed=?
            WHERE id=?
        ");
        $stmt->bind_param("sssssssi", $address, $phone, $whatsapp, $email, $fax, $op_hours, $maps_embed, $id);
        $ok = $stmt->execute();
        $stmt->close();

        $flash = $ok
            ? ['type' => 'success', 'msg' => 'Kontak berhasil disimpan.']
            : ['type' => 'danger', 'msg' => 'Gagal menyimpan. ' . $conn->error];

        $res = $conn->query("SELECT * FROM contact_settings ORDER BY id ASC LIMIT 1");
        $settings = $res ? $res->fetch_assoc() : $settings;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Kelola Kontak | Admin Polresta Padang</title>
    <link rel="stylesheet" href="admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f5b400;
            --dark-blue: #1e293b;
            --bg-body: #f8fafc;
            --border-color: #e2e8f0;
        }
        body { background: var(--bg-body); font-family: 'Inter', system-ui, sans-serif; color: #334155; }
        .wrap { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        .header-section { margin-bottom: 30px; border-left: 5px solid var(--primary-color); padding-left: 15px; }
        .header-section h1 { font-size: 24px; font-weight: 800; color: var(--dark-blue); margin: 0; }
        .header-section p { color: #64748b; margin-top: 5px; }

        .card { background: #fff; border-radius: 16px; padding: 30px; border: 1px solid var(--border-color); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        
        .form-group { margin-bottom: 20px; }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .col { flex: 1; min-width: 280px; }
        
        label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #475569; }
        label i { margin-right: 8px; color: var(--primary-color); width: 18px; text-align: center; }

        input, textarea { 
            width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; 
            outline: none; transition: all 0.3s; font-size: 15px; background: #fff; box-sizing: border-box;
        }
        input:focus, textarea:focus { border-color: var(--primary-color); box-shadow: 0 0 0 4px rgba(245, 180, 0, 0.1); }
        
        textarea { min-height: 100px; resize: vertical; }
        .maps-area { background: #f1f5f9; font-family: monospace; font-size: 13px; }

        .btn-submit { 
            background: var(--dark-blue); color: #fff; border: none; padding: 14px 28px; 
            border-radius: 10px; font-weight: 700; cursor: pointer; width: 100%; 
            transition: all 0.3s; display: flex; align-items: center; justify-content: center; gap: 10px;
            margin-top: 10px; text-transform: uppercase; letter-spacing: 1px;
        }
        .btn-submit:hover { background: #0f172a; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }

        .alert { padding: 15px; border-radius: 12px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-weight: 500; }
        .success { background: #ecfdf5; border: 1px solid #10b981; color: #065f46; }
        .danger { background: #fef2f2; border: 1px solid #ef4444; color: #991b1b; }

        /* Responsive */
        @media (max-width: 600px) { .row { flex-direction: column; gap: 0; } }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="header-section">
            <h1>Kelola Kontak</h1>
            <p>Atur informasi kontak dan lokasi Polresta Padang yang tampil di halaman publik.</p>
        </div>

        <?php if ($flash): ?>
            <div class="alert <?= e($flash['type']) === 'success' ? 'success' : 'danger' ?>">
                <i class="fa-solid <?= e($flash['type']) === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                <?= e($flash['msg']) ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <form method="post">
                <div class="form-group">
                    <label><i class="fa-solid fa-location-dot"></i>Alamat Kantor</label>
                    <textarea name="address" placeholder="Masukkan alamat lengkap kantor..."><?= e((string)($settings['address'] ?? '')) ?></textarea>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label><i class="fa-solid fa-phone"></i>Nomor Telepon</label>
                        <input name="phone" value="<?= e((string)($settings['phone'] ?? '')) ?>" placeholder="Contoh: 110 / (0751) 22311">
                    </div>
                    <div class="col form-group">
                        <label><i class="fa-brands fa-whatsapp"></i>WhatsApp</label>
                        <input name="whatsapp" value="<?= e((string)($settings['whatsapp'] ?? '')) ?>" placeholder="Contoh: +62811xxxxxxx">
                    </div>
                </div>

                <div class="row">
                    <div class="col form-group">
                        <label><i class="fa-solid fa-envelope"></i>Email Resmi</label>
                        <input type="email" name="email" value="<?= e((string)($settings['email'] ?? '')) ?>" placeholder="contoh@polri.go.id">
                    </div>
                    <div class="col form-group">
                        <label><i class="fa-solid fa-print"></i>Nomor Fax</label>
                        <input name="fax" value="<?= e((string)($settings['fax'] ?? '')) ?>" placeholder="Contoh: (0751) 33724">
                    </div>
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-clock"></i>Jam Operasional</label>
                    <input name="op_hours" value="<?= e((string)($settings['op_hours'] ?? '')) ?>" placeholder="Contoh: Senin – Jumat 08.00 – 16.00">
                </div>

                <div class="form-group">
                    <label><i class="fa-solid fa-map-location-dot"></i>Google Maps Embed Code</label>
                    <textarea name="maps_embed" class="maps-area" placeholder="Tempel kode <iframe> dari Google Maps di sini..."><?= e((string)($settings['maps_embed'] ?? '')) ?></textarea>
                    <small style="color: #64748b; font-size: 12px; margin-top: 5px; display: block;">
                        *Buka Google Maps > Share > Embed a map > Copy HTML.
                    </small>
                </div>

                <button class="btn-submit" type="submit">
                    <i class="fa-solid fa-floppy-disk"></i> SIMPAN PERUBAHAN
                </button>
            </form>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" style="color: #64748b; text-decoration: none; font-size: 14px;">
                <i class="fa-solid fa-arrow-left"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>