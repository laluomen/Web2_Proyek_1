<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('admin');
autoMarkSelesai();

$pageTitle = "Laporan Bulanan";
$activeAdmin = "laporan";

/**
 * Input periode
 */
$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));
if ($year < 2000 || $year > 2100)
    $year = (int) date('Y');
if ($month < 1 || $month > 12)
    $month = (int) date('n');

$startDate = sprintf('%04d-%02d-01', $year, $month);
$endDate = date('Y-m-t', strtotime($startDate));

/**
 * Filter opsional untuk tabel detail
 */
$ruanganId = (int) ($_GET['ruangan_id'] ?? 0);
$statusId = (int) ($_GET['status_id'] ?? 0);

/**
 * Dropdown data
 */
$ruanganList = query(
    "SELECT r.id, r.nama_ruangan, g.nama_gedung AS gedung
     FROM ruangan r
     LEFT JOIN lantai l ON l.id = r.lantai_id
     LEFT JOIN gedung g ON g.id = l.gedung_id
     ORDER BY g.nama_gedung, r.nama_ruangan"
)->fetchAll();
$statusList = query("SELECT id, nama_status FROM status_peminjaman ORDER BY id")->fetchAll();

/**
 * KPI total bulan
 */
$totalAll = (int) (query(
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE tanggal BETWEEN ? AND ?",
    [$startDate, $endDate]
)->fetch()['total'] ?? 0);

/**
 * KPI per status
 */
$statusCountsRaw = query(
    "SELECT sp.id, sp.nama_status, COUNT(p.id) AS jumlah
     FROM status_peminjaman sp
     LEFT JOIN peminjaman p
       ON p.status_id = sp.id
      AND p.tanggal BETWEEN ? AND ?
     GROUP BY sp.id, sp.nama_status
     ORDER BY sp.id",
    [$startDate, $endDate]
)->fetchAll();

$statusCounts = [];
foreach ($statusCountsRaw as $row) {
    $sid = (int) $row['id'];
    $statusCounts[$sid] = [
        'nama' => (string) $row['nama_status'],
        'jumlah' => (int) $row['jumlah'],
    ];
}

$approved = $statusCounts[2]['jumlah'] ?? 0;
$approvalRate = $totalAll > 0 ? round(($approved / $totalAll) * 100, 1) : 0.0;

/**
 * Total jam & rata-rata durasi (status 2/4)
 */
$totalJam = (string) (query(
    "SELECT COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai)))), '00:00:00') AS total_jam
     FROM peminjaman
     WHERE status_id IN (2,4)
       AND tanggal BETWEEN ? AND ?",
    [$startDate, $endDate]
)->fetch()['total_jam'] ?? '00:00:00');

$avgDurasi = (string) (query(
    "SELECT COALESCE(SEC_TO_TIME(AVG(TIME_TO_SEC(TIMEDIFF(jam_selesai, jam_mulai)))), '00:00:00') AS avg_durasi
     FROM peminjaman
     WHERE status_id IN (2,4)
       AND tanggal BETWEEN ? AND ?",
    [$startDate, $endDate]
)->fetch()['avg_durasi'] ?? '00:00:00');

/**
 * Top ruangan (paling sering dipakai) status 2/4
 */
$topRuangan = query(
        "SELECT r.id, g.nama_gedung AS gedung, r.nama_ruangan,
            COUNT(p.id) AS jumlah_booking,
            COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS total_jam
     FROM peminjaman p
     JOIN ruangan r ON r.id = p.ruangan_id
         LEFT JOIN lantai l ON l.id = r.lantai_id
         LEFT JOIN gedung g ON g.id = l.gedung_id
     WHERE p.status_id IN (2,4)
       AND p.tanggal BETWEEN ? AND ?
         GROUP BY r.id, g.nama_gedung, r.nama_ruangan
     ORDER BY jumlah_booking DESC
     LIMIT 10",
    [$startDate, $endDate]
)->fetchAll();

/**
 * Rekap harian
 */
