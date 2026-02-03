(function () {
  const current = window.location.pathname.split("/").pop() || "index.php";
  document.querySelectorAll("nav a").forEach(a => {
    const href = a.getAttribute("href");
    if (href === current) a.classList.add("active");
  });
})();
