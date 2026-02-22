<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeBox - Main Page</title>
    <link rel="stylesheet" href="mainpage.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

    <header class="top-bar">
        <div class="brand-logo">
            <i class="fas fa-box"></i> <h1>BeBox</h1>
        </div>

        <div class="search-container">
            <form action="#">
                <input type="text" placeholder="Search products..." name="search">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>

        <div class="nav-container">
            <input type="checkbox" id="menu-toggle">
            <label for="menu-toggle" class="burger-menu">
                <span></span>
                <span></span>
                <span></span>
            </label>

            <div class="menu-dropdown">
                <a href="profile.php"><i class="fas fa-user-circle"></i> Akun</a>
                <a href="promo.php"><i class="fas fa-tags"></i> Promo</a>
                <a href="history.php"><i class="fas fa-history"></i> Riwayat Transaksi</a>
                <hr>
                <a href="index.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </header>

    <section class="hero-section">
        <div class="hero-overlay">
            <div class="hero-content">
                <h2>Unwrap your surprise with BeBox!</h2>
                <p>Experience the thrill of getting a unique and exclusive blind box.</p>
            </div>
        </div>
    </section>

    <section class="product-listing">
        <h3>Today's Random Picks</h3>

        <div class="product-item">
            <div class="product-image">
                <img src="../Picture/image1.jpeg" alt="Product 1">
            </div>
            <div class="product-description">
                <h4>Hirono Celestial Drift</h4>
                <p>Exclusive limited edition from the Celestial series, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection, the Hirono Celestial Drift is a must have for true figure enthusiasts.</p>
                <p class="product-price">$17.53</p>
            </div>
            <div class="product-actions">
                <button class="buy-button">Buy now <i class="fas fa-shopping-cart"></i></button>
            </div>
        </div>

        <div class="product-item">
            <div class="product-image">
                <img src="../Picture/image2.jpeg" alt="Product 2">
            </div>
            <div class="product-description">
                <h4>Hirono Boo! Edition</h4>
                <p>Spooky but cute! Perfect for your ghost collection, specially designed with unique and luxurious touches. Featuring exclusive details, high quality materials, and a distinctive color scheme, this collection offers a stylish and elegant look for Hirono fans. Perfect for display or personal collection, the Hirono Celestial Drift is a must have for true figure enthusiasts.</p>
                <p class="product-price">$15.81</p>
            </div>
            <div class="product-actions">
                <button class="buy-button">Buy now <i class="fas fa-shopping-cart"></i></button>
            </div>
        </div>

        <div class="product-item">
            <div class="product-image">
                <img src="../Picture/image3.jpeg" alt="Product 3">
            </div>
            <div class="product-description">
                <h4>Hirono Cruise Rider</h4>
                <p>This vibrant figure captures Hirono mid-pedal, her cheerful expression radiating pure cycling happiness. With her cute helmet, windswept hair, and detailed bicycle, every element celebrates the freedom of two-wheeled adventures. The dynamic pose makes it look like she's just zoomed into your collection!  </p></p>
                <p class="product-price">$18.15</p>
            </div>
            <div class="product-actions">
                <button class="buy-button">Buy now <i class="fas fa-shopping-cart"></i></button>
            </div>
        </div>
    </section>
<script>
    // Menutup navbar otomatis jika user klik di luar area menu
    window.onclick = function(event) {
        const checkbox = document.getElementById('menu-toggle');
        const navContainer = document.querySelector('.nav-container');
        
        if (checkbox.checked && !navContainer.contains(event.target)) {
            checkbox.checked = false;
        }
    }
</script>
</body>
</html>