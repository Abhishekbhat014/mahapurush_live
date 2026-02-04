<?php
session_start();
require '../../includes/lang.php';
require '../../config/db.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$poojaTypeId = $_POST['pooja_type_id'];
$poojaDate = $_POST['pooja_date'];
$timeSlot = $_POST['time_slot'] ?? null;
$description = $_POST['description'] ?? null;

// fetch fee securely
$stmt = $con->prepare("SELECT fee FROM pooja_type WHERE id = ?");
$stmt->bind_param("i", $poojaTypeId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die($t['err_invalid_pooja_type']);
}

$fee = $res->fetch_assoc()['fee'];

// insert booking
$stmt = $con->prepare("
    INSERT INTO pooja
    (user_id, pooja_type_id, pooja_date, time_slot, description, fee, status)
    VALUES (?, ?, ?, ?, ?, ?, 'pending')
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

header("Location: pooja-history.php");
exit;
