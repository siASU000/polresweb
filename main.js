(function () {
  let current = window.location.pathname.split("/").pop() || "index";
  // Remove .php extension if present
  current = current.replace(/\.php$/, '');
  if (current === '' || current === 'Polresta_Padang') current = 'index';
  
  document.querySelectorAll("nav a").forEach(a => {
    let href = a.getAttribute("href") || '';
    // Normalize href: remove .php and get last segment
    let hrefPage = href.split('?')[0].split('#')[0].split('/').pop().replace(/\.php$/, '') || '';
    if (hrefPage === current) a.classList.add("active");
  });
})();
