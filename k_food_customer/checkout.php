<?php

// Start session and include configuration
session_start();
include 'config.php';

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Generate WebSocket auth token
if (empty($_SESSION['ws_token'])) {
    $_SESSION['ws_token'] = bin2hex(random_bytes(32));
}

// WebSocket Configuration
$wsConfig = [
    'port' => 8080,
    'host' => '127.0.0.1',
    'path' => '/ws',
    'token' => $_SESSION['ws_token']
];

// Pass WebSocket config to JavaScript
echo "<script>
    window.WS_CONFIG = " . json_encode($wsConfig) . ";
    window.WS_AUTH_TOKEN = '" . $_SESSION['ws_token'] . "';
</script>";

// Maps integration temporarily removed
// include 'includes/maps_config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch user information from database with validation
$userId = $_SESSION['user_id'];
$query = "SELECT first_name, last_name, email, phone, address FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    error_log("Failed to prepare user info query: " . $conn->error);
    die("System error occurred. Please try again later.");
}

$stmt->bind_param("i", $userId);
if (!$stmt->execute()) {
    error_log("Failed to execute user info query: " . $stmt->error);
    die("Failed to retrieve user information.");
}

$result = $stmt->get_result();
$userInfo = $result->fetch_assoc();
$stmt->close();

// Validate required user information (must have all fields before proceeding)
if (!$userInfo) {
    error_log("User information not found for ID: " . $userId);
    die("User profile data not found. Please update your profile first.");
}

foreach (['first_name', 'last_name', 'email', 'phone', 'address'] as $field) {
    if (empty($userInfo[$field])) {
        error_log("Missing required user field: " . $field);
        die("Please complete your profile information before checkout. Missing: " . ucfirst(str_replace('_', ' ', $field)));
    }
}

// Validate email format
if (!filter_var($userInfo['email'], FILTER_VALIDATE_EMAIL)) {
    error_log("Invalid email format for user: " . $userId);
    die("Invalid email format in profile. Please update your profile.");
}

