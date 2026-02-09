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

// --- KPI Data Fetching ---
$pendingPoojas = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pooja WHERE status='pending'"))[0] ?? 0;
$todayPoojas = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM pooja WHERE DATE(created_at)=CURDATE()"))[0] ?? 0;
$todayEvents = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(*) FROM events WHERE DATE(conduct_on)=CURDATE()"))[0] ?? 0;
$totalCommittee = mysqli_fetch_row(mysqli_query($con, "SELECT COUNT(DISTINCT user_id) 
FROM user_roles 
WHERE role_id <> 1;
"))[0] ?? 0;
$todayAmount = mysqli_fetch_row(mysqli_query($con, "SELECT IFNULL(SUM(amount),0) FROM payments WHERE status='success' AND DATE(created_at)=CURDATE()"))[0] ?? 0;

// --- Chart Data ---
$monthlyDonations = [];
$monthlyAmounts = [];
$monthlyRes = mysqli_query($con, "SELECT DATE_FORMAT(created_at,'%b %Y') AS month_label, IFNULL(SUM(amount),0) AS total_amount FROM payments WHERE status='success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY DATE_FORMAT(created_at,'%Y-%m') ORDER BY DATE_FORMAT(created_at,'%Y-%m') ASC");
if ($monthlyRes) {
    while ($row = mysqli_fetch_assoc($monthlyRes)) {
        $monthlyDonations[] = $row['month_label'];
        $monthlyAmounts[] = (float) $row['total_amount'];
    }
}

$receiptLabels = [];
$receiptCounts = [];
$receiptRes = mysqli_query($con, "SELECT purpose, COUNT(*) AS cnt FROM receipt GROUP BY purpose ORDER BY cnt DESC");
if ($receiptRes) {
    while ($row = mysqli_fetch_assoc($receiptRes)) {
        $receiptLabels[] = ucfirst($row['purpose']);
        $receiptCounts[] = (int) $row['cnt'];
    }
}

$poojaLabels = [];
$poojaCounts = [];
$poojaRes = mysqli_query($con, "SELECT status, COUNT(*) AS cnt FROM pooja GROUP BY status");
if ($poojaRes) {
    while ($row = mysqli_fetch_assoc($poojaRes)) {
        $poojaLabels[] = ucfirst($row['status']);
        $poojaCounts[] = (int) $row['cnt'];
    }
}

$methodLabels = [];
$methodTotals = [];
$methodRes = mysqli_query($con, "SELECT payment_method, IFNULL(SUM(amount),0) AS total_amount FROM payments WHERE status='success' GROUP BY payment_method ORDER BY total_amount DESC");
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
    <title><?php echo $t['vice_chairman_dashboard']; ?> - <?= $t['title'] ?></title>
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
            border-bottom: 3px solid transparent;
        }

        .ant-card:hover {
            transform: translateY(-4px);
            border-bottom-color: var(--ant-primary);
        }

        .ant-card-body {
            padding: 24px;
        }

        /* KPI STYLES */
        .kpi-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            display: block;
            margin-bottom: 8px;
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

        /* CHART STYLES */
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
            margin-bottom: 12px;
        }

        .chart-card canvas {
            width: 100% !important;
            height: 220px !important;
        }

        /* BUTTONS */
        .btn-primary {
            background: var(--ant-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
        }

        .btn-outline-primary {
            border-color: var(--ant-primary);
            color: var(--ant-primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--ant-primary);
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
                    <h2 class="fw-bold mb-1"><?php echo $t['vice_chairman_dashboard']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['vice_chairman_dashboard_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">

                    <div class="row g-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-orange-soft shadow-sm"><i class="bi bi-box-seam"></i></div>
                                    <span class="kpi-label"><?php echo $t['pending_approvals']; ?></span>
                                    <h3 class="kpi-value"><?= $pendingPoojas ?></h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['pooja_requests_subtitle']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-blue-soft shadow-sm"><i class="bi bi-calendar-event"></i>
                                    </div>
                                    <span class="kpi-label"><?php echo $t['todays_poojas']; ?></span>
                                    <h3 class="kpi-value"><?= $todayPoojas ?></h3>
                                    <p class="small text-muted mb-0 mt-2">
                                        <?php echo $t['scheduled_today'] ?? 'Scheduled Today'; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-green-soft shadow-sm"><i class="bi bi-currency-rupee"></i>
                                    </div>
                                    <span class="kpi-label"><?php echo $t['todays_income']; ?></span>
                                    <h3 class="kpi-value">₹<?= number_format($todayAmount, 0) ?></h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['total_collections']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="ant-card">
                                <div class="ant-card-body">
                                    <div class="kpi-icon bg-purple-soft shadow-sm"><i class="bi bi-people"></i></div>
                                    <span class="kpi-label"><?php echo $t['committee']; ?></span>
                                    <h3 class="kpi-value"><?= $totalCommittee ?></h3>
                                    <p class="small text-muted mb-0 mt-2"><?php echo $t['active_members']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title"><?php echo $t['donations_trend']; ?></div>

                                <canvas id="donationsTrend"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title"><?php echo $t['receipts_by_purpose']; ?></div>

                                <canvas id="receiptPurpose"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4 mt-2">
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title"><?php echo $t['pooja_status']; ?></div>

                                <canvas id="poojaStatus"></canvas>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="chart-card">
                                <div class="chart-title"><?php echo $t['payment_methods']; ?></div>

                                <canvas id="paymentMethods"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="ant-card">
                                <div class="ant-card-body d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?php echo $t['quick_actions']; ?></h6>
                                        <p class="text-muted small mb-0"><?php echo $t['quick_actions_subtitle']; ?></p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <a href="pooja_approvals.php"
                                            class="btn btn-primary btn-sm rounded-pill px-3"><?php echo $t['approve_requests']; ?></a>
                                        <a href="events.php"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3"><?php echo $t['view_events']; ?></a>
                                        <a href="committee.php"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3"><?php echo $t['committee']; ?></a>
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
        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Chart Configuration
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 12
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        color: '#f0f0f0'
                    }
                }
            }
        };

        // Donations Trend
        new Chart(document.getElementById('donationsTrend'), {
            type: 'line',
            data: {
                labels: <?= json_encode($monthlyDonations) ?>,
                datasets: [{
                    label: 'Amount',
                    data: <?= json_encode($monthlyAmounts) ?>,
                    borderColor: '#1677ff',
                    backgroundColor: 'rgba(22,119,255,0.15)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3
                }]
            },
            options: commonOptions
        });

        // Receipts Purpose
        new Chart(document.getElementById('receiptPurpose'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($receiptLabels) ?>,
                datasets: [{
                    data: <?= json_encode($receiptCounts) ?>,
                    backgroundColor: ['#1677ff', '#52c41a', '#faad14', '#722ed1', '#ff4d4f']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Pooja Status
        new Chart(document.getElementById('poojaStatus'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($poojaLabels) ?>,
                datasets: [{
                    label: 'Count',
                    data: <?= json_encode($poojaCounts) ?>,
                    backgroundColor: '#4096ff'
                }]
            },
            options: commonOptions
        });

        // Payment Methods
        new Chart(document.getElementById('paymentMethods'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($methodLabels) ?>,
                datasets: [{
                    label: 'Amount',
                    data: <?= json_encode($methodTotals) ?>,
                    backgroundColor: '#52c41a'
                }]
            },
            options: commonOptions
        });
    </script>
</body>

</html>