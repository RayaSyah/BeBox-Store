-- ═══════════════════════════════════════════════════════════
--  BeBox2 — Database Setup Script
--  Cara pakai: Buka CMD, jalankan MySQL, paste script ini.
--  Atau jalankan: mysql -u root -p < database.sql
-- ═══════════════════════════════════════════════════════════

-- 1. Buat & pilih database
CREATE DATABASE IF NOT EXISTS bebox_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bebox_db;

-- ─────────────────────────────────────────────────────────
-- 2. Tabel USERS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT          AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(100) NOT NULL,
    email      VARCHAR(150) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    role       ENUM('user','admin') NOT NULL DEFAULT 'user',
    phone      VARCHAR(20)  DEFAULT NULL,
    address    TEXT         DEFAULT NULL,
    avatar     VARCHAR(300) DEFAULT NULL,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Jika tabel sudah ada, tambahkan kolom avatar:
-- ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(300) DEFAULT NULL AFTER address;

-- ─────────────────────────────────────────────────────────
-- 3. Tabel PRODUCTS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS products (
    id          INT            AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(200)   NOT NULL,
    description TEXT           DEFAULT NULL,
    price       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
    image       VARCHAR(300)   DEFAULT NULL,
    stock       INT            NOT NULL DEFAULT 0,
    is_active   TINYINT(1)     NOT NULL DEFAULT 1,
    created_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────
-- 4. Tabel TRANSACTIONS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS transactions (
    id              INT           AUTO_INCREMENT PRIMARY KEY,
    user_id         INT           NOT NULL,
    product_id      INT           NOT NULL,
    quantity        INT           NOT NULL DEFAULT 1,
    unit_price      DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total_price     DECIMAL(10,2) NOT NULL,
    promo_code      VARCHAR(50)   DEFAULT NULL,
    status          ENUM('pending','processing','completed','cancelled') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tx_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_tx_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────
-- 5. Tabel PROMOS
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS promos (
    id               INT           AUTO_INCREMENT PRIMARY KEY,
    code             VARCHAR(50)   NOT NULL UNIQUE,
    description      TEXT          DEFAULT NULL,
    discount_percent DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
    valid_until      DATE          DEFAULT NULL,
    is_active        TINYINT(1)    NOT NULL DEFAULT 1,
    created_at       TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────
-- 6. Seed: ADMIN ACCOUNT
--    Username : Admin
--    Email    : admin23@gmail.com
--    Password : 123admin  (bcrypt hash di bawah)
-- ─────────────────────────────────────────────────────────
INSERT IGNORE INTO users (username, email, password, role)
VALUES (
    'Admin',
    'admin23@gmail.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin'
);

-- CATATAN: Hash di atas adalah bcrypt untuk password '123admin'.
-- Jika login masih bermasalah, jalankan: http://localhost/BeBox2/install.php
-- install.php akan otomatis membuat hash yang benar menggunakan PHP password_hash().
-- Atau generate manual: php -r "echo password_hash('123admin', PASSWORD_DEFAULT);"

-- ─────────────────────────────────────────────────────────
-- 7. Seed: PRODUCTS (3 Produk Hirono)
-- ─────────────────────────────────────────────────────────
INSERT IGNORE INTO products (name, description, price, image, stock, is_active) VALUES
(
    'Hirono Celestial Drift',
    'Exclusive limited edition from the Celestial series, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection, the Hirono Celestial Drift is a must have for true figure enthusiasts.',
    17.53,
    'Picture/image1.jpeg',
    50,
    1
),
(
    'Hirono Boo! Edition',
    'Spooky but cute! Perfect for your ghost collection, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection.',
    15.81,
    'Picture/image2.jpeg',
    50,
    1
),
(
    'Hirono Cruise Rider',
    'This vibrant figure captures Hirono mid-pedal, her cheerful expression radiating pure cycling happiness. With her cute helmet, windswept hair, and detailed bicycle, every element celebrates the freedom of two-wheeled adventures. The dynamic pose makes it look like she\'s just zoomed into your collection!',
    18.15,
    'Picture/image3.jpeg',
    50,
    1
);

-- ─────────────────────────────────────────────────────────
-- 8. Seed: PROMO CODES
-- ─────────────────────────────────────────────────────────
INSERT IGNORE INTO promos (code, description, discount_percent, valid_until, is_active) VALUES
('WELCOME10', 'Diskon selamat datang untuk member baru!',  10.00, '2026-12-31', 1),
('BLIND20',   'Diskon spesial 20% untuk pecinta blind box!', 20.00, '2026-06-30', 1),
('HIRONO5',   'Diskon 5% khusus koleksi Hirono.',           5.00,  '2026-09-30', 1);

-- ─────────────────────────────────────────────────────────
-- Tabel PHP_SESSIONS (untuk Vercel serverless)
-- ─────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS php_sessions (
    session_id    VARCHAR(128) NOT NULL PRIMARY KEY,
    data          TEXT         NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────
-- Verifikasi isi database
-- ─────────────────────────────────────────────────────────
SELECT 'users'        AS tabel, COUNT(*) AS jumlah FROM users
UNION ALL
SELECT 'products',   COUNT(*) FROM products
UNION ALL
SELECT 'promos',     COUNT(*) FROM promos
UNION ALL
SELECT 'transactions', COUNT(*) FROM transactions;
