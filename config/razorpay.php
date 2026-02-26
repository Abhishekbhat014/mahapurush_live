<?php
// config/razorpay.php

// --- SECURITY: PREVENT DIRECT ACCESS ---
if (count(get_included_files()) === 1) {
    header("HTTP/1.1 403 Forbidden");
    exit("<h3>Access Denied</h3><p>You cannot access configuration files directly.</p>");
}
// ---------------------------------------

// Replace these with your actual Razorpay Test or Live Keys
// You can get them from the Razorpay Dashboard -> Settings -> API Keys
define('RAZORPAY_KEY_ID', 'rzp_test_SKQQnri9oeNMRj'); 
define('RAZORPAY_KEY_SECRET', 'pkukkaLV4z3km48vDGLRKPgi');
?>
