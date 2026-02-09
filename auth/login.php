<?php
// Include language file
require __DIR__ . '/../includes/lang.php';

// Include db file
$dbPath = __DIR__ . '/../config/db.php';

if (file_exists($dbPath)) {
    require $dbPath;
} else {
    die($t['db_connection_missing']);
}

// FIX: Check if user is already logged in
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: redirect.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // fetch email and password
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // if email and password are empty
    if ($email === '' || $password === '') {
        $error = $t['err_email_password_required'];
    } else {
        $stmt = $con->prepare("SELECT id, first_name, last_name, password, photo FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            // if password matches
            if (password_verify($password, $user['password'])) {
                // Fetch Roles
                $roleStmt = $con->prepare("
                    SELECT r.name
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
                $_SESSION['user_name'] = trim($user['first_name'] . ' ' . $user['last_name']);
                $_SESSION['user_photo'] = $user['photo'] ?? null;
                $_SESSION['roles'] = $roles;
                $_SESSION['user_email'] = $email;
                $_SESSION['logged_in'] = true;
                $_SESSION['primary_role'] = $roles[0] ?? "customer";

                header("Location: redirect.php");
                exit;
            } else {
                $error = $t['err_invalid_password'];
            }
        } else {
            $error = $t['err_no_account'];
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

    <style>
        :root {
            --ant-primary: #1677ff;
            --ant-primary-hover: #4096ff;
            --ant-bg-layout: #f8f9fa;
            --ant-border-color: #f0f0f0;
            --ant-text: rgba(0, 0, 0, 0.88);
            --ant-text-sec: rgba(0, 0, 0, 0.45);
            --ant-radius: 12px;
            --ant-shadow: 0 6px 16px 0 rgba(0, 0, 0, 0.08);
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: radial-gradient(circle at top right, #e6f4ff 0%, #ffffff 70%);
            color: var(--ant-text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        body,
        body * {
            -webkit-user-select: none;
            user-select: none;
        }

        /* --- Header --- */
        .ant-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            height: 72px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--ant-border-color);
        }

        /* --- Login Card --- */
        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
        }

        .ant-card-body {
            padding: 40px;
        }

        /* --- Form Controls --- */
        .form-label {
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 8px;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 12px;
            border: 1px solid #d9d9d9;
            transition: all 0.2s;
        }

        .form-control:focus {
            border-color: var(--ant-primary);
            box-shadow: 0 0 0 2px rgba(22, 119, 255, 0.1);
        }

        .input-group-text {
            background: #fafafa;
            border-color: #d9d9d9;
            color: var(--ant-text-sec);
        }

        /* --- REMOVE BOOTSTRAP VALIDATION SUCCESS ICONS --- */
        .was-validated .form-control:valid {
            background-image: none !important;
            border-color: #d9d9d9 !important;
            padding-right: 12px !important;
        }

        .was-validated .form-control:valid:focus {
            border-color: var(--ant-primary) !important;
            box-shadow: 0 0 0 2px rgba(22, 119, 255, 0.1) !important;
        }

        /* --- Buttons --- */
        .ant-btn-primary {
            background-color: var(--ant-primary);
            color: #fff;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }

        .ant-btn-primary:hover {
            background-color: var(--ant-primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(22, 119, 255, 0.2);
        }

        /* --- Footer --- */
        .ant-footer {
            padding: 30px 0;
            border-top: 1px solid var(--ant-border-color);
            background: #fff;
        }

        .ant-divider {
            height: 1px;
            background: var(--ant-border-color);
            margin: 24px 0;
        }
    </style>
</head>

<body>

    <header class="ant-header">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="../index.php" class="text-secondary text-decoration-none small fw-medium">
                <?php echo $t['home']; ?>
            </a>
        </div>
    </header>

    <div class="login-container">
        <div class="ant-card">
            <div class="ant-card-body">
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3"
                        style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock fs-3"></i>
                    </div>
                    <h3 class="fw-bold mb-1"><?php echo $t['login']; ?></h3>
                    <p class="text-secondary small">
                        <?php echo $t['login_subtitle']; ?>
                    </p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small py-2 d-flex align-items-center"
                        style="background: #fff2f0; color: #ff4d4f;">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['email']; ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control"
                                placeholder="<?php echo $t['email_placeholder']; ?>" required>
                            <div class="invalid-feedback">
                                <?php echo $t['err_invalid_email'] ?? 'Please enter a valid email.'; ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label"><?php echo $t['password']; ?></label>
                        <div class="input-group has-validation">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" id="id_password"
                                placeholder="••••••••" required>
                            <button class="btn btn-outline-secondary border-secondary-subtle" type="button"
                                id="togglePassword">
                                <i class="bi bi-eye" id="toggleIcon"></i>
                            </button>
                            <div class="invalid-feedback">
                                <?php echo $t['field_required'] ?? 'This field is required.'; ?>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="ant-btn-primary">
                        <?php echo $t['login']; ?>
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">
                        <?php echo $t['dont_have_account']; ?>
                        <a href="register.php" class="text-primary text-decoration-none fw-bold ms-1">
                            <?php echo $t['sign_up']; ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="ant-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-muted">
                <?php echo sprintf($t['copyright_footer_title'], date("Y"), $t['title']); ?>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="d-flex gap-3 small fw-bold">
                    <span class="text-primary">Developed By: Yojana Gawade</span>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const passwordField = document.querySelector('#id_password');
        const toggleIcon = document.querySelector('#toggleIcon');

        togglePassword.addEventListener('click', function () {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            toggleIcon.classList.toggle('bi-eye');
            toggleIcon.classList.toggle('bi-eye-slash');
        });
    </script>
    <script>
        (function () {
            'use strict';
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
    <script>
        document.addEventListener('contextmenu', function (e) {
            e.preventDefault();
        });
    </script>
    <script>
        // Only keep this if you want to prevent form resubmission on refresh
        // It does NOT handle the back button cache issue alone.
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>

</html>