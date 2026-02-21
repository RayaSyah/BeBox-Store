<?php
// admin/products.php — CRUD Produk + Upload Gambar
$adminPage = true;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireAdmin(BASE_URL . '/index.php');

$errors  = [];
$success = '';
$editProduct = null;

// Pastikan folder uploads ada
$uploadsDir = __DIR__ . '/../uploads/';
if (!is_dir($uploadsDir)) mkdir($uploadsDir, 0755, true);

// ─── POST: Tambah / Edit / Hapus ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $prodId  = (int)($_POST['product_id'] ?? 0);
    $name    = sanitize($_POST['name'] ?? '');
    $desc    = sanitize($_POST['description'] ?? '');
    $price   = (float)($_POST['price'] ?? 0);
    $stock   = (int)($_POST['stock'] ?? 0);
    $isActive= (int)(isset($_POST['is_active']));

    if ($action === 'delete' && $prodId) {
        $del = $conn->prepare("DELETE FROM products WHERE id = ?");
        $del->bind_param('i', $prodId);
        $del->execute();
        $del->close();
        setFlash('success', 'Product deleted successfully.');
        redirect(BASE_URL . '/admin/products.php');
    }

    if (in_array($action, ['add', 'edit'])) {
        if (empty($name))  $errors[] = 'Product name is required.';
        if ($price <= 0)   $errors[] = 'Price must be greater than 0.';
        if ($stock < 0)    $errors[] = 'Stock cannot be negative.';

        // Handle upload gambar
        $imagePath = $_POST['existing_image'] ?? '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file     = $_FILES['image'];
            $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $maxSize  = 5 * 1024 * 1024; // 5MB

            if (!in_array($ext, $allowed)) $errors[] = 'Invalid image format (jpg, png, gif, webp).';
            elseif ($file['size'] > $maxSize) $errors[] = 'Maximum image size is 5MB.';
            else {
                $newName  = 'prod_' . time() . '_' . uniqid() . '.' . $ext;
                $destPath = $uploadsDir . $newName;
                if (move_uploaded_file($file['tmp_name'], $destPath)) {
                    $imagePath = 'uploads/' . $newName;
                } else {
                    $errors[] = 'Failed to upload image.';
                }
            }
        }

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, stock, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('ssdsis', $name, $desc, $price, $imagePath, $stock, $isActive);
                if ($stmt->execute()) $success = 'Product added successfully!';
                else $errors[] = 'Failed to save product.';
                $stmt->close();
            } elseif ($action === 'edit' && $prodId) {
                $stmt = $conn->prepare("UPDATE products SET name=?, description=?, price=?, image=?, stock=?, is_active=? WHERE id=?");
                $stmt->bind_param('ssdssii', $name, $desc, $price, $imagePath, $stock, $isActive, $prodId);
                if ($stmt->execute()) $success = 'Product updated successfully!';
                else $errors[] = 'Failed to update product.';
                $stmt->close();
            }
            if ($success) redirect(BASE_URL . '/admin/products.php?msg=' . urlencode($success));
        }
    }
}

// Cek redirect message
if (isset($_GET['msg'])) $success = sanitize($_GET['msg']);

// Edit mode: ambil data produk
if (isset($_GET['edit'])) {
    $editId  = (int)$_GET['edit'];
    $eStmt   = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
    $eStmt->bind_param('i', $editId);
    $eStmt->execute();
    $editProduct = $eStmt->get_result()->fetch_assoc();
    $eStmt->close();
}

