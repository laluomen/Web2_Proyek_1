<?php
if (session_status() === PHP_SESSION_NONE){
     session_start();
}

$envPath = __DIR__ . '/../.env';
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

function env(string $k, $default = null) {
    return $_ENV[$k] ?? $default;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $dsn = "mysql:host=" . env('DB_HOST','127.0.0.1') .
           ";dbname=" . env('DB_NAME','') .
           ";charset=utf8mb4";

    $pdo = new PDO($dsn, env('DB_USER','root'), env('DB_PASS',''), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    return $pdo;
}

function query(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