$rekapHarian = query(
    "SELECT tanggal,
            COUNT(*) AS total_pengajuan,
            SUM(status_id IN (2,4)) AS disetujui_atau_selesai,
            SUM(status_id = 3) AS ditolak,
            SUM(status_id = 1) AS menunggu,
            SUM(status_id = 5) AS dibatalkan
     FROM peminjaman
     WHERE tanggal BETWEEN ? AND ?
     GROUP BY tanggal
     ORDER BY tanggal",
    [$startDate, $endDate]
)->fetchAll();

/**
 * Statistik proses dari log_status:
 * rata-rata waktu Menunggu(1) -> keputusan pertama (2/3/5)
 */
$avgProcess = query(
    "SELECT
        AVG(CASE WHEN l2.status_id = 2 THEN TIMESTAMPDIFF(SECOND, l1.waktu, l2.waktu) END) AS avg_to_approve,
        AVG(CASE WHEN l2.status_id = 3 THEN TIMESTAMPDIFF(SECOND, l1.waktu, l2.waktu) END) AS avg_to_reject,
        AVG(CASE WHEN l2.status_id = 5 THEN TIMESTAMPDIFF(SECOND, l1.waktu, l2.waktu) END) AS avg_to_cancel
     FROM log_status l1
     JOIN log_status l2
       ON l2.peminjaman_id = l1.peminjaman_id
      AND l2.id = (
         SELECT MIN(x.id)
         FROM log_status x
         WHERE x.peminjaman_id = l1.peminjaman_id
           AND x.status_id IN (2,3,5)
      )
     WHERE l1.status_id = 1
       AND l1.waktu BETWEEN ? AND ?",
    [$startDate . " 00:00:00", $endDate . " 23:59:59"]
)->fetch();

function seconds_to_hm(?int $sec): string
{
    if (!$sec || $sec < 0)
        return "-";
    $h = intdiv($sec, 3600);
    $m = intdiv($sec % 3600, 60);
    return $h > 0 ? ($h . " jam " . $m . " menit") : ($m . " menit");
}

$avgToApprove = seconds_to_hm(isset($avgProcess['avg_to_approve']) ? (int) $avgProcess['avg_to_approve'] : null);
$avgToReject = seconds_to_hm(isset($avgProcess['avg_to_reject']) ? (int) $avgProcess['avg_to_reject'] : null);
$avgToCancel = seconds_to_hm(isset($avgProcess['avg_to_cancel']) ? (int) $avgProcess['avg_to_cancel'] : null);

/**
 * Aktivitas admin (approve/reject)
 */
$adminActivity = query(
    "SELECT u.id, u.nama,
            SUM(CASE WHEN ls.status_id = 2 THEN 1 ELSE 0 END) AS approve_count,
            SUM(CASE WHEN ls.status_id = 3 THEN 1 ELSE 0 END) AS reject_count
     FROM log_status ls
     JOIN users u ON u.id = ls.diubah_oleh
     WHERE ls.status_id IN (2,3)
       AND ls.waktu BETWEEN ? AND ?
     GROUP BY u.id, u.nama
     ORDER BY
       (SUM(CASE WHEN ls.status_id IN (2,3) THEN 1 ELSE 0 END)) DESC,
       u.nama ASC",
    [$startDate . " 00:00:00", $endDate . " 23:59:59"]
)->fetchAll();

/**
 * Detail transaksi
 */
$where = "p.tanggal BETWEEN ? AND ?";
$params = [$startDate, $endDate];
if ($ruanganId > 0) {
    $where .= " AND p.ruangan_id = ?";
    $params[] = $ruanganId;
}
if ($statusId > 0) {
    $where .= " AND p.status_id = ?";
    $params[] = $statusId;
}

$detail = query(
    "SELECT p.id, p.tanggal, p.jam_mulai, p.jam_selesai, p.nama_kegiatan, p.jumlah_peserta, p.catatan_admin,
            sp.nama_status,
            u.nama AS nama_peminjam, u.prodi,
         g.nama_gedung AS gedung, r.nama_ruangan
     FROM peminjaman p
     JOIN users u ON u.id = p.user_id
     JOIN ruangan r ON r.id = p.ruangan_id
     LEFT JOIN lantai l ON l.id = r.lantai_id
     LEFT JOIN gedung g ON g.id = l.gedung_id
     JOIN status_peminjaman sp ON sp.id = p.status_id
     WHERE $where
     ORDER BY p.tanggal DESC, p.jam_mulai DESC",
    $params
)->fetchAll();

