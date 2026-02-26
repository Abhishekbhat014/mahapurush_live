<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';
require __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole    = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'member');
$currLang       = $_SESSION['lang'] ?? 'en';
$loggedInUserPhoto = get_user_avatar_url('../../');

$poojaId = (int)($_GET['id'] ?? 0);
if ($poojaId <= 0) {
    header("Location: dashboard.php");
    exit;
}

// Fetch pooja + pooja type + devotee details
$stmt = $con->prepare("
    SELECT 
        p.id,
        p.pooja_date,
        p.time_slot,
        p.description,
        p.fee,
        p.status,
        p.created_at,
        pt.type        AS pooja_name,
        u.id           AS devotee_id,
        u.first_name,
        u.last_name,
        u.email,
        u.phone,
        u.photo,
        u.created_at   AS member_since
    FROM pooja p
    INNER JOIN pooja_type pt ON pt.id = p.pooja_type_id
    INNER JOIN users u       ON u.id  = p.user_id
    WHERE p.id = ?
    LIMIT 1
");
$stmt->bind_param("i", $poojaId);
$stmt->execute();
$pooja = $stmt->get_result()->fetch_assoc();

if (!$pooja) {
    header("Location: dashboard.php");
    exit;
}

$devoteeName = trim($pooja['first_name'] . ' ' . $pooja['last_name']);
$devoteePhoto = !empty($pooja['photo'])
    ? '../../uploads/users/' . htmlspecialchars(basename($pooja['photo']))
    : 'https://ui-avatars.com/api/?name=' . urlencode($devoteeName) . '&background=random';

$statusKey   = strtolower($pooja['status'] ?? 'pending');
$badgeColors = [
    'completed' => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f'],
    'paid'      => ['bg' => '#f6ffed', 'color' => '#52c41a', 'border' => '#b7eb8f'],
    'cancelled' => ['bg' => '#fff1f0', 'color' => '#f5222d', 'border' => '#ffa39e'],
    'pending'   => ['bg' => '#fffbe6', 'color' => '#faad14', 'border' => '#ffe58f'],
];
$badge = $badgeColors[$statusKey] ?? $badgeColors['pending'];
?>
<!DOCTYPE html>
<html lang="<?= $currLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['pooja_details'] ?? 'Pooja Details'; ?> - <?php echo $t['title']; ?></title>
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
            box-shadow: var(--ant-shadow);
        }

        .ant-card-head {
            padding: 16px 24px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 15px;
        }

        .ant-card-body {
            padding: 24px;
        }

        .info-row {
            display: flex;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid var(--ant-border-color);
            font-size: 14px;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--ant-text-sec);
            font-weight: 600;
            min-width: 140px;
        }

        .info-value {
            color: var(--ant-text);
        }

        .devotee-avatar {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--ant-border-color);
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

        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu { display: block; margin-top: 0; }
            .dropdown .dropdown-menu { display: none; }
            .dropdown:hover>.dropdown-menu { display: block; animation: fadeIn 0.2s ease-in-out; }
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to   { opacity: 1; transform: translateY(0); }
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
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:10px;">
                        <li><a class="dropdown-item small fw-medium <?= ($currLang=='en')?'active':'' ?>" href="?lang=en&id=<?= $poojaId ?>"><?php echo $t['lang_english']; ?></a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang=='mr')?'active':'' ?>" href="?lang=mr&id=<?= $poojaId ?>"><?php echo $t['lang_marathi_full']; ?></a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:10px;">
                            <?php foreach ($availableRoles as $role): ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role===$primaryRole)?'active':'' ?>">
                                            <?= htmlspecialchars(ucwords(str_replace('_',' ',$role))) ?>
                                        </button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28" style="object-fit:cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="sticky-top bg-transparent pb-3 pt-3" style="top: 64px; z-index: 999; margin-left: 32px;">
                    <a href="dashboard.php" class="btn btn-sm btn-light border shadow-sm rounded-pill px-3">
                        <i class="bi bi-arrow-left me-1"></i><?php echo $t['back'] ?? 'Back'; ?>
                    </a>
                </div>

                <div class="dashboard-hero" style="margin-top: -65px;">
                    <h2 class="fw-bold mb-1" style="margin-top: 30px;">
                        <i class="bi bi-flower1 me-2 text-primary"></i>
                        <?php echo $t['pooja_details'] ?? 'Pooja Details'; ?>
                    </h2>
                    <p class="text-secondary mb-0"><?php echo $t['pooja_details_subtitle'] ?? 'Full details of the pooja booking and the devotee.'; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row g-4">

                        <!-- Pooja Details Card -->
                        <div class="col-lg-6">
                            <div class="ant-card h-100">
                                <div class="ant-card-head">
                                    <i class="bi bi-calendar2-event me-2 text-primary"></i>
                                    <?php echo $t['pooja_information'] ?? 'Pooja Information'; ?>
                                </div>
                                <div class="ant-card-body">
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['pooja_type'] ?? 'Pooja Type'; ?></span>
                                        <span class="info-value fw-bold text-primary"><?= htmlspecialchars($pooja['pooja_name']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['date']; ?></span>
                                        <span class="info-value"><?= date('d M Y', strtotime($pooja['pooja_date'])) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['time_slot']; ?></span>
                                        <span class="info-value"><?= ucfirst($pooja['time_slot'] ?? '—') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['fee'] ?? 'Fee'; ?></span>
                                        <span class="info-value fw-bold">₹<?= number_format((float)$pooja['fee'], 2) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['status']; ?></span>
                                        <span class="info-value">
                                            <span class="badge rounded-pill px-3 py-2"
                                                style="background:<?= $badge['bg'] ?>;color:<?= $badge['color'] ?>;border:1px solid <?= $badge['border'] ?>;">
                                                <?= ucfirst($statusKey) ?>
                                            </span>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['booked_on'] ?? 'Booked On'; ?></span>
                                        <span class="info-value text-muted"><?= date('d M Y, h:i A', strtotime($pooja['created_at'])) ?></span>
                                    </div>
                                    <?php if (!empty($pooja['description'])): ?>
                                    <div class="info-row">
                                        <span class="info-label"><?php echo $t['special_instructions']; ?></span>
                                        <span class="info-value"><?= nl2br(htmlspecialchars($pooja['description'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Devotee Details Card -->
                        <div class="col-lg-6">
                            <div class="ant-card h-100">
                                <div class="ant-card-head">
                                    <i class="bi bi-person-circle me-2 text-primary"></i>
                                    <?php echo $t['devotee_information'] ?? 'Devotee Information'; ?>
                                </div>
                                <div class="ant-card-body">
                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <img src="<?= $devoteePhoto ?>" alt="Avatar" class="devotee-avatar shadow-sm"
                                             onerror="this.onerror=null; this.src='https://ui-avatars.com/api/?name=<?= urlencode($devoteeName) ?>&background=random';">
                                        <div>
                                            <h5 class="fw-bold mb-0">
                                                <?= htmlspecialchars($pooja['first_name'] . ' ' . $pooja['last_name']) ?>
                                            </h5>
                                            <span class="text-muted small"><?php echo $t['devotee'] ?? 'Devotee'; ?></span>
                                        </div>
                                    </div>

                                    <div class="info-row">
                                        <span class="info-label"><i class="bi bi-envelope me-1"></i><?php echo $t['email'] ?? 'Email'; ?></span>
                                        <span class="info-value">
                                            <a href="mailto:<?= htmlspecialchars($pooja['email']) ?>" class="text-primary text-decoration-none">
                                                <?= htmlspecialchars($pooja['email']) ?>
                                            </a>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><i class="bi bi-telephone me-1"></i><?php echo $t['phone'] ?? 'Phone'; ?></span>
                                        <span class="info-value">
                                            <?= !empty($pooja['phone'])
                                                ? '<a href="tel:' . htmlspecialchars($pooja['phone']) . '" class="text-primary text-decoration-none">' . htmlspecialchars($pooja['phone']) . '</a>'
                                                : '<span class="text-muted">—</span>'
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label"><i class="bi bi-calendar-check me-1"></i><?php echo $t['member_since'] ?? 'Member Since'; ?></span>
                                        <span class="info-value text-muted"><?= date('d M Y', strtotime($pooja['member_since'])) ?></span>
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
