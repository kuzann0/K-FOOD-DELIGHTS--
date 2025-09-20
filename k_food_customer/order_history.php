<?php
session_start();
include 'config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch all orders for the user
$query = "SELECT o.*, 
          COUNT(oi.item_id) as total_items,
          (SELECT status_updated_at 
           FROM order_status_history 
           WHERE order_id = o.order_id 
           ORDER BY status_updated_at DESC 
           LIMIT 1) as last_update
          FROM orders o
          LEFT JOIN order_items oi ON o.order_id = oi.order_id
          WHERE o.user_id = ?
          GROUP BY o.order_id
          ORDER BY o.order_date DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .history-container {
            max-width: 1000px;
            margin: 80px auto;
            padding: 20px;
        }

        .page-title {
            text-align: center;
            margin-bottom: 40px;
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }

        .orders-grid {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s ease;
            cursor: pointer;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .order-id {
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .order-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .info-item {
            color: #666;
        }

        .info-label {
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .order-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
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

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        .view-details-btn {
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .view-details-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .empty-history {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }

        .empty-history i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .history-container {
                padding: 15px;
                margin: 60px auto;
            }

            .page-title {
                font-size: 24px;
                margin-bottom: 30px;
            }

            .order-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="logged-in">
    <?php include 'includes/nav.php'; ?>

    <div class="history-container">
        <h1 class="page-title">Order History</h1>

        <div class="orders-grid">
            <?php if (empty($orders)): ?>
            <div class="empty-history">
                <i class="fas fa-shopping-bag"></i>
                <h2>No Orders Yet</h2>
                <p>You haven't placed any orders yet. Start shopping now!</p>
            </div>
            <?php else: ?>
            <?php foreach ($orders as $order): ?>
            <div class="order-card" onclick="window.location.href='order_confirmation.php?order_id=<?php echo $order['order_id']; ?>'">
                <div class="order-header">
                    <div class="order-id">Order #<?php echo str_pad($order['order_id'], 8, '0', STR_PAD_LEFT); ?></div>
                    <div class="order-date">
                        <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                    </div>
                </div>

                <div class="order-info">
                    <div class="info-item">
                        <div class="info-label">Total Amount</div>
                        <div class="info-value">₱<?php echo number_format($order['total_amount'], 2); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Items</div>
                        <div class="info-value"><?php echo $order['total_items']; ?> items</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?php echo $order['payment_method'] === 'gcash' ? 'GCash' : 'Cash on Delivery'; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Last Update</div>
                        <div class="info-value"><?php echo date('M d, Y h:i A', strtotime($order['last_update'])); ?></div>
                    </div>
                </div>

                <div class="order-footer">
                    <div class="status-badge status-<?php echo strtolower($order['status']); ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </div>
                    <a href="order_confirmation.php?order_id=<?php echo $order['order_id']; ?>" class="view-details-btn">
                        <i class="fas fa-eye"></i>
                        View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>