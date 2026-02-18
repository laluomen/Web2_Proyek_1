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
                query("UPDATE peminjaman SET status_id = 2, catatan_admin = ? WHERE id = ? AND status_id = 1", [$catatan, $id]);

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

                $success = "Pengajuan berhasil disetujui. Pengajuan lain yang bentrok otomatis ditolak.";
            } elseif ($action === 'reject') {
                // Tolak
                query("UPDATE peminjaman SET status_id = 3, catatan_admin = ? WHERE id = ? AND status_id = 1", [$catatan, $id]);
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

<div class="container-fluid py-4">
    <div class="mb-3 p-3 rounded" style="background: rgba(0,0,0,.35);">
        <h3 class="m-0 text-white fw-bold">Persetujuan Peminjaman</h3>
        <div class="text-white-50">Admin berhak memutuskan siapa yang diapprove berdasarkan urgensi</div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th style="min-width:150px;">Mahasiswa</th>
                            <th style="min-width:100px;">Ruangan</th>
                            <th style="min-width:100px;">Tanggal</th>
                            <th style="min-width:110px;">Jam</th>
                            <th>Kegiatan</th>
                            <th>Peserta</th>
                            <th>Surat</th>
                            <th style="min-width:180px;">Persetujuan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$pending): ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada pengajuan menunggu.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($pending as $i => $p): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <?= e($p['nama_user'] ?? '-') ?>
                                        <div class="text-muted small">
                                            <?= e($p['username_user'] ?? '-') ?>
                                            <?= !empty($p['prodi_user']) ? ' • ' . e($p['prodi_user']) : '' ?>
                                        </div>
                                    </td>
                                    <td><?= e(($p['gedung'] ?? '-') . ' - ' . ($p['nama_ruangan'] ?? '-')) ?></td>
                                    <td style="white-space:nowrap;"><?= e($p['tanggal']) ?></td>
                                    <td style="white-space:nowrap;">
                                        <?= e(substr($p['jam_mulai'], 0, 5) . ' - ' . substr($p['jam_selesai'], 0, 5)) ?>
                                    </td>
                                    <td><?= e($p['nama_kegiatan']) ?></td>
                                    <td><?= e($p['jumlah_peserta'] ?? '-') ?></td>
                                    <td>
                                        <?php if (!empty($p['surat'])): ?>
                                            <!-- Sesuaikan jika ada handler download khusus -->
                                            <a class="btn btn-sm px-3 text-white" style="background:#FFB300; border-color:#FFB300;"
                                                href="<?= $BASE ?>uploads/surat/<?= e($p['surat']) ?>" target="_blank"
                                                rel="noopener">
                                                Lihat Surat
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" style="min-width:260px;">
                                        <form method="POST" class="d-flex flex-column align-items-center gap-2">
                                            <input type="hidden" name="peminjaman_id" value="<?= (int) $p['id'] ?>">

                                            <input type="text" name="catatan_admin" class="form-control form-control-sm"
                                                placeholder="Catatan / alasan (opsional)" style="max-width:240px;">

                                            <div class="d-flex gap-2">
                                                <button class="btn btn-sm btn-success px-3" name="action" value="approve"
                                                    onclick="return confirm('Setujui pengajuan ini?');">
                                                    Setujui
                                                </button>

                                                <button class="btn btn-sm px-3"
                                                    style="background:#DC3545; border-color:#DC3545; color:#fff;" name="action"
                                                    value="reject" onclick="return confirm('Tolak pengajuan ini?');">
                                                    Tolak
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

<?php require_once __DIR__ . '/../templates/footer.php'; ?>