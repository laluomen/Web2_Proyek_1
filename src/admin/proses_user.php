<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            // Validasi input
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $role = $_POST['role'] ?? '';
            $prodi = trim($_POST['prodi'] ?? '');

            if (empty($nama) || empty($username) || empty($password) || empty($role)) {
                throw new Exception("Semua field wajib diisi!");
            }

            // Cek username sudah ada
            $stmt = query("SELECT id FROM users WHERE username = ?", [$username]);
            if ($stmt->fetch()) {
                throw new Exception("Username sudah digunakan!");
            }

            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert user baru
            $sql = "INSERT INTO users (nama, username, password, role, prodi) VALUES (?, ?, ?, ?, ?)";
            query($sql, [$nama, $username, $hashedPassword, $role, $prodi ?: null]);

            header("Location: kelola_user.php?success=add");
            exit;

        case 'edit':
            // Validasi input
            $id = (int)($_POST['id'] ?? 0);
            $nama = trim($_POST['nama'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $role = $_POST['role'] ?? '';
            $prodi = trim($_POST['prodi'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (!$id || empty($nama) || empty($username) || empty($role)) {
                throw new Exception("Data tidak lengkap!");
            }

            // Cek username sudah digunakan user lain
            $stmt = query("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $id]);
            if ($stmt->fetch()) {
                throw new Exception("Username sudah digunakan oleh user lain!");
            }

            // Update user
            if (!empty($password)) {
                // Jika password diisi, update dengan password baru
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET nama = ?, username = ?, password = ?, role = ?, prodi = ? WHERE id = ?";
                query($sql, [$nama, $username, $hashedPassword, $role, $prodi ?: null, $id]);
            } else {
                // Jika password kosong, tidak update password
                $sql = "UPDATE users SET nama = ?, username = ?, role = ?, prodi = ? WHERE id = ?";
                query($sql, [$nama, $username, $role, $prodi ?: null, $id]);
            }

            header("Location: kelola_user.php?success=edit");
            exit;

        case 'delete':
            $id = (int)($_GET['id'] ?? 0);
            
            if (!$id) {
                throw new Exception("ID tidak valid!");
            }

            // Cek apakah user yang akan dihapus adalah user yang sedang login
            if ($id == $_SESSION['user_id']) {
                throw new Exception("Tidak dapat menghapus akun sendiri!");
            }

            // Hapus user
            query("DELETE FROM users WHERE id = ?", [$id]);

            header("Location: kelola_user.php?success=delete");
            exit;

        default:
            throw new Exception("Aksi tidak valid!");
    }
} catch (Exception $e) {
    header("Location: kelola_user.php?error=" . urlencode($e->getMessage()));
    exit;
}
