<?php
require_once 'config/database.php';

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// ── Flash message support ─────────────────────────────────────────────────────
$message     = '';
$messageType = '';

// ── POST handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ── Assign approver ───────────────────────────────────────────────────────
    if ($action === 'assign_approver') {
        // Quick pre-check: if migration hasn't run, bail gracefully
        $migrationCheck = false;
        try {
            $pdo->query("SELECT assignment_id FROM approver_assignments LIMIT 1");
            $migrationCheck = true;
        } catch (PDOException $e) {}

        if (!$migrationCheck) {
            $message = 'Migration not yet applied. Please run approver_assignments_migration.sql first.';
            $messageType = 'error';
        } else {
        $assigneeId  = (int)($_POST['assignee_emp_id']  ?? 0);
        $approverId  = (int)($_POST['approver_emp_id']  ?? 0);

        if (!$assigneeId || !$approverId) {
            $message = 'Invalid employee or approver selection.'; $messageType = 'error';
        } elseif ($assigneeId === $approverId) {
            $message = 'An employee cannot approve their own submissions.'; $messageType = 'error';
        } else {
            // Fetch both employees' hierarchy levels
            $stmtChk = $pdo->prepare("
                SELECT e.emp_id, wg.hierarchy_level, wg.work_group_name
                FROM   employees e
                JOIN   work_groups wg ON wg.work_group_id = e.work_group_id
                WHERE  e.emp_id IN (?, ?)
            ");
            $stmtChk->execute([$assigneeId, $approverId]);
            $rows = $stmtChk->fetchAll(PDO::FETCH_UNIQUE);

            $assigneeLevel  = isset($rows[$assigneeId])  ? (int)$rows[$assigneeId]['hierarchy_level']  : 99;
            $approverLevel  = isset($rows[$approverId])  ? (int)$rows[$approverId]['hierarchy_level']  : 99;
            $assigneeGroup  = $rows[$assigneeId]['work_group_name']  ?? '';
            $approverGroup  = $rows[$approverId]['work_group_name']  ?? '';

            // Rule 1: BOD and Administrative (level 0) are fully exempt — no approver needed
            if ($assigneeLevel === 0) {
                $message = htmlspecialchars($assigneeGroup) . ' employees are auto-approved and do not need an approver.';
                $messageType = 'error';
            }
            // Rule 2: Executive (level 1) are also auto-approved for their own submissions
            elseif ($assigneeLevel === 1) {
                $message = htmlspecialchars($assigneeGroup) . ' employees are auto-approved and do not need an approver.';
                $messageType = 'error';
            }
            // Rule 3: BOD and Administrative (level 0) cannot act as approvers
            elseif ($approverLevel === 0) {
                $message = htmlspecialchars($approverGroup) . ' employees cannot be assigned as approvers.';
                $messageType = 'error';
            }
            // Rule 4: approver must be strictly higher in the chain (lower level number)
            // Executive(1) can approve Managerial(2), Supervisory(3), Rank and File(4)
            elseif ($approverLevel >= $assigneeLevel) {
                $message = htmlspecialchars($approverGroup) . ' employees cannot approve ' . htmlspecialchars($assigneeGroup) . ' submissions. The approver must be from a higher work group.';
                $messageType = 'error';
            } else {
                // Check assignee doesn't already have an approver
                $stmtExist = $pdo->prepare("SELECT assignment_id FROM approver_assignments WHERE assignee_emp_id = ?");
                $stmtExist->execute([$assigneeId]);
                if ($stmtExist->fetch()) {
                    $message = 'This employee already has an assigned approver. Remove the existing assignment first.';
                    $messageType = 'error';
                } else {
                    $pdo->prepare("INSERT INTO approver_assignments (assignee_emp_id, approver_emp_id) VALUES (?, ?)")
                        ->execute([$assigneeId, $approverId]);
                    $message = 'Approver assigned successfully.'; $messageType = 'success';
                }
            }
            } // end hierarchy validation
        } // end $migrationCheck
    }

    // ── Remove approver ───────────────────────────────────────────────────────
    elseif ($action === 'remove_approver') {
        $assignmentId = (int)($_POST['assignment_id'] ?? 0);
        $migrationCheck = false;
        try { $pdo->query("SELECT assignment_id FROM approver_assignments LIMIT 1"); $migrationCheck = true; } catch (PDOException $e) {}
        if ($migrationCheck && $assignmentId) {
            $pdo->prepare("DELETE FROM approver_assignments WHERE assignment_id = ?")
                ->execute([$assignmentId]);
            $message = 'Approver assignment removed.'; $messageType = 'info';
        }
    }

    // ── Admin-level DTR approve/decline ───────────────────────────────────────
    elseif ($action === 'approve_dtr') {
        $dtrId = (int)($_POST['dtr_id'] ?? 0);
        try {
            $pdo->prepare("UPDATE dtr_records SET status='approved' WHERE dtr_id=?")->execute([$dtrId]);
            $message = 'DTR request approved.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Could not update DTR: migration may not be applied yet.'; $messageType = 'error';
        }
    } elseif ($action === 'decline_dtr') {
        $dtrId = (int)($_POST['dtr_id'] ?? 0);
        try {
            $pdo->prepare("UPDATE dtr_records SET status='declined' WHERE dtr_id=?")->execute([$dtrId]);
            $message = 'DTR request declined.'; $messageType = 'info';
        } catch (PDOException $e) {
            $message = 'Could not update DTR: migration may not be applied yet.'; $messageType = 'error';
        }
    }

    // ── Admin-level Leave approve/decline ─────────────────────────────────────
    elseif ($action === 'approve_leave') {
        $leaveId = (int)($_POST['leave_id'] ?? 0);
        try {
            $pdo->prepare("UPDATE leave_records SET status='approved' WHERE leave_rec_id=?")->execute([$leaveId]);
            $message = 'Leave request approved.'; $messageType = 'success';
        } catch (PDOException $e) {
            $message = 'Could not update leave: migration may not be applied yet.'; $messageType = 'error';
        }
    } elseif ($action === 'decline_leave') {
        $leaveId = (int)($_POST['leave_id'] ?? 0);
        try {
            $pdo->prepare("UPDATE leave_records SET status='declined' WHERE leave_rec_id=?")->execute([$leaveId]);
            $message = 'Leave request declined.'; $messageType = 'info';
        } catch (PDOException $e) {
            $message = 'Could not update leave: migration may not be applied yet.'; $messageType = 'error';
        }
    }

    // ── Other standard admin actions ──────────────────────────────────────────
    elseif ($action === 'delete_employee') {
        $empId = (int)($_POST['employee_id'] ?? 0);
        $pdo->prepare("DELETE FROM employees WHERE emp_id = ?")->execute([$empId]);
        $message = 'Employee deleted successfully.'; $messageType = 'success';
    } elseif ($action === 'delete_shift') {
        $shiftId = (int)($_POST['shift_sched_id'] ?? 0);
        $pdo->prepare("DELETE FROM shift_schedules WHERE shift_sched_id = ?")->execute([$shiftId]);
        $message = 'Shift deleted successfully.'; $messageType = 'success';
    } elseif ($action === 'delete_department') {
        $deptId = (int)($_POST['dept_id'] ?? 0);
        $pdo->prepare("DELETE FROM departments WHERE dept_id = ?")->execute([$deptId]);
        $message = 'Department deleted successfully.'; $messageType = 'success';
    }

    // Redirect to prevent re-POST on refresh
    $qs = http_build_query(['msg' => $message, 'mtype' => $messageType, 'section' => $_POST['section'] ?? 'dashboard']);
    header("Location: admin-dashboard.php?$qs");
    exit();
}

