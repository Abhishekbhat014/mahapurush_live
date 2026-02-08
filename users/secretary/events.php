<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'events.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

$successMsg = '';
$errorMsg = '';

/* ============================
   HANDLE FORM SUBMISSIONS
============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Create Event
    if (isset($_POST['create_event'])) {
        $name = trim($_POST['name']);
        $duration = (int) $_POST['duration'];
        $date = $_POST['conduct_on']; // Expected YYYY-MM-DD
        $max = (int) $_POST['max_participants'];

        if ($name && $date) {
            $stmt = $con->prepare("INSERT INTO events (name, duration, conduct_on, max_participants, status, created_by, created_at) VALUES (?, ?, ?, ?, 'active', ?, NOW())");
            $stmt->bind_param("sisii", $name, $duration, $date, $max, $uid);

            if ($stmt->execute()) {
                $successMsg = $t['event_created'] ?? 'Event created successfully.';
            } else {
                $errorMsg = $t['err_generic'] ?? 'Failed to create event.';
            }
            $stmt->close();
        } else {
            $errorMsg = $t['err_fill_all'] ?? 'Please fill in all required fields.';
        }
    }

    // 2. Delete Event (Soft Delete)
    if (isset($_POST['delete_event'])) {
        $id = (int) $_POST['event_id'];
        $stmt = $con->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $successMsg = $t['event_deleted'] ?? 'Event cancelled successfully.';
        }
        $stmt->close();
    }
}

/* ============================
   FETCH ACTIVE EVENTS
============================ */
// Fetching active events, ordered by date (nearest first)
$events = mysqli_query($con, "
    SELECT e.*, 
           (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) as current_participants 
    FROM events e 
    WHERE e.status != 'cancelled' 
    ORDER BY e.conduct_on ASC
");
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES (Blue) --- */
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
        select {
            -webkit-user-select: text;
            user-select: text;
        }

        /* --- HEADER STYLES --- */
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

        /* --- PAGE CONTENT --- */
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
            box-shadow: var(--ant-shadow);
            overflow: hidden;
        }

        .ant-card-head {
            padding: 16px 24px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 15px;
            background: #fafafa;
        }

        /* Table Styling */
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

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
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
                                href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr">Marathi</a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['events_management'] ?? 'Events Management'; ?></h2>
                    <p class="text-secondary mb-0">Create and manage temple events and schedules.</p>
                </div>

                <div class="px-4 pb-5">

                    <?php if ($successMsg): ?>
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <div><?= htmlspecialchars($successMsg) ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <div><?= htmlspecialchars($errorMsg) ?></div>
                        </div>
                    <?php endif; ?>

                    <div class="ant-card mb-4">
                        <div class="ant-card-head"><?php echo $t['create_new_event'] ?? 'Create New Event'; ?></div>
                        <div class="p-4">
                            <form method="POST">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-4">
                                        <label
                                            class="form-label"><?php echo $t['event_name'] ?? 'Event Name'; ?></label>
                                        <input type="text" name="name" class="form-control"
                                            placeholder="e.g. Maha Shivratri" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><?php echo $t['date'] ?? 'Date'; ?></label>
                                        <input type="date" name="conduct_on" class="form-control" required
                                            min="<?= date('Y-m-d') ?>">
                                    </div>
                                    <div class="col-md-2">
                                        <label
                                            class="form-label"><?php echo $t['duration_hrs'] ?? 'Duration (Hrs)'; ?></label>
                                        <input type="number" name="duration" class="form-control" placeholder="2"
                                            min="1" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label"><?php echo $t['capacity'] ?? 'Capacity'; ?></label>
                                        <input type="number" name="max_participants" class="form-control"
                                            placeholder="0 = Unlimited">
                                    </div>
                                    <div class="col-md-2">
                                        <button name="create_event" class="btn btn-primary w-100 fw-bold">
                                            <i class="bi bi-plus-lg"></i> <?php echo $t['create'] ?? 'Create'; ?>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="ant-card">
                        <div class="ant-card-head"><?php echo $t['active_events'] ?? 'Active Events'; ?></div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Event Name</th>
                                        <th>Date</th>
                                        <th>Duration</th>
                                        <th>Capacity</th>
                                        <th>Registered</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($events) > 0): ?>
                                        <?php while ($e = mysqli_fetch_assoc($events)): ?>
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <?= htmlspecialchars($e['name']) ?>
                                                </td>
                                                <td>
                                                    <?= date('d M Y', strtotime($e['conduct_on'])) ?>
                                                    <?php if ($e['conduct_on'] == date('Y-m-d')): ?>
                                                        <span class="badge bg-success ms-1" style="font-size: 10px;">TODAY</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= $e['duration'] ?> Hrs</td>
                                                <td>
                                                    <?= $e['max_participants'] == 0 ? '<span class="text-muted">Unlimited</span>' : $e['max_participants'] ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        <i class="bi bi-people-fill me-1 text-primary"></i>
                                                        <?= $e['current_participants'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <form method="POST" style="display:inline;"
                                                        onsubmit="return confirm('<?php echo $t['confirm_delete_event'] ?? 'Are you sure you want to cancel this event?'; ?>');">
                                                        <input type="hidden" name="event_id" value="<?= $e['id'] ?>">
                                                        <button name="delete_event"
                                                            class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                            <i class="bi bi-trash"></i> <?php echo $t['cancel'] ?? 'Cancel'; ?>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar-x fs-1 opacity-25 d-block mb-3"></i>
                                                <?php echo $t['no_events_found'] ?? 'No upcoming events found.'; ?>
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
        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Prevent Resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>