<?php
// =========================================================
// 1. SESSION + LANGUAGE
// =========================================================
session_start();
require __DIR__ . '/includes/lang.php';

// =========================================================
// 2. BASIC CONFIG
// =========================================================
$isLoggedIn = $_SESSION['logged_in'] ?? false;
$apiKey = "HhS8ofsjDn6PfSrJX94cq2A6Gyc62vWb2xULRFPu";

// =========================================================
// 3. DATE HANDLING
// =========================================================
$dateRaw = $_GET['date'] ?? date('Y-m-d');

// Handle DD/MM/YYYY → YYYY-MM-DD
if (preg_match('#^\d{2}/\d{2}/\d{4}$#', $dateRaw)) {
    [$d, $m, $y] = explode('/', $dateRaw);
    $dateInput = "$y-$m-$d";
} else {
    $dateInput = $dateRaw;
}

$displayDate = date("l, d F Y", strtotime($dateInput));

// =========================================================
// 4. LOCATION (Mumbai)
// =========================================================
$lat = 19.0760;
$lon = 72.8777;
$tzone = 5.5;

// =========================================================
// 5. THROTTLE FUNCTION (IMPORTANT)
// =========================================================
function throttle()
{
    usleep(1100000); // 1.1 seconds (API allows 1 req/sec)
}

// =========================================================
// 6. API FUNCTIONS
// =========================================================

function getSunriseSunset($date, $lat, $lon, $tzone, $apiKey)
{
    return apiPost("getsunriseandset", [
        "year" => (int) date('Y', strtotime($date)),
        "month" => (int) date('m', strtotime($date)),
        "date" => (int) date('d', strtotime($date)),
        "hours" => 6,
        "minutes" => 0,
        "seconds" => 0,
        "latitude" => $lat,
        "longitude" => $lon,
        "timezone" => $tzone
    ], $apiKey, true);
}

function getRahuKalam($date, $lat, $lon, $tzone, $apiKey)
{
    $data = apiPost("rahu-kalam", basePayload($date, $lat, $lon, $tzone), $apiKey);
    return decodeDoubleJson($data['output'] ?? null);
}

function getTithi($date, $lat, $lon, $tzone, $apiKey)
{
    $data = apiPost("tithi-durations", basePayload($date, $lat, $lon, $tzone, true), $apiKey);
    return decodeDoubleJson($data['output'] ?? null);
}

function getNakshatra($date, $lat, $lon, $tzone, $apiKey)
{
    $data = apiPost("nakshatra-durations", basePayload($date, $lat, $lon, $tzone, true), $apiKey);
    return decodeDoubleJson($data['output'] ?? null);
}

function getYoga($date, $lat, $lon, $tzone, $apiKey)
{
    $data = apiPost("yoga-durations", basePayload($date, $lat, $lon, $tzone, true), $apiKey);
    $decoded = decodeDoubleJson($data['output'] ?? null);
    return $decoded['1'] ?? null;
}

// =========================================================
// 7. HELPERS
// =========================================================

function basePayload($date, $lat, $lon, $tzone, $config = false)
{
    $payload = [
        "year" => (int) date('Y', strtotime($date)),
        "month" => (int) date('m', strtotime($date)),
        "date" => (int) date('d', strtotime($date)),
        "hours" => 6,
        "minutes" => 0,
        "seconds" => 0,
        "latitude" => $lat,
        "longitude" => $lon,
        "timezone" => $tzone
    ];

    if ($config) {
        $payload["config"] = [
            "observation_point" => "topocentric",
            "ayanamsha" => "lahiri"
        ];
    }

    return $payload;
}

function apiPost($endpoint, $payload, $apiKey, $directOutput = false)
{
    $ch = curl_init("https://json.freeastrologyapi.com/$endpoint");

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "x-api-key: $apiKey"
        ]
    ]);

    $res = curl_exec($ch);
    curl_close($ch);

    if (!$res)
        return null;

    $decoded = json_decode($res, true);

    if ($directOutput && isset($decoded['output'])) {
        return $decoded['output'];
    }

    return $decoded;
}

function decodeDoubleJson($value)
{
    if (!is_string($value))
        return null;
    $decoded = json_decode($value, true);
    return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
}

// =========================================================
// 8. FETCH PANCHANG DATA (WITH THROTTLING)
// =========================================================

$sun = getSunriseSunset($dateInput, $lat, $lon, $tzone, $apiKey);
throttle();

$rahu = getRahuKalam($dateInput, $lat, $lon, $tzone, $apiKey);
throttle();

$tithi = getTithi($dateInput, $lat, $lon, $tzone, $apiKey);
throttle();

$nak = getNakshatra($dateInput, $lat, $lon, $tzone, $apiKey);
throttle();

$yoga = getYoga($dateInput, $lat, $lon, $tzone, $apiKey);

// =========================================================
// 9. FINAL VALUES FOR UI
// =========================================================

$sunrise = $sun['sun_rise_time'] ?? '--:--';
$sunset = $sun['sun_set_time'] ?? '--:--';

$rahuDisplay = ($rahu)
    ? date("h:i A", strtotime($rahu['starts_at'])) . " - " . date("h:i A", strtotime($rahu['ends_at']))
    : "--:--";

$tithiDisplay = ($tithi)
    ? ucfirst($tithi['paksha']) . " Paksha " . $tithi['name']
    : "Unknown";

