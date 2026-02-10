<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * OPTIONAL:
 * Di tiap halaman bisa set:
 * $ALLOWED_ROLES = ['admin']; atau ['admin','editor'];
 */
$allowedRoles = $ALLOWED_ROLES ?? null;

// No-cache biar tombol Back tidak menampilkan halaman admin setelah logout
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Sesuaikan path login ini dengan punyamu
$loginUrl = '/webandruy/admin/login.php'; // Ubah ke login.php
// Kalau URL kamu pakai /webandruy/ maka ubah sesuai itu

$isLoggedIn = !empty($_SESSION['auth']) && $_SESSION['auth'] === true;

if (!$isLoggedIn) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '');

// Sesuaikan path login dan kirim dengan POST
echo "<form id='loginForm' action='{$loginUrl}' method='POST'>
        <input type='hidden' name='next' value='{$next}'>
        <input type='submit' style='display:none'>
      </form>";
echo "<script>document.getElementById('loginForm').submit();</script>";
exit;

    exit;
}

// Jika pakai role-based access
if (is_array($allowedRoles)) {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }
}
