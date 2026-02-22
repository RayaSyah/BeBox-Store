<?php
// ─── Session Start ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Auth Helpers ────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $url = $redirect ?: (defined('BASE_URL') ? BASE_URL . '/index.php' : '/index.php');
        redirect($url);
    }
}

function requireAdmin(string $redirect = ''): void {
    if (!isAdmin()) {
        $url = $redirect ?: (defined('BASE_URL') ? BASE_URL . '/index.php' : '/index.php');
        redirect($url);
    }
}

// ─── Redirect ────────────────────────────────────────────────────
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ─── Sanitize Input ──────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// ─── Format Price ────────────────────────────────────────────────
function formatPrice(float $price): string {
    return '$' . number_format($price, 2);
}

// ─── Generate Receipt Number ─────────────────────────────────────
function generateReceiptNo(int $transactionId): string {
    return 'BBX-' . date('Ymd') . '-' . str_pad($transactionId, 5, '0', STR_PAD_LEFT);
}

// ─── Flash Messages ──────────────────────────────────────────────
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function showFlash(): void {
    $flash = getFlash();
    if ($flash) {
        $type = $flash['type']; // success | error | warning
        $msg  = sanitize($flash['message']);
        echo "<div class=\"flash-message flash-{$type}\">{$msg}</div>";
    }
}

// ─── Image Path Helper ───────────────────────────────────────────
function getProductImageUrl(string $imagePath): string {
    return BASE_URL . '/' . ltrim($imagePath, '/');
}

// ─── Time Ago ────────────────────────────────────────────────────
function timeAgo(string $datetime): string {
    $now  = new DateTime();
    $past = new DateTime($datetime);
    $diff = $now->diff($past);

    if ($diff->y > 0) return $diff->y . ' tahun lalu';
    if ($diff->m > 0) return $diff->m . ' bulan lalu';
    if ($diff->d > 0) return $diff->d . ' hari lalu';
    if ($diff->h > 0) return $diff->h . ' jam lalu';
    if ($diff->i > 0) return $diff->i . ' menit lalu';
    return 'Baru saja';
}
?>
