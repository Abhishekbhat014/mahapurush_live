<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;



}
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');


$currLang = $_SESSION['lang'] ?? 'en';

$uid = (int) $_SESSION['user_id'];
$currentPage = 'my_requests.php';

// Pending/rejected contributions
$contributions = [];
$q1 = $con->prepare("SELECT c.id, c.title, c.quantity, c.unit, c.status, c.created_at, ct.type FROM contributions c JOIN contribution_type ct ON ct.id = c.contribution_type_id WHERE c.added_by = ? AND c.status IN ('pending','rejected') ORDER BY c.created_at DESC");
$q1->bind_param("i", $uid);
$q1->execute();
$contributions = $q1->get_result()->fetch_all(MYSQLI_ASSOC);

// Pending pooja bookings
$poojas = [];
$q2 = $con->prepare("SELECT p.id, pt.type, p.pooja_date, p.time_slot, p.status, p.created_at FROM pooja p JOIN pooja_type pt ON pt.id = p.pooja_type_id WHERE p.user_id = ? AND p.status = 'pending' ORDER BY p.created_at DESC");
$q2->bind_param("i", $uid);
$q2->execute();
$poojas = $q2->get_result()->fetch_all(MYSQLI_ASSOC);

// Pending/failed payments
$payments = [];
$q3 = $con->prepare("SELECT id, amount, status, payment_method, created_at FROM payments WHERE user_id = ? AND status IN ('pending','failed') ORDER BY created_at DESC");
$q3->bind_param("i", $uid);
$q3->execute();
$payments = $q3->get_result()->fetch_all(MYSQLI_ASSOC);

// User Photo (session cached)
$userPhoto = get_user_avatar_url('../../');
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['my_requests']; ?> - <?= $t['title'] ?></title>
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
            -webkit-user-select: none;
            -ms-user-select: none;
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
            margin-bottom: 32px;
            overflow: hidden;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            background: #fafafa;
            font-size: 15px;
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

        .ant-badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .badge-rejected,
        .badge-failed {
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

        .user-pill {
            background: #fff;
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid var(--ant-border-color);
            display: flex;
            align-items: center;
            gap: 10px;
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
                    <img src="<?= htmlspecialchars($userPhoto) ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
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
                        <?php echo $t['dashboard']; ?> / <?php echo $t['my_requests']; ?>
                    </div>
                    <h2 class="fw-bold mb-1"><?php echo $t['my_requests']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['my_requests_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">

                    <div class="ant-card">
                        <div class="ant-card-head"><i class="bi bi-box-seam me-2 text-primary"></i>
                            <?php echo $t['material_contributions']; ?></div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0 table-responsive-stack">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['item_name']; ?></th>
                                        <th><?php echo $t['category']; ?></th>
                                        <th><?php echo $t['quantity']; ?></th>
                                        <th><?php echo $t['status']; ?></th>
                                        <th><?php echo $t['date']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($contributions): foreach ($contributions as $c): ?>
                                            <tr>
                                                <td data-label="<?php echo $t['item_name']; ?>" class="fw-bold"><?= htmlspecialchars($c['title']) ?></td>
                                                <td data-label="<?php echo $t['category']; ?>" class="text-secondary"><?= htmlspecialchars($c['type']) ?></td>
                                                <td data-label="<?php echo $t['quantity']; ?>"><?= $c['quantity'] . ' ' . $c['unit'] ?></td>
                                                <td data-label="<?php echo $t['status']; ?>"><span class="ant-badge <?= $c['status'] == 'pending' ? 'badge-pending' : 'badge-rejected' ?>">
                                                        <?= ($c['status'] == 'pending') ? $t['pending'] : $t['rejected']; ?></span>
                                                </td>
                                                <td data-label="<?php echo $t['date']; ?>" class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted small"><?php echo $t['no_pending_material_contributions']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ant-card">
                        <div class="ant-card-head"><i class="bi bi-calendar-event me-2 text-primary"></i>
                            <?php echo $t['pending_pooja_bookings']; ?></div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0 table-responsive-stack">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['pooja_name']; ?></th>
                                        <th><?php echo $t['scheduled_date']; ?></th>
                                        <th><?php echo $t['time_slot']; ?></th>
                                        <th><?php echo $t['status']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($poojas): foreach ($poojas as $p): ?>
                                            <tr>
                                                <td data-label="<?php echo $t['pooja_name']; ?>" class="fw-bold"><?= htmlspecialchars($p['type']) ?></td>
                                                <td data-label="<?php echo $t['scheduled_date']; ?>"><?= date('d M Y', strtotime($p['pooja_date'])) ?></td>
                                                <td data-label="<?php echo $t['time_slot']; ?>" class="text-secondary"><?= ucfirst($p['time_slot'] ?? $t['any_time']) ?></td>
                                                <td data-label="<?php echo $t['status']; ?>"><span class="ant-badge badge-pending"><?php echo $t['pending_approval']; ?></span></td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted small"><?php echo $t['no_pending_bookings']; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="ant-card">
                        <div class="ant-card-head"><i class="bi bi-exclamation-octagon me-2 text-danger"></i>
                            <?php echo $t['payment_issues']; ?></div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0 table-responsive-stack">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['amount']; ?></th>
                                        <th><?php echo $t['method']; ?></th>
                                        <th><?php echo $t['issue_status']; ?></th>
                                        <th><?php echo $t['transaction_date']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($payments): foreach ($payments as $p): ?>
                                            <tr>
                                                <td data-label="<?php echo $t['amount']; ?>" class="fw-bold text-dark">₹<?= number_format($p['amount'], 2) ?></td>
                                                <td data-label="<?php echo $t['method']; ?>"><span class="text-uppercase small fw-bold text-muted"><?= $p['payment_method'] ?></span></td>
                                                <td data-label="<?php echo $t['issue_status']; ?>"><span class="ant-badge <?= $p['status'] == 'failed' ? 'badge-failed' : 'badge-pending' ?>">
                                                        <?= ($p['status'] == 'failed') ? $t['failed'] : $t['pending']; ?></span>
                                                </td>
                                                <td data-label="<?php echo $t['transaction_date']; ?>" class="text-muted small"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-5 text-muted small"><?php echo $t['all_payments_up_to_date']; ?></td>
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