// Format full name for order
$fullName = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Map handler for confirmLocation() -->
    <script src="js/map-handler.js"></script>
    <script>
        // Debug logging
        console.log('Checkout page loaded');
        window.addEventListener('DOMContentLoaded', () => {
            console.log('DOM Content loaded');
            console.log('WebSocket Config:', window.WS_CONFIG);
            console.log('Auth Token:', window.WS_AUTH_TOKEN);
        });
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <title>Checkout - K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/cart.css">
    <link rel="stylesheet" href="css/payment.css">
    <link rel="stylesheet" href="css/notifications.css">
    <link rel="stylesheet" href="css/order-confirmation-modal.css">
    <style>
        /* Form validation styles */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
            display: none;
            animation: fadeIn 0.2s ease-in;
        }

        .form-group input.invalid,
        .form-group textarea.invalid {
            border-color: #dc3545;
            background-color: #fff8f8;
        }

        .form-group input.invalid:focus,
        .form-group textarea.invalid:focus {
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
        }
        <style>

        .order-summary {
            margin: 20px 0;
        }

        .total-section {
            background: #fff9f9;
            padding: 20px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-family: 'Poppins', sans-serif;
        }

        .summary-section:last-child {
            border-bottom: none;
        }

        .total-label {
            color: #666;
            font-size: 1.1em;
        }

        .total-value {
            font-weight: 600;
            color: #ff6b6b;
            font-size: 1.2em;
        }

        .order-item {
            background: white;
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s ease;
        }

        .order-item:hover {
            transform: translateY(-2px);
        }

        .item-name {
            font-weight: 500;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .item-price {
            font-weight: 600;
            color: #ff6b6b;
            font-family: 'Poppins', sans-serif;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #fff1f1;
        }

        .btn-confirm {
            background: linear-gradient(145deg, #ff6b6b, #ff8989);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.2);
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.3);
        }

        .btn-cancel {
            background: transparent;
            color: #666;
            border: 2px solid #eee;
            padding: 12px 28px;
            border-radius: 25px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            border-color: #ff6b6b;
            color: #ff6b6b;
            background: #fff8f8;
        }

        .error-message {
            color: #f44336;
            font-size: 0.875em;
            margin-top: 5px;
            display: none;
        }

        .form-group {
            position: relative;
            margin-bottom: 20px;
        }

        input.invalid {
            border-color: #f44336;

            width: 92%;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
            animation: slideIn 0.4s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
        }

        .modal-header {
            padding: 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to right, #fff9f9, #fff);
        }

        .modal-header h2 {
            margin: 0;
            color: #ff6666;
            font-size: 1.75rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }

        .close-modal {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 50%;
            background: transparent;
        }

        .close-modal:hover {
            background: rgba(255, 102, 102, 0.1);
            color: #ff6666;
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 24px;
            max-height: 60vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #ff6666 #f0f0f0;
        }

        .modal-body::-webkit-scrollbar {
            width: 8px;
        }

        .modal-body::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 4px;
        }

        .modal-body::-webkit-scrollbar-thumb {
            background: #ff6666;
            border-radius: 4px;
        }

        .modal-footer {
            padding: 20px 24px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #fafafa;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            font-size: 1rem;
            letter-spacing: 0.3px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ff6666, #ff8080);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 102, 102, 0.2);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #ff5252, #ff6666);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(255, 102, 102, 0.25);
        }

        .btn-secondary {
            background-color: #f8f9fa;
            color: #495057;
            border: 1px solid #e9ecef;
        }

        .btn-secondary:hover {
            background-color: #e9ecef;
            border-color: #dee2e6;
            transform: translateY(-1px);
        }

        /* Confirmation Dialog Styles */
        .confirmation-dialog {
            max-width: 500px;
            text-align: center;
        }

        .confirmation-dialog .modal-body {
            padding: 2rem;
        }

        .amount-confirmation {
            margin: 1.5rem 0;
            font-size: 1.2rem;
            color: #ff6b6b;
        }

        .confirmation-message {
            margin-top: 2rem;
            padding: 1rem;
            background: #fff9f9;
            border-radius: 8px;
            text-align: center;
            color: #666;
        }

        .final-total {
            font-size: 1.25rem;
            color: #ff6b6b;
            border-top: 2px solid #ffe9e9;
            padding-top: 1rem;
            margin-top: 1rem;
        }

        .item-row, .total-row {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 5px 0;
        }

        .total-row.total {
            border-top: 2px solid #e5e5e5;
            margin-top: 10px;
            padding-top: 10px;
            font-weight: bold;
        }

        .info-row {
            margin: 8px 0;
            line-height: 1.4;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Notification Styles */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 25px;
            border-radius: 8px;
            color: white;
            font-weight: 500;
            z-index: 1200;
            animation: slideIn 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            max-width: 90%;
            width: 400px;
        }

        .notification.success {
            background: linear-gradient(135deg, #2ecc71, #27ae60);
        }

        .notification.error {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .checkout-container {
            max-width: 1200px;
            margin: 80px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
        }

        .checkout-form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
        }

        .order-summary {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }

        .order-items-container {
            max-height: 300px;
            overflow-y: auto;
            margin-bottom: 20px;
            padding-right: 10px;
        }

        .order-items-container::-webkit-scrollbar {
            width: 6px;
        }

        .order-items-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .order-items-container::-webkit-scrollbar-thumb {
            background: #ff6666;
            border-radius: 3px;
        }

        .order-items-container::-webkit-scrollbar-thumb:hover {
            background: #ff4d4d;
        }

        .back-home-btn {
            position: fixed;
            top: 100px;
            left: 20px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            z-index: 100;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-home-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            background: linear-gradient(135deg, #ff8c66, #ff6666);
        }

        /* Confirmation Modal Styles */
        .confirmation-modal {
            display: none;
            position: fixed;
            z-index: 1100;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.6);
            backdrop-filter: blur(3px);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            animation: fadeIn 0.3s ease;
        }

        .confirmation-modal-content {
            background: white;
            margin: 15% auto;
            padding: 35px;
            width: 90%;
            max-width: 480px;
            border-radius: 20px;
            position: relative;
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .confirmation-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .confirmation-modal.active .confirmation-modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        /* Success Modal Styles */
        .success-modal {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease;
        }

        .success-modal h2 {
            color: #333;
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .success-modal p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .success-modal .order-number {
            font-weight: bold;
            color: #ff6666;
        }

        .view-order-btn {
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .view-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 102, 102, 0.2);
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
                opacity: 0;
            }
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            transform: translateY(-20px);
            opacity: 0;
            transition: all 0.3s ease-out;
        }

        .modal.active .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .success-modal {
            text-align: center;
            padding: 2rem;
        }

        .success-icon {
            font-size: 4rem;
            color: #28a745;
            margin-bottom: 1.5rem;
            animation: bounceIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
        }

        @keyframes bounceIn {
            0% { transform: scale(0); opacity: 0; }
            50% { transform: scale(1.2); }
            70% { transform: scale(0.9); }
            100% { transform: scale(1); opacity: 1; }
        }

        .success-modal h2 {
            color: #333;
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .success-modal p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .order-number {
            font-size: 1.2rem;
            color: #ff6666;
            font-weight: 600;
            margin: 1rem 0;
        }

        .view-order-btn {
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 102, 102, 0.2);
        }

        .view-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 102, 102, 0.3);
        }

        .confirmation-modal h2 {
            color: #333;
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 20px;
            text-align: center;
        }

        .confirmation-modal p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        .modal-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .confirm-btn {
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
        }

        .confirm-btn:hover {
            background: linear-gradient(135deg, #ff4d4d, #ff704d);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 102, 102, 0.2);
        }

        .cancel-btn {
            background: #f5f5f5;
            color: #333;
        }

        .cancel-btn:hover {
            background: #ebebeb;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
        }

        .modal-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .form-section {
            margin-bottom: 30px;
        }

        .form-section h2 {
            font-size: 1.5rem;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ff6666;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #ff6666;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 102, 102, 0.1);
        }

        .payment-methods {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
            padding: 0 10px;
        }

        .payment-method {
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .payment-method:hover {
            border-color: #ff6666;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255,102,102,0.15);
        }

        .payment-method.selected {
            border-color: #ff6666;
            background: linear-gradient(to bottom, #fff5f5, #fff);
            box-shadow: 0 4px 12px rgba(255,102,102,0.2);
        }

        .payment-method img {
            height: 48px;
            width: auto;
            margin-bottom: 8px;
            transition: transform 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .payment-method:hover img {
            transform: scale(1.05);
        }

        .payment-method div {
            font-weight: 500;
            color: #333;
            font-size: 1.1rem;
            font-family: 'Poppins', sans-serif;
        }

        .payment-method.selected div {
            color: #ff6666;
        }

        .order-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 15px;
        }

        .order-item-details {
            flex-grow: 1;
        }

        .order-item-price {
            font-weight: bold;
            color: #ff6666;
        }

        .order-totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }

        .final-total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #eee;
        }

        .place-order-btn {
            background: linear-gradient(135deg, #ff6666, #ffaf00);
            color: white;
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
        }

        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        #gcash-details {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        #gcash-details.active {
            display: block;
        }

        .gcash-instruction {
            margin-bottom: 10px;
            font-size: 0.9rem;
            color: #666;
        }

        .qr-code {
            max-width: 200px;
            margin: 15px auto;
            display: block;
        }

        .promo-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .promo-input {
            display: flex;
            gap: 10px;
        }

        .apply-promo-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .apply-promo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .discount-options {
            margin-top: 15px;
            display: grid;
            gap: 10px;
        }

        .discount-options .form-group {
            margin: 0;
        }

        .discount-options label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .id-input {
            margin-top: 10px;
            margin-left: 25px;
        }

        .discount-row {
            color: #28a745;
            font-weight: 500;
        }

        .savings-row {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
            font-size: 0.9em;
            color: #28a745;
        }

        .savings-amount {
            font-weight: 600;
        }

        .promo-applied {
            background: #d4edda;
            color: #155724;
            padding: 8px 12px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 0.9em;
            display: none;
        }

        /* Buy 3 Get 1 Promo Display */
        .buy3get1-alert {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
        }

        .buy3get1-alert.active {
            display: block;
        }

        .promo-progress {
            margin-top: 8px;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
        }

        .promo-progress-bar {
            height: 100%;
            background: linear-gradient(135deg, #ffc107, #ffaf00);
            width: 0;
            transition: width 0.3s ease;
        }

        /* Enhanced Promo Section Styles */
        .promo-section {
            margin: 20px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }

        .promo-section h3 {
            margin: 0 0 15px 0;
            font-size: 1.2rem;
            color: #333;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            padding: 8px 0;
        }

        .checkbox-text {
            font-size: 14px;
            color: #495057;
        }

        .promo-input {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .promo-input input {
            flex: 1;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        .apply-promo-btn {
            padding: 12px 20px;
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }

        .apply-promo-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .promo-applied {
            display: none;
            background: #d4edda;
            color: #155724;
            padding: 10px 15px;
            border-radius: 8px;
            margin-top: 10px;
            font-size: 14px;
            animation: fadeIn 0.3s ease;
        }

        .promo-applied i {
            margin-right: 5px;
            color: #28a745;
        }

        .id-input {
            margin: 10px 0 10px 25px;
        }

        .id-input input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }

        /* Improved Order Summary Totals */
        .order-totals {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #eee;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
            color: #495057;
        }

        .discount-row {
            color: #28a745;
        }

        .final-total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 2px solid #eee;
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <style>
        input[readonly] {
            background-color: #f8f9fa;
            color: #495057;
            cursor: not-allowed;
            border-color: #e9ecef;
        }

        .readonly-info {
            background-color: #f8f9fa;
            padding: 12px;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            color: #495057;
            margin: 0 0 15px 0;
            line-height: 1.5;
        }

        /* Map Container Improvements */
        .map-container {
            margin: 15px 0;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            border: 1px solid #e9ecef;
        }

        #map {
            width: 100%;
            height: 300px;
            background: #f8f9fa;
        }

        .map-search-container {
            padding: 15px;
            background: white;
            border-bottom: 1px solid #e9ecef;
        }

        .map-search-box {
            position: relative;
            margin-bottom: 0;
        }

        #pac-input {
            width: 100%;
            padding: 12px 40px 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        #pac-input:focus {
            border-color: #ff6666;
            box-shadow: 0 0 0 2px rgba(255, 102, 102, 0.1);
        }

        .location-details {
            padding: 15px;
            background: white;
            border-top: 1px solid #e9ecef;
        }

        .location-info {
            margin-top: 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
        }

        .location-info p {
            margin: 8px 0;
            color: #495057;
        }

        .location-info strong {
            color: #333;
            min-width: 100px;
            display: inline-block;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            padding: 4px;
            z-index: 1;
        }

        .map-control-btn {
            width: 36px;
            height: 36px;
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
            margin: 2px;
        }

        .map-control-btn:hover {
            background: #f8f9fa;
            color: #ff6666;
        }

        .map-control-btn.active {
            background: #ff6666;
            color: white;
        }

        .confirm-location-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #ff6666, #ff8c66);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .confirm-location-btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .confirm-location-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Order Summary Improvements */
        .order-summary h2 {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #ff6666;
            color: #333;
            font-size: 1.5rem;
        }

        .order-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .order-item:last-child {
            border-bottom: none;
        }

        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .item-total {
            color: #ff6666;
            font-weight: 600;
        }

        /* Google Maps Integration Styles */
        .map-container {
            margin-top: 15px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        #map {
            width: 100%;
            height: 300px;
            border-radius: 8px;
        }

        .map-search-box {
            margin-bottom: 15px;
            position: relative;
        }

        #pac-input {
            width: 100%;
            padding: 12px;
            padding-right: 40px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        #pac-input:focus {
            border-color: #ff6666;
            box-shadow: 0 0 0 2px rgba(255, 102, 102, 0.1);
            outline: none;
        }

        .map-controls {
            position: absolute;
            top: 10px;
            right: 10px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1;
        }

        .map-control-btn {
            padding: 8px;
            background: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            color: #666;
            transition: all 0.2s ease;
        }

        .map-control-btn:hover {
            background: #f8f9fa;
            color: #ff6666;
        }

        .map-control-btn.active {
            background: #ff6666;
            color: white;
        }

        .location-info {
            margin-top: 15px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #495057;
        }

        .location-info p {
            margin: 5px 0;
        }

        .confirm-location-btn {
            margin-top: 10px;
            padding: 8px 16px;
            background: #ff6666;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .confirm-location-btn:hover {
            background: #ff4d4d;
            transform: translateY(-1px);
        }
    </style>
    <!-- Temporarily using lite version -->
