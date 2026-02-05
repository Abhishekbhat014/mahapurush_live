<?php
session_start();
require __DIR__ . '/includes/lang.php';
require __DIR__ . '/config/db.php';

$isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
$uid = $_SESSION['user_id'] ?? NULL;

// --- 1. FETCH DATA FOR HEADER & PAGE ---
$temple = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM temple_info LIMIT 1"));

// Fetch Committee Members (Needed for the header dropdown)
$committeeMembers = [];
$cmQuery = mysqli_query($con, "SELECT u.first_name, u.last_name, u.photo, r.name AS role_name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name != 'customer' ORDER BY r.name, u.first_name");
if ($cmQuery) {
    while ($row = mysqli_fetch_assoc($cmQuery)) {
        $committeeMembers[] = $row;
    }
}

// Fetch Logged in User Photo for Header
$loggedInUserPhoto = '';
if ($isLoggedIn) {
    $uRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1"));
    $loggedInUserPhoto = !empty($uRow['photo']) ? 'uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['about_us']; ?> - <?= htmlspecialchars($t['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-bg-layout: #f8f9fa;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 16px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #fff;
            color: var(--ant-text);
            -webkit-user-select: none;
            user-select: none;
        }

        /* --- Header Fixes (Matching index.php) --- */
        .ant-header {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            height: 72px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--ant-border-color);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .ant-menu-nav {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .ant-menu-item {
            color: var(--ant-text);
            text-decoration: none;
            padding: 0 16px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.3s;
            display: flex;
            align-items: center;
            height: 72px;
        }

        .ant-menu-item:hover {
            color: var(--ant-primary);
        }

        .ant-hero {
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
            padding: 100px 0;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            height: 100%;
        }

        .ant-card:hover {
            transform: translateY(-5px);
        }

        .ant-card-body {
            padding: 40px;
        }

        .card-icon {
            width: 56px;
            height: 56px;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 24px;
        }


        .ant-btn-primary {
            background-color: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 500;
            text-decoration: none;
            /* Add this for <a> tags */
            display: inline-block;
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

        .section-title {
            font-weight: 800;
            font-size: 32px;
            margin-bottom: 24px;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 4px;
            background: var(--ant-primary);
            border-radius: 2px;
        }

        .ant-footer {
            background: #fafafa;
            border-top: 1px solid var(--ant-border-color);
            padding: 80px 0 40px;
            color: var(--ant-text);
        }

        .ant-divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--ant-border-color), transparent);
            margin: 40px 0;
        }

        .footer-link {
            color: var(--ant-text);
            text-decoration: none;
            font-size: 14px;
            opacity: 0.65;
            transition: 0.3s;
        }

        .footer-link:hover {
            opacity: 1;
            color: var(--ant-primary);
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <section class="ant-hero">
        <div class="container">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-bold text-uppercase"
                style="letter-spacing: 2px; font-size: 11px;">
                <?php echo $t['about_hero_badge']; ?>
            </span>
            <h1 class="display-4 fw-bold text-dark mb-4"><?php echo $t['about_hero_title']; ?></h1>
            <p class="text-secondary mx-auto fs-5" style="max-width: 800px; line-height: 1.8;">
                <?= htmlspecialchars($temple['description']) ?>
            </p>
        </div>
    </section>

    <main class="container py-5 mt-5">
        <div class="row align-items-center g-5 mb-5 pb-5">
            <div class="col-lg-6">
                <div class="section-title"><?php echo $t['about_heritage_title']; ?></div>
                <p class="text-secondary mb-4" style="font-size: 17px; line-height: 1.8;">
                    <?php echo $t['about_heritage_desc']; ?>
                </p>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="fw-bold fs-3 text-primary">100+</div>
                        <div class="small text-muted text-uppercase fw-bold"><?php echo $t['years_of_legacy']; ?></div>
                    </div>
                    <div class="col-6">
                        <div class="fw-bold fs-3 text-primary">50k+</div>
                        <div class="small text-muted text-uppercase fw-bold"><?php echo $t['devotees_served']; ?></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="ant-card overflow-hidden border-0 shadow-lg">
                    <img src="assets\images\temple.png" class="img-fluid"
                        alt="<?php echo $t['temple_architecture_alt']; ?>">
                </div>
            </div>
        </div>

        <div class="py-5">
            <div class="text-center mb-5">
                <div class="section-title"><?php echo $t['foundational_pillars']; ?></div>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="ant-card">
                        <div class="ant-card-body">
                            <div class="card-icon"><i class="bi bi-brightness-high"></i></div>
                            <h5 class="fw-bold mb-3"><?php echo $t['pillar_spiritual_growth']; ?></h5>
                            <p class="text-secondary small mb-0"><?php echo $t['pillar_spiritual_growth_desc']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ant-card">
                        <div class="ant-card-body">
                            <div class="card-icon"><i class="bi bi-people"></i></div>
                            <h5 class="fw-bold mb-3"><?php echo $t['pillar_community_unity']; ?></h5>
                            <p class="text-secondary small mb-0"><?php echo $t['pillar_community_unity_desc']; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="ant-card">
                        <div class="ant-card-body">
                            <div class="card-icon"><i class="bi bi-hand-thumbs-up"></i></div>
                            <h5 class="fw-bold mb-3"><?php echo $t['pillar_seva']; ?></h5>
                            <p class="text-secondary small mb-0"><?php echo $t['pillar_seva_desc']; ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>