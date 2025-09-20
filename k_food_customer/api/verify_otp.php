<?php
header('Content-Type: application/json');
require_once '../config.php';
require_once '../includes/OTPHandler.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['phone_number']) || !isset($data['otp'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Phone number and OTP are required'
    ]);
    exit;
}

$otpHandler = new OTPHandler($conn);
$result = $otpHandler->verifyOTP($data['phone_number'], $data['otp']);

if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400);
}

echo json_encode($result);
