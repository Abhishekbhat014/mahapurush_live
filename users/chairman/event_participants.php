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

$eventId = isset($_GET['event_id']) ? (int) $_GET['event_id'] : 0;
$event = null;
$participants = [];

if ($eventId > 0) {
    $stmt = $con->prepare("SELECT * FROM events WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $eventId);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($event) {
        $stmt = $con->prepare(
            "SELECT ep.id, ep.payment_id, ep.created_at, u.first_name, u.last_name, u.email, u.phone
             FROM event_participants ep
             JOIN users u ON u.id = ep.user_id
             WHERE ep.event_id = ?
             ORDER BY ep.created_at DESC"
        );
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $participants[] = $row;
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['event_participants'] ?? 'Event Participants'; ?> - <?= $t['title'] ?></title>
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
                                href="?event_id=<?= (int) $eventId ?>&lang=en"
                                aria-current="<?= ($currLang == 'en') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_english']; ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?event_id=<?= (int) $eventId ?>&lang=mr"
                                aria-current="<?= ($currLang == 'mr') ? 'true' : 'false' ?>">
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
                    <h3 class="fw-bold mb-2"><?php echo $t['event_participants'] ?? 'Event Participants'; ?></h3>
                    <p class="text-secondary mb-0">
                        <?php echo $t['event_details'] ?? 'Event details'; ?>
                    </p>
                </div>

                <div class="p-4">
                    <div class="mb-3">
                        <a href="events.php" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-left me-1"></i> <?php echo $t['back_to_events'] ?? 'Back to Events'; ?>
                        </a>
                    </div>

                    <?php if (!$event): ?>
                        <div class="alert alert-warning">
                            <?php echo $t['invalid_event'] ?? 'Invalid event selected.'; ?>
                        </div>
                    <?php else: ?>
                        <div class="ant-card mb-4">
                            <div class="p-4 border-bottom">
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($event['name']) ?></h5>
                                <div class="text-muted small">
                                    <?= $event['conduct_on'] ? date("d M Y", strtotime($event['conduct_on'])) : '-' ?>
                                    · <?= htmlspecialchars($event['duration'] ?: '-') ?>
                                </div>
                            </div>
                        </div>

                        <div class="ant-card">
                            <div class="p-4 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <h5 class="fw-bold mb-0"><?php echo $t['participants'] ?? 'Participants'; ?></h5>
                                <span class="badge bg-light text-dark border">
                                    <?= count($participants) ?> <?php echo $t['participants'] ?? 'Participants'; ?>
                                </span>
                            </div>
                            <div class="table-responsive">
                                <table class="table ant-table mb-0">
                                    <thead>
                                        <tr>
                                            <th><?php echo $t['first_name'] ?? 'First Name'; ?></th>
                                            <th><?php echo $t['last_name'] ?? 'Last Name'; ?></th>
                                            <th><?php echo $t['email'] ?? 'Email'; ?></th>
                                            <th><?php echo $t['phone'] ?? 'Phone'; ?></th>
                                            <th><?php echo $t['payment_id'] ?? 'Payment ID'; ?></th>
                                            <th><?php echo $t['date'] ?? 'Date'; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($participants) > 0): ?>
                                            <?php foreach ($participants as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['first_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['last_name']) ?></td>
                                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                                    <td><?= htmlspecialchars($row['phone'] ?? '-') ?></td>
                                                    <td><?= $row['payment_id'] ? (int) $row['payment_id'] : '-' ?></td>
                                                    <td><?= $row['created_at'] ? date("d M Y", strtotime($row['created_at'])) : '-' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-4">
                                                    <?php echo $t['no_participants'] ?? 'No participants for this event.'; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
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
