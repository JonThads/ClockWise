<?php
require_once 'config/database.php';

$error = '';
$rememberedUser = $_COOKIE['remembered_user'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (!empty($username) && !empty($password)) {
        try {
            $sql = "SELECT 
                        e.emp_id,
                        e.emp_username,
                        e.emp_first_name,
                        e.emp_password,
                        ur.role_name
                    FROM employees e
                    JOIN user_roles ur ON e.role_id = ur.role_id
                    WHERE e.emp_username = :username
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':username' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['emp_password'])) {
                $_SESSION['user_id']    = $user['emp_id'];
                $_SESSION['username']   = $user['emp_username'];
                $_SESSION['name']       = $user['emp_first_name'];
                $_SESSION['role']       = strtolower($user['role_name']);
                $_SESSION['login_time'] = time();

                if ($remember) {
                    setcookie('remembered_user', $username, time() + (86400 * 30), '/');
                }

                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin-dashboard.php');
                } elseif ($_SESSION['role'] === 'user') {
                    header('Location: user-dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error = 'Invalid username or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'A system error occurred. Please try again later.';
        }
    } else {
        $error = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- WCAG 2.4.2 — Descriptive page title -->
    <title>Login – ClockWise DTR & Leave Management</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <!-- WCAG 2.4.1 — Skip link to bypass branding panel -->
    <a href="#login-form" class="skip-link">Skip to login form</a>

    <div class="login-container">
        <!-- Left Side — Branding (decorative, hidden from assistive tech flow) -->
        <div class="login-left" aria-hidden="true">
            <div class="logo-section">
                <!-- WCAG 1.1.1 — decorative emoji, aria-hidden -->
                <div class="logo" aria-hidden="true">⏰</div>
                <p class="brand-name">ClockWise</p>
                <p class="tagline">Daily Time Record &amp; Leave Management System</p>
            </div>

            <ul class="features-list" aria-hidden="true">
                <li class="feature-item">
                    <!-- WCAG 1.1.1 — decorative icon -->
                    <span class="feature-icon" aria-hidden="true">✓</span>
                    <span>Easy DTR submission and tracking</span>
                </li>
                <li class="feature-item">
                    <span class="feature-icon" aria-hidden="true">✓</span>
                    <span>Seamless leave request management</span>
                </li>
                <li class="feature-item">
                    <span class="feature-icon" aria-hidden="true">✓</span>
                    <span>Real-time approval workflow</span>
                </li>
                <li class="feature-item">
                    <span class="feature-icon" aria-hidden="true">✓</span>
                    <span>Comprehensive reporting tools</span>
                </li>
            </ul>

            <p class="footer-motto" aria-hidden="true">"Ad Majorem Dei Gloriam"</p>
        </div>

        <!-- Right Side — Login Form -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="form-header">
                    <!-- WCAG 1.3.1 — heading hierarchy -->
                    <h1 class="form-title">Welcome Back</h1>
                    <p class="form-subtitle">Please log in to your account</p>
                </div>

                <!-- WCAG 4.1.3 — error uses role="alert" for immediate announcement -->
                <?php if ($error): ?>
                    <div class="error-message" role="alert" aria-live="assertive">
                        <!-- WCAG 1.4.1 — error not color alone; has border + bold weight -->
                        <span aria-hidden="true">⚠ </span><?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <!-- WCAG 3.3.2 — note about required fields -->
                <p class="sr-only" id="required-note">All fields marked with an asterisk (*) are required.</p>

                <form method="POST" action="login.php" id="login-form"
                      aria-describedby="required-note" novalidate>
                    <div class="form-group">
                        <!-- WCAG 1.3.1 / 3.3.2 — explicit <label> associated by for/id -->
                        <label class="form-label" for="username">
                            Username <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="form-input"
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars($rememberedUser) ?>"
                            autocomplete="username"
                            required
                            aria-required="true"
                            <?= $error ? 'aria-invalid="true"' : '' ?>
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">
                            Password <span class="required" aria-hidden="true">*</span>
                        </label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            aria-required="true"
                            <?= $error ? 'aria-invalid="true"' : '' ?>
                        >
                    </div>

                    <div class="form-options">
                        <!-- WCAG 1.3.5 — autocomplete helps users -->
                        <label class="remember-me">
                            <input type="checkbox" name="remember"
                                   <?= $rememberedUser ? 'checked' : '' ?>>
                            <span>Remember me</span>
                        </label>
                        <!-- WCAG 2.4.4 — link text describes its purpose -->
                        <a href="forgot-password.php" class="forgot-password">
                            Forgot password?
                        </a>
                    </div>

                    <button type="submit" class="login-btn">
                        Log In
                    </button>
                </form>

                <p class="footer-text">
                    "Kapag may alitaptap, tumingin sa mga ulap"
                </p>
            </div>
        </div>
    </div>
</body>
</html>