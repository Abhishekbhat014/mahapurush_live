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
$committeeMembers = [];
$eventQuery = false;

// if connection object is there and active
if ($con && $con !== false) {
    // fetch temple Info
    $templeQuery = mysqli_query($con, "SELECT * FROM temple_info LIMIT 1");
    if ($templeQuery && mysqli_num_rows($templeQuery) > 0) {
        $templeData = mysqli_fetch_assoc($templeQuery);
        $temple['temple_name'] = $templeData['temple_name'];
        $temple['description'] = $templeData['description'];
        $temple['location'] = $templeData['location'];
        $temple['contact'] = $templeData['contact'];
        $temple['time'] = $templeData['time'];
        $temple['address'] = $templeData['address'];
        $temple['photo'] = $templeData['photo'] ? '' : "assets/images/temple.png";
    }

    // Committee Members logic
    $cmQuery = mysqli_query($con, "SELECT u.first_name, u.last_name, u.photo, r.name AS role_name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name != 'customer' ORDER BY r.name, u.first_name");
    if ($cmQuery) {
        while ($row = mysqli_fetch_assoc($cmQuery)) {
            $committeeMembers[] = $row;
        }
    }

    $eventQuery = mysqli_query($con, "SELECT * FROM events WHERE conduct_on >= CURDATE() ORDER BY conduct_on ASC LIMIT 3");
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?> - <?php echo $t['official_portal']; ?></title>
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
            padding: 80px 0;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            background-image: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            transition: all 0.3s cubic-bezier(0.23, 1, 0.32, 1);
            overflow: hidden;
            height: 100%;
        }

        .ant-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }

        .ant-card-body {
            padding: 32px;
        }

        .service-icon {
            width: 64px;
            height: 64px;
            background: #e6f4ff;
            color: var(--ant-primary);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 28px;
        }

        .temple-img-main {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 16px;
            box-shadow: var(--ant-shadow);
        }

        .date-box {
            background: var(--ant-primary);
            color: #fff;
            border-radius: 8px;
            min-width: 70px;
            padding: 10px;
            text-align: center;
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
                <?php echo $t['welcome_subtitle'] ?? 'Divine Sanctuary'; ?>
            </span>
            <h1><?php echo $t['welcome_title']; ?></h1>
            <div class="d-flex justify-content-center gap-3 mt-4">
                <a href="donate.php" class="ant-btn-primary-big shadow-sm"><?php echo $t['donate_btn']; ?></a>
                <a href="auth/redirect.php" class="btn btn-outline-dark px-5 py-2 fw-bold"
                    style="border-radius: 8px; height: 50px; display: flex; align-items: center;"><?php echo $t['book_pooja_btn']; ?></a>
            </div>
        </div>
    </section>

    <main class="container py-5">

        <div class="row align-items-center g-5 mb-5 pb-4">
            <div class="col-lg-6">
                <img src="<?= $temple['photo'] ?>" class="temple-img-main" alt="<?php echo $t['temple_image_alt']; ?>">
            </div>
            <div class="col-lg-6">
                <span class="text-primary fw-bold text-uppercase small mb-2 d-block"
                    style="letter-spacing: 1px;"><?php echo $t['our_sacred_heritage']; ?></span>
                <h2 class="fw-bold mb-4"><?php echo $t['about_label']; ?> <?= $t['title'] ?></h2>
                <p class="text-secondary fs-5 mb-4" style="line-height: 1.8;">
                    <?php
                    // Show first 300 characters of description as a summary
                    echo nl2br(htmlspecialchars(mb_strimwidth($temple['description'], 0, 450, "...")));
                    ?>
                </p>
                <div class="d-flex gap-4 mb-4">
                    <div>
                        <h4 class="fw-bold text-primary mb-0"><?php echo $t['daily']; ?></h4>
                        <span class="small text-muted text-uppercase fw-bold"><?php echo $t['aarti_puja']; ?></span>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div>
                        <h4 class="fw-bold text-primary mb-0"><?php echo $t['infinite']; ?></h4>
                        <span class="small text-muted text-uppercase fw-bold"><?php echo $t['blessings']; ?></span>
                    </div>
                </div>
                <a href="about.php" class="btn btn-link text-primary fw-bold p-0 text-decoration-none">
                    <?php echo $t['read_full_history']; ?> <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <div class="mb-5 pt-4">
            <h4 class="fw-bold mb-4"><?php echo $t['temple_services']; ?></h4>
            <div class="row g-4">
                <?php
                $icons = ['calendar2-check', 'heart-fill', 'people-fill', 'shop-window'];
                $serviceTitles = [$t['service_pooja'], $t['service_donate'], $t['service_events'], $t['service_seva']];
                for ($i = 0; $i < 4; $i++): ?>
                    <div class="col-6 col-md-3">
                        <div class="ant-card">
                            <div class="ant-card-body text-center">
                                <div class="service-icon"><i class="bi bi-<?= $icons[$i] ?>"></i></div>
                                <div class="fw-bold mb-2"><?= $serviceTitles[$i] ?></div>
                                <div class="small text-muted d-none d-md-block"><?php echo $t['service_desc']; ?></div>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div style="height: 1px; background: var(--ant-border-color); margin: 60px 0;"></div>
        <!-- TODO: Event  -->
        <div class="row g-5">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-4"><?php echo $t['upcoming_events']; ?></h4>
                <?php if ($eventQuery && mysqli_num_rows($eventQuery) > 0): ?>
                    <?php while ($event = mysqli_fetch_assoc($eventQuery)): ?>
                        <div class="ant-card mb-4 border-0">
                            <div class="ant-card-body d-flex align-items-center gap-4">
                                <div class="date-box shadow-sm">
                                    <div class="fw-bold fs-4"><?= date("d", strtotime($event['conduct_on'])) ?></div>
                                    <div class="small text-uppercase fw-bold" style="font-size: 10px; opacity: 0.9;">
                                        <?= date("M", strtotime($event['conduct_on'])) ?>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($event['name']) ?></h5>
                                    <div class="small text-muted"><i class="bi bi-clock me-1"></i> <?= $event['duration'] ?>
                                    </div>
                                </div>
                                <a href="events.php?id=<?= $event['id'] ?>"
                                    class="btn btn-light btn-sm px-4 rounded-pill border fw-bold text-primary"><?php echo $t['details']; ?></a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="ant-card">
                        <div class="ant-card-body text-center py-5">
                            <i class="bi bi-calendar-x fs-1 text-muted opacity-25 mb-3"></i>
                            <p class="text-muted mb-0"><?php echo $t['no_upcoming_events']; ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="ant-card sticky-top" style="top: 88px; z-index: 1;">
                    <div class="ant-card-body">
                        <h5 class="fw-bold mb-4"><?php echo $t['contact_us']; ?></h5>
                        <div class="d-flex gap-3 mb-4">
                            <div class="text-primary fs-5"><i class="bi bi-geo-alt-fill"></i></div>
                            <div>
                                <small class="text-uppercase text-muted fw-bold d-block mb-1"
                                    style="font-size: 10px;"><?php echo $t['location']; ?></small>
                                <span
                                    class="small text-dark fw-medium"><?= htmlspecialchars($temple['location'] ?? $t['visit_us']) ?></span>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <div class="text-primary fs-5"><i class="bi bi-telephone-fill"></i></div>
                            <div>
                                <small class="text-uppercase text-muted fw-bold d-block mb-1"
                                    style="font-size: 10px;"><?php echo $t['phone']; ?></small>
                                <span
                                    class="small text-dark fw-medium"><?= htmlspecialchars($temple['contact'] ?? $t['call_us']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>