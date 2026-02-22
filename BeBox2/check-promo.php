<?php
// check-promo.php — AJAX endpoint: validasi kode promo
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

// Harus login
if (!isLoggedIn()) {
    echo json_encode(['valid' => false, 'message' => 'Please log in first.']);
    exit;
}

$code = strtoupper(trim($_GET['code'] ?? ''));

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Kode promo tidak boleh kosong.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT code, discount_percent, description
    FROM promos
    WHERE code = ? AND is_active = 1
      AND (valid_until IS NULL OR valid_until >= CURDATE())
    LIMIT 1
");
$stmt->bind_param('s', $code);
$stmt->execute();
$promo = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($promo) {
    echo json_encode([
        'valid'            => true,
        'code'             => $promo['code'],
        'discount_percent' => (float)$promo['discount_percent'],
        'description'      => $promo['description'],
    ]);
} else {
    echo json_encode([
        'valid'   => false,
        'message' => 'Kode promo tidak valid atau sudah kadaluarsa.',
    ]);
}
