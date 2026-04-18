-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 22, 2026 at 07:34 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `clockwise`
--

-- --------------------------------------------------------

--
-- Table structure for table `approver_assignments`
--

CREATE TABLE `approver_assignments` (
  `assignment_id` int(11) NOT NULL,
  `assignee_emp_id` int(11) NOT NULL COMMENT 'Employee who needs approval',
  `approver_emp_id` int(11) NOT NULL COMMENT 'Employee who approves',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `approver_assignments`
--

INSERT INTO `approver_assignments` (`assignment_id`, `assignee_emp_id`, `approver_emp_id`, `created_at`) VALUES
(2, 21, 20, '2026-03-01 04:01:23'),
(3, 26, 20, '2026-03-01 04:01:23'),
(4, 27, 15, '2026-03-01 04:01:23'),
(5, 22, 15, '2026-03-01 04:01:23'),
(6, 23, 25, '2026-03-01 04:01:23'),
(29, 36, 15, '2026-03-01 04:16:36'),
(30, 37, 15, '2026-03-01 04:16:36'),
(31, 38, 15, '2026-03-01 04:16:36'),
(32, 39, 15, '2026-03-01 04:16:36'),
(33, 40, 15, '2026-03-01 04:16:36'),
(37, 18, 15, '2026-03-09 11:54:36'),
(38, 46, 16, '2026-03-20 11:16:18');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_name` varchar(50) NOT NULL,
  `dept_desc` varchar(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_name`, `dept_desc`) VALUES
(1, 'HR', 'Human Resources: Handles recruitment, onboarding, training, employee relations, and labor compliance'),
(2, 'Finance', 'Finance: Manages budgeting, accounting, payroll, tax compliance, and financial reporting'),
(3, 'IT', 'Information Technology: Oversees infrastructure, software support, cybersecurity, and systems development'),
(4, 'Marketing', 'Marketing: Responsible for brand management, campaigns, and sales support'),
(5, 'Operations', 'Operations: Manages workflow, production, logistics, and service delivery'),
(6, 'Customer Service', 'Customer Service: Handles client support, complaints, and satisfaction metrics'),
(7, 'Legal', 'Legal: Manages contracts, corporate governance, and regulatory compliance'),
(8, 'Procurement', 'Procurement: Oversees purchasing, vendor management, inventory, and supply chain'),
(9, 'R&D', 'Research & Development: Focuses on product innovation, process improvement, and testing'),
(10, 'Administration', 'Administration: Manages facilities, clerical support, and records management');

-- --------------------------------------------------------

--
-- Table structure for table `dtr_records`
--

