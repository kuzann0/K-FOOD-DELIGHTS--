// Maintenance Panel JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initial loads
    loadSystemStatus();
    loadSystemLogs();
    loadBackups();
    
    // Set up auto-refresh intervals
    setInterval(loadSystemStatus, 30000); // Every 30 seconds
    setInterval(loadSystemLogs, 60000);   // Every minute
    
    // Initialize event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // System status toggle
    document.getElementById('systemStatus').addEventListener('change', function(e) {
        updateSystemStatus(e.target.checked);
    });
    
    // Log filters
    document.getElementById('logType').addEventListener('change', loadSystemLogs);
    document.getElementById('logLevel').addEventListener('change', loadSystemLogs);
    document.getElementById('refreshLogs').addEventListener('click', loadSystemLogs);
    
    // Backup controls
    document.getElementById('createBackup').addEventListener('click', initiateBackup);
}

// System Status Functions
async function loadSystemStatus() {
    try {
        const response = await fetch('../api/maintenance_handler.php?action=status');
        const data = await response.json();
        
        if (data.error) throw new Error(data.error);
        
        updateSystemMetrics(data);
        
    } catch (error) {
        console.error('Error loading system status:', error);
        showNotification('Failed to load system status', 'error');
    }
}

function updateSystemMetrics(data) {
    // Update versions
    document.getElementById('phpVersion').textContent = data.php_version;
    document.getElementById('mysqlVersion').textContent = data.mysql_version;
    document.getElementById('activeSessions').textContent = data.active_sessions;
    
    // Update disk usage
    const diskUsedGB = formatBytes(data.disk_space.total - data.disk_space.free);
    const diskTotalGB = formatBytes(data.disk_space.total);
    const diskUsagePercent = ((data.disk_space.total - data.disk_space.free) / data.disk_space.total) * 100;
    
    document.getElementById('diskUsed').textContent = diskUsedGB;
    document.getElementById('diskTotal').textContent = diskTotalGB;
    document.getElementById('diskUsageBar').style.width = `${diskUsagePercent}%`;
    document.getElementById('diskUsageBar').className = `progress ${getDangerClass(diskUsagePercent)}`;
    
    // Update memory usage
    const memoryLimit = parseMemoryLimit(data.memory_usage.limit);
    const memoryUsed = data.memory_usage.used;
    const memoryPercent = (memoryUsed / memoryLimit) * 100;
    
    document.getElementById('memoryUsed').textContent = formatBytes(memoryUsed);
    document.getElementById('memoryLimit').textContent = formatBytes(memoryLimit);
    document.getElementById('memoryUsageBar').style.width = `${memoryPercent}%`;
    document.getElementById('memoryUsageBar').className = `progress ${getDangerClass(memoryPercent)}`;
    
    // Update backup info
    if (data.last_backup) {
        const backupDate = new Date(data.last_backup.completed_at);
        document.getElementById('lastBackupInfo').innerHTML = `
            Last backup completed on ${backupDate.toLocaleDateString()} at ${backupDate.toLocaleTimeString()}
            <br>Size: ${formatBytes(data.last_backup.file_size)}
        `;
    }
}

// System Logs Functions
async function loadSystemLogs() {
    const type = document.getElementById('logType').value;
    const level = document.getElementById('logLevel').value;
    
    try {
        const response = await fetch(`../api/maintenance_handler.php?action=logs&type=${type}&level=${level}`);
        const logs = await response.json();
        
        if (Array.isArray(logs)) {
            displayLogs(logs);
        } else {
            throw new Error('Invalid response format');
        }
        
    } catch (error) {
        console.error('Error loading logs:', error);
        showNotification('Failed to load system logs', 'error');
    }
}

function displayLogs(logs) {
    const tbody = document.querySelector('#systemLogs tbody');
    tbody.innerHTML = logs.map(log => `
        <tr class="log-entry ${log.log_level.toLowerCase()}">
            <td class="log-timestamp">${formatDateTime(log.created_at)}</td>
            <td class="log-type">${log.log_type}</td>
            <td class="log-level ${log.log_level.toLowerCase()}">${log.log_level}</td>
            <td class="log-message">${escapeHtml(log.message)}</td>
            <td class="log-user">${log.admin_username || 'System'}</td>
        </tr>
    `).join('');
}

// Backup Functions
async function loadBackups() {
    try {
        const response = await fetch('../api/maintenance_handler.php?action=backups');
        const backups = await response.json();
        
        if (Array.isArray(backups)) {
            displayBackups(backups);
        } else {
            throw new Error('Invalid response format');
        }
        
    } catch (error) {
        console.error('Error loading backups:', error);
        showNotification('Failed to load backups', 'error');
    }
}

function displayBackups(backups) {
    const tbody = document.getElementById('backupsList');
    tbody.innerHTML = backups.map(backup => `
        <tr>
            <td>${escapeHtml(backup.backup_name)}</td>
            <td>${backup.backup_type}</td>
            <td>${formatBytes(backup.file_size || 0)}</td>
            <td>${formatDateTime(backup.started_at)}</td>
            <td>
                <span class="backup-status ${backup.status.toLowerCase()}">
                    ${backup.status}
                </span>
            </td>
            <td>
                <div class="btn-group">
                    ${backup.status === 'COMPLETED' ? `
                        <button class="btn btn-sm btn-primary" onclick="restoreBackup(${backup.backup_id})">
                            <i class="fas fa-undo"></i> Restore
                        </button>
                    ` : ''}
                    <button class="btn btn-sm btn-danger" onclick="deleteBackup(${backup.backup_id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

async function initiateBackup() {
    const type = document.getElementById('backupType').value;
    const notes = prompt('Enter backup notes (optional):');
    
    try {
        showNotification('Starting backup...', 'info');
        
        const response = await fetch('../api/maintenance_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'create_backup',
                type: type,
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
            loadBackups();
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Error creating backup:', error);
        showNotification('Failed to create backup', 'error');
    }
}

async function restoreBackup(backupId) {
    if (!confirm('Are you sure you want to restore this backup? This will overwrite current data.')) {
        return;
    }
    
    try {
        showNotification('Starting restore...', 'info');
        
        const response = await fetch('../api/maintenance_handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'restore_backup',
                backup_id: backupId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(result.message, 'success');
        } else {
            throw new Error(result.message);
        }
        
    } catch (error) {
        console.error('Error restoring backup:', error);
        showNotification('Failed to restore backup', 'error');
    }
}

// Utility Functions
function formatBytes(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function parseMemoryLimit(limit) {
    const size = parseInt(limit);
    const unit = limit.replace(/[0-9]/g, '').toLowerCase();
    const multipliers = {
        'k': 1024,
        'm': 1024 * 1024,
        'g': 1024 * 1024 * 1024
    };
    return size * (multipliers[unit] || 1);
}

function getDangerClass(percent) {
    if (percent >= 90) return 'danger';
    if (percent >= 75) return 'warning';
    return '';
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString();
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
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
