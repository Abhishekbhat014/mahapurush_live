<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../config/db.php'; // DB Connection

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

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
        $successMsg = "Profile photo removed successfully.";
    } else {
        $errorMsg = "Unable to remove photo.";
    }
}

// --- B. HANDLE DETAILS UPDATE (AND PHOTO UPLOAD) ---
if (isset($_POST['update_details'])) {
    $fName = mysqli_real_escape_string($con, $_POST['first_name']);
    $lName = mysqli_real_escape_string($con, $_POST['last_name']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);

    $photoSqlPart = ""; // Default empty if no new photo

    // 1. Handle Photo Upload
    if (!empty($_FILES['profile_photo']['name'])) {
        $targetDir = __DIR__ . '/../../uploads/users/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = time() . '_' . basename($_FILES['profile_photo']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = strtolower(pathinfo($targetFilePath, PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($fileType, $allowedTypes)) {
            // Upload new file
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $targetFilePath)) {
                // Delete old photo if exists
                $oldRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid'"));
                if (!empty($oldRow['photo'])) {
                    $oldFile = $targetDir . basename($oldRow['photo']);
                    if (is_file($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $photoSqlPart = ", photo='$fileName'";
            } else {
                $errorMsg = "Error uploading image.";
            }
        } else {
            $errorMsg = "Invalid file format. Only JPG, JPEG, PNG allowed.";
        }
    }

    // 2. Update Database
    if (empty($errorMsg)) {
        $updateQ = "UPDATE users SET first_name='$fName', last_name='$lName', phone='$phone' $photoSqlPart WHERE id='$uid'";
        if (mysqli_query($con, $updateQ)) {
            $_SESSION['user_name'] = trim($fName . ' ' . $lName);
            $successMsg = "Profile updated successfully.";
        } else {
            $errorMsg = "Update failed: " . mysqli_error($con);
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
                $successMsg = "Password updated successfully.";
            } else {
                $errorMsg = "Database error.";
            }
        } else {
            $errorMsg = "New passwords do not match.";
        }
    } else {
        $errorMsg = "Incorrect current password.";
    }
}

// --- FETCH LATEST DATA ---
require __DIR__ . '/../../includes/user_avatar.php'; // Re-include/Function to get avatar logic
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id='$uid' LIMIT 1"));
$loggedInUserPhoto = get_user_avatar_url('../../');

// Add timestamp to force image refresh after upload
$displayPhoto = $loggedInUserPhoto . '?v=' . time();

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'profile.php';
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?= $t['title'] ?? 'Temple' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES --- */
        :root {
            /* Standard App Colors (Blue) */
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f0f2f5;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);

            /* Treasurer Sidebar Overrides (Green) */
            --tr-active-text: #52c41a;
            --tr-active-bg: #f6ffed;
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

        /* --- ACTIVE DROPDOWN ITEM (Dark Blue) --- */
        .dropdown-item.active,
        .dropdown-item:active {
            background-color: var(--ant-primary) !important;
            color: #fff !important;
            font-weight: 600;
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
            background: radial-gradient(circle at top right, #f6ffed 0%, #ffffff 80%);
            /* Treasurer Green Tint */
            padding: 40px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            margin-bottom: 32px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            margin-bottom: 24px;
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

        /* Profile Specific */
        .profile-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-wrap {
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
            background: #fff;
            border: 1px solid #ffccc7;
            color: #ff4d4f;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: 0.2s;
            cursor: pointer;
        }

        .delete-photo-btn:hover {
            background: #fff1f0;
            transform: scale(1.1);
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 6px;
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
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
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
                    <img src="<?= htmlspecialchars($displayPhoto) ?>" class="rounded-circle" width="28" height="28"
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
                    <h2 class="fw-bold mb-1">My Profile</h2>
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
                                    <div class="profile-wrap mb-3">
                                        <img src="<?= htmlspecialchars($displayPhoto) ?>" class="profile-avatar"
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
                                </div>
                            </div>

                            <div class="ant-card">
                                <div class="ant-card-head">Change Password</div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control"
                                                required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required
                                                minlength="6">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" name="confirm_password" class="form-control"
                                                required>
                                        </div>
                                        <button type="submit" name="change_password" class="btn btn-primary w-100">
                                            Update Password
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="ant-card">
                                <div class="ant-card-head">Personal Information</div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" enctype="multipart/form-data"
                                        novalidate>
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" name="first_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control"
                                                    value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" name="phone" class="form-control"
                                                    value="<?= htmlspecialchars($user['phone']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control bg-light"
                                                    value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                            </div>
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Update Profile Photo</label>
                                                <input type="file" name="profile_photo" class="form-control"
                                                    accept="image/*">
                                                <div class="form-text small text-muted">Allowed formats: JPG, PNG. Max
                                                    size: 2MB.</div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-end mt-4">
                                            <button type="submit" name="update_details" class="btn btn-primary px-5">
                                                Save Changes
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

    <div class="modal fade" id="deletePhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Delete Photo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body pt-3 pb-4">
                    <p class="text-muted mb-0">Are you sure you want to remove your profile photo?</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" class="m-0">
                        <button type="submit" name="delete_photo"
                            class="btn btn-danger rounded-pill px-4">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
</body>

</html>