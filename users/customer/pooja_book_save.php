<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require '../../includes/lang.php';
require '../../config/db.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;



}
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');


if (empty($_SESSION['user_id'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = (int) $_SESSION['user_id'];

// Verify the user still exists in the database
$userCheckStmt = $con->prepare("SELECT id FROM users WHERE id = ?");
$userCheckStmt->bind_param("i", $userId);
$userCheckStmt->execute();
if ($userCheckStmt->get_result()->num_rows === 0) {
    session_destroy();
    header("Location: ../../auth/login.php?err=session_invalid");
    exit;
}
$userCheckStmt->close();

$poojaTypeId = (int) ($_POST['pooja_type_id'] ?? 0);
$poojaDate = trim($_POST['pooja_date'] ?? '');
$timeSlot = trim($_POST['time_slot'] ?? '');
$description = trim($_POST['description'] ?? '');

// fetch fee securely
$stmt = $con->prepare("SELECT fee FROM pooja_type WHERE id = ?");
$stmt->bind_param("i", $poojaTypeId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die($t['err_invalid_pooja_type']);
}

$fee = $res->fetch_assoc()['fee'];

if ($timeSlot === '') {
    header("Location: pooja_book.php?err=slot_required");
    exit;



}

$validSlots = ['morning', 'afternoon', 'evening'];
if (!in_array($timeSlot, $validSlots, true)) {
    header("Location: pooja_book.php?err=slot_invalid");
    exit;



}

// Check slot availability
$check = $con->prepare("SELECT COUNT(*) AS cnt FROM pooja WHERE pooja_date = ? AND time_slot = ? AND status <> 'cancelled'");
$check->bind_param("ss", $poojaDate, $timeSlot);
$check->execute();
$cnt = $check->get_result()->fetch_assoc()['cnt'] ?? 0;
if ((int) $cnt > 0) {
    header("Location: pooja_book.php?err=slot_unavailable");
    exit;



}

// Check full date
$fullCheck = $con->prepare("
    SELECT COUNT(DISTINCT time_slot) AS slots
    FROM pooja
    WHERE pooja_date = ?
      AND time_slot IS NOT NULL
      AND time_slot <> ''
      AND status <> 'cancelled'
");
$fullCheck->bind_param("s", $poojaDate);
$fullCheck->execute();
$slots = $fullCheck->get_result()->fetch_assoc()['slots'] ?? 0;
if ((int) $slots >= 3) {
    header("Location: pooja_book.php?err=slots_full");
    exit;



}

// insert booking
$stmt = $con->prepare("
    INSERT INTO pooja
    (user_id, pooja_type_id, pooja_date, time_slot, description, fee, status, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
");

$stmt->bind_param(
    "iisssd",
    $userId,
    $poojaTypeId,
    $poojaDate,
    $timeSlot,
    $description,
    $fee
);

$stmt->execute();

header("Location: pooja_history.php?success=1");
exit;



