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
$currentPage = 'my_donations.php';

// --- Data Fetching Logic ---
// 1. Monetary Donations (Join donations with payments for status, users for devotee name)
$donations = [];
$q1 = $con->prepare("
    SELECT p.id, p.amount, p.note, p.status, p.payment_method, p.transaction_ref AS transaction_id, p.created_at, u.first_name, u.last_name, p.donor_name 
    FROM payments p 
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id NOT IN (SELECT payment_id FROM pooja WHERE payment_id IS NOT NULL)
      AND p.id NOT IN (SELECT payment_id FROM event_participants WHERE payment_id IS NOT NULL)
    ORDER BY p.created_at DESC
");
$q1->execute();
$donations = $q1->get_result()->fetch_all(MYSQLI_ASSOC);

// 2. Material Contributions (Join contributions with contribution_type, users for contributor name)
$contributions = [];
$q2 = $con->prepare("
    SELECT c.id, c.title, c.quantity, c.unit, ct.type AS category, c.status, c.created_at, u.first_name, u.last_name
    FROM contributions c 
    JOIN contribution_type ct ON c.contribution_type_id = ct.id 
    JOIN users u ON c.added_by = u.id
    ORDER BY c.created_at DESC
");
$q2->execute();
$contributions = $q2->get_result()->fetch_all(MYSQLI_ASSOC);

// User Avatar (session cached)
$loggedInUserPhoto = get_user_avatar_url('../../');
?>

<!DOCTYPE html>
<html lang="<?= $currLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['all_donations_contributions'] ?? 'All Donations & Contributions' ?> - <?= $t['title'] ?></title>
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

        /* Status Badges */
        .ant-badge {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-pending {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .badge-success, .badge-approved {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .badge-failed, .badge-rejected {
            background: #fff1f0;
            color: #f5222d;
            border: 1px solid #ffa39e;
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

        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu { display: block; margin-top: 0; }
            .dropdown .dropdown-menu { display: none; }
            .dropdown:hover>.dropdown-menu { display: block; animation: fadeIn 0.2s ease-in-out; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
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
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:10px;">
                        <li><a class="dropdown-item small fw-medium <?= ($currLang=='en')?'active':'' ?>" href="?lang=en"><?= $t['lang_english'] ?></a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang=='mr')?'active':'' ?>" href="?lang=mr"><?= $t['lang_marathi_full'] ?></a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:10px;">
                            <?php foreach ($availableRoles as $role): ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role===$primaryRole)?'active':'' ?>">
                                            <?= htmlspecialchars(ucwords(str_replace('_',' ',$role))) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;">
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
                    <h2 class="fw-bold mb-1"><i class="bi bi-gift me-2 text-primary"></i><?= $t['all_donations_contributions'] ?? 'All Donations & Contributions' ?></h2>
                    <p class="text-secondary mb-0"><?= $t['all_donations_cont_subtitle'] ?? 'View the complete history of all monetary donations and material contributions.' ?></p>
                </div>

                <div class="px-4 pb-5">

                    <!-- Monetary Donations Table -->
                    <div class="ant-card">
                        <div class="ant-card-head d-flex justify-content-between align-items-center">
                            <div><i class="bi bi-currency-rupee me-2 text-success"></i><?= $t['monetary_donations'] ?? 'Monetary Donations' ?></div>
                            <span class="badge bg-light text-dark border"><?= count($donations) ?> <?= $t['total'] ?? 'Total' ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= $t['devotee'] ?? 'Devotee' ?></th>
                                        <th><?= $t['donation_purpose'] ?? 'Donation Purpose' ?></th>
                                        <th><?= $t['amount'] ?? 'Amount' ?></th>
                                        <th><?= $t['method'] ?? 'Method' ?></th>
                                        <th><?= $t['status'] ?? 'Status' ?></th>
                                        <th><?= $t['date'] ?? 'Date' ?></th>
                                        <th class="text-end"><?= $t['action'] ?? 'Action' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($donations): foreach ($donations as $d): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars(trim(!empty($d['first_name']) ? $d['first_name'] . ' ' . $d['last_name'] : $d['donor_name'])) ?></td>
                                            <td class="fw-medium text-dark"><?= htmlspecialchars($d['note'] ?: ($t['donation'] ?? 'Donation')) ?></td>
                                            <td class="fw-bold text-success">₹<?= number_format($d['amount'], 2) ?></td>
                                            <td><span class="text-uppercase small fw-bold text-muted"><?= htmlspecialchars($d['payment_method']) ?></span></td>
                                            <td>
                                                <span class="ant-badge badge-<?= strtolower($d['status']) ?>">
                                                    <?= $t[strtolower($d['status'])] ?? ucfirst($d['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-light rounded-pill px-3 shadow-sm border" data-bs-toggle="modal" data-bs-target="#moneyModal<?= $d['id'] ?>">
                                                    <i class="bi bi-eye text-primary"></i> <?= $t['view_details'] ?? 'View Details' ?>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Money Modal -->
                                        <div class="modal fade" id="moneyModal<?= $d['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                                                    <div class="modal-header border-bottom-0 pb-0">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-info-circle text-primary me-2"></i><?= $t['donation_details'] ?? 'Donation Details' ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['devotee'] ?? 'Devotee' ?></span>
                                                            <span class="fw-bold"><?= htmlspecialchars(trim(!empty($d['first_name']) ? $d['first_name'] . ' ' . $d['last_name'] : $d['donor_name'])) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['amount'] ?? 'Amount' ?></span>
                                                            <span class="fw-bold text-success">₹<?= number_format($d['amount'], 2) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['method'] ?? 'Method' ?></span>
                                                            <span class="fw-bold text-uppercase"><?= htmlspecialchars($d['payment_method']) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['txn_id'] ?? 'Transaction ID' ?></span>
                                                            <span class="font-monospace small"><?= htmlspecialchars($d['transaction_id'] ?? '-') ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['status'] ?? 'Status' ?></span>
                                                            <span class="ant-badge badge-<?= strtolower($d['status']) ?>"><?= $t[strtolower($d['status'])] ?? ucfirst($d['status']) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['date'] ?? 'Date' ?></span>
                                                            <span class="small"><?= date('d M Y, h:i A', strtotime($d['created_at'])) ?></span>
                                                        </div>
                                                        <div class="mb-0">
                                                            <span class="text-muted d-block mb-1"><?= $t['donation_purpose'] ?? 'Donation Purpose' ?> / Note</span>
                                                            <div class="p-3 bg-light rounded" style="font-size: 14px;"><?= nl2br(htmlspecialchars($d['note'] ?: ($t['donation'] ?? 'Donation'))) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill w-100 fw-bold border shadow-sm" data-bs-dismiss="modal"><?= $t['close'] ?? 'Close' ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted small">
                                                <i class="bi bi-journal-x d-block fs-3 opacity-25 mb-2"></i>
                                                <?= $t['no_donations_history'] ?? 'No monetary donations recorded yet.' ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Material Contributions Table -->
                    <div class="ant-card mt-4">
                        <div class="ant-card-head d-flex justify-content-between align-items-center">
                            <div><i class="bi bi-box-seam me-2 text-primary"></i><?= $t['material_contributions'] ?? 'Material Contributions' ?></div>
                            <span class="badge bg-light text-dark border"><?= count($contributions) ?> <?= $t['total'] ?? 'Total' ?></span>
                        </div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= $t['devotee'] ?? 'Devotee' ?></th>
                                        <th><?= $t['contribution_title'] ?? 'Contribution Title' ?></th>
                                        <th><?= $t['category'] ?? 'Category' ?></th>
                                        <th><?= $t['quantity'] ?? 'Quantity' ?></th>
                                        <th><?= $t['status'] ?? 'Status' ?></th>
                                        <th><?= $t['date'] ?? 'Date' ?></th>
                                        <th class="text-end"><?= $t['action'] ?? 'Action' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($contributions): foreach ($contributions as $c): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])) ?></td>
                                            <td class="fw-bold text-primary"><?= htmlspecialchars($c['title']) ?></td>
                                            <td class="text-secondary"><?= htmlspecialchars($c['category']) ?></td>
                                            <td class="fw-medium"><?= (float)$c['quantity'] . ' ' . htmlspecialchars($c['unit']) ?></td>
                                            <td>
                                                <span class="ant-badge badge-<?= strtolower($c['status']) ?>">
                                                    <?= $t[strtolower($c['status'])] ?? ucfirst($c['status']) ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-light rounded-pill px-3 shadow-sm border" data-bs-toggle="modal" data-bs-target="#materialModal<?= $c['id'] ?>">
                                                    <i class="bi bi-eye text-primary"></i> <?= $t['view_details'] ?? 'View Details' ?>
                                                </button>
                                            </td>
                                        </tr>
                                        
                                        <!-- Material Modal -->
                                        <div class="modal fade" id="materialModal<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
                                                    <div class="modal-header border-bottom-0 pb-0">
                                                        <h5 class="modal-title fw-bold"><i class="bi bi-box-seam text-primary me-2"></i><?= $t['contribution_details'] ?? 'Contribution Details' ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['devotee'] ?? 'Devotee' ?></span>
                                                            <span class="fw-bold"><?= htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name'])) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['contribution_title'] ?? 'Title' ?></span>
                                                            <span class="fw-bold text-primary"><?= htmlspecialchars($c['title']) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['category'] ?? 'Category' ?></span>
                                                            <span class="fw-bold"><?= htmlspecialchars($c['category']) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['quantity'] ?? 'Quantity' ?></span>
                                                            <span class="fw-bold"><?= (float)$c['quantity'] . ' ' . htmlspecialchars($c['unit']) ?></span>
                                                        </div>
                                                        <div class="mb-3 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['status'] ?? 'Status' ?></span>
                                                            <span class="ant-badge badge-<?= strtolower($c['status']) ?>"><?= $t[strtolower($c['status'])] ?? ucfirst($c['status']) ?></span>
                                                        </div>
                                                        <div class="mb-0 d-flex justify-content-between border-bottom pb-2">
                                                            <span class="text-muted"><?= $t['date'] ?? 'Date' ?></span>
                                                            <span class="small"><?= date('d M Y, h:i A', strtotime($c['created_at'])) ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top-0 pt-0 mt-3">
                                                        <button type="button" class="btn btn-light rounded-pill w-100 fw-bold border shadow-sm" data-bs-dismiss="modal"><?= $t['close'] ?? 'Close' ?></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted small">
                                                <i class="bi bi-box-x d-block fs-3 opacity-25 mb-2"></i>
                                                <?= $t['no_contributions_history'] ?? 'No material contributions recorded yet.' ?>
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
