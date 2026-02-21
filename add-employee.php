<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName = $_POST['first_name'] ?? '';
    $lastName = $_POST['last_name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $email = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $workGroup = $_POST['work_group'] ?? '';
    $shift = $_POST['shift'] ?? '';
    $role = $_POST['role'] ?? 'employee';
    
    // Basic validation
    if (empty($firstName) || empty($lastName) || empty($username) || empty($password)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        // In production, insert into database here
        // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // INSERT INTO employees ...
        
        $message = 'Employee added successfully!';
        $messageType = 'success';
        
        // Redirect back to admin dashboard after 2 seconds
        header('refresh:2;url=admin-dashboard.php#employees');
    }
}

// Get departments and shifts for dropdowns (in production, fetch from database)
$departments = ['IT', 'HR', 'Engineering', 'Accounting', 'Marketing', 'Operations'];
$workGroups = ['Executive', 'Supervisor', 'Rank and File'];
$shifts = ['Morning Shift', 'Afternoon Shift', 'Night Shift', 'Flexible'];
$roles = ['admin' => 'Administrator', 'executive' => 'Executive', 'supervisor' => 'Supervisor', 'employee' => 'Employee'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Employee - ClockWise</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <div class="form-page-container">
        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="admin-dashboard.php">Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            <a href="admin-dashboard.php#employees">Employees</a>
            <span class="breadcrumb-separator">›</span>
            <span>Add Employee</span>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h1 class="form-card-title">➕ Add New Employee</h1>
                <p class="form-card-subtitle">Fill in the employee information below</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add-employee.php">
                <!-- Personal Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">First Name <span class="required">*</span></label>
                            <input type="text" name="first_name" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name <span class="required">*</span></label>
                            <input type="text" name="last_name" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               placeholder="employee@company.com">
                        <small class="form-help">Optional - for notifications and password reset</small>
                    </div>
                </div>

                <!-- Account Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Account Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Username <span class="required">*</span></label>
                            <input type="text" name="username" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                   placeholder="Must be unique">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password <span class="required">*</span></label>
                            <input type="password" name="password" class="form-input" required
                                   placeholder="Minimum 8 characters">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role" class="form-select" required>
                            <?php foreach ($roles as $value => $label): ?>
                                <option value="<?= $value ?>" <?= ($_POST['role'] ?? 'employee') == $value ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-help">Determines access level and permissions</small>
                    </div>
                </div>

                <!-- Employment Information Section -->
                <div class="form-section">
                    <h3 class="form-section-title">Employment Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Department <span class="required">*</span></label>
                            <select name="department" class="form-select" required>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept ?>" <?= ($_POST['department'] ?? '') == $dept ? 'selected' : '' ?>>
                                        <?= $dept ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Work Group <span class="required">*</span></label>
                            <select name="work_group" class="form-select" required>
                                <option value="">Select Work Group</option>
                                <?php foreach ($workGroups as $group): ?>
                                    <option value="<?= $group ?>" <?= ($_POST['work_group'] ?? '') == $group ? 'selected' : '' ?>>
                                        <?= $group ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Shift Schedule <span class="required">*</span></label>
                        <select name="shift" class="form-select" required>
                            <option value="">Select Shift</option>
                            <?php foreach ($shifts as $shiftOption): ?>
                                <option value="<?= $shiftOption ?>" <?= ($_POST['shift'] ?? '') == $shiftOption ? 'selected' : '' ?>>
                                    <?= $shiftOption ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="admin-dashboard.php#employees" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
