<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'bebox_db');

// Base URL for the project (adjust if needed)
define('BASE_URL', '/BeBox2');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
        <h2>⚠️ Database Connection Failed</h2>
        <p>' . $conn->connect_error . '</p>
        <p>Pastikan XAMPP MySQL sudah berjalan dan database <strong>bebox_db</strong> sudah dibuat.</p>
        <p><a href="' . BASE_URL . '/install.php">Klik di sini untuk setup database otomatis</a></p>
    </div>');
}
?>
