<?php
require_once 'config/database.php';
session_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);


// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Sample data (in production, this would come from database)
$dtrRequests = [
    ['id' => 1, 'employee' => 'John Dela Cruz', 'date' => '2026-02-08', 'shift' => 'Morning (8AM-5PM)', 'submitted' => '2026-02-08 8:05 AM', 'status' => 'pending'],
    ['id' => 2, 'employee' => 'Maria Santos', 'date' => '2026-02-07', 'shift' => 'Morning (8AM-5PM)', 'submitted' => '2026-02-07 8:10 AM', 'status' => 'pending'],
];

$leaveRequests = [
    ['id' => 1, 'employee' => 'Jon Laguitao', 'type' => 'Vacation Leave (VL)', 'date' => '2026-02-12', 'submitted' => '2026-02-07 2:30 PM', 'status' => 'pending'],
];

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_employee':
                $message = 'Employee deleted successfully!';
                $messageType = 'success';
                break;
            case 'approve_dtr':
                $message = 'DTR request approved!';
                $messageType = 'success';
                break;
            case 'decline_dtr':
                $message = 'DTR request declined!';
                $messageType = 'info';
                break;
            case 'approve_leave':
                $message = 'Leave request approved!';
                $messageType = 'success';
                break;
            case 'decline_leave':
                $message = 'Leave request declined!';
                $messageType = 'info';
                break;
            case 'delete_shift':
                $message = 'Shift deleted successfully!';
                $messageType = 'success';
                break;
            case 'delete_department':
                $message = 'Department deleted successfully!';
                $messageType = 'success';
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClockWise - Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-section">
            <div class="logo">⏰</div>
            <h1 class="brand-name">ClockWise</h1>
        </div>

        <ul class="sidebar-menu">
            <li class="menu-item">
                <a href="#dashboard" class="menu-link active" onclick="showSection('dashboard')">
                    <span class="menu-icon">📊</span>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#employees" class="menu-link" onclick="showSection('employees')">
                    <span class="menu-icon">👥</span>
                    <span>Employee Management</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#dtr" class="menu-link" onclick="showSection('dtr')">
                    <span class="menu-icon">📋</span>
                    <span>DTR Requests</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#leave_requests" class="menu-link" onclick="showSection('leave_requests')">
                    <span class="menu-icon">🏖️</span>
                    <span>Leave Requests</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#leave_types" class="menu-link" onclick="showSection('leave_types')">
                    <span class="menu-icon">📝</span>
                    <span>Leave Types</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#shifts" class="menu-link" onclick="showSection('shifts')">
                    <span class="menu-icon">⏰</span>
                    <span>Shift Schedules</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#departments" class="menu-link" onclick="showSection('departments')">
                    <span class="menu-icon">🏢</span>
                    <span>Departments</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#work_groups" class="menu-link" onclick="showSection('work_groups')">
                    <span class="menu-icon">🧱</span>
                    <span>Work Groups</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="#reports" class="menu-link" onclick="showSection('reports')">
                    <span class="menu-icon">📈</span>
                    <span>Reports</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">Admin Dashboard</h1>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($_SESSION['name']) ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>

        <div class="content-area">
            <?php if ($message): ?>
                <div class="alert alert-<?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <div id="dashboard" class="section active">
                <h2 class="mb-3">Dashboard Overview</h2>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-number">48</div>
                        <div class="stat-label">Total Employees</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">📋</div>
                        <div class="stat-number">12</div>
                        <div class="stat-label">Pending DTR</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">🏖️</div>
                        <div class="stat-number">7</div>
                        <div class="stat-label">Pending Leaves</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-number">94%</div>
                        <div class="stat-label">Attendance Rate</div>
                    </div>
                </div>

                <div class="table-container">
                    <h3 class="mb-2">Recent Activity</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Action</th>
                                <th>Date</th>
                                <th>Status</th>
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
            </div>

            <!-- Employee Management Section -->
            <?php
                $sql_get_employees = "
                SELECT
                    e.emp_id,
                    e.emp_first_name,
                    e.emp_last_name,
                    e.emp_email,
                    e.emp_username,
                    d.dept_name,
                    wg.work_group_name,
                    ss.shift_sched_name,
                    e.created_at
                FROM employees e
                LEFT JOIN departments d ON e.dept_id = d.dept_id
                LEFT JOIN work_groups wg ON e.work_group_id = wg.work_group_id
                LEFT JOIN shift_schedules ss ON e.shift_sched_id = ss.shift_sched_id
                ORDER BY e.emp_id ASC
                ";

                $stmt = $pdo->prepare($sql_get_employees);
                $stmt->execute();
                $get_employees = $stmt->fetchAll();
            ?>
            <div id="employees" class="section">
                <div class="page-header">
                    <h2>Employee Management</h2>
                    <div class="action-buttons">
                        <a href="add-employee.php" class="btn btn-primary">+ Add Employee</a>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Department</th>
                                <th>Work Group</th>
                                <th>Shift</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                                /* function generateUsername($firstName, $middleName, $lastName, $birthday) {
                                    // First name initials (handles multiple first names)
                                    $firstInitials = '';
                                    foreach (explode(' ', trim($firstName)) as $name) {
                                        $firstInitials .= strtolower($name[0]);
                                    }

                                    $middleInitial = $middleName ? strtolower($middleName[0]) : 'x';
                                    $lastInitial   = strtolower($lastName[0]);
                                    $birthYear     = date('Y', strtotime($birthday));

                                    return $firstInitials . $middleInitial . $lastInitial . $birthYear;
                                } */
                            ?>
                            <?php foreach ($get_employees as $employees): ?>
                            <tr>
                                <td><?= $employees['emp_id'] ?></td>
                                <td><?= htmlspecialchars($employees['emp_first_name'] . ' ' . $employees['emp_last_name']) ?></td>
                                <td><?= htmlspecialchars($employees['emp_username']) ?></td>
                                <td><?= htmlspecialchars($employees['dept_name']) ?></td>
                                <td><?= htmlspecialchars($employees['work_group_name']) ?></td>
                                <td><?= htmlspecialchars($employees['shift_sched_name']) ?></td>
                                <td>
                                    <a href="edit-employee.php?id=<?= $employees['emp_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this employee?')">
                                        <input type="hidden" name="action" value="delete_employee">
                                        <input type="hidden" name="employee_id" value="<?= $employees['emp_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- DTR Requests Section -->
            <div id="dtr" class="section">
                <div class="page-header">
                    <h2>DTR Approval Requests</h2>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Date</th>
                                <th>Shift</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dtrRequests as $dtr): ?>
                            <tr>
                                <td><?= htmlspecialchars($dtr['employee']) ?></td>
                                <td><?= htmlspecialchars($dtr['date']) ?></td>
                                <td><?= htmlspecialchars($dtr['shift']) ?></td>
                                <td><?= htmlspecialchars($dtr['submitted']) ?></td>
                                <td><span class="badge badge-<?= $dtr['status'] ?>"><?= ucfirst($dtr['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= $dtr['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="decline_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= $dtr['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave Requests Section -->
            <div id="leave_requests" class="section">
                <div class="page-header">
                    <h2>Leave Approval Requests</h2>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Date</th>
                                <th>Submitted</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveRequests as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['employee']) ?></td>
                                <td><?= htmlspecialchars($leave['type']) ?></td>
                                <td><?= htmlspecialchars($leave['date']) ?></td>
                                <td><?= htmlspecialchars($leave['submitted']) ?></td>
                                <td><span class="badge badge-<?= $leave['status'] ?>"><?= ucfirst($leave['status']) ?></span></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="approve_leave">
                                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="decline_leave">
                                        <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Decline</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leave Types Section -->
            <?php
                $sql_get_leave_types = "SELECT
                    leave_type_id,
                    leave_type_name,
                    leave_type_code
                FROM leave_types
                ORDER BY leave_type_id ASC";

                $stmt = $pdo->prepare($sql_get_leave_types);
                $stmt->execute();
                $get_leave_types = $stmt->fetchAll();
            ?>
            <div id="leave_types" class="section">
                <div class="page-header">
                    <h2>Leave Types</h2>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Leave Type</th>
                                <th>Code</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($get_leave_types as $leave_types): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave_types['leave_type_id']) ?></td>
                                <td><?= htmlspecialchars($leave_types['leave_type_name']) ?></td>
                                <td><?= htmlspecialchars($leave_types['leave_type_code']) ?></td>
                                <td>
                                    <a href="edit-leave_type.php?id=<?= $leave_types['leave_type_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this department?')">
                                        <input type="hidden" name="action" value="delete_leave_type">
                                        <input type="hidden" name="leave_type_id" value="<?= $leave_types['leave_type_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Shift Schedules Section -->
            <?php
                $sql_get_shift_schedules = "SELECT
                    shift_sched_id,
                    shift_sched_name,
                    start_time,
                    end_time
                FROM shift_schedules
                ORDER BY shift_sched_id ASC";

                $stmt = $pdo->prepare($sql_get_shift_schedules);
                $stmt->execute();
                $get_shift_schedules = $stmt->fetchAll();
            ?>
            <div id="shifts" class="section">
                <div class="page-header">
                    <h2>Shift Schedules</h2>
                    <div class="action-buttons">
                        <a href="add-shift.php" class="btn btn-primary">+ Add Shift</a>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Shift Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($get_shift_schedules as $shift_sched): ?>
                            <tr>
                                <td><?= $shift_sched['shift_sched_id'] ?></td>
                                <td><?= htmlspecialchars($shift_sched['shift_sched_name']) ?></td>
                                <td><?= htmlspecialchars($shift_sched['start_time']) ?></td>
                                <td><?= htmlspecialchars($shift_sched['end_time']) ?></td>
                                <td>
                                    <a href="edit-shift.php?id=<?= $shift_sched['shift_sched_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this shift?')">
                                        <input type="hidden" name="action" value="delete_shift">
                                        <input type="hidden" name="shift_sched_id" value="<?= $shift_sched['shift_sched_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Departments Section -->
            <?php
                $sql_get_departments = "SELECT
                    dept_id,
                    dept_name,
                    dept_desc
                FROM departments
                ORDER BY dept_id ASC";

                $stmt = $pdo->prepare($sql_get_departments);
                $stmt->execute();
                $get_departments = $stmt->fetchAll();
            ?>
            <div id="departments" class="section">
                <div class="page-header">
                    <h2>Departments</h2>
                    <div class="action-buttons">
                        <a href="add-department.php" class="btn btn-primary">+ Add Department</a>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Department Name</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($get_departments as $dept): ?>
                            <tr>
                                <td><?= $dept['dept_id'] ?></td>
                                <td><?= htmlspecialchars($dept['dept_name']) ?></td>
                                <td><?= htmlspecialchars($dept['dept_desc']) ?></td>
                                <td>
                                    <a href="edit-department.php?id=<?= $dept['dept_id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this department?')">
                                        <input type="hidden" name="action" value="delete_department">
                                        <input type="hidden" name="dept_id" value="<?= $dept['dept_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Work Groups Section -->
            <?php
                $sql_get_work_groups = "
                SELECT
                    wg.work_group_id,
                    wg.work_group_name,

                    SUM(CASE WHEN lt.leave_type_code = 'VL' THEN wg.leave_type_quantity ELSE 0 END) AS VL,
                    SUM(CASE WHEN lt.leave_type_code = 'SL' THEN wg.leave_type_quantity ELSE 0 END) AS SL,
                    SUM(CASE WHEN lt.leave_type_code = 'EL' THEN wg.leave_type_quantity ELSE 0 END) AS EL,
                    SUM(CASE WHEN lt.leave_type_code = 'BDay' THEN wg.leave_type_quantity ELSE 0 END) AS BL,
                    SUM(CASE WHEN lt.leave_type_code = 'NoPay' THEN wg.leave_type_quantity ELSE 0 END) AS NoPay,
                    SUM(CASE WHEN lt.leave_type_code = 'EDU' THEN wg.leave_type_quantity ELSE 0 END) AS EDU

                FROM work_groups wg
                JOIN leave_types lt 
                    ON wg.leave_type_id = lt.leave_type_id

                GROUP BY wg.work_group_name
                ORDER BY wg.work_group_name ASC
                ";

                $stmt = $pdo->prepare($sql_get_work_groups);
                $stmt->execute();
                $get_work_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <div id="work_groups" class="section">
                <div class="page-header">
                    <h2>Work Groups</h2>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Work Group</th>
                                <th>VL</th>
                                <th>SL</th>
                                <th>EL</th>
                                <th>BL</th>
                                <th>NoPay</th>
                                <th>EDU</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($get_work_groups as $work_groups): ?>
                            <tr>
                                <td><?= htmlspecialchars($work_groups['work_group_name']) ?></td>
                                <td><?= (int)$work_groups['VL'] ?></td>
                                <td><?= (int)$work_groups['SL'] ?></td>
                                <td><?= (int)$work_groups['EL'] ?></td>
                                <td><?= (int)$work_groups['BL'] ?></td>
                                <td><?= (int)$work_groups['NoPay'] ?></td>
                                <td><?= (int)$work_groups['EDU'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>

                    </table>
                </div>
            </div>

            <!-- Reports Section -->
            <div id="reports" class="section">
                <div class="page-header">
                    <h2>Reports</h2>
                </div>
                <div class="card">
                    <div class="empty-state">
                        <div class="empty-state-icon">📊</div>
                        <div class="empty-state-text">Reports Feature Coming Soon</div>
                        <div class="empty-state-subtext">This will be powered by Python FastAPI</div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active menu
            document.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
            event.target.closest('.menu-link').classList.add('active');
        }

        // Handle hash navigation on page load
        window.addEventListener('load', function() {
            const hash = window.location.hash.substring(1);
            if (hash) {
                showSection(hash);
                const menuLink = document.querySelector(`a[href="#${hash}"]`);
                if (menuLink) {
                    document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
                    menuLink.classList.add('active');
                }
            }
        });
    </script>
</body>
</html>
