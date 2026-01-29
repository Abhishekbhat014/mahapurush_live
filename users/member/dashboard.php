<?php
// =========================================================
// 1. SESSION + AUTH + ROLE CHECK
// =========================================================
session_start();
require __DIR__ . '/../../includes/lang.php';

// Check if logged in
if (
    !isset($_SESSION['logged_in']) ||
    $_SESSION['logged_in'] !== true ||
    (isset($_SESSION['role']) && $_SESSION['role'] !== 'member')
) {
    header("Location: ../../auth/login.php");
    exit;
}

// =========================================================
// 2. DATABASE CONNECTION
// =========================================================
$dbPath = __DIR__ . '/../../config/db.php';
if (!file_exists($dbPath)) {
    die("Database connection file missing.");
}
require $dbPath;

// =========================================================
// FETCH LOGGED-IN USER PHOTO
// =========================================================
$loggedInUserPhoto = '../../assets/images/default-user.png';

if (isset($_SESSION['user_id'])) {
    $uid = (int) $_SESSION['user_id'];

    $photoQuery = mysqli_query(
        $con,
        "SELECT photo, first_name, last_name 
         FROM users 
         WHERE id = '$uid' 
         LIMIT 1"
    );

    if ($photoQuery && mysqli_num_rows($photoQuery) === 1) {
        $u = mysqli_fetch_assoc($photoQuery);

        if (!empty($u['photo'])) {
            $loggedInUserPhoto = '../../uploads/users/' . basename($u['photo']);
        } else {
            $loggedInUserPhoto =
                'https://ui-avatars.com/api/?name=' .
                urlencode($u['first_name'] . ' ' . $u['last_name']) .
                '&background=random';
        }
    }
}

// =========================================================
// 3. FETCH TEMPLE INFO
// =========================================================
$temple = null;
$templeQuery = mysqli_query($con, "SELECT * FROM temple_info LIMIT 1");

if ($templeQuery && mysqli_num_rows($templeQuery) > 0) {
    $temple = mysqli_fetch_assoc($templeQuery);
}

// =========================================================
// 4. FETCH ALL MEMBERS (Added u.photo)
// =========================================================
$members = [];
$sqlMembers = "
    SELECT u.id, CONCAT(u.first_name, ' ', u.last_name) AS name, u.email, u.phone, u.created_at, u.photo 
    FROM users u
    INNER JOIN user_roles ur ON u.id = ur.user_id
    INNER JOIN roles r ON ur.role_id = r.id
    
    ORDER BY u.first_name ASC
";

$memberQuery = mysqli_query($con, $sqlMembers);

if ($memberQuery) {
    while ($row = mysqli_fetch_assoc($memberQuery)) {
        // Prepare photo path (Logic unchanged)
        $row['photo'] = !empty($row['photo'])
            ? '../../uploads/users/' . $row['photo']
            : 'https://ui-avatars.com/api/?name=' . urlencode($row['name']) . '&background=random';

        $members[] = $row;
    }
}