// Restore flash message from redirect
if (empty($message) && isset($_GET['msg'])) {
    $message     = $_GET['msg'];
    $messageType = $_GET['mtype'] ?? 'info';
}
$activeSection = $_GET['section'] ?? 'dashboard';

// ── Detect whether migration has been run ─────────────────────────────────────
// Check for hierarchy_level column and approver_assignments table existence.
// This lets the page load safely even before the migration SQL is executed.
$migrationDone = false;
try {
    $pdo->query("SELECT hierarchy_level FROM work_groups LIMIT 1");
    $pdo->query("SELECT assignment_id   FROM approver_assignments LIMIT 1");
    $pdo->query("SELECT dtr_id          FROM dtr_records LIMIT 1");
    $migrationDone = true;
} catch (PDOException $e) {
    // Migration not yet run — page will show a notice and skip new-feature queries
}

// ── Employee list ─────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("
    SELECT e.emp_id, e.emp_first_name, e.emp_last_name, e.emp_email,
           e.emp_username, d.dept_name, wg.work_group_name,
           " . ($migrationDone ? "wg.hierarchy_level" : "99 AS hierarchy_level") . ",
           ss.shift_sched_name, e.created_at
    FROM   employees e
    LEFT JOIN departments     d  ON d.dept_id         = e.dept_id
    LEFT JOIN work_groups     wg ON wg.work_group_id   = e.work_group_id
    LEFT JOIN shift_schedules ss ON ss.shift_sched_id  = e.shift_sched_id
    ORDER  BY e.emp_id ASC
");
$stmt->execute();
$get_employees = $stmt->fetchAll();

// ── Approver Assignments: current state ───────────────────────────────────────
$allAssignments        = [];
$assignedMap           = [];
$alreadyUsedAsApprover = []; // kept for compatibility but no longer used for filtering

