<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth Check
if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'profile.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

$successMsg = '';
$errorMsg = '';

// --- A. HANDLE PHOTO DELETE ---
if (isset($_POST['delete_photo'])) {
    $existing = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid' LIMIT 1"));
    $oldPhoto = !empty($existing['photo']) ? $existing['photo'] : null;

    if (mysqli_query($con, "UPDATE users SET photo=NULL WHERE id='$uid'")) {
        if ($oldPhoto) {
            $oldPath = __DIR__ . '/../../uploads/users/' . basename($oldPhoto);
            if (is_file($oldPath))
                unlink($oldPath);
        }
        $_SESSION['user_photo'] = null;
        $successMsg = $t['profile_photo_deleted'] ?? 'Profile photo removed.';
    } else {
        $errorMsg = $t['profile_update_failed'] ?? 'Unable to update profile.';
    }
}

// --- B. HANDLE DETAILS UPDATE ---
if (isset($_POST['update_details'])) {
    $fNameRaw = trim($_POST['first_name'] ?? '');
    $lNameRaw = trim($_POST['last_name'] ?? '');
    $phoneRaw = trim($_POST['phone'] ?? '');

    if ($fNameRaw === '' || $lNameRaw === '' || $phoneRaw === '') {
        $errorMsg = $t['err_fill_all'] ?? 'Please fill in all required fields.';
    } elseif (!preg_match('/^[\p{L}\s\.\'-]+$/u', $fNameRaw) || !preg_match('/^[\p{L}\s\.\'-]+$/u', $lNameRaw)) {
        $errorMsg = $t['err_invalid_name'] ?? 'Please enter a valid name.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phoneRaw)) {
        $errorMsg = $t['err_invalid_phone'] ?? 'Please enter a valid 10-digit phone number.';
    }

    $photoName = null;
    $oldPhoto = null;

    // Handle File Upload
    if (empty($errorMsg) && !empty($_FILES['photo']['name'])) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errorMsg = $t['err_file_upload_failed'] ?? 'Photo upload failed.';
        } else {
            $fileTmp = $_FILES['photo']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'png', 'jpeg'];

            if (!in_array($fileExt, $allowed)) {
                $errorMsg = $t['err_invalid_file_type'] ?? 'Invalid format. Only JPG & PNG allowed.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errorMsg = $t['err_file_size'] ?? 'File too large (Max 2MB).';
            } else {
                $photoName = uniqid('user_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../../uploads/users/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);

                if (!move_uploaded_file($fileTmp, $uploadDir . $photoName)) {
                    $errorMsg = $t['err_file_upload_failed'] ?? 'Failed to save file.';
                } else {
                    $existing = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo FROM users WHERE id='$uid' LIMIT 1"));
                    $oldPhoto = !empty($existing['photo']) ? $existing['photo'] : null;
                }
            }
        }
    }

    if (empty($errorMsg)) {
        $fName = mysqli_real_escape_string($con, $fNameRaw);
        $lName = mysqli_real_escape_string($con, $lNameRaw);
        $phone = mysqli_real_escape_string($con, $phoneRaw);

        $photoSql = $photoName ? ", photo='$photoName'" : '';

        if (mysqli_query($con, "UPDATE users SET first_name='$fName', last_name='$lName', phone='$phone' $photoSql WHERE id='$uid'")) {
            if ($photoName && $oldPhoto) {
                $oldPath = __DIR__ . '/../../uploads/users/' . basename($oldPhoto);
                if (is_file($oldPath))
                    unlink($oldPath);
            }
            $_SESSION['user_name'] = trim($fName . ' ' . $lName);
            if ($photoName)
                $_SESSION['user_photo'] = $photoName;

            $successMsg = $t['profile_details_updated'] ?? 'Profile updated successfully.';
        } else {
            if ($photoName) {
                $newPath = __DIR__ . '/../../uploads/users/' . basename($photoName);
                if (is_file($newPath))
                    unlink($newPath);
            }
            $errorMsg = $t['profile_update_failed'] ?? 'Database update failed.';
        }
    }
}

// --- C. HANDLE PASSWORD UPDATE ---
if (isset($_POST['change_password'])) {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if ($currentPass === '' || $newPass === '' || $confirmPass === '') {
        $errorMsg = $t['err_fill_all'] ?? 'All password fields are required.';
    } elseif (strlen($newPass) < 6) {
        $errorMsg = $t['password_min_length'] ?? 'Password must be at least 6 characters.';
    } else {
        $userRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT password FROM users WHERE id='$uid'"));
        if (password_verify($currentPass, $userRow['password'])) {
            if ($newPass === $confirmPass) {
                $hashedNew = password_hash($newPass, PASSWORD_DEFAULT);
                mysqli_query($con, "UPDATE users SET password='$hashedNew' WHERE id='$uid'");
                $successMsg = $t['password_updated'] ?? 'Password updated successfully.';
            } else {
                $errorMsg = $t['new_passwords_mismatch'] ?? 'New passwords do not match.';
            }
        } else {
            $errorMsg = $t['incorrect_current_password'] ?? 'Incorrect current password.';
        }
    }
}

