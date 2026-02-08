<?php
require_once __DIR__ . '/../../includes/no_cache.php';
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

// --- A. HANDLE PHOTO DELETE ---
if (isset($_POST['delete_photo'])) {
    $existing = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid' LIMIT 1"));
    $oldPhoto = !empty($existing['photo']) ? $existing['photo'] : null;

    if (mysqli_query($con, "UPDATE users SET photo=NULL WHERE id='$uid'")) {
        if ($oldPhoto) {
            $oldPath = __DIR__ . '/../../uploads/users/' . basename($oldPhoto);
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }
        $_SESSION['user_photo'] = null; // Update session
        $successMsg = $t['profile_photo_deleted'] ?? 'Profile photo removed.';
    } else {
        $errorMsg = $t['profile_update_failed'] ?? 'Unable to update profile.';
    }
}

// --- B. HANDLE DETAILS UPDATE (AND PHOTO UPLOAD) ---
if (isset($_POST['update_details'])) {
    $fName = mysqli_real_escape_string($con, trim($_POST['first_name']));
    $lName = mysqli_real_escape_string($con, trim($_POST['last_name']));
    $phone = mysqli_real_escape_string($con, trim($_POST['phone']));

    $photoSqlPart = ""; // Default empty if no new photo is uploaded

    // 1. Handle Photo Upload Logic
    if (!empty($_FILES['profile_photo']['name'])) {
        $targetDir = __DIR__ . '/../../uploads/users/';

        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['profile_photo']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        // Validate File
        if (!in_array($fileType, $allowedTypes)) {
            $errorMsg = "Invalid file type. Only JPG, JPEG, PNG allowed.";
        } elseif ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) { // 2MB Limit
            $errorMsg = "File size must be less than 2MB.";
        } else {
            // Upload New File
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFilePath)) {

                // Remove Old File to save space
                $oldRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid'"));
                if (!empty($oldRow['photo'])) {
                    $oldFile = $targetDir . basename($oldRow['photo']);
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }

                // Prepare SQL part
                $photoSqlPart = ", photo='$fileName'";
            } else {
                $errorMsg = "Error uploading file. Check folder permissions.";
            }
        }
    }

    // 2. Update Database (only if no upload errors)
    if (empty($errorMsg)) {
        $updateQ = "UPDATE users SET first_name='$fName', last_name='$lName', phone='$phone' $photoSqlPart WHERE id='$uid'";

        if (mysqli_query($con, $updateQ)) {
            $_SESSION['user_name'] = trim($fName . ' ' . $lName);
            $successMsg = $t['profile_details_updated'] ?? 'Profile updated successfully.';
        } else {
            $errorMsg = $t['profile_update_failed'] ?? 'Update failed.';
        }
    }
}

// --- C. HANDLE PASSWORD UPDATE ---
if (isset($_POST['change_password'])) {
    $currentPassInput = $_POST['current_password'];
    $newPass = $_POST['new_password'];
    $confirmPass = $_POST['confirm_password'];

    $res = mysqli_query($con, "SELECT password FROM users WHERE id='$uid' LIMIT 1");
    $userData = mysqli_fetch_assoc($res);

    if (password_verify($currentPassInput, $userData['password'])) {
        if ($newPass === $confirmPass) {
            $hashedNew = password_hash($newPass, PASSWORD_DEFAULT);
            if (mysqli_query($con, "UPDATE users SET password='$hashedNew' WHERE id='$uid'")) {
                $successMsg = $t['password_updated'] ?? 'Password updated successfully.';
            } else {
                $errorMsg = $t['database_error'] ?? 'Database error.';
            }
        } else {
            $errorMsg = $t['new_passwords_mismatch'] ?? 'New passwords do not match.';
        }
    } else {
        $errorMsg = $t['incorrect_current_password'] ?? 'Incorrect current password.';
    }
}

