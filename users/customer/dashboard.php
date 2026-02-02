<?php
require '../../includes/lang.php';
$currLang = $_SESSION['lang'] ?? 'en';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$dbPath = __DIR__ . '/../../config/db.php';
require $dbPath;

$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? "User";

/* -------------------------
    FETCH DONATIONS
------------------------- */
$result = false;
if ($con && $userId > 0) {
    $stmt = $con->prepare("
        SELECT p.amount, p.status, p.created_at, r.receipt_no
        FROM payments p
        LEFT JOIN receipt r ON r.id = p.receipt_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['dashboard']; ?> - <?php echo $t['title']; ?></title>
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
            transition: transform 0.3s ease;
        }

        .ant-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
        }

        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 16px;
            font-size: 13px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
        }

        .ant-table td {
            padding: 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
        }

        .badge-soft {
            padding: 4px 12px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 12px;
        }

        .status-success {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .status-pending {
            background: #fffbe6;
            color: #faad14;
            border: 1px solid #ffe58f;
        }

        .ant-btn-primary {
            background: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .ant-divider {
            height: 1px;
            background: var(--ant-border-color);
            margin: 15px 20px;
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
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5">
                    <i class="bi bi-flower1 text-warning me-2"></i><?php echo $t['title']; ?>
                </a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle border" data-bs-toggle="dropdown">
                        <i class="bi bi-translate me-1"></i> <?php echo strtoupper($currLang); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item small" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small" href="?lang=mr">मराठी</a></li>
                    </ul>
                </div>
                <div class="vr mx-2 text-muted opacity-25"></div>
                <span class="small fw-bold text-secondary"><?= htmlspecialchars($userName) ?></span>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['welcome']; ?>, <?= explode(' ', $userName)[0] ?>!</h2>
                    <p class="text-secondary mb-0">Track your donations and spiritual contributions.</p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card">
                        <div class="ant-card-head d-flex justify-content-between align-items-center">
                            <span><?php echo $t['your_donations']; ?></span>
                            <a href="../donate.php" class="ant-btn-primary text-decoration-none small">
                                <i class="bi bi-plus-lg"></i> <?php echo $t['new_donation']; ?>
                            </a>
                        </div>
                        <div class="ant-card-body p-0">
                            <?php if ($result && $result->num_rows > 0) { ?>
                                <div class="table-responsive">
                                    <table class="table ant-table mb-0">
                                        <thead>
                                            <tr>
                                                <th><?php echo $t['receipt_no']; ?></th>
                                                <th><?php echo $t['amount']; ?></th>
                                                <th><?php echo $t['status']; ?></th>
                                                <th><?php echo $t['date']; ?></th>
                                                <th class="text-end"><?php echo $t['action']; ?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($row = $result->fetch_assoc()) {
                                                $statusClass = ($row['status'] === 'success') ? 'status-success' : 'status-pending';
                                                $statusLabel = ($row['status'] === 'success') ? $t['success'] : $t['pending'];
                                                ?>
                                                <tr>
                                                    <td class="fw-bold text-primary">
                                                        #<?= htmlspecialchars($row['receipt_no'] ?? 'N/A') ?></td>
                                                    <td class="fw-bold">₹<?= number_format($row['amount'], 2) ?></td>
                                                    <td><span class="badge-soft <?= $statusClass ?>"><?= $statusLabel ?></span>
                                                    </td>
                                                    <td class="text-muted small">
                                                        <?= date("d M Y", strtotime($row['created_at'])) ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="../receipt/view.php?no=<?= $row['receipt_no']; ?>"
                                                            class="btn btn-sm btn-light border px-3 rounded-pill">
                                                            <i class="bi bi-file-earmark-text"></i> <?php echo $t['view']; ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php } else { ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted opacity-25" style="font-size: 4rem;"></i>
                                    <h6 class="fw-bold text-muted mt-3"><?php echo $t['no_data']; ?></h6>
                                    <p class="small text-muted mb-4"><?php echo $t['start_journey']; ?></p>
                                    <a href="../donate.php" class="btn btn-outline-primary rounded-pill px-4">
                                        <?php echo $t['make_donation']; ?>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>