<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name']  ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $birthday   = trim($_POST['birthday']    ?? '');
    $password   = $_POST['password']         ?? '';
    $email      = trim($_POST['email']       ?? '');
    $department = $_POST['department']       ?? '';
    $workGroup  = $_POST['work_group']       ?? '';
    $userRole   = $_POST['user_role']        ?? '';
    $shift      = $_POST['shift']            ?? '';

    // Validate
    if (empty($firstName))  { $errors['first_name']  = 'First name is required.'; }
    if (empty($middleName)) { $errors['middle_name'] = 'Middle name is required.'; }
    if (empty($lastName))   { $errors['last_name']   = 'Last name is required.'; }
    if (empty($birthday))   { $errors['birthday']    = 'Birthday is required.'; }
    if (empty($password))   { $errors['password']    = 'Password is required.'; }
    if (strlen($password) < 8 && !empty($password)) {
        $errors['password'] = 'Password must be at least 8 characters.';
    }
    if (empty($department)) { $errors['department']  = 'Department is required.'; }
    if (empty($workGroup))  { $errors['work_group']  = 'Work group is required.'; }
    if (empty($userRole))   { $errors['user_role']   = 'Role is required.'; }
    if (empty($shift))      { $errors['shift']       = 'Shift schedule is required.'; }

    if (empty($errors)) {
        $birthYear = date('Y', strtotime($birthday));
        $username  = strtolower(
            substr($firstName, 0, 1) .
            substr($middleName, 0, 1) .
            $lastName .
            $birthYear
        );

        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO employees (
                        emp_first_name, emp_middle_name, emp_last_name,
                        emp_birthday, emp_email, emp_username, emp_password,
                        dept_id, work_group_id, role_id, shift_sched_id, created_at
                    ) VALUES (
                        :first_name, :middle_name, :last_name,
                        :birthday, :email, :username, :password,
                        :department, :work_group, :user_role, :shift, NOW()
                    )";
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
                ':shift'       => $shift,
            ]);

            $message     = 'Employee added successfully! Auto-generated username: ' . htmlspecialchars($username);
            $messageType = 'success';
            header('refresh:5;url=admin-dashboard.php#employees');
        } catch (PDOException $e) {
            $message     = 'A database error occurred. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Please correct the errors below before submitting.';
        $messageType = 'error';
    }
}

// Helper: fetch dropdown options safely
function getOptions(PDO $pdo, string $sql): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

