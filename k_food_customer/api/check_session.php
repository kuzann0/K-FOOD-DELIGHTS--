<?php
session_start();
header('Content-Type: application/json');

$response = array(
    'isLoggedIn' => isset($_SESSION['user_id']),
    'userId' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null
);

echo json_encode($response);
?>
