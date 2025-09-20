<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if order ID is provided
if (!isset($_GET['order_id'])) {
    header('Location: menu.php');
    exit();
}

$orderId = $_GET['order_id'];
$userId = $_SESSION['user_id'];

// Fetch order details
$orderQuery = "SELECT o.*, 
               u.first_name, u.last_name, u.email, u.phone
               FROM orders o 
               JOIN users u ON o.user_id = u.user_id 
               WHERE o.order_id = ? AND o.user_id = ?";
$stmt = $conn->prepare($orderQuery);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$orderResult = $stmt->get_result();
$orderDetails = $orderResult->fetch_assoc();
$stmt->close();

// If order not found or doesn't belong to user
if (!$orderDetails) {
    header('Location: menu.php');
    exit();
}

// Fetch order items
$itemsQuery = "SELECT * FROM order_items WHERE order_id = ?";
$stmt = $conn->prepare($itemsQuery);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$itemsResult = $stmt->get_result();
$orderItems = [];
while ($item = $itemsResult->fetch_assoc()) {
    $orderItems[] = $item;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 80px auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .order-success {
            text-align: center;
            margin-bottom: 30px;
        }

        .order-success i {
            font-size: 64px;
            color: #4CAF50;
            margin-bottom: 20px;
        }

        .order-success h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .order-id {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .section h2 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6666;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .info-item {
            margin-bottom: 15px;
        }

        .info-label {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-size: 16px;
            font-weight: 500;
        }

        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .order-items th,
        .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .order-items th {
            color: #666;
            font-weight: 500;
            background: #f8f9fa;
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-row {
            margin: 10px 0;
            font-size: 16px;
            color: #666;
        }

        .total-row.final {
            font-size: 20px;
            font-weight: 600;
            color: #333;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
        }

        .action-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
        }

        .action-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 25px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .home-btn {
            background: #f5f5f5;
            color: #333;
        }

        .track-btn {
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processing {
            background: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }

            .action-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/nav.php'; ?>

    <div class="confirmation-container">
        <div class="order-success">
            <i class="fas fa-check-circle"></i>
            <h1>Order Successfully Placed!</h1>
            <div class="order-id">Order ID: #<?php echo str_pad($orderDetails['order_id'], 8, '0', STR_PAD_LEFT); ?></div>
        </div>

        <div class="section">
            <h2>Order Status</h2>
            <div class="status-badge status-pending">
                <?php echo ucfirst($orderDetails['status']); ?>
            </div>
        </div>

        <div class="section">
            <h2>Delivery Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($orderDetails['first_name'] . ' ' . $orderDetails['last_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($orderDetails['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($orderDetails['phone']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Delivery Address</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($orderDetails['delivery_address'])); ?></div>
                </div>
                <?php if ($orderDetails['delivery_instructions']): ?>
                <div class="info-item">
                    <div class="info-label">Delivery Instructions</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($orderDetails['delivery_instructions'])); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Payment Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value"><?php echo $orderDetails['payment_method'] === 'gcash' ? 'GCash' : 'Cash on Delivery'; ?></div>
                </div>
                <?php if ($orderDetails['payment_method'] === 'gcash'): ?>
                <div class="info-item">
                    <div class="info-label">GCash Reference</div>
                    <div class="info-value"><?php echo htmlspecialchars($orderDetails['gcash_reference']); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="section">
            <h2>Order Summary</h2>
            <table class="order-items">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orderItems as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>₱<?php echo number_format($item['price'], 2); ?></td>
                        <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="total-section">
                <div class="total-row">
                    Subtotal: ₱<?php echo number_format($orderDetails['subtotal'], 2); ?>
                </div>
                <div class="total-row">
                    Delivery Fee: ₱<?php echo number_format($orderDetails['delivery_fee'], 2); ?>
                </div>
                <div class="total-row final">
                    Total: ₱<?php echo number_format($orderDetails['total_amount'], 2); ?>
                </div>
            </div>
        </div>
<!--comment-->
        <div class="action-buttons">
            <a href="index.php" class="action-btn home-btn">
                <i class="fas fa-home"></i>
                Return to Home
            </a>
            <a href="order_tracking.php?order_id=<?php echo $orderId; ?>" class="action-btn track-btn">
                <i class="fas fa-truck"></i>
                Track Order
            </a>
        </div>
    </div>
</body>
</html>