<?php
session_start();
if (($_SESSION['role'] ?? '') !== 'admin') { header("Location: ../auth/login.php"); exit; }
?>
<h1>Admin Dashboard</h1>
<p>Halo, <?= htmlspecialchars($_SESSION['nama'] ?? '') ?></p>
<a href="../auth/logout.php">Logout</a>