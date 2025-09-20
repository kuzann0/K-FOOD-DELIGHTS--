// User Roles Module
const UserRolesModule = {
    // State
    state: {
        isEnabled: false,
        currentRole: null,
        roles: []
    },

    // Initialize module
    init() {
        if (!this.state.isEnabled) return;
        
        this.attachEventListeners();
        this.setupUI();
    },

    // Event Listeners
    attachEventListeners() {
        // User roles navigation
        document.querySelectorAll('.sidebar-nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                if (link.textContent.trim() === 'User Roles') {
                    e.preventDefault();
                    this.showUserRolesSection();
                }
            });
        });
    },

    // UI Setup
    setupUI() {
        const userRolesSection = document.getElementById('userRolesSection');
        if (userRolesSection) {
            userRolesSection.style.display = 'none';
        }
    },

    // Show User Roles Section
    showUserRolesSection() {
        if (!this.state.isEnabled) return;

        const dashboardContent = document.querySelectorAll('.dashboard-content > div:not(#userRolesSection)');
        const userRolesSection = document.getElementById('userRolesSection');
        
        if (!userRolesSection) return;

        // Hide other sections
        dashboardContent.forEach(section => {
            section.style.display = 'none';
        });
        
        // Show user roles section
        userRolesSection.style.display = 'block';
        
        // Load user roles data
        this.loadUsersByRole();
    },

    // Load Users by Role
    async loadUsersByRole() {
        if (!this.state.isEnabled) return;

        const roles = [2, 3, 4]; // Admin, Crew, Customer
        
        for (const roleId of roles) {
            try {
                const response = await fetch(`api/get_users_by_role.php?role_id=${roleId}`);
                const data = await response.json();
                this.displayUsers(roleId, data);
            } catch (error) {
                console.error('Error loading users:', error);
            }
        }
    },

    // Display Users
    displayUsers(roleId, users) {
        if (!this.state.isEnabled) return;

        const containerId = this.getRoleContainerId(roleId);
        const container = document.getElementById(containerId);
        
        if (!container) return;

        container.innerHTML = users.map(user => `
            <div class="user-card">
                <div class="user-info">
                    <h3>${user.username}</h3>
                    <p>${user.email}</p>
                </div>
                <div class="user-actions">
                    <button onclick="UserRolesModule.editUser(${user.id})" class="btn-edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button onclick="UserRolesModule.deleteUser(${user.id})" class="btn-delete">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    },

    // Utility Functions
    getRoleContainerId(roleId) {
        switch (roleId) {
            case 2: return 'adminCards';
            case 3: return 'crewCards';
            case 4: return 'customerCards';
            default: return '';
        }
    },

    // Enable/Disable Module
    enable() {
        this.state.isEnabled = true;
        this.init();
    },

    disable() {
        this.state.isEnabled = false;
        this.cleanup();
    },

    // Cleanup
    cleanup() {
        // Hide user roles section
        const userRolesSection = document.getElementById('userRolesSection');
        if (userRolesSection) {
            userRolesSection.style.display = 'none';
        }

        // Clear any data or state
        this.state.currentRole = null;
        this.state.roles = [];
    }
};

// Export module
export default UserRolesModule;