<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/koneksi.php";

$pageTitle = "Home";
$activeNav = "home";
require_once __DIR__ . "/../templates/header.php";

// Sesuaikan dengan URL project kamu di browser
$Root = "/Web2_Proyek_1/src";
$BASE = $Root; // kalau di root, ganti jadi "" atau "/Web2_Proyek_1/src" sesuai kebutuhan

/* HERO IMAGE */
$heroImages = query("SELECT foto FROM ruangan WHERE foto IS NOT NULL AND foto != '' ORDER BY id")->fetchAll();

/* FILTER */
$tgl_awal = $_GET['tgl_awal'] ?? '';
$tgl_akhir = $_GET['tgl_akhir'] ?? '';
$gedung = $_GET['gedung'] ?? '';

$params = [];
$where = [];

if ($gedung) {
   $where[] = "ruangan.gedung = ?";
   $params[] = $gedung;
}

if ($tgl_awal && $tgl_akhir) {
   $where[] = "NOT EXISTS (
        SELECT 1 FROM peminjaman
        WHERE peminjaman.ruangan_id = ruangan.id
        AND tanggal BETWEEN ? AND ?
    )";
   $params[] = $tgl_awal;
   $params[] = $tgl_akhir;
}

$sql = "SELECT * FROM ruangan";
if ($where)
   $sql .= " WHERE " . implode(" AND ", $where);
$sql .= " ORDER BY nama_ruangan";

$ruangan = query($sql, $params)->fetchAll();

$gedungList = query("SELECT DISTINCT gedung FROM ruangan ORDER BY gedung")->fetchAll();
?>

<!-- HERO -->
<section class="hero-full">

   <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">

      <div class="carousel-inner">

         <?php foreach ($heroImages as $i => $img): ?>
            <div class="carousel-item <?= $i == 0 ? 'active' : '' ?>">
               <img src="<?= $BASE ?>/uploads/ruangan/<?= e($img['foto']) ?>" class="d-block w-100">
            </div>
         <?php endforeach; ?>

      </div>

   </div>

   <div class="hero-content">
      <h5>WELCOME TO RBS</h5>
      <h1>Room Booking System</h1>
      <h2>Fasilkom Unsri</h2>
   </div>

</section>

<!-- FILTER -->
<section class="filter-floating">
   <div class="container">
      <form class="filter-box" method="get">
         <div class="row g-3 align-items-end">

            <div class="col-md-3">
               <label>Tanggal Awal</label>
               <input type="date" name="tgl_awal" value="<?= e($tgl_awal) ?>" class="form-control">
            </div>

            <div class="col-md-3">
               <label>Tanggal Akhir</label>
               <input type="date" name="tgl_akhir" value="<?= e($tgl_akhir) ?>" class="form-control">
            </div>

            <div class="col-md-3">
               <label>Gedung</label>
               <div class="select-wrap">
                  <select name="gedung" class="form-control">
                     <option value="">-- Semua Gedung --</option>
                     <?php foreach ($gedungList as $g): ?>
                        <option value="<?= e($g['gedung']) ?>" <?= $gedung == $g['gedung'] ? 'selected' : '' ?>>
                           <?= e($g['gedung']) ?>
                        </option>
                     <?php endforeach; ?>
                  </select>
               </div>
            </div>

            <div class="col-md-3">
               <button class="btn w-100">Check</button>
            </div>

         </div>
      </form>
   </div>
</section>

<div class="wrap">
   <section class="room-section">
      <div class="container">

         <?php if (!$ruangan): ?>
            <div class="text-center text-muted fs-5 mt-5">
               Saat ini ruangan di gedung yang dipilih tidak tersedia.<br>
               Silakan cek gedung lain.
            </div>
         <?php else: ?>

            <div class="room-grid">
               <?php foreach ($ruangan as $r): ?>

                  <div class="room-item">

                     <div class="room-card">

                        <div class="room-img">
                           <img src="<?= $BASE ?>/uploads/ruangan/<?= e($r['foto']) ?>" alt="<?= e($r['nama_ruangan']) ?>">
                        </div>

                        <div class="room-body">
                           <div class="room-title"><?= e($r['nama_ruangan']) ?></div>

                           <div class="room-meta">
                              Gedung <?= e($r['gedung']) ?><br>
                              Kapasitas <?= e($r['kapasitas']) ?> orang
                           </div>

                           <a href="<?= $BASE ?>/mahasiswa/ruangan.php?id=<?= $r['id'] ?>" class="room-btn">
                              View Details
                           </a>
                        </div>

                     </div>

                  </div>

               <?php endforeach; ?>
            </div>

         <?php endif; ?>

      </div>
   </section>
</div>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>