<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function get_user_avatar_url(string $uploadsPrefix = ''): string
{
    $name = trim($_SESSION['user_name'] ?? 'User');
    $photo = $_SESSION['user_photo'] ?? '';

    if (!empty($photo)) {
        $prefix = $uploadsPrefix;
        if ($prefix !== '' && substr($prefix, -1) !== '/' && substr($prefix, -1) !== '\\') {
            $prefix .= '/';
        }
        return $prefix . 'uploads/users/' . basename($photo);
    }

    return 'https://ui-avatars.com/api/?name=' . urlencode($name ?: 'User') . '&background=random';
}
