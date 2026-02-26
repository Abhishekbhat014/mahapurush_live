<?php
// includes/razorpay_create_order.php
session_start();

// Ensure the request is POST and content type is JSON
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "message" => "Invalid method"]);
    exit;
}

if (empty($_SESSION['logged_in'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit;
}

// Require configuration and helper
require_once __DIR__ . '/razorpay_helper.php';

$input = json_decode(file_get_contents('php://input'), true);
$amount = floatval($input['amount'] ?? 0);
$name = trim($input['name'] ?? 'Donor');

if ($amount <= 0) {
    echo json_encode(["success" => false, "message" => "Invalid amount"]);
    exit;
}

// Create Razorpay Order
// Receipt can be a quick unique string
$receiptId = "RC-" . time() . '-' . rand(100, 999);
$order = createRazorpayOrder($amount, $receiptId);

if ($order && isset($order['id'])) {
    // Send back the Razorpay Key ID and Order ID
    echo json_encode([
        "success" => true, 
        "order_id" => $order['id'], 
        "amount" => $order['amount'],
        "key_id" => RAZORPAY_KEY_ID
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to create Razorpay Order. Check configuration."]);
}
?>
