<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'gallery.php';
$loggedInUserPhoto = get_user_avatar_url('../../');

$successMsg = '';
$errorMsg = '';

// --- 1. HANDLE UPLOAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $catId = (int) $_POST['category_id'];
    $file = $_FILES['photo'];

    if ($catId && $file['error'] === 0) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (in_array($ext, $allowed)) {
            $name = time() . '_' . uniqid() . '.' . $ext;
            $targetDir = '../../uploads/gallery/';

            if (!is_dir($targetDir))
                mkdir($targetDir, 0777, true);

            if (move_uploaded_file($file['tmp_name'], $targetDir . $name)) {
                $stmt = $con->prepare("INSERT INTO gallery (gallery_category_id, type, content, created_at) VALUES (?, 'image', ?, NOW())");
                $stmt->bind_param("is", $catId, $name);
                $stmt->execute();
                $successMsg = "Photo uploaded successfully.";
            } else {
                $errorMsg = "Failed to move uploaded file.";
            }
        } else {
            $errorMsg = "Invalid file type. Only JPG, PNG, WEBP allowed.";
        }
    } else {
        $errorMsg = "Please select a category and a valid file.";
    }
}

// --- 2. HANDLE DELETE ---
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $res = mysqli_query($con, "SELECT content FROM gallery WHERE id=$id");
    $img = mysqli_fetch_assoc($res);

    if ($img) {
        $filePath = '../../uploads/gallery/' . $img['content'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        mysqli_query($con, "DELETE FROM gallery WHERE id=$id");
        $successMsg = "Photo deleted successfully.";
    }
}

// --- 3. FETCH DATA ---
$cats = mysqli_query($con, "SELECT * FROM gallery_category ORDER BY type");
$images = mysqli_query($con, "
    SELECT g.*, c.type AS category 
    FROM gallery g 
    JOIN gallery_category c ON c.id = g.gallery_category_id 
    ORDER BY g.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - <?= $t['title'] ?></title>
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
            transition: transform 0.2s;
        }

        .gallery-card:hover {
            transform: translateY(-4px);
            border-color: var(--ant-primary);
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
        }

        .gallery-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 12px;
        }

        .gallery-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cat-badge {
            font-size: 11px;
            background: #f0f5ff;
            color: var(--ant-primary);
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: 600;
            text-transform: uppercase;
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

                <div class="user-pill">
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
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
                    <h2 class="fw-bold mb-1">Gallery</h2>
                    <p class="text-secondary mb-0">Upload and manage images for the website gallery.</p>
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

                    <div class="ant-card p-4 mb-4">
                        <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label">Category</label>
                                <select name="category_id" class="form-select" required>
                                    <option value="">Select...</option>
                                    <?php
                                    mysqli_data_seek($cats, 0); // Reset pointer
                                    while ($c = mysqli_fetch_assoc($cats)): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['type']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Select Photo</label>
                                <input type="file" name="photo" class="form-control" required accept="image/*">
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-primary w-100 fw-bold">
                                    <i class="bi bi-cloud-upload me-2"></i> Upload
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="row g-4">
                        <?php if (mysqli_num_rows($images) > 0): ?>
                            <?php while ($img = mysqli_fetch_assoc($images)): ?>
                                <div class="col-md-6 col-lg-3">
                                    <div class="ant-card gallery-card p-2 h-100">
                                        <img src="../../uploads/gallery/<?= htmlspecialchars($img['content']) ?>"
                                            class="gallery-img bg-light" alt="Gallery Image" loading="lazy">

                                        <div class="gallery-meta px-1 pb-1">
                                            <span class="cat-badge">
                                                <?= htmlspecialchars($img['category']) ?>
                                            </span>
                                            <a href="?delete=<?= $img['id'] ?>" class="text-danger small"
                                                onclick="return confirm('Are you sure you want to delete this image?');"
                                                title="Delete Image">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted mb-0">No images found in the gallery.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Disable Right Click
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });

        // Prevent Resubmission
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>