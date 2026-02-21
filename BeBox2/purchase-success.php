<?php
// purchase-success.php — Halaman konfirmasi sukses beli
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

$transactionId = (int)($_SESSION['last_transaction_id'] ?? 0);
if (!$transactionId) redirect(BASE_URL . '/dashboard.php');

// Ambil detail transaksi
$stmt = $conn->prepare("
    SELECT t.*, p.name as product_name, p.image as product_image, u.username
    FROM transactions t
    JOIN products p ON t.product_id = p.id
    JOIN users u ON t.user_id = u.id
    WHERE t.id = ? AND t.user_id = ?
    LIMIT 1
");
$stmt->bind_param('ii', $transactionId, $_SESSION['user_id']);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tx) redirect(BASE_URL . '/dashboard.php');

// Hapus dari session setelah dipakai
unset($_SESSION['last_transaction_id']);
$receiptNo = generateReceiptNo($tx['id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Purchase Successful!</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <style>
    .success-page {
        min-height: calc(100vh - 60px);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 20px;
        background: #f5f5f7;
    }
    .success-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 24px;
        padding: 48px 40px;
        max-width: 500px;
        width: 100%;
        text-align: center;
        box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    @keyframes popIn {
        from { opacity: 0; transform: scale(0.9) translateY(20px); }
        to   { opacity: 1; transform: scale(1) translateY(0); }
    }
    .checkmark {
        width: 76px; height: 76px;
        background: #000;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        font-size: 36px;
        color: #fff;
        animation: bounceIn 0.6s ease 0.3s both;
    }
    @keyframes bounceIn {
        0%   { transform: scale(0); }
        60%  { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    .success-title { font-size: 26px; font-weight: 800; margin-bottom: 8px; color: #1d1d1f; }
    .success-sub   { font-size: 15px; color: #6e6e73; margin-bottom: 28px; }
    .order-summary {
        background: #f5f5f7;
        border-radius: 14px;
        padding: 20px;
        text-align: left;
        margin-bottom: 28px;
    }
    .order-row { display: flex; justify-content: space-between; font-size: 14px; padding: 6px 0; }
    .order-row label { color: #6e6e73; }
    .order-row .val { font-weight: 600; color: #1d1d1f; }
    .order-row.total { font-size: 16px; border-top: 1.5px solid #e5e5e5; padding-top: 12px; margin-top: 6px; }
    .order-row.total label, .order-row.total .val { font-weight: 800; color: #1d1d1f; }
    .product-preview {
        display: flex; align-items: center; gap: 12px;
        margin-bottom: 16px; padding-bottom: 16px;
        border-bottom: 1px solid #e5e5e5;
    }
    .product-preview img { width: 56px; height: 56px; border-radius: 8px; object-fit: cover; }
    .product-preview .name { font-size: 14px; font-weight: 700; }
    .product-preview .price { font-size: 13px; color: #6e6e73; margin-top: 2px; }
    .action-group { display: flex; flex-direction: column; gap: 10px; }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="success-page">
    <div class="success-card">
        <div class="checkmark">✓</div>
        <h1 class="success-title">Purchase Successful! 🎉</h1>
        <p class="success-sub">Thank you, <?= sanitize($tx['username']) ?>! Your order is being processed.</p>

        <!-- Order Summary -->
        <div class="order-summary">
            <div class="product-preview">
                <img src="<?= BASE_URL . '/' . htmlspecialchars($tx['product_image']) ?>"
                     alt="<?= sanitize($tx['product_name']) ?>">
                <div>
                    <div class="name"><?= sanitize($tx['product_name']) ?></div>
                    <div class="price">Qty: <?= $tx['quantity'] ?> × <?= formatPrice($tx['unit_price']) ?></div>
                </div>
            </div>
            <div class="order-row">
                <label>Order No.</label>
                <span class="val" style="font-family:monospace;"><?= $receiptNo ?></span>
            </div>
            <div class="order-row">
                <label>Date</label>
                <span class="val"><?= date('M d, Y H:i', strtotime($tx['created_at'])) ?></span>
            </div>
            <div class="order-row">
                <label>Subtotal</label>
                <span class="val"><?= formatPrice($tx['unit_price'] * $tx['quantity']) ?></span>
            </div>
            <?php if ($tx['discount_amount'] > 0): ?>
            <div class="order-row">
                <label>Discount (<?= sanitize($tx['promo_code']) ?>)</label>
                <span class="val" style="color:#22c55e;">-<?= formatPrice($tx['discount_amount']) ?></span>
            </div>
            <?php endif; ?>
            <div class="order-row total">
                <label>Total</label>
                <span class="val"><?= formatPrice($tx['total_price']) ?></span>
            </div>
        </div>

        <!-- Actions -->
        <div class="action-group">
            <a href="<?= BASE_URL ?>/get-receipt.php?id=<?= $tx['id'] ?>" class="btn btn-primary" target="_blank">
                <i class="fas fa-receipt"></i> View & Print Receipt
            </a>
            <a href="<?= BASE_URL ?>/history.php" class="btn btn-secondary">
                <i class="fas fa-receipt"></i> Transaction History
            </a>
            <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    </div>
</div>
</body>
</html>
