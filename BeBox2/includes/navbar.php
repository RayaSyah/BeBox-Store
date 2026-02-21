<?php
// includes/navbar.php — User Navbar (include di semua halaman user)
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$searchQuery = isset($_GET['search']) ? sanitize($_GET['search']) : '';
?>
<header class="navbar">
    <div class="navbar-inner">
        <!-- Logo (pertahankan logo asli) -->
        <a href="/BeBox2/dashboard.php" class="brand-logo">
            <i class="fas fa-box"></i>
            <span>BeBox</span>
        </a>

        <!-- Search Bar -->
        <div class="search-container">
            <form action="/BeBox2/dashboard.php" method="GET" class="search-form">
                <input
                    type="text"
                    name="search"
                    placeholder="Search products..."
                    value="<?= $searchQuery ?>"
                    autocomplete="off"
                    aria-label="Search products"
                >
                <button type="submit" aria-label="Search">
                    <i class="fa fa-search"></i>
                </button>
            </form>
        </div>

        <!-- Burger Menu -->
        <div class="nav-container">
            <input type="checkbox" id="menu-toggle" hidden>
            <label for="menu-toggle" class="burger-menu" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <div class="menu-dropdown">
                <?php if (isLoggedIn()):
                    // Fetch avatar for nav
                    $navUserId = (int)($_SESSION['user_id'] ?? 0);
                    $navAvatar = '';
                    if ($navUserId) {
                        $navQ = $conn->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
                        $navQ->bind_param('i', $navUserId);
                        $navQ->execute();
                        $navRow = $navQ->get_result()->fetch_assoc();
                        $navQ->close();
                        $navAvatar = $navRow['avatar'] ?? '';
                    }
                ?>
                    <div class="menu-user-info">
                        <?php if (!empty($navAvatar) && file_exists(__DIR__ . '/../' . $navAvatar)): ?>
                            <img src="<?= BASE_URL . '/' . sanitize($navAvatar) ?>"
                                 alt="avatar"
                                 style="width:28px;height:28px;border-radius:50%;object-fit:cover;flex-shrink:0;">
                        <?php else: ?>
                            <i class="fas fa-user-circle"></i>
                        <?php endif; ?>
                        <span><?= sanitize($_SESSION['username'] ?? 'User') ?></span>
                    </div>
                    <hr class="menu-divider">
                    <a href="/BeBox2/profile.php" class="<?= $currentPage === 'profile.php' ? 'active' : '' ?>">
                        <i class="fas fa-user"></i> Account
                    </a>
                    <a href="/BeBox2/promo.php" class="<?= $currentPage === 'promo.php' ? 'active' : '' ?>">
                        <i class="fas fa-tags"></i> Promo
                    </a>
                    <a href="/BeBox2/history.php" class="<?= $currentPage === 'history.php' ? 'active' : '' ?>">
                        <i class="fas fa-receipt"></i> Transaction History
                    </a>
                    <?php if (isAdmin()): ?>
                    <hr class="menu-divider">
                    <a href="/BeBox2/admin/">
                        <i class="fas fa-shield-halved"></i> Admin Panel
                    </a>
                    <?php endif; ?>
                    <hr class="menu-divider">
                    <a href="/BeBox2/logout.php" class="logout-link">
                        <i class="fas fa-right-from-bracket"></i> Logout
                    </a>
                <?php else: ?>
                    <a href="/BeBox2/index.php"><i class="fas fa-right-to-bracket"></i> Login</a>
                    <a href="/BeBox2/register.php"><i class="fas fa-user-plus"></i> Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
<div class="navbar-spacer"></div>

<script>
// Auto-close menu on outside click
document.addEventListener('click', function(e) {
    const toggle = document.getElementById('menu-toggle');
    const nav    = document.querySelector('.nav-container');
    if (toggle && toggle.checked && nav && !nav.contains(e.target)) {
        toggle.checked = false;
    }
});
</script>
