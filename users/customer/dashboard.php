<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';
require __DIR__ . '/../../config/db.php'; // DB conection

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'dashboard.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

/* ------------------ TREASURER DASHBOARD DATA ------------------ */

$today = date('Y-m-d');

// Use prepared statements ideally, but keeping your logic for now
$totalCollections = $con->query("
    SELECT IFNULL(SUM(amount),0) as total 
    FROM payments 
    WHERE DATE(created_at) = '$today' AND status='success'
")->fetch_assoc()['total'] ?? 0;

$totalDonations = $con->query("
    SELECT COUNT(*) as cnt 
    FROM payments 
    WHERE DATE(created_at) = '$today' AND status='success'
")->fetch_assoc()['cnt'] ?? 0;

$totalReceipts = $con->query("
    SELECT COUNT(*) as cnt 
    FROM receipt
    WHERE DATE(created_at) = '$today'
")->fetch_assoc()['cnt'] ?? 0;
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treasurer Dashboard - <?= $t['title'] ?? 'Temple' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        /* TREASURER THEME OVERRIDES (Green Sidebar) */
        :root {
            --ant-primary: #1677ff; 
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --tr-active-text: #52c41a; /* Green for active sidebar items */
            --tr-active-bg: #f6ffed;   /* Light Green BG for sidebar */
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f0f2f5;
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        /* Allow inputs to be selectable */
        input, textarea, select {
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
        .lang-btn:hover { background: #e6f4ff; color: #1677ff; }

        /* --- HOVER DROPDOWN LOGIC --- */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }
            .dropdown .dropdown-menu {
                display: none;
            }
            .dropdown:hover > .dropdown-menu {
                display: block;
                animation: fadeIn 0.2s ease-in-out;
            }
        }

        /* --- ACTIVE STATE FOR DROPDOWNS (RESTORED TO DARK BLUE) --- */
        .dropdown-item.active, .dropdown-item:active {
            background-color: var(--ant-primary) !important; /* Solid Dark Blue */
            color: #ffffff !important;                      /* White Text */
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- DASHBOARD ELEMENTS --- */
        .dashboard-hero {
            background: radial-gradient(circle at top right, #f6ffed 0%, #ffffff 80%); /* Green-ish tint */
            padding: 40px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 32px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: 12px;
            box-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease;
            height: 100%;
        }

        .ant-card:hover {
            transform: translateY(-4px);
        }

        .btn-outline-primary {
            border-color: #1677ff;
            color: #1677ff;
        }
        .btn-outline-primary:hover {
            background-color: #1677ff;
            color: #fff;
        }
        
        /* Treasurer specific button colors */
        .btn-outline-success {
            border-color: #52c41a;
            color: #52c41a;
        }
        .btn-outline-success:hover {
            background-color: #52c41a;
            color: #fff;
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
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>" href="?lang=mr">Marathi</a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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

                <div class="user-pill">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28" style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1">Treasurer Dashboard</h2>
                    <p class="text-secondary mb-0">Today’s financial summary and quick access to finance operations.</p>
                </div>

                <div class="px-4 pb-5">

                    <div class="row g-4">

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-cash-stack fs-1 text-success mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">Today's Collections</h6>
                                <h3 class="fw-bold mb-0">₹ <?= number_format($totalCollections, 2) ?></h3>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-heart-fill fs-1 text-danger mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">Donations Received</h6>
                                <h3 class="fw-bold mb-0"><?= $totalDonations ?></h3>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-receipt-cutoff fs-1 text-primary mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">Receipts Generated</h6>
                                <h3 class="fw-bold mb-0"><?= $totalReceipts ?></h3>
                            </div>
                        </div>

                    </div>

                    <div class="ant-card p-4 mt-4">
                        <h5 class="fw-bold mb-3">Quick Actions</h5>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="donations.php" class="btn btn-outline-success px-4 py-2 rounded-pill">
                                <i class="bi bi-heart-fill me-2"></i> Record Donation
                            </a>
                            <a href="receipts.php" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                                <i class="bi bi-receipt-cutoff me-2"></i> Generate Receipt
                            </a>
                            <a href="donation_records.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                <i class="bi bi-journal-text me-2"></i> View Records
                            </a>
                            <a href="reports.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                                <i class="bi bi-bar-chart me-2"></i> Reports
                            </a>
                        </div>
                    </div>

                </div>
            </main>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>