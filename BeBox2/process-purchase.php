<?php
// process-purchase.php — Proses transaksi pembelian
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(BASE_URL . '/dashboard.php');

$userId    = (int)$_SESSION['user_id'];
$productId = (int)($_POST['product_id'] ?? 0);
$quantity  = max(1, (int)($_POST['quantity'] ?? 1));
$promoCode = strtoupper(trim($_POST['promo_code'] ?? ''));

// ─── Validasi Produk ──────────────────────────────────────
$pStmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
$pStmt->bind_param('i', $productId);
$pStmt->execute();
$product = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(BASE_URL . '/dashboard.php');
}

if ($product['stock'] < $quantity) {
    setFlash('error', 'Insufficient stock. Only ' . $product['stock'] . ' unit(s) remaining.');
    redirect(BASE_URL . '/dashboard.php');
}

// ─── Hitung Harga & Promo ─────────────────────────────────
$unitPrice      = (float)$product['price'];
$subtotal       = $unitPrice * $quantity;
$discountAmount = 0.00;
$validPromo     = null;

if (!empty($promoCode)) {
    $prStmt = $conn->prepare("
        SELECT * FROM promos
        WHERE code = ? AND is_active = 1
          AND (valid_until IS NULL OR valid_until >= CURDATE())
        LIMIT 1
    ");
    $prStmt->bind_param('s', $promoCode);
    $prStmt->execute();
    $validPromo = $prStmt->get_result()->fetch_assoc();
    $prStmt->close();

    if ($validPromo) {
        $discountAmount = $subtotal * ($validPromo['discount_percent'] / 100);
    } else {
        setFlash('error', 'Promo code is invalid or has expired.');
        redirect(BASE_URL . '/dashboard.php');
    }
}

$totalPrice = max(0, $subtotal - $discountAmount);
$appliedCode = $validPromo ? $validPromo['code'] : null;

// ─── Simpan ke DB (Transaksi) ─────────────────────────────
$conn->begin_transaction();
try {
    // 1. Insert transaksi
    $txStmt = $conn->prepare("
        INSERT INTO transactions
          (user_id, product_id, quantity, unit_price, discount_amount, total_price, promo_code, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $txStmt->bind_param('iiiddds', $userId, $productId, $quantity, $unitPrice, $discountAmount, $totalPrice, $appliedCode);
    $txStmt->execute();
    $transactionId = $conn->insert_id;
    $txStmt->close();

    // 2. Kurangi stok
    $stStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
    $stStmt->bind_param('iii', $quantity, $productId, $quantity);
    $stStmt->execute();
    if ($stStmt->affected_rows === 0) throw new Exception('Insufficient stock.');
    $stStmt->close();

    $conn->commit();

    // Simpan ID transaksi ke session untuk halaman sukses
    $_SESSION['last_transaction_id'] = $transactionId;
    redirect(BASE_URL . '/purchase-success.php');

} catch (Exception $e) {
    $conn->rollback();
    setFlash('error', 'Transaction failed: ' . $e->getMessage());
    redirect(BASE_URL . '/dashboard.php');
}
