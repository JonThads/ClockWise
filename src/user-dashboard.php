<?php
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Sample DTR and leave data with codes
$dtrRecords = [
    '2026-02-01' => ['status' => 'approved', 'shift' => 'Morning'],
    '2026-02-03' => ['status' => 'approved', 'shift' => 'Morning'],
    '2026-02-04' => ['status' => 'approved', 'shift' => 'Afternoon'],
    '2026-02-05' => ['status' => 'approved', 'shift' => 'Night'],
    '2026-02-06' => ['status' => 'approved', 'shift' => 'Flexible'],
    '2026-02-07' => ['status' => 'pending', 'shift' => 'Morning'],
    '2026-02-08' => ['status' => 'declined', 'shift' => 'Morning'],
];

$leaveRecords = [
    '2026-02-10' => ['type' => 'VL', 'status' => 'pending'],
    '2026-02-11' => ['type' => 'SL', 'status' => 'approved'],
    '2026-02-12' => ['type' => 'EL', 'status' => 'approved'],
    '2026-02-13' => ['type' => 'BL', 'status' => 'pending'],
    '2026-02-14' => ['type' => 'STU', 'status' => 'approved'],
    '2026-02-17' => ['type' => 'NP', 'status' => 'declined'],
];

$leaveBalances = [
    'VL' => 5,
    'SL' => 7,
    'EL' => 3,
    'BL' => 1,
    'STU' => 2,
    'NP' => 999
];

// Sample approval requests (for supervisors)
$pendingDTRApprovals = [];
$pendingLeaveApprovals = [];

if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'executive') {
    $pendingDTRApprovals = [
        ['id' => 1, 'employee' => 'John Dela Cruz', 'date' => '2026-02-08', 'shift' => 'Morning (8AM-5PM)', 'submitted' => '2026-02-08 8:05 AM', 'status' => 'pending'],
    ];
    
    $pendingLeaveApprovals = [
        ['id' => 1, 'employee' => 'Maria Santos', 'type' => 'Vacation Leave (VL)', 'date' => '2026-02-12', 'submitted' => '2026-02-07 2:30 PM', 'status' => 'pending'],
    ];
}

// Handle form submissions
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'submit_dtr':
                $message = 'DTR submitted successfully! Status: Pending Approval';
                $messageType = 'success';
                break;
            case 'submit_leave':
                $message = 'Leave request submitted successfully! Status: Pending Approval';
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
        }
    }
}

