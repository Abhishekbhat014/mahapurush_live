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
$currentPage = 'contributions.php';
$success = '';
$error = '';

// --- Header Identity Logic (session cached) ---
$loggedInUserPhoto = get_user_avatar_url('../../');

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
$sql = "SELECT c.id, c.title, c.quantity, c.unit, c.created_at, ct.type AS category, c.contributor_name, c.description, u.phone, u.first_name, u.last_name
        FROM contributions c
        JOIN contribution_type ct ON ct.id = c.contribution_type_id
        LEFT JOIN users u ON u.id = c.added_by
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
                                        <?php while ($r = mysqli_fetch_assoc($rows)): 
                                                $fullName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                                            ?>
                                            <tr style="cursor: pointer;" onclick="showContributionModal('<?= htmlspecialchars(addslashes($r['contributor_name'])) ?>', '<?= htmlspecialchars(addslashes($fullName)) ?>', '<?= htmlspecialchars(addslashes($r['phone'] ?? 'N/A')) ?>', '<?= htmlspecialchars(addslashes($r['title'])) ?>', '<?= htmlspecialchars(addslashes($r['category'])) ?>', '<?= number_format($r['quantity'], 2) . ' ' . addslashes($r['unit']) ?>', '<?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>', '<?= htmlspecialchars(addslashes($r['description'] ?? 'No additional details provided.')) ?>')">
                                                <td class="fw-bold">
                                                    <?= htmlspecialchars($r['contributor_name'] ?: 'Unknown') ?>
                                                </td>
                                                <td class="text-primary fw-medium">
                                                    <?= htmlspecialchars($r['title'] ?: 'Unnamed Item') ?>
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
                                                <td class="text-end" onclick="event.stopPropagation();">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <form method="POST" class="needs-validation m-0" novalidate>
                                                            <input type="hidden" name="contribution_id" value="<?= $r['id'] ?>">
                                                            <input type="hidden" name="action" value="approved">
                                                            <button type="submit" class="btn-ant-success"><?php echo $t['approve']; ?></button>
                                                        </form>
                                                        <form method="POST" class="needs-validation m-0" novalidate>
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

    <!-- Details Modal -->
    <div class="modal fade" id="contributionModal" tabindex="-1" aria-labelledby="contributionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold" id="contributionModalLabel"><?php echo $t['contribution_details'] ?? 'Contribution Details'; ?></h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-4">
                    <div class="d-flex flex-column gap-3">
                        
                        <div class="p-3 bg-light rounded">
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Contributor Information</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted d-block">Contributor Name</small>
                                    <span class="fw-bold" id="modalContributor"></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Submitted By</small>
                                    <span class="fw-medium text-dark" id="modalAddedBy"></span>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted d-block">Phone Number</small>
                                    <span class="fw-medium text-dark" id="modalPhone"></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 border rounded">
                            <h6 class="text-muted text-uppercase small fw-bold mb-2">Item Details</h6>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Item Name</small>
                                    <span class="fw-bold text-primary" id="modalItem"></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Category</small>
                                    <span class="badge bg-secondary" id="modalCategory"></span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-6">
                                    <small class="text-muted d-block">Quantity</small>
                                    <span class="fw-bold" id="modalQty"></span>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Submitted On</small>
                                    <span class="fw-medium" id="modalDate"></span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <small class="text-muted d-block">Description</small>
                                    <p class="mb-0 text-dark" id="modalDesc" style="white-space: pre-wrap;"></p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal"><?php echo $t['close'] ?? 'Close'; ?></button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showContributionModal(contributor, addedBy, phone, item, category, qty, date, desc) {
            document.getElementById('modalContributor').innerText = contributor;
            document.getElementById('modalAddedBy').innerText = addedBy;
            document.getElementById('modalPhone').innerText = phone;
            document.getElementById('modalItem').innerText = item;
            document.getElementById('modalCategory').innerText = category;
            document.getElementById('modalQty').innerText = qty;
            document.getElementById('modalDate').innerText = date;
            document.getElementById('modalDesc').innerText = desc;
            
            var myModal = new bootstrap.Modal(document.getElementById('contributionModal'));
            myModal.show();
        }

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

        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>
