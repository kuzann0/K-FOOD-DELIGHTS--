<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verify admin session and permissions
session_start();
if (!isAdmin() || !hasPermission('manage_roles')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Set JSON response header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get and validate input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    $input = $_POST;
}

// Validate required fields
$required_fields = ['username', 'email', 'password'];
foreach ($required_fields as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit();
    }
}

// Sanitize and validate inputs
$username = filter_var($input['username'], FILTER_SANITIZE_STRING);
$email = filter_var($input['email'], FILTER_VALIDATE_EMAIL);
$password = $input['password'];

// Additional validation
if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters long']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT id FROM user_accounts WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit();
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Insert new crew account
    $stmt = $pdo->prepare("
        INSERT INTO user_accounts (
            username, 
            email, 
            password, 
            role_id, 
            tool_access, 
            created_at
        ) VALUES (
            :username,
            :email,
            :password,
            3,
            'Inventory,Orders',
            NOW()
        )
    ");

    $stmt->execute([
        ':username' => $username,
        ':email' => $email,
        ':password' => password_hash($password, PASSWORD_DEFAULT)
    ]);

    $newUserId = $pdo->lastInsertId();

    // Log the account creation
    $stmt = $pdo->prepare("
        INSERT INTO admin_activity_log (
            admin_id,
            action_type,
            action_details,
            created_at
        ) VALUES (
            :admin_id,
            'create_crew_account',
            :details,
            NOW()
        )
    ");

    $stmt->execute([
        ':admin_id' => $_SESSION['admin_id'],
        ':details' => "Created crew account for user: $username"
    ]);

    // Commit transaction
    $pdo->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Crew account created successfully',
        'userId' => $newUserId
    ]);

} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Error creating crew account: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while creating the account'
    ]);
}
?>
