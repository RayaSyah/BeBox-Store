<?php
// config/database.php
// XAMPP   → default localhost/root/''
// Docker  → DB_HOST, DB_USER, DB_PASS, DB_NAME
// Railway → MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE, MYSQLPORT
// Vercel  → DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT

// Railway MySQL plugin pakai env var berbeda
$_dbHost = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$_dbUser = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$_dbPass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';
$_dbName = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'bebox_db';
$_dbPort = (int)(getenv('MYSQLPORT') ?: getenv('DB_PORT') ?: 3306);

define('DB_HOST', $_dbHost);
define('DB_USER', $_dbUser);
define('DB_PASS', $_dbPass);
define('DB_NAME', $_dbName);
define('DB_PORT', $_dbPort);

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

// Koneksi database (DB_PORT support untuk Railway public MySQL)
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
?>