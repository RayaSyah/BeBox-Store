<?php
/**
 * BeBox2 - Database Installer
 * Kunjungi sekali: http://localhost/BeBox2/install.php
 * Hapus file ini setelah instalasi berhasil!
 */

$host = 'localhost';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("Koneksi gagal: " . $conn->connect_error);

$log = [];

// 1. Buat database
$conn->query("CREATE DATABASE IF NOT EXISTS bebox_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db('bebox_db');
$log[] = "✅ Database <strong>bebox_db</strong> siap.";

// 2. Tabel users
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    role        ENUM('user','admin') DEFAULT 'user',
    phone       VARCHAR(20) DEFAULT NULL,
    address     TEXT DEFAULT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = "✅ Tabel <strong>users</strong> siap.";

// 3. Tabel products
$conn->query("
CREATE TABLE IF NOT EXISTS products (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    image       VARCHAR(300) DEFAULT NULL,
    stock       INT NOT NULL DEFAULT 0,
    is_active   TINYINT(1) DEFAULT 1,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = "✅ Tabel <strong>products</strong> siap.";

// 4. Tabel transactions
$conn->query("
CREATE TABLE IF NOT EXISTS transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL,
    product_id      INT NOT NULL,
    quantity        INT NOT NULL DEFAULT 1,
    unit_price      DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    total_price     DECIMAL(10,2) NOT NULL,
    promo_code      VARCHAR(50) DEFAULT NULL,
    status          ENUM('pending','processing','completed','cancelled') DEFAULT 'pending',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = "✅ Tabel <strong>transactions</strong> siap.";

// 5. Tabel promos
$conn->query("
CREATE TABLE IF NOT EXISTS promos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50) NOT NULL UNIQUE,
    description      TEXT,
    discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    valid_until      DATE DEFAULT NULL,
    is_active        TINYINT(1) DEFAULT 1,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
$log[] = "✅ Tabel <strong>promos</strong> siap.";

// 6. Seed: Admin account
$check = $conn->query("SELECT id FROM users WHERE email = 'admin23@gmail.com' LIMIT 1");
if ($check->num_rows === 0) {
    $adminPass = password_hash('123admin', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'admin')");
    $adminName  = 'Admin';
    $adminEmail = 'admin23@gmail.com';
    $stmt->bind_param('sss', $adminName, $adminEmail, $adminPass);
    if ($stmt->execute()) {
        $log[] = "✅ Akun admin <strong>admin23@gmail.com</strong> berhasil dibuat.";
    } else {
        $log[] = "⚠️ Gagal membuat admin: " . $stmt->error;
    }
    $stmt->close();
} else {
    $log[] = "ℹ️ Akun admin sudah ada, skip.";
}

// 7. Seed: Products (hanya jika belum ada)
$check = $conn->query("SELECT COUNT(*) as c FROM products");
$row = $check->fetch_assoc();
if ($row['c'] == 0) {
    $products = [
        [
            'name'  => 'Hirono Celestial Drift',
            'desc'  => 'Exclusive limited edition from the Celestial series, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection, the Hirono Celestial Drift is a must have for true figure enthusiasts.',
            'price' => 17.53,
            'image' => 'Picture/image1.jpeg',
            'stock' => 50,
        ],
        [
            'name'  => 'Hirono Boo! Edition',
            'desc'  => 'Spooky but cute! Perfect for your ghost collection, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection, the Hirono Celestial Drift is a must have for true figure enthusiasts.',
            'price' => 15.81,
            'image' => 'Picture/image2.jpeg',
            'stock' => 50,
        ],
        [
            'name'  => 'Hirono Cruise Rider',
            'desc'  => 'This vibrant figure captures Hirono mid-pedal, her cheerful expression radiating pure cycling happiness. With her cute helmet, windswept hair, and detailed bicycle, every element celebrates the freedom of two-wheeled adventures. The dynamic pose makes it look like she\'s just zoomed into your collection!',
            'price' => 18.15,
            'image' => 'Picture/image3.jpeg',
            'stock' => 50,
        ],
    ];
    $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock) VALUES (?, ?, ?, ?, ?)");
    foreach ($products as $p) {
        $stmt->bind_param('ssdsi', $p['name'], $p['desc'], $p['price'], $p['image'], $p['stock']);
        $stmt->execute();
    }
    $stmt->close();
    $log[] = "✅ 3 produk Hirono berhasil di-seed.";
} else {
    $log[] = "ℹ️ Produk sudah ada (" . $row['c'] . " produk), skip.";
}

// 8. Seed: Promos
$checkP = $conn->query("SELECT COUNT(*) as c FROM promos");
$rowP = $checkP->fetch_assoc();
if ($rowP['c'] == 0) {
    $promos = [
        ['WELCOME10', 'Diskon selamat datang untuk member baru!', 10.00, '2026-12-31'],
        ['BLIND20',   'Diskon spesial 20% untuk pecinta blind box!', 20.00, '2026-06-30'],
        ['HIRONO5',   'Diskon 5% khusus koleksi Hirono.', 5.00, '2026-09-30'],
    ];
    $stmt = $conn->prepare("INSERT INTO promos (code, description, discount_percent, valid_until) VALUES (?, ?, ?, ?)");
    foreach ($promos as $promo) {
        $stmt->bind_param('ssds', $promo[0], $promo[1], $promo[2], $promo[3]);
        $stmt->execute();
    }
    $stmt->close();
    $log[] = "✅ 3 promo berhasil di-seed.";
} else {
    $log[] = "ℹ️ Promo sudah ada, skip.";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BeBox2 - Installer</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #000; color: #fff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
  .card { background: #111; border: 1px solid #222; border-radius: 16px; padding: 48px 40px; max-width: 560px; width: 100%; }
  h1 { font-size: 28px; font-weight: 700; margin-bottom: 8px; }
  .sub { color: #888; font-size: 15px; margin-bottom: 32px; }
  .log-item { padding: 12px 0; border-bottom: 1px solid #1e1e1e; font-size: 15px; line-height: 1.5; }
  .log-item:last-child { border-bottom: none; }
  .actions { margin-top: 32px; display: flex; gap: 12px; flex-wrap: wrap; }
  a.btn { display: inline-block; padding: 12px 24px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; transition: 0.3s; }
  a.btn-primary { background: #fff; color: #000; }
  a.btn-primary:hover { background: #e5e5e5; }
  a.btn-outline { border: 1px solid #444; color: #fff; }
  a.btn-outline:hover { background: #1e1e1e; }
  .warning { background: #1a1000; border: 1px solid #443300; border-radius: 8px; padding: 16px; margin-top: 24px; color: #ffcc00; font-size: 14px; }
</style>
</head>
<body>
<div class="card">
  <h1>📦 BeBox2 Installer</h1>
  <p class="sub">Setup database dan data awal selesai!</p>
  <?php foreach ($log as $entry): ?>
    <div class="log-item"><?= $entry ?></div>
  <?php endforeach; ?>
  <div class="warning">
    ⚠️ <strong>Penting:</strong> Hapus file <code>install.php</code> setelah instalasi berhasil untuk alasan keamanan!
  </div>
  <div class="actions">
    <a href="/BeBox2/" class="btn btn-primary">🚀 Ke Halaman Login</a>
    <a href="/BeBox2/admin/" class="btn btn-outline">⚙️ Ke Admin Panel</a>
  </div>
</div>
</body>
</html>
