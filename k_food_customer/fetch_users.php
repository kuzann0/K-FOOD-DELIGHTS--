<?php
include 'config.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Prepare the SQL statement
    $sql = "SELECT id, firstname, lastname, email, username, created_at FROM users";
    $stmt = $conn->prepare($sql);
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    $users = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            // Exclude password for security
            $users[] = array(
                'id' => $row['id'],
                'firstname' => $row['firstname'],
                'lastname' => $row['lastname'],
                'email' => $row['email'],
                'username' => $row['username'],
                'created_at' => $row['created_at']
            );
        }
        echo json_encode(array(
            'status' => 'success',
            'data' => $users
        ));
    } else {
        echo json_encode(array(
            'status' => 'success',
            'data' => []
        ));
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array(
        'status' => 'error',
        'message' => 'Error fetching users: ' . $e->getMessage()
    ));
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
