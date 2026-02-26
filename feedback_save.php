<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

$redirect = $_POST['redirect'] ?? 'index.php';
$redirect = basename($redirect); // security

$message = trim($_POST['message'] ?? '');
$rating = (int) ($_POST['rating'] ?? 0);
$email = trim($_POST['email'] ?? '');

if ($message === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $rating < 1 || $rating > 5) {
    $_SESSION['feedback'] = 'error';   // ✅ flash message
    header("Location: $redirect");
    exit;
}

require __DIR__ . '/config/db.php';

$stmt = $con->prepare("INSERT INTO feedbacks (email, message, rating) VALUES (?, ?, ?)");
if (!$stmt) {
    $_SESSION['feedback'] = 'error';   // ✅ flash message
    header("Location: $redirect");
    exit;
}

$stmt->bind_param("ssi", $email, $message, $rating);
$stmt->execute();

$_SESSION['feedback'] = 'success';     // ✅ flash message
header("Location: $redirect");
exit;
