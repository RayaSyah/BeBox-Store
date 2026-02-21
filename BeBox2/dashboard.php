<?php
// dashboard.php — Halaman utama user, produk listing + search
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

// ─── Search Query ─────────────────────────────────────────
$searchQuery = trim($_GET['search'] ?? '');

// ─── Ambil Produk ─────────────────────────────────────────
if (!empty($searchQuery)) {
    $like = '%' . $conn->real_escape_string($searchQuery) . '%';
    $stmt = $conn->prepare("SELECT * FROM products WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?) ORDER BY id ASC");
    $stmt->bind_param('ss', $like, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM products WHERE is_active = 1 ORDER BY id ASC");
}
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Dashboard</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <style>
    /* ── Dashboard page-specific styles (mainpage.css merged) ── */
    .hero-section {
        background-image: url('<?= BASE_URL ?>/Picture/banner.jpeg');
    }
    .no-results-msg {
        text-align: center;
        padding: 60px 20px;
        color: #6e6e73;
    }
    .no-results-msg i { font-size: 48px; opacity: 0.3; display: block; margin-bottom: 14px; }
    .no-results-msg h3 { font-size: 20px; font-weight: 600; color: #1d1d1f; margin-bottom: 8px; }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<!-- Hero Section -->
<?php if (empty($searchQuery)): ?>
<section class="hero-section">
    <div class="hero-content">
        <h2>Unwrap your surprise with BeBox!</h2>
        <p>Experience the thrill of getting a unique and exclusive blind box.</p>
    </div>
</section>
<?php endif; ?>

<!-- Products -->
<div class="page-container">
    <?php showFlash(); ?>

    <?php if (!empty($searchQuery)): ?>
    <div class="search-banner">
        <span>Search results for <strong>"<?= sanitize($searchQuery) ?>"</strong> &mdash; <?= count($products) ?> product(s) found</span>
        <a href="<?= BASE_URL ?>/dashboard.php">✕ Clear search</a>
    </div>
    <?php endif; ?>

    <section class="product-listing">
        <?php if (empty($searchQuery)): ?>
            <h3>Today's Random Picks</h3>
        <?php else: ?>
            <h3>Search Results</h3>
        <?php endif; ?>

        <?php if (empty($products)): ?>
        <div class="no-results-msg">
            <i class="fas fa-box-open"></i>
            <h3>No products found</h3>
            <p>Try a different keyword or <a href="<?= BASE_URL ?>/dashboard.php" style="color:#000;font-weight:600;">browse all products</a>.</p>
        </div>
        <?php else: ?>
        <?php foreach ($products as $p): ?>
        <div class="product-item">
            <div class="product-image">
                <img
                    src="<?= BASE_URL . '/' . htmlspecialchars($p['image']) ?>"
                    alt="<?= sanitize($p['name']) ?>"
                    onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'"
                >
            </div>
            <div class="product-description">
                <h4><?= sanitize($p['name']) ?></h4>
                <p><?= sanitize($p['description']) ?></p>
                <p class="product-price"><?= formatPrice($p['price']) ?></p>
            </div>
            <div class="product-actions">
                <?php if ($p['stock'] > 0): ?>
                <a href="<?= BASE_URL ?>/checkout.php?product_id=<?= (int)$p['id'] ?>" class="buy-button" style="display:inline-block;text-align:center;text-decoration:none;">
                    Buy now <i class="fas fa-shopping-cart"></i>
                </a>
                <?php else: ?>
                <button class="out-of-stock-btn" disabled>Out of Stock</button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </section>
</div>

</body>
</html>
