<?php
session_start();
require '../../includes/lang.php';
require '../../config/db.php';

$currLang = $_SESSION['lang'] ?? 'en';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? "User";

// fetch pooja types
$poojaTypes = $con->query("SELECT id, type, fee FROM pooja_type ORDER BY type");

// Sidebar Active Logic
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="<?php echo $currLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Pooja - <?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
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
        }

        /* --- Header --- */
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

        /* --- Sidebar Style (Matches Dashboard) --- */
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

        /* --- Form Card --- */
        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 18px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #d9d9d9;
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--ant-primary);
            box-shadow: 0 0 0 2px rgba(22, 119, 255, 0.1);
        }

        .ant-btn-primary {
            background: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 2px 0 rgba(5, 145, 255, 0.1);
        }

        .ant-btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
        }

        .user-pill {
            background: #fff;
            padding: 6px 16px;
            border-radius: 50px;
            border: 1px solid var(--ant-border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ant-divider {
            height: 1px;
            background: var(--ant-border-color);
            margin: 15px 20px;
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
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5">
                    <i class="bi bi-flower1 text-warning me-2"></i><?php echo $t['title']; ?>
                </a>
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-light btn-sm dropdown-toggle border" data-bs-toggle="dropdown">
                        <i class="bi bi-translate me-1"></i> <?php echo strtoupper($currLang); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                        <li><a class="dropdown-item small" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small" href="?lang=mr">मराठी</a></li>
                    </ul>
                </div>
                <div class="vr mx-2 text-muted opacity-25"></div>
                <span class="small fw-bold text-secondary"><?= htmlspecialchars($userName) ?></span>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1">Book a Pooja</h2>
                    <p class="text-secondary mb-0">Select your preferred service and schedule your sacred ceremony.</p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row justify-content-center">
                        <div class="col-xl-8">
                            <div class="ant-card">
                                <div class="ant-card-head">
                                    <i class="bi bi-calendar2-plus me-2 text-primary"></i>New Booking Request
                                </div>
                                <div class="ant-card-body">
                                    <form method="POST" action="pooja-book-save.php">
                                        <div class="mb-4">
                                            <label class="form-label">Pooja Type <span
                                                    class="text-danger">*</span></label>
                                            <select name="pooja_type_id" class="form-select" required>
                                                <option value="" disabled selected>-- Select Pooja Service --</option>
                                                <?php while ($row = $poojaTypes->fetch_assoc()) { ?>
                                                    <option value="<?php echo $row['id']; ?>">
                                                        <?php echo htmlspecialchars($row['type']); ?>
                                                        (₹<?php echo number_format($row['fee'], 2); ?>)
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label">Preferred Date <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="pooja_date" min="<?php echo date('Y-m-d'); ?>"
                                                    class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label">Time Slot</label>
                                                <select name="time_slot" class="form-select">
                                                    <option value="">Any Time</option>
                                                    <option value="morning">Morning</option>
                                                    <option value="afternoon">Afternoon</option>
                                                    <option value="evening">Evening</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label">Description / Special Instructions
                                                (Optional)</label>
                                            <textarea name="description" class="form-control" rows="4"
                                                placeholder="Mention specific names for Sankalp or any other details..."></textarea>
                                        </div>

                                        <div class="d-flex justify-content-end gap-3 pt-2">
                                            <a href="dashboard.php" class="btn btn-light px-4 border"
                                                style="border-radius: 8px;">Cancel</a>
                                            <button type="submit" class="ant-btn-primary px-5">
                                                Confirm Booking
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>