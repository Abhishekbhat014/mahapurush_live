<?php
session_start();
require __DIR__ . '/../../includes/lang.php';

// Auth Check
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../../auth/login.php");
    exit;
}

require __DIR__ . '/../../config/db.php';
$uid = (int) $_SESSION['user_id'];

// --- Header Profile Photo ---
$uQuery = mysqli_query($con, "SELECT photo, first_name, last_name FROM users WHERE id='$uid' LIMIT 1");
$uRow = mysqli_fetch_assoc($uQuery);
$loggedInUserPhoto = !empty($uRow['photo']) ? '../../uploads/users/' . basename($uRow['photo']) : 'https://ui-avatars.com/api/?name=' . urlencode($uRow['first_name'] . ' ' . $uRow['last_name']) . '&background=random';

// --- Search Logic ---
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

// --- Fetch Members ---
$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.photo, r.name as role_name 
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        JOIN roles r ON ur.role_id = r.id 
        WHERE (u.first_name LIKE '%$search%' OR u.last_name LIKE '%$search%' OR u.email LIKE '%$search%')
        ORDER BY u.first_name ASC";
$membersResult = mysqli_query($con, $sql);

$currentPage = 'member_directory.php';
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Directory - <?php echo $t['title']; ?></title>
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
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }

        .selectable,
        .form-control {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
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
            margin-bottom: 0;
            /* Changed to 0 to let the padding-4 handle spacing */
        }

        /* --- Rounded Card DNA --- */
        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            overflow: hidden;
            /* Ensures table corners are rounded */
        }

        .ant-table th {
            background: #fafafa;
            font-weight: 600;
            padding: 16px;
            font-size: 13px;
            color: var(--ant-text-sec);
            text-transform: uppercase;
            border-bottom: 1px solid var(--ant-border-color);
        }

        .ant-table td {
            padding: 16px;
            border-bottom: 1px solid var(--ant-border-color);
            vertical-align: middle;
        }

        .member-avatar {
            width: 44px;
            height: 44px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: zoom-in;
            transition: transform 0.2s ease;
        }

        .member-avatar:hover {
            transform: scale(1.15);
            border-color: var(--ant-primary);
        }

        .copy-btn {
            border: none;
            background: #f5f5f5;
            color: var(--ant-text-sec);
            border-radius: 6px;
            padding: 6px 10px;
            transition: 0.3s;
        }

        .copy-btn:hover {
            background: var(--ant-primary);
            color: #fff;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            overflow: hidden;
        }

        .enlarged-img {
            width: 100%;
            height: auto;
            max-height: 70vh;
            object-fit: contain;
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
            <div class="user-pill shadow-sm">
                <img src="<?= htmlspecialchars($loggedInUserPhoto) ?>" class="rounded-circle" width="28" height="28"
                    style="object-fit: cover;">
                <span class="small fw-bold d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name']) ?></span>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>

            <main class="col-lg-10 p-0">
                <div class="dashboard-hero">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                        <div>
                            <h2 class="fw-bold mb-1">Member Directory</h2>
                            <p class="text-secondary mb-0 small">View all temple members and committee staff.</p>
                        </div>
                        <div style="min-width: 320px;">
                            <form method="GET" class="input-group shadow-sm">
                                <span class="input-group-text bg-white border-end-0"><i
                                        class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0"
                                    placeholder="Search members..." value="<?= htmlspecialchars($search) ?>">
                            </form>
                        </div>
                    </div>
                </div>

                <div class="p-4 pb-5">
                    <div class="ant-card">
                        <div class="ant-card-body p-0">
                            <div class="table-responsive">
                                <table class="table ant-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Member Profile</th>
                                            <th>Role</th>
                                            <th>Email Address</th>
                                            <th>Phone</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($membersResult)):
                                            $photo = !empty($row['photo']) ? '../../uploads/users/' . $row['photo'] : 'https://ui-avatars.com/api/?name=' . urlencode($row['first_name'] . ' ' . $row['last_name']) . '&background=random';
                                            $fullName = $row['first_name'] . ' ' . $row['last_name'];
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?= $photo ?>" class="member-avatar" alt="Profile"
                                                            data-bs-toggle="modal" data-bs-target="#enlargePhotoModal"
                                                            onclick="prepareModal('<?= $photo ?>', '<?= htmlspecialchars($fullName) ?>')">
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($fullName) ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td><span
                                                        class="badge bg-light text-dark border fw-medium"><?= ucfirst($row['role_name']) ?></span>
                                                </td>
                                                <td class="small selectable"><?= htmlspecialchars($row['email']) ?></td>
                                                <td class="small selectable"><?= htmlspecialchars($row['phone']) ?></td>
                                                <td class="text-end">
                                                    <button class="copy-btn"
                                                        onclick="copyValue('<?= htmlspecialchars($row['email']) ?>', this)"
                                                        title="Copy Email"><i class="bi bi-envelope"></i></button>
                                                    <button class="copy-btn ms-1"
                                                        onclick="copyValue('<?= htmlspecialchars($row['phone']) ?>', this)"
                                                        title="Copy Phone"><i class="bi bi-telephone"></i></button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="enlargePhotoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header border-0 pb-0">
                    <h6 class="modal-title fw-bold" id="modalNameDisplay">Member Photo</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img src="" id="modalImageDisplay" class="enlarged-img rounded shadow-sm" alt="Member">
                </div>
                <div class="modal-footer border-0 pt-0 justify-content-center">
                    <button type="button" class="btn btn-light btn-sm px-4 rounded-pill border"
                        data-bs-dismiss="modal">Close Preview</button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="copyToast" class="toast align-items-center text-white bg-dark border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body"><i class="bi bi-check2-circle me-2 text-success"></i> Copied to clipboard!</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function prepareModal(imgSrc, name) {
            document.getElementById('modalImageDisplay').src = imgSrc;
            document.getElementById('modalNameDisplay').innerText = name;
        }
        function copyValue(text, btn) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = new bootstrap.Toast(document.getElementById('copyToast'));
                toast.show();
                const icon = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check-lg"></i>';
                btn.style.background = '#52c41a'; btn.style.color = '#fff';
                setTimeout(() => { btn.innerHTML = icon; btn.style.background = ''; btn.style.color = ''; }, 2000);
            });
        }
    </script>
</body>

</html>