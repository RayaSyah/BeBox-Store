<?php
// history.php — Riwayat transaksi + struk digital
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

$userId = (int)$_SESSION['user_id'];

// Filter status
$statusFilter = $_GET['status'] ?? 'all';
$allowed      = ['all', 'pending', 'processing', 'completed', 'cancelled'];
if (!in_array($statusFilter, $allowed)) $statusFilter = 'all';

// Ambil transaksi
if ($statusFilter === 'all') {
    $stmt = $conn->prepare("
        SELECT t.*, p.name as product_name, p.image as product_image
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        WHERE t.user_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('i', $userId);
} else {
    $stmt = $conn->prepare("
        SELECT t.*, p.name as product_name, p.image as product_image
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        WHERE t.user_id = ? AND t.status = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('is', $userId, $statusFilter);
}
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$statusLabels = [
    'pending'    => 'Pending',
    'processing' => 'Processing',
    'completed'  => 'Completed',
    'cancelled'  => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Transaction History</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <style>
    .tx-card {
        background: #fff;
        border: 1px solid #e5e5e5;
        border-radius: 16px;
        padding: 20px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: box-shadow 0.3s;
    }
    .tx-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .tx-img { width: 72px; height: 72px; object-fit: cover; border-radius: 10px; flex-shrink: 0; }
    .tx-info { flex-grow: 1; min-width: 0; }
    .tx-name { font-size: 16px; font-weight: 700; color: #1d1d1f; margin-bottom: 4px; }
    .tx-meta { font-size: 13px; color: #6e6e73; display: flex; gap: 16px; flex-wrap: wrap; margin-top: 6px; }
    .tx-total { font-size: 17px; font-weight: 800; color: #1d1d1f; margin-left: auto; flex-shrink: 0; }
    .tx-actions { display: flex; flex-direction: column; gap: 8px; align-items: flex-end; }
    @media(max-width:640px) {
        .tx-card { flex-wrap: wrap; }
        .tx-actions { flex-direction: row; width: 100%; }
    }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="page-container">
    <?php showFlash(); ?>
    <h1 class="section-title">🧾 Transaction History</h1>
    <p class="section-subtitle">View all your orders and download your digital receipts.</p>

    <!-- Filter -->
    <div class="status-filter">
        <a href="?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">All</a>
        <?php foreach ($statusLabels as $key => $label): ?>
        <a href="?status=<?= $key ?>" class="filter-btn <?= $statusFilter === $key ? 'active' : '' ?>">
            <?= $label ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($transactions)): ?>
    <div class="empty-state">
        <i class="fas fa-receipt"></i>
        <h3>No transactions yet</h3>
        <p>Start shopping for your first blind box!</p>
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary" style="margin-top:16px;">
            Shop Now
        </a>
    </div>
    <?php else: ?>
    <?php foreach ($transactions as $tx): ?>
    <div class="tx-card">
        <img
            class="tx-img"
            src="<?= BASE_URL . '/' . htmlspecialchars($tx['product_image']) ?>"
            alt="<?= sanitize($tx['product_name']) ?>"
            onerror="this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 fill=%22%23f0f0f0%22/></svg>'"
        >
        <div class="tx-info">
            <div class="tx-name"><?= sanitize($tx['product_name']) ?></div>
            <span class="badge badge-<?= $tx['status'] ?>"><?= $statusLabels[$tx['status']] ?></span>
            <div class="tx-meta">
                <span><i class="fas fa-hashtag"></i> <?= generateReceiptNo($tx['id']) ?></span>
                <span><i class="fas fa-calendar"></i> <?= date('M d, Y H:i', strtotime($tx['created_at'])) ?></span>
                <span><i class="fas fa-box"></i> Qty: <?= $tx['quantity'] ?></span>
                <?php if ($tx['promo_code']): ?>
                <span><i class="fas fa-tag"></i> <?= sanitize($tx['promo_code']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div class="tx-actions">
            <div class="tx-total"><?= formatPrice($tx['total_price']) ?></div>
            <a href="<?= BASE_URL ?>/get-receipt.php?id=<?= $tx['id'] ?>" class="btn btn-primary btn-sm" target="_blank">
                <i class="fas fa-receipt"></i> View Receipt
            </a>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
</body>
</html>
