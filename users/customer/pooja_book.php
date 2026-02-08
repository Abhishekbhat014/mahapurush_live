<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require '../../includes/lang.php';
require '../../config/db.php';
require '../../includes/user_avatar.php';

$currLang = $_SESSION['lang'] ?? 'en';

if (empty($_SESSION['logged_in'])) {
    header("Location: ../../auth/login.php");
    exit;
}
$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');


$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? $t['user'];
$errorMsg = '';
if (!empty($_GET['err'])) {
    $errorMsg = $t['slot_unavailable'] ?? 'Selected time slot is not available for that date.';
}

// fetch pooja types
$poojaTypes = $con->query("SELECT id, type, fee FROM pooja_type ORDER BY type");

// Sidebar Active Logic
$currentPage = basename($_SERVER['PHP_SELF']);

// User photo for header (session cached)
$userPhotoUrl = get_user_avatar_url('../../');

// Pooja slot availability (by date)
$slotMap = [];
$fullDates = [];
$slotQuery = $con->query("
    SELECT pooja_date, time_slot, COUNT(*) AS cnt 
    FROM pooja 
    WHERE pooja_date >= CURDATE() 
      AND time_slot IS NOT NULL 
      AND time_slot <> ''
      AND status <> 'cancelled'
    GROUP BY pooja_date, time_slot
");
if ($slotQuery) {
    while ($row = $slotQuery->fetch_assoc()) {
        $dateKey = $row['pooja_date'];
        $slot = $row['time_slot'];
        if (!isset($slotMap[$dateKey])) {
            $slotMap[$dateKey] = [];
        }
        if (!in_array($slot, $slotMap[$dateKey], true)) {
            $slotMap[$dateKey][] = $slot;
        }
    }
    foreach ($slotMap as $dateKey => $slots) {
        if (count($slots) >= 3) {
            $fullDates[$dateKey] = true;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $currLang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['book_pooja']; ?> - <?php echo $t['title']; ?></title>
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
            /* Disable text selection */
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        /* Re-enable selection for inputs */
        input,
        textarea,
        select,
        button {
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

        /* --- Sidebar Style --- */
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

        /* --- Form Card --- */
        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
        }

        .ant-card-body {
            padding: 32px;
        }

        .ant-card-head {
            padding: 16px 32px;
            border-bottom: 1px solid var(--ant-border-color);
            font-weight: 700;
            font-size: 18px;
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
            transition: all 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
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

                <?php if (!empty($availableRoles) && count($availableRoles) > 1): ?>
                    <div class="dropdown">
                        <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="bi bi-person-badge me-1"></i>
                            <?= htmlspecialchars(ucwords(str_replace('_', ' ', $primaryRole))) ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius: 10px;">
                            <?php foreach ($availableRoles as $role):
                                $roleLabel = ucwords(str_replace('_', ' ', $role));
                                ?>
                                <li>
                                    <form action="../../auth/switch_role.php" method="post" class="px-2 py-1">
                                        <button type="submit" name="role" value="<?= htmlspecialchars($role) ?>"
                                            class="dropdown-item small fw-medium <?= ($role === $primaryRole) ? 'active' : '' ?>">
                                            <?= htmlspecialchars($roleLabel) ?>
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
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <div class="small text-muted mb-1">
                        <?php echo $t['dashboard']; ?> / <?php echo $t['pooja_bookings']; ?>
                    </div>
                    <h2 class="fw-bold mb-1"><?php echo $t['book_pooja']; ?></h2>
                    <p class="text-secondary mb-0"><?php echo $t['pooja_book_subtitle']; ?></p>
                </div>

                <div class="px-4 pb-5">
                    <div class="row justify-content-center">
                        <div class="col-xl-8">
                            <div class="ant-card">
                                <div class="ant-card-head">
                                    <i
                                        class="bi bi-calendar2-plus me-2 text-primary"></i><?php echo $t['new_booking_request']; ?>
                                </div>
                                <div class="ant-card-body">
                                    <?php if ($errorMsg): ?>
                                        <div class="alert border-0 shadow-sm mb-4"
                                            style="background: #fff2f0; color: #ff4d4f; border-radius: 8px;">
                                            <i class="bi bi-exclamation-circle-fill me-2"></i>
                                            <?= htmlspecialchars($errorMsg) ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="POST" action="pooja_book_save.php" class="needs-validation"
                                        novalidate>
                                        <div class="mb-4">
                                            <label class="form-label"><?php echo $t['pooja_type']; ?> <span
                                                    class="text-danger">*</span></label>
                                            <select name="pooja_type_id" class="form-select" required>
                                                <option value="" disabled selected>
                                                    <?php echo $t['select_pooja_service']; ?>
                                                </option>
                                                <?php while ($row = $poojaTypes->fetch_assoc()) { ?>
                                                    <option value="<?php echo $row['id']; ?>">
                                                        <?php echo htmlspecialchars($row['type']); ?>
                                                        (₹<?php echo number_format($row['fee'], 2); ?>)
                                                    </option>
                                                <?php } ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                <?php echo $t['field_required'] ?? 'This field is required.'; ?>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label"><?php echo $t['preferred_date']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <input type="date" name="pooja_date" min="<?php echo date('Y-m-d'); ?>"
                                                    class="form-control" required>
                                                <div class="invalid-feedback">
                                                    <?php echo $t['field_required'] ?? 'This field is required.'; ?>
                                                </div>
                                                <div id="slotNotice" class="small text-danger mt-2 d-none">
                                                    <?php echo $t['no_slots_available'] ?? 'All time slots are booked for this date.'; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6 mb-4">
                                                <label class="form-label"><?php echo $t['time_slot']; ?> <span
                                                        class="text-danger">*</span></label>
                                                <select name="time_slot" class="form-select" required>
                                                    <option value="" disabled selected>
                                                        <?php echo $t['select_time_slot'] ?? 'Select time slot'; ?>
                                                    </option>
                                                    <option value="morning"><?php echo $t['morning']; ?></option>
                                                    <option value="afternoon"><?php echo $t['afternoon']; ?></option>
                                                    <option value="evening"><?php echo $t['evening']; ?></option>
                                                </select>
                                                <div class="invalid-feedback">
                                                    <?php echo $t['field_required'] ?? 'This field is required.'; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label"><?php echo $t['special_instructions']; ?></label>
                                            <textarea name="description" class="form-control" rows="4"
                                                placeholder="<?php echo $t['special_instructions_placeholder']; ?>"></textarea>
                                        </div>

                                        <div class="d-flex justify-content-end gap-3 mt-4">
                                            <a href="dashboard.php" class="btn btn-light px-4 border"
                                                style="border-radius: 8px;"><?php echo $t['cancel']; ?></a>
                                            <button type="submit" class="ant-btn-primary px-5">
                                                <?php echo $t['confirm_booking']; ?>
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
    <script>
        (function () {
            const slotMap = <?php echo json_encode($slotMap, JSON_UNESCAPED_SLASHES); ?>;
            const fullDates = <?php echo json_encode(array_keys($fullDates), JSON_UNESCAPED_SLASHES); ?>;
            const dateInput = document.querySelector('input[name="pooja_date"]');
            const slotSelect = document.querySelector('select[name="time_slot"]');
            const notice = document.getElementById('slotNotice');

            if (!dateInput || !slotSelect) return;

            function resetSlots() {
                Array.from(slotSelect.options).forEach(opt => {
                    if (opt.value) {
                        opt.disabled = false;
                        opt.hidden = false;
                    }
                });
            }

            function applyAvailability(dateVal) {
                resetSlots();
                if (!dateVal) return;

                if (fullDates.includes(dateVal)) {
                    if (notice) notice.classList.remove('d-none');
                    dateInput.value = '';
                    slotSelect.value = '';
                    return;
                }

                if (notice) notice.classList.add('d-none');
                const booked = slotMap[dateVal] || [];
                Array.from(slotSelect.options).forEach(opt => {
                    if (opt.value && booked.includes(opt.value)) {
                        opt.disabled = true;
                        opt.hidden = true;
                        if (slotSelect.value === opt.value) slotSelect.value = '';
                    }
                });
            }

            dateInput.addEventListener('change', function () {
                applyAvailability(this.value);
            });
        })();
    </script>
</body>

</html>