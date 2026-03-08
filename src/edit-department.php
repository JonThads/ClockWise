<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$deptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$deptId) {
    header('Location: admin-dashboard.php?section=departments');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

// ── Fetch existing record ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT dept_id, dept_name, dept_desc FROM departments WHERE dept_id = ?");
$stmt->execute([$deptId]);
$dept = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dept) {
    header('Location: admin-dashboard.php?section=departments&msg=Department+not+found.&mtype=error');
    exit();
}

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['dept_name'] ?? '');
    $desc = trim($_POST['dept_desc'] ?? '');

    if (empty($name)) { $errors['dept_name'] = 'Department name is required.'; }

    if (empty($errors)) {
        // Duplicate name check (excluding self)
        $check = $pdo->prepare("SELECT dept_id FROM departments WHERE dept_name = ? AND dept_id != ?");
        $check->execute([$name, $deptId]);
        if ($check->fetch()) {
            $errors['dept_name'] = 'A department with this name already exists.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE departments SET dept_name = ?, dept_desc = ? WHERE dept_id = ?")
                ->execute([$name, $desc, $deptId]);
            $qs = http_build_query(['msg' => 'Department updated successfully.', 'mtype' => 'success', 'section' => 'departments']);
            header("Location: admin-dashboard.php?$qs");
            exit();
        } catch (PDOException $e) {
            $message     = 'A database error occurred. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
        $dept['dept_name'] = $name;
        $dept['dept_desc'] = $desc;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department – <?= htmlspecialchars($dept['dept_name']) ?> – ClockWise Admin</title>
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
            <a href="admin-dashboard.php?section=departments">Departments</a>
            <span>>>></span>
            <span aria-current="page">Edit Department</span>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <h1 class="form-card-title">
                        <span aria-hidden="true">✏️ </span>Edit Department
                    </h1>
                    <p class="form-card-subtitle">
                        Updating: <strong><?= htmlspecialchars($dept['dept_name']) ?></strong>
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
                      action="edit-department.php?id=<?= $deptId ?>"
                      aria-describedby="required-note" novalidate>

                    <fieldset class="form-section">
                        <legend class="form-section-title">Department Details</legend>

                        <div class="form-group">
                            <label class="form-label" for="dept_name">
                                Department Name <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="dept_name" name="dept_name"
                                   class="form-input"
                                   value="<?= htmlspecialchars($dept['dept_name']) ?>"
                                   placeholder="e.g. Information Technology"
                                   required aria-required="true"
                                   <?= isset($errors['dept_name']) ? 'aria-invalid="true" aria-describedby="dept_name-error"' : '' ?>>
                            <?php if (isset($errors['dept_name'])): ?>
                                <p id="dept_name-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['dept_name']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="dept_desc">Description</label>
                            <textarea id="dept_desc" name="dept_desc"
                                      class="form-input"
                                      rows="4"
                                      placeholder="Optional description of this department's responsibilities"
                                      aria-describedby="dept_desc-help"><?= htmlspecialchars($dept['dept_desc']) ?></textarea>
                            <p id="dept_desc-help" class="form-help">
                                Optional. Briefly describe the department's role or responsibilities.
                            </p>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="admin-dashboard.php?section=departments" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Department</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>