<?php
session_start();
require '../../includes/lang.php';
require '../../config/db.php';

$currLang = $_SESSION['lang'] ?? 'en';

$userId = $_SESSION['user_id'];

$result = $con->prepare("
    SELECT p.*, pt.type
    FROM pooja p
    JOIN pooja_type pt ON pt.id = p.pooja_type_id
    WHERE p.user_id = ?
    ORDER BY p.created_at DESC
");

$result->bind_param("i", $userId);
$result->execute();
$data = $result->get_result();
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <title><?php echo $t['my_poojas']; ?> - <?php echo $t['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-bg-layout: #f0f2f5;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
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
<?php
$uRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$userId' LIMIT 1"));
$userName = $_SESSION['user_name'] ?? $t['user'];
$userPhotoUrl = !empty($uRow['photo'])
    ? '../../uploads/users/' . basename($uRow['photo'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($userName) . '&background=random';
?>

    <header class="ant-header shadow-sm">
        <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
                    <i class="bi bi-list"></i>
                </button>
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
                    <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </div>
    </header>

<div class="container py-5">
    <div class="row">

        <?php include __DIR__ . '/../includes/customer_sidebar.php'; ?>

        <div class="col-lg-9">
            <div class="card dashboard-card">
                <div class="card-body p-4">
                    <h4 class="fw-bold mb-4"><?php echo $t['my_pooja_bookings']; ?></h4>

                    <table class="table">
                        <thead>
                        <tr>
                            <th><?php echo $t['pooja']; ?></th>
                            <th><?php echo $t['date']; ?></th>
                            <th><?php echo $t['time_slot']; ?></th>
                            <th><?php echo $t['fee']; ?></th>
                            <th><?php echo $t['status']; ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = $data->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $row['type']; ?></td>
                                <td><?php echo date('d M Y', strtotime($row['pooja_date'])); ?></td>
                                <td><?php echo ucfirst($row['time_slot'] ?? $t['not_available']); ?></td>
                                <td>₹<?php echo number_format($row['fee'], 2); ?></td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst($row['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
