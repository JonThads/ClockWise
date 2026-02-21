<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin-dashboard.php');
    } else {
        header('Location: user-dashboard.php');
    }
    exit();
}

// Handle login form submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Sample credentials (replace with database check in production)
    $validCredentials = [
        'admin' => ['password' => 'admin123', 'role' => 'admin', 'name' => 'Admin User'],
        'hradmin' => ['password' => 'hr123', 'role' => 'admin', 'name' => 'HR Administrator'],
        'supervisor1' => ['password' => 'super123', 'role' => 'supervisor', 'name' => 'Maria Santos'],
        'jlaguitao' => ['password' => 'user123', 'role' => 'employee', 'name' => 'Jon Thaddeus Laguitao'],
        'employee1' => ['password' => 'emp123', 'role' => 'employee', 'name' => 'Employee One'],
    ];
    
    if (isset($validCredentials[$username]) && $validCredentials[$username]['password'] === $password) {
        // Set session
        $_SESSION['user_id'] = $username;
        $_SESSION['username'] = $username;
        $_SESSION['name'] = $validCredentials[$username]['name'];
        $_SESSION['role'] = $validCredentials[$username]['role'];
        $_SESSION['login_time'] = time();
        
        // Remember me functionality
        if ($remember) {
            setcookie('remembered_user', $username, time() + (86400 * 30), '/'); // 30 days
        }
        
        // Redirect based on role
        if ($validCredentials[$username]['role'] === 'admin') {
            header('Location: admin-dashboard.php');
        } else {
            header('Location: user-dashboard.php');
        }
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}

// Get remembered username from cookie
$rememberedUser = $_COOKIE['remembered_user'] ?? '';
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
                    <p>Test Credentials:<br>
                    Admin: admin/admin123 | Employee: jlaguitao/user123</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
