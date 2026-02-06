<?php
session_start();
require __DIR__ . '/../../includes/lang.php';

// Auth Check
if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$currLang = $_SESSION['lang'] ?? 'en';

require __DIR__ . '/../../config/db.php';
$uid = (int) $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';

// A. HANDLE DETAILS UPDATE
if (isset($_POST['update_details'])) {
    $fNameRaw = trim($_POST['first_name'] ?? '');
    $lNameRaw = trim($_POST['last_name'] ?? '');
    $phoneRaw = trim($_POST['phone'] ?? '');

    if ($fNameRaw === '' || $lNameRaw === '' || $phoneRaw === '') {
        $errorMsg = $t['err_fill_all'] ?? 'Please fill in all required fields.';
    } elseif (!preg_match('/^(?=.{2,50}$)[\p{L}\s\.\'-]+$/u', $fNameRaw) || !preg_match('/^(?=.{2,50}$)[\p{L}\s\.\'-]+$/u', $lNameRaw)) {
        $errorMsg = $t['err_invalid_name'] ?? 'Please enter a valid name (2-50 letters).';
    } elseif (!preg_match('/^[0-9]{10}$/', $phoneRaw)) {
        $errorMsg = $t['err_invalid_phone'] ?? 'Please enter a valid 10-digit phone number.';
    }

    $fName = mysqli_real_escape_string($con, $fNameRaw);
    $lName = mysqli_real_escape_string($con, $lNameRaw);
    $phone = mysqli_real_escape_string($con, $phoneRaw);
    $photoName = null;
    $oldPhoto = null;

    if (!empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = $t['err_file_upload_failed'] ?? 'Photo upload failed.';
        } else {
            $fileTmp = $_FILES['photo']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'png', 'jpeg', 'webp'];
            if (!in_array($fileExt, $allowed, true)) {
                $errorMsg = $t['err_invalid_file_type'] ?? 'Invalid file type.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errorMsg = $t['err_file_size'] ?? 'File is too large.';
            } else {
                $photoName = uniqid('user_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../../uploads/users/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                if (!move_uploaded_file($fileTmp, $uploadDir . $photoName)) {
                    $errorMsg = $t['err_file_upload_failed'] ?? 'Photo upload failed.';
                } else {
                    $existing = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid' LIMIT 1"));
                    $oldPhoto = !empty($existing['photo']) ? $existing['photo'] : null;
                }
            }
        }
    }

    if (empty($errorMsg)) {
        $photoSql = $photoName ? ", photo='$photoName'" : '';
        if (mysqli_query($con, "UPDATE users SET first_name='$fName', last_name='$lName', phone='$phone' $photoSql WHERE id='$uid'")) {
            if ($photoName && $oldPhoto) {
                $oldPath = __DIR__ . '/../../uploads/users/' . basename($oldPhoto);
                if (is_file($oldPath)) {
                    unlink($oldPath);
                }
            }
            $_SESSION['user_name'] = $fName . ' ' . $lName;
            $successMsg = $t['profile_details_updated'] ?? 'Profile updated successfully.';
        } else {
            if ($photoName) {
                $newPath = __DIR__ . '/../../uploads/users/' . basename($photoName);
                if (is_file($newPath)) {
                    unlink($newPath);
                }
            }
            $errorMsg = $t['profile_update_failed'] ?? 'Unable to update profile.';
        }
    }
}

// B. HANDLE PASSWORD UPDATE
if (isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
        $errorMsg = $t['err_fill_all'] ?? 'Please fill in all required fields.';
    } elseif (strlen($newPass) < 6) {
        $errorMsg = $t['password_min_length'] ?? 'Password must be at least 6 characters.';
    }

    $userRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT password FROM users WHERE id='$uid'"));

    if (empty($errorMsg)) {
        if ($userRow && password_verify($currentPass, $userRow['password'])) {
            if ($newPass === $confirmPass) {
                $hashedNew = password_hash($newPass, PASSWORD_DEFAULT);
                mysqli_query($con, "UPDATE users SET password='$hashedNew' WHERE id='$uid'");
                $successMsg = $t['password_updated'] ?? 'Password updated.';
            } else {
                $errorMsg = $t['new_passwords_mismatch'] ?? 'Passwords must match.';
            }
        } else {
            $errorMsg = $t['incorrect_current_password'] ?? 'Incorrect current password.';
        }
    }
}

// FETCH CURRENT DATA
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id='$uid' LIMIT 1"));
$userPhotoUrl = !empty($user['photo'])
    ? '../../uploads/users/' . basename($user['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=random';

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
                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                </div>
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
                                    <p class="text-muted small mb-0"><?php echo $t['chairman_role'] ?? 'Chairman'; ?></p>
                                </div>
                            </div>

                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['security_settings']; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $t['current_password']; ?></label>
                                            <input type="password" name="current_password" class="form-control" required>
                                            <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $t['new_password']; ?></label>
                                            <input type="password" name="new_password" class="form-control" required
                                                minlength="6">
                                            <div class="invalid-feedback"><?php echo $t['password_min_length'] ?? 'Password must be at least 6 characters.'; ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label"><?php echo $t['confirm_new_password']; ?></label>
                                            <input type="password" name="confirm_password" class="form-control" required
                                                minlength="6">
                                            <div class="invalid-feedback"><?php echo $t['new_passwords_mismatch'] ?? 'Passwords must match.'; ?></div>
                                        </div>
                                        <button type="submit" name="change_password"
                                            class="btn btn-outline-danger w-100 rounded-pill btn-sm fw-bold">
                                            <?php echo $t['update_password']; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['account_information']; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['first_name']; ?></label>
                                                <input type="text" name="first_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['first_name']) ?>" required
                                                    minlength="2" maxlength="50">
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['last_name']; ?></label>
                                                <input type="text" name="last_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['last_name']) ?>" required
                                                    minlength="2" maxlength="50">
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['phone_number']; ?></label>
                                                <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}"
                                                    value="<?= htmlspecialchars($user['phone']) ?>" required>
                                                <div class="invalid-feedback"><?php echo $t['err_invalid_phone'] ?? 'Please enter a valid 10-digit phone number.'; ?></div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label"><?php echo $t['email_address']; ?></label>
                                                <input type="email" class="form-control bg-light"
                                                    value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                                <div class="form-text small" style="font-size: 11px;"><i
                                                        class="bi bi-lock-fill"></i>
                                                    <?php echo $t['email_verified_cannot_change']; ?></div>
                                            </div>
                                            <div class="col-md-12 mb-3">
                                                <label class="form-label"><?php echo $t['profile_photo_optional']; ?></label>
                                                <input type="file" name="photo" class="form-control" accept="image/*">
                                                <div class="form-text small" style="font-size: 11px;">
                                                    <?php echo $t['profile_photo_hint'] ?? 'JPG, PNG, or WEBP up to 2MB.'; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="ant-divider"></div>

                                        <div class="d-flex justify-content-end gap-3">
                                            <a href="dashboard.php" class="btn btn-light px-4 border"
                                                style="border-radius: 8px;"><?php echo $t['cancel']; ?></a>
                                            <button type="submit" name="update_details" class="ant-btn-primary px-5">
                                                <?php echo $t['save_profile_changes']; ?>
                                            </button>
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
    <script>
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>
