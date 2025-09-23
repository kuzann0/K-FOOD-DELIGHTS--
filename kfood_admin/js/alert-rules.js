document.addEventListener("DOMContentLoaded", function () {
  // Load initial rules
  loadRules();

  // Event listeners
  document
    .getElementById("createRuleBtn")
    .addEventListener("click", showCreateRuleModal);
  document.getElementById("cancelBtn").addEventListener("click", closeModal);
  document
    .getElementById("ruleForm")
    .addEventListener("submit", handleRuleSubmit);
  document
    .getElementById("addConditionBtn")
    .addEventListener("click", addConditionField);
  document
    .getElementById("addActionBtn")
    .addEventListener("click", addActionField);

  // Filter event listeners
  document.getElementById("typeFilter").addEventListener("change", loadRules);
  document
    .getElementById("severityFilter")
    .addEventListener("change", loadRules);
  document.getElementById("statusFilter").addEventListener("change", loadRules);
});

// Load rules from the server
async function loadRules() {
  const typeFilter = document.getElementById("typeFilter").value;
  const severityFilter = document.getElementById("severityFilter").value;
  const statusFilter = document.getElementById("statusFilter").value;

  try {
    const response = await fetch("api/get_alert_rules.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        filters: {
          type: typeFilter,
          severity: severityFilter,
          status: statusFilter,
        },
      }),
    });

    const rules = await response.json();
    displayRules(rules);
  } catch (error) {
    console.error("Error loading rules:", error);
    showError("Failed to load alert rules");
  }
}

// Display rules in the table
function displayRules(rules) {
  const tbody = document.getElementById("rulesTableBody");
  tbody.innerHTML = "";

  rules.forEach((rule) => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
            <td>${escapeHtml(rule.name)}</td>
            <td>${escapeHtml(rule.type)}</td>
            <td><span class="severity-badge ${rule.severity}">${
      rule.severity
    }</span></td>
            <td><span class="status-badge ${rule.status}">${
      rule.status
    }</span></td>
            <td>${formatDate(rule.updated_at)}</td>
            <td>
                <button onclick="editRule(${
                  rule.id
                })" class="btn btn-sm btn-primary">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deleteRule(${
                  rule.id
                })" class="btn btn-sm btn-danger">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
    tbody.appendChild(tr);
  });
}

// Show create rule modal
function showCreateRuleModal() {
  document.getElementById("modalTitle").textContent = "Create Alert Rule";
  document.getElementById("ruleId").value = "";
  document.getElementById("ruleForm").reset();
  clearConditionsAndActions();
  showModal();
}

// Show edit rule modal
async function editRule(ruleId) {
  try {
    const response = await fetch(`api/get_alert_rule.php?id=${ruleId}`);
    const rule = await response.json();

    document.getElementById("modalTitle").textContent = "Edit Alert Rule";
    document.getElementById("ruleId").value = rule.id;
    document.getElementById("ruleName").value = rule.name;
    document.getElementById("ruleDescription").value = rule.description;
    document.getElementById("ruleType").value = rule.type;
    document.getElementById("ruleSeverity").value = rule.severity;

    // Load conditions and actions
    clearConditionsAndActions();
    const conditions = JSON.parse(rule.conditions);
    const actions = JSON.parse(rule.actions);

    conditions.forEach((condition) => addConditionField(condition));
    actions.forEach((action) => addActionField(action));

    showModal();
  } catch (error) {
    console.error("Error loading rule:", error);
    showError("Failed to load alert rule");
  }
}

// Handle rule form submission
async function handleRuleSubmit(event) {
  event.preventDefault();

  const ruleId = document.getElementById("ruleId").value;
  const formData = {
    name: document.getElementById("ruleName").value,
    description: document.getElementById("ruleDescription").value,
    type: document.getElementById("ruleType").value,
    severity: document.getElementById("ruleSeverity").value,
    conditions: getConditions(),
    actions: getActions(),
  };

  try {
    const response = await fetch("api/save_alert_rule.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        id: ruleId,
        ...formData,
      }),
    });

    const result = await response.json();

    if (result.success) {
      closeModal();
      loadRules();
      showSuccess(
        ruleId ? "Rule updated successfully" : "Rule created successfully"
      );
    } else {
      showError(result.message || "Failed to save rule");
    }
  } catch (error) {
    console.error("Error saving rule:", error);
    showError("Failed to save rule");
  }
}

// Delete rule
async function deleteRule(ruleId) {
  if (!confirm("Are you sure you want to delete this rule?")) {
    return;
  }

  try {
    const response = await fetch("api/delete_alert_rule.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ id: ruleId }),
    });

    const result = await response.json();

    if (result.success) {
      loadRules();
      showSuccess("Rule deleted successfully");
    } else {
      showError(result.message || "Failed to delete rule");
    }
  } catch (error) {
    console.error("Error deleting rule:", error);
    showError("Failed to delete rule");
  }
}

