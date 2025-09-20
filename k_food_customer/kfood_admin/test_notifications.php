<?php
require_once '../config/database.php';
require_once '../config/admin_config.php';
require_once '../includes/ErrorHandler.php';

// Test different types of notifications
function testNotifications() {
    global $conn;
    $errorHandler = new ErrorHandler($conn);
    
    try {
        // Test a regular error
        throw new OrderProcessingError(
            "Test order processing error",
            "ORDER_PROCESSING_ERROR",
            ['orderId' => 'TEST123'],
            "An error occurred while processing your order"
        );
    } catch (Throwable $e) {
        $errorHandler->handleError($e, [
            'context' => 'Testing order processing notification'
        ]);
    }
    
    try {
        // Test a critical system error
        throw new Exception("Critical system test error");
    } catch (Throwable $e) {
        $errorHandler->handleError($e, [
            'type' => 'SYSTEM_ERROR',
            'context' => 'Testing critical error notification'
        ]);
    }
    
    try {
        // Test a security violation
        throw new Exception("Security violation test");
    } catch (Throwable $e) {
        $errorHandler->handleError($e, [
            'type' => 'SECURITY_VIOLATION',
            'context' => 'Testing security notification'
        ]);
    }
    
    echo "Test notifications have been generated.\n";
}

// Only run if explicitly requested
if (isset($_GET['run_test']) && $_GET['run_test'] === 'true') {
    // Verify admin authentication
    session_start();
    if (!isset($_SESSION['admin_id'])) {
        die("Unauthorized access");
    }
    
    testNotifications();
} else {
    echo "Add ?run_test=true to the URL to run notification tests.";
}