<?php
require __DIR__ . '/includes/lang.php';
// =========================================================
// 2. DATABASE CONNECTION
// =========================================================
if (file_exists("config/db.php")) {
    include "config/db.php";
} else {
    $conn = false;
}

// =========================================================
// 3. DATA FETCHING
// =========================================================

// Defaults
$temple = [
    'description' => ($lang == 'mr') ? 'भगवान शंकरांना समर्पित एक पवित्र स्थान.' : 'A sacred sanctuary devoted to Lord Shiva.',
    'location' => 'Temple Location',
    'contact' => '+91 00000 00000'
];
$committeeMembers = [];
$eventQuery = false;

if ($conn) {
    // 1. Fetch Temple Info
    $templeQuery = mysqli_query($conn, "SELECT * FROM temple_info LIMIT 1");
    if ($templeQuery && mysqli_num_rows($templeQuery) > 0) {
        $templeData = mysqli_fetch_assoc($templeQuery);
        // Note: DB content is usually static. If you have mr/en columns in DB, switch here.
        // For now, we use the DB description, falling back to static if empty
        if (!empty($templeData['description'])) {
            $temple['description'] = $templeData['description'];
        }
        $temple['location'] = $templeData['location'];
        $temple['contact'] = $templeData['contact'];
    }

    // 2. Fetch Committee
    $cmQuery = mysqli_query($conn, "SELECT u.first_name, u.last_name, u.photo, r.name AS role_name FROM users u JOIN user_roles ur ON u.id = ur.user_id JOIN roles r ON ur.role_id = r.id WHERE r.name != 'customer' ORDER BY r.name, u.first_name");
    if ($cmQuery) {
        while ($row = mysqli_fetch_assoc($cmQuery)) {
            $committeeMembers[] = $row;
        }
    }

    // 3. Events
    $eventQuery = mysqli_query($conn, "SELECT * FROM events WHERE conduct_on >= CURDATE() ORDER BY conduct_on ASC LIMIT 3");
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['title']; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        /* ===============================
           THEME VARIABLES
        =============================== */
        :root {
            --shiva-blue-light: #e3f2fd;
            --shiva-blue-deep: #1565c0;
            --shiva-saffron: #ff9800;
            --shiva-saffron-light: #fff3e0;
            --text-dark: #2c3e50;
            --text-muted: #607d8b;
            --bg-body: #fdfbf7;
        }

        body {
            font-family: 'Inter', sans-serif;
            /* Works well for English & Marathi */
            color: var(--text-dark);
            background-color: var(--bg-body);
            line-height: 1.7;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        .navbar-brand {
            font-family: 'Playfair Display', serif;
        }

        /* Navbar & General UI Styles (Same as before) */
        .navbar {
            background-color: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--shiva-saffron) !important;
            letter-spacing: 0.5px;
        }

        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
            margin-left: 1rem;
            transition: color 0.3s;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--shiva-saffron);
        }

        .committee-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 10px;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .committee-item:hover {
            background-color: var(--shiva-blue-light);
        }

        .committee-item img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--shiva-saffron);
        }

        .dropdown-menu {
            border: none;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            padding: 10px;
        }

        .hero-section {
            background: linear-gradient(135deg, #ffffff 0%, var(--shiva-blue-light) 100%);
            padding: 120px 0;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .hero-subtitle {
            font-size: 1.2rem;
            letter-spacing: 2px;
            color: var(--shiva-blue-deep);
            text-transform: uppercase;
            font-weight: 600;
        }

        .btn-saffron {
            background-color: var(--shiva-saffron);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 35px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.3);
        }

        .btn-saffron:hover {
            background-color: #f57c00;
            transform: translateY(-2px);
            color: white;
        }

        .btn-outline-blue {
            border: 2px solid var(--shiva-blue-deep);
            color: var(--shiva-blue-deep);
            border-radius: 50px;
            padding: 8px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-blue:hover {
            background-color: var(--shiva-blue-deep);
            color: white;
        }

        .feature-card {
            background: white;
            border: none;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0, 0, 0, 0.03);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-color: var(--shiva-blue-light);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--shiva-saffron);
            margin-bottom: 1rem;
        }

        .event-card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
            background: white;
        }

        .event-card:hover {
            transform: translateY(-5px);
        }

        .event-date-box {
            background-color: var(--shiva-blue-light);
            color: var(--shiva-blue-deep);
            padding: 10px;
            text-align: center;
            border-radius: 8px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }

        .dev-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border: 4px solid var(--shiva-blue-light);
            padding: 2px;
            transition: transform 0.3s;
        }

        .dev-card:hover .dev-img {
            transform: scale(1.05);
            border-color: var(--shiva-saffron);
        }

        footer {
            background-color: #263238;
            color: #eceff1;
        }

        footer a {
            color: #b0bec5;
            text-decoration: none;
            transition: color 0.2s;
        }

        footer a:hover {
            color: var(--shiva-saffron);
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-brightness-high-fill me-2"></i><?php echo $t['title']; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="index.php"><?php echo $t['home']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#about"><?php echo $t['about']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#pooja"><?php echo $t['pooja']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#events"><?php echo $t['events']; ?></a></li>
                    <li class="nav-item"><a class="nav-link" href="#donations"><?php echo $t['donations']; ?></a></li>

                    <li class="nav-item dropdown ms-lg-3">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <?php echo $t['committee']; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if (!empty($committeeMembers)) { ?>
                                <?php foreach ($committeeMembers as $member) {
                                    $photo = !empty($member['photo']) ? 'uploads/users/' . $member['photo'] : 'assets/img/default-user.png';
                                    ?>
                                    <li>
                                        <div class="committee-item">
                                            <img src="<?php echo $photo; ?>" alt="Member">
                                            <div>
                                                <div class="committee-name">
                                                    <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                                </div>
                                                <div class="committee-role">
                                                    <?php echo ucfirst(htmlspecialchars($member['role_name'])); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                <?php } ?>
                            <?php } else { ?>
                                <li class="text-center py-3 text-muted small"><?php echo $t['no_members']; ?></li>
                            <?php } ?>
                        </ul>
                    </li>

                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle fw-bold text-primary" href="#" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-translate me-1"></i>
                            <?php echo ($lang == 'en') ? 'English' : 'मराठी'; ?>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end text-center" style="min-width: 120px;">
                            <li><a class="dropdown-item <?php echo ($lang == 'en') ? 'active' : ''; ?>"
                                    href="?lang=en">English</a></li>
                            <li><a class="dropdown-item <?php echo ($lang == 'mr') ? 'active' : ''; ?>"
                                    href="?lang=mr">मराठी</a></li>
                        </ul>
                    </li>

                    <li class="nav-item ms-lg-3 mt-3 mt-lg-0">
                        <a class="btn btn-outline-blue btn-sm" href="auth/login.php"><?php echo $t['login']; ?></a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <p class="hero-subtitle mb-3"><?php echo $t['welcome_subtitle']; ?></p>
            <h1 class="display-3 fw-bold"><?php echo $t['welcome_title']; ?></h1>
            <p class="lead text-muted mt-3 mb-5"><?php echo htmlspecialchars($temple['description']); ?></p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="#donations" class="btn btn-saffron btn-lg"><?php echo $t['donate_btn']; ?></a>
                <a href="#pooja" class="btn btn-outline-blue btn-lg"><?php echo $t['book_pooja_btn']; ?></a>
            </div>
        </div>
    </section>

    <section id="about" class="py-5 bg-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4">
                    <div class="bg-light rounded-4 p-5 text-center border">
                        <i class="bi bi-bank2 text-muted" style="font-size:5rem;opacity:.3;"></i>
                        <p class="small text-muted mt-2">Temple Image Placeholder</p>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h2 class="fw-bold"><?php echo $t['about_title']; ?></h2>
                    <p class="text-muted mt-3"><?php echo htmlspecialchars($temple['description']); ?></p>
                    <a href="#" class="btn btn-link px-0 text-decoration-none"><?php echo $t['read_history']; ?> →</a>
                </div>
            </div>
        </div>
    </section>

    <section id="pooja" class="py-5" style="background-color:var(--shiva-blue-light);">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold"><?php echo $t['services_title']; ?></h2>
            </div>
            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h5><?php echo $t['service_pooja']; ?></h5>
                        <p class="small text-muted"><?php echo $t['service_pooja_desc']; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-heart-fill feature-icon"></i>
                        <h5><?php echo $t['service_donate']; ?></h5>
                        <p class="small text-muted"><?php echo $t['service_donate_desc']; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon"></i>
                        <h5><?php echo $t['service_events']; ?></h5>
                        <p class="small text-muted"><?php echo $t['service_events_desc']; ?></p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-basket-fill feature-icon"></i>
                        <h5><?php echo $t['service_seva']; ?></h5>
                        <p class="small text-muted"><?php echo $t['service_seva_desc']; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="events" class="py-5 bg-white">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold"><?php echo $t['upcoming_events']; ?></h2>
            </div>
            <div class="row justify-content-center">
                <?php if ($eventQuery && mysqli_num_rows($eventQuery) > 0) { ?>
                    <?php while ($event = mysqli_fetch_assoc($eventQuery)) {
                        $dateObj = strtotime($event['conduct_on']);
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card event-card p-4 h-100 text-center">
                                <div class="event-date-box">
                                    <span class="d-block small"><?php echo $t['month_of']; ?></span>
                                    <span class="fs-4"><?php echo date("F", $dateObj); ?></span>
                                </div>
                                <h5 class="fw-bold"><?php echo htmlspecialchars($event['name']); ?></h5>
                                <p class="small text-muted">
                                    <i class="bi bi-calendar-event me-1"></i>
                                    <?php echo date("l, d Y", $dateObj); ?>
                                </p>
                                <p class="small text-muted"><?php echo $t['duration']; ?>:
                                    <?php echo htmlspecialchars($event['duration']); ?>
                                </p>
                            </div>
                        </div>
                    <?php } ?>
                <?php } else { ?>
                    <div class="col-12 text-center">
                        <p class="text-muted"><?php echo $t['no_events']; ?></p>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>

    <section id="donations" class="py-5 text-center" style="background-color:var(--shiva-saffron-light);">
        <div class="container">
            <h2 class="fw-bold"><?php echo $t['support_title']; ?></h2>
            <p class="lead text-muted mb-4"><?php echo $t['support_desc']; ?></p>
            <a href="user/donate.php" class="btn btn-saffron btn-lg px-5"><?php echo $t['contribute_btn']; ?></a>
        </div>
    </section>

    <section class="py-5 bg-white border-top">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="fw-bold h4"><?php echo $t['tech_team']; ?></h2>
                <p class="text-muted small"><?php echo $t['dev_by']; ?></p>
            </div>
            <div class="row justify-content-center">
                <div class="col-md-4 col-lg-3 text-center mb-4">
                    <div class="dev-card">
                        <img src="https://via.placeholder.com/150" alt="Dev 1"
                            class="rounded-circle dev-img mb-3 shadow-sm">
                        <h5 class="fw-bold mb-1">Developer Name 1</h5>
                        <p class="text-muted small">Backend Specialist</p>
                    </div>
                </div>
                <div class="col-md-4 col-lg-3 text-center">
                    <div class="dev-card">
                        <img src="https://via.placeholder.com/150" alt="Dev 2"
                            class="rounded-circle dev-img mb-3 shadow-sm">
                        <h5 class="fw-bold mb-1">Developer Name 2</h5>
                        <p class="text-muted small">Frontend Specialist</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5 mt-5" style="background-color:#263238;">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3">
                        <i class="bi bi-brightness-high-fill me-2 text-warning"></i>
                        <?php echo $t['title']; ?>
                    </h5>
                    <p class="small text-secondary"><?php echo htmlspecialchars($temple['description']); ?></p>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3"><?php echo $t['contact']; ?></h5>
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i>
                            <?php echo htmlspecialchars($temple['location']); ?></li>
                        <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i>
                            <?php echo htmlspecialchars($temple['contact']); ?></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3"><?php echo $t['quick_links']; ?></h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="privacy.php"
                                class="text-secondary text-decoration-none"><?php echo $t['privacy']; ?></a></li>
                        <li class="mb-2"><a href="terms.php"
                                class="text-secondary text-decoration-none"><?php echo $t['terms']; ?></a></li>
                        <li class="mb-2"><a href="user/donate.php"
                                class="text-secondary text-decoration-none"><?php echo $t['donations']; ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary mt-4 pt-4 text-center">
                <small>
                &copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?> |
                <span class="text-white-50"><?php echo $t['copyright']; ?></span>
            </small>
            </div>
            
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>