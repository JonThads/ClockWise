<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$employeeId = $_GET['id'] ?? null;
if (!$employeeId) {
    header('Location: admin-dashboard.php#employees');
    exit();
}

// In production, fetch from DB. Sample data used here.
$employee = [
    'id'         => $employeeId,
    'first_name' => 'Jon',
    'last_name'  => 'Laguitao',
    'username'   => 'jlaguitao',
    'email'      => 'jlaguitao@company.com',
    'department' => 'IT',
    'work_group' => 'Rank and File',
    'shift'      => 'Morning Shift',
    'role'       => 'employee',
];

$message     = '';
$messageType = '';
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName  = trim($_POST['first_name']  ?? '');
    $lastName   = trim($_POST['last_name']   ?? '');
    $username   = trim($_POST['username']    ?? '');
    $email      = trim($_POST['email']       ?? '');
    $department = $_POST['department']       ?? '';
    $workGroup  = $_POST['work_group']       ?? '';
    $shift      = $_POST['shift']            ?? '';
    $role       = $_POST['role']             ?? 'employee';
    $newPass    = $_POST['password']         ?? '';

    if (empty($firstName))  { $errors['first_name']  = 'First name is required.'; }
    if (empty($lastName))   { $errors['last_name']   = 'Last name is required.'; }
    if (empty($username))   { $errors['username']    = 'Username is required.'; }
    if (empty($department)) { $errors['department']  = 'Department is required.'; }
    if (empty($workGroup))  { $errors['work_group']  = 'Work group is required.'; }
    if (empty($shift))      { $errors['shift']       = 'Shift schedule is required.'; }
    if (!empty($newPass) && strlen($newPass) < 8) {
        $errors['password'] = 'New password must be at least 8 characters.';
    }

    if (empty($errors)) {
        // UPDATE employees SET ... WHERE emp_id = :id
        $message     = 'Employee updated successfully!';
        $messageType = 'success';
        header('refresh:2;url=admin-dashboard.php#employees');
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
    }
}

$departments = ['IT', 'HR', 'Engineering', 'Accounting', 'Marketing', 'Operations'];
$workGroups  = ['Executive', 'Supervisor', 'Rank and File'];
$shifts      = ['Morning Shift', 'Afternoon Shift', 'Night Shift', 'Flexible'];
$roles       = [
    'admin'      => 'Administrator',
    'executive'  => 'Executive',
    'supervisor' => 'Supervisor',
    'employee'   => 'Employee',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- WCAG 2.4.2 — descriptive title including employee context -->
    <title>Edit Employee – <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?> – ClockWise Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <!-- WCAG 2.4.1 -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="form-page-container" id="main-content">

        <!-- WCAG 2.4.8 — breadcrumb -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><a href="admin-dashboard.php#employees">Employees</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><span aria-current="page">Edit Employee</span></li>
            </ol>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <h1 class="form-card-title">
                        <span aria-hidden="true">✏️ </span>Edit Employee
                    </h1>
                    <p class="form-card-subtitle">
                        Updating:
                        <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                    </p>
                </div>

                <!-- WCAG 4.1.3 — live region -->
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
                      action="edit-employee.php?id=<?= (int)$employeeId ?>"
                      aria-describedby="required-note" novalidate>

                    <!-- ── Personal Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Personal Information</legend>

                        <div class="form-row">
                            <?php foreach (['first_name' => 'First Name', 'last_name' => 'Last Name'] as $f => $lbl): ?>
                            <div class="form-group">
                                <label class="form-label" for="<?= $f ?>">
                                    <?= $lbl ?> <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="<?= $f ?>" name="<?= $f ?>"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST[$f] ?? $employee[$f]) ?>"
                                       required aria-required="true"
                                       <?= isset($errors[$f]) ? "aria-invalid=\"true\" aria-describedby=\"{$f}-error\"" : '' ?>>
                                <?php if (isset($errors[$f])): ?>
                                    <p id="<?= $f ?>-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors[$f]) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email"
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['email'] ?? $employee['email']) ?>"
                                   placeholder="employee@company.com"
                                   autocomplete="email">
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
                                       value="<?= htmlspecialchars($_POST['username'] ?? $employee['username']) ?>"
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
                                    Only fill this if you want to change the password. Minimum 8 characters.
                                </p>
                                <?php if (isset($errors['password'])): ?>
                                    <p id="password-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['password']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="role">
                                Role <span class="required" aria-hidden="true">*</span>
                            </label>
                            <select id="role" name="role" class="form-select"
                                    required aria-required="true">
                                <?php foreach ($roles as $val => $lbl): ?>
                                    <option value="<?= $val ?>"
                                        <?= ($_POST['role'] ?? $employee['role']) === $val ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($lbl) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </fieldset>

                    <!-- ── Employment Information ── -->
                    <fieldset class="form-section">
                        <legend class="form-section-title">Employment Information</legend>

                        <div class="form-row">
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
                                        <option value="<?= $dept ?>"
                                            <?= ($_POST['department'] ?? $employee['department']) === $dept ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept) ?>
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
                                <label class="form-label" for="work_group">
                                    Work Group <span class="required" aria-hidden="true">*</span>
                                </label>
                                <select id="work_group" name="work_group"
                                        class="form-select"
                                        required aria-required="true"
                                        <?= isset($errors['work_group']) ? 'aria-invalid="true" aria-describedby="work_group-error"' : '' ?>>
                                    <option value="">Select Work Group</option>
                                    <?php foreach ($workGroups as $grp): ?>
                                        <option value="<?= $grp ?>"
                                            <?= ($_POST['work_group'] ?? $employee['work_group']) === $grp ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($grp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['work_group'])): ?>
                                    <p id="work_group-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['work_group']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
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
                                <?php foreach ($shifts as $s): ?>
                                    <option value="<?= $s ?>"
                                        <?= ($_POST['shift'] ?? $employee['shift']) === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s) ?>
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
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>