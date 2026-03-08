<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$shiftId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$shiftId) {
    header('Location: admin-dashboard.php?section=shifts');
    exit();
}

$message     = '';
$messageType = '';
$errors      = [];

// ── Fetch existing record ──────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT shift_sched_id, shift_sched_name, shift_sched_code, start_time, end_time FROM shift_schedules WHERE shift_sched_id = ?");
$stmt->execute([$shiftId]);
$shift = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shift) {
    header('Location: admin-dashboard.php?section=shifts&msg=Shift+schedule+not+found.&mtype=error');
    exit();
}

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = trim($_POST['shift_sched_name'] ?? '');
    $code      = strtoupper(trim($_POST['shift_sched_code'] ?? ''));
    $startTime = trim($_POST['start_time'] ?? '');
    $endTime   = trim($_POST['end_time']   ?? '');

    if (empty($name))      { $errors['shift_sched_name'] = 'Shift name is required.'; }
    if (empty($code))      { $errors['shift_sched_code'] = 'Shift code is required.'; }
    elseif (!preg_match('/^[A-Za-z0-9_\-]{1,20}$/', $code)) {
        $errors['shift_sched_code'] = 'Code must be 1–20 alphanumeric characters (hyphens/underscores allowed).';
    }
    if (empty($startTime)) { $errors['start_time'] = 'Start time is required.'; }
    if (empty($endTime))   { $errors['end_time']   = 'End time is required.'; }

    if (empty($errors)) {
        // Duplicate code check (excluding self)
        $check = $pdo->prepare("SELECT shift_sched_id FROM shift_schedules WHERE shift_sched_code = ? AND shift_sched_id != ?");
        $check->execute([$code, $shiftId]);
        if ($check->fetch()) {
            $errors['shift_sched_code'] = 'This shift code is already used by another shift schedule.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->prepare("UPDATE shift_schedules SET shift_sched_name = ?, shift_sched_code = ?, start_time = ?, end_time = ? WHERE shift_sched_id = ?")
                ->execute([$name, $code, $startTime, $endTime, $shiftId]);
            $qs = http_build_query(['msg' => 'Shift schedule updated successfully.', 'mtype' => 'success', 'section' => 'shifts']);
            header("Location: admin-dashboard.php?$qs");
            exit();
        } catch (PDOException $e) {
            $message     = 'A database error occurred. Please try again.';
            $messageType = 'error';
        }
    } else {
        $message     = 'Please correct the errors below.';
        $messageType = 'error';
        $shift['shift_sched_name'] = $name;
        $shift['shift_sched_code'] = $code;
        $shift['start_time']       = $startTime;
        $shift['end_time']         = $endTime;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Shift Schedule – <?= htmlspecialchars($shift['shift_sched_name']) ?> – ClockWise Admin</title>
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
            <a href="admin-dashboard.php?section=shifts">Shift Schedules</a>
            <span>>>></span>
            <span aria-current="page">Edit Shift Schedule</span>
        </nav>

        <main>
            <div class="form-card">
                <div class="form-card-header">
                    <h1 class="form-card-title">
                        <span aria-hidden="true">✏️ </span>Edit Shift Schedule
                    </h1>
                    <p class="form-card-subtitle">
                        Updating: <strong><?= htmlspecialchars($shift['shift_sched_name']) ?></strong>
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
                      action="edit-shift-schedule.php?id=<?= $shiftId ?>"
                      aria-describedby="required-note" novalidate>

                    <fieldset class="form-section">
                        <legend class="form-section-title">Shift Details</legend>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="shift_sched_name">
                                    Shift Name <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="shift_sched_name" name="shift_sched_name"
                                       class="form-input"
                                       value="<?= htmlspecialchars($shift['shift_sched_name']) ?>"
                                       placeholder="e.g. Morning Shift"
                                       required aria-required="true"
                                       <?= isset($errors['shift_sched_name']) ? 'aria-invalid="true" aria-describedby="shift_sched_name-error"' : '' ?>>
                                <?php if (isset($errors['shift_sched_name'])): ?>
                                    <p id="shift_sched_name-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['shift_sched_name']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="shift_sched_code">
                                    Shift Code <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="shift_sched_code" name="shift_sched_code"
                                       class="form-input"
                                       value="<?= htmlspecialchars($shift['shift_sched_code']) ?>"
                                       placeholder="e.g. AM"
                                       maxlength="20"
                                       required aria-required="true"
                                       aria-describedby="shift_sched_code-help<?= isset($errors['shift_sched_code']) ? ' shift_sched_code-error' : '' ?>"
                                       <?= isset($errors['shift_sched_code']) ? 'aria-invalid="true"' : '' ?>>
                                <p id="shift_sched_code-help" class="form-help">
                                    Short unique code for this shift (e.g. AM, PM, NS). Max 20 characters.
                                </p>
                                <?php if (isset($errors['shift_sched_code'])): ?>
                                    <p id="shift_sched_code-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['shift_sched_code']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <fieldset class="form-section">
                        <legend class="form-section-title">Shift Hours</legend>

                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="start_time">
                                    Start Time <span class="required" aria-hidden="true">*</span>
                                </label>
                                <input type="time" id="start_time" name="start_time"
                                       class="form-input"
                                       value="<?= htmlspecialchars($shift['start_time']) ?>"
                                       required aria-required="true"
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
                                       value="<?= htmlspecialchars($shift['end_time']) ?>"
                                       required aria-required="true"
                                       <?= isset($errors['end_time']) ? 'aria-invalid="true" aria-describedby="end_time-error"' : '' ?>>
                                <?php if (isset($errors['end_time'])): ?>
                                    <p id="end_time-error" class="field-error" role="alert">
                                        <?= htmlspecialchars($errors['end_time']) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </fieldset>

                    <div class="form-actions">
                        <a href="admin-dashboard.php?section=shifts" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Shift Schedule</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>