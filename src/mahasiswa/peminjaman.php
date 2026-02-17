<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';
autoMarkSelesai();
requireLogin();
requireRole('mahasiswa');

$pageTitle = "Peminjaman";
$activeNav = "peminjaman";

$userId = (int)($_SESSION['user_id'] ?? 0);

$success = '';
$error = '';

// --- Handle CANCEL (hapus tiket jika masih Menunggu) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $id = (int)($_POST['peminjaman_id'] ?? 0);

    if ($id <= 0) {
        $error = "ID peminjaman tidak valid.";
    } else {
        // Hapus hanya jika milik user ini dan status masih Menunggu (1)
        $stmt = query(
            "DELETE FROM peminjaman
             WHERE id = ? AND user_id = ? AND status_id = 1",
            [$id, $userId]
        );

        if ($stmt->rowCount() > 0) {
            $success = "Pengajuan berhasil dibatalkan (dihapus dari antrian).";
        } else {
            $error = "Gagal membatalkan. Pastikan status masih Menunggu dan milik Anda.";
        }
    }
}

// --- Handle CREATE (ajukan peminjaman) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $ruanganId      = (int)($_POST['ruangan_id'] ?? 0);
    $namaKegiatan   = trim($_POST['nama_kegiatan'] ?? '');
    $tanggal        = trim($_POST['tanggal'] ?? '');
    $jamMulai       = trim($_POST['jam_mulai'] ?? '');
    $jamSelesai     = trim($_POST['jam_selesai'] ?? '');
    $jumlahPeserta  = ($_POST['jumlah_peserta'] ?? '') === '' ? null : (int)$_POST['jumlah_peserta'];

    // Validasi minimal
    if ($ruanganId <= 0) $error = "Ruangan wajib dipilih.";
    else if ($namaKegiatan === '') $error = "Nama kegiatan wajib diisi.";
    else if ($tanggal === '') $error = "Tanggal wajib diisi.";
    else if ($jamMulai === '' || $jamSelesai === '') $error = "Jam mulai & jam selesai wajib diisi.";
    else if ($jamMulai >= $jamSelesai) $error = "Jam mulai harus lebih kecil dari jam selesai.";

    // Pastikan format tanggal benar (YYYY-MM-DD)
    if ($error === '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        $error = "Format tanggal harus YYYY-MM-DD.";
    }

    // Cek bentrok dengan peminjaman yang sudah Disetujui (status_id=2)
    if ($error === '') {
        $conflict = query(
            "SELECT COUNT(*) AS c
             FROM peminjaman
             WHERE ruangan_id = ?
               AND tanggal = ?
               AND status_id = 2
               AND NOT ( ? >= jam_selesai OR ? <= jam_mulai )",
            [$ruanganId, $tanggal, $jamMulai, $jamSelesai]
        )->fetch();

        if (($conflict['c'] ?? 0) > 0) {
            $error = "Jadwal bentrok. Ruangan sudah dipakai pada rentang jam tersebut (sudah disetujui).";
        }
    }

    // Upload surat (opsional)
    $suratFilename = null;
    if ($error === '' && isset($_FILES['surat']) && $_FILES['surat']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['surat']['error'] !== UPLOAD_ERR_OK) {
            $error = "Upload surat gagal.";
        } else {
            $allowed = ['pdf','jpg','jpeg','png'];
            $ext = strtolower(pathinfo($_FILES['surat']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) {
                $error = "Format surat harus PDF/JPG/PNG.";
            } else {
                $uploadDir = __DIR__ . '/../uploads/surat/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $suratFilename = 'surat_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest = $uploadDir . $suratFilename;
                if (!move_uploaded_file($_FILES['surat']['tmp_name'], $dest)) {
                    $error = "Gagal menyimpan file surat.";
                }
            }
        }
    }

    // Insert peminjaman (status default Menunggu / 1)
    if ($error === '') {
        query(
            "INSERT INTO peminjaman
             (user_id, ruangan_id, nama_kegiatan, tanggal, jam_mulai, jam_selesai, jumlah_peserta, surat, status_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$userId, $ruanganId, $namaKegiatan, $tanggal, $jamMulai, $jamSelesai, $jumlahPeserta, $suratFilename]
        );
        $success = "Pengajuan berhasil dibuat dan masuk antrian (Menunggu).";

        // reset form values (opsional)
        $_POST = [];
    }
}

