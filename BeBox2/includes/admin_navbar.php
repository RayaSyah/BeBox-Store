<?php
// includes/admin_navbar.php — Admin Navbar
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header class="navbar admin-navbar">
    <div class="navbar-inner">
        <!-- Logo -->
        <a href="<?= BASE_URL ?>/admin/" class="brand-logo">
            <i class="fas fa-box"></i>
            <span>BeBox <span class="admin-badge">Admin</span></span>
        </a>

        <!-- Desktop Nav Links -->
        <nav class="admin-links">
            <a href="<?= BASE_URL ?>/admin/" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i> Dashboard
            </a>
            <a href="<?= BASE_URL ?>/admin/products.php" class="<?= $currentPage === 'products.php' ? 'active' : '' ?>">
                <i class="fas fa-box-open"></i> Products
            </a>
            <a href="<?= BASE_URL ?>/admin/transactions.php" class="<?= $currentPage === 'transactions.php' ? 'active' : '' ?>">
                <i class="fas fa-receipt"></i> Transactions
            </a>
            <a href="<?= BASE_URL ?>/admin/promos.php" class="<?= $currentPage === 'promos.php' ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Promo
            </a>
        </nav>

        <!-- Right: burger for mobile -->
        <div class="nav-container">
            <input type="checkbox" id="menu-toggle" hidden>
            <label for="menu-toggle" class="burger-menu" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <div class="menu-dropdown">
                <div class="menu-user-info">
                    <i class="fas fa-user-shield"></i>
                    <span><?= sanitize($_SESSION['username'] ?? 'Admin') ?></span>
                </div>
                <hr class="menu-divider">
                <a href="<?= BASE_URL ?>/admin/"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="<?= BASE_URL ?>/admin/products.php"><i class="fas fa-box-open"></i> Products</a>
                <a href="<?= BASE_URL ?>/admin/transactions.php"><i class="fas fa-receipt"></i> Transactions</a>
                <a href="<?= BASE_URL ?>/admin/promos.php"><i class="fas fa-tags"></i> Promo</a>
                <hr class="menu-divider">
                <a href="<?= BASE_URL ?>/dashboard.php"><i class="fas fa-store"></i> View Store</a>
                <a href="<?= BASE_URL ?>/admin/logout.php" class="logout-link"><i class="fas fa-right-from-bracket"></i> Logout</a>
            </div>
        </div>
    </div>
</header>
<div class="navbar-spacer"></div>

<script>
document.addEventListener('click', function(e) {
    const toggle = document.getElementById('menu-toggle');
    const nav    = document.querySelector('.nav-container');
    if (toggle && toggle.checked && nav && !nav.contains(e.target)) {
        toggle.checked = false;
    }
});
</script>
