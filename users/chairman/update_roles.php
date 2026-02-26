<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
// Default to an empty array if no roles are selected
$roles = isset($_POST['roles']) && is_array($_POST['roles']) ? $_POST['roles'] : [];

if ($user_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid user']);
    exit;
}

mysqli_begin_transaction($con);

try {
    // Delete existing roles for user
    $stmt = mysqli_prepare($con, "DELETE FROM user_roles WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Insert new roles
    if (!empty($roles)) {
        $insertQuery = "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)";
        $stmtInsert = mysqli_prepare($con, $insertQuery);

        foreach ($roles as $role_id) {
            $r_id = (int)$role_id;
            mysqli_stmt_bind_param($stmtInsert, "ii", $user_id, $r_id);
            mysqli_stmt_execute($stmtInsert);
        }
        mysqli_stmt_close($stmtInsert);
    }
    
    // Check if we need to update the customer role to ensure baseline access if no roles given
    // Actually, usually users should at least have customer. But let's leave it as explicit as possible.
    // If we want to enforce every user must have at least 'customer' (ID 1), we could do it here:
    // If empty roles, add customer role.
    if (empty($roles)) {
        $stmtFix = mysqli_prepare($con, "INSERT INTO user_roles (user_id, role_id) VALUES (?, 1)");
        mysqli_stmt_bind_param($stmtFix, "i", $user_id);
        mysqli_stmt_execute($stmtFix);
        mysqli_stmt_close($stmtFix);
    }

    mysqli_commit($con);
    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    mysqli_rollback($con);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
