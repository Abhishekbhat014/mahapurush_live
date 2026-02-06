<?php
session_start();
require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/lang.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = (int) $_SESSION['user_id'];
$currentPage = 'gallery.php';
$currLang = $_SESSION['lang'] ?? 'en';
$success = '';
$error = '';

$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo'])
    ? '../../uploads/users/' . basename($uRow['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

$galleryDir = __DIR__ . '/../../gallery';
if (!is_dir($galleryDir)) {
    mkdir($galleryDir, 0775, true);
}

function saveGalleryImage(string $field, string $galleryDir, ?string &$error): ?string
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please choose a valid image file.';
        return null;
    }

    $tmp = $_FILES[$field]['tmp_name'];
    $imageInfo = @getimagesize($tmp);
    if ($imageInfo === false) {
        $error = 'Uploaded file is not a valid image.';
        return null;
    }

    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed, true)) {
        $error = 'Allowed image types: JPG, PNG, WEBP.';
        return null;
    }

    $name = 'gallery_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $target = rtrim($galleryDir, '/\\') . DIRECTORY_SEPARATOR . $name;

    if (!move_uploaded_file($tmp, $target)) {
        $error = 'Failed to upload image. Please try again.';
        return null;
    }

    return $name;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $type = trim($_POST['category_name'] ?? '');
        if ($type === '') {
            $error = $t['invalid_category'] ?? 'Please enter a category name.';
        } else {
            $stmt = $con->prepare("INSERT INTO gallery_category (type, created_at) VALUES (?, NOW())");
            $stmt->bind_param("s", $type);
            if ($stmt->execute()) {
                $success = $t['category_added'] ?? 'Category added successfully.';
            } else {
                $error = $t['category_add_failed'] ?? 'Failed to add category.';
            }
        }
    }

    if (isset($_POST['update_category'])) {
        $catId = (int) ($_POST['category_id'] ?? 0);
        $type = trim($_POST['category_name'] ?? '');
        if ($catId <= 0 || $type === '') {
            $error = $t['invalid_category'] ?? 'Please provide a valid category name.';
        } else {
            $stmt = $con->prepare("UPDATE gallery_category SET type=? WHERE id=?");
            $stmt->bind_param("si", $type, $catId);
            if ($stmt->execute()) {
                $success = $t['category_updated'] ?? 'Category updated successfully.';
            } else {
                $error = $t['category_update_failed'] ?? 'Failed to update category.';
            }
        }
    }

    if (isset($_POST['delete_category'])) {
        $catId = (int) ($_POST['category_id'] ?? 0);
        if ($catId > 0) {
            $stmt = $con->prepare("DELETE FROM gallery_category WHERE id=?");
            $stmt->bind_param("i", $catId);
            if ($stmt->execute()) {
                $success = $t['category_deleted'] ?? 'Category deleted successfully.';
            } else {
                $error = $t['category_delete_failed'] ?? 'Failed to delete category.';
            }
        } else {
            $error = $t['invalid_category'] ?? 'Invalid category.';
        }
    }

    if (isset($_POST['add_image'])) {
        $catId = (int) ($_POST['gallery_category_id'] ?? 0);
        $type = 'image';

        if ($catId <= 0) {
            $error = $t['select_category'] ?? 'Please select a category.';
        } else {
            $fileName = saveGalleryImage('gallery_image', $galleryDir, $error);
            if ($fileName) {
                $stmt = $con->prepare("INSERT INTO gallery (gallery_category_id, type, content, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->bind_param("iss", $catId, $type, $fileName);
                if ($stmt->execute()) {
                    $success = $t['gallery_added'] ?? 'Gallery image added successfully.';
                } else {
                    $error = $t['gallery_add_failed'] ?? 'Failed to add gallery image.';
                }
            }
        }
    }

    if (isset($_POST['update_image'])) {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        $catId = (int) ($_POST['gallery_category_id'] ?? 0);
        if ($imgId <= 0 || $catId <= 0) {
            $error = $t['invalid_gallery_item'] ?? 'Invalid gallery item.';
        } else {
            $current = $con->prepare("SELECT content FROM gallery WHERE id=? LIMIT 1");
            $current->bind_param("i", $imgId);
            $current->execute();
            $currentRes = $current->get_result();
            $row = $currentRes ? $currentRes->fetch_assoc() : null;
            $existingFile = $row['content'] ?? '';

            $newFileName = $existingFile;
            if (!empty($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $newFileName = saveGalleryImage('gallery_image', $galleryDir, $error);
                if (!$newFileName) {
                    $newFileName = $existingFile;
                }
            }

            if (!$error) {
                $stmt = $con->prepare("UPDATE gallery SET gallery_category_id=?, content=? WHERE id=?");
                $stmt->bind_param("isi", $catId, $newFileName, $imgId);
                if ($stmt->execute()) {
                    if ($existingFile && $newFileName !== $existingFile) {
                        $oldPath = rtrim($galleryDir, '/\\') . DIRECTORY_SEPARATOR . basename($existingFile);
                        if (is_file($oldPath)) {
                            unlink($oldPath);
                        }
                    }
                    $success = $t['gallery_updated'] ?? 'Gallery image updated successfully.';
                } else {
                    $error = $t['gallery_update_failed'] ?? 'Failed to update gallery image.';
                }
            }
        }
    }

    if (isset($_POST['delete_image'])) {
        $imgId = (int) ($_POST['image_id'] ?? 0);
        if ($imgId > 0) {
            $current = $con->prepare("SELECT content FROM gallery WHERE id=? LIMIT 1");
            $current->bind_param("i", $imgId);
            $current->execute();
            $currentRes = $current->get_result();
            $row = $currentRes ? $currentRes->fetch_assoc() : null;
            $existingFile = $row['content'] ?? '';

            $stmt = $con->prepare("DELETE FROM gallery WHERE id=?");
            $stmt->bind_param("i", $imgId);
            if ($stmt->execute()) {
                if ($existingFile) {
                    $oldPath = rtrim($galleryDir, '/\\') . DIRECTORY_SEPARATOR . basename($existingFile);
                    if (is_file($oldPath)) {
                        unlink($oldPath);
                    }
                }
                $success = $t['gallery_deleted'] ?? 'Gallery image deleted successfully.';
            } else {
                $error = $t['gallery_delete_failed'] ?? 'Failed to delete gallery image.';
            }
        } else {
            $error = $t['invalid_gallery_item'] ?? 'Invalid gallery item.';
        }
    }
}

$categories = [];
$catRes = mysqli_query($con, "SELECT * FROM gallery_category ORDER BY id ASC");
while ($row = mysqli_fetch_assoc($catRes)) {
    $categories[] = $row;
}

$images = [];
$imgRes = mysqli_query(
    $con,
    "SELECT g.*, gc.type AS category
     FROM gallery g
     LEFT JOIN gallery_category gc ON gc.id = g.gallery_category_id
     ORDER BY g.created_at DESC"
);
while ($row = mysqli_fetch_assoc($imgRes)) {
    $images[] = $row;
}
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['gallery_manager'] ?? 'Gallery Manager'; ?> - <?= $t['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-success: #52c41a;
            --ant-error: #ff4d4f;
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
            overflow: hidden;
        }

        .gallery-card {
            background: #fff;
            border-radius: var(--ant-radius);
            border: 1px solid var(--ant-border-color);
            overflow: hidden;
            box-shadow: var(--ant-shadow);
        }

        .gallery-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
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
    </style>
</head>

<body>
    <header class="ant-header shadow-sm">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i>
                </button>
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
                    <i class="bi bi-flower1 text-warning me-2"></i>
                    <?= $t['title'] ?>
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
                <div class="user-pill">
                    <img src="<?= $loggedInUserPhoto ?>" class="rounded-circle" width="28" height="28"
                        style="object-fit: cover;">
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                    <div class="vr mx-2 text-muted opacity-25"></div>
                    <a href="../../auth/logout.php" class="text-danger"><i class="bi bi-power"></i></a>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <h2 class="fw-bold mb-1"><?php echo $t['gallery_manager'] ?? 'Gallery Manager'; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['gallery_manager_subtitle'] ?? 'Add, update, and organize gallery images.'; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <?php if ($success): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                            <i class="bi bi-check-circle-fill me-2"></i>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                        <div class="alert border-0 shadow-sm mb-4"
                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-lg-4">
                            <div class="ant-card p-4 mb-4">
                                <h6 class="fw-bold mb-3"><?php echo $t['manage_categories'] ?? 'Manage Categories'; ?></h6>
                                <form method="POST" class="mb-3">
                                    <label class="form-label small fw-bold">
                                        <?php echo $t['category_name'] ?? 'Category Name'; ?>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="category_name" required>
                                        <button class="btn btn-primary" type="submit" name="add_category">
                                            <?php echo $t['add'] ?? 'Add'; ?>
                                        </button>
                                    </div>
                                </form>

                                <div class="d-flex flex-column gap-3">
                                    <?php if ($categories): ?>
                                        <?php foreach ($categories as $cat): ?>
                                            <form method="POST" class="border rounded-3 p-3">
                                                <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                <div class="mb-2">
                                                    <input type="text" class="form-control form-control-sm" name="category_name"
                                                        value="<?= htmlspecialchars($cat['type']) ?>" required>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" type="submit"
                                                        name="update_category"><?php echo $t['update'] ?? 'Update'; ?></button>
                                                    <button class="btn btn-sm btn-outline-danger" type="submit"
                                                        name="delete_category"
                                                        onclick="return confirm('Delete this category? Images will be uncategorized.')">
                                                        <?php echo $t['delete'] ?? 'Delete'; ?>
                                                    </button>
                                                </div>
                                            </form>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-muted small"><?php echo $t['no_categories'] ?? 'No categories yet.'; ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="ant-card p-4">
                                <h6 class="fw-bold mb-3"><?php echo $t['add_gallery_item'] ?? 'Add Gallery Image'; ?></h6>
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold"><?php echo $t['category'] ?? 'Category'; ?></label>
                                        <select class="form-select" name="gallery_category_id" required>
                                            <option value=""><?php echo $t['select_category'] ?? 'Select Category'; ?></option>
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['type']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold"><?php echo $t['image'] ?? 'Image'; ?></label>
                                        <input type="file" class="form-control" name="gallery_image" accept="image/*" required>
                                    </div>
                                    <button class="btn btn-primary w-100" type="submit" name="add_image">
                                        <?php echo $t['upload'] ?? 'Upload'; ?>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <div class="row g-4">
                                <?php if ($images): ?>
                                    <?php foreach ($images as $img): ?>
                                        <div class="col-md-6 col-xl-4">
                                            <div class="gallery-card">
                                                <img src="../../gallery/<?= htmlspecialchars($img['content']) ?>"
                                                    alt="<?php echo $t['gallery_image_alt']; ?>">
                                                <div class="p-3">
                                                    <div class="small text-muted mb-1">
                                                        <?= htmlspecialchars($img['category'] ?? ($t['uncategorized'] ?? 'Uncategorized')) ?>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                            data-bs-target="#editImage<?= $img['id'] ?>">
                                                            <?php echo $t['edit'] ?? 'Edit'; ?>
                                                        </button>
                                                        <form method="POST" onsubmit="return confirm('Delete this image?')">
                                                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                            <button class="btn btn-sm btn-outline-danger" type="submit"
                                                                name="delete_image"><?php echo $t['delete'] ?? 'Delete'; ?></button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal fade" id="editImage<?= $img['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content">
                                                    <form method="POST" enctype="multipart/form-data">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo $t['edit_gallery_item'] ?? 'Edit Gallery Image'; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-bold"><?php echo $t['category'] ?? 'Category'; ?></label>
                                                                <select class="form-select" name="gallery_category_id" required>
                                                                    <option value=""><?php echo $t['select_category'] ?? 'Select Category'; ?></option>
                                                                    <?php foreach ($categories as $cat): ?>
                                                                        <option value="<?= $cat['id'] ?>" <?= ($img['gallery_category_id'] == $cat['id']) ? 'selected' : '' ?>>
                                                                            <?= htmlspecialchars($cat['type']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="mb-2">
                                                                <label class="form-label small fw-bold"><?php echo $t['replace_image'] ?? 'Replace Image (optional)'; ?></label>
                                                                <input type="file" class="form-control" name="gallery_image" accept="image/*">
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                                                <?php echo $t['close'] ?? 'Close'; ?>
                                                            </button>
                                                            <button type="submit" name="update_image" class="btn btn-primary">
                                                                <?php echo $t['update'] ?? 'Update'; ?>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="ant-card p-4 text-center text-muted">
                                            <i class="bi bi-images fs-1 opacity-25 d-block mb-2"></i>
                                            <?php echo $t['no_gallery_items'] ?? 'No gallery images yet.'; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
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
