<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "kfood_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $customer_name = $_POST['customer_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $product = $_POST['product'];
    $quantity = $_POST['quantity'];
    $total_price = $_POST['price'] * $quantity;

    $sql = "INSERT INTO orders (customer_name, email, phone, address, product, quantity, total_price, order_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssid", $customer_name, $email, $phone, $address, $product, $quantity, $total_price);
    
    if ($stmt->execute()) {
        echo "<script>alert('Order placed successfully!'); window.location.href='index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

$product = $_GET['product'] ?? '';
$price = $_GET['price'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Place Order - K-Food Delight</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-form {
            max-width: 600px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
        }
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .submit-btn {
            background: #ff6b6b;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .submit-btn:hover {
            background: #ff5252;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <!-- Copy your existing navbar code here -->
    </div>

    <div class="main-content">
        <div class="order-form">
            <h2>Place Your Order</h2>
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <input type="hidden" name="product" value="<?php echo htmlspecialchars($product); ?>">
                <input type="hidden" name="price" value="<?php echo htmlspecialchars($price); ?>">
                
                <div class="form-group">
                    <label for="customer_name">Full Name:</label>
                    <input type="text" id="customer_name" name="customer_name" required>
                </div>

                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="form-group">
                    <label for="address">Delivery Address:</label>
                    <input type="text" id="address" name="address" required>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                </div>

                <button type="submit" class="submit-btn">Place Order</button>
            </form>
        </div>
    </div>
</body>
</html>
