<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';
require __DIR__ . '/../../config/db.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;



}
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');


$currLang = $_SESSION['lang'] ?? 'en';
$uid = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? $t['user'];
$currentPage = 'history.php';

$filter = $_GET['type'] ?? 'all';
$allowed = ['all', 'pooja', 'contribution', 'donation'];
if (!in_array($filter, $allowed, true)) {
    $filter = 'all';
}

// User photo for header (session cached)
$userPhotoUrl = get_user_avatar_url('../../');

$items = [];

if ($filter === 'all' || $filter === 'pooja') {
    $q = $con->prepare("
        SELECT p.pooja_date AS item_date,
               p.time_slot,
               p.status,
               p.fee AS amount,
               pt.type AS title,
               'pooja' AS item_type
        FROM pooja p
        JOIN pooja_type pt ON pt.id = p.pooja_type_id
        WHERE p.user_id = ?
        ORDER BY p.pooja_date DESC, p.created_at DESC
    ");
    $q->bind_param("i", $uid);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

if ($filter === 'all' || $filter === 'contribution') {
    $q = $con->prepare("
        SELECT c.created_at AS item_date,
               c.unit,
               c.quantity,
               c.status,
               NULL AS amount,
               c.title,
               ct.type AS subtype,
               'contribution' AS item_type
        FROM contributions c
        JOIN contribution_type ct ON ct.id = c.contribution_type_id
        WHERE c.added_by = ?
        ORDER BY c.created_at DESC
    ");
    $q->bind_param("i", $uid);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

if ($filter === 'all' || $filter === 'donation') {
    $q = $con->prepare("
        SELECT p.created_at AS item_date,
               p.status,
               p.amount,
               p.donor_name AS title,
               p.payment_method,
               'donation' AS item_type
        FROM payments p
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");
    $q->bind_param("i", $uid);
    $q->execute();
    $res = $q->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
}

usort($items, function ($a, $b) {
    return strtotime($b['item_date']) <=> strtotime($a['item_date']);
});
?>

<!DOCTYPE html>
<html lang="<?php echo $currLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['history'] ?? 'History'; ?> - <?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="customer-responsive.css">

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
            padding: 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 24px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
        }

        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 14px;
            font-size: 12px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
        }

        .ant-table td {
            padding: 14px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
        }

        .filter-btn.active {
            background: var(--ant-primary);
            color: #fff;
            border-color: var(--ant-primary);
        }

        .status-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .status-success,
        .status-approved {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .status-pending {
            background: #fffbe6;
            color: #faad14;
            border: 1px solid #ffe58f;
        }

        .status-rejected,
        .status-failed,
        .status-cancelled {
            background: #fff1f0;
            color: #f5222d;
            border: 1px solid #ffa39e;
        }

        @media (max-width: 767.98px) {
            .table-responsive-stack thead {
                display: none;
            }
            .table-responsive-stack tr {
                display: block;
                border: 1px solid var(--ant-border-color);
                border-radius: 12px;
                margin-bottom: 12px;
                padding: 6px 8px;
            }
            .table-responsive-stack td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border: none;
                padding: 6px 8px;
            }
            .table-responsive-stack td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--ant-text-sec);
                padding-right: 12px;
            }
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
                    <div class="small text-muted mb-1">
                        <?php echo $t['dashboard']; ?> / <?php echo $t['history'] ?? 'History'; ?>
                    </div>
                    <h2 class="fw-bold mb-1"><?php echo $t['history'] ?? 'History'; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['history_subtitle'] ?? 'All your temple service activity in one place.'; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <a class="btn btn-outline-primary btn-sm filter-btn <?= $filter === 'all' ? 'active' : '' ?>" href="?type=all">
                            <?php echo $t['all'] ?? 'All'; ?>
                        </a>
                        <a class="btn btn-outline-primary btn-sm filter-btn <?= $filter === 'pooja' ? 'active' : '' ?>" href="?type=pooja">
                            <?php echo $t['pooja'] ?? 'Pooja'; ?>
                        </a>
                        <a class="btn btn-outline-primary btn-sm filter-btn <?= $filter === 'contribution' ? 'active' : '' ?>" href="?type=contribution">
                            <?php echo $t['contribution'] ?? 'Contribution'; ?>
                        </a>
                        <a class="btn btn-outline-primary btn-sm filter-btn <?= $filter === 'donation' ? 'active' : '' ?>" href="?type=donation">
                            <?php echo $t['donations'] ?? 'Donation'; ?>
                        </a>
                    </div>

                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0 table-responsive-stack">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['type'] ?? 'Type'; ?></th>
                                        <th><?php echo $t['details'] ?? 'Details'; ?></th>
                                        <th><?php echo $t['date']; ?></th>
                                        <th><?php echo $t['status']; ?></th>
                                        <th class="text-end"><?php echo $t['amount'] ?? 'Amount'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($items)): ?>
                                        <?php foreach ($items as $row): ?>
                                            <?php
                                            $statusKey = strtolower($row['status'] ?? 'pending');
                                            $statusClass = match ($statusKey) {
                                                'paid', 'completed', 'success' => 'status-success',
                                                'approved' => 'status-approved',
                                                'failed', 'rejected', 'cancelled' => 'status-failed',
                                                default => 'status-pending'
                                            };
                                            ?>
                                            <tr>
                                                <td data-label="<?php echo $t['type'] ?? 'Type'; ?>" class="fw-bold text-uppercase small">
                                                    <?php
                                                    echo $row['item_type'] === 'pooja'
                                                        ? ($t['pooja'] ?? 'Pooja')
                                                        : ($row['item_type'] === 'contribution' ? ($t['contribution'] ?? 'Contribution') : ($t['donations'] ?? 'Donation'));
                                                    ?>
                                                </td>
                                                <td data-label="<?php echo $t['details'] ?? 'Details'; ?>">
                                                    <?php if ($row['item_type'] === 'pooja'): ?>
                                                        <?= htmlspecialchars($row['title']) ?>
                                                        <div class="small text-muted">
                                                            <?= ucfirst($row['time_slot'] ?? $t['not_available']) ?>
                                                        </div>
                                                    <?php elseif ($row['item_type'] === 'contribution'): ?>
                                                        <?= htmlspecialchars($row['title']) ?>
                                                        <div class="small text-muted">
                                                            <?= htmlspecialchars($row['subtype'] ?? '') ?>
                                                            <?= ($row['quantity'] ?? '') !== '' ? '• ' . $row['quantity'] . ' ' . ($row['unit'] ?? '') : '' ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <?= htmlspecialchars($row['title'] ?? ($t['donation'] ?? 'Donation')) ?>
                                                        <div class="small text-muted">
                                                            <?= htmlspecialchars(strtoupper($row['payment_method'] ?? '')) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td data-label="<?php echo $t['date']; ?>"><?= date('d M Y', strtotime($row['item_date'])) ?></td>
                                                <td data-label="<?php echo $t['status']; ?>">
                                                    <span class="status-pill <?php echo $statusClass; ?>">
                                                        <?= ucfirst($row['status'] ?? $t['pending']) ?>
                                                    </span>
                                                </td>
                                                <td data-label="<?php echo $t['amount'] ?? 'Amount'; ?>" class="text-end fw-bold">
                                                    <?php if (!empty($row['amount'])): ?>
                                                        â‚¹<?= number_format((float) $row['amount'], 2) ?>
                                                    <?php else: ?>
                                                        —
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-clock-history d-block fs-3 opacity-25 mb-2"></i>
                                                <?php echo $t['no_history'] ?? 'No activity found.'; ?>
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
