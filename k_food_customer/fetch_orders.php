<?php
include 'config.php';

// Set proper headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Prepare the SQL statement
    $sql = "SELECT order_id, customer_name, email, phone, address, product, quantity, total_price, 
            order_date, delivery_date FROM orders ORDER BY order_date DESC";
    $stmt = $conn->prepare($sql);
    
    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();
    
    $orders = array();
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $orders[] = array(
                'order_id' => $row['order_id'],
                'customer_name' => $row['customer_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'address' => $row['address'],
                'product' => $row['product'],
                'quantity' => $row['quantity'],
                'total_price' => $row['total_price'],
                'order_date' => $row['order_date'],
                'delivery_date' => $row['delivery_date']
            );
        }
        echo json_encode(array(
            'status' => 'success',
            'data' => $orders
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
        'message' => 'Error fetching orders: ' . $e->getMessage()
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
