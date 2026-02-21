<?php
// get-receipt.php — Struk digital, bisa dicetak
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

$txId   = (int)($_GET['id'] ?? 0);
$userId = (int)$_SESSION['user_id'];

if (!$txId) redirect(BASE_URL . '/history.php');

// Admin bisa lihat semua, user hanya miliknya
if (isAdmin()) {
    $stmt = $conn->prepare("
        SELECT t.*, p.name as product_name, p.image as product_image,
               u.username, u.email
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? LIMIT 1
    ");
    $stmt->bind_param('i', $txId);
} else {
    $stmt = $conn->prepare("
        SELECT t.*, p.name as product_name, p.image as product_image,
               u.username, u.email
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        JOIN users u ON t.user_id = u.id
        WHERE t.id = ? AND t.user_id = ? LIMIT 1
    ");
    $stmt->bind_param('ii', $txId, $userId);
}
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tx) {
    setFlash('error', 'Receipt not found.');
    redirect(BASE_URL . '/history.php');
}

$receiptNo      = generateReceiptNo($tx['id']);
$statusLabels   = ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
$statusBadgeMap = ['pending' => '#f59e0b', 'processing' => '#3b82f6', 'completed' => '#22c55e', 'cancelled' => '#ef4444'];
$statusColor    = $statusBadgeMap[$tx['status']] ?? '#888';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Receipt #<?= $receiptNo ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/global.css">
    <style>
    body { background: #f5f5f7; }
    .receipt-page {
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 32px 20px;
        gap: 20px;
    }
    .receipt {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 20px;
        padding: 44px 40px;
        max-width: 480px;
        width: 100%;
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
    }
    .receipt-header { text-align: center; margin-bottom: 24px; }
    .receipt-logo { font-size: 22px; font-weight: 800; display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 4px; }
    .receipt-tagline { font-size: 12px; color: #aeaeb2; }
    .receipt-title { font-size: 13px; font-weight: 600; color: #6e6e73; margin-top: 14px; text-transform: uppercase; letter-spacing: 1px; }
    .receipt-number { font-family: 'Courier New', monospace; font-size: 20px; font-weight: 800; margin-top: 4px; letter-spacing: 2px; }
    .receipt-divider { border: none; border-top: 2px dashed #e5e5e5; margin: 20px 0; }
    .product-box {
        display: flex; gap: 14px; align-items: center;
        background: #f5f5f7; border-radius: 12px; padding: 14px;
        margin-bottom: 16px;
    }
    .product-box img { width: 64px; height: 64px; object-fit: cover; border-radius: 8px; }
    .product-box .name { font-size: 15px; font-weight: 700; }
    .product-box .qty  { font-size: 13px; color: #6e6e73; margin-top: 3px; }
    .receipt-row { display: flex; justify-content: space-between; padding: 7px 0; font-size: 14px; }
    .receipt-row label { color: #6e6e73; }
    .receipt-row .val  { font-weight: 500; color: #1d1d1f; text-align: right; }
    .receipt-row.total {
        font-size: 17px;
        border-top: 2px solid #1d1d1f;
        padding-top: 14px;
        margin-top: 8px;
    }
    .receipt-row.total label, .receipt-row.total .val { font-weight: 800; }
    .receipt-status {
        margin-top: 20px;
        text-align: center;
        padding: 10px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #fff;
        background: <?= $statusColor ?>;
    }
    .receipt-footer { text-align: center; margin-top: 24px; }
    .receipt-footer p { font-size: 12px; color: #aeaeb2; margin-bottom: 4px; }
    .barcode { font-family: 'Courier New', monospace; font-size: 16px; letter-spacing: 4px; color: #1d1d1f; margin-top: 8px; }
    .action-group { display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
    @media print {
        body { background: #fff !important; }
        .navbar, .navbar-spacer, .no-print { display: none !important; }
        .receipt { border: none; box-shadow: none; padding: 0; border-radius: 0; max-width: 100%; }
        .receipt-page { padding: 0; }
    }
    </style>
</head>
<body>
<?php if (!isset($adminPage)): ?>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<?php endif; ?>

<div class="receipt-page">
    <!-- Action buttons (hidden on print) -->
    <div class="action-group no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <a href="<?= BASE_URL ?>/history.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    <!-- Struk Digital -->
    <div class="receipt">
        <div class="receipt-header">
            <div class="receipt-logo"><i class="fas fa-box" style="font-size:18px;"></i> BeBox</div>
            <div class="receipt-tagline">Blind Box Specialist · bebox.store</div>
            <div class="receipt-title">Proof of Payment</div>
            <div class="receipt-number"><?= $receiptNo ?></div>
        </div>

        <hr class="receipt-divider">

        <!-- Produk -->
        <div class="product-box">
            <img src="<?= BASE_URL . '/' . htmlspecialchars($tx['product_image']) ?>"
                 alt="<?= sanitize($tx['product_name']) ?>">
            <div>
                <div class="name"><?= sanitize($tx['product_name']) ?></div>
                <div class="qty">Quantity: <?= $tx['quantity'] ?> unit(s)</div>
            </div>
        </div>

        <!-- Info transaksi -->
        <div class="receipt-row">
            <label>Customer</label>
            <span class="val"><?= sanitize($tx['username']) ?></span>
        </div>
        <div class="receipt-row">
            <label>Email</label>
            <span class="val"><?= sanitize($tx['email']) ?></span>
        </div>
        <div class="receipt-row">
            <label>Date</label>
            <span class="val"><?= date('M d, Y', strtotime($tx['created_at'])) ?></span>
        </div>
        <div class="receipt-row">
            <label>Time</label>
            <span class="val"><?= date('H:i:s', strtotime($tx['created_at'])) ?></span>
        </div>

        <hr class="receipt-divider">

        <!-- Rincian Harga -->
        <div class="receipt-row">
            <label>Unit Price</label>
            <span class="val"><?= formatPrice($tx['unit_price']) ?></span>
        </div>
        <div class="receipt-row">
            <label>Qty</label>
            <span class="val">×<?= $tx['quantity'] ?></span>
        </div>
        <?php if ($tx['discount_amount'] > 0): ?>
        <div class="receipt-row">
            <label>Promo (<?= sanitize($tx['promo_code']) ?>)</label>
            <span class="val" style="color:#22c55e;">-<?= formatPrice($tx['discount_amount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="receipt-row total">
            <label>TOTAL</label>
            <span class="val"><?= formatPrice($tx['total_price']) ?></span>
        </div>

        <!-- Status -->
        <div class="receipt-status">
            <?= strtoupper($statusLabels[$tx['status']]) ?>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <p>Thank you for shopping at BeBox! 🎁</p>
            <p>Keep this receipt as proof of purchase.</p>
            <div class="barcode"><?= strtoupper(substr(md5($tx['id'] . $tx['created_at']), 0, 16)) ?></div>
        </div>
    </div>
</div>
</body>
</html>