$nakName = $nak['name'] ?? 'Unknown';
$yogaName = $yoga['name'] ?? 'Unknown';

?>




<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Panchang - <?php echo $t['title']; ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Playfair+Display:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        /* ===============================
           SHIVA THEME VARIABLES
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
            background: linear-gradient(135deg, var(--bg-body) 0%, var(--shiva-blue-light) 100%);
            min-height: 100vh;
            color: var(--text-dark);
            display: flex;
            flex-direction: column;
        }

        h1,
        h2,
        h3,
        h4,
        .navbar-brand {
            font-family: 'Playfair Display', serif;
        }

        /* Navbar */
        .navbar {
            background: #ffffff;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.04);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--shiva-saffron) !important;
            font-size: 1.5rem;
        }

        .nav-link {
            color: var(--text-dark);
            font-weight: 500;
        }

        .nav-link:hover,
        .nav-link.active {
            color: var(--shiva-saffron);
        }

        /* Header */
        .panchang-header {
            padding: 60px 0 40px;
            text-align: center;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            background: white;
        }

        /* Cards */
        .panchang-card {
            background: white;
            border: none;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            height: 100%;
            border-top: 4px solid var(--shiva-saffron);
            transition: transform 0.3s;
        }

        .panchang-card:hover {
            transform: translateY(-5px);
        }

        .data-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .data-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--shiva-blue-deep);
        }

        .sun-card {
            background-color: var(--shiva-saffron-light);
            border-top-color: var(--shiva-blue-deep);
        }

        /* Footer */
        footer {
            background-color: #263238;
            color: #eceff1;
            padding: 2rem 0;
            margin-top: auto;
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
                    <li class="nav-item"><a class="nav-link" href="index.php"><?php echo $t['home']; ?></a></li>
                    <li class="nav-item"><a class="nav-link active" href="panchang.php">Panchang</a></li>

                    <li class="nav-item dropdown ms-lg-2">
                        <a class="nav-link dropdown-toggle text-primary fw-bold" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-translate me-1"></i> <?php echo ($lang == 'en') ? 'English' : 'मराठी'; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?date=<?php echo $dateInput; ?>&lang=en">English</a></li>
                            <li><a class="dropdown-item" href="?date=<?php echo $dateInput; ?>&lang=mr">मराठी</a></li>
                        </ul>
                    </li>

                    <li class="nav-item ms-lg-3">
                        <?php if ($isLoggedIn) { ?>
                            <a class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                href="auth/redirect.php">Dashboard</a>
                        <?php } else { ?>
                            <a class="btn btn-outline-primary btn-sm rounded-pill px-3"
                                href="auth/login.php"><?php echo $t['login']; ?></a>
                        <?php } ?>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="panchang-header">
        <div class="container">
            <h1 class="display-5 fw-bold mb-2">Daily Panchang</h1>
            <p class="lead text-muted mb-4">
                <i class="bi bi-geo-alt-fill me-1"></i> Mumbai &bull; <?php echo $displayDate; ?>
            </p>
            <form action="" method="GET" class="mx-auto" style="max-width: 300px;">
                <div class="input-group">
                    <input type="date" name="date" class="form-control rounded-start-pill ps-3 border-secondary"
                        value="<?php echo date('Y-m-d', strtotime($dateInput)); ?>" <button type="submit"
                        class="btn btn-warning text-white rounded-end-pill px-4 fw-bold">Go</button>
                </div>
            </form>
        </div>
    </header>

    <div class="container py-5">
        <div class="row g-4">

            <div class="col-lg-8">
                <div class="panchang-card">
                    <div class="d-flex align-items-center mb-4">
                        <div class="icon-circle bg-light text-warning rounded-circle p-3 me-3">
                            <i class="bi bi-moon-stars-fill fs-3"></i>
                        </div>
                        <h3 class="fw-bold mb-0 text-dark">Almanac Details</h3>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-12">
                            <div class="p-4 rounded-3" style="background-color: var(--shiva-blue-light);">
                                <div class="data-label text-primary">Current Tithi (Lunar Day)</div>
                                <div class="data-value" style="font-size: 1.8rem;">
                                    <?php echo $tithiDisplay; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-4 h-100">
                                <div class="data-label">Nakshatra (Star)</div>
                                <div class="data-value text-secondary">
                                    <?php echo $nakName; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-4 h-100">
                                <div class="data-label">Yoga</div>
                                <div class="data-value text-secondary">
                                    <?php echo $yogaName; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="row g-4">
                    <div class="col-12">
                        <div class="panchang-card sun-card">
                            <h4 class="fw-bold mb-4"><i class="bi bi-brightness-alt-high-fill me-2"></i> Sun Details
                            </h4>
                            <div
                                class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-3">
                                <span class="text-muted fw-bold">Sunrise</span>
                                <span class="fs-4 fw-bold text-dark"><?php echo $sunrise; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-bold">Sunset</span>
                                <span class="fs-4 fw-bold text-dark"><?php echo $sunset; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="panchang-card">
                            <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-exclamation-circle-fill me-2"></i>
                                Inauspicious Time</h5>
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-danger">
                                <div class="data-label text-danger mb-1">Rahu Kalam</div>
                                <div class="fs-4 fw-bold text-dark"><?php echo $rahuDisplay; ?></div>
                                <small class="text-muted">Avoid starting new tasks.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <small>&copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?> | <span
                    class="text-white-50"><?php echo $t['copyright_msg']; ?></span></small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>