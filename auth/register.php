<?php
require __DIR__ . '/../includes/lang.php';

$dbPath = __DIR__ . '/../config/db.php';
if (file_exists($dbPath)) {
    require $dbPath;
} else {
    die("Database connection file missing.");
}

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

    if ($firstName === '' || $lastName === '' || $email === '' || $phone === '' || $password === '') {
        $error = $t['err_fill_all'];
    } else if ($password !== $confirm) {
        $error = $t['err_pass_mismatch'];
    } else if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = ($lang === 'mr') ? 'अवैध फोन नंबर.' : 'Invalid phone number.';
    }

    if (empty($error)) {
        $check = $con->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $checkResult = $check->get_result();
        if ($checkResult->num_rows > 0) {
            $error = ($lang === 'mr') ? 'ईमेल किंवा फोन आधीच नोंदणीकृत आहे.' : 'Email or Phone already registered.';
        }
        $check->close();
    }

    if (empty($error)) {
        if (!empty($_FILES['photo']['name']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $fileTmp = $_FILES['photo']['tmp_name'];
            $fileExt = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'png', 'jpeg', 'webp'];
            if (!in_array($fileExt, $allowed)) {
                $error = "Invalid file type.";
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $error = "File size must be under 2MB.";
            } else {
                $photoName = uniqid('user_', true) . '.' . $fileExt;
                $uploadDir = __DIR__ . '/../uploads/users/';
                if (!is_dir($uploadDir))
                    mkdir($uploadDir, 0777, true);
                move_uploaded_file($fileTmp, $uploadDir . $photoName);
            }
        }

        if (empty($error)) {
            $con->begin_transaction();
            try {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $con->prepare("INSERT INTO users (first_name, last_name, email, phone, password, photo, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("ssssss", $firstName, $lastName, $email, $phone, $hashedPassword, $photoName);
                if (!$stmt->execute())
                    throw new Exception($t['err_generic']);
                $newUserId = $stmt->insert_id;
                $stmt->close();

                $roleFetch = $con->prepare("SELECT id FROM roles WHERE name = 'customer' LIMIT 1");
                $roleFetch->execute();
                $roleResult = $roleFetch->get_result();
                $memberRoleId = ($roleResult->num_rows === 1) ? (int) $roleResult->fetch_assoc()['id'] : 2;
                $roleFetch->close();

                $roleStmt = $con->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (?, ?)");
                $roleStmt->bind_param("ii", $newUserId, $memberRoleId);
                $roleStmt->execute();
                $roleStmt->close();

                $con->commit();
                $success = $t['success_register'];
            } catch (Exception $e) {
                $con->rollback();
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

        .ant-header {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            height: 72px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--ant-border-color);
        }

        .auth-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
        }

        .ant-card {
            background: #fff;
            border: 1px solid var(--ant-border-color);
            border-radius: var(--ant-radius);
            box-shadow: var(--ant-shadow);
            width: 100%;
            max-width: 650px;
        }

        .ant-card-body {
            padding: 40px;
        }

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

        .ant-footer {
            padding: 30px 0;
            border-top: 1px solid var(--ant-border-color);
            background: #fff;
        }
    </style>
</head>

<body>

    <header class="ant-header">
        <div class="container d-flex align-items-center justify-content-between">
            <a href="../index.php" class="fw-bold text-dark text-decoration-none fs-4 d-flex align-items-center">
                <i class="bi bi-bank2 text-primary me-2"></i><?php echo $t['title']; ?>
            </a>
            <a href="login.php" class="text-secondary text-decoration-none small fw-medium">
                <?php echo $t['login_here']; ?> <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </header>

    <div class="auth-container">
        <div class="ant-card">
            <div class="ant-card-body">
                <div class="text-center mb-4">
                    <h3 class="fw-bold mb-1"><?php echo $t['register_title']; ?></h3>
                    <p class="text-secondary small"><?php echo $t['register_subtitle']; ?></p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 small py-2 d-flex align-items-center mb-4"
                        style="background: #fff2f0; color: #ff4d4f;">
                        <i class="bi bi-exclamation-circle-fill me-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success border-0 small py-3 text-center mb-4"
                        style="background: #f6ffed; color: #52c41a;">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success); ?><br>
                        <a href="login.php"
                            class="btn btn-sm btn-success mt-2 rounded-pill px-4"><?php echo $t['login_here']; ?></a>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo $t['first_name']; ?></label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo $t['last_name']; ?></label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo $t['email']; ?></label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo ($lang === 'mr') ? 'फोन' : 'Phone'; ?></label>
                            <input type="text" name="phone" class="form-control" pattern="[0-9]{10}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label
                            class="form-label"><?php echo ($lang === 'mr') ? 'प्रोफाइल फोटो (ऐच्छिक)' : 'Profile Photo (Optional)'; ?></label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label"><?php echo $t['password']; ?></label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control" id="reg_pass" required>
                                <button class="btn btn-outline-secondary border-secondary-subtle" type="button"
                                    onclick="togglePass('reg_pass', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?php echo $t['confirm_password']; ?></label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="reg_confirm"
                                    required>
                                <button class="btn btn-outline-secondary border-secondary-subtle" type="button"
                                    onclick="togglePass('reg_confirm', this)">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="ant-btn-primary"><?php echo $t['register_btn']; ?></button>
                </form>

                <div class="text-center mt-4 pt-3 border-top">
                    <p class="small text-muted mb-0">
                        <?php echo $t['have_account']; ?>
                        <a href="login.php"
                            class="text-primary text-decoration-none fw-bold ms-1"><?php echo $t['login_here']; ?></a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <footer class="ant-footer">
        <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="small text-muted">&copy; <?php echo date("Y"); ?> <?php echo $t['title']; ?>.</div>
            <div class="d-flex align-items-center gap-3">
                <img src="../assets/images/dev/Yojana.jpeg" width="32" height="32" class="rounded-circle">
                <div class="text-start">
                    <div style="font-size: 11px;" class="fw-bold">Yojana Gawade</div>
                    <div style="font-size: 9px;" class="text-uppercase text-primary fw-bold">Full Stack Developer</div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        function togglePass(inputId, btn) {
            const input = document.getElementById(inputId);
            const icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        }
    </script>
</body>

</html>