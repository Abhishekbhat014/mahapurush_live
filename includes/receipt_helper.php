<?php
require __DIR__ . "/../config/db.php";

if (!isset($con)) {
    die('Database connection not found in receipt_helper.php');
}

/**
 * Generate a unique receipt number
 */
function generateReceiptNumber(string $prefix = 'TMP'): string
{
    return $prefix . '/' . date('Y') . '/' . date('mdHis') . '/' . rand(100, 999);
}

/**
 * Create a receipt
 *
 * @param mysqli $con
 * @param int    $userId
 * @param string $purpose        donation | pooja | contribution
 * @param float  $amount
 * @param string $sourceTable    pooja | donations | contributions
 *
 * @return int receipt_id
 * @throws Exception
 */
function createReceipt(mysqli $con, int $userId, string $purpose, float $amount, string $sourceTable): int
{
    $receiptNo = generateReceiptNumber(strtoupper(substr($purpose, 0, 3)));

    $stmt = $con->prepare(
        "INSERT INTO receipt (receipt_no, user_id, purpose, amount, source_table, issued_on, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
    );

    if (!$stmt) {
        throw new Exception('Receipt prepare failed');
    }

    $stmt->bind_param(
        "sisds",
        $receiptNo,
        $userId,
        $purpose,
        $amount,
        $sourceTable
    );

    if (!$stmt->execute()) {
        throw new Exception('Receipt creation failed');
    }

    return $stmt->insert_id;
}

/**
 * Link receipt to payment
 */
function attachReceiptToPayment(mysqli $con, int $paymentId, int $receiptId): void
{
    $stmt = $con->prepare(
        "UPDATE payments SET receipt_id = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $receiptId, $paymentId);
    $stmt->execute();
}

/**
 * Link payment to pooja
 */
function attachPaymentToPooja(mysqli $con, int $poojaId, int $paymentId): void
{
    $stmt = $con->prepare(
        "UPDATE pooja SET payment_id = ?, status = 'paid' WHERE id = ?"
    );
    $stmt->bind_param("ii", $paymentId, $poojaId);
    $stmt->execute();
}

/**
 * Link payment to donation
 */
function attachPaymentToDonation(mysqli $con, int $donationId, int $paymentId): void
{
    $stmt = $con->prepare(
        "UPDATE donations SET payment_id = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $paymentId, $donationId);
    $stmt->execute();
}

/**
 * Link receipt to contribution
 */
function attachReceiptToContribution(mysqli $con, int $contributionId, int $receiptId): void
{
    $stmt = $con->prepare(
        "UPDATE contributions SET receipt_id = ? WHERE id = ?"
    );
    $stmt->bind_param("ii", $receiptId, $contributionId);
    $stmt->execute();
}

/**
 * Validate receipt ownership (SECURITY CRITICAL)
 *
 * @param mysqli $con
 * @param int    $receiptId
 * @param int    $userId
 *
 * @return bool
 */
function validateReceiptOwnership(mysqli $con, int $receiptId, int $userId): bool
{
    $stmt = $con->prepare(
        "SELECT id FROM receipt WHERE id = ? AND user_id = ? LIMIT 1"
    );
    $stmt->bind_param("ii", $receiptId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();

    return $res->num_rows === 1;
}
