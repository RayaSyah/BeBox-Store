<?php
// admin/promos.php — CRUD Promo
$adminPage = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin(BASE_URL . '/index.php');

$errors  = [];
$success = '';
$editPromo = null;

// ─── POST Actions ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action     = $_POST['action'] ?? '';
    $promoId    = (int)($_POST['promo_id'] ?? 0);
    $code       = strtoupper(trim(sanitize($_POST['code'] ?? '')));
    $desc       = sanitize($_POST['description'] ?? '');
    $discount   = (float)($_POST['discount_percent'] ?? 0);
    $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
    $isActive   = (int)isset($_POST['is_active']);

    if ($action === 'delete' && $promoId) {
        $del = $conn->prepare("DELETE FROM promos WHERE id = ?");
        $del->bind_param('i', $promoId);
        $del->execute();
        $del->close();
        setFlash('success', 'Promo deleted successfully.');
        redirect(BASE_URL . '/admin/promos.php');
    }

    if (in_array($action, ['add', 'edit'])) {
        if (empty($code))        $errors[] = 'Promo code is required.';
        if ($discount <= 0 || $discount > 100) $errors[] = 'Discount must be between 0.01% - 100%.';

        // Cek duplikat kode
        if (empty($errors)) {
            $dupQ = $conn->prepare("SELECT id FROM promos WHERE code = ? AND id != ?");
            $dupQ->bind_param('si', $code, $promoId);
            $dupQ->execute();
            if ($dupQ->get_result()->num_rows > 0) $errors[] = 'Promo code already in use.';
            $dupQ->close();
        }

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO promos (code, description, discount_percent, valid_until, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param('ssdsi', $code, $desc, $discount, $validUntil, $isActive);
                if ($stmt->execute()) { setFlash('success', 'Promo added successfully!'); redirect(BASE_URL . '/admin/promos.php'); }
                else $errors[] = 'Failed to save promo.';
                $stmt->close();
            } elseif ($action === 'edit' && $promoId) {
                $stmt = $conn->prepare("UPDATE promos SET code=?, description=?, discount_percent=?, valid_until=?, is_active=? WHERE id=?");
                $stmt->bind_param('ssdsii', $code, $desc, $discount, $validUntil, $isActive, $promoId);
                if ($stmt->execute()) { setFlash('success', 'Promo updated successfully!'); redirect(BASE_URL . '/admin/promos.php'); }
                else $errors[] = 'Failed to update promo.';
                $stmt->close();
            }
        }
    }
}

// Edit mode
if (isset($_GET['edit'])) {
    $eId = (int)$_GET['edit'];
    $eS  = $conn->prepare("SELECT * FROM promos WHERE id = ? LIMIT 1");
    $eS->bind_param('i', $eId);
    $eS->execute();
    $editPromo = $eS->get_result()->fetch_assoc();
    $eS->close();
}

$promos = $conn->query("SELECT * FROM promos ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox Admin — Promos</title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
<div class="admin-container">
    <?php showFlash(); ?>
    <?php if (!empty($errors)): ?><div class="flash-message flash-error">⚠️ <?= implode(' | ', array_map('sanitize', $errors)) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h1 class="section-title">Promo Management</h1>
            <p class="section-subtitle">Create and manage discount codes for customers.</p>
        </div>
        <button onclick="document.getElementById('promoForm').classList.toggle('hidden')" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add Promo
        </button>
    </div>

    <!-- Form -->
    <div id="promoForm" class="card <?= ($editPromo || !empty($errors)) ? '' : 'hidden' ?>" style="margin-bottom:24px;">
        <div class="card-body">
            <h3 style="font-size:17px;font-weight:700;margin-bottom:18px;">
                <?= $editPromo ? '✏️ Edit Promo' : '➕ Add New Promo' ?>
            </h3>
            <form method="POST" novalidate>
                <input type="hidden" name="action" value="<?= $editPromo ? 'edit' : 'add' ?>">
                <?php if ($editPromo): ?>
                <input type="hidden" name="promo_id" value="<?= $editPromo['id'] ?>">
                <?php endif; ?>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div class="form-group">
                        <label>Promo Code *</label>
                        <input class="form-control" type="text" name="code"
                            value="<?= sanitize($editPromo['code'] ?? '') ?>"
                            required placeholder="e.g. WELCOME10" style="text-transform:uppercase;">
                    </div>
                    <div class="form-group">
                        <label>Discount (%) *</label>
                        <input class="form-control" type="number" name="discount_percent"
                            min="0.01" max="100" step="0.01"
                            value="<?= $editPromo['discount_percent'] ?? '' ?>"
                            required placeholder="e.g. 10">
                    </div>
                    <div class="form-group">
                        <label>Valid Until</label>
                        <input class="form-control" type="date" name="valid_until"
                            value="<?= $editPromo['valid_until'] ?? '' ?>">
                        <small style="color:#aeaeb2;font-size:12px;">Leave empty for no expiry.</small>
                    </div>
                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control" name="is_active">
                            <option value="1" <?= (!$editPromo || $editPromo['is_active']) ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= ($editPromo && !$editPromo['is_active']) ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input class="form-control" type="text" name="description"
                        value="<?= sanitize($editPromo['description'] ?? '') ?>"
                        placeholder="Short description of this promo...">
                </div>
                <div style="display:flex;gap:10px;">
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                    <a href="<?= BASE_URL ?>/admin/promos.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Promo -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Description</th>
                    <th>Discount</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($promos)): ?>
                <tr><td colspan="6" style="text-align:center;padding:40px;color:#aeaeb2;">No promos found</td></tr>
                <?php else: ?>
                <?php foreach ($promos as $promo): ?>
                <?php
                    $isExpired = $promo['valid_until'] && $promo['valid_until'] < date('Y-m-d');
                    $isCurrentlyActive = $promo['is_active'] && !$isExpired;
                ?>
                <tr>
                    <td>
                        <code style="background:#f5f5f7;padding:4px 10px;border-radius:6px;font-size:14px;font-weight:700;letter-spacing:1px;">
                            <?= sanitize($promo['code']) ?>
                        </code>
                    </td>
                    <td style="font-size:13px;color:#6e6e73;max-width:200px;"><?= sanitize($promo['description']) ?></td>
                    <td style="font-weight:800;font-size:16px;"><?= number_format($promo['discount_percent'], 0) ?>%</td>
                    <td style="font-size:13px;">
                        <?php if ($promo['valid_until']): ?>
                            <span style="color:<?= $isExpired ? '#ef4444' : '#1d1d1f' ?>;">
                                <?= date('d M Y', strtotime($promo['valid_until'])) ?>
                                <?= $isExpired ? ' (Expired)' : '' ?>
                            </span>
                        <?php else: ?>
                            <span style="color:#aeaeb2;">No expiry</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $isCurrentlyActive ? 'active' : 'inactive' ?>">
                            <?= $isCurrentlyActive ? 'Active' : ($isExpired ? 'Expired' : 'Inactive') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="?edit=<?= $promo['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="promo_id" value="<?= $promo['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Delete promo '<?= sanitize($promo['code']) ?>'?">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
<?php if ($editPromo): ?>
document.getElementById('promoForm').classList.remove('hidden');
<?php endif; ?>
</script>
<style>.hidden { display: none !important; }</style>
</body>
</html>
