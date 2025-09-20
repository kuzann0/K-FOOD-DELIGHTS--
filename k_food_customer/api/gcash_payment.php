<?php
require_once '../config.php';
require_once '../includes/auth.php';

class GCashPayment {
    private $merchantId;
    private $merchantSecret;
    private $environment;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->loadSettings();
    }

    private function loadSettings() {
        $stmt = $this->conn->prepare("SELECT * FROM gcash_settings WHERE is_active = 1 LIMIT 1");
        $stmt->execute();
        $settings = $stmt->get_result()->fetch_assoc();
        
        if (!$settings) {
            throw new Exception("GCash integration is not configured");
        }

        $this->merchantId = $settings['merchant_id'];
        $this->merchantSecret = $settings['merchant_secret'];
        $this->environment = $settings['environment'];
    }

    public function createPayment($orderId, $amount) {
        // Get order details
        $stmt = $this->conn->prepare("
            SELECT o.*, u.email, CONCAT(u.first_name, ' ', u.last_name) as customer_name 
            FROM orders o 
            JOIN users u ON o.customer_id = u.user_id 
            WHERE o.order_id = ?
        ");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            throw new Exception("Order not found");
        }

        // Create GCash payment source
        $data = [
            'data' => [
                'attributes' => [
                    'amount' => $amount * 100, // Convert to cents
                    'currency' => 'PHP',
                    'source' => [
                        'type' => 'gcash',
                        'currency' => 'PHP',
                        'redirect' => [
                            'success' => BASE_URL . '/payment/success.php',
                            'failed' => BASE_URL . '/payment/failed.php'
                        ]
                    ],
                    'description' => "Order #$orderId",
                    'statement_descriptor' => 'K-FOOD DELIGHTS',
                ]
            ]
        ];

        // Make API call to GCash
        $response = $this->makeGCashApiCall('/payments', 'POST', $data);

        if (!isset($response['data']['attributes']['redirect']['checkout_url'])) {
            throw new Exception("Failed to create GCash payment");
        }

        // Record payment attempt
        $stmt = $this->conn->prepare("
            INSERT INTO payment_transactions (
                order_id, method_id, amount, status, reference_number, payment_details
            ) VALUES (
                ?, 
                (SELECT method_id FROM payment_methods WHERE method_name = 'GCash'),
                ?, 'pending', ?, ?
            )
        ");
        $refNumber = $response['data']['id'];
        $paymentDetails = json_encode($response['data']);
        $stmt->bind_param("idss", $orderId, $amount, $refNumber, $paymentDetails);
        $stmt->execute();

        return [
            'checkout_url' => $response['data']['attributes']['redirect']['checkout_url'],
            'reference' => $refNumber
        ];
    }

    public function verifyPayment($referenceNumber) {
        $response = $this->makeGCashApiCall("/payments/$referenceNumber", 'GET');

        if (!isset($response['data']['attributes']['status'])) {
            throw new Exception("Failed to verify payment status");
        }

        $paymentStatus = $response['data']['attributes']['status'];
        $stmt = $this->conn->prepare("
            UPDATE payment_transactions 
            SET status = ?, payment_details = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE reference_number = ?
        ");

        $status = $paymentStatus === 'paid' ? 'completed' : 'failed';
        $details = json_encode($response['data']);
        $stmt->bind_param("sss", $status, $details, $referenceNumber);
        $stmt->execute();

        if ($paymentStatus === 'paid') {
            // Update order status
            $stmt = $this->conn->prepare("
                UPDATE orders o
                JOIN payment_transactions pt ON o.order_id = pt.order_id
                SET o.payment_status = 'paid',
                    o.order_status = 'processing'
                WHERE pt.reference_number = ?
            ");
            $stmt->bind_param("s", $referenceNumber);
            $stmt->execute();

            // Generate receipt
            $this->generateReceipt($referenceNumber);
        }

        return $status;
    }

    private function generateReceipt($referenceNumber) {
        require_once '../vendor/autoload.php'; // For TCPDF
        
        $stmt = $this->conn->prepare("
            SELECT o.*, pt.amount, pt.reference_number,
                   u.first_name, u.last_name, u.email,
                   GROUP_CONCAT(
                       CONCAT(oi.quantity, 'x ', p.product_name, ' @ ', oi.unit_price)
                       SEPARATOR '\n'
                   ) as items
            FROM orders o
            JOIN payment_transactions pt ON o.order_id = pt.order_id
            JOIN users u ON o.customer_id = u.user_id
            JOIN order_items oi ON o.order_id = oi.order_id
            JOIN products p ON oi.product_id = p.product_id
            WHERE pt.reference_number = ?
            GROUP BY o.order_id
        ");
        $stmt->bind_param("s", $referenceNumber);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();

        // Generate PDF using TCPDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        // ... PDF generation code ...
        $pdfContent = $pdf->Output('', 'S');
        $pdfPath = "receipts/" . $referenceNumber . ".pdf";
        
        file_put_contents("../uploads/" . $pdfPath, $pdfContent);

        // Store receipt record
        $stmt = $this->conn->prepare("
            INSERT INTO digital_receipts (
                order_id, receipt_number, receipt_content, pdf_path
            ) VALUES (
                ?, ?, ?, ?
            )
        ");
        $receiptContent = json_encode($data);
        $stmt->bind_param("isss", $data['order_id'], $referenceNumber, $receiptContent, $pdfPath);
        $stmt->execute();
    }

    private function makeGCashApiCall($endpoint, $method, $data = null) {
        $baseUrl = $this->environment === 'production' 
            ? 'https://api.gcash.com/pg/v1' 
            : 'https://api.gcash.sandbox.com/pg/v1';

        $ch = curl_init($baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Basic ' . base64_encode($this->merchantId . ':' . $this->merchantSecret),
            'Content-Type: application/json'
        ]);

        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($statusCode !== 200) {
            throw new Exception("GCash API error: " . $response);
        }

        return json_decode($response, true);
    }
}

// API endpoint handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $gcash = new GCashPayment($conn);

        switch ($data['action']) {
            case 'create':
                $result = $gcash->createPayment($data['order_id'], $data['amount']);
                echo json_encode(['success' => true, 'data' => $result]);
                break;

            case 'verify':
                $status = $gcash->verifyPayment($data['reference']);
                echo json_encode(['success' => true, 'status' => $status]);
                break;

            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
?>
