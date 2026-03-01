<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$empId = (int) $_SESSION['user_id'];

// ── Fetch logged-in employee profile ──────────────────────────────────────────
$stmtEmp = $pdo->prepare("
    SELECT e.emp_id, e.emp_first_name, e.emp_last_name,
           e.work_group_id, wg.work_group_name, wg.hierarchy_level,
           e.shift_sched_id, ur.role_name, e.emp_birthday
    FROM   employees e
    JOIN   work_groups  wg ON wg.work_group_id = e.work_group_id
    LEFT JOIN user_roles ur ON ur.role_id      = e.role_id
    WHERE  e.emp_id = ?
");
$stmtEmp->execute([$empId]);
$employee = $stmtEmp->fetch();

if (!$employee) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$workGroupId    = (int)$employee['work_group_id'];
$isAdmin        = ($employee['role_name'] === 'Admin');

// ── Detect whether migration has been run ─────────────────────────────────────
$migrationDone = false;
try {
    $pdo->query("SELECT hierarchy_level FROM work_groups LIMIT 1");
    $pdo->query("SELECT assignment_id   FROM approver_assignments LIMIT 1");
    $pdo->query("SELECT dtr_id          FROM dtr_records LIMIT 1");
    $migrationDone = true;
} catch (PDOException $e) {
    // Migration not yet applied — fall back to safe defaults
}

// ── Auto-approved group? (hierarchy_level <= 1) ───────────────────────────────
// BOD (0), Administrative (0): fully exempt.
// Executive (1): auto-approved for own submissions AND can act as approver for others.
$hierarchyLevel = $migrationDone ? (int)$employee['hierarchy_level'] : 99;
$isAutoApproved = ($migrationDone && $hierarchyLevel <= 1);

// ── Is this user an approver for anyone? ─────────────────────────────────────
$hasAssignees = false;
if ($migrationDone) {
    $stmtIsApprover = $pdo->prepare("SELECT COUNT(*) FROM approver_assignments WHERE approver_emp_id = ?");
    $stmtIsApprover->execute([$empId]);
    $hasAssignees = ((int)$stmtIsApprover->fetchColumn()) > 0;
}

// ── Leave entitlements for this work group ─────────────────────────────────
$stmtEnt = $pdo->prepare("
    SELECT lt.leave_type_id, lt.leave_type_name, lt.leave_type_code,
           wgl.leave_type_quantity AS allotted
    FROM   work_group_leaves wgl
    JOIN   leave_types lt ON lt.leave_type_id  = wgl.leave_type_id
    JOIN   work_groups  wg ON wg.work_group_name = wgl.work_group_name
    WHERE  wg.work_group_id = ?
    ORDER  BY lt.leave_type_id
");
$stmtEnt->execute([$workGroupId]);
$entitlements = $stmtEnt->fetchAll();

// ── Leave used this calendar year ─────────────────────────────────────────────
$stmtUsed = $pdo->prepare("
    SELECT lr.leave_type_id, COUNT(*) AS used
    FROM   leave_records lr
    WHERE  lr.emp_id = ?
      AND  lr.status IN ('pending','approved')
      AND  YEAR(lr.date) = YEAR(CURDATE())
    GROUP  BY lr.leave_type_id
");
$stmtUsed->execute([$empId]);
$usedMap = [];
foreach ($stmtUsed->fetchAll() as $u) { $usedMap[$u['leave_type_id']] = (int)$u['used']; }

$leaveBalances = [];
foreach ($entitlements as $ent) {
    $ltid     = (int)$ent['leave_type_id'];
    $allotted = (int)$ent['allotted'];
    $used     = $usedMap[$ltid] ?? 0;
    $leaveBalances[$ltid] = [
        'leave_type_id'   => $ltid,
        'leave_type_name' => $ent['leave_type_name'],
        'leave_type_code' => $ent['leave_type_code'],
        'allotted'        => $allotted,
        'used'            => $used,
        'remaining'       => max(0, $allotted - $used),
    ];
}

// ── Calendar month/year ───────────────────────────────────────────────────────
$currentMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$currentYear  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');
if ($currentMonth < 1)  { $currentMonth = 12; $currentYear--; }
if ($currentMonth > 12) { $currentMonth = 1;  $currentYear++; }

$monthStart = sprintf('%04d-%02d-01', $currentYear, $currentMonth);
$monthEnd   = date('Y-m-t', strtotime($monthStart));

// ── DTR records for this employee this month ──────────────────────────────────
$dtrRecords = [];
if ($migrationDone) {
    $stmtDtr = $pdo->prepare("
        SELECT dr.dtr_id, dr.date, dr.status,
               ss.shift_sched_code, ss.shift_sched_name, ss.start_time, ss.end_time
        FROM   dtr_records dr
        JOIN   shift_schedules ss ON ss.shift_sched_id = dr.shift_sched_id
        WHERE  dr.emp_id = ? AND dr.date BETWEEN ? AND ?
    ");
    $stmtDtr->execute([$empId, $monthStart, $monthEnd]);
    foreach ($stmtDtr->fetchAll() as $row) { $dtrRecords[$row['date']] = $row; }
}

// ── Leave records for this employee this month ────────────────────────────────
$leaveRecords = [];
$stmtLv = $pdo->prepare("
    SELECT lr.leave_rec_id, lr.date,
           " . ($migrationDone ? "lr.status" : "'pending' AS status") . ",
           lt.leave_type_code, lt.leave_type_name
    FROM   leave_records lr
    JOIN   leave_types lt ON lt.leave_type_id = lr.leave_type_id
    WHERE  lr.emp_id = ? AND lr.date BETWEEN ? AND ?
");
$stmtLv->execute([$empId, $monthStart, $monthEnd]);
foreach ($stmtLv->fetchAll() as $row) { $leaveRecords[$row['date']] = $row; }

// ── Shift schedules (dropdown) ────────────────────────────────────────────────
$allShifts = $pdo->query("SELECT * FROM shift_schedules ORDER BY shift_sched_id")->fetchAll();

// ── Pending approvals scoped to THIS user's assignees ─────────────────────────
// Only loaded when the user is an approver (hasAssignees = true).
$pendingDTRApprovals   = [];
$pendingLeaveApprovals = [];

if ($hasAssignees && $migrationDone) {
    // DTR pending for employees assigned to me
    $stmtDA = $pdo->prepare("
        SELECT dr.dtr_id, dr.date, dr.status, dr.submitted_at,
               CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee_name,
               ss.shift_sched_name, ss.start_time, ss.end_time
        FROM   dtr_records dr
        JOIN   employees       e  ON e.emp_id          = dr.emp_id
        JOIN   shift_schedules ss ON ss.shift_sched_id = dr.shift_sched_id
        -- scope: only my assignees
        JOIN   approver_assignments aa
               ON aa.assignee_emp_id = dr.emp_id
              AND aa.approver_emp_id = ?
        WHERE  dr.status = 'pending'
        ORDER  BY dr.submitted_at ASC
    ");
    $stmtDA->execute([$empId]);
    $pendingDTRApprovals = $stmtDA->fetchAll();

    // Leave pending for employees assigned to me
    $stmtLA = $pdo->prepare("
        SELECT lr.leave_rec_id, lr.date, lr.status, lr.submitted_at,
               CONCAT(e.emp_first_name,' ',e.emp_last_name) AS employee_name,
               lt.leave_type_name, lt.leave_type_code
        FROM   leave_records lr
        JOIN   employees   e  ON e.emp_id          = lr.emp_id
        JOIN   leave_types lt ON lt.leave_type_id  = lr.leave_type_id
        -- scope: only my assignees
        JOIN   approver_assignments aa
               ON aa.assignee_emp_id = lr.emp_id
              AND aa.approver_emp_id = ?
        WHERE  lr.status = 'pending'
        ORDER  BY lr.submitted_at ASC
    ");
    $stmtLA->execute([$empId]);
    $pendingLeaveApprovals = $stmtLA->fetchAll();
}

// ── POST handler ──────────────────────────────────────────────────────────────
$message = ''; $messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ── Submit DTR ────────────────────────────────────────────────────────────
    if ($action === 'submit_dtr') {
        if (!$migrationDone) {
            $message = 'DTR submission is not available yet. Database migration has not been applied.';
            $messageType = 'error';
        } else {
        $date         = $_POST['date']         ?? '';
        $shiftSchedId = (int)($_POST['shift_sched_id'] ?? 0);

        if (!$date || !$shiftSchedId) {
            $message = 'Please select a valid date and shift.'; $messageType = 'error';
        } else {
            $chk = $pdo->prepare("SELECT dtr_id FROM dtr_records WHERE emp_id = ? AND date = ?");
            $chk->execute([$empId, $date]);
            if ($chk->fetch()) {
                $message = 'A DTR entry for ' . htmlspecialchars($date) . ' already exists.'; $messageType = 'error';
            } else {
                // Auto-approved groups get status='approved' immediately; others get 'pending'
                $initialStatus = $isAutoApproved ? 'approved' : 'pending';
                $pdo->prepare("INSERT INTO dtr_records (emp_id, shift_sched_id, date, status) VALUES (?,?,?,?)")
                    ->execute([$empId, $shiftSchedId, $date, $initialStatus]);
                $message = $isAutoApproved
                    ? 'DTR submitted and automatically approved for ' . htmlspecialchars($date) . '.'
                    : 'DTR submitted for ' . htmlspecialchars($date) . '. Pending approval from your assigned approver.';
                $messageType = 'success';
            }
        }
        } // end $migrationDone
    }

    // ── Submit Leave ──────────────────────────────────────────────────────────
    elseif ($action === 'submit_leave') {
        if (!$migrationDone) {
            $message = 'Leave submission is not available yet. Database migration has not been applied.';
            $messageType = 'error';
        } else {
        $date        = $_POST['date']          ?? '';
        $leaveTypeId = (int)($_POST['leave_type_id'] ?? 0);

        if (!$date || !$leaveTypeId) {
            $message = 'Please select a valid date and leave type.'; $messageType = 'error';
        } elseif (!isset($leaveBalances[$leaveTypeId])) {
            $message = 'That leave type is not available for your work group.'; $messageType = 'error';
        } else {
            $bal    = $leaveBalances[$leaveTypeId];
            $isLWOP = ($bal['leave_type_code'] === 'NoPay');
            if (!$isLWOP && $bal['remaining'] <= 0) {
                $message = 'You have no remaining ' . htmlspecialchars($bal['leave_type_name']) . ' balance.'; $messageType = 'error';
            } else {
                $chk = $pdo->prepare("SELECT leave_rec_id FROM leave_records WHERE emp_id = ? AND date = ?");
                $chk->execute([$empId, $date]);
                if ($chk->fetch()) {
                    $message = 'A leave entry for ' . htmlspecialchars($date) . ' already exists.'; $messageType = 'error';
                } else {
                    $initialStatus = $isAutoApproved ? 'approved' : 'pending';
                    $pdo->prepare("INSERT INTO leave_records (emp_id, leave_type_id, date, status) VALUES (?,?,?,?)")
                        ->execute([$empId, $leaveTypeId, $date, $initialStatus]);
                    $message = $isAutoApproved
                        ? htmlspecialchars($bal['leave_type_name']) . ' submitted and automatically approved for ' . htmlspecialchars($date) . '.'
                        : htmlspecialchars($bal['leave_type_name']) . ' submitted for ' . htmlspecialchars($date) . '. Pending approval from your assigned approver.';
                    $messageType = 'success';
                }
            }
        }
        } // end $migrationDone
    }

    // ── Approve DTR (approver only, scoped to their assignees) ───────────────
    elseif ($action === 'approve_dtr' && $hasAssignees) {
        $dtrId = (int)($_POST['dtr_id'] ?? 0);
        // Security: confirm this DTR belongs to one of MY assignees
        $stmtSec = $pdo->prepare("
            SELECT dr.dtr_id FROM dtr_records dr
            JOIN   approver_assignments aa ON aa.assignee_emp_id = dr.emp_id
                                          AND aa.approver_emp_id = ?
            WHERE  dr.dtr_id = ?
        ");
        $stmtSec->execute([$empId, $dtrId]);
        if ($stmtSec->fetch()) {
            $pdo->prepare("UPDATE dtr_records SET status='approved' WHERE dtr_id=?")->execute([$dtrId]);
            $message = 'DTR approved.'; $messageType = 'success';
        } else {
            $message = 'Unauthorized action.'; $messageType = 'error';
        }
    }

    // ── Decline DTR (approver only, scoped) ───────────────────────────────────
    elseif ($action === 'decline_dtr' && $hasAssignees) {
        $dtrId = (int)($_POST['dtr_id'] ?? 0);
        $stmtSec = $pdo->prepare("
            SELECT dr.dtr_id FROM dtr_records dr
            JOIN   approver_assignments aa ON aa.assignee_emp_id = dr.emp_id
                                          AND aa.approver_emp_id = ?
            WHERE  dr.dtr_id = ?
        ");
        $stmtSec->execute([$empId, $dtrId]);
        if ($stmtSec->fetch()) {
            $pdo->prepare("UPDATE dtr_records SET status='declined' WHERE dtr_id=?")->execute([$dtrId]);
            $message = 'DTR declined.'; $messageType = 'info';
        } else {
            $message = 'Unauthorized action.'; $messageType = 'error';
        }
    }

    // ── Approve Leave (approver only, scoped) ─────────────────────────────────
    elseif ($action === 'approve_leave' && $hasAssignees) {
        $leaveRecId = (int)($_POST['leave_rec_id'] ?? 0);
        $stmtSec = $pdo->prepare("
            SELECT lr.leave_rec_id FROM leave_records lr
            JOIN   approver_assignments aa ON aa.assignee_emp_id = lr.emp_id
                                          AND aa.approver_emp_id = ?
            WHERE  lr.leave_rec_id = ?
        ");
        $stmtSec->execute([$empId, $leaveRecId]);
        if ($stmtSec->fetch()) {
            $pdo->prepare("UPDATE leave_records SET status='approved' WHERE leave_rec_id=?")->execute([$leaveRecId]);
            $message = 'Leave approved.'; $messageType = 'success';
        } else {
            $message = 'Unauthorized action.'; $messageType = 'error';
        }
    }

    // ── Decline Leave (approver only, scoped) ─────────────────────────────────
    elseif ($action === 'decline_leave' && $hasAssignees) {
        $leaveRecId = (int)($_POST['leave_rec_id'] ?? 0);
        $stmtSec = $pdo->prepare("
            SELECT lr.leave_rec_id FROM leave_records lr
            JOIN   approver_assignments aa ON aa.assignee_emp_id = lr.emp_id
                                          AND aa.approver_emp_id = ?
            WHERE  lr.leave_rec_id = ?
        ");
        $stmtSec->execute([$empId, $leaveRecId]);
        if ($stmtSec->fetch()) {
            $pdo->prepare("UPDATE leave_records SET status='declined' WHERE leave_rec_id=?")->execute([$leaveRecId]);
            $message = 'Leave declined.'; $messageType = 'info';
        } else {
            $message = 'Unauthorized action.'; $messageType = 'error';
        }
    }

    header("Location: user-dashboard.php?month=$currentMonth&year=$currentYear&msg=" . urlencode($message) . "&mtype=" . urlencode($messageType));
    exit();
}

// Flash message from redirect
if (empty($message) && isset($_GET['msg'])) {
    $message = $_GET['msg']; $messageType = $_GET['mtype'] ?? 'info';
}

$shiftLegend   = $pdo->query("SELECT shift_sched_code, shift_sched_name FROM shift_schedules ORDER BY shift_sched_id")->fetchAll();
$ltLegend      = $pdo->query("SELECT leave_type_code, leave_type_name FROM leave_types ORDER BY leave_type_id")->fetchAll();
$birthdayMonth = $employee['emp_birthday'] ? (int)date('n', strtotime($employee['emp_birthday'])) : null;
$monthName     = date('F Y', strtotime("$currentYear-$currentMonth-01"));

// Badge count for approvals tab
$pendingCount = count($pendingDTRApprovals) + count($pendingLeaveApprovals);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Calendar – ClockWise</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/calendar.css">
    <style>
        /* Pending count badge on sidebar tab */
        .pending-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 5px;
            border-radius: 10px;
            background: #e63946;
            color: #fff;
            font-size: .72em;
            font-weight: 700;
            margin-left: 6px;
            vertical-align: middle;
        }
        /* Auto-approved notice in modal */
        .auto-approved-notice {
            background: #d1ecf1;
            border-left: 4px solid #0c5460;
            padding: 10px 14px;
            border-radius: 0 6px 6px 0;
            font-size: .9em;
            color: #0c5460;
            margin-bottom: 14px;
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
        <li>
            <a href="#calendar" class="menu-link active" aria-current="page"
               onclick="showSection('calendar', this); return false;">
                <span aria-hidden="true">📅</span> My Calendar
            </a>
        </li>
        <?php if ($hasAssignees): ?>
        <li>
            <a href="#approvals" class="menu-link"
               onclick="showSection('approvals', this); return false;">
                <span aria-hidden="true">✅</span> Approvals
                <?php if ($pendingCount > 0): ?>
                    <span class="pending-badge" aria-label="<?= $pendingCount ?> pending"><?= $pendingCount ?></span>
                <?php endif; ?>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>

<main class="main-content" id="main-content">
    <header class="top-bar">
        <h1 class="page-title" id="page-heading">My Calendar</h1>
        <div class="user-info">
            <div class="user-avatar" aria-hidden="true">
                <?= strtoupper(substr($employee['emp_first_name'], 0, 1)) ?>
            </div>
            <div class="user-details">
                <span class="user-name">
                    <?= htmlspecialchars($employee['emp_first_name'] . ' ' . $employee['emp_last_name']) ?>
                </span>
                <span class="user-role">
                    <?= htmlspecialchars($employee['work_group_name']) ?><?= $isAdmin ? ' · Admin' : '' ?>
                </span>
            </div>
            <a href="logout.php" class="logout-btn">Log out</a>
        </div>
    </header>

    <div class="content-area">

        <?php if ($message): ?>
            <div
                class="alert alert-<?= htmlspecialchars($messageType) ?>"
                role="<?= $messageType === 'error' ? 'alert' : 'status' ?>"
                aria-live="<?= $messageType === 'error' ? 'assertive' : 'polite' ?>"
            >
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- ══ Calendar Section ══════════════════════════════════════════════ -->
        <section id="calendar" class="section active" aria-labelledby="calendar-heading">
            <div class="calendar-container">

                <!-- Legend -->
                <div class="calendar-legend" aria-label="Calendar legend">
                    <p style="font-weight:700;width:100%;margin-bottom:8px;color:var(--ateneo-blue);">Legend</p>
                    <ul style="display:contents;list-style:none;">
                        <li class="legend-item">
                            <div class="legend-box" style="background:#d4edda;" aria-hidden="true"></div>
                            <span>Approved</span>
                        </li>
                        <li class="legend-item">
                            <div class="legend-box" style="background:#fff3cd;" aria-hidden="true"></div>
                            <span>Pending</span>
                        </li>
                        <li class="legend-item">
                            <div class="legend-box" style="background:#f8d7da;" aria-hidden="true"></div>
                            <span>Declined</span>
                        </li>
                    </ul>

                    <hr style="width:100%;border-color:var(--border-color);margin:8px 0;" aria-hidden="true">
                    <p style="font-weight:700;width:100%;">Shift Codes</p>
                    <ul style="display:contents;list-style:none;">
                        <?php foreach ($shiftLegend as $sl): ?>
                        <li class="legend-item">
                            <span style="background:rgba(0,85,164,0.1);padding:2px 6px;border-radius:4px;font-weight:700;">
                                <?= htmlspecialchars($sl['shift_sched_code']) ?>
                            </span>
                            <span><?= htmlspecialchars($sl['shift_sched_name']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <hr style="width:100%;border-color:var(--border-color);margin:8px 0;" aria-hidden="true">
                    <p style="font-weight:700;width:100%;">Leave Codes</p>
                    <ul style="display:contents;list-style:none;">
                        <?php foreach ($ltLegend as $lt): ?>
                        <li class="legend-item">
                            <span style="font-weight:700;">
                                <?= htmlspecialchars($lt['leave_type_code']) ?> = <?= htmlspecialchars($lt['leave_type_name']) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if (!empty($leaveBalances)): ?>
                    <hr style="width:100%;border-color:var(--border-color);margin:8px 0;" aria-hidden="true">
                    <p style="font-weight:700;width:100%;">Your Leave Balances</p>
                    <ul style="display:contents;list-style:none;">
                        <?php foreach ($leaveBalances as $lb): ?>
                        <li class="legend-item" style="justify-content:space-between;width:100%;">
                            <span><?= htmlspecialchars($lb['leave_type_code']) ?></span>
                            <span style="font-weight:600;">
                                <?= $lb['remaining'] == 9999 ? '∞' : $lb['remaining'] ?>
                                /
                                <?= $lb['allotted'] == 9999 ? '∞' : $lb['allotted'] ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($isAutoApproved): ?>
                    <hr style="width:100%;border-color:var(--border-color);margin:8px 0;" aria-hidden="true">
                    <p style="font-size:.82em;color:#0c5460;background:#d1ecf1;padding:6px 8px;border-radius:4px;width:100%;box-sizing:border-box;">
                        ✓ Your submissions are <strong>auto-approved</strong>.
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Calendar header + nav -->
                <div class="calendar-header">
                    <h2 id="calendar-heading"><?= htmlspecialchars($monthName) ?></h2>
                    <nav class="calendar-nav" aria-label="Month navigation">
                        <a href="?month=<?= $currentMonth - 1 ?>&amp;year=<?= $currentYear ?>"
                           class="nav-btn" aria-label="Previous month">← Previous</a>
                        <a href="?month=<?= $currentMonth + 1 ?>&amp;year=<?= $currentYear ?>"
                           class="nav-btn" aria-label="Next month">Next →</a>
                    </nav>
                </div>

                <!-- Calendar grid -->
                <div
                    class="calendar-grid"
                    role="grid"
                    aria-label="<?= htmlspecialchars($monthName) ?> calendar"
                >
                    <?php
                    $dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                    $dayAbbr  = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    foreach ($dayAbbr as $i => $abbr): ?>
                    <div class="day-header" role="columnheader" aria-label="<?= $dayNames[$i] ?>">
                        <abbr title="<?= $dayNames[$i] ?>"><?= $abbr ?></abbr>
                    </div>
                    <?php endforeach;

                    $firstDay  = (int)date('w', strtotime("$currentYear-$currentMonth-01"));
                    $totalDays = (int)date('t', strtotime("$currentYear-$currentMonth-01"));
                    $today     = (int)date('j');
                    $thisMonth = (int)date('n');
                    $thisYear  = (int)date('Y');

                    for ($i = 0; $i < $firstDay; $i++) {
                        echo '<div role="gridcell" aria-hidden="true"></div>';
                    }

                    for ($day = 1; $day <= $totalDays; $day++):
                        $dateStr   = sprintf('%04d-%02d-%02d', $currentYear, $currentMonth, $day);
                        $isToday   = ($day == $today && $currentMonth == $thisMonth && $currentYear == $thisYear);
                        $dtr       = $dtrRecords[$dateStr]   ?? null;
                        $leave     = $leaveRecords[$dateStr] ?? null;
                        $cellLabel = $dayNames[(int)date('w', strtotime($dateStr))] . ', ' . date('F j, Y', strtotime($dateStr));
                        if ($dtr)   $cellLabel .= ', DTR ' . $dtr['status'];
                        if ($leave) $cellLabel .= ', ' . $leave['leave_type_code'] . ' ' . $leave['status'];
                        if ($isToday) $cellLabel .= ', Today';
                    ?>
                    <div
                        class="day-cell<?= $isToday ? ' today' : '' ?>"
                        role="gridcell"
                        tabindex="0"
                        aria-label="<?= htmlspecialchars($cellLabel) ?>. Press Enter to submit DTR or leave."
                        onclick="showDTRModal('<?= htmlspecialchars($dateStr) ?>')"
                        onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();showDTRModal('<?= htmlspecialchars($dateStr) ?>')}"
                    >
                        <div class="day-number" aria-hidden="true"><?= $day ?></div>
                        <?php if ($dtr): ?>
                            <div class="day-status status-<?= htmlspecialchars($dtr['status']) ?>" aria-hidden="true">DTR</div>
                            <div class="shift-code" aria-hidden="true"><?= htmlspecialchars($dtr['shift_sched_code']) ?></div>
                        <?php endif; ?>
                        <?php if ($leave): ?>
                            <div class="day-status status-<?= htmlspecialchars($leave['status']) ?>" aria-hidden="true">
                                <?= htmlspecialchars($leave['leave_type_code']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div><!-- /.calendar-grid -->

            </div><!-- /.calendar-container -->
        </section>

        <!-- ══ Approvals Section (shown only when user is an approver) ═══════ -->
        <?php if ($hasAssignees): ?>
        <section id="approvals" class="section" aria-labelledby="approvals-heading">
            <h2 id="approvals-heading" class="mb-3">Pending Approvals</h2>

            <p style="margin-bottom:16px;color:var(--text-muted);font-size:.9em;">
                Showing submissions from employees assigned to you for approval.
            </p>

            <!-- DTR Requests -->
            <h3 id="dtr-approvals-heading" class="mt-2 mb-2">DTR Requests</h3>
            <div class="table-container">
                <table aria-labelledby="dtr-approvals-heading">
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
                        <?php if ($pendingDTRApprovals): ?>
                            <?php foreach ($pendingDTRApprovals as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td>
                                    <?= htmlspecialchars($row['shift_sched_name']) ?>
                                    (<?= date('g:i A', strtotime($row['start_time'])) ?>–<?= date('g:i A', strtotime($row['end_time'])) ?>)
                                </td>
                                <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                                <td><span class="badge badge-pending">Pending</span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="approve_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= (int)$row['dtr_id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve DTR for <?= htmlspecialchars($row['employee_name']) ?> on <?= htmlspecialchars($row['date']) ?>">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="decline_dtr">
                                        <input type="hidden" name="dtr_id" value="<?= (int)$row['dtr_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline DTR for <?= htmlspecialchars($row['employee_name']) ?> on <?= htmlspecialchars($row['date']) ?>">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">
                                    No pending DTR requests
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Leave Requests -->
            <h3 id="leave-approvals-heading" class="mt-3 mb-2">Leave Requests</h3>
            <div class="table-container">
                <table aria-labelledby="leave-approvals-heading">
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
                        <?php if ($pendingLeaveApprovals): ?>
                            <?php foreach ($pendingLeaveApprovals as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['employee_name']) ?></td>
                                <td><?= htmlspecialchars($row['leave_type_name']) ?> (<?= htmlspecialchars($row['leave_type_code']) ?>)</td>
                                <td><?= htmlspecialchars($row['date']) ?></td>
                                <td><?= htmlspecialchars($row['submitted_at']) ?></td>
                                <td><span class="badge badge-pending">Pending</span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"       value="approve_leave">
                                        <input type="hidden" name="leave_rec_id" value="<?= (int)$row['leave_rec_id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm"
                                                aria-label="Approve leave for <?= htmlspecialchars($row['employee_name']) ?> on <?= htmlspecialchars($row['date']) ?>">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action"       value="decline_leave">
                                        <input type="hidden" name="leave_rec_id" value="<?= (int)$row['leave_rec_id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm"
                                                aria-label="Decline leave for <?= htmlspecialchars($row['employee_name']) ?> on <?= htmlspecialchars($row['date']) ?>">
                                            Decline
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">
                                    No pending leave requests
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <?php endif; ?>

    </div><!-- /.content-area -->
</main>

<!-- ══ DTR / Leave Modal ══════════════════════════════════════════════════════ -->
<div
    id="dtrModal"
    class="modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modalTitle"
    aria-hidden="true"
>
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Choose Action</h2>
            <button class="close-btn" onclick="closeModal()" aria-label="Close dialog">
                <span aria-hidden="true">×</span>
            </button>
        </div>

        <?php if ($isAutoApproved): ?>
        <div class="auto-approved-notice" role="note">
            ✓ You are in the <strong><?= htmlspecialchars($employee['work_group_name']) ?></strong> group.
            Your DTR and leave submissions are <strong>automatically approved</strong>.
        </div>
        <?php endif; ?>

        <!-- Step 1: choose action -->
        <div id="actionSelection">
            <p id="actionPrompt" class="mb-2" style="font-weight:600;">Choose an action for this date:</p>
            <div style="display:flex;flex-direction:column;gap:10px;">
                <button class="btn btn-primary" onclick="showDTRForm()" style="width:100%;">
                    <span aria-hidden="true">📋</span> Submit DTR (Daily Time Record)
                </button>
                <button class="btn btn-secondary" onclick="showLeaveForm()" style="width:100%;">
                    <span aria-hidden="true">🏖️</span> File Leave Request
                </button>
            </div>
        </div>

        <!-- Step 2a: DTR form -->
        <div id="dtrForm" hidden>
            <form method="POST">
                <input type="hidden" name="action" value="submit_dtr">
                <input type="hidden" name="date"   id="selectedDate">

                <div class="form-group">
                    <label class="form-label" for="shift_sched_id">
                        Select Shift
                        <span class="required" aria-hidden="true">*</span>
                        <span class="sr-only">(required)</span>
                    </label>
                    <select name="shift_sched_id" id="shift_sched_id" class="form-select" required aria-required="true">
                        <option value="">Choose shift…</option>
                        <?php foreach ($allShifts as $s): ?>
                        <option value="<?= (int)$s['shift_sched_id'] ?>">
                            <?= htmlspecialchars($s['shift_sched_name']) ?>
                            (<?= date('g:i A', strtotime($s['start_time'])) ?>–<?= date('g:i A', strtotime($s['end_time'])) ?>)
                            [<?= htmlspecialchars($s['shift_sched_code']) ?>]
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="backToSelection()">Back</button>
                    <button type="submit" class="btn btn-primary">
                        Submit DTR<?= $isAutoApproved ? ' (Auto-Approved)' : '' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Step 2b: Leave form -->
        <div id="leaveForm" hidden>
            <form method="POST">
                <input type="hidden" name="action" value="submit_leave">
                <input type="hidden" name="date"   id="selectedDateLeave">

                <div class="form-group">
                    <label class="form-label" for="leave_type_id">
                        Leave Type
                        <span class="required" aria-hidden="true">*</span>
                        <span class="sr-only">(required)</span>
                    </label>
                    <select name="leave_type_id" id="leave_type_id" class="form-select" required aria-required="true">
                        <option value="">Choose leave type…</option>
                        <?php foreach ($leaveBalances as $lb):
                            if ($lb['leave_type_code'] === 'BDay' && $birthdayMonth !== null && $birthdayMonth !== (int)date('n')) continue;
                            $isLWOP    = ($lb['leave_type_code'] === 'NoPay');
                            $noBalance = (!$isLWOP && $lb['remaining'] <= 0);
                            $label     = htmlspecialchars($lb['leave_type_name']) . ' (' . htmlspecialchars($lb['leave_type_code']) . ')';
                            $balance   = $isLWOP ? '' : ' — ' . $lb['remaining'] . ' / ' . $lb['allotted'] . ' days';
                        ?>
                        <option
                            value="<?= (int)$lb['leave_type_id'] ?>"
                            <?= $noBalance ? 'disabled aria-disabled="true"' : '' ?>
                        >
                            <?= $label . $balance . ($noBalance ? ' [No Balance]' : '') ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:flex;gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="backToSelection()">Back</button>
                    <button type="submit" class="btn btn-primary">
                        Submit Leave<?= $isAutoApproved ? ' (Auto-Approved)' : '' ?>
                    </button>
                </div>
            </form>
        </div>

    </div><!-- /.modal-content -->
</div><!-- /#dtrModal -->

<script>
var lastFocusedCell = null;

function showSection(sectionId, linkEl) {
    document.querySelectorAll('.section').forEach(function(s) { s.classList.remove('active'); });
    var target = document.getElementById(sectionId);
    if (target) {
        target.classList.add('active');
        var heading = target.querySelector('h2');
        if (heading) { heading.setAttribute('tabindex', '-1'); heading.focus(); }
    }
    document.querySelectorAll('.menu-link').forEach(function(l) {
        l.classList.remove('active'); l.removeAttribute('aria-current');
    });
    if (linkEl) { linkEl.classList.add('active'); linkEl.setAttribute('aria-current', 'page'); }
    var headings = { calendar: 'My Calendar', approvals: 'Approvals' };
    var ph = document.getElementById('page-heading');
    if (ph && headings[sectionId]) ph.textContent = headings[sectionId];
}

function showDTRModal(date) {
    lastFocusedCell = document.activeElement;
    var modal = document.getElementById('dtrModal');
    document.getElementById('selectedDate').value      = date;
    document.getElementById('selectedDateLeave').value = date;
    document.getElementById('modalTitle').textContent  = 'Action for ' + date;
    resetModal();
    modal.classList.add('active');
    modal.removeAttribute('aria-hidden');
    var firstFocusable = modal.querySelector('button, [href], input, select, [tabindex]:not([tabindex="-1"])');
    if (firstFocusable) firstFocusable.focus();
    modal.addEventListener('keydown', trapFocus);
}

function closeModal() {
    var modal = document.getElementById('dtrModal');
    modal.classList.remove('active');
    modal.setAttribute('aria-hidden', 'true');
    modal.removeEventListener('keydown', trapFocus);
    if (lastFocusedCell) lastFocusedCell.focus();
}

function trapFocus(e) {
    if (e.key === 'Escape') { closeModal(); return; }
    if (e.key !== 'Tab') return;
    var modal = document.getElementById('dtrModal');
    var focusable = modal.querySelectorAll('button:not([disabled]), [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
    var first = focusable[0]; var last = focusable[focusable.length - 1];
    if (e.shiftKey) {
        if (document.activeElement === first) { e.preventDefault(); last.focus(); }
    } else {
        if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
    }
}

function showDTRForm() {
    document.getElementById('actionSelection').hidden = true;
    document.getElementById('dtrForm').hidden         = false;
    document.getElementById('modalTitle').textContent = 'Submit DTR';
    document.getElementById('shift_sched_id').focus();
}

function showLeaveForm() {
    document.getElementById('actionSelection').hidden = true;
    document.getElementById('leaveForm').hidden       = false;
    document.getElementById('modalTitle').textContent = 'File Leave Request';
    document.getElementById('leave_type_id').focus();
}

function backToSelection() { resetModal(); }

function resetModal() {
    document.getElementById('actionSelection').hidden = false;
    document.getElementById('dtrForm').hidden         = true;
    document.getElementById('leaveForm').hidden       = true;
    document.getElementById('modalTitle').textContent = 'Choose Action';
}

document.getElementById('dtrModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

</body>
</html>