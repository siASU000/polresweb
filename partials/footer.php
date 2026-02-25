<?php
// partials/footer.php
$fsRes = $conn->query("SELECT * FROM footer_settings WHERE id=1");
$fs = $fsRes ? $fsRes->fetch_assoc() : [];

$contact  = $fs['contact_text'] ?? 'Kontak kami: 110 - WA +62 811 6693 110';
$fax      = $fs['fax_text'] ?? 'Fax: (0751) 33724';
$copy     = $fs['copyright_text'] ?? '© 2026 Polresta Padang';
$topBg    = $fs['top_bg'] ?? '#1a1a2e';
$botBg    = $fs['bottom_bg'] ?? '#0f0f1a';
?>

<style>
  /* ===== POLICE THEMED FOOTER ===== */
  .police-footer {
    font-family: 'Poppins', sans-serif;
    color: #fff;
    margin-top: 60px;
  }

  .police-footer a {
    color: #ccc;
    text-decoration: none;
    transition: color 0.25s;
  }
  .police-footer a:hover {
    color: #FCC236;
  }

  /* Top Section - Main Footer */
  .footer-main {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 50px 20px 40px;
  }

  .footer-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1.5fr 1fr 1fr 1.2fr;
    gap: 40px;
  }

  /* Column: About */
  .footer-col h3 {
    font-size: 16px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 18px;
    color: #FCC236;
    position: relative;
    padding-bottom: 10px;
  }
  .footer-col h3::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 40px;
    height: 3px;
    background: #FCC236;
    border-radius: 2px;
  }

  .footer-about-text {
    font-size: 13px;
    line-height: 1.75;
    color: #b0b0c0;
    margin-bottom: 18px;
  }

  .footer-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 14px;
    background: rgba(252,194,54,0.1);
    border: 1px solid rgba(252,194,54,0.25);
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    color: #FCC236;
    letter-spacing: 0.5px;
  }

  .footer-badge svg {
    width: 16px;
    height: 16px;
    fill: #FCC236;
  }

  /* Column: Links */
  .footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .footer-links li {
    margin-bottom: 10px;
  }
  .footer-links li a {
    font-size: 13px;
    color: #b0b0c0;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .footer-links li a::before {
    content: '›';
    font-size: 16px;
    color: #FCC236;
    font-weight: 700;
  }

  /* Column: Contact */
  .footer-contact-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    margin-bottom: 16px;
  }
  .footer-contact-icon {
    width: 36px;
    height: 36px;
    background: rgba(252,194,54,0.12);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .footer-contact-icon svg {
    width: 16px;
    height: 16px;
    fill: #FCC236;
  }
  .footer-contact-text {
    font-size: 13px;
    color: #b0b0c0;
    line-height: 1.5;
  }
  .footer-contact-text strong {
    color: #e0e0e0;
    display: block;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 2px;
  }

  /* Emergency Bar */
  .footer-emergency {
    background: linear-gradient(90deg, #dc2626 0%, #b91c1c 100%);
    padding: 18px 20px;
  }
  .footer-emergency-inner {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
  }
  .footer-emergency-text {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 14px;
    font-weight: 600;
    color: #fff;
  }
  .footer-emergency-text svg {
    width: 20px;
    height: 20px;
    fill: #fff;
    animation: pulse-ring 1.5s infinite;
  }
  @keyframes pulse-ring {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
  }
  .footer-emergency-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 24px;
    background: #fff;
    color: #dc2626;
    font-weight: 700;
    font-size: 13px;
    border-radius: 50px;
    text-decoration: none;
    transition: transform 0.2s, box-shadow 0.2s;
    letter-spacing: 0.5px;
  }
  .footer-emergency-btn:hover {
    transform: scale(1.04);
    box-shadow: 0 4px 16px rgba(0,0,0,0.2);
    color: #dc2626;
  }

  /* Bottom Bar */
  .footer-bottom-bar {
    background: #0f0f1a;
    padding: 16px 20px;
    text-align: center;
  }
  .footer-bottom-bar p {
    max-width: 1200px;
    margin: 0 auto;
    font-size: 12px;
    color: #666;
    letter-spacing: 0.3px;
  }
  .footer-bottom-bar p a {
    color: #FCC236;
  }

  /* Responsive Footer */
  @media (max-width: 992px) {
    .footer-inner {
      grid-template-columns: 1fr 1fr;
      gap: 30px;
    }
  }
  @media (max-width: 600px) {
    .footer-inner {
      grid-template-columns: 1fr;
      gap: 28px;
    }
    .footer-emergency-inner {
      flex-direction: column;
      text-align: center;
    }
  }
</style>

<footer class="police-footer">

  <!-- Main Footer -->
  <div class="footer-main">
    <div class="footer-inner">

      <!-- About -->
      <div class="footer-col">
        <h3>Polresta Padang</h3>
        <p class="footer-about-text">
          Kepolisian Resor Kota Padang melayani masyarakat Kota Padang dan sekitarnya. 
          Kami berkomitmen untuk menjaga keamanan, ketertiban, dan memberikan pelayanan terbaik 
          kepada seluruh masyarakat.
        </p>
        <div class="footer-badge">
          <svg viewBox="0 0 24 24"><path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm-2 16l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
          POLRI PRESISI
        </div>
      </div>

      <!-- Quick Links -->
      <div class="footer-col">
        <h3>Navigasi</h3>
        <ul class="footer-links">
          <li><a href="/Polresta_Padang/">Beranda</a></li>
          <li><a href="profil">Profil</a></li>
          <li><a href="berita">Berita</a></li>
          <li><a href="galeri">Galeri</a></li>
          <li><a href="informasi">Informasi</a></li>
          <li><a href="hubungi-kami">Hubungi Kami</a></li>
        </ul>
      </div>

      <!-- Services -->
      <div class="footer-col">
        <h3>Layanan</h3>
        <ul class="footer-links">
          <li><a href="konten#sim">SIM</a></li>
          <li><a href="konten#skck">SKCK</a></li>
          <li><a href="konten#spkt">SPKT</a></li>
          <li><a href="informasi#dpo">DPO</a></li>
          <li><a href="informasi#orang-hilang">Orang Hilang</a></li>
        </ul>
      </div>

      <!-- Contact -->
      <div class="footer-col">
        <h3>Kontak</h3>

        <div class="footer-contact-item">
          <div class="footer-contact-icon">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5a2.5 2.5 0 0 1 0-5 2.5 2.5 0 0 1 0 5z"/></svg>
          </div>
          <div class="footer-contact-text">
            <strong>Alamat</strong>
            Jl. Moh. Yamin, Padang, Sumatera Barat
          </div>
        </div>

        <div class="footer-contact-item">
          <div class="footer-contact-icon">
            <svg viewBox="0 0 24 24"><path d="M6.62 10.79a15.053 15.053 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
          </div>
          <div class="footer-contact-text">
            <strong>Telepon</strong>
            <?= e($contact) ?>
          </div>
        </div>

        <?php if(trim((string)$fax) !== ''): ?>
        <div class="footer-contact-item">
          <div class="footer-contact-icon">
            <svg viewBox="0 0 24 24"><path d="M19 8h-1V3H6v5H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zM8 5h8v3H8V5zm8 14H8v-4h8v4zm2-4v-2H6v2H4v-4c0-.55.45-1 1-1h14c.55 0 1 .45 1 1v4h-2z"/></svg>
          </div>
          <div class="footer-contact-text">
            <strong>Fax</strong>
            <?= e($fax) ?>
          </div>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <!-- Emergency Bar -->
  <div class="footer-emergency">
    <div class="footer-emergency-inner">
      <div class="footer-emergency-text">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
        Butuh Bantuan Darurat? Hubungi kami 24 JAM
      </div>
      <a href="tel:110" class="footer-emergency-btn">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="#dc2626"><path d="M6.62 10.79a15.053 15.053 0 0 0 6.59 6.59l2.2-2.2a1 1 0 0 1 1.01-.24c1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
        EMERGENCY 110
      </a>
    </div>
  </div>

  <!-- Bottom Bar -->
  <div class="footer-bottom-bar">
    <p><?= e($copy) ?> &mdash; Kepolisian Negara Republik Indonesia</p>
  </div>

</footer>