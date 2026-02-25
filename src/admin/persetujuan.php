<?php

declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('admin');

autoMarkSelesai();

$pageTitle = "Persetujuan Peminjaman";
$activeAdmin = "approve";
$adminId = (int) ($_SESSION['user_id'] ?? 0);

$success = '';
$error = '';

/**
 * Cek overlap jam: NOT (newStart >= oldEnd OR newEnd <= oldStart)
 * untuk reject otomatis pengajuan lain yang bentrok.
 */

// Handle action approve/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['peminjaman_id'] ?? 0);
    $catatan = trim($_POST['catatan_admin'] ?? '');

    if ($id <= 0) {
        $error = "ID peminjaman tidak valid.";
    } else {
        // Ambil data peminjaman yang mau diproses (harus masih Menunggu)
        $p = query(
            "SELECT id, ruangan_id, tanggal, jam_mulai, jam_selesai, status_id
             FROM peminjaman
             WHERE id = ?",
            [$id]
        )->fetch();

        if (!$p) {
            $error = "Data peminjaman tidak ditemukan.";
        } elseif ((int) $p['status_id'] !== 1) {
            $error = "Pengajuan ini sudah diproses (bukan Menunggu).";
        } else {
            if ($action === 'approve') {
                // Setujui
                query(
                    "UPDATE peminjaman SET status_id = 2, catatan_admin = ? WHERE id = ? AND status_id = 1",
                    [$catatan, $id]
                );

                // log approve (status baru = 2)
                $noteApprove = trim($catatan) !== '' ? $catatan : 'Disetujui oleh admin';
                query(
                    "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)",
                    [$id, 2, $adminId, $noteApprove]
                );

                // --- ambil daftar pengajuan lain yang bentrok SEBELUM di-update ---
                $conflictIds = query(
                    "SELECT id
                    FROM peminjaman
                    WHERE status_id = 1
                    AND id <> ?
                    AND ruangan_id = ?
                    AND tanggal = ?
                    AND NOT ( ? >= jam_selesai OR ? <= jam_mulai )",
                    [$id, $p['ruangan_id'], $p['tanggal'], $p['jam_mulai'], $p['jam_selesai']]
                )->fetchAll(PDO::FETCH_COLUMN);

                // auto-tolak yang bentrok di slot sama
                query(
                    "UPDATE peminjaman
                    SET status_id = 3,
                    catatan_admin = IFNULL(NULLIF(catatan_admin,''), 'Mohon maaf, anda bentrok jadwal dengan pengajuan lain')
                    WHERE status_id = 1
                    AND id <> ?
                    AND ruangan_id = ?
                    AND tanggal = ?
                    AND NOT ( ? >= jam_selesai OR ? <= jam_mulai )",
                    [$id, $p['ruangan_id'], $p['tanggal'], $p['jam_mulai'], $p['jam_selesai']]
                );

                // log untuk yang auto-ditolak karena bentrok
                foreach ($conflictIds as $cid) {
                    query(
                        "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)",
                        [(int) $cid, 3, $adminId, 'Auto-ditolak karena bentrok jadwal']
                    );
                }

                $success = "Pengajuan berhasil disetujui. Pengajuan lain yang bentrok otomatis ditolak.";
            } elseif ($action === 'reject') {
                // Tolak
                query(
                    "UPDATE peminjaman SET status_id = 3, catatan_admin = ? WHERE id = ? AND status_id = 1",
                    [$catatan, $id]
                );

                // log reject (status baru = 3)
                $noteReject = trim($catatan) !== '' ? $catatan : 'Ditolak oleh admin';
                query(
                    "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan) VALUES (?, ?, ?, ?)",
                    [$id, 3, $adminId, $noteReject]
                );

                $success = "Pengajuan berhasil ditolak.";
            } else {
                $error = "Aksi tidak dikenal.";
            }
        }
    }
}

// List pengajuan Menunggu
$pending = query(
    "SELECT p.id, p.nama_kegiatan, p.tanggal, p.jam_mulai, p.jam_selesai, p.jumlah_peserta, p.surat,
            r.nama_ruangan, r.gedung,
            u.nama AS nama_user, u.username AS username_user, u.prodi AS prodi_user
     FROM peminjaman p
     JOIN ruangan r ON r.id = p.ruangan_id
     JOIN users u ON u.id = p.user_id
     WHERE p.status_id = 1
     ORDER BY p.tanggal ASC, p.jam_mulai ASC, p.id ASC"
)->fetchAll();

