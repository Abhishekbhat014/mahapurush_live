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

/* ------------------ DEVOTEE DASHBOARD DATA ------------------ */

$uid = (int)($_SESSION['user_id'] ?? 0);

// 1. Total Donations by the user
$totalDonationsStmt = $con->prepare("
    SELECT IFNULL(SUM(amount),0) as total 
    FROM receipt 
    WHERE user_id = ? AND purpose = 'donation'
");
$totalDonationsStmt->bind_param("i", $uid);
$totalDonationsStmt->execute();
$myTotalDonations = $totalDonationsStmt->get_result()->fetch_assoc()['total'] ?? 0;

// 2. Total Pooja Bookings by the user
$poojaCountStmt = $con->prepare("
    SELECT COUNT(*) as cnt 
    FROM pooja 
    WHERE user_id = ?
");
$poojaCountStmt->bind_param("i", $uid);
$poojaCountStmt->execute();
$myPoojaBookings = $poojaCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0;

// 3. Total Receipts generated for the user
$receiptCountStmt = $con->prepare("
    SELECT COUNT(*) as cnt 
    FROM receipt
    WHERE user_id = ?
");
$receiptCountStmt->bind_param("i", $uid);
$receiptCountStmt->execute();
$myTotalReceipts = $receiptCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0;
?>


<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['devotee_dashboard']; ?> - <?= $t['title'] ?? 'Temple' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="customer-responsive.css">
    <style>
        :root {
            --ant-primary: #1677ff;
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
            user-select: none;
        }

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

        .ant-sidebar {
            background: #fff;
            border-right: 1px solid var(--ant-border-color);
            height: calc(100vh - 64px);
            position: sticky;
            top: 64px;
            padding: 20px 0;
        }

        .nav-link-custom {
            padding: 12px 24px;
            color: var(--ant-text);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s;
            text-decoration: none;
            font-size: 14px;
        }

        .nav-link-custom:hover,
        .nav-link-custom.active {
            color: var(--ant-primary);
            background: #e6f4ff;
            border-right: 3px solid var(--ant-primary);
        }

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
        }

        .ant-card-body {
            padding: 0;
        }

        /* Dash UI specific specific button colors */
        .btn-outline-primary {
            border-color: #1677ff;
            color: #1677ff;
        }

        .btn-outline-primary:hover {
            background-color: #1677ff;
            color: #fff;
        }
        
        .btn-outline-success {
            border-color: #52c41a;
            color: #52c41a;
        }

        .btn-outline-success:hover {
            background-color: #52c41a;
            color: #fff;
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

                <div class="user-pill">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['devotee_dashboard']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['devotee_dashboard_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">

                    <div class="row g-4">

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-cash-stack fs-1 text-success mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">
                                    <?php echo $t['my_total_donations'] ?? 'My Total Donations'; ?>
                                </h6>
                                <h3 class="fw-bold mb-0">₹ <?= number_format($myTotalDonations, 2) ?></h3>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-flower1 fs-1 text-danger mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">
                                    <?php echo $t['my_pooja_bookings'] ?? 'My Pooja Bookings'; ?>
                                </h6>
                                <h3 class="fw-bold mb-0"><?= $myPoojaBookings ?></h3>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="ant-card p-4 text-center">
                                <i class="bi bi-receipt-cutoff fs-1 text-primary mb-3 d-block"></i>
                                <h6 class="text-muted text-uppercase small fw-bold">
                                    <?php echo $t['my_receipts'] ?? 'My Receipts'; ?>
                                </h6>
                                <h3 class="fw-bold mb-0"><?= $myTotalReceipts ?></h3>
                            </div>
                        </div>

                    </div>

                    <div class="ant-card p-4 mt-4">
                        <h5 class="fw-bold mb-3"><?php echo $t['quick_actions']; ?></h5>
                        <div class="d-flex gap-3 flex-wrap">
                            <a href="pooja_book.php" class="btn btn-outline-success px-4 py-2 rounded-pill">
                                <i class="bi bi-flower1 me-2"></i> <?php echo $t['book_pooja'] ?? 'Book Pooja'; ?>
                            </a>
                            <a href="donate.php" class="btn btn-outline-primary px-4 py-2 rounded-pill">
                                <i class="bi bi-heart-fill me-2"></i> <?php echo $t['donate_btn'] ?? 'Donate Now'; ?>
                            </a>
                            <a href="my_receipts.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill">
                                <i class="bi bi-receipt me-2"></i> <?php echo $t['my_receipts'] ?? 'My Receipts'; ?>
                            </a>
                            <a href="my_requests.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">
                                <i class="bi bi-journal-text me-2"></i> <?php echo $t['my_requests'] ?? 'My Requests'; ?>
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