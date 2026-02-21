<?php
// profile.php — Halaman profil user
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

$userId = (int)$_SESSION['user_id'];
$errors  = [];
$success = '';

// Ambil data user terbaru
$stmt = $conn->prepare("SELECT id, username, email, phone, address, avatar, created_at FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $userId);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Hitung total transaksi
$txStmt = $conn->prepare("SELECT COUNT(*) as total FROM transactions WHERE user_id = ?");
$txStmt->bind_param('i', $userId);
$txStmt->execute();
$txCount = $txStmt->get_result()->fetch_assoc()['total'];
$txStmt->close();

// ─── POST: Update Profil ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $newUsername = sanitize($_POST['username'] ?? '');
        $newPhone    = sanitize($_POST['phone'] ?? '');
        $newAddress  = sanitize($_POST['address'] ?? '');

        if (strlen($newUsername) < 3) $errors[] = 'Username must be at least 3 characters.';

        if (empty($errors)) {
            $upd = $conn->prepare("UPDATE users SET username = ?, phone = ?, address = ? WHERE id = ?");
            $upd->bind_param('sssi', $newUsername, $newPhone, $newAddress, $userId);
            if ($upd->execute()) {
                $_SESSION['username'] = $newUsername;
                $success = 'Profile updated successfully!';
                $user['username'] = $newUsername;
                $user['phone']    = $newPhone;
                $user['address']  = $newAddress;
            } else {
                $errors[] = 'Failed to save changes.';
            }
            $upd->close();
        }
    }

    if ($action === 'change_password') {
        $oldPass = $_POST['old_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confPass= $_POST['confirm_password'] ?? '';

        if (strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';
        if ($newPass !== $confPass) $errors[] = 'Password confirmation does not match.';

        if (empty($errors)) {
            $chk = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $chk->bind_param('i', $userId);
            $chk->execute();
            $row = $chk->get_result()->fetch_assoc();
            $chk->close();

            if (!password_verify($oldPass, $row['password'])) {
                $errors[] = 'Old password is incorrect.';
            } else {
                $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                $upd    = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param('si', $hashed, $userId);
                if ($upd->execute()) {
                    $success = 'Password changed successfully!';
                } else {
                    $errors[] = 'Failed to change password.';
                }
                $upd->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Profile</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <style>
    .profile-section { margin-bottom: 28px; }
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    @media(max-width:640px) { .two-col { grid-template-columns: 1fr; } }
    .stat-mini {
        display: flex;
        gap: 20px;
        margin-top: 12px;
    }
    .stat-mini-item { text-align: center; }
    .stat-mini-item .val { font-size: 24px; font-weight: 800; color: #1d1d1f; }
    .stat-mini-item .lbl { font-size: 12px; color: #6e6e73; }

    /* ── Avatar Upload UI ── */
    .avatar-wrap {
        position: relative;
        width: 88px;
        height: 88px;
        flex-shrink: 0;
        cursor: pointer;
    }
    .avatar-wrap:hover .avatar-overlay { opacity: 1; }
    .avatar-img {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        object-fit: cover;
        display: block;
    }
    .avatar-icon-wrap {
        width: 88px;
        height: 88px;
        border-radius: 50%;
        background: #1d1d1f;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 36px;
    }
    .avatar-overlay {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0,0,0,0.55);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.25s ease;
        color: #fff;
        font-size: 11px;
        font-weight: 600;
        gap: 4px;
        letter-spacing: 0.3px;
    }
    .avatar-overlay i { font-size: 18px; }
    #avatarFileInput { display: none; }

    /* Upload spinner */
    .avatar-spinner {
        position: absolute;
        inset: 0;
        border-radius: 50%;
        background: rgba(0,0,0,0.65);
        display: none;
        align-items: center;
        justify-content: center;
    }
    .avatar-spinner.show { display: flex; }
    .spinner-ring {
        width: 30px; height: 30px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: #fff;
        border-radius: 50%;
        animation: spin 0.7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Toast for avatar feedback */
    #avatarToast {
        position: fixed;
        bottom: 28px;
        left: 50%;
        transform: translateX(-50%) translateY(20px);
        background: #1d1d1f;
        color: #fff;
        padding: 12px 24px;
        border-radius: 50px;
        font-size: 14px;
        font-weight: 500;
        opacity: 0;
        transition: opacity 0.3s, transform 0.3s;
        pointer-events: none;
        z-index: 9999;
        white-space: nowrap;
    }
    #avatarToast.show {
        opacity: 1;
        transform: translateX(-50%) translateY(0);
    }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="page-container">
    <?php showFlash(); ?>
    <?php if ($success): ?>
        <div class="flash-message flash-success">✅ <?= sanitize($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="flash-message flash-error">⚠️ <?= implode(' | ', array_map('sanitize', $errors)) ?></div>
    <?php endif; ?>

    <!-- Profile Header -->
    <div class="profile-header">

        <!-- Avatar — click to upload -->
        <div class="avatar-wrap" id="avatarWrap" title="Klik untuk ganti foto profil">
            <?php if (!empty($user['avatar']) && file_exists(__DIR__ . '/' . $user['avatar'])): ?>
                <img id="avatarDisplay" class="avatar-img"
                     src="<?= BASE_URL . '/' . sanitize($user['avatar']) ?>"
                     alt="Profile picture <?= sanitize($user['username']) ?>">
            <?php else: ?>
                <div class="avatar-icon-wrap" id="avatarIconWrap">
                    <i class="fas fa-user"></i>
                </div>
                <img id="avatarDisplay" class="avatar-img" src="" alt="" style="display:none;">
            <?php endif; ?>

            <div class="avatar-overlay" title="Click to change profile picture">
                <i class="fas fa-camera"></i>
                <span>Change</span>
            </div>
            <div class="avatar-spinner" id="avatarSpinner">
                <div class="spinner-ring"></div>
            </div>
        </div>

        <!-- Hidden file input -->
        <input type="file" id="avatarFileInput" accept="image/jpeg,image/png,image/gif,image/webp">

        <div class="profile-info">
            <h2><?= sanitize($user['username']) ?></h2>
            <p><?= sanitize($user['email']) ?></p>
            <p style="font-size:12px;color:#aeaeb2;margin-top:4px;">
                Joined <?= date('d M Y', strtotime($user['created_at'])) ?>
            </p>
            <div class="stat-mini">
                <div class="stat-mini-item">
                    <div class="val"><?= $txCount ?></div>
                    <div class="lbl">Transactions</div>
                </div>
            </div>
        </div>
    </div>

    <div class="two-col">
        <!-- Edit Profil -->
        <div class="card profile-section">
            <div class="card-body">
                <h3 style="font-size:18px;font-weight:700;margin-bottom:20px;">Edit Profile</h3>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="update_profile">
                    <div class="form-group">
                        <label>Username</label>
                        <input class="form-control" type="text" name="username"
                            value="<?= sanitize($user['username']) ?>"
                            required minlength="3">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input class="form-control" type="email" value="<?= sanitize($user['email']) ?>" disabled>
                        <small style="color:#aeaeb2;font-size:12px;">Email cannot be changed.</small>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input class="form-control" type="tel" name="phone"
                            value="<?= sanitize($user['phone'] ?? '') ?>"
                            placeholder="+1 555 000 0000">
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <textarea class="form-control" name="address" rows="3"
                            placeholder="Enter your full address"><?= sanitize($user['address'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <!-- Ganti Password -->
        <div class="card profile-section">
            <div class="card-body">
                <h3 style="font-size:18px;font-weight:700;margin-bottom:20px;">Change Password</h3>
                <form method="POST" novalidate>
                    <input type="hidden" name="action" value="change_password">
                    <div class="form-group">
                        <label>Old Password</label>
                        <input class="form-control" type="password" name="old_password"
                            required placeholder="Enter old password">
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input class="form-control" type="password" name="new_password"
                            required minlength="6" placeholder="At least 6 characters">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input class="form-control" type="password" name="confirm_password"
                            required minlength="6" placeholder="Repeat new password">
                    </div>
                    <button type="submit" class="btn btn-outline" style="width:100%;">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div style="text-align:center;margin-top:12px;">
        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Toast notification -->
<div id="avatarToast"></div>

<script>
(function() {
    const wrap    = document.getElementById('avatarWrap');
    const input   = document.getElementById('avatarFileInput');
    const spinner = document.getElementById('avatarSpinner');
    const display = document.getElementById('avatarDisplay');
    const iconWrap = document.getElementById('avatarIconWrap');
    const toast   = document.getElementById('avatarToast');

    // Click avatar → open file picker
    wrap.addEventListener('click', function() {
        input.click();
    });

    // File chosen → upload
    input.addEventListener('change', function() {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        // Validate client-side
        const allowed = ['image/jpeg','image/jpg','image/png','image/gif','image/webp'];
        if (!allowed.includes(file.type)) {
            showToast('❌ File must be an image (JPG, PNG, GIF, WEBP)', true);
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            showToast('❌ Maximum file size is 5MB', true);
            return;
        }

        // Show spinner
        spinner.classList.add('show');

        const fd = new FormData();
        fd.append('avatar', file);

        fetch('<?= BASE_URL ?>/upload-avatar.php', {
            method: 'POST',
            body: fd,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            spinner.classList.remove('show');
            if (data.success) {
                // Update avatar display
                display.src = data.path + '?v=' + Date.now();
                display.style.display = 'block';
                if (iconWrap) iconWrap.style.display = 'none';
                showToast('✅ Profile picture updated successfully!', false);
            } else {
                showToast('❌ ' + (data.message || 'Upload gagal'), true);
            }
        })
        .catch(function() {
            spinner.classList.remove('show');
            showToast('❌ Something went wrong, please try again.', true);
        });

        // Reset input so same file can be re-selected
        input.value = '';
    });

    function showToast(msg, isError) {
        toast.textContent = msg;
        toast.style.background = isError ? '#c0392b' : '#1d1d1f';
        toast.classList.add('show');
        setTimeout(function() { toast.classList.remove('show'); }, 3000);
    }
})();
</script>
</body>
</html>
