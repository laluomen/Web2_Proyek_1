<?php
session_start();
require_once __DIR__ . "/../config/koneksi.php";

// Cek role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

$activeAdmin = 'user';
$pageTitle = "Kelola User";

// Ambil data users
$stmt = query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

require_once __DIR__ . "/../templates/admin_head.php";
require_once __DIR__ . "/../templates/admin_sidebar.php";
?>

<div class="admin-container" style="max-width: 100%;">
    <!-- Page Header -->
    <div class="kelola-header mb-4">
        <h1>Kelola User</h1>
        <button class="btn-tambah" data-bs-toggle="modal" data-bs-target="#modalAddUser">
            <i class="bi bi-person-plus-fill me-2"></i>Tambah User
        </button>
    </div>

    <!-- Alert Messages -->
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?php
            switch ($_GET['success']) {
                case 'add':
                    echo "<strong>Berhasil!</strong> User berhasil ditambahkan.";
                    break;
                case 'edit':
                    echo "<strong>Berhasil!</strong> User berhasil diperbarui.";
                    break;
                case 'delete':
                    echo "<strong>Berhasil!</strong> User berhasil dihapus.";
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

    <!-- Card Tabel User -->
    <div class="card shadow border-0" style="border-radius: 15px; overflow: hidden;">
        <div class="card-header bg-white py-3 border-bottom" style="background: linear-gradient(to right, #f8f9fa, #e9ecef) !important;">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h5 class="mb-0 fw-bold" style="color: #495057;">
                        <i class="bi bi-people-fill me-2" style="color: #22c55e;"></i>Daftar User
                    </h5>
                </div>
                <div class="col-md-6">
                    <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search" style="color: #22c55e;"></i>
                        </span>
                        <input type="text" class="form-control border-start-0 bg-white" id="searchInput"
                            placeholder="Cari nama, username..." style="border-left: 0;">
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tableUser">
                    <thead style="background: linear-gradient(to right, #f8f9fa, #e9ecef);">
                        <tr>
                            <th class="text-center" style="width: 50px; padding: 15px 10px;">
                                <i class="bi bi-hash"></i>
                            </th>
                            <th style="width: 25%; padding: 15px;">
                                <i class="bi bi-person me-1"></i>Nama
                            </th>
                            <th style="width: 20%; padding: 15px;">
                                <i class="bi bi-at me-1"></i>Username
                            </th>
                            <th class="text-center" style="width: 12%; padding: 15px;">
                                <i class="bi bi-shield-check me-1"></i>Role
                            </th>
                            <th style="width: 18%; padding: 15px;">
                                <i class="bi bi-mortarboard me-1"></i>Prodi
                            </th>
                            <th class="text-center" style="width: 280px; padding: 15px;">
                                <i class="bi bi-gear me-1"></i>Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox display-4 d-block mb-3"></i>
                                        <p class="mb-0">Belum ada data user</p>
                                        <small>Tambahkan user pertama Anda</small>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $i => $user): ?>
                                <tr>
                                    <td class="text-center">
                                        <span class="badge bg-secondary bg-opacity-75" style="font-size: 0.9rem; padding: 0.4rem 0.7rem;">
                                            <?= $i + 1 ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle me-3">
                                                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark" style="font-size: 1rem;">
                                                    <?= htmlspecialchars($user['nama']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-calendar3 me-1"></i><?= date('d M Y', strtotime($user['created_at'])) ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-dark">
                                            <i class="bi bi-person-badge me-1 text-muted"></i><?= htmlspecialchars($user['username']) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($user['role'] === 'admin'): ?>
                                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; font-weight: 600; border-radius: 8px;">
                                                <i class="bi bi-shield-fill-check me-1"></i>Admin
                                            </span>
                                        <?php else: ?>
                                            <span class="badge px-3 py-2" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0); color: white; font-weight: 600; border-radius: 8px;">
                                                <i class="bi bi-person-fill me-1"></i>Mahasiswa
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-dark">
                                            <?= $user['prodi'] ? '<i class="bi bi-mortarboard-fill me-1 text-success"></i>' . htmlspecialchars($user['prodi']) : '<span class="text-muted">-</span>' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 justify-content-center">
                                            <button class="btn btn-info btn-sm text-white px-2"
                                                style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="viewDetail(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama']) ?>', '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?>', '<?= htmlspecialchars($user['prodi'] ?? '') ?>', '<?= date('d M Y', strtotime($user['created_at'])) ?>')">
                                                <i class="bi bi-eye-fill me-1"></i>Detail
                                            </button>
                                            <button class="btn btn-warning btn-sm text-white px-2"
                                                style="min-width: 60px; font-size: 0.8rem;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#modalEditUser"
                                                onclick="editUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama']) ?>', '<?= htmlspecialchars($user['username']) ?>', '<?= $user['role'] ?>', '<?= htmlspecialchars($user['prodi'] ?? '') ?>')">
                                                <i class="bi bi-pencil-fill me-1"></i>Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm px-2"
                                                style="min-width: 65px; font-size: 0.8rem;"
                                                onclick="deleteUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nama']) ?>')">
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
    </div>
