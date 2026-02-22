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
            // Fetch user record (adjust table name if needed: roles vs user_roles)
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
                // Set session
                $_SESSION['user_id']    = $user['emp_id'];
                $_SESSION['username']   = $user['emp_username'];
                $_SESSION['name']       = $user['emp_first_name'];
                $_SESSION['role']       = strtolower($user['role_name']); // e.g. "admin", "user"
                $_SESSION['login_time'] = time();

                // Remember me
                if ($remember) {
                    setcookie('remembered_user', $username, time() + (86400 * 30), '/');
                }

                // Redirect based on role
                if ($_SESSION['role'] === 'admin') {
                    header('Location: admin-dashboard.php');
                } elseif ($_SESSION['role'] === 'user') {
                    header('Location: user-dashboard.php');
                } else {
                    // fallback if role is unknown
                    header('Location: dashboard.php');
                }
                exit();

            } else {
                $error = 'Invalid Username or Password';
            }
        } catch (PDOException $e) {
            $error = 'Database Error: ' . $e->getMessage();
        }
    } else {
        $error = 'Please enter both Username and Password';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClockWise - Login</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <!-- Left Side - Branding -->
        <div class="login-left">
            <div class="logo-section">
                <div class="logo">⏰</div>
                <h1 class="brand-name">ClockWise</h1>
                <p class="tagline">Daily Time Record & Leave Management System</p>
            </div>

            <div class="features-list">
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Easy DTR submission and tracking</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Seamless leave request management</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Real-time approval workflow</span>
                </div>
                <div class="feature-item">
                    <span class="feature-icon">✓</span>
                    <span>Comprehensive reporting tools</span>
                </div>
            </div>

            <div class="footer-motto">
                "Ad Majorem Dei Gloriam"<br>
                For the Greater Glory of God
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="login-right">
            <div class="login-form-container">
                <div class="form-header">
                    <h2 class="form-title">Welcome Back</h2>
                    <p class="form-subtitle">Please login to your account</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label class="form-label" for="username">Username</label>
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="form-input" 
                            placeholder="Enter your username"
                            value="<?= htmlspecialchars($rememberedUser) ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="form-input" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" name="remember" <?= $rememberedUser ? 'checked' : '' ?>>
                            <span>Remember me</span>
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">Login</button>
                </form>

                <div class="footer-text">
                    <p>"Kapag may alitaptap, tumingin sa mga ulap"<br>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
