<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$leaveTypeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$leaveTypeId) {
    header('Location: admin-dashboard.php?section=leave_types');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

// ── Fetch existing record ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT leave_type_id, leave_type_name, leave_type_code FROM leave_types WHERE leave_type_id = ?");
$stmt->execute([$leaveTypeId]);
$leaveType = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$leaveType) {
    header('Location: admin-dashboard.php?section=leave_types&msg=Leave+type+not+found.&mtype=error');
    exit();
}

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['leave_type_name'] ?? '');
    $code = strtoupper(trim($_POST['leave_type_code'] ?? ''));

    if (empty($name)) { $errors['leave_type_name'] = 'Leave type name is required.'; }
    if (empty($code)) { $errors['leave_type_code'] = 'Leave type code is required.'; }
    elseif (!preg_match('/^[A-Za-z0-9_\-]{1,20}$/', $code)) {
        $errors['leave_type_code'] = 'Code must be 1–20 alphanumeric characters (hyphens/underscores allowed).';
    }

    if (empty($errors)) {
        // Check for duplicate code (excluding self)
        $check = $pdo->prepare("SELECT leave_type_id FROM leave_types WHERE leave_type_code = ? AND leave_type_id != ?");
        $check->execute([$code, $leaveTypeId]);
        if ($check->fetch()) {
            $errors['leave_type_code'] = 'This leave type code is already in use by another leave type.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE leave_types SET leave_type_name = ?, leave_type_code = ? WHERE leave_type_id = ?")
                ->execute([$name, $code, $leaveTypeId]);
            $qs = http_build_query(['msg' => 'Leave type updated successfully.', 'mtype' => 'success', 'section' => 'leave_types']);
            header("Location: admin-dashboard.php?$qs");
            exit();
        } catch (PDOException $e) {
            $message     = 'A database error occurred. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
        // Keep entered values for re-render
        $leaveType['leave_type_name'] = $name;
        $leaveType['leave_type_code'] = $code;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Leave Type – <?= htmlspecialchars($leaveType['leave_type_name']) ?> – ClockWise Admin</title>
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
            <a href="admin-dashboard.php?section=leave_types">Leave Types</a>
            <span>>>></span>
            <span aria-current="page">Edit Leave Type</span>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <h1 class="form-card-title">
                        <span aria-hidden="true">✏️ </span>Edit Leave Type
                    </h1>
                    <p class="form-card-subtitle">
                        Updating: <strong><?= htmlspecialchars($leaveType['leave_type_name']) ?></strong>
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
                      action="edit-leave-type.php?id=<?= $leaveTypeId ?>"
                      aria-describedby="required-note" novalidate>

                    <fieldset class="form-section">
                        <legend class="form-section-title">Leave Type Details</legend>

                        <div class="form-group">
                            <label class="form-label" for="leave_type_name">
                                Leave Type Name <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="leave_type_name" name="leave_type_name"
                                   class="form-input"
                                   value="<?= htmlspecialchars($leaveType['leave_type_name']) ?>"
                                   placeholder="e.g. Vacation Leave"
                                   required aria-required="true"
                                   <?= isset($errors['leave_type_name']) ? 'aria-invalid="true" aria-describedby="leave_type_name-error"' : '' ?>>
                            <?php if (isset($errors['leave_type_name'])): ?>
                                <p id="leave_type_name-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['leave_type_name']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="leave_type_code">
                                Leave Type Code <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="leave_type_code" name="leave_type_code"
                                   class="form-input"
                                   value="<?= htmlspecialchars($leaveType['leave_type_code']) ?>"
                                   placeholder="e.g. VL"
                                   maxlength="20"
                                   required aria-required="true"
                                   aria-describedby="leave_type_code-help<?= isset($errors['leave_type_code']) ? ' leave_type_code-error' : '' ?>"
                                   <?= isset($errors['leave_type_code']) ? 'aria-invalid="true"' : '' ?>>
                            <p id="leave_type_code-help" class="form-help">
                                Short alphanumeric code used in records (e.g. VL, SL, EL). Max 20 characters.
                            </p>
                            <?php if (isset($errors['leave_type_code'])): ?>
                                <p id="leave_type_code-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['leave_type_code']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="admin-dashboard.php?section=leave_types" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Leave Type</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>