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

$pendingContributions = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM contributions WHERE status='pending'"))[0] ?? 0;
$pendingPoojas = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pooja WHERE status='pending'"))[0] ?? 0;
$todayAmount = mysqli_fetch_row(mysqli_query($con, "SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='success' AND DATE(created_at)=CURDATE()"))[0] ?? 0;
$todayReceipts = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM receipt WHERE DATE(issued_on)=CURDATE()"))[0] ?? 0;

// Chart data
$monthlyDonations = [];
$monthlyAmounts = [];
$monthlyRes = mysqli_query(
    $con,
    "SELECT DATE_FORMAT(created_at,'%b %Y') AS month_label, IFNULL(SUM(amount),0) AS total_amount
     FROM payments
     WHERE status='success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY DATE_FORMAT(created_at,'%Y-%m')
     ORDER BY DATE_FORMAT(created_at,'%Y-%m') ASC"
);
if ($monthlyRes) {
    while ($row = mysqli_fetch_assoc($monthlyRes)) {
        $monthlyDonations[] = $row['month_label'];
        $monthlyAmounts[] = (float) $row['total_amount'];
    }
}

$receiptLabels = [];
$receiptCounts = [];
$receiptRes = mysqli_query(
    $con,
    "SELECT purpose, COUNT(*) AS cnt
     FROM receipt
     GROUP BY purpose
     ORDER BY cnt DESC"
);
if ($receiptRes) {
    while ($row = mysqli_fetch_assoc($receiptRes)) {
        $receiptLabels[] = ucfirst($row['purpose']);
        $receiptCounts[] = (int) $row['cnt'];
    }
}

$poojaLabels = [];
$poojaCounts = [];
$poojaRes = mysqli_query(
    $con,
    "SELECT status, COUNT(*) AS cnt
     FROM pooja
     GROUP BY status"
);
if ($poojaRes) {
    while ($row = mysqli_fetch_assoc($poojaRes)) {
        $poojaLabels[] = ucfirst($row['status']);
        $poojaCounts[] = (int) $row['cnt'];
    }
}