</head>
<body class="logged-in">
    <?php include 'includes/nav.php'; ?>
    
    <!-- Temporary notice -->
    <div style="background-color: #fff3cd; color: #856404; padding: 12px; margin: 10px; border-radius: 4px; text-align: center;">
        <i class="fas fa-info-circle"></i> Delivery address selection is temporarily disabled. Your default address will be used.
    </div>

    <!-- Back to Home Button -->
    <button class="back-home-btn" onclick="showConfirmationModal()">
        <i class="fas fa-arrow-left"></i> Back to Home
    </button>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal">
        <div class="confirmation-modal-content">
            <h2>Cancel Order?</h2>
            <p>Are you sure you want to leave? Your order will be cancelled and all items will be removed from your cart.</p>
            <div class="modal-buttons">
                <button class="modal-btn cancel-btn" onclick="hideConfirmationModal()">
                    <i class="fas fa-times"></i> Stay Here
                </button>
                <button class="modal-btn confirm-btn" onclick="returnToHome()">
                    <i class="fas fa-check"></i> Yes, Leave
                </button>
            </div>
        </div>
    </div>

    <div class="checkout-container">
        <div class="checkout-form">
            <form id="checkoutForm" method="POST" action="process_order.php" autocomplete="off">
                <!-- Hidden userId for AJAX authentication -->
                <input type="hidden" id="userId" name="userId" value="<?php echo htmlspecialchars($userId); ?>">
                <!-- Hidden cart data for order processing -->
                <input type="hidden" id="cart-data" name="cartData" value="<?php echo htmlspecialchars(isset($_SESSION['cart']) ? json_encode($_SESSION['cart']) : '[]'); ?>">
                <div class="form-section">
                    <h2>Billing Information</h2>
                    <div class="form-group">
                        <label for="customerName">Full Name</label>
                        <input type="text" 
                               id="customerName" 
                               name="customerName" 
                               value="<?php echo htmlspecialchars($fullName); ?>" 
                               required 
                               readonly>
                        <div class="error-message" id="customerNameError"></div>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($userInfo['email']); ?>" 
                               required 
                               readonly>
                        <div class="error-message" id="emailError"></div>
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($userInfo['phone']); ?>" 
                               pattern="[0-9]{11}" 
                               title="Please enter a valid 11-digit phone number"
                               required 
                               readonly>
                        <div class="error-message" id="phoneError"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Delivery Information</h2>
                    <div class="form-group">
                        <label>Delivery Address</label>
                        <div class="map-container">
                            <div class="map-search-container">
                                <div class="map-search-box">
                                    <input id="pac-input" type="text" placeholder="Search for your delivery address" 
                                        value="<?php echo htmlspecialchars($userInfo['address']); ?>">
                                </div>
                            </div>
                            <div class="map-wrapper">
                                <div class="map-controls">
                                    <button type="button" class="map-control-btn active" data-map-type="roadmap" title="Street Map" aria-label="Switch to street map view">
                                        <i class="fas fa-map" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="map-control-btn" data-map-type="satellite" title="Satellite View" aria-label="Switch to satellite view">
                                        <i class="fas fa-satellite" aria-hidden="true"></i>
                                    </button>
                                    <button type="button" class="map-control-btn" onclick="getCurrentLocation()" title="Use Current Location" aria-label="Use your current location">
                                        <i class="fas fa-location-crosshairs" aria-hidden="true"></i>
                                    </button>
                                </div>
                                <div id="map"></div>
                            </div>
                            <div class="location-details">
                                <div class="location-info">
                                    <p>
                                        <strong>Address:</strong> 
                                        <span id="formatted-address"><?php echo htmlspecialchars($userInfo['address']); ?></span>
                                    </p>
                                    <p>
                                        <strong>Landmark:</strong> 
                                        <span id="landmark">Not specified</span>
                                    </p>
                                </div>
                                <button type="button" class="confirm-location-btn" onclick="confirmLocation()">
                                    <i class="fas fa-check-circle"></i> Confirm Location
                                </button>
                            </div>
                        </div>
                        <label for="address" class="sr-only">Delivery Address</label>
                        <input type="text" 
                               id="address" 
                               name="address" 
                               value="<?php echo htmlspecialchars($userInfo['address']); ?>"
                               required
                               readonly
                               aria-label="Selected delivery address">
                        <div class="error-message" id="addressError"></div>
                        <input type="hidden" id="latitude" name="latitude" value="">
                        <input type="hidden" id="longitude" name="longitude" value="">
                    </div>
                    <div class="form-group">
                        <label for="deliveryInstructions">Delivery Instructions (Optional)</label>
                        <textarea id="deliveryInstructions" 
                                name="deliveryInstructions" 
                                rows="3" 
                                placeholder="Add any special instructions for delivery (e.g., landmarks, gate codes, preferred entrance)"></textarea>
                        <div class="error-message" id="instructionsError"></div>
                    </div>
                </div>

                <div class="form-section">
                    <h2>Payment Method</h2>
                    <div class="payment-methods">
                        <label class="payment-method-option" style="display: flex; align-items: center; gap: 12px; background: #fff6f0; border-radius: 12px; padding: 12px 18px; margin-bottom: 12px; cursor: pointer; border: 2px solid #ffe0d2;">
                            <input type="radio" name="paymentMethod" value="cash" style="accent-color: #ff6b6b; width: 20px; height: 20px; margin-right: 10px;">
                            <img src="../resources/images/cash-icon.png" alt="Cash on Delivery" style="width: 36px; height: 36px;">
                            <span style="font-size: 1.1em; color: #d35400; font-weight: 500;">Cash on Delivery</span>
                        </label>
                        <label class="payment-method-option" style="display: flex; align-items: center; gap: 12px; background: #f0f8ff; border-radius: 12px; padding: 12px 18px; margin-bottom: 12px; cursor: pointer; border: 2px solid #b2e0ff;">
                            <input type="radio" name="paymentMethod" value="gcash" style="accent-color: #0091ea; width: 20px; height: 20px; margin-right: 10px;">
                            <img src="../resources/images/gcash-icon.png" alt="GCash" style="width: 36px; height: 36px;">
                            <span style="font-size: 1.1em; color: #0077b6; font-weight: 500;">GCash</span>
                        </label>
                    </div>

                    <div id="gcash-details">
                        <div class="gcash-instruction">Please scan the QR code or send payment to:</div>
                        <div class="gcash-instruction">GCash Number: 09XX-XXX-XXXX</div>
                        <img src="../resources/images/gcash-qr.png" alt="GCash QR Code" class="qr-code">
                        <div class="form-group">
                            <label for="gcashReference">GCash Reference Number</label>
                            <label for="gcashReference">GCash Reference Number</label>
                            <input type="text" 
                                   id="gcashReference" 
                                   name="gcashReference"
                                   placeholder="Enter your GCash reference number"
                                   aria-label="GCash reference number">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="order-summary">
            <h2>Order Summary</h2>
            <div class="order-items-container">
                <div id="orderItems">
                    <!-- Order items will be populated by JavaScript -->
                </div>
            </div>

            <!-- Buy 3 Get 1 Alert -->
            <div class="buy3get1-alert">
                <div><strong>Buy 3 Get 1 Free Progress</strong></div>
                <div>Add <span class="items-needed">3</span> more eligible items to get 1 free!</div>
                <div class="promo-progress">
                    <div class="promo-progress-bar"></div>
                </div>
            </div>

            <div class="promo-section">
                <h3>Discounts & Promotions</h3>
                <div class="form-group">
                    <label for="promoCode">Promo Code</label>
                    <div class="promo-input">
                        <label for="promoCode" class="sr-only">Promo Code</label>
                        <input type="text" 
                               id="promoCode" 
                               name="promoCode" 
                               placeholder="Enter promo code"
                               aria-label="Enter promo code">
                        <button type="button" class="apply-promo-btn" onclick="applyPromoCode()">Apply</button>
                    </div>
                    <div class="promo-applied">
                        <i class="fas fa-check-circle"></i> Promo code applied successfully!
                    </div>
                </div>

                <div class="discount-options">
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="seniorDiscount" name="seniorDiscount" onchange="updateDiscounts()">
                            <span class="checkbox-text">Senior Citizen Discount (20% off)</span>
                        </label>
                        <div class="id-input" id="seniorIdInput" style="display: none;">
                            <label for="seniorId" class="sr-only">Senior Citizen ID</label>
                            <input type="text" 
                                   id="seniorId" 
                                   name="seniorId" 
                                   placeholder="Enter Senior Citizen ID"
                                   aria-label="Senior Citizen ID number">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="pwdDiscount" name="pwdDiscount" onchange="updateDiscounts()">
                            <span class="checkbox-text">PWD Discount (15% off)</span>
                        </label>
                        <div class="id-input" id="pwdIdInput" style="display: none;">
                            <label for="pwdId" class="sr-only">PWD ID</label>
                            <input type="text" 
                                   id="pwdId" 
                                   name="pwdId" 
                                   placeholder="Enter PWD ID"
                                   aria-label="PWD (Person with Disability) ID number">
                        </div>
                    </div>
                </div>
            </div>
            <div class="order-totals">
                <div class="total-row">
                    <span>Subtotal</span>
                    <span id="subtotal">₱0.00</span>
                </div>
                <div class="total-row discount-row" style="display: none;">
                    <span>Promo Discount</span>
                    <span id="promoDiscount">-₱0.00</span>
                </div>
                <div class="total-row discount-row" style="display: none;">
                    <span>Senior/PWD Discount</span>
                    <span id="seniorPwdDiscount">-₱0.00</span>
                </div>
                <div class="total-row savings-row" style="display: none;">
                    <span>Total Savings</span>
                    <span id="totalSavings" class="savings-amount">₱0.00</span>
                </div>
                <div class="final-total">
                    <span>Total</span>
                    <span id="totalAmount">₱0.00</span>
                </div>
            </div>
            <button type="button" class="place-order-btn" id="placeOrderBtn">Place Order</button>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    <div id="orderConfirmationModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-utensils"></i> Confirm Your Order</h2>
                <button type="button" class="modal-close" onclick="OrderConfirmationHandler.close()" aria-label="Close order confirmation modal">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="modal-body">
                <!-- Order Summary -->
                <div class="summary-section">
                    <h3><i class="fas fa-shopping-basket"></i> Order Items</h3>
                    <div class="order-items" id="modalOrderItems">
                        <!-- Items will be inserted here -->
                    </div>
                </div>

                <!-- Delivery Details -->
                <div class="summary-section">
                    <h3><i class="fas fa-shipping-fast"></i> Delivery Details</h3>
                    <div class="delivery-info">
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-user"></i> Name:</span>
                            <span class="info-value" id="modalCustomerName"></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-phone"></i> Phone:</span>
                            <span class="info-value" id="modalCustomerPhone"></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-map-marker-alt"></i> Address:</span>
                            <span class="info-value" id="modalDeliveryAddress"></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="fas fa-info-circle"></i> Special Instructions:</span>
                            <span class="info-value" id="modalDeliveryInstructions"></span>
                        </div>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="summary-section">
                    <h3><i class="fas fa-receipt"></i> Payment Summary</h3>
                    <div class="total-section">
                        <div class="total-row">
                            <span class="total-label">Subtotal:</span>
                            <span class="total-value">₱<span id="modalSubtotal"></span></span>
                        </div>
                        <div class="total-row">
                        <div class="total-row" id="modalDiscountRow" style="display: none;">
                            <span class="total-label">Discounts:</span>
                            <span class="total-value" style="color: #2ecc71">-₱<span id="modalDiscounts"></span></span>
                        </div>
                        <div class="total-row" style="margin-top: 10px; border-top: 2px dashed #fff1f1; padding-top: 15px;">
                            <span class="total-label" style="font-size: 1.2em; font-weight: 600;">Total Amount:</span>
                            <span class="total-value" style="font-size: 1.3em;">₱<span id="modalTotalAmount"></span></span>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="OrderConfirmationHandler.close()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="button" class="btn-confirm" onclick="OrderConfirmationHandler.processOrder()">
                        <i class="fas fa-check"></i> Place Order
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Error handling and notification system
    const NotificationType = {
        SUCCESS: 'success',
        ERROR: 'error',
        WARNING: 'warning',
        INFO: 'info'
    };

    class NotificationManager {
        // Add init method to avoid TypeError when notificationManager.init() is called
        init() {
            // No initialization needed, present for compatibility
        }
        constructor() {
            this.container = document.createElement('div');
            this.container.className = 'notification-container';
            document.body.appendChild(this.container);
        }

        show(message, type = NotificationType.INFO) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.setAttribute('role', 'alert');
            
            // Add appropriate icon
            const icon = this.getIcon(type);
            notification.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span class="notification-message">${message}</span>
                <button class="notification-close" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            `;

            // Add close button handler
            const closeBtn = notification.querySelector('.notification-close');
            closeBtn.addEventListener('click', () => this.dismiss(notification));

            // Add to container
            this.container.appendChild(notification);

            // Animate in
            requestAnimationFrame(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            });

            // Auto dismiss after delay
            setTimeout(() => this.dismiss(notification), 5000);
        }

        dismiss(notification) {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => notification.remove(), 300);
        }

        getIcon(type) {
            switch(type) {
                case NotificationType.SUCCESS: return 'check-circle';
                case NotificationType.ERROR: return 'exclamation-circle';
                case NotificationType.WARNING: return 'exclamation-triangle';
                default: return 'info-circle';
            }
        }
    }

    // Cart manager for handling cart operations
    class CartManager {
        constructor() {
            this.items = this.loadCart();
        }

        init() {
            this.updateDisplay();
            this.updateCartData();
        }

        updateCartData() {
            // Update hidden input with current cart data
            const cartInput = document.getElementById('cart-data');
            if (cartInput && Array.isArray(this.items)) {
                // Validate and normalize cart items
                const validItems = this.items.map(item => ({
                    product_id: item.product_id || item.id,
                    name: item.name,
                    price: parseFloat(item.price) || 0,
                    quantity: parseInt(item.quantity) || 0,
                    image: item.image
                })).filter(item => 
                    item.product_id && 
                    item.quantity > 0 && 
                    item.price > 0
                );
                cartInput.value = JSON.stringify(validItems);
                this.items = validItems; // Update the items array with validated data
            }
        }

        loadCart() {
            try {
                const cartData = JSON.parse(localStorage.getItem('cart') || '[]');
                // Ensure each item has required fields
                return cartData.map(item => ({
                    product_id: item.product_id || item.id, // Maintain backward compatibility
                    name: item.name,
                    price: parseFloat(item.price) || 0,
                    quantity: parseInt(item.quantity) || 0,
                    image: item.image
                })).filter(item => 
                    item.product_id && 
                    item.quantity > 0 && 
                    item.price > 0
                );
            } catch (e) {
                console.error('Failed to load cart:', e);
                return [];
            }
        }

        createHiddenInput() {
            // Create hidden input if it doesn't exist
            let cartInput = document.getElementById('cart-data');
            if (!cartInput) {
                cartInput = document.createElement('input');
                cartInput.type = 'hidden';
                cartInput.id = 'cart-data';
                document.getElementById('checkoutForm')?.appendChild(cartInput);
            }
            this.updateHiddenInput();
        }

        updateHiddenInput() {
            const cartInput = document.getElementById('cart-data');
            if (cartInput && Array.isArray(this.items)) {
                // Ensure each item has the required fields
                const validatedItems = this.items.map(item => ({
                    product_id: item.product_id || item.id, // Support both formats
                    name: item.name,
                    price: item.price,
                    quantity: parseInt(item.quantity) || 0,
                    image: item.image
                })).filter(item => 
                    item.product_id && 
                    item.quantity > 0 && 
                    typeof item.price === 'number'
                );
                cartInput.value = JSON.stringify(validatedItems);
            }
        }

        updateDisplay() {
            this.updateHiddenInput();
            const orderItems = document.getElementById('orderItems');
            if (!orderItems) return;

            let html = '';
            let subtotal = 0;

            this.items.forEach(item => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                html += `
                    <div class="order-item">
                        <img src="${item.image || ''}" alt="${item.name}">
                        <div class="order-item-details">
                            <div class="item-name">${item.name}</div>
                            <div class="item-quantity">Quantity: ${item.quantity}</div>
                        </div>
                        <div class="item-total">₱${itemTotal.toFixed(2)}</div>
                    </div>
                `;
            });

            orderItems.innerHTML = html;
            this.updateTotals(subtotal);
        }

        updateTotals(subtotal) {
            const elements = {
                subtotal: document.getElementById('subtotal'),

                totalAmount: document.getElementById('totalAmount')
            };

            if (elements.subtotal) {
                elements.subtotal.textContent = `₱${subtotal.toFixed(2)}`;
            }

            const total = subtotal;

            if (elements.totalAmount) {
                elements.totalAmount.textContent = `₱${total.toFixed(2)}`;
            }

            // Trigger discount recalculation
            if (typeof updateDiscounts === 'function') {
                updateDiscounts();
            }
        }

        clearCart() {
            localStorage.removeItem('cart');
            this.items = [];
            this.updateHiddenInput();
            this.updateDisplay();
        }
    }

    // Initialize managers
    const notificationManager = new NotificationManager();
    const cartManager = new CartManager();

    // UI Event Handlers
    function showConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        modal.style.display = 'block';
        setTimeout(() => modal.classList.add('active'), 10);
    }

    function hideConfirmationModal() {
        const modal = document.getElementById('confirmationModal');
        modal.classList.remove('active');
        setTimeout(() => modal.style.display = 'none', 300);
    }

    function returnToHome() {
        cartManager.clearCart();
        window.location.href = 'index.php';
    }

    function updateDiscounts() {
        const subtotal = parseFloat(document.getElementById('subtotal').textContent.replace('₱', ''));
        const seniorChecked = document.getElementById('seniorDiscount').checked;
        const pwdChecked = document.getElementById('pwdDiscount').checked;
        
        let discount = 0;
        if (seniorChecked) discount += subtotal * 0.20;
        if (pwdChecked) discount += subtotal * 0.15;
        
        const discountElement = document.getElementById('seniorPwdDiscount');
        if (discountElement) {
            discountElement.textContent = `-₱${discount.toFixed(2)}`;
            discountElement.parentElement.style.display = discount > 0 ? 'flex' : 'none';
        }
        
        const total = subtotal - discount;
        
        const totalElement = document.getElementById('totalAmount');
        if (totalElement) {
            totalElement.textContent = `₱${total.toFixed(2)}`;
        }

        // Update savings display
        const savingsElement = document.getElementById('totalSavings');
        if (savingsElement) {
            savingsElement.textContent = `₱${discount.toFixed(2)}`;
            savingsElement.parentElement.style.display = discount > 0 ? 'flex' : 'none';
        }
    }

    // Initialize page
    document.addEventListener('DOMContentLoaded', function() {
        cartManager.updateDisplay();
        
        // Close modal on outside click
        window.onclick = function(event) {
            const modal = document.getElementById('confirmationModal');
            if (event.target === modal) {
                hideConfirmationModal();
            }
        };

        // Set up discount change handlers
        const discountCheckboxes = ['seniorDiscount', 'pwdDiscount'];
        discountCheckboxes.forEach(id => {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.addEventListener('change', function() {
                    const idInput = document.getElementById(`${id.replace('Discount', 'Id')}Input`);
                    if (idInput) {
                        idInput.style.display = this.checked ? 'block' : 'none';
                        if (!this.checked) {
                            const input = idInput.querySelector('input');
                            if (input) input.value = '';
                        }
                    }
                    updateDiscounts();
                });
            }
        });

        // Show temporary notice about delivery
        notificationManager.show('Delivery address selection is temporarily disabled. Using default address.', NotificationType.INFO);
    });
</script>
    <!-- Success Modal -->
    <div id="successModal" class="modal">
        <div class="modal-content success-modal">
            <div class="modal-header">
                <h2>Order Placed Successfully!</h2>
                <button type="button" class="close-modal">&times;</button>
            </div>
            <div class="modal-body text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <p>Your order has been confirmed and is being processed.</p>
                <p class="order-number"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="view-order-btn">View Order Status</button>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="notificationContainer" class="notification-container"></div>

    <!-- Initialize global variables -->
    <script>
        window.userId = <?php echo json_encode($userId); ?>;
    </script>
    
    <!-- Order logic scripts -->
    <!-- Core Libraries -->
    <script src="js/order-amount-handler.js"></script>
    
    <!-- Form Validation -->
    <script src="js/checkout-form-validator.js"></script>
    
    <!-- Order Handling -->
    <script src="js/ajax-handler.js"></script>
    <script src="js/order-ajax-handler.js"></script>
    <script src="js/order-confirmation-handler.js"></script>
    <script src="js/order-success-handler.js"></script>
    <script src="js/order-submission.js"></script>
<script>
    // Initialize handlers after DOM is fully loaded
    document.addEventListener('DOMContentLoaded', async () => {
        // Initialize order confirmation handler
        try {
            window.orderConfirmationHandler = OrderConfirmationHandler.init();
        } catch (error) {
            console.error('Failed to initialize OrderConfirmationHandler:', error);
            notificationManager.show('System initialization failed. Please refresh the page.', NotificationType.ERROR);
            return;
        }

        // Set up place order button handler
        const placeOrderBtn = document.getElementById('placeOrderBtn');
        if (placeOrderBtn) {
            placeOrderBtn.addEventListener('click', async () => {
                try {
                    // Validate form
                    const form = document.getElementById('checkoutForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }

                    // Get order data
                    // Calculate subtotal from cart items
                    const subtotal = cartManager.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    const total = subtotal;

                    // Validate required fields first
                    const requiredFields = {
                        customerName: document.getElementById('customerName'),
                        phone: document.getElementById('phone'),
                        address: document.getElementById('address')
                    };

                    // Check if all required fields exist and have values
                    for (const [field, element] of Object.entries(requiredFields)) {
                        if (!element || !element.value) {
                            throw new Error(`${field} is required`);
                        }
                    }

                    const orderData = {
                        items: cartManager.items.map(item => ({
                            name: item.name || '',
                            quantity: parseInt(item.quantity) || 0,
                            price: parseFloat(item.price) || 0,
                            product_id: item.product_id
                        })).filter(item => 
                            item.quantity > 0 && 
                            item.price > 0 && 
                            item.product_id
                        ),
                        customerName: requiredFields.customerName.value,
                        phone: requiredFields.phone.value,
                        address: requiredFields.address.value,
                        instructions: document.getElementById('deliveryInstructions')?.value || '',
                        customerId: document.getElementById('userId')?.value,
                        paymentMethod: document.querySelector('input[name="paymentMethod"]:checked')?.value,
                        timestamp: new Date().toISOString(),
                        amounts: {
                            subtotal: parseFloat(subtotal.toFixed(2)),
                            total: parseFloat(total.toFixed(2))
                        }
                    };

                    // Show confirmation modal with order details
                    await window.orderConfirmationHandler.showConfirmation(orderData);

                } catch (error) {
                    console.error('Error processing order:', error);
                    notificationManager.show('Error processing order. Please try again.', NotificationType.ERROR);
                }
            });
        }
        // Initialize cart and notifications
        cartManager.init();
        notificationManager.init();

        // Initialize order confirmation handler (handles modal and order submission)
        try {
            window.orderConfirmationHandler = OrderConfirmationHandler.init();
        } catch (error) {
            console.error('Failed to initialize OrderConfirmationHandler:', error);
            notificationManager.show('System initialization failed. Please refresh the page.', NotificationType.ERROR);
        }

        // Initialize amount handling
        const cartItems = JSON.parse(document.getElementById('cart-data')?.value || '[]');
        const initialAmounts = recalculateAmounts(cartItems);
        updateOrderSummary(initialAmounts);
        
        // Listen for cart updates
        document.addEventListener('cartUpdated', (e) => {
            const updatedAmounts = recalculateAmounts(e.detail.items);
            updateOrderSummary(updatedAmounts);
        });
    });
</script>
<script>
    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Update initial order summary
        const cartItems = JSON.parse(document.getElementById('cart-data')?.value || '[]');
        const initialAmounts = recalculateAmounts(cartItems);
        updateOrderSummary(initialAmounts);
        
        // Setup amount recalculation on cart changes
        document.addEventListener('cartUpdated', function(e) {
            const updatedAmounts = recalculateAmounts(e.detail.items);
            updateOrderSummary(updatedAmounts);
        });

        // Add form submission handler
        const checkoutForm = document.getElementById('checkout-form');
        if (checkoutForm) {
            checkoutForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                try {
                    // Show loading state
                    const submitButton = this.querySelector('button[type="submit"]');
                    const originalText = submitButton.textContent;
                    submitButton.disabled = true;
                    submitButton.textContent = 'Processing Order...';

                    // Validate and submit order
                    const validationResult = await validateOrderForm(this);
                    if (!validationResult.isValid) {
                        throw new Error(validationResult.errors.join('\n'));
                    }

                    const orderData = await prepareOrderData(this);
                    const response = await submitOrder(orderData);
                    await handleOrderResponse(response);

                } catch (error) {
                    handleOrderError(error);
                } finally {
                    // Reset button state
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                }
            });
        }

        // Initialize discounts if available
        if (typeof updateDiscounts === 'function') {
            updateDiscounts();
        }
    });

    // Order status updates are handled by order-confirmation-handler.js via AJAX
    // This ensures real-time updates for order status and crew dashboard

    // Promo code handling
    async function applyPromoCode() {
        const promoInput = document.getElementById('promoCode');
        const promoCode = promoInput.value.trim();
        
        if (!promoCode) {
            showNotification('error', 'Please enter a promo code');
            return;
        }

        try {
            const response = await fetch('api/validate_promo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ promoCode })
            });

            const data = await response.json();

            if (data.success) {
                showNotification('success', data.message || 'Promo code applied successfully');
                
                // Update the order summary with the new discount
                if (data.discount) {
                    const cartItems = JSON.parse(document.getElementById('cart-data').value || '[]');
                    const amounts = recalculateAmounts(cartItems, data.discount);
                    updateOrderSummary(amounts);
                }
                
                // Disable the input and button
                promoInput.disabled = true;
                document.querySelector('.apply-promo-btn').disabled = true;
            } else {
                showNotification('error', data.message || 'Invalid promo code');
            }
        } catch (error) {
            console.error('Error applying promo code:', error);
            showNotification('error', 'Failed to apply promo code. Please try again.');
        }
    }
</script>
</body>
</html>
