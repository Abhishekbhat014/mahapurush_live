<?php

// -------------------------------------------------
// 1. Start session (must be first)
// -------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// -------------------------------------------------
// 2. Supported languages
// -------------------------------------------------
$supportedLangs = ['en', 'mr'];

// -------------------------------------------------
// 3. Handle language change from URL
// -------------------------------------------------
if (isset($_GET['lang']) && in_array($_GET['lang'], $supportedLangs, true)) {

    // Save for current session
    $_SESSION['lang'] = $_GET['lang'];

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

// -------------------------------------------------
// 4. Decide active language
// Priority: Session > Cookie > Default
// -------------------------------------------------
$lang = $_SESSION['lang']
    ?? $_COOKIE['lang']
    ?? 'en';

// Safety check (fallback)
if (!in_array($lang, $supportedLangs, true)) {
    $lang = 'en';
}

// -------------------------------------------------
// 5. Load language file
// -------------------------------------------------
$langFile = __DIR__ . "/../lang/{$lang}.php";

// Fallback if file missing
if (!file_exists($langFile)) {
    $langFile = __DIR__ . "/../lang/en.php";
}

$t = require $langFile;

// -------------------------------------------------
// 6. $lang  -> current language code
//    $t     -> translations array
// -------------------------------------------------
