<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'pooja_bookings.php'; // Adjusted filename based on Secretary sidebar
$loggedInUserPhoto = get_user_avatar_url('../../');

$success = '';
$error = '';

/* ============================
   HANDLE STATUS UPDATE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pooja_id'], $_POST['new_status'])) {
    $poojaId = (int) $_POST['pooja_id'];
    $newStatus = $_POST['new_status'];

    $performedBy = isset($_POST['performed_by']) ? trim($_POST['performed_by']) : null;

    if (in_array($newStatus, ['approved', 'rejected'])) {
        // Only update if currently 'pending'
        if ($newStatus === 'approved' && !empty($performedBy)) {
            $stmt = $con->prepare("UPDATE pooja SET status=?, approved_by=?, performed_by=?, approved_at=NOW() WHERE id=? AND status='pending'");
            $stmt->bind_param("sisi", $newStatus, $uid, $performedBy, $poojaId);
        } else {
            $stmt = $con->prepare("UPDATE pooja SET status=?, approved_by=?, approved_at=NOW() WHERE id=? AND status='pending'");
            $stmt->bind_param("sii", $newStatus, $uid, $poojaId);
        }

        if ($stmt->execute()) {
            $statusText = ($newStatus === 'approved') ? ($t['approved'] ?? 'Approved') : ($t['rejected'] ?? 'Rejected');
            $success = sprintf($t['status_update_success'] ?? 'Pooja request %s successfully.', $statusText);
        } else {
            $error = $t['status_update_error'] ?? 'Failed to update status.';
        }
        $stmt->close();
    }
}

/* ============================
   FETCH ALL BOOKINGS
============================ */
$sql = "SELECT p.id, pt.type AS pooja_name, p.pooja_date, p.time_slot,
               p.status, p.fee, p.performed_by, u.first_name, u.last_name, p.created_at
        FROM pooja p
        JOIN pooja_type pt ON pt.id = p.pooja_type_id
        JOIN users u ON u.id = p.user_id
        ORDER BY FIELD(p.status, 'pending', 'approved', 'rejected', 'completed', 'cancelled'), p.pooja_date ASC";

$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['pooja_management_title']; ?> - <?= $t['title'] ?></title>
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
            --ant-success: #52c41a;
            --ant-error: #ff4d4f;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--ant-bg-layout);
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        /* Allow selection in inputs */
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

        /* Active Dropdown Item */
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

        /* Status Badges */
        .badge-pending {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .badge-approved {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .badge-rejected {
            background: #fff1f0;
            color: #ff4d4f;
            border: 1px solid #ffa39e;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .badge-completed {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-ant-success {
            background: var(--ant-success);
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            transition: 0.2s;
        }

        .btn-ant-success:hover {
            background: #389e0d;
            transform: translateY(-1px);
        }

        .btn-ant-danger {
            background: #fff;
            color: var(--ant-error);
            border: 1px solid var(--ant-error);
            padding: 5px 11px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 12px;
            transition: 0.2s;
        }

        .btn-ant-danger:hover {
            background: #fff1f0;
            transform: translateY(-1px);
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

                <div class="user-pill shadow-sm">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['pooja_management_title']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['pooja_management_desc']; ?></p>
                </div>

                <div class="p-4 pb-5">

                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?= htmlspecialchars($success) ?></div>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['devotee_name'] ?? 'Devotee'; ?></th>
                                        <th><?php echo $t['pooja_type'] ?? 'Pooja Type'; ?></th>
                                        <th><?php echo $t['schedule'] ?? 'Schedule'; ?></th>
                                        <th><?php echo $t['priest'] ?? 'Priest'; ?></th>
                                        <th><?php echo $t['fee'] ?? 'Fee'; ?></th>
                                        <th><?php echo $t['status'] ?? 'Status'; ?></th>
                                        <th class="text-end"><?php echo $t['actions'] ?? 'Actions'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($result)):
                                            $statusClass = match ($r['status']) {
                                                'pending' => 'badge-pending',
                                                'approved' => 'badge-approved',
                                                'rejected' => 'badge-rejected',
                                                'completed' => 'badge-completed',
                                                'cancelled' => 'badge-rejected',
                                                default => ''
                                            };
                                            ?>
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>
                                                </td>
                                                <td class="text-primary fw-medium">
                                                    <?= htmlspecialchars($r['pooja_name']) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-medium">
                                                        <?= date('d M Y', strtotime($r['pooja_date'])) ?>
                                                    </div>
                                                    <small class="text-muted text-uppercase" style="font-size: 10px;">
                                                        <?= $r['time_slot'] ?? $t['anytime'] ?? 'Anytime' ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?= htmlspecialchars($r['performed_by'] ?? 'Not Assigned') ?>
                                                </td>
                                                <td class="fw-bold">₹<?= number_format($r['fee'], 0) ?></td>
                                                <td>
                                                    <span class="<?= $statusClass ?>">
                                                        <?= ucfirst(htmlspecialchars($r['status'])) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($r['status'] === 'pending'): ?>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <form method="POST" class="d-flex gap-2 align-items-center mb-0">
                                                                <input type="hidden" name="pooja_id" value="<?= $r['id'] ?>">
                                                                <input type="hidden" name="new_status" value="approved">
                                                                <input type="text" name="performed_by" class="form-control form-control-sm" style="width:130px;" placeholder="<?= $t['priest_name'] ?? 'Priest Name' ?>" required>
                                                                <button type="submit" class="btn-ant-success">
                                                                    <i class="bi bi-check2"></i> <?php echo $t['approve_btn']; ?>
                                                                </button>
                                                            </form>
                                                            <form method="POST" class="mb-0 mt-1">
                                                                <input type="hidden" name="pooja_id" value="<?= $r['id'] ?>">
                                                                <input type="hidden" name="new_status" value="rejected">
                                                                <button type="submit" class="btn-ant-danger">
                                                                    <i class="bi bi-x-lg"></i> <?php echo $t['reject_btn']; ?>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small fst-italic">
                                                            <?php echo $t['processed_status']; ?>
                                                        </span>
                                                    <?php endif; ?>
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
    <script>
        // Disable Right Click
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