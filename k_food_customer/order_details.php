<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Validate order ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: order_history.php');
    exit();
}

$userId = $_SESSION['user_id'];
$orderId = $_GET['id'];

// Fetch order details with security check for user ownership
$query = "SELECT o.*, 
          (SELECT status_updated_at 
           FROM order_status_history 
           WHERE order_id = o.id 
           ORDER BY status_updated_at DESC 
           LIMIT 1) as last_update
          FROM orders o
          WHERE o.id = ? AND o.user_id = ?";

try {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: order_history.php');
        exit();
    }
    
    $order = $result->fetch_assoc();
    $stmt->close();

    // Fetch order items
    $itemsQuery = "SELECT oi.*, p.name as product_name, p.image_url 
                  FROM order_items oi
                  JOIN products p ON oi.product_id = p.id
                  WHERE oi.order_id = ?";
    
    $stmt = $conn->prepare($itemsQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $itemsResult = $stmt->get_result();
    $orderItems = [];
    while ($item = $itemsResult->fetch_assoc()) {
        $orderItems[] = $item;
    }
    $stmt->close();

    // Fetch order status history
    $historyQuery = "SELECT * FROM order_status_history 
                    WHERE order_id = ? 
                    ORDER BY status_updated_at DESC";
    
    $stmt = $conn->prepare($historyQuery);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $historyResult = $stmt->get_result();
    $statusHistory = [];
    while ($status = $historyResult->fetch_assoc()) {
        $statusHistory[] = $status;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log("Error fetching order details: " . $e->getMessage());
    header('Location: order_history.php?error=1');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .details-container {
            max-width: 1000px;
            margin: 80px auto;
            padding: 20px;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .order-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
        }

        .order-date {
            color: #666;
        }

        .order-sections {
            display: grid;
            gap: 30px;
        }

        .section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .items-list {
            display: grid;
            gap: 15px;
        }

        .item-card {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 8px;
        }

        .item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 15px;
        }

        .item-details {
            flex-grow: 1;
        }

        .item-name {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .item-price {
            color: #666;
            font-size: 14px;
        }

        .item-quantity {
            color: #666;
            font-size: 14px;
        }

        .status-timeline {
            display: grid;
            gap: 15px;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 15px;
        }

        .timeline-icon {
            width: 30px;
            height: 30px;
            background: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }

        .timeline-content {
            flex-grow: 1;
        }

        .timeline-status {
            font-weight: 500;
            margin-bottom: 5px;
        }

        .timeline-date {
            font-size: 14px;
            color: #666;
        }

        .order-summary {
            display: grid;
            gap: 10px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 500;
        }

        .total-row {
            font-size: 18px;
            font-weight: 600;
            border-top: 2px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 8px 16px;
            background: #f8f9fa;
            border: none;
            border-radius: 20px;
            color: #333;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            background: #e9ecef;
        }

        @media (max-width: 768px) {
            .details-container {
                padding: 15px;
                margin: 60px auto;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .item-card {
                flex-direction: column;
                text-align: center;
            }

            .item-image {
                margin-right: 0;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/nav.php'; ?>

    <div class="details-container">
        <div class="order-header">
            <h1 class="order-title">Order #<?php echo str_pad($order['id'], 8, '0', STR_PAD_LEFT); ?></h1>
            <div class="order-date">
                <?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?>
            </div>
        </div>

        <a href="order_history.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Order History
        </a>

        <div class="order-sections">
            <div class="section">
                <h2 class="section-title">Order Items</h2>
                <div class="items-list">
                    <?php foreach ($orderItems as $item): ?>
                    <div class="item-card">
                        <?php if ($item['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="item-image">
                        <?php endif; ?>
                        <div class="item-details">
                            <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                            <div class="item-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="item-quantity">Quantity: <?php echo $item['quantity']; ?></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Order Status</h2>
                <div class="status-timeline">
                    <?php foreach ($statusHistory as $status): ?>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-circle"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-status"><?php echo ucfirst($status['status']); ?></div>
                            <div class="timeline-date">
                                <?php echo date('F d, Y h:i A', strtotime($status['status_updated_at'])); ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="section">
                <h2 class="section-title">Order Summary</h2>
                <div class="order-summary">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value">₱<?php echo number_format($order['subtotal'], 2); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Delivery Fee</span>
                        <span class="summary-value">₱<?php echo number_format($order['delivery_fee'], 2); ?></span>
                    </div>
                    <?php if ($order['discount'] > 0): ?>
                    <div class="summary-row">
                        <span class="summary-label">Discount</span>
                        <span class="summary-value">-₱<?php echo number_format($order['discount'], 2); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="summary-row total-row">
                        <span class="summary-label">Total</span>
                        <span class="summary-value">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add any necessary JavaScript functionality here
        });
    </script>
</body>
</html>