<?php
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'pooja_approvals.php';
$success = '';
$error = '';

// --- Header Identity Logic ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=1677ff&color=fff';

/* ============================
   HANDLE STATUS UPDATE
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pooja_id'], $_POST['new_status'])) {
    $poojaId = (int) $_POST['pooja_id'];
    $newStatus = $_POST['new_status'];

    if (in_array($newStatus, ['completed', 'cancelled'])) {
        $stmt = $con->prepare("UPDATE pooja SET status=? WHERE id=? AND status IN ('pending','paid')");
        $stmt->bind_param("si", $newStatus, $poojaId);

        if ($stmt->execute()) {
            $success = "Pooja booking has been " . $newStatus . " successfully.";
        } else {
            $error = "Unable to update status. Please try again.";
        }
    }
}

/* ============================
   FETCH ALL BOOKINGS
============================ */
$sql = "SELECT p.id, pt.type AS pooja_name, p.pooja_date, p.time_slot, p.status, p.fee, u.first_name, u.last_name, p.created_at
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
    <title>Pooja Management -
        <?= $t['title'] ?>
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-success: #52c41a;
            --ant-error: #ff4d4f;
            --ant-bg-layout: #f0f2f5;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        /* Prevent Text Selection */
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

        /* Status Badge DNA */
        .ant-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .badge-pending {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .badge-paid {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
        }

        .badge-completed {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .badge-cancelled {
            background: #fff1f0;
            color: #f5222d;
            border: 1px solid #ffa39e;
        }

        .btn-ant-success {
            background: var(--ant-success);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            padding: 6px 12px;
            font-size: 13px;
            transition: 0.3s;
        }

        .btn-ant-success:hover {
            background: #73d13d;
            transform: translateY(-1px);
        }

        .btn-ant-danger {
            background: transparent;
            color: var(--ant-error);
            border: 1px solid var(--ant-error);
            border-radius: 6px;
            font-weight: 600;
            padding: 6px 12px;
            font-size: 13px;
            transition: 0.3s;
        }

        .btn-ant-danger:hover {
            background: #fff1f0;
            color: #ff7875;
            border-color: #ff7875;
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
                    <i class="bi bi-flower1 text-warning me-2"></i>
                    <?= $t['title'] ?>
                </a>
            </div>
            <div class="user-pill">
                <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline">
                    <?= htmlspecialchars($_SESSION['user_name']) ?>
                </span>
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
                    <h2 class="fw-bold mb-1">Pooja Bookings</h2>
                    <p class="text-secondary mb-0">Manage devotee schedules and verify ritual completions.</p>
                </div>

                <div class="px-4 pb-5">

                    <?php if ($success): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Devotee Name</th>
                                        <th>Pooja Type</th>
                                        <th>Schedule</th>
                                        <th>Fee</th>
                                        <th>Status</th>
                                        <th class="text-end">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($result)):
                                            $statusClass = match ($r['status']) {
                                                'pending' => 'badge-pending',
                                                'paid' => 'badge-paid',
                                                'completed' => 'badge-completed',
                                                'cancelled' => 'badge-cancelled',
                                                default => ''
                                            };
                                            ?>
                                            <tr>
                                                <td class="fw-bold">
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
                                                        <?= $r['time_slot'] ?? 'Anytime' ?>
                                                    </small>
                                                </td>
                                                <td class="fw-bold">₹
                                                    <?= number_format($r['fee'], 0) ?>
                                                </td>
                                                <td><span class="ant-badge <?= $statusClass ?>">
                                                        <?= ucfirst($r['status']) ?>
                                                    </span></td>
                                                <td class="text-end">
                                                    <?php if (in_array($r['status'], ['pending', 'paid'])): ?>
                                                        <div class="d-flex justify-content-end gap-2">
                                                            <form method="POST">
                                                                <input type="hidden" name="pooja_id" value="<?= $r['id'] ?>">
                                                                <input type="hidden" name="new_status" value="completed">
                                                                <button type="submit" class="btn-ant-success"
                                                                    title="Mark as Completed">
                                                                    <i class="bi bi-check2"></i> Done
                                                                </button>
                                                            </form>
                                                            <form method="POST">
                                                                <input type="hidden" name="pooja_id" value="<?= $r['id'] ?>">
                                                                <input type="hidden" name="new_status" value="cancelled">
                                                                <button type="submit" class="btn-ant-danger" title="Cancel Booking">
                                                                    <i class="bi bi-x-lg"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Archived</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar2-x fs-1 opacity-25 d-block mb-3"></i>
                                                No bookings scheduled at the moment.
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