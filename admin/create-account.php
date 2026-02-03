<?php
// Kalau kamu punya guard login admin, aktifkan:
// $ALLOWED_ROLES = ['admin'];
// require __DIR__ . '/auth_guard.php';
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Account - Admin</title>

  <link rel="stylesheet" href="admin.css" />

  <!-- CSS kecil untuk memastikan layout rapi meski admin.css berbeda -->
  <style>
    /* Background full */
    body.create-account-page{
      min-height: 100vh;
      margin: 0;
      background:
        linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)),
        url("../assets/background_web_polresta.png");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 28px 14px;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
    }

    /* Card form */
    .ca-card{
      width: min(720px, 100%);
      background: rgba(255,255,255,.92);
      border-radius: 14px;
      box-shadow: 0 18px 45px rgba(0,0,0,.22);
      padding: 22px 22px 18px;
      backdrop-filter: blur(6px);
    }

    .ca-title{
      text-align: center;
      margin: 0 0 14px;
      font-weight: 800;
      letter-spacing: .2px;
    }

    .ca-grid{
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }

    @media (max-width: 640px){
      .ca-grid{ grid-template-columns: 1fr; }
    }

    .ca-field label{
      display:block;
      font-weight: 600;
      margin-bottom: 6px;
    }

    .ca-field input, .ca-field select{
      width: 100%;
      padding: 12px 12px;
      border: 1px solid rgba(0,0,0,.15);
      border-radius: 10px;
      outline: none;
      font-size: 15px;
      background: #fff;
    }

    .ca-field input:focus, .ca-field select:focus{
      border-color: rgba(0,78,146,.6);
      box-shadow: 0 0 0 3px rgba(0,78,146,.12);
    }

    .ca-actions{
      display:flex;
      gap: 10px;
      align-items:center;
      justify-content: space-between;
      margin-top: 16px;
      flex-wrap: wrap;
    }

    .ca-btn{
      border: none;
      background: #0b5ed7;
      color: #fff;
      padding: 12px 16px;
      border-radius: 10px;
      font-weight: 700;
      cursor: pointer;
      min-width: 180px;
    }
    .ca-btn:hover{ filter: brightness(.95); }

    .ca-back{
      color: #0b5ed7;
      text-decoration: underline;
      font-weight: 600;
    }

    .ca-note{
      margin-top: 10px;
      font-size: 13px;
      color: rgba(0,0,0,.65);
    }
  </style>
</head>

<body class="create-account-page">
  <section class="ca-card">
    <h1 class="ca-title">Buat Akun Admin/Editor</h1>

    <form id="createAccountForm" autocomplete="off">
      <div class="ca-grid">
        <div class="ca-field" style="grid-column: 1 / -1;">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Masukkan username" required />
        </div>

        <div class="ca-field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Masukkan password" required />
        </div>

        <div class="ca-field">
          <label for="confirm-password">Confirm Password</label>
          <input id="confirm-password" type="password" placeholder="Ulangi password" required />
        </div>

        <div class="ca-field" style="grid-column: 1 / -1;">
          <label for="role">Role</label>
          <select id="role" name="role" required>
            <option value="">Pilih role</option>
            <option value="admin">admin</option>
            <option value="editor">editor</option>
          </select>
        </div>
      </div>

      <div class="ca-actions">
        <a class="ca-back" href="dashboard.php">Kembali</a>
        <button class="ca-btn" type="submit">Simpan</button>
      </div>

      <div class="ca-note">
        Catatan: Username harus unik. Role hanya <b>admin</b> atau <b>editor</b>.
      </div>
    </form>
  </section>

  <script src="create-account.js"></script>
</body>
</html>
