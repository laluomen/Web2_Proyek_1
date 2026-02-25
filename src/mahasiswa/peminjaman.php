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

$preselectRuanganId = (int) ($_GET['ruangan_id'] ?? 0);

$userId = (int) ($_SESSION['user_id'] ?? 0);

$success = '';
$error = '';

function getCancelStatusId(): int
{
    static $cancelStatusId = null;
    if ($cancelStatusId !== null) {
        return $cancelStatusId;
    }

    $row = query(
        "SELECT id
         FROM status_peminjaman
         WHERE LOWER(nama_status) IN ('dibatalkan', 'ditolak')
         ORDER BY CASE LOWER(nama_status)
             WHEN 'dibatalkan' THEN 1
             WHEN 'ditolak' THEN 2
             ELSE 3
         END
         LIMIT 1"
    )->fetch();

    $cancelStatusId = (int) ($row['id'] ?? 3);
    return $cancelStatusId;
}

// --- Handle CANCEL (batalkan pengajuan yang masih Menunggu) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $id = (int) ($_POST['peminjaman_id'] ?? 0);
    $cancelStatusId = getCancelStatusId();

    if ($id <= 0) {
        $error = "ID peminjaman tidak valid.";
    } else {
        // BATALKAN: ubah status jadi Dibatalkan jika ada, fallback ke Ditolak
        $stmt = query(
            "UPDATE peminjaman
             SET status_id = ?,
                 catatan_admin = IFNULL(NULLIF(catatan_admin,''), 'Dibatalkan oleh mahasiswa')
             WHERE id = ? AND user_id = ? AND status_id = 1",
            [$cancelStatusId, $id, $userId]
        );

        if ($stmt->rowCount() > 0) {
            query(
                "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan)
                 VALUES (?, ?, ?, 'Dibatalkan oleh mahasiswa')",
                [$id, $cancelStatusId, $userId]
            );
            $success = "Pengajuan berhasil dibatalkan.";
        } else {
            $error = "Gagal membatalkan. Pastikan status masih Menunggu dan milik Anda.";
        }
    }
}

// --- Handle CREATE (ajukan peminjaman) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $ruanganId = (int) ($_POST['ruangan_id'] ?? 0);
    $namaKegiatan = trim($_POST['nama_kegiatan'] ?? '');
    $tanggal = trim($_POST['tanggal'] ?? '');
    $jamMulai = trim($_POST['jam_mulai'] ?? '');
    $jamSelesai = trim($_POST['jam_selesai'] ?? '');
    $jumlahPeserta = ($_POST['jumlah_peserta'] ?? '') === '' ? null : (int) $_POST['jumlah_peserta'];

    // Validasi minimal
    if ($ruanganId <= 0)
        $error = "Ruangan wajib dipilih.";
    else if ($namaKegiatan === '')
        $error = "Nama kegiatan wajib diisi.";
    else if ($tanggal === '')
        $error = "Tanggal wajib diisi.";
    else if ($jamMulai === '' || $jamSelesai === '')
        $error = "Jam mulai & jam selesai wajib diisi.";
    else if ($jamMulai >= $jamSelesai)
        $error = "Jam mulai harus lebih kecil dari jam selesai.";

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
            $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
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

    if ($error === '') {
        query(
            "INSERT INTO peminjaman
            (user_id, ruangan_id, nama_kegiatan, tanggal, jam_mulai, jam_selesai, jumlah_peserta, surat, status_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$userId, $ruanganId, $namaKegiatan, $tanggal, $jamMulai, $jamSelesai, $jumlahPeserta, $suratFilename]
        );

        $peminjamanId = (int) db()->lastInsertId();

        query(
            "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan)
            VALUES (?, ?, ?, ?)",
            [$peminjamanId, 1, $userId, 'Pengajuan dibuat oleh mahasiswa']
        );

        $success = "Pengajuan berhasil dibuat dan masuk antrian (Menunggu).";
        $_POST = [];
    }
}

