<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');

$uid = (int) $_SESSION['user_id'];
$currentPage = 'dashboard.php';
$currLang = $_SESSION['lang'] ?? 'en';
$loggedInUserPhoto = get_user_avatar_url('../../');

/* --- KPI QUERIES --- */
// Using fetch_row for simple count queries is efficient
$pendingContributions = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM contributions WHERE status='pending'"))[0] ?? 0;
$pendingPoojas = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pooja WHERE status='pending'"))[0] ?? 0;

$todayPoojaRequests = mysqli_fetch_row(mysqli_query(
    $con,
    "SELECT COUNT(*) FROM pooja WHERE DATE(created_at)=CURDATE()"
))[0] ?? 0;

$todayDonationRequests = mysqli_fetch_row(mysqli_query(
    $con,
    "SELECT COUNT(*) FROM contributions WHERE DATE(created_at)=CURDATE()"
))[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secretary Dashboard - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES (Blue) --- */
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f0f2f5;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        /* Allow inputs to be selectable */
        input,
        textarea,
        select {
            -webkit-user-select: text;
            user-select: text;
        }

        /* --- HEADER STYLES --- */
        .ant-header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            height: 64px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--ant-border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .user-pill {
            background: #fff;
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid var(--ant-border-color);
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

        /* --- HOVER DROPDOWN LOGIC --- */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }

            .dropdown .dropdown-menu {
                display: none;
            }

            .dropdown:hover>.dropdown-menu {
                display: block;
                animation: fadeIn 0.2s ease-in-out;
            }
        }

        /* Active Dropdown Item */
        .dropdown-item.active,
        .dropdown-item:active {
            background-color: var(--ant-primary) !important;
            color: #fff !important;
            font-weight: 600;
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

        /* --- PAGE CONTENT --- */
        .dashboard-hero {
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
            padding: 40px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 32px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            transition: all 0.3s;
            height: 100%;
            border-bottom: 3px solid transparent;
        }

        .ant-card:hover {
            transform: translateY(-4px);
            border-bottom-color: var(--ant-primary);
        }

        .ant-card-body {
            padding: 24px;
        }

        /* KPI DNA */
        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        .kpi-value {
            font-size: 28px;
            font-weight: 800;
            color: var(--ant-text);
            margin-bottom: 0;
        }

        .kpi-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        /* Colors */
        .bg-blue-soft {
            background: #e6f4ff;
            color: #1677ff;
        }

        .bg-orange-soft {
            background: #fff7e6;
            color: #fa8c16;
        }

        .bg-green-soft {
            background: #f6ffed;
            color: #52c41a;
        }

        .bg-purple-soft {
            background: #f9f0ff;
            color: #722ed1;
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
        }
    </style>
</head>

<body>

    <header class="ant-header shadow-sm">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i>
                </button>
            </div>
            <div class="d-flex align-items-center gap-3">

                <div class="dropdown">
                    <button class="lang-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-translate me-1"></i>
                        <?= ($currLang == 'mr') ? $t['lang_marathi'] : $t['lang_english']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>"
                                href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr">Marathi</a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                            <?php foreach ($availableRoles as $role): ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role === $primaryRole) ? 'active' : '' ?>">
                                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $role))) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span
                        class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="fw-bold mb-1"><?php echo $t['secretary_dashboard'] ?? 'Secretary Dashboard'; ?>
                            </h2>
                            <p class="text-secondary mb-0">Overview of pending requests and daily activities.</p>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-5">

                    <div class="row g-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-orange-soft shadow-sm"><i class="bi bi-box-seam"></i></div>
                                    <span class="kpi-label"><?php echo $t['pending_items'] ?? 'Pending Items'; ?></span>
                                    <h3 class="kpi-value"><?= $pendingContributions ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['material_contributions'] ?? 'Material Requests'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-blue-soft shadow-sm"><i class="bi bi-calendar-check"></i>
                                    </div>
                                    <span
                                        class="kpi-label"><?php echo $t['pending_poojas'] ?? 'Pending Poojas'; ?></span>
                                    <h3 class="kpi-value"><?= $pendingPoojas ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['bookings_awaiting_review'] ?? 'Awaiting Review'; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-green-soft shadow-sm"><i class="bi bi-calendar-plus"></i>
                                    </div>
                                    <span class="kpi-label">Today's Pooja Requests</span>
                                    <h3 class="kpi-value"><?= $todayPoojaRequests ?></h3>
                                    <p class="small text-muted mb-0 mt-2">New Bookings Today</p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-purple-soft shadow-sm"><i class="bi bi-gift"></i></div>
                                    <span class="kpi-label">Today's Donation Requests</span>
                                    <h3 class="kpi-value"><?= $todayDonationRequests ?></h3>
                                    <p class="small text-muted mb-0 mt-2">New Contributions Today</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="ant-card" style="border-bottom: 1px solid var(--ant-border-color);">
                                <div
                                    class="ant-card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <?php echo $t['administrative_actions'] ?? 'Administrative Actions'; ?></h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo $t['jump_to_pending_queues'] ?? 'Quickly access pending items.'; ?>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="contributions_review.php"
                                            class="btn btn-primary btn-sm rounded-pill px-4 fw-bold">
                                            <?php echo $t['review_items'] ?? 'Review Contributions'; ?>
                                        </a>
                                        <a href="pooja_bookings.php"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold">
                                            <?php echo $t['approve_poojas'] ?? 'Review Poojas'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
</body>

</html>