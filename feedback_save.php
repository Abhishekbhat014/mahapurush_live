<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}



$redirect = $_POST['redirect'] ?? ($_SERVER['HTTP_REFERER'] ?? 'index.php');

function append_query_param(string $url, string $key, string $value): string
{
    $separator = (strpos($url, '?') !== false) ? '&' : '?';
    return $url . $separator . urlencode($key) . '=' . urlencode($value);
}

$message = trim($_POST['message'] ?? '');
$rating = (int) ($_POST['rating'] ?? 0);
$email = trim($_POST['email'] ?? '');
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

if ($message === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $rating < 1 || $rating > 5) {
    header("Location: " . append_query_param($redirect, 'feedback', 'error'));
    exit;
}

require __DIR__ . '/config/db.php';

$stmt = $con->prepare("INSERT INTO feedbacks (user_id, email, message, rating) VALUES (?, ?, ?, ?)");
if (!$stmt) {
    header("Location: " . append_query_param($redirect, 'feedback', 'error'));
    exit;
}

$stmt->bind_param("issi", $userId, $email, $message, $rating);
$stmt->execute();

header("Location: " . append_query_param($redirect, 'feedback', 'success'));
exit;
