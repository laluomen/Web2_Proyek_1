<?php
// src/auth/login.php
declare(strict_types=1);
session_start();

if (isset($_SESSION['user_id'], $_SESSION['role'])) {
  header("Location: " . ($_SESSION['role'] === 'admin' ? "../admin/dashboard.php" : "../mahasiswa/dashboard.php"));
  exit;
}

$err = $_GET['err'] ?? '';
$redirect = $_GET['redirect'] ?? '';

$pageTitle = "Login - Peminjaman Ruangan";
$activeNav = "login";
require_once __DIR__ . "/../templates/header.php";
?>

<div class="login-stage">
  <div class="panel">
    <section class="hero">
      <h1>Kelola peminjaman ruangan dengan cepat.</h1>
      <p>
        Masuk untuk mengakses dashboard sesuai role. Session akan menyimpan identitas user
        agar proses routing dan filtering data lebih mudah.
      </p>
      <div class="badge">🔒 Aman • Cepat • Terstruktur</div>
    </section>

    <section class="card">
      <div class="cardhead">
        <div class="title">Form Login</div>
        <div class="sub">Silakan login untuk melanjutkan</div>
      </div>

      <?php if ($err === 'invalid'): ?>
        <div class="msg">Username atau password salah.</div>
      <?php endif; ?>

      <form action="proses_login.php" method="post" autocomplete="off">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
        <div class="field">
          <label for="username">Username</label>
          <input class="input" id="username" type="text" name="username" placeholder="Masukkan username" required>
        </div>

        <div class="field">
          <label for="pw">Password</label>
          <input class="input" id="pw" type="password" name="password" placeholder="Masukkan password" required>
        </div>

        <div class="row">
          <div class="showpw">
            <input id="showpw" type="checkbox">
            <label for="showpw" style="margin:0;color:#334155;font-size:13px">Show password</label>
          </div>
        </div>

        <button class="btn" type="submit">Masuk</button>

        <div class="helper">
          Gunakan akun yang sudah terdaftar. Jika gagal login, pastikan username/password benar.
        </div>
      </form>
    </section>
  </div>
</div>

<script>
  const cb = document.getElementById('showpw');
  const pw = document.getElementById('pw');
  if (cb && pw) cb.addEventListener('change', () => { pw.type = cb.checked ? 'text' : 'password'; });
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>