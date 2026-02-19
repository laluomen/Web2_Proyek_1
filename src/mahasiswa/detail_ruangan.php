<?php
require_once __DIR__ . '/../config/koneksi.php';
require_once __DIR__ . '/../templates/header.php';

$id = $_GET['id'] ?? null;
if (!$id) { echo "ID tidak valid"; }

$ruangan = query("SELECT * FROM ruangan WHERE id = ?", [$id])->fetch();
if (!$ruangan) { echo "Ruangan tidak ditemukan"; }
?>

<h1><?= e($ruangan['nama_ruangan']) ?></h1>

<img src="<?= $BASE ?>/uploads/ruangan/<?= e($ruangan['foto']) ?>"
     alt="<?= e($ruangan['nama_ruangan']) ?>"
     style="max-width:520px;height:auto;">

<?php require_once __DIR__ . '/../templates/footer.php'; ?>
