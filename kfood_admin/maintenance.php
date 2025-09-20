<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/auth_check.php';
require_once 'modules/maintenance/MaintenanceManager.php';

// Check if user has maintenance privileges
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Initialize maintenance manager
$maintenanceManager = new MaintenanceManager($conn);

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = [];

    switch ($_POST['action']) {
        case 'backup':
            $response = $maintenanceManager->createBackup();
            break;
        case 'optimize':
            $response = $maintenanceManager->optimizeDatabase();
            break;
        case 'clear_cache':
            $response = $maintenanceManager->clearCache();
            break;
        case 'scan':
            $response = $maintenanceManager->scanSystem();
            break;
        case 'get_status':
            $response = ['success' => true, 'data' => $maintenanceManager->getSystemStatus()];
            break;
        default:
            $response = ['success' => false, 'message' => 'Invalid action'];
    }

    echo json_encode($response);
    exit;
}

// Get initial system status
$systemStatus = $maintenanceManager->getSystemStatus();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Maintenance - K-Food Delights Admin</title>
    <link rel="stylesheet" href="css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .maintenance-container {
            padding: var(--spacing-lg);
            max-width: 1200px;
            margin: 0 auto;
        }

        .maintenance-header {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(255, 102, 102, 0.3);
        }

        .maintenance-card {
            background: var(--surface-color);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .maintenance-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1);
        }

        .maintenance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .backup-section {
            background: linear-gradient(to right, rgba(255, 102, 102, 0.1), rgba(255, 128, 80, 0.05));
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 2rem;
            border: 1px solid var(--primary-color);
        }

        .action-button {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 102, 102, 0.3);
        }

        .action-button:hover {
            background: linear-gradient(135deg, var(--accent-color), var(--secondary-color));
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 102, 102, 0.4);
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-badge.active {
            background: linear-gradient(135deg, var(--success-color), #45a049);
            color: white;
        }

        .status-badge.inactive {
            background: linear-gradient(135deg, var(--error-color), #ff6666);
            color: white;
        }

        .maintenance-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-box {
            background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(255, 102, 102, 0.3);
        }

        .stat-box h3 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-box p {
            margin: 0;
            opacity: 0.9;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }

        .progress-bar .progress {
            height: 100%;
            background: linear-gradient(to right, var(--secondary-color), var(--accent-color));
            border-radius: 4px;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="maintenance-container">
            <div class="maintenance-header">
                <h1><i class="fas fa-tools"></i> System Maintenance</h1>
                <p>Manage system maintenance tasks and monitor system health</p>
            </div>

            <div class="maintenance-stats">
                <div class="stat-box">
                    <h3>98%</h3>
                    <p>System Uptime</p>
                    <div class="progress-bar">
                        <div class="progress" style="width: 98%"></div>
                    </div>
                </div>
                <div class="stat-box">
                    <h3>75%</h3>
                    <p>Storage Used</p>
                    <div class="progress-bar">
                        <div class="progress" style="width: 75%"></div>
                    </div>
                </div>
                <div class="stat-box">
                    <h3>45ms</h3>
                    <p>Response Time</p>
                    <div class="progress-bar">
                        <div class="progress" style="width: 45%"></div>
                    </div>
                </div>
            </div>

            <div class="maintenance-grid">
                <div class="maintenance-card">
                    <h2><i class="fas fa-database"></i> Database Maintenance</h2>
                    <p>Last optimization: 2 days ago</p>
                    <button class="action-button">
                        <i class="fas fa-sync"></i> Optimize Now
                    </button>
                </div>

                <div class="maintenance-card">
                    <h2><i class="fas fa-trash-alt"></i> Cache Management</h2>
                    <p>Cache size: 234 MB</p>
                    <button class="action-button">
                        <i class="fas fa-broom"></i> Clear Cache
                    </button>
                </div>

                <div class="maintenance-card">
                    <h2><i class="fas fa-shield-alt"></i> Security Scan</h2>
                    <p>Last scan: 1 day ago</p>
                    <button class="action-button">
                        <i class="fas fa-search"></i> Run Scan
                    </button>
                </div>
            </div>

            <div class="backup-section">
                <h2><i class="fas fa-server"></i> System Backup</h2>
                <p>Automatic backups are scheduled daily at midnight</p>
                <div style="margin-top: 1rem;">
                    <button class="action-button">
                        <i class="fas fa-download"></i> Manual Backup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Utility function for showing notifications
        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.innerHTML = message;
            document.querySelector('.maintenance-container').prepend(notification);
            
            setTimeout(() => notification.remove(), 5000);
        }

        // Utility function for API calls
        async function apiCall(action, data = {}) {
            try {
                const response = await fetch('maintenance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action,
                        ...data
                    })
                });
                
                return await response.json();
            } catch (error) {
                console.error('API Error:', error);
                showNotification(error.message, 'error');
                return { success: false, message: error.message };
            }
        }

        // Update system stats
        async function updateSystemStatus() {
            const response = await apiCall('get_status');
            if (response.success) {
                const status = response.data;
                
                // Update uptime
                document.querySelector('.stat-box:nth-child(1) h3').textContent = status.uptime.percentage + '%';
                document.querySelector('.stat-box:nth-child(1) .progress').style.width = status.uptime.percentage + '%';
                
                // Update storage
                document.querySelector('.stat-box:nth-child(2) h3').textContent = status.storage.percentage + '%';
                document.querySelector('.stat-box:nth-child(2) p').textContent = `${status.storage.used} / ${status.storage.total}`;
                document.querySelector('.stat-box:nth-child(2) .progress').style.width = status.storage.percentage + '%';
                
                // Update response time
                document.querySelector('.stat-box:nth-child(3) h3').textContent = status.response_time + 'ms';
                document.querySelector('.stat-box:nth-child(3) .progress').style.width = 
                    Math.min((status.response_time / 100) * 100, 100) + '%';
                
                // Update last backup info
                if (status.last_backup) {
                    document.querySelector('.backup-section p').textContent = 
                        `Last backup: ${new Date(status.last_backup.created_at).toLocaleString()}`;
                }
                
                // Update cache size
                document.querySelector('.maintenance-card:nth-child(2) p').textContent = 
                    `Cache size: ${status.cache_size}`;
            }
        }

        // Initialize action buttons
        document.querySelectorAll('.action-button').forEach(button => {
            // Store original HTML
            button.originalHTML = button.innerHTML;
            
            button.addEventListener('click', async function() {
                // Add loading state
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                this.disabled = true;
                
                let action;
                if (this.innerHTML.includes('Backup')) action = 'backup';
                else if (this.innerHTML.includes('Optimize')) action = 'optimize';
                else if (this.innerHTML.includes('Cache')) action = 'clear_cache';
                else if (this.innerHTML.includes('Scan')) action = 'scan';
                
                if (action) {
                    const response = await apiCall(action);
                    
                    if (response.success) {
                        showNotification(response.message);
                        // Update system status after successful action
                        await updateSystemStatus();
                    } else {
                        showNotification(response.message, 'error');
                    }
                }
                
                // Restore button state
                this.innerHTML = this.originalHTML;
                this.disabled = false;
            });
        });

        // Update system status periodically
        setInterval(updateSystemStatus, 30000); // Every 30 seconds
        
        // Initial update
        updateSystemStatus();
    </script>
</body>
</html>
