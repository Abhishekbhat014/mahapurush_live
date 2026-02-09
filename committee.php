<?php
// Logic remains identical...
session_start();
require __DIR__ . '/includes/lang.php';
$dbPath = __DIR__ . '/config/db.php';
if (!file_exists($dbPath)) {
    die($t['db_config_missing']);
}
require $dbPath;

if (!isset($con) && isset($conn)) {
    $con = $conn;
}

$search = trim($_GET['search'] ?? '');
$roleId = trim($_GET['role'] ?? '');

$userRoles = [];
$roleQuery = mysqli_query($con, "SELECT id, name FROM roles ORDER BY name ASC");
if ($roleQuery) {
    while ($r = mysqli_fetch_assoc($roleQuery)) {
        $userRoles[] = $r;
    }
}

// Helper to determine the current label for the dropdown
$currentRoleLabel = $t['all_roles'];
if (!empty($roleId)) {
    foreach ($userRoles as $r) {
        if ($r['id'] == $roleId) {
            $currentRoleLabel = ucfirst($r['name']);
            break;
        }
    }
}

$sql = "
    SELECT u.id, u.first_name, u.last_name, u.photo, r.id as role_id, r.name as role_name
    FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE r.name != 'customer' 
";

if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($con, $search);
    $sql .= " AND (u.first_name LIKE '%$safeSearch%' OR u.last_name LIKE '%$safeSearch%')";
}
if ($roleId !== '') {
    $safeRole = mysqli_real_escape_string($con, $roleId);
    $sql .= " AND r.id = '$safeRole'";
}
$sql .= " ORDER BY r.name, u.first_name";

$committeeMembers = [];
$result = mysqli_query($con, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $memberName = trim($row['first_name'] . ' ' . $row['last_name']);
        $photoPath = !empty($row['photo'])
            ? 'uploads/users/' . basename($row['photo'])
            : 'https://ui-avatars.com/api/?name=' . urlencode($memberName) . '&background=random';
        $committeeMembers[] = [
            'id' => $row['id'],
            'name' => $memberName,
            'photo' => $photoPath,
            'role' => ucfirst($row['role_name']),
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['committee']; ?> - <?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f8f9fa;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 70%);
            color: var(--ant-text);
            min-height: 100vh;
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

        /* --- Page Hero --- */
        .ant-page-hero {
            padding: 60px 0;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 40px;
        }

        .ant-page-hero h1 {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #111 0%, #444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* --- Filter Bar --- */
        .filter-container {
            background: linear-gradient(180deg, #ffffff 0%, #f6f9ff 100%);
            padding: 16px;
            border-radius: 18px;
            border: 1px solid var(--ant-border-color);
            box-shadow: var(--ant-shadow);
            margin-bottom: 50px;
        }

        .search-pill {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fbff;
            border: 1px solid #d9e6ff;
            padding: 10px 14px;
            border-radius: 999px;
            transition: all 0.2s ease-in-out;
            height: 100%;
            position: relative;
            /* Essential for dropdown positioning */
        }

        .search-pill:hover {
            border-color: #91caff;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .search-pill:focus-within {
            border-color: var(--ant-primary);
            box-shadow: 0 0 0 3px rgba(22, 119, 255, 0.15);
            background: #fff;
        }

        .form-control {
            border: none;
            box-shadow: none !important;
            font-size: 14px;
            background: transparent;
            width: 100%;
        }

        /* Dropdown Styling override for the filter */
        .search-pill .dropdown-toggle::after {
            margin-left: auto;
            /* Push caret to the right */
        }

        .search-pill .dropdown-menu {
            border-radius: 12px;
            border: 1px solid #eee;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1) !important;
            margin-top: 8px !important;
        }

        .search-pill .dropdown-item {
            font-size: 14px;
            padding: 8px 16px;
            border-radius: 8px;
            margin: 0 4px;
            width: auto;
        }

        .search-pill .dropdown-item:active {
            background-color: var(--ant-primary);
        }

        /* --- Member Card --- */
        .member-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            height: 100%;
            text-align: center;
            padding: 32px;
            position: relative;
            top: 0;
        }

        .member-card:hover {
            top: -8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            border-color: #d6e4ff;
        }

        .member-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #e6f4ff;
            transition: border-color 0.3s;
        }

        .member-card:hover .member-img {
            border-color: var(--ant-primary);
        }

        .role-tag {
            display: inline-block;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 700;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 20px;
            text-transform: uppercase;
        }

        .ant-footer {
            margin-top: 60px;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="ant-page-hero">
        <div class="container">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-bold text-uppercase"
                style="letter-spacing: 1px; font-size: 11px;">
                <?php echo $t['our_dedicated_team']; ?>
            </span>
            <h1><?php echo $t['committee']; ?></h1>
            <p class="text-secondary small mb-0 mx-auto" style="max-width: 600px;">
                <?php echo $t['dedicated_sevaks_serving_the_temple_and_community']; ?>
            </p>
        </div>
    </div>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="filter-container">
                    <form method="GET" action="" class="row g-3 align-items-center needs-validation"
                        id="committeeFilterForm" novalidate>

                        <div class="col-md-7">
                            <div class="search-pill">
                                <i class="bi bi-search text-muted"></i>
                                <input type="text" name="search" class="form-control"
                                    placeholder="<?php echo $t['search_member_name']; ?>..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="search-pill dropdown">
                                <i class="bi bi-funnel text-muted"></i>

                                <input type="hidden" name="role" id="roleInput"
                                    value="<?php echo htmlspecialchars($roleId); ?>">

                                <a href="#"
                                    class="text-decoration-none text-dark w-100 d-flex align-items-center dropdown-toggle"
                                    data-bs-toggle="dropdown" aria-expanded="false">
                                    <span class="ms-2 text-truncate"><?php echo $currentRoleLabel; ?></span>
                                </a>

                                <ul class="dropdown-menu w-100">
                                    <li>
                                        <a class="dropdown-item" href="#"
                                            onclick="selectRole('', '<?php echo $t['all_roles']; ?>')">
                                            <?php echo $t['all_roles']; ?>
                                        </a>
                                    </li>
                                    <?php foreach ($userRoles as $role): ?>
                                        <li>
                                            <a class="dropdown-item" href="#"
                                                onclick="selectRole('<?php echo $role['id']; ?>', '<?php echo ucfirst($role['name']); ?>')">
                                                <?php echo ucfirst($role['name']); ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-1 text-end">
                            <?php if (!empty($search) || !empty($roleId)): ?>
                                <a href="committee.php" class="btn btn-light rounded-circle shadow-sm"
                                    style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                    <i class="bi bi-x-lg"></i>
                                </a>
                            <?php endif; ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <?php if (count($committeeMembers) > 0): ?>
                <?php foreach ($committeeMembers as $member): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="member-card">
                            <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo $t['member']; ?>"
                                class="member-img">
                            <h6 class="fw-bold mb-2 text-dark"><?php echo htmlspecialchars($member['name']); ?></h6>
                            <span class="role-tag"><?php echo htmlspecialchars($member['role']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-people text-muted opacity-25" style="font-size: 4rem;"></i>
                    <h5 class="fw-bold text-muted mt-3"><?php echo $t['no_members']; ?></h5>
                    <p class="small text-muted"><?php echo $t['try_adjusting_search']; ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // JS to handle the custom dropdown selection
        function selectRole(roleId, roleName) {
            // Set the hidden input value
            document.getElementById('roleInput').value = roleId;
            // Submit the form
            document.getElementById('committeeFilterForm').submit();
        }
    </script>
</body>

</html>