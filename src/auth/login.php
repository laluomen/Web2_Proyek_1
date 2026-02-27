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

// butuh akses query()/db()
require_once __DIR__ . "/../config/koneksi.php";

// ===== Ambil statistik dari DB =====
$totalRuangan = '—';
$totalGedung = '—';
$totalKapas = '—';

try {
  $row = query("
      SELECT
        COUNT(*)                           AS total_ruangan,
        COUNT(DISTINCT gedung)             AS total_gedung,
        COALESCE(SUM(kapasitas), 0)        AS total_kapasitas
      FROM ruangan
  ")->fetch();

  if ($row) {
    $totalRuangan = number_format((int) $row['total_ruangan'], 0, ',', '.');
    $totalGedung = number_format((int) $row['total_gedung'], 0, ',', '.');
    $totalKapas = number_format((int) $row['total_kapasitas'], 0, ',', '.') . " org";
  }
} catch (Throwable $e) {
  // fallback tetap '—' jika DB error / belum import
}

require_once __DIR__ . "/../templates/header.php";

$stats = [
  ['label' => 'Total Ruangan', 'value' => $totalRuangan],
  ['label' => 'Gedung', 'value' => $totalGedung],
  ['label' => 'Kapasitas', 'value' => $totalKapas],
];
?>

<style>
  .login-stage {
    padding: 30px 0
  }

  .login-stage .panel {
    max-width: 1120px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr .9fr;
    gap: 24px;
    align-items: stretch;
    padding: 0 18px
  }

  .stats-left {
    border-radius: 22px;
    padding: 34px 30px;
    background: rgba(255, 255, 255, .10);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, .12)
  }

  .stats-left h1 {
    margin: 0 0 8px;
    font-size: 40px;
    line-height: 1.05;
    color: #eaf2ff;
    letter-spacing: -.5px
  }

  .stats-left p {
    margin: 0 0 18px;
    color: rgba(234, 242, 255, .80);
    max-width: 52ch
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-top: 16px
  }

  .s-card {
    border-radius: 18px;
    padding: 14px 14px;
    background: rgba(255, 255, 255, .08);
    border: 1px solid rgba(255, 255, 255, .12)
  }

  .s-val {
    font-size: 22px;
    font-weight: 900;
    color: #eaf2ff
  }

  .s-lbl {
    margin-top: 6px;
    font-size: 12.5px;
    color: rgba(234, 242, 255, .75)
  }

  .stats-note {
    margin-top: 14px;
    font-size: 12.5px;
    color: rgba(234, 242, 255, .70)
  }

  .login-card {
    border-radius: 22px;
    overflow: hidden;
    background: #fff;
    box-shadow: 0 18px 60px rgba(0, 0, 0, .25)
  }

  .login-card .cardhead {
    padding: 20px 22px;
    border-bottom: 1px solid #eef2f7
  }

  .login-card .title {
    font-weight: 800;
    font-size: 26px;
    color: #0f172a
  }

  .login-card .sub {
    margin-top: 4px;
    color: #64748b
  }

  .login-card form {
    padding: 18px 22px 22px
  }

  .login-card .field {
    margin-bottom: 14px
  }

  .login-card label {
    display: block;
    font-size: 13px;
    color: #475569;
    margin-bottom: 6px
  }

  .login-card .input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    outline: none
  }

  .login-card .input:focus {
    border-color: #86efac;
    box-shadow: 0 0 0 4px rgba(34, 197, 94, .12)
  }

  .login-card .row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 6px 0 14px
  }

  .login-card .msg {
    background: #fee2e2;
    color: #991b1b;
    padding: 10px 12px;
    border-radius: 14px;
    margin: 14px 22px 0;
    border: 1px solid #fecaca
  }

  .login-card .btn {
    width: 100%;
    border: 0;
    border-radius: 16px;
    padding: 12px 14px;
    font-weight: 800;
    color: #fff;
    background: linear-gradient(180deg, #22c55e, #16a34a);
    box-shadow: 0 14px 30px rgba(34, 197, 94, .25)
  }

  .login-card .helper {
    margin-top: 12px;
    font-size: 12.5px;
    color: #64748b;
    line-height: 1.45
  }

  @media (max-width: 900px) {
    .login-stage .panel {
      grid-template-columns: 1fr
    }

    .stats-grid {
      grid-template-columns: 1fr
    }

    .stats-left h1 {
      font-size: 36px
    }
  }
</style>

<div class="login-stage">
  <div class="panel">
    <section class="stats-left">
      <h1>Ringkasan sistem.</h1>
      <p>Login untuk melihat detail ruangan dan mengajukan peminjaman.</p>

      <div class="stats-grid">
        <?php foreach ($stats as $s): ?>
          <div class="s-card">
            <div class="s-val"><?= htmlspecialchars((string) $s['value']) ?></div>
            <div class="s-lbl"><?= htmlspecialchars((string) $s['label']) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="stats-note">
        Ringkasan ini menunjukkan gambaran cepat ruangan yang tersedia di sistem.
        <br><br>
        Setelah login, kamu bisa:
        <ul>
          <li>Melihat detail ruangan (foto, fasilitas, kapasitas, lokasi).</li>
          <li>Mengajukan peminjaman sesuai tanggal dan jam yang dibutuhkan.</li>
          <li>Memantau status pengajuan langsung dari riwayat pengajuan.</li>
        </ul>
      </div>
    </section>

    <section class="login-card">
      <div class="cardhead">
        <div class="title">Form Login</div>
        <div class="sub">Silakan login untuk melanjutkan</div>
      </div>

      <?php if ($err === 'invalid'): ?>
        <div class="msg">Username atau password salah.</div>
      <?php endif; ?>

      <form action="proses_login.php" method="post" autocomplete="off">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars((string) $redirect) ?>">

        <div class="field">
          <label for="username">Username</label>
          <input class="input" id="username" type="text" name="username" placeholder="Masukkan username" required>
        </div>

        <div class="field">
          <label for="pw">Password</label>
          <input class="input" id="pw" type="password" name="password" placeholder="Masukkan password" required>
        </div>

        <div class="row">
          <div class="showpw" style="display:flex;gap:10px;align-items:center">
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