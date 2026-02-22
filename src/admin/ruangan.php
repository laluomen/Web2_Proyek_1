<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'ruangan';
$pageTitle = "Kelola Ruangan";

// Ambil data ruangan + foto sampul + jumlah foto detail
$stmt = query(
    "SELECT r.*,
        (
            SELECT rf.nama_file
            FROM ruangan_foto rf
            WHERE rf.ruangan_id = r.id AND rf.tipe = 'cover'
            ORDER BY rf.id DESC
            LIMIT 1
        ) AS cover_foto,
        (
            SELECT COUNT(*)
            FROM ruangan_foto rf
            WHERE rf.ruangan_id = r.id AND rf.tipe = 'detail'
        ) AS detail_count
     FROM ruangan r
     ORDER BY r.nama_ruangan ASC"
);
$ruangans = $stmt->fetchAll();

// Map foto per ruangan untuk edit
$fotoRows = query("SELECT id, ruangan_id, nama_file, tipe FROM ruangan_foto ORDER BY id DESC")->fetchAll();
$ruanganPhotos = [];
foreach ($fotoRows as $row) {
    $rid = (int)$row['ruangan_id'];
    if (!isset($ruanganPhotos[$rid])) {
        $ruanganPhotos[$rid] = ['cover' => [], 'detail' => []];
    }
    $ruanganPhotos[$rid][$row['tipe']][] = [
        'id' => (int)$row['id'],
        'nama_file' => $row['nama_file']
    ];
}

// Master fasilitas untuk form tambah/edit
$fasilitasList = query("SELECT id, nama_fasilitas FROM fasilitas ORDER BY id ASC, nama_fasilitas ASC")->fetchAll();
$fasilitasNameMap = [];
foreach ($fasilitasList as $f) {
    $fasilitasNameMap[(int)$f['id']] = $f['nama_fasilitas'];
}

