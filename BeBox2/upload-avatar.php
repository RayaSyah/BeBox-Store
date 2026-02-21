<?php
// upload-avatar.php — handles profile picture upload
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

header('Content-Type: application/json');

$userId = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$file    = $_FILES['avatar'];
$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$maxSize = 5 * 1024 * 1024; // 5MB

// Validate
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error.']);
    exit;
}
if (!in_array($file['type'], $allowed)) {
    echo json_encode(['success' => false, 'message' => 'File must be an image (JPG, PNG, GIF, WEBP).']);
    exit;
}
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'Maximum file size is 5MB.']);
    exit;
}

// Destination
$uploadDir  = __DIR__ . '/uploads/avatars/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

// Delete old avatar
$chk = $conn->prepare("SELECT avatar FROM users WHERE id = ? LIMIT 1");
$chk->bind_param('i', $userId);
$chk->execute();
$old = $chk->get_result()->fetch_assoc();
$chk->close();
if (!empty($old['avatar'])) {
    $oldFile = __DIR__ . '/' . $old['avatar'];
    if (file_exists($oldFile)) @unlink($oldFile);
}

// Save new file
$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'avatar_' . $userId . '_' . time() . '.' . strtolower($ext);
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    exit;
}

$dbPath = 'uploads/avatars/' . $filename;

$upd = $conn->prepare("UPDATE users SET avatar = ? WHERE id = ?");
$upd->bind_param('si', $dbPath, $userId);
$upd->execute();
$upd->close();

echo json_encode(['success' => true, 'path' => BASE_URL . '/' . $dbPath]);
