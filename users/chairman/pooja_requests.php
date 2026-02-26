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
$currentPage = 'pooja_requests.php';
$currLang = $_SESSION['lang'] ?? 'en';

$loggedInUserPhoto = get_user_avatar_url('../../');

$sql = "SELECT p.id, pt.type AS pooja_name, p.pooja_date, p.time_slot, p.status, p.fee, p.performed_by, u.first_name, u.last_name
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
            color: var(--ant-primary);
        }

        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu { display: block; margin-top: 0; }
            .dropdown .dropdown-menu { display: none; }
            .dropdown:hover>.dropdown-menu { display: block; animation: fadeIn 0.2s ease-in-out; }
        }

        .dropdown-item.active, .dropdown-item:active {
            background-color: var(--ant-primary) !important;
            color: #fff !important;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($r = mysqli_fetch_assoc($result)): ?>
                                            <tr style="cursor: pointer;" onclick="viewPooja(
                                                '<?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?>',
                                                '<?= htmlspecialchars($r['pooja_name']) ?>',
                                                '<?= date('d M Y', strtotime($r['pooja_date'])) ?>',
                                                '<?= htmlspecialchars($r['time_slot'] ?? 'Anytime') ?>',
                                                '<?= htmlspecialchars($r['fee']) ?>',
                                                '<?= htmlspecialchars($r['status']) ?>',
                                                '<?= htmlspecialchars($r['performed_by'] ?? 'Not Assigned') ?>'
                                            )">
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

    <!-- View Pooja Modal -->
    <div class="modal fade" id="viewPoojaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--ant-radius); border: none;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><?php echo $t['pooja_details'] ?? 'Pooja Details'; ?></h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-0"><?php echo $t['devotee_name'] ?? 'Devotee'; ?></label>
                        <div class="fw-medium" id="modalDevotee"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-0"><?php echo $t['pooja_type'] ?? 'Pooja Type'; ?></label>
                        <div class="fw-medium text-primary" id="modalPoojaType"></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="form-label text-muted small mb-0"><?php echo $t['pooja_date'] ?? 'Date'; ?></label>
                            <div class="fw-medium" id="modalDate"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small mb-0"><?php echo $t['time_slot'] ?? 'Time Slot'; ?></label>
                            <div class="fw-medium text-uppercase small" id="modalTimeSlot"></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted small mb-0"><?php echo $t['performed_by'] ?? 'Assigned Priest'; ?></label>
                        <div class="fw-medium" id="modalPriest"></div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <label class="form-label text-muted small mb-0"><?php echo $t['fee'] ?? 'Fee'; ?></label>
                            <div class="fw-bold" id="modalFee"></div>
                        </div>
                        <div class="col-6">
                            <label class="form-label text-muted small mb-0"><?php echo $t['status'] ?? 'Status'; ?></label>
                            <div><span class="badge-status" id="modalStatus"></span></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?php echo $t['close'] ?? 'Close'; ?></button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const viewPoojaModal = new bootstrap.Modal(document.getElementById('viewPoojaModal'));
        
        function viewPooja(devotee, type, date, timeSlot, fee, status, priest) {
            document.getElementById('modalDevotee').innerText = devotee;
            document.getElementById('modalPoojaType').innerText = type;
            document.getElementById('modalDate').innerText = date;
            document.getElementById('modalTimeSlot').innerText = timeSlot;
            document.getElementById('modalFee').innerText = '₹' + parseInt(fee).toLocaleString();
            document.getElementById('modalStatus').innerText = status;
            document.getElementById('modalPriest').innerText = priest;
            
            viewPoojaModal.show();
        }

        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
</body>

</html>