$workGroups       = getOptions($pdo, "SELECT work_group_id, work_group_name FROM work_groups ORDER BY work_group_id");
$roles            = getOptions($pdo, "SELECT role_id, role_name FROM user_roles ORDER BY role_id");
$departments      = getOptions($pdo, "SELECT dept_id, dept_name FROM departments ORDER BY dept_id");
$shiftSchedules   = getOptions($pdo, "SELECT shift_sched_id, shift_sched_name, shift_sched_code FROM shift_schedules ORDER BY shift_sched_id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- WCAG 2.4.2 -->
    <title>Add Employee – ClockWise Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <!-- WCAG 2.4.1 -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="form-page-container" id="main-content">

        <!-- WCAG 2.4.8 — breadcrumb landmark -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><a href="admin-dashboard.php#employees">Employees</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><span aria-current="page">Add Employee</span></li>
            </ol>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <!-- WCAG 1.3.1 — page-level h1 -->
                    <h1 class="form-card-title">
                        <span aria-hidden="true">➕ </span>Add New Employee
                    </h1>
                    <p class="form-card-subtitle">Fill in the employee information below</p>
                </div>

                <!-- WCAG 4.1.3 — live region -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>"
                         role="<?= $messageType === 'error' ? 'alert' : 'status' ?>"
                         aria-live="<?= $messageType === 'error' ? 'assertive' : 'polite' ?>">
                        <?= $message /* already escaped or safe */ ?>
                    </div>
                <?php endif; ?>

                <!-- WCAG 3.3.2 — explain required convention -->
                <p id="required-note" class="form-help">
                    Fields marked with <span aria-hidden="true">*</span>
                    <span class="sr-only">an asterisk</span> are required.
                </p>

                <form method="POST" action="add-employee.php"
                      aria-describedby="required-note" novalidate>

                    <!-- ── Personal Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Personal Information</legend>

                        <div class="form-row">
                            <?php
                            $nameFields = [
                                'first_name'  => ['First Name',  'Username is generated from name initials + birth year'],
                                'middle_name' => ['Middle Name', null],
                                'last_name'   => ['Last Name',   null],
                            ];
                            foreach ($nameFields as $field => [$label, $help]): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?= $field ?>">
                                    <?= $label ?> <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="<?= $field ?>" name="<?= $field ?>"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST[$field] ?? '') ?>"
                                       required aria-required="true"
                                       <?= $help ? "aria-describedby=\"{$field}-help\"" : '' ?>
                                       <?= isset($errors[$field]) ? "aria-invalid=\"true\" aria-describedby=\"{$field}-error\"" : '' ?>>
                                <?php if ($help): ?>
                                    <p id="<?= $field ?>-help" class="form-help"><?= $help ?></p>
                                <?php endif; ?>
                                <?php if (isset($errors[$field])): ?>
                                    <p id="<?= $field ?>-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors[$field]) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email"
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                   placeholder="employee@company.com"
                                   autocomplete="email"
                                   aria-describedby="email-help">
                            <p id="email-help" class="form-help">Optional — for notifications and password reset</p>
                        </div>
                    </fieldset>

                    <!-- ── Account Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Account Information</legend>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="birthday">
                                    Birthday <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="date" id="birthday" name="birthday"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST['birthday'] ?? '') ?>"
                                       required aria-required="true"
                                       <?= isset($errors['birthday']) ? 'aria-invalid="true" aria-describedby="birthday-error"' : '' ?>>
                                <?php if (isset($errors['birthday'])): ?>
                                    <p id="birthday-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['birthday']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">
                                    Password <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="password" id="password" name="password"
                                       class="form-input"
                                       placeholder="Minimum 8 characters"
                                       autocomplete="new-password"
                                       required aria-required="true"
                                       aria-describedby="password-help<?= isset($errors['password']) ? ' password-error' : '' ?>"
                                       <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                                <p id="password-help" class="form-help">Minimum 8 characters</p>
                                <?php if (isset($errors['password'])): ?>
                                    <p id="password-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['password']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-row">
                            <!-- Work Group -->
                            <div class="form-group">
                                <label class="form-label" for="work_group">
                                    Work Group <span class="required" aria-hidden="true">*</span>
                                </label>
                                <select id="work_group" name="work_group"
                                        class="form-select"
                                        required aria-required="true"
                                        aria-describedby="work_group-help<?= isset($errors['work_group']) ? ' work_group-error' : '' ?>"
                                        <?= isset($errors['work_group']) ? 'aria-invalid="true"' : '' ?>>
                                    <option value="">Select Work Group</option>
                                    <?php foreach ($workGroups as $wg): ?>
                                        <option value="<?= $wg['work_group_id'] ?>"
                                            <?= ($_POST['work_group'] ?? '') == $wg['work_group_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($wg['work_group_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="work_group-help" class="form-help">Determines the working rank of the employee</p>
                                <?php if (isset($errors['work_group'])): ?>
                                    <p id="work_group-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['work_group']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <!-- Role -->
                            <div class="form-group">
                                <label class="form-label" for="user_role">
                                    Role <span class="required" aria-hidden="true">*</span>
                                </label>
                                <select id="user_role" name="user_role"
                                        class="form-select"
                                        required aria-required="true"
                                        aria-describedby="user_role-help<?= isset($errors['user_role']) ? ' user_role-error' : '' ?>"
                                        <?= isset($errors['user_role']) ? 'aria-invalid="true"' : '' ?>>
                                    <option value="">Select Role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['role_id'] ?>"
                                            <?= ($_POST['user_role'] ?? '') == $role['role_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="user_role-help" class="form-help">Determines access level and permissions</p>
                                <?php if (isset($errors['user_role'])): ?>
                                    <p id="user_role-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['user_role']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <!-- ── Employment Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Employment Information</legend>

                        <div class="form-group">
                            <label class="form-label" for="department">
                                Department <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="department" name="department"
                                    class="form-select"
                                    required aria-required="true"
                                    <?= isset($errors['department']) ? 'aria-invalid="true" aria-describedby="department-error"' : '' ?>>
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['dept_id'] ?>"
                                        <?= ($_POST['department'] ?? '') == $dept['dept_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['dept_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['department'])): ?>
                                <p id="department-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['department']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="shift">
                                Shift Schedule <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="shift" name="shift"
                                    class="form-select"
                                    required aria-required="true"
                                    <?= isset($errors['shift']) ? 'aria-invalid="true" aria-describedby="shift-error"' : '' ?>>
                                <option value="">Select Shift</option>
                                <?php foreach ($shiftSchedules as $s): ?>
                                    <option value="<?= $s['shift_sched_id'] ?>"
                                        <?= ($_POST['shift'] ?? '') == $s['shift_sched_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['shift_sched_code'] . ' – ' . $s['shift_sched_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['shift'])): ?>
                                <p id="shift-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['shift']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="admin-dashboard.php#employees" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>