// Data ruangan untuk dropdown
$ruanganList = query("SELECT id, nama_ruangan, gedung, kapasitas FROM ruangan ORDER BY gedung, nama_ruangan")->fetchAll();
$selectedRuanganId = (int) ($_POST['ruangan_id'] ?? $preselectRuanganId);

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
    <div class="kelola-header mb-4">
        <h1>Ajukan Peminjaman</h1>
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
                 <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057; padding-bottom: 20px;">
                        Form Detail Peminjaman
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    </div>
                </div>
            </div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ruangan</label>
                        <select name="ruangan_id" class="form-select" required>
                            <option value="">-- Pilih Ruangan --</option>
                            <?php foreach ($ruanganList as $r): ?>
                                <option value="<?= (int) $r['id'] ?>" <?= ($selectedRuanganId === (int) $r['id']) ? 'selected' : '' ?>>
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
                        <input type="date" name="tanggal" class="form-control" value="<?= e($_POST['tanggal'] ?? '') ?>"
                            required>
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

    <!-- Card Tabel Riwayat Peminjaman -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i  style="color: #22c55e;"></i>Riwayat Pengajuan
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search" style="color: #22c55e;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-white" id="searchInput"
                            placeholder="Cari ruangan, gedung..." style="border-left: 0;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableRiwayat">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th  class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th class="text-center" style="width: 20%; padding: 15px;"><i class="bi bi-door-closed me-1"></i>Ruangan</th>
                            <th class="text-center" style="width: 12%; padding: 15px;"><i class="bi bi-calendar-event me-1"></i>Tanggal</th>
                            <th class="text-center" style="width: 10%; padding: 15px;"><i class="bi bi-clock me-1"></i>Jam</th>
                            <th class="text-center" style="width: 12%; padding: 15px;"><i class="bi bi-card-text me-1"></i>Kegiatan</th>
                            <th class="text-center" style="width: 10%; padding: 15px;"><i class="bi bi-patch-check me-1"></i>Status</th>
                            <th class="text-center" style="width: 20%; padding: 15px;"><i class="bi bi-chat-left-text me-1"></i>Catatan </th>
                            <th class="text-center" style="width: 210px; padding: 15px; white-space: nowrap;"><i class="bi bi-gear me-1"></i>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($riwayat)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada pengajuan peminjaman</p>
                                        <small>Ajukan peminjaman ruangan pertama Anda</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($riwayat as $i => $p): ?>
                                <?php $statusId = (int) $p['status_id']; ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-number">
                                            <?= $i + 1 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;">
                                            <?= htmlspecialchars($p['nama_ruangan']) ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.85rem;">
                                            <i class="bi bi-building me-1"></i><?= htmlspecialchars($p['gedung'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-calendar-fill me-1"></i><?= htmlspecialchars($p['tanggal']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-clock-fill me-1"></i><?= htmlspecialchars(substr($p['jam_mulai'], 0, 5)) ?> - <?= htmlspecialchars(substr($p['jam_selesai'], 0, 5)) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-people-fill me-1"></i><?= htmlspecialchars($p['nama_kegiatan']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php
                                            if ($statusId === 1) $statusBg = 'linear-gradient(135deg, #f59e0b, #d97706)';
                                            elseif ($statusId === 2) $statusBg = 'linear-gradient(135deg, #22c55e, #16a34a)';
                                            elseif ($statusId === 3) $statusBg = 'linear-gradient(135deg, #ef4444, #dc2626)';
                                            elseif ($statusId === 4) $statusBg = 'linear-gradient(135deg, #6b7280, #4b5563)';
                                            elseif ($statusId === 5) $statusBg = 'linear-gradient(135deg, #f97316, #ea580c)';
                                            else $statusBg = 'linear-gradient(135deg, #94a3b8, #64748b)';
                                        ?>
                                        <span class="badge px-3 py-2"
                                            style="background: <?= $statusBg ?>; color: white; font-weight: 600; border-radius: 8px;">
                                            <?= htmlspecialchars($p['nama_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['catatan_admin'])): ?>
                                            <small class="text-muted" style="font-size: 0.85rem;">
                                                <i class="bi bi-info-circle me-1"></i><?= htmlspecialchars(substr($p['catatan_admin'], 0, 60)) ?><?= strlen($p['catatan_admin']) > 60 ? '...' : '' ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <?php if ($statusId === 1): ?>
                                                <form method="POST" onsubmit="return confirm('Batalkan pengajuan ini?');"
                                                    style="display:inline-block;">
                                                    <input type="hidden" name="action" value="cancel">
                                                    <input type="hidden" name="peminjaman_id" value="<?= (int) $p['id'] ?>">
                                                    <button class="btn btn-danger aksi-btn" style="min-width: 90px; font-size: 0.8rem;">
                                                        <i class="bi bi-x-circle-fill me-1"></i>Batalkan
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted fw-semibold">
                    <i class="bi bi-info-circle-fill me-1" style="color: #22c55e;"></i>Total Data:
                    <span class="badge ms-1"
                        style="background: linear-gradient(135deg, #22c55e, #16a34a);"><?= count($riwayat) ?></span>
                    pengajuan terdaftar
                </small>
                <small class="text-muted">
                    <i class="bi bi-calendar-check me-1"></i><?= date('d F Y') ?>
                </small>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableRiwayat tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>