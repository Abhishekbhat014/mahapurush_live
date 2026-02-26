<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/receipt_helper.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: donations.php");
    exit;
}

$uid = $_SESSION['user_id'] ?? NULL;

// Retrieve form fields
$fullName = trim($_POST['full_name'] ?? '');
$amount = trim($_POST['amount'] ?? '');
$paymentMethod = strtolower(trim($_POST['payment_method'] ?? 'cash'));
$note = trim($_POST['note'] ?? '');

$paymentMethods = [];
if ($con) {
    $res = $con->query("SELECT method_name FROM payment_methods WHERE is_active = 1");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $paymentMethods[] = $row['method_name'];
        }
    }
}
$allowedMethods = array_map('strtolower', $paymentMethods);

if ($fullName === '' || !is_numeric($amount) || $amount <= 0) {
    header("Location: donations.php?error=" . urlencode($t['err_valid_name_amount'] ?? 'Please provide valid details.'));
    exit;
} elseif (!in_array($paymentMethod, $allowedMethods, true)) {
    header("Location: donations.php?error=" . urlencode($t['something_went_wrong'] ?? 'Invalid payment method.'));
    exit;
}

try {
    $con->begin_transaction();

    // 1. Insert into payments table
    // Treasurers receive donations on behalf of users, but we might not have a specific user account. 
    // We will leave user_id NULL if the devotee isn't logged in, but we can store their name.
    $status = 'success';
    $stmt = $con->prepare("INSERT INTO payments (donor_name, amount, note, payment_method, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("sdsss", $fullName, $amount, $note, $paymentMethod, $status);
    $stmt->execute();
    $paymentId = $stmt->insert_id;

    // 2. Generate receipt
    // Let's pass the treasurer uid.
    $receiptId = createReceipt($con, $uid, 'donation', (float) $amount, 'payments');

    // 3. Attach receipt to payment
    attachReceiptToPayment($con, $paymentId, $receiptId);

    $con->commit();
    header("Location: donations.php?success=" . urlencode($t['donation_receipt_generated'] ?? 'Donation recorded successfully!'));
    exit;
} catch (Exception $e) {
    $con->rollback();
    header("Location: donations.php?error=" . urlencode($t['something_went_wrong'] ?? 'Something went wrong. Please try again.'));
    exit;
}
