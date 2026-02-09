<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: 0");

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');

$dbPath = __DIR__ . '/../../config/db.php';
require $dbPath;

$uid = (int) $_SESSION['user_id'];
$currLang = $_SESSION['lang'] ?? 'en'; // Ensure lang variable is set

// --- 1. Fetch User Photo (session cached) ---
$loggedInUserPhoto = get_user_avatar_url('../../');

// --- 2. Fetch Temple Info ---
$temple = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM temple_info LIMIT 1"));

// --- 3. Fetch Pending Pooja Requests Count ---
$pendingCount = mysqli_fetch_assoc(mysqli_query($con, "
    SELECT COUNT(*) as cnt FROM pooja WHERE status = 'pending'
"))['cnt'] ?? 0;

// --- 4. Today's Poojas ---
$todaysPoojas = [];
$resToday = mysqli_query($con, "
    SELECT p.pooja_date, pt.type AS pooja_name, u.first_name AS devotee
    FROM pooja p
    INNER JOIN pooja_type pt ON pt.id = p.pooja_type_id
    INNER JOIN users u ON u.id = p.user_id
    WHERE DATE(p.pooja_date) = CURDATE()
    ORDER BY p.pooja_date ASC
");
while ($row = mysqli_fetch_assoc($resToday)) {
    $todaysPoojas[] = $row;
}

// --- 5. Upcoming Events ---
$events = [];
$resEvents = mysqli_query($con, "
    SELECT name, conduct_on FROM events
    WHERE conduct_on >= CURDATE()
    ORDER BY conduct_on ASC LIMIT 5
");
while ($erow = mysqli_fetch_assoc($resEvents)) {
    $events[] = $erow;
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
            -webkit-user-select: none;
            user-select: none;
        }

        /* Allow selection in inputs */
        input,
        textarea,
        select,
        button {
            -webkit-user-select: text;
            user-select: text;
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
            color: #1677ff;
        }

        /* --- Sidebar & Nav --- */
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

        /* --- Cards --- */
        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            height: 100%;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .ant-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-head {
            padding: 16px 24px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 16px;
        }

        .ant-card-body {
            padding: 24px;
        }

        /* --- Tables --- */
        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            color: var(--ant-text-sec);
            text-transform: uppercase;
            font-size: 12px;
        }

        .ant-table td {
            padding: 12px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
            font-size: 14px;
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
                                href="?lang=en"><?php echo $t['lang_english']; ?></a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr"><?php echo $t['lang_marathi_full']; ?></a></li>
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
                    <span
                        class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['namaste']; ?>,
                        <?= explode(' ', $_SESSION['user_name'])[0] ?>!</h2>
                    <p class="text-secondary mb-0">
                        <?php echo $t['committee_dashboard_subtitle']; ?>
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row g-4">

                        <div class="col-lg-4">
                            <div
                                class="ant-card text-center d-flex flex-column justify-content-center align-items-center">
                                <div class="ant-card-body">
                                    <div class="rounded-circle bg-warning bg-opacity-10 p-3 d-inline-block mb-3">
                                        <i class="bi bi-hourglass-split fs-1 text-warning"></i>
                                    </div>
                                    <h3 class="fw-bold mb-1 display-5"><?= $pendingCount ?></h3>
                                    <p class="text-muted mb-0 fw-medium"><?php echo $t['pending_pooja_requests']; ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head">
                                    <i class="bi bi-bank2 me-2 text-primary"></i><?php echo $t['temple_information']; ?>
                                </div>
                                <div class="ant-card-body">
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($temple['temple_name']) ?>
                                    </h5>
                                    <div class="d-flex align-items-start gap-2 mb-2 text-secondary">
                                        <i class="bi bi-geo-alt mt-1"></i>
                                        <span><?= nl2br(htmlspecialchars($temple['address'])) ?></span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 text-secondary">
                                        <i class="bi bi-telephone"></i>
                                        <span><?= htmlspecialchars($temple['contact']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="ant-card">
                                <div class="ant-card-head d-flex justify-content-between align-items-center">
                                    <span><i
                                            class="bi bi-calendar-event me-2 text-primary"></i><?php echo $t['todays_poojas']; ?></span>
                                    <span class="badge bg-light text-dark border"><?= date('d M Y') ?></span>
                                </div>
                                <div class="ant-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table ant-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $t['pooja']; ?></th>
                                                    <th><?php echo $t['devotee']; ?></th>
                                                    <th><?php echo $t['date']; ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($todaysPoojas as $p): ?>
                                                    <tr>
                                                        <td class="fw-bold text-primary">
                                                            <?= htmlspecialchars($p['pooja_name']) ?></td>
                                                        <td>
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary fw-bold"
                                                                    style="width:28px; height:28px; font-size:11px;">
                                                                    <?= strtoupper(substr($p['devotee'], 0, 1)) ?>
                                                                </div>
                                                                <?= htmlspecialchars($p['devotee']) ?>
                                                            </div>
                                                        </td>
                                                        <td class="text-muted">
                                                            <?= date('d M Y', strtotime($p['pooja_date'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($todaysPoojas)): ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center text-muted py-5">
                                                            <i class="bi bi-calendar-x fs-1 opacity-25 d-block mb-2"></i>
                                                            <?php echo $t['no_todays_poojas']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="ant-card">
                                <div class="ant-card-head">
                                    <i class="bi bi-stars me-2 text-primary"></i><?php echo $t['upcoming_events']; ?>
                                </div>
                                <div class="ant-card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table ant-table mb-0">
                                            <thead>
                                                <tr>
                                                    <th><?php echo $t['event_name']; ?></th>
                                                    <th><?php echo $t['scheduled_date']; ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($events as $e): ?>
                                                    <tr>
                                                        <td class="fw-medium"><?= htmlspecialchars($e['name']) ?></td>
                                                        <td class="text-muted">
                                                            <?= date('d M Y', strtotime($e['conduct_on'])) ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                <?php if (empty($events)): ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center text-muted py-5">
                                                            <i
                                                                class="bi bi-calendar-range fs-1 opacity-25 d-block mb-2"></i>
                                                            <?php echo $t['no_upcoming_events_found']; ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
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