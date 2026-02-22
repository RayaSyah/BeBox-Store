<?php
// admin/index.php — Dashboard Admin
$adminPage = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin(BASE_URL . '/index.php');

// ─── Statistik ────────────────────────────────────────────
$stats = [];

$r = $conn->query("SELECT COUNT(*) as c FROM users WHERE role = 'user'");
$stats['users'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM products WHERE is_active = 1");
$stats['products'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COUNT(*) as c FROM transactions");
$stats['transactions'] = $r->fetch_assoc()['c'];

$r = $conn->query("SELECT COALESCE(SUM(total_price), 0) as rev FROM transactions WHERE status = 'completed'");
$stats['revenue'] = (float)$r->fetch_assoc()['rev'];

$r = $conn->query("SELECT COUNT(*) as c FROM transactions WHERE status = 'pending'");
$stats['pending'] = $r->fetch_assoc()['c'];

// Transaksi terbaru
$recentTx = $conn->query("
    SELECT t.id, t.total_price, t.status, t.created_at,
           u.username, p.name as product_name
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    JOIN products p ON t.product_id = p.id
    ORDER BY t.created_at DESC
    LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

// Produk stok rendah
$lowStock = $conn->query("
    SELECT id, name, stock FROM products WHERE stock <= 10 AND is_active = 1 ORDER BY stock ASC LIMIT 5
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox Admin — Dashboard</title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <style>
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    @media(max-width:768px) { .two-col { grid-template-columns: 1fr; } }
    .pending-badge {
        background: #000;
        color: #fff;
        font-size: 11px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        margin-left: 8px;
    }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<div class="admin-container">
    <?php showFlash(); ?>
    <h1 class="section-title">Dashboard</h1>
    <p class="section-subtitle">Welcome back, <?= sanitize($_SESSION['username']) ?>!</p>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-label">Total Users</div>
            <div class="stat-value"><?= number_format($stats['users']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-box-open"></i></div>
            <div class="stat-label">Active Products</div>
            <div class="stat-value"><?= number_format($stats['products']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-receipt"></i></div>
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value"><?= number_format($stats['transactions']) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
            <div class="stat-label">Revenue (Completed)</div>
            <div class="stat-value"><?= formatPrice($stats['revenue']) ?></div>
        </div>
    </div>

    <div class="two-col">
        <!-- Transaksi Terbaru -->
        <div class="card">
            <div class="card-body" style="padding-bottom:0;">
                <h3 style="font-size:17px;font-weight:700;margin-bottom:16px;">
                    Recent Transactions
                    <?php if ($stats['pending'] > 0): ?>
                    <span class="pending-badge"><?= $stats['pending'] ?> pending</span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="table-wrap" style="border-radius:0;border:none;border-top:1px solid #f0f0f0;">
                <table>
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>User</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTx)): ?>
                        <tr><td colspan="4" style="text-align:center;color:#aeaeb2;padding:30px;">No transactions yet</td></tr>
                        <?php else: ?>
                        <?php foreach ($recentTx as $tx): ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/get-receipt.php?id=<?= $tx['id'] ?>"
                                   style="font-size:12px;font-family:monospace;color:#000;font-weight:600;text-decoration:none;">
                                    <?= generateReceiptNo($tx['id']) ?>
                                </a>
                                <div style="font-size:11px;color:#aeaeb2;"><?= sanitize($tx['product_name']) ?></div>
                            </td>
                            <td style="font-size:13px;"><?= sanitize($tx['username']) ?></td>
                            <td style="font-weight:700;"><?= formatPrice($tx['total_price']) ?></td>
                            <td><span class="badge badge-<?= $tx['status'] ?>"><?= ucfirst($tx['status']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="padding:14px 16px;">
                <a href="<?= BASE_URL ?>/admin/transactions.php" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">
                    View All Transactions <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Stok Rendah -->
        <div class="card">
            <div class="card-body">
                <h3 style="font-size:17px;font-weight:700;margin-bottom:16px;">⚠️ Low Stock</h3>
                <?php if (empty($lowStock)): ?>
                    <p style="color:#22c55e;font-size:14px;">✅ All products are well stocked!</p>
                <?php else: ?>
                <?php foreach ($lowStock as $prod): ?>
                <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f0f0f0;">
                    <span style="font-size:14px;font-weight:600;"><?= sanitize($prod['name']) ?></span>
                    <span style="font-size:13px;font-weight:700;color:<?= $prod['stock'] <= 5 ? '#ef4444' : '#f59e0b' ?>;">
                        <?= $prod['stock'] ?> unit
                    </span>
                </div>
                <?php endforeach; ?>
                <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-outline btn-sm" style="margin-top:14px;width:100%;justify-content:center;">
                    Manage Products
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>
</html>
