<?php
// Logic remains identical to your provided PHP...
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
        $photoPath = !empty($row['photo']) ? 'uploads/users/' . $row['photo'] : 'assets/img/default-user.png';
        $committeeMembers[] = [
            'id' => $row['id'],
            'name' => $row['first_name'] . ' ' . $row['last_name'],
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
            transition: 0.2s;
        }

        .search-pill:focus-within {
            border-color: var(--ant-primary);
            box-shadow: 0 0 0 3px rgba(22, 119, 255, 0.15);
            background: #fff;
        }

        .form-control,
        .form-select {
            border: none;
            box-shadow: none !important;
            font-size: 14px;
            background: transparent;
        }

        .btn-ant-primary {
            background: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 8px 24px;
            border-radius: 8px;
            font-weight: 600;
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
            /* Cohesive 32px Padding */
        }

        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--ant-shadow);
        }

        .member-img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 20px;
            border: 3px solid #e6f4ff;
        }

        .role-tag {
            display: inline-block;
            padding: 2px 12px;
            font-size: 11px;
            font-weight: 700;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 20px;
            text-transform: uppercase;
        }

        /* --- Footer spacing helper --- */
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
                    <form method="GET" action="" class="row g-2 align-items-center" id="committeeFilterForm">
                        <div class="col-md-5">
                            <div class="search-pill">
                                <i class="bi bi-search text-muted"></i>
                                <input type="text" name="search" class="form-control"
                                    placeholder="<?php echo $t['search_member_name']; ?>..."
                                    value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-center px-3 border-start">
                            <i class="bi bi-funnel text-muted me-2"></i>
                            <select name="role" class="form-select" id="roleFilterSelect">
                                <option value=""><?php echo $t['all_roles']; ?></option>
                                <?php foreach ($userRoles as $role): ?>
                                    <option value="<?php echo $role['id']; ?>" <?php echo ($roleId == $role['id']) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($role['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex justify-content-end gap-2 px-3">
                            <?php if (!empty($search) || !empty($roleId)): ?>
                                <a href="committee.php" class="btn btn-light rounded-pill"><i class="bi bi-x-lg"></i></a>
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
                            <img src="<?php echo htmlspecialchars($member['photo']); ?>" alt="<?php echo $t['member']; ?>" class="member-img">
                            <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($member['name']); ?></h6>
                            <span class="role-tag mb-3"><?php echo htmlspecialchars($member['role']); ?></span>

                            <div class="d-flex justify-content-center gap-3 mt-2 text-muted opacity-50">
                                <i class="bi bi-envelope fs-5"></i>
                                <i class="bi bi-telephone fs-5"></i>
                            </div>
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
        (function () {
            var roleSelect = document.getElementById('roleFilterSelect');
            var filterForm = document.getElementById('committeeFilterForm');
            if (roleSelect && filterForm) {
                roleSelect.addEventListener('change', function () {
                    filterForm.submit();
                });
            }
        })();
    </script>
</body>

</html>
