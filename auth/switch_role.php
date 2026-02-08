<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$requestedRole = $_POST['role'] ?? '';
$availableRoles = $_SESSION['roles'] ?? [];

if ($requestedRole !== '' && in_array($requestedRole, $availableRoles, true)) {
    $_SESSION['primary_role'] = $requestedRole;
    // Backward compatibility for legacy checks
    $_SESSION['role'] = $requestedRole;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");
header("Location: redirect.php");
exit;
