<?php
// Check for proper authentication
require_once 'auth.php';
if (!hasPermission('manage_roles')) {
    exit('Unauthorized access');
}
?>

<div class="user-roles-container">
    <div class="role-sections">
        <!-- Admin Account Creation (Role ID 2) -->
        <div class="role-section" id="adminSection">
            <div class="role-header">
                <h2><i class="fas fa-user-shield"></i> Admin Accounts</h2>
                <button class="btn-create" onclick="showCreateForm(2)">
                    <i class="fas fa-plus"></i> Create Admin
                </button>
            </div>
            <div class="role-cards" id="adminCards"></div>
        </div>

        <!-- Crew Account Creation (Role ID 3) -->
        <div class="role-section" id="crewSection">
            <div class="role-header">
                <h2><i class="fas fa-user-tie"></i> Crew Accounts</h2>
                <button class="btn-create" onclick="showCrewAccountForm()">
                    <i class="fas fa-plus"></i> Create Crew Account
                </button>
            </div>
            <div class="role-cards" id="crewCards"></div>
        </div>

        <!-- Crew Account Creation Modal -->
        <div class="modal" id="crewAccountModal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Create Crew Account</h2>
                    <button class="close-modal" onclick="closeCrewModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="crewAccountForm" onsubmit="return submitCrewAccount(event)" enctype="multipart/form-data">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="crewUsername">Username*</label>
                                <input type="text" id="crewUsername" name="username" required
                                    pattern="[a-zA-Z0-9_]{3,20}"
                                    title="Username must be between 3-20 characters and can only contain letters, numbers, and underscores">
                                <small class="form-text">3-20 characters, letters, numbers, and underscores only</small>
                            </div>

                            <div class="form-group">
                                <label for="crewEmail">Email*</label>
                                <input type="email" id="crewEmail" name="email" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="crewFirstName">First Name*</label>
                                <input type="text" id="crewFirstName" name="first_name" required>
                            </div>

                            <div class="form-group">
                                <label for="crewLastName">Last Name*</label>
                                <input type="text" id="crewLastName" name="last_name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="crewPhone">Phone Number*</label>
                                <input type="tel" id="crewPhone" name="phone" required
                                    pattern="[0-9]{11}"
                                    title="Please enter a valid 11-digit phone number">
                                <small class="form-text">11-digit phone number (e.g., 09123456789)</small>
                            </div>

                            <div class="form-group">
                                <label for="crewAddress">Address*</label>
                                <textarea id="crewAddress" name="address" required></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="crewPassword">Password*</label>
                                <input type="password" id="crewPassword" name="password" required
                                    minlength="8"
                                    pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}"
                                    title="Must contain at least one number, one uppercase and lowercase letter, and be at least 8 characters long">
                                <div class="password-strength-meter"></div>
                                <small class="form-text">Minimum 8 characters, must include uppercase, lowercase, and numbers</small>
                            </div>

                            <div class="form-group">
                                <label for="crewConfirmPassword">Confirm Password*</label>
                                <input type="password" id="crewConfirmPassword" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="crewProfilePicture">Profile Picture</label>
                                <input type="file" id="crewProfilePicture" name="profile_picture" accept="image/*">
                                <small class="form-text">Maximum file size: 2MB. Supported formats: JPG, PNG</small>
                            </div>

                            <div class="form-group">
                                <label for="crewIsActive">Account Status</label>
                                <select id="crewIsActive" name="is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <input type="hidden" name="role_id" value="3">
                        <div class="form-message"></div>

                        <div class="form-actions">
                            <button type="button" class="btn-secondary" onclick="closeCrewModal()">Cancel</button>
                            <button type="submit" class="btn-primary">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Customer Account Creation (Role ID 4) -->
        <div class="role-section" id="customerSection">
            <div class="role-header">
                <h2><i class="fas fa-users"></i> Customer Accounts</h2>
                <button class="btn-create" onclick="showCreateForm(4)">
                    <i class="fas fa-plus"></i> Create Customer
                </button>
            </div>
            <div class="role-cards" id="customerCards"></div>
        </div>
    </div>

    <!-- Create Account Modal -->
    <div class="modal" id="createAccountModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Create New Account</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="createAccountForm">
                    <input type="hidden" id="roleId" name="roleId">
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="password-strength"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <input type="password" id="confirmPassword" name="confirmPassword" required>
                    </div>

                    <!-- Additional fields based on role -->
                    <div id="additionalFields"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn-primary" onclick="createAccount()">Create Account</button>
            </div>
        </div>
    </div>
</div>