// Map fasilitas per ruangan untuk prefill form edit
$rfRows = query("SELECT ruangan_id, fasilitas_id FROM ruangan_fasilitas ORDER BY ruangan_id, fasilitas_id")->fetchAll();
$ruanganFasilitasMap = [];
foreach ($rfRows as $row) {
    $rid = (int)$row['ruangan_id'];
    if (!isset($ruanganFasilitasMap[$rid])) {
        $ruanganFasilitasMap[$rid] = [];
    }
    $ruanganFasilitasMap[$rid][] = (int)$row['fasilitas_id'];
}

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1>Kelola Ruangan</h1>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalAddRuangan">
            Tambah Ruangan
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
            switch ($_GET['success']) {
                case 'add':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil ditambahkan.";
                    break;
                case 'edit':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil diperbarui.";
                    break;
                case 'delete':
                    echo "<strong>Berhasil!</strong> Ruangan berhasil dihapus.";
                    break;
            }
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Error!</strong> <?php echo htmlspecialchars($_GET['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Card Tabel Ruangan -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom"
            style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-list-ul me-2" style="color: #22c55e;"></i>Daftar Ruangan
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
                <table class="table table-hover align-middle mb-0" id="tableRuangan">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th style="width: 20%; padding: 15px;">
                                <i class="bi bi-door-closed me-1"></i>Nama Ruangan
                            </th>
                            <th style="width: 12%; padding: 15px;">
                                <i class="bi bi-building me-1"></i>Gedung
                            </th>
                            <th class="text-center" style="width: 10%; padding: 15px;">
                                <i class="bi bi-layers me-1"></i>Lantai
                            </th>
                            <th class="text-center" style="width: 12%; padding: 15px;">
                                <i class="bi bi-people me-1"></i>Kapasitas
                            </th>
                            <th class="text-center" style="width: 10%; padding: 15px;">
                                <i class="bi bi-image me-1"></i>Foto
                            </th>
                            <th class="text-center" style="width: 280px; padding: 15px;">
                                <i class="bi bi-gear me-1"></i>Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ruangans)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada data ruangan</p>
                                        <small>Tambahkan ruangan pertama Anda</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ruangans as $i => $ruangan): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge-number">
                                            <?= $i + 1 ?>
                                        </span>

                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark" style="font-size: 1rem;">
                                            <?= htmlspecialchars($ruangan['nama_ruangan']) ?>
                                        </div>
                                        <?php if (!empty($ruangan['deskripsi'])): ?>
                                            <small class="text-muted" style="font-size: 0.85rem;">
                                                <i
                                                    class="bi bi-info-circle me-1"></i><?= htmlspecialchars(substr($ruangan['deskripsi'], 0, 50)) ?><?= strlen($ruangan['deskripsi']) > 50 ? '...' : '' ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #17a2b8, #138496); color: white; font-weight: 600; border-radius: 8px;">
                                            <i
                                                class="bi bi-building-fill me-1"></i><?= htmlspecialchars($ruangan['gedung'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #8b5cf6, #7c3aed); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-layers-fill me-1"></i><?= htmlspecialchars($ruangan['Lantai'] ?? '-') ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-3 py-2"
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-people-fill me-1"></i><?= $ruangan['kapasitas'] ?? '0' ?> orang
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php $coverFoto = $ruangan['cover_foto'] ?: ($ruangan['foto'] ?? ''); ?>
                                        <?php if ($coverFoto): ?>
                                            <img src="../uploads/ruangan/<?= htmlspecialchars($coverFoto) ?>"
                                                alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>"
                                                class="rounded shadow-sm img-thumbnail"
                                                style="width: 80px; height: 55px; object-fit: cover; cursor: pointer;"
                                                data-bs-toggle="modal" data-bs-target="#modalViewImage"
                                                onclick="viewImage('../uploads/ruangan/<?= htmlspecialchars($coverFoto) ?>', '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>')">
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <i class="bi bi-image"></i> No Image
                                            </span>
                                        <?php endif; ?>
                                        <div class="small text-muted mt-1">
                                            Detail: <?= (int)($ruangan['detail_count'] ?? 0) ?> foto
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-info aksi-btn" style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="viewDetail(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', '<?= htmlspecialchars($ruangan['Lantai'] ?? '', ENT_QUOTES) ?>', <?= (int)($ruangan['kapasitas'] ?? 0) ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>', '<?= htmlspecialchars($coverFoto ?? '') ?>')">
                                                <i class="bi bi-eye-fill me-1"></i>Detail
                                            </button>
                                            <button class="btn btn-warning aksi-btn" style="min-width: 60px; font-size: 0.8rem;"
                                                data-bs-toggle="modal" data-bs-target="#modalEditRuangan"
                                                onclick="editRuangan(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', '<?= htmlspecialchars($ruangan['Lantai'] ?? '', ENT_QUOTES) ?>', <?= (int)($ruangan['kapasitas'] ?? 0) ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>')">
                                                <i class="bi bi-pencil-fill me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-danger aksi-btn" style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="deleteRuangan(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>')">
                                                <i class="bi bi-trash-fill me-1"></i>Hapus
                                            </button>
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
                        style="background: linear-gradient(135deg, #22c55e, #16a34a);"><?= count($ruangans) ?></span>
                    ruangan terdaftar
                </small>
                <small class="text-muted">
                    <i class="bi bi-calendar-check me-1"></i><?= date('d F Y') ?>
                </small>
            </div>
        </div>
    </div>
</div>

</main><!-- end admin-main -->
</div><!-- end wrap -->

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="modalAddRuangan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-plus-circle-fill me-2"></i>Tambah Ruangan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="add">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #22c55e;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan" required
                                placeholder="Contoh: Ruang 301">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #22c55e;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung" placeholder="Contoh: Gedung A">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-layers me-1" style="color: #22c55e;"></i>Lantai
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="lantai" required placeholder="Contoh: 2">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-people me-1" style="color: #22c55e;"></i>Kapasitas
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="kapasitas" placeholder="Jumlah orang"
                                min="1">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i> orang
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-card-text me-1" style="color: #22c55e;"></i>Deskripsi
                        </label>
                        <textarea class="form-control" name="deskripsi" rows="3"
                            placeholder="Keterangan ruangan (opsional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-check2-square me-1" style="color: #22c55e;"></i>Fasilitas
                        </label>
                        <?php if (empty($fasilitasList)): ?>
                            <div class="text-muted small">Data fasilitas belum tersedia.</div>
                        <?php else: ?>
                            <div class="row g-2 fasilitas-grid">
                                <?php foreach ($fasilitasList as $f): ?>
                                    <div class="col-md-6">
                                        <div class="fasilitas-item">
                                            <input class="form-check-input" type="checkbox" name="fasilitas_ids[]"
                                                value="<?= (int)$f['id'] ?>" id="addFasilitas<?= (int)$f['id'] ?>">
                                            <label class="form-check-label" for="addFasilitas<?= (int)$f['id'] ?>">
                                                <?= htmlspecialchars($f['nama_fasilitas']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1" style="color: #22c55e;"></i>Foto Sampul
                        </label>
                        <input type="file" class="form-control" name="foto_cover" accept="image/*" id="addCoverInput"
                            onchange="previewAddCover(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Format: JPG, PNG, GIF (Max 2MB)
                        </small>
                        <div class="mt-3" id="addCoverPreviewContainer" style="display: none;">
                            <img id="addCoverPreview" src="" alt="Preview" class="img-thumbnail rounded"
                                style="max-height: 200px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-images me-1" style="color: #22c55e;"></i>Foto Detail (Bisa lebih dari satu)
                        </label>
                        <input type="file" class="form-control" name="foto_detail[]" accept="image/*" multiple>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Pilih beberapa foto untuk detail
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal"
                        style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white"
                        style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-save me-1"></i>Simpan Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ruangan -->
<div class="modal fade" id="modalEditRuangan" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editRuanganId">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #f59e0b;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan" id="editNamaRuangan" required>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #f59e0b;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung" id="editGedung">
                        </div>

                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-layers me-1" style="color: #f59e0b;"></i>Lantai
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="lantai" id="editLantai" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-people me-1" style="color: #f59e0b;"></i>Kapasitas
                        </label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="kapasitas" id="editKapasitas" min="1">
                            <span class="input-group-text">
                                <i class="bi bi-person-fill"></i> orang
                            </span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-card-text me-1" style="color: #f59e0b;"></i>Deskripsi
                        </label>
                        <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-check2-square me-1" style="color: #f59e0b;"></i>Fasilitas
                        </label>
                        <?php if (empty($fasilitasList)): ?>
                            <div class="text-muted small">Data fasilitas belum tersedia.</div>
                        <?php else: ?>
                            <div class="row g-2 fasilitas-grid">
                                <?php foreach ($fasilitasList as $f): ?>
                                    <div class="col-md-6">
                                        <div class="fasilitas-item">
                                            <input class="form-check-input edit-fasilitas-checkbox" type="checkbox"
                                                name="fasilitas_ids[]" value="<?= (int)$f['id'] ?>"
                                                id="editFasilitas<?= (int)$f['id'] ?>">
                                            <label class="form-check-label" for="editFasilitas<?= (int)$f['id'] ?>">
                                                <?= htmlspecialchars($f['nama_fasilitas']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1" style="color: #f59e0b;"></i>Foto Sampul Saat Ini
                        </label>
                        <div id="editExistingCover" class="d-flex flex-wrap gap-2"></div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Centang jika ingin menghapus foto
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-images me-1" style="color: #f59e0b;"></i>Foto Detail Saat Ini
                        </label>
                        <div id="editExistingDetail" class="d-flex flex-wrap gap-2"></div>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Centang foto detail untuk dihapus
                        </small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-image me-1" style="color: #f59e0b;"></i>Ganti Foto Sampul
                        </label>
                        <input type="file" class="form-control" name="foto_cover" accept="image/*" id="editCoverFile"
                            onchange="previewEditCover(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah foto sampul
                        </small>
                        <div class="mt-3" id="editCoverPreviewContainer" style="display: none;">
                            <img id="editCoverPreview" src="" alt="Foto Preview" class="img-thumbnail rounded"
                                style="max-height: 200px;">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-images me-1" style="color: #f59e0b;"></i>Tambah Foto Detail
                        </label>
                        <input type="file" class="form-control" name="foto_detail[]" accept="image/*" multiple>
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Bisa pilih lebih dari satu foto
                        </small>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal"
                        style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white"
                        style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-check-circle me-1"></i>Update Ruangan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Detail -->
<div class="modal fade" id="modalViewDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header text-white"
                style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%); border: none;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-eye-fill me-2"></i>Detail Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-door-closed me-1"></i>Nama Ruangan
                            </label>
                            <h5 class="fw-bold" id="detailNamaRuangan">-</h5>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-building me-1"></i>Gedung
                            </label>
                            <h6 id="detailGedung">-</h6>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-people me-1"></i>Kapasitas
                            </label>
                            <h6 id="detailKapasitas">-</h6>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-layers me-1"></i>Lantai
                            </label>
                            <h6 id="detailLantai">-</h6>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">
                                <i class="bi bi-check2-square me-1"></i>Fasilitas
                            </label>
                            <div id="detailFasilitasList" class="d-flex flex-wrap gap-2"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-2">
                            <i class="bi bi-images me-1"></i>Galeri Foto (Sampul + Detail)
                        </label>
                        <div id="detailGalleryContainer" class="detail-gallery-wrap">
                            <div class="p-3 text-muted small">Belum ada foto.</div>
                        </div>
                        <div class="mt-3">
                            <div class="small text-muted mt-2" id="detailFotoSummary">-</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-card-text me-1"></i>Deskripsi
                    </label>
                    <p class="border p-3 rounded bg-light" id="detailDeskripsi">-</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                    style="border-radius: 8px; padding: 10px 24px;">
                    <i class="bi bi-x-circle me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal View Image -->
<div class="modal fade" id="modalViewImage" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg bg-dark" style="border-radius: 15px; overflow: hidden;">
            <div class="modal-header bg-dark text-white border-0">
                <h5 class="modal-title fw-bold" id="viewImageTitle">
                    <i class="bi bi-images me-2"></i>Foto Ruangan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 bg-dark text-center">
                <img id="viewImageSrc" src="" alt="Foto Ruangan" class="img-fluid" style="max-height: 70vh;">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const ruanganPhotos = <?php echo json_encode(
                                $ruanganPhotos,
                                JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                            ); ?>;
    const ruanganFasilitasMap = <?php echo json_encode(
                                     $ruanganFasilitasMap,
                                     JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                                 ); ?>;
    const fasilitasNameMap = <?php echo json_encode(
                                  $fasilitasNameMap,
                                  JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
                              ); ?>;

    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableRuangan tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Preview image on add modal
    function previewAddCover(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('addCoverPreview').src = e.target.result;
                document.getElementById('addCoverPreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Preview image on edit modal
    function previewEditCover(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('editCoverPreview').src = e.target.result;
                document.getElementById('editCoverPreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    function renderExistingFotos(id) {
        const data = ruanganPhotos[id] || {
            cover: [],
            detail: []
        };
        const coverWrap = document.getElementById('editExistingCover');
        const detailWrap = document.getElementById('editExistingDetail');

        coverWrap.innerHTML = '';
        detailWrap.innerHTML = '';

        if (!data.cover.length) {
            coverWrap.innerHTML = '<div class="text-muted small">Belum ada foto sampul.</div>';
        } else {
            data.cover.forEach(item => {
                coverWrap.innerHTML += `
                    <label class="border rounded p-2 text-center" style="width:120px;">
                        <img src="../uploads/ruangan/${item.nama_file}" alt="Cover" class="img-fluid rounded" style="height:70px;object-fit:cover;width:100%;">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="delete_foto[]" value="${item.id}">
                            <span class="small">hapus</span>
                        </div>
                    </label>
                `;
            });
        }

        if (!data.detail.length) {
            detailWrap.innerHTML = '<div class="text-muted small">Belum ada foto detail.</div>';
        } else {
            data.detail.forEach(item => {
                detailWrap.innerHTML += `
                    <label class="border rounded p-2 text-center" style="width:120px;">
                        <img src="../uploads/ruangan/${item.nama_file}" alt="Detail" class="img-fluid rounded" style="height:70px;object-fit:cover;width:100%;">
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="delete_foto[]" value="${item.id}">
                            <span class="small">hapus</span>
                        </div>
                    </label>
                `;
            });
        }
    }

    function renderEditFasilitas(id) {
        const selected = new Set((ruanganFasilitasMap[id] || []).map(String));
        document.querySelectorAll('.edit-fasilitas-checkbox').forEach((cb) => {
            cb.checked = selected.has(cb.value);
        });
    }

    function renderDetailFasilitas(id) {
        const wrap = document.getElementById('detailFasilitasList');
        const ids = ruanganFasilitasMap[id] || [];
        wrap.innerHTML = '';

        if (!ids.length) {
            wrap.innerHTML = '<span class="text-muted small">Belum ada fasilitas.</span>';
            return;
        }

        ids.forEach((fid) => {
            const name = fasilitasNameMap[fid] || ('Fasilitas #' + fid);
            wrap.innerHTML += `
                <span class="badge rounded-pill text-bg-light border px-3 py-2">${name}</span>
            `;
        });
    }

    // Edit ruangan function
    function editRuangan(id, nama, gedung, lantai, kapasitas, deskripsi) {
        document.getElementById('editRuanganId').value = id;
        document.getElementById('editNamaRuangan').value = nama;
        document.getElementById('editGedung').value = gedung;
        document.getElementById('editLantai').value = lantai;
        document.getElementById('editKapasitas').value = kapasitas;
        document.getElementById('editDeskripsi').value = deskripsi;

        // Hide preview when opening modal
        document.getElementById('editCoverPreviewContainer').style.display = 'none';
        document.getElementById('editCoverFile').value = '';
        renderExistingFotos(id);
        renderEditFasilitas(id);
    }

    // View detail function
    function viewDetail(id, nama, gedung, lantai, kapasitas, deskripsi, foto) {
        document.getElementById('detailNamaRuangan').textContent = nama;
        document.getElementById('detailGedung').textContent = gedung || '-';
        document.getElementById('detailKapasitas').textContent = kapasitas ? kapasitas + ' orang' : '-';
        document.getElementById('detailLantai').textContent = lantai || '-';
        document.getElementById('detailDeskripsi').textContent = deskripsi || 'Tidak ada deskripsi';
        const galleryWrap = document.getElementById('detailGalleryContainer');
        const fotoSummary = document.getElementById('detailFotoSummary');
        const data = ruanganPhotos[id] || {
            cover: [],
            detail: []
        };

        renderDetailFasilitas(id);

        const images = [];
        const coverFromMap = (data.cover && data.cover.length > 0) ? data.cover[0].nama_file : '';
        const coverFile = foto || coverFromMap;
        if (coverFile) {
            images.push({
                src: '../uploads/ruangan/' + coverFile,
                label: 'Sampul'
            });
        }
        data.detail.forEach((item) => {
            images.push({
                src: '../uploads/ruangan/' + item.nama_file,
                label: 'Detail'
            });
        });

        if (!images.length) {
            galleryWrap.innerHTML = '<div class="p-3 text-muted small"><i class="bi bi-image me-1"></i>Tidak ada foto ruangan.</div>';
            fotoSummary.textContent = 'Total foto: 0';
        } else {
            const slideItems = images.map((img, idx) => `
                <div class="carousel-item ${idx === 0 ? 'active' : ''}" data-label="${img.label}">
                    <img src="${img.src}" alt="${img.label} ${idx + 1}" class="detail-gallery-img">
                </div>
            `).join('');

            const thumbItems = images.map((img, idx) => `
                <button type="button" class="detail-thumb-item ${idx === 0 ? 'active' : ''}" data-index="${idx}" aria-label="Foto ${idx + 1}">
                    <img src="${img.src}" alt="Thumb ${idx + 1}">
                </button>
            `).join('');

            const controls = images.length > 1 ? `
                <button class="carousel-control-prev" type="button" data-bs-target="#detailPhotoCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#detailPhotoCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon"></span>
                </button>
            ` : '';

            galleryWrap.innerHTML = `
                <div id="detailPhotoCarousel" class="carousel slide" data-bs-interval="false">
                    <div class="carousel-inner">${slideItems}</div>
                    ${controls}
                </div>
                <div class="detail-thumb-list" id="detailThumbList">${thumbItems}</div>
            `;

            const carouselEl = document.getElementById('detailPhotoCarousel');
            const carousel = bootstrap.Carousel.getOrCreateInstance(carouselEl, {
                interval: false,
                touch: true
            });

            const thumbs = galleryWrap.querySelectorAll('#detailThumbList .detail-thumb-item');
            thumbs.forEach((thumb) => {
                thumb.addEventListener('click', () => {
                    const idx = Number(thumb.getAttribute('data-index') || 0);
                    carousel.to(idx);
                });
            });

            carouselEl.addEventListener('slid.bs.carousel', (e) => {
                thumbs.forEach((thumb) => thumb.classList.remove('active'));
                const activeThumb = galleryWrap.querySelector(`#detailThumbList .detail-thumb-item[data-index="${e.to}"]`);
                if (activeThumb) {
                    activeThumb.classList.add('active');
                }
            });

            const detailCount = data.detail.length;
            fotoSummary.textContent = 'Total foto: ' + images.length + ' (Sampul: ' + (coverFile ? 1 : 0) + ', Detail: ' + detailCount + ')';
        }

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('modalViewDetail'));
        modal.show();
    }

    // View image in modal
    function viewImage(src, title) {
        document.getElementById('viewImageSrc').src = src;
        document.getElementById('viewImageTitle').textContent = title;
    }

    // Delete ruangan with better confirmation
    function deleteRuangan(id, nama) {
        if (confirm('Apakah Anda yakin ingin menghapus ruangan "' + nama + '"?\n\nTindakan ini tidak dapat dibatalkan!')) {
            window.location.href = 'proses_ruangan.php?action=delete&id=' + id;
        }
    }

    // Reset add form when modal is closed
    document.getElementById('modalAddRuangan').addEventListener('hidden.bs.modal', function() {
        this.querySelector('form').reset();
        document.getElementById('addCoverPreviewContainer').style.display = 'none';
    });

    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function() {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>