// Data ruangan untuk dropdown
$ruanganList = query("SELECT id, nama_ruangan, gedung, kapasitas FROM ruangan ORDER BY gedung, nama_ruangan")->fetchAll();

// Riwayat pengajuan user
$riwayat = query(
    "SELECT p.*, r.nama_ruangan, r.gedung, sp.nama_status
     FROM peminjaman p
     JOIN ruangan r ON r.id = p.ruangan_id
     JOIN status_peminjaman sp ON sp.id = p.status_id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC",
    [$userId]
)->fetchAll();

require_once __DIR__ . "/../templates/header.php";
?>

<div class="container py-4">
    <div class="mb-3 p-3 rounded" style="background: rgba(0,0,0,.35);">
        <h3 class="m-0 text-white fw-bold">Ajukan Peminjaman Ruangan</h3>
        <div class="text-white-50">Isi form di bawah untuk masuk antrian peminjaman.</div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ruangan</label>
                        <select name="ruangan_id" class="form-select" required>
                            <option value="">-- Pilih Ruangan --</option>
                            <?php foreach ($ruanganList as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"
                                    <?= ((int)($_POST['ruangan_id'] ?? 0) === (int)$r['id']) ? 'selected' : '' ?>>
                                    <?= e($r['gedung'] . ' - ' . $r['nama_ruangan'] . ' (Kapasitas: ' . ($r['kapasitas'] ?? '-') . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nama Kegiatan</label>
                        <input type="text" name="nama_kegiatan" class="form-control"
                               value="<?= e($_POST['nama_kegiatan'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control"
                               value="<?= e($_POST['tanggal'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jam Mulai</label>
                        <input type="time" name="jam_mulai" class="form-control"
                               value="<?= e($_POST['jam_mulai'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Jam Selesai</label>
                        <input type="time" name="jam_selesai" class="form-control"
                               value="<?= e($_POST['jam_selesai'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Jumlah Peserta (opsional)</label>
                        <input type="number" name="jumlah_peserta" class="form-control" min="1"
                               value="<?= e($_POST['jumlah_peserta'] ?? '') ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Surat (opsional: PDF/JPG/PNG)</label>
                        <input type="file" name="surat" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                </div>

                <button class="btn btn-primary mt-3">Ajukan</button>
            </form>
        </div>
    </div>

    <h4 class="mb-3">Riwayat Pengajuan Saya</h4>
    <div class="rounded-4 bg-white shadow-sm">
        <div class="table-responsive rounded-4">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Ruangan</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Kegiatan</th>
                        <th>Status</th>
                        <th class="text-center" style="min-width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$riwayat): ?>
                    <tr><td colspan="7" class="text-center">Belum ada pengajuan.</td></tr>
                <?php else: ?>
                    <?php foreach ($riwayat as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= e($p['gedung'] . ' - ' . $p['nama_ruangan']) ?></td>
                            <td><?= e($p['tanggal']) ?></td>
                            <td><?= e(substr($p['jam_mulai'],0,5) . ' - ' . substr($p['jam_selesai'],0,5)) ?></td>
                            <td><?= e($p['nama_kegiatan']) ?></td>
                            <td><?= e($p['nama_status']) ?></td>
                            <td class="text-center" style="overflow:hidden;">
                                <?php if ((int)$p['status_id'] === 1): ?>
                                    <form method="POST" onsubmit="return confirm('Batalkan pengajuan ini?');" style="display:inline-block; max-width:100%;">
                                        <input type="hidden" name="action" value="cancel">
                                        <input type="hidden" name="peminjaman_id" value="<?= (int)$p['id'] ?>">
                                        <button class="btn btn-sm btn-danger" style="display:inline-block; width:auto; max-width:100%; padding:.35rem .9rem; border-radius:999px; white-space:nowrap;">
                                            Batalkan
                                        </button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>