</div>

<!-- Modal Add User -->
<div class="modal fade" id="modalAddUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-person-plus-fill me-2"></i>Tambah User Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_user.php?action=add" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="nama" required
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-at me-1"></i>Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="username" required
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-key me-1"></i>Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control" name="password" required
                            style="border-radius: 8px; padding: 10px 15px;">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shield-check me-1"></i>Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="role" id="addRole" required onchange="toggleProdiField('add')"
                            style="border-radius: 8px; padding: 10px 15px;">
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>
                    <div class="mb-3" id="addProdiField" style="display: none;">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>Program Studi
                        </label>
                        <input type="text" class="form-control" name="prodi"
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-save me-1"></i>Simpan User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="modalEditUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-pencil-square me-2"></i>Edit User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="proses_user.php?action=edit" method="POST">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-person me-1"></i>Nama Lengkap <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="nama" id="editNama" required
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-at me-1"></i>Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" class="form-control" name="username" id="editUsername" required
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-key me-1"></i>Password Baru
                        </label>
                        <input type="password" class="form-control" name="password" id="editPassword"
                            style="border-radius: 8px; padding: 10px 15px;">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shield-check me-1"></i>Role <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="role" id="editRole" required onchange="toggleProdiField('edit')"
                            style="border-radius: 8px; padding: 10px 15px;">
                            <option value="">Pilih Role</option>
                            <option value="admin">Admin</option>
                            <option value="mahasiswa">Mahasiswa</option>
                        </select>
                    </div>
                    <div class="mb-3" id="editProdiField" style="display: none;">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-mortarboard me-1"></i>Program Studi
                        </label>
                        <input type="text" class="form-control" name="prodi" id="editProdi"
                            style="border-radius: 8px; padding: 10px 15px;">
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button type="button" class="btn text-white" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-x-circle me-1"></i>Batal
                    </button>
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); border: none; border-radius: 8px; padding: 10px 24px; font-weight: 600;">
                        <i class="bi bi-check-circle me-1"></i>Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal View Detail -->
