<?php
// ------------------------------------
// 1. Start session
// ------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------
// 2. Preserve language
// ------------------------------------
$lang = $_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'en');

// ------------------------------------
// 3. Unset all session variables
// ------------------------------------
$_SESSION = [];

// ------------------------------------
// 4. Delete session cookie
// ------------------------------------
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// ------------------------------------
// 5. Destroy session
// ------------------------------------
session_destroy();

// ------------------------------------
// 6. Restore language (session or cookie)
// ------------------------------------
session_start();
$_SESSION['lang'] = $lang;
setcookie('lang', $lang, time() + (86400 * 30), "/");



// ------------------------------------
// 8. Redirect to login
// ------------------------------------
header("Location: login.php");
exit;
