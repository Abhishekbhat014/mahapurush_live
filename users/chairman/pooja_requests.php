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
$currentPage = 'pooja_requests.php';
$currLang = $_SESSION['lang'] ?? 'en';

$loggedInUserPhoto = get_user_avatar_url('../../');

$sql = "SELECT p.id, pt.type AS pooja_name, p.pooja_date, p.time_slot, p.status, p.fee, u.first_name, u.last_name
        FROM pooja p
        JOIN pooja_type pt ON pt.id = p.pooja_type_id
        JOIN users u ON u.id = p.user_id
        ORDER BY p.pooja_date ASC, p.created_at DESC";
$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['pooja_requests'] ?? 'Pooja Requests'; ?> - <?= $t['title'] ?></title>
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

        .badge-status {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            background: #fffbe6;
            color: #faad14;
            border: 1px solid #ffe58f;
        }

        .action-btn {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 12px;
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
                <div class="user-pill">
                    <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <div class="vr mx-2 text-muted opacity-25"></div>
                    <a href="../../auth/logout.php" class="text-danger"><i class="bi bi-power"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['pooja_requests'] ?? 'Pooja Approvals'; ?></h2>
                    <p class="text-secondary mb-0">
                        View all pooja booking requests, check availability, and approve/reject when required (override authority).
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['devotee_name'] ?? 'Devotee'; ?></th>
                                        <th><?php echo $t['pooja_type'] ?? 'Pooja Type'; ?></th>
                                        <th><?php echo $t['schedule'] ?? 'Schedule'; ?></th>
                                        <th><?php echo $t['fee'] ?? 'Fee'; ?></th>
                                        <th><?php echo $t['status'] ?? 'Status'; ?></th>
                                        <th class="text-end"><?php echo $t['actions'] ?? 'Actions'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($result)): ?>
                                            <tr>
                                                <td class="fw-bold"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                                                <td class="text-primary fw-medium"><?= htmlspecialchars($r['pooja_name']) ?></td>
                                                <td>
                                                    <div class="fw-medium"><?= date('d M Y', strtotime($r['pooja_date'])) ?></div>
                                                    <small class="text-muted text-uppercase" style="font-size: 10px;">
                                                        <?= $r['time_slot'] ?? ($t['anytime'] ?? 'Anytime') ?>
                                                    </small>
                                                </td>
                                                <td class="fw-bold">&#8377;<?= number_format($r['fee'], 0) ?></td>
                                                <td><span class="badge-status"><?= htmlspecialchars($r['status']) ?></span></td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <button class="btn btn-success btn-sm action-btn" disabled><?php echo $t['approve'] ?? 'Approve'; ?></button>
                                                        <button class="btn btn-outline-primary btn-sm action-btn" disabled><?php echo $t['assign'] ?? 'Assign'; ?></button>
                                                        <button class="btn btn-light btn-sm action-btn border" disabled><?php echo $t['edit'] ?? 'Edit'; ?></button>
                                                        <button class="btn btn-outline-danger btn-sm action-btn" disabled><?php echo $t['delete'] ?? 'Delete'; ?></button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar2-x fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_bookings_scheduled'] ?? 'No requests found.'; ?>
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
