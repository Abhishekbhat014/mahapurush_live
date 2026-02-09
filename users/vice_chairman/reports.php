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
$currentPage = 'reports.php';
$currLang = $_SESSION['lang'] ?? 'en';

$loggedInUserPhoto = get_user_avatar_url('../../');

// --- REPORT LOGIC ---
$fromDate = $_GET['from_date'] ?? date('Y-m-01'); // Default: Start of current month
$toDate = $_GET['to_date'] ?? date('Y-m-d');     // Default: Today

// 1. Overview Totals
$totalAmount = 0;
$totalCount = 0;
$modeBreakdown = [];

if ($con) {
    // Total Amount & Count
    $summaryQuery = "
        SELECT 
            COUNT(*) as cnt, 
            COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'success' 
          AND DATE(created_at) BETWEEN '$fromDate' AND '$toDate'
    ";
    $summaryRes = mysqli_query($con, $summaryQuery);
    $summary = mysqli_fetch_assoc($summaryRes);
    $totalAmount = $summary['total'];
    $totalCount = $summary['cnt'];

    // Breakdown by Mode
    $modeQuery = "
        SELECT 
            payment_method, 
            COUNT(*) as cnt, 
            COALESCE(SUM(amount), 0) as total 
        FROM payments 
        WHERE status = 'success' 
          AND DATE(created_at) BETWEEN '$fromDate' AND '$toDate'
        GROUP BY payment_method
    ";
    $modeRes = mysqli_query($con, $modeQuery);
    while ($row = mysqli_fetch_assoc($modeRes)) {
        $modeBreakdown[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['temple_activity_reports']; ?> - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* GLOBAL THEME */
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

        /* HEADER STYLES */
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

        /* HEADER DROPDOWN & BUTTONS */
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

        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }

            .dropdown:hover>.dropdown-menu {
                animation: fadeIn 0.2s ease-in-out;
            }
        }

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

        /* DASHBOARD CONTENT */
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
        }

        .ant-card:hover {
            transform: translateY(-4px);
        }

        .ant-card-body {
            padding: 24px;
        }

        /* SUMMARY BOX */
        .summary-box {
            border: 1px solid var(--ant-border-color);
            border-radius: 12px;
            padding: 24px;
            background: #fafafa;
            text-align: center;
            height: 100%;
        }

        .summary-box h3 {
            margin-bottom: 0;
            color: #333;
        }

        .summary-box h6 {
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        /* BUTTONS */
        .btn-primary {
            background: var(--ant-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 6px;
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
                    <h2 class="fw-bold mb-1"><?php echo $t['temple_activity_reports']; ?></h2>
                    <p class="text-secondary mb-0">
                        <?php echo $t['temple_activity_reports_desc']; ?>
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card p-4">

                        <form method="GET" class="row g-3 mb-5 align-items-end">
                            <div class="col-md-4">
                                <label
                                    class="form-label small text-muted text-uppercase fw-bold"><?php echo $t['from_date']; ?></label>
                                <input type="date" name="from_date" class="form-control"
                                    value="<?= htmlspecialchars($fromDate) ?>">
                            </div>
                            <div class="col-md-4">
                                <label
                                    class="form-label small text-muted text-uppercase fw-bold"><?php echo $t['to_date']; ?></label>
                                <input type="date" name="to_date" class="form-control"
                                    value="<?= htmlspecialchars($toDate) ?>">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-bar-chart-line me-2"></i> <?php echo $t['generate_report']; ?>
                                </button>
                            </div>
                        </form>

                        <div class="row g-4 mb-4">
                            <div class="col-md-4">
                                <div class="summary-box">
                                    <h6 class="text-primary"><?php echo $t['total_temple_collection']; ?></h6>
                                    <h3 class="fw-bold text-dark">₹<?= number_format($totalAmount, 2) ?></h3>
                                    <small class="text-muted"><?php echo $t['selected_period']; ?></small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="summary-box">
                                    <h6 class="text-success"><?php echo $t['total_transactions']; ?></h6>
                                    <h3 class="fw-bold text-dark"><?= number_format($totalCount) ?></h3>
                                    <small class="text-muted"><?php echo $t['pooja_donation_receipts']; ?></small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="summary-box">
                                    <h6 class="text-info"><?php echo $t['average_donation']; ?></h6>
                                    <h3 class="fw-bold text-dark">
                                        ₹<?= $totalCount > 0 ? number_format($totalAmount / $totalCount, 2) : '0.00' ?>
                                    </h3>
                                    <small class="text-muted"><?php echo $t['per_transaction']; ?></small>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($modeBreakdown)): ?>
                            <h5 class="fw-bold mb-3"><?php echo $t['breakdown_by_payment_mode']; ?></h5>
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th><?php echo $t['payment_mode']; ?></th>
                                            <th class="text-center"><?php echo $t['transactions']; ?></th>
                                            <th class="text-end"><?php echo $t['total_amount']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($modeBreakdown as $mode): ?>
                                            <tr>
                                                <td class="text-capitalize fw-medium">
                                                    <?= htmlspecialchars($mode['payment_method']) ?>
                                                </td>
                                                <td class="text-center"><?= $mode['cnt'] ?></td>
                                                <td class="text-end fw-bold">₹<?= number_format($mode['total'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr class="table-secondary fw-bold">
                                            <td><?php echo $t['total'] ?? 'Total'; ?></td>
                                            <td class="text-center"><?= $totalCount ?></td>
                                            <td class="text-end">₹<?= number_format($totalAmount, 2) ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

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