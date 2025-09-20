<?php
require_once 'config.php';
require_once 'includes/SessionManager.php';

$sessionManager = new SessionManager();
$sessionManager->startSecureSession();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized Access | K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="shortcut icon" href="../logo-tab-icon.ico" type="image/x-icon" />
    <style>
        .error-container {
            text-align: center;
            padding: 50px 20px;
            max-width: 600px;
            margin: 50px auto;
        }
        .error-icon {
            font-size: 48px;
            color: #ff6666;
            margin-bottom: 20px;
        }
        .error-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
        }
        .error-message {
            color: #666;
            margin-bottom: 25px;
        }
        .back-link {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ff6666;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .back-link:hover {
            background-color: #ff4444;
        }
    </style>
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="error-container">
        <div class="error-icon">
            <i class="fas fa-exclamation-circle"></i>
        </div>
        <h1 class="error-title">Unauthorized Access</h1>
        <p class="error-message">You do not have permission to access this page. Please log in with an appropriate account.</p>
        <a href="login.php" class="back-link">Back to Login</a>
    </div>

    <?php include 'includes/logout-modal.php'; ?>
</body>
</html>