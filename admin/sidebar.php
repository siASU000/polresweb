<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function sidebarLink(string $page, string $label, string $currentPage): string
{
    $isActive = ($page === $currentPage);
    $style = $isActive
        ? 'background:#e8edff; font-weight:700; color:#1f2f77;'
        : 'color:#0f172a;';
    return "<a href=\"$page\" style=\"display:block; padding:10px 12px; border-radius:10px; text-decoration:none; margin-bottom:6px; transition:background 0.2s; $style\" 
        onmouseover=\"if(!this.style.fontWeight.includes('700')) this.style.background='#f1f5ff'\"
        onmouseout=\"if(!this.style.fontWeight.includes('700')) this.style.background=''\">$label</a>";
}
?>
<style>
    body {
        margin: 0;
        font-family: 'Poppins', sans-serif;
    }
</style>

<header
    style="height:64px; background:#1f2f77; color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 20px; position:sticky; top:0; z-index:1000; box-shadow:0 2px 8px rgba(0,0,0,0.15);">
    <div style="display:flex; gap:10px; align-items:center;">
        <span
            style="background:rgba(255,255,255,0.15); padding:5px 10px; border-radius:8px; font-weight:700; font-size:13px; letter-spacing:0.5px;">ADMIN</span>
        <span style="font-weight:600; font-size:15px;">Polresta Padang</span>
    </div>
    <div style="display:flex; gap:12px; align-items:center;">
        <?php if ($currentPage !== 'dashboard.php'): ?>
            <a href="dashboard.php"
                style="color:rgba(255,255,255,0.85); text-decoration:none; font-size:13px; padding:6px 12px; border-radius:6px; border:1px solid rgba(255,255,255,0.2); transition:0.2s;"
                onmouseover="this.style.background='rgba(255,255,255,0.1)'" onmouseout="this.style.background=''">&#8592;
                Dashboard</a>
        <?php endif; ?>
        <a href="logout.php"
            style="display:inline-flex; align-items:center; padding:7px 16px; border-radius:8px; text-decoration:none; font-weight:600; font-size:13px; color:#fff; background:#2563eb; border:1px solid rgba(255,255,255,0.2); transition:0.2s;"
            onmouseover="this.style.background='#1d4ed8'" onmouseout="this.style.background='#2563eb'"
            onclick="return confirm('Yakin ingin logout?')">Logout</a>
    </div>
</header>

<div style="display:flex; min-height:calc(100vh - 64px);">

    <aside
        style="width:240px; min-width:240px; background:#fff; border-right:1px solid #e6eaf5; padding:16px; position:sticky; top:64px; height:calc(100vh - 64px); overflow-y:auto; box-shadow:2px 0 6px rgba(0,0,0,0.03);">

        <div
            style="font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin:4px 0 10px;">
            Menu</div>
        <?= sidebarLink('dashboard.php', 'Profil Admin', $currentPage) ?>

        <div
            style="font-size:11px; font-weight:700; color:#94a3b8; text-transform:uppercase; letter-spacing:1px; margin:18px 0 10px;">
            Kelola Konten</div>
        <?= sidebarLink('kelola-home.php', 'Kelola Home', $currentPage) ?>
        <?= sidebarLink('kelola-profil.php', 'Kelola Profil', $currentPage) ?>
        <?= sidebarLink('kelola-berita.php', 'Kelola Berita', $currentPage) ?>
        <?= sidebarLink('kelola-galeri.php', 'Kelola Galeri', $currentPage) ?>
        <?= sidebarLink('kelola-informasi.php', 'Kelola Informasi', $currentPage) ?>
        <?= sidebarLink('kelola-kontak.php', 'Kelola Kontak', $currentPage) ?>
        <?= sidebarLink('kelola-header.php', 'Kelola Header', $currentPage) ?>
        <?= sidebarLink('kelola-footer.php', 'Kelola Footer', $currentPage) ?>
    </aside>

    <main style="flex:1; padding:24px; overflow-x:hidden; box-sizing:border-box; background:#f4f6fb;">