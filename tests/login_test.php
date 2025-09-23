<?php
require_once __DIR__ . '/../k_food_customer/config.php';
require_once __DIR__ . '/../k_food_customer/includes/auth.php';

/**
 * Login System Test Suite
 */
class LoginTestSuite {
    private $conn;
    private $testResults = [];

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function runTests() {
        echo "\nStarting Login System Tests...\n";
        echo "===========================\n\n";

        $this->testDatabaseStructure();
        $this->testUserCreation();
        $this->testLoginValidation();
        $this->testRedirection();

        $this->displayResults();
    }

    private function testDatabaseStructure() {
        try {
            // Check users table existence
            $tableExists = $this->conn->query("
                SELECT COUNT(*) as table_exists 
                FROM information_schema.tables 
                WHERE table_schema = DATABASE() 
                AND table_name = 'users'
            ")->fetch_assoc()['table_exists'];

            $this->logResult('Users Table Exists', $tableExists === '1');

            if ($tableExists) {
                // Check required columns
                $requiredColumns = ['id', 'username', 'password', 'email', 'role_id', 
                                  'account_status', 'login_attempts', 'last_login'];
                
                $columnsQuery = $this->conn->query("
                    SELECT COLUMN_NAME 
                    FROM INFORMATION_SCHEMA.COLUMNS 
                    WHERE TABLE_SCHEMA = DATABASE()
                    AND TABLE_NAME = 'users'
                ");
                
                $existingColumns = [];
                while ($column = $columnsQuery->fetch_assoc()) {
                    $existingColumns[] = $column['COLUMN_NAME'];
                }

                foreach ($requiredColumns as $column) {
                    $this->logResult(
                        "Column '$column' Exists", 
                        in_array($column, $existingColumns)
                    );
                }
            }
        } catch (Exception $e) {
            $this->logResult('Database Structure Test', false, $e->getMessage());
        }
    }

    private function testUserCreation() {
        try {
            // Create test user
            $username = 'test_user_' . time();
            $password = password_hash('Test123!', PASSWORD_DEFAULT);
            $email = "test_{$username}@example.com";

            $stmt = $this->conn->prepare("
                INSERT INTO users (username, password, email, role_id, account_status) 
                VALUES (?, ?, ?, 1, 'active')
            ");
            $stmt->bind_param('sss', $username, $password, $email);
            $success = $stmt->execute();

            $this->logResult('Test User Creation', $success);

            if ($success) {
                // Store test user ID for cleanup
                $this->testUserId = $this->conn->insert_id;
            }
        } catch (Exception $e) {
            $this->logResult('User Creation Test', false, $e->getMessage());
        }
    }

    private function testLoginValidation() {
        try {
            // Test valid login
            $stmt = $this->conn->prepare("
                SELECT id, password, role_id, account_status 
                FROM users 
                WHERE username = ?
            ");
            $username = 'test_user_' . time();
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $this->logResult('Login Query Execution', $result !== false);
            
            if ($result) {
                $user = $result->fetch_assoc();
                $this->logResult(
                    'Password Verification', 
                    password_verify('Test123!', $user['password'])
                );
            }

            // Test login attempts update
            $updateStmt = $this->conn->prepare("
                UPDATE users 
                SET last_login = CURRENT_TIMESTAMP, 
                    login_attempts = 0 
                WHERE user_id = ?
            ");
            $updateStmt->bind_param('i', $this->testUserId);
            $this->logResult('Login Attempt Update', $updateStmt->execute());

        } catch (Exception $e) {
            $this->logResult('Login Validation Test', false, $e->getMessage());
        }
    }

    private function testRedirection() {
        // Test redirection URLs
        $testUrls = [
            '/cart.php' => true,
            '/profile.php' => true,
            'http://malicious.com' => false,
            'javascript:alert(1)' => false
        ];

        foreach ($testUrls as $url => $shouldAllow) {
            $sanitizedUrl = filter_var($url, FILTER_SANITIZE_URL);
            $isValid = false;

            if (strpos($url, 'http') === 0) {
                // External URL - should only allow same domain
                $urlHost = parse_url($url, PHP_URL_HOST);
                $isValid = $urlHost === $_SERVER['HTTP_HOST'];
            } else {
                // Internal URL - should start with /
                $isValid = strpos($url, '/') === 0;
            }

            $this->logResult(
                "URL Validation ($url)", 
                $isValid === $shouldAllow,
                $shouldAllow ? "Should allow" : "Should block"
            );
        }
    }

    private function logResult($test, $passed, $message = '') {
        $this->testResults[] = [
            'test' => $test,
            'status' => $passed ? 'PASS' : 'FAIL',
            'message' => $message
        ];
    }

    private function displayResults() {
        echo "\nTest Results:\n";
        echo "============\n";
        
        $passed = 0;
        $total = count($this->testResults);
        
        foreach ($this->testResults as $result) {
            echo sprintf(
                "%-30s: %s%s\n",
                $result['test'],
                $result['status'],
                $result['message'] ? " ({$result['message']})" : ''
            );
            
            if ($result['status'] === 'PASS') {
                $passed++;
            }
        }
        
        echo "\nSummary:\n";
        echo "========\n";
        echo "Total Tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: " . ($total - $passed) . "\n";
        echo "Success Rate: " . round(($passed / $total) * 100, 2) . "%\n";
    }

    public function cleanup() {
        try {
            // Remove test user
            if (isset($this->testUserId)) {
                $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param('i', $this->testUserId);
                $stmt->execute();
            }
        } catch (Exception $e) {
            echo "\nCleanup error: " . $e->getMessage() . "\n";
        }
    }

    public function __destruct() {
        $this->cleanup();
    }
}

// Run the tests
$testSuite = new LoginTestSuite($conn);
$testSuite->runTests();