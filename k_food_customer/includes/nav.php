<div class="navbar">
    <div class="navdiv">
        <div class="logo">
            <a href="index.php">K-Food Delights</a>
        </div>
        <nav class="nav-links">
            <ul>
                <li><a href="index.php#aboutus-section">About Us</a></li>
                <li><a href="index.php#product-section">Product</a></li>
                <li><a href="index.php#contact-section">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="cart-nav">
                        <a href="#" id="cartButton">
                            <i class="fas fa-shopping-cart cart-icon"></i>
                            <span id="cartCount" class="cart-count">0</span>
                        </a>
                    </li>
                    <li class="profile-link">
                        <a href="profile.php">
                            <i class="fas fa-user"></i> Profile
                        </a>
                    </li>
                    <li>
                        <a href="logout.php" class="logout-link">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</div>
