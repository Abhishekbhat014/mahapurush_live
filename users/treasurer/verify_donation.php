<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';

if (empty($_SESSION['logged_in']) || !in_array('treasurer', $_SESSION['roles'] ?? [])) {
    header("Location: ../../auth/login.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/receipt_helper.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con) {
    if (!isset($_POST['payment_id']) || empty($_POST['payment_id'])) {
        header("Location: donation_records.php?error=" . urlencode('Invalid Request.'));
        exit;
    }

    $paymentId = (int) $_POST['payment_id'];
    
    // Validate it's pending and cash
    $stmt = $con->prepare("SELECT user_id, amount, payment_method, status FROM payments WHERE id = ?");
    $stmt->bind_param("i", $paymentId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        header("Location: donation_records.php?error=" . urlencode('Record not found.'));
        exit;
    }

    $payment = $res->fetch_assoc();

    if ($payment['status'] !== 'pending' || strtolower($payment['payment_method']) !== 'cash') {
        header("Location: donation_records.php?error=" . urlencode('This transaction cannot be verified.'));
        exit;
    }

    $con->begin_transaction();
    try {
        // Update Status
        $upd = $con->prepare("UPDATE payments SET status = 'success', updated_at = NOW() WHERE id = ?");
        $upd->bind_param("i", $paymentId);
        $upd->execute();

        // Generate Receipt
        $uid = $payment['user_id'];
        $amount = (float) $payment['amount'];
        
        $receiptId = createReceipt($con, $uid, 'donation', $amount, 'payments');

        // Attach Receipt
        attachReceiptToPayment($con, $paymentId, $receiptId);

        $con->commit();
        header("Location: donation_records.php?success=" . urlencode('Cash verification successful! Receipt generated.'));
        exit;
    } catch (Exception $e) {
        $con->rollback();
        header("Location: donation_records.php?error=" . urlencode('Error verifying transaction: ' . $e->getMessage()));
        exit;
    }
}

header("Location: donation_records.php");
exit;