// FETCH USER DATA
$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id='$uid' LIMIT 1"));
$userPhotoUrl = !empty($user['photo'])
    ? '../../uploads/users/' . basename($user['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($user['first_name'] . ' ' . $user['last_name']) . '&background=ffffff&color=1677ff';

// Force refresh image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errorMsg)) {
    $userPhotoUrl .= '?v=' . time();
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        /* --- GLOBAL THEME VARIABLES (Blue) --- */
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

        /* Allow selection in inputs */
        input, textarea, select {
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
        .lang-btn:hover { background: #e6f4ff; color: #1677ff; }

        /* --- HOVER DROPDOWN LOGIC --- */
        @media (min-width: 992px) {
            .dropdown:hover .dropdown-menu {
                display: block;
                margin-top: 0;
            }
            .dropdown .dropdown-menu {
                display: none;
            }
            .dropdown:hover > .dropdown-menu {
                display: block;
                animation: fadeIn 0.2s ease-in-out;
            }
        }

        /* Active Dropdown Item */
        .dropdown-item.active, .dropdown-item:active {
            background-color: var(--ant-primary) !important;
            color: #fff !important;
            font-weight: 600;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* --- PAGE CONTENT --- */
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
            overflow: hidden;
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
            background-color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-wrap { position: relative; display: inline-block; }
        
        .delete-photo-btn {
            position: absolute; right: 0; bottom: 0;
            width: 32px; height: 32px;
            border-radius: 50%;
            background: #fff; border: 1px solid #ffccc7;
            color: #ff4d4f;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: 0.2s;
            cursor: pointer;
        }
        .delete-photo-btn:hover { background: #fff1f0; transform: scale(1.1); }

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
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'en') ? 'active' : '' ?>" href="?lang=en">English</a></li>
                        <li><a class="dropdown-item small fw-medium <?= ($currLang == 'mr') ? 'active' : '' ?>" href="?lang=mr">Marathi</a></li>
                    </ul>
                </div>

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                        <div class="dropdown">
                            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28" style="object-fit: cover;">
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
                    <h2 class="fw-bold mb-1">My Profile</h2>
                    <p class="text-secondary mb-0">Manage your personal information and security settings.</p>
                </div>

                <div class="px-4 pb-5">
                    
                    <?php if ($successMsg): ?>
                            <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <div><?= htmlspecialchars($successMsg) ?></div>
                            </div>
                    <?php endif; ?>
                    <?php if ($errorMsg): ?>
                            <div class="alert alert-danger border-0 shadow-sm d-flex align-items-center mb-4" role="alert">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <div><?= htmlspecialchars($errorMsg) ?></div>
                            </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        
                        <div class="col-lg-4">
                            <div class="ant-card text-center">
                                <div class="ant-card-body">
                                    <div class="profile-wrap mb-3">
                                        <img src="<?= htmlspecialchars($userPhotoUrl) ?>" class="profile-avatar" alt="Profile">
                                        <?php if (!empty($user['photo'])): ?>
                                                <button type="button" class="delete-photo-btn" data-bs-toggle="modal" data-bs-target="#deletePhotoModal" title="Delete Photo">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                        <?php endif; ?>
                                    </div>
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h5>
                                    <p class="text-muted small mb-0"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?></p>
                                </div>
                            </div>

                            <div class="ant-card">
                                <div class="ant-card-head">Change Password</div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <div class="mb-3">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Confirm Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
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
                                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                        <div class="row g-3">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">First Name</label>
                                                <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Last Name</label>
                                                <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Phone Number</label>
                                                <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}" value="<?= htmlspecialchars($user['phone']) ?>" required>
                                                <div class="invalid-feedback">Valid 10-digit number required.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                                                <div class="form-text small text-muted">Email cannot be changed.</div>
                                            </div>
                                            
                                            <div class="col-12 mb-3">
                                                <label class="form-label">Update Profile Photo</label>
                                                <input type="file" name="photo" class="form-control" accept="image/*">
                                                <div class="form-text small text-muted">Supported formats: JPG, JPEG, PNG. Max size: 2MB.</div>
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

    <?php if (!empty($user['photo'])): ?>
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
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <form method="POST" class="m-0">
                            <button type="submit" name="delete_photo" class="btn btn-danger rounded-pill px-4">Delete</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form Validation
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

        // Prevent resubmission
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