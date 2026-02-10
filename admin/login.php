<?php
declare(strict_types=1);
session_start();
require_once __DIR__ . '/db_connection.php';

function safe_next(string $next, string $fallback): string {
    $next = trim($next);
    if ($next === '') return $fallback;

    // blok open redirect (URL luar)
    if (preg_match('#^(https?:)?//#i', $next)) return $fallback;

    // hanya izinkan path absolut internal
    if ($next[0] !== '/') return $fallback;

    // batasi hanya dalam aplikasi ini (sesuaikan jika folder berbeda)
    if (stripos($next, '/webandruy/admin/') !== 0) return $fallback;

    return $next;
}

$defaultAfterLogin = '/webandruy/admin/dashboard.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $nextPost = (string)($_POST['next'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password, role FROM admin WHERE username = ? LIMIT 1');
        if (!$stmt) {
            $error = 'Query error: ' . $conn->error;
        } else {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            if (!$row || !password_verify($password, (string)$row['password'])) {
                $error = 'Username atau password salah';
            } else {
                $_SESSION['auth'] = true;
                $_SESSION['admin_id'] = (int)$row['id'];
                $_SESSION['username'] = (string)$row['username'];
                $_SESSION['role'] = (string)$row['role'];

                $dest = safe_next($nextPost, $defaultAfterLogin);
                header('Location: ' . $dest);
                exit;
            }
        }
    }
}

$nextGet = (string)($_GET['next'] ?? '');
$nextValue = htmlspecialchars(safe_next($nextGet, $defaultAfterLogin), ENT_QUOTES, 'UTF-8');
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login - Polresta Padang</title>

  <!-- PENTING: path ini harus benar -->
  <link rel="stylesheet" href="admin.css?v=1">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>

<body class="admin-login-page">
  <div class="login-shell">
    <div class="login-card">
      <div class="login-brand">
        <!-- GANTI nama file logo sesuai yang kamu punya -->
        <img class="login-logo" src="../assets/logo polresta padang.png" alt="Polresta Padang">
      </div>

      <div class="login-title">LOGIN</div>

      <?php if ($error !== ''): ?>
        <div style="width:min(420px, 78vw); margin-bottom:12px; color:#b00020; font-size:13px;">
          <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
      <?php endif; ?>

      <form class="login-form" method="post" action="login.php" autocomplete="off">
        <label class="login-label" for="username">USERNAME</label>
        <input class="login-input" type="text" id="username" name="username" placeholder="Masukkan Username" required>

        <label class="login-label" for="password">PASSWORD</label>
        <input class="login-input" type="password" id="password" name="password" placeholder="Masukkan Password" required>

        <input type="hidden" name="next" value="<?= $nextValue ?>">

        <a class="login-forgot" href="#" onclick="return false;">lupa password?</a>

        <button class="login-btn" type="submit">Login</button>
      </form>
    </div>
  </div>
</body>
</html>
