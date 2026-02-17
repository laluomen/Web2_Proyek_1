<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ===== FUNGSI HELPER =====
function generateFileName($namaFile, $ruanganId)
{
    $ext = pathinfo($namaFile, PATHINFO_EXTENSION);
    return 'ruangan_' . $ruanganId . '_' . time() . '.' . $ext;
}

function getUploadDir()
{
    $dir = __DIR__ . "/../uploads/ruangan";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function deleteOldPhoto($ruanganId)
{
    $uploadDir = getUploadDir();
    $pattern = $uploadDir . '/ruangan_' . $ruanganId . '_*';
    $files = glob($pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
}

// ===== TAMBAH RUANGAN =====
if ($action === 'add') {
    try {
        $nama_ruangan = $_POST['nama_ruangan'] ?? '';
        $gedung = $_POST['gedung'] ?? '';
        $kapasitas = $_POST['kapasitas'] ?? null;
        $deskripsi = $_POST['deskripsi'] ?? '';

        if (empty($nama_ruangan)) {
            throw new Exception("Nama ruangan tidak boleh kosong");
        }

        // Insert ke database
        $stmt = query(
            "INSERT INTO ruangan (nama_ruangan, gedung, kapasitas, deskripsi) VALUES (?, ?, ?, ?)",
            [$nama_ruangan, $gedung, $kapasitas, $deskripsi]
        );

        $ruanganId = db()->lastInsertId();

        // Handle upload foto
        if (!empty($_FILES['foto']['name'])) {
            $foto = $_FILES['foto'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($foto['size'] > $maxSize) {
                throw new Exception("Ukuran foto terlalu besar (max 2MB)");
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($foto['type'], $allowedTypes)) {
                throw new Exception("Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF");
            }

            $uploadDir = getUploadDir();
            $fileName = generateFileName($foto['name'], $ruanganId);
            $uploadPath = $uploadDir . '/' . $fileName;

            if (!move_uploaded_file($foto['tmp_name'], $uploadPath)) {
                throw new Exception("Gagal mengupload foto");
            }

            // Update foto path ke database
            query("UPDATE ruangan SET foto = ? WHERE id = ?", [$fileName, $ruanganId]);
        }

        header("Location: ruangan.php?success=add");
        exit;
    } catch (Exception $e) {
        header("Location: ruangan.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ===== EDIT RUANGAN =====
else if ($action === 'edit') {
    try {
        $id = $_POST['id'] ?? 0;
        $nama_ruangan = $_POST['nama_ruangan'] ?? '';
        $gedung = $_POST['gedung'] ?? '';
        $kapasitas = $_POST['kapasitas'] ?? null;
        $deskripsi = $_POST['deskripsi'] ?? '';
        $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
        if ($is_active !== 0 && $is_active !== 1) $is_active = 1;

        if (empty($id) || empty($nama_ruangan)) {
            throw new Exception("Data tidak valid");
        }

        // Get ruangan saat ini untuk cek foto lama
        $stmt = query("SELECT * FROM ruangan WHERE id = ?", [$id]);
        $ruangan = $stmt->fetch();

        if (!$ruangan) {
            throw new Exception("Ruangan tidak ditemukan");
        }

        $fotoName = $ruangan['foto'];

        // Handle upload foto baru
        if (!empty($_FILES['foto']['name'])) {
            $foto = $_FILES['foto'];
            $maxSize = 2 * 1024 * 1024; // 2MB

            if ($foto['size'] > $maxSize) {
                throw new Exception("Ukuran foto terlalu besar (max 2MB)");
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($foto['type'], $allowedTypes)) {
                throw new Exception("Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF");
            }

            // Hapus foto lama
            deleteOldPhoto($id);

            $uploadDir = getUploadDir();
            $fileName = generateFileName($foto['name'], $id);
            $uploadPath = $uploadDir . '/' . $fileName;

            if (!move_uploaded_file($foto['tmp_name'], $uploadPath)) {
                throw new Exception("Gagal mengupload foto");
            }

            $fotoName = $fileName;
        }

        // Update database
        query(
        "UPDATE ruangan 
        SET nama_ruangan = ?, gedung = ?, kapasitas = ?, deskripsi = ?, foto = ?, is_active = ?
        WHERE id = ?",
        [$nama_ruangan, $gedung, $kapasitas, $deskripsi, $fotoName, $is_active, $id]
        );


        header("Location: ruangan.php?success=edit");
        exit;
    } catch (Exception $e) {
        header("Location: ruangan.php?error=" . urlencode($e->getMessage()));
        exit;
    }
}

// ===== HAPUS RUANGAN =====
else if ($action === 'delete') {
    try {
        $id = $_GET['id'] ?? 0;

        if (empty($id)) {
            throw new Exception("ID ruangan tidak valid");
        }

        // Get ruangan
        $stmt = query("SELECT * FROM ruangan ORDER BY is_active DESC, nama_ruangan ASC");
        $ruangan = $stmt->fetch();

        if (!$ruangan) {
            throw new Exception("Ruangan tidak ditemukan");
        }

        // Hapus foto
        if ($ruangan['foto']) {
            deleteOldPhoto($id);
        }

        // Delete dari database
        query("DELETE FROM ruangan WHERE id = ?", [$id]);

        header("Location: ruangan.php?success=delete");
        exit;
    } catch (Exception $e) {
        header("Location: ruangan.php?error=" . urlencode($e->getMessage()));
        exit;
    }
} else {
    header("Location: ruangan.php?error=Aksi tidak valid");
    exit;
}
