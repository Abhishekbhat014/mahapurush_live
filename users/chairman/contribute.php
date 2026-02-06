<?php
// =========================================================
// 1. SESSION + AUTH
// =========================================================
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/receipt_helper.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

// =========================================================
// 2. DB CONNECTION
// =========================================================
require __DIR__ . '/../../config/db.php';

$uid = (int) $_SESSION['user_id'];
$successMsg = '';
$errorMsg = '';
$currentPage = 'contribute.php';
$currLang = $_SESSION['lang'] ?? 'en';

// =========================================================
// 3. HANDLE FORM SUBMISSION (WITH RECEIPT TRACKING)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contribution'])) {

    mysqli_begin_transaction($con);

    try {
        $typeId = (int) ($_POST['contribution_type_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $qty = (float) ($_POST['quantity'] ?? 0);
        $unit = trim($_POST['unit'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $contributorName = $_SESSION['user_name'];

        if ($typeId <= 0 || $title === '' || $qty <= 0) {
            throw new Exception($t['err_fill_required_fields']);
        }

        // --- Step A: Create receipt using helper ---
        $receiptId = createReceipt(
            $con,
            $uid,
            'contribution',
            0.00,
            'contributions'
        );

        // --- Step B: Insert Contribution linked to Receipt ---
        $stmt = $con->prepare("
            INSERT INTO contributions 
            (receipt_id, added_by, contributor_name, contribution_type_id, title, quantity, unit, description, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
        ");

        $stmt->bind_param("iiisidss", $receiptId, $uid, $contributorName, $typeId, $title, $qty, $unit, $desc);
        $stmt->execute();

        mysqli_commit($con);
        $successMsg = $t['contribution_submitted_success'] ?? $t['success'];

    } catch (Exception $e) {
        mysqli_rollback($con);
        $errorMsg = $e->getMessage();
    }
}

// =========================================================
// 4. DATA FETCHING
// =========================================================
$types = mysqli_query($con, "SELECT id, type FROM contribution_type ORDER BY type ASC");
$uRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1"));
$userPhotoUrl = !empty($uRow['photo'])
    ? '../../uploads/users/' . basename($uRow['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['make_contribution']; ?> - <?php echo $t['title']; ?></title>
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
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 16px;
        }

        .ant-card-body {
            padding: 32px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
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
            box-shadow: 0 2px 0 rgba(5, 145, 255, 0.1);
        }

        .ant-btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
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
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu"><i
                        class="bi bi-list"></i></button>
                <a href="../../index.php" class="fw-bold text-dark text-decoration-none fs-5 d-flex align-items-center">
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
                    <h2 class="fw-bold mb-1"><?php echo $t['make_contribution']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['contribution_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row justify-content-center">
                        <div class="col-xl-8">

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

                            <div class="ant-card">
                                <div class="ant-card-head"><?php echo $t['contribution_details']; ?></div>
                                <div class="ant-card-body">
                                    <form method="POST" class="needs-validation" novalidate>
                                        <div class="row g-4">
                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo $t['contribution_category']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <select name="contribution_type_id" class="form-select" required>
                                                    <option value="" disabled selected><?php echo $t['select_category']; ?></option>
                                                    <?php while ($row = mysqli_fetch_assoc($types)): ?>
                                                        <option value="<?= $row['id'] ?>">
                                                            <?= htmlspecialchars($row['type']) ?>
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo $t['item_name_title']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <input type="text" name="title" class="form-control"
                                                    placeholder="<?php echo $t['item_name_placeholder']; ?>" required>
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo $t['quantity']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <input type="number" step="0.01" name="quantity" class="form-control"
                                                    placeholder="<?php echo $t['amount_placeholder']; ?>" required>
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>

                                            <div class="col-md-6">
                                                <label class="form-label"><?php echo $t['unit']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <select name="unit" class="form-select" required>
                                                    <option value="kg"><?php echo $t['unit_kg']; ?></option>
                                                    <option value="litre"><?php echo $t['unit_litre']; ?></option>
                                                    <option value="pcs"><?php echo $t['unit_pieces']; ?></option>
                                                    <option value="bags"><?php echo $t['unit_bags']; ?></option>
                                                    <option value="quintal"><?php echo $t['unit_quintal']; ?></option>
                                                </select>
                                                <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label"><?php echo $t['additional_description']; ?></label>
                                                <textarea name="description" class="form-control" rows="3"
                                                    placeholder="<?php echo $t['additional_description_placeholder']; ?>"></textarea>
                                            </div>

                                            <div class="col-12 text-end pt-2">
                                                <a href="dashboard.php" class="btn btn-light px-4 border me-2"
                                                    style="border-radius: 8px;"><?php echo $t['cancel']; ?></a>
                                                <button type="submit" name="submit_contribution"
                                                    class="ant-btn-primary">
                                                    <?php echo $t['submit_contribution']; ?>
                                                </button>
                                            </div>
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
