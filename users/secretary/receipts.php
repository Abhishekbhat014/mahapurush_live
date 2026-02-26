<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth check (secretary)
if (!isset($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'receipts.php';

// --- Header Identity Logic (session cached) ---
$loggedInUserPhoto = get_user_avatar_url('../../');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rid'])) {
    $rid = (int) $_POST['rid'];

    mysqli_query($con, "
        UPDATE receipt 
        SET verified_by_secretary = $uid,
            verified_at = NOW()
        WHERE id = $rid
        AND verified_by_secretary IS NULL
    ");

    header("Location: receipts.php");
    exit;
}

$sql = "SELECT r.id, r.receipt_no, r.issued_on,
               CASE 
                   WHEN p.id IS NOT NULL THEN 'Donation'
                   WHEN pj.id IS NOT NULL THEN 'Pooja'
                   WHEN c.id IS NOT NULL THEN 'Contribution'
               END AS purpose,
               COALESCE(p.donor_name, pt.type, c.title) AS title,
               COALESCE(p.amount, pj.fee, 0) AS amount
        FROM receipt r
        LEFT JOIN payments p ON p.receipt_id = r.id
        LEFT JOIN pooja pj ON pj.payment_id = p.id
        LEFT JOIN pooja_type pt ON pt.id = pj.pooja_type_id
        LEFT JOIN contributions c ON c.receipt_id = r.id
        WHERE r.verified_by_secretary IS NULL
        ORDER BY r.issued_on ASC";



$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipt_archive']; ?> -
        <?= $t['title'] ?>
    </title>
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

        /* Global Selection Protection */
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

        /* Card & Table Styling */
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

        .receipt-id {
            font-family: 'SFMono-Regular', Consolas, monospace;
            color: var(--ant-primary);
            font-weight: 600;
        }

        /* Purpose Badges */
        .ant-badge {
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

        .badge-contribution {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
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
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1 m-0">
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

                <div class="user-pill shadow-sm">
                    <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline">
                        <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['receipt_archive']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['receipt_archive_subtitle']; ?></p>
                </div>

                <div class="p-4 pb-5">
                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['receipt_id']; ?></th>
                                        <th><?php echo $t['purpose']; ?></th>
                                        <th><?php echo $t['details']; ?></th>
                                        <th><?php echo $t['amount']; ?></th>
                                        <th><?php echo $t['issued_date']; ?></th>
                                        <th class="text-end"><?php echo $t['actions']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)):
                                            $badgeClass = 'badge-' . strtolower($row['purpose']);
                                            ?>
                                            <tr>
                                                <td class="receipt-id">#
                                                    <?= htmlspecialchars($row['receipt_no']) ?>
                                                </td>
                                                <td><span class="ant-badge <?= $badgeClass ?>">
                                                        <?= $row['purpose'] ?>
                                                    </span></td>
                                                <td class="fw-medium">
                                                    <?= htmlspecialchars($row['title']) ?>
                                                </td>
                                                <td class="fw-bold text-dark">
                                                    <?= ($row['purpose'] === 'Contribution')
                                                        ? '<span class="text-muted small">' . $t['in_kind'] . '</span>'
                                                        : '₹' . number_format($row['amount'], 2) ?>
                                                </td>
                                                <td class="text-secondary small">
                                                    <?= date('d M Y', strtotime($row['issued_on'])) ?>
                                                    <div style="font-size: 11px; opacity: 0.6;">
                                                        <?= date('h:i A', strtotime($row['issued_on'])) ?>
                                                    </div>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST">
                                                        <input type="hidden" name="rid" value="<?= $row['id'] ?>">
                                                        <button class="btn btn-success btn-sm rounded-pill px-3">
                                                            <i class="bi bi-check2-circle"></i> Verify
                                                        </button>
                                                    </form>

                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-receipt-cutoff fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_receipts_issued']; ?>
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