<?php
$ALLOWED_ROLES = ['admin','editor']; // jika mau admin saja: ['admin']
require __DIR__ . "/auth_guard.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Kelola Home - Admin Polresta Padang</title>

  <link rel="stylesheet" href="admin.css" />
  <link rel="stylesheet" href="dashboard.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>

<body class="admin-dashboard-page">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <span class="badge">ADMIN</span>
      <span class="brand">Polresta Padang</span>
    </div>
    <div class="topbar-right">
      <span class="top-username">admin</span>
      <a href="logout.php" class="btn-logout">Logout</a>
    </div>
  </header>

  <div class="layout">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="sidebar-title">Menu</div>

      <a class="nav-item" href="dashboard.php">Profil Admin</a>

      <div class="sidebar-title mt">Kelola Konten</div>
      <a class="nav-item active" href="kelola-home.php">Kelola Home</a>
      <a class="nav-item" href="kelola-berita.php">Kelola Berita</a>
      <a class="nav-item" href="kelola-galeri.php">Kelola Galeri</a>
      <a class="nav-item" href="kelola-informasi.php">Kelola Informasi</a>
      <a class="nav-item" href="kelola-kontak.php">Kelola Kontak</a>
    </aside>

    <main class="content">
      <div class="page-head">
        <div>
          <h1 class="page-title">Kelola Home</h1>
          <p class="page-subtitle">Atur banner dan layanan utama yang tampil di halaman Home.</p>
        </div>
      </div>

      <!-- CARD UTAMA -->
      <section class="card home-card">
        <!-- Header card -->
        <div class="card-head">
          <div class="card-head-left">
            <h2 class="card-title">Konfigurasi Home</h2>
            <p class="card-subtitle">
              Penyimpanan sementara menggunakan <b>localStorage</b> (browser).
            </p>
          </div>

          <div class="card-head-right">
            <a class="btn-outline" href="dashboard.php" style="text-decoration:none;">← Kembali</a>
            <button class="btn-outline" id="btnReset" type="button">Reset</button>
            <button class="btn" id="btnSave" type="button">Simpan</button>
            <span id="saveStatus" class="status"></span>
          </div>
        </div>

        <div class="divider"></div>

        <div class="form-grid home-form">

          <!-- A. Banner -->
          <div class="field field-full">
            <div class="section-title">A. Banner Home</div>
          </div>

          <div class="field">
            <label for="bannerTop">Tagline Atas</label>
            <input id="bannerTop" type="text" placeholder="Contoh: POLRI PRESISI" />
          </div>

          <div class="field">
            <label for="bannerTitle">Judul (H1)</label>
            <input id="bannerTitle" type="text" placeholder="Contoh: Polresta Padang" />
          </div>

          <div class="field">
            <label for="bannerBottom">Tagline Bawah</label>
            <input id="bannerBottom" type="text" placeholder="Contoh: POLRI UNTUK MASYARAKAT" />
          </div>

          <div class="field">
            <label for="bannerBg">Background Banner (path)</label>
            <input id="bannerBg" type="text" placeholder="Contoh: assets/background_web_polresta.png" />
            <div class="helper">
              Tulis path relatif dari root web. Contoh: <b>assets/background_web_polresta.png</b>
            </div>
          </div>

          <!-- B. Layanan -->
          <div class="field field-full top-gap">
            <div class="section-title">B. 3 Layanan Utama (SIM / SKCK / SPKT)</div>
          </div>

          <!-- SIM -->
          <div class="field field-full">
            <div class="subsection-title">1) SIM</div>
          </div>

          <div class="field">
            <label for="simTitle">Judul</label>
            <input id="simTitle" type="text" placeholder="SIM" />
          </div>

          <div class="field">
            <label for="simLink">Link Tujuan</label>
            <input id="simLink" type="text" placeholder="konten.php#sim" />
          </div>

          <div class="field field-full">
            <label for="simDesc">Deskripsi</label>
            <textarea id="simDesc" rows="3" placeholder="Deskripsi SIM..."></textarea>
          </div>

          <div class="field field-full">
            <label for="simIcon">Icon (path)</label>
            <input id="simIcon" type="text" placeholder="assets/logo sim.png" />
          </div>

          <div class="divider field-full"></div>

          <!-- SKCK -->
          <div class="field field-full">
            <div class="subsection-title">2) SKCK</div>
          </div>

          <div class="field">
            <label for="skckTitle">Judul</label>
            <input id="skckTitle" type="text" placeholder="SKCK" />
          </div>

          <div class="field">
            <label for="skckLink">Link Tujuan</label>
            <input id="skckLink" type="text" placeholder="konten.php#skck" />
          </div>

          <div class="field field-full">
            <label for="skckDesc">Deskripsi</label>
            <textarea id="skckDesc" rows="3" placeholder="Deskripsi SKCK..."></textarea>
          </div>

          <div class="field field-full">
            <label for="skckIcon">Icon (path)</label>
            <input id="skckIcon" type="text" placeholder="assets/logo SKCK.png" />
          </div>

          <div class="divider field-full"></div>

          <!-- SPKT -->
          <div class="field field-full">
            <div class="subsection-title">3) SPKT</div>
          </div>

          <div class="field">
            <label for="spktTitle">Judul</label>
            <input id="spktTitle" type="text" placeholder="SPKT" />
          </div>

          <div class="field">
            <label for="spktLink">Link Tujuan</label>
            <input id="spktLink" type="text" placeholder="konten.php#spkt" />
          </div>

          <div class="field field-full">
            <label for="spktDesc">Deskripsi</label>
            <textarea id="spktDesc" rows="3" placeholder="Deskripsi SPKT..."></textarea>
          </div>

          <div class="field field-full">
            <label for="spktIcon">Icon (path)</label>
            <input id="spktIcon" type="text" placeholder="assets/logo SPKT.png" />
          </div>

          <div class="field field-full note">
            <b>Catatan:</b> Jika Anda ingin perubahan ini tampil di <b>Home</b>, halaman <code>index.php</code>
            harus membaca konfigurasi dari localStorage dengan key <code>polresta_home_config_v1</code>.
          </div>
        </div>

        <div class="card-footer">
          <button class="btn" id="btnSaveBottom" type="button">Simpan</button>
          <button class="btn-outline" id="btnResetBottom" type="button">Reset</button>
          <span id="saveStatusBottom" class="status"></span>
        </div>
      </section>

      <div class="footer-mini">
        © 2026 Polresta Padang - Admin Panel
      </div>
    </main>
  </div>

  <script>
    const KEY = "polresta_home_config_v1";
    const $ = (id) => document.getElementById(id);

    function setStatus(msg, isError = false) {
      const elTop = $("saveStatus");
      const elBottom = $("saveStatusBottom");

      [elTop, elBottom].forEach((el) => {
        if (!el) return;
        el.textContent = msg || "";
        el.style.color = isError ? "#b91c1c" : "#0f766e";
      });

      if (msg) setTimeout(() => {
        if (elTop) elTop.textContent = "";
        if (elBottom) elBottom.textContent = "";
      }, 2500);
    }

    function getDefaultConfig() {
      return {
        banner: {
          top: "POLRI PRESISI",
          title: "Polresta Padang",
          bottom: "POLRI UNTUK MASYARAKAT",
          bg: "assets/background_web_polresta.png"
        },
        services: {
          sim: {
            title: "SIM",
            desc: "Polresta Padang melayani penerbitan Surat Izin Mengemudi (SIM) untuk masyarakat di Kota Padang.",
            icon: "assets/logo sim.png",
            link: "konten.php#sim"
          },
          skck: {
            title: "SKCK",
            desc: "Polresta Padang melayani penerbitan Surat Keterangan Catatan Kepolisian (SKCK) untuk masyarakat di Kota Padang.",
            icon: "assets/logo SKCK.png",
            link: "konten.php#skck"
          },
          spkt: {
            title: "SPKT",
            desc: "Polresta Padang juga bertindak sebagai Sentra Pelayanan Kepolisian Terpadu bagi masyarakat di Kota Padang.",
            icon: "assets/logo SPKT.png",
            link: "konten.php#spkt"
          }
        }
      };
    }

    function loadConfig() {
      try {
        const raw = localStorage.getItem(KEY);
        return raw ? JSON.parse(raw) : getDefaultConfig();
      } catch {
        return getDefaultConfig();
      }
    }

    function fillForm(cfg) {
      $("bannerTop").value = cfg.banner.top || "";
      $("bannerTitle").value = cfg.banner.title || "";
      $("bannerBottom").value = cfg.banner.bottom || "";
      $("bannerBg").value = cfg.banner.bg || "";

      $("simTitle").value = cfg.services.sim.title || "";
      $("simDesc").value  = cfg.services.sim.desc || "";
      $("simIcon").value  = cfg.services.sim.icon || "";
      $("simLink").value  = cfg.services.sim.link || "";

      $("skckTitle").value = cfg.services.skck.title || "";
      $("skckDesc").value  = cfg.services.skck.desc || "";
      $("skckIcon").value  = cfg.services.skck.icon || "";
      $("skckLink").value  = cfg.services.skck.link || "";

      $("spktTitle").value = cfg.services.spkt.title || "";
      $("spktDesc").value  = cfg.services.spkt.desc || "";
      $("spktIcon").value  = cfg.services.spkt.icon || "";
      $("spktLink").value  = cfg.services.spkt.link || "";
    }

    function readForm() {
      return {
        banner: {
          top: $("bannerTop").value.trim(),
          title: $("bannerTitle").value.trim(),
          bottom: $("bannerBottom").value.trim(),
          bg: $("bannerBg").value.trim()
        },
        services: {
          sim: {
            title: $("simTitle").value.trim(),
            desc: $("simDesc").value.trim(),
            icon: $("simIcon").value.trim(),
            link: $("simLink").value.trim()
          },
          skck: {
            title: $("skckTitle").value.trim(),
            desc: $("skckDesc").value.trim(),
            icon: $("skckIcon").value.trim(),
            link: $("skckLink").value.trim()
          },
          spkt: {
            title: $("spktTitle").value.trim(),
            desc: $("spktDesc").value.trim(),
            icon: $("spktIcon").value.trim(),
            link: $("spktLink").value.trim()
          }
        }
      };
    }

    function save() {
      const newCfg = readForm();
      localStorage.setItem(KEY, JSON.stringify(newCfg));
      setStatus("Berhasil disimpan.");
    }

    function reset() {
      if (!confirm("Reset ke default?")) return;
      const def = getDefaultConfig();
      localStorage.setItem(KEY, JSON.stringify(def));
      fillForm(def);
      setStatus("Sudah di-reset ke default.");
    }

    // init
    fillForm(loadConfig());

    $("btnSave").addEventListener("click", save);
    $("btnSaveBottom").addEventListener("click", save);

    $("btnReset").addEventListener("click", reset);
    $("btnResetBottom").addEventListener("click", reset);
  </script>

</body>
</html>
