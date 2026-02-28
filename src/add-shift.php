<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $shiftName   = trim($_POST['shift_name']   ?? '');
    $startTime   = trim($_POST['start_time']   ?? '');
    $endTime     = trim($_POST['end_time']     ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($shiftName)) {
        $errors['shift_name'] = 'Shift name is required.';
    }
    if (empty($startTime)) {
        $errors['start_time'] = 'Start time is required.';
    }
    if (empty($endTime)) {
        $errors['end_time'] = 'End time is required.';
    }
    if (!empty($startTime) && !empty($endTime) && $endTime <= $startTime) {
        $errors['end_time'] = 'End time must be after start time.';
    }

    if (empty($errors)) {
        // INSERT INTO shift_schedules (name, start_time, end_time, description) VALUES (...)
        $message     = 'Shift schedule added successfully!';
        $messageType = 'success';
        header('refresh:2;url=admin-dashboard.php#shifts');
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
    <!-- WCAG 2.4.2 -->
    <title>Add Shift Schedule – ClockWise Admin</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <!-- WCAG 2.4.1 — skip link -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <div class="form-page-container" id="main-content">

        <!-- WCAG 2.4.8 — breadcrumb landmark -->
        <nav aria-label="Breadcrumb">
            <ol class="breadcrumb">
                <li><a href="admin-dashboard.php">Dashboard</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><a href="admin-dashboard.php#shifts">Shift Schedules</a></li>
                <li aria-hidden="true"><span class="breadcrumb-separator">›</span></li>
                <li><span aria-current="page">Add Shift</span></li>
            </ol>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <!-- WCAG 1.3.1 — h1 heading -->
                    <h1 class="form-card-title">
                        <span aria-hidden="true">⏰ </span>Add Shift Schedule
                    </h1>
                    <p class="form-card-subtitle">Define a new work shift schedule</p>
                </div>

                <!-- WCAG 4.1.3 — live region for status -->
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

                <form method="POST" action="add-shift.php"
                      aria-describedby="required-note" novalidate>

                    <fieldset class="form-section">
                        <legend class="form-section-title">Shift Information</legend>

                        <div class="form-group">
                            <label class="form-label" for="shift_name">
                                Shift Name <span class="required" aria-hidden="true">*</span>
                            </label>
                            <input type="text" id="shift_name" name="shift_name"
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['shift_name'] ?? '') ?>"
                                   placeholder="e.g., Morning Shift, Night Shift"
                                   required
                                   aria-required="true"
                                   aria-describedby="shift_name-help<?= isset($errors['shift_name']) ? ' shift_name-error' : '' ?>"
                                   <?= isset($errors['shift_name']) ? 'aria-invalid="true"' : '' ?>>
                            <p id="shift_name-help" class="form-help">Give this shift a descriptive name</p>
                            <?php if (isset($errors['shift_name'])): ?>
                                <p id="shift_name-error" class="field-error" role="alert">
                                    <?= htmlspecialchars($errors['shift_name']) ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="start_time">
                                    Start Time <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="time" id="start_time" name="start_time"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>"
                                       required
                                       aria-required="true"
                                       <?= isset($errors['start_time']) ? 'aria-invalid="true" aria-describedby="start_time-error"' : '' ?>>
                                <?php if (isset($errors['start_time'])): ?>
                                    <p id="start_time-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['start_time']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="end_time">
                                    End Time <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="time" id="end_time" name="end_time"
                                       class="form-input"
                                       value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>"
                                       required
                                       aria-required="true"
                                       <?= isset($errors['end_time']) ? 'aria-invalid="true" aria-describedby="end_time-error"' : '' ?>>
                                <?php if (isset($errors['end_time'])): ?>
                                    <p id="end_time-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['end_time']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="description">Description</label>
                            <textarea id="description" name="description"
                                      class="form-textarea" rows="4"
                                      aria-describedby="description-help"
                                      placeholder="Optional description of this shift"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                            <p id="description-help" class="form-help">
                                Add any relevant notes about this shift schedule
                            </p>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="admin-dashboard.php#shifts" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Shift</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>