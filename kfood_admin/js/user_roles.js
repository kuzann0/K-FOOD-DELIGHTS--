// User Roles Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Role Management UI
    initRoleManagement();

    // Initialize Form Validation
    initFormValidation();

    // Initialize Profile Picture Upload
    initProfilePictureUpload();

    // Load existing users
    loadUsersByRole();

    // Setup role tabs
    setupRoleTabs();
});

// Role management functions
function updateRoleUI(role) {
    const descriptions = {
        admin: {
            title: 'Administrator Account',
            description: 'Create an administrator account with full access to system features including user management, inventory control, order processing, and system configuration.',
            permissions: [
                'Full system access',
                'User management',
                'Order processing',
                'Inventory control',
                'System configuration'
            ],
            icon: 'user-shield'
        },
        crew: {
            title: 'Crew Member Account',
            description: 'Create a crew member account for managing orders, customer service, and inventory tracking.',
            permissions: [
                'Order processing',
                'Customer service',
                'Inventory management',
                'Basic reports access'
            ],
            icon: 'user-tie'
        },
        customer: {
            title: 'Customer Account',
            description: 'Create a customer account for placing orders and tracking deliveries.',
            permissions: [
                'Place orders',
                'Track deliveries',
                'View menu',
                'Manage profile'
            ],
            icon: 'user'
        }
    };

    const info = descriptions[role];
    const descriptionEl = document.getElementById('roleDescription');
    
    if (descriptionEl && info) {
        descriptionEl.innerHTML = `
            <div class="role-icon">
                <i class="fas fa-${info.icon}"></i>
            </div>
            <div class="role-info">
                <h3>${info.title}</h3>
                <p>${info.description}</p>
                <ul class="role-permissions">
                    ${info.permissions.map(perm => `
                        <li><i class="fas fa-check"></i> ${perm}</li>
                    `).join('')}
                </ul>
            </div>
        `;
    }

    // Update form fields visibility
    const form = document.getElementById('userCreateForm');
    if (form) {
        // Reset form
        form.reset();
        
        // Show/hide fields based on role
        const addressField = form.querySelector('[data-field="address"]');
        const phoneField = form.querySelector('[data-field="phone"]');
        const adminFields = form.querySelector('[data-role="admin"]');
        const crewFields = form.querySelector('[data-role="crew"]');
        const customerFields = form.querySelector('[data-role="customer"]');

        if (addressField) addressField.style.display = role === 'customer' ? 'block' : 'none';
        if (phoneField) phoneField.style.display = role === 'admin' ? 'none' : 'block';
        
        // Show/hide role-specific fields
        if (adminFields) adminFields.style.display = role === 'admin' ? 'block' : 'none';
        if (crewFields) crewFields.style.display = role === 'crew' ? 'block' : 'none';
        if (customerFields) customerFields.style.display = role === 'customer' ? 'block' : 'none';
        
        // Update required attributes
        const addressInput = form.querySelector('#address');
        const phoneInput = form.querySelector('#phone');
        
        if (addressInput) {
            addressInput.required = role === 'customer';
        }
        if (phoneInput) {
            phoneInput.required = role !== 'admin';
        }
    }
}

function initRoleManagement() {
    const roleTabs = document.querySelectorAll('.role-tab');
    
    roleTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active state
            roleTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');

            // Get role info
            const role = this.getAttribute('data-role');
            const roleId = this.getAttribute('data-role-id');

            // Update hidden role ID input
            const roleInput = document.getElementById('role_id');
            if (roleInput) {
                roleInput.value = roleId;
            }

            // Update UI
            updateRoleUI(role);
        });
    });

    // Initialize with admin role
    updateRoleUI('admin');
}

function setupEventListeners() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            closeModal();
        }
    };

    // Password strength validation
    document.getElementById('password').addEventListener('input', checkPasswordStrength);

    // Form submission
    const userForm = document.getElementById('createAccountForm');
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            createAccount();
            return false;
        });
    }
}

function loadUsersByRole() {
    const roles = [2, 3, 4]; // Admin, Crew, Customer
    
    roles.forEach(roleId => {
        fetch(`api/get_users_by_role.php?role_id=${roleId}`)
            .then(response => response.json())
            .then(data => {
                displayUsers(roleId, data);
            })
            .catch(error => console.error('Error loading users:', error));
    });
}