// Get current month and year
$currentMonth = $_GET['month'] ?? date('n');
$currentYear = $_GET['year'] ?? date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClockWise - Employee Dashboard</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/calendar.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="logo-section">
            <div class="logo">⏰</div>
            <h1 class="brand-name">ClockWise</h1>
        </div>

        <ul class="sidebar-menu">
            <li><a href="#" class="menu-link active" onclick="showSection('calendar')">📅 My Calendar</a></li>
            <?php if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'executive'): ?>
            <li><a href="#" class="menu-link" onclick="showSection('approvals')">✅ Approvals</a></li>
            <?php endif; ?>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="top-bar">
            <h1 class="page-title">My Calendar</h1>
            <div class="user-info">
                <div class="user-avatar"><?= strtoupper(substr($_SESSION['name'], 0, 1)) ?></div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($_SESSION['name']) ?></div>
                    <div style="font-size: 0.85em; opacity: 0.9;"><?= ucfirst($_SESSION['role']) ?></div>
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

            <!-- Calendar Section -->
            <div id="calendar" class="section active">
                <div class="calendar-container">
                    <!-- Calendar Legend -->
                    <div class="calendar-legend">
                        <div style="font-weight: 700; width: 100%; margin-bottom: 10px; color: var(--ateneo-blue);">Legend:</div>
                        
                        <div class="legend-item">
                            <div class="legend-box" style="background: #d4edda;"></div>
                            <span>Approved</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box" style="background: #fff3cd;"></div>
                            <span>Pending</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-box" style="background: #f8d7da;"></div>
                            <span>Declined</span>
                        </div>
                        
                        <div style="width: 100%; border-top: 1px solid var(--border-color); margin: 10px 0;"></div>
                        
                        <div class="legend-item">
                            <strong>Shift Codes:</strong>
                        </div>
                        <?php 
                        $sql_get_shift_schedules = "SELECT
                            shift_sched_name,
                            shift_sched_code
                            FROM shift_schedules
                            ORDER BY shift_sched_id ASC";

                            $stmt = $pdo->prepare($sql_get_shift_schedules);
                            $stmt->execute();
                            $get_shift_schedules = $stmt->fetchAll();

                            foreach ($get_shift_schedules as $shift_sched) {
                                echo '<div class="legend-item">';
                                echo '<span style="background: rgba(0, 85, 164, 0.1); padding: 2px 6px; border-radius: 4px; font-weight: 700;">' . htmlspecialchars($shift_sched['shift_sched_code']) . '</span>';
                                echo '<span>' . htmlspecialchars($shift_sched['shift_sched_name']) . '</span>';
                                echo '</div>'; } ?>
                        
                        <div style="width: 100%; border-top: 1px solid var(--border-color); margin: 10px 0;"></div>
                        
                        <div class="legend-item">
                            <strong>Leave Codes:</strong>
                        </div>

                        <?php 
                            $sql_get_leave_types = "SELECT
                            leave_type_code,
                            leave_type_name
                            FROM leave_types
                            ORDER BY leave_type_id ASC";
                            
                            $stmt = $pdo->prepare($sql_get_leave_types);
                            $stmt->execute();
                            $get_leave_types = $stmt->fetchAll();
                            
                            foreach ($get_leave_types as $leave_types) {
                                echo '<div class="legend-item">';
                                echo '<span style="font-weight: 700;">'. htmlspecialchars($leave_types['leave_type_code']) . " = " . htmlspecialchars($leave_types['leave_type_name']) . '</span>';
                                echo '</div>'; } ?>
                        
                    </div>

                    <div class="calendar-header">
                        <h2><?= date('F Y', strtotime("$currentYear-$currentMonth-01")) ?></h2>
                        <div class="calendar-nav">
                            <a href="?month=<?= $currentMonth - 1 ?>&year=<?= $currentYear ?>" class="nav-btn">← Previous</a>
                            <a href="?month=<?= $currentMonth + 1 ?>&year=<?= $currentYear ?>" class="nav-btn">Next →</a>
                        </div>
                    </div>

                    <div class="calendar-grid">
                        <?php
                        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                        foreach ($days as $day) {
                            echo "<div class='day-header'>$day</div>";
                        }

                        $firstDay = date('w', strtotime("$currentYear-$currentMonth-01"));
                        $totalDays = date('t', strtotime("$currentYear-$currentMonth-01"));
                        $today = date('j');
                        $thisMonth = date('n');
                        $thisYear = date('Y');

                        // Empty cells before first day
                        for ($i = 0; $i < $firstDay; $i++) {
                            echo "<div></div>";
                        }

                        // Days of month
                        for ($day = 1; $day <= $totalDays; $day++) {
                            $dateStr = sprintf("%04d-%02d-%02d", $currentYear, $currentMonth, $day);
                            $isToday = ($day == $today && $currentMonth == $thisMonth && $currentYear == $thisYear) ? 'today' : '';
                            
                            $dtr = $dtrRecords[$dateStr] ?? null;
                            $leave = $leaveRecords[$dateStr] ?? null;

                            echo "<div class='day-cell $isToday' onclick=\"showDTRModal('$dateStr')\">";
                            echo "<div class='day-number'>$day</div>";
                            
                            // Show DTR status with code
                            if ($dtr) {
                                $statusClass = "status-{$dtr['status']}";
                                echo "<div class='day-status $statusClass'>DTR</div>";
                                
                                // Show shift code
                                $shiftCode = '';
                                switch($dtr['shift']) {
                                    case 'Morning': $shiftCode = 'M'; break;
                                    case 'Afternoon': $shiftCode = 'A'; break;
                                    case 'Night': $shiftCode = 'N'; break;
                                    case 'Flexible': $shiftCode = 'F'; break;
                                }
                                echo "<div class='shift-code'>$shiftCode</div>";
                            }
                            
                            // Show Leave code
                            if ($leave) {
                                $statusClass = "status-{$leave['status']}";
                                $leaveCode = $leave['type']; // VL, SL, EL, BL, STU, NP
                                echo "<div class='day-status $statusClass'>$leaveCode</div>";
                            }
                            
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Approvals Section -->
            <?php if ($_SESSION['role'] === 'supervisor' || $_SESSION['role'] === 'executive'): ?>
            <div id="approvals" class="section">
                <h2 style="margin-bottom: 20px;">Pending Approvals</h2>

                <h3 style="margin-top: 20px; margin-bottom: 15px;">DTR Requests</h3>
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
                            <?php if (count($pendingDTRApprovals) > 0): ?>
                                <?php foreach ($pendingDTRApprovals as $dtr): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dtr['employee']) ?></td>
                                    <td><?= htmlspecialchars($dtr['date']) ?></td>
                                    <td><?= htmlspecialchars($dtr['shift']) ?></td>
                                    <td><?= htmlspecialchars($dtr['submitted']) ?></td>
                                    <td><span class="badge badge-pending">Pending</span></td>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                        No pending DTR requests
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <h3 style="margin-top: 40px; margin-bottom: 15px;">Leave Requests</h3>
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
                            <?php if (count($pendingLeaveApprovals) > 0): ?>
                                <?php foreach ($pendingLeaveApprovals as $leave): ?>
                                <tr>
                                    <td><?= htmlspecialchars($leave['employee']) ?></td>
                                    <td><?= htmlspecialchars($leave['type']) ?></td>
                                    <td><?= htmlspecialchars($leave['date']) ?></td>
                                    <td><?= htmlspecialchars($leave['submitted']) ?></td>
                                    <td><span class="badge badge-pending">Pending</span></td>
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
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                        No pending leave requests
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- DTR/Leave Modal -->
    <div id="dtrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Submit DTR</h3>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <div id="actionSelection">
                <h4 style="margin-bottom: 15px;">Choose Action:</h4>
                <button class="btn btn-primary" onclick="showDTRForm()" style="width: 100%; margin-bottom: 10px;">
                    📋 Submit DTR (Daily Time Record)
                </button>
                <button class="btn btn-secondary" onclick="showLeaveForm()" style="width: 100%;">
                    🏖️ File Leave Request
                </button>
            </div>

            <!-- DTR Form -->
            <div id="dtrForm" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="action" value="submit_dtr">
                    <input type="hidden" name="date" id="selectedDate">

                    <div class="form-group">
                        <label class="form-label">Select Shift</label>
                        <select name="shift" class="form-select" required>
                            <option value="">Choose shift...</option>
                            <option value="morning">Morning (8:00 AM - 5:00 PM)</option>
                            <option value="afternoon">Afternoon (2:00 PM - 11:00 PM)</option>
                            <option value="night">Night (10:00 PM - 7:00 AM)</option>
                            <option value="flexible">Flexible Hours</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="backToSelection()">Back</button>
                        <button type="submit" class="btn btn-primary">Submit DTR</button>
                    </div>
                </form>
            </div>

            <!-- Leave Form -->
            <div id="leaveForm" style="display: none;">
                <form method="POST">
                    <input type="hidden" name="action" value="submit_leave">
                    <input type="hidden" name="date" id="selectedDateLeave">

                    <div class="form-group">
                        <label class="form-label">Leave Type</label>
                        <select name="leave_type" class="form-select" required>
                            <option value="">Choose leave type...</option>
                            <option value="VL">Vacation Leave (VL) - <?= $leaveBalances['VL'] ?> days</option>
                            <option value="SL">Sick Leave (SL) - <?= $leaveBalances['SL'] ?> days</option>
                            <option value="EL">Emergency Leave (EL) - <?= $leaveBalances['EL'] ?> days</option>
                            <option value="BL">Birthday Leave (BL) - <?= $leaveBalances['BL'] ?> day</option>
                            <option value="STU">Study Leave (STU) - <?= $leaveBalances['STU'] ?> days</option>
                            <option value="NP">Leave w/o Pay (NP)</option>
                        </select>
                    </div>

                    <div style="display: flex; gap: 10px;">
                        <button type="button" class="btn btn-secondary" onclick="backToSelection()">Back</button>
                        <button type="submit" class="btn btn-primary">Submit Leave</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
            document.getElementById(sectionId).classList.add('active');
            
            document.querySelectorAll('.menu-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showDTRModal(date) {
            document.getElementById('dtrModal').classList.add('active');
            document.getElementById('selectedDate').value = date;
            document.getElementById('selectedDateLeave').value = date;
            document.getElementById('modalTitle').textContent = 'Action for ' + date;
            
            // Reset to action selection
            document.getElementById('actionSelection').style.display = 'block';
            document.getElementById('dtrForm').style.display = 'none';
            document.getElementById('leaveForm').style.display = 'none';
        }

        function showDTRForm() {
            document.getElementById('actionSelection').style.display = 'none';
            document.getElementById('dtrForm').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Submit DTR';
        }

        function showLeaveForm() {
            document.getElementById('actionSelection').style.display = 'none';
            document.getElementById('leaveForm').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'File Leave Request';
        }

        function backToSelection() {
            document.getElementById('actionSelection').style.display = 'block';
            document.getElementById('dtrForm').style.display = 'none';
            document.getElementById('leaveForm').style.display = 'none';
            document.getElementById('modalTitle').textContent = 'Choose Action';
        }

        function closeModal() {
            document.getElementById('dtrModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('active');
            }
        }
    </script>
</body>
</html>