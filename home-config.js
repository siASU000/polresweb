(() => {
  const KEY = "polresta_home_config_v1";

  function safeParse(raw) {
    try { return JSON.parse(raw); } catch { return null; }
  }

  const cfg = safeParse(localStorage.getItem(KEY));
  if (!cfg || typeof cfg !== "object") return;

  // Helpers
  const byId = (id) => document.getElementById(id);
  const safeText = (v) => (typeof v === "string" ? v : "");
  const safePath = (v) => (typeof v === "string" ? v.trim() : "");
  const safeHref = (v) => (typeof v === "string" ? v.trim() : "");

  // ===== Banner =====
  const bannerTopEl = byId("bannerTopText");
  const bannerTitleEl = byId("bannerTitleText");
  const bannerBottomEl = byId("bannerBottomText");
  const bannerSectionEl = byId("homeBanner");

  if (cfg.banner) {
    if (bannerTopEl) bannerTopEl.textContent = safeText(cfg.banner.top) || bannerTopEl.textContent;
    if (bannerTitleEl) bannerTitleEl.textContent = safeText(cfg.banner.title) || bannerTitleEl.textContent;
    if (bannerBottomEl) bannerBottomEl.textContent = safeText(cfg.banner.bottom) || bannerBottomEl.textContent;

    const bgPath = safePath(cfg.banner.bg);
    if (bannerSectionEl && bgPath) {
      // Pastikan path cocok dengan struktur project kamu
      bannerSectionEl.style.backgroundImage = `url("${bgPath}")`;
      bannerSectionEl.style.backgroundSize = "cover";
      bannerSectionEl.style.backgroundPosition = "center";
    }
  }

  // ===== Services =====
  function applyService(serviceKey, map) {
    const srv = cfg.services && cfg.services[serviceKey];
    if (!srv) return;

    const title = safeText(srv.title);
    const desc = safeText(srv.desc);
    const icon = safePath(srv.icon);
    const link = safeHref(srv.link);

    const titleEl = byId(map.titleId);
    const descEl = byId(map.descId);
    const iconEl = byId(map.iconId);
    const linkEl = byId(map.linkId);

    if (titleEl && title) titleEl.textContent = title;
    if (descEl && desc) descEl.textContent = desc;

    if (iconEl && icon) {
      iconEl.src = icon;
      // alt ikut disesuaikan jika title ada
      if (title) iconEl.alt = `${title} Icon`;
    }

    if (linkEl && link) {
      linkEl.href = link;
    }
  }

  applyService("sim",  { titleId: "serviceSimTitle",  descId: "serviceSimDesc",  iconId: "serviceSimIcon",  linkId: "serviceSimLink" });
  applyService("skck", { titleId: "serviceSkckTitle", descId: "serviceSkckDesc", iconId: "serviceSkckIcon", linkId: "serviceSkckLink" });
  applyService("spkt", { titleId: "serviceSpktTitle", descId: "serviceSpktDesc", iconId: "serviceSpktIcon", linkId: "serviceSpktLink" });
})();
