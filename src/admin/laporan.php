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
$ruanganList = query("SELECT id, nama_ruangan, gedung FROM ruangan ORDER BY gedung, nama_ruangan")->fetchAll();
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
    "SELECT r.id, r.gedung, r.nama_ruangan,
            COUNT(p.id) AS jumlah_booking,
            COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(TIMEDIFF(p.jam_selesai, p.jam_mulai)))), '00:00:00') AS total_jam
     FROM peminjaman p
     JOIN ruangan r ON r.id = p.ruangan_id
     WHERE p.status_id IN (2,4)
       AND p.tanggal BETWEEN ? AND ?
     GROUP BY r.id, r.gedung, r.nama_ruangan
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
            r.gedung, r.nama_ruangan
     FROM peminjaman p
     JOIN users u ON u.id = p.user_id
     JOIN ruangan r ON r.id = p.ruangan_id
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

<div class="mb-3 p-4 rounded" style="background: rgba(0,0,0,.35);">
    <h2 class="m-0 text-white fw-bold">Laporan Bulanan</h2>
    <div class="text-white-50 mt-1">
        Periode:
        <?= e(date('F Y', strtotime($startDate))) ?> (
        <?= e($startDate) ?> s/d
        <?= e($endDate) ?>)
    </div>
</div>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
        <div class="ps-5">
            <form class="row g-3 align-items-end" method="GET">
                <div class="col-6 col-md-2">
                    <label class="form-label text-white-50 mb-1">Bulan</label>
                    <select name="month" class="form-select">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m === $month ? 'selected' : '' ?>><?= $m ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-6 col-md-2">
                    <label class="form-label text-white-50 mb-1">Tahun</label>
                    <select name="year" class="form-select">
                        <?php $yNow = (int) date('Y');
                        for ($y = $yNow - 3; $y <= $yNow + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y === $year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-12 col-md-4">
                    <label class="form-label text-white-50 mb-1">Ruangan</label>
                    <select name="ruangan_id" class="form-select">
                        <option value="0">Semua Ruangan</option>
                        <?php foreach ($ruanganList as $r): ?>
                            <option value="<?= (int) $r['id'] ?>" <?= ((int) $r['id'] === $ruanganId) ? 'selected' : '' ?>>
                                <?= e($r['gedung'] . ' - ' . $r['nama_ruangan']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label class="form-label text-white-50 mb-1">Status</label>
                    <select name="status_id" class="form-select">
                        <option value="0">Semua Status</option>
                        <?php foreach ($statusList as $s): ?>
                            <option value="<?= (int) $s['id'] ?>" <?= ((int) $s['id'] === $statusId) ? 'selected' : '' ?>>
                                <?= e($s['nama_status']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12 col-md-1 d-grid">
                    <button class="btn btn-success">Terapkan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="text-muted">Total Pengajuan</div>
                    <div class="fs-3 fw-bold"><?= (int) $totalAll ?></div>
                    <div class="text-muted">Approval rate: <?= e((string) $approvalRate) ?>%</div>
                </div>
            </div>
        </div>

        <?php
        $kpiMap = [
            2 => 'Disetujui',
            4 => 'Selesai',
            3 => 'Ditolak',
            1 => 'Menunggu',
            5 => 'Dibatalkan',
        ];
        foreach ($kpiMap as $sid => $label):
            $val = $statusCounts[$sid]['jumlah'] ?? 0;
            ?>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <div class="text-muted"><?= e($label) ?></div>
                        <div class="fs-3 fw-bold"><?= (int) $val ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Pemakaian</div>
                    <div>Total jam terpakai (status 2/4): <span class="fw-bold"><?= e($totalJam) ?></span></div>
                    <div>Rata-rata durasi: <span class="fw-bold"><?= e($avgDurasi) ?></span></div>
                    <hr>
                    <div class="fw-bold mb-2">Kecepatan Proses (log_status)</div>
                    <div>Menunggu → Disetujui: <span class="fw-bold"><?= e($avgToApprove) ?></span></div>
                    <div>Menunggu → Ditolak: <span class="fw-bold"><?= e($avgToReject) ?></span></div>
                    <div>Menunggu → Dibatalkan: <span class="fw-bold"><?= e($avgToCancel) ?></span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-body">
                    <div class="fw-bold mb-2">Top Ruangan (paling sering dipakai)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Ruangan</th>
                                    <th class="text-end">Jumlah Booking</th>
                                    <th class="text-end">Total Jam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$topRuangan): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">Tidak ada data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($topRuangan as $i => $tr): ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= e($tr['gedung'] . ' - ' . $tr['nama_ruangan']) ?></td>
                                            <td class="text-end"><?= (int) $tr['jumlah_booking'] ?></td>
                                            <td class="text-end"><?= e($tr['total_jam']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted mt-2">Hanya status Disetujui/Selesai dihitung pemakaian.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Rekap Harian</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Setuju/Selesai</th>
                                    <th class="text-end">Ditolak</th>
                                    <th class="text-end">Menunggu</th>
                                    <th class="text-end">Dibatalkan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$rekapHarian): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">Tidak ada data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rekapHarian as $rh): ?>
                                        <tr>
                                            <td><?= e($rh['tanggal']) ?></td>
                                            <td class="text-end"><?= (int) $rh['total_pengajuan'] ?></td>
                                            <td class="text-end"><?= (int) $rh['disetujui_atau_selesai'] ?></td>
                                            <td class="text-end"><?= (int) $rh['ditolak'] ?></td>
                                            <td class="text-end"><?= (int) $rh['menunggu'] ?></td>
                                            <td class="text-end"><?= (int) $rh['dibatalkan'] ?></td>
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
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Aktivitas Admin</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Admin</th>
                                    <th class="text-end">Approve</th>
                                    <th class="text-end">Reject</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$adminActivity): ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">Tidak ada data.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($adminActivity as $a): ?>
                                        <tr>
                                            <td><?= e($a['nama']) ?></td>
                                            <td class="text-end"><?= (int) $a['approve_count'] ?></td>
                                            <td class="text-end"><?= (int) $a['reject_count'] ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-muted mt-2">Sumber: log_status (status 2/3).</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <div class="fw-bold">Detail Transaksi (sesuai filter)</div>
                <a class="btn btn-outline-secondary btn-sm"
                    href="?year=<?= $year ?>&month=<?= $month ?>&ruangan_id=<?= $ruanganId ?>&status_id=<?= $statusId ?>&export=csv">
                    Export CSV
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Ruangan</th>
                            <th>Peminjam</th>
                            <th>Prodi</th>
                            <th>Kegiatan</th>
                            <th class="text-end">Peserta</th>
                            <th>Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$detail): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted">Tidak ada data.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($detail as $d): ?>
                                <tr>
                                    <td><?= (int) $d['id'] ?></td>
                                    <td><?= e($d['tanggal']) ?></td>
                                    <td><?= e(substr((string) $d['jam_mulai'], 0, 5) . ' - ' . substr((string) $d['jam_selesai'], 0, 5)) ?>
                                    </td>
                                    <td><?= e($d['gedung'] . ' - ' . $d['nama_ruangan']) ?></td>
                                    <td><?= e($d['nama_peminjam']) ?></td>
                                    <td><?= e($d['prodi'] ?? '-') ?></td>
                                    <td><?= e($d['nama_kegiatan']) ?></td>
                                    <td class="text-end"><?= e((string) ($d['jumlah_peserta'] ?? '-')) ?></td>
                                    <td><?= e($d['nama_status']) ?></td>
                                    <td><?= !empty($d['catatan_admin']) ? e($d['catatan_admin']) : '<span class="text-muted">-</span>' ?>
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