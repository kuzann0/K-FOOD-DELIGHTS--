
<?php
require_once 'includes/crew_auth.php';
require_once '../k_food_customer/config.php';

// Validate crew session
validateCrewSession();

// Get crew member details
$stmt = $conn->prepare("SELECT username, first_name, last_name FROM users WHERE user_id = ? AND role_id = 3");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$crew = $result->fetch_assoc();
$stmt->close();

$pageTitle = "Crew Dashboard";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> | K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/crew-dashboard.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="shortcut icon" href="../logo-tab-icon.ico" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-font: 'Poppins', sans-serif;
            --error-color: #f44336;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --info-color: #2196f3;
        }
        
        body {
            font-family: var(--primary-font);
        }

        #notification-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            pointer-events: none;
        }

        #notification-container > div {
            pointer-events: auto;
        }

        .notification {
            font-family: var(--primary-font);
            margin-bottom: 10px;
            padding: 15px 20px;
            border-radius: 8px;
            min-width: 300px;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            opacity: 0;
            transform: translateX(100%);
            animation: slideIn 0.3s forwards;
        }

        .notification i {
            font-size: 20px;
        }

        .notification.error {
            background-color: var(--error-color);
            color: white;
        }

        .notification.success {
            background-color: var(--success-color);
            color: white;
        }

        .notification.warning {
            background-color: var(--warning-color);
            color: white;
        }

        .notification.info {
            background-color: var(--info-color);
            color: white;
        }

        @keyframes slideIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>
    <!-- Notification Container -->
    <div id="notification-container"></div>

    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="../resources/images/logo.png" alt="K-Food Delight Logo" class="sidebar-logo">
                <h2>Crew Dashboard</h2>
                <p class="crew-name">Welcome, <?php echo htmlspecialchars($crew['first_name']); ?></p>  
                <!-- ' ' . $crew['last_name'] -->
            </div>
            
            <nav>
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="#" class="nav-link active" data-view="orders">
                            <i class="fas fa-clipboard-list"></i>
                            Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-view="preparation">
                            <i class="fas fa-utensils"></i>
                            Preparation
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#" class="nav-link" data-view="completed">
                            <i class="fas fa-check-circle"></i>
                            Completed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="logout.php" class="nav-link">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Status Filters -->
            <div class="status-filters">
                <button class="filter-btn active" data-status="all">All Orders</button>
                <button class="filter-btn" data-status="pending">Pending</button>
                <button class="filter-btn" data-status="preparing">Preparing</button>
                <button class="filter-btn" data-status="ready">Ready</button>
                <button class="filter-btn" data-status="delivered">Delivered</button>
            </div>

            <!-- Orders Grid -->
            <div class="orders-grid" id="ordersContainer">
                <!-- Orders will be loaded here dynamically -->
                <div class="empty-state">
                    <i class="fas fa-clipboard-list"></i>
                    <h3>No orders yet</h3>
                    <p>New orders will appear here</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Order Details Modal Template -->
    <div class="modal" id="orderModal">
        <div class="modal-content">
            <!-- Modal content will be loaded dynamically -->
        </div>
    </div>

            <!-- Notification Container -->
    <div id="notification-container"></div>

    <!-- Scripts -->
    <!-- AJAX Polling Configuration -->
    <script src="../k_food_customer/js/websocket-config.js"></script>
    
    <!-- Core Libraries -->
    <script src="js/notification-system.js"></script>
    <script src="js/websocket-manager.js"></script>
    
    <script>
    // Initialize WebSocket connection for crew dashboard
    document.addEventListener('DOMContentLoaded', async () => {
        try {
            // Initialize WebSocket manager
            const ws = new WebSocketManager(WebSocketConfig.getConfig());
            
            // Connect and authenticate
            await ws.connect();
            
            // Listen for new orders
            ws.on(WebSocketMessageTypes.NEW_ORDER, (orderData) => {
                // Update orders display
                const ordersList = document.getElementById('activeOrders');
                if (ordersList) {
                    const orderElement = createOrderElement(orderData);
                    ordersList.insertBefore(orderElement, ordersList.firstChild);
                    
                    // Show notification
                    notificationManager.show('New order received!', 'success');
                }
            });
            
            console.log('Crew dashboard WebSocket initialized successfully');
        } catch (error) {
            console.error('Failed to initialize WebSocket connection:', error);
            notificationManager.show('Connection error. Please refresh the page.', 'error');
        }
    });
    
    function createOrderElement(order) {
        const orderDiv = document.createElement('div');
        orderDiv.className = 'order-card new';
        orderDiv.innerHTML = `
            <div class="order-header">
                <h3>Order #${order.orderId}</h3>
                <span class="order-time">${new Date(order.timestamp).toLocaleTimeString()}</span>
            </div>
            <div class="order-details">
                <p><strong>Customer:</strong> ${order.customerName}</p>
                <p><strong>Items:</strong></p>
                <ul>
                    ${order.items.map(item => `
                        <li>${item.quantity}x ${item.name}</li>
                    `).join('')}
                </ul>
                <p><strong>Total:</strong> ₱${order.total.toFixed(2)}</p>
                <p><strong>Address:</strong> ${order.address}</p>
                ${order.instructions ? `<p><strong>Instructions:</strong> ${order.instructions}</p>` : ''}
            </div>
            <div class="order-actions">
                <button class="btn accept-btn" onclick="updateOrderStatus('${order.orderId}', 'confirmed')">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn reject-btn" onclick="updateOrderStatus('${order.orderId}', 'cancelled')">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        `;
        return orderDiv;
    }
    
    async function updateOrderStatus(orderId, status) {
        try {
            await ws.send({
                type: WebSocketMessageTypes.ORDER_UPDATED,
                data: {
                    orderId,
                    status,
                    timestamp: new Date().toISOString()
                }
            });
            
            // Update UI
            const orderElement = document.querySelector(`[data-order-id="${orderId}"]`);
            if (orderElement) {
                orderElement.className = `order-card ${status}`;
                notificationManager.show(`Order ${status}`, 'success');
            }
        } catch (error) {
            console.error('Failed to update order status:', error);
            notificationManager.show('Failed to update order status', 'error');
        }
    }</script>
    <script>
        // Initialize systems after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            try {
                // Initialize notification system
                window.notifications = new NotificationSystem();
                
                // Load crew dashboard script
                const script = document.createElement('script');
                script.src = 'js/crew-dashboard.js';
                script.onerror = function() {
                    console.error('Failed to load crew-dashboard.js');
                    window.notifications.error('Failed to initialize dashboard. Please refresh the page.');
                };
                document.body.appendChild(script);
            } catch (error) {
                console.error('Initialization error:', error);
                alert('Failed to initialize the dashboard. Please refresh the page.');
            }
        });
    </script>
</body>
</html>
        <!-- }
        
        .sidebar-header h2,
        .crew-name,
        .nav-menu {
            font-family: var(--primary-font);
        }
        
        .orders-grid {
            font-family: var(--primary-font);
        }
        
        .modal-content {
            font-family: var(--primary-font);
        } -->
    </style>
</body>
</html>
