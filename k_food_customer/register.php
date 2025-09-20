<?php
require_once 'config.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: profile.php');
    exit();
}

// Initialize variables for potential error messages
$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = sanitizeInput($_POST['firstName']);
    $lastName = sanitizeInput($_POST['lastName']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirmPassword'];
    
    // Validate inputs
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($password)) {
        $error = 'Please fill in all required fields';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (!isValidPassword($password)) {
        $error = 'Password must be at least 8 characters and include numbers, uppercase and lowercase letters';
    } else {
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username is already taken';
        } else {
            // Check if email exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = 'Email is already registered';
            } else {
                // Check if roles are set up
                $checkRoles = $conn->query("SELECT COUNT(*) as count FROM admin_roles WHERE role_id = 4");
                $roleExists = $checkRoles->fetch_assoc()['count'] > 0;
                
                if (!$roleExists) {
                    // Redirect to role setup if roles don't exist
                    header('Location: setup_roles.php');
                    exit();
                }
                
                // Create new user with default customer role (role_id = 4)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?, 4)");
                $stmt->bind_param("sssss", $username, $hashedPassword, $email, $firstName, $lastName);
                
                if ($stmt->execute()) {
                    // Get the new user's ID
                    $userId = $conn->insert_id;
                    // Log the user in automatically
                    $_SESSION['user_id'] = $userId;
                    // Redirect to index page
                    header('Location: index.php');
                    exit();
                } else {
                    $error = 'Error creating account. Please try again.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | K-Food Delight</title>
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
                <h1>Create Account</h1>
                <p>Join K-Food Delight today</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="POST" action="register.php" id="registerForm" novalidate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstName">
                            <i class="fas fa-user"></i>
                            First Name
                        </label>
                        <input type="text" id="firstName" name="firstName" required
                               value="<?php echo isset($_POST['firstName']) ? htmlspecialchars($_POST['firstName']) : ''; ?>">
                    </div>

                    <div class="form-group">
                        <label for="lastName">
                            <i class="fas fa-user"></i>
                            Last Name
                        </label>
                        <input type="text" id="lastName" name="lastName" required
                               value="<?php echo isset($_POST['lastName']) ? htmlspecialchars($_POST['lastName']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-at"></i>
                        Username
                    </label>
                    <input type="text" id="username" name="username" required
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email
                    </label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
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
                    <div class="password-strength-meter">
                        <div class="strength-bars">
                            <div class="bar"></div>
                            <div class="bar"></div>
                            <div class="bar"></div>
                            <div class="bar"></div>
                        </div>
                        <span class="strength-text"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirmPassword">
                        <i class="fas fa-lock"></i>
                        Confirm Password
                    </label>
                    <div class="password-input">
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                        <button type="button" class="toggle-password">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group terms">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span>I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a></span>
                    </label>
                </div>

                <button type="submit" class="auth-button">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>

                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Sign In</a></p>
                </div>
            </form>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    <script src="js/auth.js"></script>
    <script src="js/register.js"></script>
</body>
</html>
