<?php
// config/db.php

// --- SECURITY: PREVENT DIRECT ACCESS ---
// If this file is the only file included, it means it's being accessed directly.
if (count(get_included_files()) === 1) {
    header("HTTP/1.1 403 Forbidden");
    exit("<h3>Access Denied</h3><p>You cannot access configuration files directly.</p>");
}
// ---------------------------------------

$host = "localhost";
$user = "root";
$password = "";
$database = "mahapurush_mandir";

// 1. Enable strict error reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // 2. Create Connection
    $con = mysqli_connect($host, $user, $password, $database);

    // 3. Set Charset
    mysqli_set_charset($con, "utf8mb4");

    // 4. Set Timezone to India
    date_default_timezone_set('Asia/Kolkata');
    $con->query("SET time_zone = '+05:30'");

} catch (mysqli_sql_exception $e) {
    // 5. Log error and hide details
    error_log("Database Connection Error: " . $e->getMessage());
    die("<h3>System Error</h3><p>Unable to connect to the database. Please try again later.</p>");
}
?>