function displayUsers(roleId, users) {
    const containerId = getRoleContainerId(roleId);
    const container = document.getElementById(containerId);
    
    if (!container) return;

    container.innerHTML = users.map(user => `
        <div class="user-card">
            <div class="user-info">
                <h3>${user.username}</h3>
                <p>${user.email}</p>
            </div>
            <div class="user-actions">
                <button onclick="editUser(${user.id})" class="btn-edit">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteUser(${user.id})" class="btn-delete">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

function getRoleContainerId(roleId) {
    switch (roleId) {
        case 2: return 'adminCards';
        case 3: return 'crewCards';
        case 4: return 'customerCards';
        default: return '';
    }
}

// Crew Account Management
function showCrewAccountForm() {
    const modal = document.getElementById('crewAccountModal');
    modal.style.display = 'block';
    
    // Reset form
    document.getElementById('crewAccountForm').reset();
    document.querySelector('.form-message').style.display = 'none';
    document.querySelector('.password-strength-meter').className = 'password-strength-meter';
}

function closeCrewModal() {
    const modal = document.getElementById('crewAccountModal');
    modal.style.display = 'none';
}

function checkPasswordStrength(password) {
    let strength = 0;
    
    // Length check
    if (password.length >= 8) strength++;
    
    // Uppercase check
    if (/[A-Z]/.test(password)) strength++;
    
    // Lowercase check
    if (/[a-z]/.test(password)) strength++;
    
    // Number check
    if (/[0-9]/.test(password)) strength++;
    
    // Special character check
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    return strength;
}

document.getElementById('crewPassword').addEventListener('input', function(e) {
    const strength = checkPasswordStrength(this.value);
    const meter = document.querySelector('.password-strength-meter');
    
    meter.className = 'password-strength-meter';
    if (strength >= 4) meter.classList.add('strong');
    else if (strength >= 3) meter.classList.add('medium');
    else if (strength >= 1) meter.classList.add('weak');
});

function showFormMessage(message, type = 'error') {
    const messageDiv = document.querySelector('.form-message');
    messageDiv.textContent = message;
    messageDiv.className = `form-message ${type}`;
    messageDiv.style.display = 'block';
}

async function submitCrewAccount(event) {
    event.preventDefault();
    
    // Get form values
    const form = document.getElementById('crewAccountForm');
    const formData = new FormData(form);
    
    // Get password confirmation for validation
    const password = document.getElementById('crewPassword').value;
    const confirmPassword = document.getElementById('crewConfirmPassword').value;
    
    // Validate passwords match
    if (password !== confirmPassword) {
        showFormMessage('Passwords do not match');
        return false;
    }
    
    // Validate password strength
    if (checkPasswordStrength(password) < 3) {
        showFormMessage('Password is not strong enough');
        return false;
    }
    
    try {
        const response = await fetch('api/create_crew_account.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showFormMessage(data.message, 'success');
            setTimeout(() => {
                closeCrewModal();
                loadUsersByRole(); // Refresh the user list
            }, 1500);
        } else {
            showFormMessage(data.message);
        }
    } catch (error) {
        console.error('Error:', error);
        showFormMessage('An error occurred while creating the account');
    }
    
    return false;
}

function showCreateForm(roleId) {
    const modal = document.getElementById('createAccountModal');
    const roleInput = document.getElementById('roleId');
    const modalTitle = document.getElementById('modalTitle');
    const additionalFields = document.getElementById('additionalFields');
    
    roleInput.value = roleId;
    modalTitle.textContent = `Create ${getRoleName(roleId)} Account`;
    
    // Add role-specific fields
    additionalFields.innerHTML = getAdditionalFields(roleId);
    
    modal.style.display = 'block';
}

function getRoleName(roleId) {
    switch (roleId) {
        case 2: return 'Admin';
        case 3: return 'Crew';
        case 4: return 'Customer';
        default: return 'User';
    }
}

function getAdditionalFields(roleId) {
    switch (roleId) {
        case 2:
            return `
                <div class="form-group">
                    <label for="adminPermissions">Admin Permissions</label>
                    <select id="adminPermissions" name="permissions[]" multiple required>
                        <option value="manage_users">Manage Users</option>
                        <option value="manage_inventory">Manage Inventory</option>
                        <option value="manage_orders">Manage Orders</option>
                    </select>
                </div>
            `;
        case 3:
            return `
                <div class="form-group">
                    <label for="shift">Shift Schedule</label>
                    <select id="shift" name="shift" required>
                        <option value="morning">Morning</option>
                        <option value="afternoon">Afternoon</option>
                        <option value="evening">Evening</option>
                    </select>
                </div>
            `;
        case 4:
            return `
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="address">Delivery Address</label>
                    <textarea id="address" name="address" required></textarea>
                </div>
            `;
        default:
            return '';
    }
}

function closeModal() {
    const modal = document.getElementById('createAccountModal');
    modal.style.display = 'none';
    document.getElementById('createAccountForm').reset();
}

async function createAccount() {
    const form = document.getElementById('createAccountForm');
    const formData = new FormData(form);
    const loadingIndicator = document.querySelector('.loading-indicator');

    // Validate form
    if (!validateForm(formData)) return;

    try {
        // Show loading state
        if (loadingIndicator) loadingIndicator.style.display = 'flex';
        
        // Clear previous errors
        form.querySelectorAll('.error-message').forEach(msg => msg.remove());
        form.querySelectorAll('.error').forEach(field => field.classList.remove('error'));

        // Send to server
        const response = await fetch('api/create_user.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showNotification('Account created successfully', 'success');
            closeModal();
            loadUsersByRole();
            form.reset();
            
            // Reset profile picture if exists
            const preview = document.getElementById('profile_preview');
            if (preview) {
                preview.src = 'resources/images/default-profile.png';
            }
        } else {
            const error = data.message || 'Failed to create account';
            showNotification(error, 'error');
            
            // Show field-specific errors if provided
            if (data.errors) {
                Object.entries(data.errors).forEach(([field, message]) => {
                    const fieldElement = form.querySelector(`[name="${field}"]`);
                    if (fieldElement) {
                        showFieldError(fieldElement, message);
                    }
                });
            }
        }
    } catch (error) {
        console.error('Error creating account:', error);
        showNotification('An error occurred while creating the account', 'error');
    } finally {
        // Hide loading state
        if (loadingIndicator) loadingIndicator.style.display = 'none';
    }
}

function validateForm(formData) {
    let isValid = true;
    const form = document.getElementById('createAccountForm');

    // Clear previous errors
    form.querySelectorAll('.error-message').forEach(msg => msg.remove());

    // Required field validation
    form.querySelectorAll('[required]').forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'This field is required');
            isValid = false;
        }
    });

    // Password validation
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');
    const passwordField = form.querySelector('#password');
    
    if (password !== confirmPassword) {
        showFieldError(form.querySelector('#confirmPassword'), 'Passwords do not match');
        isValid = false;
    }
    
    if (password.length < 8) {
        showFieldError(passwordField, 'Password must be at least 8 characters long');
        isValid = false;
    }

    // Email validation
    const email = formData.get('email');
    const emailField = form.querySelector('#email');
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        showFieldError(emailField, 'Please enter a valid email address');
        isValid = false;
    }

    // Phone validation (if present)
    const phone = formData.get('phone');
    const phoneField = form.querySelector('#phone');
    if (phone && !/^\+?\d{10,}$/.test(phone.replace(/[\s-]/g, ''))) {
        showFieldError(phoneField, 'Please enter a valid phone number');
        isValid = false;
    }

    return isValid;
}

function showFieldError(field, message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
    field.classList.add('error');
}

function checkPasswordStrength(event) {
    const password = event.target.value;
    const strengthBar = document.querySelector('.password-strength');
    
    // Calculate strength
    let strength = 0;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[^a-zA-Z0-9]/)) strength++;
    
    // Update strength bar
    const colors = ['#ff4b2b', '#ffa500', '#00c853'];
    strengthBar.style.width = `${(strength / 4) * 100}%`;
    strengthBar.style.backgroundColor = colors[strength - 1] || '#eee';
}

function showNotification(message, type) {
    // Implementation of notification system
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

function initProfilePictureUpload() {
    const fileInput = document.getElementById('profile_picture');
    const preview = document.getElementById('profile_preview');
    const uploadTrigger = document.querySelector('.upload-trigger');

    if (fileInput && preview) {
        if (uploadTrigger) {
            uploadTrigger.addEventListener('click', () => fileInput.click());
        }

        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Validate file type
                const file = this.files[0];
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                
                if (!allowedTypes.includes(file.type)) {
                    showNotification('Please select a valid image file (JPEG, PNG, or GIF)', 'error');
                    this.value = '';
                    return;
                }

                // Validate file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    showNotification('Image file size must be less than 2MB', 'error');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Edit and Delete functions
function editUser(userId) {
    // Fetch user data
    fetch(`api/get_user.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                populateEditForm(data.user);
                showCreateForm(data.user.role_id);
            } else {
                showNotification(data.message || 'Failed to load user data', 'error');
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
            showNotification('Failed to load user data', 'error');
        });
}

function deleteUser(userId) {
    if (confirm('Are you sure you want to delete this user?')) {
        fetch(`api/delete_user.php?id=${userId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('User deleted successfully', 'success');
                loadUsersByRole();
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error deleting user:', error);
            showNotification('Failed to delete user', 'error');
        });
    }
}
