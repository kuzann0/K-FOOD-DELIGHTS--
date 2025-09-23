<?php
require_once '../../includes/AlertRuleEngine.php';
require_once '../../includes/database.php';

// Initialize the database connection
$db = new Database();
$alertEngine = new KFood\Monitoring\AlertRuleEngine($db->getConnection());

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle different HTTP methods
switch ($method) {
    case 'GET':
        // Get a specific rule
        if (isset($_GET['id'])) {
            $rule = $alertEngine->getRules(['id' => $_GET['id']]);
            echo json_encode($rule[0] ?? null);
        }
        // Get all rules with filters
        else {
            $filters = [];
            if (isset($_GET['type'])) $filters['type'] = $_GET['type'];
            if (isset($_GET['severity'])) $filters['severity'] = $_GET['severity'];
            if (isset($_GET['status'])) $filters['status'] = $_GET['status'];
            
            $rules = $alertEngine->getRules($filters);
            echo json_encode($rules);
        }
        break;
        
    case 'POST':
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        // Create or update rule
        if (isset($data['id'])) {
            // Update existing rule
            $success = $alertEngine->updateRule($data['id'], [
                'name' => $data['name'],
                'description' => $data['description'],
                'type' => $data['type'],
                'conditions' => $data['conditions'],
                'actions' => $data['actions'],
                'severity' => $data['severity']
            ]);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Rule updated successfully' : 'Failed to update rule'
            ]);
        } else {
            // Create new rule
            try {
                $ruleId = $alertEngine->createRule([
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'type' => $data['type'],
                    'conditions' => $data['conditions'],
                    'actions' => $data['actions'],
                    'severity' => $data['severity']
                ]);
                
                echo json_encode([
                    'success' => true,
                    'id' => $ruleId,
                    'message' => 'Rule created successfully'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create rule: ' . $e->getMessage()
                ]);
            }
        }
        break;
        
    case 'DELETE':
        // Delete rule
        if (isset($_GET['id'])) {
            $success = $alertEngine->deleteRule($_GET['id']);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Rule deleted successfully' : 'Failed to delete rule'
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Rule ID is required'
            ]);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}