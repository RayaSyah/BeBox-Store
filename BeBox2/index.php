<?php
// index.php — Halaman Login BeBox2
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    redirect(BASE_URL . '/dashboard.php');
}

$error   = '';
$success = '';

// Cek flash message dari register
if (isset($_SESSION['flash'])) {
    $f = getFlash();
    if ($f && $f['type'] === 'success') $success = $f['message'];
}

// ─── POST: Proses Login ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, password, role FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            // Set session
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'admin') {
                redirect(BASE_URL . '/admin/');
            } else {
                redirect(BASE_URL . '/hello-animation.php');
            }
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeBox — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* ── Embedded CSS (index.php) — based on index.css + Apple redesign ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: url('Picture/login.jpeg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
        -webkit-font-smoothing: antialiased;
    }
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }
    .login-wrapper {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 420px;
        padding: 20px;
        animation: fadeUp 0.5s ease;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .login-container {
        background: rgba(20, 20, 20, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 20px;
        padding: 44px 36px;
        box-shadow: 0 24px 64px rgba(0,0,0,0.5);
    }
    .login-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 24px;
        font-weight: 800;
        letter-spacing: -0.5px;
    }
    .login-logo svg { width: 28px; height: 28px; fill: #fff; }
    .login-sub {
        text-align: center;
        color: rgba(255,255,255,0.55);
        font-size: 14px;
        margin-bottom: 32px;
    }
    h2 { text-align: center; font-size: 26px; font-weight: 700; margin-bottom: 6px; letter-spacing: -0.3px; }
    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        margin-bottom: 20px;
        display: flex;
        align-items: flex-start;
        gap: 8px;
        line-height: 1.4;
    }
    .alert-error   { background: rgba(255,65,54,0.15); border: 1px solid rgba(255,65,54,0.3); color: #ff8a80; }
    .alert-success { background: rgba(52,199,89,0.15); border: 1px solid rgba(52,199,89,0.3); color: #69db7c; }
    .input-group { margin-bottom: 18px; }
    .input-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.6);
        margin-bottom: 8px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-group input {
        width: 100%;
        padding: 13px 16px;
        background: rgba(255,255,255,0.1);
        border: 1.5px solid rgba(255,255,255,0.15);
        border-radius: 12px;
        color: #fff;
        font-size: 15px;
        font-family: inherit;
        outline: none;
        transition: border-color 0.3s, background 0.3s;
    }
    .input-group input:focus {
        border-color: rgba(255,255,255,0.5);
        background: rgba(255,255,255,0.14);
    }
    .input-group input::placeholder { color: rgba(255,255,255,0.35); }
    .input-group input:invalid:not(:placeholder-shown) + .validation-message { display: block; }
    .validation-message {
        display: none;
        color: #ff8a80;
        font-size: 12px;
        margin-top: 6px;
    }
    .submit-btn {
        width: 100%;
        padding: 14px;
        background: #fff;
        color: #000;
        border: none;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.3s, transform 0.2s;
        margin-top: 8px;
        letter-spacing: 0.2px;
    }
    .submit-btn:hover { background: #e5e5e5; transform: translateY(-1px); }
    .submit-btn:active { transform: translateY(0); }
    .register-text {
        text-align: center;
        margin-top: 22px;
        font-size: 14px;
        color: rgba(255,255,255,0.55);
    }
    .register-text a {
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px solid rgba(255,255,255,0.4);
        padding-bottom: 1px;
        transition: border-color 0.3s;
    }
    .register-text a:hover { border-color: #fff; }
    @media (max-width: 480px) {
        .login-container { padding: 36px 24px; border-radius: 16px; }
    }
    </style>
</head>
<body>
<div class="login-wrapper">
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-box" style="font-size:22px;"></i> BeBox
        </div>
        <p class="login-sub">Sign in to your account</p>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠️ <?= sanitize($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success">✅ <?= sanitize($success) ?></div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/index.php" method="POST" novalidate>
            <div class="input-group">
                <label for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    value="<?= isset($_POST['email']) ? sanitize($_POST['email']) : '' ?>"
                    required
                    autocomplete="email"
                >
                <small class="validation-message">Please enter a valid email address.</small>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                    minlength="6"
                    autocomplete="current-password"
                >
                <small class="validation-message">Password must be at least 6 characters.</small>
            </div>
            <button type="submit" class="submit-btn">Sign In</button>
        </form>

        <p class="register-text">
            Don't have an account? <a href="<?= BASE_URL ?>/register.php">Create an account</a>
        </p>
    </div>
</div>
</body>
</html>
