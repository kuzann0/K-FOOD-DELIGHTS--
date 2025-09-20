<?php
require_once 'config.php';

try {
    // Read SQL file content
    $sql = file_get_contents(__DIR__ . '/sql/update_billing_module.sql');

    // Execute multi query
    if ($conn->multi_query($sql)) {
        do {
            // Store first result set
            if ($result = $conn->store_result()) {
                $result->free();
            }
        } while ($conn->more_results() && $conn->next_result());

        echo "Successfully updated database schema.\n";
    } else {
        throw new Exception("Error updating database: " . $conn->error);
    }

    // Insert some default promotions
    $promoSql = "INSERT INTO promotions (code, type, discount_value, min_purchase, max_discount) VALUES
                 ('SENIOR20', 'senior_pwd', 20.00, 0.00, 1000.00),
                 ('PWD15', 'senior_pwd', 15.00, 0.00, 1000.00),
                 ('WELCOME10', 'percentage', 10.00, 500.00, 200.00)";

    if ($conn->query($promoSql)) {
        echo "Successfully added default promotions.\n";
    }

    // Insert buy 3 get 1 promo
    $buy3get1Sql = "INSERT INTO promotions (code, type, discount_value, min_purchase) VALUES
                    ('BUY3GET1', 'buy_x_get_y', 100.00, 0.00)";
    
    if ($conn->query($buy3get1Sql)) {
        $promoId = $conn->insert_id;
        $promoItemSql = "INSERT INTO promo_items (promo_id, buy_quantity, free_quantity) VALUES
                        ($promoId, 3, 1)";
        if ($conn->query($promoItemSql)) {
            echo "Successfully added Buy 3 Get 1 promotion.\n";
        }
    }

    echo "Setup completed successfully.\n";

} catch (Exception $e) {
    echo "Error during setup: " . $e->getMessage() . "\n";
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>