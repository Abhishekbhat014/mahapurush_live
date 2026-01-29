<?php
// session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// supported languages
$supportedLangs = ['en', 'mr'];

// language change
// first check if lang is set or not and then check if the value in lang is supported by website or not
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs, true)) {

    // save language in session
    $_SESSION['lang'] = $_GET['lang'] ?? 'en';

    // Save for future visits (30 days)
    setcookie(
        'lang',
        $_GET['lang'],
        time() + (60 * 60 * 24 * 30),
        '/'
    );


    // Redirect to clean URL (remove ?lang=)
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit;
}

// Decide active language
// Priority: Session > Cookie > Default
$lang = $_SESSION['lang']
    ?? $_COOKIE['lang']
    ?? 'en';

// Safety check (fallback)
if (!in_array($lang, $supportedLangs, true)) {
    $lang = 'en';
}

// 5. Load language file
$langFile = __DIR__ . "/../lang/{$lang}.php";

// Fallback if file missing
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/en.php";
}

$t = require $langFile;

