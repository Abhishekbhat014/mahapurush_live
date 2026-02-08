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

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'reports.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

// --- REPORT LOGIC ---
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? '';

$reportData = [];

if ($type === 'pooja') {
    $q = mysqli_query($con, "
        SELECT p.id, p.devotee_name, p.pooja_type_id, p.pooja_date, p.status
        FROM pooja p
        WHERE DATE(p.pooja_date) BETWEEN '$from' AND '$to'
        ORDER BY p.pooja_date DESC
    ");
    while ($r = mysqli_fetch_assoc($q))
        $reportData[] = $r;
}

if ($type === 'donation') {
    $q = mysqli_query($con, "
        SELECT id, donor_name, amount, payment_method, created_at
        FROM payments
        WHERE status='success'
        AND DATE(created_at) BETWEEN '$from' AND '$to'
        ORDER BY created_at DESC
    ");
    while ($r = mysqli_fetch_assoc($q))
        $reportData[] = $r;
}

if ($type === 'receipt') {
    $q = mysqli_query($con, "
        SELECT receipt_no, purpose, amount, created_at
        FROM receipt
        WHERE DATE(created_at) BETWEEN '$from' AND '$to'
        ORDER BY created_at DESC
    ");
    while ($r = mysqli_fetch_assoc($q))
        $reportData[] = $r;
}

if ($type === 'event') {
    $q = mysqli_query($con, "
        SELECT name, conduct_on, status, created_at
        FROM events
        WHERE DATE(conduct_on) BETWEEN '$from' AND '$to'
        ORDER BY conduct_on DESC
    ");
    while ($r = mysqli_fetch_assoc($q))
        $reportData[] = $r;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - <?= $t['title'] ?></title>
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

        /* Allow selection in inputs */
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
            margin-bottom: 0;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            overflow: hidden;
        }

        /* Table Styling */
        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 16px;
            font-size: 13px;
            color: var(--ant-text-sec);
            border-bottom: 1px solid var(--ant-border-color);
            text-transform: uppercase;
        }

        .ant-table td {
            padding: 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
            font-size: 14px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
                    <h2 class="fw-bold mb-1">Reports</h2>
                    <p class="text-secondary mb-0">Generate summarized reports for temple activities.</p>
                </div>

                <div class="px-4 pb-5 pt-4">
                    <div class="ant-card p-4">

                        <form class="row g-3 mb-4 align-items-end" method="get">
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>"
                                    class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" class="form-control">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Report Type</label>
                                <select name="type" class="form-select">
                                    <option value="">Select Report...</option>
                                    <option value="pooja" <?= $type == 'pooja' ? 'selected' : '' ?>>Pooja Report</option>
                                    <option value="donation" <?= $type == 'donation' ? 'selected' : '' ?>>Donation Report
                                    </option>
                                    <option value="receipt" <?= $type == 'receipt' ? 'selected' : '' ?>>Receipt Report
                                    </option>
                                    <option value="event" <?= $type == 'event' ? 'selected' : '' ?>>Event Report</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-file-earmark-text me-2"></i> Generate Report
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($reportData)): ?>
                            <div class="table-responsive">
                                <table class="table ant-table mb-0">
                                    <thead>
                                        <tr>
                                            <?php foreach (array_keys($reportData[0]) as $head): ?>
                                                <th><?= ucfirst(str_replace('_', ' ', $head)) ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($reportData as $row): ?>
                                            <tr>
                                                <?php foreach ($row as $key => $val): ?>
                                                    <td>
                                                        <?php
                                                        // Basic Formatting
                                                        if (strpos($key, 'amount') !== false || strpos($key, 'fee') !== false) {
                                                            echo '₹' . number_format((float) $val, 2);
                                                        } elseif (strpos($key, 'date') !== false || strpos($key, 'at') !== false || strpos($key, 'on') !== false) {
                                                            echo date('d M Y', strtotime($val));
                                                        } else {
                                                            echo htmlspecialchars($val);
                                                        }
                                                        ?>
                                                    </td>
                                                <?php endforeach; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($type): ?>
                            <div class="text-center py-5 text-muted border rounded bg-light">
                                <i class="bi bi-search fs-1 opacity-25 d-block mb-3"></i>
                                No data found for the selected criteria.
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted border rounded bg-light">
                                <i class="bi bi-bar-chart-line fs-1 opacity-25 d-block mb-3"></i>
                                Please select a report type and date range.
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