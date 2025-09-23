<?php
// Get the current page URL to set active states in navigation
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>K-Food Delights</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="wiz-cursor.ico" type="image/x-icon">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    
    <!-- Theme Colors -->
    <style>
        :root {
            --primary-color: #ff6b6b;
            --secondary-color: #4ecdc4;
            --accent-color: #ffe66d;
            --text-color: #2d3436;
            --background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <header class="site-header">
        <nav class="main-nav">
            <div class="logo">
                <a href="index.php">
                    <img src="images/logo.png" alt="K-Food Delights Logo">
                </a>
            </div>
            
            <ul class="nav-links">
                <li <?php echo $current_page === 'index.php' ? 'class="active"' : ''; ?>>
                    <a href="index.php">Home</a>
                </li>
                <li <?php echo $current_page === 'menu.php' ? 'class="active"' : ''; ?>>
                    <a href="menu.php">Menu</a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li <?php echo $current_page === 'order_history.php' ? 'class="active"' : ''; ?>>
                        <a href="order_history.php">Orders</a>
                    </li>
                    <li <?php echo $current_page === 'profile.php' ? 'class="active"' : ''; ?>>
                        <a href="profile.php">Profile</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="nav-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="cart.php" class="cart-icon" aria-label="Shopping Cart">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count">0</span>
                    </a>
                    <a href="logout.php" class="auth-icon" aria-label="Sign Out">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="auth-icon" aria-label="Sign In" 
                       role="button" 
                       data-tooltip="Sign In to Order">
                        <i class="fas fa-sign-in-alt"></i>
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    
    <!-- Add any flash messages/notifications -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
            <?php 
                echo $_SESSION['message']; 
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
            ?>
        </div>
    <?php endif; ?>