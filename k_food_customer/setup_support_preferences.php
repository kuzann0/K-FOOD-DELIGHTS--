<?php
require_once 'includes/DatabaseConnection.php';

function setupSupportAndPreferences() {
    try {
        $db = new DatabaseConnection();
        $conn = $db->getConnection();

        // Read and execute SQL files
        $sqlFiles = [
            'sql/create_user_preferences.sql',
            'sql/create_support_tables.sql'
        ];

        foreach ($sqlFiles as $sqlFile) {
            $sql = file_get_contents($sqlFile);
            
            // Split SQL file into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    if (!$conn->query($statement)) {
                        throw new Exception("Error executing SQL: " . $conn->error);
                    }
                }
            }
        }

        // Create upload directories if they don't exist
        $directories = [
            'uploads/support_attachments'
        ];

        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }

        return ['success' => true, 'message' => 'Support and preferences tables created successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Run setup if this file is accessed directly
if (__FILE__ == $_SERVER['SCRIPT_FILENAME']) {
    header('Content-Type: application/json');
    echo json_encode(setupSupportAndPreferences());
}
?>