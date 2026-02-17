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
        <div class="admin-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <h1 style="margin: 0; color: #eaf2f7; font-size: 28px; font-weight: 900;">Kelola Ruangan</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAddRuangan">
                <i class="bi bi-plus-circle"></i> Tambah Ruangan
            </button>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php 
                switch($_GET['success']) {
                    case 'add': echo "Ruangan berhasil ditambahkan!"; break;
                    case 'edit': echo "Ruangan berhasil diperbarui!"; break;
                    case 'delete': echo "Ruangan berhasil dihapus!"; break;
                }
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabel Ruangan -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th width="5%">No</th>
                        <th width="25%">Nama Ruangan</th>
                        <th width="20%">Gedung</th>
                        <th width="15%">Kapasitas</th>
                        <th width="15%">Foto</th>
                        <th width="15%">Status</th>
                        <th width="20%">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ruangans)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Belum ada data ruangan</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ruangans as $i => $ruangan): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($ruangan['nama_ruangan']) ?></strong></td>
                                <td><?= htmlspecialchars($ruangan['gedung'] ?? '-') ?></td>
                                <td><?= $ruangan['kapasitas'] ?? '-' ?> orang</td>
                                <td>
                                    <?php if ($ruangan['foto']): ?>
                                        <img src="../uploads/ruangan/<?= htmlspecialchars($ruangan['foto']) ?>" 
                                             alt="<?= htmlspecialchars($ruangan['nama_ruangan']) ?>" 
                                             style="max-width: 80px; max-height: 60px; object-fit: cover; border-radius: 4px;">
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>


                                <td>
                                    <?php if ($ruangan['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Tidak Aktif</span>
                                    <?php endif; ?>
                                <td>
                                    <button class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modalEditRuangan"
                                            onclick="editRuangan(
                                            <?= (int)$ruangan['id'] ?>,
                                            '<?= htmlspecialchars($ruangan['nama_ruangan'], ENT_QUOTES) ?>',
                                            '<?= htmlspecialchars($ruangan['gedung'] ?? '', ENT_QUOTES) ?>',
                                            <?= (int)($ruangan['kapasitas'] ?? 0) ?>,
                                            '<?= htmlspecialchars($ruangan['deskripsi'] ?? '', ENT_QUOTES) ?>',
                                            <?= (int)$ruangan['is_active'] ?>
                                            )">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="if(confirm('Yakin ingin menghapus ruangan ini?')) window.location.href='proses_ruangan.php?action=delete&id=<?= $ruangan['id'] ?>'">
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main><!-- end admin-main -->
</div><!-- end wrap -->

<!-- Modal Tambah Ruangan -->
<div class="modal fade" id="modalAddRuangan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Ruangan Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Ruangan</label>
                        <input type="text" class="form-control" name="nama_ruangan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gedung</label>
                        <input type="text" class="form-control" name="gedung" placeholder="Contoh: Gedung A">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" class="form-control" name="kapasitas" placeholder="Jumlah orang">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" rows="3" placeholder="Keterangan ruangan (opsional)"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Ruangan</label>
                        <input type="file" class="form-control" name="foto" accept="image/*">
                        <small class="text-muted">Format: JPG, PNG, GIF (Max 2MB)</small>
                    </div>
                    <div class="mb-3">
                    <label class="form-label">Status Ruangan</label>
                    <select class="form-select" name="is_active" id="editIsActive">
                        <option value="1">Aktif</option>
                        <option value="0">Nonaktif</option>
                    </select>
                    </div>


                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Ruangan -->
<div class="modal fade" id="modalEditRuangan" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Ruangan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="proses_ruangan.php" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="editRuanganId">
                    
                    <div class="mb-3">
                        <label class="form-label">Nama Ruangan</label>
                        <input type="text" class="form-control" name="nama_ruangan" id="editNamaRuangan" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gedung</label>
                        <input type="text" class="form-control" name="gedung" id="editGedung">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Kapasitas</label>
                        <input type="number" class="form-control" name="kapasitas" id="editKapasitas">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" name="deskripsi" id="editDeskripsi" rows="3"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Foto Ruangan</label>
                        <input type="file" class="form-control" name="foto" accept="image/*" id="editFotoFile">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                        <div class="mt-2">
                            <img id="editFotoPreview" src="" alt="Foto Preview" style="max-width: 100%; max-height: 200px; display: none; border-radius: 4px;">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status Ruangan</label>
                            <select class="form-select" name="is_active" id="editIsActive">
                                <option value="1">Aktif</option>
                                <option value="0">Nonaktif</option>
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editRuangan(id, nama, gedung, kapasitas, deskripsi) {
    document.getElementById('editRuanganId').value = id;
    document.getElementById('editNamaRuangan').value = nama;
    document.getElementById('editGedung').value = gedung;
    document.getElementById('editKapasitas').value = kapasitas;
    document.getElementById('editDeskripsi').value = deskripsi;

    document.getElementById('editIsActive').value = is_active;
}
</script>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>