<?php
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'committee.php';
$currLang = $_SESSION['lang'] ?? 'en';

$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo'])
    ? '../../uploads/users/' . basename($uRow['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

$success = '';
$error = '';

// Handle role assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['role_id'])) {
    $targetUserId = (int) $_POST['user_id'];
    $targetRoleId = (int) $_POST['role_id'];

    if ($targetUserId === $uid) {
        $error = $t['cannot_change_own_role'] ?? 'You cannot change your own role.';
    } else {
        $roleCheck = $con->prepare("SELECT id FROM roles WHERE id = ? LIMIT 1");
        $roleCheck->bind_param("i", $targetRoleId);
        $roleCheck->execute();
        $roleOk = $roleCheck->get_result()->num_rows === 1;
        $roleCheck->close();

        if ($roleOk && $targetUserId > 0) {
            $con->begin_transaction();
            try {
                $del = $con->prepare("DELETE FROM user_roles WHERE user_id = ?");
                $del->bind_param("i", $targetUserId);
                $del->execute();
                $del->close();

                $ins = $con->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $ins->bind_param("ii", $targetUserId, $targetRoleId);
                $ins->execute();
                $ins->close();

                $con->commit();
                $success = $t['role_updated'] ?? 'Role updated successfully.';
            } catch (Exception $e) {
                $con->rollback();
                $error = $t['err_generic'] ?? 'Something went wrong.';
            }
        } else {
            $error = $t['invalid_role'] ?? 'Invalid role selection.';
        }
    }
}

$roles = [];
$roleRes = mysqli_query($con, "SELECT id, name FROM roles ORDER BY name");
if ($roleRes) {
    while ($r = mysqli_fetch_assoc($roleRes)) {
        $roles[] = $r;
    }
}

$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, r.name AS role_name, r.id AS role_id
        FROM users u
        JOIN user_roles ur ON ur.user_id = u.id
        JOIN roles r ON r.id = ur.role_id
        ORDER BY r.name, u.first_name";
$result = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['committee'] ?? 'Committee'; ?> - <?= $t['title'] ?></title>
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
            margin-bottom: 0;
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

        .action-btn {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 12px;
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

        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding: 16px 20px;
            border-bottom: 1px solid var(--ant-border-color);
            background: #fafafa;
        }

        .filter-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
                    <i class="bi bi-flower1 text-warning me-2"></i><?= $t['title'] ?>
                </a>
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
                    <h2 class="fw-bold mb-1"><?php echo $t['committee'] ?? 'Committee'; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['committee_subtitle'] ?? 'Manage committee members and roles.'; ?></p>
                </div>

                <div class="p-4 pb-5">
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
                        <div class="filter-bar">
                            <div>
                                <div class="filter-label"><?php echo $t['filter_by_role'] ?? 'Filter by Role'; ?></div>
                            </div>
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
                                        <th><?php echo $t['name'] ?? 'Name'; ?></th>
                                        <th><?php echo $t['role'] ?? 'Role'; ?></th>
                                        <th><?php echo $t['email'] ?? 'Email'; ?></th>
                                        <th><?php echo $t['phone'] ?? 'Phone'; ?></th>
                                        <th class="text-end"><?php echo $t['actions'] ?? 'Actions'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($result) > 0): ?>
                                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                            <tr data-role="<?= htmlspecialchars($row['role_name']) ?>">
                                                <td class="fw-bold"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark border text-uppercase" style="font-size: 10px;">
                                                        <?= htmlspecialchars($row['role_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($row['email']) ?></td>
                                                <td><?= htmlspecialchars($row['phone']) ?></td>
                                                <td class="text-end">
                                                    <?php if ((int) $row['id'] === $uid): ?>
                                                        <span class="text-muted small"><?php echo $t['role_management'] ?? 'Role management'; ?></span>
                                                    <?php else: ?>
                                                        <form method="POST" class="d-inline-flex align-items-center gap-2 m-0">
                                                            <input type="hidden" name="user_id" value="<?= (int) $row['id'] ?>">
                                                            <select name="role_id" class="form-select form-select-sm" style="min-width: 160px;">
                                                                <?php foreach ($roles as $role): ?>
                                                                    <option value="<?= (int) $role['id'] ?>" <?= ((int) $row['role_id'] === (int) $role['id']) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars(ucfirst($role['name'])) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="submit" class="btn btn-primary btn-sm action-btn">
                                                                <?php echo $t['assign'] ?? 'Assign'; ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-people fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_members_found'] ?? 'No members found.'; ?>
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
        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) {
            roleFilter.addEventListener('change', function () {
                const value = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr[data-role]');
                rows.forEach(row => {
                    const role = (row.getAttribute('data-role') || '').toLowerCase();
                    row.style.display = (value === 'all' || role === value) ? '' : 'none';
                });
            });
        }
    </script>
</body>

</html>
