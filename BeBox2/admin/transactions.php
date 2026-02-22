<?php
// admin/transactions.php — Kelola Status Transaksi
$adminPage = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin(BASE_URL . '/index.php');

// ─── POST: Update Status ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $txId      = (int)$_POST['tx_id'];
    $newStatus = $_POST['status'] ?? '';
    $allowed   = ['pending', 'processing', 'completed', 'cancelled'];

    if ($txId && in_array($newStatus, $allowed)) {
        $stmt = $conn->prepare("UPDATE transactions SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $newStatus, $txId);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'Transaction status updated.');
    }
    redirect(BASE_URL . '/admin/transactions.php?status=' . urlencode($_POST['prev_status'] ?? 'all'));
}

// ─── Filter ───────────────────────────────────────────────
$statusFilter = $_GET['status'] ?? 'all';
$validStatuses= ['all', 'pending', 'processing', 'completed', 'cancelled'];
if (!in_array($statusFilter, $validStatuses)) $statusFilter = 'all';

// ─── Ambil Transaksi ──────────────────────────────────────
if ($statusFilter === 'all') {
    $transactions = $conn->query("
        SELECT t.*, p.name as product_name, p.image as product_image,
               u.username, u.email
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ")->fetch_all(MYSQLI_ASSOC);
} else {
    $stmt = $conn->prepare("
        SELECT t.*, p.name as product_name, p.image as product_image,
               u.username, u.email
        FROM transactions t
        JOIN products p ON t.product_id = p.id
        JOIN users u ON t.user_id = u.id
        WHERE t.status = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param('s', $statusFilter);
    $stmt->execute();
    $transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$statusOptions = ['pending' => 'Pending', 'processing' => 'Processing', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox Admin — Transactions</title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
<div class="admin-container">
    <?php showFlash(); ?>
    <h1 class="section-title">Transaction Management</h1>
    <p class="section-subtitle">Manage and update the status of all transactions.</p>

    <!-- Filter -->
    <div class="status-filter" style="margin-bottom:20px;">
        <?php
        $counts = [];
        $r = $conn->query("SELECT status, COUNT(*) as c FROM transactions GROUP BY status");
        while ($row = $r->fetch_assoc()) $counts[$row['status']] = $row['c'];
        $allCount = array_sum($counts);
        ?>
        <a href="?status=all" class="filter-btn <?= $statusFilter === 'all' ? 'active' : '' ?>">
            All (<?= $allCount ?>)
        </a>
        <?php foreach ($statusOptions as $key => $label): ?>
        <a href="?status=<?= $key ?>" class="filter-btn <?= $statusFilter === $key ? 'active' : '' ?>">
            <?= $label ?> (<?= $counts[$key] ?? 0 ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabel -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Produk</th>
                    <th>Total</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#aeaeb2;">No transactions found</td></tr>
                <?php else: ?>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td>
                        <a href="<?= BASE_URL ?>/get-receipt.php?id=<?= $tx['id'] ?>"
                           target="_blank" style="font-family:monospace;font-size:12px;font-weight:700;color:#000;text-decoration:none;">
                            <?= generateReceiptNo($tx['id']) ?>
                        </a>
                        <?php if ($tx['promo_code']): ?>
                        <div style="font-size:11px;color:#22c55e;margin-top:2px;">🏷️ <?= sanitize($tx['promo_code']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:14px;"><?= sanitize($tx['username']) ?></div>
                        <div style="font-size:12px;color:#aeaeb2;"><?= sanitize($tx['email']) ?></div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($tx['product_image']) ?>"
                                 style="width:36px;height:36px;border-radius:6px;object-fit:cover;"
                                 onerror="this.style.display='none'">
                            <div>
                                <div style="font-size:13px;font-weight:600;"><?= sanitize($tx['product_name']) ?></div>
                                <div style="font-size:12px;color:#aeaeb2;">Qty: <?= $tx['quantity'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight:800;font-size:15px;"><?= formatPrice($tx['total_price']) ?></td>
                    <td style="font-size:13px;color:#6e6e73;"><?= date('d M Y<\b\r>H:i', strtotime($tx['created_at'])) ?></td>
                    <td><span class="badge badge-<?= $tx['status'] ?>"><?= ucfirst($tx['status']) ?></span></td>
                    <td>
                        <form method="POST" style="display:flex;gap:6px;align-items:center;">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="tx_id" value="<?= $tx['id'] ?>">
                            <input type="hidden" name="prev_status" value="<?= $statusFilter ?>">
                            <select name="status" class="form-control" style="padding:6px 10px;font-size:13px;width:130px;border-radius:8px;">
                                <?php foreach ($statusOptions as $key => $lbl): ?>
                                <option value="<?= $key ?>" <?= $tx['status'] === $key ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
