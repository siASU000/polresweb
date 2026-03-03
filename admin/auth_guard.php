<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$allowedRoles = $ALLOWED_ROLES ?? null;

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$loginUrl = '/webandruy/admin/login.php'; 

$isLoggedIn = !empty($_SESSION['auth']) && $_SESSION['auth'] === true;

if (!$isLoggedIn) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '');

    echo "<form id='loginForm' action='{$loginUrl}' method='POST'>
        <input type='hidden' name='next' value='{$next}'>
        <input type='submit' style='display:none'>
      </form>";
    echo "<script>document.getElementById('loginForm').submit();</script>";
    exit;

    exit;
}

if (is_array($allowedRoles)) {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, $allowedRoles, true)) {
        http_response_code(403);
        echo "403 Forbidden";
        exit;
    }
}
