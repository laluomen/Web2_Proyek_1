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

// Ambil data ruangan
$stmt = query("SELECT * FROM ruangan ORDER BY nama_ruangan ASC");
$ruangans = $stmt->fetchAll();

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
                                <td colspan="6" class="text-center py-5">
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
                                            style="background: linear-gradient(135deg, #22c55e, #16a34a); color: white; font-weight: 600; border-radius: 8px;">
                                            <i class="bi bi-people-fill me-1"></i><?= $ruangan['kapasitas'] ?? '0' ?> orang
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($ruangan['foto']): ?>
                                            <img src="../uploads/ruangan/<?= htmlspecialchars($ruangan['foto']) ?>"
                                                alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>"
                                                class="rounded shadow-sm img-thumbnail"
                                                style="width: 80px; height: 55px; object-fit: cover; cursor: pointer;"
                                                data-bs-toggle="modal" data-bs-target="#modalViewImage"
                                                onclick="viewImage('../uploads/ruangan/<?= htmlspecialchars($ruangan['foto']) ?>', '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>')">
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary">
                                                <i class="bi bi-image"></i> No Image
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-info aksi-btn" style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="viewDetail(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', <?= $ruangan['kapasitas'] ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>', '<?= htmlspecialchars($ruangan['foto'] ?? '') ?>')">
                                                <i class="bi bi-eye-fill me-1"></i>Detail
                                            </button>
                                            <button class="btn btn-warning aksi-btn" style="min-width: 60px; font-size: 0.8rem;"
                                                data-bs-toggle="modal" data-bs-target="#modalEditRuangan"
                                                onclick="editRuangan(<?= $ruangan['id'] ?>, '<?= htmlspecialchars($ruangan['nama_ruangan']) ?>', '<?= htmlspecialchars($ruangan['gedung'] ?? '') ?>', <?= $ruangan['kapasitas'] ?>, '<?= htmlspecialchars($ruangan['deskripsi'] ?? '') ?>')">
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #22c55e;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan" required
                                placeholder="Contoh: Ruang 301">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #22c55e;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung" placeholder="Contoh: Gedung A">
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
                            <i class="bi bi-image me-1" style="color: #22c55e;"></i>Foto Ruangan
                        </label>
                        <input type="file" class="form-control" name="foto" accept="image/*" id="addFotoInput"
                            onchange="previewAddImage(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Format: JPG, PNG, GIF (Max 2MB)
                        </small>
                        <div class="mt-3" id="addImagePreviewContainer" style="display: none;">
                            <img id="addImagePreview" src="" alt="Preview" class="img-thumbnail rounded"
                                style="max-height: 200px;">
                        </div>
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-door-closed me-1" style="color: #f59e0b;"></i>Nama Ruangan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" name="nama_ruangan" id="editNamaRuangan" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-building me-1" style="color: #f59e0b;"></i>Gedung
                            </label>
                            <input type="text" class="form-control" name="gedung" id="editGedung">
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
                            <i class="bi bi-image me-1" style="color: #f59e0b;"></i>Foto Ruangan
                        </label>
                        <input type="file" class="form-control" name="foto" accept="image/*" id="editFotoFile"
                            onchange="previewEditImage(event)">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>Kosongkan jika tidak ingin mengubah foto
                        </small>
                        <div class="mt-3" id="editImagePreviewContainer" style="display: none;">
                            <img id="editFotoPreview" src="" alt="Foto Preview" class="img-thumbnail rounded"
                                style="max-height: 200px;">
                        </div>
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
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small mb-2">
                            <i class="bi bi-image me-1"></i>Foto Ruangan
                        </label>
                        <div id="detailFotoContainer">
                            <img id="detailFoto" src="" alt="Foto Ruangan" class="img-fluid rounded shadow-sm"
                                style="max-height: 250px; width: 100%; object-fit: cover;">
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
    // Initialize Bootstrap tooltips
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function () {
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
    function previewAddImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('addImagePreview').src = e.target.result;
                document.getElementById('addImagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Preview image on edit modal
    function previewEditImage(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                document.getElementById('editFotoPreview').src = e.target.result;
                document.getElementById('editImagePreviewContainer').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    // Edit ruangan function
    function editRuangan(id, nama, gedung, kapasitas, deskripsi) {
        document.getElementById('editRuanganId').value = id;
        document.getElementById('editNamaRuangan').value = nama;
        document.getElementById('editGedung').value = gedung;
        document.getElementById('editKapasitas').value = kapasitas;
        document.getElementById('editDeskripsi').value = deskripsi;

        // Hide preview when opening modal
        document.getElementById('editImagePreviewContainer').style.display = 'none';
        document.getElementById('editFotoFile').value = '';
    }

    // View detail function
    function viewDetail(id, nama, gedung, kapasitas, deskripsi, foto) {
        document.getElementById('detailNamaRuangan').textContent = nama;
        document.getElementById('detailGedung').textContent = gedung || '-';
        document.getElementById('detailKapasitas').textContent = kapasitas ? kapasitas + ' orang' : '-';
        document.getElementById('detailDeskripsi').textContent = deskripsi || 'Tidak ada deskripsi';

        if (foto) {
            document.getElementById('detailFoto').src = '../uploads/ruangan/' + foto;
            document.getElementById('detailFotoContainer').style.display = 'block';
        } else {
            document.getElementById('detailFotoContainer').innerHTML = '<div class="alert alert-secondary text-center"><i class="bi bi-image"></i> Tidak ada foto</div>';
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
    document.getElementById('modalAddRuangan').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        document.getElementById('addImagePreviewContainer').style.display = 'none';
    });

    // Auto-dismiss alerts after 5 seconds
    document.addEventListener('DOMContentLoaded', function () {
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });
</script>

<style>
    /* ================= HEADER ================= */
    .kelola-header {
        background: linear-gradient(135deg, #2d3748, #1a202c);
        border-radius: 16px;
        padding: 26px 34px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 6px 24px rgba(0, 0, 0, .25);
        border: 1px solid rgba(255, 255, 255, .08);
    }

    .kelola-header h1 {
        margin: 0;
        color: #fff;
        font-size: 30px;
        font-weight: 700;
    }

    .btn-tambah {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 12px 30px;
        font-weight: 600;
        transition: .25s;
    }

    .btn-tambah:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 18px rgba(34, 197, 94, .35);
    }

    /* ================= TABLE ================= */

    #tableRuangan {
        border-collapse: separate;
        border-spacing: 0;
    }

    #tableRuangan thead th {
        font-size: 12px;
        letter-spacing: .6px;
        color: #475569;
        border-bottom: 1px solid #e5e7eb;
        background: linear-gradient(to right, #f8fafc, #eef2f7);
    }

    #tableRuangan tbody td {
        padding: 14px 12px;
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }

    /* column separator */
    #tableRuangan th,
    #tableRuangan td {
        border-right: 1px solid #e5e7eb;
    }

    #tableRuangan th:last-child,
    #tableRuangan td:last-child {
        border-right: none;
    }

    /* ===== HOVER ROW (FIXED) ===== */
    #tableRuangan tbody tr {
        transition: background .15s ease;
    }

    #tableRuangan tbody tr:hover td {
        background: #f1f5f9;
    }

    /* ================= BADGE & BUTTON ALIGNMENT (PIXEL PERFECT) ================= */

    /* base height semua label */
    #tableRuangan .badge,
    #tableRuangan .btn,
    .badge-number {
        height: 34px;
        line-height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 16px;
        font-size: 13px;
        font-weight: 600;
        border-radius: 8px;
    }

    /* bootstrap badge baseline correction */
    #tableRuangan .badge {
        transform: translateY(-1px);
    }

    /* nomor */
    .badge-number {
        width: 38px;
        background: #9aa1a9;
        color: #fff;
    }

    /* GEDUNG */
    #tableRuangan td:nth-child(3) .badge {
        min-width: 90px;
    }

    /* KAPASITAS */
    #tableRuangan td:nth-child(4) .badge {
        min-width: 110px;
    }

    /* tombol */
    #tableRuangan .btn {
        min-width: 90px;
        box-shadow: 0 8px 18px rgba(0, 0, 0, .18);
        transition: .2s;
        color: #fff;
    }

    /* warna tombol */
    #tableRuangan .btn-info {
        background: linear-gradient(135deg, #22b8cf, #0ea5b7) !important;
    }

    #tableRuangan .btn-warning {
        background: linear-gradient(135deg, #ffbe0b, #ff9800) !important;
    }

    #tableRuangan .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626) !important;
    }

    /* hover tombol */
    #tableRuangan .btn:hover {
        transform: translateY(-1px);
    }

    /* hilangkan teks hitam saat hover */
    #tableRuangan .btn-info:hover,
    #tableRuangan .btn-info:focus,
    #tableRuangan .btn-info:active {
        color: #fff !important;
    }

    /* spacing tombol */
    #tableRuangan td:last-child .d-flex {
        gap: 10px;
        align-items: center;
    }
</style>
<?php require_once __DIR__ . "/../templates/footer.php"; ?>