$methodLabels = [];
$methodTotals = [];
$methodRes = mysqli_query(
    $con,
    "SELECT payment_method, IFNULL(SUM(amount),0) AS total_amount
     FROM payments
     WHERE status='success'
     GROUP BY payment_method
     ORDER BY total_amount DESC"
);
if ($methodRes) {
    while ($row = mysqli_fetch_assoc($methodRes)) {
        $methodLabels[] = strtoupper($row['payment_method']);
        $methodTotals[] = (float) $row['total_amount'];
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['chairman_dashboard'] ?? 'Chairman Dashboard'; ?> - <?= $t['title'] ?></title>
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

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
            -webkit-user-select: none;
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

        .chart-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            padding: 20px;
            height: 100%;
            overflow: hidden;
        }

        .chart-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }

        .chart-card canvas {
            width: 100% !important;
            height: 220px !important;
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
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>"
                                href="?lang=en" aria-current="<?= ($currLang == 'en') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_english']; ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr" aria-current="<?= ($currLang == 'mr') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_marathi_full']; ?>
                            </a>
                        </li>
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
                            <?php foreach ($availableRoles as $role):
                                $roleLabel = ucwords(str_replace('_', ' ', $role));
                                ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role === $primaryRole) ? 'active' : '' ?>">
                                            <?= htmlspecialchars($roleLabel) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
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
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1">Dashboard</h2>
                    <p class="text-secondary mb-0">
                        Role-specific overview of today’s poojas, donations, receipts, events, and pending actions.
                    </p>
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
                                        <?php echo $t['material_contributions'] ?? 'Material Contributions'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-blue-soft shadow-sm"><i class="bi bi-calendar-event"></i>
                                    </div>
                                    <span
                                        class="kpi-label"><?php echo $t['pending_poojas'] ?? 'Pending Poojas'; ?></span>
                                    <h3 class="kpi-value"><?= $pendingPoojas ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['bookings_awaiting_review'] ?? 'Bookings Awaiting Review'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-green-soft shadow-sm"><i class="bi bi-currency-rupee"></i>
                                    </div>
                                    <span class="kpi-label"><?php echo $t['todays_income'] ?? 'Todays Income'; ?></span>
                                    <h3 class="kpi-value">&#8377;<?= number_format($todayAmount, 0) ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['cash_online_total'] ?? 'Cash + Online Total'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-purple-soft shadow-sm"><i class="bi bi-receipt"></i></div>
                                    <span
                                        class="kpi-label"><?php echo $t['receipts_issued'] ?? 'Receipts Issued'; ?></span>
                                    <h3 class="kpi-value"><?= $todayReceipts ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['generated_today'] ?? 'Generated Today'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title">
                                    <?php echo $t['donations_trend'] ?? 'Donations Trend (Last 6 Months)'; ?>
                                </div>
                                <canvas id="donationsTrend" height="160"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title">
                                    <?php echo $t['receipts_by_purpose'] ?? 'Receipts By Purpose'; ?>
                                </div>
                                <canvas id="receiptPurpose" height="160"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title">
                                    <?php echo $t['pooja_status_overview'] ?? 'Pooja Status Overview'; ?>
                                </div>
                                <canvas id="poojaStatus" height="160"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title"><?php echo $t['payment_methods'] ?? 'Payment Methods'; ?></div>
                                <canvas id="paymentMethods" height="160"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="ant-card" style="border-bottom: 1px solid var(--ant-border-color);">
                                <div class="ant-card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <?php echo $t['chairman_actions'] ?? 'Chairman Actions'; ?>
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo $t['chairman_actions_subtitle'] ?? 'Jump to approvals and assignments.'; ?>
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="pooja_requests.php" class="btn btn-primary btn-sm rounded-pill px-3">
                                            <?php echo $t['review_poojas'] ?? 'Review Poojas'; ?>
                                        </a>
                                        <a href="committee.php"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <?php echo $t['manage_committee'] ?? 'Manage Committee'; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const donationsLabels = <?= json_encode($monthlyDonations) ?>;
        const donationsData = <?= json_encode($monthlyAmounts) ?>;
        const receiptLabels = <?= json_encode($receiptLabels) ?>;
        const receiptCounts = <?= json_encode($receiptCounts) ?>;
        const poojaLabels = <?= json_encode($poojaLabels) ?>;
        const poojaCounts = <?= json_encode($poojaCounts) ?>;
        const methodLabels = <?= json_encode($methodLabels) ?>;
        const methodTotals = <?= json_encode($methodTotals) ?>;

        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12 } }
            },
            scales: {
                x: { grid: { display: false } },
                y: { grid: { color: '#f0f0f0' } }
            }
        };

        const ctxTrend = document.getElementById('donationsTrend');
        if (ctxTrend) {
            new Chart(ctxTrend, {
                type: 'line',
                data: {
                    labels: donationsLabels,
                    datasets: [{
                        label: 'Amount',
                        data: donationsData,
                        borderColor: '#1677ff',
                        backgroundColor: 'rgba(22,119,255,0.15)',
                        fill: true,
                        tension: 0.35,
                        pointRadius: 3
                    }]
                },
                options: commonOptions
            });
        }

        const ctxPurpose = document.getElementById('receiptPurpose');
        if (ctxPurpose) {
            new Chart(ctxPurpose, {
                type: 'doughnut',
                data: {
                    labels: receiptLabels,
                    datasets: [{
                        data: receiptCounts,
                        backgroundColor: ['#1677ff', '#52c41a', '#faad14', '#722ed1', '#ff4d4f']
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }

        const ctxPooja = document.getElementById('poojaStatus');
        if (ctxPooja) {
            new Chart(ctxPooja, {
                type: 'bar',
                data: {
                    labels: poojaLabels,
                    datasets: [{
                        label: 'Count',
                        data: poojaCounts,
                        backgroundColor: '#4096ff'
                    }]
                },
                options: commonOptions
            });
        }

        const ctxMethods = document.getElementById('paymentMethods');
        if (ctxMethods) {
            new Chart(ctxMethods, {
                type: 'bar',
                data: {
                    labels: methodLabels,
                    datasets: [{
                        label: 'Amount',
                        data: methodTotals,
                        backgroundColor: '#52c41a'
                    }]
                },
                options: commonOptions
            });
        }
    </script>
</body>

</html>