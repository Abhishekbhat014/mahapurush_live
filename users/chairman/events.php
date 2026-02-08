<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'events.php';
$currLang = $_SESSION['lang'] ?? 'en';

$loggedInUserPhoto = get_user_avatar_url('../../');

$success = '';
$error = '';

if (isset($_GET['success']) && $_GET['success'] === '1') {
    $success = $t['event_added'] ?? 'Event added successfully.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_event') {
    $name = trim($_POST['name'] ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $conductOn = $_POST['conduct_on'] ?? '';
    $maxParticipants = null;

    if (isset($_POST['max_participants']) && $_POST['max_participants'] !== '') {
        $maxParticipants = (int) $_POST['max_participants'];
        if ($maxParticipants < 0) {
            $maxParticipants = 0;
        }
    }

    if ($name === '' || $conductOn === '') {
        $error = $t['err_fill_all'] ?? 'All fields are required.';
    } else {
        $stmt = $con->prepare("INSERT INTO events (name, duration, conduct_on, max_participants, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssi", $name, $duration, $conductOn, $maxParticipants);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: events.php?success=1");
            exit;
        }
        $stmt->close();
        $error = $t['event_add_failed'] ?? 'Failed to add event. Please try again.';
    }
}

$eventsRes = mysqli_query(
    $con,
    "SELECT e.*, (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count
     FROM events e
     ORDER BY e.conduct_on DESC, e.created_at DESC"
);
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['events'] ?? 'Events'; ?> - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
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
            padding: 36px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 0;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            overflow: hidden;
        }

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

        .action-btn {
            font-size: 12px;
            border-radius: 999px;
            padding: 6px 12px;
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

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.4px;
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
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>"
                                href="?lang=en" aria-current="<?= ($currLang == 'en') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_english']; ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr" aria-current="<?= ($currLang == 'mr') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_marathi_full']; ?>
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="user-pill">
                    <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <div class="vr mx-2 text-muted opacity-25"></div>
                    <a href="../../auth/logout.php" class="text-danger"><i class="bi bi-power"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 ms-auto p-0">
                <div class="dashboard-hero">
                    <h3 class="fw-bold mb-2"><?php echo $t['manage_events'] ?? 'Events'; ?></h3>
                    <p class="text-secondary mb-0">
                        View and manage upcoming temple events, schedules, and related arrangements.
                    </p>
                </div>

                <div class="p-4">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>

                    <div class="ant-card mb-4">
                        <div class="p-4 border-bottom">
                            <h5 class="fw-bold mb-1"><?php echo $t['add_event'] ?? 'Add Event'; ?></h5>
                            <div class="text-muted small"><?php echo $t['event_details'] ?? 'Event details'; ?></div>
                        </div>
                        <div class="p-4">
                            <form method="post" class="row g-3">
                                <input type="hidden" name="action" value="create_event">
                                <div class="col-md-6">
                                    <label class="form-label"><?php echo $t['event_name'] ?? 'Event Name'; ?></label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo $t['event_duration'] ?? 'Duration'; ?></label>
                                    <input type="text" name="duration" class="form-control" placeholder="2 hours">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo $t['event_date'] ?? 'Event Date'; ?></label>
                                    <input type="date" name="conduct_on" class="form-control" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label"><?php echo $t['max_participants'] ?? 'Max Participants'; ?></label>
                                    <input type="number" name="max_participants" class="form-control" min="0" placeholder="0">
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-plus-circle me-1"></i> <?php echo $t['add_event'] ?? 'Add Event'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="ant-card">
                        <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <h5 class="fw-bold mb-0"><?php echo $t['event_list'] ?? 'Event List'; ?></h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['event_name'] ?? 'Event Name'; ?></th>
                                        <th><?php echo $t['event_duration'] ?? 'Duration'; ?></th>
                                        <th><?php echo $t['event_date'] ?? 'Event Date'; ?></th>
                                        <th><?php echo $t['max_participants'] ?? 'Max Participants'; ?></th>
                                        <th><?php echo $t['participants'] ?? 'Participants'; ?></th>
                                        <th><?php echo $t['action'] ?? 'Action'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($eventsRes && mysqli_num_rows($eventsRes) > 0): ?>
                                        <?php while ($event = mysqli_fetch_assoc($eventsRes)): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($event['name']) ?></td>
                                                <td><?= htmlspecialchars($event['duration'] ?: '-') ?></td>
                                                <td>
                                                    <?= $event['conduct_on'] ? date("d M Y", strtotime($event['conduct_on'])) : '-' ?>
                                                </td>
                                                <td><?= ($event['max_participants'] === null || $event['max_participants'] === '') ? '-' : (int) $event['max_participants'] ?></td>
                                                <td><?= (int) $event['participant_count'] ?></td>
                                                <td>
                                                    <a class="btn btn-outline-primary action-btn"
                                                        href="event_participants.php?event_id=<?= (int) $event['id'] ?>">
                                                        <i class="bi bi-people me-1"></i>
                                                        <?php echo $t['view_participants'] ?? 'View Participants'; ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <?php echo $t['no_events_found'] ?? 'No events found.'; ?>
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
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
</body>

</html>
