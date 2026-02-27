<?php
require_once __DIR__ . '/../auth/auth.php';
require_once __DIR__ . '/../auth/role.php';
require_once __DIR__ . '/../config/koneksi.php';

requireLogin();
requireRole('mahasiswa');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid");

$ruangan = query("SELECT * FROM ruangan WHERE id = ?", [$id])->fetch();
if (!$ruangan) die("Ruangan tidak ditemukan");

$activeNav = 'ruangan';
$pageTitle = 'Detail Ruangan - ' . ($ruangan['nama_ruangan'] ?? 'Peminjaman Ruangan');
require_once __DIR__ . '/../templates/header.php';

$cover = query(
  "SELECT nama_file FROM ruangan_foto WHERE ruangan_id = ? AND tipe='cover' ORDER BY id DESC LIMIT 1",
  [$id]
)->fetchColumn();

$fotos = query(
  "SELECT nama_file FROM ruangan_foto WHERE ruangan_id = ? AND tipe='detail' ORDER BY id DESC",
  [$id]
)->fetchAll();

$fasilitas = query(
  "SELECT f.nama_fasilitas
   FROM ruangan_fasilitas rf
   JOIN fasilitas f ON f.id = rf.fasilitas_id
   WHERE rf.ruangan_id = ?
   ORDER BY f.nama_fasilitas",
  [$id]
)->fetchAll();

$images = [];
if (!empty($cover)) {
  $images[] = $BASE . "/uploads/ruangan/" . e($cover);
} elseif (!empty($ruangan['foto'])) {
  $images[] = $BASE . "/uploads/ruangan/" . e($ruangan['foto']);
}
foreach ($fotos as $f) $images[] = $BASE . "/uploads/ruangan/" . e($f['nama_file']);

function iconFasilitas(string $nama): string {
  $n = strtolower($nama);
  $map = [
    'proyektor' => 'bi-easel',
    'wifi' => 'bi-wifi',
    'sound' => 'bi-speaker',
    'speaker' => 'bi-speaker',
    'papan' => 'bi-journal-text',
    'mikrofon' => 'bi-mic',
    'mic' => 'bi-mic',
    'kursi' => 'bi-person',
    'meja' => 'bi-table',
    'stopkontak' => 'bi-plug',
    'listrik' => 'bi-plug',
    'tv' => 'bi-tv',
    'monitor' => 'bi-tv',
  ];
  foreach ($map as $k => $ico) if (str_contains($n, $k)) return $ico;
  if ($n === 'ac' || str_contains($n, ' ac') || str_contains($n, 'ac ')) return 'bi-snow';
  return 'bi-check-circle';
}
?>

<section class="hero-page">
    <?php if (!empty($images[0])): ?>
      <img class="hero-bg" src="<?= $images[0] ?>" alt="<?= e($ruangan['nama_ruangan']) ?>">
    <?php else: ?>
      <div class="hero-bg hero-bg-fallback"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-page-content">
      <h1><?= e($ruangan['nama_ruangan']) ?></h1>
      <div class="breadcrumb">
        <a href="<?= $BASE ?>/mahasiswa/dashboard.php">Home</a><span class="sep">/</span>
        <a href="<?= $BASE ?>/mahasiswa/ruangan.php">Ruangan</a><span class="sep">/</span>
        <span class="current"><?= e($ruangan['nama_ruangan']) ?></span>
      </div>
    </div>
</section>

