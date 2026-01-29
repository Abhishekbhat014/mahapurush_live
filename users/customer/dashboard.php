<?php
require '../../includes/lang.php';

$currLang = $_SESSION['lang'] ?? 'en';

/* -------------------------
   AUTH CHECK
------------------------- */
if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

/* -------------------------
   DATABASE CONNECTION (FIXED PATH)
------------------------- */
// dashboard.php → customer → users → project root → config
$dbPath = __DIR__ . '/../../config/db.php';

if (!file_exists($dbPath)) {
    die('Database configuration file missing.');
}
require $dbPath;

/* -------------------------
   USER DATA (FIXED)
------------------------- */
$userId = $_SESSION['user_id'] ?? 0;
$userName = $_SESSION['user_name'] ?? "User";

/* -------------------------
   FETCH DONATIONS
------------------------- */
$result = false;

if ($con && $userId > 0) {
    $stmt = $con->prepare("
        SELECT 
            p.amount,
            p.status,
            p.created_at,
            r.receipt_no
        FROM payments p
        LEFT JOIN receipt r ON r.id = p.receipt_id
        WHERE p.user_id = ?
        ORDER BY p.created_at DESC
    ");

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
    }
}
?>


<!DOCTYPE html>
<html lang="<?php echo $currLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['dashboard']; ?> - <?php echo $t['title']; ?></title>

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
            --shiva-saffron-hover: #f57c00;
            --shiva-saffron-light: #fff3e0;
            --text-dark: #2c3e50;
            --text-muted: #607d8b;
            --bg-body: #fdfbf7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, var(--shiva-blue-light) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
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
            transition: color 0.3s;
        }

        .nav-link:hover {
            color: var(--shiva-saffron);
        }

        /* Dashboard Card */
        .dashboard-card {
            background: #ffffff;
            border: none;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.05);
            border-top: 5px solid var(--shiva-saffron);
        }

        /* Badges */
        .receipt-badge {
            background-color: var(--shiva-blue-light);
            color: var(--shiva-blue-deep);
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 8px;
        }

        .bg-success-soft {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .bg-secondary-soft {
            background-color: #e2e3e5;
            color: #41464b;
        }

        /* Buttons */
        .btn-saffron {
            background-color: var(--shiva-saffron);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-saffron:hover {
            background-color: var(--shiva-saffron-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
            color: white;
        }

        .btn-outline-primary {
            border-color: var(--shiva-blue-deep);
            color: var(--shiva-blue-deep);
            border-radius: 50px;
        }

        .btn-outline-primary:hover {
            background-color: var(--shiva-blue-deep);
            color: white;
        }

        /* Table Styling */
        .table thead th {
            background-color: #f8f9fa;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.9rem;
            border-bottom: 2px solid #dee2e6;
        }

        .table tbody td {
            vertical-align: middle;
            color: var(--text-dark);
            padding: 1rem 0.5rem;
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

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-3">
                        <span class="text-muted small"><?php echo $t['welcome']; ?>,
                            <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                    </li>
                    <li class="nav-item">
                        <a href="../../index.php" class="nav-link"><?php echo $t['home']; ?></a>
                    </li>
                    <li class="nav-item dropdown ms-2">
                        <a class="nav-link dropdown-toggle fw-bold text-primary" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            <?php echo ($currLang == 'en') ? 'English' : 'मराठी'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" style="min-width: 100px;">
                            <li><a class="dropdown-item" href="?lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?lang=mr">मराठी</a></li>
                        </ul>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a href="../../auth/logout.php" class="btn btn-sm btn-outline-danger rounded-pill px-3">
                            <?php echo $t['logout']; ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">

        <div class="mb-5 text-center text-md-start">
            <h2 class="fw-bold display-6">
                <?php echo $t['welcome']; ?>, <span
                    class="text-primary"><?php echo htmlspecialchars($userName); ?></span>
            </h2>
            <p class="text-muted lead fs-6">
                <?php echo ($currLang === 'mr') ? 'येथे तुमचा देणगी इतिहास आणि खाते तपशील आहेत.' : 'Here is your donation history and account overview.'; ?>
            </p>
        </div>

        <div class="card dashboard-card">
            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold mb-0">
                        <?php echo $t['your_donations']; ?>
                    </h5>
                    <a href="../donate.php" class="btn btn-sm btn-saffron">
                        <i class="bi bi-plus-lg me-1"></i> <?php echo $t['new_donation']; ?>
                    </a>
                </div>

                <?php if ($result && $result->num_rows > 0) { ?>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th><?php echo $t['receipt_no']; ?></th>
                                    <th><?php echo $t['amount']; ?></th>
                                    <th><?php echo $t['status']; ?></th>
                                    <th><?php echo $t['date']; ?></th>
                                    <th><?php echo $t['action']; ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()) {
                                    // Status Badge Logic
                                    $statusLabel = ucfirst(htmlspecialchars($row['status']));
                                    $statusClass = 'bg-secondary-soft';
                                    if ($row['status'] === 'success') {
                                        $statusClass = 'bg-success-soft';
                                        $statusLabel = $t['success'];
                                    } elseif ($row['status'] === 'pending') {
                                        $statusLabel = $t['pending'];
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge receipt-badge">
                                                #<?php echo htmlspecialchars($row['receipt_no']); ?>
                                            </span>
                                        </td>
                                        <td class="fw-bold">₹<?php echo number_format($row['amount'], 2); ?></td>
                                        <td>
                                            <span class="badge <?php echo $statusClass; ?> rounded-pill px-3">
                                                <?php echo $statusLabel; ?>
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <i class="bi bi-calendar-event me-1"></i>
                                            <?php echo date("d M Y", strtotime($row['created_at'])); ?>
                                        </td>
                                        <td>
                                            <a href="../receipt/view.php?no=<?php echo $row['receipt_no']; ?>"
                                                class="btn btn-sm btn-outline-primary px-3">
                                                <i class="bi bi-file-earmark-text me-1"></i> <?php echo $t['view']; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                <?php } else { ?>

                    <div class="text-center text-muted py-5">
                        <div class="mb-3">
                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                        </div>
                        <h6 class="fw-bold"><?php echo $t['no_data']; ?></h6>
                        <p class="small mb-4"><?php echo $t['start_journey']; ?></p>
                        <a href="../donate.php" class="btn btn-saffron px-4">
                            <?php echo $t['make_donation']; ?>
                        </a>
                    </div>

                <?php } ?>

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

</body>

</html>