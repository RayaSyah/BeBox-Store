<?php
/**
 * BeBox2 — Fix Admin Password
 * Kunjungi sekali: http://localhost/BeBox2/fix-admin.php
 * HAPUS file ini setelah berhasil!
 */

require_once __DIR__ . '/config/database.php';

$newPassword = password_hash('123admin', PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = 'admin23@gmail.com'");
$stmt->bind_param('s', $newPassword);
$stmt->execute();
$affected = $stmt->affected_rows;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Fix Admin Password</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { background: #000; color: #fff; font-family: -apple-system, sans-serif;
         display: flex; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
  .card { background: #111; border: 1px solid #222; border-radius: 16px; padding: 40px; max-width: 500px; width: 100%; }
  h1 { font-size: 22px; margin-bottom: 16px; }
  .ok  { color: #34c759; font-size: 15px; margin-bottom: 20px; }
  .err { color: #ff453a; font-size: 15px; margin-bottom: 20px; }
  a { display: inline-block; padding: 12px 24px; background: #fff; color: #000;
      border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 14px; margin-top: 8px; }
  a:hover { background: #e0e0e0; }
  code { background: #1e1e1e; padding: 4px 8px; border-radius: 6px; font-size: 13px; color: #fff; }
  .warn { background: #1a1000; border: 1px solid #443300; border-radius: 8px;
          padding: 14px; margin-top: 20px; color: #ffcc00; font-size: 13px; }
</style>
</head>
<body>
<div class="card">
  <h1>🔧 Fix Admin Password</h1>

  <?php if ($affected > 0): ?>
    <p class="ok">✅ Password admin berhasil diperbarui!</p>
    <p style="font-size:14px;color:#8e8e93;margin-bottom:8px;">Sekarang kamu bisa login dengan:</p>
    <p style="font-size:14px;margin-bottom:4px;">📧 Email: <code>admin23@gmail.com</code></p>
    <p style="font-size:14px;margin-bottom:20px;">🔑 Password: <code>123admin</code></p>
    <a href="/BeBox2/index.php">→ Pergi ke Login</a>
  <?php elseif ($affected === 0): ?>
    <p class="err">⚠️ Tidak ada perubahan. Akun admin mungkin belum ada di database.</p>
    <p style="font-size:14px;color:#8e8e93;margin-bottom:16px;">Coba jalankan <a href="/BeBox2/install.php" style="background:none;color:#0071e3;padding:0;font-weight:500;">install.php</a> terlebih dahulu.</p>
  <?php else: ?>
    <p class="err">❌ Terjadi error saat update password.</p>
  <?php endif; ?>

  <div class="warn">⚠️ <strong>Penting:</strong> Hapus file <code>fix-admin.php</code> setelah selesai untuk keamanan!</div>
</div>
</body>
</html>
