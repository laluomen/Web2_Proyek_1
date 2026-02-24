<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'dashboard';
$pageTitle = "Dashboard";

$ruangan = query(
    "SELECT SUM(terpakai) AS ruangan_terpakai, SUM(1 - terpakai) AS ruangan_tersedia 
    FROM (
        SELECT r.id, MAX(CASE
            WHEN p.status_id = 2 
                AND p.tanggal = CURDATE() 
                AND CURTIME() BETWEEN p.jam_mulai AND p.jam_selesai 
            THEN 1
            ELSE 0
            END
        ) AS terpakai
        FROM ruangan AS r LEFT JOIN peminjaman as p on r.id=p.ruangan_id
        GROUP BY r.id
    ) AS status_ruangan"
)->fetchAll()[0];

$users = query(
    "SELECT 
        COUNT(*) AS total_pengguna, 
        COUNT(IF(role = 'admin', 1, NULL)) AS total_admin, 
        COUNT(IF(role = 'mahasiswa', 1, NULL)) AS total_mahasiswa 
    FROM users"
)->fetchAll()[0];

$peminjaman = query(
    "SELECT 
        COUNT(IF(status_id = 1, 1, NULL)) AS pending_hari_ini, 
        COUNT(IF(status_id = 2, 1, NULL)) AS disetujui_hari_ini, 
        COUNT(IF(status_id = 3, 1, NULL)) AS ditolak_hari_ini
    FROM peminjaman
    WHERE tanggal = CURDATE()"
)->fetchAll()[0];

function renderProgressBar($max, $segments) {
    $html = '<div class="progress mb-3" style="height: 25px;">';
    $accValue = 0;

    foreach ($segments as $segment) {
        $val = max(0, (float)$segment['value']);

        if (($accValue + $val) > $max) {
            $val = $max - $accValue;
        }

        if ($val > 0) {
            $percent = round(($val / $max) * 100, 2);

            $bgClass = $segment['bg_class'] ?? 'bg-primary';
            $textClass = $segment['text_class'] ?? '';

            $html .= sprintf(
                '<div class="progress-bar %s %s" role="progressbar" style="width: %s%%;" aria-valuenow="%s" aria-valuemin="0" aria-valuemax="%s"></div>',
                htmlspecialchars($bgClass),
                htmlspecialchars($textClass),
                $percent,
                $val,
                $max
            );
        }

        $accValue += $val;

        if ($accValue >= $max) break;
    }

    $html .= "</div>";
    return $html;
}

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">

<div class="admin-container" style="max-width: 100%">
    <div class="kelola-header mb-4">
        <h1>Dashboard</h1>
    </div>
</div>

<?php
$ruanganTerpakai = $ruangan['ruangan_terpakai'];
$ruanganTersedia = $ruangan['ruangan_tersedia'];

$peminjamanDitolak = $peminjaman['ditolak_hari_ini'];
$peminjamanPending = $peminjaman['pending_hari_ini'];
$peminjamanDisetujui = $peminjaman['disetujui_hari_ini'];

$totalPengguna = $users['total_pengguna'];
$totalAdmin = $users['total_admin'];
$totalMahasiswa = $users['total_mahasiswa'];
?>

<div class="container-fluid p-2">
    <div class="row">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        Ruangan 
                        <a href="./ruangan.php">
                            <i class="bi bi-arrow-right-circle"></i>
                        </a>
                    </h5>
                    <div class="row mb-3">
                        <div class="col-6 text-center text-danger">
                            <h1 class="mb-0"><?= $ruanganTerpakai ?></h1>
                            <span class="text-danger-subtle">Terpakai</span>
                        </div>
                        <div class="col-6 text-center text-success">
                            <h1 class="mb-0"><?= $ruanganTersedia ?></h1>
                            <span class="text-success-subtle">Tersedia</span>
                        </div>
                    </div>
                    <?php
                    $ruanganMax = $ruanganTerpakai + $ruanganTersedia;
                    $ruanganSegments = [
                        ['value' => $ruanganTerpakai, 'label' => 'Ruangan Terpakai', 'bg_class' => 'bg-danger'],
                        ['value' => $ruanganTersedia, 'label' => 'Ruangan Tersedia', 'bg_class' => 'bg-success']
                    ];

                    echo renderProgressBar($ruanganMax, $ruanganSegments);
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        Peminjaman
                        <a href="./persetujuan.php">
                            <i class="bi bi-arrow-right-circle"></i>
                        </a>
                    </h5>
                    <div class="row mb-3">
                        <div class="col-4 text-center text-danger">
                            <h1 class="mb-0"><?= $peminjamanDitolak ?></h1>
                            <span class="text-danger-subtle">Ditolak</span>
                        </div>
                        <div class="col-4 text-center text-warning">
                            <h1 class="mb-0"><?= $peminjamanPending ?></h1>
                            <span class="text-warning-subtle">Pending</span>
                        </div>
                        <div class="col-4 text-center text-success">
                            <h1 class="mb-0"><?= $peminjamanDisetujui ?></h1>
                            <span class="text-success-subtle">Disetujui</span>
                        </div>
                    </div>
                    <?php
                    $peminjamanMax = $peminjamanPending + $peminjamanDitolak + $peminjamanDisetujui;
                    $peminjamanSegments = [
                        ['value' => $peminjamanDitolak, 'label' => 'Peminjaman Ditolak', 'bg_class' => 'bg-danger'],
                        ['value' => $peminjamanPending, 'label' => 'Peminjaman Pending', 'bg_class' => 'bg-warning'],
                        ['value' => $peminjamanDisetujui, 'label' => 'Peminjaman Disetujui', 'bg_class' => 'bg-success']
                    ];

                    echo renderProgressBar($peminjamanMax, $peminjamanSegments);
                    ?>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h5 class="card-title">
                        Pengguna
                        <a href="./kelola_user.php">
                            <i class="bi bi-arrow-right-circle"></i>
                        </a>
                    </h5>
                    <h1 class="text-dark"><?= $totalPengguna ?></h1>
                    <?php
                    $usersSegments = [
                        ['value' => $totalAdmin, 'label' => 'Admin', 'bg_class' => 'bg-primary'],
                        ['value' => $totalMahasiswa, 'label' => 'Mahasiswa', 'bg_class' => 'bg-info']
                    ];

                    echo renderProgressBar($totalPengguna, $usersSegments);
                    ?>
                    <p>
                        <span class="text-primary"><?= $totalAdmin ?> admin, </span>
                        <span class="text-info"><?= $totalMahasiswa ?> mahasiswa</span>
                    </p>

                </div>
            </div>
        </div>
    </div>
</div>