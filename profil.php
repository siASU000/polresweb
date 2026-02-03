<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profil - Polresta Padang</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Poppins', sans-serif; background-color: #f4f4f4; }

    header {
      background-color: #222;
      padding: 20px 50px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .header-container {
      display: flex;
      justify-content: space-between;
      align-items: center;
      max-width: 1400px;
      margin: 0 auto;
      flex-wrap: wrap;
      gap: 10px;
    }

    .logo { width: 220px; height: auto; }

    nav ul { display: flex; list-style: none; gap: 20px; }
    nav ul li { position: relative; }
    nav ul li a {
      text-decoration: none;
      color: #fff;
      font-weight: 600;
      font-size: 14px;
      text-transform: uppercase;
      transition: color 0.3s ease;
      position: relative;
      display: inline-block;
    }
    nav ul li a:hover { color: #FCC236; }
    nav ul li a:hover::after {
      content: '';
      position: absolute;
      bottom: -5px;
      left: 0;
      width: 100%;
      height: 2px;
      background-color: #FCC236;
      transition: all 0.3s ease;
    }
    nav ul li a.active {
      color: #FCC236;
      border-bottom: 2px solid #FCC236;
    }

    .footer-search input {
      padding: 10px;
      border-radius: 4px;
      border: none;
      width: 250px;
      font-size: 14px;
    }
    .footer-search button {
      padding: 10px 20px;
      background-color: #0c1427;
      color: white;
      border: none;
      border-radius: 4px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.3s ease;
      width: 100px;
    }
    .footer-search button:hover { background-color: #FCC236; transform: scale(1.05); }

    .call-center {
      background-color: #FCC236;
      color: #000;
      padding: 10px 20px;
      border: none;
      font-weight: bold;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.3s ease, transform 0.3s ease;
    }
    .call-center:hover { background-color: #f5a623; transform: scale(1.05); }

    .has-dropdown { position: relative; }
    .has-dropdown .dropdown{
      position: absolute;
      top: 100%;
      left: 0;
      min-width: 260px;
      background: #fff;
      list-style: none;
      padding: 10px 0;
      margin: 12px 0 0;
      border-radius: 6px;
      box-shadow: 0 12px 25px rgba(0,0,0,0.18);
      display: none;
      z-index: 2000;
    }
    .has-dropdown .dropdown li a{
      display: block;
      padding: 14px 18px;
      color: #111;
      text-transform: uppercase;
      font-weight: 600;
      font-size: 12px;
      text-decoration: none;
    }
    .has-dropdown .dropdown li a:hover{
      background: #f2f2f2;
      color: #111;
    }
    .has-dropdown:hover .dropdown{ display: block; }

    main {
      max-width: 1200px;
      margin: 0 auto;
      padding: 30px 20px 60px;
      background: transparent;
    }

    .profil-grid{
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 30px;
      align-items: start;
    }

    .profil-sidebar{
      display: flex;
      flex-direction: column;
      gap: 25px;
    }
    .profil-card{
      border: 1px solid #e6e6e6;
      background: #fff;
    }
    .profil-card-title{
      font-weight: 700;
      font-size: 11px;
      letter-spacing: 0.3px;
      padding: 10px 12px;
      border-bottom: 1px solid #e6e6e6;
    }
    .profil-card-photo{
      background: #d9d9d9;
      padding: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .profil-card-photo img{
      width: 100%;
      height: auto;
      object-fit: cover;
      border-radius: 4px;
    }
    .profil-card-name{
      font-size: 11px;
      font-weight: 600;
      padding: 10px 12px;
    }

    .profil-content{
      display: flex;
      flex-direction: column;
      gap: 40px;
    }
    .vm-row{
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 40px;
    }
    .vm-box h2{
      font-size: 20px;
      font-weight: 800;
      letter-spacing: 1px;
      margin-bottom: 12px;
      text-transform: uppercase;
      color: #111;
    }
    .vm-placeholder{
      background: #d9d9d9;
      height: 300px;
      border-radius: 2px;
    }

    .simple-section{
      margin-top: 30px;
      background: #fff;
      border: 1px solid #e6e6e6;
      padding: 20px;
    }
    .simple-section h2{ margin-bottom: 10px; }

    footer { display: flex; flex-direction: column; margin-top: 50px; }

    .footer-top {
      background-color: #f44336;
      padding: 30px 50px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
    }
    .footer-bottom {
      background-color: #b71c1c;
      padding: 15px 0;
      text-align: center;
      color: white;
      font-size: 14px;
    }
    .footer-info p { margin: 5px 0; font-weight: 500; }

    @media (max-width: 992px){
      .profil-grid{ grid-template-columns: 1fr; }
      .vm-row{ grid-template-columns: 1fr; }
      header{ padding: 20px; }
      .footer-top{ padding: 20px; }
    }

    @media (max-width: 768px){
      .footer-search input, .footer-search button { width: 100%; }
      .footer-top{ flex-direction: column; align-items: center; gap: 12px; }
    }
  </style>
</head>

<body>

<?php require __DIR__ . '/partials/header.php'; ?>

<main>
  <section id="visi-misi">
    <div class="profil-grid">

      <aside class="profil-sidebar">
        <div class="profil-card" id="kapolres">
          <div class="profil-card-title">KAPOLRESTA PADANG</div>
          <div class="profil-card-photo">
            <img src="assets/kapolres padang.jpg" alt="Kapolres Padang">
          </div>
          <div class="profil-card-name">Kombes Pol. Apri Wibowo, S.I.K., M.H.</div>
        </div>

        <div class="profil-card" id="wakapolres">
          <div class="profil-card-title">WAKAPOLRESTA PADANG</div>
          <div class="profil-card-photo">
            <img src="assets/wakapolres padang.jpg" alt="Wakapolres Padang">
          </div>
          <div class="profil-card-name">AKBP Faidil Zikri, S.H., S.I.K., M.Si.</div>
        </div>
      </aside>

      <div class="profil-content">
        <div class="vm-row">
          <div class="vm-box">
            <h2>VISI</h2>
            <div class="vm-placeholder"></div>
          </div>
          <div class="vm-box">
            <h2>MISI</h2>
            <div class="vm-placeholder"></div>
          </div>
        </div>

        <div class="vm-row">
          <div class="vm-box">
            <h2>VISI</h2>
            <div class="vm-placeholder"></div>
          </div>
          <div class="vm-box">
            <h2>MISI</h2>
            <div class="vm-placeholder"></div>
          </div>
        </div>
      </div>

    </div>
  </section>

  <section id="sejarah" class="simple-section">
    <h2>Sejarah</h2>
    <p>Isi sejarah Polresta Padang ditulis di sini.</p>
  </section>

  <section id="struktur" class="simple-section">
    <h2>Struktur Organisasi</h2>
    <p>Isi struktur organisasi ditulis di sini.</p>
  </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
  // Auto active menu berdasarkan nama file
  (function () {
    const current = window.location.pathname.split("/").pop() || "index.php";
    document.querySelectorAll("nav a").forEach(a => {
      const href = a.getAttribute("href");
      if (href === current) a.classList.add("active");
    });
  })();
</script>

</body>
</html>
