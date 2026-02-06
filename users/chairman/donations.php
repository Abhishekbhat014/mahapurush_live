<?php
// =========================================================
// 1. LANGUAGE + SESSION
// =========================================================
session_start();
require __DIR__ . '/../../includes/lang.php';

// =========================================================
// 2. DATABASE CONNECTION
// =========================================================
$dbPath = __DIR__ . '/../../config/db.php';
require $dbPath;
require '../../includes/receipt_helper.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$uid = $_SESSION['user_id'] ?? NULL;
$userName = $_SESSION['user_name'] ?? $t['user'];
$currentPage = 'donations.php';
$currLang = $_SESSION['lang'] ?? 'en';

// --- Fetch Latest Profile Photo and Name for Header ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$displayName = ($uRow['first_name'] ?? '') . ' ' . ($uRow['last_name'] ?? '');
$displayName = trim($displayName) !== '' ? $displayName : $userName;
$loggedInUserPhoto = !empty($uRow['photo'])
    ? '../../uploads/users/' . basename($uRow['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($displayName) . '&background=random';

// --- FORM HANDLING ---
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $con) {
    $donorName = trim($_POST['name'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $note = trim($_POST['note'] ?? '');

    if ($donorName === '' || !is_numeric($amount) || $amount <= 0) {
        $error = $t['err_valid_name_amount'];
    } else {
        $con->begin_transaction();
        try {
            // 1. Insert payment
            $stmt = $con->prepare("INSERT INTO payments (user_id, donor_name, amount, note, payment_method, status, created_at) VALUES (?, ?, ?, ?, 'cash', 'success', NOW())");
            $stmt->bind_param("isds", $uid, $donorName, $amount, $note);
            $stmt->execute();
            $paymentId = $stmt->insert_id;

            // 2. Generate receipt
            $receiptId = createReceipt($con, $uid, 'donation', (float) $amount, 'donations');

            // 3. Attach receipt
            attachReceiptToPayment($con, $paymentId, $receiptId);

            $con->commit();
            $success = $t['donation_receipt_generated'];
        } catch (Exception $e) {
            $con->rollback();
            $error = $t['something_went_wrong'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['donations']; ?> - <?php echo $t['title']; ?></title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
            border-radius: 16px;
            box-shadow: var(--ant-shadow);
            margin: 0 auto;
            max-width: 550px;
        }

        .ant-card-body {
            padding: 40px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 12px;
            border: 1px solid #d9d9d9;
            transition: 0.3s;
        }

        .ant-btn-primary {
            background: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }

        .icon-circle {
            width: 64px;
            height: 64px;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 20px;
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
                    <i class="bi bi-flower1 text-warning me-2"></i><?= $t['title'] ?>
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
                    <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
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
                    <div class="container">
                        <h2 class="fw-bold mb-1"><?php echo $t['donations']; ?></h2>
                        <p class="text-secondary mb-0">
                            <?php echo $t['donations_subtitle']; ?>
                        </p>
                    </div>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card">
                        <div class="ant-card-body">
                            <div class="text-center">
                                <div class="icon-circle shadow-sm"><i class="bi bi-heart-fill"></i></div>
                                <h3 class="fw-bold mb-1"><?php echo $t['support_title']; ?></h3>
                                <p class="text-secondary small mb-4"><?php echo $t['support_desc']; ?></p>
                            </div>

                            <?php if ($error): ?>
                                <div class="alert border-0 small py-2 d-flex align-items-center mb-4"
                                    style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                                    <i class="bi bi-exclamation-circle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($success): ?>
                                <div class="alert border-0 small py-3 text-center mb-4"
                                    style="background: #f6ffed; color: #52c41a; border-radius: 8px;">
                                    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $t['full_name']; ?></label>
                                    <input type="text" name="name" class="form-control"
                                        value="<?= htmlspecialchars($displayName) ?>"
                                        placeholder="<?php echo $t['full_name_placeholder']; ?>" required>
                                    <div class="invalid-feedback"><?php echo $t['field_required'] ?? 'This field is required.'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label"><?php echo $t['amount_inr']; ?></label>
                                    <div class="input-group has-validation">
                                        <span class="input-group-text bg-light border-end-0">₹</span>
                                        <input type="number" name="amount" class="form-control" min="1"
                                            placeholder="<?php echo $t['amount_placeholder']; ?>" required>
                                        <div class="invalid-feedback"><?php echo $t['amount_required'] ?? 'Please enter a valid amount.'; ?></div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label"><?php echo $t['note_optional']; ?></label>
                                    <textarea name="note" class="form-control" rows="3"
                                        placeholder="<?php echo $t['donation_note_placeholder']; ?>"></textarea>
                                </div>
                                <button type="submit" class="ant-btn-primary"><i
                                        class="bi bi-shield-check me-2"></i><?php echo $t['donate_btn']; ?></button>
                            </form>
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
