<?php
session_start();
require __DIR__ . '/includes/lang.php';
$dbPath = __DIR__ . '/config/db.php';
if (!file_exists($dbPath)) {
    die($t['db_config_missing']);
}
require $dbPath;

if (!isset($con) && isset($conn)) {
    $con = $conn;
}

$chairman = null;
if (isset($con)) {
    $chairmanQuery = mysqli_query(
        $con,
        "SELECT u.id, u.first_name, u.last_name, u.photo, u.email, u.phone, r.name AS role_name
         FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         JOIN roles r ON r.id = ur.role_id
         WHERE r.name = 'chairman'
         LIMIT 1"
    );
    if ($chairmanQuery) {
        $chairman = mysqli_fetch_assoc($chairmanQuery);
    }
}

$chairmanName = '';
$chairmanPhoto = '';
if (!empty($chairman)) {
    $chairmanName = trim(($chairman['first_name'] ?? '') . ' ' . ($chairman['last_name'] ?? ''));
    $chairmanPhoto = !empty($chairman['photo'])
        ? 'uploads/users/' . basename($chairman['photo'])
        : 'https://ui-avatars.com/api/?name=' . urlencode($chairmanName) . '&background=random';
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['chairman_profile_title'] ?? $t['chairman_role'] ?? 'Chairman'; ?> - <?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f8f9fa;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 16px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 70%);
            color: var(--ant-text);
            min-height: 100vh;
        }

        .ant-page-hero {
            padding: 70px 0 50px;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 40px;
        }

        .ant-page-hero h1 {
            font-size: 42px;
            font-weight: 800;
            letter-spacing: -1.5px;
            background: linear-gradient(135deg, #111 0%, #444 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .profile-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            padding: 32px;
            height: 100%;
        }

        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            border-bottom: 1px solid var(--ant-border-color);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }

        .profile-img {
            width: 110px;
            height: 110px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #e6f4ff;
        }

        .role-tag {
            display: inline-block;
            padding: 4px 14px;
            font-size: 11px;
            font-weight: 700;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
        }

        .info-tile {
            background: #f8fbff;
            border: 1px solid #d9e6ff;
            border-radius: 12px;
            padding: 14px 16px;
        }

        .info-label {
            font-size: 11px;
            letter-spacing: 1px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .info-value {
            font-weight: 600;
            color: var(--ant-text);
            font-size: 15px;
        }

        .btn-ant-primary {
            background: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 8px 20px;
            font-weight: 600;
        }

        .feature-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            padding: 24px;
            box-shadow: var(--ant-shadow);
        }

        .feature-icon {
            width: 46px;
            height: 46px;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <div class="ant-page-hero">
        <div class="container">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-bold text-uppercase"
                style="letter-spacing: 1px; font-size: 11px;">
                <?php echo $t['chairman_role'] ?? 'Chairman'; ?>
            </span>
            <h1><?php echo $t['chairman_profile_title'] ?? 'Chairman Profile'; ?></h1>
            <p class="text-secondary small mb-0 mx-auto" style="max-width: 680px;">
                <?php echo $t['chairman_profile_subtitle'] ?? 'Meet the leader guiding our temple community with dedication and devotion.'; ?>
            </p>
        </div>
    </div>

    <div class="container pb-5">
        <?php if (!empty($chairman)): ?>
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-7">
                    <div class="profile-card">
                        <div class="profile-header">
                            <img src="<?php echo htmlspecialchars($chairmanPhoto); ?>" alt="<?php echo $t['chairman_role'] ?? 'Chairman'; ?>"
                                class="profile-img">
                            <div>
                                <h2 class="fw-bold mb-1"><?php echo htmlspecialchars($chairmanName); ?></h2>
                                <div class="role-tag mb-2"><?php echo $t['chairman_role'] ?? 'Chairman'; ?></div>
                                <div class="text-muted small"><?php echo $t['chairman_profile_about'] ?? 'A devoted guide ensuring the temple’s traditions, seva initiatives, and community growth.'; ?></div>
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label"><?php echo $t['email'] ?? 'Email'; ?></div>
                                    <div class="info-value">
                                        <?php echo !empty($chairman['email']) ? htmlspecialchars($chairman['email']) : ($t['not_available'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="info-tile">
                                    <div class="info-label"><?php echo $t['phone'] ?? 'Phone'; ?></div>
                                    <div class="info-value">
                                        <?php echo !empty($chairman['phone']) ? htmlspecialchars($chairman['phone']) : ($t['not_available'] ?? 'N/A'); ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="info-tile d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="info-label"><?php echo $t['committee'] ?? 'Committee'; ?></div>
                                        <div class="info-value"><?php echo $t['dedicated_sevaks_serving_the_temple_and_community'] ?? 'Dedicated sevaks serving the temple and community.'; ?></div>
                                    </div>
                                    <a href="committee.php" class="btn btn-ant-primary rounded-pill px-4">
                                        <?php echo $t['view_all_members'] ?? 'View All Members'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="feature-card mb-4">
                        <div class="feature-icon"><i class="bi bi-flower3"></i></div>
                        <h5 class="fw-bold mb-2"><?php echo $t['chairman_focus_title'] ?? 'Spiritual Leadership'; ?></h5>
                        <p class="text-secondary small mb-0">
                            <?php echo $t['chairman_focus_desc'] ?? 'Oversees rituals, festivals, and the spiritual direction of the temple.'; ?>
                        </p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon"><i class="bi bi-people"></i></div>
                        <h5 class="fw-bold mb-2"><?php echo $t['chairman_community_title'] ?? 'Community Stewardship'; ?></h5>
                        <p class="text-secondary small mb-0">
                            <?php echo $t['chairman_community_desc'] ?? 'Supports volunteers and strengthens community bonds through seva.'; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-person-badge text-muted opacity-25" style="font-size: 4rem;"></i>
                <h5 class="fw-bold text-muted mt-3"><?php echo $t['no_members_found'] ?? 'No members found'; ?></h5>
                <p class="small text-muted"><?php echo $t['try_adjusting_search'] ?? 'Please check back later for updates.'; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
