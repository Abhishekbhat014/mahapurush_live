<?php
session_start();
require __DIR__ . '/../../includes/lang.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || (isset($_SESSION['role']) && $_SESSION['role'] !== 'member')) {
    header("Location: ../../auth/login.php");
    exit;
}

require __DIR__ . '/../../config/db.php';

$uid = (int) $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// --- Handle Booking ---
if (isset($_POST['book_pooja'])) {
    $poojaTypeId = (int) $_POST['pooja_type_id'];
    $poojaDate = mysqli_real_escape_string($con, $_POST['pooja_date']);
    $timeSlot = mysqli_real_escape_string($con, $_POST['time_slot']);
    $description = mysqli_real_escape_string($con, $_POST['notes']);

    if (empty($poojaTypeId) || empty($poojaDate)) {
        $errorMsg = $t['err_select_pooja_date'];
    } else {
        $fee = 0;
        $feeQuery = mysqli_query($con, "SELECT fee FROM pooja_type WHERE id = '$poojaTypeId' LIMIT 1");
        if ($feeQuery && $f = mysqli_fetch_assoc($feeQuery)) {
            $fee = $f['fee'] ?? 0;
        }

        $sql = "INSERT INTO pooja (user_id, pooja_type_id, pooja_date, time_slot, description, fee, status)
                VALUES ('$uid', '$poojaTypeId', '$poojaDate', '$timeSlot', '$description', '$fee', 'pending')";

        if (mysqli_query($con, $sql)) {
            $successMsg = $t['pooja_request_submitted'];
        } else {
            $errorMsg = $t['booking_failed_try_again'];
        }
    }
}

// --- Fetch Available Poojas ---
$poojaList = [];
$q = mysqli_query($con, "SELECT id, type, fee FROM pooja_type ORDER BY type ASC");
while ($row = mysqli_fetch_assoc($q)) {
    $poojaList[] = $row;
}

// --- Header Profile Photo ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
if ($uRow = mysqli_fetch_assoc($uQuery)) {
    $loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['book_pooja']; ?> - <?php echo $t['title']; ?></title>
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
body {
            -webkit-user-select: none;
            /* Chrome, Safari */
            -moz-user-select: none;
            /* Firefox */
            -ms-user-select: none;
            /* IE/Edge */
            user-select: none;
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
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
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

        .form-control:focus {
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
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
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
            <div class="user-pill shadow-sm">
                <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">

            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['book_pooja']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['pooja_schedule_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">

                            <?php if ($successMsg): ?>
                                <div class="alert border-0 shadow-sm d-flex align-items-center mb-4"
                                    style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                                    <i class="bi bi-check-circle-fill me-2"></i> <?= $successMsg ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($errorMsg): ?>
                                <div class="alert border-0 shadow-sm d-flex align-items-center mb-4"
                                    style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $errorMsg ?>
                                </div>
                            <?php endif; ?>

                            <div class="ant-card shadow-sm">
                                <div class="ant-card-head"><?php echo $t['pooja_details']; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST">
                                        <div class="mb-4">
                                            <label class="form-label"><?php echo $t['select_pooja_service_label']; ?> <span
                                                    class="text-danger">*</span></label>
                                            <select name="pooja_type_id" class="form-select" required>
                                                <option value="" disabled selected><?php echo $t['choose_service']; ?></option>
                                                <?php foreach ($poojaList as $p): ?>
                                                    <option value="<?= $p['id'] ?>">
                                                        <?= htmlspecialchars($p['type']) ?>
                                                        <?= $p['fee'] ? '(₹' . number_format($p['fee']) . ')' : '' ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label"><?php echo $t['preferred_date']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="pooja_date" class="form-control"
                                                    min="<?= date('Y-m-d') ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label"><?php echo $t['time_slot']; ?></label>
                                                <select name="time_slot" class="form-select">
                                                    <option value="morning"><?php echo $t['morning']; ?></option>
                                                    <option value="afternoon"><?php echo $t['afternoon']; ?></option>
                                                    <option value="evening"><?php echo $t['evening']; ?></option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label"><?php echo $t['special_requests_notes']; ?></label>
                                            <textarea name="notes" class="form-control" rows="4"
                                                placeholder="<?php echo $t['special_requests_placeholder']; ?>"></textarea>
                                        </div>

                                        <div class="d-flex gap-3 justify-content-end mt-2">
                                            <a href="dashboard.php" class="btn btn-light px-4 border"
                                                style="border-radius: 8px;"><?php echo $t['cancel']; ?></a>
                                            <button type="submit" name="book_pooja" class="ant-btn-primary"><?php echo $t['confirm_request']; ?></button>
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
