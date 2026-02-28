<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message = '';
$messageType = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $deptName    = trim($_POST['dept_name']    ?? '');
    $deptCode    = trim($_POST['dept_code']    ?? '');
    $description = trim($_POST['description'] ?? '');
    $headName    = trim($_POST['head_name']    ?? '');

    if (empty($deptName)) {
        $errors['dept_name'] = 'Department name is required.';
    }

    if (empty($errors)) {
        // INSERT INTO departments (name, code, description, head_name) VALUES (...)
        $message     = 'Department added successfully!';
        $messageType = 'success';
        header('refresh:2;url=admin-dashboard.php#departments');
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- WCAG 2.4.2 — Descriptive page title -->
    <title>Add Department – ClockWise Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <!-- WCAG 2.4.1 — Skip to main content -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="form-page-container" id="main-content">
        <!-- WCAG 2.4.8 — Breadcrumb navigation with landmark + aria-current -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><a href="admin-dashboard.php#departments">Departments</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><span aria-current="page">Add Department</span></li>
            </ol>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <!-- WCAG 1.3.1 — h1 is the main page heading -->
                    <h1 class="form-card-title">
                        <!-- WCAG 1.1.1 — decorative emoji -->
                        <span aria-hidden="true">🏢 </span>Add Department
                    </h1>
                    <p class="form-card-subtitle">Create a new department in your organization</p>
                </div>

                <!-- WCAG 4.1.3 — status message announced live -->
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>"
                         role="<?= $messageType === 'error' ? 'alert' : 'status' ?>"
                         aria-live="<?= $messageType === 'error' ? 'assertive' : 'polite' ?>">
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- WCAG 3.3.2 — required fields instruction -->
                <p id="required-note" class="form-help">
                    Fields marked with <span aria-hidden="true">*</span>
                    <span class="sr-only">an asterisk</span> are required.
                </p>

                <form method="POST" action="add-department.php"
                      aria-describedby="required-note" novalidate>

                    <fieldset class="form-section">
                        <!-- WCAG 1.3.1 — fieldset+legend groups related controls -->
                        <legend class="form-section-title">Department Information</legend>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="dept_name">
                                    Department Name
                                    <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="dept_name" name="dept_name"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST['dept_name'] ?? '') ?>"
                                       placeholder="e.g., Marketing"
                                       required
                                       aria-required="true"
                                       <?= isset($errors['dept_name']) ? 'aria-invalid="true" aria-describedby="dept_name-error"' : '' ?>>
                                <!-- WCAG 3.3.1 — error message linked via aria-describedby -->
                                <?php if (isset($errors['dept_name'])): ?>
                                    <p id="dept_name-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['dept_name']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="dept_code">Department Code</label>
                                <input type="text" id="dept_code" name="dept_code"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST['dept_code'] ?? '') ?>"
                                       placeholder="e.g., MKT"
                                       maxlength="10"
                                       aria-describedby="dept_code-help">
                                <!-- WCAG 3.3.2 — help text associated via aria-describedby -->
                                <p id="dept_code-help" class="form-help">
                                    Optional short code for the department
                                </p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="head_name">Department Head</label>
                            <input type="text" id="head_name" name="head_name"
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['head_name'] ?? '') ?>"
                                   placeholder="Name of department head"
                                   aria-describedby="head_name-help">
                            <p id="head_name-help" class="form-help">
                                Optional — name of the person heading this department
                            </p>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description"
                                      class="form-textarea" rows="4"
                                      placeholder="Brief description of the department's role and responsibilities"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <!-- WCAG 2.4.4 — link text is self-describing -->
                        <a href="admin-dashboard.php#departments" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">Add Department</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>