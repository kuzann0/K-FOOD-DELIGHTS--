<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alert Rules Management - K-Food Delights Admin</title>
    <link rel="stylesheet" href="../css/admin-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include('../includes/admin-header.php'); ?>
    <?php include('../includes/admin-sidebar.php'); ?>

    <main class="content">
        <div class="page-header">
            <h1>Alert Rules Management</h1>
            <button id="createRuleBtn" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create New Rule
            </button>
        </div>

        <div class="filters">
            <select id="typeFilter">
                <option value="">All Types</option>
                <option value="system">System</option>
                <option value="database">Database</option>
                <option value="order">Order</option>
                <option value="security">Security</option>
            </select>
            <select id="severityFilter">
                <option value="">All Severities</option>
                <option value="low">Low</option>
                <option value="medium">Medium</option>
                <option value="high">High</option>
                <option value="critical">Critical</option>
            </select>
            <select id="statusFilter">
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>

        <div class="rules-container">
            <table id="rulesTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Status</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="rulesTableBody">
                    <!-- Rules will be loaded here -->
                </tbody>
            </table>
        </div>
    </main>

    <!-- Create/Edit Rule Modal -->
    <div id="ruleModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2 id="modalTitle">Create Alert Rule</h2>
            
            <form id="ruleForm">
                <input type="hidden" id="ruleId">
                
                <div class="form-group">
                    <label for="ruleName">Rule Name</label>
                    <input type="text" id="ruleName" required>
                </div>
                
                <div class="form-group">
                    <label for="ruleDescription">Description</label>
                    <textarea id="ruleDescription" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label for="ruleType">Type</label>
                    <select id="ruleType" required>
                        <option value="system">System</option>
                        <option value="database">Database</option>
                        <option value="order">Order</option>
                        <option value="security">Security</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="ruleSeverity">Severity</label>
                    <select id="ruleSeverity" required>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Conditions</label>
                    <div id="conditionsContainer">
                        <!-- Conditions will be added here -->
                    </div>
                    <button type="button" id="addConditionBtn" class="btn btn-secondary">
                        Add Condition
                    </button>
                </div>
                
                <div class="form-group">
                    <label>Actions</label>
                    <div id="actionsContainer">
                        <!-- Actions will be added here -->
                    </div>
                    <button type="button" id="addActionBtn" class="btn btn-secondary">
                        Add Action
                    </button>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Rule</button>
                    <button type="button" class="btn btn-secondary" id="cancelBtn">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/alert-rules.js"></script>
</body>
</html>