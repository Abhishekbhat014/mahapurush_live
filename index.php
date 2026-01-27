<?php
// Keep your existing database logic exactly as is
include "config/db.php";

/* Fetch temple info */
$templeQuery = mysqli_query($conn, "SELECT * FROM temple_info LIMIT 1");
$temple = mysqli_fetch_assoc($templeQuery);

/* Fetch upcoming events */
$eventQuery = mysqli_query(
    $conn,
    "SELECT * FROM events ORDER BY conduct_on ASC LIMIT 3"
);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mahapurush Live - Lord Shiva Temple</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            /* Palette Inspired by Shiva */
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
            color: var(--text-dark);
            background-color: var(--bg-body);
            line-height: 1.7;
        }

        /* Typography Overrides */
        h1,
        h2,
        h3,
        h4,
        h5,
        .navbar-brand {
            font-family: 'Playfair Display', serif;
        }

        /* Navbar */
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

        /* Hero Section */
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

        /* Buttons */
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
            padding: 12px 35px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-outline-blue:hover {
            background-color: var(--shiva-blue-deep);
            color: white;
        }

        /* Cards & Sections */
        .section-title {
            color: var(--text-dark);
            font-weight: 700;
            margin-bottom: 1rem;
            position: relative;
            display: inline-block;
        }

        .section-title::after {
            content: '';
            display: block;
            width: 50%;
            height: 3px;
            background-color: var(--shiva-saffron);
            margin: 10px auto 0;
            border-radius: 2px;
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

        /* Event Card Specifics */
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

        /* Developer Section Styles */
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

        /* Footer */
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
            <a class="navbar-brand" href="#">
                <i class="bi bi-brightness-high-fill me-2"></i>Mahapurush Live
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Temple</a></li>
                    <li class="nav-item"><a class="nav-link" href="#pooja">Pooja</a></li>
                    <li class="nav-item"><a class="nav-link" href="#events">Events</a></li>
                    <li class="nav-item"><a class="nav-link" href="#donations">Donations</a></li>
                    <li class="nav-item ms-lg-3">
                        <a class="btn btn-outline-secondary btn-sm rounded-pill px-4" href="#">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero-section text-center">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <p class="hero-subtitle mb-3">Om Namah Shivaya</p>
                    <h1 class="display-3 mb-4 fw-bold">Welcome to <br>Mahapurush Live</h1>
                    <p class="lead text-muted mb-5 px-lg-5">
                        A sacred sanctuary devoted to Lord Shiva. Join us in prayer, meditation, and the celebration of
                        the divine.
                    </p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="#donations" class="btn btn-saffron btn-lg">Donate Now</a>
                        <a href="#pooja" class="btn btn-outline-blue btn-lg">Book Pooja</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="about" class="py-5 bg-white">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <div class="bg-light rounded-4 p-5 text-center border">
                        <i class="bi bi-bank2 text-muted" style="font-size: 5rem; opacity: 0.3;"></i>
                        <p class="small text-muted mt-2">Temple Image Placeholder</p>
                    </div>
                </div>
                <div class="col-lg-6 ps-lg-5">
                    <h2 class="section-title text-start ms-0">About The Temple</h2>
                    <p class="lead text-muted mt-3">
                        A place of peace and spiritual awakening.
                    </p>
                    <div class="text-muted">
                        <?php if ($temple) { ?>
                            <p><?php echo $temple['description']; ?></p>
                        <?php } else { ?>
                            <p>Mahapurush Live is a dedicated space for devotees to connect with the divine energy of Lord
                                Shiva. We provide a peaceful environment for meditation, worship, and community service.</p>
                        <?php } ?>
                    </div>
                    <a href="#" class="btn btn-link text-decoration-none p-0 mt-3">Read Full History →</a>
                </div>
            </div>
        </div>
    </section>

    <section id="pooja" class="py-5" style="background-color: var(--shiva-blue-light);">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="section-title">Our Services</h2>
                <p class="text-muted">How we serve the community</p>
            </div>

            <div class="row g-4">
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-calendar-check feature-icon"></i>
                        <h5 class="fw-bold">Pooja Booking</h5>
                        <p class="small text-muted mb-0">Book Rudrabhishekam and Archana online effortlessly.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-heart-fill feature-icon"></i>
                        <h5 class="fw-bold">Donations</h5>
                        <p class="small text-muted mb-0">Contribute to Annadanam and temple maintenance.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-people-fill feature-icon"></i>
                        <h5 class="fw-bold">Events</h5>
                        <p class="small text-muted mb-0">Join us for festivals, bhajans, and spiritual gatherings.</p>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3">
                    <div class="feature-card">
                        <i class="bi bi-basket-fill feature-icon"></i>
                        <h5 class="fw-bold">Material Seva</h5>
                        <p class="small text-muted mb-0">Donate items like flowers, milk, and clothes for deities.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="events" class="py-5 bg-white">
        <div class="container py-4">
            <div class="text-center mb-5">
                <h2 class="section-title">Upcoming Events</h2>
            </div>

            <div class="row">
                <?php
                if (mysqli_num_rows($eventQuery) > 0) {
                    while ($event = mysqli_fetch_assoc($eventQuery)) {
                        $dateObj = strtotime($event['conduct_on']);
                        ?>
                        <div class="col-md-4 mb-4">
                            <div class="card event-card h-100 p-4">
                                <div class="card-body text-center">
                                    <div class="event-date-box">
                                        <span class="d-block text-uppercase small">Month of</span>
                                        <span class="fs-4"><?php echo date("F", $dateObj); ?></span>
                                    </div>
                                    <h5 class="card-title fw-bold mb-3"><?php echo $event['name']; ?></h5>
                                    <p class="card-text text-muted mb-3">
                                        <i class="bi bi-calendar-event me-2"></i>
                                        <?php echo date("l, d Y", $dateObj); ?>
                                    </p>
                                    <p class="card-text small text-muted">
                                        <i class="bi bi-hourglass-split me-2"></i>
                                        Duration: <?php echo $event['duration']; ?>
                                    </p>
                                    <a href="#" class="btn btn-outline-primary btn-sm rounded-pill mt-3 px-4">Details</a>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center text-muted"><p>No upcoming events scheduled at the moment.</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <section id="donations" class="py-5 text-center" style="background-color: var(--shiva-saffron-light);">
        <div class="container py-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <h2 class="mb-3 fw-bold font-serif">Support Our Temple</h2>
                    <p class="lead text-muted mb-4">
                        "The act of giving is the act of receiving." <br>
                        Your contribution helps us serve devotees and maintain the sanctity of the temple.
                    </p>
                    <button class="btn btn-saffron btn-lg px-5">Contribute for Temple</button>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white border-top">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title">Technical Team</h2>
                <p class="text-muted">Developed & Maintained By</p>
            </div>

            <div class="row justify-content-center">
                <div class="col-md-4 col-lg-3 text-center mb-4 mb-md-0">
                    <div class="dev-card">
                        <img src="https://via.placeholder.com/150" alt="Developer 1"
                            class="rounded-circle dev-img mb-3 shadow-sm">
                        <h5 class="fw-bold mb-1">Developer Name 1</h5>
                        <p class="text-muted small mb-2">Backend Specialist</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-secondary"><i class="bi bi-github"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 col-lg-3 text-center">
                    <div class="dev-card">
                        <img src="https://via.placeholder.com/150" alt="Developer 2"
                            class="rounded-circle dev-img mb-3 shadow-sm">
                        <h5 class="fw-bold mb-1">Developer Name 2</h5>
                        <p class="text-muted small mb-2">Frontend Specialist</p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="#" class="text-secondary"><i class="bi bi-github"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-linkedin"></i></a>
                            <a href="#" class="text-secondary"><i class="bi bi-envelope"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3">Mahapurush Live</h5>
                    <p class="small text-secondary">
                        A sacred temple devoted to Lord Shiva. May the blessings of Mahadev be with you always.
                    </p>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3">Visit Us</h5>
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="bi bi-geo-alt-fill me-2"></i>
                            <?php echo $temple['location'] ?? 'Temple Address, City'; ?></li>
                        <li class="mb-2"><i class="bi bi-telephone-fill me-2"></i>
                            <?php echo $temple['contact'] ?? '+91 000 000 0000'; ?></li>
                        <li class="mb-2"><i class="bi bi-clock-fill me-2"></i>
                            <?php echo $temple['time'] ?? '6 AM - 9 PM'; ?></li>
                    </ul>
                    <a target="_blank"
                        href="https://www.google.com/maps/search/?api=1&query=4P76+HF3+Sindhu+Durg+Oros+Maharashtra+416812"
                        class="text-info small">
                        <i class="bi bi-map me-1"></i> View on Google Maps
                    </a>
                </div>
                <div class="col-md-4">
                    <h5 class="fw-bold text-white mb-3">Quick Links</h5>
                    <ul class="list-unstyled small">
                        <li class="mb-2"><a href="#">Privacy Policy</a></li>
                        <li class="mb-2"><a href="#">Terms of Service</a></li>
                        <li class="mb-2"><a href="#">Contact Support</a></li>
                    </ul>
                </div>
            </div>
            <div class="border-top border-secondary mt-5 pt-4 text-center">
                <small class="text-secondary">
                    © <?php echo date("Y"); ?> Mahapurush Live | <span class="text-white">Har Har Mahadev</span>
                </small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>