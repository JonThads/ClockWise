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
    $deptName = $_POST['dept_name'] ?? '';
    $deptCode = $_POST['dept_code'] ?? '';
    $description = $_POST['description'] ?? '';
    $headName = $_POST['head_name'] ?? '';
    
    if (empty($deptName)) {
        $message = 'Department name is required';
        $messageType = 'error';
    } else {
        // In production, insert into database here
        // INSERT INTO departments (name, code, description, head_name) VALUES (...)
        
        $message = 'Department added successfully!';
        $messageType = 'success';
        
        header('refresh:2;url=admin-dashboard.php#departments');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Department - ClockWise</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/form.css">
</head>
<body class="form-page">
    <div class="form-page-container">
        <div class="breadcrumb">
            <a href="admin-dashboard.php">Dashboard</a>
            <span class="breadcrumb-separator">›</span>
            <a href="admin-dashboard.php#departments">Departments</a>
            <span class="breadcrumb-separator">›</span>
            <span>Add Department</span>
        </div>

        <div class="form-card">
            <div class="form-card-header">
                <h1 class="form-card-title">🏢 Add Department</h1>
                <p class="form-card-subtitle">Create a new department in your organization</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add-department.php">
                <div class="form-section">
                    <h3 class="form-section-title">Department Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Department Name <span class="required">*</span></label>
                            <input type="text" name="dept_name" class="form-input" required
                                   value="<?= htmlspecialchars($_POST['dept_name'] ?? '') ?>"
                                   placeholder="e.g., Marketing">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department Code</label>
                            <input type="text" name="dept_code" class="form-input"
                                   value="<?= htmlspecialchars($_POST['dept_code'] ?? '') ?>"
                                   placeholder="e.g., MKT"
                                   maxlength="10">
                            <small class="form-help">Optional short code for the department</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Department Head</label>
                        <input type="text" name="head_name" class="form-input"
                               value="<?= htmlspecialchars($_POST['head_name'] ?? '') ?>"
                               placeholder="Name of department head">
                        <small class="form-help">Optional - Name of the person heading this department</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-textarea" rows="4"
                                  placeholder="Brief description of the department's role and responsibilities"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="admin-dashboard.php#departments" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Add Department</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