// =========================================================
// 5. FETCH SINGLE MEMBER (Added u.photo)
// =========================================================
$viewMember = null;
if (isset($_GET['view_member'])) {
    $memberId = (int) $_GET['view_member'];

    $sqlView = "
    SELECT 
        u.id,
        CONCAT(u.first_name, ' ', u.last_name) AS name,
        u.email,
        u.phone,
        u.created_at,
        u.photo,
        r.name AS role_name
    FROM users u
    INNER JOIN user_roles ur ON ur.user_id = u.id
    INNER JOIN roles r ON r.id = ur.role_id
    WHERE u.id = '$memberId'
    LIMIT 1
";


    $viewQuery = mysqli_query($con, $sqlView);

    $viewMember['role_name'] = ucfirst($viewMember['role_name'] ?? "Temple Member");

    if ($viewQuery && mysqli_num_rows($viewQuery) === 1) {
        $viewMember = mysqli_fetch_assoc($viewQuery);
        // Prepare photo path (Logic unchanged)
        $viewMember['photo'] = !empty($viewMember['photo'])
            ? '../../uploads/users/' . $viewMember['photo']
            : 'https://ui-avatars.com/api/?name=' . urlencode($viewMember['name']) . '&background=random';

    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - <?php echo $t['title']; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        /* ===============================
           SHIVA THEME VARIABLES
        =============================== */
        :root {
            --shiva-blue-light: #e3f2fd;
            --shiva-blue-deep: #1565c0;
            --shiva-saffron: #ff9800;
            --shiva-saffron-light: #fff3e0;
            --text-dark: #2c3e50;
            --text-muted: #607d8b;
            --bg-body: #fdfbf7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, var(--bg-body) 0%, var(--shiva-blue-light) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
        }

        h1,
        h2,
        h3,
        h4,
        .navbar-brand {
            font-family: 'Playfair Display', serif;
        }

        /* Navbar */
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

        /* Dashboard Header */
        .dash-header {
            padding: 40px 0;
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        /* Cards */
        .dash-card {
            background: white;
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border-top: 4px solid var(--shiva-saffron);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            padding: 1.25rem 1.5rem;
            font-family: 'Playfair Display', serif;
            font-weight: 700;
            color: var(--shiva-blue-deep);
            font-size: 1.1rem;
        }

        /* Tables */
        .table thead th {
            background-color: var(--shiva-blue-light);
            color: var(--shiva-blue-deep);
            border: none;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table tbody tr:hover {
            background-color: rgba(255, 152, 0, 0.03);
        }

        /* Avatar Styling */
        .avatar-sm {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--shiva-blue-light);
        }

        .avatar-xl {
            width: 130px;
            height: 130px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        /* Buttons */
        .btn-saffron {
            background-color: var(--shiva-saffron);
            color: white;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 500;
            border: none;
        }

        .btn-saffron:hover {
            background-color: #f57c00;
            color: white;
        }

        .btn-outline-blue {
            border-color: var(--shiva-blue-deep);
            color: var(--shiva-blue-deep);
            border-radius: 50px;
        }

        .btn-outline-blue:hover {
            background-color: var(--shiva-blue-deep);
            color: white;
        }

        /* Footer */
        footer {
            background-color: #263238;
            color: #eceff1;
            padding: 2rem 0;
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
            <a class="navbar-brand" href="../../index.php">
                <i class="bi bi-brightness-high-fill me-2"></i><?php echo $t['title']; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="../../index.php"><?php echo $t['home']; ?></a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-danger btn-sm rounded-pill px-3" href="../../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-1"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="dash-header">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <h2 class="fw-bold mb-1">Member Dashboard</h2>
                    <p class="text-muted mb-0">Welcome back,
                        <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Member'); ?>
                    </p>
                </div>
                <div class="icon-circle bg-light text-primary rounded-circle p-3 d-none d-md-block">

                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" alt="Profile Photo" class="avatar-xl mb-3">

                </div>
            </div>
        </div>
    </header>

    <div class="container pb-5">

        <div class="dash-card mb-4">
            <div class="card-header">
                <i class="bi bi-building me-2"></i> Temple Information
            </div>
            <div class="card-body p-4">
                <?php if ($temple): ?>
                    <h4 class="fw-bold text-dark mb-3"><?= htmlspecialchars($temple['temple_name']) ?></h4>
                    <p class="text-muted"><?= htmlspecialchars($temple['description']) ?></p>
                    <hr class="text-secondary opacity-25">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong class="text-primary d-block small text-uppercase">Address</strong>
                            <?= htmlspecialchars($temple['address']) ?>
                        </div>
                        <div class="col-md-6">
                            <strong class="text-primary d-block small text-uppercase">Contact</strong>
                            <?= htmlspecialchars($temple['contact']) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">
                        <i class="bi bi-exclamation-circle me-1"></i> Temple information not available.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="dash-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-people me-2"></i> Temple Members Directory</span>
                <span class="badge bg-primary rounded-pill"><?= count($members) ?> Found</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="ps-4" style="width: 50px;">#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $index => $m): ?>
                                    <tr>
                                        <td class="ps-4 fw-bold text-muted"><?= $index + 1 ?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="<?= htmlspecialchars($m['photo']) ?>" alt=""
                                                    class="avatar-sm me-3 shadow-sm">
                                                <span class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="text-muted"><?= htmlspecialchars($m['email']) ?></td>
                                        <td class="text-end pe-4">
                                            <a href="dashboard.php?view_member=<?= $m['id'] ?>"
                                                class="btn btn-sm btn-outline-blue rounded-pill px-3">
                                                View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">
                                        <i class="bi bi-people display-4 d-block mb-3 opacity-25"></i>
                                        No other members found in this temple group.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">

                <div class="modal-body p-0">
                    <div class="text-center pt-5 pb-3"
                        style="background: linear-gradient(135deg, var(--shiva-saffron-light) 0%, #fff 100%);">
                        <?php if ($viewMember): ?>
                            <img src="<?= htmlspecialchars($viewMember['photo']) ?>" alt="Profile Photo"
                                class="avatar-xl mb-3">
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($viewMember['name']) ?></h4>
                            <span class="badge bg-warning text-dark rounded-pill px-3">
                                <?= htmlspecialchars($viewMember['role_name']) ?>
                            </span>

                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <?php if ($viewMember): ?>
                            <div class="list-group list-group-flush rounded-3 border">
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-muted small fw-bold text-uppercase"><i
                                            class="bi bi-envelope me-2"></i> Email</span>
                                    <span class="text-dark"><?= htmlspecialchars($viewMember['email']) ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-muted small fw-bold text-uppercase"><i
                                            class="bi bi-telephone me-2"></i> Phone</span>
                                    <span class="text-dark"><?= htmlspecialchars($viewMember['phone']) ?></span>
                                </div>
                                <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                    <span class="text-muted small fw-bold text-uppercase"><i
                                            class="bi bi-calendar-check me-2"></i> Joined</span>
                                    <span
                                        class="text-dark"><?= date("d M Y", strtotime($viewMember['created_at'])) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="modal-footer border-0 bg-light justify-content-center py-3">
                    <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill px-5">Close</a>
                </div>
            </div>
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

    <?php if ($viewMember): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var myModal = new bootstrap.Modal(document.getElementById('profileModal'));
                myModal.show();
            });
        </script>
    <?php endif; ?>

</body>

</html>