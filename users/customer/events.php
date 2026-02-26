<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/user_avatar.php';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'events.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? ($t['user'] ?? 'User');
$userPhotoUrl = get_user_avatar_url('../../');

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register_event') {
    $eventId = (int) ($_POST['event_id'] ?? 0);

    if ($eventId <= 0 || !$con) {
        $error = $t['something_went_wrong'] ?? 'Something went wrong.';
    } else {
        $eventStmt = $con->prepare("SELECT id, max_participants FROM events WHERE id = ?");
        $eventStmt->bind_param("i", $eventId);
        $eventStmt->execute();
        $eventRes = $eventStmt->get_result();
        $event = $eventRes ? $eventRes->fetch_assoc() : null;
        $eventStmt->close();

        if (!$event) {
            $error = $t['no_events_found'] ?? 'Event not found.';
        } else {
            $checkStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM event_participants WHERE event_id = ? AND user_id = ?");
            $checkStmt->bind_param("ii", $eventId, $userId);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result()->fetch_assoc();
            $alreadyRegistered = (int) ($checkRes['cnt'] ?? 0) > 0;
            $checkStmt->close();

            if ($alreadyRegistered) {
                $error = $t['already_registered'] ?? 'You are already registered for this event.';
            } else {
                $countStmt = $con->prepare("SELECT COUNT(*) AS cnt FROM event_participants WHERE event_id = ?");
                $countStmt->bind_param("i", $eventId);
                $countStmt->execute();
                $countRes = $countStmt->get_result()->fetch_assoc();
                $participantCount = (int) ($countRes['cnt'] ?? 0);
                $countStmt->close();

                $maxParticipants = $event['max_participants'];
                if ($maxParticipants !== null && (int) $maxParticipants > 0 && $participantCount >= (int) $maxParticipants) {
                    $error = $t['event_full'] ?? 'This event is full.';
                } else {
                    $insertStmt = $con->prepare("INSERT INTO event_participants (user_id, event_id, created_at) VALUES (?, ?, NOW())");
                    $insertStmt->bind_param("ii", $userId, $eventId);
                    if ($insertStmt->execute()) {
                        $success = $t['registration_success'] ?? 'Registration successful.';
                    } else {
                        $error = $t['something_went_wrong'] ?? 'Something went wrong.';
                    }
                    $insertStmt->close();
                }
            }
        }
    }
}

$events = [];
if ($con) {
    $stmt = $con->prepare(
        "SELECT e.*, 
                (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id) AS participant_count,
                (SELECT COUNT(*) FROM event_participants ep WHERE ep.event_id = e.id AND ep.user_id = ?) AS is_registered
         FROM events e
         ORDER BY e.conduct_on DESC, e.created_at DESC"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $events[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="<?= $currLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['events'] ?? 'Events'; ?> - <?php echo $t['title'] ?? 'Temple'; ?></title>
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
            /* Safari */
            -moz-user-select: none;
            /* Firefox */
            -ms-user-select: none;
            /* IE10+/Edge */
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



        .dashboard-hero {
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
            padding: 40px 32px;
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

        .badge-soft {
            padding: 4px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 12px;
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .badge-full {
            background: #fff2f0;
            color: #ff4d4f;
            border: 1px solid #ffccc7;
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

        /* ADD THIS SECTION FOR HOVER DROPDOWNS */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
                /* Removes gap so mouse can enter menu without it closing */
            }

            /* Optional: Add a slight animation */
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
                        <?= ($currLang == 'mr') ? ($t['lang_marathi'] ?? 'Marathi') : ($t['lang_english'] ?? 'English'); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>"
                                href="?lang=en" aria-current="<?= ($currLang == 'en') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_english'] ?? 'English'; ?>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>"
                                href="?lang=mr" aria-current="<?= ($currLang == 'mr') ? 'true' : 'false' ?>">
                                <?php echo $t['lang_marathi_full'] ?? 'Marathi'; ?>
                            </a>
                        </li>
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
                            <?php foreach ($availableRoles as $role):
                                $roleLabel = ucwords(str_replace('_', ' ', $role));
                                ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role === $primaryRole) ? 'active' : '' ?>">
                                            <?= htmlspecialchars($roleLabel) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['events'] ?? 'Events'; ?></h2>
                    <p class="text-secondary mb-0">View upcoming temple events and register if available.</p>
                </div>

                <div class="p-4 pb-5">
                    <?php if ($success): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="ant-card">
                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['event_name'] ?? 'Event'; ?></th>
                                        <th><?php echo $t['event_date'] ?? 'Date'; ?></th>
                                        <th><?php echo $t['event_duration'] ?? 'Duration'; ?></th>
                                        <th><?php echo $t['participants'] ?? 'Participants'; ?></th>
                                        <th><?php echo $t['status'] ?? 'Status'; ?></th>
                                        <th class="text-end"><?php echo $t['action'] ?? 'Action'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($events)): ?>
                                        <?php foreach ($events as $event):
                                            $max = $event['max_participants'];
                                            $count = (int) ($event['participant_count'] ?? 0);
                                            $isRegistered = (int) ($event['is_registered'] ?? 0) > 0;
                                            $isFull = $max !== null && (int) $max > 0 && $count >= (int) $max;
                                            ?>
                                            <tr>
                                                <td class="fw-semibold"><?= htmlspecialchars($event['name']) ?></td>
                                                <td><?= $event['conduct_on'] ? date('d M Y', strtotime($event['conduct_on'])) : '-' ?>
                                                </td>
                                                <td><?= htmlspecialchars($event['duration'] ?: '-') ?></td>
                                                <td><?= $max ? $count . ' / ' . (int) $max : $count ?></td>
                                                <td>
                                                    <?php if ($isRegistered): ?>
                                                        <span
                                                            class="badge-soft"><?php echo $t['registered'] ?? 'Registered'; ?></span>
                                                    <?php elseif ($isFull): ?>
                                                        <span
                                                            class="badge-soft badge-full"><?php echo $t['full'] ?? 'Full'; ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><?php echo $t['open'] ?? 'Open'; ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <?php if ($isRegistered): ?>
                                                        <button class="btn btn-light btn-sm border rounded-pill px-3" disabled>
                                                            <?php echo $t['registered'] ?? 'Registered'; ?>
                                                        </button>
                                                    <?php elseif ($isFull): ?>
                                                        <button class="btn btn-light btn-sm border rounded-pill px-3" disabled>
                                                            <?php echo $t['full'] ?? 'Full'; ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="action" value="register_event">
                                                            <input type="hidden" name="event_id" value="<?= (int) $event['id'] ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">
                                                                <?php echo $t['register'] ?? 'Register'; ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">
                                                <i class="bi bi-calendar2-x fs-1 opacity-25 d-block mb-3"></i>
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
</body>

</html>