<?php
require_once 'config.php';
require_once 'includes/auth.php';
require_once 'includes/cart_setup.php';

$pageTitle = "Our Menu";

// Get categories for filter
try {
    $categories = $conn->query("
        SELECT DISTINCT c.* 
        FROM categories c
        INNER JOIN menu_items m ON c.category_id = m.category_id
        WHERE m.is_available = 1
        GROUP BY c.category_id
        ORDER BY c.name
    ")->fetch_all(MYSQLI_ASSOC);
} catch (mysqli_sql_exception $e) {
    // If there's an error (like missing column), use this fallback query
    $categories = $conn->query("
        SELECT DISTINCT c.* 
        FROM categories c
        INNER JOIN menu_items m ON c.category_id = m.category_id
        GROUP BY c.category_id
        ORDER BY c.name
    ")->fetch_all(MYSQLI_ASSOC);
}

include 'includes/nav.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - K-Food</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/product.css">
    <link rel="stylesheet" href="css/cart.css">
</head>
<body>
    <main class="menu-page">
        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Our Menu</h1>
                <p>Discover authentic Korean flavors</p>
            </div>
        </section>
        
        <!-- Filters Section -->
        <section class="filters-section">
            <div class="container">
                <div class="search-filter">
                    <input type="text" id="menuSearch" placeholder="Search menu...">
                    <i class="fas fa-search"></i>
                </div>
                
                <div class="category-filter">
                    <button class="category-btn active" data-category="">All</button>
                    <?php foreach ($categories as $category): ?>
                        <button class="category-btn" data-category="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        
        <!-- Menu Grid -->
        <section class="menu-section">
            <div class="container">
                <div id="menuGrid" class="menu-grid">
                    <!-- Will be populated via JavaScript -->
                </div>
            </div>
        </section>
        
        <!-- Order Modal -->
        <div id="orderModal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="itemName"></h2>
                    <button type="button" class="close-modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="item-details">
                        <div class="item-image">
                            <img id="itemImage" src="" alt="">
                        </div>
                        <div class="item-info">
                            <p id="itemDescription"></p>
                            <div class="price-time">
                                <span class="price">₱<span id="itemPrice"></span></span>
                                <span class="prep-time">
                                    <i class="fas fa-clock"></i>
                                    <span id="prepTime"></span> mins
                                </span>
                            </div>
                            <div class="quantity-selector">
                                <button type="button" class="qty-btn" onclick="decrementQuantity()">-</button>
                                <input type="number" id="quantity" value="1" min="1" max="99">
                                <button type="button" class="qty-btn" onclick="incrementQuantity()">+</button>
                            </div>
                            <button type="button" id="addToCart" class="btn btn-primary">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include 'includes/cart_modal.php'; ?>
    </main>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
    <script src="js/product-filter.js"></script>
    <script src="js/cart.js"></script>
    <script>
        // Initialize menu display
        document.addEventListener('DOMContentLoaded', function() {
            loadMenuItems();
            setupFilters();
        });
    </script>
</body>
</html>
