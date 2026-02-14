<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireRole(string $role): void {
    if (($_SESSION['role'] ?? '') !== $role) {
        http_response_code(403);
        echo "Akses ditolak.";
        exit;
    }
}
?>