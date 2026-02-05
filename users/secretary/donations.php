<?php
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

// Auth check (secretary)
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'payments.php';

// --- Header Identity Logic ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

// Fetch payments with Receipt Joining
$sql = "SELECT p.id, r.receipt_no, p.donor_name, p.amount, p.payment_method, p.status, p.created_at
        FROM payments p
        LEFT JOIN receipt r ON r.id = p.receipt_id
        ORDER BY p.created_at DESC";
$result = mysqli_query($con, $sql);

// Calculate Total for Summary
$totalRes = mysqli_query($con, "SELECT SUM(amount) FROM payments WHERE status='success'");
$totalCollection = mysqli_fetch_row($totalRes)[0] ?? 0;
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['payment_ledger']; ?> - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

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

        /* Global Unselectable Text */
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
            margin-bottom: 0;
        }

        /* Card & Table DNA */
        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            overflow: hidden;
        }

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

        .receipt-pill {
            font-family: monospace;
            background: #e6f4ff;
            color: var(--ant-primary);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
        }

        /* Status Badges */
        .ant-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .bg-ant-success {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .bg-ant-error {
            background: #fff1f0;
            color: #ff4d4f;
            border: 1px solid #ffa39e;
        }

        .bg-ant-warning {
            background: #fffbe6;
            color: #faad14;
            border: 1px solid #ffe58f;
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
                    <i class="bi bi-flower1 text-warning me-2"></i><?= $t['title'] ?>
                </a>
            </div>
            <div class="user-pill">
                <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="fw-bold mb-1"><?php echo $t['financial_ledger']; ?></h2>
                            <p class="text-secondary mb-0"><?php echo $t['financial_ledger_subtitle']; ?></p>
                        </div>
                        <div class="text-end">
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 10px;"><?php echo $t['lifetime_collection']; ?></small>
                            <h3 class="fw-bold text-primary mb-0">₹<?= number_format($totalCollection, 2) ?></h3>
                        </div>
                    </div>
                </div>

                <div class="p-4 pb-5">
                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['receipt_no']; ?></th>
                                        <th><?php echo $t['donor_devotee']; ?></th>
                                        <th><?php echo $t['amount']; ?></th>
                                        <th><?php echo $t['method']; ?></th>
                                        <th><?php echo $t['status']; ?></th>
                                        <th><?php echo $t['date_time']; ?></th>
                                        <th class="text-end"><?php echo $t['actions']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($row['receipt_no']): ?>
                                                        <span
                                                            class="receipt-pill">#<?= htmlspecialchars($row['receipt_no']) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><?php echo $t['not_available']; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="fw-bold"><?= htmlspecialchars($row['donor_name']) ?></td>
                                                <td class="fw-bold text-dark">₹<?= number_format($row['amount'], 2) ?></td>
                                                <td><span class="badge bg-light text-dark border text-uppercase"
                                                        style="font-size: 10px;"><?= $row['payment_method'] ?></span></td>
                                                <td>
                                                    <?php
                                                    $sClass = match ($row['status']) {
                                                        'success' => 'bg-ant-success',
                                                        'failed' => 'bg-ant-error',
                                                        default => 'bg-ant-warning'
                                                    };
                                                    ?>
                                                    <span class="ant-badge <?= $sClass ?>"><?= $row['status'] === 'success' ? $t['success'] : ($row['status'] === 'failed' ? $t['failed'] : $t['pending']); ?></span>
                                                </td>
                                                <td class="text-secondary small">
                                                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                                                    <div style="font-size: 11px; opacity: 0.7;">
                                                        <?= date('h:i A', strtotime($row['created_at'])) ?>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($row['receipt_no']): ?>
                                                        <a href="../receipt/view.php?no=<?= $row['receipt_no'] ?>"
                                                            class="btn btn-light btn-sm border rounded-pill px-3 fw-bold"
                                                            style="font-size: 12px;">
                                                            <i class="bi bi-eye me-1"></i> <?php echo $t['view']; ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-wallet2 fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_payment_transactions']; ?>
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
</body>

</html>
