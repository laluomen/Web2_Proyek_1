<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void
{
    global $BASE;

    if (!empty($_SESSION['user_id'])) {
        return;
    }

    $current = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . $BASE . 'auth/login.php?redirect=' . urlencode($current));
    exit;
}
?>