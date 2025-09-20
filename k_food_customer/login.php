<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Check if required columns exist
$checkColumns = $conn->query("
    SELECT COUNT(*) as column_count 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME IN ('account_status', 'role_id', 'login_attempts')
");

$columnCount = $checkColumns->fetch_assoc()['column_count'];

// If we don't have all required columns, redirect to update script
if ($columnCount < 3) {
    header('Location: update_user_security.php');
    exit();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: profile.php');
    exit();
}

// Initialize variables for potential error messages
$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT user_id, password, role_id, account_status FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Check if account is active
            if ($user['account_status'] !== 'active') {
                $error = 'Account is not active. Please contact support.';
            } else if (password_verify($password, $user['password'])) {
                // Update last login timestamp and login attempt reset
                $updateStmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP, login_attempts = 0 WHERE user_id = ?");
                $updateStmt->bind_param("i", $user['user_id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['last_activity'] = time();
                
                // Regenerate session ID for security
                session_regenerate_id(true);
                
                // Store role in session for role-based access control
                $_SESSION['role_id'] = $user['role_id'];
                
                // Redirect based on role
                switch ($user['role_id']) {
                    case 2: // Admin account
                        $_SESSION['is_admin'] = true;
                        header('Location: ../kfood_admin/index.php');
                        break;
                    case 3: // Crew account
                        $_SESSION['is_crew'] = true;
                        header('Location: ../kfood_crew/index.php');
                        break;
                    case 1: // Customer account
                    default:
                        $_SESSION['is_customer'] = true;
                        // Check if there's a redirect URL stored in session
                        if (isset($_SESSION['redirect_after_login'])) {
                            $redirect = $_SESSION['redirect_after_login'];
                            unset($_SESSION['redirect_after_login']);
                            header('Location: ' . $redirect);
                        } else {
                            header('Location: index.php');
                        }
                }
                exit();
            } else {
                $error = 'Invalid credentials';
            }
        } else {
            $error = 'Invalid credentials';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | K-Food Delight</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link rel="shortcut icon" href="../logo-tab-icon.ico" type="image/x-icon" />
</head>
<body>
    <?php include 'includes/nav.php'; ?>

    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <img src="../resources/images/logo.png" alt="K-Food Delight Logo" class="auth-logo">
                <h1>Welcome Back</h1>
                <p>Sign in to your account</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="login.php" id="loginForm">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="auth-button">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>

                <div class="auth-footer">
                    <p>Don't have an account? <a href="register.php">Sign Up</a></p>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="js/auth.js"></script>
</body>
</html>
