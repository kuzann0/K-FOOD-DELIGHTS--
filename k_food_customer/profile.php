<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Require login for this page
requireLogin();

// Get current user data
$user = getCurrentUser($conn);
if (!$user) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/profile-enhanced.css">
    <link rel="stylesheet" href="css/profile-responsive.css">
    <link rel="shortcut icon" href="../logo-tab-icon.ico" type="image/x-icon" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
</head>
<body>
    <!-- Include navigation -->
    <?php include 'includes/nav.php'; ?>

    <div class="profile-container">
        <div class="profile-header">
            <div class="header-top">
                <a href="index.php" class="back-btn" onclick="window.location.href='index.php'; return false;">
                    <i class="fas fa-arrow-left"></i> 
                    <span>Back to Home</span>
                </a>
                <div class="last-login">
                    <i class="fas fa-clock"></i>
                    Last login: <?php 
                        echo isset($user['last_login']) && $user['last_login']
                            ? date('M d, Y H:i', strtotime($user['last_login']))
                            : 'First time login'; 
                    ?>
                </div>
            </div>
            
            <div class="welcome-message">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p>Manage your profile and orders here</p>
            </div>
            
            <!-- Korean-inspired decorative pattern -->
            <div class="k-pattern left"></div>
            <div class="k-pattern right"></div>
            
            <div class="profile-info">
                <div class="profile-picture">
                    <div class="picture-frame">
                        <img src="uploads/profile/<?php echo htmlspecialchars($user['profile_picture'] ?: 'default.png'); ?>" 
                             alt="Profile Picture" 
                             id="profilePreview"
                             onerror="this.src='uploads/profile/default.png'">
                        <div class="picture-overlay">
                            <i class="fas fa-camera"></i>
                            <span>Change Photo</span>
                        </div>
                        <div class="picture-loading">
                            <i class="fas fa-spinner fa-spin"></i>
                        </div>
                    </div>
                    <input type="file" 
                           id="profilePicture" 
                           name="profile_picture" 
                           accept="image/*" 
                           class="hidden-input"
                           aria-label="Change profile picture">
                </div>
                
                <div class="user-info">
                    <h2 class="user-name"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h2>
                    <div class="user-details">
                        <span class="user-email">
                            <i class="fas fa-envelope"></i>
                            <?php echo htmlspecialchars($user['email']); ?>
                        </span>
                        <?php if ($user['phone']): ?>
                        <span class="user-phone">
                            <i class="fas fa-phone"></i>
                            <?php echo htmlspecialchars($user['phone']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="profile-stats">
                <?php 
                // Get stats from database
                $userId = $user['user_id'];
                $stats = [
                    'orders' => 0,
                    'favorites' => 0,
                    'reviews' => 0
                ];
                
                // Get order count
                $orderQuery = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
                $orderQuery->bind_param("i", $userId);
                $orderQuery->execute();
                $stats['orders'] = $orderQuery->get_result()->fetch_assoc()['count'];
                
                // Get favorites count - Check if table exists first
                $stats['favorites'] = 0;
                $checkFavTable = $conn->query("SHOW TABLES LIKE 'favorites'");
                if ($checkFavTable->num_rows > 0) {
                    $favQuery = $conn->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
                    $favQuery->bind_param("i", $userId);
                    $favQuery->execute();
                    $stats['favorites'] = $favQuery->get_result()->fetch_assoc()['count'];
                }
                
                // Get reviews count - Check if table exists first
                $stats['reviews'] = 0;
                $checkRevTable = $conn->query("SHOW TABLES LIKE 'reviews'");
                if ($checkRevTable->num_rows > 0) {
                    $reviewQuery = $conn->prepare("SELECT COUNT(*) as count FROM reviews WHERE user_id = ?");
                    $reviewQuery->bind_param("i", $userId);
                    $reviewQuery->execute();
                    $stats['reviews'] = $reviewQuery->get_result()->fetch_assoc()['count'];
                }
                ?>
                
                <div class="stat" title="Total Orders">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="stat-value"><?php echo $stats['orders']; ?></span>
                    <span class="stat-label">Orders</span>
                </div>
                <div class="stat" title="Favorite Items">
                    <i class="fas fa-heart"></i>
                    <span class="stat-value"><?php echo $stats['favorites']; ?></span>
                    <span class="stat-label">Favorites</span>
                </div>
                <div class="stat" title="Product Reviews">
                    <i class="fas fa-star"></i>
                    <span class="stat-value"><?php echo $stats['reviews']; ?></span>
                    <span class="stat-label">Reviews</span>
                </div>
            </div>
        </div>

        <div class="profile-tabs">
            <button class="tab-btn active" data-tab="profile">
                <i class="fas fa-user-circle"></i> Profile
            </button>
            <button class="tab-btn" data-tab="security">
                <i class="fas fa-shield-alt"></i> Security
            </button>
            <button class="tab-btn" data-tab="preferences">
                <i class="fas fa-sliders-h"></i> Preferences
            </button>
            <button class="tab-btn" data-tab="support">
                <i class="fas fa-headset"></i> Support
            </button>
        </div>

        <div class="profile-content">
            <div class="profile-section profile-info active" id="profile-tab">
                <h2><i class="fas fa-user-circle"></i> Profile Information</h2>
                <form id="profileForm" class="animated-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">First Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" id="firstName" name="firstName" 
                                    value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="lastName">Last Name</label>
                            <div class="input-group">
                                <i class="fas fa-user"></i>
                                <input type="text" id="lastName" name="lastName" 
                                    value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="email" name="email" 
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <div class="input-group">
                            <i class="fas fa-phone"></i>
                            <input type="tel" id="phone" name="phone" 
                                value="<?php echo htmlspecialchars($user['phone']); ?>"
                                pattern="[0-9+\-\s()]*">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="address">Delivery Address</label>
                        <div class="input-group">
                            <i class="fas fa-map-marker-alt"></i>
                            <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-gradient">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </form>
            </div>

            <div class="profile-section security-section">
                <h2><i class="fas fa-lock"></i> Security Settings</h2>
                <form id="passwordForm" class="animated-form">
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="input-group">
                            <i class="fas fa-key"></i>
                            <input type="password" id="currentPassword" name="currentPassword" required>
                            <button type="button" class="toggle-password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="newPassword" name="newPassword" required>
                            <button type="button" class="toggle-password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-meter"></div>
                            <span class="strength-text"></span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="input-group">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirmPassword" name="confirmPassword" required>
                            <button type="button" class="toggle-password" tabindex="-1">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-match"></div>
                    </div>
                    
                    <button type="submit" class="btn-primary btn-gradient">
                        <i class="fas fa-shield-alt"></i> Update Password
                    </button>
                </form>
            </div>

            <!-- Preferences Section -->
            <div class="profile-section preferences-section" id="preferences-tab" style="display: none;">
                <h2><i class="fas fa-sliders-h"></i> Preferences</h2>
                <form id="preferencesForm" class="animated-form">
                    <div class="form-group">
                        <label>Language Preference</label>
                        <div class="input-group">
                            <i class="fas fa-language"></i>
                            <select id="language" name="language">
                                <option value="en">English</option>
                                <option value="ko">한국어 (Korean)</option>
                                <option value="fil">Filipino</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Notification Preferences</label>
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="orderUpdates" checked>
                                Order Updates
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="promotions" checked>
                                Promotions & Deals
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="newsletter">
                                Newsletter
                            </label>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary btn-gradient">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>

            <!-- Support Section -->
            <div class="profile-section support-section" id="support-tab" style="display: none;">
                <h2><i class="fas fa-headset"></i> Help Center</h2>
                
                <div class="support-categories">
                    <div class="support-card">
                        <i class="fas fa-book"></i>
                        <h3>User Guide</h3>
                        <p>Learn how to use KFood Delights features</p>
                        <a href="#" class="btn-link">Read More</a>
                    </div>

                    <div class="support-card">
                        <i class="fas fa-question-circle"></i>
                        <h3>FAQs</h3>
                        <p>Find answers to common questions</p>
                        <a href="#" class="btn-link">View FAQs</a>
                    </div>

                    <div class="support-card">
                        <i class="fas fa-ticket-alt"></i>
                        <h3>Support Ticket</h3>
                        <p>Create a ticket for specific issues</p>
                        <button class="btn-link" onclick="showTicketForm()">Create Ticket</button>
                    </div>

                    <div class="support-card">
                        <i class="fas fa-comments"></i>
                        <h3>Live Chat</h3>
                        <p>Chat with our support team</p>
                        <button class="btn-link" onclick="initLiveChat()">Start Chat</button>
                    </div>
                </div>

                <!-- Support Ticket Form -->
                <div id="ticketForm" class="support-form" style="display: none;">
                    <h3>Create Support Ticket</h3>
                    <form id="supportTicketForm" class="animated-form">
                        <div class="form-group">
                            <label>Subject</label>
                            <div class="input-group">
                                <i class="fas fa-tag"></i>
                                <input type="text" name="subject" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Category</label>
                            <div class="input-group">
                                <i class="fas fa-folder"></i>
                                <select name="category" required>
                                    <option value="">Select Category</option>
                                    <option value="order">Order Issues</option>
                                    <option value="account">Account Issues</option>
                                    <option value="payment">Payment Issues</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <div class="input-group">
                                <i class="fas fa-pen"></i>
                                <textarea name="description" rows="4" required></textarea>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Attachments</label>
                            <div class="input-group">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" name="attachments" multiple>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary btn-gradient">
                            <i class="fas fa-paper-plane"></i> Submit Ticket
                        </button>
                    </form>
                </div>
            </div>

            <!-- Order History Section -->
            <div class="profile-section order-history-section" id="orders-tab">
                <h2>
                    <i class="fas fa-history"></i> Order History
                    <a href="order_history.php" class="view-all-link">View All <i class="fas fa-arrow-right"></i></a>
                </h2>
                <div class="recent-orders">
                    <?php
                    // Fetch recent orders (last 3)
                    $recentOrdersQuery = "SELECT 
                                        o.*,
                                        COUNT(oi.item_id) as total_items,
                                        GROUP_CONCAT(
                                            CONCAT(
                                                COALESCE(oi.product_name, ''),
                                                ' x',
                                                COALESCE(oi.quantity, 0)
                                            ) SEPARATOR ', '
                                        ) as items_list 
                                        FROM orders o 
                                        LEFT JOIN order_items oi ON o.order_id = oi.order_id 
                                        WHERE o.user_id = ? 
                                        GROUP BY o.order_id 
                                        ORDER BY o.created_at DESC 
                                        LIMIT 3";
                    $stmt = $conn->prepare($recentOrdersQuery);
                    $stmt->bind_param("i", $user['user_id']);
                    $stmt->execute();
                    $recentOrders = $stmt->get_result();
                    
                    if ($recentOrders->num_rows > 0):
                        while ($order = $recentOrders->fetch_assoc()):
                    ?>
                        <div class="order-preview">
                            <div class="order-info">
                                <div class="order-id">Order #<?php echo str_pad($order['order_id'], 8, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
                            </div>
                            <div class="order-summary">
                                <span class="items"><?php echo $order['total_items']; ?> items</span>
                                <span class="total">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                            </div>
                            <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>
                    <?php 
                        endwhile;
                    else:
                    ?>
                        <p class="no-orders">No orders yet</p>
                    <?php endif; ?>
                    <div class="view-all">
                        <a href="order_history.php" class="btn-secondary">View All Orders</a>
                    </div>
                </div>
            </div>

            <div class="profile-section danger-zone">
                <h2>Account Management</h2>
                <p>This action cannot be undone.</p>
                <button onclick="confirmDeleteAccount()" class="btn-danger">Delete Account</button>
            </div>
        </div>
    </div>

    <style>
        .recent-orders {
            margin-top: 20px;
        }

        .order-preview {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        .order-preview:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .order-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .order-id {
            font-weight: 600;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 0.9em;
        }

        .order-summary {
            display: flex;
            justify-content: space-between;
            color: #666;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .order-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
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

        .no-orders {
            text-align: center;
            color: #666;
            padding: 20px;
        }

        .view-all {
            text-align: center;
            margin-top: 20px;
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            padding: 8px 20px;
            border-radius: 20px;
            text-decoration: none;
            transition: all 0.3s ease;
            border: 1px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #ccc;
        }
    </style>

    <!-- Success/Error Message Toast -->
    <div id="toast" class="toast"></div>

    <script src="js/profile.js"></script>
</body>
</html>
