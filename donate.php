<?php
// Include language file
require __DIR__ . '/includes/lang.php';

// include db file
if (file_exists("config/db.php")) {
    require __DIR__ . "/config/db.php";
} else {
    $con = false;
}

$isLoggedIn = $_SESSION['logged_in'] ?? false;
$primaryRole = $_SESSION['primary_role'] ?? null;

if ($isLoggedIn && $primaryRole) {
    if ($primaryRole === 'customer') {
        header("Location: users/customer/donate.php");
        exit;
    }
    if ($primaryRole === 'member') {
        header("Location: users/member/donate.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['donate_btn'] ?? 'Donate'; ?> - <?php echo $t['official_portal']; ?></title>
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
        }

        .ant-hero {
            background: #fff;
            padding: 70px 0;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            background-image: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            overflow: hidden;
        }

        .ant-card-body {
            padding: 32px;
        }

        .qr-box {
            width: 260px;
            height: 260px;
            border-radius: 16px;
            border: 1px dashed #d9d9d9;
            background: #fafafa;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
        }

        .qr-img {
            width: 220px;
            height: 220px;
            object-fit: contain;
        }

        .ant-btn-primary-big {
            background-color: var(--ant-primary);
            color: #fff !important;
            padding: 12px 36px;
            border-radius: 8px;
            font-weight: 600;
            display: inline-block;
            text-decoration: none;
            transition: 0.3s;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <section class="ant-hero">
        <div class="container">
            <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary px-3 py-2 mb-3 fw-bold text-uppercase"
                style="letter-spacing: 1px; font-size: 11px;">
                <?php echo $t['donate_btn'] ?? 'Donate'; ?>
            </span>
            <h1><?php echo $t['support_temple'] ?? 'Support the Temple'; ?></h1>
            <p class="text-muted mt-3 mb-0">
                <?php echo $t['scan_qr_pay'] ?? 'Scan the QR code to make your contribution.'; ?>
            </p>
        </div>
    </section>

    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="ant-card">
                    <div class="ant-card-body text-center">
                        <div class="qr-box mb-4">
                            <img src="assets/images/qr.png" alt="QR Code" class="qr-img">
                        </div>
                        <div class="text-muted small mb-3">
                            <?php echo $t['scan_qr_note'] ?? 'Use any UPI app to scan and pay.'; ?>
                        </div>
                        <a href="index.php" class="ant-btn-primary-big"><?php echo $t['back_home'] ?? 'Back to Home'; ?></a>
                    </div>
                </div>
                <div class="text-center small text-muted mt-3">
                    <?php echo $t['qr_help'] ?? 'If the QR code is not visible, please contact us.'; ?>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
