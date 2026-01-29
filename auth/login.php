<?php

require __DIR__ . '/../includes/lang.php';
$dbPath = __DIR__ . '/../config/db.php';
if (file_exists($dbPath)) {
    require $dbPath;
} else {
    die("Database conection file missing.");
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = ($lang === 'mr')
            ? 'कृपया ई-मेल आणि पासवर्ड भरा.'
            : 'Please enter both email and password.';
    } else {

        $stmt = $con->prepare("
            SELECT id, first_name, last_name, password
            FROM users
            WHERE email = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {

                // Fetch Roles
                $roleStmt = $con->prepare("
                    SELECT r.id, r.name
                    FROM user_roles ur
                    JOIN roles r ON r.id = ur.role_id
                    WHERE ur.user_id = ?
                ");
                $roleStmt->bind_param("i", $user['id']);
                $roleStmt->execute();
                $roleResult = $roleStmt->get_result();

                $roles = [];
                while ($row = $roleResult->fetch_assoc()) {
                    $roles[] = $row['name'];
                }

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['roles'] = $roles;
                $_SESSION['logged_in'] = true;

                // Role Logic
                $_SESSION['is_customer'] = false;
                $_SESSION['is_committee'] = false;
                $_SESSION['primary_role'] = null;

                $rolePriority = ['chairman', 'vice chairman', 'treasurer', 'secretary', 'member'];

                foreach ($rolePriority as $priorityRole) {
                    if (in_array($priorityRole, $roles, true)) {
                        $_SESSION['primary_role'] = $priorityRole;
                        if ($priorityRole === 'member') {
                            $_SESSION['is_customer'] = true;
                        } else {
                            $_SESSION['is_committee'] = true;
                        }
                        break;
                    }
                }

                header("Location: redirect.php");
                exit;

            } else {
                $error = ($lang === 'mr') ? 'चुकीचा पासवर्ड.' : 'Invalid password.';
            }

        } else {
            $error = ($lang === 'mr') ? 'हे खाते अस्तित्वात नाही.' : 'No account found with that email.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $t['login']; ?> - <?php echo $t['title']; ?></title>

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

        /* Login Card */
        .login-card {
            background: #ffffff;
            width: 100%;
            max-width: 450px;
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

        /* Specific radius for input groups */
        .input-group .form-control:not(:last-child) {
            border-radius: 0;
        }

        .input-group .form-control:last-child {
            border-radius: 0 10px 10px 0;
        }

        /* Eye Icon Style */
        .password-toggle {
            cursor: pointer;
            border-radius: 0 10px 10px 0 !important;
            background-color: white;
        }

        .password-input {
            border-right: none;
            border-radius: 0 !important;
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

        body {
            -webkit-user-select: none;
            /* Chrome, Safari */
            -moz-user-select: none;
            /* Firefox */
            -ms-user-select: none;
            /* IE/Edge */
            user-select: none;
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
                <?php echo $t['home']; ?>
            </a>
        </div>
    </nav>

    <div class="main-wrapper">
        <div class="login-card fade-in">

            <div class="text-center">
                <div class="icon-circle">
                    <i class="bi bi-person-circle"></i>
                </div>
                <h2 class="fw-bold mb-2"><?php echo $t['login']; ?></h2>
                <p class="text-muted small mb-4">
                    <?php echo ($lang === 'mr') ? 'आपल्या खात्यात प्रवेश करण्यासाठी माहिती भरा' : 'Enter your credentials to access your account'; ?>
                </p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center small py-2 rounded-3 mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post">

                <div class="mb-3">
                    <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['email']; ?></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between">
                        <label class="form-label small fw-bold text-muted ps-1"><?php echo $t['password']; ?></label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control password-input" id="id_password">
                        <span class="input-group-text password-toggle" id="togglePassword">
                            <i class="bi bi-eye" id="toggleIcon"></i>
                        </span>
                    </div>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-saffron">
                        <?php echo $t['login']; ?>
                    </button>
                </div>

            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <p class="small text-muted mb-0 mt-3">
                    <?php echo $t['dont_have_account']; ?>
                    <a href="register.php" class="link-saffron ms-1">
                        <?php echo ($lang === 'mr') ? 'नोंदणी करा' : 'Sign Up'; ?>
                    </a>
                </p>
            </div>

        </div>
    </div>

    <footer class="text-center">
        <div class="container">
            <small class="text-white-50">
                &copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?> |
                <span class="text-white"><?php echo $t['copyright']; ?></span>
            </small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#id_password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', function (e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            // toggle the icon
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });
    </script>

</body>

</html>