CREATE TABLE `dtr_records` (
  `dtr_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `shift_sched_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_records`
--

INSERT INTO `dtr_records` (`dtr_id`, `emp_id`, `shift_sched_id`, `date`, `status`, `submitted_at`, `updated_at`) VALUES
(1, 17, 1, '2026-03-01', 'approved', '2026-03-01 03:36:41', '2026-03-01 03:37:06'),
(2, 20, 1, '2026-02-25', 'approved', '2026-02-25 09:05:00', '2026-03-01 04:01:34'),
(3, 20, 1, '2026-02-26', 'approved', '2026-02-26 09:10:00', '2026-03-01 04:01:34'),
(4, 21, 1, '2026-02-25', 'approved', '2026-02-25 09:15:00', '2026-03-01 04:01:34'),
(5, 26, 4, '2026-02-27', 'approved', '2026-02-27 10:00:00', '2026-03-01 04:02:23'),
(6, 27, 4, '2026-02-27', 'pending', '2026-02-27 10:05:00', '2026-03-01 04:01:34'),
(7, 11, 3, '2026-02-28', 'approved', '2026-02-28 23:05:00', '2026-03-01 04:01:34'),
(8, 12, 1, '2026-02-28', 'approved', '2026-02-28 09:00:00', '2026-03-01 04:01:34'),
(9, 28, 1, '2026-03-02', 'approved', '2026-03-02 09:05:00', '2026-03-01 04:12:39'),
(10, 28, 1, '2026-03-03', 'approved', '2026-03-03 09:01:00', '2026-03-01 04:12:39'),
(11, 28, 1, '2026-03-04', 'pending', '2026-03-04 09:10:00', '2026-03-01 04:12:39'),
(12, 28, 1, '2026-03-05', 'pending', '2026-03-05 09:00:00', '2026-03-01 04:12:39'),
(13, 28, 1, '2026-03-06', 'pending', '2026-03-06 09:05:00', '2026-03-01 04:12:39'),
(14, 29, 1, '2026-03-02', 'pending', '2026-03-02 09:15:00', '2026-03-01 04:12:39'),
(15, 29, 1, '2026-03-03', 'pending', '2026-03-03 09:10:00', '2026-03-01 04:12:39'),
(16, 29, 1, '2026-03-04', 'pending', '2026-03-04 09:20:00', '2026-03-01 04:12:39'),
(17, 29, 1, '2026-03-05', 'pending', '2026-03-05 09:05:00', '2026-03-01 04:12:39'),
(18, 29, 1, '2026-03-06', 'pending', '2026-03-06 09:12:00', '2026-03-01 04:12:39'),
(19, 31, 2, '2026-03-02', 'pending', '2026-03-02 15:05:00', '2026-03-01 04:12:39'),
(20, 31, 2, '2026-03-03', 'pending', '2026-03-03 15:01:00', '2026-03-01 04:12:39'),
(21, 31, 2, '2026-03-04', 'pending', '2026-03-04 15:10:00', '2026-03-01 04:12:39'),
(22, 31, 2, '2026-03-05', 'pending', '2026-03-05 15:00:00', '2026-03-01 04:12:39'),
(23, 31, 2, '2026-03-06', 'pending', '2026-03-06 15:05:00', '2026-03-01 04:12:39'),
(24, 35, 1, '2026-03-02', 'approved', '2026-03-02 09:00:00', '2026-03-01 04:12:39'),
(25, 35, 1, '2026-03-03', 'approved', '2026-03-03 09:00:00', '2026-03-01 04:12:39'),
(26, 35, 1, '2026-03-04', 'approved', '2026-03-04 09:00:00', '2026-03-01 04:12:39'),
(27, 35, 1, '2026-03-05', 'pending', '2026-03-05 09:00:00', '2026-03-01 04:12:39'),
(28, 35, 1, '2026-03-06', 'pending', '2026-03-06 09:00:00', '2026-03-01 04:12:39'),
(29, 36, 1, '2026-03-01', 'approved', '2026-03-01 09:05:00', '2026-03-01 04:16:41'),
(30, 36, 1, '2026-03-02', 'approved', '2026-03-02 09:01:00', '2026-03-01 04:16:41'),
(31, 36, 1, '2026-03-03', 'pending', '2026-03-03 09:10:00', '2026-03-01 04:16:41'),
(32, 36, 1, '2026-03-04', 'pending', '2026-03-04 09:00:00', '2026-03-01 04:16:41'),
(33, 36, 1, '2026-03-05', 'pending', '2026-03-05 09:05:00', '2026-03-01 04:16:41'),
(34, 37, 1, '2026-03-01', 'approved', '2026-03-01 09:00:00', '2026-03-01 04:16:41'),
(35, 37, 1, '2026-03-02', 'pending', '2026-03-02 09:00:00', '2026-03-01 04:16:41'),
(36, 37, 1, '2026-03-03', 'pending', '2026-03-03 09:00:00', '2026-03-01 04:16:41'),
(37, 37, 1, '2026-03-04', 'pending', '2026-03-04 09:00:00', '2026-03-01 04:16:41'),
(38, 37, 1, '2026-03-05', 'pending', '2026-03-05 09:00:00', '2026-03-01 04:16:41'),
(39, 38, 2, '2026-03-01', 'pending', '2026-03-01 15:05:00', '2026-03-01 04:16:41'),
(40, 38, 2, '2026-03-02', 'pending', '2026-03-02 15:01:00', '2026-03-01 04:16:41'),
(41, 38, 2, '2026-03-03', 'pending', '2026-03-03 15:10:00', '2026-03-01 04:16:41'),
(42, 38, 2, '2026-03-04', 'pending', '2026-03-04 15:00:00', '2026-03-01 04:16:41'),
(43, 38, 2, '2026-03-05', 'pending', '2026-03-05 15:05:00', '2026-03-01 04:16:41'),
(44, 40, 3, '2026-03-01', 'pending', '2026-03-01 23:05:00', '2026-03-01 04:16:41'),
(45, 40, 3, '2026-03-02', 'pending', '2026-03-02 23:01:00', '2026-03-01 04:16:41'),
(46, 40, 3, '2026-03-03', 'pending', '2026-03-03 23:10:00', '2026-03-01 04:16:41'),
(47, 40, 3, '2026-03-04', 'pending', '2026-03-04 23:00:00', '2026-03-01 04:16:41'),
(48, 40, 3, '2026-03-05', 'pending', '2026-03-05 23:05:00', '2026-03-01 04:16:41'),
(69, 17, 1, '2026-03-02', 'pending', '2026-03-01 09:52:54', '2026-03-01 09:52:54');

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `emp_id` int(11) NOT NULL,
  `emp_first_name` varchar(128) NOT NULL,
  `emp_middle_name` varchar(128) DEFAULT NULL,
  `emp_last_name` varchar(128) NOT NULL,
  `emp_birthday` date DEFAULT NULL,
  `emp_email` varchar(128) NOT NULL,
  `emp_username` varchar(128) NOT NULL,
  `emp_password` varchar(256) NOT NULL,
  `dept_id` int(11) NOT NULL,
  `work_group_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `shift_sched_id` int(11) NOT NULL,
  `approver_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`emp_id`, `emp_first_name`, `emp_middle_name`, `emp_last_name`, `emp_birthday`, `emp_email`, `emp_username`, `emp_password`, `dept_id`, `work_group_id`, `role_id`, `shift_sched_id`, `approver_id`, `created_at`, `updated_at`) VALUES
(11, 'James', NULL, 'Ramos', NULL, 'james.ramos@example.com', 'jramos', '', 3, 3, NULL, 3, 0, '2026-02-21 09:46:13', '2026-02-21 09:46:13'),
(12, 'Marco', 'James', 'Maningiko', '1995-03-12', 'marco.maningiko@example.com', 'mjmaningiko1995', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 2, 2, NULL, 1, 0, '2026-02-22 08:30:16', '2026-02-22 08:30:16'),
(14, 'Admin', 'Admin', 'Admin', '2018-03-15', '', 'aaadmin2018', '$2y$10$zMQ1NNuwSgBxByCFey0.Bev1.cRnjcM3WwiD0HGdK430GBcjjdKPa', 1, 5, 1, 1, 0, '2026-02-22 12:21:43', '2026-02-22 12:21:43'),
(15, 'Aaron', 'Go', 'Go', '2026-02-22', '', 'aggo2026', '$2y$10$mZWbph8rEqZdoLii.p.mNONcNGV9DxiGmv0wSamGS637b7u3fZxXG', 3, 3, 2, 1, 0, '2026-02-22 12:26:52', '2026-02-22 12:26:52'),
(16, 'Kool', 'Ma', 'Ja', '1983-07-07', '', 'kmja1983', '$2y$10$37yW8zAySILhh3PIPFwTeeRV424ao8HMkr2T9iyThu9.jboxlNzWy', 3, 4, 2, 1, 0, '2026-03-01 03:08:11', '2026-03-01 03:08:11'),
(17, 'Jool', 'Ki', 'Lo', '1980-12-29', '', 'jklo1980', '$2y$10$FeXY7Ed1cJkrlYzinaLszOQo6dAvSzxzG1Z2GmPNCXDL.g0rBB/nK', 3, 1, 2, 1, 0, '2026-03-01 03:13:14', '2026-03-01 03:13:14'),
(18, 'Khilua', 'Maki', 'Asagi', '1993-10-19', '', 'kmasagi1993', '$2y$10$g3r.bqQWRLMBKZkb00JrWuc3FmtX5u3RScQYPkT.POT3uJnae5V7C', 3, 2, 2, 1, 0, '2026-03-01 03:15:11', '2026-03-01 03:15:11'),
(19, 'A', 'D', 'Min', '1990-10-17', '', 'admin1990', '$2y$10$c5ZLkDBi5aLnaUzpRiSsP.a/VTIkbQeXJq7U.AFd/2vkX3uCLn4sm', 10, 5, 2, 1, 0, '2026-03-01 03:40:12', '2026-03-01 03:40:12'),
(20, 'Sarah', 'Jane', 'Connor', '1985-05-12', 'sarah.connor@example.com', 'sconnor', '$2y$10$abcdefghijklmnopqrstuv', 3, 3, 2, 1, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(21, 'John', 'T', 'Doe', '1990-01-01', 'john.doe@example.com', 'jdoe', '$2y$10$abcdefghijklmnopqrstuv', 2, 2, 2, 1, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(22, 'Ellen', 'L', 'Ripley', '1982-10-25', 'ellen.ripley@example.com', 'eripley', '$2y$10$abcdefghijklmnopqrstuv', 5, 1, 2, 2, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(23, 'Marty', 'S', 'McFly', '1998-06-15', 'marty.mcfly@example.com', 'mmcfly', '$2y$10$abcdefghijklmnopqrstuv', 4, 4, 2, 1, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(24, 'Dana', 'K', 'Scully', '1988-02-23', 'dana.scully@example.com', 'dscully', '$2y$10$abcdefghijklmnopqrstuv', 9, 3, 2, 1, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(25, 'Fox', 'W', 'Mulder', '1986-10-13', 'fox.mulder@example.com', 'fmulder', '$2y$10$abcdefghijklmnopqrstuv', 9, 2, 2, 3, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(26, 'Thomas', 'A', 'Anderson', '1992-09-11', 'neo@example.com', 'neo', '$2y$10$abcdefghijklmnopqrstuv', 3, 1, 2, 4, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(27, 'Trinity', 'M', 'Moss', '1994-04-04', 'trinity@example.com', 'trinity', '$2y$10$abcdefghijklmnopqrstuv', 3, 2, 2, 4, 0, '2026-03-01 04:01:12', '2026-03-01 04:01:12'),
(28, 'Alice', 'V', 'Wonder', '1995-03-10', 'alice.w@example.com', 'awonder', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 1, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(29, 'Bob', 'D', 'Builder', '1992-07-22', 'bob.b@example.com', 'bbuilder', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 1, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(30, 'Charlie', 'S', 'Chocolate', '1990-11-05', 'charlie.c@example.com', 'cchocolate', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 2, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(31, 'David', 'G', 'Goliath', '1988-01-15', 'david.g@example.com', 'dgoliath', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 5, 1, 2, 2, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(32, 'Eve', 'M', 'Garden', '1994-05-30', 'eve.g@example.com', 'egarden', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 5, 1, 2, 3, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(33, 'Frank', 'L', 'Stein', '1985-10-31', 'frank.s@example.com', 'fstein', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 9, 1, 2, 3, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(34, 'Grace', 'H', 'Hopper', '1991-12-09', 'grace.h@example.com', 'ghopper', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 1, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(35, 'Heidi', 'K', 'Klum', '1982-06-01', 'heidi.k@example.com', 'hklum', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 4, 1, 2, 1, 13, '2026-03-01 04:12:28', '2026-03-01 04:12:28'),
(36, 'Luke', 'S', 'Skywalker', '1995-05-04', 'luke.s@example.com', 'lskywalker', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 1, 15, '2026-03-01 04:16:30', '2026-03-01 04:16:30'),
(37, 'Leia', 'O', 'Organa', '1995-05-04', 'leia.o@example.com', 'lorgana', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 2, 1, 2, 1, 15, '2026-03-01 04:16:30', '2026-03-01 04:16:30'),
(38, 'Han', 'S', 'Solo', '1990-07-13', 'han.s@example.com', 'hsolo', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 5, 1, 2, 2, 15, '2026-03-01 04:16:30', '2026-03-01 04:16:30'),
(39, 'Lando', 'C', 'Calrissian', '1988-12-15', 'lando.c@example.com', 'lcalrissian', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 5, 1, 2, 2, 15, '2026-03-01 04:16:30', '2026-03-01 04:16:30'),
(40, 'Chew', 'B', 'Acca', '1980-01-01', 'chewie@example.com', 'cacca', '$2y$10$7rP124a5xnLVnE.0mOIRbuMg1LnMJNwgtFSxvU7Gh/TGKnWdHHXMC', 3, 1, 2, 3, 15, '2026-03-01 04:16:30', '2026-03-01 04:16:30'),
(41, 'Coo', 'L', 'Aid', '1985-06-25', '', 'claid1985', '$2y$10$Ra7S5eQvxAXlCO6gi76qO./OMYRBoBZH3J4zp.CCOM4AYZckSmXJm', 3, 3, 2, 1, 0, '2026-03-01 04:20:22', '2026-03-01 04:20:22'),
(42, 'Board', 'Of', 'Director', '1975-10-08', '', 'bodirector1975', '$2y$10$iNCqnfGE3avCPyhC9uIpH.JAxcfOm57oIMj7tFQwUnx8SwAuHdMoi', 10, 6, 2, 1, 0, '2026-03-01 09:58:11', '2026-03-01 09:58:11'),
(43, 'Gh', 'A', 'Fre', '1996-06-04', '', 'gafre1996', '$2y$10$a28JrF/cs3o6xYZXgZb/bOtKjwG./wsS7hRoWc7bHBT1v8Wpp9Kve', 6, 5, 2, 1, 0, '2026-03-01 10:00:44', '2026-03-01 10:00:44'),
(44, 'Julia', 'G', 'Montes', '1990-06-06', '', 'jgmontes1990', '$2y$10$TFZkQCTpgw51fH/gCKILv.ohXABsoPqkHSXTnGWy3lv01Ebz8yfXi', 10, 6, 2, 1, 0, '2026-03-01 10:05:26', '2026-03-01 10:05:26'),
(46, 'Employee', 'With', 'NoLeaves', '1985-04-20', '', 'ewnoleaves1985', '$2y$10$JlErJV.O5opQQZb3O6ivq.cDwzzNv/jSpU5P9cXHr51S4OQ047LI6', 3, 3, 2, 1, 0, '2026-03-20 11:15:25', '2026-03-20 11:15:25');

-- --------------------------------------------------------

--
-- Table structure for table `leave_records`
--

CREATE TABLE `leave_records` (
  `leave_rec_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `date` date NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','declined') NOT NULL DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_records`
--

INSERT INTO `leave_records` (`leave_rec_id`, `emp_id`, `leave_type_id`, `date`, `submitted_at`, `status`) VALUES
(1, 17, 1, '2026-03-01', '2026-03-01 03:36:47', 'approved'),
(2, 21, 1, '2026-03-05', '2026-03-01 04:01:42', 'pending'),
(3, 22, 2, '2026-03-02', '2026-03-01 04:01:42', 'approved'),
(4, 23, 4, '2026-06-15', '2026-03-01 04:01:42', 'pending'),
(5, 26, 6, '2026-03-10', '2026-03-01 04:01:42', 'pending'),
(6, 18, 2, '2026-03-01', '2026-03-01 04:01:42', 'approved'),
(7, 28, 1, '2026-03-15', '2026-03-01 04:12:44', 'pending'),
(8, 29, 2, '2026-03-09', '2026-03-01 04:12:44', 'pending'),
(9, 30, 3, '2026-03-10', '2026-03-01 04:12:44', 'pending'),
(10, 31, 1, '2026-04-01', '2026-03-01 04:12:44', 'pending'),
(11, 32, 2, '2026-03-11', '2026-03-01 04:12:44', 'pending'),
(12, 33, 4, '2026-10-31', '2026-03-01 04:12:44', 'pending'),
(13, 34, 1, '2026-03-20', '2026-03-01 04:12:44', 'pending'),
(14, 35, 1, '2026-03-25', '2026-03-01 04:12:44', 'pending'),
(15, 28, 2, '2026-03-12', '2026-03-01 04:12:44', 'approved'),
(16, 36, 1, '2026-03-10', '2026-03-01 04:16:51', 'pending'),
(17, 37, 2, '2026-03-12', '2026-03-01 04:16:51', 'pending'),
(18, 38, 3, '2026-03-15', '2026-03-01 04:16:51', 'pending'),
(19, 39, 1, '2026-03-20', '2026-03-01 04:16:51', 'pending'),
(20, 40, 2, '2026-03-05', '2026-03-01 04:16:51', 'approved'),
(21, 17, 1, '2026-03-09', '2026-03-08 06:27:09', 'pending'),
(27, 18, 1, '2026-03-17', '2026-03-10 13:03:10', 'approved'),
(66, 46, 1, '2026-01-05', '2026-03-20 11:16:38', 'pending'),
(67, 46, 1, '2026-01-06', '2026-03-20 11:16:43', 'pending'),
(68, 46, 1, '2026-01-07', '2026-03-20 11:16:48', 'pending'),
(69, 46, 1, '2026-01-08', '2026-03-20 11:16:54', 'pending'),
(70, 46, 1, '2026-01-09', '2026-03-20 11:16:57', 'pending'),
(71, 46, 1, '2026-01-12', '2026-03-20 11:17:01', 'pending'),
(72, 46, 1, '2026-01-13', '2026-03-20 11:17:06', 'pending'),
(73, 46, 1, '2026-01-14', '2026-03-20 11:17:14', 'pending'),
(74, 46, 1, '2026-01-15', '2026-03-20 11:17:23', 'pending'),
(75, 46, 1, '2026-01-16', '2026-03-20 11:17:26', 'pending'),
(76, 46, 1, '2026-01-19', '2026-03-20 11:17:31', 'pending'),
(77, 46, 1, '2026-01-20', '2026-03-20 11:17:36', 'pending'),
(78, 46, 1, '2026-01-21', '2026-03-20 11:17:39', 'pending'),
(79, 46, 1, '2026-01-22', '2026-03-20 11:17:42', 'pending'),
(80, 46, 1, '2026-01-23', '2026-03-20 11:17:45', 'pending'),
(81, 46, 5, '2026-01-26', '2026-03-20 11:18:19', 'pending'),
(82, 46, 2, '2026-01-27', '2026-03-20 11:18:29', 'pending'),
(83, 46, 2, '2026-01-28', '2026-03-20 11:18:33', 'pending'),
(84, 46, 2, '2026-01-29', '2026-03-20 11:18:37', 'pending'),
(85, 46, 2, '2026-01-30', '2026-03-20 11:18:40', 'pending'),
(86, 46, 2, '2026-01-31', '2026-03-20 11:18:44', 'pending'),
(87, 46, 3, '2026-02-02', '2026-03-20 11:18:49', 'pending'),
(88, 46, 3, '2026-02-03', '2026-03-20 11:18:56', 'pending'),
(89, 46, 3, '2026-02-04', '2026-03-20 11:19:00', 'pending'),
(90, 46, 5, '2026-02-09', '2026-03-21 07:08:23', 'pending'),
(91, 46, 5, '2026-02-10', '2026-03-21 07:08:29', 'pending'),
(92, 46, 5, '2026-02-11', '2026-03-21 07:08:37', 'pending'),
(93, 46, 5, '2026-02-12', '2026-03-21 07:08:41', 'pending'),
(94, 46, 5, '2026-02-13', '2026-03-21 07:08:47', 'pending'),
(95, 46, 5, '2026-02-16', '2026-03-21 07:08:53', 'pending'),
(96, 46, 5, '2026-02-17', '2026-03-21 07:08:56', 'pending'),
(97, 46, 5, '2026-02-18', '2026-03-21 07:09:01', 'pending'),
(98, 46, 5, '2026-02-19', '2026-03-21 07:09:05', 'pending'),
(99, 46, 5, '2026-02-20', '2026-03-21 07:09:12', 'pending'),
(100, 46, 5, '2026-02-23', '2026-03-21 07:09:18', 'pending'),
(101, 46, 5, '2026-02-24', '2026-03-21 07:09:22', 'pending'),
(102, 46, 5, '2026-02-25', '2026-03-21 07:09:27', 'pending'),
(103, 46, 5, '2026-02-26', '2026-03-21 07:09:31', 'pending'),
(105, 46, 6, '2026-05-04', '2026-03-21 07:10:18', 'pending'),
(106, 46, 6, '2026-05-05', '2026-03-21 07:10:22', 'pending'),
(107, 46, 6, '2026-05-06', '2026-03-21 07:10:25', 'pending'),
(108, 46, 6, '2026-05-07', '2026-03-21 07:10:29', 'pending'),
(109, 46, 6, '2026-05-08', '2026-03-21 07:10:32', 'pending'),
(110, 46, 6, '2026-05-11', '2026-03-21 07:10:36', 'pending'),
(111, 46, 6, '2026-05-12', '2026-03-21 07:10:39', 'pending'),
(112, 46, 6, '2026-05-13', '2026-03-21 07:10:43', 'pending'),
(113, 46, 6, '2026-05-14', '2026-03-21 07:10:47', 'pending'),
(114, 46, 6, '2026-05-15', '2026-03-21 07:10:50', 'pending'),
(115, 46, 6, '2026-05-18', '2026-03-21 07:10:54', 'pending'),
(116, 46, 6, '2026-05-19', '2026-03-21 07:11:00', 'pending'),
(117, 46, 6, '2026-05-20', '2026-03-21 07:11:07', 'pending'),
(118, 46, 6, '2026-05-21', '2026-03-21 07:11:19', 'pending'),
(119, 46, 6, '2026-05-22', '2026-03-21 07:11:26', 'pending'),
(120, 16, 1, '2026-03-03', '2026-03-21 07:51:55', 'approved'),
(124, 16, 5, '2026-03-02', '2026-03-21 08:28:27', 'approved'),
(125, 16, 6, '2026-03-04', '2026-03-21 08:29:14', 'approved');

-- --------------------------------------------------------

--
-- Table structure for table `leave_types`
--

CREATE TABLE `leave_types` (
  `leave_type_id` int(11) NOT NULL,
  `leave_type_name` varchar(50) NOT NULL,
  `leave_type_code` varchar(5) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_types`
--

INSERT INTO `leave_types` (`leave_type_id`, `leave_type_name`, `leave_type_code`) VALUES
(1, 'Vacation Leave', 'VL'),
(2, 'Sick Leave', 'SL'),
(3, 'Emergency Leave', 'EL'),
(4, 'Birthday Leave', 'BDay'),
(5, 'Leave Without Pay', 'NoPay'),
(6, 'Study Leave', 'EDU');

-- --------------------------------------------------------

--
-- Table structure for table `shift_schedules`
--

CREATE TABLE `shift_schedules` (
  `shift_sched_id` int(11) NOT NULL,
  `shift_sched_name` varchar(50) NOT NULL,
  `shift_sched_code` varchar(5) DEFAULT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shift_schedules`
--

INSERT INTO `shift_schedules` (`shift_sched_id`, `shift_sched_name`, `shift_sched_code`, `start_time`, `end_time`, `created_at`) VALUES
(1, 'Morning Shift', 'M', '08:00:00', '17:00:00', '2026-02-17 07:27:58'),
(2, 'Afternoon Shift', 'A', '14:00:00', '23:00:00', '2026-02-17 07:27:58'),
(3, 'Night Shift', 'N', '22:00:00', '07:00:00', '2026-02-17 07:27:58'),
(4, 'Malta Timezone', 'MAL', '16:00:00', '00:00:00', '2026-02-17 07:32:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(2, 'User');

-- --------------------------------------------------------

--
-- Table structure for table `work_groups`
--

CREATE TABLE `work_groups` (
  `work_group_id` int(11) NOT NULL,
  `work_group_name` varchar(50) NOT NULL,
  `hierarchy_level` tinyint(3) UNSIGNED NOT NULL DEFAULT 99 COMMENT '0=auto-approved (BOD/Admin/Executive),1=Executive,2=Managerial,3=Supervisory,4=Rank and File'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_groups`
--

INSERT INTO `work_groups` (`work_group_id`, `work_group_name`, `hierarchy_level`) VALUES
(1, 'Rank and File', 4),
(2, 'Supervisory', 3),
(3, 'Managerial', 2),
(4, 'Executive', 1),
(5, 'Administrative', 0),
(6, 'Board of Directors', 0);

-- --------------------------------------------------------

--
-- Table structure for table `work_group_leaves`
--

CREATE TABLE `work_group_leaves` (
  `work_group_id` int(11) NOT NULL,
  `work_group_name` varchar(50) NOT NULL,
  `leave_type_id` int(11) NOT NULL,
  `leave_type_quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `work_group_leaves`
--

INSERT INTO `work_group_leaves` (`work_group_id`, `work_group_name`, `leave_type_id`, `leave_type_quantity`) VALUES
(1, 'Rank and File', 1, 10),
(2, 'Rank and File', 2, 5),
(3, 'Rank and File', 3, 1),
(4, 'Rank and File', 4, 1),
(5, 'Rank and File', 5, 0),
(6, 'Rank and File', 6, 0),
(7, 'Supervisory', 1, 15),
(8, 'Supervisory', 2, 5),
(9, 'Supervisory', 3, 3),
(10, 'Supervisory', 4, 1),
(11, 'Supervisory', 5, 15),
(12, 'Supervisory', 6, 10),
(13, 'Managerial', 1, 15),
(14, 'Managerial', 2, 5),
(15, 'Managerial', 3, 3),
(16, 'Managerial', 4, 1),
(17, 'Managerial', 5, 15),
(18, 'Managerial', 6, 15),
(19, 'Executive', 1, 20),
(20, 'Executive', 2, 10),
(21, 'Executive', 3, 5),
(22, 'Executive', 4, 1),
(23, 'Executive', 5, 99),
(24, 'Executive', 6, 30),
(25, 'Administrative', 1, 15),
(26, 'Administrative', 2, 5),
(27, 'Administrative', 3, 3),
(28, 'Administrative', 4, 1),
(29, 'Administrative', 5, 15),
(30, 'Administrative', 6, 10),
(31, 'Board of Directors', 1, 30),
(32, 'Board of Directors', 2, 15),
(33, 'Board of Directors', 3, 10),
(34, 'Board of Directors', 4, 1),
(35, 'Board of Directors', 5, 0),
(36, 'Board of Directors', 6, 0);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `approver_assignments`
--
ALTER TABLE `approver_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_assignee` (`assignee_emp_id`),
  ADD KEY `idx_approver` (`approver_emp_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `dept_name` (`dept_name`);

--
-- Indexes for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD PRIMARY KEY (`dtr_id`),
  ADD UNIQUE KEY `uq_emp_date` (`emp_id`,`date`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `shift_sched_id` (`shift_sched_id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`emp_id`),
  ADD UNIQUE KEY `emp_username` (`emp_username`),
  ADD KEY `dept_id` (`dept_id`),
  ADD KEY `work_group_id` (`work_group_id`),
  ADD KEY `shift_sched_id` (`shift_sched_id`),
  ADD KEY `fk_employees_role` (`role_id`);

--
-- Indexes for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD PRIMARY KEY (`leave_rec_id`),
  ADD KEY `emp_id` (`emp_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- Indexes for table `leave_types`
--
ALTER TABLE `leave_types`
  ADD PRIMARY KEY (`leave_type_id`),
  ADD UNIQUE KEY `leave_type_name` (`leave_type_name`),
  ADD UNIQUE KEY `leave_type_code` (`leave_type_code`);

--
-- Indexes for table `shift_schedules`
--
ALTER TABLE `shift_schedules`
  ADD PRIMARY KEY (`shift_sched_id`),
  ADD UNIQUE KEY `shift_sched_name` (`shift_sched_name`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `work_groups`
--
ALTER TABLE `work_groups`
  ADD PRIMARY KEY (`work_group_id`);

--
-- Indexes for table `work_group_leaves`
--
ALTER TABLE `work_group_leaves`
  ADD PRIMARY KEY (`work_group_id`),
  ADD KEY `leave_type_id` (`leave_type_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `approver_assignments`
--
ALTER TABLE `approver_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `dtr_records`
--
ALTER TABLE `dtr_records`
  MODIFY `dtr_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `emp_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `leave_records`
--
ALTER TABLE `leave_records`
  MODIFY `leave_rec_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=175;

--
-- AUTO_INCREMENT for table `leave_types`
--
ALTER TABLE `leave_types`
  MODIFY `leave_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `shift_schedules`
--
ALTER TABLE `shift_schedules`
  MODIFY `shift_sched_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `work_groups`
--
ALTER TABLE `work_groups`
  MODIFY `work_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `work_group_leaves`
--
ALTER TABLE `work_group_leaves`
  MODIFY `work_group_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `approver_assignments`
--
ALTER TABLE `approver_assignments`
  ADD CONSTRAINT `aa_ibfk_approver` FOREIGN KEY (`approver_emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `aa_ibfk_assignee` FOREIGN KEY (`assignee_emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE;

--
-- Constraints for table `dtr_records`
--
ALTER TABLE `dtr_records`
  ADD CONSTRAINT `dtr_records_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`),
  ADD CONSTRAINT `dtr_records_ibfk_2` FOREIGN KEY (`shift_sched_id`) REFERENCES `shift_schedules` (`shift_sched_id`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`),
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`work_group_id`) REFERENCES `work_group_leaves` (`work_group_id`),
  ADD CONSTRAINT `employees_ibfk_3` FOREIGN KEY (`shift_sched_id`) REFERENCES `shift_schedules` (`shift_sched_id`),
  ADD CONSTRAINT `fk_employees_role` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`role_id`);

--
-- Constraints for table `leave_records`
--
ALTER TABLE `leave_records`
  ADD CONSTRAINT `leave_records_ibfk_1` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`),
  ADD CONSTRAINT `leave_records_ibfk_2` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`);

--
-- Constraints for table `work_group_leaves`
--
ALTER TABLE `work_group_leaves`
  ADD CONSTRAINT `work_group_leaves_ibfk_1` FOREIGN KEY (`leave_type_id`) REFERENCES `leave_types` (`leave_type_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
