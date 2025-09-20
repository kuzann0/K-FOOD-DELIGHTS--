<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

// Check if any admin exists
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM admin_users");
$stmt->execute();
$result = $stmt->get_result();
$isFirstSetup = ($result->fetch_assoc()['count'] == 0);

$error = '';
$success = '';

// Check for logout message
if (isset($_GET['status']) && $_GET['status'] === 'logged_out') {
    $success = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'login') {
            // Login logic
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password.';
            } else {
                if (loginAdmin($username, $password)) {
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $error = 'Invalid username or password.';
                }
            }
        }
        else if ($_POST['action'] === 'setup' && $isFirstSetup) {
            // Initial admin setup logic
            $fullName = trim($_POST['fullName'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            // Validation
            if (empty($fullName) || empty($email) || empty($username) || empty($password) || empty($confirmPassword)) {
                $error = "All fields are required";
            }
            else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Invalid email format";
            }
            else if ($password !== $confirmPassword) {
                $error = "Passwords do not match";
            }
            else if (strlen($password) < 8) {
                $error = "Password must be at least 8 characters";
            }
            else if (!preg_match("/[A-Z]/", $password) || 
                     !preg_match("/[a-z]/", $password) || 
                     !preg_match("/[0-9]/", $password) || 
                     !preg_match("/[^A-Za-z0-9]/", $password)) {
                $error = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
            }
            else {
                // Create tables if they don't exist
                require_once 'setup_admin_tables.php';

                // Get the super admin role ID
                $stmt = $conn->prepare("SELECT role_id FROM admin_roles WHERE is_super_admin = 1 LIMIT 1");
                $stmt->execute();
                $roleId = $stmt->get_result()->fetch_assoc()['role_id'];

                // Insert the super admin user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    INSERT INTO admin_users (username, password, email, full_name, role_id, is_active)
                    VALUES (?, ?, ?, ?, ?, 1)
                ");
                $stmt->bind_param("ssssi", $username, $hashedPassword, $email, $fullName, $roleId);
                
                if ($stmt->execute()) {
                    $success = "Super Admin account created successfully. You can now login.";
                    $isFirstSetup = false;
                } else {
                    $error = "Error creating admin account: " . $conn->error;
                }
            }
        }
    }
}

$pageTitle = "Admin Login";
$currentModule = "login";
$additionalStyles = ['admin_login'];

// Include the common header
include 'includes/header_common.php';
?>
<head>
    <link rel="stylesheet" href="css/admin_style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <img src="../resources/images/logo.png" alt="K-Food Delights Logo" class="login-logo">
            
            <?php if ($isFirstSetup): ?>
                <h1>Create Admin Account</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form setup-form">
                    <input type="hidden" name="action" value="setup">
                    
                    <div class="form-group">
                        <label for="fullName">Full Name</label>
                        <input type="text" id="fullName" name="fullName" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <small class="password-requirements">
                            Password must be at least 8 characters long and contain uppercase, lowercase, 
                            number, and special character
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Create Admin Account</button>
                </form>
            <?php else: ?>
                <h1>Admin Login</h1>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" class="login-form">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
                
                <?php if (isAdminLoggedIn() && isAdmin()): ?>
                    <div class="register-link">
                        <a href="register_admin.php" class="btn btn-secondary">Register New Admin</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="js/admin_script.js"></script>
</body>
</html>