if ($migrationDone) {
    $stmtAssign = $pdo->prepare("
        SELECT aa.assignment_id, aa.assignee_emp_id, aa.approver_emp_id,
               CONCAT(ae.emp_first_name,' ',ae.emp_last_name) AS assignee_name,
               CONCAT(ap.emp_first_name,' ',ap.emp_last_name) AS approver_name,
               wga.work_group_name AS assignee_group,
               wgp.work_group_name AS approver_group,
               wga.hierarchy_level AS assignee_level,
               wgp.hierarchy_level AS approver_level
        FROM   approver_assignments aa
        JOIN   employees   ae  ON ae.emp_id         = aa.assignee_emp_id
        JOIN   employees   ap  ON ap.emp_id          = aa.approver_emp_id
        JOIN   work_groups wga ON wga.work_group_id  = ae.work_group_id
        JOIN   work_groups wgp ON wgp.work_group_id  = ap.work_group_id
        ORDER  BY wga.hierarchy_level ASC, assignee_name ASC
    ");
    $stmtAssign->execute();
    $allAssignments = $stmtAssign->fetchAll();

    foreach ($allAssignments as $row) {
        $assignedMap[$row['assignee_emp_id']] = $row;
    }
    // NOTE: we do NOT build an "alreadyUsedAsApprover" exclusion list.
    // An approver CAN supervise multiple subordinates — normal org-chart behaviour.
    // The UNIQUE KEY on approver_assignments.assignee_emp_id already ensures each
    // assignee has at most one approver.
}

// ── DTR requests (all pending, for admin overview) ────────────────────────────
$dtrRequests = [];
if ($migrationDone) {
    $stmtDTR = $pdo->prepare("
        SELECT dr.dtr_id, dr.date, dr.status, dr.submitted_at,
               CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee,
               ss.shift_sched_name AS shift, ss.start_time, ss.end_time
        FROM   dtr_records dr
        JOIN   employees       e  ON e.emp_id           = dr.emp_id
        JOIN   shift_schedules ss ON ss.shift_sched_id   = dr.shift_sched_id
        WHERE  dr.status = 'pending'
        ORDER  BY dr.submitted_at DESC
    ");
    $stmtDTR->execute();
    $dtrRequests = $stmtDTR->fetchAll();
}

// ── Leave requests (all pending, for admin overview) ──────────────────────────
$leaveRequests = [];
$leaveStatusFilter = $migrationDone ? "WHERE lr.status = 'pending'" : "WHERE 1=1";
$stmtLeave = $pdo->prepare("
    SELECT lr.leave_rec_id, lr.date,
           " . ($migrationDone ? "lr.status" : "'pending' AS status") . ",
           lr.submitted_at,
           CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee,
           lt.leave_type_name AS type, lt.leave_type_code
    FROM   leave_records lr
    JOIN   employees   e  ON e.emp_id          = lr.emp_id
    JOIN   leave_types lt ON lt.leave_type_id   = lr.leave_type_id
    $leaveStatusFilter
    ORDER  BY lr.submitted_at DESC
");
$stmtLeave->execute();
$leaveRequests = $stmtLeave->fetchAll();

// ── Leave types ───────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT leave_type_id, leave_type_name, leave_type_code FROM leave_types ORDER BY leave_type_id ASC");
$stmt->execute();
$get_leave_types = $stmt->fetchAll();

// ── Shift schedules ───────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT shift_sched_id, shift_sched_name, start_time, end_time FROM shift_schedules ORDER BY shift_sched_id ASC");
$stmt->execute();
$get_shift_schedules = $stmt->fetchAll();

// ── Departments ───────────────────────────────────────────────────────────────
$stmt = $pdo->prepare("SELECT dept_id, dept_name, dept_desc FROM departments ORDER BY dept_id ASC");
$stmt->execute();
$get_departments = $stmt->fetchAll();

// ── Work groups ───────────────────────────────────────────────────────────────
// NOTE: work_group_leaves uses wgl alias; work_groups (the real table) uses real_wg.
// This avoids the alias collision that caused the previous 500: wg.hierarchy_level
// was being resolved against work_group_leaves which has no such column.
if ($migrationDone) {
    $stmt = $pdo->prepare("
        SELECT real_wg.work_group_id, real_wg.work_group_name, real_wg.hierarchy_level,
               SUM(CASE WHEN lt.leave_type_code = 'VL'    THEN wgl.leave_type_quantity ELSE 0 END) AS VL,
               SUM(CASE WHEN lt.leave_type_code = 'SL'    THEN wgl.leave_type_quantity ELSE 0 END) AS SL,
               SUM(CASE WHEN lt.leave_type_code = 'EL'    THEN wgl.leave_type_quantity ELSE 0 END) AS EL,
               SUM(CASE WHEN lt.leave_type_code = 'BDay'  THEN wgl.leave_type_quantity ELSE 0 END) AS BL,
               SUM(CASE WHEN lt.leave_type_code = 'NoPay' THEN wgl.leave_type_quantity ELSE 0 END) AS NoPay,
               SUM(CASE WHEN lt.leave_type_code = 'EDU'   THEN wgl.leave_type_quantity ELSE 0 END) AS EDU
        FROM   work_group_leaves wgl
        JOIN   leave_types  lt      ON lt.leave_type_id     = wgl.leave_type_id
        JOIN   work_groups  real_wg ON real_wg.work_group_name = wgl.work_group_name
        GROUP  BY real_wg.work_group_id, real_wg.work_group_name, real_wg.hierarchy_level
        ORDER  BY real_wg.hierarchy_level ASC, real_wg.work_group_name ASC
    ");
} else {
    $stmt = $pdo->prepare("
        SELECT 0 AS work_group_id, wgl.work_group_name, 99 AS hierarchy_level,
               SUM(CASE WHEN lt.leave_type_code = 'VL'    THEN wgl.leave_type_quantity ELSE 0 END) AS VL,
               SUM(CASE WHEN lt.leave_type_code = 'SL'    THEN wgl.leave_type_quantity ELSE 0 END) AS SL,
               SUM(CASE WHEN lt.leave_type_code = 'EL'    THEN wgl.leave_type_quantity ELSE 0 END) AS EL,
               SUM(CASE WHEN lt.leave_type_code = 'BDay'  THEN wgl.leave_type_quantity ELSE 0 END) AS BL,
               SUM(CASE WHEN lt.leave_type_code = 'NoPay' THEN wgl.leave_type_quantity ELSE 0 END) AS NoPay,
               SUM(CASE WHEN lt.leave_type_code = 'EDU'   THEN wgl.leave_type_quantity ELSE 0 END) AS EDU
        FROM   work_group_leaves wgl
        JOIN   leave_types lt ON lt.leave_type_id = wgl.leave_type_id
        GROUP  BY wgl.work_group_name
        ORDER  BY wgl.work_group_name ASC
    ");
}
$stmt->execute();
$get_work_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── All work groups with levels (for Approvals Setup filter dropdowns) ────────
$allWorkGroups = [];
if ($migrationDone) {
    $stmtWG = $pdo->query("SELECT work_group_id, work_group_name, hierarchy_level FROM work_groups ORDER BY hierarchy_level ASC");
    $allWorkGroups = $stmtWG->fetchAll();
} else {
    $stmtWG = $pdo->query("SELECT work_group_id, work_group_name FROM work_groups ORDER BY work_group_name ASC");
    foreach ($stmtWG->fetchAll() as $wg) {
        $wg['hierarchy_level'] = 99;
        $allWorkGroups[] = $wg;
    }
}

// Build a emp_id => {level, name, group} map used by the Approvals Setup section
$empHierarchyMap = [];
foreach ($get_employees as $emp) {
    $empHierarchyMap[(int)$emp['emp_id']] = [
        'name'  => $emp['emp_first_name'] . ' ' . $emp['emp_last_name'],
        'level' => isset($emp['hierarchy_level']) ? (int)$emp['hierarchy_level'] : 99,
        'group' => $emp['work_group_name'] ?? '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard – ClockWise</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        /* ── Approvals Setup section styles ── */
        .approvals-table .badge-auto    { background:#d1ecf1; color:#0c5460; border-radius:4px; padding:2px 8px; font-size:.8em; font-weight:600; }
        .approvals-table .badge-assigned{ background:#d4edda; color:#155724; border-radius:4px; padding:2px 8px; font-size:.8em; font-weight:600; }
        .approvals-table .badge-unassigned{ background:#fff3cd; color:#856404; border-radius:4px; padding:2px 8px; font-size:.8em; font-weight:600; }

        .assign-form-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .assign-form-row select { flex:1; min-width:160px; }

        .hierarchy-badge {
            display:inline-block;
            padding:2px 7px;
            border-radius:3px;
            font-size:.78em;
            font-weight:700;
            letter-spacing:.3px;
        }
        .level-0 { background:#cce5ff; color:#004085; }   /* Auto-approved */
        .level-1 { background:#e2d9f3; color:#432874; }   /* Executive      */
        .level-2 { background:#d6f0e0; color:#155724; }   /* Managerial     */
        .level-3 { background:#fff3cd; color:#856404; }   /* Supervisory    */
        .level-4 { background:#f8d7da; color:#721c24; }   /* Rank and File  */

        .section-note {
            background:#f0f4ff;
            border-left:4px solid var(--ateneo-blue,#003087);
            padding:12px 16px;
            border-radius:0 6px 6px 0;
            margin-bottom:20px;
            font-size:.9em;
            line-height:1.5;
        }

        .approvals-filter-bar {
            display:flex;
            gap:10px;
            margin-bottom:16px;
            flex-wrap:wrap;
            align-items:center;
        }
        .approvals-filter-bar input[type="search"] {
            flex:1;
            min-width:180px;
            padding:7px 12px;
            border:1px solid var(--border-color,#ccc);
            border-radius:6px;
            font-size:.9em;
        }
        .approvals-filter-bar select {
            padding:7px 12px;
            border:1px solid var(--border-color,#ccc);
            border-radius:6px;
            font-size:.9em;
        }
    </style>
</head>
<body>

<a href="#main-content" class="skip-link">Skip to main content</a>

<nav class="sidebar" aria-label="Main navigation">
    <div class="logo-section">
        <div class="logo" aria-hidden="true">⏰</div>
        <p class="brand-name">ClockWise</p>
    </div>

    <ul class="sidebar-menu">
        <li class="menu-item">
            <a href="#dashboard" class="menu-link<?= $activeSection==='dashboard'?' active':'' ?>" aria-current="<?= $activeSection==='dashboard'?'page':'false' ?>"
               onclick="showSection('dashboard', this); return false;">
                <span class="menu-icon" aria-hidden="true">📊</span><span>Dashboard</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#employees" class="menu-link<?= $activeSection==='employees'?' active':'' ?>" aria-current="<?= $activeSection==='employees'?'page':'false' ?>"
               onclick="showSection('employees', this); return false;">
                <span class="menu-icon" aria-hidden="true">👥</span><span>Employee Management</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#approvals_setup" class="menu-link<?= $activeSection==='approvals_setup'?' active':'' ?>" aria-current="<?= $activeSection==='approvals_setup'?'page':'false' ?>"
               onclick="showSection('approvals_setup', this); return false;">
                <span class="menu-icon" aria-hidden="true">🔗</span><span>Approvals Setup</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#dtr" class="menu-link<?= $activeSection==='dtr'?' active':'' ?>" aria-current="<?= $activeSection==='dtr'?'page':'false' ?>"
               onclick="showSection('dtr', this); return false;">
                <span class="menu-icon" aria-hidden="true">📋</span><span>DTR Requests</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#leave_requests" class="menu-link<?= $activeSection==='leave_requests'?' active':'' ?>" aria-current="<?= $activeSection==='leave_requests'?'page':'false' ?>"
               onclick="showSection('leave_requests', this); return false;">
                <span class="menu-icon" aria-hidden="true">🏖️</span><span>Leave Requests</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#leave_types" class="menu-link<?= $activeSection==='leave_types'?' active':'' ?>"
               onclick="showSection('leave_types', this); return false;">
                <span class="menu-icon" aria-hidden="true">📝</span><span>Leave Types</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#shifts" class="menu-link<?= $activeSection==='shifts'?' active':'' ?>"
               onclick="showSection('shifts', this); return false;">
                <span class="menu-icon" aria-hidden="true">⏰</span><span>Shift Schedules</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#departments" class="menu-link<?= $activeSection==='departments'?' active':'' ?>"
               onclick="showSection('departments', this); return false;">
                <span class="menu-icon" aria-hidden="true">🏢</span><span>Departments</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#work_groups" class="menu-link<?= $activeSection==='work_groups'?' active':'' ?>"
               onclick="showSection('work_groups', this); return false;">
                <span class="menu-icon" aria-hidden="true">🧱</span><span>Work Groups</span>
            </a>
        </li>
        <li class="menu-item">
            <a href="#reports" class="menu-link<?= $activeSection==='reports'?' active':'' ?>"
               onclick="showSection('reports', this); return false;">
                <span class="menu-icon" aria-hidden="true">📈</span><span>Reports</span>
            </a>
        </li>
    </ul>
</nav>

<main class="main-content" id="main-content">

    <header class="top-bar">
        <h1 class="page-title" id="page-heading">Dashboard Overview</h1>
        <div class="user-info">
            <div class="user-avatar" aria-hidden="true"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                <span class="user-role">Administrator</span>
            </div>
            <a href="logout.php" class="logout-btn">Log out</a>
        </div>
    </header>

    <div class="content-area">

        <?php if ($message): ?>
            <div class="alert alert-<?= htmlspecialchars($messageType) ?>" role="status" aria-live="polite">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ── Dashboard Section ── -->
        <section id="dashboard" class="section<?= $activeSection==='dashboard'?' active':'' ?>" aria-labelledby="dashboard-heading">
            <h2 id="dashboard-heading" class="mb-3">Dashboard Overview</h2>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon" aria-hidden="true">👥</div>
                    <div class="stat-number"><?= count($get_employees) ?></div>
                    <div class="stat-label">Total Employees</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" aria-hidden="true">📋</div>
                    <div class="stat-number"><?= count($dtrRequests) ?></div>
                    <div class="stat-label">Pending DTR</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" aria-hidden="true">🏖️</div>
                    <div class="stat-number"><?= count($leaveRequests) ?></div>
                    <div class="stat-label">Pending Leaves</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon" aria-hidden="true">🔗</div>
                    <div class="stat-number"><?= count($allAssignments) ?></div>
                    <div class="stat-label">Approver Assignments</div>
                </div>
            </div>

            <div class="table-container">
                <table aria-labelledby="recent-activity-caption">
                    <caption id="recent-activity-caption" class="mb-2">Recent Pending Requests</caption>
                    <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Type</th>
                            <th scope="col">Date</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($dtrRequests, 0, 3) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee']) ?></td>
                            <td>DTR – <?= htmlspecialchars($row['shift']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><span class="badge badge-pending">Pending</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php foreach (array_slice($leaveRequests, 0, 3) as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['employee']) ?></td>
                            <td>Leave – <?= htmlspecialchars($row['type']) ?></td>
                            <td><?= htmlspecialchars($row['date']) ?></td>
                            <td><span class="badge badge-pending">Pending</span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (!$dtrRequests && !$leaveRequests): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;">No pending requests</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Employee Management Section ── -->
        <section id="employees" class="section<?= $activeSection==='employees'?' active':'' ?>" aria-labelledby="employees-heading">
            <div class="page-header">
                <h2 id="employees-heading">Employee Management</h2>
                <div class="action-buttons">
                    <a href="add-employee.php" class="btn btn-primary">+ Add Employee</a>
                </div>
            </div>

            <div class="table-container">
                <table aria-labelledby="employees-heading">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Department</th>
                            <th scope="col">Work Group</th>
                            <th scope="col">Shift</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($get_employees as $emp): ?>
                        <tr>
                            <td><?= (int)$emp['emp_id'] ?></td>
                            <td><?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']) ?></td>
                            <td><?= htmlspecialchars($emp['emp_username']) ?></td>
                            <td><?= htmlspecialchars($emp['dept_name']) ?></td>
                            <td><?= htmlspecialchars($emp['work_group_name']) ?></td>
                            <td><?= htmlspecialchars($emp['shift_sched_name']) ?></td>
                            <td>
                                <a href="edit-employee.php?id=<?= (int)$emp['emp_id'] ?>"
                                   class="btn btn-secondary btn-sm"
                                   aria-label="Edit <?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']) ?>">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirmDelete(event, '<?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name'], ENT_QUOTES) ?>')">
                                    <input type="hidden" name="action"      value="delete_employee">
                                    <input type="hidden" name="employee_id" value="<?= (int)$emp['emp_id'] ?>">
                                    <input type="hidden" name="section"     value="employees">
                                    <button type="submit" class="btn btn-danger btn-sm"
                                            aria-label="Delete <?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']) ?>">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ══════════════════════════════════════════════════════════════════ -->
        <!-- ── Approvals Setup Section ── -->
        <!-- ══════════════════════════════════════════════════════════════════ -->
        <section id="approvals_setup" class="section<?= $activeSection==='approvals_setup'?' active':'' ?>" aria-labelledby="approvals-setup-heading">
            <div class="page-header">
                <h2 id="approvals-setup-heading">Approvals Setup</h2>
            </div>

            <?php if (!$migrationDone): ?>
            <!-- ── Migration not yet run ── -->
            <div style="background:#fff3cd;border-left:4px solid #856404;padding:16px 20px;border-radius:0 6px 6px 0;margin-bottom:20px;">
                <strong>⚠️ Database migration required</strong><br>
                The Approvals Setup feature requires schema changes that have not been applied yet.
                Please run <code>approver_assignments_migration.sql</code> against your
                <code>clockwise</code> database, then reload this page.<br><br>
                <strong>What the migration does:</strong>
                <ul style="margin:.5em 0 0 1.2em;line-height:1.7;">
                    <li>Adds <code>hierarchy_level</code> column to <code>work_groups</code></li>
                    <li>Creates the <code>approver_assignments</code> table</li>
                    <li>Creates the <code>dtr_records</code> table</li>
                    <li>Adds <code>status</code> column to <code>leave_records</code></li>
                </ul>
            </div>
            <?php else: ?>

            <div class="section-note" role="note">
                <strong>Hierarchy Rules:</strong> Assign an approver to each employee who needs one.
                The approver must be from a <em>higher</em> work group level than the assignee.<br>
                <strong>Fully Exempt Groups</strong> (auto-approved, cannot be approvers):
                <span class="hierarchy-badge level-0">Board of Directors</span>
                <span class="hierarchy-badge level-0">Administrative</span><br>
                <strong>Auto-Approved + Can Approve Others:</strong>
                <span class="hierarchy-badge level-1">Executive</span>
                — their own submissions are auto-approved, and they can approve employees below them.<br>
                <strong>Hierarchy Chain:</strong>
                <span class="hierarchy-badge level-1">Executive</span> →
                <span class="hierarchy-badge level-2">Managerial</span> →
                <span class="hierarchy-badge level-3">Supervisory</span> →
                <span class="hierarchy-badge level-4">Rank and File</span><br>
                Example: A <em>Supervisory</em> employee can only be assigned an approver from
                <em>Executive</em> or <em>Managerial</em>.
            </div>

            <!-- Filter bar -->
            <div class="approvals-filter-bar">
                <input type="search" id="approvalSearch" placeholder="Search employee name…"
                       aria-label="Search employees in approvals table"
                       oninput="filterApprovalsTable()">
                <select id="approvalGroupFilter" aria-label="Filter by work group" onchange="filterApprovalsTable()">
                    <option value="">All Work Groups</option>
                    <?php foreach ($allWorkGroups as $wg): ?>
                    <option value="<?= htmlspecialchars($wg['work_group_name']) ?>">
                        <?= htmlspecialchars($wg['work_group_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <select id="approvalStatusFilter" aria-label="Filter by assignment status" onchange="filterApprovalsTable()">
                    <option value="">All Statuses</option>
                    <option value="auto">Auto-Approved</option>
                    <option value="assigned">Has Approver</option>
                    <option value="unassigned">Needs Approver</option>
                </select>
            </div>

            <div class="table-container">
                <table id="approvalsTable" class="approvals-table" aria-labelledby="approvals-setup-heading">
                    <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Work Group</th>
                            <th scope="col">Approval Status</th>
                            <th scope="col">Current Approver</th>
                            <th scope="col">Assign Approver</th>
                            <th scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($get_employees as $emp):
                        $empId       = (int)$emp['emp_id'];
                        $empLevel    = (int)$emp['hierarchy_level'];
                        $empFullName = htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']);
                        $empGroup    = htmlspecialchars($emp['work_group_name']);
                        $isAutoApproved = ($empLevel <= 1);  // BOD(0), Administrative(0), Executive(1)
                        $currentAssignment = $assignedMap[$empId] ?? null;

                        // Build the list of valid approvers for this employee:
                        // Must have hierarchy_level < this employee's level (and > 0 means not auto-approved group)
                        // Must NOT already be used as an approver for someone ELSE
                        // Must NOT be the employee themselves
                        $validApprovers = [];
                        foreach ($get_employees as $candidate) {
                            $cId    = (int)$candidate['emp_id'];
                            $cLevel = (int)$candidate['hierarchy_level'];
                            if ($cId === $empId)     continue;  // can't approve self
                            if ($cLevel === 0)        continue;  // BOD/Administrative: fully exempt, cannot be approvers
                            if ($cLevel >= $empLevel) continue;  // must be strictly higher in the chain
                            $validApprovers[] = $candidate;
                        }
                    ?>
                        <tr data-group="<?= $empGroup ?>"
                            data-status="<?= $isAutoApproved ? 'auto' : ($currentAssignment ? 'assigned' : 'unassigned') ?>">
                            <td><?= $empFullName ?></td>
                            <td>
                                <span class="hierarchy-badge level-<?= $empLevel === 0 ? '0' : $empLevel ?>">
                                    <?= $empGroup ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($isAutoApproved): ?>
                                    <span class="badge-auto">Auto-Approved</span>
                                <?php elseif ($currentAssignment): ?>
                                    <span class="badge-assigned">Approver Assigned</span>
                                <?php else: ?>
                                    <span class="badge-unassigned">Needs Approver</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isAutoApproved): ?>
                                    <em style="color:var(--text-muted);">—</em>
                                <?php elseif ($currentAssignment): ?>
                                    <strong><?= htmlspecialchars($currentAssignment['approver_name']) ?></strong>
                                    <br><small style="color:var(--text-muted);"><?= htmlspecialchars($currentAssignment['approver_group']) ?></small>
                                <?php else: ?>
                                    <em style="color:var(--text-muted);">Not assigned</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isAutoApproved): ?>
                                    <em style="color:var(--text-muted);font-size:.85em;">Not required</em>
                                <?php elseif ($currentAssignment): ?>
                                    <em style="color:var(--text-muted);font-size:.85em;">Remove current to reassign</em>
                                <?php else: ?>
                                    <?php if (empty($validApprovers)): ?>
                                        <em style="color:var(--text-muted);font-size:.85em;">No eligible approvers available</em>
                                    <?php else: ?>
                                        <form method="POST" class="assign-form-row">
                                            <input type="hidden" name="action"          value="assign_approver">
                                            <input type="hidden" name="assignee_emp_id" value="<?= $empId ?>">
                                            <input type="hidden" name="section"         value="approvals_setup">
                                            <select name="approver_emp_id" class="form-select"
                                                    aria-label="Select approver for <?= $empFullName ?>" required>
                                                <option value="">Select approver…</option>
                                                <?php foreach ($validApprovers as $ap): ?>
                                                <option value="<?= (int)$ap['emp_id'] ?>">
                                                    <?= htmlspecialchars($ap['emp_first_name'] . ' ' . $ap['emp_last_name']) ?>
                                                    (<?= htmlspecialchars($ap['work_group_name']) ?>)
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn btn-primary btn-sm"
                                                    aria-label="Assign approver to <?= $empFullName ?>">
                                                Assign
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($currentAssignment): ?>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirmDelete(event, 'this approver assignment')">
                                        <input type="hidden" name="action"        value="remove_approver">
                                        <input type="hidden" name="assignment_id" value="<?= (int)$currentAssignment['assignment_id'] ?>">
                                        <input type="hidden" name="section"       value="approvals_setup">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Remove approver assignment for <?= $empFullName ?>">
                                            Remove
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <em style="color:var(--text-muted);font-size:.85em;">—</em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Current assignments summary -->
            <?php if ($allAssignments): ?>
            <h3 id="assignments-summary-heading" class="mt-3 mb-2">Assignment Summary</h3>
            <div class="table-container">
                <table aria-labelledby="assignments-summary-heading">
                    <thead>
                        <tr>
                            <th scope="col">Approver</th>
                            <th scope="col">Approver Work Group</th>
                            <th scope="col">Approves Submissions For</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Group assignments by approver
                        $byApprover = [];
                        foreach ($allAssignments as $a) {
                            $byApprover[$a['approver_emp_id']][] = $a;
                        }
                        foreach ($byApprover as $approverId => $rows):
                            $approverName  = htmlspecialchars($rows[0]['approver_name']);
                            $approverGroup = htmlspecialchars($rows[0]['approver_group']);
                            $assigneeList  = implode(', ', array_map(fn($r) => htmlspecialchars($r['assignee_name']), $rows));
                        ?>
                        <tr>
                            <td><strong><?= $approverName ?></strong></td>
                            <td><?= $approverGroup ?></td>
                            <td><?= $assigneeList ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            <?php endif; // end $migrationDone ?>
        </section>

        <!-- ── DTR Requests Section ── -->
        <section id="dtr" class="section<?= $activeSection==='dtr'?' active':'' ?>" aria-labelledby="dtr-heading">
            <div class="page-header">
                <h2 id="dtr-heading">DTR Approval Requests</h2>
            </div>
            <div class="table-container">
                <table aria-labelledby="dtr-heading">
                    <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Date</th>
                            <th scope="col">Shift</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($dtrRequests): ?>
                            <?php foreach ($dtrRequests as $dtr): ?>
                            <tr>
                                <td><?= htmlspecialchars($dtr['employee']) ?></td>
                                <td><?= htmlspecialchars($dtr['date']) ?></td>
                                <td><?= htmlspecialchars($dtr['shift']) ?> (<?= date('g:i A', strtotime($dtr['start_time'])) ?>–<?= date('g:i A', strtotime($dtr['end_time'])) ?>)</td>
                                <td><?= htmlspecialchars($dtr['submitted_at']) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($dtr['status']) ?>"><?= ucfirst($dtr['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"  value="approve_dtr">
                                        <input type="hidden" name="dtr_id"  value="<?= (int)$dtr['dtr_id'] ?>">
                                        <input type="hidden" name="section" value="dtr">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve DTR for <?= htmlspecialchars($dtr['employee']) ?>">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"  value="decline_dtr">
                                        <input type="hidden" name="dtr_id"  value="<?= (int)$dtr['dtr_id'] ?>">
                                        <input type="hidden" name="section" value="dtr">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline DTR for <?= htmlspecialchars($dtr['employee']) ?>">Decline</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">No pending DTR requests</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Leave Requests Section ── -->
        <section id="leave_requests" class="section<?= $activeSection==='leave_requests'?' active':'' ?>" aria-labelledby="leave-req-heading">
            <div class="page-header">
                <h2 id="leave-req-heading">Leave Approval Requests</h2>
            </div>
            <div class="table-container">
                <table aria-labelledby="leave-req-heading">
                    <thead>
                        <tr>
                            <th scope="col">Employee</th>
                            <th scope="col">Leave Type</th>
                            <th scope="col">Date</th>
                            <th scope="col">Submitted</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($leaveRequests): ?>
                            <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['employee']) ?></td>
                                <td><?= htmlspecialchars($leave['type']) ?> (<?= htmlspecialchars($leave['leave_type_code']) ?>)</td>
                                <td><?= htmlspecialchars($leave['date']) ?></td>
                                <td><?= htmlspecialchars($leave['submitted_at']) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($leave['status']) ?>"><?= ucfirst($leave['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"   value="approve_leave">
                                        <input type="hidden" name="leave_id" value="<?= (int)$leave['leave_rec_id'] ?>">
                                        <input type="hidden" name="section"  value="leave_requests">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve leave for <?= htmlspecialchars($leave['employee']) ?>">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"   value="decline_leave">
                                        <input type="hidden" name="leave_id" value="<?= (int)$leave['leave_rec_id'] ?>">
                                        <input type="hidden" name="section"  value="leave_requests">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline leave for <?= htmlspecialchars($leave['employee']) ?>">Decline</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">No pending leave requests</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Leave Types Section ── -->
        <section id="leave_types" class="section" aria-labelledby="leave-types-heading">
            <div class="page-header">
                <h2 id="leave-types-heading">Leave Types</h2>
            </div>
            <div class="table-container">
                <table aria-labelledby="leave-types-heading">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Leave Type</th>
                            <th scope="col">Code</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($get_leave_types as $lt): ?>
                        <tr>
                            <td><?= (int)$lt['leave_type_id'] ?></td>
                            <td><?= htmlspecialchars($lt['leave_type_name']) ?></td>
                            <td><?= htmlspecialchars($lt['leave_type_code']) ?></td>
                            <td>
                                <a href="edit-leave_type.php?id=<?= (int)$lt['leave_type_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirmDelete(event, '<?= htmlspecialchars($lt['leave_type_name'], ENT_QUOTES) ?> leave type')">
                                    <input type="hidden" name="action"        value="delete_leave_type">
                                    <input type="hidden" name="leave_type_id" value="<?= (int)$lt['leave_type_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Shift Schedules Section ── -->
        <section id="shifts" class="section" aria-labelledby="shifts-heading">
            <div class="page-header">
                <h2 id="shifts-heading">Shift Schedules</h2>
                <div class="action-buttons">
                    <a href="add-shift.php" class="btn btn-primary">+ Add Shift</a>
                </div>
            </div>
            <div class="table-container">
                <table aria-labelledby="shifts-heading">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Shift Name</th>
                            <th scope="col">Start Time</th>
                            <th scope="col">End Time</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($get_shift_schedules as $shift): ?>
                        <tr>
                            <td><?= (int)$shift['shift_sched_id'] ?></td>
                            <td><?= htmlspecialchars($shift['shift_sched_name']) ?></td>
                            <td><?= htmlspecialchars($shift['start_time']) ?></td>
                            <td><?= htmlspecialchars($shift['end_time']) ?></td>
                            <td>
                                <a href="edit-shift.php?id=<?= (int)$shift['shift_sched_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirmDelete(event, '<?= htmlspecialchars($shift['shift_sched_name'], ENT_QUOTES) ?> shift')">
                                    <input type="hidden" name="action"         value="delete_shift">
                                    <input type="hidden" name="shift_sched_id" value="<?= (int)$shift['shift_sched_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Departments Section ── -->
        <section id="departments" class="section" aria-labelledby="departments-heading">
            <div class="page-header">
                <h2 id="departments-heading">Departments</h2>
                <div class="action-buttons">
                    <a href="add-department.php" class="btn btn-primary">+ Add Department</a>
                </div>
            </div>
            <div class="table-container">
                <table aria-labelledby="departments-heading">
                    <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Department Name</th>
                            <th scope="col">Description</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($get_departments as $dept): ?>
                        <tr>
                            <td><?= (int)$dept['dept_id'] ?></td>
                            <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                            <td><?= htmlspecialchars($dept['dept_desc']) ?></td>
                            <td>
                                <a href="edit-department.php?id=<?= (int)$dept['dept_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <form method="POST" style="display:inline;"
                                      onsubmit="return confirmDelete(event, '<?= htmlspecialchars($dept['dept_name'], ENT_QUOTES) ?> department')">
                                    <input type="hidden" name="action"  value="delete_department">
                                    <input type="hidden" name="dept_id" value="<?= (int)$dept['dept_id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Work Groups Section ── -->
        <section id="work_groups" class="section" aria-labelledby="work-groups-heading">
            <div class="page-header">
                <h2 id="work-groups-heading">Work Groups</h2>
            </div>
            <div class="table-container">
                <table aria-labelledby="work-groups-heading">
                    <thead>
                        <tr>
                            <th scope="col">Work Group</th>
                            <th scope="col">Approval</th>
                            <th scope="col"><abbr title="Vacation Leave">VL</abbr></th>
                            <th scope="col"><abbr title="Sick Leave">SL</abbr></th>
                            <th scope="col"><abbr title="Emergency Leave">EL</abbr></th>
                            <th scope="col"><abbr title="Birthday Leave">BL</abbr></th>
                            <th scope="col"><abbr title="Leave Without Pay">NoPay</abbr></th>
                            <th scope="col"><abbr title="Educational Leave">EDU</abbr></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($get_work_groups as $wg): ?>
                        <tr>
                            <td><?= htmlspecialchars($wg['work_group_name']) ?></td>
                            <td>
                                <?php if ((int)$wg['hierarchy_level'] <= 1): ?>
                                    <span class="hierarchy-badge level-0">Auto-Approved</span>
                                <?php else: ?>
                                    <span class="hierarchy-badge level-<?= (int)$wg['hierarchy_level'] ?>">Requires Approval</span>
                                <?php endif; ?>
                            </td>
                            <td><?= (int)$wg['VL'] ?></td>
                            <td><?= (int)$wg['SL'] ?></td>
                            <td><?= (int)$wg['EL'] ?></td>
                            <td><?= (int)$wg['BL'] ?></td>
                            <td><?= (int)$wg['NoPay'] ?></td>
                            <td><?= (int)$wg['EDU'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Reports Section ── -->
        <section id="reports" class="section" aria-labelledby="reports-heading">
            <div class="page-header">
                <h2 id="reports-heading">Reports</h2>
            </div>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-state-icon" aria-hidden="true">📊</div>
                    <p class="empty-state-text">Reports Feature Coming Soon</p>
                    <p class="empty-state-subtext">This will be powered by Python FastAPI</p>
                </div>
            </div>
        </section>

    </div><!-- /.content-area -->
</main>

<script>
// ── Section navigation ────────────────────────────────────────────────────────
var sectionTitles = {
    dashboard:        'Dashboard Overview',
    employees:        'Employee Management',
    approvals_setup:  'Approvals Setup',
    dtr:              'DTR Requests',
    leave_requests:   'Leave Requests',
    leave_types:      'Leave Types',
    shifts:           'Shift Schedules',
    departments:      'Departments',
    work_groups:      'Work Groups',
    reports:          'Reports'
};

function showSection(sectionId, linkEl) {
    document.querySelectorAll('.section').forEach(function(s) { s.classList.remove('active'); });
    var target = document.getElementById(sectionId);
    if (target) {
        target.classList.add('active');
        var heading = target.querySelector('h2');
        if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus(); }
    }
    document.querySelectorAll('.menu-link').forEach(function(link) {
        link.classList.remove('active');
        link.removeAttribute('aria-current');
    });
    if (linkEl) { linkEl.classList.add('active'); linkEl.setAttribute('aria-current', 'page'); }
    var ph = document.getElementById('page-heading');
    if (ph && sectionTitles[sectionId]) ph.textContent = sectionTitles[sectionId];
}

// ── Approvals table filter ────────────────────────────────────────────────────
function filterApprovalsTable() {
    var searchVal = document.getElementById('approvalSearch').value.toLowerCase();
    var groupVal  = document.getElementById('approvalGroupFilter').value;
    var statusVal = document.getElementById('approvalStatusFilter').value;

    document.querySelectorAll('#approvalsTable tbody tr').forEach(function(row) {
        var nameCell  = row.cells[0] ? row.cells[0].textContent.toLowerCase() : '';
        var groupCell = row.getAttribute('data-group') || '';
        var statusCell= row.getAttribute('data-status') || '';

        var matchSearch = !searchVal || nameCell.includes(searchVal);
        var matchGroup  = !groupVal  || groupCell === groupVal;
        var matchStatus = !statusVal || statusCell === statusVal;

        row.style.display = (matchSearch && matchGroup && matchStatus) ? '' : 'none';
    });
}

// ── Confirm destructive actions ───────────────────────────────────────────────
function confirmDelete(event, itemName) {
    var confirmed = window.confirm('Are you sure you want to delete "' + itemName + '"? This action cannot be undone.');
    if (!confirmed) { event.preventDefault(); return false; }
    return true;
}

// ── Hash navigation on load ───────────────────────────────────────────────────
window.addEventListener('load', function() {
    // Priority: GET ?section= param (from redirect after POST), then URL hash
    var fromGet = '<?= htmlspecialchars($activeSection) ?>';
    if (fromGet && fromGet !== 'dashboard') {
        var menuLink = document.querySelector('a[href="#' + fromGet + '"]');
        showSection(fromGet, menuLink);
        return;
    }
    var hash = window.location.hash.replace('#', '');
    if (hash) {
        var menuLink = document.querySelector('a[href="#' + hash + '"]');
        showSection(hash, menuLink);
    }
});
</script>

</body>
</html>