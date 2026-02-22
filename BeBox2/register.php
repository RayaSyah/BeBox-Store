<?php
// register.php — Halaman Register BeBox2
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isLoggedIn()) redirect(BASE_URL . '/dashboard.php');

$errors  = [];
$success = '';
$form    = ['username' => '', 'email' => ''];

// ─── POST: Proses Register ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username  = sanitize($_POST['username'] ?? '');
    $email     = sanitize($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    // Validasi PHP
    if (strlen($username) < 3)    $errors[] = 'Username must be at least 3 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 6)    $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)   $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Cek email duplikat
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $check->bind_param('s', $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $errors[] = 'This email is already registered. Please use a different one.';
        } else {
            // Insert user baru
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt->bind_param('sss', $username, $email, $hashed);
            if ($stmt->execute()) {
                setFlash('success', 'Account created successfully! Please sign in.');
                redirect(BASE_URL . '/index.php');
            } else {
                $errors[] = 'Something went wrong. Please try again.';
            }
            $stmt->close();
        }
        $check->close();
    }

    $form = ['username' => $username, 'email' => $email];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeBox — Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
    /* ── Embedded CSS (register.php) — based on register.css + Apple redesign ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background-image: url('Picture/register.jpeg');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        color: #fff;
        -webkit-font-smoothing: antialiased;
        padding: 20px 0;
    }
    body::before {
        content: '';
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.55);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }
    .register-wrapper {
        position: relative;
        z-index: 1;
        width: 100%;
        max-width: 440px;
        padding: 20px;
        animation: fadeUp 0.5s ease;
    }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(20px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .register-container {
        background: rgba(20, 20, 20, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.12);
        border-radius: 20px;
        padding: 44px 36px;
        box-shadow: 0 24px 64px rgba(0,0,0,0.5);
    }
    .register-logo {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 8px;
        font-size: 24px;
        font-weight: 800;
    }
    .register-sub {
        text-align: center;
        color: rgba(255,255,255,0.55);
        font-size: 14px;
        margin-bottom: 28px;
    }
    .alert {
        padding: 12px 16px;
        border-radius: 10px;
        font-size: 13px;
        margin-bottom: 18px;
        border: 1px solid rgba(255,65,54,0.3);
        background: rgba(255,65,54,0.12);
        color: #ff8a80;
    }
    .alert ul { padding-left: 16px; }
    .alert li { margin-bottom: 4px; }
    .input-group { margin-bottom: 16px; }
    .input-group label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: rgba(255,255,255,0.6);
        margin-bottom: 7px;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .input-group input {
        width: 100%;
        padding: 12px 15px;
        background: rgba(255,255,255,0.1);
        border: 1.5px solid rgba(255,255,255,0.15);
        border-radius: 11px;
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
    .input-group input::placeholder { color: rgba(255,255,255,0.32); }
    .input-group input:invalid:not(:placeholder-shown) + .validation-message { display: block; }
    .validation-message {
        display: none;
        color: #ff8a80;
        font-size: 12px;
        margin-top: 5px;
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
    }
    .submit-btn:hover { background: #e5e5e5; transform: translateY(-1px); }
    .login-text {
        text-align: center;
        margin-top: 20px;
        font-size: 14px;
        color: rgba(255,255,255,0.55);
    }
    .login-text a {
        color: #fff;
        font-weight: 600;
        text-decoration: none;
        border-bottom: 1px solid rgba(255,255,255,0.4);
        padding-bottom: 1px;
        transition: border-color 0.3s;
    }
    .login-text a:hover { border-color: #fff; }
    @media (max-width: 480px) {
        .register-container { padding: 32px 20px; }
    }
    </style>
</head>
<body>
<div class="register-wrapper">
    <div class="register-container">
        <div class="register-logo">
            <i class="fas fa-box" style="font-size:22px;"></i> BeBox
        </div>
        <p class="register-sub">Create your account</p>

        <?php if (!empty($errors)): ?>
        <div class="alert">
            <ul>
                <?php foreach ($errors as $err): ?>
                    <li><?= sanitize($err) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="<?= BASE_URL ?>/register.php" method="POST" novalidate>
            <div class="input-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    placeholder="Choose a username"
                    value="<?= sanitize($form['username']) ?>"
                    required minlength="3" autocomplete="username">
                <small class="validation-message">Username must be at least 3 characters.</small>
            </div>
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email"
                    placeholder="Enter your email"
                    value="<?= sanitize($form['email']) ?>"
                    required autocomplete="email">
                <small class="validation-message">Please enter a valid email address.</small>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    placeholder="Create a password"
                    required minlength="6" autocomplete="new-password">
                <small class="validation-message">Password must be at least 6 characters.</small>
            </div>
            <div class="input-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password"
                    placeholder="Confirm your password"
                    required minlength="6" autocomplete="new-password">
                <small class="validation-message">Passwords must match.</small>
            </div>
            <button type="submit" class="submit-btn">Create Account</button>
        </form>

        <p class="login-text">
            Already have an account? <a href="<?= BASE_URL ?>/index.php">Sign In</a>
        </p>
    </div>
</div>
</body>
</html>
