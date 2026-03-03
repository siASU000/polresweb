<?php
require __DIR__ . '/admin/db_connection.php';

echo "Updating Database...\n";

$res = $conn->query("SHOW COLUMNS FROM header_settings LIKE 'tagline'");
if ($res->num_rows == 0) {
    if ($conn->query("ALTER TABLE header_settings ADD COLUMN tagline VARCHAR(255) DEFAULT 'Melayani dengan Sepenuh Hati'")) {
        echo "[OK] Column 'tagline' added.\n";
    } else {
        echo "[ERR] Failed adding column: " . $conn->error . "\n";
    }
} else {
    echo "[SKIP] Column 'tagline' exists.\n";
}

$res = $conn->query("SELECT id FROM header_menu WHERE label IN ('Home', 'HOME', 'Beranda') OR url IN ('index.php', 'index')");
if ($res && $row = $res->fetch_assoc()) {
    $id = $row['id'];

    if ($conn->query("UPDATE header_menu SET label='DASHBOARD', url='dashboard' WHERE id=$id")) {
        echo "[OK] Menu ID $id updated to DASHBOARD.\n";
    }
} else {

    $conn->query("INSERT INTO header_menu (label, url, sort_order, is_active) VALUES ('DASHBOARD', 'dashboard', 0, 1)");
    echo "[OK] Menu DASHBOARD inserted.\n";
}

echo "Database update complete.\n";
