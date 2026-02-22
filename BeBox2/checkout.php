<?php
// checkout.php — Halaman checkout sebelum konfirmasi pembelian
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/functions/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();
requireLogin(BASE_URL . '/index.php');

// Ambil product_id dari GET
$productId = (int)($_GET['product_id'] ?? 0);
if ($productId <= 0) {
    setFlash('error', 'Invalid product.');
    redirect(BASE_URL . '/dashboard.php');
}

// Cek produk ada & aktif
$pStmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
$pStmt->bind_param('i', $productId);
$pStmt->execute();
$product = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(BASE_URL . '/dashboard.php');
}

// Ambil semua promo aktif untuk ditampilkan
$activePromos = $conn->query("
    SELECT code, discount_percent, description FROM promos
    WHERE is_active = 1 AND (valid_until IS NULL OR valid_until >= CURDATE())
    ORDER BY discount_percent DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BeBox — Checkout</title>
    <?php include __DIR__ . '/includes/head.php'; ?>
    <style>
    /* ── Checkout Page ── */
    .checkout-wrapper {
        max-width: 700px;
        margin: 40px auto;
        padding: 0 20px 60px;
    }
    .checkout-title {
        font-size: 28px;
        font-weight: 700;
        color: #1d1d1f;
        margin-bottom: 6px;
        letter-spacing: -0.5px;
    }
    .checkout-sub {
        font-size: 15px;
        color: #6e6e73;
        margin-bottom: 32px;
    }

    /* Product Card */
    .product-card {
        display: flex;
        gap: 20px;
        align-items: flex-start;
        background: #fff;
        border: 1px solid #e5e5ea;
        border-radius: 18px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }
    .product-card img {
        width: 110px;
        height: 110px;
        object-fit: contain;
        border-radius: 12px;
        flex-shrink: 0;
        background: #fff;
    }
    .product-card-info { flex: 1; }
    .product-card-name {
        font-size: 18px;
        font-weight: 700;
        color: #1d1d1f;
        margin-bottom: 6px;
    }
    .product-card-desc {
        font-size: 13px;
        color: #6e6e73;
        margin-bottom: 12px;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .product-card-price {
        font-size: 22px;
        font-weight: 700;
        color: #1d1d1f;
    }

    /* Order Form */
    .order-form {
        background: #fff;
        border: 1px solid #e5e5ea;
        border-radius: 18px;
        padding: 28px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.05);
    }
    .form-section-title {
        font-size: 13px;
        font-weight: 700;
        color: #6e6e73;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 16px;
    }
    .form-group { margin-bottom: 20px; }
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #1d1d1f;
        margin-bottom: 8px;
    }
    .qty-input {
        width: 100px;
        padding: 10px 14px;
        border: 1.5px solid #d1d1d6;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        font-family: inherit;
        color: #1d1d1f;
        background: #f5f5f7;
        outline: none;
        transition: border-color 0.2s;
    }
    .qty-input:focus { border-color: #0071e3; background: #fff; }
    .stock-note { font-size: 12px; color: #aeaeb2; margin-top: 5px; }

    /* Promo Section */
    .promo-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .promo-code-input {
        flex: 1;
        padding: 12px 16px;
        border: 1.5px solid #d1d1d6;
        border-radius: 12px;
        font-size: 15px;
        font-weight: 700;
        letter-spacing: 2px;
        font-family: inherit;
        color: #1d1d1f;
        background: #f5f5f7;
        outline: none;
        text-transform: uppercase;
        transition: border-color 0.2s, background 0.2s;
    }
    .promo-code-input:focus { border-color: #0071e3; background: #fff; }
    .promo-code-input::placeholder {
        letter-spacing: 0;
        font-weight: 400;
        color: #aeaeb2;
        font-size: 14px;
    }
    .apply-btn {
        padding: 12px 20px;
        background: #1d1d1f;
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 600;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.2s;
        white-space: nowrap;
    }
    .apply-btn:hover { background: #3a3a3c; }
    .promo-feedback {
        margin-top: 10px;
        font-size: 13px;
        min-height: 18px;
        font-weight: 500;
    }
    .promo-feedback.ok  { color: #34c759; }
    .promo-feedback.err { color: #ff453a; }

    /* Promo Chips */
    .promo-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .promo-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        background: #f0f0f5;
        border: 1.5px solid #e0e0e8;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        color: #3c3c43;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
        letter-spacing: 0.5px;
    }
    .promo-chip:hover {
        background: #e5e5f0;
        border-color: #0071e3;
        color: #0071e3;
    }
    .promo-chip .chip-discount {
        background: #0071e3;
        color: #fff;
        border-radius: 20px;
        padding: 1px 7px;
        font-size: 11px;
    }

    /* Divider */
    .divider { border: none; border-top: 1px solid #f0f0f5; margin: 24px 0; }

    /* Order Summary */
    .summary-table { width: 100%; border-collapse: collapse; }
    .summary-table td {
        padding: 7px 0;
        font-size: 15px;
        color: #3c3c43;
    }
    .summary-table td:last-child { text-align: right; font-weight: 600; }
    .summary-table .total-row td {
        font-size: 18px;
        font-weight: 700;
        color: #1d1d1f;
        padding-top: 12px;
        border-top: 1px solid #e5e5ea;
    }
    .discount-row td { color: #34c759 !important; }

    /* Confirm Button */
    .confirm-btn {
        width: 100%;
        padding: 16px;
        margin-top: 24px;
        background: #1d1d1f;
        color: #fff;
        border: none;
        border-radius: 14px;
        font-size: 16px;
        font-weight: 700;
        font-family: inherit;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        letter-spacing: 0.2px;
    }
    .confirm-btn:hover { background: #3a3a3c; transform: translateY(-1px); }
    .confirm-btn:active { transform: translateY(0); }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: #6e6e73;
        text-decoration: none;
        font-size: 14px;
        margin-bottom: 24px;
        transition: color 0.2s;
    }
    .back-link:hover { color: #1d1d1f; }

    @media (max-width: 540px) {
        .product-card { flex-direction: column; }
        .product-card img {
            width: 100%;
            height: auto;
            max-height: 280px;
            object-fit: contain;
            background: #fff;
        }
    }
    </style>
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="checkout-wrapper">
    <a href="<?= BASE_URL ?>/dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to shop
    </a>

    <h1 class="checkout-title">Checkout</h1>
    <p class="checkout-sub">Review your order and enter a promo code if you have one.</p>

    <?php showFlash(); ?>

    <!-- Product Card -->
    <div class="product-card">
        <img
            src="<?= BASE_URL . '/' . htmlspecialchars($product['image']) ?>"
            alt="<?= sanitize($product['name']) ?>"
            onerror="this.src='<?= BASE_URL ?>/assets/img/no-image.png'"
        >
        <div class="product-card-info">
            <div class="product-card-name"><?= sanitize($product['name']) ?></div>
            <div class="product-card-desc"><?= sanitize($product['description']) ?></div>
            <div class="product-card-price" id="unit-price-display"><?= formatPrice($product['price']) ?></div>
        </div>
    </div>

    <!-- Order Form -->
    <form class="order-form" action="<?= BASE_URL ?>/process-purchase.php" method="POST" id="checkout-form">
        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
        <input type="hidden" name="promo_code"  id="promo-code-hidden" value="">

        <!-- Quantity -->
        <p class="form-section-title">Order Details</p>
        <div class="form-group">
            <label for="quantity">Quantity</label>
            <input
                type="number"
                id="quantity"
                name="quantity"
                class="qty-input"
                value="1"
                min="1"
                max="<?= (int)$product['stock'] ?>"
                oninput="recalc()"
            >
            <p class="stock-note">Available stock: <?= (int)$product['stock'] ?> unit(s)</p>
        </div>

        <hr class="divider">

        <!-- Promo Code -->
        <p class="form-section-title">Promo Code</p>
        <div class="form-group">
            <label for="promo-input">Enter promo code</label>
            <div class="promo-row">
                <input
                    type="text"
                    id="promo-input"
                    class="promo-code-input"
                    placeholder="e.g. WELCOME10"
                    maxlength="50"
                    autocomplete="off"
                    oninput="this.value=this.value.toUpperCase(); clearPromo();"
                >
                <button type="button" class="apply-btn" onclick="applyPromo()">Apply</button>
            </div>
            <div class="promo-feedback" id="promo-feedback"></div>

            <?php if (!empty($activePromos)): ?>
            <div class="promo-chips">
                <?php foreach ($activePromos as $ap): ?>
                <span class="promo-chip" onclick="useChip('<?= sanitize($ap['code']) ?>')">
                    <span><?= sanitize($ap['code']) ?></span>
                    <span class="chip-discount"><?= number_format($ap['discount_percent'], 0) ?>% OFF</span>
                </span>
                <?php endforeach; ?>
            </div>
            <p style="font-size:12px;color:#aeaeb2;margin-top:8px;">💡 Click a code above to apply it instantly</p>
            <?php endif; ?>
        </div>

        <hr class="divider">

        <!-- Order Summary -->
        <p class="form-section-title">Order Summary</p>
        <table class="summary-table">
            <tr>
                <td>Unit price</td>
                <td><?= formatPrice($product['price']) ?></td>
            </tr>
            <tr>
                <td>Quantity</td>
                <td id="summary-qty">1</td>
            </tr>
            <tr>
                <td>Subtotal</td>
                <td id="summary-subtotal"><?= formatPrice($product['price']) ?></td>
            </tr>
            <tr class="discount-row" id="discount-row" style="display:none;">
                <td id="discount-label">Promo discount</td>
                <td id="summary-discount">-$0.00</td>
            </tr>
            <tr class="total-row">
                <td>Total</td>
                <td id="summary-total"><?= formatPrice($product['price']) ?></td>
            </tr>
        </table>

        <button type="submit" class="confirm-btn" id="confirm-btn">
            <i class="fas fa-lock"></i> Confirm Purchase
        </button>
    </form>
</div>

<script>
const UNIT_PRICE = <?= (float)$product['price'] ?>;
let appliedDiscount = 0;   // percentage
let appliedCode     = '';

function fmt(n) {
    return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function recalc() {
    const qty      = Math.max(1, parseInt(document.getElementById('quantity').value) || 1);
    const subtotal = UNIT_PRICE * qty;
    const discAmt  = subtotal * (appliedDiscount / 100);
    const total    = Math.max(0, subtotal - discAmt);

    document.getElementById('summary-qty').textContent      = qty;
    document.getElementById('summary-subtotal').textContent = fmt(subtotal);
    document.getElementById('summary-total').textContent    = fmt(total);

    if (discAmt > 0) {
        document.getElementById('discount-row').style.display    = '';
        document.getElementById('summary-discount').textContent  = '-' + fmt(discAmt);
        document.getElementById('discount-label').textContent    = 'Discount ' + appliedCode + ' (' + appliedDiscount + '%)';
    } else {
        document.getElementById('discount-row').style.display = 'none';
    }
}

function clearPromo() {
    appliedDiscount = 0;
    appliedCode     = '';
    document.getElementById('promo-code-hidden').value = '';
    document.getElementById('promo-feedback').textContent = '';
    document.getElementById('promo-feedback').className = 'promo-feedback';
    recalc();
}

function useChip(code) {
    document.getElementById('promo-input').value = code;
    applyPromo();
}

function applyPromo() {
    const code = document.getElementById('promo-input').value.trim().toUpperCase();
    const fb   = document.getElementById('promo-feedback');

    if (!code) {
        fb.textContent = 'Please enter a promo code.';
        fb.className   = 'promo-feedback err';
        return;
    }

    fb.textContent = '⌛ Checking code...';
    fb.className   = 'promo-feedback';

    fetch('<?= BASE_URL ?>/check-promo.php?code=' + encodeURIComponent(code))
        .then(r => r.json())
        .then(data => {
            if (data.valid) {
                appliedDiscount = data.discount_percent;
                appliedCode     = data.code;
                document.getElementById('promo-code-hidden').value = data.code;
                fb.textContent = '✅ Code ' + data.code + ' applied! ' + data.discount_percent + '% discount.';
                fb.className   = 'promo-feedback ok';
            } else {
                clearPromo();
                fb.textContent = '❌ ' + (data.message || 'Promo code is invalid or has expired.');
                fb.className   = 'promo-feedback err';
            }
            recalc();
        })
        .catch(() => {
            fb.textContent = '❌ Failed to verify code, please try again.';
            fb.className   = 'promo-feedback err';
        });
}

// Recalc on quantity change
document.getElementById('quantity').addEventListener('change', recalc);
</script>
</body>
</html>
