<?php
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../config/db.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$currLang = $_SESSION['lang'] ?? 'en';

$uid = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? $t['user'];
$currentPage = 'my_receipts.php';

// Fetch receipts for customer (donations + pooja)
$receipts = [];
if ($uid > 0) {
    $sql = "
        SELECT
            r.receipt_no,
            r.issued_on,
            r.purpose,
            r.amount
        FROM receipt r
        WHERE r.user_id = ?
        ORDER BY r.issued_on DESC
    ";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $receipts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

// Fetch user photo for header
$uRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1"));
$displayName = trim(($uRow['first_name'] ?? '') . ' ' . ($uRow['last_name'] ?? ''));
$userPhotoUrl = !empty($uRow['photo'])
    ? '../../uploads/users/' . $uRow['photo']
    : 'https://ui-avatars.com/api/?name=' . urlencode($displayName ?: $userName) . '&background=random';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipt_history']; ?> - <?php echo $t['title']; ?></title>
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

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
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
        }

        .ant-card-body {
            padding: 0;
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

        .purpose-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-pooja {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .badge-donation {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .receipt-no {
            font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
            color: var(--ant-primary);
            font-weight: 600;
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
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i
                        class="bi bi-list"></i></button>
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
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
                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['receipt_history']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['receipt_history_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card">
                        <div class="ant-card-body">
                            <div class="table-responsive">
                                <table class="table ant-table mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php echo $t['receipt_no']; ?></th>
                                            <th><?php echo $t['type']; ?></th>
                                            <th><?php echo $t['amount']; ?></th>
                                            <th><?php echo $t['issued_date']; ?></th>
                                            <th class="text-end"><?php echo $t['action']; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($receipts)): ?>
                                            <?php foreach ($receipts as $r):
                                                $badgeClass = 'badge-' . $r['purpose']; ?>
                                                <tr>
                                                    <td class="receipt-no">#<?= htmlspecialchars($r['receipt_no']) ?></td>
                                                    <td>
                                                        <span class="purpose-badge <?= $badgeClass ?>">
                                                            <?= $r['purpose'] === 'pooja' ? $t['pooja'] : $t['donation'] ?>
                                                        </span>
                                                    </td>
                                                    <td class="fw-bold">₹<?= number_format($r['amount'], 2) ?></td>
                                                    <td class="text-secondary small">
                                                        <?= date("d M Y, h:i A", strtotime($r['issued_on'])) ?>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="../receipt/view.php?no=<?= urlencode($r['receipt_no']) ?>"
                                                            class="btn btn-light btn-sm border rounded-pill px-3 fw-medium">
                                                            <i class="bi bi-file-earmark-text me-1"></i> <?php echo $t['view']; ?>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5 text-muted">
                                                    <i class="bi bi-file-earmark-x d-block fs-1 opacity-25 mb-3"></i>
                                                    <?php echo $t['no_receipts_found']; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
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