// FETCH CURRENT DATA
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id='$uid' LIMIT 1"));
$userPhotoUrl = !empty($user['photo'])
    ? '../../uploads/users/' . basename($user['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=random';

// Append timestamp to force browser to reload image if just updated
if (isset($_POST['update_details']) && empty($errorMsg)) {
    $userPhotoUrl .= '?v=' . time();
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
?>

<!DOCTYPE html>
<html lang="<?= $currLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $t['my_profile'] ?? 'My Profile' ?> - <?= $t['title'] ?? 'Temple' ?></title>
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

        /* Allow inputs to be selectable */
        input,
        textarea,
        select {
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

        /* --- Sidebar --- */
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
            overflow: hidden;
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

        /* --- Forms --- */
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
            transition: 0.3s;
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

        /* --- Profile Specific --- */
        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: var(--ant-shadow);
            background-color: #fff;
            /* Fix for transparent PNGs */
        }

        .profile-avatar-wrap {
            position: relative;
            display: inline-block;
        }

        .delete-photo-btn {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 1px solid #ffccc7;
            background: #fff;
            color: #ff4d4f;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.2s;
            cursor: pointer;
        }

        .delete-photo-btn:hover {
            background: #fff1f0;
            transform: scale(1.1);
        }

        /* --- Header Elements --- */
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
            </div>

            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="lang-btn dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-translate me-1"></i>
                        <?= ($currLang == 'mr') ? $t['lang_marathi'] : $t['lang_english']; ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                        <li><a class="dropdown-item small fw-medium" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small fw-medium" href="?lang=mr">Marathi</a></li>
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

                <div class="user-pill shadow-sm">
                    <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="rounded-circle" width="28" height="28"
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
                    <div class="small text-muted mb-1"><?php echo $t['dashboard'] ?? 'Dashboard'; ?> /
                        <?php echo $t['my_profile'] ?? 'Profile'; ?></div>
                    <h2 class="fw-bold mb-1"><?php echo $t['my_profile'] ?? 'My Profile'; ?></h2>
                    <p class="text-secondary mb-0">Manage your personal information and security settings.</p>
                </div>

                <div class="px-4 pb-5">

                    <?php if ($successMsg): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($successMsg) ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($errorMsg) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="ant-card text-center">
                                <div class="ant-card-body">
                                    <div class="profile-avatar-wrap mb-3">
                                        <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="profile-avatar"
                                            alt="Profile">
                                        <?php if (!empty($user['photo'])): ?>
                                            <button type="button" class="delete-photo-btn" data-bs-toggle="modal"
                                                data-bs-target="#deletePhotoModal" title="Delete Photo">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="fw-bold mb-1">
                                        <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                                    <p class="text-muted small mb-0">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?></p>
                                    <p class="text-muted small">Member since
                                        <?= date("M Y", strtotime($user['created_at'])) ?></p>
                                </div>
                            </div>

                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['change_password'] ?? 'Change Password'; ?>
                                </div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <label
                                                class="form-label"><?php echo $t['current_password'] ?? 'Current Password'; ?></label>
                                            <input type="password" name="current_password" class="form-control"
                                                required>
                                            <div class="invalid-feedback">Required</div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label"><?php echo $t['new_password'] ?? 'New Password'; ?></label>
                                            <input type="password" name="new_password" class="form-control" required
                                                minlength="6">
                                            <div class="invalid-feedback">Min 6 chars.</div>
                                        </div>
                                        <div class="mb-3">
                                            <label
                                                class="form-label"><?php echo $t['confirm_new_password'] ?? 'Confirm Password'; ?></label>
                                            <input type="password" name="confirm_password" class="form-control"
                                                required>
                                            <div class="invalid-feedback">Passwords must match.</div>
                                        </div>
                                        <button type="submit" name="change_password" class="ant-btn-primary w-100">
                                            <?php echo $t['update_password'] ?? 'Update Password'; ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head">
                                    <?php echo $t['personal_information'] ?? 'Personal Information'; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST" enctype="multipart/form-data" class="needs-validation"
                                        novalidate>
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label
                                                    class="form-label"><?php echo $t['first_name'] ?? 'First Name'; ?></label>
                                                <input type="text" name="first_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label
                                                    class="form-label"><?php echo $t['last_name'] ?? 'Last Name'; ?></label>
                                                <input type="text" name="last_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label
                                                    class="form-label"><?php echo $t['phone_number'] ?? 'Phone'; ?></label>
                                                <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}"
                                                    value="<?= htmlspecialchars($user['phone']) ?>" required>
                                                <div class="invalid-feedback">Valid 10-digit number required.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label
                                                    class="form-label"><?php echo $t['email_address'] ?? 'Email'; ?></label>
                                                <input type="email" class="form-control bg-light"
                                                    value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                                <div class="form-text small text-muted">Email cannot be changed.</div>
                                            </div>

                                            <div class="col-12 mb-3">
                                                <label class="form-label fw-bold">Update Profile Photo</label>
                                                <input type="file" name="profile_photo" class="form-control"
                                                    accept="image/*">
                                                <div class="form-text small text-muted">Allowed formats: JPG, PNG. Max
                                                    size: 2MB.</div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="submit" name="update_details" class="ant-btn-primary px-5">
                                                <?php echo $t['save_changes'] ?? 'Save Changes'; ?>
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

    <?php if (!empty($user['photo'])): ?>
        <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold"><?php echo $t['delete_photo'] ?? 'Delete Photo'; ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-3 pb-4">
                        <p class="text-muted mb-0">
                            <?php echo $t['delete_photo_confirm'] ?? 'Are you sure you want to remove your profile photo?'; ?>
                        </p>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal"><?php echo $t['cancel'] ?? 'Cancel'; ?></button>
                        <form method="POST" class="m-0">
                            <button type="submit" name="delete_photo"
                                class="btn btn-danger rounded-pill px-4"><?php echo $t['delete'] ?? 'Delete'; ?></button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form Validation & Prevent Resubmission
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
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>