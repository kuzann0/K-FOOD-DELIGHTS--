<?php
header('Content-Type: application/json');
require_once(__DIR__ . '/../includes/DatabaseConnection.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Validate required fields
$requiredFields = ['role_id', 'username', 'password', 'email', 'first_name', 'last_name'];
$errors = [];

foreach ($requiredFields as $field) {
    if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
        $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
    }
}

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

try {
    // Get form data
    $roleId = intval($_POST['role_id']);
    $username = trim($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : null;
    $address = isset($_POST['address']) ? trim($_POST['address']) : null;
    $isActive = isset($_POST['status']) ? intval($_POST['status']) : 1;
    
    // Handle profile picture upload
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile_pictures/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $fileExtension;
        $uploadFile = $uploadDir . $fileName;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadFile)) {
            $profilePicture = 'uploads/profile_pictures/' . $fileName;
        }
    }

    $db = DatabaseConnection::getInstance()->getConnection();
    $db->beginTransaction();

    // Check if username exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    if ($stmt->fetch()) {
        throw new Exception('Username already exists');
    }

    // Check if email exists
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        throw new Exception('Email already exists');
    }

    // Validate role_id
    $stmt = $db->prepare("SELECT role_id FROM admin_roles WHERE role_id = :roleId");
    $stmt->execute(['roleId' => $roleId]);
    if (!$stmt->fetch()) {
        throw new Exception('Invalid role selected');
    }

    // Insert new user with proper field names
    $query = "INSERT INTO users (
        username, 
        password, 
        email, 
        first_name, 
        last_name, 
        phone, 
        address, 
        profile_picture, 
        is_active, 
        role_id
    ) VALUES (
        :username,
        :password,
        :email,
        :firstName,
        :lastName,
        :phone,
        :address,
        :profilePicture,
        :isActive,
        :roleId
    )";
    
    $stmt = $db->prepare($query);
    
    $params = [
        ':username' => $username,
        ':password' => $password,
        ':email' => $email,
        ':firstName' => $firstName,
        ':lastName' => $lastName,
        ':phone' => $phone,
        ':address' => $address,
        ':profilePicture' => $profilePicture,
        ':isActive' => $isActive,
        ':roleId' => $roleId
    ];

    if ($stmt->execute($params)) {
        $userId = $db->lastInsertId();
        
        // Fetch the created user for confirmation
        $stmt = $db->prepare("SELECT user_id, username, email, role_id, is_active FROM users WHERE user_id = :userId");
        $stmt->execute(['userId' => $userId]);
        $createdUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($createdUser) {
            // Commit transaction
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'User account created successfully',
                'user' => [
                    'user_id' => $createdUser['user_id'],
                    'username' => $createdUser['username'],
                    'email' => $createdUser['email'],
                    'role_id' => $createdUser['role_id'],
                    'is_active' => $createdUser['is_active']
                ]
            ]);
            exit;
        }
        
        throw new Exception('User created but unable to fetch details');
    }
    
    throw new Exception('Failed to create user');

} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    $errorMessage = 'Database error occurred';
    if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
        if (strpos($e->getMessage(), 'username') !== false) {
            $errorMessage = 'Username already exists';
        } elseif (strpos($e->getMessage(), 'email') !== false) {
            $errorMessage = 'Email already exists';
        }
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $errorMessage,
        'errors' => ['database' => $e->getMessage()]
    ]);
    exit;

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit;
}