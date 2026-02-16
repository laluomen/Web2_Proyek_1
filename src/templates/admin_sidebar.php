<?php
// src/templates/admin_sidebar.php
if (session_status() === PHP_SESSION_NONE) session_start();

$activeAdmin = $activeAdmin ?? "dashboard";

$A = [
  "dashboard" => "/admin/dashboard.php",
  "ruangan"   => "/admin/ruangan.php",
  "user"      => "/admin/user.php",
  "approve"   => "/admin/approve.php",
  "laporan"   => "/admin/laporan.php",
  "logout"    => "/auth/logout.php",
];

if (!function_exists('aactive')) {
  function aactive($key, $activeAdmin) { return $key === $activeAdmin ? "active" : ""; }
}
?>

<aside class="asb" id="adminSidebar" aria-label="Admin sidebar">
  <div class="asb-head">
    <a class="asb-brand" href="<?= $A['dashboard'] ?>">
      <div class="asb-logo">NF</div>
      <div class="asb-title">
        <div class="t1">Admin Panel</div>
        <div class="t2">Peminjaman Ruangan</div>
      </div>
    </a>

    <button class="asb-burger" id="asbBurger" aria-label="Toggle sidebar" aria-expanded="false">
      <span class="asb-lines" aria-hidden="true">
        <span class="asb-line"></span>
        <span class="asb-line"></span>
        <span class="asb-line"></span>
      </span>
    </button>
  </div>

  <nav class="asb-nav">
    <a class="asb-link <?= aactive('dashboard', $activeAdmin) ?>" href="<?= $A['dashboard'] ?>">
      <span class="dot"></span> Dashboard
    </a>
    <a class="asb-link <?= aactive('ruangan', $activeAdmin) ?>" href="<?= $A['ruangan'] ?>">
      <span class="dot"></span> Kelola Ruangan
    </a>
    <a class="asb-link <?= aactive('approve', $activeAdmin) ?>" href="<?= $A['approve'] ?>">
      <span class="dot"></span> Approve Peminjaman
    </a>
    <a class="asb-link <?= aactive('user', $activeAdmin) ?>" href="<?= $A['user'] ?>">
      <span class="dot"></span> Kelola User
    </a>
    <a class="asb-link <?= aactive('laporan', $activeAdmin) ?>" href="<?= $A['laporan'] ?>">
      <span class="dot"></span> Laporan
    </a>
  </nav>

  <div class="asb-foot">
    <a class="asb-logout" href="<?= $A['logout'] ?>">
      <span class="dot"></span> Logout
    </a>
  </div>
</aside>

<div class="asb-overlay" id="asbOverlay" aria-hidden="true"></div>
