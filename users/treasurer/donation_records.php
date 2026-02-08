<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';
require __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'donation_records.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

// --- FILTER LOGIC ---
$whereClause = "WHERE p.status = 'success'"; // Only show successful payments

// 1. Date Filter
if (!empty($_GET['from_date']) && !empty($_GET['to_date'])) {
    $fromDate = mysqli_real_escape_string($con, $_GET['from_date']);
    $toDate = mysqli_real_escape_string($con, $_GET['to_date']);
    $whereClause .= " AND DATE(p.created_at) BETWEEN '$fromDate' AND '$toDate'";
}

// 2. Payment Mode
if (!empty($_GET['payment_mode'])) {
    $mode = mysqli_real_escape_string($con, $_GET['payment_mode']);
    $whereClause .= " AND p.payment_mode = '$mode'";
}

// 3. Search (Devotee Name or Phone)
if (!empty($_GET['devotee'])) {
    $search = mysqli_real_escape_string($con, $_GET['devotee']);
    $whereClause .= " AND (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.phone LIKE '%$search%')";
}

// --- FETCH DATA ---
$records = [];
if ($con) {
    $query = "
        SELECT 
            p.id, 
            p.amount, 
            p.payment_method, 
            p.created_at, 
            
            r.receipt_no,
            COALESCE(u.first_name, 'Guest') as devotee_name,
            u.last_name
        FROM payments p
        LEFT JOIN receipt r ON r.id = p.receipt_id
        LEFT JOIN users u ON u.id = p.user_id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT 50
    ";

    $result = mysqli_query($con, $query);
    while ($row = mysqli_fetch_assoc($result)) {
        $records[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Records - <?= $t['title'] ?? 'Temple' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES --- */
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f0f2f5;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);

            /* Treasurer Sidebar Overrides (Green) */
            --tr-active-text: #52c41a;
            --tr-active-bg: #f6ffed;
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

        /* --- ACTIVE DROPDOWN ITEM (Dark Blue) --- */
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
            background: radial-gradient(circle at top right, #f6ffed 0%, #ffffff 80%);
            /* Treasurer Green Tint */
            padding: 40px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 32px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        /* --- TABLES --- */
        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 14px 16px;
            font-size: 12px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            border-bottom: 1px solid var(--ant-border-color);
        }

        .ant-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
            font-size: 14px;
        }

        .ant-table tr:hover td {
            background-color: #fafafa;
        }

        .badge-soft {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-cash {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .badge-online {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
        }

        .badge-cheque {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: 0.2s;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
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
                    <h2 class="fw-bold mb-1">Donation Records</h2>
                    <p class="text-secondary mb-0">
                        View, search and filter past donation transactions.
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card p-4">

                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">From Date</label>
                                <input type="date" name="from_date" class="form-control"
                                    value="<?= htmlspecialchars($_GET['from_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">To Date</label>
                                <input type="date" name="to_date" class="form-control"
                                    value="<?= htmlspecialchars($_GET['to_date'] ?? '') ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted text-uppercase fw-bold">Mode</label>
                                <select name="payment_mode" class="form-select">
                                    <option value="">All</option>
                                    <option value="cash" <?= (isset($_GET['payment_mode']) && $_GET['payment_mode'] == 'cash') ? 'selected' : '' ?>>Cash</option>
                                    <option value="online" <?= (isset($_GET['payment_mode']) && $_GET['payment_mode'] == 'online') ? 'selected' : '' ?>>Online</option>
                                    <option value="cheque" <?= (isset($_GET['payment_mode']) && $_GET['payment_mode'] == 'cheque') ? 'selected' : '' ?>>Cheque</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small text-muted text-uppercase fw-bold">Search</label>
                                <input type="text" name="devotee" class="form-control"
                                    placeholder="Devotee Name / Mobile"
                                    value="<?= htmlspecialchars($_GET['devotee'] ?? '') ?>">
                            </div>
                            <div class="col-md-1 d-flex align-items-end">
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table ant-table align-middle">
                                <thead>
                                    <tr>
                                        <th>Receipt No</th>
                                        <th>Date</th>
                                        <th>Devotee</th>
                                        <th>Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($records)): ?>
                                        <?php foreach ($records as $r):
                                            // Badge logic
                                            $mode = strtolower($r['payment_mode']);
                                            $badgeClass = 'badge-cash';
                                            if (strpos($mode, 'online') !== false)
                                                $badgeClass = 'badge-online';
                                            if (strpos($mode, 'cheque') !== false)
                                                $badgeClass = 'badge-cheque';
                                            ?>
                                            <tr>
                                                <td class="fw-bold text-primary">
                                                    #<?= htmlspecialchars($r['receipt_no'] ?? '-') ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= date('d M Y', strtotime($r['created_at'])) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-medium text-dark">
                                                        <?= htmlspecialchars($r['devotee_name'] . ' ' . ($r['last_name'] ?? '')) ?>
                                                    </div>
                                                </td>
                                                <td class="fw-bold">₹<?= number_format($r['amount'], 2) ?></td>
                                                <td>
                                                    <span class="badge-soft <?= $badgeClass ?>">
                                                        <?= htmlspecialchars($r['payment_mode']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= htmlspecialchars($r['description'] ?? '-') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-journal-x fs-1 opacity-25 d-block mb-3"></i>
                                                <p class="mb-0">No records found for the selected criteria.</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
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