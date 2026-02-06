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
$currentPage = 'contributions_review.php';
$success = '';
$error = '';

// --- Header Identity Logic ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

/* ============================
   HANDLE APPROVE / REJECT
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) $_POST['contribution_id'];
    $action = $_POST['action'];

    if (in_array($action, ['approved', 'rejected'])) {
        $stmt = $con->prepare("UPDATE contributions SET status=? WHERE id=? AND status='pending'");
        $stmt->bind_param("si", $action, $id);

        if ($stmt->execute()) {
            $success = sprintf($t['contribution_action_success'], $action === 'approved' ? $t['approved'] : $t['rejected']);
        } else {
            $error = $t['contribution_update_failed'];
        }
    }
}

/* ============================
   FETCH PENDING CONTRIBUTIONS
============================ */
$sql = "SELECT c.id, c.title, c.quantity, c.unit, c.created_at, ct.type AS category, c.contributor_name
        FROM contributions c
        JOIN contribution_type ct ON ct.id = c.contribution_type_id
        WHERE c.status = 'pending'
        ORDER BY c.created_at ASC";
$rows = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['contributions_approval']; ?> -
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

        /* Button DNA */
        .btn-ant-success {
            background: var(--ant-success);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            padding: 6px 16px;
            transition: 0.3s;
        }

        .btn-ant-success:hover {
            background: #73d13d;
            transform: translateY(-1px);
        }

        .btn-ant-reject {
            background: transparent;
            color: var(--ant-error);
            border: 1px solid var(--ant-error);
            border-radius: 6px;
            font-weight: 600;
            padding: 6px 16px;
            transition: 0.3s;
        }

        .btn-ant-reject:hover {
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
                    <h2 class="fw-bold mb-1"><?php echo $t['contributions_approval']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['contributions_approval_subtitle']; ?></p>
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
                                        <th><?php echo $t['contributor']; ?></th>
                                        <th><?php echo $t['item_name']; ?></th>
                                        <th><?php echo $t['category']; ?></th>
                                        <th><?php echo $t['quantity']; ?></th>
                                        <th><?php echo $t['submitted_on']; ?></th>
                                        <th class="text-end"><?php echo $t['actions']; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($rows) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($rows)): ?>
                                            <tr>
                                                <td class="fw-bold">
                                                    <?= htmlspecialchars($r['contributor_name']) ?>
                                                </td>
                                                <td class="text-primary fw-medium">
                                                    <?= htmlspecialchars($r['title']) ?>
                                                </td>
                                                <td><span class="badge bg-light text-dark border">
                                                        <?= htmlspecialchars($r['category']) ?>
                                                    </span></td>
                                                <td>
                                                    <?= number_format($r['quantity'], 2) ?> <span class="text-muted small">
                                                        <?= $r['unit'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-secondary small">
                                                    <?= date('d M Y', strtotime($r['created_at'])) ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <form method="POST" class="needs-validation" novalidate>
                                                            <input type="hidden" name="contribution_id" value="<?= $r['id'] ?>">
                                                            <input type="hidden" name="action" value="approved">
                                                            <button type="submit" class="btn-ant-success"><?php echo $t['approve']; ?></button>
                                                        </form>
                                                        <form method="POST" class="needs-validation" novalidate>
                                                            <input type="hidden" name="contribution_id" value="<?= $r['id'] ?>">
                                                            <input type="hidden" name="action" value="rejected">
                                                            <button type="submit" class="btn-ant-reject"><?php echo $t['reject']; ?></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-clipboard-check fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_pending_contributions']; ?>
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
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>
