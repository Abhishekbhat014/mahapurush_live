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



$nakName = $nak['name'] ?? $t['unknown'];
$yogaName = $yoga['name'] ?? $t['unknown'];

?>




<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['daily_panchang']; ?> - <?php echo $t['title']; ?></title>

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
            min-height: 100vh;
            -webkit-user-select: none;
            user-select: none;
        }

        /* Header */
        .panchang-header {
            padding: 70px 0 40px;
            text-align: center;
            border-bottom: 1px solid var(--ant-border-color);
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 80%);
        }

        /* Cards */
        .panchang-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            padding: 2rem;
            box-shadow: var(--ant-shadow);
            height: 100%;
            transition: transform 0.3s;
        }

        .panchang-card:hover {
            transform: translateY(-5px);
        }

        .data-label {
            color: var(--ant-text-sec);
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 0.5rem;
        }

        .data-value {
            font-size: 1.35rem;
            font-weight: 700;
            color: var(--ant-primary);
        }

        .sun-card {
            background-color: #f6f9ff;
        }

        /* Footer spacing helper */
        .ant-footer {
            margin-top: auto;
        }
    </style>
</head>

<body>

    <?php include 'includes/header.php'; ?>

    <header class="panchang-header">
        <div class="container">
            <h1 class="display-5 fw-bold mb-2"><?php echo $t['daily_panchang']; ?></h1>
            <p class="lead text-muted mb-4">
                <i class="bi bi-geo-alt-fill me-1"></i> <?php echo $t['mumbai']; ?> &bull; <?php echo $displayDate; ?>
            </p>
            <form action="" method="GET" class="mx-auto" style="max-width: 300px;">
                <div class="input-group">
                    <input type="date" name="date" class="form-control rounded-start-pill ps-3 border-secondary"
                        value="<?php echo date('Y-m-d', strtotime($dateInput)); ?>">
                    <button type="submit" class="btn btn-primary rounded-end-pill px-4 fw-bold">
                        <?php echo $t['go']; ?>
                    </button>
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
                        <h3 class="fw-bold mb-0 text-dark"><?php echo $t['almanac_details']; ?></h3>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-12">
                        <div class="p-4 rounded-3" style="background-color: #f0f6ff;">
                                <div class="data-label text-primary"><?php echo $t['current_tithi(lunar_day)']; ?></div>
                                <div class="data-value" style="font-size: 1.8rem;">
                                    <?php echo "Demo"; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-4 h-100">
                                <div class="data-label"><?php echo $t['nakshatra(star)']; ?></div>
                                <div class="data-value text-secondary">
                                    <?php echo $nakName; ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="border rounded-3 p-4 h-100">
                                <div class="data-label"><?php echo $t['yoga']; ?></div>
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
                            <h4 class="fw-bold mb-4"><i class="bi bi-brightness-alt-high-fill me-2"></i>
                                <?php echo $t['sun_details']; ?>
                            </h4>
                            <div
                                class="d-flex justify-content-between align-items-center mb-3 border-bottom border-secondary pb-3">
                                <span class="text-muted fw-bold"><?php echo $t['sunrise']; ?></span>
                                <span class="fs-4 fw-bold text-dark"><?php echo $sunrise; ?></span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fw-bold"><?php echo $t['sunset']; ?></span>
                                <span class="fs-4 fw-bold text-dark"><?php echo $sunset; ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="panchang-card">
                            <h5 class="fw-bold mb-3 text-danger"><i class="bi bi-exclamation-circle-fill me-2"></i>
                                <?php echo $t['inauspicious_time']; ?></h5>
                            <div class="p-3 bg-light rounded-3 border-start border-4 border-danger">
                                <div class="data-label text-danger mb-1"><?php echo $t['rahu_kalam']; ?></div>
                                <div class="fs-4 fw-bold text-dark"><?php echo $rahuDisplay; ?></div>
                                <small class="text-muted"><?php echo $t['avoid_starting_new_task']; ?>.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>

