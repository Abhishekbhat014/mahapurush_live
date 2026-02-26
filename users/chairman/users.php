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
$currentPage = 'users.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

// --- FETCH ALL ROLES FOR MODAL ---
$roles = [];
$roleRes = mysqli_query($con, "SELECT id, name FROM roles ORDER BY name");
if ($roleRes) {
    while ($r = mysqli_fetch_assoc($roleRes)) {
        $roles[] = $r;
    }
}

// --- FETCH ALL USERS ---
$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, 
               GROUP_CONCAT(r.id) as role_ids, 
               GROUP_CONCAT(r.name) as role_names
        FROM users u
        LEFT JOIN user_roles ur ON ur.user_id = u.id
        LEFT JOIN roles r ON r.id = ur.role_id
        GROUP BY u.id
        ORDER BY u.first_name";
$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['user_management'] ?? 'User Management' ?> - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES --- */
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
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

        input, textarea, select {
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
            background: #ffffff;
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

        .role-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            background: #f0f5ff;
            color: var(--ant-primary);
            border: 1px solid #d6e4ff;
            margin-right: 4px;
            margin-bottom: 4px;
            display: inline-block;
        }
        
        /* Modal Checkbox list styling */
        .role-checkbox-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid var(--ant-border-color);
            border-radius: 6px;
            padding: 12px;
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

                <div class="user-pill">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
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
                    <h2 class="fw-bold mb-1"><?= $t['user_management'] ?? 'Users & Committee' ?></h2>
                    <p class="text-secondary mb-0"><?= $t['user_management_desc'] ?? 'Manage registered users and assign roles.' ?></p>
                </div>

                <div class="p-4 pb-5">
                    
                    <!-- Alert Placeholder -->
                    <div id="alertBox" class="alert d-none" role="alert"></div>

                    <div class="ant-card">
                        
                        <div class="filter-bar d-flex align-items-center justify-content-between p-3 border-bottom" style="background: #fafafa; gap: 12px; flex-wrap: wrap;">
                            <div class="filter-label text-muted small fw-bold text-uppercase" style="letter-spacing: 0.5px;"><?php echo $t['filter_by_role'] ?? 'Filter by Role'; ?></div>
                            <div class="d-flex align-items-center gap-2">
                                <select id="roleFilter" class="form-select form-select-sm" style="min-width: 200px;">
                                    <option value="all"><?php echo $t['all_roles'] ?? 'All Roles'; ?></option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role['name']) ?>">
                                            <?= htmlspecialchars(ucfirst($role['name'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?= $t['user'] ?? 'User ID' ?></th>
                                        <th><?= $t['full_name'] ?? 'Name' ?></th>
                                        <th><?= $t['email_label'] ?? 'Email' ?></th>
                                        <th><?= $t['phone_label'] ?? 'Phone' ?></th>
                                        <th><?= $t['role_name'] ?? 'Roles' ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr style="cursor: pointer;" data-roles="<?= htmlspecialchars($row['role_names']) ?>" onclick="openRolesModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['role_ids'] ?? '') ?>')">
                                                <td class="text-muted small">#<?= htmlspecialchars($row['id']) ?></td>
                                                <td class="fw-bold text-dark">
                                                    <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= htmlspecialchars($row['email']) ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= htmlspecialchars($row['phone']) ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($row['role_names']) {
                                                        $roles_arr = explode(',', $row['role_names']);
                                                        foreach ($roles_arr as $rname) {
                                                            echo '<span class="role-badge">' . htmlspecialchars($rname) . '</span>';
                                                        }
                                                    } else {
                                                        echo '<span class="text-muted small">-</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-people fs-1 opacity-25 d-block mb-3"></i>
                                                <?= $t['no_users_found'] ?? 'No users found.' ?>
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

    <!-- Edit Roles Modal -->
    <div class="modal fade" id="editRolesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: var(--ant-radius); border: none;">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold"><?= $t['assign_roles'] ?? 'Assign Roles' ?></h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateRolesForm">
                        <input type="hidden" name="user_id" id="editUserId">
                        <div class="mb-3">
                            <label class="form-label fw-semibold"><?= $t['select_roles'] ?? 'Select Roles' ?></label>
                            <div class="role-checkbox-list">
                                <?php foreach ($roles as $role): ?>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input role-checkbox" type="checkbox" 
                                            name="roles[]" value="<?= $role['id'] ?>" id="role_<?= $role['id'] ?>">
                                        <label class="form-check-label text-capitalize" for="role_<?= $role['id'] ?>">
                                            <?= htmlspecialchars($role['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal"><?= $t['cancel'] ?? 'Cancel' ?></button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="saveRolesBtn" onclick="saveRoles()">
                        <?= $t['update_roles'] ?? 'Update Roles' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editRolesModal = new bootstrap.Modal(document.getElementById('editRolesModal'));
        
        function openRolesModal(userId, roleIdsStr) {
            document.getElementById('editUserId').value = userId;
            
            // Uncheck all first
            document.querySelectorAll('.role-checkbox').forEach(cb => cb.checked = false);
            
            if (roleIdsStr) {
                const activeRoleIds = roleIdsStr.split(',');
                activeRoleIds.forEach(id => {
                    const cb = document.getElementById('role_' + id);
                    if (cb) cb.checked = true;
                });
            }
            
            editRolesModal.show();
        }

        function saveRoles() {
            const btn = document.getElementById('saveRolesBtn');
            const form = document.getElementById('updateRolesForm');
            const formData = new FormData(form);
            
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...';

            fetch('update_roles.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    showAlert('success', '<?= $t['roles_updated_success'] ?? 'Roles updated successfully.' ?>');
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    showAlert('danger', data.message || '<?= $t['roles_update_failed'] ?? 'Failed to update roles.' ?>');
                    btn.disabled = false;
                    btn.innerHTML = '<?= $t['update_roles'] ?? 'Update Roles' ?>';
                }
            })
            .catch(err => {
                showAlert('danger', '<?= $t['something_went_wrong'] ?? 'Something went wrong.' ?>');
                btn.disabled = false;
                btn.innerHTML = '<?= $t['update_roles'] ?? 'Update Roles' ?>';
                console.error(err);
            });
        }

        function showAlert(type, message) {
            const box = document.getElementById('alertBox');
            box.className = `alert alert-${type}`;
            box.innerHTML = message;
            box.classList.remove('d-none');
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        // --- Role Filter Logic ---
        document.addEventListener('DOMContentLoaded', function() {
            const roleFilter = document.getElementById('roleFilter');
            const userRows = document.querySelectorAll('tbody tr[data-roles]');

            if (roleFilter) {
                roleFilter.addEventListener('change', function() {
                    const selectedRole = this.value.toLowerCase();
                    
                    userRows.forEach(row => {
                        const rolesStr = row.getAttribute('data-roles') || '';
                        const rolesArray = rolesStr.split(',').map(r => r.trim().toLowerCase());
                        
                        if (selectedRole === 'all') {
                            row.style.display = '';
                        } else {
                            if (rolesArray.includes(selectedRole)) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        }
                    });
                });
            }
        });
    </script>
</body>

</html>
