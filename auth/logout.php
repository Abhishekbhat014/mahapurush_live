<?php
// ------------------------------------
// 1. Start session
// ------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------
// 2. Preserve language (optional)
// ------------------------------------
$lang = $_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'en');

// ------------------------------------
// 3. Clear all session data
// ------------------------------------
$_SESSION = [];

// ------------------------------------
// 4. Destroy session
// ------------------------------------
session_destroy();

// ------------------------------------
// 5. Restore language in new session
// ------------------------------------
session_start();
$_SESSION['lang'] = $lang;

// ------------------------------------
// 6. Redirect to login page
// ------------------------------------
header("Location: login.php");
exit;
