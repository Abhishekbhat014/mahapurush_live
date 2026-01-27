<?php
// =========================================================
// 1. INCLUDE LANGUAGE LOGIC
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
// 3. REGISTER LOGIC
// =========================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? ''); // New
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $photoName = null; // New

    // 1. Basic Validation
    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $error = $t['err_fill_all'];
    } elseif ($password !== $confirm) {
        $error = $t['err_pass_mismatch'];
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        // Simple 10-digit check
        $error = ($lang === 'mr') ? 'अवैध फोन नंबर.' : 'Invalid phone number.';
    } else {

        // 2. Check Uniqueness (Email OR Phone)
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = ($lang === 'mr')
                ? 'ईमेल किंवा फोन आधीच नोंदणीकृत आहे.'
                : 'Email or Phone already registered.';
        } else {

            // 3. Handle File Upload (Photo)
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $fileTmp = $_FILES['photo']['tmp_name'];
                $fileName = basename($_FILES['photo']['name']);
                $fileSize = $_FILES['photo']['size'];
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $allowed = ['jpg', 'jpeg', 'png', 'webp'];

                if (in_array($fileExt, $allowed)) {
                    if ($fileSize < 2 * 1024 * 1024) { // 2MB Limit
                        // Generate unique name
                        $newFileName = uniqid('user_', true) . '.' . $fileExt;
                        $uploadDir = __DIR__ . '/../uploads/users/';

                        // Ensure directory exists
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }

                        if (move_uploaded_file($fileTmp, $uploadDir . $newFileName)) {
                            $photoName = $newFileName;
                        } else {
                            $error = "Failed to upload photo.";
                        }
                    } else {
                        $error = "File size must be less than 2MB.";
                    }
                } else {
                    $error = "Invalid file type. Only JPG, PNG, WEBP allowed.";
                }
            }

            // Only proceed if no upload error occurred
            if (empty($error)) {
                // 4. Insert User
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                // 4. Insert User (WITHOUT role)
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $conn->prepare(
                    "INSERT INTO users (first_name, last_name, email, phone, password, photo, created_at)
     VALUES (?, ?, ?, ?, ?, ?, NOW())"
                );
                $stmt->bind_param(
                    "ssssss",
                    $firstName,
                    $lastName,
                    $email,
                    $phone,
                    $hashedPassword,
                    $photoName
                );

                if ($stmt->execute()) {

                    // Get newly created user ID
                    $newUserId = $stmt->insert_id;
                    $stmt->close();

                    // 5. Assign default role (customer)
                    // ⚠️ Change role_id according to your roles table
                    $defaultRoleId = 3; // example: customer

                    $roleStmt = $conn->prepare(
                        "INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)"
                    );
                    $roleStmt->bind_param("ii", $newUserId, $defaultRoleId);

                    if ($roleStmt->execute()) {
                        $success = $t['success_register'];
                    } else {
                        $error = $t['err_generic'];
                    }

                    $roleStmt->close();

                } else {
                    $error = $t['err_generic'];
                }

                if ($stmt->execute()) {
                    $success = $t['success_register'];
                } else {
                    $error = $t['err_generic'];
                }
                $stmt->close();
            }
        }
        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['register_title']; ?> - <?php echo $t['title']; ?></title>

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

        /* Register Card */
        .register-card {
            background: #ffffff;
            width: 100%;
            max-width: 600px;
            /* Slightly wider for new fields */
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
            border-radius: 0 10px 10px 0;
        }

        /* Specific fix for file input */
        input[type="file"].form-control {
            border-radius: 10px;
            padding: 10px;
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

        .link-saffron {
            color: var(--shiva-saffron);
            text-decoration: none;
            font-weight: 600;
        }

        .link-saffron:hover {
            text-decoration: underline;
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
            <a href="login.php" class="nav-link-back text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> <?php echo $t['back_to_login']; ?>
            </a>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="register-card fade-in">

            <div class="text-center">
                <div class="icon-circle">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h2 class="fw-bold mb-2"><?php echo $t['register_title']; ?></h2>
                <p class="text-muted small mb-4">
                    <?php echo $t['register_subtitle']; ?>
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
                    <div class="mt-2">
                        <a href="login.php" class="fw-bold text-success text-decoration-underline">
                            <?php echo $t['login_here']; ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['first_name']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['last_name']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['email']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="example@mail.com"
                                required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted ps-1">
                            <?php echo ($lang === 'mr') ? 'फोन' : 'Phone'; ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="phone" class="form-control" placeholder="9876543210"
                                pattern="[0-9]{10}" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ps-1">
                        <?php echo ($lang === 'mr') ? 'प्रोफाइल फोटो (ऐच्छिक)' : 'Profile Photo (Optional)'; ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-camera"></i></span>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="form-text small mt-1">Max size 2MB (JPG, PNG)</div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['password']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label
                            class="form-label small fw-bold text-muted ps-1"><?php echo $t['confirm_password']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control" placeholder="••••••••"
                                required>
                        </div>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-saffron">
                        <?php echo $t['register_btn']; ?>
                    </button>
                </div>

            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <p class="small text-muted mb-0 mt-3">
                    <?php echo $t['have_account']; ?>
                    <a href="login.php" class="link-saffron ms-1">
                        <?php echo $t['login_here']; ?>
                    </a>
                </p>
            </div>

        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <small>
                <?php echo $t['copyright']; ?> |
                <span class="text-white-50"><?php echo $t['copyright_msg']; ?></span>
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>