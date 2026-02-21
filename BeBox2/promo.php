<?php
// promo.php — Halaman promo aktif
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

// Ambil promo aktif
$promos = $conn->query("
    SELECT * FROM promos
    WHERE is_active = 1
      AND (valid_until IS NULL OR valid_until >= CURDATE())
    ORDER BY created_at DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Promo</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="page-container">
    <?php showFlash(); ?>
    <h1 class="section-title">🏷️ Active Promos</h1>
    <p class="section-subtitle">Use a promo code at checkout to get a special discount!</p>

    <?php if (empty($promos)): ?>
    <div class="empty-state">
        <i class="fas fa-tags"></i>
        <h3>No active promos yet</h3>
        <p>Check back soon for exciting deals!</p>
    </div>
    <?php else: ?>
    <div class="promo-grid">
        <?php foreach ($promos as $promo): ?>
        <div class="promo-card">
            <div class="promo-discount"><?= number_format($promo['discount_percent'], 0) ?>% OFF</div>
            <div class="promo-code" data-code="<?= sanitize($promo['code']) ?>" title="Klik untuk menyalin">
                <?= sanitize($promo['code']) ?>
            </div>
            <p class="promo-desc"><?= sanitize($promo['description']) ?></p>
            <?php if ($promo['valid_until']): ?>
            <p class="promo-valid">
                <i class="fas fa-clock"></i>
                Valid until <?= date('M d, Y', strtotime($promo['valid_until'])) ?>
            </p>
            <?php else: ?>
            <p class="promo-valid"><i class="fas fa-infinity"></i> No expiry date</p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <p style="margin-top:20px;font-size:13px;color:#aeaeb2;text-align:center;">
        💡 Click a promo code to copy it to clipboard
    </p>
    <?php endif; ?>

    <div style="text-align:center;margin-top:32px;">
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Shop
        </a>
    </div>
</div>
</body>
</html>