// Add condition field
function addConditionField(condition = null) {
  const container = document.getElementById("conditionsContainer");
  const conditionDiv = document.createElement("div");
  conditionDiv.className = "condition-item";

  conditionDiv.innerHTML = `
        <select class="condition-operator" required>
            <option value=">" ${
              condition?.operator === ">" ? "selected" : ""
            }>Greater than</option>
            <option value="<" ${
              condition?.operator === "<" ? "selected" : ""
            }>Less than</option>
            <option value=">=" ${
              condition?.operator === ">=" ? "selected" : ""
            }>Greater than or equal</option>
            <option value="<=" ${
              condition?.operator === "<=" ? "selected" : ""
            }>Less than or equal</option>
            <option value="==" ${
              condition?.operator === "==" ? "selected" : ""
            }>Equal to</option>
            <option value="!=" ${
              condition?.operator === "!=" ? "selected" : ""
            }>Not equal to</option>
            <option value="contains" ${
              condition?.operator === "contains" ? "selected" : ""
            }>Contains</option>
            <option value="regex" ${
              condition?.operator === "regex" ? "selected" : ""
            }>Matches regex</option>
            <option value="threshold" ${
              condition?.operator === "threshold" ? "selected" : ""
            }>Threshold</option>
        </select>
        <input type="text" class="condition-value" placeholder="Value" 
               value="${condition?.value || ""}" required>
        <input type="text" class="condition-metric" placeholder="Metric name"
               value="${condition?.metric || ""}"
               style="display: ${
                 condition?.operator === "threshold" ? "inline-block" : "none"
               }">
        <input type="number" class="condition-period" placeholder="Period (seconds)"
               value="${condition?.period || ""}"
               style="display: ${
                 condition?.operator === "threshold" ? "inline-block" : "none"
               }">
        <button type="button" class="btn btn-danger btn-sm" onclick="removeCondition(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

  container.appendChild(conditionDiv);

  // Add event listener for operator change
  const operatorSelect = conditionDiv.querySelector(".condition-operator");
  operatorSelect.addEventListener("change", function () {
    const metricInput = conditionDiv.querySelector(".condition-metric");
    const periodInput = conditionDiv.querySelector(".condition-period");

    if (this.value === "threshold") {
      metricInput.style.display = "inline-block";
      periodInput.style.display = "inline-block";
      metricInput.required = true;
      periodInput.required = true;
    } else {
      metricInput.style.display = "none";
      periodInput.style.display = "none";
      metricInput.required = false;
      periodInput.required = false;
    }
  });
}

// Add action field
function addActionField(action = null) {
  const container = document.getElementById("actionsContainer");
  const actionDiv = document.createElement("div");
  actionDiv.className = "action-item";

  actionDiv.innerHTML = `
        <select class="action-type" required>
            <option value="email" ${
              action?.type === "email" ? "selected" : ""
            }>Email</option>
            <option value="webhook" ${
              action?.type === "webhook" ? "selected" : ""
            }>Webhook</option>
            <option value="slack" ${
              action?.type === "slack" ? "selected" : ""
            }>Slack</option>
            <option value="sms" ${
              action?.type === "sms" ? "selected" : ""
            }>SMS</option>
        </select>
        <input type="text" class="action-value" 
               placeholder="Recipients/URL/Webhook URL/Phone numbers (comma-separated)"
               value="${
                 action?.recipients ||
                 action?.url ||
                 action?.webhook ||
                 action?.numbers ||
                 ""
               }"
               required>
        <button type="button" class="btn btn-danger btn-sm" onclick="removeAction(this)">
            <i class="fas fa-times"></i>
        </button>
    `;

  container.appendChild(actionDiv);
}

// Remove condition
function removeCondition(button) {
  button.closest(".condition-item").remove();
}

// Remove action
function removeAction(button) {
  button.closest(".action-item").remove();
}

// Get conditions from form
function getConditions() {
  const conditions = [];
  document.querySelectorAll(".condition-item").forEach((item) => {
    const condition = {
      operator: item.querySelector(".condition-operator").value,
      value: item.querySelector(".condition-value").value,
    };

    if (condition.operator === "threshold") {
      condition.metric = item.querySelector(".condition-metric").value;
      condition.period = parseInt(
        item.querySelector(".condition-period").value
      );
    }

    conditions.push(condition);
  });
  return conditions;
}

// Get actions from form
function getActions() {
  const actions = [];
  document.querySelectorAll(".action-item").forEach((item) => {
    const type = item.querySelector(".action-type").value;
    const value = item.querySelector(".action-value").value;

    const action = { type };
    switch (type) {
      case "email":
        action.recipients = value.split(",").map((email) => email.trim());
        break;
      case "webhook":
        action.url = value;
        break;
      case "slack":
        action.webhook = value;
        break;
      case "sms":
        action.numbers = value.split(",").map((number) => number.trim());
        break;
    }

    actions.push(action);
  });
  return actions;
}

// Clear conditions and actions
function clearConditionsAndActions() {
  document.getElementById("conditionsContainer").innerHTML = "";
  document.getElementById("actionsContainer").innerHTML = "";
}

// Show/hide modal
function showModal() {
  document.getElementById("ruleModal").style.display = "block";
}

function closeModal() {
  document.getElementById("ruleModal").style.display = "none";
}

// Helper functions
function formatDate(dateString) {
  return new Date(dateString).toLocaleString();
}

function escapeHtml(str) {
  const div = document.createElement("div");
  div.textContent = str;
  return div.innerHTML;
}

function showSuccess(message) {
  // Implement toast or notification
  alert(message);
}

function showError(message) {
  // Implement toast or notification
  alert("Error: " + message);
}
