<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../templates/header.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) die("ID tidak valid");

$ruangan = query("SELECT * FROM ruangan WHERE id = ?", [$id])->fetch();
if (!$ruangan) die("Ruangan tidak ditemukan");

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

<style>
  :root{--bg1:#0b1220;--bg2:#101a2e;--text:#0f172a;--muted:#6b7280;--accent:#22c55e;--accent2:#16a34a}
  body{margin:0;color:var(--text);
    background:radial-gradient(1200px 600px at 20% 10%, #1f2a44 0%, transparent 60%),
              radial-gradient(900px 500px at 90% 30%, #1a3b2a 0%, transparent 55%),
              linear-gradient(180deg,var(--bg1),var(--bg2))}
  .wrap{max-width:1100px;margin:0 auto;padding:0 22px 90px}
  .hero-page{position:relative;height:260px;overflow:hidden;display:flex;align-items:center;justify-content:center;text-align:center;
    margin:24px 0 18px;border-radius:18px;box-shadow:0 22px 60px rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.12)}
  .hero-bg{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;filter:brightness(.45)}
  .hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,20,40,.4),rgba(10,20,40,.75))}
  .hero-page-content{position:relative;color:#fff;padding:0 18px}
  .hero-page h1{font-size:44px;font-weight:900;margin:0;line-height:1.1}
  @media (max-width:768px){.hero-page{height:220px}.hero-page h1{font-size:30px}}

  .breadcrumbx{margin-top:10px;font-size:15px;opacity:.95;display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
  .breadcrumbx a{color:#fff;text-decoration:none;font-weight:650;position:relative}
  .breadcrumbx a::after{content:"";position:absolute;left:0;bottom:-2px;width:0;height:2px;background:var(--accent);transition:.25s}
  .breadcrumbx a:hover::after{width:100%}
  .breadcrumbx .sep{opacity:.6}.breadcrumbx .current{opacity:.85}

  .glass-card{border-radius:22px;background:rgba(255,255,255,.94);border:1px solid rgba(255,255,255,.6);
    box-shadow:0 22px 60px rgba(0,0,0,.28);overflow:hidden}
  .glass-head{padding:18px 20px;border-bottom:1px solid rgba(15,23,42,.08);background:linear-gradient(180deg,#fff,#f8fafc)}
  .glass-head .title{margin:0;font-size:22px;font-weight:900;color:#14532d}
  .glass-head .sub{margin:6px 0 0;color:var(--muted);font-size:13px}

  .info-pill{display:inline-flex;align-items:center;gap:8px;padding:8px 12px;border-radius:999px;
    background:rgba(34,197,94,.10);border:1px solid rgba(34,197,94,.22);font-weight:750;color:#14532d;font-size:13px}
  .info-pill i{font-size:16px;line-height:1}

  .btn-green{border:none;border-radius:14px;padding:11px 14px;font-weight:900;
    background:linear-gradient(180deg,var(--accent),var(--accent2));color:#fff;box-shadow:0 10px 25px rgba(34,197,94,.35)}

  .slide-wrap{border-radius:16px;overflow:hidden;border:1px solid rgba(15,23,42,.12);box-shadow:0 10px 24px rgba(0,0,0,.12);background:#fff}
  .carousel-item{position:relative}
  .carousel-item img{width:100%;height:320px;object-fit:cover;display:block}
  @media (max-width:768px){.carousel-item img{height:240px}}

  .zoom-overlay{position:absolute;bottom:12px;right:12px;z-index:5;background:rgba(0,0,0,.55);color:#fff;
    border:none;border-radius:10px;padding:8px 14px;font-size:14px;font-weight:700;cursor:pointer;
    backdrop-filter:blur(6px);display:flex;align-items:center;gap:6px;transition:.2s}
  .zoom-overlay:hover{background:rgba(34,197,94,.8);transform:scale(1.05)}

  .thumbs{display:flex;gap:10px;padding:12px;overflow:auto;background:#f8fafc;border-top:1px solid rgba(15,23,42,.08)}
  .thumb{width:92px;height:60px;border-radius:12px;overflow:hidden;border:2px solid transparent;flex:0 0 auto;cursor:pointer;background:#e5e7eb}
  .thumb img{width:100%;height:100%;object-fit:cover;display:block}
  .thumb.active{border-color:var(--accent)}

  .img-modal .modal-content{background:#0b1220;border:1px solid rgba(255,255,255,.12)}
  .img-modal .modal-header{border-bottom:1px solid rgba(255,255,255,.12)}
</style>

<div class="wrap">
  <div class="hero-page">
    <?php if (!empty($images[0])): ?>
      <img class="hero-bg" src="<?= $images[0] ?>" alt="<?= e($ruangan['nama_ruangan']) ?>">
    <?php else: ?>
      <div class="hero-bg" style="background:#111827;"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-page-content">
      <h1><?= e($ruangan['nama_ruangan']) ?></h1>
      <div class="breadcrumbx">
        <a href="<?= $BASE ?>/index.php">Home</a><span class="sep">/</span>
        <a href="<?= $BASE ?>/ruangan.php">Ruangan</a><span class="sep">/</span>
        <span class="current"><?= e($ruangan['nama_ruangan']) ?></span>
      </div>
    </div>
  </div>

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
            <span class="info-pill">👥 <?= e((string)($ruangan['kapasitas'] ?? 0)) ?> orang</span>
          </div>

          <div class="mb-3">
            <div class="text-muted fw-semibold mb-2">Deskripsi</div>
            <div class="p-3 rounded-4" style="background:#f8fafc;border:1px solid rgba(15,23,42,.10);">
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

          <a class="btn btn-green w-100" href="<?= $BASE ?>ruangan.php">Kembali</a>
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
        <img id="imagePreviewSrc" src="" alt="Preview" class="img-fluid" style="max-height:80vh;">
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