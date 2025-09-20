<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Only super admins can access registration page
requireAdminLogin();
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit();
}

$admin = getCurrentAdmin();
$pageTitle = "Register New Admin";
$currentModule = "admin_registration";

// Include the common header
include 'includes/header_common.php';
?>

<div class="admin-container">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="main-content">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-wrapper">
            <div class="content-header">
                <h1>Register New Admin</h1>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <form id="adminRegistrationForm" method="POST" action="api/register_admin.php" class="admin-form">
                        <div class="form-group">
                            <label for="fullName">Full Name</label>
                            <input type="text" id="fullName" name="fullName" class="form-control" required>
                            <div class="error-message" id="fullNameError"></div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                            <div class="error-message" id="emailError"></div>
                        </div>

                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" class="form-control" required>
                            <div class="error-message" id="usernameError"></div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">Password</label>
                                <div class="password-input-group">
                                    <input type="password" id="password" name="password" class="form-control" required>
                                    <button type="button" class="toggle-password" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="error-message" id="passwordError"></div>
                            </div>

                            <div class="form-group col-md-6">
                                <label for="confirmPassword">Confirm Password</label>
                                <div class="password-input-group">
                                    <input type="password" id="confirmPassword" name="confirmPassword" class="form-control" required>
                                    <button type="button" class="toggle-password" tabindex="-1">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="error-message" id="confirmPasswordError"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="role">Admin Role</label>
                            <select id="role" name="role" class="form-control" required>
                                <?php
                                // Fetch available roles
                                $roles = $conn->query("SELECT role_id, role_name FROM admin_roles ORDER BY role_name");
                                while ($role = $roles->fetch_assoc()) {
                                    echo "<option value='" . $role['role_id'] . "'>" . htmlspecialchars($role['role_name']) . "</option>";
                                }
                                ?>
                            </select>
                            <div class="error-message" id="roleError"></div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Register Admin</button>
                            <button type="button" class="btn btn-secondary" onclick="window.history.back()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="js/admin_registration.js"></script>
</body>
</html>
