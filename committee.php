<?php
// =========================================================
// 1. SESSION + LANGUAGE
// =========================================================
session_start(); // Start session for language support (even if no login)

// Adjust path to lang.php as needed
require __DIR__ . '/includes/lang.php';

// =========================================================
// 2. DATABASE CONNECTION
// =========================================================
$dbPath = __DIR__ . '/config/db.php';
if (!file_exists($dbPath)) {
    die("Database config missing.");
}
require $dbPath;

// Handle Variable Name Mismatch (if your db.php uses $conn, but logic uses $con)
if (!isset($con) && isset($conn)) {
    $con = $conn;
}

// =========================================================
// 3. INPUT PARAMETERS (SEARCH & FILTER)
// =========================================================
$search = trim($_GET['search'] ?? '');
$roleId = trim($_GET['role'] ?? '');

// =========================================================
// 4. FETCH ROLES (FOR FILTER DROPDOWN)
// =========================================================
$userRoles = [];
// Assuming your roles table has 'id' and 'name' columns based on previous context
// If column is 'role_name', change 'name' to 'role_name' below
$roleQuery = mysqli_query($con, "SELECT id, name FROM roles ORDER BY name ASC");

if ($roleQuery) {
    while ($r = mysqli_fetch_assoc($roleQuery)) {
        $userRoles[] = $r;
    }
}

// =========================================================
// 5. BUILD MAIN QUERY (COMMITTEE MEMBERS)
// =========================================================
// Only fetch users who have a role (INNER JOIN)
$sql = "
    SELECT 
        u.id,
        u.first_name,
        u.last_name,
        u.photo,
        r.name as role_name
    FROM users u
    JOIN user_roles ur ON ur.user_id = u.id
    JOIN roles r ON r.id = ur.role_id
    WHERE r.name != 'customer' 
";

// ---------- SEARCH FILTER ----------
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($con, $search);
    $sql .= " AND (u.first_name LIKE '%$safeSearch%' OR u.last_name LIKE '%$safeSearch%')";
}

// ---------- ROLE FILTER ----------
if ($roleId !== '') {
    $safeRole = mysqli_real_escape_string($con, $roleId);
    $sql .= " AND r.id = '$safeRole'";
}

// ---------- ORDER ----------
$sql .= " ORDER BY r.name, u.first_name";

// =========================================================
// 6. EXECUTE QUERY
// =========================================================
$committeeMembers = [];
$result = mysqli_query($con, $sql);

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Fallback for photo
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
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        /* ===============================
           THEME VARIABLES
        =============================== */
        :root {
            --shiva-blue-light: #e3f2fd;
            --shiva-blue-deep: #1565c0;
            --shiva-saffron: #ff9800;
            --shiva-saffron-hover: #f57c00;
            --shiva-saffron-light: #fff3e0;
            --text-dark: #2c3e50;
            --text-muted: #607d8b;
            --bg-body: #fdfbf7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        h4,
        .navbar-brand {
            font-family: 'Playfair Display', serif;
        }

        /* Navbar (Embedded for consistency) */
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--shiva-saffron) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
        }

        .nav-link:hover {
            color: var(--shiva-saffron);
        }

        /* Hero Section */
        .page-header {
            background: linear-gradient(135deg, #ffffff 0%, var(--shiva-blue-light) 100%);
            padding: 3rem 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
        }

        /* Member Card */
        .member-card {
            background: #fff;
            border: none;
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            text-align: center;
            border-top: 4px solid var(--shiva-saffron);
        }

        .member-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .member-img-wrapper {
            width: 120px;
            height: 120px;
            margin: 2rem auto 1rem;
            position: relative;
        }

        .member-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid var(--shiva-saffron-light);
            padding: 3px;
        }

        .role-badge {
            background-color: var(--shiva-blue-light);
            color: var(--shiva-blue-deep);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Section */
        .filter-card {
            background: white;
            border-radius: 50px;
            padding: 10px 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 3rem;
            border: 1px solid #eee;
        }

        .form-control,
        .form-select {
            border: none;
            box-shadow: none;
            background: transparent;
        }

        .form-control:focus,
        .form-select:focus {
            box-shadow: none;
        }

        .btn-saffron {
            background-color: var(--shiva-saffron);
            color: white;
            border-radius: 50px;
            padding: 8px 25px;
            font-weight: 600;
        }

        .btn-saffron:hover {
            background-color: var(--shiva-saffron-hover);
            color: white;
        }

        /* Footer */
        footer {
            background-color: #263238;
            color: #cfd8dc;
            padding: 1.5rem 0;
            margin-top: auto;
        }

        body {
            -webkit-user-select: none;
            /* Chrome, Safari */
            -moz-user-select: none;
            /* Firefox */
            -ms-user-select: none;
            /* IE/Edge */
            user-select: none;
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-brightness-high-fill me-2"></i><?php echo $t['title']; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php"><?php echo $t['home']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="auth/login.php"><?php echo $t['login']; ?></a></li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="page-header text-center">
        <div class="container">
            <h1 class="display-5 fw-bold mb-2"><?php echo $t['committee']; ?></h1>
            <p class="text-muted lead">Dedicated sevaks serving the temple and community</p>
        </div>
    </header>

    <div class="container pb-5">

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <form method="GET" action="" class="filter-card d-flex flex-wrap align-items-center gap-2">

                    <div class="d-flex align-items-center flex-grow-1 border-end pe-2">
                        <i class="bi bi-search text-muted ms-2"></i>
                        <input type="text" name="search" class="form-control" placeholder="Search member name..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="d-flex align-items-center flex-grow-1">
                        <i class="bi bi-person-badge text-muted ms-2"></i>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <?php foreach ($userRoles as $role): ?>
                                <option value="<?php echo $role['id']; ?>" <?php echo ($roleId == $role['id']) ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($role['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-saffron">Filter</button>

                    <?php if (!empty($search) || !empty($roleId)): ?>
                        <a href="committee.php" class="btn btn-outline-secondary rounded-pill px-3" title="Clear Filters">
                            <i class="bi bi-x-lg"></i>
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="row g-4">

            <?php if (count($committeeMembers) > 0): ?>

                <?php foreach ($committeeMembers as $member): ?>
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="member-card shadow-sm">
                            <div class="member-img-wrapper">
                                <img src="<?php echo htmlspecialchars($member['photo']); ?>"
                                    alt="<?php echo htmlspecialchars($member['name']); ?>" class="member-img">
                            </div>
                            <div class="card-body pt-0 pb-4">
                                <h5 class="card-title fw-bold mb-2">
                                    <?php echo htmlspecialchars($member['name']); ?>
                                </h5>
                                <span class="role-badge">
                                    <?php echo htmlspecialchars($member['role']); ?>
                                </span>
                                <div class="mt-3 opacity-50">
                                    <i class="bi bi-envelope me-2"></i>
                                    <i class="bi bi-telephone"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center py-5">
                    <div class="text-muted opacity-50 mb-3">
                        <i class="bi bi-people" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="fw-bold text-muted">No members found</h4>
                    <p class="text-muted">Try adjusting your search filters.</p>
                    <a href="committee.php" class="btn btn-outline-primary rounded-pill mt-2">View All Members</a>
                </div>

            <?php endif; ?>

        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <small class="text-white-50">
                &copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?> |
                <span class="text-white"><?php echo $t['copyright']; ?></span>
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>