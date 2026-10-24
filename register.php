<?php 
session_start();

if (isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

require_once 'config/db.php';
require_once 'classes/User.php';

$error = '';
$success = '';
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $database = new DBConn();
            $db = $database->connect();
            $userModel = new User($db);

            if ($userModel->emailExists($email)) {
                $error = 'This email is already registered.';
            } elseif ($userModel->register($name, $email, $password)) {
                $success = 'Registration successful! You can now log in.';
                $name = '';
                $email = '';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - GatherPoint</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --bg-body: #f4f7fb;
            --primary-blue: #2563eb;
            --primary-hover: #1d4ed8;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 1.5rem;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 28px;
            border: 1px solid rgba(226, 232, 240, 0.8);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.04);
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
        }

        .form-control {
            border-radius: 14px;
            padding: 0.75rem 1rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
        }

        .btn-custom-primary {
            background-color: var(--primary-blue);
            color: #fff;
            border-radius: 50px;
            padding: 0.8rem;
            font-weight: 600;
            border: none;
            width: 100%;
            transition: all 0.2s ease;
        }

        .btn-custom-primary:hover {
            background-color: var(--primary-hover);
            color: #fff;
        }
    </style>
</head>
<body>

<div class="auth-card">
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center bg-primary-subtle text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
            <i class="bi bi-person-plus fs-2"></i>
        </div>

        <h4 class="fw-bold mb-1">Create Account</h4>
        <p class="text-muted small">Join us to book available slots instantly</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <a href="login.php" class="alert-link">Log in here</a>
        </div>
    <?php endif; ?>

    <form action="register.php" method="POST">
        <div class="mb-3">
            <label for="name" class="form-label small fw-semibold">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="John Doe" value="<?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>" maxlength="100" required>
        </div>

        <div class="mb-3">
            <label for="email" class="form-label small fw-semibold">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="name@example.com" value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" maxlength="200" required>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-semibold">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" minlength="8" required>
        </div>

        <div class="mb-4">
            <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter your password" minlength="8" required>
        </div>

        <button type="submit" class="btn btn-custom-primary mb-3">Sign Up</button>
    </form>

    <div class="text-center">
        <span class="text-muted small">Already registered?</span>
        <a href="login.php" class="text-primary text-decoration-none fw-semibold small ms-1">Log in</a>
    </div>
</div>

</body>
</html>