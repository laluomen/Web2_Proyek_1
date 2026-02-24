<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

// ===== BASE URL (single source of truth) =====
// Hasil contoh: /web2/projek/Web2_Proyek_1/src/
$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$pos = strpos($scriptName, '/src/');
if ($pos !== false) {
    $BASE = substr($scriptName, 0, $pos + 5); // termasuk "/src/"
} else {
    $BASE = rtrim(dirname($scriptName), '/') . '/';
}
$BASE = rtrim($BASE, '/') . '/';

$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $_ENV[$k] = trim($v, "\"'");
    }
}

function env(string $k, $default = null)
{
    return $_ENV[$k] ?? $default;
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo)
        return $pdo;

    $dsn = "mysql:host=" . env('DB_HOST', '127.0.0.1') .
        ";dbname=" . env('DB_NAME', '') .
        ";charset=utf8mb4";

    $pdo = new PDO($dsn, env('DB_USER', 'root'), env('DB_PASS', ''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Otomatis ubah status peminjaman yang sudah lewat menjadi "Selesai"
 * Aturan: hanya yang statusnya Disetujui (2) -> Selesai (4)
 */
function autoMarkSelesai(): void {
    $today = date('Y-m-d');
    $now = date('H:i:s');

    // 1) ambil id yang akan di-mark selesai
    $ids = query(
        "SELECT id
         FROM peminjaman
         WHERE status_id = 2
           AND (
                tanggal < ?
                OR (tanggal = ? AND jam_selesai <= ?)
           )",
        [$today, $today, $now]
    )->fetchAll(PDO::FETCH_COLUMN);

    if (!$ids)
        return;

    // 2) update status
    query(
        "UPDATE peminjaman
         SET status_id = 4
         WHERE id IN (" . implode(',', array_fill(0, count($ids), '?')) . ")",
        array_map('intval', $ids)
    );

    // 3) tulis log (system)
    foreach ($ids as $pid) {
        query(
            "INSERT INTO log_status (peminjaman_id, status_id, diubah_oleh, catatan)
            VALUES (?, 4, NULL, 'Otomatis selesai (waktu telah lewat)')",
            [(int) $pid]
        );
    }
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}