/**
 * Export CSV (detail transaksi)
 */
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="laporan_' . $year . '_' . sprintf('%02d', $month) . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Durasi (menit)', 'Ruangan', 'Peminjam', 'Prodi', 'Kegiatan', 'Peserta', 'Status', 'Catatan']);

    foreach ($detail as $d) {
        $durMin = 0;
        if (!empty($d['jam_mulai']) && !empty($d['jam_selesai'])) {
            $durMin = (int) round((strtotime($d['tanggal'] . ' ' . $d['jam_selesai']) - strtotime($d['tanggal'] . ' ' . $d['jam_mulai'])) / 60);
        }
        fputcsv($out, [
            $d['id'],
            $d['tanggal'],
            substr((string) $d['jam_mulai'], 0, 5),
            substr((string) $d['jam_selesai'], 0, 5),
            $durMin,
            $d['gedung'] . ' - ' . $d['nama_ruangan'],
            $d['nama_peminjam'],
            $d['prodi'] ?? '-',
            $d['nama_kegiatan'],
            $d['jumlah_peserta'] ?? '',
            $d['nama_status'],
            $d['catatan_admin'] ?? '',
        ]);
    }
    fclose($out);
    exit;
}

// ====== Templates ======
require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Bulanan</h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow border-0 mb-4" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-funnel me-2" style="color: #22c55e;"></i>Filter Laporan
                    </h5>
                </div>
                <div class="col-md-6 text-end">
                    <span class="badge bg-info">
                        <i class="bi bi-calendar3 me-1"></i>
                        Periode: <?= e(date('F Y', strtotime($startDate))) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="card-body p-4">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar-month me-1"></i>Bulan
                    </label>
                    <select name="month" class="form-select" style="border-radius: 8px; padding: 10px 15px;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-calendar-year me-1"></i>Tahun
                    </label>
                    <select name="year" class="form-select" style="border-radius: 8px; padding: 10px 15px;">
                        <?php $yNow = (int) date('Y');
                        for ($y = $yNow - 3; $y <= $yNow + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-door-open me-1"></i>Ruangan
                    </label>
                    <select name="ruangan_id" class="form-select" style="border-radius: 8px; padding: 10px 15px;">
                        <option value="0">Semua Ruangan</option>
                        <?php foreach ($ruanganList as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= ((int) $r['id'] === $ruanganId) ? 'selected' : '' ?>>
                                <?= e($r['gedung'] . ' - ' . $r['nama_ruangan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-bookmark-check me-1"></i>Status
                    </label>
                    <select name="status_id" class="form-select" style="border-radius: 8px; padding: 10px 15px;">
                        <option value="0">Semua Status</option>
                        <?php foreach ($statusList as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ((int) $s['id'] === $statusId) ? 'selected' : '' ?>>
                                <?= e($s['nama_status']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn text-white w-100"
                        style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-search me-1"></i>Terapkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="text-muted mb-2">
                                <i class="bi bi-file-earmark-text me-1"></i>Total Pengajuan
                            </h6>
                            <h2 class="mb-0 fw-bold text-primary"><?= (int) $totalAll ?></h2>
                            <small class="text-muted">
                                <i class="bi bi-graph-up me-1"></i>Approval rate: <span class="fw-bold text-success"><?= e((string) $approvalRate) ?>%</span>
                            </small>
                        </div>
                        <div class="text-primary" style="font-size: 2.5rem; opacity: 0.3;">
                            <i class="bi bi-clipboard-data"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        $kpiMap = [
            2 => ['label' => 'Disetujui', 'icon' => 'check-circle-fill', 'color' => 'success', 'bg' => '#d4edda'],
            4 => ['label' => 'Selesai', 'icon' => 'check2-all', 'color' => 'info', 'bg' => '#d1ecf1'],
            3 => ['label' => 'Ditolak', 'icon' => 'x-circle-fill', 'color' => 'danger', 'bg' => '#f8d7da'],
        ];
        foreach ($kpiMap as $sid => $config):
            $val = $statusCounts[$sid]['jumlah'] ?? 0;
            ?>
            <div class="col-lg-3 col-md-6">
                <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted mb-2">
                                    <i class="bi bi-<?= $config['icon'] ?> me-1"></i><?= e($config['label']) ?>
                                </h6>
                                <h2 class="mb-0 fw-bold text-<?= $config['color'] ?>"><?= (int) $val ?></h2>
                                <small class="text-muted">Pengajuan</small>
                            </div>
                            <div class="text-<?= $config['color'] ?>" style="font-size: 2.5rem; opacity: 0.3;">
                                <i class="bi bi-<?= $config['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom"
                    style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
                    <h6 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-clock-history me-2" style="color: #22c55e;"></i>Statistik Pemakaian
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                        <label class="text-muted small mb-1">
                            <i class="bi bi-hourglass-split me-1"></i>Total Jam Terpakai (Status Disetujui/Selesai)
                        </label>
                        <div class="fw-bold fs-5 text-primary"><?= e($totalJam) ?></div>
                    </div>
                    <div class="mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                        <label class="text-muted small mb-1">
                            <i class="bi bi-speedometer2 me-1"></i>Rata-rata Durasi
                        </label>
                        <div class="fw-bold fs-5 text-success"><?= e($avgDurasi) ?></div>
                    </div>
                    <hr>
                    <h6 class="fw-bold mb-3">
                        <i class="bi bi-lightning me-1 text-warning"></i>Kecepatan Proses
                    </h6>
                    <div class="mb-2">
                        <small class="text-muted">Menunggu → Disetujui:</small>
                        <span class="fw-bold float-end text-success"><?= e($avgToApprove) ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Menunggu → Ditolak:</small>
                        <span class="fw-bold float-end text-danger"><?= e($avgToReject) ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Menunggu → Dibatalkan:</small>
                        <span class="fw-bold float-end text-warning"><?= e($avgToCancel) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow border-0 h-100" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom"
                    style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
                    <h6 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-star-fill me-2" style="color: #fbbf24;"></i>Top Ruangan (Paling Sering Dipakai)
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                                <tr>
                                    <th style="width: 50px; padding: 15px 10px;" class="text-center">
                                        <i class="bi bi-hash"></i>
                                    </th>
                                    <th style="padding: 15px;">
                                        <i class="bi bi-door-open me-1"></i>Ruangan
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-calendar-check me-1"></i>Jumlah Booking
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-clock me-1"></i>Total Jam
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$topRuangan): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">Tidak ada data</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topRuangan as $i => $tr): ?>
                                        <tr>
                                            <td class="text-center">
                                                <span class="badge-number"><?= $i + 1 ?></span>
                                            </td>
                                            <td>
                                                <span class="fw-bold"><?= e($tr['gedung'] . ' - ' . $tr['nama_ruangan']) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-info"><?= (int) $tr['jumlah_booking'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success"><?= e($tr['total_jam']) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Hanya status Disetujui/Selesai dihitung pemakaian.
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Daily Report & Admin Activity -->
    <div class="row g-3 mb-4">
        <div class="col-lg-7">
            <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom"
                    style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
                    <h6 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-calendar3 me-2" style="color: #22c55e;"></i>Rekap Harian
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                                <tr>
                                    <th style="padding: 15px;">
                                        <i class="bi bi-calendar-date me-1"></i>Tanggal
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-files me-1"></i>Total
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-check-circle me-1"></i>Setuju
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-x-circle me-1"></i>Tolak
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-clock me-1"></i>Tunggu
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-dash-circle me-1"></i>Batal
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rekapHarian): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">Tidak ada data</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rekapHarian as $rh): ?>
                                        <tr>
                                            <td>
                                                <span class="fw-bold"><?= e(date('d M Y', strtotime($rh['tanggal']))) ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-primary"><?= (int) $rh['total_pengajuan'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success"><?= (int) $rh['disetujui_atau_selesai'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-danger"><?= (int) $rh['ditolak'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-warning"><?= (int) $rh['menunggu'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-secondary"><?= (int) $rh['dibatalkan'] ?></span>
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

        <div class="col-lg-5">
            <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
                <div class="card-header bg-white py-3 border-bottom"
                    style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
                    <h6 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-person-check me-2" style="color: #667eea;"></i>Aktivitas Admin
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                                <tr>
                                    <th style="padding: 15px;">
                                        <i class="bi bi-person me-1"></i>Admin
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-check-lg me-1"></i>Approve
                                    </th>
                                    <th class="text-end" style="padding: 15px;">
                                        <i class="bi bi-x-lg me-1"></i>Reject
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                            <tbody>
                                <?php if (!$adminActivity): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                                <p class="mb-0">Tidak ada data</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($adminActivity as $a): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-2" style="width: 35px; height: 35px; font-size: 0.875rem;">
                                                        <?= strtoupper(substr($a['nama'], 0, 1)) ?>
                                                    </div>
                                                    <span class="fw-bold"><?= e($a['nama']) ?></span>
                                                </div>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-success"><?= (int) $a['approve_count'] ?></span>
                                            </td>
                                            <td class="text-end">
                                                <span class="badge bg-danger"><?= (int) $a['reject_count'] ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light border-0">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>Sumber: log_status (status Disetujui/Ditolak).
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Detail Transactions -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                <h6 class="mb-0 fw-bold" style="color: #495057;">
                    <i class="bi bi-list-columns me-2" style="color: #22c55e;"></i>Detail Transaksi
                </h6>
                <a class="btn btn-sm text-white"
                    style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 8px; padding: 6px 16px; font-weight: 600;"
                    href="?year=<?= $year ?>&month=<?= $month ?>&ruangan_id=<?= $ruanganId ?>&status_id=<?= $statusId ?>&export=csv">
                    <i class="bi bi-download me-1"></i>Export CSV
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th style="width: 50px; padding: 15px 10px;" class="text-center">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-calendar-date me-1"></i>Tanggal
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-clock me-1"></i>Jam
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-door-open me-1"></i>Ruangan
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-person me-1"></i>Peminjam
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-mortarboard me-1"></i>Prodi
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-activity me-1"></i>Kegiatan
                            </th>
                            <th class="text-end" style="padding: 15px;">
                                <i class="bi bi-people me-1"></i>Peserta
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-bookmark me-1"></i>Status
                            </th>
                            <th style="padding: 15px;">
                                <i class="bi bi-chat-left-text me-1"></i>Catatan
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    </thead>
                    <tbody>
                        <?php if (!$detail): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Tidak ada data transaksi</p>
                                        <small>Coba ubah filter untuk melihat data lainnya</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php 
                            $statusBadgeMap = [
                                'Menunggu' => 'warning',
                                'Disetujui' => 'success',
                                'Ditolak' => 'danger',
                                'Selesai' => 'info',
                                'Dibatalkan' => 'secondary'
                            ];
                            foreach ($detail as $d): 
                                $badgeClass = $statusBadgeMap[$d['nama_status']] ?? 'secondary';
                            ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-number"><?= (int) $d['id'] ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= e(date('d M Y', strtotime($d['tanggal']))) ?></span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?= e(substr((string) $d['jam_mulai'], 0, 5)) ?> - <?= e(substr((string) $d['jam_selesai'], 0, 5)) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= e($d['gedung'] . ' - ' . $d['nama_ruangan']) ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-2" style="width: 30px; height: 30px; font-size: 0.75rem;">
                                                <?= strtoupper(substr($d['nama_peminjam'], 0, 1)) ?>
                                            </div>
                                            <span><?= e($d['nama_peminjam']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <?= $d['prodi'] ? '<small>' . e($d['prodi']) . '</small>' : '<span class="text-muted">-</span>' ?>
                                    </td>
                                    <td><?= e($d['nama_kegiatan']) ?></td>
                                    <td class="text-end">
                                        <span class="badge bg-info"><?= e((string) ($d['jumlah_peserta'] ?? '-')) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= e($d['nama_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($d['catatan_admin'])): ?>
                                            <small><?= e($d['catatan_admin']) ?></small>
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
</div>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>