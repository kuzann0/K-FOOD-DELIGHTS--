<?php
session_start();
require_once 'config.php';
require_once 'includes/DatabaseConnection.php';
require_once 'includes/CacheManager.php';
require_once 'includes/Cart.php';

// Set JSON header
header('Content-Type: application/json');

/**
 * Standardized response function
 */
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error,
        'timestamp' => time()
    ]);
    exit;
}

try {
    // Verify user authentication
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not logged in');
    }

    // Get database and cache instances
    $db = DatabaseConnection::getInstance();
    $cache = CacheManager::getInstance();
    $cart = new Cart($db);

    // Get and validate action
    $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
    if (!$action) {
        throw new Exception('No action specified');
    }

    // Process cart actions
    switch ($action) {
        case 'add':
            // Validate inputs
            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
            
            // For new items, always start with quantity 1
            if ($action === 'add') {
                $quantity = 1;
            } else {
                $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
                if ($quantity === null || $quantity < 1) {
                    throw new Exception('Invalid quantity');
                }
            }
            
            if (!$productId) {
                throw new Exception('Invalid product');
            }

            // Check product availability
            $product = $cart->checkProductAvailability($productId);
            if (!$product) {
                throw new Exception('Product not available');
            }

            // Check stock availability
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception('Not enough stock available');
            }

            // Add to cart
            $cart->addToCart($_SESSION['user_id'], $productId, $quantity);
            
            // Clear relevant cache
            $cache->remove('cart_' . $_SESSION['user_id']);
            
            $response = [
                'success' => true,
                'message' => 'Item added to cart successfully',
                'cart' => $cart->getCartSummary($_SESSION['user_id'])
            ];
            break;

        case 'update':
            // Validate inputs
            $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
            $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);
            
            if (!$cartId || $quantity < 0) {
                throw new Exception('Invalid cart ID or quantity');
            }

            // Verify cart item belongs to user
            if (!$cart->verifyCartOwnership($_SESSION['user_id'], $cartId)) {
                throw new Exception('Invalid cart item');
            }

            // Check stock availability
            $cartItem = $cart->getCartItem($cartId);
            $product = $cart->checkProductAvailability($cartItem['product_id']);
            
            if ($product['stock_quantity'] < $quantity) {
                throw new Exception('Not enough stock available');
            }

            // Update quantity
            if ($quantity === 0) {
                $cart->removeFromCart($cartId);
            } else {
                $cart->updateQuantity($cartId, $quantity);
            }

            // Clear relevant cache
            $cache->remove('cart_' . $_SESSION['user_id']);
            
            $response = [
                'success' => true,
                'message' => 'Cart updated successfully',
                'cart' => $cart->getCartSummary($_SESSION['user_id'])
            ];
            break;

        case 'remove':
            // Validate input
            $cartId = filter_input(INPUT_POST, 'cart_id', FILTER_VALIDATE_INT);
            
            if (!$cartId) {
                throw new Exception('Invalid cart ID');
            }

            // Verify cart item belongs to user
            if (!$cart->verifyCartOwnership($_SESSION['user_id'], $cartId)) {
                throw new Exception('Invalid cart item');
            }

            // Remove item
            $cart->removeFromCart($cartId);
            
            // Clear relevant cache
            $cache->remove('cart_' . $_SESSION['user_id']);
            
            $response = [
                'success' => true,
                'message' => 'Item removed from cart successfully',
                'cart' => $cart->getCartSummary($_SESSION['user_id'])
            ];
            break;

        case 'get':
            // Get cart summary from cache or database
            $cacheKey = 'cart_' . $_SESSION['user_id'];
            $cartData = $cache->get($cacheKey);
            
            if ($cartData === null) {
                $cartData = $cart->getCartSummary($_SESSION['user_id']);
                $cache->set($cacheKey, $cartData, 300); // Cache for 5 minutes
            }
            
            $response = [
                'success' => true,
                'cart' => $cartData
            ];
            break;

        case 'clear':
            // Clear entire cart
            $cart->clearCart($_SESSION['user_id']);
            
            // Clear relevant cache
            $cache->remove('cart_' . $_SESSION['user_id']);
            
            $response = [
                'success' => true,
                'message' => 'Cart cleared successfully',
                'cart' => [
                    'items' => [],
                    'total' => 0,
                    'item_count' => 0
                ]
            ];
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Send response
echo json_encode($response);
