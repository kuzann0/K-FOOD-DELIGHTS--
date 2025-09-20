<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to checkout']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

try {
    // Create cart_orders table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS cart_orders (
        order_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10,2) NOT NULL,
        order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status VARCHAR(50) DEFAULT 'Pending',
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    $conn->query($sql);

    // Create cart_order_items table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS cart_order_items (
        item_id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES cart_orders(order_id)
    )";
    $conn->query($sql);

    // Start transaction
    $conn->begin_transaction();

    // Insert main order
    $stmt = $conn->prepare("INSERT INTO cart_orders (user_id, total_amount) VALUES (?, ?)");
    $total_amount = floatval(str_replace(['₱', ','], '', $data['total']));
    $stmt->bind_param("id", $_SESSION['user_id'], $total_amount);
    $stmt->execute();
    $order_id = $stmt->insert_id;

    // Insert order items
    $stmt = $conn->prepare("INSERT INTO cart_order_items (order_id, product_name, quantity, price) VALUES (?, ?, ?, ?)");
    
    foreach ($data['items'] as $item) {
        $price = floatval(str_replace(['₱', ','], '', $item['price']));
        $stmt->bind_param("isid", $order_id, $item['name'], $item['quantity'], $price);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => 'Error processing order: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