// Ambil semua produk
$products = $conn->query("SELECT * FROM products ORDER BY created_at DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox Admin — Products</title>
    <?php include __DIR__ . '/../includes/head.php'; ?>
    <style>
    .form-card { background:#fff;border:1px solid #e5e5e5;border-radius:16px;padding:28px;margin-bottom:28px; }
    .three-col { display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px; }
    @media(max-width:640px) { .three-col { grid-template-columns:1fr; } }
    .product-thumb { width:48px;height:48px;object-fit:cover;border-radius:8px; }
    .img-preview { max-width:120px;border-radius:10px;margin-top:8px; }
    .toggle-form-btn { margin-bottom:16px; }
    </style>
    <script src="<?= BASE_URL ?>/assets/js/main.js" defer></script>
</head>
<body>
<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
<div class="admin-container">
    <?php if ($success): ?><div class="flash-message flash-success">✅ <?= sanitize($success) ?></div><?php endif; ?>
    <?php if (!empty($errors)): ?><div class="flash-message flash-error">⚠️ <?= implode(' | ', array_map('sanitize', $errors)) ?></div><?php endif; ?>

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h1 class="section-title">Product Management</h1>
            <p class="section-subtitle">Add, edit, and remove blind box products.</p>
        </div>
        <button onclick="document.getElementById('formPanel').classList.toggle('hidden')" class="btn btn-primary toggle-form-btn">
            <i class="fas fa-plus"></i> Add Product
        </button>
    </div>

    <!-- Form Tambah / Edit -->
    <div id="formPanel" class="form-card <?= ($editProduct || !empty($errors)) ? '' : 'hidden' ?>" style="">
        <h3 style="font-size:17px;font-weight:700;margin-bottom:20px;">
            <?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?>
        </h3>
        <form method="POST" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="<?= $editProduct ? 'edit' : 'add' ?>">
            <?php if ($editProduct): ?>
            <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
            <input type="hidden" name="existing_image" value="<?= sanitize($editProduct['image']) ?>">
            <?php endif; ?>

            <div class="form-group">
                <label>Product Name *</label>
                <input class="form-control" type="text" name="name"
                    value="<?= sanitize($editProduct['name'] ?? '') ?>"
                    required placeholder="Blind box product name">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="4"
                    placeholder="Product description..."><?= sanitize($editProduct['description'] ?? '') ?></textarea>
            </div>
            <div class="three-col">
                <div class="form-group">
                    <label>Price ($) *</label>
                    <input class="form-control" type="number" name="price" step="0.01" min="0.01"
                        value="<?= $editProduct['price'] ?? '' ?>" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Stock *</label>
                    <input class="form-control" type="number" name="stock" min="0"
                        value="<?= $editProduct['stock'] ?? '0' ?>" required>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select class="form-control" name="is_active">
                        <option value="1" <?= (!$editProduct || $editProduct['is_active']) ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= ($editProduct && !$editProduct['is_active']) ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Product Image <?= $editProduct ? '(optional, replace image)' : '' ?></label>
                <?php if ($editProduct && $editProduct['image']): ?>
                <div style="margin-bottom:8px;">
                    <img src="<?= BASE_URL . '/' . sanitize($editProduct['image']) ?>" class="img-preview" alt="Current">
                    <p style="font-size:12px;color:#aeaeb2;margin-top:4px;">Current image</p>
                </div>
                <?php endif; ?>
                <input class="form-control" type="file" name="image" accept="image/*" onchange="previewImg(this)">
                <img id="imgPreview" class="img-preview" style="display:none;">
                <small style="color:#aeaeb2;font-size:12px;">Format: JPG, PNG, GIF, WebP. Max 5MB.</small>
            </div>

            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
                <a href="<?= BASE_URL ?>/admin/products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>

    <!-- Tabel Produk -->
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Products</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:#aeaeb2;">No products found</td></tr>
                <?php else: ?>
                <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:12px;">
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($p['image']) ?>"
                                 class="product-thumb" alt="<?= sanitize($p['name']) ?>"
                                 onerror="this.style.display='none'">
                            <div>
                                <div style="font-weight:700;font-size:14px;"><?= sanitize($p['name']) ?></div>
                                <div style="font-size:12px;color:#aeaeb2;margin-top:2px;">ID: <?= $p['id'] ?></div>
                            </div>
                        </div>
                    </td>
                    <td style="font-weight:700;"><?= formatPrice($p['price']) ?></td>
                    <td>
                        <span style="font-weight:700;color:<?= $p['stock'] <= 5 ? '#ef4444' : ($p['stock'] <= 10 ? '#f59e0b' : '#22c55e') ?>;">
                            <?= $p['stock'] ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-<?= $p['is_active'] ? 'active' : 'inactive' ?>">
                            <?= $p['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:8px;">
                            <a href="?edit=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">
                                <i class="fas fa-pen"></i>
                            </a>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm"
                                    data-confirm="Delete product '<?= sanitize($p['name']) ?>'? Related transactions will not be deleted.">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function previewImg(input) {
    const preview = document.getElementById('imgPreview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}
// Show form jika ada edit param
<?php if ($editProduct): ?>
document.getElementById('formPanel').classList.remove('hidden');
<?php endif; ?>
</script>
<style>.hidden { display: none !important; }</style>
</body>
</html>