require_once __DIR__ . '/../templates/admin_head.php';
require_once __DIR__ . '/../templates/admin_sidebar.php';
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1><i class="bi bi-clipboard-check me-2"></i>Persetujuan Peminjaman</h1>
    </div>

    <!-- Alert Messages -->
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Error!</strong> <?= e($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <strong>Berhasil!</strong> <?= e($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Card Tabel Persetujuan -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-list-ul me-2" style="color: #22c55e;"></i>Daftar Persetujuan
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search" style="color: #22c55e;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-white" id="searchInput"
                            placeholder="Cari mahasiswa, ruangan, kegiatan..." style="border-left: 0;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tablePersetujuan">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th style="width: 20%; padding: 15px;">
                                <i class="bi bi-person me-1"></i>Mahasiswa
                            </th>
                            <th style="width: 15%; padding: 15px;">
                                <i class="bi bi-door-closed me-1"></i>Ruangan
                            </th>
                            <th class="text-center" style="width: 10%; padding: 15px;">
                                <i class="bi bi-calendar3 me-1"></i>Tanggal
                            </th>
                            <th class="text-center" style="width: 10%; padding: 15px;">
                                <i class="bi bi-clock me-1"></i>Jam
                            </th>
                            <th style="width: 15%; padding: 15px;">
                                <i class="bi bi-clipboard-check me-1"></i>Kegiatan
                            </th>
                            <th class="text-center" style="width: 8%; padding: 15px;">
                                <i class="bi bi-people me-1"></i>Peserta
                            </th>
                            <th class="text-center" style="width: 8%; padding: 15px;">
                                <i class="bi bi-file-text me-1"></i>Surat
                            </th>
                            <th class="text-center" style="width: 280px; padding: 15px;">
                                <i class="bi bi-gear me-1"></i>Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$pending): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada pengajuan menunggu</p>
                                        <small>Semua pengajuan telah diproses</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending as $i => $p): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-number">
                                            <?= $i + 1 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;">
                                            <?= e($p['nama_user'] ?? '-') ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.85rem;">
                                            <i class="bi bi-person-badge me-1"></i><?= e($p['username_user'] ?? '-') ?>
                                            <?= !empty($p['prodi_user']) ? ' • ' . e($p['prodi_user']) : '' ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;">
                                            <?= e($p['nama_ruangan'] ?? '-') ?>
                                        </div>
                                        <small class="text-muted" style="font-size: 0.85rem;">
                                            <i class="bi bi-building me-1"></i><?= e($p['gedung'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background: linear-gradient(135deg, #10b981, #059669);">
                                            <?= date('d M Y', strtotime($p['tanggal'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                            <?= e(substr($p['jam_mulai'], 0, 5) . ' - ' . substr($p['jam_selesai'], 0, 5)) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-dark"><?= e($p['nama_kegiatan']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                                            <?= e($p['jumlah_peserta'] ?? '0') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($p['surat'])): ?>
                                            <a class="btn btn-warning" href="<?= $BASE ?>uploads/surat/<?= e($p['surat']) ?>"
                                                target="_blank" rel="noopener">
                                                <i class="bi bi-file-earmark-pdf me-1"></i>Lihat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-flex flex-column gap-2" style="padding: 0.5rem;">
                                            <input type="hidden" name="peminjaman_id" value="<?= (int) $p['id'] ?>">

                                            <input type="text" name="catatan_admin" class="form-control form-control-sm"
                                                placeholder="Catatan / alasan (opsional)" style="font-size: 0.875rem;">

                                            <div class="d-flex justify-content-center">
                                                <button class="btn btn-success me-2" name="action" value="approve"
                                                    onclick="return confirm('Setujui pengajuan ini?\n\nPengajuan lain yang bentrok jadwal akan otomatis ditolak.');">
                                                    <i class="bi bi-check-circle me-1"></i>Setujui
                                                </button>

                                                <button class="btn btn-danger" name="action" value="reject"
                                                    onclick="return confirm('Tolak pengajuan ini?');">
                                                    <i class="bi bi-x-circle me-1"></i>Tolak
                                                </button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const table = document.getElementById('tablePersetujuan');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];

            // Skip empty state row
            if (row.cells.length === 1) continue;

            const text = row.textContent.toLowerCase();

            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        }
    });

    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>