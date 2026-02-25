<?php
// src/templates/header.php
declare(strict_types=1);

require_once __DIR__ . "/../config/koneksi.php";

if (session_status() === PHP_SESSION_NONE)
  session_start();

$pageTitle = $pageTitle ?? "Peminjaman Ruangan";
$activeNav = $activeNav ?? "home";


// Link menu
$L = [
  "home" => $BASE . "mahasiswa/dashboard.php",
  "ruangan" => $BASE . "mahasiswa/ruangan.php",
  "peminjaman" => $BASE . "mahasiswa/peminjaman.php",
  "login" => $BASE . "auth/login.php",
  "logout" => $BASE . "auth/logout.php",
];

function active(string $key, string $activeNav): string
{
  return $key === $activeNav ? "active" : "";
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?= htmlspecialchars($pageTitle) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="<?= $BASE ?>/assets/css/style.css?v=1">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body>

  <div class="topnav">
    <div class="topnavin">
      <a class="brand" href="<?= $L['home'] ?>">
        <div class="logo">NF</div>
        <div>Peminjaman Ruangan</div>
      </a>

      <nav class="mainmenu" aria-label="Primary">
        <a class="<?= active('home', $activeNav) ?>" href="<?= $L['home'] ?>">Home</a>
        <a class="<?= active('ruangan', $activeNav) ?>" href="<?= $L['ruangan'] ?>">Ruangan</a>
        <a class="<?= active('peminjaman', $activeNav) ?>" href="<?= $L['peminjaman'] ?>">Peminjaman</a>
        <?php if (!empty($_SESSION['role'])): ?>
          <a href="<?= $L['logout'] ?>">Logout</a>
        <?php else: ?>
          <a class="<?= active('login', $activeNav) ?>" href="<?= $L['login'] ?>">Login</a>
        <?php endif; ?>

      </nav>

      <!-- Hamburger (mobile) -->
      <button class="burger" id="burgerBtn" aria-label="Buka menu" aria-expanded="false" aria-controls="mobileMenu">
        <span class="lines" aria-hidden="true">
          <span class="line"></span>
          <span class="line"></span>
          <span class="line"></span>
        </span>
      </button>

      <!-- Mobile dropdown -->
      <div class="mobilePanel" id="mobileMenu" role="menu">
        <a class="<?= active('home', $activeNav) ?>" href="<?= $L['home'] ?>" role="menuitem">Home</a>
        <a class="<?= active('ruangan', $activeNav) ?>" href="<?= $L['ruangan'] ?>" role="menuitem">Ruangan</a>
        <a class="<?= active('peminjaman', $activeNav) ?>" href="<?= $L['peminjaman'] ?>" role="menuitem">Peminjaman</a>
        <a class="<?= active('login', $activeNav) ?>" href="<?= $L['login'] ?>" role="menuitem">Login</a>
      </div>
    </div>
  </div>