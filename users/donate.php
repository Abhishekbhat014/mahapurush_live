<?php
// =========================================================
// 1. LANGUAGE + SESSION
// =========================================================
require __DIR__ . '/../includes/lang.php';

// =========================================================
// 2. DATABASE CONNECTION
// =========================================================
$dbPath = __DIR__ . '/../config/db.php';
if (file_exists($dbPath)) {
    require $dbPath;
} else {
    $conn = false;
}

// =========================================================
// 3. FORM HANDLING (GUEST DONATION)
// =========================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    $donorName = trim($_POST['name'] ?? '');
    $amount = trim($_POST['amount'] ?? '');
    $note = trim($_POST['note'] ?? '');

    // optional logged-in user
    $userId = $_SESSION['user_id'] ?? NULL;

    // -----------------------------
    // Validation
    // -----------------------------
    if ($donorName === '' || !is_numeric($amount) || $amount <= 0) {
        $error = ($lang === 'mr')
            ? 'कृपया वैध नाव आणि रक्कम भरा.'
            : 'Please enter a valid name and amount.';
    } else {

        $conn->begin_transaction();

        try {
            // =========================================================
            // STEP 1: CREATE RECEIPT
            // =========================================================
            $receiptNo = 'MP-' . date('Y') . '-' . rand(10000, 99999);

            $stmt = $conn->prepare("
                INSERT INTO receipt (receipt_no, issued_on)
                VALUES (?, NOW())
            ");
            $stmt->bind_param("s", $receiptNo);
            $stmt->execute();
            $receiptId = $stmt->insert_id;
            $stmt->close();

            // =========================================================
            // STEP 2: INSERT PAYMENT
            // =========================================================
            $stmt = $conn->prepare("
                INSERT INTO payments
                (receipt_id, user_id, donor_name, amount, note, payment_method, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'cash', 'success', NOW())
            ");

            $stmt->bind_param(
                "iisds",
                $receiptId,
                $userId,
                $donorName,
                $amount,
                $note
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $success = ($lang === 'mr')
                ? "धन्यवाद! आपले दान यशस्वीरीत्या नोंदवले गेले आहे. पावती क्रमांक: $receiptNo"
                : "Thank you! Your donation has been recorded. Receipt No: $receiptNo";

        } catch (Exception $e) {
            $conn->rollback();
            $error = ($lang === 'mr')
                ? 'काहीतरी चूक झाली. कृपया पुन्हा प्रयत्न करा.'
                : 'Something went wrong. Please try again.';
        }
    }
}
?>



<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['donations']; ?> - <?php echo $t['title']; ?></title>

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
            --shiva-saffron-hover: #f57c00;
            --shiva-saffron-light: #fff3e0;
            --text-dark: #2c3e50;
            --text-muted: #607d8b;
            --bg-body: #fdfbf7;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #ffffff 0%, var(--shiva-blue-light) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
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

        .nav-link-back {
            color: var(--text-muted);
            font-weight: 500;
            transition: color 0.3s;
        }

        .nav-link-back:hover {
            color: var(--shiva-saffron);
        }

        /* Main Content */
        .main-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem 1rem;
        }

        /* Donate Card */
        .donate-card {
            background: #ffffff;
            width: 100%;
            max-width: 500px;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08);
            border-top: 6px solid var(--shiva-saffron);
            position: relative;
        }

        .icon-circle {
            width: 70px;
            height: 70px;
            background-color: var(--shiva-saffron-light);
            color: var(--shiva-saffron);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 1.5rem auto;
        }

        /* Form Controls */
        .form-control {
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
            font-size: 0.95rem;
        }

        .form-control:focus {
            border-color: var(--shiva-saffron);
            box-shadow: 0 0 0 4px rgba(255, 152, 0, 0.1);
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 10px 0 0 10px;
            border: 1px solid #dee2e6;
            color: var(--text-muted);
        }

        .form-control {
            border-radius: 10px;
        }

        /* Override for non-group inputs */

        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }

        /* Buttons */
        .btn-saffron {
            background-color: var(--shiva-saffron);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
        }

        .btn-saffron:hover {
            background-color: var(--shiva-saffron-hover);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 152, 0, 0.3);
            color: white;
        }

        /* Footer */
        footer {
            background-color: #263238;
            color: #cfd8dc;
            padding: 1.5rem 0;
            font-size: 0.85rem;
            margin-top: auto;
        }
    </style>
</head>

<body>

    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="bi bi-brightness-high-fill me-2"></i><?php echo $t['title']; ?>
            </a>
            <a href="../index.php" class="nav-link-back text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> <?php echo ($lang === 'mr') ? 'मुख्यपृष्ठ' : 'Home'; ?>
            </a>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="donate-card fade-in">

            <div class="text-center">
                <div class="icon-circle">
                    <i class="bi bi-heart-fill"></i>
                </div>
                <h2 class="fw-bold mb-2"><?php echo $t['support_title']; ?></h2>
                <p class="text-muted small mb-4">
                    <?php echo $t['support_desc']; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center small py-2 rounded-3 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center small py-2 rounded-3 mb-4">
                    <i class="bi bi-check-circle-fill me-1"></i> <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ps-1">
                        <?php echo ($lang === 'mr') ? 'पूर्ण नाव' : 'Full Name'; ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ps-1">
                        <?php echo ($lang === 'mr') ? 'रक्कम (₹)' : 'Amount (₹)'; ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="amount" class="form-control" min="1" placeholder="101" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold text-muted ps-1">
                        <?php echo ($lang === 'mr') ? 'टीप (ऐच्छिक)' : 'Note (Optional)'; ?>
                    </label>
                    <textarea name="note" class="form-control" rows="3" style="border-radius: 10px;"></textarea>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-saffron">
                        <i class="bi bi-heart-fill me-2"></i>
                        <?php echo $t['donate_btn']; ?>
                    </button>
                </div>

            </form>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">
                    <i class="bi bi-shield-check me-1 text-success"></i>
                    <?php echo ($lang === 'mr')
                        ? 'आपले योगदान मंदिराच्या सेवेसाठी वापरले जाईल.'
                        : 'Your contribution will be secure and used for temple services.'; ?>
                </p>
            </div>

        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <small>
                &copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?> |
                <span class="text-white-50"><?php echo $t['copyright_msg']; ?></span>
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>