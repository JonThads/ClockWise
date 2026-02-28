<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

$dtrRequests = [
    ['id' => 1, 'employee' => 'John Dela Cruz', 'date' => '2026-02-08', 'shift' => 'Morning (8AM–5PM)', 'submitted' => '2026-02-08 8:05 AM', 'status' => 'pending'],
    ['id' => 2, 'employee' => 'Maria Santos',   'date' => '2026-02-07', 'shift' => 'Morning (8AM–5PM)', 'submitted' => '2026-02-07 8:10 AM', 'status' => 'pending'],
];
$leaveRequests = [
    ['id' => 1, 'employee' => 'Jon Laguitao', 'type' => 'Vacation Leave (VL)', 'date' => '2026-02-12', 'submitted' => '2026-02-07 2:30 PM', 'status' => 'pending'],
];

$message     = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'delete_employee':   $message = 'Employee deleted successfully!';   $messageType = 'success'; break;
        case 'approve_dtr':       $message = 'DTR request approved!';            $messageType = 'success'; break;
        case 'decline_dtr':       $message = 'DTR request declined.';            $messageType = 'info';    break;
        case 'approve_leave':     $message = 'Leave request approved!';          $messageType = 'success'; break;
        case 'decline_leave':     $message = 'Leave request declined.';          $messageType = 'info';    break;
        case 'delete_shift':      $message = 'Shift deleted successfully!';      $messageType = 'success'; break;
        case 'delete_department': $message = 'Department deleted successfully!'; $messageType = 'success'; break;
    }
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
</head>
<body>

    <!-- WCAG 2.4.1 – Skip navigation -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- WCAG 1.3.1 – Sidebar is a landmark nav -->
    <nav class="sidebar" aria-label="Main navigation">
        <div class="logo-section">
            <div class="logo" aria-hidden="true">⏰</div>
            <p class="brand-name">ClockWise</p>
        </div>

        <!-- WCAG 4.1.2 – aria-current applied by JS when section is active -->
        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="#dashboard" class="menu-link active" aria-current="page"
                   onclick="showSection('dashboard', this); return false;">
                    <span class="menu-icon" aria-hidden="true">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#employees" class="menu-link"
                   onclick="showSection('employees', this); return false;">
                    <span class="menu-icon" aria-hidden="true">👥</span>
                    <span>Employee Management</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#dtr" class="menu-link"
                   onclick="showSection('dtr', this); return false;">
                    <span class="menu-icon" aria-hidden="true">📋</span>
                    <span>DTR Requests</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#leave_requests" class="menu-link"
                   onclick="showSection('leave_requests', this); return false;">
                    <span class="menu-icon" aria-hidden="true">🏖️</span>
                    <span>Leave Requests</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#leave_types" class="menu-link"
                   onclick="showSection('leave_types', this); return false;">
                    <span class="menu-icon" aria-hidden="true">📝</span>
                    <span>Leave Types</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#shifts" class="menu-link"
                   onclick="showSection('shifts', this); return false;">
                    <span class="menu-icon" aria-hidden="true">⏰</span>
                    <span>Shift Schedules</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#departments" class="menu-link"
                   onclick="showSection('departments', this); return false;">
                    <span class="menu-icon" aria-hidden="true">🏢</span>
                    <span>Departments</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#work_groups" class="menu-link"
                   onclick="showSection('work_groups', this); return false;">
                    <span class="menu-icon" aria-hidden="true">🧱</span>
                    <span>Work Groups</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#reports" class="menu-link"
                   onclick="showSection('reports', this); return false;">
                    <span class="menu-icon" aria-hidden="true">📈</span>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- WCAG 1.3.1 – main landmark -->
    <main class="main-content" id="main-content">

        <header class="top-bar">
            <!-- WCAG 2.4.6 – descriptive heading updates via JS -->
            <h1 class="page-title" id="page-heading">Dashboard Overview</h1>
            <div class="user-info">
                <!-- aria-hidden: initials avatar is decorative; name follows -->
                <div class="user-avatar" aria-hidden="true">
                    <?= strtoupper(substr($_SESSION['name'], 0, 1)) ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></span>
                    <span class="user-role">Administrator</span>
                </div>
                <!-- WCAG 2.4.4 – link purpose clear -->
                <a href="logout.php" class="logout-btn">Log out</a>
            </div>
        </header>

        <div class="content-area">

            <?php if ($message): ?>
                <!-- WCAG 4.1.3 – role="status" for non-critical messages -->
                <div
                    class="alert alert-<?= htmlspecialchars($messageType) ?>"
                    role="status"
                    aria-live="polite"
                >
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- ── Dashboard Section ── -->
            <section id="dashboard" class="section active" aria-labelledby="dashboard-heading">
                <h2 id="dashboard-heading" class="mb-3">Dashboard Overview</h2>

                <!-- WCAG 1.3.1 – stats are conveyed as text, not just numbers -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">👥</div>
                        <div class="stat-number" aria-label="48 total employees">48</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">📋</div>
                        <div class="stat-number" aria-label="12 pending DTR requests">12</div>
                        <div class="stat-label">Pending DTR</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">🏖️</div>
                        <div class="stat-number" aria-label="7 pending leave requests">7</div>
                        <div class="stat-label">Pending Leaves</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" aria-hidden="true">✅</div>
                        <div class="stat-number" aria-label="Attendance rate 94 percent">94%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                </div>

                <div class="table-container">
                    <!-- WCAG 1.3.1 – caption describes table purpose -->
                    <table aria-labelledby="recent-activity-caption">
                        <caption id="recent-activity-caption" class="mb-2">Recent Activity</caption>
                        <thead>
                            <tr>
                                <!-- WCAG 1.3.1 – scope="col" on th -->
                                <th scope="col">Employee</th>
                                <th scope="col">Action</th>
                                <th scope="col">Date</th>
                                <th scope="col">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>John Dela Cruz</td>
                                <td>DTR Submission</td>
                                <td>2026-02-08</td>
                                <td><span class="badge badge-pending">Pending</span></td>
                            </tr>
                            <tr>
                                <td>Maria Santos</td>
                                <td>Leave Request (VL)</td>
                                <td>2026-02-07</td>
                                <td><span class="badge badge-approved">Approved</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Employee Management Section ── -->
            <?php
            $sql_get_employees = "
                SELECT e.emp_id, e.emp_first_name, e.emp_last_name, e.emp_email,
                       e.emp_username, d.dept_name, wg.work_group_name,
                       ss.shift_sched_name, e.created_at
                FROM employees e
                LEFT JOIN departments d     ON e.dept_id        = d.dept_id
                LEFT JOIN work_groups wg    ON e.work_group_id  = wg.work_group_id
                LEFT JOIN shift_schedules ss ON e.shift_sched_id = ss.shift_sched_id
                ORDER BY e.emp_id ASC";
            $stmt = $pdo->prepare($sql_get_employees);
            $stmt->execute();
            $get_employees = $stmt->fetchAll();
            ?>
            <section id="employees" class="section" aria-labelledby="employees-heading">
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
                                       aria-label="Edit <?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']) ?>">
                                        Edit
                                    </a>
                                    <!-- WCAG 3.3.4 – confirm dialog prevents accidental deletion -->
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirmDelete(event, '<?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name'], ENT_QUOTES) ?>')">
                                        <input type="hidden" name="action"      value="delete_employee">
                                        <input type="hidden" name="employee_id" value="<?= (int)$emp['emp_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Delete <?= htmlspecialchars($emp['emp_first_name'] . ' ' . $emp['emp_last_name']) ?>">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── DTR Requests Section ── -->
            <section id="dtr" class="section" aria-labelledby="dtr-heading">
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
                            <?php foreach ($dtrRequests as $dtr): ?>
                            <tr>
                                <td><?= htmlspecialchars($dtr['employee']) ?></td>
                                <td><?= htmlspecialchars($dtr['date']) ?></td>
                                <td><?= htmlspecialchars($dtr['shift']) ?></td>
                                <td><?= htmlspecialchars($dtr['submitted']) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($dtr['status']) ?>"><?= ucfirst(htmlspecialchars($dtr['status'])) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="approve_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= (int)$dtr['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve DTR for <?= htmlspecialchars($dtr['employee']) ?> on <?= htmlspecialchars($dtr['date']) ?>">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="decline_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= (int)$dtr['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline DTR for <?= htmlspecialchars($dtr['employee']) ?> on <?= htmlspecialchars($dtr['date']) ?>">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Leave Requests Section ── -->
            <section id="leave_requests" class="section" aria-labelledby="leave-req-heading">
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
                            <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['employee']) ?></td>
                                <td><?= htmlspecialchars($leave['type']) ?></td>
                                <td><?= htmlspecialchars($leave['date']) ?></td>
                                <td><?= htmlspecialchars($leave['submitted']) ?></td>
                                <td><span class="badge badge-<?= htmlspecialchars($leave['status']) ?>"><?= ucfirst(htmlspecialchars($leave['status'])) ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"   value="approve_leave">
                                        <input type="hidden" name="leave_id" value="<?= (int)$leave['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve leave for <?= htmlspecialchars($leave['employee']) ?> on <?= htmlspecialchars($leave['date']) ?>">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"   value="decline_leave">
                                        <input type="hidden" name="leave_id" value="<?= (int)$leave['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline leave for <?= htmlspecialchars($leave['employee']) ?> on <?= htmlspecialchars($leave['date']) ?>">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Leave Types Section ── -->
            <?php
            $stmt = $pdo->prepare("SELECT leave_type_id, leave_type_name, leave_type_code FROM leave_types ORDER BY leave_type_id ASC");
            $stmt->execute();
            $get_leave_types = $stmt->fetchAll();
            ?>
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
                                    <a href="edit-leave_type.php?id=<?= (int)$lt['leave_type_id'] ?>"
                                       class="btn btn-secondary btn-sm"
                                       aria-label="Edit leave type <?= htmlspecialchars($lt['leave_type_name']) ?>">
                                        Edit
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirmDelete(event, '<?= htmlspecialchars($lt['leave_type_name'], ENT_QUOTES) ?> leave type')">
                                        <input type="hidden" name="action"        value="delete_leave_type">
                                        <input type="hidden" name="leave_type_id" value="<?= (int)$lt['leave_type_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Delete leave type <?= htmlspecialchars($lt['leave_type_name']) ?>">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Shift Schedules Section ── -->
            <?php
            $stmt = $pdo->prepare("SELECT shift_sched_id, shift_sched_name, start_time, end_time FROM shift_schedules ORDER BY shift_sched_id ASC");
            $stmt->execute();
            $get_shift_schedules = $stmt->fetchAll();
            ?>
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
                                    <a href="edit-shift.php?id=<?= (int)$shift['shift_sched_id'] ?>"
                                       class="btn btn-secondary btn-sm"
                                       aria-label="Edit shift <?= htmlspecialchars($shift['shift_sched_name']) ?>">
                                        Edit
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirmDelete(event, '<?= htmlspecialchars($shift['shift_sched_name'], ENT_QUOTES) ?> shift')">
                                        <input type="hidden" name="action"       value="delete_shift">
                                        <input type="hidden" name="shift_sched_id" value="<?= (int)$shift['shift_sched_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Delete shift <?= htmlspecialchars($shift['shift_sched_name']) ?>">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Departments Section ── -->
            <?php
            $stmt = $pdo->prepare("SELECT dept_id, dept_name, dept_desc FROM departments ORDER BY dept_id ASC");
            $stmt->execute();
            $get_departments = $stmt->fetchAll();
            ?>
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
                                    <a href="edit-department.php?id=<?= (int)$dept['dept_id'] ?>"
                                       class="btn btn-secondary btn-sm"
                                       aria-label="Edit department <?= htmlspecialchars($dept['dept_name']) ?>">
                                        Edit
                                    </a>
                                    <form method="POST" style="display:inline;"
                                          onsubmit="return confirmDelete(event, '<?= htmlspecialchars($dept['dept_name'], ENT_QUOTES) ?> department')">
                                        <input type="hidden" name="action"  value="delete_department">
                                        <input type="hidden" name="dept_id" value="<?= (int)$dept['dept_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Delete department <?= htmlspecialchars($dept['dept_name']) ?>">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ── Work Groups Section ── -->
            <?php
            $stmt = $pdo->prepare("
                SELECT wg.work_group_id, wg.work_group_name,
                       SUM(CASE WHEN lt.leave_type_code = 'VL'    THEN wg.leave_type_quantity ELSE 0 END) AS VL,
                       SUM(CASE WHEN lt.leave_type_code = 'SL'    THEN wg.leave_type_quantity ELSE 0 END) AS SL,
                       SUM(CASE WHEN lt.leave_type_code = 'EL'    THEN wg.leave_type_quantity ELSE 0 END) AS EL,
                       SUM(CASE WHEN lt.leave_type_code = 'BDay'  THEN wg.leave_type_quantity ELSE 0 END) AS BL,
                       SUM(CASE WHEN lt.leave_type_code = 'NoPay' THEN wg.leave_type_quantity ELSE 0 END) AS NoPay,
                       SUM(CASE WHEN lt.leave_type_code = 'EDU'   THEN wg.leave_type_quantity ELSE 0 END) AS EDU
                FROM work_group_leaves wg
                JOIN leave_types lt ON wg.leave_type_id = lt.leave_type_id
                GROUP BY wg.work_group_name
                ORDER BY wg.work_group_name ASC
            ");
            $stmt->execute();
            $get_work_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <section id="work_groups" class="section" aria-labelledby="work-groups-heading">
                <div class="page-header">
                    <h2 id="work-groups-heading">Work Groups</h2>
                </div>

                <div class="table-container">
                    <!-- WCAG 1.3.1 – abbreviation expanded in header + title attr -->
                    <table aria-labelledby="work-groups-heading">
                        <thead>
                            <tr>
                                <th scope="col">Work Group</th>
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
    /* WCAG 2.4.3 – manage focus when section changes */
    /* WCAG 4.1.2 – update aria-current on menu links  */
    function showSection(sectionId, linkEl) {
        // Hide all sections
        document.querySelectorAll('.section').forEach(function(s) {
            s.classList.remove('active');
        });
        // Show selected section
        var target = document.getElementById(sectionId);
        if (target) {
            target.classList.add('active');
            // Move focus to section heading for screen-reader announcement
            var heading = target.querySelector('h2');
            if (heading) {
                heading.setAttribute('tabindex', '-1');
                heading.focus();
            }
        }
        // Update aria-current on nav links
        document.querySelectorAll('.menu-link').forEach(function(link) {
            link.classList.remove('active');
            link.removeAttribute('aria-current');
        });
        if (linkEl) {
            linkEl.classList.add('active');
            linkEl.setAttribute('aria-current', 'page');
        }
        // Update top-bar heading (WCAG 2.4.6)
        var headings = {
            dashboard: 'Dashboard Overview', employees: 'Employee Management',
            dtr: 'DTR Requests', leave_requests: 'Leave Requests',
            leave_types: 'Leave Types', shifts: 'Shift Schedules',
            departments: 'Departments', work_groups: 'Work Groups', reports: 'Reports'
        };
        var pageHeading = document.getElementById('page-heading');
        if (pageHeading && headings[sectionId]) {
            pageHeading.textContent = headings[sectionId];
        }
    }

    /* WCAG 3.3.4 – confirm destructive actions (non-browser-native approach) */
    function confirmDelete(event, itemName) {
        // Using window.confirm is acceptable for basic implementation;
        // a custom dialog with focus trap would be used for full WCAG 2.4.3 compliance
        var confirmed = window.confirm('Are you sure you want to delete "' + itemName + '"? This action cannot be undone.');
        if (!confirmed) {
            event.preventDefault();
            return false;
        }
        return true;
    }

    // Handle hash navigation on page load
    window.addEventListener('load', function() {
        var hash = window.location.hash.replace('#', '');
        if (hash) {
            var menuLink = document.querySelector('a[href="#' + hash + '"]');
            showSection(hash, menuLink);
        }
    });
    </script>

</body>
</html>