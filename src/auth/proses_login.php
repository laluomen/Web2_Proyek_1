<?php
// src/auth/proses_login.php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../config/koneksi.php';

$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');

$redirect = trim($_POST['redirect'] ?? '');

if ($username === '' || $password === '') {
    header("Location: login.php?err=invalid&redirect=" . urlencode($redirect));
    exit;
}

$user = query("SELECT id, nama, password, role FROM users WHERE username = ? LIMIT 1", [$username])->fetch();

if (!$user) {
    header("Location: login.php?err=invalid&redirect=" . urlencode($redirect));
    exit;
}

$stored = (string)$user['password'];
$ok = false;

// bcrypt
if (preg_match('/^\$2[aby]\$/', $stored)) {
    $ok = password_verify($password, $stored);
}
// md5 lama (upgrade otomatis ke bcrypt saat berhasil)
else if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
    $ok = (md5($password) === strtolower($stored));
    if ($ok) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        query("UPDATE users SET password = ? WHERE id = ?", [$newHash, (int)$user['id']]);
    }
}

if (!$ok) {
    header("Location: login.php?err=invalid&redirect=" . urlencode($redirect));
    exit;
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['role']    = (string)$user['role'];
$_SESSION['nama']    = (string)$user['nama'];

if ($redirect !== '') {
    header("Location: " . $redirect);
    exit;
}

if ($_SESSION['role'] === 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
}
header("Location: ../mahasiswa/dashboard.php");
exit;
?>