<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /Web2_Proyek_1/src/auth/login.php?err=' . urlencode('Silakan login dulu.'));
        exit;
    }
}
?>