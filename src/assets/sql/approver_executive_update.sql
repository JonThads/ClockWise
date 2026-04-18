-- ============================================================
-- ClockWise: Approver Assignments System
-- Run this against your existing `clockwise` database.
-- ============================================================

-- ── Step 1: Add hierarchy_level to work_groups ───────────────────────────────
-- Lower number = higher in the chain of command.
--
-- AUTO-APPROVED groups (submissions approved instantly, no approver needed):
--   0  = Board of Directors  — exempt, cannot be assigned as approvers
--   0  = Administrative      — exempt, cannot be assigned as approvers
--
-- HIERARCHY chain (Executive can also approve; their OWN submissions are auto-approved):
--   1  = Executive     — auto-approved for own submissions; CAN approve Managerial and below
--   2  = Managerial    — needs approval from Executive
--   3  = Supervisory   — needs approval from Executive or Managerial
--   4  = Rank and File — needs approval from Executive, Managerial, or Supervisory
--
-- Rule: a candidate is a valid approver when:
--   candidate.hierarchy_level < assignee.hierarchy_level   (strictly higher in chain)
--   AND candidate.hierarchy_level >= 1                     (not a non-participating exempt group)
--
-- Auto-approval for submissions is triggered when:
--   employee.hierarchy_level <= 1   (BOD=0, Administrative=0, Executive=1)

ALTER TABLE `work_groups`
    ADD COLUMN IF NOT EXISTS `hierarchy_level` TINYINT UNSIGNED NOT NULL DEFAULT 99
        COMMENT '0=BOD/Admin (fully exempt),1=Executive (auto-approved + can approve others),2=Managerial,3=Supervisory,4=Rank and File'
    AFTER `work_group_name`;

-- Set levels for the seeded work groups
UPDATE `work_groups` SET `hierarchy_level` = 0 WHERE `work_group_name` = 'Board of Directors';
UPDATE `work_groups` SET `hierarchy_level` = 0 WHERE `work_group_name` = 'Administrative';
UPDATE `work_groups` SET `hierarchy_level` = 1 WHERE `work_group_name` = 'Executive';
UPDATE `work_groups` SET `hierarchy_level` = 2 WHERE `work_group_name` = 'Managerial';
UPDATE `work_groups` SET `hierarchy_level` = 3 WHERE `work_group_name` = 'Supervisory';
UPDATE `work_groups` SET `hierarchy_level` = 4 WHERE `work_group_name` = 'Rank and File';

-- ── Step 2: Create approver_assignments table ─────────────────────────────────
-- One row per assignee: each employee that NEEDS approval has exactly one approver.
-- Constraint: an employee can only appear once as assignee_emp_id (they have one boss).
-- Constraint: an employee can only appear once as approver_emp_id
--             (one approver per assignee — but an approver CAN have multiple assignees,
--              so the UNIQUE is on assignee only; see note below).
--
-- NOTE on Consideration #3: "a User already assigned to someone must not be added
-- to someone else" — this means each ASSIGNEE can only have ONE approver.
-- An approver, however, can supervise multiple people (that is standard org-chart).
-- The UNIQUE KEY on assignee_emp_id enforces this.

CREATE TABLE IF NOT EXISTS `approver_assignments` (
    `assignment_id`   INT(11)   NOT NULL AUTO_INCREMENT,
    `assignee_emp_id` INT(11)   NOT NULL COMMENT 'Employee who needs approval',
    `approver_emp_id` INT(11)   NOT NULL COMMENT 'Employee who approves',
    `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`assignment_id`),
    UNIQUE  KEY `uq_assignee`  (`assignee_emp_id`),   -- one approver per person
    KEY `idx_approver` (`approver_emp_id`),
    CONSTRAINT `aa_ibfk_assignee`  FOREIGN KEY (`assignee_emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE,
    CONSTRAINT `aa_ibfk_approver`  FOREIGN KEY (`approver_emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Step 3: Ensure dtr_records exists (from previous migration) ───────────────
CREATE TABLE IF NOT EXISTS `dtr_records` (
    `dtr_id`          INT(11)   NOT NULL AUTO_INCREMENT,
    `emp_id`          INT(11)   NOT NULL,
    `shift_sched_id`  INT(11)   NOT NULL,
    `date`            DATE      NOT NULL,
    `status`          ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending',
    `submitted_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`dtr_id`),
    UNIQUE  KEY `uq_emp_date`    (`emp_id`, `date`),
    KEY `idx_emp`                (`emp_id`),
    KEY `idx_shift`              (`shift_sched_id`),
    CONSTRAINT `dtr_ibfk_emp`   FOREIGN KEY (`emp_id`)         REFERENCES `employees`       (`emp_id`) ON DELETE CASCADE,
    CONSTRAINT `dtr_ibfk_shift` FOREIGN KEY (`shift_sched_id`) REFERENCES `shift_schedules` (`shift_sched_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ── Step 4: Ensure leave_records has a status column ─────────────────────────
ALTER TABLE `leave_records`
    ADD COLUMN IF NOT EXISTS `status` ENUM('pending','approved','declined') NOT NULL DEFAULT 'pending'
    AFTER `submitted_at`;

-- ── Step 5: Auto-approve existing records for exempt work groups ──────────────
-- Auto-approval applies to: BOD (level 0), Administrative (level 0), Executive (level 1).
-- The threshold is hierarchy_level <= 1.
UPDATE `dtr_records` dr
JOIN   `employees`   e  ON e.emp_id         = dr.emp_id
JOIN   `work_groups` wg ON wg.work_group_id  = e.work_group_id
SET    dr.status = 'approved'
WHERE  wg.hierarchy_level <= 1
  AND  dr.status = 'pending';

UPDATE `leave_records` lr
JOIN   `employees`   e  ON e.emp_id         = lr.emp_id
JOIN   `work_groups` wg ON wg.work_group_id  = e.work_group_id
SET    lr.status = 'approved'
WHERE  wg.hierarchy_level <= 1
  AND  lr.status = 'pending';

-- ── Step 6: Remediation — if migration was already run with old levels ─────────
-- If Executive was previously set to hierarchy_level = 0, correct it to 1.
-- Safe to re-run; does nothing if already correct.
UPDATE `work_groups` SET `hierarchy_level` = 1 WHERE `work_group_name` = 'Executive' AND `hierarchy_level` = 0;