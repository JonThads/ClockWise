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
    $shiftName = $_POST['shift_name'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $description = $_POST['description'] ?? '';
    
    if (empty($shiftName) || empty($startTime) || empty($endTime)) {
        $message = 'Please fill in all required fields';
        $messageType = 'error';
    } else {
        // In production, insert into database here
        // INSERT INTO shifts (name, start_time, end_time, description) VALUES (...)
        
        $message = 'Shift schedule added successfully!';
        $messageType = 'success';
        
        header('refresh:2;url=admin-dashboard.php#shifts');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Shift Schedule - ClockWise</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <div class="form-page-container">
        <div class="breadcrumb">
            <a href="admin-dashboard.php">Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            <a href="admin-dashboard.php#shifts">Shift Schedules</a>
            <span class="breadcrumb-separator">›</span>
            <span>Add Shift</span>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h1 class="form-card-title">⏰ Add Shift Schedule</h1>
                <p class="form-card-subtitle">Define a new work shift schedule</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add-shift.php">
                <div class="form-section">
                    <h3 class="form-section-title">Shift Information</h3>
                    
                    <div class="form-group">
                        <label class="form-label">Shift Name <span class="required">*</span></label>
                        <input type="text" name="shift_name" class="form-input" required
                               value="<?= htmlspecialchars($_POST['shift_name'] ?? '') ?>"
                               placeholder="e.g., Morning Shift, Night Shift">
                        <small class="form-help">Give this shift a descriptive name</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Time <span class="required">*</span></label>
                            <input type="time" name="start_time" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['start_time'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label">End Time <span class="required">*</span></label>
                            <input type="time" name="end_time" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['end_time'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" rows="4"
                                  placeholder="Optional description of this shift"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                        <small class="form-help">Add any relevant notes about this shift schedule</small>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="admin-dashboard.php#shifts" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Shift</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
