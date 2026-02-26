<?php
// includes/razorpay_helper.php

// --- SECURITY: PREVENT DIRECT ACCESS ---
if (count(get_included_files()) === 1) {
    header("HTTP/1.1 403 Forbidden");
    exit("<h3>Access Denied</h3><p>You cannot access helper files directly.</p>");
}
// ---------------------------------------

require_once __DIR__ . '/../config/razorpay.php';

/**
 * Creates a Razorpay Order ID using cURL.
 * 
 * @param float $amount Amount in INR (will be converted to paisa)
 * @param string $receipt receipt string (max 40 chars)
 * @return array|null Returns associative array with id, amount, etc. or null on error.
 */
function createRazorpayOrder($amount, $receipt) {
    $url = "https://api.razorpay.com/v1/orders";
    
    // Convert to paisa (multiply by 100)
    $amountInPaisa = round((float)$amount * 100);
    
    $data = [
        "amount" => $amountInPaisa,
        "currency" => "INR",
        "receipt" => substr($receipt, 0, 40)
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, RAZORPAY_KEY_ID . ":" . RAZORPAY_KEY_SECRET);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    
    // Adding this since local environments sometimes have SSL certification issues with cURL
    // Remove or set to true in production if SSL certificates are properly configured
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        error_log("Razorpay cURL Error: " . curl_error($ch));
        curl_close($ch);
        return null;
    }
    
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    } else {
        error_log("Razorpay API Error ($httpCode): " . $response);
        return null;
    }
}

/**
 * Verifies the Razorpay Signature.
 * 
 * @param string $orderId
 * @param string $paymentId
 * @param string $signature
 * @return bool True if valid, False otherwise
 */
function verifyRazorpaySignature($orderId, $paymentId, $signature) {
    // Construct the payload by concatenating order_id and payment_id with a pipe
    $payload = $orderId . '|' . $paymentId;
    
    // Generate HMAC SHA256 signature using the key secret
    $expectedSignature = hash_hmac('sha256', $payload, RAZORPAY_KEY_SECRET);
    
    // Use hash_equals to prevent timing attacks
    return hash_equals($expectedSignature, $signature);
}
?>