<div class="wrap detail-ruangan-page">

  <div class="glass-card">
    <div class="glass-head">
      <p class="title mb-1">Detail Ruangan</p>
      <p class="sub mb-0">Slide foto + fasilitas.</p>
    </div>

    <div class="p-4">
      <div class="row g-4 align-items-start">
        <div class="col-lg-5">
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="info-pill">🏢 <?= e($ruangan['gedung'] ?? '-') ?></span>
            <span class="info-pill">🏬 Lantai <?= e($ruangan['Lantai'] ?? ($ruangan['lantai'] ?? '-')) ?></span>
            <span class="info-pill">👥 <?= e((string)($ruangan['kapasitas'] ?? 0)) ?> orang</span>
          </div>

          <div class="mb-3">
            <div class="text-muted fw-semibold mb-2">Deskripsi</div>
            <div class="p-3 rounded-4 desc-box">
              <?= e($ruangan['deskripsi'] ?? 'Tidak ada deskripsi') ?>
            </div>
          </div>

          <div class="mb-3">
            <div class="text-muted fw-semibold mb-2">Fasilitas</div>
            <?php if (empty($fasilitas)): ?>
              <div class="alert alert-secondary mb-0">Belum ada fasilitas.</div>
            <?php else: ?>
              <div class="d-flex flex-wrap gap-2">
                <?php foreach ($fasilitas as $fa): ?>
                  <span class="info-pill">
                    <i class="bi <?= iconFasilitas($fa['nama_fasilitas']) ?>"></i>
                    <?= e($fa['nama_fasilitas']) ?>
                  </span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <div class="d-grid gap-2">
            <a class="btn btn-green w-100" href="<?= $BASE ?>/mahasiswa/peminjaman.php?ruangan_id=<?= (int)$ruangan['id'] ?>">Booking Sekarang</a>
            <a class="btn btn-outline-secondary w-100" href="<?= $BASE ?>/mahasiswa/ruangan.php">Kembali</a>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="text-muted fw-semibold mb-2">Foto Ruangan</div>

          <?php if (empty($images)): ?>
            <div class="alert alert-secondary">Tidak ada foto.</div>
          <?php else: ?>
            <div class="slide-wrap">
              <div id="roomCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                  <?php foreach ($images as $i => $src): ?>
                    <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                      <img src="<?= $src ?>" alt="Foto <?= $i+1 ?>">
                      <button type="button" class="zoom-overlay" data-zoom-src="<?= $src ?>">
                        <i class="bi bi-zoom-in"></i> Perbesar
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#roomCarousel" data-bs-slide="prev">
                  <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#roomCarousel" data-bs-slide="next">
                  <span class="carousel-control-next-icon"></span>
                </button>
              </div>

              <div class="thumbs" id="thumbs">
                <?php foreach ($images as $i => $src): ?>
                  <div class="thumb <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                    <img src="<?= $src ?>" alt="Thumb <?= $i+1 ?>" data-preview-src="<?= $src ?>">
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade img-modal" id="imagePreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-white">Foto Ruangan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0 text-center">
        <img id="imagePreviewSrc" src="" alt="Preview" class="img-fluid image-preview-src">
      </div>
    </div>
  </div>
</div>

<script>
(() => {
  const el = document.getElementById('roomCarousel');
  const thumbs = document.querySelectorAll('#thumbs .thumb');
  if (!el || !thumbs.length) return;

  const c = bootstrap.Carousel.getOrCreateInstance(el);

  // Thumbnail: klik pindah slide
  thumbs.forEach(t => {
    t.addEventListener('click', (e) => {
      // Kalau klik tombol zoom di thumb, jangan pindah slide
      if (e.target.closest('.zoom-btn')) return;
      c.to(+t.dataset.index);
    });
  });

  el.addEventListener('slid.bs.carousel', e => {
    thumbs.forEach(x => x.classList.remove('active'));
    document.querySelector(`#thumbs .thumb[data-index="${e.to}"]`)?.classList.add('active');
  });

  // Modal preview
  const modalEl = document.getElementById('imagePreviewModal');
  const modal = new bootstrap.Modal(modalEl);
  const preview = document.getElementById('imagePreviewSrc');

  // Klik tombol zoom di carousel => buka modal
  document.querySelectorAll('.zoom-overlay').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      preview.src = btn.getAttribute('data-zoom-src');
      modal.show();
    });
  });

  // Double-klik thumbnail => buka modal
  thumbs.forEach(t => {
    t.addEventListener('dblclick', () => {
      const img = t.querySelector('img');
      if (img) {
        preview.src = img.src;
        modal.show();
      }
    });
  });
})();
</script>

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
