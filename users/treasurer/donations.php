<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$loggedInUserPhoto = get_user_avatar_url('../../');
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donations - <?= $t['title'] ?? 'Temple' ?></title>
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
            /* Disable text selection globally */
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
        }

        /* Form Controls */
        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--ant-text-sec);
            margin-bottom: 6px;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 10px 14px;
            border: 1px solid #d9d9d9;
            transition: 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--ant-primary);
            box-shadow: 0 0 0 2px rgba(22, 119, 255, 0.1);
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background: var(--ant-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2);
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
                    <h2 class="fw-bold mb-1">Record Donation</h2>
                    <p class="text-secondary mb-0">
                        Enter donation payments received at the temple counter (cash / online / cheque).
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card p-4">
                        <form action="save_donation.php" method="POST">
                            <div class="row g-4">

                                <div class="col-md-6">
                                    <label class="form-label">Devotee Name</label>
                                    <input type="text" name="devotee_name" class="form-control" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Mobile Number</label>
                                    <input type="text" name="mobile" class="form-control">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Donation Amount (₹)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0">₹</span>
                                        <input type="number" name="amount" class="form-control border-start-0 ps-0"
                                            required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Payment Mode</label>
                                    <select name="payment_mode" class="form-select" required>
                                        <option value="">Select Mode</option>
                                        <option value="cash">Cash</option>
                                        <option value="online">Online (UPI/Card)</option>
                                        <option value="cheque">Cheque</option>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Purpose / Remarks</label>
                                    <input type="text" name="remarks" class="form-control"
                                        placeholder="E.g. General Fund, Annadhanam, etc.">
                                </div>

                                <div class="col-12 d-flex justify-content-end mt-4">
                                    <button type="submit" class="btn btn-primary px-5 py-2">
                                        <i class="bi bi-save2 me-2"></i> Record Donation
                                    </button>
                                </div>

                            </div>
                        </form>
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
    </script>
</body>

</html>