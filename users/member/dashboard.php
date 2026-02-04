<?php
session_start();
require __DIR__ . '/../../includes/lang.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$dbPath = __DIR__ . '/../../config/db.php';
require $dbPath;

$uid = (int) $_SESSION['user_id'];

// --- 1. Fetch User Photo ---
$loggedInUserPhoto = '../../assets/images/default-user.png';
$photoQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id = '$uid' LIMIT 1");
if ($u = mysqli_fetch_assoc($photoQuery)) {
    $loggedInUserPhoto = !empty($u['photo']) ? '../../uploads/users/' . basename($u['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($u['first_name'] . ' ' . $u['last_name']) . '&background=random';
}

// --- 2. Fetch Temple Info ---
$temple = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM temple_info LIMIT 1"));

// --- 3. Fetch Pooja Bookings ---
$myPoojaBookings = [];
$resBookings = mysqli_query($con, "SELECT p.*, pt.type AS pooja_name FROM pooja p INNER JOIN pooja_type pt ON pt.id = p.pooja_type_id WHERE p.user_id = '$uid' ORDER BY p.created_at DESC LIMIT 5");
while ($row = mysqli_fetch_assoc($resBookings)) {
    $myPoojaBookings[] = $row;
}

// --- 4. Fetch Donation History (Newly Added) ---
$myDonations = [];
$resDonations = mysqli_query($con, "SELECT py.*, r.receipt_no FROM payments py LEFT JOIN receipt r ON r.id = py.receipt_id WHERE py.user_id = '$uid' ORDER BY py.created_at DESC LIMIT 5");
while ($drow = mysqli_fetch_assoc($resDonations)) {
    $myDonations[] = $drow;
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['member_dashboard']; ?> - <?php echo $t['title']; ?></title>
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
            height: 100%;
            transition: transform 0.3s ease;
        }

        .ant-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-table {
            font-size: 13px;
        }

        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            color: var(--ant-text-sec);
            text-transform: uppercase;
            font-size: 11px;
        }

        .ant-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
        }

        .badge-soft {
            padding: 4px 10px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 11px;
        }

        .status-completed,
        .status-success {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .status-paid {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
        }

        .status-pending {
            background: #fffbe6;
            color: #faad14;
            border: 1px solid #ffe58f;
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
    </style>
</head>

<body>

    <header class="ant-header shadow-sm">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i
                        class="bi bi-list"></i></button>
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['namaste']; ?>, <?= explode(' ', $_SESSION['user_name'])[0] ?>!</h2>
                    <p class="text-secondary mb-0"><?php echo $t['member_dashboard_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['temple_info']; ?></div>
                                <div class="ant-card-body">
                                    <div class="text-center mb-4">
                                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                            style="width:60px; height:60px;">
                                            <i class="bi bi-bank fs-3 text-primary"></i>
                                        </div>
                                        <h6 class="fw-bold mb-1">
                                            <?= htmlspecialchars($temple['temple_name'] ?? $t['temple']) ?>
                                        </h6>
                                        <p class="small text-muted mb-0">
                                            <?= htmlspecialchars($temple['contact'] ?? '') ?>
                                        </p>
                                    </div>
                                    <div class="p-3 rounded-3 bg-light small mb-2"><i
                                            class="bi bi-geo-alt-fill text-primary me-2"></i><?= htmlspecialchars($temple['address'] ?? '') ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head d-flex justify-content-between align-items-center">
                                    <span><?php echo $t['recent_pooja_bookings']; ?></span>
                                    <a href="pooja_book.php"
                                        class="btn btn-link btn-sm text-primary text-decoration-none p-0"><?php echo $t['new']; ?>
                                        <i class="bi bi-plus"></i></a>
                                </div>
                                <div class="ant-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table ant-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $t['pooja']; ?></th>
                                                    <th><?php echo $t['date']; ?></th>
                                                    <th><?php echo $t['fee']; ?></th>
                                                    <th><?php echo $t['status']; ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($myPoojaBookings as $b):
                                                    $sClass = match ($b['status']) { 'completed' => 'status-completed', 'paid' => 'status-paid', default => 'status-pending'};
                                                    ?>
                                                    <tr>
                                                        <td class="fw-bold"><?= htmlspecialchars($b['pooja_name']) ?></td>
                                                        <td><?= date('d M Y', strtotime($b['pooja_date'])) ?></td>
                                                        <td class="fw-bold text-dark">₹<?= number_format($b['fee'], 0) ?>
                                                        </td>
                                                        <td><span class="badge-soft <?= $sClass ?>">
                                                                <?= $b['status'] === 'completed' ? $t['completed'] : ($b['status'] === 'paid' ? $t['paid'] : $t['pending']) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                                if (empty($myPoojaBookings))
                                                    echo "<tr><td colspan='4' class='text-center py-4 text-muted small'>{$t['no_bookings_found']}</td></tr>"; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="ant-card">
                                <div class="ant-card-head d-flex justify-content-between align-items-center">
                                    <span><?php echo $t['generous_donations']; ?></span>
                                    <a href="../donate.php"
                                        class="btn btn-link btn-sm text-primary text-decoration-none p-0"><?php echo $t['donate_plus']; ?></a>
                                </div>
                                <div class="ant-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table ant-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $t['receipt_no']; ?></th>
                                                    <th><?php echo $t['amount']; ?></th>
                                                    <th><?php echo $t['date']; ?></th>
                                                    <th><?php echo $t['status']; ?></th>
                                                    <th class="text-end"><?php echo $t['action']; ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($myDonations as $don):
                                                    $dStatus = ($don['status'] == 'success') ? 'status-success' : 'status-pending';
                                                    ?>
                                                    <tr>
                                                        <td class="fw-bold text-primary">
                                                            #<?= htmlspecialchars($don['receipt_no'] ?? $t['not_available']) ?></td>
                                                        <td class="fw-bold text-dark">
                                                            ₹<?= number_format($don['amount'], 2) ?></td>
                                                        <td class="text-secondary">
                                                            <?= date('d M Y', strtotime($don['created_at'])) ?>
                                                        </td>
                                                        <td><span
                                                                class="badge-soft <?= $dStatus ?>"><?= $don['status'] === 'success' ? $t['success'] : $t['pending']; ?></span>
                                                        </td>
                                                        <td class="text-end">
                                                            <a href="receipt_download.php?id=13"
                                                                class="btn btn-light btn-sm border rounded-pill px-3 fw-medium">
                                                                <i class="bi bi-file-earmark-pdf me-1"></i> <?php echo $t['view']; ?>
                                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                                if (empty($myDonations))
                                                    echo "<tr><td colspan='5' class='text-center py-5 text-muted small'>{$t['no_donations_yet']}</td></tr>"; ?>
                                            </tbody>
                                        </table>
                                    </div>
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
