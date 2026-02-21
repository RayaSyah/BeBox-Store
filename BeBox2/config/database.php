<?php
// config/database.php
// Di XAMPP  → pakai localhost / root / '' + auto-detect subfolder
// Di Docker → env var DB_HOST, DB_USER, DB_PASS, DB_NAME, BASE_URL di-set otomatis

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'bebox_db');

// Base URL — auto-detect subfolder di XAMPP, pakai env var di Docker/Coolify
$baseUrl = getenv('BASE_URL');
if (!$baseUrl) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Auto-detect subfolder: config/ ada di dalam project root
    // __DIR__ = .../htdocs/BeBox2/config  →  parent = .../htdocs/BeBox2
    $docRoot     = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(__DIR__ . '/..');

    if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
        // Ubah separator jadi slash, lalu ambil bagian setelah docRoot
        $basePath = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    } else {
        $basePath = '';
    }

    $baseUrl = $protocol . $host . $basePath;
}
define('BASE_URL', rtrim($baseUrl, '/'));

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>