<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$empId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$empId) {
    header('Location: admin-dashboard.php?section=employees');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

// ── Helper: fetch dropdown options safely ─────────────────────────────────────
function getOptions(PDO $pdo, string $sql): array {
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// ── Load dropdowns ────────────────────────────────────────────────────────────
$workGroups     = getOptions($pdo, "SELECT work_group_id, work_group_name FROM work_groups ORDER BY work_group_id");
$roles          = getOptions($pdo, "SELECT role_id, role_name FROM user_roles ORDER BY role_id");
$departments    = getOptions($pdo, "SELECT dept_id, dept_name FROM departments ORDER BY dept_id");
$shiftSchedules = getOptions($pdo, "SELECT shift_sched_id, shift_sched_name, shift_sched_code FROM shift_schedules ORDER BY shift_sched_id");

// ── Fetch existing employee record ────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT emp_id, emp_first_name, emp_middle_name, emp_last_name,
           emp_birthday, emp_email, emp_username,
           dept_id, work_group_id, role_id, shift_sched_id
    FROM   employees
    WHERE  emp_id = ?
");
$stmt->execute([$empId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: admin-dashboard.php?section=employees&msg=Employee+not+found.&mtype=error');
    exit();
}

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name']  ?? '');
    $middleName = trim($_POST['middle_name'] ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $birthday   = trim($_POST['birthday']    ?? '');
    $email      = trim($_POST['email']       ?? '');
    $username   = trim($_POST['username']    ?? '');
    $newPass    = $_POST['password']         ?? '';
    $department = (int)($_POST['department'] ?? 0);
    $workGroup  = (int)($_POST['work_group'] ?? 0);
    $userRole   = (int)($_POST['user_role']  ?? 0);
    $shift      = (int)($_POST['shift']      ?? 0);

    if (empty($firstName))  { $errors['first_name']  = 'First name is required.'; }
    if (empty($middleName)) { $errors['middle_name'] = 'Middle name is required.'; }
    if (empty($lastName))   { $errors['last_name']   = 'Last name is required.'; }
    if (empty($birthday))   { $errors['birthday']    = 'Birthday is required.'; }
    if (empty($username))   { $errors['username']    = 'Username is required.'; }
    if (!empty($newPass) && strlen($newPass) < 8) {
        $errors['password'] = 'New password must be at least 8 characters.';
    }
    if (!$department) { $errors['department'] = 'Department is required.'; }
    if (!$workGroup)  { $errors['work_group'] = 'Work group is required.'; }
    if (!$userRole)   { $errors['user_role']  = 'Role is required.'; }
    if (!$shift)      { $errors['shift']      = 'Shift schedule is required.'; }

    if (empty($errors)) {
        // Duplicate username check (excluding self)
        $check = $pdo->prepare("SELECT emp_id FROM employees WHERE emp_username = ? AND emp_id != ?");
        $check->execute([$username, $empId]);
        if ($check->fetch()) {
            $errors['username'] = 'This username is already taken by another employee.';
        }
    }

    if (empty($errors)) {
        try {
            if (!empty($newPass)) {
                $hashedPassword = password_hash($newPass, PASSWORD_DEFAULT);
                $pdo->prepare("
                    UPDATE employees
                    SET    emp_first_name  = ?,
                           emp_middle_name = ?,
                           emp_last_name   = ?,
                           emp_birthday    = ?,
                           emp_email       = ?,
                           emp_username    = ?,
                           emp_password    = ?,
                           dept_id         = ?,
                           work_group_id   = ?,
                           role_id         = ?,
                           shift_sched_id  = ?
                    WHERE  emp_id = ?
                ")->execute([
                    $firstName, $middleName, $lastName, $birthday,
                    $email, $username, $hashedPassword,
                    $department, $workGroup, $userRole, $shift,
                    $empId
                ]);
            } else {
                $pdo->prepare("
                    UPDATE employees
                    SET    emp_first_name  = ?,
                           emp_middle_name = ?,
                           emp_last_name   = ?,
                           emp_birthday    = ?,
                           emp_email       = ?,
                           emp_username    = ?,
                           dept_id         = ?,
                           work_group_id   = ?,
                           role_id         = ?,
                           shift_sched_id  = ?
                    WHERE  emp_id = ?
                ")->execute([
                    $firstName, $middleName, $lastName, $birthday,
                    $email, $username,
                    $department, $workGroup, $userRole, $shift,
                    $empId
                ]);
            }

            $qs = http_build_query(['msg' => 'Employee updated successfully.', 'mtype' => 'success', 'section' => 'employees']);
            header("Location: admin-dashboard.php?$qs");
            exit();
        } catch (PDOException $e) {
            $message     = 'A database error occurred. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
        // Reflect entered values back
        $employee['emp_first_name']  = $firstName;
        $employee['emp_middle_name'] = $middleName;
        $employee['emp_last_name']   = $lastName;
        $employee['emp_birthday']    = $birthday;
        $employee['emp_email']       = $email;
        $employee['emp_username']    = $username;
        $employee['dept_id']         = $department;
        $employee['work_group_id']   = $workGroup;
        $employee['role_id']         = $userRole;
        $employee['shift_sched_id']  = $shift;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee – <?= htmlspecialchars($employee['emp_first_name'] . ' ' . $employee['emp_last_name']) ?> – ClockWise Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="form-page-container" id="main-content">

        <!-- WCAG 2.4.8 — Breadcrumb navigation with landmark + aria-current -->
        <nav aria-label="Breadcrumb" class="breadcrumb">
            <a href="admin-dashboard.php">Dashboard</a>
            <span>>>></span>
            <a href="admin-dashboard.php?section=employees">Employees</a>
            <span>>>></span>
            <span aria-current="page">Edit Employee</span>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <h1 class="form-card-title">
                        <span aria-hidden="true">✏️ </span>Edit Employee
                    </h1>
                    <p class="form-card-subtitle">
                        Updating: <strong><?= htmlspecialchars($employee['emp_first_name'] . ' ' . $employee['emp_last_name']) ?></strong>
                    </p>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>"
                         role="<?= $messageType === 'error' ? 'alert' : 'status' ?>"
                         aria-live="<?= $messageType === 'error' ? 'assertive' : 'polite' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <p id="required-note" class="form-help">
                    Fields marked with <span aria-hidden="true">*</span>
                    <span class="sr-only">an asterisk</span> are required.
                </p>

                <form method="POST"
                      action="edit-employee.php?id=<?= $empId ?>"
                      aria-describedby="required-note" novalidate>

                    <!-- ── Personal Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Personal Information</legend>

                        <div class="form-row">
                            <?php
                            $nameFields = [
                                'first_name'  => ['First Name',  'emp_first_name'],
                                'middle_name' => ['Middle Name', 'emp_middle_name'],
                                'last_name'   => ['Last Name',   'emp_last_name'],
                            ];
                            foreach ($nameFields as $field => [$label, $dbKey]): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?= $field ?>">
                                    <?= $label ?> <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="<?= $field ?>" name="<?= $field ?>"
                                       class="form-input"
                                       value="<?= htmlspecialchars($employee[$dbKey] ?? '') ?>"
                                       required aria-required="true"
                                       <?= isset($errors[$field]) ? "aria-invalid=\"true\" aria-describedby=\"{$field}-error\"" : '' ?>>
                                <?php if (isset($errors[$field])): ?>
                                    <p id="<?= $field ?>-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors[$field]) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="birthday">
                                    Birthday <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="date" id="birthday" name="birthday"
                                       class="form-input"
                                       value="<?= htmlspecialchars($employee['emp_birthday'] ?? '') ?>"
                                       required aria-required="true"
                                       <?= isset($errors['birthday']) ? 'aria-invalid="true" aria-describedby="birthday-error"' : '' ?>>
                                <?php if (isset($errors['birthday'])): ?>
                                    <p id="birthday-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['birthday']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email Address</label>
                                <input type="email" id="email" name="email"
                                       class="form-input"
                                       value="<?= htmlspecialchars($employee['emp_email'] ?? '') ?>"
                                       placeholder="employee@company.com"
                                       autocomplete="email"
                                       aria-describedby="email-help">
                                <p id="email-help" class="form-help">Optional — for notifications and password reset.</p>
                            </div>
                        </div>
                    </fieldset>

                    <!-- ── Account Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Account Information</legend>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="username">
                                    Username <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="username" name="username"
                                       class="form-input"
                                       value="<?= htmlspecialchars($employee['emp_username'] ?? '') ?>"
                                       autocomplete="username"
                                       required aria-required="true"
                                       <?= isset($errors['username']) ? 'aria-invalid="true" aria-describedby="username-error"' : '' ?>>
                                <?php if (isset($errors['username'])): ?>
                                    <p id="username-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['username']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">New Password</label>
                                <input type="password" id="password" name="password"
                                       class="form-input"
                                       placeholder="Leave blank to keep current password"
                                       autocomplete="new-password"
                                       aria-describedby="password-help<?= isset($errors['password']) ? ' password-error' : '' ?>"
                                       <?= isset($errors['password']) ? 'aria-invalid="true"' : '' ?>>
                                <p id="password-help" class="form-help">
                                    Only fill this in to change the password. Minimum 8 characters.
                                </p>
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
                                            <?= $employee['work_group_id'] == $wg['work_group_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($wg['work_group_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="work_group-help" class="form-help">Determines the working rank of the employee.</p>
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
                                            <?= $employee['role_id'] == $role['role_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($role['role_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p id="user_role-help" class="form-help">Determines access level and permissions.</p>
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
                                        <?= $employee['dept_id'] == $dept['dept_id'] ? 'selected' : '' ?>>
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
                                        <?= $employee['shift_sched_id'] == $s['shift_sched_id'] ? 'selected' : '' ?>>
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
                        <a href="admin-dashboard.php?section=employees" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>