<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $firstName  = $_POST['first_name'] ?? '';
    $middleName = $_POST['middle_name'] ?? '';
    $lastName   = $_POST['last_name'] ?? '';
    $birthday = $_POST['birthday'] ?? '';
    $username   = $_POST['username'] ?? '';
    $password   = $_POST['password'] ?? '';
    $email      = $_POST['email'] ?? '';
    $department = $_POST['department'] ?? '';
    $workGroup  = $_POST['work_group'] ?? '';
    $userRole   = $_POST['user_role'] ?? '';
    $shift      = $_POST['shift'] ?? '';

    // Auto-generate username: first initial + middle initial + last name + birth year
    $birthYear = !empty($birthday) ? date('Y', strtotime($birthday)) : '';
    $username = strtolower(substr($firstName, 0, 1) . substr($middleName, 0, 1) . $lastName . $birthYear);

    // Basic validation
    if (empty($firstName) || empty($middleName) || empty($lastName) || empty($birthday) || empty($password)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        try {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // Insert into employees table
            $sql = "INSERT INTO employees (
                    emp_first_name,
                    emp_middle_name,
                    emp_last_name,
                    emp_birthday,
                    emp_email,
                    emp_username,
                    emp_password,
                    dept_id,
                    work_group_id,
                    role_id,
                    shift_sched_id,
                    created_at)
                VALUES 
                    (:first_name,
                    :middle_name,
                    :last_name,
                    :birthday,
                    :email,
                    :username,
                    :password,
                    :department,
                    :work_group,
                    :user_role,
                    :shift,
                    NOW())";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':first_name'  => $firstName,
                ':middle_name' => $middleName,
                ':last_name'   => $lastName,
                ':birthday'    => $birthday,
                ':email'       => $email,
                ':username'    => $username,
                ':password'    => $hashedPassword,
                ':department'  => $department,
                ':work_group'  => $workGroup,
                ':user_role'   => $userRole,
                ':shift'       => $shift
            ]);

            $message = 'Employee Added Successfully! Username: ' . $username;
            $messageType = 'success';

            // Redirect back to dashboard after 2 seconds
            header('refresh:5;url=admin-dashboard.php#employees');
        } catch (PDOException $e) {
            $message = 'Error adding employee: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

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
                            <small class="form-help">Username's are based on the initials of the employee's name</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Middle Name <span class="required">*</span></label>
                            <input type="text" name="middle_name" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
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
                            <label class="form-label">Birthday <span class="required">*</span></label>
                            <input type="date" name="birthday" class="form-input" required
                                value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Password <span class="required">*</span></label>
                            <input type="password" name="password" class="form-input" required
                                   placeholder="Minimum 8 characters">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Work Group <span class="required">*</span></label>
                            <select name="work_group" class="form-select" required>
                                <option value="">Select Work Group</option>
                                <?php
                                    $sql_get_work_groups = "SELECT
                                        work_group_id,
                                        work_group_name
                                    FROM work_groups
                                    ORDER BY work_group_id ASC";

                                    $stmt = $pdo->prepare($sql_get_work_groups);
                                    $stmt->execute();
                                    $get_work_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($get_work_groups as $work_groups): ?>
                                    <option value="<?= $work_groups['work_group_id'] ?>" <?= ($_POST['work_group'] ?? '') == $work_groups['work_group_id'] ? 'selected' : '' ?>>
                                        <?= $work_groups['work_group_name'] ?>
                                    </option>
                                <?php
                                    endforeach;
                                ?>
                            </select>
                            <small class="form-help">Determines the Working Rank of the Employee</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role <span class="required">*</span></label>
                            <select name="user_role" class="form-select" required>
                                <option value="">Select Role</option>
                                <?php
                                    $sql_get_roles = "SELECT
                                        role_id,
                                        role_name
                                    FROM user_roles
                                    ORDER BY role_id ASC";

                                    $stmt = $pdo->prepare($sql_get_roles);
                                    $stmt->execute();
                                    $get_roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($get_roles as $role): ?>
                                    <option value="<?= $role['role_id'] ?>" <?= ($_POST['user_role'] ?? '') == $role['role_id'] ? 'selected' : '' ?>>
                                        <?= $role['role_name'] ?>
                                    </option>
                                <?php
                                    endforeach;
                                ?>
                            </select>
                            <small class="form-help">Determines Access Level and Permissions</small>
                        </div>
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
                            <?php
                                $sql_get_departments = "SELECT
                                    dept_id,
                                    dept_name
                                FROM departments
                                ORDER BY dept_id ASC";

                                $stmt = $pdo->prepare($sql_get_departments);
                                $stmt->execute();
                                $get_departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($get_departments as $dept): ?>
                                <option value="<?= $dept['dept_id'] ?>" <?= ($_POST['department'] ?? '') == $dept['dept_id'] ? 'selected' : '' ?>>
                                    <?= $dept['dept_name'] ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Shift Schedule <span class="required">*</span></label>
                        <select name="shift" class="form-select" required>
                            <option value="">Select Shift</option>
                           <?php
                                $sql_get_shift_schedules = "SELECT
                                    shift_sched_id,
                                    shift_sched_name,
                                    shift_sched_code
                                FROM shift_schedules
                                ORDER BY shift_sched_id ASC";

                                $stmt = $pdo->prepare($sql_get_shift_schedules);
                                $stmt->execute();
                                $get_shift_schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                foreach ($get_shift_schedules as $shift): ?>
                                <option value="<?= $shift['shift_sched_id'] ?>" <?= ($_POST['shift'] ?? '') == $shift['shift_sched_id'] ? 'selected' : '' ?>>
                                    <?= $shift['shift_sched_code'] . " - " . $shift['shift_sched_name'] ?>
                                </option>
                            <?php
                                endforeach;
                            ?>
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