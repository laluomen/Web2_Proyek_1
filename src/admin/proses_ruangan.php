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
    $ext = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));
    $token = bin2hex(random_bytes(6));
    return 'ruangan_' . $ruanganId . '_' . time() . '_' . $token . '.' . $ext;
}

function getUploadDir()
{
    $dir = __DIR__ . "/../uploads/ruangan";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function deletePhotoFile($fileName)
{
    if (empty($fileName)) {
        return;
    }
    $uploadDir = getUploadDir();
    $path = $uploadDir . '/' . $fileName;
    if (is_file($path)) {
        unlink($path);
    }
}

function validateImageFile(array $file)
{
    $maxSize = 2 * 1024 * 1024; // 2MB
    if (!empty($file['error']) && $file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload foto gagal: " . uploadErrorMessage((int)$file['error']));
    }
    if (!empty($file['size']) && $file['size'] > $maxSize) {
        throw new Exception("Ukuran foto terlalu besar (max 2MB)");
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($mime, $allowedTypes, true)) {
        throw new Exception("Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF. Terdeteksi: " . $mime);
    }
}

function uploadImageFile(array $file, $ruanganId)
{
    validateImageFile($file);
    $uploadDir = getUploadDir();
    $fileName = generateFileName($file['name'], $ruanganId);
    $uploadPath = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        throw new Exception("Gagal mengupload foto: tidak dapat memindahkan file ke folder upload");
    }
    return $fileName;
}

function uploadErrorMessage(int $code)
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
            return "Ukuran file melebihi batas upload di server (php.ini)";
        case UPLOAD_ERR_FORM_SIZE:
            return "Ukuran file melebihi batas yang ditentukan pada form";
        case UPLOAD_ERR_PARTIAL:
            return "File hanya ter-upload sebagian";
        case UPLOAD_ERR_NO_FILE:
            return "Tidak ada file yang diupload";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "Folder temporary tidak ditemukan";
        case UPLOAD_ERR_CANT_WRITE:
            return "Gagal menulis file ke disk";
        case UPLOAD_ERR_EXTENSION:
            return "Upload dihentikan oleh ekstensi PHP";
        case UPLOAD_ERR_OK:
            return "OK";
        default:
            return "Error tidak dikenal (kode: " . $code . ")";
    }
}

function normalizeFiles(array $files)
{
    $normalized = [];
    if (!isset($files['name']) || !is_array($files['name'])) {
        return $normalized;
    }
    foreach ($files['name'] as $i => $name) {
        if (empty($name)) {
            continue;
        }
        $normalized[] = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i] ?? '',
            'tmp_name' => $files['tmp_name'][$i] ?? '',
            'error' => $files['error'][$i] ?? UPLOAD_ERR_NO_FILE,
            'size' => $files['size'][$i] ?? 0,
        ];
    }
    return $normalized;
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

        // Handle upload foto sampul
        if (!empty($_FILES['foto_cover']['name'])) {
            $fileName = uploadImageFile($_FILES['foto_cover'], $ruanganId);
            query(
                "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'cover')",
                [$ruanganId, $fileName]
            );
        }

        // Handle upload foto detail (multiple)
        if (!empty($_FILES['foto_detail']['name'])) {
            $detailFiles = normalizeFiles($_FILES['foto_detail']);
            foreach ($detailFiles as $file) {
                $fileName = uploadImageFile($file, $ruanganId);
                query(
                    "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'detail')",
                    [$ruanganId, $fileName]
                );
            }
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

        if (empty($id) || empty($nama_ruangan)) {
            throw new Exception("Data tidak valid");
        }

        // Get ruangan saat ini
        $stmt = query("SELECT * FROM ruangan WHERE id = ?", [$id]);
        $ruangan = $stmt->fetch();

        if (!$ruangan) {
            throw new Exception("Ruangan tidak ditemukan");
        }

        // Hapus foto terpilih
        $deleteFotoIds = $_POST['delete_foto'] ?? [];
        if (!empty($deleteFotoIds) && is_array($deleteFotoIds)) {
            foreach ($deleteFotoIds as $fotoId) {
                $fotoId = (int)$fotoId;
                if ($fotoId <= 0) {
                    continue;
                }
                $fotoRow = query(
                    "SELECT id, nama_file FROM ruangan_foto WHERE id = ? AND ruangan_id = ?",
                    [$fotoId, $id]
                )->fetch();
                if ($fotoRow) {
                    deletePhotoFile($fotoRow['nama_file']);
                    query("DELETE FROM ruangan_foto WHERE id = ?", [$fotoId]);
                }
            }
        }

        // Upload foto sampul baru (replace cover lama)
        if (!empty($_FILES['foto_cover']['name'])) {
            $coverRows = query(
                "SELECT id, nama_file FROM ruangan_foto WHERE ruangan_id = ? AND tipe = 'cover'",
                [$id]
            )->fetchAll();
            foreach ($coverRows as $row) {
                deletePhotoFile($row['nama_file']);
                query("DELETE FROM ruangan_foto WHERE id = ?", [$row['id']]);
            }
            $fileName = uploadImageFile($_FILES['foto_cover'], $id);
            query(
                "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'cover')",
                [$id, $fileName]
            );
        }

        // Upload foto detail baru (multiple)
        if (!empty($_FILES['foto_detail']['name'])) {
            $detailFiles = normalizeFiles($_FILES['foto_detail']);
            foreach ($detailFiles as $file) {
                $fileName = uploadImageFile($file, $id);
                query(
                    "INSERT INTO ruangan_foto (ruangan_id, nama_file, tipe) VALUES (?, ?, 'detail')",
                    [$id, $fileName]
                );
            }
        }

        // Update database
        query(
            "UPDATE ruangan SET nama_ruangan = ?, gedung = ?, kapasitas = ?, deskripsi = ? WHERE id = ?",
            [$nama_ruangan, $gedung, $kapasitas, $deskripsi, $id]
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
        $stmt = query("SELECT * FROM ruangan WHERE id = ?", [$id]);
        $ruangan = $stmt->fetch();

        if (!$ruangan) {
            throw new Exception("Ruangan tidak ditemukan");
        }

        // Hapus semua foto dari tabel ruangan_foto
        $fotoRows = query(
            "SELECT nama_file FROM ruangan_foto WHERE ruangan_id = ?",
            [$id]
        )->fetchAll();
        foreach ($fotoRows as $row) {
            deletePhotoFile($row['nama_file']);
        }

        // Hapus foto lama di tabel ruangan (jika ada)
        if (!empty($ruangan['foto'])) {
            deletePhotoFile($ruangan['foto']);
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
