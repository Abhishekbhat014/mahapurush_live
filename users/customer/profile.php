<?php
session_start();
require __DIR__ . '/../../includes/lang.php';

// Auth Check
if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
$uid = (int) $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// A. HANDLE DETAILS UPDATE
if (isset($_POST['update_details'])) {
    $fName = mysqli_real_escape_string($con, $_POST['first_name']);
    $lName = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);

    if (mysqli_query($con, "UPDATE users SET first_name='$fName', last_name='$lName', phone='$phone' WHERE id='$uid'")) {
        $_SESSION['user_name'] = $fName . ' ' . $lName;
        $successMsg = $t['profile_details_updated'];
    } else {
        $errorMsg = $t['profile_update_failed'];
    }
}

// B. HANDLE PASSWORD UPDATE
if (isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    $userRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT password FROM users WHERE id='$uid'"));

    if (password_verify($currentPass, $userRow['password'])) {
        if ($newPass === $confirmPass) {
            $hashedNew = password_hash($newPass, PASSWORD_DEFAULT);
            mysqli_query($con, "UPDATE users SET password='$hashedNew' WHERE id='$uid'");
            $successMsg = $t['password_updated'];
        } else {
            $errorMsg = $t['new_passwords_mismatch'];
        }
    } else {
        $errorMsg = $t['incorrect_current_password'];
    }
}

// FETCH CURRENT DATA
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id='$uid' LIMIT 1"));
$userPhotoUrl = !empty($user['photo'])
    ? '../../uploads/users/' . $user['photo']
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=1677ff&color=fff';

$currentPage = 'profile.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['my_profile']; ?> - <?php echo $t['title']; ?></title>
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
            height: 100%;
            transition: transform 0.3s ease;
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 16px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 8px;
        }

        .form-control {
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
            transition: 0.3s;
        }

        .ant-btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: var(--ant-shadow);
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

        .ant-divider {
            height: 1px;
            background: var(--ant-border-color);
            margin: 24px 0;
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
                <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                <div class="vr mx-2 text-muted opacity-25"></div>
                <a href="../../auth/logout.php" class="text-danger"><i class="bi bi-power"></i></a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['my_profile']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['profile_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <?php if ($successMsg): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= $successMsg ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> <?= $errorMsg ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="ant-card text-center mb-4">
                                <div class="ant-card-body">
                                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="profile-avatar mb-3"
                                        alt="<?php echo $t['profile_avatar_alt']; ?>">
                                    <h5 class="fw-bold mb-1">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                    </h5>
                                    <p class="text-muted small mb-0"><?php echo $t['member_since']; ?>
                                        <?= date("M Y", strtotime($user['created_at'])) ?>
                                    </p>

                                    <div class="ant-divider"></div>

                                    <div class="text-start">
                                        <h6 class="fw-bold mb-3 small text-uppercase" style="letter-spacing: 1px;">
                                            <?php echo $t['security_settings']; ?></h6>
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo $t['current_password']; ?></label>
                                                <input type="password" name="current_password" class="form-control"
                                                    required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo $t['new_password']; ?></label>
                                                <input type="password" name="new_password" class="form-control" required
                                                    minlength="6">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label"><?php echo $t['confirm_new_password']; ?></label>
                                                <input type="password" name="confirm_password" class="form-control"
                                                    required minlength="6">
                                            </div>
                                            <button type="submit" name="change_password"
                                                class="btn btn-outline-danger w-100 rounded-pill btn-sm fw-bold"><?php echo $t['update_password']; ?></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['account_information']; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST">
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['first_name']; ?></label>
                                                <input type="text" name="first_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['last_name']; ?></label>
                                                <input type="text" name="last_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['phone_number']; ?></label>
                                                <input type="tel" name="phone" class="form-control"
                                                    value="<?= htmlspecialchars($user['phone']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['email_address']; ?></label>
                                                <input type="email" class="form-control bg-light"
                                                    value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                                <div class="form-text small" style="font-size: 11px;"><i
                                                        class="bi bi-lock-fill"></i> <?php echo $t['email_verified_cannot_change']; ?></div>
                                            </div>
                                        </div>

                                        <div class="ant-divider"></div>

                                        <div class="d-flex justify-content-end gap-3">
                                            <a href="dashboard.php" class="btn btn-light px-4 border"
                                                style="border-radius: 8px;"><?php echo $t['cancel']; ?></a>
                                            <button type="submit" name="update_details"
                                                class="ant-btn-primary px-5"><?php echo $t['save_profile_changes']; ?></button>
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
