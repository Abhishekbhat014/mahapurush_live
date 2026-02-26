<?php
require_once __DIR__ . '/../../includes/no_cache.php';
session_start();
require __DIR__ . '/../../includes/lang.php';
require __DIR__ . '/../../includes/user_avatar.php';
require __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

$availableRoles = $_SESSION['roles'] ?? [];
$primaryRole = $_SESSION['primary_role'] ?? ($availableRoles[0] ?? 'customer');
$currLang = $_SESSION['lang'] ?? 'en';
$currentPage = 'receipts.php';
$loggedInUserPhoto = get_user_avatar_url('../../');
$userName = $_SESSION['user_name'] ?? 'User';

// --- SEARCH & FETCH LOGIC ---
$search = $_GET['search'] ?? '';
$receipts = [];

if ($con) {
    // Note: Added 'r.purpose' to SELECT list to show what the receipt is for
    $query = "
        SELECT 
            r.id, 
            r.receipt_no, 
            r.purpose,
            r.created_at,
            r.amount, 
            p.payment_method AS payment_mode, 
            
            COALESCE(u.first_name, 'Guest') AS devotee_name,
            u.phone,
            COALESCE(p.note, '') as remarks
        FROM receipt r
        LEFT JOIN payments p ON p.receipt_id = r.id
        LEFT JOIN users u ON u.id = r.user_id
        WHERE 1=1
    ";

    if (!empty($search)) {
        $searchTerm = "%" . mysqli_real_escape_string($con, $search) . "%";
        $query .= " AND (r.receipt_no LIKE '$searchTerm' OR u.first_name LIKE '$searchTerm' OR u.phone LIKE '$searchTerm' OR r.purpose LIKE '$searchTerm')";
    }

    $query .= " ORDER BY r.created_at DESC LIMIT 100";

    $result = mysqli_query($con, $query);
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $receipts[] = $row;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $currLang ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['receipts_title'] ?? 'All Receipts'; ?> - <?= $t['title'] ?? 'Temple' ?></title>
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
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
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

        /* --- TABLES --- */
        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 14px 16px;
            font-size: 12px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            border-bottom: 1px solid var(--ant-border-color);
        }

        .ant-table td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
            font-size: 14px;
        }

        .ant-table tr:hover td {
            background-color: #fafafa;
        }

        .badge-soft {
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            text-transform: capitalize;
        }

        .badge-cash {
            background: #f6ffed;
            color: #52c41a;
            border: 1px solid #b7eb8f;
        }

        .badge-online {
            background: #e6f4ff;
            color: #1677ff;
            border: 1px solid #91caff;
        }

        .badge-cheque {
            background: #fff7e6;
            color: #fa8c16;
            border: 1px solid #ffd591;
        }

        .btn-primary {
            background: var(--ant-primary);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            transition: 0.2s;
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
                    <h2 class="fw-bold mb-1"><?php echo $t['receipts_title'] ?? 'Total Receipts Database'; ?></h2>
                    <p class="text-secondary mb-0">
                        <?php echo $t['receipts_desc'] ?? 'A comprehensive, searchable list of all generated receipts.'; ?>
                    </p>
                </div>

                <div class="px-4 pb-5">
                    <div class="ant-card p-4">

                        <form method="GET" class="row g-3 mb-4">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i
                                            class="bi bi-search text-muted"></i></span>
                                    <input type="text" name="search" class="form-control border-start-0 ps-0"
                                        placeholder="<?php echo $t['search_receipts_placeholder'] ?? 'Search by Receipt No or Name...'; ?>"
                                        value="<?= htmlspecialchars($search) ?>">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-primary w-100">
                                    <?php echo $t['search_btn'] ?? 'Search'; ?>
                                </button>
                            </div>
                            <?php if (!empty($search)): ?>
                                <div class="col-md-2">
                                    <a href="receipts.php"
                                        class="btn btn-light border w-100"><?php echo $t['clear_btn'] ?? 'Clear'; ?></a>
                                </div>
                            <?php endif; ?>
                        </form>

                        <div class="table-responsive">
                            <table class="table ant-table mb-0">
                                <thead>
                                    <tr>
                                        <th><?php echo $t['receipt_no'] ?? 'Receipt No'; ?></th>
                                        <th><?php echo $t['devotee'] ?? 'Devotee'; ?></th>
                                        <th><?php echo $t['amount'] ?? 'Amount'; ?></th>
                                        <th><?php echo $t['purpose'] ?? 'Purpose'; ?></th>
                                        <th><?php echo $t['date'] ?? 'Date'; ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($receipts)): ?>
                                        <?php foreach ($receipts as $r):
                                            $mode = strtolower($r['payment_mode'] ?? 'cash');
                                            $badge = 'badge-cash'; 
                                            if (strpos($mode, 'online') !== false)
                                                $badge = 'badge-online';
                                            if (strpos($mode, 'cheque') !== false)
                                                $badge = 'badge-cheque';
                                            ?>
                                            <tr style="cursor: pointer;" onclick="window.open('../receipt/view.php?no=<?= urlencode($r['receipt_no']) ?>', '_blank')">
                                                <td class="fw-bold text-primary">
                                                    #<?= htmlspecialchars($r['receipt_no']) ?>
                                                </td>
                                                <td>
                                                    <div class="fw-medium text-dark"><?= htmlspecialchars($r['devotee_name']) ?>
                                                    </div>
                                                    <div class="text-muted small"><?= htmlspecialchars($r['phone'] ?? '-') ?>
                                                    </div>
                                                </td>
                                                <td class="fw-bold">
                                                    <?= rtrim(rtrim(number_format($r['amount'], 2), '0'), '.') ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border fw-bold px-2 py-1">
                                                        <?= htmlspecialchars(ucfirst($r['purpose'] ?? 'General')) ?>
                                                    </span>
                                                    <?php if(!empty($r['payment_mode'])): ?>
                                                        <span class="badge-soft <?= $badge ?> ms-1">
                                                            <?= htmlspecialchars($r['payment_mode']) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-muted small">
                                                    <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-receipt fs-1 opacity-25 d-block mb-3"></i>
                                                <p class="mb-0"><?php echo $t['no_receipts_found_criteria'] ?? 'No receipts found.'; ?></p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

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