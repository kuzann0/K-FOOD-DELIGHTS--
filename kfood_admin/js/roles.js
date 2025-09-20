// Role Management JavaScript
document.addEventListener('DOMContentLoaded', function() {
    loadAllAdmins();
    setupEventListeners();
});

function setupEventListeners() {
    // Role selection change handler
    document.getElementById('roleId')?.addEventListener('change', function() {
        const crewAdminSelect = document.querySelector('.crew-admin-select');
        if (this.value === '3') { // Crew Member
            crewAdminSelect.style.display = 'block';
            loadCrewAdmins();
        } else {
            crewAdminSelect.style.display = 'none';
        }
    });

    // Password strength checker
    document.getElementById('password')?.addEventListener('input', checkPasswordStrength);
    document.getElementById('newPassword')?.addEventListener('input', checkPasswordStrength);
}

function loadAllAdmins() {
    fetch('../api/get_admins.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayAdmins(data.admins);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load administrators', 'error');
        });
}

function displayAdmins(admins) {
    const superAdminList = document.getElementById('superAdminList');
    const crewAdminList = document.getElementById('crewAdminList');
    const crewMemberList = document.getElementById('crewMemberList');

    superAdminList.innerHTML = '';
    crewAdminList.innerHTML = '';
    crewMemberList.innerHTML = '';

    admins.forEach(admin => {
        const card = createAdminCard(admin);
        
        switch(parseInt(admin.role_id)) {
            case 1:
                superAdminList.appendChild(card);
                break;
            case 2:
                crewAdminList.appendChild(card);
                break;
            case 3:
                crewMemberList.appendChild(card);
                break;
        }
    });
}

function createAdminCard(admin) {
    const div = document.createElement('div');
    div.className = 'admin-card';
    
    const roleBadgeClass = admin.role_id === 1 ? 'super-admin' : 
                          admin.role_id === 2 ? 'crew-admin' : 'crew-member';
    
    div.innerHTML = `
        <span class="role-badge ${roleBadgeClass}">
            ${admin.role_id === 1 ? 'Super Admin' : 
              admin.role_id === 2 ? 'Crew Admin' : 'Crew Member'}
        </span>
        <div class="admin-info">
            <div class="admin-name">${admin.username}</div>
            <div class="admin-email">${admin.email}</div>
            <div class="admin-status">
                <span class="status-indicator ${admin.is_active ? 'status-active' : 'status-inactive'}"></span>
                ${admin.is_active ? 'Active' : 'Inactive'}
            </div>
        </div>
        ${admin.role_id !== 1 ? `
            <div class="admin-actions">
                <button class="btn btn-secondary" onclick="editAdmin(${admin.id})">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-danger" onclick="deleteAdmin(${admin.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        ` : ''}
    `;
    
    return div;
}

function loadCrewAdmins() {
    fetch('../api/get_crew_admins.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const select = document.getElementById('crewAdminId');
                select.innerHTML = data.admins.map(admin => 
                    `<option value="${admin.id}">${admin.username}</option>`
                ).join('');
            }
        })
        .catch(error => console.error('Error:', error));
}

function showCreateAdminModal() {
    const modal = document.getElementById('createAdminModal');
    modal.classList.add('show');
    document.getElementById('createAdminForm').reset();
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

function checkPasswordStrength(event) {
    const password = event.target.value;
    const strengthBar = event.target.nextElementSibling;
    
    let strength = 0;
    if (password.match(/[a-z]+/)) strength += 20;
    if (password.match(/[A-Z]+/)) strength += 20;
    if (password.match(/[0-9]+/)) strength += 20;
    if (password.match(/[!@#$%^&*(),.?":{}|<>]+/)) strength += 20;
    if (password.length >= 8) strength += 20;

    strengthBar.style.setProperty('--strength', strength + '%');
}

function createAdmin() {
    const form = document.getElementById('createAdminForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    if (formData.get('password') !== formData.get('confirmPassword')) {
        showNotification('Passwords do not match', 'error');
        return;
    }

    fetch('../api/create_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('createAdminModal');
            loadAllAdmins();
            showNotification('Administrator account created successfully', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to create administrator account', 'error');
    });
}

function editAdmin(adminId) {
    fetch(`../api/get_admin.php?id=${adminId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const admin = data.admin;
                document.getElementById('editAdminId').value = admin.id;
                document.getElementById('editUsername').value = admin.username;
                document.getElementById('editEmail').value = admin.email;
                document.getElementById('editRoleId').value = admin.role_id;
                document.getElementById('editAdminModal').classList.add('show');
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('Failed to load administrator details', 'error');
        });
}

function updateAdmin() {
    const form = document.getElementById('editAdminForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    
    fetch('../api/update_admin.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeModal('editAdminModal');
            loadAllAdmins();
            showNotification('Administrator account updated successfully', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to update administrator account', 'error');
    });
}

function deleteAdmin(adminId) {
    if (!confirm('Are you sure you want to delete this administrator account?')) {
        return;
    }

    fetch('../api/delete_admin.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ id: adminId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadAllAdmins();
            showNotification('Administrator account deleted successfully', 'success');
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Failed to delete administrator account', 'error');
    });
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.classList.add('show');
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }, 100);
}
