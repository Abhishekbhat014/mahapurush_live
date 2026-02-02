<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

// Use primary_role set during login
$role = $_SESSION['primary_role'];

switch ($role) {

    case 'admin':
        header("Location: ../users/admin/dashboard.php");
        break;

    case 'member':
        header("Location: ../users/member/dashboard.php");
        break;

    case 'secretary':
        header("Location: ../users/secretary/dashboard.php");
        break;

    case 'treasurer':
        header("Location: ../users/treasurer/dashboard.php");
        break;

    case 'vice chairman':
        header("Location: ../users/vice_chairman/dashboard.php");
        break;

    case 'chairman':
        header("Location: ../users/chairman/dashboard.php");
        break;

    case 'customer':
        header("Location: ../users/customer/dashboard.php");
        break;

    default:
        header("Location: ../index.php");
        break;
}

exit;
