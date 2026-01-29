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
    die("Database connection file missing.");
}

// =========================================================
// 3. REGISTER LOGIC
// =========================================================
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');
    $photoName = null;

    // validation
    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $error = $t['err_fill_all'];
    } else if ($password !== $confirm) {
        $error = $t['err_pass_mismatch'];
    } else if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = ($lang === 'mr') ? 'अवैध फोन नंबर.' : 'Invalid phone number.';
    }

    // Check duplicate
    if (empty($error)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $checkResult = $check->get_result();

        if ($checkResult->num_rows > 0) {
            $error = ($lang === 'mr') ? 'ईमेल किंवा फोन आधीच नोंदणीकृत आहे.' : 'Email or Phone already registered.';
        }
        $check->close();
    }

    if (empty($error)) {
        // photo upload optional
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['photo']['tmp_name'];
            $fileName = basename($_FILES['photo']['name']);
            $fileSize = $_FILES['photo']['size'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'png', 'jpeg', 'webp'];

            if (!in_array($fileExt, $allowed)) {
                $error = "Invalid file type.";
            } elseif ($fileSize > 2 * 1024 * 1024) {
                $error = "File size must be under 2MB.";
            } else {
                $photoName = uniqid('user_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../uploads/users/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                if (!move_uploaded_file($fileTmp, $uploadDir . $photoName)) {
                    $error = "Photo upload failed.";
                }
            }
        }

        // Insert
        if (empty($error)) {
            $conn->begin_transaction();
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert User
                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $hashedPassword, $photoName);

                if (!$stmt->execute()) {
                    throw new Exception($t['err_generic']);
                }
                $newUserId = $stmt->insert_id;
                $stmt->close();

                // Assign Role (Customer)
                $roleFetch = $conn->prepare("SELECT id FROM roles WHERE name = 'customer' LIMIT 1");
                $roleFetch->execute();
                $roleResult = $roleFetch->get_result();

                if ($roleResult->num_rows !== 1) {
                    // Fallback to ID 3 if role name not found (adjust based on your DB)
                    $memberRoleId = 3;
                } else {
                    $roleRow = $roleResult->fetch_assoc();
                    $memberRoleId = (int) $roleRow['id'];
                }
                $roleFetch->close();

                $roleStmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $roleStmt->bind_param("ii", $newUserId, $memberRoleId);

                if (!$roleStmt->execute()) {
                    throw new Exception($t['err_generic']);
                }
                $roleStmt->close();

                $conn->commit();
                $success = $t['success_register'];

            } catch (Exception $e) {
                $conn->rollback();
                $error = $e->getMessage();
            }
        }
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
           THEME VARIABLES (Shiva Design)
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
            background: linear-gradient(135deg, var(--bg-body) 0%, var(--shiva-blue-light) 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            color: var(--text-dark);
        }

        /* Typography */
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
        .auth-wrapper {
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
            max-width: 650px;
            /* Wider for 2-column fields */
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
        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.3rem;
        }

        .input-group-text {
            background-color: #f8f9fa;
            border-radius: 10px 0 0 10px;
            border: 1px solid #dee2e6;
            border-right: none;
            color: var(--text-muted);
            min-width: 45px;
            justify-content: center;
        }

        .form-control {
            padding: 12px;
            border-radius: 0 10px 10px 0;
            border: 1px solid #dee2e6;
            border-left: none;
            font-size: 0.95rem;
        }

        /* Focus State */
        .input-group:focus-within .input-group-text {
            border-color: var(--shiva-saffron);
            color: var(--shiva-saffron);
        }

        .input-group:focus-within .form-control {
            border-color: var(--shiva-saffron);
            box-shadow: 0 0 0 4px rgba(255, 152, 0, 0.1);
        }

        /* Special styling for File Input */
        input[type="file"].form-control {
            border-radius: 10px;
            border-left: 1px solid #dee2e6;
        }

        .input-group-file .input-group-text {
            border-right: 1px solid #dee2e6;
            /* Restore border for file icon */
        }

        /* Password Eye Toggle Styling */
        .password-input {
            border-right: none !important;
            border-radius: 0 !important;
        }

        .password-toggle {
            cursor: pointer;
            background-color: white;
            border-left: none;
            border-radius: 0 10px 10px 0 !important;
        }

        .password-toggle:hover {
            color: var(--shiva-saffron);
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

        /* Links */
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

    <div class="auth-wrapper">
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
                <div class="alert alert-danger d-flex align-items-center rounded-3 p-2 small mb-4">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 me-2"></i>
                    <div><?php echo htmlspecialchars($error); ?></div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success text-center rounded-3 p-3 mb-4">
                    <div class="d-flex align-items-center justify-content-center mb-2">
                        <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                        <span class="fw-bold"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <a href="login.php" class="btn btn-sm btn-outline-success rounded-pill px-4">
                        <?php echo $t['login_here']; ?>
                    </a>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['first_name']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['last_name']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['email']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@example.com"
                                required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo ($lang === 'mr') ? 'फोन' : 'Phone'; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                            <input type="text" name="phone" class="form-control" pattern="[0-9]{10}"
                                placeholder="9876543210" required>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label
                        class="form-label"><?php echo ($lang === 'mr') ? 'प्रोफाइल फोटो (ऐच्छिक)' : 'Profile Photo (Optional)'; ?></label>
                    <div class="input-group input-group-file">
                        <span class="input-group-text"><i class="bi bi-camera"></i></span>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="form-text small mt-1 text-end">Max size 2MB (JPG, PNG)</div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['password']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control password-input" id="reg_pass"
                                required>
                            <span class="input-group-text password-toggle" onclick="togglePass('reg_pass', this)">
                                <i class="bi bi-eye"></i>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label"><?php echo $t['confirm_password']; ?></label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock-fill"></i></span>
                            <input type="password" name="confirm_password" class="form-control password-input"
                                id="reg_confirm" required>
                            <span class="input-group-text password-toggle" onclick="togglePass('reg_confirm', this)">
                                <i class="bi bi-eye"></i>
                            </span>
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

    <script>
        function togglePass(inputId, toggleBtn) {
            const input = document.getElementById(inputId);
            const icon = toggleBtn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>

</body>

</html>