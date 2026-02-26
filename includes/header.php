<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Handle language switch from dropdown
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'mr'])) {
    $_SESSION['lang'] = $_GET['lang'];
    setcookie('lang', $_GET['lang'], time() + (86400 * 30), "/"); // 30 days
    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?')); // remove ?lang from URL
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/user_avatar.php';

$isLoggedIn = $_SESSION['logged_in'] ?? false;
$uid = $_SESSION['user_id'] ?? null;
$currentLang = $lang ?? ($_SESSION['lang'] ?? ($_COOKIE['lang'] ?? 'en'));

// Fetch committee for dropdown (Modified to fetch role_name)
$headerMembers = [];
if ($con) {
    $cmRes = mysqli_query(
        $con,
        "SELECT u.first_name, u.last_name, u.photo, r.name as role_name 
         FROM users u 
         JOIN user_roles ur ON u.id = ur.user_id 
         JOIN roles r ON ur.role_id = r.id 
         WHERE r.name != 'customer' 
         ORDER BY r.name, u.first_name 
         LIMIT 5" // Increased limit slightly to show more variety
    );
    if ($cmRes) {
        while ($row = mysqli_fetch_assoc($cmRes)) {
            $headerMembers[] = $row;
        }
    }
}

// Use session cached user photo for header avatar
if ($isLoggedIn) {
    $headerPhoto = get_user_avatar_url('');
}
?>
<style>
    body,
    body * {
        -webkit-user-select: none;
        user-select: none;
    }

    .ant-header {
        background: rgba(255, 255, 255, 0.9);
        backdrop-filter: blur(12px);
        height: 72px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid #f0f0f0;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    a {
        text-decoration: none;
        color: #000;
    }

    .ant-menu-item {
        color: rgba(0, 0, 0, 0.88);
        text-decoration: none;
        padding: 0 16px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        height: 72px;
        transition: 0.3s;
    }

    .ant-menu-item:hover {
        color: #1677ff;
    }

    .ant-btn-header {
        background-color: #1677ff;
        color: #fff !important;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: 500;
        text-decoration: none;
    }

    .user-pill-header {
        background: #fff;
        padding: 6px 16px;
        border-radius: 50px;
        border: 1px solid #f0f0f0;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .lang-btn {
        border: none;
        background: #f5f5f5;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 6px;
        transition: 0.2s;
    }

    .lang-btn:hover {
        background: #e6f4ff;
        color: #1677ff;
    }

    /* --- HOVER DROPDOWN LOGIC (Desktop Only) --- */
    @media (min-width: 992px) {
        .ant-header .dropdown:hover>.dropdown-menu {
            display: block;
            margin-top: 0;
            animation: fadeIn 0.2s ease-in-out;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<header class="ant-header">
    <div class="container d-flex align-items-center justify-content-between">
        <a href="index.php" class="fw-bold text-dark text-decoration-none fs-4 d-flex align-items-center">
            <i class="bi bi-bank2 text-primary me-2"></i><?php echo $t['title'] ?? 'Temple'; ?>
        </a>

        <div class="d-none d-lg-flex align-items-center">
            <div class="d-flex align-items-center">
                <a href="index.php" class="ant-menu-item"><?php echo $t['home']; ?></a>
                <a href="about.php" class="ant-menu-item"><?php echo $t['about']; ?></a>
                <a href="panchang.php" class="ant-menu-item"><?php echo $t['panchang']; ?></a>
                <a href="gallery.php" class="ant-menu-item"><?php echo $t['gallery']; ?></a>

                <div class="dropdown">
                    <a class="ant-menu-item dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <?php echo $t['committee']; ?>
                    </a>
                    <ul class="dropdown-menu border-0 shadow-lg p-3 mt-0"
                        style="border-radius: 12px; min-width: 240px;"> <?php if (!empty($headerMembers)): ?>
                            <?php foreach ($headerMembers as $member): ?>
                                <?php
                                $memberName = trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? ''));
                                $memberRole = ucfirst($member['role_name'] ?? 'Member'); // Get role
                        
                                if (!empty($member['photo'])) {
                                    $memberPhoto = 'uploads/users/' . basename($member['photo']);
                                } else {
                                    $memberPhoto = 'https://ui-avatars.com/api/?name=' . urlencode($memberName) . '&background=random';
                                }
                                ?>
                                <li class="mb-3">
                                    <div class="d-flex align-items-center gap-3 px-2">
                                        <img src="<?= $memberPhoto ?>" class="rounded-circle border shadow-sm"
                                            style="width:36px; height:36px; object-fit:cover;">

                                        <div class="d-flex flex-column" style="line-height: 1.2;">
                                            <span class="small fw-bold text-dark">
                                                <?= htmlspecialchars($memberName ?: ($t['member'] ?? 'Member')) ?>
                                            </span>
                                            <small class="text-muted text-uppercase"
                                                style="font-size: 10px; letter-spacing: 0.5px;">
                                                <?= htmlspecialchars($memberRole) ?>
                                            </small>
                                        </div>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="mb-2">
                                <div class="px-2 py-1 small text-muted">
                                    <?= htmlspecialchars($t['no_members_found'] ?? 'No members found') ?>
                                </div>
                            </li>
                        <?php endif; ?>

                        <li>
                            <hr class="dropdown-divider opacity-50">
                        </li>

                        <li>
                            <a class="dropdown-item fw-bold text-primary small text-center rounded-pill"
                                href="committee.php"><?php echo $t['view_all_members']; ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3">
            <div class="dropdown">
                <button class="lang-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-translate me-1"></i>
                    <?= ($currentLang == 'mr') ? $t['lang_marathi'] : $t['lang_english']; ?>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                    <li>
                        <a class="dropdown-item small fw-medium <?= ($currentLang == 'en') ? 'active' : '' ?>"
                            href="?lang=en" aria-current="<?= ($currentLang == 'en') ? 'true' : 'false' ?>">
                            <?php echo $t['lang_english']; ?>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item small fw-medium <?= ($currentLang == 'mr') ? 'active' : '' ?>"
                            href="?lang=mr" aria-current="<?= ($currentLang == 'mr') ? 'true' : 'false' ?>">
                            <?php echo $t['lang_marathi_full']; ?>
                        </a>
                    </li>
                </ul>
            </div>

            <?php if ($isLoggedIn): ?>
                <a href="auth/redirect.php">
                    <div class="user-pill-header shadow-sm">
                        <img src="<?= $headerPhoto ?>" class="rounded-circle" width="28" height="28"
                            style="object-fit: cover;">
                        <span class="small fw-bold d-none d-md-inline">Dashboard</span>
                    </div>
                </a>
            <?php else: ?>
                <a href="auth/login.php" class="ant-btn-header shadow-sm"><?php echo $t['login'] ?? 'Login'; ?></a>
            <?php endif; ?>
        </div>
    </div>
</header>