<div class="modal fade" id="modalViewDetail" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
            <div class="modal-header text-white border-0" style="background: linear-gradient(135deg, #0dcaf0 0%, #0aa2c0 100%); border-radius: 15px 15px 0 0;">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-info-circle-fill me-2"></i>Detail User
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="detail-item mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-person me-1"></i>Nama Lengkap
                    </label>
                    <div class="fw-bold" id="detailNama"></div>
                </div>
                <div class="detail-item mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-at me-1"></i>Username
                    </label>
                    <div class="fw-bold" id="detailUsername"></div>
                </div>
                <div class="detail-item mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-shield-check me-1"></i>Role
                    </label>
                    <div id="detailRole"></div>
                </div>
                <div class="detail-item mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-mortarboard me-1"></i>Program Studi
                    </label>
                    <div class="fw-bold" id="detailProdi"></div>
                </div>
                <div class="detail-item mb-3 p-3" style="background-color: #f8f9fa; border-radius: 8px;">
                    <label class="text-muted small mb-1">
                        <i class="bi bi-calendar3 me-1"></i>Terdaftar Sejak
                    </label>
                    <div class="fw-bold" id="detailCreated"></div>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 8px; padding: 10px 24px;">
                    <i class="bi bi-x-circle me-1"></i>Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . "/../templates/footer.php"; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Search functionality
    document.getElementById('searchInput').addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableUser tbody tr');

        tableRows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });

    // Toggle prodi field based on role
    function toggleProdiField(mode) {
        const roleSelect = document.getElementById(mode + 'Role');
        const prodiField = document.getElementById(mode + 'ProdiField');
        
        if (roleSelect.value === 'mahasiswa') {
            prodiField.style.display = 'block';
        } else {
            prodiField.style.display = 'none';
        }
    }

    // Edit user function
    function editUser(id, nama, username, role, prodi) {
        document.getElementById('editUserId').value = id;
        document.getElementById('editNama').value = nama;
        document.getElementById('editUsername').value = username;
        document.getElementById('editRole').value = role;
        document.getElementById('editProdi').value = prodi;
        document.getElementById('editPassword').value = '';
        
        // Toggle prodi field
        toggleProdiField('edit');
    }

    // View detail function
    function viewDetail(id, nama, username, role, prodi, created) {
        document.getElementById('detailNama').textContent = nama;
        document.getElementById('detailUsername').textContent = username;
        document.getElementById('detailProdi').textContent = prodi || '-';
        document.getElementById('detailCreated').textContent = created;
        
        // Set role badge
        if (role === 'admin') {
            document.getElementById('detailRole').innerHTML = '<span class="badge" style="background: linear-gradient(135deg, #667eea, #764ba2);"><i class="bi bi-shield-fill-check me-1"></i>Admin</span>';
        } else {
            document.getElementById('detailRole').innerHTML = '<span class="badge" style="background: linear-gradient(135deg, #0dcaf0, #0aa2c0);"><i class="bi bi-person-fill me-1"></i>Mahasiswa</span>';
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('modalViewDetail'));
        modal.show();
    }

    // Delete user with confirmation
    function deleteUser(id, nama) {
        if (confirm('Apakah Anda yakin ingin menghapus user "' + nama + '"?\n\nTindakan ini tidak dapat dibatalkan!')) {
            window.location.href = 'proses_user.php?action=delete&id=' + id;
        }
    }

    // Reset add form when modal is closed
    document.getElementById('modalAddUser').addEventListener('hidden.bs.modal', function() {
        this.querySelector('form').reset();
        document.getElementById('addProdiField').style.display = 'none';
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

<style>
    /* Kelola Header Styling */
    .kelola-header {
        background: linear-gradient(135deg, #2d3748 0%, #1a202c 100%);
        border-radius: 16px;
        padding: 28px 40px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .kelola-header h1 {
        margin: 0;
        color: #ffffff;
        font-size: 32px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .btn-tambah {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: #ffffff;
        border: none;
        border-radius: 10px;
        padding: 12px 32px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(34, 197, 94, 0.3);
    }

    .btn-tambah:hover {
        background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(34, 197, 94, 0.4);
    }

    .btn-tambah:active {
        transform: translateY(0);
    }

    /* Avatar Circle */
    .avatar-circle {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
        box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);
    }

    /* Table Styling */
    .table tbody tr {
        transition: all 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: rgba(34, 197, 94, 0.05);
        transform: scale(1.001);
    }

    /* Custom scrollbar for table */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }

    .table-responsive::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #22c55e, #16a34a);
        border-radius: 10px;
    }

    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #16a34a, #22c55e);
    }

    /* Button Styling */
    .btn-info {
        background: linear-gradient(135deg, #0dcaf0, #0aa2c0);
        border: none;
    }

    .btn-info:hover {
        background: linear-gradient(135deg, #0aa2c0, #0891b2);
    }

    .btn-warning {
        background: linear-gradient(135deg, #fbbf24, #f59e0b);
        border: none;
    }

    .btn-warning:hover {
        background: linear-gradient(135deg, #f59e0b, #d97706);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        border: none;
    }

    .btn-danger:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .kelola-header {
            flex-direction: column;
            gap: 20px;
            padding: 24px 28px;
            text-align: center;
        }

        .kelola-header h1 {
            font-size: 26px;
        }

        .btn-tambah {
            width: 100%;
            padding: 14px 32px;
        }
    }

    @media (max-width: 480px) {
        .kelola-header {
            padding: 20px 20px;
        }

        .kelola-header h1 {
            font-size: 22px;
        }

        .btn-tambah {
            font-size: 14px;
            padding: 12px 24px;
        }
    }
</style>
