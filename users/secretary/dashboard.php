<?php
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'dashboard.php';

// --- Header Identity Logic ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

/* KPI QUERIES */
$pendingContributions = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM contributions WHERE status='pending'"))[0];
$pendingPoojas = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pooja WHERE status='pending'"))[0];
$todayAmount = mysqli_fetch_row(mysqli_query($con, "SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='success' AND DATE(created_at)=CURDATE()"))[0];
$todayReceipts = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM receipt WHERE DATE(issued_on)=CURDATE()"))[0];
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['secretary_dashboard']; ?> -
        <?= $t['title'] ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
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

        /* Prevent Text Selection globally */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
            -webkit-user-select: none;
            -ms-user-select: none;
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

        /* KPI Card DNA */
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
    </style>
</head>

<body>

    <header class="ant-header shadow-sm">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i
                        class="bi bi-list"></i></button>
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
                    <i class="bi bi-flower1 text-warning me-2"></i>
                    <?= $t['title'] ?>
                </a>
            </div>
            <div class="user-pill">
                <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
                <div class="vr mx-2 text-muted opacity-25"></div>
                <a href="../../auth/logout.php" class="text-danger"><i class="bi bi-power"></i></a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['secretary_overview']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['secretary_overview_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row g-4">

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-orange-soft shadow-sm"><i class="bi bi-box-seam"></i></div>
                                    <span class="kpi-label"><?php echo $t['pending_items']; ?></span>
                                    <h3 class="kpi-value">
                                        <?= $pendingContributions ?>
                                    </h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['material_contributions']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-blue-soft shadow-sm"><i class="bi bi-calendar-event"></i>
                                    </div>
                                    <span class="kpi-label"><?php echo $t['pending_poojas']; ?></span>
                                    <h3 class="kpi-value">
                                        <?= $pendingPoojas ?>
                                    </h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['bookings_awaiting_review']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-green-soft shadow-sm"><i class="bi bi-currency-rupee"></i>
                                    </div>
                                    <span class="kpi-label"><?php echo $t['todays_income']; ?></span>
                                    <h3 class="kpi-value">₹
                                        <?= number_format($todayAmount, 0) ?>
                                    </h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['cash_online_total']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-purple-soft shadow-sm"><i class="bi bi-receipt"></i></div>
                                    <span class="kpi-label"><?php echo $t['receipts_issued']; ?></span>
                                    <h3 class="kpi-value">
                                        <?= $todayReceipts ?>
                                    </h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['generated_today']; ?></p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="ant-card" style="border-bottom: 1px solid var(--ant-border-color);">
                                <div class="ant-card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo $t['administrative_actions']; ?></h6>
                                        <p class="text-muted small mb-0"><?php echo $t['jump_to_pending_queues']; ?></p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="contributions_review.php"
                                            class="btn btn-primary btn-sm rounded-pill px-3"><?php echo $t['review_items']; ?></a>
                                        <a href="pooja_approvals.php"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3"><?php echo $t['approve_poojas']; ?></a>
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
</body>

</html>
