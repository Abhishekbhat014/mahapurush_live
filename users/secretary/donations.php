<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'payments.php'; // Ensure this matches your sidebar link
$loggedInUserPhoto = get_user_avatar_url('../../');

// --- 1. HANDLE ACTIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['action'])) {
    $pid = (int) $_POST['payment_id'];
    $action = $_POST['action'];

    // Secure status update
    if ($action === 'approve') {
        // Typically status changes to 'success' or 'approved' based on your DB Enum
        mysqli_query($con, "UPDATE payments SET status='success' WHERE id=$pid AND status='pending'");
    } elseif ($action === 'reject') {
        mysqli_query($con, "UPDATE payments SET status='failed' WHERE id=$pid AND status='pending'");
    }

    header("Location: payments.php?msg=updated");
    exit;
}

// --- 2. FETCH DATA ---

// Calculate Total Collection (Fixed missing query)
$totalRes = mysqli_query($con, "SELECT IFNULL(SUM(amount), 0) FROM payments WHERE status='success'");
$totalCollection = mysqli_fetch_row($totalRes)[0] ?? 0;

// Fetch Pending Payments
$sql = "SELECT p.id, p.donor_name, p.amount, p.payment_method, p.created_at
        FROM payments p
        WHERE p.status = 'pending'
        ORDER BY p.created_at ASC";

$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['payment_ledger_title']; ?> - <?= $t['title'] ?></title>
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

        /* --- DROPDOWN ANIMATION --- */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }

            .dropdown:hover>.dropdown-menu {
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

        .badge-method {
            font-size: 10px;
            text-transform: uppercase;
            padding: 4px 8px;
            border-radius: 4px;
            background: #f0f5ff;
            color: #2f54eb;
            border: 1px solid #adc6ff;
            font-weight: 600;
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?php echo $t['payment_ledger_title']; ?></h2>
                            <p class="text-secondary mb-0"><?php echo $t['payment_ledger_desc']; ?></p>
                        </div>
                        <div class="text-end">
                            <small class="text-uppercase text-muted fw-bold"
                                style="font-size: 10px;"><?php echo $t['total_collection']; ?></small>
                            <h3 class="fw-bold text-primary mb-0">₹<?= number_format($totalCollection, 2) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="px-4 pb-5 pt-4">

                    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'updated'): ?>
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?php echo $t['transaction_updated_success']; ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['donor_name']; ?></th>
                                        <th><?php echo $t['txn_id']; ?></th>
                                        <th><?php echo $t['amount']; ?></th>
                                        <th><?php echo $t['method']; ?></th>
                                        <th><?php echo $t['date']; ?></th>
                                        <th class="text-end"><?php echo $t['actions']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <?= htmlspecialchars($row['donor_name']) ?>
                                                </td>
                                                <td class="text-muted small font-monospace">
                                                    <?= htmlspecialchars($row['transaction_id'] ?? '-') ?>
                                                </td>
                                                <td class="fw-bold text-success">
                                                    ₹<?= number_format($row['amount'], 2) ?>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge-method"><?= htmlspecialchars($row['payment_method']) ?></span>
                                                </td>
                                                <td class="text-secondary small">
                                                    <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" class="d-flex gap-2 justify-content-end">
                                                        <input type="hidden" name="payment_id" value="<?= $row['id'] ?>">

                                                        <button type="submit" name="action" value="approve"
                                                            class="btn btn-sm btn-success rounded-pill px-3 fw-bold"
                                                            title="<?php echo $t['approve_action']; ?>">
                                                            <i class="bi bi-check-lg"></i> <?php echo $t['approve_action']; ?>
                                                        </button>

                                                        <button type="submit" name="action" value="reject"
                                                            class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold"
                                                            title="<?php echo $t['reject_action']; ?>"
                                                            onclick="return confirm('<?php echo $t['confirm_reject_payment']; ?>');">
                                                            <i class="bi bi-x-lg"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-wallet2 fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_pending_payments']; ?>
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
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Prevent form resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>