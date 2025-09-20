<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Check if WebSocket server is running on port 8080
$connection = @fsockopen('127.0.0.1', 8080, $errno, $errstr, 1);

if ($connection) {
    fclose($connection);
    echo json_encode([
        'status' => 'running',
        'message' => 'WebSocket server is running'
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'WebSocket server is not running',
        'error' => $errstr
    ]);
}