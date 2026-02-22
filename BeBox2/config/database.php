<?php
// config/database.php
// XAMPP   → default localhost/root/''
// Vercel  → DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT

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

// Base URL — pakai env var jika ada, otherwise auto-detect
$baseUrl = getenv('BASE_URL');
if (!$baseUrl) {
    // Vercel & reverse proxy set X-Forwarded-Proto, bukan $_SERVER['HTTPS']
    $isHttps  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
              || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
              || ($_SERVER['HTTP_X_FORWARDED_SSL']   ?? '') === 'on';
    $protocol = $isHttps ? 'https://' : 'http://';
    $host     = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $docRoot     = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
    $projectRoot = realpath(__DIR__ . '/..');

    if ($docRoot && $projectRoot && strpos($projectRoot, $docRoot) === 0) {
        $basePath = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
    } else {
        $basePath = '';
    }

    $baseUrl = $protocol . $host . $basePath;
}
define('BASE_URL', rtrim($baseUrl, '/'));

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ─── Database Session Handler ──────────────────────────────
// Vercel serverless: file-based sessions tidak persist antar requests.
// Session disimpan ke MySQL agar login tetap aktif di semua halaman.
class DbSessionHandler implements SessionHandlerInterface {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function open($path, $name): bool { return true; }
    public function close(): bool            { return true; }

    public function read($id): string|false {
        $expiry = time() - 7200;
        $stmt   = $this->conn->prepare(
            "SELECT data FROM php_sessions WHERE session_id = ? AND last_activity > ?"
        );
        $stmt->bind_param('si', $id, $expiry);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ? $row['data'] : '';
    }

    public function write($id, $data): bool {
        $time = time();
        $stmt = $this->conn->prepare(
            "REPLACE INTO php_sessions (session_id, data, last_activity) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('ssi', $id, $data, $time);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    public function destroy($id): bool {
        $stmt = $this->conn->prepare("DELETE FROM php_sessions WHERE session_id = ?");
        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return (bool)$ok;
    }

    public function gc($maxlifetime): int|false {
        $expiry = time() - $maxlifetime;
        $this->conn->query("DELETE FROM php_sessions WHERE last_activity < $expiry");
        return $this->conn->affected_rows;
    }
}

$handler = new DbSessionHandler($conn);
session_set_save_handler($handler, true);
ini_set('session.gc_maxlifetime', 7200);