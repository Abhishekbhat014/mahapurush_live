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
$userName = $_SESSION['user_name'] ?? $t['user'];

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

/* -------------------------
    FETCH CONTRIBUTIONS
------------------------- */
$contribResult = false;
if ($con && $userId > 0) {
    $cStmt = $con->prepare("
        SELECT c.title, c.quantity, c.unit, c.status, c.created_at, ct.type AS category
        FROM contributions c
        LEFT JOIN contribution_type ct ON ct.id = c.contribution_type_id
        WHERE c.added_by = ?
        ORDER BY c.created_at DESC
        LIMIT 5
    ");
    $cStmt->bind_param("i", $userId);
    $cStmt->execute();
    $contribResult = $cStmt->get_result();
}

/* -------------------------
    SUMMARY CARDS
------------------------- */
$donationTotal = 0.0;
$donationCount = 0;
$contribPending = 0;
$contribTotal = 0;
$poojaPending = 0;
$receiptCount = 0;

if ($con && $userId > 0) {
    $sumStmt = $con->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS total FROM payments WHERE user_id = ? AND status = 'success'");
    $sumStmt->bind_param("i", $userId);
    $sumStmt->execute();
    $sumRes = $sumStmt->get_result()->fetch_assoc();
    $donationCount = (int) ($sumRes['cnt'] ?? 0);
    $donationTotal = (float) ($sumRes['total'] ?? 0);

    $cCountStmt = $con->prepare("SELECT COUNT(*) AS cnt, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_cnt FROM contributions WHERE added_by = ?");
    $cCountStmt->bind_param("i", $userId);
    $cCountStmt->execute();
    $cRes = $cCountStmt->get_result()->fetch_assoc();
    $contribTotal = (int) ($cRes['cnt'] ?? 0);
    $contribPending = (int) ($cRes['pending_cnt'] ?? 0);

    $pCountStmt = $con->prepare("SELECT SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_cnt FROM pooja WHERE user_id = ?");
    $pCountStmt->bind_param("i", $userId);
    $pCountStmt->execute();
    $pRes = $pCountStmt->get_result()->fetch_assoc();
    $poojaPending = (int) ($pRes['pending_cnt'] ?? 0);

    $rCountStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM receipt WHERE user_id = ?");
    $rCountStmt->bind_param("i", $userId);
    $rCountStmt->execute();
    $rRes = $rCountStmt->get_result()->fetch_assoc();
    $receiptCount = (int) ($rRes['cnt'] ?? 0);
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

        .stat-card {
            padding: 22px;
            border-radius: var(--ant-radius);
            border: 1px solid var(--ant-border-color);
            background: #fff;
            box-shadow: var(--ant-shadow);
            height: 100%;
        }

        .stat-label {
            font-size: 12px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--ant-text);
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

        .status-approved {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .status-rejected {
            background: #fff1f0;
            color: #f5222d;
            border: 1px solid #ffa39e;
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
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5">
                    <i class="bi bi-flower1 text-warning me-2"></i><?php echo $t['title']; ?>
                </a>
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
                <div class="user-pill shadow-sm">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($userName) ?>&background=random"
                        class="rounded-circle" width="28" height="28" style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['welcome']; ?>, <?= explode(' ', $userName)[0] ?>!</h2>
                    <p class="text-secondary mb-0"><?php echo $t['track_donations_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row g-4">
                        <div class="col-12 col-xl-8">
                            <div class="ant-card h-100">
                                <div class="ant-card-head d-flex justify-content-between align-items-center">
                                    <span><?php echo $t['your_donations']; ?></span>
                                    <a href="donate.php" class="ant-btn-primary text-decoration-none small">
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
                                                                #<?= htmlspecialchars($row['receipt_no'] ?? $t['not_available']) ?></td>
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
                                            <a href="donate.php" class="btn btn-outline-primary rounded-pill px-4">
                                                <?php echo $t['make_donation']; ?>
                                            </a>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="d-flex flex-column gap-4">
                                <div class="stat-card">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="stat-label"><?php echo $t['donations']; ?></div>
                                            <div class="stat-value">₹<?php echo number_format($donationTotal, 2); ?></div>
                                        </div>
                                        <div class="text-primary fs-3"><i class="bi bi-heart-fill"></i></div>
                                    </div>
                                    <div class="small text-muted mt-2"><?php echo $donationCount . ' ' . ($t['transactions'] ?? 'transactions'); ?></div>
                                    <div class="mt-3">
                                        <a href="donate.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <?php echo $t['donate_btn']; ?>
                                        </a>
                                    </div>
                                </div>

                                <div class="ant-card">
                                    <div class="ant-card-head d-flex justify-content-between align-items-center">
                                        <span><?php echo $t['contribution']; ?></span>
                                        <a href="contribute.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-plus-lg"></i> <?php echo $t['submit_contribution']; ?>
                                        </a>
                                    </div>
                                    <div class="ant-card-body p-0">
                                        <?php if ($contribResult && $contribResult->num_rows > 0) { ?>
                                            <div class="table-responsive">
                                                <table class="table ant-table mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th><?php echo $t['item_name']; ?></th>
                                                            <th><?php echo $t['status']; ?></th>
                                                            <th class="text-end"><?php echo $t['date']; ?></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while ($row = $contribResult->fetch_assoc()) {
                                                            $statusKey = strtolower($row['status'] ?? 'pending');
                                                            $statusClass = match ($statusKey) {
                                                                'approved' => 'status-approved',
                                                                'rejected' => 'status-rejected',
                                                                default => 'status-pending'
                                                            };
                                                            $statusLabel = match ($statusKey) {
                                                                'approved' => $t['approved'] ?? 'Approved',
                                                                'rejected' => $t['rejected'] ?? 'Rejected',
                                                                default => $t['pending']
                                                            };
                                                            ?>
                                                            <tr>
                                                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['title']) ?></td>
                                                                <td><span class="badge-soft <?= $statusClass ?>"><?= $statusLabel ?></span></td>
                                                                <td class="text-muted small text-end">
                                                                    <?= date("d M Y", strtotime($row['created_at'])) ?>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php } else { ?>
                                            <div class="text-center py-4">
                                                <i class="bi bi-box-seam text-muted opacity-25" style="font-size: 2.5rem;"></i>
                                                <div class="small text-muted mt-2"><?php echo $t['no_pending_material_contributions']; ?></div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>

                                <div class="stat-card">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <div class="stat-label"><?php echo $t['pooja_bookings']; ?></div>
                                            <div class="stat-value"><?php echo $poojaPending; ?></div>
                                        </div>
                                        <div class="text-primary fs-3"><i class="bi bi-calendar-check"></i></div>
                                    </div>
                                    <div class="small text-muted mt-2"><?php echo $t['pending_approval'] ?? $t['pending']; ?></div>
                                    <div class="mt-3 d-flex gap-2">
                                        <a href="pooja_book.php" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <?php echo $t['book_pooja_btn']; ?>
                                        </a>
                                        <a href="my_requests.php" class="btn btn-light btn-sm rounded-pill px-3 border">
                                            <?php echo $t['my_requests']; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>


