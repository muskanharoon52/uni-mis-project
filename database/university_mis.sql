-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 01, 2026 at 09:33 AM
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
-- Database: `university_mis`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_confirm_admission` (IN `p_application_id` INT, IN `p_current_semester_id` INT, IN `p_batch_year` SMALLINT, OUT `p_student_id` INT)   BEGIN
    DECLARE v_program_id INT;
    DECLARE v_session_id INT;

    SELECT program_id, session_id INTO v_program_id, v_session_id
    FROM admission_applications
    WHERE application_id = p_application_id
      AND application_status IN ('Approved','Under Review','Submitted');

    INSERT INTO students (
        application_id, full_name, father_name, cnic_or_bform, dob, gender,
        contact_no, email, address, program_id, admission_session_id,
        current_session_id, current_semester_id, batch_year, admission_date, status
    )
    SELECT
        application_id, full_name, father_name, cnic_or_bform, dob, gender,
        contact_no, email, address, program_id, session_id,
        session_id, p_current_semester_id, p_batch_year, CURDATE(), 'Active'
    FROM admission_applications
    WHERE application_id = p_application_id;

    SET p_student_id = LAST_INSERT_ID();

    UPDATE admission_applications
    SET application_status = 'Admitted'
    WHERE application_id = p_application_id;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_sessions`
--

CREATE TABLE `academic_sessions` (
  `id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `session_code` varchar(20) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `is_current` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_sessions`
--

INSERT INTO `academic_sessions` (`id`, `session_name`, `session_code`, `start_date`, `end_date`, `is_current`, `status`, `created_at`) VALUES
(1, 'Fall 2026', 'F2026', '2026-09-01', '2026-12-31', 1, 'active', '2026-07-30 19:30:19'),
(2, 'Spring 2027', 'S2027', '2027-02-01', '2027-05-31', 0, 'active', '2026-07-30 19:30:19');

-- --------------------------------------------------------

--
-- Table structure for table `acr_requests`
--

CREATE TABLE `acr_requests` (
  `id` int(11) NOT NULL,
  `request_type` varchar(50) NOT NULL,
  `student_ref` varchar(60) DEFAULT NULL,
  `application_id` int(11) DEFAULT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Applied') NOT NULL DEFAULT 'Pending',
  `requested_by` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT current_timestamp(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `acr_requests`
--

INSERT INTO `acr_requests` (`id`, `request_type`, `student_ref`, `application_id`, `student_name`, `old_value`, `new_value`, `status`, `requested_by`, `requested_at`, `remarks`) VALUES
(1, 'Section Change', 'STU-2026-AI02', 911, 'Sana Malik', 'Section C', 'Section C', 'Applied', 10, '2026-08-01 01:48:52', NULL),
(2, 'Department Transfer', 'STU-2026-AI02', 911, 'Sana Malik', 'Information Technology', 'Computer Science', 'Applied', 10, '2026-08-01 01:48:52', NULL),
(3, 'Program Change', 'STU-2026-AI02', 911, 'Sana Malik', 'BS Computer Science', 'BS Information Technology', 'Applied', 10, '2026-08-01 01:48:52', NULL),
(4, 'Course Add/Drop', 'STU-2026-AI02', 911, 'Sana Malik', 'AI-P1-S1-01, AI-P1-S2-01', 'AI-P1-S1-01, AI-P1-S2-01, DS-P1-S1-01', 'Applied', 10, '2026-08-01 01:48:53', NULL),
(5, 'Course Withdrawal', 'STU-2026-AI02', 911, 'Sana Malik', 'AI-P1-S1-01, AI-P1-S2-01, DS-P1-S1-01', 'AI-P1-S1-01, AI-P1-S2-01', 'Applied', 10, '2026-08-01 01:48:53', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `log_id` bigint(20) NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `reference_table` varchar(100) DEFAULT NULL,
  `reference_id` bigint(20) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `activity_logs`
--

INSERT INTO `activity_logs` (`log_id`, `module`, `action`, `reference_table`, `reference_id`, `performed_by`, `details`, `created_at`) VALUES
(1, 'Finance', 'Payment Received', 'payments', 1, 10, 'Amount: 21666.67', '2026-07-27 16:30:18'),
(2, 'Finance', 'Payment Received', 'payments', 2, 10, 'Amount: 23333.33', '2026-07-27 16:30:18'),
(3, 'Finance', 'Payment Received', 'payments', 3, 10, 'Amount: 26666.67', '2026-07-27 16:30:18'),
(4, 'Finance', 'Payment Received', 'payments', 4, 10, 'Amount: 28333.33', '2026-07-27 16:30:18'),
(5, 'Finance', 'Payment Received', 'payments', 5, 10, 'Amount: 23333.33', '2026-07-27 16:30:18'),
(6, 'Finance', 'Payment Received', 'payments', 6, 10, 'Amount: 25000.00', '2026-07-27 16:30:18'),
(7, 'Finance', 'Payment Received', 'payments', 7, 10, 'Amount: 28333.33', '2026-07-27 16:30:18'),
(8, 'Finance', 'Payment Received', 'payments', 8, 10, 'Amount: 21666.67', '2026-07-27 16:30:18'),
(9, 'Finance', 'Payment Received', 'payments', 1, 10, 'Amount: 25000.00', '2026-07-27 16:35:20'),
(10, 'Finance', 'Payment Received', 'payments', 2, 10, 'Amount: 25000.00', '2026-07-27 16:35:20'),
(11, 'Finance', 'Payment Received', 'payments', 3, 10, 'Amount: 78000.00', '2026-07-27 16:35:20'),
(12, 'Finance', 'Payment Received', 'payments', 4, 10, 'Amount: 50000.00', '2026-07-27 16:35:20'),
(13, 'Finance', 'Payment Received', 'payments', 5, 10, 'Amount: 72000.00', '2026-07-27 16:35:20'),
(14, 'Finance', 'Payment Received', 'payments', 6, 10, 'Amount: 75000.00', '2026-07-27 16:35:20'),
(15, 'Finance', 'Payment Received', 'payments', 1, 10, 'Amount: 21666.67', '2026-07-27 16:42:35'),
(16, 'Finance', 'Payment Received', 'payments', 2, 10, 'Amount: 23333.33', '2026-07-27 16:42:35'),
(17, 'Finance', 'Payment Received', 'payments', 3, 10, 'Amount: 26666.67', '2026-07-27 16:42:35'),
(18, 'Finance', 'Payment Received', 'payments', 4, 10, 'Amount: 28333.33', '2026-07-27 16:42:35'),
(19, 'Finance', 'Payment Received', 'payments', 5, 10, 'Amount: 23333.33', '2026-07-27 16:42:35'),
(20, 'Finance', 'Payment Received', 'payments', 6, 10, 'Amount: 25000.00', '2026-07-27 16:42:35'),
(21, 'Finance', 'Payment Received', 'payments', 7, 10, 'Amount: 28333.33', '2026-07-27 16:42:35'),
(22, 'Finance', 'Payment Received', 'payments', 8, 10, 'Amount: 21666.67', '2026-07-27 16:42:35'),
(23, 'Finance', 'Payment Received', 'payments', 9, 10, 'Amount: 25000.00', '2026-07-27 16:42:35'),
(24, 'Finance', 'Payment Reversed', 'payment_reversals', 1, 2, 'Refund for transaction 1', '2026-07-27 16:49:25'),
(25, 'Finance', 'Payment Reversed', 'payment_reversals', 2, 1, 'Refund for transaction 2', '2026-07-27 16:49:25'),
(26, 'Finance', 'Payment Reversed', 'payment_reversals', 3, 2, 'Refund for transaction 3', '2026-07-27 16:49:25'),
(27, 'Finance', 'Payment Received', 'payments', 10, 9, 'Amount: 20.00', '2026-07-27 20:06:11'),
(28, 'Finance', 'Payment Received', 'payments', 11, 9, 'Amount: 50000.00', '2026-07-27 20:09:14'),
(29, 'Finance', 'Soft Delete Fee Head', 'fee_heads', 6, NULL, 'cafetaria', '2026-07-28 05:39:22'),
(30, 'Finance', 'Payment Received', 'payments', 12, 9, 'Amount: 50.00', '2026-07-28 05:55:32'),
(31, 'Finance', 'Soft Delete Fee Head', 'fee_heads', 2, NULL, 'Lab Fee', '2026-07-30 18:24:30'),
(32, 'Dashboard', 'Page View', 'dashboard.php', NULL, 10, '/uni-mis-project/dashboard.php', '2026-08-01 05:50:22'),
(33, 'Reports', 'Page View', 'index.php', NULL, 10, '/uni-mis-project/reports/index.php', '2026-08-01 05:57:13'),
(34, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 05:59:30'),
(35, 'Reports', 'Page View', 'index.php', NULL, 10, '/uni-mis-project/reports/index.php', '2026-08-01 06:01:42'),
(36, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:02:01'),
(37, 'Reports', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/reports/index.php', '2026-08-01 06:02:10'),
(38, 'Reports', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/reports/index.php', '2026-08-01 06:03:55'),
(39, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:03:58'),
(40, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:04:25'),
(41, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:12:46'),
(42, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:13:05'),
(43, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:15:00'),
(44, 'Student Schedule Requests', 'Page View', 'history.php', NULL, 14, '/uni-mis-project/student_schedule_requests/history.php', '2026-08-01 06:15:09'),
(45, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:15:15'),
(46, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:16:55'),
(47, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:19:07'),
(48, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:19:40'),
(49, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:20:50'),
(50, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:21:43'),
(51, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:21:44'),
(52, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:21:46'),
(53, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:21:56'),
(54, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:21:57'),
(55, 'Reports', 'Page View', 'index.php', NULL, 10, '/uni-mis-project/reports/index.php', '2026-08-01 06:27:35'),
(56, 'Dashboard', 'Page View', 'dashboard.php', NULL, 10, '/uni-mis-project/dashboard.php', '2026-08-01 06:27:35'),
(57, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:28:23'),
(58, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 06:30:28'),
(59, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php', '2026-08-01 06:31:06'),
(60, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php?mode=individual', '2026-08-01 06:31:14'),
(61, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 06:31:22'),
(62, 'Academic Change Requests', 'Page View', 'department_transfer.php', NULL, 14, '/uni-mis-project/academic_change_requests/department_transfer.php', '2026-08-01 06:31:24'),
(63, 'Academic Change Requests', 'Page View', 'request_status.php', NULL, 14, '/uni-mis-project/academic_change_requests/request_status.php', '2026-08-01 06:31:27'),
(64, 'Faculty Registry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_registry/index.php', '2026-08-01 06:31:38'),
(65, 'Faculty Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_management/index.php', '2026-08-01 06:31:55'),
(66, 'Faculty Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_management/index.php?dept=1', '2026-08-01 06:32:00'),
(67, 'Faculty Enquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_enquiry/index.php', '2026-08-01 06:32:37'),
(68, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:33:15'),
(69, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 06:33:39'),
(70, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:34:27'),
(71, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 06:36:20'),
(72, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php', '2026-08-01 06:36:40'),
(73, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php?mode=individual', '2026-08-01 06:36:49'),
(74, 'Academic Change Requests', 'Page View', 'course_withdrawal.php', NULL, 14, '/uni-mis-project/academic_change_requests/course_withdrawal.php', '2026-08-01 06:37:16'),
(75, 'Faculty Registry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_registry/index.php', '2026-08-01 06:37:29'),
(76, 'Faculty Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_management/index.php', '2026-08-01 06:37:37'),
(77, 'Faculty Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_management/index.php?dept=5', '2026-08-01 06:37:39'),
(78, 'Faculty Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_management/index.php?dept=1', '2026-08-01 06:37:47'),
(79, 'Faculty Enquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_enquiry/index.php', '2026-08-01 06:38:09'),
(80, 'Faculty Enquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/faculty_enquiry/index.php?mode=bulk', '2026-08-01 06:38:14'),
(81, 'Courses', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/Courses/index.php', '2026-08-01 06:38:24'),
(82, 'Attendance', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/attendance/index.php', '2026-08-01 06:38:31'),
(83, 'Application', 'Page View', 'lms_applications.php', NULL, 14, '/uni-mis-project/lms_applications.php', '2026-08-01 06:39:02'),
(84, 'Timetable Management', 'Page View', 'view.php', NULL, 14, '/uni-mis-project/timetable_management/view.php', '2026-08-01 06:39:22'),
(85, 'Timetable Management', 'Page View', 'conflicts.php', NULL, 14, '/uni-mis-project/timetable_management/conflicts.php', '2026-08-01 06:39:37'),
(86, 'Timetable Management', 'Page View', 'adjust.php', NULL, 14, '/uni-mis-project/timetable_management/adjust.php', '2026-08-01 06:39:39'),
(87, 'Timetable Management', 'Page View', 'publish.php', NULL, 14, '/uni-mis-project/timetable_management/publish.php', '2026-08-01 06:39:42'),
(88, 'Student Schedule Requests', 'Page View', 'review.php', NULL, 14, '/uni-mis-project/student_schedule_requests/review.php?status=Pending', '2026-08-01 06:39:51'),
(89, 'Reports', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/reports/index.php', '2026-08-01 06:40:24'),
(90, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:41:01'),
(91, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:42:58'),
(92, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:55:28'),
(93, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 06:57:43'),
(94, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:08:32'),
(95, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:09:19'),
(96, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:10:55'),
(97, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:12:12'),
(98, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:13:11'),
(99, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:14:39'),
(100, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:15:52'),
(101, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 07:16:06'),
(102, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 07:18:17'),
(103, 'Students', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/students/index.php', '2026-08-01 07:19:51'),
(104, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php', '2026-08-01 07:19:56'),
(105, 'Academic Change Requests', 'Page View', 'department_transfer.php', NULL, 14, '/uni-mis-project/academic_change_requests/department_transfer.php', '2026-08-01 07:20:01'),
(106, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 07:20:02'),
(107, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 07:23:25'),
(108, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 07:25:54'),
(109, 'Academic Change Requests', 'Page View', 'department_transfer.php', NULL, 14, '/uni-mis-project/academic_change_requests/department_transfer.php', '2026-08-01 07:26:10'),
(110, 'Academic Change Requests', 'Page View', 'program_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/program_change.php', '2026-08-01 07:26:13'),
(111, 'Academic Change Requests', 'Page View', 'course_add_drop.php', NULL, 14, '/uni-mis-project/academic_change_requests/course_add_drop.php', '2026-08-01 07:26:14'),
(112, 'Academic Change Requests', 'Page View', 'course_withdrawal.php', NULL, 14, '/uni-mis-project/academic_change_requests/course_withdrawal.php', '2026-08-01 07:26:16'),
(113, 'Academic Change Requests', 'Page View', 'request_status.php', NULL, 14, '/uni-mis-project/academic_change_requests/request_status.php', '2026-08-01 07:26:17'),
(114, 'Academic Change Requests', 'Page View', 'course_withdrawal.php', NULL, 14, '/uni-mis-project/academic_change_requests/course_withdrawal.php', '2026-08-01 07:26:19'),
(115, 'Timetable Management', 'Page View', 'generate.php', NULL, 14, '/uni-mis-project/timetable_management/generate.php', '2026-08-01 07:26:29'),
(116, 'Academic Change Requests', 'Page View', 'request_status.php', NULL, 14, '/uni-mis-project/academic_change_requests/request_status.php', '2026-08-01 07:26:30'),
(117, 'Timetable Management', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/timetable_management/index.php', '2026-08-01 07:26:36'),
(118, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 07:26:56'),
(119, 'Timetable Management', 'Page View', 'view.php', NULL, 14, '/uni-mis-project/timetable_management/view.php', '2026-08-01 07:27:03'),
(120, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php', '2026-08-01 07:27:11'),
(121, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php', '2026-08-01 07:31:28'),
(122, 'Student Inquiry', 'Page View', 'index.php', NULL, 14, '/uni-mis-project/student_inquiry/index.php?mode=individual', '2026-08-01 07:31:33'),
(123, 'Academic Change Requests', 'Page View', 'section_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/section_change.php', '2026-08-01 07:31:40'),
(124, 'Academic Change Requests', 'Page View', 'department_transfer.php', NULL, 14, '/uni-mis-project/academic_change_requests/department_transfer.php', '2026-08-01 07:31:41'),
(125, 'Academic Change Requests', 'Page View', 'program_change.php', NULL, 14, '/uni-mis-project/academic_change_requests/program_change.php', '2026-08-01 07:31:42'),
(126, 'Dashboard', 'Page View', 'dashboard.php', NULL, 14, '/uni-mis-project/dashboard.php', '2026-08-01 07:32:00');

-- --------------------------------------------------------

--
-- Table structure for table `admission_applications`
--

CREATE TABLE `admission_applications` (
  `application_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `father_name` varchar(150) NOT NULL,
  `cnic_or_bform` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `previous_degree` varchar(50) DEFAULT NULL,
  `program` varchar(50) DEFAULT NULL,
  `obtained_marks` float DEFAULT NULL,
  `total_marks` float DEFAULT NULL,
  `percentage` float DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `applied_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `address` varchar(255) DEFAULT NULL,
  `program_id` int(11) DEFAULT 1,
  `session_id` int(11) DEFAULT NULL,
  `applied_semester_id` int(11) DEFAULT NULL,
  `application_status` enum('Submitted','Under Review','Approved','Rejected','Admitted','Cancelled') NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_applications`
--

INSERT INTO `admission_applications` (`application_id`, `full_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `previous_degree`, `program`, `obtained_marks`, `total_marks`, `percentage`, `password`, `status`, `applied_date`, `address`, `program_id`, `session_id`, `applied_semester_id`, `application_status`, `submitted_at`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(13, 'Muhammad Ali', 'Ahmed Ali', '42101-1234567-1', '2000-01-15', 'Male', '0310-1111111', 'ali@email.com', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'House #12, Street 5, Lahore', 1, 1, 1, 'Approved', '2026-07-26 12:10:32', NULL, NULL, NULL, '2026-07-26 07:10:32', '2026-07-26 07:10:32'),
(100, 'Ahmed Ali', 'Father of Ahmed Ali', '42101-1234567-100', '2003-05-15', 'Male', '0300-1000100', 'stu100@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 2, 6, 1, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(101, 'Sara Butt', 'Father of Sara Butt', '42101-1234567-101', '2003-05-15', 'Male', '0300-1000101', 'stu101@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 3, 5, 2, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(102, 'Usman Khan', 'Father of Usman Khan', '42101-1234567-102', '2003-05-15', 'Male', '0300-1000102', 'stu102@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 4, 6, 3, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(103, 'Hira Ahmed', 'Father of Hira Ahmed', '42101-1234567-103', '2003-05-15', 'Male', '0300-1000103', 'stu103@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 5, 5, 4, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(104, 'Bilal Hussain', 'Father of Bilal Hussain', '42101-1234567-104', '2003-05-15', 'Male', '0300-1000104', 'stu104@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 6, 6, 5, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(105, 'Zainab Noor', 'Father of Zainab Noor', '42101-1234567-105', '2003-05-15', 'Male', '0300-1000105', 'stu105@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 2, 5, 6, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(106, 'Muhammad Umer', 'Father of Muhammad Umer', '42101-1234567-106', '2003-05-15', 'Male', '0300-1000106', 'stu106@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 3, 6, 7, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(107, 'Ayesha Siddiqui', 'Father of Ayesha Siddiqui', '42101-1234567-107', '2003-05-15', 'Male', '0300-1000107', 'stu107@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 4, 5, 8, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(108, 'Farhan Iqbal', 'Father of Farhan Iqbal', '42101-1234567-108', '2003-05-15', 'Male', '0300-1000108', 'stu108@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 5, 6, 1, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(109, 'Maryam Khalid', 'Father of Maryam Khalid', '42101-1234567-109', '2003-05-15', 'Male', '0300-1000109', 'stu109@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 6, 5, 2, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(110, 'Waleed Aslam', 'Father of Waleed Aslam', '42101-1234567-110', '2003-05-15', 'Male', '0300-1000110', 'stu110@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 2, 6, 3, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(111, 'Nida Butt', 'Father of Nida Butt', '42101-1234567-111', '2003-05-15', 'Male', '0300-1000111', 'stu111@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 3, 5, 4, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(112, 'Student 37', 'Father of Student 37', '42101-1234567-112', '2003-05-15', 'Male', '0300-1000112', 'stu112@uni.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'Lahore', 4, 6, 5, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(113, 'Ali', 'khan', '63871357817132', '2003-02-10', 'Male', '6157351656152', 'ali@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'peshawar', 1, 5, 1, 'Admitted', '2026-07-27 21:29:01', 14, '2026-07-28 00:36:26', '', '2026-07-27 19:29:01', '2026-07-27 19:36:26'),
(114, 'Sajjal', 'Khan', '7823728832273', '2000-02-01', 'Female', '62736268283', 'sajjal@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-30 13:32:14', '..', 1, 5, 14, 'Approved', '2026-07-27 22:46:59', 10, '2026-07-30 19:23:08', NULL, '2026-07-27 20:46:59', '2026-07-30 14:23:08'),
(115, 'Maryam', 'Ahmed', '67131526612618', '2000-10-10', 'Female', '6127617263782', 'maryam@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'rejected', '2026-07-30 13:32:14', 'Pakistan', 2, 6, 2, 'Rejected', '2026-07-28 04:55:26', 10, '2026-07-30 19:18:05', '..', '2026-07-28 02:55:26', '2026-07-30 14:18:05'),
(116, 'Wareesha', 'Khan', '73652362536257', '2022-10-22', 'Female', '632326613', 'wareesha@gmai.com', NULL, NULL, NULL, NULL, NULL, NULL, 'pending', '2026-07-30 13:32:14', 'peshawar', 2, 5, 5, 'Admitted', '2026-07-28 07:57:12', 10, '2026-07-28 10:57:46', '', '2026-07-28 05:57:12', '2026-07-28 05:57:46'),
(120, 'noor', 'jahan', '7284678381892', '2000-05-10', 'Female', '03754782782', 'noorjahan@gmail.com', 'F.Sc (Pre-Medical)', 'BS English', 950, 1100, 86.3636, '$2y$10$4P34SceWiAHRnCwFT3qS.OzdmXcYfbU4kYp1obTldqaXlwtGk.85m', 'approved', '2026-07-30 11:06:23', 'peshawar', 1, NULL, NULL, 'Approved', '2026-07-30 16:06:23', 10, '2026-07-30 19:21:59', NULL, '2026-07-30 11:06:23', '2026-07-30 14:21:59'),
(121, 'umair', 'ali', '7827282387823', '2006-02-02', 'Male', '03747583219', 'manahil@gmail.com', 'F.Sc (Pre-Medical)', 'BS Computer Science', 950, 1100, 86.3636, '$2y$10$xRn1D97IuN4bv0TIyYDf5OnN26vfLR3W20BlDSOo0oTGjZzwY2TIG', '', '2026-07-30 11:34:35', 'peshawar', 1, NULL, NULL, '', '2026-07-30 16:34:35', NULL, NULL, NULL, '2026-07-30 11:34:35', '2026-07-30 15:03:42'),
(122, 'Ayesha', 'khan', '3827238238929', '2000-03-01', 'Female', '03239298392', 'Ayesha10@gmail.com', 'F.Sc (Pre-Medical)', 'BS Artificial Intelligence', 950, 1100, 86.3636, '$2y$10$HJfC0qOReAgqvCahz2PZkuIW9zfBbBJrx03wJ4Anl2yEo2iOmN.r2', '', '2026-07-30 11:42:18', 'peshawar', 1, NULL, NULL, '', '2026-07-30 16:42:18', NULL, NULL, NULL, '2026-07-30 11:42:18', '2026-07-30 14:59:11'),
(123, 'akash', 'ali', '8198219219912', '2000-10-01', 'Male', '03747583219', 'akash@gmail.com', 'I.Com', 'BS English', 950, 1100, 86.3636, '$2y$10$h68QX2Fz4AGm/CA1g.JF/eQIjro2AhIOrcV8eP2QhsxBVQtlof1xC', '', '2026-07-30 12:05:48', 'peshawar', 1, NULL, NULL, '', '2026-07-30 17:05:48', NULL, NULL, NULL, '2026-07-30 12:05:48', '2026-07-30 15:06:08'),
(124, 'aliya', 'Khan', '819289128199128', '2001-03-01', 'Female', '0389121288912', 'aliya@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-30 15:13:11', 'peshawar', 2, 6, 6, 'Approved', '2026-07-30 17:13:11', 10, '2026-07-30 20:23:22', NULL, '2026-07-30 15:13:11', '2026-07-30 15:23:22'),
(125, 'aleena', 'ali', '732773297112', '2000-05-10', 'Female', '038377743843', 'aleena@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-30 16:01:27', 'peshawar', 2, 5, 7, 'Approved', '2026-07-30 18:01:27', 51, '2026-07-30 21:29:56', NULL, '2026-07-30 16:01:27', '2026-07-30 16:29:56'),
(126, 'umama', 'khan', '8329923847274', '2000-10-02', 'Female', '03743673593', 'umama@gmail.com', 'F.Sc (Pre-Engineering)', 'BS Computer Science', 950, 1100, 86.3636, '$2y$10$vNE.n7KtoKNBAm/cWIJHW.ctY5Wi1aOk39Imc3t5mtYghYxqAd6ja', 'approved', '2026-07-30 13:21:39', 'peshawar', 1, NULL, NULL, 'Approved', '2026-07-30 18:21:39', 51, '2026-07-30 21:24:01', NULL, '2026-07-30 13:21:39', '2026-07-30 16:24:01'),
(127, 'hashir', 'ali', '8378274894719', '2000-02-01', 'Male', '03837984729', 'hashir@gmail.com', 'F.Sc (Pre-Medical)', 'BS Computer Science', 950, 1100, 86.3636, '$2y$10$zjeS0tI8wFz8DHgUTRZOR.F9KLvjZkmo.WQJhP7fNCJ.8E7Kp8B0S', 'approved', '2026-07-30 13:37:17', 'peshawar', 1, NULL, NULL, 'Approved', '2026-07-30 18:37:17', 51, '2026-07-30 21:38:46', NULL, '2026-07-30 13:37:17', '2026-07-30 16:38:46'),
(128, 'alina', 'khan', '7823728832433', '2000-02-02', 'Female', '038928923982', 'alina@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-30 19:18:30', 'peshawar', 5, 5, 1, 'Approved', '2026-07-30 21:18:30', 51, '2026-07-31 00:29:09', NULL, '2026-07-30 19:18:30', '2026-07-30 19:29:09'),
(129, 'aliya', 'sohail', '0903902039200', '2001-10-01', 'Female', '89328932883', 'aliya21@gmail.com', 'F.Sc (Pre-Engineering)', 'BS Software Engineering', 1000, 1100, 90.9091, '$2y$10$mOihrxC6bMC/aSVjGLAbT.fvwXbORFkc1v1qGR1rIJ2R6/XTzTtMK', 'approved', '2026-07-30 16:31:51', 'peshawar', 1, NULL, NULL, 'Approved', '2026-07-30 21:31:51', 9, '2026-07-31 00:57:37', NULL, '2026-07-30 16:31:51', '2026-07-30 20:14:37'),
(130, 'farah', 'khan', '78327823783282', '2000-10-01', 'Female', '03782182717821', 'farahkhan@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-31 17:15:52', 'peshawar', 2, 5, 1, 'Admitted', '2026-07-31 19:15:52', 51, '2026-07-31 22:16:30', NULL, '2026-07-31 17:15:52', '2026-07-31 17:21:37'),
(131, 'mashal', 'khan', '9328948398348', '2000-05-02', 'Female', '03189281929', 'mashal@gmail.com', 'F.Sc (Pre-Engineering)', 'BS Computer Science', 1000, 1100, 90.9091, '$2y$10$MGngof7/b7V/uZhLzlAb/uJRgJ.pKeD0r0Xnv8aNAwzInCHJTi2f6', 'approved', '2026-07-31 15:04:55', 'peshawar', 1, NULL, NULL, '', '2026-07-31 20:04:55', 51, '2026-07-31 23:05:27', NULL, '2026-07-31 15:04:55', '2026-07-31 18:05:40'),
(132, 'maria', 'khan', '8932782738273', '2000-10-01', 'Female', '03781271268', 'mariakh@gmail.com', 'F.Sc (Pre-Medical)', 'BS Software Engineering', 1000, 1100, 90.9091, '$2y$10$GyvxielWL/AQAZME5fNHmOXu5iYSYJZWzPwIZhy1r2lpQYOproLjS', 'approved', '2026-07-31 15:15:54', 'peshawar', 1, NULL, NULL, '', '2026-07-31 20:15:54', 51, '2026-07-31 23:17:36', NULL, '2026-07-31 15:15:54', '2026-07-31 18:18:03'),
(910, 'Ali Raza', 'Raza Ahmed', '42101-1234567-1', '2004-03-15', 'Male', '0300-1110001', 'ali.raza@demo.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-31 20:35:36', NULL, 5, 5, 1, 'Admitted', '2026-08-01 01:35:36', NULL, NULL, NULL, '2026-07-31 20:35:36', '2026-07-31 20:35:36'),
(911, 'Sana Malik', 'Malik Hussain', '42201-2345678-2', '2005-07-20', 'Female', '0300-1110002', 'sana.malik@demo.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-31 20:35:36', NULL, 5, 5, 1, 'Admitted', '2026-08-01 01:35:36', NULL, NULL, NULL, '2026-07-31 20:35:36', '2026-07-31 20:35:36'),
(912, 'Bilal Ahmed', 'Ahmed Nawaz', '42301-3456789-3', '2004-11-02', 'Male', '0300-1110003', 'bilal.ahmed@demo.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-31 20:35:36', NULL, 5, 5, 1, 'Admitted', '2026-08-01 01:35:36', NULL, NULL, NULL, '2026-07-31 20:35:36', '2026-07-31 20:35:36'),
(913, 'Hina Tariq', 'Tariq Mehmood', '42401-4567890-4', '2005-01-25', 'Female', '0300-1110004', 'hina.tariq@demo.edu', NULL, NULL, NULL, NULL, NULL, NULL, 'approved', '2026-07-31 20:35:36', NULL, 5, 5, 1, 'Admitted', '2026-08-01 01:35:36', NULL, NULL, NULL, '2026-07-31 20:35:36', '2026-07-31 20:35:36');

-- --------------------------------------------------------

--
-- Table structure for table `admission_applications_backup_2026`
--

CREATE TABLE `admission_applications_backup_2026` (
  `application_id` int(11) NOT NULL DEFAULT 0,
  `temp_application_no` varchar(30) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `father_name` varchar(150) NOT NULL,
  `cnic_or_bform` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `applied_semester_id` int(11) NOT NULL,
  `application_status` enum('Submitted','Under Review','Approved','Rejected','Admitted','Cancelled') NOT NULL DEFAULT 'Submitted',
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_applications_backup_2026`
--

INSERT INTO `admission_applications_backup_2026` (`application_id`, `temp_application_no`, `full_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `session_id`, `applied_semester_id`, `application_status`, `submitted_at`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(13, '', 'Muhammad Ali', 'Ahmed Ali', '42101-1234567-1', '2000-01-15', 'Male', '0310-1111111', 'ali@email.com', 'House #12, Street 5, Lahore', 1, 1, 1, 'Approved', '2026-07-26 12:10:32', NULL, NULL, NULL, '2026-07-26 07:10:32', '2026-07-26 07:10:32'),
(100, 'APP-0100', 'Ahmed Ali', 'Father of Ahmed Ali', '42101-1234567-100', '2003-05-15', 'Male', '0300-1000100', 'stu100@uni.edu', 'Lahore', 2, 6, 1, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(101, 'APP-0101', 'Sara Butt', 'Father of Sara Butt', '42101-1234567-101', '2003-05-15', 'Male', '0300-1000101', 'stu101@uni.edu', 'Lahore', 3, 5, 2, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(102, 'APP-0102', 'Usman Khan', 'Father of Usman Khan', '42101-1234567-102', '2003-05-15', 'Male', '0300-1000102', 'stu102@uni.edu', 'Lahore', 4, 6, 3, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(103, 'APP-0103', 'Hira Ahmed', 'Father of Hira Ahmed', '42101-1234567-103', '2003-05-15', 'Male', '0300-1000103', 'stu103@uni.edu', 'Lahore', 5, 5, 4, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(104, 'APP-0104', 'Bilal Hussain', 'Father of Bilal Hussain', '42101-1234567-104', '2003-05-15', 'Male', '0300-1000104', 'stu104@uni.edu', 'Lahore', 6, 6, 5, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(105, 'APP-0105', 'Zainab Noor', 'Father of Zainab Noor', '42101-1234567-105', '2003-05-15', 'Male', '0300-1000105', 'stu105@uni.edu', 'Lahore', 2, 5, 6, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(106, 'APP-0106', 'Muhammad Umer', 'Father of Muhammad Umer', '42101-1234567-106', '2003-05-15', 'Male', '0300-1000106', 'stu106@uni.edu', 'Lahore', 3, 6, 7, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(107, 'APP-0107', 'Ayesha Siddiqui', 'Father of Ayesha Siddiqui', '42101-1234567-107', '2003-05-15', 'Male', '0300-1000107', 'stu107@uni.edu', 'Lahore', 4, 5, 8, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(108, 'APP-0108', 'Farhan Iqbal', 'Father of Farhan Iqbal', '42101-1234567-108', '2003-05-15', 'Male', '0300-1000108', 'stu108@uni.edu', 'Lahore', 5, 6, 1, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(109, 'APP-0109', 'Maryam Khalid', 'Father of Maryam Khalid', '42101-1234567-109', '2003-05-15', 'Male', '0300-1000109', 'stu109@uni.edu', 'Lahore', 6, 5, 2, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(110, 'APP-0110', 'Waleed Aslam', 'Father of Waleed Aslam', '42101-1234567-110', '2003-05-15', 'Male', '0300-1000110', 'stu110@uni.edu', 'Lahore', 2, 6, 3, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(111, 'APP-0111', 'Nida Butt', 'Father of Nida Butt', '42101-1234567-111', '2003-05-15', 'Male', '0300-1000111', 'stu111@uni.edu', 'Lahore', 3, 5, 4, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(112, 'APP-0112', 'Student 37', 'Father of Student 37', '42101-1234567-112', '2003-05-15', 'Male', '0300-1000112', 'stu112@uni.edu', 'Lahore', 4, 6, 5, 'Approved', '2026-08-15 10:00:00', NULL, NULL, NULL, '2026-07-26 07:00:00', '2026-07-27 19:54:08'),
(113, 'APP-2026-45877', 'Ali', 'khan', '63871357817132', '2003-02-10', 'Male', '6157351656152', 'ali@gmail.com', 'peshawar', 1, 5, 1, 'Admitted', '2026-07-27 21:29:01', 14, '2026-07-28 00:36:26', '', '2026-07-27 19:29:01', '2026-07-27 19:36:26');

-- --------------------------------------------------------

--
-- Table structure for table `admission_fees`
--

CREATE TABLE `admission_fees` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `student_name` varchar(100) NOT NULL,
  `cnic` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 1000.00,
  `fee_challan_no` varchar(50) NOT NULL,
  `status` enum('pending','paid','cancelled') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_reference` varchar(100) DEFAULT NULL,
  `paid_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `due_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_fees`
--

INSERT INTO `admission_fees` (`id`, `application_id`, `student_name`, `cnic`, `email`, `phone`, `program`, `fee_amount`, `fee_challan_no`, `status`, `payment_method`, `payment_reference`, `paid_at`, `created_at`, `due_date`) VALUES
(1, '120', 'Noor', '4210112345678', 'noor@test.com', '03001112222', 'BS Computer Science', 1000.00, 'CH-2026-00001', 'paid', 'Bank Transfer', 'TRX89328', '2026-07-30 19:21:59', '2026-07-30 19:19:39', '2026-08-06 19:19:39'),
(2, '114', 'Sajjal', '7823728832273', 'sajjal@gmail.com', '62736268283', '', 1000.00, 'CH-2026-48666', 'paid', 'Cash', 'TRX392', '2026-07-30 19:23:08', '2026-07-30 19:23:08', '2026-08-06 19:23:08'),
(3, '122', 'Ayesha', '3827238238929', 'Ayesha10@gmail.com', '03239298392', 'BS Artificial Intelligence', 1000.00, 'CH-2026-45543', 'pending', NULL, NULL, NULL, '2026-07-30 19:59:11', '2026-08-06 19:59:11'),
(4, '121', 'umair', '7827282387823', 'manahil@gmail.com', '03747583219', 'BS Computer Science', 1000.00, 'CH-2026-53455', 'pending', NULL, NULL, NULL, '2026-07-30 20:03:42', '2026-08-06 20:03:42'),
(5, '123', 'akash', '8198219219912', 'akash@gmail.com', '03747583219', 'BS English', 1000.00, 'CH-2026-24108', 'pending', NULL, NULL, NULL, '2026-07-30 20:06:08', '2026-08-06 20:06:08'),
(6, '124', 'aliya', '819289128199128', 'aliya@gmail.com', '0389121288912', '', 1000.00, 'CH-2026-49286', 'paid', 'Cash', 'TXR781', '2026-07-30 20:23:22', '2026-07-30 20:23:22', '2026-08-06 20:23:22'),
(7, '126', 'umama', '8329923847274', 'umama@gmail.com', '03743673593', 'BS Computer Science', 1000.00, 'CH-2026-35886', 'paid', 'Cash', 'TRX8392', '2026-07-30 21:24:00', '2026-07-30 21:24:00', '2026-08-06 21:24:00'),
(8, '125', 'aleena', '732773297112', 'aleena@gmail.com', '038377743843', '', 1000.00, 'CH-2026-39103', 'paid', 'Cash', 'TRX782', '2026-07-30 21:29:56', '2026-07-30 21:29:56', '2026-08-06 21:29:56'),
(9, '127', 'hashir', '8378274894719', 'hashir@gmail.com', '03837984729', 'BS Computer Science', 1000.00, 'CH-2026-76355', 'paid', 'Cash', 'TRX8932', '2026-07-30 21:38:46', '2026-07-30 21:38:46', '2026-08-06 21:38:46');

-- --------------------------------------------------------

--
-- Table structure for table `admission_scholarships`
--

CREATE TABLE `admission_scholarships` (
  `scholarship_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `scholarship_type` enum('Merit','Need-based','Sports','Talent','Special','Other') NOT NULL DEFAULT 'Merit',
  `description` text DEFAULT NULL,
  `scholarship_name` varchar(200) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `amount` decimal(12,2) DEFAULT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Active','Expired','Cancelled') NOT NULL DEFAULT 'Pending',
  `application_status` enum('Submitted','Under Review','Approved','Rejected','Granted','Denied') DEFAULT 'Submitted',
  `approved_by` int(11) DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_scholarships`
--

INSERT INTO `admission_scholarships` (`scholarship_id`, `application_id`, `scholarship_type`, `description`, `scholarship_name`, `percentage`, `amount`, `duration`, `semester_id`, `session_id`, `status`, `application_status`, `approved_by`, `approved_date`, `rejection_reason`, `remarks`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Merit', NULL, 'Merit Scholarship - 50%', 50.00, NULL, 'Full Program', 1, 5, 'Active', 'Approved', NULL, '2026-01-20', NULL, 'Outstanding academic performance', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(2, NULL, 'Merit', NULL, 'Merit Scholarship - 40%', 40.00, NULL, 'Full Program', 1, 5, 'Active', 'Approved', NULL, '2026-01-20', NULL, 'Excellent grades in entrance test', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(3, NULL, 'Need-based', NULL, 'Need-based Financial Aid', 30.00, NULL, 'One Semester', 9, 5, 'Active', 'Approved', NULL, '2026-01-25', NULL, 'Based on financial need assessment', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(4, NULL, 'Need-based', NULL, 'Need-based Financial Aid - 25%', 25.00, NULL, 'One Semester', 9, 5, 'Active', 'Approved', NULL, '2026-01-25', NULL, 'Financial need verified', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(5, NULL, 'Sports', NULL, 'Sports Excellence Scholarship', 25.00, NULL, 'One Year', 17, 5, 'Active', 'Approved', NULL, '2026-01-30', NULL, 'Represented university in national sports', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(6, NULL, 'Sports', NULL, 'Sports Excellence - 20%', 20.00, NULL, 'One Year', 17, 5, 'Active', 'Approved', NULL, '2026-01-30', NULL, 'Represented university in athletics', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(7, NULL, 'Talent', NULL, 'Talent Scholarship - 20%', 20.00, NULL, 'Full Program', 25, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Under review by scholarship committee', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(8, NULL, 'Talent', NULL, 'Talent Scholarship - 15%', 15.00, NULL, 'Full Program', 25, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Waiting for additional documentation', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(9, NULL, 'Merit', NULL, 'Merit Scholarship - 30%', 30.00, NULL, 'Full Program', 33, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Awaiting verification of academic records', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(10, NULL, 'Merit', NULL, 'Merit Scholarship - 25%', 25.00, NULL, 'Full Program', 33, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Application received - Under initial review', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(11, NULL, 'Special', NULL, 'Special Scholarship - 35%', 35.00, NULL, 'One Semester', 2, 5, 'Active', 'Approved', NULL, '2026-02-01', NULL, 'Special consideration - Orphan student', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(12, NULL, 'Special', NULL, 'Special Scholarship - 30%', 30.00, NULL, 'One Semester', 2, 5, 'Approved', 'Approved', NULL, '2026-02-01', NULL, 'Special consideration - Disabled student', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(13, NULL, 'Merit', NULL, 'Merit Scholarship - 30%', 30.00, 30000.00, 'Full Program', 34, 5, 'Approved', 'Approved', NULL, '2026-02-10', NULL, 'Approved - Outstanding academic record', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(14, NULL, 'Merit', NULL, 'Merit Scholarship Renewal - 50%', 50.00, 50000.00, 'Full Program', 2, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Renewal application for Semester 2', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(15, NULL, 'Sports', NULL, 'Sports Scholarship Renewal', 25.00, 25000.00, 'One Year', 18, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Renewal for second year', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(17, 6712, 'Merit', '..', 'Scholarship1', 999.99, 500.00, '1 year', NULL, NULL, 'Pending', 'Submitted', NULL, '2026-02-02', 'dcalnkalncl', 'acsnjncanc', '2026-07-28 03:02:04', '2026-07-28 03:02:04'),
(19, 7612, 'Sports', '..', 'testing', 50.00, 50.00, '1 year', 5, 5, 'Active', 'Submitted', NULL, NULL, '', '', '2026-07-28 06:14:06', '2026-07-28 06:14:06');

-- --------------------------------------------------------

--
-- Table structure for table `admission_scholarship_applications`
--

CREATE TABLE `admission_scholarship_applications` (
  `id` int(11) NOT NULL,
  `scholarship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `marks_obtained` decimal(10,2) DEFAULT NULL,
  `total_marks` decimal(10,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `scholarship_percentage` decimal(5,2) DEFAULT 0.00,
  `scholarship_amount` decimal(12,2) DEFAULT 0.00,
  `fee_after_scholarship` decimal(12,2) DEFAULT 0.00,
  `status` enum('pending','approved','rejected','Under Review') DEFAULT 'pending',
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_scholarship_applications`
--

INSERT INTO `admission_scholarship_applications` (`id`, `scholarship_id`, `student_id`, `marks_obtained`, `total_marks`, `percentage`, `scholarship_percentage`, `scholarship_amount`, `fee_after_scholarship`, `status`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 1, 25, NULL, NULL, 85.00, 0.00, 0.00, 0.00, 'approved', NULL, '2026-07-27 18:20:35', '2026-07-27 18:20:35'),
(2, 2, 26, NULL, NULL, 78.00, 0.00, 0.00, 0.00, 'pending', NULL, '2026-07-27 18:20:35', '2026-07-27 18:20:35'),
(3, 3, 27, NULL, NULL, 92.00, 0.00, 0.00, 0.00, 'approved', NULL, '2026-07-27 18:20:35', '2026-07-27 18:20:35'),
(4, 4, 28, NULL, NULL, 65.00, 0.00, 0.00, 0.00, 'rejected', NULL, '2026-07-27 18:20:35', '2026-07-27 18:20:35'),
(5, 5, 29, NULL, NULL, 70.00, 0.00, 0.00, 0.00, 'pending', NULL, '2026-07-27 18:20:35', '2026-07-27 18:20:35'),
(7, 1, 25, 850.00, 1100.00, 77.27, 50.00, 50000.00, 50000.00, 'pending', NULL, '2026-07-27 19:47:39', '2026-07-27 19:47:39'),
(9, 6, 25, 700.00, 1000.00, 70.00, 50.00, 50000.00, 50000.00, 'pending', NULL, '2026-07-27 19:49:00', '2026-07-27 19:49:00'),
(10, 17, 25, 940.00, 1000.00, 94.00, 100.00, 50000.00, 0.00, 'pending', NULL, '2026-07-28 03:05:11', '2026-07-28 03:05:11'),
(11, 6, 41, 700.00, 1000.00, 70.00, 50.00, 25000.00, 25000.00, 'pending', NULL, '2026-07-28 05:58:36', '2026-07-28 05:58:36'),
(12, 11, 41, 700.00, 100.00, 700.00, 0.00, 0.00, 50000.00, 'pending', NULL, '2026-07-28 06:03:48', '2026-07-28 06:03:48'),
(13, 5, 35, 700.00, 1000.00, 70.00, 50.00, 25000.00, 25000.00, 'pending', NULL, '2026-07-28 06:07:05', '2026-07-28 06:07:05');

-- --------------------------------------------------------

--
-- Table structure for table `admission_scholarship_programs`
--

CREATE TABLE `admission_scholarship_programs` (
  `id` int(11) NOT NULL,
  `scholarship_name` varchar(255) NOT NULL,
  `scholarship_type` enum('Merit','Need Based','Sports','Research','Other') DEFAULT 'Merit',
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `min_marks_percentage` decimal(5,2) DEFAULT 0.00,
  `scholarship_percentage` decimal(5,2) DEFAULT 0.00,
  `total_slots` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_scholarship_programs`
--

INSERT INTO `admission_scholarship_programs` (`id`, `scholarship_name`, `scholarship_type`, `description`, `amount`, `min_marks_percentage`, `scholarship_percentage`, `total_slots`, `deadline`, `status`, `created_at`, `updated_at`) VALUES
(1, 'testing', 'Merit', '..', 50.00, 50.00, 100.00, 5, '2017-02-10', 'active', '2026-07-27 19:40:27', '2026-07-27 19:40:27'),
(2, 'testing', 'Merit', 'for good grades', 50.00, 50.00, 100.00, 5, '2027-01-02', 'active', '2026-07-27 19:41:10', '2026-07-27 19:41:10');

-- --------------------------------------------------------

--
-- Table structure for table `admission_students`
--

CREATE TABLE `admission_students` (
  `id` int(11) NOT NULL,
  `student_id` varchar(30) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `father_name` varchar(150) DEFAULT NULL,
  `cnic_or_bform` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('active','inactive','graduated','suspended') DEFAULT 'active',
  `is_activated` tinyint(1) NOT NULL DEFAULT 0,
  `fee_paid` tinyint(1) NOT NULL DEFAULT 0,
  `fee_paid_at` datetime DEFAULT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `application_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_students`
--

INSERT INTO `admission_students` (`id`, `student_id`, `full_name`, `student_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `department_id`, `status`, `is_activated`, `fee_paid`, `fee_paid_at`, `scholarship_id`, `section_id`, `created_at`, `updated_at`, `application_id`) VALUES
(1, 'STD026', 'Student 1', 'Student 1', 'Father 1', '42101-1234567-01-1', '2026-09-02', 'Male', 'value_1', 'faculty1@uni.edu', 'Lahore, Pakistan', 2, 2, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(2, 'STD027', 'Student 2', 'Student 2', 'Father 2', '42101-1234567-02-2', '2026-09-03', 'Male', 'value_2', 'faculty2@uni.edu', 'Lahore, Pakistan', 3, 3, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(3, 'STD028', 'Student 3', 'Student 3', 'Father 3', '42101-1234567-03-0', '2026-09-04', 'Male', 'value_3', 'faculty3@uni.edu', 'Lahore, Pakistan', 4, 4, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(4, 'STD029', 'Student 4', 'Student 4', 'Father 4', '42101-1234567-04-1', '2026-09-05', 'Male', 'value_4', 'faculty4@uni.edu', 'Lahore, Pakistan', 5, 5, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(5, 'STD030', 'Student 5', 'Student 5', 'Father 5', '42101-1234567-05-2', '2026-09-06', 'Male', 'value_5', 'faculty5@uni.edu', 'Lahore, Pakistan', 6, 6, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(6, 'STD031', 'Student 6', 'Student 6', 'Father 6', '42101-1234567-06-0', '2026-09-07', 'Male', 'value_6', 'faculty6@uni.edu', 'Lahore, Pakistan', 1, 1, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(7, 'STD032', 'Student 7', 'Student 7', 'Father 7', '42101-1234567-07-1', '2026-09-08', 'Male', 'value_7', 'faculty7@uni.edu', 'Lahore, Pakistan', 2, 2, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(8, 'STD033', 'Student 8', 'Student 8', 'Father 8', '42101-1234567-08-2', '2026-09-09', 'Male', 'value_8', 'faculty8@uni.edu', 'Lahore, Pakistan', 3, 3, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(9, 'STD034', 'Student 9', 'Student 9', 'Father 9', '42101-1234567-09-0', '2026-09-10', 'Male', 'value_9', 'faculty9@uni.edu', 'Lahore, Pakistan', 4, 4, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(10, 'STD035', 'Student 10', 'Student 10', 'Father 10', '42101-1234567-00-1', '2026-09-11', 'Male', 'value_10', 'faculty10@uni.edu', 'Lahore, Pakistan', 5, 5, 'active', 0, 0, NULL, NULL, NULL, '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(12, 'UNI-2026-00001', '', 'Ali', 'khan', '63871357817132', '2003-02-10', 'Male', '6157351656152', 'ali@gmail.com', 'peshawar', 1, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-27 19:36:26', '2026-07-27 19:36:26', 113),
(15, 'UNI-2026-00002', '', 'Wareesha', 'ali', '73652362536257', '2022-10-22', 'Female', '632326613', 'wareesha@gmai.com', 'peshawar', 2, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-28 05:57:46', '2026-07-28 05:58:01', 116),
(16, 'STU-2026-2145', '', 'noor', 'jahan', '7284678381892', '2000-05-10', 'Female', '03754782782', 'noorjahan@gmail.com', 'peshawar', 1, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 14:21:59', '2026-07-30 14:21:59', 120),
(17, 'STU-2026-1376', '', 'Sajjal', 'Khan', '7823728832273', '2000-02-01', 'Female', '62736268283', 'sajjal@gmail.com', '..', 1, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 14:23:08', '2026-07-30 14:23:08', 114),
(18, 'STU-2026-8858', '', 'aliya', 'Khan', '819289128199128', '2001-03-01', 'Female', '0389121288912', 'aliya1@gmail.com', 'peshawar', 2, NULL, 'active', 0, 0, NULL, 1, NULL, '2026-07-30 15:23:22', '2026-07-30 16:06:26', 124),
(19, 'STU-2026-4021', '', 'umama', 'khan', '8329923847274', '2000-10-02', 'Female', '03743673593', 'umama@gmail.com', 'peshawar', 5, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 16:24:01', '2026-07-30 16:24:55', 126),
(20, 'STU-2026-9456', '', 'aleena', 'ali', '732773297112', '2000-05-10', 'Female', '038377743843', 'aleena@gmail.com', 'peshawar', 2, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 16:29:56', '2026-07-30 16:29:56', 125),
(21, 'STU-2026-8791', '', 'hashir', 'ali', '8378274894719', '2000-02-01', 'Male', '03837984729', 'hashir@gmail.com', 'peshawar', 1, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 16:38:46', '2026-07-30 16:38:46', 127),
(22, 'STU-2026-2420', 'alina', 'alina', 'khan', '7823728832433', '2000-02-02', 'Female', '038928923982', 'alina@gmail.com', 'peshawar', 5, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 19:29:09', '2026-07-30 19:29:09', 128),
(23, 'STU-2026-3369', 'aliya', 'aliya', 'sohail', '0903902039200', '2001-10-01', 'Female', '89328932883', 'aliya21@gmail.com', 'peshawar', 1, NULL, 'active', 0, 0, NULL, NULL, NULL, '2026-07-30 19:57:37', '2026-07-30 20:14:34', 129),
(24, 'STU-2026-2316', 'farah', 'farah', 'khan', '78327823783282', '2000-10-01', 'Female', '03782182717821', 'farahkhan@gmail.com', 'peshawar', 2, 2, 'active', 0, 1, '2026-07-31 19:21:37', NULL, 3, '2026-07-31 17:16:30', '2026-07-31 20:29:25', 130),
(25, 'STU-2026-3409', 'mashal', 'mashal', 'khan', '9328948398348', '2000-05-02', 'Female', '03189281929', 'mashal@gmail.com', 'peshawar', 1, NULL, 'active', 0, 1, '2026-07-31 20:05:40', NULL, NULL, '2026-07-31 18:05:27', '2026-07-31 18:39:44', 131),
(26, 'STU-2026-2638', 'maria', 'maria', 'khan', '8932782738273', '2000-10-01', 'Female', '03781271268', 'mariakh@gmail.com', 'peshawar', 1, NULL, 'active', 0, 1, '2026-07-31 20:18:03', NULL, NULL, '2026-07-31 18:17:36', '2026-07-31 18:18:03', 132),
(29, 'STU-2026-AI01', 'Ali Raza', 'Ali Raza', 'Raza Ahmed', '42101-1234567-1', '2004-03-15', 'Male', '0300-1110001', 'ali.raza@demo.edu', NULL, 5, 5, 'active', 1, 1, NULL, NULL, 28, '2026-07-31 20:35:44', '2026-07-31 20:37:55', 910),
(30, 'STU-2026-AI02', 'Sana Malik', 'Sana Malik', 'Malik Hussain', '42201-2345678-2', '2005-07-20', 'Female', '0300-1110002', 'sana.malik@demo.edu', NULL, 5, 5, 'active', 1, 1, NULL, NULL, 29, '2026-07-31 20:35:44', '2026-07-31 20:49:01', 911),
(31, 'STU-2026-AI03', 'Bilal Ahmed', 'Bilal Ahmed', 'Ahmed Nawaz', '42301-3456789-3', '2004-11-02', 'Male', '0300-1110003', 'bilal.ahmed@demo.edu', NULL, 5, 5, 'active', 0, 1, NULL, NULL, 30, '2026-07-31 20:35:44', '2026-07-31 20:35:44', 912),
(32, 'STU-2026-AI04', 'Hina Tariq', 'Hina Tariq', 'Tariq Mehmood', '42401-4567890-4', '2005-01-25', 'Female', '0300-1110004', 'hina.tariq@demo.edu', NULL, 5, 5, 'active', 0, 1, NULL, NULL, NULL, '2026-07-31 20:35:44', '2026-07-31 20:35:44', 913);

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `application_type` varchar(100) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `review_date` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `remarks` text DEFAULT NULL,
  `registration_fee` decimal(10,2) DEFAULT 0.00,
  `admission_fee` decimal(10,2) DEFAULT 0.00,
  `total_fee` decimal(10,2) DEFAULT 0.00,
  `fee_paid` tinyint(1) DEFAULT 0,
  `fee_payment_date` date DEFAULT NULL,
  `fee_receipt_no` varchar(50) DEFAULT NULL,
  `finance_verified` tinyint(1) DEFAULT 0,
  `finance_verified_by` int(11) DEFAULT NULL,
  `finance_verified_at` timestamp NULL DEFAULT NULL,
  `finance_remarks` text DEFAULT NULL,
  `finance_status` enum('pending','verified','paid') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `student_id`, `application_type`, `subject`, `description`, `attachment`, `status`, `created_at`, `review_date`, `reviewed_by`, `updated_at`, `remarks`, `registration_fee`, `admission_fee`, `total_fee`, `fee_paid`, `fee_payment_date`, `fee_receipt_no`, `finance_verified`, `finance_verified_by`, `finance_verified_at`, `finance_remarks`, `finance_status`) VALUES
(1, '25', 'ID Card', 'cacdsccdscdsc', 'dcdd', '', 'Pending', '2026-07-28 06:45:23', NULL, NULL, '2026-07-28 06:45:23', NULL, 0.00, 0.00, 0.00, 0, NULL, NULL, 0, NULL, NULL, NULL, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `date` date DEFAULT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL,
  `remark` text DEFAULT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `date`, `student_id`, `course_id`, `faculty_id`, `teacher_id`, `class_date`, `status`, `remark`, `marked_at`) VALUES
(243, NULL, 25, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-28 08:34:48'),
(244, NULL, 25, 13, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-28 08:34:48'),
(245, NULL, 25, 13, NULL, 1, '2026-07-07', 'Absent', NULL, '2026-07-28 08:34:48'),
(246, NULL, 25, 13, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-28 08:34:48'),
(247, NULL, 25, 13, NULL, 1, '2026-07-14', 'Present', NULL, '2026-07-28 08:34:48'),
(248, NULL, 25, 13, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-28 08:34:48'),
(249, NULL, 25, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-28 08:34:48'),
(250, NULL, 25, 13, NULL, 1, '2026-07-24', 'Present', NULL, '2026-07-28 08:34:48'),
(251, NULL, 25, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(252, NULL, 25, 14, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-28 08:34:48'),
(253, NULL, 25, 14, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-28 08:34:48'),
(254, NULL, 25, 14, NULL, 1, '2026-07-08', 'Present', NULL, '2026-07-28 08:34:48'),
(255, NULL, 25, 14, NULL, 1, '2026-07-11', 'Absent', NULL, '2026-07-28 08:34:48'),
(256, NULL, 25, 14, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-28 08:34:48'),
(257, NULL, 25, 14, NULL, 1, '2026-07-18', 'Present', NULL, '2026-07-28 08:34:48'),
(258, NULL, 25, 14, NULL, 1, '2026-07-22', 'Present', NULL, '2026-07-28 08:34:48'),
(259, NULL, 25, 14, NULL, 1, '2026-07-25', 'Absent', NULL, '2026-07-28 08:34:48'),
(260, NULL, 25, 14, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(261, NULL, 26, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-28 08:34:48'),
(262, NULL, 26, 13, NULL, 1, '2026-07-03', 'Absent', NULL, '2026-07-28 08:34:48'),
(263, NULL, 26, 13, NULL, 1, '2026-07-07', 'Present', NULL, '2026-07-28 08:34:48'),
(264, NULL, 26, 13, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-28 08:34:48'),
(265, NULL, 26, 13, NULL, 1, '2026-07-14', 'Absent', NULL, '2026-07-28 08:34:48'),
(266, NULL, 26, 13, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-28 08:34:48'),
(267, NULL, 26, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-28 08:34:48'),
(268, NULL, 26, 13, NULL, 1, '2026-07-24', 'Absent', NULL, '2026-07-28 08:34:48'),
(269, NULL, 26, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(270, NULL, 26, 16, NULL, 1, '2026-07-02', 'Present', NULL, '2026-07-28 08:34:48'),
(271, NULL, 26, 16, NULL, 1, '2026-07-06', 'Present', NULL, '2026-07-28 08:34:48'),
(272, NULL, 26, 16, NULL, 1, '2026-07-09', 'Absent', NULL, '2026-07-28 08:34:48'),
(273, NULL, 26, 16, NULL, 1, '2026-07-13', 'Present', NULL, '2026-07-28 08:34:48'),
(274, NULL, 26, 16, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-28 08:34:48'),
(275, NULL, 26, 16, NULL, 1, '2026-07-20', 'Present', NULL, '2026-07-28 08:34:48'),
(276, NULL, 26, 16, NULL, 1, '2026-07-23', 'Absent', NULL, '2026-07-28 08:34:48'),
(277, NULL, 26, 16, NULL, 1, '2026-07-26', 'Present', NULL, '2026-07-28 08:34:48'),
(278, NULL, 27, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-28 08:34:48'),
(279, NULL, 27, 13, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-28 08:34:48'),
(280, NULL, 27, 13, NULL, 1, '2026-07-07', 'Present', NULL, '2026-07-28 08:34:48'),
(281, NULL, 27, 13, NULL, 1, '2026-07-10', 'Absent', NULL, '2026-07-28 08:34:48'),
(282, NULL, 27, 13, NULL, 1, '2026-07-14', 'Present', NULL, '2026-07-28 08:34:48'),
(283, NULL, 27, 13, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-28 08:34:48'),
(284, NULL, 27, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-28 08:34:48'),
(285, NULL, 27, 13, NULL, 1, '2026-07-24', 'Present', NULL, '2026-07-28 08:34:48'),
(286, NULL, 27, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(287, NULL, 27, 15, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-28 08:34:48'),
(288, NULL, 27, 15, NULL, 1, '2026-07-05', 'Present', NULL, '2026-07-28 08:34:48'),
(289, NULL, 27, 15, NULL, 1, '2026-07-12', 'Absent', NULL, '2026-07-28 08:34:48'),
(290, NULL, 27, 15, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-28 08:34:48'),
(291, NULL, 27, 15, NULL, 1, '2026-07-19', 'Present', NULL, '2026-07-28 08:34:48'),
(292, NULL, 27, 15, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-28 08:34:48'),
(293, NULL, 28, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-28 08:34:48'),
(294, NULL, 28, 13, NULL, 1, '2026-07-03', 'Absent', NULL, '2026-07-28 08:34:48'),
(295, NULL, 28, 13, NULL, 1, '2026-07-07', 'Present', NULL, '2026-07-28 08:34:48'),
(296, NULL, 28, 13, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-28 08:34:48'),
(297, NULL, 28, 13, NULL, 1, '2026-07-14', 'Present', NULL, '2026-07-28 08:34:48'),
(298, NULL, 28, 13, NULL, 1, '2026-07-17', 'Absent', NULL, '2026-07-28 08:34:48'),
(299, NULL, 28, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-28 08:34:48'),
(300, NULL, 28, 13, NULL, 1, '2026-07-24', 'Absent', NULL, '2026-07-28 08:34:48'),
(301, NULL, 28, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(302, NULL, 28, 14, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-28 08:34:48'),
(303, NULL, 28, 14, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-28 08:34:48'),
(304, NULL, 28, 14, NULL, 1, '2026-07-08', 'Present', NULL, '2026-07-28 08:34:48'),
(305, NULL, 28, 14, NULL, 1, '2026-07-11', 'Present', NULL, '2026-07-28 08:34:48'),
(306, NULL, 28, 14, NULL, 1, '2026-07-15', 'Absent', NULL, '2026-07-28 08:34:48'),
(307, NULL, 28, 14, NULL, 1, '2026-07-18', 'Present', NULL, '2026-07-28 08:34:48'),
(308, NULL, 28, 14, NULL, 1, '2026-07-22', 'Present', NULL, '2026-07-28 08:34:48'),
(309, NULL, 28, 14, NULL, 1, '2026-07-25', 'Present', NULL, '2026-07-28 08:34:48'),
(310, NULL, 28, 14, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(311, NULL, 29, 15, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-28 08:34:48'),
(312, NULL, 29, 15, NULL, 1, '2026-07-05', 'Present', NULL, '2026-07-28 08:34:48'),
(313, NULL, 29, 15, NULL, 1, '2026-07-12', 'Present', NULL, '2026-07-28 08:34:48'),
(314, NULL, 29, 15, NULL, 1, '2026-07-15', 'Absent', NULL, '2026-07-28 08:34:48'),
(315, NULL, 29, 15, NULL, 1, '2026-07-19', 'Present', NULL, '2026-07-28 08:34:48'),
(316, NULL, 29, 15, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-28 08:34:48'),
(317, NULL, 29, 15, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-28 08:34:48'),
(318, NULL, 29, 16, NULL, 1, '2026-07-02', 'Present', NULL, '2026-07-28 08:34:48'),
(319, NULL, 29, 16, NULL, 1, '2026-07-06', 'Present', NULL, '2026-07-28 08:34:48'),
(320, NULL, 29, 16, NULL, 1, '2026-07-09', 'Present', NULL, '2026-07-28 08:34:48'),
(321, NULL, 29, 16, NULL, 1, '2026-07-13', 'Absent', NULL, '2026-07-28 08:34:48'),
(322, NULL, 29, 16, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-28 08:34:48'),
(323, NULL, 29, 16, NULL, 1, '2026-07-20', 'Present', NULL, '2026-07-28 08:34:48'),
(324, NULL, 29, 16, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-28 08:34:48'),
(325, NULL, 29, 16, NULL, 1, '2026-07-26', 'Absent', NULL, '2026-07-28 08:34:48'),
(326, '2026-06-30', 30, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-31 20:12:43'),
(327, '2026-07-03', 30, 13, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-31 20:12:43'),
(328, '2026-07-07', 30, 13, NULL, 1, '2026-07-07', 'Present', NULL, '2026-07-31 20:12:43'),
(329, '2026-07-10', 30, 13, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-31 20:12:43'),
(330, '2026-07-14', 30, 13, NULL, 1, '2026-07-14', 'Present', NULL, '2026-07-31 20:12:43'),
(331, '2026-07-17', 30, 13, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-31 20:12:43'),
(332, '2026-07-21', 30, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-31 20:12:43'),
(333, '2026-07-24', 30, 13, NULL, 1, '2026-07-24', 'Present', NULL, '2026-07-31 20:12:43'),
(334, '2026-07-27', 30, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:12:43'),
(335, '2026-06-30', 35, 13, NULL, 1, '2026-06-30', 'Present', NULL, '2026-07-31 20:12:43'),
(336, '2026-07-03', 35, 13, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-31 20:12:43'),
(337, '2026-07-07', 35, 13, NULL, 1, '2026-07-07', 'Present', NULL, '2026-07-31 20:12:43'),
(338, '2026-07-10', 35, 13, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-31 20:12:43'),
(339, '2026-07-14', 35, 13, NULL, 1, '2026-07-14', 'Present', NULL, '2026-07-31 20:12:43'),
(340, '2026-07-17', 35, 13, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-31 20:12:43'),
(341, '2026-07-21', 35, 13, NULL, 1, '2026-07-21', 'Present', NULL, '2026-07-31 20:12:43'),
(342, '2026-07-24', 35, 13, NULL, 1, '2026-07-24', 'Present', NULL, '2026-07-31 20:12:43'),
(343, '2026-07-27', 35, 13, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:12:43'),
(357, '2026-07-01', 30, 14, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-31 20:13:01'),
(358, '2026-07-04', 30, 14, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-31 20:13:01'),
(359, '2026-07-08', 30, 14, NULL, 1, '2026-07-08', 'Present', NULL, '2026-07-31 20:13:01'),
(360, '2026-07-11', 30, 14, NULL, 1, '2026-07-11', 'Present', NULL, '2026-07-31 20:13:01'),
(361, '2026-07-15', 30, 14, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-31 20:13:01'),
(362, '2026-07-18', 30, 14, NULL, 1, '2026-07-18', 'Present', NULL, '2026-07-31 20:13:01'),
(363, '2026-07-22', 30, 14, NULL, 1, '2026-07-22', 'Present', NULL, '2026-07-31 20:13:01'),
(364, '2026-07-25', 30, 14, NULL, 1, '2026-07-25', 'Present', NULL, '2026-07-31 20:13:01'),
(365, '2026-07-27', 30, 14, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:13:01'),
(366, '2026-07-01', 35, 14, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-31 20:13:01'),
(367, '2026-07-04', 35, 14, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-31 20:13:01'),
(368, '2026-07-08', 35, 14, NULL, 1, '2026-07-08', 'Present', NULL, '2026-07-31 20:13:01'),
(369, '2026-07-11', 35, 14, NULL, 1, '2026-07-11', 'Present', NULL, '2026-07-31 20:13:01'),
(370, '2026-07-15', 35, 14, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-31 20:13:01'),
(371, '2026-07-18', 35, 14, NULL, 1, '2026-07-18', 'Present', NULL, '2026-07-31 20:13:01'),
(372, '2026-07-22', 35, 14, NULL, 1, '2026-07-22', 'Present', NULL, '2026-07-31 20:13:01'),
(373, '2026-07-25', 35, 14, NULL, 1, '2026-07-25', 'Present', NULL, '2026-07-31 20:13:01'),
(374, '2026-07-27', 35, 14, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:13:01'),
(388, '2026-07-01', 30, 15, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-31 20:13:01'),
(389, '2026-07-05', 30, 15, NULL, 1, '2026-07-05', 'Present', NULL, '2026-07-31 20:13:01'),
(390, '2026-07-12', 30, 15, NULL, 1, '2026-07-12', 'Present', NULL, '2026-07-31 20:13:01'),
(391, '2026-07-15', 30, 15, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-31 20:13:01'),
(392, '2026-07-19', 30, 15, NULL, 1, '2026-07-19', 'Present', NULL, '2026-07-31 20:13:01'),
(393, '2026-07-23', 30, 15, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-31 20:13:01'),
(394, '2026-07-27', 30, 15, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:13:01'),
(395, '2026-07-01', 35, 15, NULL, 1, '2026-07-01', 'Present', NULL, '2026-07-31 20:13:01'),
(396, '2026-07-05', 35, 15, NULL, 1, '2026-07-05', 'Present', NULL, '2026-07-31 20:13:01'),
(397, '2026-07-12', 35, 15, NULL, 1, '2026-07-12', 'Present', NULL, '2026-07-31 20:13:01'),
(398, '2026-07-15', 35, 15, NULL, 1, '2026-07-15', 'Present', NULL, '2026-07-31 20:13:01'),
(399, '2026-07-19', 35, 15, NULL, 1, '2026-07-19', 'Present', NULL, '2026-07-31 20:13:01'),
(400, '2026-07-23', 35, 15, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-31 20:13:01'),
(401, '2026-07-27', 35, 15, NULL, 1, '2026-07-27', 'Present', NULL, '2026-07-31 20:13:01'),
(403, '2026-07-02', 30, 16, NULL, 1, '2026-07-02', 'Present', NULL, '2026-07-31 20:13:01'),
(404, '2026-07-06', 30, 16, NULL, 1, '2026-07-06', 'Present', NULL, '2026-07-31 20:13:01'),
(405, '2026-07-09', 30, 16, NULL, 1, '2026-07-09', 'Present', NULL, '2026-07-31 20:13:01'),
(406, '2026-07-13', 30, 16, NULL, 1, '2026-07-13', 'Present', NULL, '2026-07-31 20:13:01'),
(407, '2026-07-16', 30, 16, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-31 20:13:01'),
(408, '2026-07-20', 30, 16, NULL, 1, '2026-07-20', 'Present', NULL, '2026-07-31 20:13:01'),
(409, '2026-07-23', 30, 16, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-31 20:13:01'),
(410, '2026-07-26', 30, 16, NULL, 1, '2026-07-26', 'Present', NULL, '2026-07-31 20:13:01'),
(411, '2026-07-02', 35, 16, NULL, 1, '2026-07-02', 'Present', NULL, '2026-07-31 20:13:01'),
(412, '2026-07-06', 35, 16, NULL, 1, '2026-07-06', 'Present', NULL, '2026-07-31 20:13:01'),
(413, '2026-07-09', 35, 16, NULL, 1, '2026-07-09', 'Present', NULL, '2026-07-31 20:13:01'),
(414, '2026-07-13', 35, 16, NULL, 1, '2026-07-13', 'Present', NULL, '2026-07-31 20:13:01'),
(415, '2026-07-16', 35, 16, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-31 20:13:01'),
(416, '2026-07-20', 35, 16, NULL, 1, '2026-07-20', 'Present', NULL, '2026-07-31 20:13:01'),
(417, '2026-07-23', 35, 16, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-31 20:13:01'),
(418, '2026-07-26', 35, 16, NULL, 1, '2026-07-26', 'Present', NULL, '2026-07-31 20:13:01'),
(434, '2026-07-02', 26, 18, NULL, 1, '2026-07-02', 'Leave', NULL, '2026-07-31 20:13:29'),
(435, '2026-07-02', 31, 18, NULL, 1, '2026-07-02', 'Absent', NULL, '2026-07-31 20:13:29'),
(436, '2026-07-02', 36, 18, NULL, 1, '2026-07-02', 'Present', NULL, '2026-07-31 20:13:29'),
(437, '2026-07-09', 26, 18, NULL, 1, '2026-07-09', 'Absent', NULL, '2026-07-31 20:13:29'),
(438, '2026-07-09', 31, 18, NULL, 1, '2026-07-09', 'Present', NULL, '2026-07-31 20:13:29'),
(439, '2026-07-09', 36, 18, NULL, 1, '2026-07-09', 'Present', NULL, '2026-07-31 20:13:29'),
(440, '2026-07-16', 26, 18, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-31 20:13:29'),
(441, '2026-07-16', 31, 18, NULL, 1, '2026-07-16', 'Present', NULL, '2026-07-31 20:13:29'),
(442, '2026-07-16', 36, 18, NULL, 1, '2026-07-16', 'Leave', NULL, '2026-07-31 20:13:29'),
(443, '2026-07-23', 26, 18, NULL, 1, '2026-07-23', 'Present', NULL, '2026-07-31 20:13:29'),
(444, '2026-07-23', 31, 18, NULL, 1, '2026-07-23', 'Leave', NULL, '2026-07-31 20:13:29'),
(445, '2026-07-23', 36, 18, NULL, 1, '2026-07-23', 'Absent', NULL, '2026-07-31 20:13:29'),
(449, '2026-07-03', 27, 20, NULL, 1, '2026-07-03', 'Absent', NULL, '2026-07-31 20:13:29'),
(450, '2026-07-03', 32, 20, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-31 20:13:29'),
(451, '2026-07-03', 37, 20, NULL, 1, '2026-07-03', 'Present', NULL, '2026-07-31 20:13:29'),
(452, '2026-07-10', 27, 20, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-31 20:13:29'),
(453, '2026-07-10', 32, 20, NULL, 1, '2026-07-10', 'Present', NULL, '2026-07-31 20:13:29'),
(454, '2026-07-10', 37, 20, NULL, 1, '2026-07-10', 'Leave', NULL, '2026-07-31 20:13:29'),
(455, '2026-07-17', 27, 20, NULL, 1, '2026-07-17', 'Present', NULL, '2026-07-31 20:13:29'),
(456, '2026-07-17', 32, 20, NULL, 1, '2026-07-17', 'Leave', NULL, '2026-07-31 20:13:29'),
(457, '2026-07-17', 37, 20, NULL, 1, '2026-07-17', 'Absent', NULL, '2026-07-31 20:13:29'),
(458, '2026-07-24', 27, 20, NULL, 1, '2026-07-24', 'Leave', NULL, '2026-07-31 20:13:29'),
(459, '2026-07-24', 32, 20, NULL, 1, '2026-07-24', 'Absent', NULL, '2026-07-31 20:13:29'),
(460, '2026-07-24', 37, 20, NULL, 1, '2026-07-24', 'Present', NULL, '2026-07-31 20:13:29'),
(464, '2026-07-04', 28, 21, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-31 20:13:29'),
(465, '2026-07-04', 33, 21, NULL, 1, '2026-07-04', 'Present', NULL, '2026-07-31 20:13:29'),
(466, '2026-07-11', 28, 21, NULL, 1, '2026-07-11', 'Present', NULL, '2026-07-31 20:13:29'),
(467, '2026-07-11', 33, 21, NULL, 1, '2026-07-11', 'Leave', NULL, '2026-07-31 20:13:29'),
(468, '2026-07-18', 28, 21, NULL, 1, '2026-07-18', 'Leave', NULL, '2026-07-31 20:13:29'),
(469, '2026-07-18', 33, 21, NULL, 1, '2026-07-18', 'Absent', NULL, '2026-07-31 20:13:29'),
(470, '2026-07-25', 28, 21, NULL, 1, '2026-07-25', 'Absent', NULL, '2026-07-31 20:13:29'),
(471, '2026-07-25', 33, 21, NULL, 1, '2026-07-25', 'Present', NULL, '2026-07-31 20:13:29'),
(479, '2026-07-05', 29, 22, NULL, 1, '2026-07-05', 'Present', NULL, '2026-07-31 20:13:29'),
(480, '2026-07-05', 34, 22, NULL, 1, '2026-07-05', 'Leave', NULL, '2026-07-31 20:13:29'),
(481, '2026-07-12', 29, 22, NULL, 1, '2026-07-12', 'Leave', NULL, '2026-07-31 20:13:29'),
(482, '2026-07-12', 34, 22, NULL, 1, '2026-07-12', 'Absent', NULL, '2026-07-31 20:13:29'),
(483, '2026-07-19', 29, 22, NULL, 1, '2026-07-19', 'Absent', NULL, '2026-07-31 20:13:29'),
(484, '2026-07-19', 34, 22, NULL, 1, '2026-07-19', 'Present', NULL, '2026-07-31 20:13:29'),
(485, '2026-07-26', 29, 22, NULL, 1, '2026-07-26', 'Present', NULL, '2026-07-31 20:13:29'),
(486, '2026-07-26', 34, 22, NULL, 1, '2026-07-26', 'Present', NULL, '2026-07-31 20:13:29');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) DEFAULT NULL,
  `course_title` varchar(150) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `credit_hours` tinyint(4) NOT NULL DEFAULT 3,
  `description` text DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `semester_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `semester_name` varchar(40) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `course_title`, `program_id`, `credit_hours`, `description`, `department_id`, `semester_id`, `status`, `created_at`, `semester_name`, `teacher_id`) VALUES
(13, 'CS101', 'Computer Fundamentals', 'Computer Fundamentals', 2, 3, 'Computer Fundamentals course description', 1, 9, 'Active', '2026-07-27 16:42:35', 'Semester 9', NULL),
(14, 'CS201', 'Data Structures', 'Data Structures', 2, 3, 'Data Structures course description', 1, 10, 'Active', '2026-07-27 16:42:35', 'Semester 10', NULL),
(15, 'CS301', 'Operating Systems', 'Operating Systems', 2, 3, 'Operating Systems course description', 1, 11, 'Active', '2026-07-27 16:42:35', 'Semester 11', NULL),
(16, 'CS401', 'Database Systems', 'Database Systems', 2, 3, 'Database Systems course description', 1, 12, 'Active', '2026-07-27 16:42:35', 'Semester 12', NULL),
(17, 'CS501', 'Software Engineering', 'Software Engineering', 2, 3, 'Software Engineering course description', 1, 9, 'Active', '2026-07-27 16:42:35', 'Semester 9', NULL),
(18, 'IT101', 'Computer Networks', 'Computer Networks', 3, 3, 'Computer Networks course description', 2, 10, 'Active', '2026-07-27 16:42:35', 'Semester 10', NULL),
(19, 'IT201', 'Web Technologies', 'Web Technologies', 3, 3, 'Web Technologies course description', 2, 11, 'Active', '2026-07-27 16:42:35', 'Semester 11', NULL),
(20, 'SE301', 'Software Testing', 'Software Testing', 4, 3, 'Software Testing course description', 3, 9, 'Active', '2026-07-27 16:42:35', 'Semester 9', NULL),
(21, 'AI401', 'Machine Learning', 'Machine Learning', 5, 3, 'Machine Learning course description', 4, 20, 'Active', '2026-07-27 16:42:35', 'Semester 12', NULL),
(22, 'DS501', 'Data Mining', 'Data Mining', 6, 3, 'Data Mining course description', 5, 13, 'Active', '2026-07-27 16:42:35', 'Semester 13', NULL),
(23, 'CS601', 'AI Concepts', 'AI Concepts', 2, 3, 'AI Concepts course description', 1, 10, 'Active', '2026-07-27 16:42:35', 'Semester 10', NULL),
(24, 'SE601', 'Project Management', 'Project Management', 4, 3, 'Project Management course description', 3, 14, 'Active', '2026-07-27 16:42:35', 'Semester 14', NULL),
(27, 'MATH201', NULL, 'Calculus II', NULL, 4, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(28, 'ENG101', NULL, 'English Composition', NULL, 3, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(29, 'PHY101', NULL, 'Physics Fundamentals', NULL, 4, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(30, 'CS102', NULL, 'Object Oriented Programming', NULL, 3, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(31, 'MATH202', NULL, 'Linear Algebra', NULL, 3, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(32, 'ENG102', NULL, 'Technical Writing', NULL, 3, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(33, 'PHY102', NULL, 'Electricity and Magnetism', NULL, 4, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0),
(35, 'NACASC', 'Machine Learning', '', 4, 3, '.', 1, 19, 'Active', '2026-07-28 06:25:23', NULL, NULL),
(36, 'MATH201-P1', NULL, 'Calculus II (Default Program)', 1, 4, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(37, 'ENG101-P1', NULL, 'English Composition (Default Program)', 1, 3, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(38, 'PHY101-P1', NULL, 'Physics Fundamentals (Default Program)', 1, 4, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(39, 'CS102-P1', NULL, 'Object Oriented Programming (Default Program)', 1, 3, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(40, 'MATH202-P1', NULL, 'Linear Algebra (Default Program)', 1, 3, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(41, 'ENG102-P1', NULL, 'Technical Writing (Default Program)', 1, 3, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(42, 'PHY102-P1', NULL, 'Electricity and Magnetism (Default Program)', 1, 4, NULL, 1, 1, 'Active', '2026-07-30 20:13:27', 'Semester 1', NULL),
(43, 'CS-P1-S1-01', 'Programming Fundamentals', 'Programming Fundamentals', 2, 3, NULL, 1, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(44, 'CS-P1-S1-02', 'Discrete Mathematics', 'Discrete Mathematics', 2, 3, NULL, 1, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(45, 'CS-P1-S1-03', 'Calculus I', 'Calculus I', 2, 3, NULL, 1, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(46, 'CS-P1-S2-01', 'Object Oriented Programming', 'Object Oriented Programming', 2, 3, NULL, 1, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL),
(47, 'CS-P1-S2-02', 'Data Structures', 'Data Structures', 2, 3, NULL, 1, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL),
(48, 'IT-P1-S1-01', 'Introduction to IT', 'Introduction to IT', 3, 3, NULL, 2, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(49, 'IT-P1-S1-02', 'Web Fundamentals', 'Web Fundamentals', 3, 3, NULL, 2, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(50, 'IT-P1-S2-01', 'Computer Networks', 'Computer Networks', 3, 3, NULL, 2, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL),
(51, 'SE-P1-S1-01', 'Software Engineering Intro', 'Software Engineering Intro', 4, 3, NULL, 3, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(52, 'SE-P1-S1-02', 'Programming Basics', 'Programming Basics', 4, 3, NULL, 3, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(53, 'SE-P1-S2-01', 'Software Requirements', 'Software Requirements', 4, 3, NULL, 3, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL),
(54, 'AI-P1-S1-01', 'AI Fundamentals', 'AI Fundamentals', 5, 3, NULL, 4, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(55, 'AI-P1-S1-02', 'Python Programming', 'Python Programming', 5, 3, NULL, 4, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(56, 'AI-P1-S2-01', 'Machine Learning Basics', 'Machine Learning Basics', 5, 3, NULL, 4, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL),
(57, 'DS-P1-S1-01', 'Data Science Intro', 'Data Science Intro', 6, 3, NULL, 5, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(58, 'DS-P1-S1-02', 'Statistics', 'Statistics', 6, 3, NULL, 5, 9, 'Active', '2026-07-30 20:14:03', 'Semester 1', NULL),
(59, 'DS-P1-S2-01', 'Data Mining', 'Data Mining', 6, 3, NULL, 5, 10, 'Active', '2026-07-30 20:14:03', 'Semester 2', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `course_master`
--

CREATE TABLE `course_master` (
  `id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `credit_hours` int(11) NOT NULL DEFAULT 3,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT 1,
  `is_elective` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `course_master`
--

INSERT INTO `course_master` (`id`, `course_code`, `course_name`, `credit_hours`, `department_id`, `program_id`, `semester`, `is_elective`, `status`, `created_at`) VALUES
(1, 'ENG-101', 'English Language & Composition', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(2, 'MATH-101', 'Mathematics', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(3, 'CS-101', 'Introduction to Computer Science', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(4, 'PHY-101', 'Physics', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(5, 'CHEM-101', 'Chemistry', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(6, 'BIO-101', 'Biology', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(7, 'PSY-101', 'Psychology', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19'),
(8, 'SOC-101', 'Sociology', 3, NULL, NULL, 1, 0, 'active', '2026-07-30 19:30:19');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_name` varchar(100) NOT NULL,
  `department_code` varchar(20) NOT NULL,
  `duration_years` tinyint(4) NOT NULL DEFAULT 4,
  `total_semesters` tinyint(4) NOT NULL DEFAULT 8,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_name`, `department_code`, `duration_years`, `total_semesters`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Default Department', '', 4, 8, 'Active', '2026-07-26 06:47:52', '2026-07-26 06:47:52'),
(2, 'Computer Science', 'CS', 4, 8, 'Active', '2026-07-26 07:03:24', '2026-07-26 07:03:24'),
(3, 'Information Technology', 'IT', 4, 8, 'Active', '2026-07-26 07:07:25', '2026-07-26 07:07:25'),
(4, 'Software Engineering', 'SE', 4, 8, 'Active', '2026-07-26 07:07:25', '2026-07-26 07:07:25'),
(5, 'Artificial Intelligence', 'AI', 4, 8, 'Active', '2026-07-26 07:07:25', '2026-07-26 07:07:25'),
(6, 'Data Science', 'DS', 4, 8, 'Active', '2026-07-26 07:07:25', '2026-07-26 07:07:25');

-- --------------------------------------------------------

--
-- Table structure for table `examinations`
--

CREATE TABLE `examinations` (
  `exam_id` int(11) NOT NULL,
  `exam_type` enum('Mid','Final') NOT NULL,
  `session_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `examinations`
--

INSERT INTO `examinations` (`exam_id`, `exam_type`, `session_id`, `semester_id`, `created_by`, `created_at`) VALUES
(1, 'Mid', 5, 9, 10, '2026-07-26 07:00:00'),
(2, 'Final', 5, 10, 10, '2026-07-26 07:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_attendance`
--

CREATE TABLE `exam_attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `present` tinyint(1) DEFAULT 0,
  `absent_reason` text DEFAULT NULL,
  `marked_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_attendance`
--

INSERT INTO `exam_attendance` (`attendance_id`, `student_id`, `exam_id`, `present`, `absent_reason`, `marked_by`, `created_at`) VALUES
(1, 0, 1, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(2, 0, 2, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(3, 0, 3, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(4, 0, 4, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(5, 0, 5, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(6, 0, 6, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(7, 0, 7, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(8, 0, 8, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(9, 0, 9, 0, 'Sick', 0, '2026-07-15 05:30:00'),
(10, 0, 10, 0, 'Sick', 0, '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) NOT NULL,
  `total_marks` decimal(5,2) NOT NULL,
  `grade` varchar(2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('draft','published','archived') DEFAULT 'draft',
  `remarks` text DEFAULT NULL,
  `entered_by` int(11) DEFAULT NULL,
  `published_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_results`
--

INSERT INTO `exam_results` (`result_id`, `student_id`, `exam_id`, `marks_obtained`, `total_marks`, `grade`, `percentage`, `status`, `remarks`, `entered_by`, `published_by`, `published_at`, `created_at`, `updated_at`) VALUES
(1, 25, 1, 40.00, 50.00, 'A', 80.00, 'published', 'SBE Midterm CS101 - Good performance', 1, 10, '2026-09-16 10:00:00', '2026-07-28 07:31:28', '2026-07-28 07:31:28'),
(2, 26, 1, 35.00, 50.00, 'B', 70.00, 'published', 'SBE Midterm CS101 - Satisfactory', 1, 10, '2026-09-16 10:00:00', '2026-07-28 07:31:28', '2026-07-28 07:31:28'),
(3, 27, 1, 30.00, 50.00, 'C', 60.00, 'published', 'SBE Midterm CS101 - Needs improvement', 1, 10, '2026-09-16 10:00:00', '2026-07-28 07:31:28', '2026-07-28 07:31:28'),
(4, 28, 1, 20.00, 50.00, 'F', 40.00, 'published', 'SBE Midterm CS101 - Below passing', 1, 10, '2026-09-16 10:00:00', '2026-07-28 07:31:28', '2026-07-28 07:31:28'),
(5, 29, 1, 45.00, 50.00, 'A+', 90.00, 'published', 'SBE Midterm CS101 - Excellent top scorer', 1, 10, '2026-09-16 10:00:00', '2026-07-28 07:31:28', '2026-07-28 07:31:28');

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` varchar(30) NOT NULL,
  `date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room` varchar(30) NOT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`exam_id`, `course_id`, `exam_type`, `date`, `start_time`, `end_time`, `room`, `status`, `created_at`) VALUES
(1, 13, 'Mid', '2026-09-15', '10:00:00', '12:00:00', 'R1', 'Completed', '2026-07-26 07:00:00'),
(2, 14, 'Mid', '2026-09-16', '10:00:00', '12:00:00', 'R2', 'Completed', '2026-07-26 07:00:00'),
(3, 15, 'Mid', '2026-09-17', '10:00:00', '12:00:00', 'R3', 'Completed', '2026-07-26 07:00:00'),
(4, 16, 'Mid', '2026-09-18', '10:00:00', '12:00:00', 'R4', 'Completed', '2026-07-26 07:00:00'),
(50, 13, 'Final', '2026-12-20', '10:00:00', '12:00:00', 'R50', 'Scheduled', '2026-07-26 07:00:00'),
(51, 14, 'Final', '2026-12-21', '10:00:00', '12:00:00', 'R51', 'Scheduled', '2026-07-26 07:00:00'),
(52, 15, 'Final', '2026-12-22', '10:00:00', '12:00:00', 'R52', 'Scheduled', '2026-07-26 07:00:00'),
(53, 16, 'Final', '2026-12-23', '10:00:00', '12:00:00', 'R53', 'Scheduled', '2026-07-26 07:00:00'),
(75, 32, 'final', '2000-02-22', '10:00:00', '11:00:00', 'Lab 1', 'Scheduled', '2026-07-28 06:53:47'),
(76, 32, 'final', '2026-02-22', '10:00:00', '11:00:00', 'LAB 1', 'Scheduled', '2026-07-28 06:57:56'),
(77, 32, 'final', '2026-02-02', '10:00:00', '11:00:00', 'Lab 1', 'Scheduled', '2026-07-28 07:06:27'),
(78, 22, 'final', '2026-02-02', '10:00:00', '11:00:00', 'A1', 'Scheduled', '2026-07-28 07:08:11'),
(79, 29, 'final', '0026-02-01', '10:00:00', '11:00:00', 'LAB 1', 'Scheduled', '2026-07-31 20:29:58');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `faculty_name` varchar(150) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `user_id`, `name`, `teacher_id`, `faculty_name`, `designation`, `email`, `phone`, `department_id`, `status`, `created_at`) VALUES
(1, 2, 'Faculty 1', 2, 'Faculty 1', 'Lecturer', 'faculty1@uni.edu', '0300-5551001', 2, 'Active', '2026-07-15 05:30:00'),
(2, 3, 'Faculty 2', 1, 'Faculty 2', 'Lecturer', 'faculty2@uni.edu', '0300-5551002', 3, 'Active', '2026-07-15 05:30:00'),
(3, 4, 'Faculty 3', 2, 'Faculty 3', 'Lecturer', 'faculty3@uni.edu', '0300-5551003', 4, 'Active', '2026-07-15 05:30:00'),
(4, 5, 'Faculty 4', 1, 'Faculty 4', 'Lecturer', 'faculty4@uni.edu', '0300-5551004', 5, 'Active', '2026-07-15 05:30:00'),
(5, 6, 'Faculty 5', 2, 'Faculty 5', 'Lecturer', 'faculty5@uni.edu', '0300-5551005', 6, 'Active', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `fee_heads`
--

CREATE TABLE `fee_heads` (
  `fee_head_id` int(11) NOT NULL,
  `fee_head_name` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_heads`
--

INSERT INTO `fee_heads` (`fee_head_id`, `fee_head_name`, `amount`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Tuition Fee', 0.00, 'Annual tuition fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(2, 'Lab Fee', 0.00, 'Laboratory usage fee', 'Active', '2026-07-30 18:24:30', '2026-07-27 16:30:18', '2026-07-30 18:24:30'),
(3, 'Library Fee', 0.00, 'Library card and resources fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(4, 'Exam Fee', 0.00, 'Examination fee per semester', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(5, 'Sports Fee', 0.00, 'Sports facility fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(6, 'cafetaria', 0.00, 'food', 'Active', '2026-07-28 05:39:22', '2026-07-28 05:39:07', '2026-07-28 05:39:22'),
(7, 'Electricity', 1000.00, '..', 'Active', NULL, '2026-07-30 18:24:18', '2026-07-30 18:24:18');

--
-- Triggers `fee_heads`
--
DELIMITER $$
CREATE TRIGGER `trg_after_feehead_update` AFTER UPDATE ON `fee_heads` FOR EACH ROW BEGIN
    IF OLD.deleted_at IS NULL AND NEW.deleted_at IS NOT NULL THEN
        INSERT INTO activity_logs (module, action, reference_table, reference_id, details)
        VALUES ('Finance', 'Soft Delete Fee Head', 'fee_heads', NEW.fee_head_id, NEW.fee_head_name);
    ELSEIF OLD.deleted_at IS NOT NULL AND NEW.deleted_at IS NULL THEN
        INSERT INTO activity_logs (module, action, reference_table, reference_id, details)
        VALUES ('Finance', 'Restore Fee Head', 'fee_heads', NEW.fee_head_id, NEW.fee_head_name);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `fee_master`
--

CREATE TABLE `fee_master` (
  `id` int(11) NOT NULL,
  `fee_head` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `category` enum('admission','tuition','exam','library','sports','other') DEFAULT 'other',
  `is_mandatory` tinyint(1) DEFAULT 1,
  `applicable_to` varchar(50) DEFAULT 'all',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_master`
--

INSERT INTO `fee_master` (`id`, `fee_head`, `description`, `amount`, `category`, `is_mandatory`, `applicable_to`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admission Fee', 'One-time admission processing fee', 1000.00, 'admission', 1, 'all', 'active', '2026-07-30 19:30:19', NULL),
(2, 'Tuition Fee (Per Semester)', 'Tuition fee for one semester', 15000.00, 'tuition', 1, 'all', 'active', '2026-07-30 19:30:19', NULL),
(3, 'Examination Fee', 'Semester examination fee', 2000.00, 'exam', 1, 'all', 'active', '2026-07-30 19:30:19', NULL),
(4, 'Library Fee', 'Library access and services', 1000.00, 'library', 1, 'all', 'active', '2026-07-30 19:30:19', NULL),
(5, 'Sports Fee', 'Sports facilities and activities', 500.00, 'sports', 0, 'all', 'active', '2026-07-30 19:30:19', NULL),
(6, 'Student Welfare Fund', 'Student welfare activities', 300.00, 'other', 0, 'all', 'active', '2026-07-30 19:30:19', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(20) DEFAULT 'cash',
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_payments`
--

INSERT INTO `fee_payments` (`id`, `student_id`, `fee_type`, `amount`, `payment_date`, `payment_method`, `status`) VALUES
(1, 25, 'Tuition Fee', 45000.00, '2026-01-15', 'bank_transfer', 'completed'),
(2, 26, 'Tuition Fee', 55000.00, '2026-01-20', 'cash', 'completed'),
(3, 27, 'Admission Fee', 15000.00, '2026-02-01', 'cheque', 'pending'),
(4, 25, 'Library Fee', 5000.00, '2026-02-10', 'cash', 'completed'),
(5, 28, 'Tuition Fee', 40000.00, '2026-02-15', 'bank_transfer', 'completed'),
(6, 26, 'Lab Fee', 8000.00, '2026-03-01', 'online', 'completed'),
(7, 27, 'Tuition Fee', 45000.00, '2026-03-10', 'bank_transfer', 'pending'),
(8, 29, 'Admission Fee', 20000.00, '2026-03-15', 'cash', 'completed');

-- --------------------------------------------------------

--
-- Table structure for table `fee_records`
--

CREATE TABLE `fee_records` (
  `fee_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `total_fee` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `remaining_amount` decimal(10,2) DEFAULT 0.00,
  `payment_date` date DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` enum('Paid','Partial','Unpaid') DEFAULT 'Unpaid',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_records`
--

INSERT INTO `fee_records` (`fee_id`, `student_id`, `total_fee`, `paid_amount`, `remaining_amount`, `payment_date`, `payment_method`, `transaction_id`, `status`, `created_at`) VALUES
(1, 'STD026', 0.00, 3000.00, 2000.00, '2026-09-02', 'Bank Transfer', 'TXN1001', '', '2026-07-15 05:30:00'),
(2, 'STD027', 0.00, 3000.00, 2000.00, '2026-09-03', 'Bank Transfer', 'TXN1002', '', '2026-07-15 05:30:00'),
(3, 'STD028', 0.00, 3000.00, 2000.00, '2026-09-04', 'Bank Transfer', 'TXN1003', '', '2026-07-15 05:30:00'),
(4, 'STD029', 0.00, 3000.00, 2000.00, '2026-09-05', 'Bank Transfer', 'TXN1004', '', '2026-07-15 05:30:00'),
(5, 'STD030', 0.00, 3000.00, 2000.00, '2026-09-06', 'Bank Transfer', 'TXN1005', '', '2026-07-15 05:30:00'),
(6, 'STD031', 0.00, 3000.00, 2000.00, '2026-09-07', 'Bank Transfer', 'TXN1006', '', '2026-07-15 05:30:00'),
(7, 'STD032', 0.00, 3000.00, 2000.00, '2026-09-08', 'Bank Transfer', 'TXN1007', '', '2026-07-15 05:30:00'),
(8, 'STD033', 0.00, 3000.00, 2000.00, '2026-09-09', 'Bank Transfer', 'TXN1008', '', '2026-07-15 05:30:00'),
(9, 'STD034', 0.00, 3000.00, 2000.00, '2026-09-10', 'Bank Transfer', 'TXN1009', '', '2026-07-15 05:30:00'),
(10, 'STD035', 0.00, 3000.00, 2000.00, '2026-09-11', 'Bank Transfer', 'TXN1010', '', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure`
--

CREATE TABLE `fee_structure` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `fee_head` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `is_recurring` tinyint(1) DEFAULT 0,
  `recurrence_period` varchar(20) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `fee_structure_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'active',
  `session_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`fee_structure_id`, `department_id`, `fee_type`, `amount`, `academic_year`, `status`, `session_id`, `semester_id`) VALUES
(1, 1, 'Tuition Fee', 45000.00, '2026', 'active', NULL, NULL),
(2, 1, 'Admission Fee', 15000.00, '2026', 'active', NULL, NULL),
(3, 1, 'Library Fee', 5000.00, '2026', 'active', NULL, NULL),
(4, 2, 'Tuition Fee', 55000.00, '2026', 'active', NULL, NULL),
(5, 2, 'Admission Fee', 20000.00, '2026', 'active', NULL, NULL),
(6, 2, 'Lab Fee', 8000.00, '2026', 'active', NULL, NULL),
(7, 3, 'Tuition Fee', 40000.00, '2026', 'active', NULL, NULL),
(8, 3, 'Sports Fee', 3000.00, '2026', 'inactive', NULL, NULL),
(9, 1, 'Tuition Fee', 45000.00, '2026', 'active', NULL, NULL),
(10, 1, 'Admission Fee', 15000.00, '2026', 'active', NULL, NULL),
(11, 1, 'Library Fee', 5000.00, '2026', 'active', NULL, NULL),
(12, 2, 'Tuition Fee', 55000.00, '2026', 'active', NULL, NULL),
(13, 2, 'Admission Fee', 20000.00, '2026', 'active', NULL, NULL),
(14, 3, 'Tuition Fee', 40000.00, '2026', 'active', NULL, NULL),
(15, 3, 'Sports Fee', 3000.00, '2026', 'active', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures_backup_2026`
--

CREATE TABLE `fee_structures_backup_2026` (
  `fee_structure_id` int(11) NOT NULL DEFAULT 0,
  `program_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_structures_backup_2026`
--

INSERT INTO `fee_structures_backup_2026` (`fee_structure_id`, `program_id`, `session_id`, `semester_id`, `total_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 9, 65000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(2, 3, 6, 9, 65000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(3, 4, 5, 10, 70000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(4, 4, 6, 10, 70000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(5, 5, 5, 11, 75000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(6, 5, 6, 11, 75000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(7, 6, 5, 12, 80000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(8, 6, 6, 12, 80000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(9, 7, 5, 13, 85000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(10, 7, 6, 13, 85000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18');

-- --------------------------------------------------------

--
-- Table structure for table `fee_structure_details`
--

CREATE TABLE `fee_structure_details` (
  `id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `fee_head_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structure_details`
--

INSERT INTO `fee_structure_details` (`id`, `fee_structure_id`, `fee_head_id`, `amount`) VALUES
(1, 1, 1, 45000.00),
(2, 1, 2, 4000.00),
(3, 1, 3, 2500.00),
(4, 1, 4, 8000.00),
(5, 1, 5, 5500.00),
(6, 2, 1, 45000.00),
(7, 2, 2, 4000.00),
(8, 2, 3, 2500.00),
(9, 2, 4, 8000.00),
(10, 2, 5, 5500.00),
(11, 3, 1, 45000.00),
(12, 3, 2, 4000.00),
(13, 3, 3, 2500.00),
(14, 3, 4, 8000.00),
(15, 3, 5, 5500.00),
(16, 4, 1, 45000.00),
(17, 4, 2, 4000.00),
(18, 4, 3, 2500.00),
(19, 4, 4, 8000.00),
(20, 4, 5, 5500.00),
(21, 5, 1, 45000.00),
(22, 5, 2, 4000.00),
(23, 5, 3, 2500.00),
(24, 5, 4, 8000.00),
(25, 5, 5, 5500.00),
(26, 6, 1, 45000.00),
(27, 6, 2, 4000.00),
(28, 6, 3, 2500.00),
(29, 6, 4, 8000.00),
(30, 6, 5, 5500.00),
(31, 7, 1, 45000.00),
(32, 7, 2, 4000.00),
(33, 7, 3, 2500.00),
(34, 7, 4, 8000.00),
(35, 7, 5, 5500.00),
(36, 8, 1, 45000.00),
(37, 8, 2, 4000.00),
(38, 8, 3, 2500.00),
(39, 8, 4, 8000.00),
(40, 8, 5, 5500.00),
(41, 9, 1, 45000.00),
(42, 9, 2, 4000.00),
(43, 9, 3, 2500.00),
(44, 9, 4, 8000.00),
(45, 9, 5, 5500.00),
(46, 10, 1, 45000.00),
(47, 10, 2, 4000.00),
(48, 10, 3, 2500.00),
(49, 10, 4, 8000.00),
(50, 10, 5, 5500.00),
(51, 19, 1, 50000.00),
(52, 19, 2, 4000.00),
(53, 19, 3, 3000.00),
(54, 19, 4, 5000.00),
(55, 19, 5, 2000.00),
(56, 20, 1, 50000.00),
(57, 20, 2, 4000.00),
(58, 20, 3, 3000.00),
(59, 20, 4, 5000.00),
(60, 20, 5, 2000.00),
(61, 1, 6, 10833.33),
(62, 2, 6, 10833.33),
(63, 3, 6, 11666.67),
(64, 4, 6, 11666.67),
(65, 5, 6, 12500.00),
(66, 6, 6, 12500.00),
(67, 7, 6, 13333.33),
(68, 8, 6, 13333.33),
(69, 9, 6, 14166.67),
(70, 10, 6, 14166.67),
(71, 16, 1, 16666.67),
(72, 16, 2, 16666.67),
(73, 16, 3, 16666.67),
(74, 16, 4, 16666.67),
(75, 16, 5, 16666.67),
(76, 16, 6, 16666.67),
(77, 17, 1, 16666.67),
(78, 17, 2, 16666.67),
(79, 17, 3, 16666.67),
(80, 17, 4, 16666.67),
(81, 17, 5, 16666.67),
(82, 17, 6, 16666.67),
(83, 19, 6, 10666.67),
(84, 20, 6, 10666.67),
(85, 22, 1, 10833.33),
(86, 22, 2, 10833.33),
(87, 22, 3, 10833.33),
(88, 22, 4, 10833.33),
(89, 22, 5, 10833.33),
(90, 22, 6, 10833.33),
(91, 23, 1, 9166.67),
(92, 23, 2, 9166.67),
(93, 23, 3, 9166.67),
(94, 23, 4, 9166.67),
(95, 23, 5, 9166.67),
(96, 23, 6, 9166.67),
(97, 24, 1, 9166.67),
(98, 24, 2, 9166.67),
(99, 24, 3, 9166.67),
(100, 24, 4, 9166.67),
(101, 24, 5, 9166.67),
(102, 24, 6, 9166.67),
(103, 25, 1, 9166.67),
(104, 25, 2, 9166.67),
(105, 25, 3, 9166.67),
(106, 25, 4, 9166.67),
(107, 25, 5, 9166.67),
(108, 25, 6, 9166.67),
(109, 26, 1, 10833.33),
(110, 26, 2, 10833.33),
(111, 26, 3, 10833.33),
(112, 26, 4, 10833.33),
(113, 26, 5, 10833.33),
(114, 26, 6, 10833.33),
(115, 27, 1, 10833.33),
(116, 27, 2, 10833.33),
(117, 27, 3, 10833.33),
(118, 27, 4, 10833.33),
(119, 27, 5, 10833.33),
(120, 27, 6, 10833.33),
(121, 28, 1, 10833.33),
(122, 28, 2, 10833.33),
(123, 28, 3, 10833.33),
(124, 28, 4, 10833.33),
(125, 28, 5, 10833.33),
(126, 28, 6, 10833.33),
(127, 29, 1, 11666.67),
(128, 29, 2, 11666.67),
(129, 29, 3, 11666.67),
(130, 29, 4, 11666.67),
(131, 29, 5, 11666.67),
(132, 29, 6, 11666.67),
(133, 30, 1, 11666.67),
(134, 30, 2, 11666.67),
(135, 30, 3, 11666.67),
(136, 30, 4, 11666.67),
(137, 30, 5, 11666.67),
(138, 30, 6, 11666.67),
(139, 31, 1, 11666.67),
(140, 31, 2, 11666.67),
(141, 31, 3, 11666.67),
(142, 31, 4, 11666.67),
(143, 31, 5, 11666.67),
(144, 31, 6, 11666.67),
(145, 32, 1, 10000.00),
(146, 32, 2, 10000.00),
(147, 32, 3, 10000.00),
(148, 32, 4, 10000.00),
(149, 32, 5, 10000.00),
(150, 32, 6, 10000.00),
(151, 33, 1, 10000.00),
(152, 33, 2, 10000.00),
(153, 33, 3, 10000.00),
(154, 33, 4, 10000.00),
(155, 33, 5, 10000.00),
(156, 33, 6, 10000.00),
(157, 34, 1, 10000.00),
(158, 34, 2, 10000.00),
(159, 34, 3, 10000.00),
(160, 34, 4, 10000.00),
(161, 34, 5, 10000.00),
(162, 34, 6, 10000.00),
(163, 35, 1, 9666.67),
(164, 35, 2, 9666.67),
(165, 35, 3, 9666.67),
(166, 35, 4, 9666.67),
(167, 35, 5, 9666.67),
(168, 35, 6, 9666.67),
(169, 36, 1, 9666.67),
(170, 36, 2, 9666.67),
(171, 36, 3, 9666.67),
(172, 36, 4, 9666.67),
(173, 36, 5, 9666.67),
(174, 36, 6, 9666.67),
(175, 37, 1, 9666.67),
(176, 37, 2, 9666.67),
(177, 37, 3, 9666.67),
(178, 37, 4, 9666.67),
(179, 37, 5, 9666.67),
(180, 37, 6, 9666.67),
(181, 38, 1, 10833.33),
(182, 38, 2, 10833.33),
(183, 38, 3, 10833.33),
(184, 38, 4, 10833.33),
(185, 38, 5, 10833.33),
(186, 38, 6, 10833.33),
(187, 39, 1, 10833.33),
(188, 39, 2, 10833.33),
(189, 39, 3, 10833.33),
(190, 39, 4, 10833.33),
(191, 39, 5, 10833.33),
(192, 39, 6, 10833.33),
(193, 40, 1, 10833.33),
(194, 40, 2, 10833.33),
(195, 40, 3, 10833.33),
(196, 40, 4, 10833.33),
(197, 40, 5, 10833.33),
(198, 40, 6, 10833.33),
(199, 41, 1, 9166.67),
(200, 41, 2, 9166.67),
(201, 41, 3, 9166.67),
(202, 41, 4, 9166.67),
(203, 41, 5, 9166.67),
(204, 41, 6, 9166.67),
(205, 42, 1, 9166.67),
(206, 42, 2, 9166.67),
(207, 42, 3, 9166.67),
(208, 42, 4, 9166.67),
(209, 42, 5, 9166.67),
(210, 42, 6, 9166.67),
(211, 43, 1, 10833.33),
(212, 43, 2, 10833.33),
(213, 43, 3, 10833.33),
(214, 43, 4, 10833.33),
(215, 43, 5, 10833.33),
(216, 43, 6, 10833.33),
(217, 44, 1, 10833.33),
(218, 44, 2, 10833.33),
(219, 44, 3, 10833.33),
(220, 44, 4, 10833.33),
(221, 44, 5, 10833.33),
(222, 44, 6, 10833.33),
(223, 45, 1, 11666.67),
(224, 45, 2, 11666.67),
(225, 45, 3, 11666.67),
(226, 45, 4, 11666.67),
(227, 45, 5, 11666.67),
(228, 45, 6, 11666.67),
(229, 46, 1, 11666.67),
(230, 46, 2, 11666.67),
(231, 46, 3, 11666.67),
(232, 46, 4, 11666.67),
(233, 46, 5, 11666.67),
(234, 46, 6, 11666.67),
(235, 47, 1, 11666.67),
(236, 47, 2, 11666.67),
(237, 47, 3, 11666.67),
(238, 47, 4, 11666.67),
(239, 47, 5, 11666.67),
(240, 47, 6, 11666.67),
(241, 48, 1, 10000.00),
(242, 48, 2, 10000.00),
(243, 48, 3, 10000.00),
(244, 48, 4, 10000.00),
(245, 48, 5, 10000.00),
(246, 48, 6, 10000.00),
(247, 49, 1, 10000.00),
(248, 49, 2, 10000.00),
(249, 49, 3, 10000.00),
(250, 49, 4, 10000.00),
(251, 49, 5, 10000.00),
(252, 49, 6, 10000.00),
(253, 50, 1, 10000.00),
(254, 50, 2, 10000.00),
(255, 50, 3, 10000.00),
(256, 50, 4, 10000.00),
(257, 50, 5, 10000.00),
(258, 50, 6, 10000.00),
(259, 51, 1, 9666.67),
(260, 51, 2, 9666.67),
(261, 51, 3, 9666.67),
(262, 51, 4, 9666.67),
(263, 51, 5, 9666.67),
(264, 51, 6, 9666.67),
(265, 52, 1, 9666.67),
(266, 52, 2, 9666.67),
(267, 52, 3, 9666.67),
(268, 52, 4, 9666.67),
(269, 52, 5, 9666.67),
(270, 52, 6, 9666.67),
(271, 53, 1, 9666.67),
(272, 53, 2, 9666.67),
(273, 53, 3, 9666.67),
(274, 53, 4, 9666.67),
(275, 53, 5, 9666.67),
(276, 53, 6, 9666.67),
(277, 54, 1, 10833.33),
(278, 54, 2, 10833.33),
(279, 54, 3, 10833.33),
(280, 54, 4, 10833.33),
(281, 54, 5, 10833.33),
(282, 54, 6, 10833.33),
(283, 55, 1, 10833.33),
(284, 55, 2, 10833.33),
(285, 55, 3, 10833.33),
(286, 55, 4, 10833.33),
(287, 55, 5, 10833.33),
(288, 55, 6, 10833.33),
(289, 56, 1, 10833.33),
(290, 56, 2, 10833.33),
(291, 56, 3, 10833.33),
(292, 56, 4, 10833.33),
(293, 56, 5, 10833.33),
(294, 56, 6, 10833.33),
(295, 57, 1, 9166.67),
(296, 57, 2, 9166.67),
(297, 57, 3, 9166.67),
(298, 57, 4, 9166.67),
(299, 57, 5, 9166.67),
(300, 57, 6, 9166.67),
(301, 58, 1, 9166.67),
(302, 58, 2, 9166.67),
(303, 58, 3, 9166.67),
(304, 58, 4, 9166.67),
(305, 58, 5, 9166.67),
(306, 58, 6, 9166.67),
(307, 59, 1, 9166.67),
(308, 59, 2, 9166.67),
(309, 59, 3, 9166.67),
(310, 59, 4, 9166.67),
(311, 59, 5, 9166.67),
(312, 59, 6, 9166.67),
(313, 60, 1, 10833.33),
(314, 60, 2, 10833.33),
(315, 60, 3, 10833.33),
(316, 60, 4, 10833.33),
(317, 60, 5, 10833.33),
(318, 60, 6, 10833.33),
(319, 61, 1, 10833.33),
(320, 61, 2, 10833.33),
(321, 61, 3, 10833.33),
(322, 61, 4, 10833.33),
(323, 61, 5, 10833.33),
(324, 61, 6, 10833.33),
(325, 62, 1, 10833.33),
(326, 62, 2, 10833.33),
(327, 62, 3, 10833.33),
(328, 62, 4, 10833.33),
(329, 62, 5, 10833.33),
(330, 62, 6, 10833.33),
(331, 63, 1, 11666.67),
(332, 63, 2, 11666.67),
(333, 63, 3, 11666.67),
(334, 63, 4, 11666.67),
(335, 63, 5, 11666.67),
(336, 63, 6, 11666.67),
(337, 64, 1, 11666.67),
(338, 64, 2, 11666.67),
(339, 64, 3, 11666.67),
(340, 64, 4, 11666.67),
(341, 64, 5, 11666.67),
(342, 64, 6, 11666.67),
(343, 65, 1, 11666.67),
(344, 65, 2, 11666.67),
(345, 65, 3, 11666.67),
(346, 65, 4, 11666.67),
(347, 65, 5, 11666.67),
(348, 65, 6, 11666.67),
(349, 66, 1, 10000.00),
(350, 66, 2, 10000.00),
(351, 66, 3, 10000.00),
(352, 66, 4, 10000.00),
(353, 66, 5, 10000.00),
(354, 66, 6, 10000.00),
(355, 67, 1, 10000.00),
(356, 67, 2, 10000.00),
(357, 67, 3, 10000.00),
(358, 67, 4, 10000.00),
(359, 67, 5, 10000.00),
(360, 67, 6, 10000.00),
(361, 68, 1, 10000.00),
(362, 68, 2, 10000.00),
(363, 68, 3, 10000.00),
(364, 68, 4, 10000.00),
(365, 68, 5, 10000.00),
(366, 68, 6, 10000.00),
(367, 69, 1, 9666.67),
(368, 69, 2, 9666.67),
(369, 69, 3, 9666.67),
(370, 69, 4, 9666.67),
(371, 69, 5, 9666.67),
(372, 69, 6, 9666.67),
(373, 70, 1, 9666.67),
(374, 70, 2, 9666.67),
(375, 70, 3, 9666.67),
(376, 70, 4, 9666.67),
(377, 70, 5, 9666.67),
(378, 70, 6, 9666.67),
(379, 71, 1, 9666.67),
(380, 71, 2, 9666.67),
(381, 71, 3, 9666.67),
(382, 71, 4, 9666.67),
(383, 71, 5, 9666.67),
(384, 71, 6, 9666.67),
(385, 72, 1, 10833.33),
(386, 72, 2, 10833.33),
(387, 72, 3, 10833.33),
(388, 72, 4, 10833.33),
(389, 72, 5, 10833.33),
(390, 72, 6, 10833.33),
(391, 73, 1, 10833.33),
(392, 73, 2, 10833.33),
(393, 73, 3, 10833.33),
(394, 73, 4, 10833.33),
(395, 73, 5, 10833.33),
(396, 73, 6, 10833.33),
(397, 74, 1, 10833.33),
(398, 74, 2, 10833.33),
(399, 74, 3, 10833.33),
(400, 74, 4, 10833.33),
(401, 74, 5, 10833.33),
(402, 74, 6, 10833.33),
(403, 75, 1, 9166.67),
(404, 75, 2, 9166.67),
(405, 75, 3, 9166.67),
(406, 75, 4, 9166.67),
(407, 75, 5, 9166.67),
(408, 75, 6, 9166.67),
(409, 76, 1, 9166.67),
(410, 76, 2, 9166.67),
(411, 76, 3, 9166.67),
(412, 76, 4, 9166.67),
(413, 76, 5, 9166.67),
(414, 76, 6, 9166.67),
(415, 77, 1, 9166.67),
(416, 77, 2, 9166.67),
(417, 77, 3, 9166.67),
(418, 77, 4, 9166.67),
(419, 77, 5, 9166.67),
(420, 77, 6, 9166.67),
(421, 78, 1, 10833.33),
(422, 78, 2, 10833.33),
(423, 78, 3, 10833.33),
(424, 78, 4, 10833.33),
(425, 78, 5, 10833.33),
(426, 78, 6, 10833.33),
(427, 79, 1, 10833.33),
(428, 79, 2, 10833.33),
(429, 79, 3, 10833.33),
(430, 79, 4, 10833.33),
(431, 79, 5, 10833.33),
(432, 79, 6, 10833.33),
(433, 80, 1, 10833.33),
(434, 80, 2, 10833.33),
(435, 80, 3, 10833.33),
(436, 80, 4, 10833.33),
(437, 80, 5, 10833.33),
(438, 80, 6, 10833.33),
(439, 81, 1, 11666.67),
(440, 81, 2, 11666.67),
(441, 81, 3, 11666.67),
(442, 81, 4, 11666.67),
(443, 81, 5, 11666.67),
(444, 81, 6, 11666.67),
(445, 82, 1, 11666.67),
(446, 82, 2, 11666.67),
(447, 82, 3, 11666.67),
(448, 82, 4, 11666.67),
(449, 82, 5, 11666.67),
(450, 82, 6, 11666.67),
(451, 83, 1, 11666.67),
(452, 83, 2, 11666.67),
(453, 83, 3, 11666.67),
(454, 83, 4, 11666.67),
(455, 83, 5, 11666.67),
(456, 83, 6, 11666.67),
(457, 84, 1, 10000.00),
(458, 84, 2, 10000.00),
(459, 84, 3, 10000.00),
(460, 84, 4, 10000.00),
(461, 84, 5, 10000.00),
(462, 84, 6, 10000.00),
(463, 85, 1, 10000.00),
(464, 85, 2, 10000.00),
(465, 85, 3, 10000.00),
(466, 85, 4, 10000.00),
(467, 85, 5, 10000.00),
(468, 85, 6, 10000.00),
(469, 86, 1, 10000.00),
(470, 86, 2, 10000.00),
(471, 86, 3, 10000.00),
(472, 86, 4, 10000.00),
(473, 86, 5, 10000.00),
(474, 86, 6, 10000.00),
(475, 87, 1, 9666.67),
(476, 87, 2, 9666.67),
(477, 87, 3, 9666.67),
(478, 87, 4, 9666.67),
(479, 87, 5, 9666.67),
(480, 87, 6, 9666.67),
(481, 88, 1, 9666.67),
(482, 88, 2, 9666.67),
(483, 88, 3, 9666.67),
(484, 88, 4, 9666.67),
(485, 88, 5, 9666.67),
(486, 88, 6, 9666.67),
(487, 89, 1, 9666.67),
(488, 89, 2, 9666.67),
(489, 89, 3, 9666.67),
(490, 89, 4, 9666.67),
(491, 89, 5, 9666.67),
(492, 89, 6, 9666.67),
(493, 90, 1, 10833.33),
(494, 90, 2, 10833.33),
(495, 90, 3, 10833.33),
(496, 90, 4, 10833.33),
(497, 90, 5, 10833.33),
(498, 90, 6, 10833.33),
(499, 91, 1, 10833.33),
(500, 91, 2, 10833.33),
(501, 91, 3, 10833.33),
(502, 91, 4, 10833.33),
(503, 91, 5, 10833.33),
(504, 91, 6, 10833.33),
(505, 92, 1, 10833.33),
(506, 92, 2, 10833.33),
(507, 92, 3, 10833.33),
(508, 92, 4, 10833.33),
(509, 92, 5, 10833.33),
(510, 92, 6, 10833.33),
(511, 93, 1, 9166.67),
(512, 93, 2, 9166.67),
(513, 93, 3, 9166.67),
(514, 93, 4, 9166.67),
(515, 93, 5, 9166.67),
(516, 93, 6, 9166.67),
(517, 94, 1, 9166.67),
(518, 94, 2, 9166.67),
(519, 94, 3, 9166.67),
(520, 94, 4, 9166.67),
(521, 94, 5, 9166.67),
(522, 94, 6, 9166.67),
(523, 95, 1, 9166.67),
(524, 95, 2, 9166.67),
(525, 95, 3, 9166.67),
(526, 95, 4, 9166.67),
(527, 95, 5, 9166.67),
(528, 95, 6, 9166.67),
(529, 96, 1, 10833.33),
(530, 96, 2, 10833.33),
(531, 96, 3, 10833.33),
(532, 96, 4, 10833.33),
(533, 96, 5, 10833.33),
(534, 96, 6, 10833.33),
(535, 97, 1, 10833.33),
(536, 97, 2, 10833.33),
(537, 97, 3, 10833.33),
(538, 97, 4, 10833.33),
(539, 97, 5, 10833.33),
(540, 97, 6, 10833.33),
(541, 98, 1, 10833.33),
(542, 98, 2, 10833.33),
(543, 98, 3, 10833.33),
(544, 98, 4, 10833.33),
(545, 98, 5, 10833.33),
(546, 98, 6, 10833.33),
(547, 99, 1, 11666.67),
(548, 99, 2, 11666.67),
(549, 99, 3, 11666.67),
(550, 99, 4, 11666.67),
(551, 99, 5, 11666.67),
(552, 99, 6, 11666.67),
(553, 100, 1, 11666.67),
(554, 100, 2, 11666.67),
(555, 100, 3, 11666.67),
(556, 100, 4, 11666.67),
(557, 100, 5, 11666.67),
(558, 100, 6, 11666.67),
(559, 101, 1, 11666.67),
(560, 101, 2, 11666.67),
(561, 101, 3, 11666.67),
(562, 101, 4, 11666.67),
(563, 101, 5, 11666.67),
(564, 101, 6, 11666.67),
(565, 102, 1, 10000.00),
(566, 102, 2, 10000.00),
(567, 102, 3, 10000.00),
(568, 102, 4, 10000.00),
(569, 102, 5, 10000.00),
(570, 102, 6, 10000.00),
(571, 103, 1, 10000.00),
(572, 103, 2, 10000.00),
(573, 103, 3, 10000.00),
(574, 103, 4, 10000.00),
(575, 103, 5, 10000.00),
(576, 103, 6, 10000.00),
(577, 104, 1, 10000.00),
(578, 104, 2, 10000.00),
(579, 104, 3, 10000.00),
(580, 104, 4, 10000.00),
(581, 104, 5, 10000.00),
(582, 104, 6, 10000.00),
(583, 105, 1, 9666.67),
(584, 105, 2, 9666.67),
(585, 105, 3, 9666.67),
(586, 105, 4, 9666.67),
(587, 105, 5, 9666.67),
(588, 105, 6, 9666.67),
(589, 106, 1, 9666.67),
(590, 106, 2, 9666.67),
(591, 106, 3, 9666.67),
(592, 106, 4, 9666.67),
(593, 106, 5, 9666.67),
(594, 106, 6, 9666.67),
(595, 107, 1, 9666.67),
(596, 107, 2, 9666.67),
(597, 107, 3, 9666.67),
(598, 107, 4, 9666.67),
(599, 107, 5, 9666.67),
(600, 107, 6, 9666.67),
(601, 108, 1, 10833.33),
(602, 108, 2, 10833.33),
(603, 108, 3, 10833.33),
(604, 108, 4, 10833.33),
(605, 108, 5, 10833.33),
(606, 108, 6, 10833.33),
(607, 109, 1, 10833.33),
(608, 109, 2, 10833.33),
(609, 109, 3, 10833.33),
(610, 109, 4, 10833.33),
(611, 109, 5, 10833.33),
(612, 109, 6, 10833.33),
(613, 110, 1, 10833.33),
(614, 110, 2, 10833.33),
(615, 110, 3, 10833.33),
(616, 110, 4, 10833.33),
(617, 110, 5, 10833.33),
(618, 110, 6, 10833.33),
(619, 111, 1, 9166.67),
(620, 111, 2, 9166.67),
(621, 111, 3, 9166.67),
(622, 111, 4, 9166.67),
(623, 111, 5, 9166.67),
(624, 111, 6, 9166.67),
(625, 112, 1, 9166.67),
(626, 112, 2, 9166.67),
(627, 112, 3, 9166.67),
(628, 112, 4, 9166.67),
(629, 112, 5, 9166.67),
(630, 112, 6, 9166.67),
(631, 113, 1, 9166.67),
(632, 113, 2, 9166.67),
(633, 113, 3, 9166.67),
(634, 113, 4, 9166.67),
(635, 113, 5, 9166.67),
(636, 113, 6, 9166.67),
(637, 114, 1, 10833.33),
(638, 114, 2, 10833.33),
(639, 114, 3, 10833.33),
(640, 114, 4, 10833.33),
(641, 114, 5, 10833.33),
(642, 114, 6, 10833.33),
(643, 115, 1, 10833.33),
(644, 115, 2, 10833.33),
(645, 115, 3, 10833.33),
(646, 115, 4, 10833.33),
(647, 115, 5, 10833.33),
(648, 115, 6, 10833.33),
(649, 116, 1, 10833.33),
(650, 116, 2, 10833.33),
(651, 116, 3, 10833.33),
(652, 116, 4, 10833.33),
(653, 116, 5, 10833.33),
(654, 116, 6, 10833.33),
(655, 117, 1, 11666.67),
(656, 117, 2, 11666.67),
(657, 117, 3, 11666.67),
(658, 117, 4, 11666.67),
(659, 117, 5, 11666.67),
(660, 117, 6, 11666.67),
(661, 118, 1, 11666.67),
(662, 118, 2, 11666.67),
(663, 118, 3, 11666.67),
(664, 118, 4, 11666.67),
(665, 118, 5, 11666.67),
(666, 118, 6, 11666.67),
(667, 119, 1, 11666.67),
(668, 119, 2, 11666.67),
(669, 119, 3, 11666.67),
(670, 119, 4, 11666.67),
(671, 119, 5, 11666.67),
(672, 119, 6, 11666.67),
(673, 120, 1, 10000.00),
(674, 120, 2, 10000.00),
(675, 120, 3, 10000.00),
(676, 120, 4, 10000.00),
(677, 120, 5, 10000.00),
(678, 120, 6, 10000.00),
(679, 121, 1, 10000.00),
(680, 121, 2, 10000.00),
(681, 121, 3, 10000.00),
(682, 121, 4, 10000.00),
(683, 121, 5, 10000.00),
(684, 121, 6, 10000.00),
(685, 122, 1, 10000.00),
(686, 122, 2, 10000.00),
(687, 122, 3, 10000.00),
(688, 122, 4, 10000.00),
(689, 122, 5, 10000.00),
(690, 122, 6, 10000.00),
(691, 123, 1, 9666.67),
(692, 123, 2, 9666.67),
(693, 123, 3, 9666.67),
(694, 123, 4, 9666.67),
(695, 123, 5, 9666.67),
(696, 123, 6, 9666.67),
(697, 124, 1, 9666.67),
(698, 124, 2, 9666.67),
(699, 124, 3, 9666.67),
(700, 124, 4, 9666.67),
(701, 124, 5, 9666.67),
(702, 124, 6, 9666.67),
(703, 125, 1, 9666.67),
(704, 125, 2, 9666.67),
(705, 125, 3, 9666.67),
(706, 125, 4, 9666.67),
(707, 125, 5, 9666.67),
(708, 125, 6, 9666.67),
(709, 126, 1, 10833.33),
(710, 126, 2, 10833.33),
(711, 126, 3, 10833.33),
(712, 126, 4, 10833.33),
(713, 126, 5, 10833.33),
(714, 126, 6, 10833.33),
(715, 127, 1, 10833.33),
(716, 127, 2, 10833.33),
(717, 127, 3, 10833.33),
(718, 127, 4, 10833.33),
(719, 127, 5, 10833.33),
(720, 127, 6, 10833.33),
(721, 128, 1, 10833.33),
(722, 128, 2, 10833.33),
(723, 128, 3, 10833.33),
(724, 128, 4, 10833.33),
(725, 128, 5, 10833.33),
(726, 128, 6, 10833.33),
(727, 129, 1, 9166.67),
(728, 129, 2, 9166.67),
(729, 129, 3, 9166.67),
(730, 129, 4, 9166.67),
(731, 129, 5, 9166.67),
(732, 129, 6, 9166.67),
(733, 130, 1, 9166.67),
(734, 130, 2, 9166.67),
(735, 130, 3, 9166.67),
(736, 130, 4, 9166.67),
(737, 130, 5, 9166.67),
(738, 130, 6, 9166.67),
(739, 131, 1, 9166.67),
(740, 131, 2, 9166.67),
(741, 131, 3, 9166.67),
(742, 131, 4, 9166.67),
(743, 131, 5, 9166.67),
(744, 131, 6, 9166.67),
(745, 132, 1, 10833.33),
(746, 132, 2, 10833.33),
(747, 132, 3, 10833.33),
(748, 132, 4, 10833.33),
(749, 132, 5, 10833.33),
(750, 132, 6, 10833.33),
(751, 133, 1, 10833.33),
(752, 133, 2, 10833.33),
(753, 133, 3, 10833.33),
(754, 133, 4, 10833.33),
(755, 133, 5, 10833.33),
(756, 133, 6, 10833.33),
(757, 134, 1, 10833.33),
(758, 134, 2, 10833.33),
(759, 134, 3, 10833.33),
(760, 134, 4, 10833.33),
(761, 134, 5, 10833.33),
(762, 134, 6, 10833.33),
(763, 135, 1, 11666.67),
(764, 135, 2, 11666.67),
(765, 135, 3, 11666.67),
(766, 135, 4, 11666.67),
(767, 135, 5, 11666.67),
(768, 135, 6, 11666.67),
(769, 136, 1, 11666.67),
(770, 136, 2, 11666.67),
(771, 136, 3, 11666.67),
(772, 136, 4, 11666.67),
(773, 136, 5, 11666.67),
(774, 136, 6, 11666.67),
(775, 137, 1, 11666.67),
(776, 137, 2, 11666.67),
(777, 137, 3, 11666.67),
(778, 137, 4, 11666.67),
(779, 137, 5, 11666.67),
(780, 137, 6, 11666.67),
(781, 138, 1, 10000.00),
(782, 138, 2, 10000.00),
(783, 138, 3, 10000.00),
(784, 138, 4, 10000.00),
(785, 138, 5, 10000.00),
(786, 138, 6, 10000.00),
(787, 139, 1, 10000.00),
(788, 139, 2, 10000.00),
(789, 139, 3, 10000.00),
(790, 139, 4, 10000.00),
(791, 139, 5, 10000.00),
(792, 139, 6, 10000.00),
(793, 140, 1, 10000.00),
(794, 140, 2, 10000.00),
(795, 140, 3, 10000.00),
(796, 140, 4, 10000.00),
(797, 140, 5, 10000.00),
(798, 140, 6, 10000.00),
(799, 141, 1, 9666.67),
(800, 141, 2, 9666.67),
(801, 141, 3, 9666.67),
(802, 141, 4, 9666.67),
(803, 141, 5, 9666.67),
(804, 141, 6, 9666.67),
(805, 142, 1, 9666.67),
(806, 142, 2, 9666.67),
(807, 142, 3, 9666.67),
(808, 142, 4, 9666.67),
(809, 142, 5, 9666.67),
(810, 142, 6, 9666.67),
(811, 143, 1, 9666.67),
(812, 143, 2, 9666.67),
(813, 143, 3, 9666.67),
(814, 143, 4, 9666.67),
(815, 143, 5, 9666.67),
(816, 143, 6, 9666.67),
(817, 144, 1, 10833.33),
(818, 144, 2, 10833.33),
(819, 144, 3, 10833.33),
(820, 144, 4, 10833.33),
(821, 144, 5, 10833.33),
(822, 144, 6, 10833.33),
(823, 145, 1, 10833.33),
(824, 145, 2, 10833.33),
(825, 145, 3, 10833.33),
(826, 145, 4, 10833.33),
(827, 145, 5, 10833.33),
(828, 145, 6, 10833.33),
(829, 146, 1, 10833.33),
(830, 146, 2, 10833.33),
(831, 146, 3, 10833.33),
(832, 146, 4, 10833.33),
(833, 146, 5, 10833.33),
(834, 146, 6, 10833.33),
(835, 147, 1, 9166.67),
(836, 147, 2, 9166.67),
(837, 147, 3, 9166.67),
(838, 147, 4, 9166.67),
(839, 147, 5, 9166.67),
(840, 147, 6, 9166.67),
(841, 148, 1, 9166.67),
(842, 148, 2, 9166.67),
(843, 148, 3, 9166.67),
(844, 148, 4, 9166.67),
(845, 148, 5, 9166.67),
(846, 148, 6, 9166.67),
(847, 149, 1, 9166.67),
(848, 149, 2, 9166.67),
(849, 149, 3, 9166.67),
(850, 149, 4, 9166.67),
(851, 149, 5, 9166.67),
(852, 149, 6, 9166.67),
(853, 150, 1, 10833.33),
(854, 150, 2, 10833.33),
(855, 150, 3, 10833.33),
(856, 150, 4, 10833.33),
(857, 150, 5, 10833.33),
(858, 150, 6, 10833.33),
(859, 151, 1, 10833.33),
(860, 151, 2, 10833.33),
(861, 151, 3, 10833.33),
(862, 151, 4, 10833.33),
(863, 151, 5, 10833.33),
(864, 151, 6, 10833.33),
(865, 152, 1, 10833.33),
(866, 152, 2, 10833.33),
(867, 152, 3, 10833.33),
(868, 152, 4, 10833.33),
(869, 152, 5, 10833.33),
(870, 152, 6, 10833.33),
(871, 153, 1, 11666.67),
(872, 153, 2, 11666.67),
(873, 153, 3, 11666.67),
(874, 153, 4, 11666.67),
(875, 153, 5, 11666.67),
(876, 153, 6, 11666.67),
(877, 154, 1, 11666.67),
(878, 154, 2, 11666.67),
(879, 154, 3, 11666.67),
(880, 154, 4, 11666.67),
(881, 154, 5, 11666.67),
(882, 154, 6, 11666.67),
(883, 155, 1, 11666.67),
(884, 155, 2, 11666.67),
(885, 155, 3, 11666.67),
(886, 155, 4, 11666.67),
(887, 155, 5, 11666.67),
(888, 155, 6, 11666.67),
(889, 156, 1, 10000.00),
(890, 156, 2, 10000.00),
(891, 156, 3, 10000.00),
(892, 156, 4, 10000.00),
(893, 156, 5, 10000.00),
(894, 156, 6, 10000.00),
(895, 157, 1, 10000.00),
(896, 157, 2, 10000.00),
(897, 157, 3, 10000.00),
(898, 157, 4, 10000.00),
(899, 157, 5, 10000.00),
(900, 157, 6, 10000.00),
(901, 158, 1, 10000.00),
(902, 158, 2, 10000.00),
(903, 158, 3, 10000.00),
(904, 158, 4, 10000.00),
(905, 158, 5, 10000.00),
(906, 158, 6, 10000.00),
(907, 159, 1, 9666.67),
(908, 159, 2, 9666.67),
(909, 159, 3, 9666.67),
(910, 159, 4, 9666.67),
(911, 159, 5, 9666.67),
(912, 159, 6, 9666.67),
(913, 160, 1, 9666.67),
(914, 160, 2, 9666.67),
(915, 160, 3, 9666.67),
(916, 160, 4, 9666.67),
(917, 160, 5, 9666.67),
(918, 160, 6, 9666.67),
(919, 161, 1, 9666.67),
(920, 161, 2, 9666.67),
(921, 161, 3, 9666.67),
(922, 161, 4, 9666.67),
(923, 161, 5, 9666.67),
(924, 161, 6, 9666.67),
(925, 162, 1, 10833.33),
(926, 162, 2, 10833.33),
(927, 162, 3, 10833.33),
(928, 162, 4, 10833.33),
(929, 162, 5, 10833.33),
(930, 162, 6, 10833.33),
(931, 163, 1, 10833.33),
(932, 163, 2, 10833.33),
(933, 163, 3, 10833.33),
(934, 163, 4, 10833.33),
(935, 163, 5, 10833.33),
(936, 163, 6, 10833.33),
(937, 164, 1, 10833.33),
(938, 164, 2, 10833.33),
(939, 164, 3, 10833.33),
(940, 164, 4, 10833.33),
(941, 164, 5, 10833.33),
(942, 164, 6, 10833.33),
(943, 165, 1, 9166.67),
(944, 165, 2, 9166.67),
(945, 165, 3, 9166.67),
(946, 165, 4, 9166.67),
(947, 165, 5, 9166.67),
(948, 165, 6, 9166.67),
(949, 166, 1, 9166.67),
(950, 166, 2, 9166.67),
(951, 166, 3, 9166.67),
(952, 166, 4, 9166.67),
(953, 166, 5, 9166.67),
(954, 166, 6, 9166.67),
(955, 167, 1, 9166.67),
(956, 167, 2, 9166.67),
(957, 167, 3, 9166.67),
(958, 167, 4, 9166.67),
(959, 167, 5, 9166.67),
(960, 167, 6, 9166.67),
(961, 168, 1, 10833.33),
(962, 168, 2, 10833.33),
(963, 168, 3, 10833.33),
(964, 168, 4, 10833.33),
(965, 168, 5, 10833.33),
(966, 168, 6, 10833.33),
(967, 169, 1, 11666.67),
(968, 169, 2, 11666.67),
(969, 169, 3, 11666.67),
(970, 169, 4, 11666.67),
(971, 169, 5, 11666.67),
(972, 169, 6, 11666.67),
(973, 170, 1, 11666.67),
(974, 170, 2, 11666.67),
(975, 170, 3, 11666.67),
(976, 170, 4, 11666.67),
(977, 170, 5, 11666.67),
(978, 170, 6, 11666.67),
(979, 171, 1, 11666.67),
(980, 171, 2, 11666.67),
(981, 171, 3, 11666.67),
(982, 171, 4, 11666.67),
(983, 171, 5, 11666.67),
(984, 171, 6, 11666.67),
(985, 172, 1, 10000.00),
(986, 172, 2, 10000.00),
(987, 172, 3, 10000.00),
(988, 172, 4, 10000.00),
(989, 172, 5, 10000.00),
(990, 172, 6, 10000.00),
(991, 173, 1, 10000.00),
(992, 173, 2, 10000.00),
(993, 173, 3, 10000.00),
(994, 173, 4, 10000.00),
(995, 173, 5, 10000.00),
(996, 173, 6, 10000.00),
(997, 174, 1, 10000.00),
(998, 174, 2, 10000.00),
(999, 174, 3, 10000.00),
(1000, 174, 4, 10000.00),
(1001, 174, 5, 10000.00),
(1002, 174, 6, 10000.00),
(1003, 175, 1, 9666.67),
(1004, 175, 2, 9666.67),
(1005, 175, 3, 9666.67),
(1006, 175, 4, 9666.67),
(1007, 175, 5, 9666.67),
(1008, 175, 6, 9666.67),
(1009, 176, 1, 9666.67),
(1010, 176, 2, 9666.67),
(1011, 176, 3, 9666.67),
(1012, 176, 4, 9666.67),
(1013, 176, 5, 9666.67),
(1014, 176, 6, 9666.67),
(1015, 177, 1, 9666.67),
(1016, 177, 2, 9666.67),
(1017, 177, 3, 9666.67),
(1018, 177, 4, 9666.67),
(1019, 177, 5, 9666.67),
(1020, 177, 6, 9666.67),
(1021, 178, 1, 10833.33),
(1022, 178, 2, 10833.33),
(1023, 178, 3, 10833.33),
(1024, 178, 4, 10833.33),
(1025, 178, 5, 10833.33),
(1026, 178, 6, 10833.33),
(1027, 179, 1, 10833.33),
(1028, 179, 2, 10833.33),
(1029, 179, 3, 10833.33),
(1030, 179, 4, 10833.33),
(1031, 179, 5, 10833.33),
(1032, 179, 6, 10833.33),
(1033, 180, 1, 10833.33),
(1034, 180, 2, 10833.33),
(1035, 180, 3, 10833.33),
(1036, 180, 4, 10833.33),
(1037, 180, 5, 10833.33),
(1038, 180, 6, 10833.33),
(1039, 181, 1, 9166.67),
(1040, 181, 2, 9166.67),
(1041, 181, 3, 9166.67),
(1042, 181, 4, 9166.67),
(1043, 181, 5, 9166.67),
(1044, 181, 6, 9166.67),
(1045, 182, 1, 9166.67),
(1046, 182, 2, 9166.67),
(1047, 182, 3, 9166.67),
(1048, 182, 4, 9166.67),
(1049, 182, 5, 9166.67),
(1050, 182, 6, 9166.67),
(1051, 183, 1, 9166.67),
(1052, 183, 2, 9166.67),
(1053, 183, 3, 9166.67),
(1054, 183, 4, 9166.67),
(1055, 183, 5, 9166.67),
(1056, 183, 6, 9166.67),
(1057, 184, 1, 10833.33),
(1058, 184, 2, 10833.33),
(1059, 184, 3, 10833.33),
(1060, 184, 4, 10833.33),
(1061, 184, 5, 10833.33),
(1062, 184, 6, 10833.33),
(1063, 185, 1, 10833.33),
(1064, 185, 2, 10833.33),
(1065, 185, 3, 10833.33),
(1066, 185, 4, 10833.33),
(1067, 185, 5, 10833.33),
(1068, 185, 6, 10833.33),
(1069, 186, 1, 10833.33),
(1070, 186, 2, 10833.33),
(1071, 186, 3, 10833.33),
(1072, 186, 4, 10833.33),
(1073, 186, 5, 10833.33),
(1074, 186, 6, 10833.33),
(1075, 187, 1, 11666.67),
(1076, 187, 2, 11666.67),
(1077, 187, 3, 11666.67),
(1078, 187, 4, 11666.67),
(1079, 187, 5, 11666.67),
(1080, 187, 6, 11666.67),
(1081, 188, 1, 10000.00),
(1082, 188, 2, 10000.00),
(1083, 188, 3, 10000.00),
(1084, 188, 4, 10000.00),
(1085, 188, 5, 10000.00),
(1086, 188, 6, 10000.00),
(1087, 189, 1, 10000.00),
(1088, 189, 2, 10000.00),
(1089, 189, 3, 10000.00),
(1090, 189, 4, 10000.00),
(1091, 189, 5, 10000.00),
(1092, 189, 6, 10000.00),
(1093, 190, 1, 10000.00),
(1094, 190, 2, 10000.00),
(1095, 190, 3, 10000.00),
(1096, 190, 4, 10000.00),
(1097, 190, 5, 10000.00),
(1098, 190, 6, 10000.00),
(1099, 191, 1, 9666.67),
(1100, 191, 2, 9666.67),
(1101, 191, 3, 9666.67),
(1102, 191, 4, 9666.67),
(1103, 191, 5, 9666.67),
(1104, 191, 6, 9666.67),
(1105, 192, 1, 9666.67),
(1106, 192, 2, 9666.67),
(1107, 192, 3, 9666.67),
(1108, 192, 4, 9666.67),
(1109, 192, 5, 9666.67),
(1110, 192, 6, 9666.67),
(1111, 193, 1, 9666.67),
(1112, 193, 2, 9666.67),
(1113, 193, 3, 9666.67),
(1114, 193, 4, 9666.67),
(1115, 193, 5, 9666.67),
(1116, 193, 6, 9666.67),
(1117, 194, 1, 10833.33),
(1118, 194, 2, 10833.33),
(1119, 194, 3, 10833.33),
(1120, 194, 4, 10833.33),
(1121, 194, 5, 10833.33),
(1122, 194, 6, 10833.33),
(1123, 195, 1, 10833.33),
(1124, 195, 2, 10833.33),
(1125, 195, 3, 10833.33),
(1126, 195, 4, 10833.33),
(1127, 195, 5, 10833.33),
(1128, 195, 6, 10833.33),
(1129, 196, 1, 10833.33),
(1130, 196, 2, 10833.33),
(1131, 196, 3, 10833.33),
(1132, 196, 4, 10833.33),
(1133, 196, 5, 10833.33),
(1134, 196, 6, 10833.33),
(1135, 197, 1, 9166.67),
(1136, 197, 2, 9166.67),
(1137, 197, 3, 9166.67),
(1138, 197, 4, 9166.67),
(1139, 197, 5, 9166.67),
(1140, 197, 6, 9166.67),
(1141, 198, 1, 9166.67),
(1142, 198, 2, 9166.67),
(1143, 198, 3, 9166.67),
(1144, 198, 4, 9166.67),
(1145, 198, 5, 9166.67),
(1146, 198, 6, 9166.67),
(1147, 199, 1, 9166.67),
(1148, 199, 2, 9166.67),
(1149, 199, 3, 9166.67),
(1150, 199, 4, 9166.67),
(1151, 199, 5, 9166.67),
(1152, 199, 6, 9166.67),
(1153, 200, 1, 10833.33),
(1154, 200, 2, 10833.33),
(1155, 200, 3, 10833.33),
(1156, 200, 4, 10833.33),
(1157, 200, 5, 10833.33),
(1158, 200, 6, 10833.33),
(1159, 201, 1, 10833.33),
(1160, 201, 2, 10833.33),
(1161, 201, 3, 10833.33),
(1162, 201, 4, 10833.33),
(1163, 201, 5, 10833.33),
(1164, 201, 6, 10833.33),
(1165, 202, 1, 10833.33),
(1166, 202, 2, 10833.33),
(1167, 202, 3, 10833.33),
(1168, 202, 4, 10833.33),
(1169, 202, 5, 10833.33),
(1170, 202, 6, 10833.33),
(1171, 203, 1, 11666.67),
(1172, 203, 2, 11666.67),
(1173, 203, 3, 11666.67),
(1174, 203, 4, 11666.67),
(1175, 203, 5, 11666.67),
(1176, 203, 6, 11666.67),
(1177, 204, 1, 11666.67),
(1178, 204, 2, 11666.67),
(1179, 204, 3, 11666.67),
(1180, 204, 4, 11666.67),
(1181, 204, 5, 11666.67),
(1182, 204, 6, 11666.67),
(1183, 205, 1, 11666.67),
(1184, 205, 2, 11666.67),
(1185, 205, 3, 11666.67),
(1186, 205, 4, 11666.67),
(1187, 205, 5, 11666.67),
(1188, 205, 6, 11666.67),
(1189, 206, 1, 10000.00),
(1190, 206, 2, 10000.00),
(1191, 206, 3, 10000.00),
(1192, 206, 4, 10000.00),
(1193, 206, 5, 10000.00),
(1194, 206, 6, 10000.00),
(1195, 207, 1, 9666.67),
(1196, 207, 2, 9666.67),
(1197, 207, 3, 9666.67),
(1198, 207, 4, 9666.67),
(1199, 207, 5, 9666.67),
(1200, 207, 6, 9666.67),
(1201, 208, 1, 9666.67),
(1202, 208, 2, 9666.67),
(1203, 208, 3, 9666.67),
(1204, 208, 4, 9666.67),
(1205, 208, 5, 9666.67),
(1206, 208, 6, 9666.67),
(1207, 209, 1, 9666.67),
(1208, 209, 2, 9666.67),
(1209, 209, 3, 9666.67),
(1210, 209, 4, 9666.67),
(1211, 209, 5, 9666.67),
(1212, 209, 6, 9666.67),
(1213, 210, 1, 10833.33),
(1214, 210, 2, 10833.33),
(1215, 210, 3, 10833.33),
(1216, 210, 4, 10833.33),
(1217, 210, 5, 10833.33),
(1218, 210, 6, 10833.33),
(1219, 211, 1, 10833.33),
(1220, 211, 2, 10833.33),
(1221, 211, 3, 10833.33),
(1222, 211, 4, 10833.33),
(1223, 211, 5, 10833.33),
(1224, 211, 6, 10833.33),
(1225, 212, 1, 10833.33),
(1226, 212, 2, 10833.33),
(1227, 212, 3, 10833.33),
(1228, 212, 4, 10833.33),
(1229, 212, 5, 10833.33),
(1230, 212, 6, 10833.33),
(1231, 213, 1, 9166.67),
(1232, 213, 2, 9166.67),
(1233, 213, 3, 9166.67),
(1234, 213, 4, 9166.67),
(1235, 213, 5, 9166.67),
(1236, 213, 6, 9166.67),
(1237, 214, 1, 9166.67),
(1238, 214, 2, 9166.67),
(1239, 214, 3, 9166.67),
(1240, 214, 4, 9166.67),
(1241, 214, 5, 9166.67),
(1242, 214, 6, 9166.67),
(1243, 215, 1, 9166.67),
(1244, 215, 2, 9166.67),
(1245, 215, 3, 9166.67),
(1246, 215, 4, 9166.67),
(1247, 215, 5, 9166.67),
(1248, 215, 6, 9166.67),
(1249, 216, 1, 10833.33),
(1250, 216, 2, 10833.33),
(1251, 216, 3, 10833.33),
(1252, 216, 4, 10833.33),
(1253, 216, 5, 10833.33),
(1254, 216, 6, 10833.33),
(1255, 217, 1, 10833.33),
(1256, 217, 2, 10833.33),
(1257, 217, 3, 10833.33),
(1258, 217, 4, 10833.33),
(1259, 217, 5, 10833.33),
(1260, 217, 6, 10833.33),
(1261, 218, 1, 10833.33),
(1262, 218, 2, 10833.33),
(1263, 218, 3, 10833.33),
(1264, 218, 4, 10833.33),
(1265, 218, 5, 10833.33),
(1266, 218, 6, 10833.33),
(1267, 219, 1, 11666.67),
(1268, 219, 2, 11666.67),
(1269, 219, 3, 11666.67),
(1270, 219, 4, 11666.67),
(1271, 219, 5, 11666.67),
(1272, 219, 6, 11666.67),
(1273, 220, 1, 11666.67),
(1274, 220, 2, 11666.67),
(1275, 220, 3, 11666.67),
(1276, 220, 4, 11666.67),
(1277, 220, 5, 11666.67),
(1278, 220, 6, 11666.67),
(1279, 221, 1, 11666.67),
(1280, 221, 2, 11666.67),
(1281, 221, 3, 11666.67),
(1282, 221, 4, 11666.67),
(1283, 221, 5, 11666.67),
(1284, 221, 6, 11666.67),
(1285, 222, 1, 10000.00),
(1286, 222, 2, 10000.00),
(1287, 222, 3, 10000.00),
(1288, 222, 4, 10000.00),
(1289, 222, 5, 10000.00),
(1290, 222, 6, 10000.00),
(1291, 223, 1, 10000.00),
(1292, 223, 2, 10000.00),
(1293, 223, 3, 10000.00),
(1294, 223, 4, 10000.00),
(1295, 223, 5, 10000.00),
(1296, 223, 6, 10000.00),
(1297, 224, 1, 10000.00),
(1298, 224, 2, 10000.00),
(1299, 224, 3, 10000.00),
(1300, 224, 4, 10000.00),
(1301, 224, 5, 10000.00),
(1302, 224, 6, 10000.00),
(1303, 225, 1, 9666.67),
(1304, 225, 2, 9666.67),
(1305, 225, 3, 9666.67),
(1306, 225, 4, 9666.67),
(1307, 225, 5, 9666.67),
(1308, 225, 6, 9666.67),
(1309, 226, 1, 10833.33),
(1310, 226, 2, 10833.33),
(1311, 226, 3, 10833.33),
(1312, 226, 4, 10833.33),
(1313, 226, 5, 10833.33),
(1314, 226, 6, 10833.33),
(1315, 227, 1, 10833.33),
(1316, 227, 2, 10833.33),
(1317, 227, 3, 10833.33),
(1318, 227, 4, 10833.33),
(1319, 227, 5, 10833.33),
(1320, 227, 6, 10833.33),
(1321, 228, 1, 10833.33),
(1322, 228, 2, 10833.33),
(1323, 228, 3, 10833.33),
(1324, 228, 4, 10833.33),
(1325, 228, 5, 10833.33),
(1326, 228, 6, 10833.33),
(1327, 229, 1, 9166.67),
(1328, 229, 2, 9166.67),
(1329, 229, 3, 9166.67),
(1330, 229, 4, 9166.67),
(1331, 229, 5, 9166.67),
(1332, 229, 6, 9166.67),
(1333, 230, 1, 9166.67),
(1334, 230, 2, 9166.67),
(1335, 230, 3, 9166.67),
(1336, 230, 4, 9166.67),
(1337, 230, 5, 9166.67),
(1338, 230, 6, 9166.67),
(1339, 231, 1, 9166.67),
(1340, 231, 2, 9166.67),
(1341, 231, 3, 9166.67),
(1342, 231, 4, 9166.67),
(1343, 231, 5, 9166.67),
(1344, 231, 6, 9166.67),
(1345, 232, 1, 10833.33),
(1346, 232, 2, 10833.33),
(1347, 232, 3, 10833.33),
(1348, 232, 4, 10833.33),
(1349, 232, 5, 10833.33),
(1350, 232, 6, 10833.33),
(1351, 233, 1, 10833.33),
(1352, 233, 2, 10833.33),
(1353, 233, 3, 10833.33),
(1354, 233, 4, 10833.33),
(1355, 233, 5, 10833.33),
(1356, 233, 6, 10833.33),
(1357, 234, 1, 10833.33),
(1358, 234, 2, 10833.33),
(1359, 234, 3, 10833.33),
(1360, 234, 4, 10833.33),
(1361, 234, 5, 10833.33),
(1362, 234, 6, 10833.33),
(1363, 235, 1, 11666.67),
(1364, 235, 2, 11666.67),
(1365, 235, 3, 11666.67),
(1366, 235, 4, 11666.67),
(1367, 235, 5, 11666.67),
(1368, 235, 6, 11666.67),
(1369, 236, 1, 11666.67),
(1370, 236, 2, 11666.67),
(1371, 236, 3, 11666.67),
(1372, 236, 4, 11666.67),
(1373, 236, 5, 11666.67),
(1374, 236, 6, 11666.67),
(1375, 237, 1, 11666.67),
(1376, 237, 2, 11666.67),
(1377, 237, 3, 11666.67),
(1378, 237, 4, 11666.67),
(1379, 237, 5, 11666.67),
(1380, 237, 6, 11666.67),
(1381, 238, 1, 10000.00),
(1382, 238, 2, 10000.00),
(1383, 238, 3, 10000.00),
(1384, 238, 4, 10000.00),
(1385, 238, 5, 10000.00),
(1386, 238, 6, 10000.00),
(1387, 239, 1, 10000.00),
(1388, 239, 2, 10000.00),
(1389, 239, 3, 10000.00),
(1390, 239, 4, 10000.00),
(1391, 239, 5, 10000.00),
(1392, 239, 6, 10000.00),
(1393, 240, 1, 10000.00),
(1394, 240, 2, 10000.00),
(1395, 240, 3, 10000.00),
(1396, 240, 4, 10000.00),
(1397, 240, 5, 10000.00),
(1398, 240, 6, 10000.00),
(1399, 241, 1, 9666.67),
(1400, 241, 2, 9666.67),
(1401, 241, 3, 9666.67),
(1402, 241, 4, 9666.67),
(1403, 241, 5, 9666.67),
(1404, 241, 6, 9666.67),
(1405, 242, 1, 9666.67),
(1406, 242, 2, 9666.67),
(1407, 242, 3, 9666.67),
(1408, 242, 4, 9666.67),
(1409, 242, 5, 9666.67),
(1410, 242, 6, 9666.67),
(1411, 243, 1, 9666.67),
(1412, 243, 2, 9666.67),
(1413, 243, 3, 9666.67),
(1414, 243, 4, 9666.67),
(1415, 243, 5, 9666.67),
(1416, 243, 6, 9666.67),
(1417, 244, 1, 10833.33),
(1418, 244, 2, 10833.33),
(1419, 244, 3, 10833.33),
(1420, 244, 4, 10833.33),
(1421, 244, 5, 10833.33),
(1422, 244, 6, 10833.33),
(1423, 245, 1, 10833.33),
(1424, 245, 2, 10833.33),
(1425, 245, 3, 10833.33),
(1426, 245, 4, 10833.33),
(1427, 245, 5, 10833.33),
(1428, 245, 6, 10833.33),
(1429, 246, 1, 10833.33),
(1430, 246, 2, 10833.33),
(1431, 246, 3, 10833.33),
(1432, 246, 4, 10833.33),
(1433, 246, 5, 10833.33),
(1434, 246, 6, 10833.33),
(1435, 247, 1, 9166.67),
(1436, 247, 2, 9166.67),
(1437, 247, 3, 9166.67),
(1438, 247, 4, 9166.67),
(1439, 247, 5, 9166.67),
(1440, 247, 6, 9166.67),
(1441, 248, 1, 9166.67),
(1442, 248, 2, 9166.67),
(1443, 248, 3, 9166.67),
(1444, 248, 4, 9166.67),
(1445, 248, 5, 9166.67),
(1446, 248, 6, 9166.67),
(1447, 249, 1, 9166.67),
(1448, 249, 2, 9166.67),
(1449, 249, 3, 9166.67),
(1450, 249, 4, 9166.67),
(1451, 249, 5, 9166.67),
(1452, 249, 6, 9166.67),
(1453, 250, 1, 10833.33),
(1454, 250, 2, 10833.33),
(1455, 250, 3, 10833.33),
(1456, 250, 4, 10833.33),
(1457, 250, 5, 10833.33),
(1458, 250, 6, 10833.33),
(1459, 251, 1, 10833.33),
(1460, 251, 2, 10833.33),
(1461, 251, 3, 10833.33),
(1462, 251, 4, 10833.33),
(1463, 251, 5, 10833.33),
(1464, 251, 6, 10833.33),
(1465, 252, 1, 10833.33),
(1466, 252, 2, 10833.33),
(1467, 252, 3, 10833.33),
(1468, 252, 4, 10833.33),
(1469, 252, 5, 10833.33),
(1470, 252, 6, 10833.33),
(1471, 253, 1, 11666.67),
(1472, 253, 2, 11666.67),
(1473, 253, 3, 11666.67),
(1474, 253, 4, 11666.67),
(1475, 253, 5, 11666.67),
(1476, 253, 6, 11666.67),
(1477, 254, 1, 11666.67),
(1478, 254, 2, 11666.67),
(1479, 254, 3, 11666.67),
(1480, 254, 4, 11666.67),
(1481, 254, 5, 11666.67),
(1482, 254, 6, 11666.67),
(1483, 255, 1, 11666.67),
(1484, 255, 2, 11666.67),
(1485, 255, 3, 11666.67),
(1486, 255, 4, 11666.67),
(1487, 255, 5, 11666.67),
(1488, 255, 6, 11666.67),
(1489, 256, 1, 10000.00),
(1490, 256, 2, 10000.00),
(1491, 256, 3, 10000.00),
(1492, 256, 4, 10000.00),
(1493, 256, 5, 10000.00),
(1494, 256, 6, 10000.00),
(1495, 257, 1, 10000.00),
(1496, 257, 2, 10000.00),
(1497, 257, 3, 10000.00),
(1498, 257, 4, 10000.00),
(1499, 257, 5, 10000.00),
(1500, 257, 6, 10000.00),
(1501, 258, 1, 10000.00),
(1502, 258, 2, 10000.00),
(1503, 258, 3, 10000.00),
(1504, 258, 4, 10000.00),
(1505, 258, 5, 10000.00),
(1506, 258, 6, 10000.00),
(1507, 259, 1, 9666.67),
(1508, 259, 2, 9666.67),
(1509, 259, 3, 9666.67),
(1510, 259, 4, 9666.67),
(1511, 259, 5, 9666.67),
(1512, 259, 6, 9666.67),
(1513, 260, 1, 9666.67),
(1514, 260, 2, 9666.67),
(1515, 260, 3, 9666.67),
(1516, 260, 4, 9666.67),
(1517, 260, 5, 9666.67),
(1518, 260, 6, 9666.67),
(1519, 261, 1, 9666.67),
(1520, 261, 2, 9666.67),
(1521, 261, 3, 9666.67),
(1522, 261, 4, 9666.67),
(1523, 261, 5, 9666.67),
(1524, 261, 6, 9666.67),
(1525, 262, 1, 10833.33),
(1526, 262, 2, 10833.33),
(1527, 262, 3, 10833.33),
(1528, 262, 4, 10833.33),
(1529, 262, 5, 10833.33),
(1530, 262, 6, 10833.33),
(1531, 263, 1, 10833.33),
(1532, 263, 2, 10833.33),
(1533, 263, 3, 10833.33),
(1534, 263, 4, 10833.33),
(1535, 263, 5, 10833.33),
(1536, 263, 6, 10833.33),
(1537, 264, 1, 10833.33),
(1538, 264, 2, 10833.33),
(1539, 264, 3, 10833.33),
(1540, 264, 4, 10833.33),
(1541, 264, 5, 10833.33),
(1542, 264, 6, 10833.33),
(1543, 265, 1, 9166.67),
(1544, 265, 2, 9166.67),
(1545, 265, 3, 9166.67),
(1546, 265, 4, 9166.67),
(1547, 265, 5, 9166.67),
(1548, 265, 6, 9166.67),
(1549, 266, 1, 9166.67),
(1550, 266, 2, 9166.67),
(1551, 266, 3, 9166.67),
(1552, 266, 4, 9166.67),
(1553, 266, 5, 9166.67),
(1554, 266, 6, 9166.67),
(1555, 267, 1, 9166.67),
(1556, 267, 2, 9166.67),
(1557, 267, 3, 9166.67),
(1558, 267, 4, 9166.67),
(1559, 267, 5, 9166.67),
(1560, 267, 6, 9166.67),
(1561, 268, 1, 10833.33),
(1562, 268, 2, 10833.33),
(1563, 268, 3, 10833.33),
(1564, 268, 4, 10833.33),
(1565, 268, 5, 10833.33),
(1566, 268, 6, 10833.33),
(1567, 269, 1, 10833.33),
(1568, 269, 2, 10833.33),
(1569, 269, 3, 10833.33),
(1570, 269, 4, 10833.33),
(1571, 269, 5, 10833.33),
(1572, 269, 6, 10833.33),
(1573, 270, 1, 10833.33),
(1574, 270, 2, 10833.33),
(1575, 270, 3, 10833.33),
(1576, 270, 4, 10833.33),
(1577, 270, 5, 10833.33),
(1578, 270, 6, 10833.33),
(1579, 271, 1, 11666.67),
(1580, 271, 2, 11666.67),
(1581, 271, 3, 11666.67),
(1582, 271, 4, 11666.67),
(1583, 271, 5, 11666.67),
(1584, 271, 6, 11666.67),
(1585, 272, 1, 11666.67),
(1586, 272, 2, 11666.67),
(1587, 272, 3, 11666.67),
(1588, 272, 4, 11666.67),
(1589, 272, 5, 11666.67),
(1590, 272, 6, 11666.67),
(1591, 273, 1, 11666.67),
(1592, 273, 2, 11666.67),
(1593, 273, 3, 11666.67),
(1594, 273, 4, 11666.67),
(1595, 273, 5, 11666.67),
(1596, 273, 6, 11666.67),
(1597, 274, 1, 10000.00),
(1598, 274, 2, 10000.00),
(1599, 274, 3, 10000.00),
(1600, 274, 4, 10000.00),
(1601, 274, 5, 10000.00),
(1602, 274, 6, 10000.00),
(1603, 275, 1, 10000.00),
(1604, 275, 2, 10000.00),
(1605, 275, 3, 10000.00),
(1606, 275, 4, 10000.00),
(1607, 275, 5, 10000.00),
(1608, 275, 6, 10000.00),
(1609, 276, 1, 10000.00),
(1610, 276, 2, 10000.00),
(1611, 276, 3, 10000.00),
(1612, 276, 4, 10000.00),
(1613, 276, 5, 10000.00),
(1614, 276, 6, 10000.00),
(1615, 277, 1, 9666.67),
(1616, 277, 2, 9666.67),
(1617, 277, 3, 9666.67),
(1618, 277, 4, 9666.67),
(1619, 277, 5, 9666.67),
(1620, 277, 6, 9666.67),
(1621, 278, 1, 9666.67),
(1622, 278, 2, 9666.67),
(1623, 278, 3, 9666.67),
(1624, 278, 4, 9666.67),
(1625, 278, 5, 9666.67),
(1626, 278, 6, 9666.67),
(1627, 279, 1, 9666.67),
(1628, 279, 2, 9666.67),
(1629, 279, 3, 9666.67),
(1630, 279, 4, 9666.67),
(1631, 279, 5, 9666.67),
(1632, 279, 6, 9666.67),
(1633, 280, 1, 10833.33),
(1634, 280, 2, 10833.33),
(1635, 280, 3, 10833.33),
(1636, 280, 4, 10833.33),
(1637, 280, 5, 10833.33),
(1638, 280, 6, 10833.33),
(1639, 281, 1, 10833.33),
(1640, 281, 2, 10833.33),
(1641, 281, 3, 10833.33),
(1642, 281, 4, 10833.33),
(1643, 281, 5, 10833.33),
(1644, 281, 6, 10833.33),
(1645, 282, 1, 10833.33),
(1646, 282, 2, 10833.33),
(1647, 282, 3, 10833.33),
(1648, 282, 4, 10833.33),
(1649, 282, 5, 10833.33),
(1650, 282, 6, 10833.33),
(1651, 283, 1, 9166.67),
(1652, 283, 2, 9166.67),
(1653, 283, 3, 9166.67),
(1654, 283, 4, 9166.67),
(1655, 283, 5, 9166.67),
(1656, 283, 6, 9166.67),
(1657, 284, 1, 9166.67),
(1658, 284, 2, 9166.67),
(1659, 284, 3, 9166.67),
(1660, 284, 4, 9166.67),
(1661, 284, 5, 9166.67),
(1662, 284, 6, 9166.67),
(1663, 285, 1, 9166.67),
(1664, 285, 2, 9166.67),
(1665, 285, 3, 9166.67),
(1666, 285, 4, 9166.67),
(1667, 285, 5, 9166.67),
(1668, 285, 6, 9166.67),
(1669, 286, 1, 10833.33),
(1670, 286, 2, 10833.33),
(1671, 286, 3, 10833.33),
(1672, 286, 4, 10833.33),
(1673, 286, 5, 10833.33),
(1674, 286, 6, 10833.33),
(1675, 287, 1, 10833.33),
(1676, 287, 2, 10833.33),
(1677, 287, 3, 10833.33),
(1678, 287, 4, 10833.33),
(1679, 287, 5, 10833.33),
(1680, 287, 6, 10833.33),
(1681, 288, 1, 10833.33),
(1682, 288, 2, 10833.33),
(1683, 288, 3, 10833.33),
(1684, 288, 4, 10833.33),
(1685, 288, 5, 10833.33),
(1686, 288, 6, 10833.33),
(1687, 289, 1, 11666.67),
(1688, 289, 2, 11666.67),
(1689, 289, 3, 11666.67),
(1690, 289, 4, 11666.67),
(1691, 289, 5, 11666.67),
(1692, 289, 6, 11666.67),
(1693, 290, 1, 11666.67),
(1694, 290, 2, 11666.67),
(1695, 290, 3, 11666.67),
(1696, 290, 4, 11666.67),
(1697, 290, 5, 11666.67),
(1698, 290, 6, 11666.67),
(1699, 291, 1, 11666.67),
(1700, 291, 2, 11666.67),
(1701, 291, 3, 11666.67),
(1702, 291, 4, 11666.67),
(1703, 291, 5, 11666.67),
(1704, 291, 6, 11666.67),
(1705, 292, 1, 10000.00),
(1706, 292, 2, 10000.00),
(1707, 292, 3, 10000.00),
(1708, 292, 4, 10000.00),
(1709, 292, 5, 10000.00),
(1710, 292, 6, 10000.00),
(1711, 293, 1, 10000.00),
(1712, 293, 2, 10000.00),
(1713, 293, 3, 10000.00),
(1714, 293, 4, 10000.00),
(1715, 293, 5, 10000.00),
(1716, 293, 6, 10000.00),
(1717, 294, 1, 10000.00),
(1718, 294, 2, 10000.00),
(1719, 294, 3, 10000.00),
(1720, 294, 4, 10000.00),
(1721, 294, 5, 10000.00),
(1722, 294, 6, 10000.00),
(1723, 295, 1, 9666.67),
(1724, 295, 2, 9666.67),
(1725, 295, 3, 9666.67),
(1726, 295, 4, 9666.67),
(1727, 295, 5, 9666.67),
(1728, 295, 6, 9666.67),
(1729, 296, 1, 9666.67),
(1730, 296, 2, 9666.67),
(1731, 296, 3, 9666.67),
(1732, 296, 4, 9666.67),
(1733, 296, 5, 9666.67),
(1734, 296, 6, 9666.67),
(1735, 297, 1, 9666.67),
(1736, 297, 2, 9666.67),
(1737, 297, 3, 9666.67),
(1738, 297, 4, 9666.67),
(1739, 297, 5, 9666.67),
(1740, 297, 6, 9666.67),
(1741, 298, 1, 10833.33),
(1742, 298, 2, 10833.33),
(1743, 298, 3, 10833.33),
(1744, 298, 4, 10833.33),
(1745, 298, 5, 10833.33),
(1746, 298, 6, 10833.33),
(1747, 299, 1, 10833.33),
(1748, 299, 2, 10833.33),
(1749, 299, 3, 10833.33),
(1750, 299, 4, 10833.33),
(1751, 299, 5, 10833.33),
(1752, 299, 6, 10833.33),
(1753, 300, 1, 10833.33),
(1754, 300, 2, 10833.33),
(1755, 300, 3, 10833.33),
(1756, 300, 4, 10833.33),
(1757, 300, 5, 10833.33),
(1758, 300, 6, 10833.33),
(1759, 301, 1, 9166.67),
(1760, 301, 2, 9166.67),
(1761, 301, 3, 9166.67),
(1762, 301, 4, 9166.67),
(1763, 301, 5, 9166.67),
(1764, 301, 6, 9166.67),
(1765, 302, 1, 9166.67),
(1766, 302, 2, 9166.67),
(1767, 302, 3, 9166.67),
(1768, 302, 4, 9166.67),
(1769, 302, 5, 9166.67),
(1770, 302, 6, 9166.67),
(1771, 303, 1, 9166.67),
(1772, 303, 2, 9166.67),
(1773, 303, 3, 9166.67),
(1774, 303, 4, 9166.67),
(1775, 303, 5, 9166.67),
(1776, 303, 6, 9166.67),
(1777, 304, 1, 10833.33),
(1778, 304, 2, 10833.33),
(1779, 304, 3, 10833.33),
(1780, 304, 4, 10833.33),
(1781, 304, 5, 10833.33),
(1782, 304, 6, 10833.33),
(1783, 305, 1, 10833.33),
(1784, 305, 2, 10833.33),
(1785, 305, 3, 10833.33),
(1786, 305, 4, 10833.33),
(1787, 305, 5, 10833.33),
(1788, 305, 6, 10833.33),
(1789, 306, 1, 10833.33),
(1790, 306, 2, 10833.33),
(1791, 306, 3, 10833.33),
(1792, 306, 4, 10833.33),
(1793, 306, 5, 10833.33),
(1794, 306, 6, 10833.33),
(1795, 307, 1, 11666.67),
(1796, 307, 2, 11666.67),
(1797, 307, 3, 11666.67),
(1798, 307, 4, 11666.67),
(1799, 307, 5, 11666.67),
(1800, 307, 6, 11666.67),
(1801, 308, 1, 11666.67),
(1802, 308, 2, 11666.67),
(1803, 308, 3, 11666.67),
(1804, 308, 4, 11666.67),
(1805, 308, 5, 11666.67),
(1806, 308, 6, 11666.67),
(1807, 309, 1, 11666.67),
(1808, 309, 2, 11666.67),
(1809, 309, 3, 11666.67),
(1810, 309, 4, 11666.67),
(1811, 309, 5, 11666.67),
(1812, 309, 6, 11666.67),
(1813, 310, 1, 10000.00),
(1814, 310, 2, 10000.00),
(1815, 310, 3, 10000.00),
(1816, 310, 4, 10000.00),
(1817, 310, 5, 10000.00),
(1818, 310, 6, 10000.00),
(1819, 311, 1, 10000.00),
(1820, 311, 2, 10000.00),
(1821, 311, 3, 10000.00),
(1822, 311, 4, 10000.00),
(1823, 311, 5, 10000.00),
(1824, 311, 6, 10000.00),
(1825, 312, 1, 10000.00),
(1826, 312, 2, 10000.00),
(1827, 312, 3, 10000.00),
(1828, 312, 4, 10000.00),
(1829, 312, 5, 10000.00),
(1830, 312, 6, 10000.00),
(1831, 313, 1, 9666.67),
(1832, 313, 2, 9666.67),
(1833, 313, 3, 9666.67),
(1834, 313, 4, 9666.67),
(1835, 313, 5, 9666.67),
(1836, 313, 6, 9666.67),
(1837, 314, 1, 9666.67),
(1838, 314, 2, 9666.67),
(1839, 314, 3, 9666.67),
(1840, 314, 4, 9666.67),
(1841, 314, 5, 9666.67),
(1842, 314, 6, 9666.67),
(1843, 315, 1, 9666.67),
(1844, 315, 2, 9666.67),
(1845, 315, 3, 9666.67),
(1846, 315, 4, 9666.67),
(1847, 315, 5, 9666.67),
(1848, 315, 6, 9666.67),
(1849, 316, 1, 10833.33),
(1850, 316, 2, 10833.33),
(1851, 316, 3, 10833.33),
(1852, 316, 4, 10833.33),
(1853, 316, 5, 10833.33),
(1854, 316, 6, 10833.33),
(1855, 317, 1, 10833.33),
(1856, 317, 2, 10833.33),
(1857, 317, 3, 10833.33),
(1858, 317, 4, 10833.33),
(1859, 317, 5, 10833.33),
(1860, 317, 6, 10833.33),
(1861, 318, 1, 10833.33),
(1862, 318, 2, 10833.33),
(1863, 318, 3, 10833.33),
(1864, 318, 4, 10833.33),
(1865, 318, 5, 10833.33),
(1866, 318, 6, 10833.33),
(1867, 319, 1, 9166.67),
(1868, 319, 2, 9166.67),
(1869, 319, 3, 9166.67),
(1870, 319, 4, 9166.67),
(1871, 319, 5, 9166.67),
(1872, 319, 6, 9166.67),
(1873, 320, 1, 9166.67),
(1874, 320, 2, 9166.67),
(1875, 320, 3, 9166.67),
(1876, 320, 4, 9166.67),
(1877, 320, 5, 9166.67),
(1878, 320, 6, 9166.67),
(1879, 321, 1, 9166.67),
(1880, 321, 2, 9166.67),
(1881, 321, 3, 9166.67),
(1882, 321, 4, 9166.67),
(1883, 321, 5, 9166.67),
(1884, 321, 6, 9166.67),
(1885, 322, 1, 10833.33),
(1886, 322, 2, 10833.33),
(1887, 322, 3, 10833.33),
(1888, 322, 4, 10833.33),
(1889, 322, 5, 10833.33),
(1890, 322, 6, 10833.33),
(1891, 323, 1, 10833.33),
(1892, 323, 2, 10833.33),
(1893, 323, 3, 10833.33),
(1894, 323, 4, 10833.33),
(1895, 323, 5, 10833.33),
(1896, 323, 6, 10833.33),
(1897, 324, 1, 10833.33),
(1898, 324, 2, 10833.33),
(1899, 324, 3, 10833.33),
(1900, 324, 4, 10833.33),
(1901, 324, 5, 10833.33),
(1902, 324, 6, 10833.33),
(1903, 325, 1, 11666.67),
(1904, 325, 2, 11666.67),
(1905, 325, 3, 11666.67),
(1906, 325, 4, 11666.67),
(1907, 325, 5, 11666.67),
(1908, 325, 6, 11666.67),
(1909, 326, 1, 11666.67),
(1910, 326, 2, 11666.67),
(1911, 326, 3, 11666.67),
(1912, 326, 4, 11666.67),
(1913, 326, 5, 11666.67),
(1914, 326, 6, 11666.67),
(1915, 327, 1, 11666.67),
(1916, 327, 2, 11666.67),
(1917, 327, 3, 11666.67),
(1918, 327, 4, 11666.67),
(1919, 327, 5, 11666.67),
(1920, 327, 6, 11666.67),
(1921, 328, 1, 10000.00),
(1922, 328, 2, 10000.00),
(1923, 328, 3, 10000.00),
(1924, 328, 4, 10000.00),
(1925, 328, 5, 10000.00),
(1926, 328, 6, 10000.00),
(1927, 329, 1, 10000.00),
(1928, 329, 2, 10000.00),
(1929, 329, 3, 10000.00),
(1930, 329, 4, 10000.00),
(1931, 329, 5, 10000.00),
(1932, 329, 6, 10000.00),
(1933, 330, 1, 10000.00),
(1934, 330, 2, 10000.00),
(1935, 330, 3, 10000.00),
(1936, 330, 4, 10000.00),
(1937, 330, 5, 10000.00),
(1938, 330, 6, 10000.00),
(1939, 331, 1, 9666.67),
(1940, 331, 2, 9666.67),
(1941, 331, 3, 9666.67),
(1942, 331, 4, 9666.67),
(1943, 331, 5, 9666.67),
(1944, 331, 6, 9666.67),
(1945, 332, 1, 9666.67),
(1946, 332, 2, 9666.67),
(1947, 332, 3, 9666.67),
(1948, 332, 4, 9666.67),
(1949, 332, 5, 9666.67),
(1950, 332, 6, 9666.67),
(1951, 333, 1, 9666.67),
(1952, 333, 2, 9666.67),
(1953, 333, 3, 9666.67),
(1954, 333, 4, 9666.67),
(1955, 333, 5, 9666.67),
(1956, 333, 6, 9666.67),
(1957, 334, 1, 10833.33),
(1958, 334, 2, 10833.33),
(1959, 334, 3, 10833.33),
(1960, 334, 4, 10833.33),
(1961, 334, 5, 10833.33),
(1962, 334, 6, 10833.33),
(1963, 335, 1, 10833.33),
(1964, 335, 2, 10833.33),
(1965, 335, 3, 10833.33),
(1966, 335, 4, 10833.33),
(1967, 335, 5, 10833.33),
(1968, 335, 6, 10833.33),
(1969, 336, 1, 10833.33),
(1970, 336, 2, 10833.33),
(1971, 336, 3, 10833.33),
(1972, 336, 4, 10833.33),
(1973, 336, 5, 10833.33),
(1974, 336, 6, 10833.33),
(1975, 337, 1, 9166.67),
(1976, 337, 2, 9166.67),
(1977, 337, 3, 9166.67),
(1978, 337, 4, 9166.67),
(1979, 337, 5, 9166.67),
(1980, 337, 6, 9166.67),
(1981, 338, 1, 9166.67),
(1982, 338, 2, 9166.67),
(1983, 338, 3, 9166.67),
(1984, 338, 4, 9166.67),
(1985, 338, 5, 9166.67),
(1986, 338, 6, 9166.67),
(1987, 339, 1, 9166.67),
(1988, 339, 2, 9166.67),
(1989, 339, 3, 9166.67),
(1990, 339, 4, 9166.67),
(1991, 339, 5, 9166.67),
(1992, 339, 6, 9166.67),
(1993, 340, 1, 10833.33),
(1994, 340, 2, 10833.33),
(1995, 340, 3, 10833.33),
(1996, 340, 4, 10833.33),
(1997, 340, 5, 10833.33),
(1998, 340, 6, 10833.33),
(1999, 341, 1, 10833.33),
(2000, 341, 2, 10833.33),
(2001, 341, 3, 10833.33),
(2002, 341, 4, 10833.33),
(2003, 341, 5, 10833.33),
(2004, 341, 6, 10833.33),
(2005, 342, 1, 10833.33),
(2006, 342, 2, 10833.33),
(2007, 342, 3, 10833.33),
(2008, 342, 4, 10833.33),
(2009, 342, 5, 10833.33),
(2010, 342, 6, 10833.33),
(2011, 343, 1, 11666.67),
(2012, 343, 2, 11666.67),
(2013, 343, 3, 11666.67),
(2014, 343, 4, 11666.67),
(2015, 343, 5, 11666.67),
(2016, 343, 6, 11666.67),
(2017, 344, 1, 11666.67),
(2018, 344, 2, 11666.67),
(2019, 344, 3, 11666.67),
(2020, 344, 4, 11666.67),
(2021, 344, 5, 11666.67),
(2022, 344, 6, 11666.67),
(2023, 345, 1, 11666.67),
(2024, 345, 2, 11666.67),
(2025, 345, 3, 11666.67),
(2026, 345, 4, 11666.67),
(2027, 345, 5, 11666.67),
(2028, 345, 6, 11666.67),
(2029, 346, 1, 10000.00),
(2030, 346, 2, 10000.00),
(2031, 346, 3, 10000.00),
(2032, 346, 4, 10000.00),
(2033, 346, 5, 10000.00),
(2034, 346, 6, 10000.00),
(2035, 347, 1, 10000.00),
(2036, 347, 2, 10000.00),
(2037, 347, 3, 10000.00),
(2038, 347, 4, 10000.00),
(2039, 347, 5, 10000.00),
(2040, 347, 6, 10000.00),
(2041, 348, 1, 10000.00),
(2042, 348, 2, 10000.00),
(2043, 348, 3, 10000.00),
(2044, 348, 4, 10000.00),
(2045, 348, 5, 10000.00),
(2046, 348, 6, 10000.00),
(2047, 349, 1, 9666.67),
(2048, 349, 2, 9666.67),
(2049, 349, 3, 9666.67),
(2050, 349, 4, 9666.67),
(2051, 349, 5, 9666.67),
(2052, 349, 6, 9666.67),
(2053, 350, 1, 9666.67),
(2054, 350, 2, 9666.67),
(2055, 350, 3, 9666.67),
(2056, 350, 4, 9666.67),
(2057, 350, 5, 9666.67),
(2058, 350, 6, 9666.67),
(2059, 351, 1, 9666.67),
(2060, 351, 2, 9666.67),
(2061, 351, 3, 9666.67),
(2062, 351, 4, 9666.67),
(2063, 351, 5, 9666.67),
(2064, 351, 6, 9666.67),
(2065, 352, 1, 10833.33),
(2066, 352, 2, 10833.33),
(2067, 352, 3, 10833.33),
(2068, 352, 4, 10833.33),
(2069, 352, 5, 10833.33),
(2070, 352, 6, 10833.33),
(2071, 353, 1, 10833.33),
(2072, 353, 2, 10833.33),
(2073, 353, 3, 10833.33),
(2074, 353, 4, 10833.33),
(2075, 353, 5, 10833.33),
(2076, 353, 6, 10833.33),
(2077, 354, 1, 10833.33),
(2078, 354, 2, 10833.33),
(2079, 354, 3, 10833.33),
(2080, 354, 4, 10833.33),
(2081, 354, 5, 10833.33),
(2082, 354, 6, 10833.33),
(2083, 355, 1, 9166.67),
(2084, 355, 2, 9166.67),
(2085, 355, 3, 9166.67),
(2086, 355, 4, 9166.67),
(2087, 355, 5, 9166.67),
(2088, 355, 6, 9166.67),
(2089, 356, 1, 9166.67),
(2090, 356, 2, 9166.67),
(2091, 356, 3, 9166.67),
(2092, 356, 4, 9166.67),
(2093, 356, 5, 9166.67),
(2094, 356, 6, 9166.67),
(2095, 357, 1, 9166.67),
(2096, 357, 2, 9166.67),
(2097, 357, 3, 9166.67),
(2098, 357, 4, 9166.67),
(2099, 357, 5, 9166.67),
(2100, 357, 6, 9166.67),
(2101, 358, 1, 10833.33),
(2102, 358, 2, 10833.33),
(2103, 358, 3, 10833.33),
(2104, 358, 4, 10833.33),
(2105, 358, 5, 10833.33),
(2106, 358, 6, 10833.33),
(2107, 359, 1, 10833.33),
(2108, 359, 2, 10833.33),
(2109, 359, 3, 10833.33),
(2110, 359, 4, 10833.33),
(2111, 359, 5, 10833.33),
(2112, 359, 6, 10833.33),
(2113, 360, 1, 10833.33),
(2114, 360, 2, 10833.33),
(2115, 360, 3, 10833.33),
(2116, 360, 4, 10833.33),
(2117, 360, 5, 10833.33),
(2118, 360, 6, 10833.33),
(2119, 361, 1, 11666.67),
(2120, 361, 2, 11666.67),
(2121, 361, 3, 11666.67),
(2122, 361, 4, 11666.67),
(2123, 361, 5, 11666.67),
(2124, 361, 6, 11666.67),
(2125, 362, 1, 11666.67),
(2126, 362, 2, 11666.67),
(2127, 362, 3, 11666.67),
(2128, 362, 4, 11666.67),
(2129, 362, 5, 11666.67),
(2130, 362, 6, 11666.67),
(2131, 363, 1, 11666.67),
(2132, 363, 2, 11666.67),
(2133, 363, 3, 11666.67),
(2134, 363, 4, 11666.67),
(2135, 363, 5, 11666.67),
(2136, 363, 6, 11666.67),
(2137, 364, 1, 10000.00),
(2138, 364, 2, 10000.00),
(2139, 364, 3, 10000.00),
(2140, 364, 4, 10000.00),
(2141, 364, 5, 10000.00),
(2142, 364, 6, 10000.00),
(2143, 365, 1, 10000.00),
(2144, 365, 2, 10000.00),
(2145, 365, 3, 10000.00),
(2146, 365, 4, 10000.00),
(2147, 365, 5, 10000.00),
(2148, 365, 6, 10000.00),
(2149, 366, 1, 10000.00),
(2150, 366, 2, 10000.00),
(2151, 366, 3, 10000.00),
(2152, 366, 4, 10000.00),
(2153, 366, 5, 10000.00),
(2154, 366, 6, 10000.00),
(2155, 367, 1, 9666.67),
(2156, 367, 2, 9666.67),
(2157, 367, 3, 9666.67),
(2158, 367, 4, 9666.67),
(2159, 367, 5, 9666.67),
(2160, 367, 6, 9666.67),
(2161, 368, 1, 9666.67),
(2162, 368, 2, 9666.67),
(2163, 368, 3, 9666.67),
(2164, 368, 4, 9666.67),
(2165, 368, 5, 9666.67),
(2166, 368, 6, 9666.67),
(2167, 369, 1, 9666.67),
(2168, 369, 2, 9666.67),
(2169, 369, 3, 9666.67),
(2170, 369, 4, 9666.67),
(2171, 369, 5, 9666.67),
(2172, 369, 6, 9666.67),
(2173, 370, 1, 10833.33),
(2174, 370, 2, 10833.33),
(2175, 370, 3, 10833.33),
(2176, 370, 4, 10833.33),
(2177, 370, 5, 10833.33),
(2178, 370, 6, 10833.33),
(2179, 371, 1, 10833.33),
(2180, 371, 2, 10833.33),
(2181, 371, 3, 10833.33),
(2182, 371, 4, 10833.33);
INSERT INTO `fee_structure_details` (`id`, `fee_structure_id`, `fee_head_id`, `amount`) VALUES
(2183, 371, 5, 10833.33),
(2184, 371, 6, 10833.33),
(2185, 372, 1, 10833.33),
(2186, 372, 2, 10833.33),
(2187, 372, 3, 10833.33),
(2188, 372, 4, 10833.33),
(2189, 372, 5, 10833.33),
(2190, 372, 6, 10833.33),
(2191, 373, 1, 9166.67),
(2192, 373, 2, 9166.67),
(2193, 373, 3, 9166.67),
(2194, 373, 4, 9166.67),
(2195, 373, 5, 9166.67),
(2196, 373, 6, 9166.67),
(2197, 374, 1, 9166.67),
(2198, 374, 2, 9166.67),
(2199, 374, 3, 9166.67),
(2200, 374, 4, 9166.67),
(2201, 374, 5, 9166.67),
(2202, 374, 6, 9166.67),
(2203, 375, 1, 9166.67),
(2204, 375, 2, 9166.67),
(2205, 375, 3, 9166.67),
(2206, 375, 4, 9166.67),
(2207, 375, 5, 9166.67),
(2208, 375, 6, 9166.67),
(2209, 376, 1, 10833.33),
(2210, 376, 2, 10833.33),
(2211, 376, 3, 10833.33),
(2212, 376, 4, 10833.33),
(2213, 376, 5, 10833.33),
(2214, 376, 6, 10833.33),
(2215, 377, 1, 10833.33),
(2216, 377, 2, 10833.33),
(2217, 377, 3, 10833.33),
(2218, 377, 4, 10833.33),
(2219, 377, 5, 10833.33),
(2220, 377, 6, 10833.33),
(2221, 378, 1, 10833.33),
(2222, 378, 2, 10833.33),
(2223, 378, 3, 10833.33),
(2224, 378, 4, 10833.33),
(2225, 378, 5, 10833.33),
(2226, 378, 6, 10833.33),
(2227, 379, 1, 11666.67),
(2228, 379, 2, 11666.67),
(2229, 379, 3, 11666.67),
(2230, 379, 4, 11666.67),
(2231, 379, 5, 11666.67),
(2232, 379, 6, 11666.67),
(2233, 380, 1, 11666.67),
(2234, 380, 2, 11666.67),
(2235, 380, 3, 11666.67),
(2236, 380, 4, 11666.67),
(2237, 380, 5, 11666.67),
(2238, 380, 6, 11666.67),
(2239, 381, 1, 11666.67),
(2240, 381, 2, 11666.67),
(2241, 381, 3, 11666.67),
(2242, 381, 4, 11666.67),
(2243, 381, 5, 11666.67),
(2244, 381, 6, 11666.67),
(2245, 382, 1, 10000.00),
(2246, 382, 2, 10000.00),
(2247, 382, 3, 10000.00),
(2248, 382, 4, 10000.00),
(2249, 382, 5, 10000.00),
(2250, 382, 6, 10000.00),
(2251, 383, 1, 10000.00),
(2252, 383, 2, 10000.00),
(2253, 383, 3, 10000.00),
(2254, 383, 4, 10000.00),
(2255, 383, 5, 10000.00),
(2256, 383, 6, 10000.00),
(2257, 384, 1, 10000.00),
(2258, 384, 2, 10000.00),
(2259, 384, 3, 10000.00),
(2260, 384, 4, 10000.00),
(2261, 384, 5, 10000.00),
(2262, 384, 6, 10000.00),
(2263, 385, 1, 9666.67),
(2264, 385, 2, 9666.67),
(2265, 385, 3, 9666.67),
(2266, 385, 4, 9666.67),
(2267, 385, 5, 9666.67),
(2268, 385, 6, 9666.67),
(2269, 386, 1, 9666.67),
(2270, 386, 2, 9666.67),
(2271, 386, 3, 9666.67),
(2272, 386, 4, 9666.67),
(2273, 386, 5, 9666.67),
(2274, 386, 6, 9666.67),
(2275, 387, 1, 9666.67),
(2276, 387, 2, 9666.67),
(2277, 387, 3, 9666.67),
(2278, 387, 4, 9666.67),
(2279, 387, 5, 9666.67),
(2280, 387, 6, 9666.67),
(2281, 388, 1, 10833.33),
(2282, 388, 2, 10833.33),
(2283, 388, 3, 10833.33),
(2284, 388, 4, 10833.33),
(2285, 388, 5, 10833.33),
(2286, 388, 6, 10833.33),
(2287, 389, 1, 10833.33),
(2288, 389, 2, 10833.33),
(2289, 389, 3, 10833.33),
(2290, 389, 4, 10833.33),
(2291, 389, 5, 10833.33),
(2292, 389, 6, 10833.33),
(2293, 390, 1, 10833.33),
(2294, 390, 2, 10833.33),
(2295, 390, 3, 10833.33),
(2296, 390, 4, 10833.33),
(2297, 390, 5, 10833.33),
(2298, 390, 6, 10833.33),
(2299, 391, 1, 9166.67),
(2300, 391, 2, 9166.67),
(2301, 391, 3, 9166.67),
(2302, 391, 4, 9166.67),
(2303, 391, 5, 9166.67),
(2304, 391, 6, 9166.67),
(2305, 392, 1, 9166.67),
(2306, 392, 2, 9166.67),
(2307, 392, 3, 9166.67),
(2308, 392, 4, 9166.67),
(2309, 392, 5, 9166.67),
(2310, 392, 6, 9166.67),
(2311, 393, 1, 9166.67),
(2312, 393, 2, 9166.67),
(2313, 393, 3, 9166.67),
(2314, 393, 4, 9166.67),
(2315, 393, 5, 9166.67),
(2316, 393, 6, 9166.67),
(2317, 394, 1, 10833.33),
(2318, 394, 2, 10833.33),
(2319, 394, 3, 10833.33),
(2320, 394, 4, 10833.33),
(2321, 394, 5, 10833.33),
(2322, 394, 6, 10833.33),
(2323, 395, 1, 10833.33),
(2324, 395, 2, 10833.33),
(2325, 395, 3, 10833.33),
(2326, 395, 4, 10833.33),
(2327, 395, 5, 10833.33),
(2328, 395, 6, 10833.33),
(2329, 396, 1, 10833.33),
(2330, 396, 2, 10833.33),
(2331, 396, 3, 10833.33),
(2332, 396, 4, 10833.33),
(2333, 396, 5, 10833.33),
(2334, 396, 6, 10833.33),
(2335, 397, 1, 11666.67),
(2336, 397, 2, 11666.67),
(2337, 397, 3, 11666.67),
(2338, 397, 4, 11666.67),
(2339, 397, 5, 11666.67),
(2340, 397, 6, 11666.67),
(2341, 398, 1, 11666.67),
(2342, 398, 2, 11666.67),
(2343, 398, 3, 11666.67),
(2344, 398, 4, 11666.67),
(2345, 398, 5, 11666.67),
(2346, 398, 6, 11666.67),
(2347, 399, 1, 11666.67),
(2348, 399, 2, 11666.67),
(2349, 399, 3, 11666.67),
(2350, 399, 4, 11666.67),
(2351, 399, 5, 11666.67),
(2352, 399, 6, 11666.67),
(2353, 400, 1, 10000.00),
(2354, 400, 2, 10000.00),
(2355, 400, 3, 10000.00),
(2356, 400, 4, 10000.00),
(2357, 400, 5, 10000.00),
(2358, 400, 6, 10000.00),
(2359, 401, 1, 10000.00),
(2360, 401, 2, 10000.00),
(2361, 401, 3, 10000.00),
(2362, 401, 4, 10000.00),
(2363, 401, 5, 10000.00),
(2364, 401, 6, 10000.00),
(2365, 402, 1, 10000.00),
(2366, 402, 2, 10000.00),
(2367, 402, 3, 10000.00),
(2368, 402, 4, 10000.00),
(2369, 402, 5, 10000.00),
(2370, 402, 6, 10000.00),
(2371, 403, 1, 9666.67),
(2372, 403, 2, 9666.67),
(2373, 403, 3, 9666.67),
(2374, 403, 4, 9666.67),
(2375, 403, 5, 9666.67),
(2376, 403, 6, 9666.67),
(2377, 404, 1, 9666.67),
(2378, 404, 2, 9666.67),
(2379, 404, 3, 9666.67),
(2380, 404, 4, 9666.67),
(2381, 404, 5, 9666.67),
(2382, 404, 6, 9666.67),
(2383, 405, 1, 9666.67),
(2384, 405, 2, 9666.67),
(2385, 405, 3, 9666.67),
(2386, 405, 4, 9666.67),
(2387, 405, 5, 9666.67),
(2388, 405, 6, 9666.67),
(2389, 406, 1, 10833.33),
(2390, 406, 2, 10833.33),
(2391, 406, 3, 10833.33),
(2392, 406, 4, 10833.33),
(2393, 406, 5, 10833.33),
(2394, 406, 6, 10833.33),
(2395, 407, 1, 10833.33),
(2396, 407, 2, 10833.33),
(2397, 407, 3, 10833.33),
(2398, 407, 4, 10833.33),
(2399, 407, 5, 10833.33),
(2400, 407, 6, 10833.33),
(2401, 408, 1, 10833.33),
(2402, 408, 2, 10833.33),
(2403, 408, 3, 10833.33),
(2404, 408, 4, 10833.33),
(2405, 408, 5, 10833.33),
(2406, 408, 6, 10833.33),
(2407, 409, 1, 9166.67),
(2408, 409, 2, 9166.67),
(2409, 409, 3, 9166.67),
(2410, 409, 4, 9166.67),
(2411, 409, 5, 9166.67),
(2412, 409, 6, 9166.67),
(2413, 410, 1, 9166.67),
(2414, 410, 2, 9166.67),
(2415, 410, 3, 9166.67),
(2416, 410, 4, 9166.67),
(2417, 410, 5, 9166.67),
(2418, 410, 6, 9166.67),
(2419, 411, 1, 9166.67),
(2420, 411, 2, 9166.67),
(2421, 411, 3, 9166.67),
(2422, 411, 4, 9166.67),
(2423, 411, 5, 9166.67),
(2424, 411, 6, 9166.67),
(2425, 412, 1, 10833.33),
(2426, 412, 2, 10833.33),
(2427, 412, 3, 10833.33),
(2428, 412, 4, 10833.33),
(2429, 412, 5, 10833.33),
(2430, 412, 6, 10833.33),
(2431, 413, 1, 10833.33),
(2432, 413, 2, 10833.33),
(2433, 413, 3, 10833.33),
(2434, 413, 4, 10833.33),
(2435, 413, 5, 10833.33),
(2436, 413, 6, 10833.33),
(2437, 414, 1, 10833.33),
(2438, 414, 2, 10833.33),
(2439, 414, 3, 10833.33),
(2440, 414, 4, 10833.33),
(2441, 414, 5, 10833.33),
(2442, 414, 6, 10833.33),
(2443, 415, 1, 11666.67),
(2444, 415, 2, 11666.67),
(2445, 415, 3, 11666.67),
(2446, 415, 4, 11666.67),
(2447, 415, 5, 11666.67),
(2448, 415, 6, 11666.67),
(2449, 416, 1, 11666.67),
(2450, 416, 2, 11666.67),
(2451, 416, 3, 11666.67),
(2452, 416, 4, 11666.67),
(2453, 416, 5, 11666.67),
(2454, 416, 6, 11666.67),
(2455, 417, 1, 11666.67),
(2456, 417, 2, 11666.67),
(2457, 417, 3, 11666.67),
(2458, 417, 4, 11666.67),
(2459, 417, 5, 11666.67),
(2460, 417, 6, 11666.67),
(2461, 418, 1, 10000.00),
(2462, 418, 2, 10000.00),
(2463, 418, 3, 10000.00),
(2464, 418, 4, 10000.00),
(2465, 418, 5, 10000.00),
(2466, 418, 6, 10000.00),
(2467, 419, 1, 10000.00),
(2468, 419, 2, 10000.00),
(2469, 419, 3, 10000.00),
(2470, 419, 4, 10000.00),
(2471, 419, 5, 10000.00),
(2472, 419, 6, 10000.00),
(2473, 420, 1, 10000.00),
(2474, 420, 2, 10000.00),
(2475, 420, 3, 10000.00),
(2476, 420, 4, 10000.00),
(2477, 420, 5, 10000.00),
(2478, 420, 6, 10000.00),
(2479, 421, 1, 9666.67),
(2480, 421, 2, 9666.67),
(2481, 421, 3, 9666.67),
(2482, 421, 4, 9666.67),
(2483, 421, 5, 9666.67),
(2484, 421, 6, 9666.67),
(2485, 422, 1, 9666.67),
(2486, 422, 2, 9666.67),
(2487, 422, 3, 9666.67),
(2488, 422, 4, 9666.67),
(2489, 422, 5, 9666.67),
(2490, 422, 6, 9666.67),
(2491, 423, 1, 9666.67),
(2492, 423, 2, 9666.67),
(2493, 423, 3, 9666.67),
(2494, 423, 4, 9666.67),
(2495, 423, 5, 9666.67),
(2496, 423, 6, 9666.67),
(2497, 424, 1, 10833.33),
(2498, 424, 2, 10833.33),
(2499, 424, 3, 10833.33),
(2500, 424, 4, 10833.33),
(2501, 424, 5, 10833.33),
(2502, 424, 6, 10833.33),
(2503, 425, 1, 10833.33),
(2504, 425, 2, 10833.33),
(2505, 425, 3, 10833.33),
(2506, 425, 4, 10833.33),
(2507, 425, 5, 10833.33),
(2508, 425, 6, 10833.33),
(2509, 426, 1, 10833.33),
(2510, 426, 2, 10833.33),
(2511, 426, 3, 10833.33),
(2512, 426, 4, 10833.33),
(2513, 426, 5, 10833.33),
(2514, 426, 6, 10833.33),
(2515, 427, 1, 9166.67),
(2516, 427, 2, 9166.67),
(2517, 427, 3, 9166.67),
(2518, 427, 4, 9166.67),
(2519, 427, 5, 9166.67),
(2520, 427, 6, 9166.67),
(2521, 428, 1, 9166.67),
(2522, 428, 2, 9166.67),
(2523, 428, 3, 9166.67),
(2524, 428, 4, 9166.67),
(2525, 428, 5, 9166.67),
(2526, 428, 6, 9166.67),
(2527, 429, 1, 9166.67),
(2528, 429, 2, 9166.67),
(2529, 429, 3, 9166.67),
(2530, 429, 4, 9166.67),
(2531, 429, 5, 9166.67),
(2532, 429, 6, 9166.67),
(2533, 430, 1, 10833.33),
(2534, 430, 2, 10833.33),
(2535, 430, 3, 10833.33),
(2536, 430, 4, 10833.33),
(2537, 430, 5, 10833.33),
(2538, 430, 6, 10833.33),
(2539, 431, 1, 10833.33),
(2540, 431, 2, 10833.33),
(2541, 431, 3, 10833.33),
(2542, 431, 4, 10833.33),
(2543, 431, 5, 10833.33),
(2544, 431, 6, 10833.33),
(2545, 432, 1, 10833.33),
(2546, 432, 2, 10833.33),
(2547, 432, 3, 10833.33),
(2548, 432, 4, 10833.33),
(2549, 432, 5, 10833.33),
(2550, 432, 6, 10833.33),
(2551, 433, 1, 11666.67),
(2552, 433, 2, 11666.67),
(2553, 433, 3, 11666.67),
(2554, 433, 4, 11666.67),
(2555, 433, 5, 11666.67),
(2556, 433, 6, 11666.67),
(2557, 434, 1, 11666.67),
(2558, 434, 2, 11666.67),
(2559, 434, 3, 11666.67),
(2560, 434, 4, 11666.67),
(2561, 434, 5, 11666.67),
(2562, 434, 6, 11666.67),
(2563, 435, 1, 11666.67),
(2564, 435, 2, 11666.67),
(2565, 435, 3, 11666.67),
(2566, 435, 4, 11666.67),
(2567, 435, 5, 11666.67),
(2568, 435, 6, 11666.67),
(2569, 436, 1, 10000.00),
(2570, 436, 2, 10000.00),
(2571, 436, 3, 10000.00),
(2572, 436, 4, 10000.00),
(2573, 436, 5, 10000.00),
(2574, 436, 6, 10000.00),
(2575, 437, 1, 10000.00),
(2576, 437, 2, 10000.00),
(2577, 437, 3, 10000.00),
(2578, 437, 4, 10000.00),
(2579, 437, 5, 10000.00),
(2580, 437, 6, 10000.00),
(2581, 438, 1, 10000.00),
(2582, 438, 2, 10000.00),
(2583, 438, 3, 10000.00),
(2584, 438, 4, 10000.00),
(2585, 438, 5, 10000.00),
(2586, 438, 6, 10000.00),
(2587, 439, 1, 9666.67),
(2588, 439, 2, 9666.67),
(2589, 439, 3, 9666.67),
(2590, 439, 4, 9666.67),
(2591, 439, 5, 9666.67),
(2592, 439, 6, 9666.67),
(2593, 440, 1, 9666.67),
(2594, 440, 2, 9666.67),
(2595, 440, 3, 9666.67),
(2596, 440, 4, 9666.67),
(2597, 440, 5, 9666.67),
(2598, 440, 6, 9666.67),
(2599, 441, 1, 9666.67),
(2600, 441, 2, 9666.67),
(2601, 441, 3, 9666.67),
(2602, 441, 4, 9666.67),
(2603, 441, 5, 9666.67),
(2604, 441, 6, 9666.67),
(2605, 442, 1, 10833.33),
(2606, 442, 2, 10833.33),
(2607, 442, 3, 10833.33),
(2608, 442, 4, 10833.33),
(2609, 442, 5, 10833.33),
(2610, 442, 6, 10833.33),
(2611, 443, 1, 10833.33),
(2612, 443, 2, 10833.33),
(2613, 443, 3, 10833.33),
(2614, 443, 4, 10833.33),
(2615, 443, 5, 10833.33),
(2616, 443, 6, 10833.33),
(2617, 444, 1, 10833.33),
(2618, 444, 2, 10833.33),
(2619, 444, 3, 10833.33),
(2620, 444, 4, 10833.33),
(2621, 444, 5, 10833.33),
(2622, 444, 6, 10833.33),
(2623, 445, 1, 9166.67),
(2624, 445, 2, 9166.67),
(2625, 445, 3, 9166.67),
(2626, 445, 4, 9166.67),
(2627, 445, 5, 9166.67),
(2628, 445, 6, 9166.67),
(2629, 446, 1, 9166.67),
(2630, 446, 2, 9166.67),
(2631, 446, 3, 9166.67),
(2632, 446, 4, 9166.67),
(2633, 446, 5, 9166.67),
(2634, 446, 6, 9166.67),
(2635, 447, 1, 9166.67),
(2636, 447, 2, 9166.67),
(2637, 447, 3, 9166.67),
(2638, 447, 4, 9166.67),
(2639, 447, 5, 9166.67),
(2640, 447, 6, 9166.67),
(2641, 448, 1, 10833.33),
(2642, 448, 2, 10833.33),
(2643, 448, 3, 10833.33),
(2644, 448, 4, 10833.33),
(2645, 448, 5, 10833.33),
(2646, 448, 6, 10833.33),
(2647, 449, 1, 10833.33),
(2648, 449, 2, 10833.33),
(2649, 449, 3, 10833.33),
(2650, 449, 4, 10833.33),
(2651, 449, 5, 10833.33),
(2652, 449, 6, 10833.33),
(2653, 450, 1, 10833.33),
(2654, 450, 2, 10833.33),
(2655, 450, 3, 10833.33),
(2656, 450, 4, 10833.33),
(2657, 450, 5, 10833.33),
(2658, 450, 6, 10833.33),
(2659, 451, 1, 11666.67),
(2660, 451, 2, 11666.67),
(2661, 451, 3, 11666.67),
(2662, 451, 4, 11666.67),
(2663, 451, 5, 11666.67),
(2664, 451, 6, 11666.67),
(2665, 452, 1, 11666.67),
(2666, 452, 2, 11666.67),
(2667, 452, 3, 11666.67),
(2668, 452, 4, 11666.67),
(2669, 452, 5, 11666.67),
(2670, 452, 6, 11666.67),
(2671, 453, 1, 11666.67),
(2672, 453, 2, 11666.67),
(2673, 453, 3, 11666.67),
(2674, 453, 4, 11666.67),
(2675, 453, 5, 11666.67),
(2676, 453, 6, 11666.67),
(2677, 454, 1, 10000.00),
(2678, 454, 2, 10000.00),
(2679, 454, 3, 10000.00),
(2680, 454, 4, 10000.00),
(2681, 454, 5, 10000.00),
(2682, 454, 6, 10000.00),
(2683, 455, 1, 10000.00),
(2684, 455, 2, 10000.00),
(2685, 455, 3, 10000.00),
(2686, 455, 4, 10000.00),
(2687, 455, 5, 10000.00),
(2688, 455, 6, 10000.00),
(2689, 456, 1, 10000.00),
(2690, 456, 2, 10000.00),
(2691, 456, 3, 10000.00),
(2692, 456, 4, 10000.00),
(2693, 456, 5, 10000.00),
(2694, 456, 6, 10000.00),
(2695, 457, 1, 9666.67),
(2696, 457, 2, 9666.67),
(2697, 457, 3, 9666.67),
(2698, 457, 4, 9666.67),
(2699, 457, 5, 9666.67),
(2700, 457, 6, 9666.67),
(2701, 458, 1, 9666.67),
(2702, 458, 2, 9666.67),
(2703, 458, 3, 9666.67),
(2704, 458, 4, 9666.67),
(2705, 458, 5, 9666.67),
(2706, 458, 6, 9666.67),
(2707, 459, 1, 9666.67),
(2708, 459, 2, 9666.67),
(2709, 459, 3, 9666.67),
(2710, 459, 4, 9666.67),
(2711, 459, 5, 9666.67),
(2712, 459, 6, 9666.67),
(2713, 460, 1, 10833.33),
(2714, 460, 2, 10833.33),
(2715, 460, 3, 10833.33),
(2716, 460, 4, 10833.33),
(2717, 460, 5, 10833.33),
(2718, 460, 6, 10833.33),
(2719, 461, 1, 10833.33),
(2720, 461, 2, 10833.33),
(2721, 461, 3, 10833.33),
(2722, 461, 4, 10833.33),
(2723, 461, 5, 10833.33),
(2724, 461, 6, 10833.33),
(2725, 462, 1, 10833.33),
(2726, 462, 2, 10833.33),
(2727, 462, 3, 10833.33),
(2728, 462, 4, 10833.33),
(2729, 462, 5, 10833.33),
(2730, 462, 6, 10833.33),
(2731, 463, 1, 9166.67),
(2732, 463, 2, 9166.67),
(2733, 463, 3, 9166.67),
(2734, 463, 4, 9166.67),
(2735, 463, 5, 9166.67),
(2736, 463, 6, 9166.67),
(2737, 464, 1, 9166.67),
(2738, 464, 2, 9166.67),
(2739, 464, 3, 9166.67),
(2740, 464, 4, 9166.67),
(2741, 464, 5, 9166.67),
(2742, 464, 6, 9166.67),
(2743, 465, 1, 9166.67),
(2744, 465, 2, 9166.67),
(2745, 465, 3, 9166.67),
(2746, 465, 4, 9166.67),
(2747, 465, 5, 9166.67),
(2748, 465, 6, 9166.67),
(2749, 466, 1, 10833.33),
(2750, 466, 2, 10833.33),
(2751, 466, 3, 10833.33),
(2752, 466, 4, 10833.33),
(2753, 466, 5, 10833.33),
(2754, 466, 6, 10833.33),
(2755, 467, 1, 10833.33),
(2756, 467, 2, 10833.33),
(2757, 467, 3, 10833.33),
(2758, 467, 4, 10833.33),
(2759, 467, 5, 10833.33),
(2760, 467, 6, 10833.33),
(2761, 468, 1, 10833.33),
(2762, 468, 2, 10833.33),
(2763, 468, 3, 10833.33),
(2764, 468, 4, 10833.33),
(2765, 468, 5, 10833.33),
(2766, 468, 6, 10833.33),
(2767, 469, 1, 11666.67),
(2768, 469, 2, 11666.67),
(2769, 469, 3, 11666.67),
(2770, 469, 4, 11666.67),
(2771, 469, 5, 11666.67),
(2772, 469, 6, 11666.67),
(2773, 470, 1, 11666.67),
(2774, 470, 2, 11666.67),
(2775, 470, 3, 11666.67),
(2776, 470, 4, 11666.67),
(2777, 470, 5, 11666.67),
(2778, 470, 6, 11666.67),
(2779, 471, 1, 11666.67),
(2780, 471, 2, 11666.67),
(2781, 471, 3, 11666.67),
(2782, 471, 4, 11666.67),
(2783, 471, 5, 11666.67),
(2784, 471, 6, 11666.67),
(2785, 472, 1, 10000.00),
(2786, 472, 2, 10000.00),
(2787, 472, 3, 10000.00),
(2788, 472, 4, 10000.00),
(2789, 472, 5, 10000.00),
(2790, 472, 6, 10000.00),
(2791, 473, 1, 10000.00),
(2792, 473, 2, 10000.00),
(2793, 473, 3, 10000.00),
(2794, 473, 4, 10000.00),
(2795, 473, 5, 10000.00),
(2796, 473, 6, 10000.00),
(2797, 474, 1, 10000.00),
(2798, 474, 2, 10000.00),
(2799, 474, 3, 10000.00),
(2800, 474, 4, 10000.00),
(2801, 474, 5, 10000.00),
(2802, 474, 6, 10000.00),
(2803, 475, 1, 9666.67),
(2804, 475, 2, 9666.67),
(2805, 475, 3, 9666.67),
(2806, 475, 4, 9666.67),
(2807, 475, 5, 9666.67),
(2808, 475, 6, 9666.67),
(2809, 476, 1, 9666.67),
(2810, 476, 2, 9666.67),
(2811, 476, 3, 9666.67),
(2812, 476, 4, 9666.67),
(2813, 476, 5, 9666.67),
(2814, 476, 6, 9666.67),
(2815, 477, 1, 9666.67),
(2816, 477, 2, 9666.67),
(2817, 477, 3, 9666.67),
(2818, 477, 4, 9666.67),
(2819, 477, 5, 9666.67),
(2820, 477, 6, 9666.67),
(2821, 478, 1, 10833.33),
(2822, 478, 2, 10833.33),
(2823, 478, 3, 10833.33),
(2824, 478, 4, 10833.33),
(2825, 478, 5, 10833.33),
(2826, 478, 6, 10833.33),
(2827, 479, 1, 10833.33),
(2828, 479, 2, 10833.33),
(2829, 479, 3, 10833.33),
(2830, 479, 4, 10833.33),
(2831, 479, 5, 10833.33),
(2832, 479, 6, 10833.33),
(2833, 480, 1, 10833.33),
(2834, 480, 2, 10833.33),
(2835, 480, 3, 10833.33),
(2836, 480, 4, 10833.33),
(2837, 480, 5, 10833.33),
(2838, 480, 6, 10833.33),
(2839, 481, 1, 9166.67),
(2840, 481, 2, 9166.67),
(2841, 481, 3, 9166.67),
(2842, 481, 4, 9166.67),
(2843, 481, 5, 9166.67),
(2844, 481, 6, 9166.67),
(2845, 482, 1, 9166.67),
(2846, 482, 2, 9166.67),
(2847, 482, 3, 9166.67),
(2848, 482, 4, 9166.67),
(2849, 482, 5, 9166.67),
(2850, 482, 6, 9166.67),
(2851, 483, 1, 9166.67),
(2852, 483, 2, 9166.67),
(2853, 483, 3, 9166.67),
(2854, 483, 4, 9166.67),
(2855, 483, 5, 9166.67),
(2856, 483, 6, 9166.67),
(2857, 484, 1, 10833.33),
(2858, 484, 2, 10833.33),
(2859, 484, 3, 10833.33),
(2860, 484, 4, 10833.33),
(2861, 484, 5, 10833.33),
(2862, 484, 6, 10833.33),
(2863, 485, 1, 10833.33),
(2864, 485, 2, 10833.33),
(2865, 485, 3, 10833.33),
(2866, 485, 4, 10833.33),
(2867, 485, 5, 10833.33),
(2868, 485, 6, 10833.33),
(2869, 486, 1, 10833.33),
(2870, 486, 2, 10833.33),
(2871, 486, 3, 10833.33),
(2872, 486, 4, 10833.33),
(2873, 486, 5, 10833.33),
(2874, 486, 6, 10833.33),
(2875, 487, 1, 11666.67),
(2876, 487, 2, 11666.67),
(2877, 487, 3, 11666.67),
(2878, 487, 4, 11666.67),
(2879, 487, 5, 11666.67),
(2880, 487, 6, 11666.67),
(2881, 488, 1, 11666.67),
(2882, 488, 2, 11666.67),
(2883, 488, 3, 11666.67),
(2884, 488, 4, 11666.67),
(2885, 488, 5, 11666.67),
(2886, 488, 6, 11666.67),
(2887, 489, 1, 11666.67),
(2888, 489, 2, 11666.67),
(2889, 489, 3, 11666.67),
(2890, 489, 4, 11666.67),
(2891, 489, 5, 11666.67),
(2892, 489, 6, 11666.67),
(2893, 490, 1, 10000.00),
(2894, 490, 2, 10000.00),
(2895, 490, 3, 10000.00),
(2896, 490, 4, 10000.00),
(2897, 490, 5, 10000.00),
(2898, 490, 6, 10000.00),
(2899, 491, 1, 10000.00),
(2900, 491, 2, 10000.00),
(2901, 491, 3, 10000.00),
(2902, 491, 4, 10000.00),
(2903, 491, 5, 10000.00),
(2904, 491, 6, 10000.00),
(2905, 492, 1, 10000.00),
(2906, 492, 2, 10000.00),
(2907, 492, 3, 10000.00),
(2908, 492, 4, 10000.00),
(2909, 492, 5, 10000.00),
(2910, 492, 6, 10000.00),
(2911, 493, 1, 9666.67),
(2912, 493, 2, 9666.67),
(2913, 493, 3, 9666.67),
(2914, 493, 4, 9666.67),
(2915, 493, 5, 9666.67),
(2916, 493, 6, 9666.67),
(2917, 494, 1, 9666.67),
(2918, 494, 2, 9666.67),
(2919, 494, 3, 9666.67),
(2920, 494, 4, 9666.67),
(2921, 494, 5, 9666.67),
(2922, 494, 6, 9666.67),
(2923, 495, 1, 9666.67),
(2924, 495, 2, 9666.67),
(2925, 495, 3, 9666.67),
(2926, 495, 4, 9666.67),
(2927, 495, 5, 9666.67),
(2928, 495, 6, 9666.67),
(2929, 496, 1, 10833.33),
(2930, 496, 2, 10833.33),
(2931, 496, 3, 10833.33),
(2932, 496, 4, 10833.33),
(2933, 496, 5, 10833.33),
(2934, 496, 6, 10833.33),
(2935, 497, 1, 10833.33),
(2936, 497, 2, 10833.33),
(2937, 497, 3, 10833.33),
(2938, 497, 4, 10833.33),
(2939, 497, 5, 10833.33),
(2940, 497, 6, 10833.33),
(2941, 498, 1, 10833.33),
(2942, 498, 2, 10833.33),
(2943, 498, 3, 10833.33),
(2944, 498, 4, 10833.33),
(2945, 498, 5, 10833.33),
(2946, 498, 6, 10833.33),
(2947, 499, 1, 9166.67),
(2948, 499, 2, 9166.67),
(2949, 499, 3, 9166.67),
(2950, 499, 4, 9166.67),
(2951, 499, 5, 9166.67),
(2952, 499, 6, 9166.67),
(2953, 500, 1, 9166.67),
(2954, 500, 2, 9166.67),
(2955, 500, 3, 9166.67),
(2956, 500, 4, 9166.67),
(2957, 500, 5, 9166.67),
(2958, 500, 6, 9166.67),
(2959, 501, 1, 9166.67),
(2960, 501, 2, 9166.67),
(2961, 501, 3, 9166.67),
(2962, 501, 4, 9166.67),
(2963, 501, 5, 9166.67),
(2964, 501, 6, 9166.67),
(2965, 502, 1, 10833.33),
(2966, 502, 2, 10833.33),
(2967, 502, 3, 10833.33),
(2968, 502, 4, 10833.33),
(2969, 502, 5, 10833.33),
(2970, 502, 6, 10833.33),
(2971, 503, 1, 10833.33),
(2972, 503, 2, 10833.33),
(2973, 503, 3, 10833.33),
(2974, 503, 4, 10833.33),
(2975, 503, 5, 10833.33),
(2976, 503, 6, 10833.33),
(2977, 504, 1, 10833.33),
(2978, 504, 2, 10833.33),
(2979, 504, 3, 10833.33),
(2980, 504, 4, 10833.33),
(2981, 504, 5, 10833.33),
(2982, 504, 6, 10833.33),
(2983, 505, 1, 11666.67),
(2984, 505, 2, 11666.67),
(2985, 505, 3, 11666.67),
(2986, 505, 4, 11666.67),
(2987, 505, 5, 11666.67),
(2988, 505, 6, 11666.67),
(2989, 506, 1, 11666.67),
(2990, 506, 2, 11666.67),
(2991, 506, 3, 11666.67),
(2992, 506, 4, 11666.67),
(2993, 506, 5, 11666.67),
(2994, 506, 6, 11666.67),
(2995, 507, 1, 11666.67),
(2996, 507, 2, 11666.67),
(2997, 507, 3, 11666.67),
(2998, 507, 4, 11666.67),
(2999, 507, 5, 11666.67),
(3000, 507, 6, 11666.67),
(3001, 508, 1, 10000.00),
(3002, 508, 2, 10000.00),
(3003, 508, 3, 10000.00),
(3004, 508, 4, 10000.00),
(3005, 508, 5, 10000.00),
(3006, 508, 6, 10000.00),
(3007, 509, 1, 10000.00),
(3008, 509, 2, 10000.00),
(3009, 509, 3, 10000.00),
(3010, 509, 4, 10000.00),
(3011, 509, 5, 10000.00),
(3012, 509, 6, 10000.00),
(3013, 510, 1, 10000.00),
(3014, 510, 2, 10000.00),
(3015, 510, 3, 10000.00),
(3016, 510, 4, 10000.00),
(3017, 510, 5, 10000.00),
(3018, 510, 6, 10000.00),
(3019, 511, 1, 9666.67),
(3020, 511, 2, 9666.67),
(3021, 511, 3, 9666.67),
(3022, 511, 4, 9666.67),
(3023, 511, 5, 9666.67),
(3024, 511, 6, 9666.67),
(3025, 512, 1, 9666.67),
(3026, 512, 2, 9666.67),
(3027, 512, 3, 9666.67),
(3028, 512, 4, 9666.67),
(3029, 512, 5, 9666.67),
(3030, 512, 6, 9666.67),
(3031, 513, 1, 9666.67),
(3032, 513, 2, 9666.67),
(3033, 513, 3, 9666.67),
(3034, 513, 4, 9666.67),
(3035, 513, 5, 9666.67),
(3036, 513, 6, 9666.67),
(3037, 514, 1, 10833.33),
(3038, 514, 2, 10833.33),
(3039, 514, 3, 10833.33),
(3040, 514, 4, 10833.33),
(3041, 514, 5, 10833.33),
(3042, 514, 6, 10833.33),
(3043, 515, 1, 10833.33),
(3044, 515, 2, 10833.33),
(3045, 515, 3, 10833.33),
(3046, 515, 4, 10833.33),
(3047, 515, 5, 10833.33),
(3048, 515, 6, 10833.33),
(3049, 516, 1, 10833.33),
(3050, 516, 2, 10833.33),
(3051, 516, 3, 10833.33),
(3052, 516, 4, 10833.33),
(3053, 516, 5, 10833.33),
(3054, 516, 6, 10833.33),
(3055, 517, 1, 9166.67),
(3056, 517, 2, 9166.67),
(3057, 517, 3, 9166.67),
(3058, 517, 4, 9166.67),
(3059, 517, 5, 9166.67),
(3060, 517, 6, 9166.67),
(3061, 518, 1, 9166.67),
(3062, 518, 2, 9166.67),
(3063, 518, 3, 9166.67),
(3064, 518, 4, 9166.67),
(3065, 518, 5, 9166.67),
(3066, 518, 6, 9166.67),
(3067, 519, 1, 9166.67),
(3068, 519, 2, 9166.67),
(3069, 519, 3, 9166.67),
(3070, 519, 4, 9166.67),
(3071, 519, 5, 9166.67),
(3072, 519, 6, 9166.67),
(3073, 520, 1, 10833.33),
(3074, 520, 2, 10833.33),
(3075, 520, 3, 10833.33),
(3076, 520, 4, 10833.33),
(3077, 520, 5, 10833.33),
(3078, 520, 6, 10833.33),
(3079, 521, 1, 10833.33),
(3080, 521, 2, 10833.33),
(3081, 521, 3, 10833.33),
(3082, 521, 4, 10833.33),
(3083, 521, 5, 10833.33),
(3084, 521, 6, 10833.33),
(3085, 522, 1, 10833.33),
(3086, 522, 2, 10833.33),
(3087, 522, 3, 10833.33),
(3088, 522, 4, 10833.33),
(3089, 522, 5, 10833.33),
(3090, 522, 6, 10833.33),
(3091, 523, 1, 11666.67),
(3092, 523, 2, 11666.67),
(3093, 523, 3, 11666.67),
(3094, 523, 4, 11666.67),
(3095, 523, 5, 11666.67),
(3096, 523, 6, 11666.67),
(3097, 524, 1, 11666.67),
(3098, 524, 2, 11666.67),
(3099, 524, 3, 11666.67),
(3100, 524, 4, 11666.67),
(3101, 524, 5, 11666.67),
(3102, 524, 6, 11666.67),
(3103, 525, 1, 11666.67),
(3104, 525, 2, 11666.67),
(3105, 525, 3, 11666.67),
(3106, 525, 4, 11666.67),
(3107, 525, 5, 11666.67),
(3108, 525, 6, 11666.67),
(3109, 526, 1, 10000.00),
(3110, 526, 2, 10000.00),
(3111, 526, 3, 10000.00),
(3112, 526, 4, 10000.00),
(3113, 526, 5, 10000.00),
(3114, 526, 6, 10000.00),
(3115, 527, 1, 10000.00),
(3116, 527, 2, 10000.00),
(3117, 527, 3, 10000.00),
(3118, 527, 4, 10000.00),
(3119, 527, 5, 10000.00),
(3120, 527, 6, 10000.00),
(3121, 528, 1, 10000.00),
(3122, 528, 2, 10000.00),
(3123, 528, 3, 10000.00),
(3124, 528, 4, 10000.00),
(3125, 528, 5, 10000.00),
(3126, 528, 6, 10000.00),
(3127, 529, 1, 9666.67),
(3128, 529, 2, 9666.67),
(3129, 529, 3, 9666.67),
(3130, 529, 4, 9666.67),
(3131, 529, 5, 9666.67),
(3132, 529, 6, 9666.67),
(3133, 530, 1, 9666.67),
(3134, 530, 2, 9666.67),
(3135, 530, 3, 9666.67),
(3136, 530, 4, 9666.67),
(3137, 530, 5, 9666.67),
(3138, 530, 6, 9666.67),
(3139, 531, 1, 9666.67),
(3140, 531, 2, 9666.67),
(3141, 531, 3, 9666.67),
(3142, 531, 4, 9666.67),
(3143, 531, 5, 9666.67),
(3144, 531, 6, 9666.67),
(3145, 532, 1, 10833.33),
(3146, 532, 2, 10833.33),
(3147, 532, 3, 10833.33),
(3148, 532, 4, 10833.33),
(3149, 532, 5, 10833.33),
(3150, 532, 6, 10833.33),
(3151, 533, 1, 10833.33),
(3152, 533, 2, 10833.33),
(3153, 533, 3, 10833.33),
(3154, 533, 4, 10833.33),
(3155, 533, 5, 10833.33),
(3156, 533, 6, 10833.33),
(3157, 534, 1, 10833.33),
(3158, 534, 2, 10833.33),
(3159, 534, 3, 10833.33),
(3160, 534, 4, 10833.33),
(3161, 534, 5, 10833.33),
(3162, 534, 6, 10833.33),
(3163, 535, 1, 9166.67),
(3164, 535, 2, 9166.67),
(3165, 535, 3, 9166.67),
(3166, 535, 4, 9166.67),
(3167, 535, 5, 9166.67),
(3168, 535, 6, 9166.67),
(3169, 536, 1, 9166.67),
(3170, 536, 2, 9166.67),
(3171, 536, 3, 9166.67),
(3172, 536, 4, 9166.67),
(3173, 536, 5, 9166.67),
(3174, 536, 6, 9166.67),
(3175, 537, 1, 9166.67),
(3176, 537, 2, 9166.67),
(3177, 537, 3, 9166.67),
(3178, 537, 4, 9166.67),
(3179, 537, 5, 9166.67),
(3180, 537, 6, 9166.67),
(3181, 538, 1, 10833.33),
(3182, 538, 2, 10833.33),
(3183, 538, 3, 10833.33),
(3184, 538, 4, 10833.33),
(3185, 538, 5, 10833.33),
(3186, 538, 6, 10833.33),
(3187, 539, 1, 10833.33),
(3188, 539, 2, 10833.33),
(3189, 539, 3, 10833.33),
(3190, 539, 4, 10833.33),
(3191, 539, 5, 10833.33),
(3192, 539, 6, 10833.33),
(3193, 540, 1, 10833.33),
(3194, 540, 2, 10833.33),
(3195, 540, 3, 10833.33),
(3196, 540, 4, 10833.33),
(3197, 540, 5, 10833.33),
(3198, 540, 6, 10833.33),
(3199, 541, 1, 11666.67),
(3200, 541, 2, 11666.67),
(3201, 541, 3, 11666.67),
(3202, 541, 4, 11666.67),
(3203, 541, 5, 11666.67),
(3204, 541, 6, 11666.67),
(3205, 542, 1, 11666.67),
(3206, 542, 2, 11666.67),
(3207, 542, 3, 11666.67),
(3208, 542, 4, 11666.67),
(3209, 542, 5, 11666.67),
(3210, 542, 6, 11666.67),
(3211, 543, 1, 11666.67),
(3212, 543, 2, 11666.67),
(3213, 543, 3, 11666.67),
(3214, 543, 4, 11666.67),
(3215, 543, 5, 11666.67),
(3216, 543, 6, 11666.67),
(3217, 544, 1, 10000.00),
(3218, 544, 2, 10000.00),
(3219, 544, 3, 10000.00),
(3220, 544, 4, 10000.00),
(3221, 544, 5, 10000.00),
(3222, 544, 6, 10000.00),
(3223, 545, 1, 10000.00),
(3224, 545, 2, 10000.00),
(3225, 545, 3, 10000.00),
(3226, 545, 4, 10000.00),
(3227, 545, 5, 10000.00),
(3228, 545, 6, 10000.00),
(3229, 546, 1, 10000.00),
(3230, 546, 2, 10000.00),
(3231, 546, 3, 10000.00),
(3232, 546, 4, 10000.00),
(3233, 546, 5, 10000.00),
(3234, 546, 6, 10000.00),
(3235, 547, 1, 9666.67),
(3236, 547, 2, 9666.67),
(3237, 547, 3, 9666.67),
(3238, 547, 4, 9666.67),
(3239, 547, 5, 9666.67),
(3240, 547, 6, 9666.67),
(3241, 548, 1, 9666.67),
(3242, 548, 2, 9666.67),
(3243, 548, 3, 9666.67),
(3244, 548, 4, 9666.67),
(3245, 548, 5, 9666.67),
(3246, 548, 6, 9666.67),
(3247, 549, 1, 9666.67),
(3248, 549, 2, 9666.67),
(3249, 549, 3, 9666.67),
(3250, 549, 4, 9666.67),
(3251, 549, 5, 9666.67),
(3252, 549, 6, 9666.67),
(3253, 550, 1, 10833.33),
(3254, 550, 2, 10833.33),
(3255, 550, 3, 10833.33),
(3256, 550, 4, 10833.33),
(3257, 550, 5, 10833.33),
(3258, 550, 6, 10833.33),
(3259, 551, 1, 10833.33),
(3260, 551, 2, 10833.33),
(3261, 551, 3, 10833.33),
(3262, 551, 4, 10833.33),
(3263, 551, 5, 10833.33),
(3264, 551, 6, 10833.33),
(3265, 552, 1, 10833.33),
(3266, 552, 2, 10833.33),
(3267, 552, 3, 10833.33),
(3268, 552, 4, 10833.33),
(3269, 552, 5, 10833.33),
(3270, 552, 6, 10833.33),
(3271, 553, 1, 9166.67),
(3272, 553, 2, 9166.67),
(3273, 553, 3, 9166.67),
(3274, 553, 4, 9166.67),
(3275, 553, 5, 9166.67),
(3276, 553, 6, 9166.67),
(3277, 554, 1, 9166.67),
(3278, 554, 2, 9166.67),
(3279, 554, 3, 9166.67),
(3280, 554, 4, 9166.67),
(3281, 554, 5, 9166.67),
(3282, 554, 6, 9166.67),
(3283, 555, 1, 9166.67),
(3284, 555, 2, 9166.67),
(3285, 555, 3, 9166.67),
(3286, 555, 4, 9166.67),
(3287, 555, 5, 9166.67),
(3288, 555, 6, 9166.67),
(3289, 556, 1, 10833.33),
(3290, 556, 2, 10833.33),
(3291, 556, 3, 10833.33),
(3292, 556, 4, 10833.33),
(3293, 556, 5, 10833.33),
(3294, 556, 6, 10833.33),
(3295, 557, 1, 10833.33),
(3296, 557, 2, 10833.33),
(3297, 557, 3, 10833.33),
(3298, 557, 4, 10833.33),
(3299, 557, 5, 10833.33),
(3300, 557, 6, 10833.33),
(3301, 558, 1, 10833.33),
(3302, 558, 2, 10833.33),
(3303, 558, 3, 10833.33),
(3304, 558, 4, 10833.33),
(3305, 558, 5, 10833.33),
(3306, 558, 6, 10833.33),
(3307, 559, 1, 11666.67),
(3308, 559, 2, 11666.67),
(3309, 559, 3, 11666.67),
(3310, 559, 4, 11666.67),
(3311, 559, 5, 11666.67),
(3312, 559, 6, 11666.67),
(3313, 560, 1, 11666.67),
(3314, 560, 2, 11666.67),
(3315, 560, 3, 11666.67),
(3316, 560, 4, 11666.67),
(3317, 560, 5, 11666.67),
(3318, 560, 6, 11666.67),
(3319, 561, 1, 11666.67),
(3320, 561, 2, 11666.67),
(3321, 561, 3, 11666.67),
(3322, 561, 4, 11666.67),
(3323, 561, 5, 11666.67),
(3324, 561, 6, 11666.67),
(3325, 562, 1, 10000.00),
(3326, 562, 2, 10000.00),
(3327, 562, 3, 10000.00),
(3328, 562, 4, 10000.00),
(3329, 562, 5, 10000.00),
(3330, 562, 6, 10000.00),
(3331, 563, 1, 10000.00),
(3332, 563, 2, 10000.00),
(3333, 563, 3, 10000.00),
(3334, 563, 4, 10000.00),
(3335, 563, 5, 10000.00),
(3336, 563, 6, 10000.00),
(3337, 564, 1, 10000.00),
(3338, 564, 2, 10000.00),
(3339, 564, 3, 10000.00),
(3340, 564, 4, 10000.00),
(3341, 564, 5, 10000.00),
(3342, 564, 6, 10000.00),
(3343, 565, 1, 9666.67),
(3344, 565, 2, 9666.67),
(3345, 565, 3, 9666.67),
(3346, 565, 4, 9666.67),
(3347, 565, 5, 9666.67),
(3348, 565, 6, 9666.67),
(3349, 566, 1, 9666.67),
(3350, 566, 2, 9666.67),
(3351, 566, 3, 9666.67),
(3352, 566, 4, 9666.67),
(3353, 566, 5, 9666.67),
(3354, 566, 6, 9666.67),
(3355, 567, 1, 9666.67),
(3356, 567, 2, 9666.67),
(3357, 567, 3, 9666.67),
(3358, 567, 4, 9666.67),
(3359, 567, 5, 9666.67),
(3360, 567, 6, 9666.67),
(3361, 568, 1, 10833.33),
(3362, 568, 2, 10833.33),
(3363, 568, 3, 10833.33),
(3364, 568, 4, 10833.33),
(3365, 568, 5, 10833.33),
(3366, 568, 6, 10833.33),
(3367, 569, 1, 10833.33),
(3368, 569, 2, 10833.33),
(3369, 569, 3, 10833.33),
(3370, 569, 4, 10833.33),
(3371, 569, 5, 10833.33),
(3372, 569, 6, 10833.33),
(3373, 570, 1, 10833.33),
(3374, 570, 2, 10833.33),
(3375, 570, 3, 10833.33),
(3376, 570, 4, 10833.33),
(3377, 570, 5, 10833.33),
(3378, 570, 6, 10833.33),
(3379, 571, 1, 9166.67),
(3380, 571, 2, 9166.67),
(3381, 571, 3, 9166.67),
(3382, 571, 4, 9166.67),
(3383, 571, 5, 9166.67),
(3384, 571, 6, 9166.67),
(3385, 572, 1, 9166.67),
(3386, 572, 2, 9166.67),
(3387, 572, 3, 9166.67),
(3388, 572, 4, 9166.67),
(3389, 572, 5, 9166.67),
(3390, 572, 6, 9166.67),
(3391, 573, 1, 9166.67),
(3392, 573, 2, 9166.67),
(3393, 573, 3, 9166.67),
(3394, 573, 4, 9166.67),
(3395, 573, 5, 9166.67),
(3396, 573, 6, 9166.67),
(3397, 574, 1, 10833.33),
(3398, 574, 2, 10833.33),
(3399, 574, 3, 10833.33),
(3400, 574, 4, 10833.33),
(3401, 574, 5, 10833.33),
(3402, 574, 6, 10833.33),
(3403, 575, 1, 10833.33),
(3404, 575, 2, 10833.33),
(3405, 575, 3, 10833.33),
(3406, 575, 4, 10833.33),
(3407, 575, 5, 10833.33),
(3408, 575, 6, 10833.33),
(3409, 576, 1, 10833.33),
(3410, 576, 2, 10833.33),
(3411, 576, 3, 10833.33),
(3412, 576, 4, 10833.33),
(3413, 576, 5, 10833.33),
(3414, 576, 6, 10833.33),
(3415, 577, 1, 11666.67),
(3416, 577, 2, 11666.67),
(3417, 577, 3, 11666.67),
(3418, 577, 4, 11666.67),
(3419, 577, 5, 11666.67),
(3420, 577, 6, 11666.67),
(3421, 578, 1, 11666.67),
(3422, 578, 2, 11666.67),
(3423, 578, 3, 11666.67),
(3424, 578, 4, 11666.67),
(3425, 578, 5, 11666.67),
(3426, 578, 6, 11666.67),
(3427, 579, 1, 11666.67),
(3428, 579, 2, 11666.67),
(3429, 579, 3, 11666.67),
(3430, 579, 4, 11666.67),
(3431, 579, 5, 11666.67),
(3432, 579, 6, 11666.67),
(3433, 580, 1, 10000.00),
(3434, 580, 2, 10000.00),
(3435, 580, 3, 10000.00),
(3436, 580, 4, 10000.00),
(3437, 580, 5, 10000.00),
(3438, 580, 6, 10000.00),
(3439, 581, 1, 10000.00),
(3440, 581, 2, 10000.00),
(3441, 581, 3, 10000.00),
(3442, 581, 4, 10000.00),
(3443, 581, 5, 10000.00),
(3444, 581, 6, 10000.00),
(3445, 582, 1, 10000.00),
(3446, 582, 2, 10000.00),
(3447, 582, 3, 10000.00),
(3448, 582, 4, 10000.00),
(3449, 582, 5, 10000.00),
(3450, 582, 6, 10000.00),
(3451, 583, 1, 9666.67),
(3452, 583, 2, 9666.67),
(3453, 583, 3, 9666.67),
(3454, 583, 4, 9666.67),
(3455, 583, 5, 9666.67),
(3456, 583, 6, 9666.67),
(3457, 584, 1, 9666.67),
(3458, 584, 2, 9666.67),
(3459, 584, 3, 9666.67),
(3460, 584, 4, 9666.67),
(3461, 584, 5, 9666.67),
(3462, 584, 6, 9666.67),
(3463, 585, 1, 9666.67),
(3464, 585, 2, 9666.67),
(3465, 585, 3, 9666.67),
(3466, 585, 4, 9666.67),
(3467, 585, 5, 9666.67),
(3468, 585, 6, 9666.67),
(3469, 586, 1, 10833.33),
(3470, 586, 2, 10833.33),
(3471, 586, 3, 10833.33),
(3472, 586, 4, 10833.33),
(3473, 586, 5, 10833.33),
(3474, 586, 6, 10833.33),
(3475, 587, 1, 10833.33),
(3476, 587, 2, 10833.33),
(3477, 587, 3, 10833.33),
(3478, 587, 4, 10833.33),
(3479, 587, 5, 10833.33),
(3480, 587, 6, 10833.33),
(3481, 588, 1, 10833.33),
(3482, 588, 2, 10833.33),
(3483, 588, 3, 10833.33),
(3484, 588, 4, 10833.33),
(3485, 588, 5, 10833.33),
(3486, 588, 6, 10833.33),
(3487, 589, 1, 9166.67),
(3488, 589, 2, 9166.67),
(3489, 589, 3, 9166.67),
(3490, 589, 4, 9166.67),
(3491, 589, 5, 9166.67),
(3492, 589, 6, 9166.67),
(3493, 590, 1, 9166.67),
(3494, 590, 2, 9166.67),
(3495, 590, 3, 9166.67),
(3496, 590, 4, 9166.67),
(3497, 590, 5, 9166.67),
(3498, 590, 6, 9166.67),
(3499, 591, 1, 9166.67),
(3500, 591, 2, 9166.67),
(3501, 591, 3, 9166.67),
(3502, 591, 4, 9166.67),
(3503, 591, 5, 9166.67),
(3504, 591, 6, 9166.67),
(3505, 592, 1, 10833.33),
(3506, 592, 2, 10833.33),
(3507, 592, 3, 10833.33),
(3508, 592, 4, 10833.33),
(3509, 592, 5, 10833.33),
(3510, 592, 6, 10833.33),
(3511, 593, 1, 10833.33),
(3512, 593, 2, 10833.33),
(3513, 593, 3, 10833.33),
(3514, 593, 4, 10833.33),
(3515, 593, 5, 10833.33),
(3516, 593, 6, 10833.33),
(3517, 594, 1, 10833.33),
(3518, 594, 2, 10833.33),
(3519, 594, 3, 10833.33),
(3520, 594, 4, 10833.33),
(3521, 594, 5, 10833.33),
(3522, 594, 6, 10833.33),
(3523, 595, 1, 11666.67),
(3524, 595, 2, 11666.67),
(3525, 595, 3, 11666.67),
(3526, 595, 4, 11666.67),
(3527, 595, 5, 11666.67),
(3528, 595, 6, 11666.67),
(3529, 596, 1, 11666.67),
(3530, 596, 2, 11666.67),
(3531, 596, 3, 11666.67),
(3532, 596, 4, 11666.67),
(3533, 596, 5, 11666.67),
(3534, 596, 6, 11666.67),
(3535, 597, 1, 11666.67),
(3536, 597, 2, 11666.67),
(3537, 597, 3, 11666.67),
(3538, 597, 4, 11666.67),
(3539, 597, 5, 11666.67),
(3540, 597, 6, 11666.67),
(3541, 598, 1, 10000.00),
(3542, 598, 2, 10000.00),
(3543, 598, 3, 10000.00),
(3544, 598, 4, 10000.00),
(3545, 598, 5, 10000.00),
(3546, 598, 6, 10000.00),
(3547, 599, 1, 10000.00),
(3548, 599, 2, 10000.00),
(3549, 599, 3, 10000.00),
(3550, 599, 4, 10000.00),
(3551, 599, 5, 10000.00),
(3552, 599, 6, 10000.00),
(3553, 600, 1, 10000.00),
(3554, 600, 2, 10000.00),
(3555, 600, 3, 10000.00),
(3556, 600, 4, 10000.00),
(3557, 600, 5, 10000.00),
(3558, 600, 6, 10000.00),
(3559, 601, 1, 9666.67),
(3560, 601, 2, 9666.67),
(3561, 601, 3, 9666.67),
(3562, 601, 4, 9666.67),
(3563, 601, 5, 9666.67),
(3564, 601, 6, 9666.67),
(3565, 602, 1, 9666.67),
(3566, 602, 2, 9666.67),
(3567, 602, 3, 9666.67),
(3568, 602, 4, 9666.67),
(3569, 602, 5, 9666.67),
(3570, 602, 6, 9666.67),
(3571, 603, 1, 9666.67),
(3572, 603, 2, 9666.67),
(3573, 603, 3, 9666.67),
(3574, 603, 4, 9666.67),
(3575, 603, 5, 9666.67),
(3576, 603, 6, 9666.67),
(3577, 604, 1, 10833.33),
(3578, 604, 2, 10833.33),
(3579, 604, 3, 10833.33),
(3580, 604, 4, 10833.33),
(3581, 604, 5, 10833.33),
(3582, 604, 6, 10833.33),
(3583, 605, 1, 10833.33),
(3584, 605, 2, 10833.33),
(3585, 605, 3, 10833.33),
(3586, 605, 4, 10833.33),
(3587, 605, 5, 10833.33),
(3588, 605, 6, 10833.33),
(3589, 606, 1, 10833.33),
(3590, 606, 2, 10833.33),
(3591, 606, 3, 10833.33),
(3592, 606, 4, 10833.33),
(3593, 606, 5, 10833.33),
(3594, 606, 6, 10833.33),
(3595, 607, 1, 9166.67),
(3596, 607, 2, 9166.67),
(3597, 607, 3, 9166.67),
(3598, 607, 4, 9166.67),
(3599, 607, 5, 9166.67),
(3600, 607, 6, 9166.67),
(3601, 608, 1, 9166.67),
(3602, 608, 2, 9166.67),
(3603, 608, 3, 9166.67),
(3604, 608, 4, 9166.67),
(3605, 608, 5, 9166.67),
(3606, 608, 6, 9166.67),
(3607, 609, 1, 9166.67),
(3608, 609, 2, 9166.67),
(3609, 609, 3, 9166.67),
(3610, 609, 4, 9166.67),
(3611, 609, 5, 9166.67),
(3612, 609, 6, 9166.67),
(3613, 610, 1, 10833.33),
(3614, 610, 2, 10833.33),
(3615, 610, 3, 10833.33),
(3616, 610, 4, 10833.33),
(3617, 610, 5, 10833.33),
(3618, 610, 6, 10833.33),
(3619, 611, 1, 10833.33),
(3620, 611, 2, 10833.33),
(3621, 611, 3, 10833.33),
(3622, 611, 4, 10833.33),
(3623, 611, 5, 10833.33),
(3624, 611, 6, 10833.33),
(3625, 612, 1, 10833.33),
(3626, 612, 2, 10833.33),
(3627, 612, 3, 10833.33),
(3628, 612, 4, 10833.33),
(3629, 612, 5, 10833.33),
(3630, 612, 6, 10833.33),
(3631, 613, 1, 11666.67),
(3632, 613, 2, 11666.67),
(3633, 613, 3, 11666.67),
(3634, 613, 4, 11666.67),
(3635, 613, 5, 11666.67),
(3636, 613, 6, 11666.67),
(3637, 614, 1, 11666.67),
(3638, 614, 2, 11666.67),
(3639, 614, 3, 11666.67),
(3640, 614, 4, 11666.67),
(3641, 614, 5, 11666.67),
(3642, 614, 6, 11666.67),
(3643, 615, 1, 11666.67),
(3644, 615, 2, 11666.67),
(3645, 615, 3, 11666.67),
(3646, 615, 4, 11666.67),
(3647, 615, 5, 11666.67),
(3648, 615, 6, 11666.67),
(3649, 616, 1, 10000.00),
(3650, 616, 2, 10000.00),
(3651, 616, 3, 10000.00),
(3652, 616, 4, 10000.00),
(3653, 616, 5, 10000.00),
(3654, 616, 6, 10000.00),
(3655, 617, 1, 10000.00),
(3656, 617, 2, 10000.00),
(3657, 617, 3, 10000.00),
(3658, 617, 4, 10000.00),
(3659, 617, 5, 10000.00),
(3660, 617, 6, 10000.00),
(3661, 618, 1, 10000.00),
(3662, 618, 2, 10000.00),
(3663, 618, 3, 10000.00),
(3664, 618, 4, 10000.00),
(3665, 618, 5, 10000.00),
(3666, 618, 6, 10000.00),
(3667, 619, 1, 9666.67),
(3668, 619, 2, 9666.67),
(3669, 619, 3, 9666.67),
(3670, 619, 4, 9666.67),
(3671, 619, 5, 9666.67),
(3672, 619, 6, 9666.67),
(3673, 620, 1, 9666.67),
(3674, 620, 2, 9666.67),
(3675, 620, 3, 9666.67),
(3676, 620, 4, 9666.67),
(3677, 620, 5, 9666.67),
(3678, 620, 6, 9666.67),
(3679, 621, 1, 9666.67),
(3680, 621, 2, 9666.67),
(3681, 621, 3, 9666.67),
(3682, 621, 4, 9666.67),
(3683, 621, 5, 9666.67),
(3684, 621, 6, 9666.67),
(3685, 622, 1, 10833.33),
(3686, 622, 2, 10833.33),
(3687, 622, 3, 10833.33),
(3688, 622, 4, 10833.33),
(3689, 622, 5, 10833.33),
(3690, 622, 6, 10833.33),
(3691, 623, 1, 10833.33),
(3692, 623, 2, 10833.33),
(3693, 623, 3, 10833.33),
(3694, 623, 4, 10833.33),
(3695, 623, 5, 10833.33),
(3696, 623, 6, 10833.33),
(3697, 624, 1, 10833.33),
(3698, 624, 2, 10833.33),
(3699, 624, 3, 10833.33),
(3700, 624, 4, 10833.33),
(3701, 624, 5, 10833.33),
(3702, 624, 6, 10833.33),
(3703, 625, 1, 9166.67),
(3704, 625, 2, 9166.67),
(3705, 625, 3, 9166.67),
(3706, 625, 4, 9166.67),
(3707, 625, 5, 9166.67),
(3708, 625, 6, 9166.67),
(3709, 626, 1, 9166.67),
(3710, 626, 2, 9166.67),
(3711, 626, 3, 9166.67),
(3712, 626, 4, 9166.67),
(3713, 626, 5, 9166.67),
(3714, 626, 6, 9166.67),
(3715, 627, 1, 9166.67),
(3716, 627, 2, 9166.67),
(3717, 627, 3, 9166.67),
(3718, 627, 4, 9166.67),
(3719, 627, 5, 9166.67),
(3720, 627, 6, 9166.67),
(3721, 628, 1, 10833.33),
(3722, 628, 2, 10833.33),
(3723, 628, 3, 10833.33),
(3724, 628, 4, 10833.33),
(3725, 628, 5, 10833.33),
(3726, 628, 6, 10833.33),
(3727, 629, 1, 10833.33),
(3728, 629, 2, 10833.33),
(3729, 629, 3, 10833.33),
(3730, 629, 4, 10833.33),
(3731, 629, 5, 10833.33),
(3732, 629, 6, 10833.33),
(3733, 630, 1, 10833.33),
(3734, 630, 2, 10833.33),
(3735, 630, 3, 10833.33),
(3736, 630, 4, 10833.33),
(3737, 630, 5, 10833.33),
(3738, 630, 6, 10833.33),
(3739, 631, 1, 11666.67),
(3740, 631, 2, 11666.67),
(3741, 631, 3, 11666.67),
(3742, 631, 4, 11666.67),
(3743, 631, 5, 11666.67),
(3744, 631, 6, 11666.67),
(3745, 632, 1, 11666.67),
(3746, 632, 2, 11666.67),
(3747, 632, 3, 11666.67),
(3748, 632, 4, 11666.67),
(3749, 632, 5, 11666.67),
(3750, 632, 6, 11666.67),
(3751, 633, 1, 11666.67),
(3752, 633, 2, 11666.67),
(3753, 633, 3, 11666.67),
(3754, 633, 4, 11666.67),
(3755, 633, 5, 11666.67),
(3756, 633, 6, 11666.67),
(3757, 634, 1, 10000.00),
(3758, 634, 2, 10000.00),
(3759, 634, 3, 10000.00),
(3760, 634, 4, 10000.00),
(3761, 634, 5, 10000.00),
(3762, 634, 6, 10000.00),
(3763, 635, 1, 10000.00),
(3764, 635, 2, 10000.00),
(3765, 635, 3, 10000.00),
(3766, 635, 4, 10000.00),
(3767, 635, 5, 10000.00),
(3768, 635, 6, 10000.00),
(3769, 636, 1, 10000.00),
(3770, 636, 2, 10000.00),
(3771, 636, 3, 10000.00),
(3772, 636, 4, 10000.00),
(3773, 636, 5, 10000.00),
(3774, 636, 6, 10000.00),
(3775, 637, 1, 9666.67),
(3776, 637, 2, 9666.67),
(3777, 637, 3, 9666.67),
(3778, 637, 4, 9666.67),
(3779, 637, 5, 9666.67),
(3780, 637, 6, 9666.67),
(3781, 638, 1, 9666.67),
(3782, 638, 2, 9666.67),
(3783, 638, 3, 9666.67),
(3784, 638, 4, 9666.67),
(3785, 638, 5, 9666.67),
(3786, 638, 6, 9666.67),
(3787, 639, 1, 9666.67),
(3788, 639, 2, 9666.67),
(3789, 639, 3, 9666.67),
(3790, 639, 4, 9666.67),
(3791, 639, 5, 9666.67),
(3792, 639, 6, 9666.67),
(3793, 640, 1, 10833.33),
(3794, 640, 2, 10833.33),
(3795, 640, 3, 10833.33),
(3796, 640, 4, 10833.33),
(3797, 640, 5, 10833.33),
(3798, 640, 6, 10833.33),
(3799, 641, 1, 10833.33),
(3800, 641, 2, 10833.33),
(3801, 641, 3, 10833.33),
(3802, 641, 4, 10833.33),
(3803, 641, 5, 10833.33),
(3804, 641, 6, 10833.33),
(3805, 642, 1, 10833.33),
(3806, 642, 2, 10833.33),
(3807, 642, 3, 10833.33),
(3808, 642, 4, 10833.33),
(3809, 642, 5, 10833.33),
(3810, 642, 6, 10833.33),
(3811, 643, 1, 9166.67),
(3812, 643, 2, 9166.67),
(3813, 643, 3, 9166.67),
(3814, 643, 4, 9166.67),
(3815, 643, 5, 9166.67),
(3816, 643, 6, 9166.67),
(3817, 644, 1, 9166.67),
(3818, 644, 2, 9166.67),
(3819, 644, 3, 9166.67),
(3820, 644, 4, 9166.67),
(3821, 644, 5, 9166.67),
(3822, 644, 6, 9166.67),
(3823, 645, 1, 9166.67),
(3824, 645, 2, 9166.67),
(3825, 645, 3, 9166.67),
(3826, 645, 4, 9166.67),
(3827, 645, 5, 9166.67),
(3828, 645, 6, 9166.67),
(3829, 646, 1, 10833.33),
(3830, 646, 2, 10833.33),
(3831, 646, 3, 10833.33),
(3832, 646, 4, 10833.33),
(3833, 646, 5, 10833.33),
(3834, 646, 6, 10833.33),
(3835, 647, 1, 10833.33),
(3836, 647, 2, 10833.33),
(3837, 647, 3, 10833.33),
(3838, 647, 4, 10833.33),
(3839, 647, 5, 10833.33),
(3840, 647, 6, 10833.33),
(3841, 648, 1, 10833.33),
(3842, 648, 2, 10833.33),
(3843, 648, 3, 10833.33),
(3844, 648, 4, 10833.33),
(3845, 648, 5, 10833.33),
(3846, 648, 6, 10833.33),
(3847, 649, 1, 11666.67),
(3848, 649, 2, 11666.67),
(3849, 649, 3, 11666.67),
(3850, 649, 4, 11666.67),
(3851, 649, 5, 11666.67),
(3852, 649, 6, 11666.67),
(3853, 650, 1, 11666.67),
(3854, 650, 2, 11666.67),
(3855, 650, 3, 11666.67),
(3856, 650, 4, 11666.67),
(3857, 650, 5, 11666.67),
(3858, 650, 6, 11666.67),
(3859, 651, 1, 11666.67),
(3860, 651, 2, 11666.67),
(3861, 651, 3, 11666.67),
(3862, 651, 4, 11666.67),
(3863, 651, 5, 11666.67),
(3864, 651, 6, 11666.67),
(3865, 652, 1, 10000.00),
(3866, 652, 2, 10000.00),
(3867, 652, 3, 10000.00),
(3868, 652, 4, 10000.00),
(3869, 652, 5, 10000.00),
(3870, 652, 6, 10000.00),
(3871, 653, 1, 10000.00),
(3872, 653, 2, 10000.00),
(3873, 653, 3, 10000.00),
(3874, 653, 4, 10000.00),
(3875, 653, 5, 10000.00),
(3876, 653, 6, 10000.00),
(3877, 654, 1, 10000.00),
(3878, 654, 2, 10000.00),
(3879, 654, 3, 10000.00),
(3880, 654, 4, 10000.00),
(3881, 654, 5, 10000.00),
(3882, 654, 6, 10000.00),
(3883, 655, 1, 9666.67),
(3884, 655, 2, 9666.67),
(3885, 655, 3, 9666.67),
(3886, 655, 4, 9666.67),
(3887, 655, 5, 9666.67),
(3888, 655, 6, 9666.67),
(3889, 656, 1, 9666.67),
(3890, 656, 2, 9666.67),
(3891, 656, 3, 9666.67),
(3892, 656, 4, 9666.67),
(3893, 656, 5, 9666.67),
(3894, 656, 6, 9666.67),
(3895, 657, 1, 9666.67),
(3896, 657, 2, 9666.67),
(3897, 657, 3, 9666.67),
(3898, 657, 4, 9666.67),
(3899, 657, 5, 9666.67),
(3900, 657, 6, 9666.67),
(3901, 658, 1, 10833.33),
(3902, 658, 2, 10833.33),
(3903, 658, 3, 10833.33),
(3904, 658, 4, 10833.33),
(3905, 658, 5, 10833.33),
(3906, 658, 6, 10833.33),
(3907, 659, 1, 10833.33),
(3908, 659, 2, 10833.33),
(3909, 659, 3, 10833.33),
(3910, 659, 4, 10833.33),
(3911, 659, 5, 10833.33),
(3912, 659, 6, 10833.33),
(3913, 660, 1, 10833.33),
(3914, 660, 2, 10833.33),
(3915, 660, 3, 10833.33),
(3916, 660, 4, 10833.33),
(3917, 660, 5, 10833.33),
(3918, 660, 6, 10833.33),
(3919, 661, 1, 9166.67),
(3920, 661, 2, 9166.67),
(3921, 661, 3, 9166.67),
(3922, 661, 4, 9166.67),
(3923, 661, 5, 9166.67),
(3924, 661, 6, 9166.67),
(3925, 662, 1, 9166.67),
(3926, 662, 2, 9166.67),
(3927, 662, 3, 9166.67),
(3928, 662, 4, 9166.67),
(3929, 662, 5, 9166.67),
(3930, 662, 6, 9166.67),
(3931, 663, 1, 9166.67),
(3932, 663, 2, 9166.67),
(3933, 663, 3, 9166.67),
(3934, 663, 4, 9166.67),
(3935, 663, 5, 9166.67),
(3936, 663, 6, 9166.67),
(3937, 664, 1, 10833.33),
(3938, 664, 2, 10833.33),
(3939, 664, 3, 10833.33),
(3940, 664, 4, 10833.33),
(3941, 664, 5, 10833.33),
(3942, 664, 6, 10833.33),
(3943, 665, 1, 10833.33),
(3944, 665, 2, 10833.33),
(3945, 665, 3, 10833.33),
(3946, 665, 4, 10833.33),
(3947, 665, 5, 10833.33),
(3948, 665, 6, 10833.33),
(3949, 666, 1, 10833.33),
(3950, 666, 2, 10833.33),
(3951, 666, 3, 10833.33),
(3952, 666, 4, 10833.33),
(3953, 666, 5, 10833.33),
(3954, 666, 6, 10833.33),
(3955, 667, 1, 11666.67),
(3956, 667, 2, 11666.67),
(3957, 667, 3, 11666.67),
(3958, 667, 4, 11666.67),
(3959, 667, 5, 11666.67),
(3960, 667, 6, 11666.67),
(3961, 668, 1, 11666.67),
(3962, 668, 2, 11666.67),
(3963, 668, 3, 11666.67),
(3964, 668, 4, 11666.67),
(3965, 668, 5, 11666.67),
(3966, 668, 6, 11666.67),
(3967, 669, 1, 11666.67),
(3968, 669, 2, 11666.67),
(3969, 669, 3, 11666.67),
(3970, 669, 4, 11666.67),
(3971, 669, 5, 11666.67),
(3972, 669, 6, 11666.67),
(3973, 670, 1, 10000.00),
(3974, 670, 2, 10000.00),
(3975, 670, 3, 10000.00),
(3976, 670, 4, 10000.00),
(3977, 670, 5, 10000.00),
(3978, 670, 6, 10000.00),
(3979, 671, 1, 10000.00),
(3980, 671, 2, 10000.00),
(3981, 671, 3, 10000.00),
(3982, 671, 4, 10000.00),
(3983, 671, 5, 10000.00),
(3984, 671, 6, 10000.00),
(3985, 672, 1, 10000.00),
(3986, 672, 2, 10000.00),
(3987, 672, 3, 10000.00),
(3988, 672, 4, 10000.00),
(3989, 672, 5, 10000.00),
(3990, 672, 6, 10000.00),
(3991, 673, 1, 9666.67),
(3992, 673, 2, 9666.67),
(3993, 673, 3, 9666.67),
(3994, 673, 4, 9666.67),
(3995, 673, 5, 9666.67),
(3996, 673, 6, 9666.67),
(3997, 674, 1, 9666.67),
(3998, 674, 2, 9666.67),
(3999, 674, 3, 9666.67),
(4000, 674, 4, 9666.67),
(4001, 674, 5, 9666.67),
(4002, 674, 6, 9666.67),
(4003, 675, 1, 9666.67),
(4004, 675, 2, 9666.67),
(4005, 675, 3, 9666.67),
(4006, 675, 4, 9666.67),
(4007, 675, 5, 9666.67),
(4008, 675, 6, 9666.67),
(4009, 676, 1, 10833.33),
(4010, 676, 2, 10833.33),
(4011, 676, 3, 10833.33),
(4012, 676, 4, 10833.33),
(4013, 676, 5, 10833.33),
(4014, 676, 6, 10833.33),
(4015, 677, 1, 10833.33),
(4016, 677, 2, 10833.33),
(4017, 677, 3, 10833.33),
(4018, 677, 4, 10833.33),
(4019, 677, 5, 10833.33),
(4020, 677, 6, 10833.33),
(4021, 678, 1, 10833.33),
(4022, 678, 2, 10833.33),
(4023, 678, 3, 10833.33),
(4024, 678, 4, 10833.33),
(4025, 678, 5, 10833.33),
(4026, 678, 6, 10833.33),
(4027, 679, 1, 9166.67),
(4028, 679, 2, 9166.67),
(4029, 679, 3, 9166.67),
(4030, 679, 4, 9166.67),
(4031, 679, 5, 9166.67),
(4032, 679, 6, 9166.67),
(4033, 680, 1, 9166.67),
(4034, 680, 2, 9166.67),
(4035, 680, 3, 9166.67),
(4036, 680, 4, 9166.67),
(4037, 680, 5, 9166.67),
(4038, 680, 6, 9166.67),
(4039, 681, 1, 9166.67),
(4040, 681, 2, 9166.67),
(4041, 681, 3, 9166.67),
(4042, 681, 4, 9166.67),
(4043, 681, 5, 9166.67),
(4044, 681, 6, 9166.67),
(4045, 682, 1, 10833.33),
(4046, 682, 2, 10833.33),
(4047, 682, 3, 10833.33),
(4048, 682, 4, 10833.33),
(4049, 682, 5, 10833.33),
(4050, 682, 6, 10833.33),
(4051, 683, 1, 10833.33),
(4052, 683, 2, 10833.33),
(4053, 683, 3, 10833.33),
(4054, 683, 4, 10833.33),
(4055, 683, 5, 10833.33),
(4056, 683, 6, 10833.33),
(4057, 684, 1, 10833.33),
(4058, 684, 2, 10833.33),
(4059, 684, 3, 10833.33),
(4060, 684, 4, 10833.33),
(4061, 684, 5, 10833.33),
(4062, 684, 6, 10833.33),
(4063, 685, 1, 11666.67),
(4064, 685, 2, 11666.67),
(4065, 685, 3, 11666.67),
(4066, 685, 4, 11666.67),
(4067, 685, 5, 11666.67),
(4068, 685, 6, 11666.67),
(4069, 686, 1, 11666.67),
(4070, 686, 2, 11666.67),
(4071, 686, 3, 11666.67),
(4072, 686, 4, 11666.67),
(4073, 686, 5, 11666.67),
(4074, 686, 6, 11666.67),
(4075, 687, 1, 11666.67),
(4076, 687, 2, 11666.67),
(4077, 687, 3, 11666.67),
(4078, 687, 4, 11666.67),
(4079, 687, 5, 11666.67),
(4080, 687, 6, 11666.67),
(4081, 688, 1, 10000.00),
(4082, 688, 2, 10000.00),
(4083, 688, 3, 10000.00),
(4084, 688, 4, 10000.00),
(4085, 688, 5, 10000.00),
(4086, 688, 6, 10000.00),
(4087, 689, 1, 10000.00),
(4088, 689, 2, 10000.00),
(4089, 689, 3, 10000.00),
(4090, 689, 4, 10000.00),
(4091, 689, 5, 10000.00),
(4092, 689, 6, 10000.00),
(4093, 690, 1, 10000.00),
(4094, 690, 2, 10000.00),
(4095, 690, 3, 10000.00),
(4096, 690, 4, 10000.00),
(4097, 690, 5, 10000.00),
(4098, 690, 6, 10000.00),
(4099, 691, 1, 9666.67),
(4100, 691, 2, 9666.67),
(4101, 691, 3, 9666.67),
(4102, 691, 4, 9666.67),
(4103, 691, 5, 9666.67),
(4104, 691, 6, 9666.67),
(4105, 692, 1, 9666.67),
(4106, 692, 2, 9666.67),
(4107, 692, 3, 9666.67),
(4108, 692, 4, 9666.67),
(4109, 692, 5, 9666.67),
(4110, 692, 6, 9666.67),
(4111, 693, 1, 9666.67),
(4112, 693, 2, 9666.67),
(4113, 693, 3, 9666.67),
(4114, 693, 4, 9666.67),
(4115, 693, 5, 9666.67),
(4116, 693, 6, 9666.67),
(4117, 694, 1, 10833.33),
(4118, 694, 2, 10833.33),
(4119, 694, 3, 10833.33),
(4120, 694, 4, 10833.33),
(4121, 694, 5, 10833.33),
(4122, 694, 6, 10833.33),
(4123, 695, 1, 10833.33),
(4124, 695, 2, 10833.33),
(4125, 695, 3, 10833.33),
(4126, 695, 4, 10833.33),
(4127, 695, 5, 10833.33),
(4128, 695, 6, 10833.33),
(4129, 696, 1, 10833.33),
(4130, 696, 2, 10833.33),
(4131, 696, 3, 10833.33),
(4132, 696, 4, 10833.33),
(4133, 696, 5, 10833.33),
(4134, 696, 6, 10833.33),
(4135, 697, 1, 9166.67),
(4136, 697, 2, 9166.67),
(4137, 697, 3, 9166.67),
(4138, 697, 4, 9166.67),
(4139, 697, 5, 9166.67),
(4140, 697, 6, 9166.67),
(4141, 698, 1, 9166.67),
(4142, 698, 2, 9166.67),
(4143, 698, 3, 9166.67),
(4144, 698, 4, 9166.67),
(4145, 698, 5, 9166.67),
(4146, 698, 6, 9166.67),
(4147, 699, 1, 9166.67),
(4148, 699, 2, 9166.67),
(4149, 699, 3, 9166.67),
(4150, 699, 4, 9166.67),
(4151, 699, 5, 9166.67),
(4152, 699, 6, 9166.67),
(4153, 700, 1, 10833.33),
(4154, 700, 2, 10833.33),
(4155, 700, 3, 10833.33),
(4156, 700, 4, 10833.33),
(4157, 700, 5, 10833.33),
(4158, 700, 6, 10833.33),
(4159, 701, 1, 10833.33),
(4160, 701, 2, 10833.33),
(4161, 701, 3, 10833.33),
(4162, 701, 4, 10833.33),
(4163, 701, 5, 10833.33),
(4164, 701, 6, 10833.33),
(4165, 702, 1, 10833.33),
(4166, 702, 2, 10833.33),
(4167, 702, 3, 10833.33),
(4168, 702, 4, 10833.33),
(4169, 702, 5, 10833.33),
(4170, 702, 6, 10833.33),
(4171, 703, 1, 11666.67),
(4172, 703, 2, 11666.67),
(4173, 703, 3, 11666.67),
(4174, 703, 4, 11666.67),
(4175, 703, 5, 11666.67),
(4176, 703, 6, 11666.67),
(4177, 704, 1, 11666.67),
(4178, 704, 2, 11666.67),
(4179, 704, 3, 11666.67),
(4180, 704, 4, 11666.67),
(4181, 704, 5, 11666.67),
(4182, 704, 6, 11666.67),
(4183, 705, 1, 11666.67),
(4184, 705, 2, 11666.67),
(4185, 705, 3, 11666.67),
(4186, 705, 4, 11666.67),
(4187, 705, 5, 11666.67),
(4188, 705, 6, 11666.67),
(4189, 706, 1, 10000.00),
(4190, 706, 2, 10000.00),
(4191, 706, 3, 10000.00),
(4192, 706, 4, 10000.00),
(4193, 706, 5, 10000.00),
(4194, 706, 6, 10000.00),
(4195, 707, 1, 10000.00),
(4196, 707, 2, 10000.00),
(4197, 707, 3, 10000.00),
(4198, 707, 4, 10000.00),
(4199, 707, 5, 10000.00),
(4200, 707, 6, 10000.00),
(4201, 708, 1, 10000.00),
(4202, 708, 2, 10000.00),
(4203, 708, 3, 10000.00),
(4204, 708, 4, 10000.00),
(4205, 708, 5, 10000.00),
(4206, 708, 6, 10000.00),
(4207, 709, 1, 9666.67),
(4208, 709, 2, 9666.67),
(4209, 709, 3, 9666.67),
(4210, 709, 4, 9666.67),
(4211, 709, 5, 9666.67),
(4212, 709, 6, 9666.67),
(4213, 710, 1, 9666.67),
(4214, 710, 2, 9666.67),
(4215, 710, 3, 9666.67),
(4216, 710, 4, 9666.67),
(4217, 710, 5, 9666.67),
(4218, 710, 6, 9666.67),
(4219, 711, 1, 9666.67),
(4220, 711, 2, 9666.67),
(4221, 711, 3, 9666.67),
(4222, 711, 4, 9666.67),
(4223, 711, 5, 9666.67),
(4224, 711, 6, 9666.67),
(4225, 712, 1, 10833.33),
(4226, 712, 2, 10833.33),
(4227, 712, 3, 10833.33),
(4228, 712, 4, 10833.33),
(4229, 712, 5, 10833.33),
(4230, 712, 6, 10833.33),
(4231, 713, 1, 10833.33),
(4232, 713, 2, 10833.33),
(4233, 713, 3, 10833.33),
(4234, 713, 4, 10833.33),
(4235, 713, 5, 10833.33),
(4236, 713, 6, 10833.33),
(4237, 714, 1, 10833.33),
(4238, 714, 2, 10833.33),
(4239, 714, 3, 10833.33),
(4240, 714, 4, 10833.33),
(4241, 714, 5, 10833.33),
(4242, 714, 6, 10833.33),
(4243, 715, 1, 9166.67),
(4244, 715, 2, 9166.67),
(4245, 715, 3, 9166.67),
(4246, 715, 4, 9166.67),
(4247, 715, 5, 9166.67),
(4248, 715, 6, 9166.67),
(4249, 716, 1, 9166.67),
(4250, 716, 2, 9166.67),
(4251, 716, 3, 9166.67),
(4252, 716, 4, 9166.67),
(4253, 716, 5, 9166.67),
(4254, 716, 6, 9166.67),
(4255, 717, 1, 9166.67),
(4256, 717, 2, 9166.67),
(4257, 717, 3, 9166.67),
(4258, 717, 4, 9166.67),
(4259, 717, 5, 9166.67),
(4260, 717, 6, 9166.67),
(4261, 718, 1, 10833.33),
(4262, 718, 2, 10833.33),
(4263, 718, 3, 10833.33),
(4264, 718, 4, 10833.33),
(4265, 718, 5, 10833.33),
(4266, 718, 6, 10833.33),
(4267, 719, 1, 10833.33),
(4268, 719, 2, 10833.33),
(4269, 719, 3, 10833.33),
(4270, 719, 4, 10833.33),
(4271, 719, 5, 10833.33),
(4272, 719, 6, 10833.33),
(4273, 720, 1, 10833.33),
(4274, 720, 2, 10833.33),
(4275, 720, 3, 10833.33),
(4276, 720, 4, 10833.33),
(4277, 720, 5, 10833.33),
(4278, 720, 6, 10833.33),
(4279, 721, 1, 11666.67),
(4280, 721, 2, 11666.67),
(4281, 721, 3, 11666.67),
(4282, 721, 4, 11666.67),
(4283, 721, 5, 11666.67),
(4284, 721, 6, 11666.67),
(4285, 722, 1, 11666.67),
(4286, 722, 2, 11666.67),
(4287, 722, 3, 11666.67),
(4288, 722, 4, 11666.67),
(4289, 722, 5, 11666.67),
(4290, 722, 6, 11666.67);
INSERT INTO `fee_structure_details` (`id`, `fee_structure_id`, `fee_head_id`, `amount`) VALUES
(4291, 723, 1, 11666.67),
(4292, 723, 2, 11666.67),
(4293, 723, 3, 11666.67),
(4294, 723, 4, 11666.67),
(4295, 723, 5, 11666.67),
(4296, 723, 6, 11666.67),
(4297, 724, 1, 10000.00),
(4298, 724, 2, 10000.00),
(4299, 724, 3, 10000.00),
(4300, 724, 4, 10000.00),
(4301, 724, 5, 10000.00),
(4302, 724, 6, 10000.00),
(4303, 725, 1, 10000.00),
(4304, 725, 2, 10000.00),
(4305, 725, 3, 10000.00),
(4306, 725, 4, 10000.00),
(4307, 725, 5, 10000.00),
(4308, 725, 6, 10000.00),
(4309, 726, 1, 10000.00),
(4310, 726, 2, 10000.00),
(4311, 726, 3, 10000.00),
(4312, 726, 4, 10000.00),
(4313, 726, 5, 10000.00),
(4314, 726, 6, 10000.00),
(4315, 727, 1, 9666.67),
(4316, 727, 2, 9666.67),
(4317, 727, 3, 9666.67),
(4318, 727, 4, 9666.67),
(4319, 727, 5, 9666.67),
(4320, 727, 6, 9666.67),
(4321, 728, 1, 9666.67),
(4322, 728, 2, 9666.67),
(4323, 728, 3, 9666.67),
(4324, 728, 4, 9666.67),
(4325, 728, 5, 9666.67),
(4326, 728, 6, 9666.67),
(4327, 729, 1, 9666.67),
(4328, 729, 2, 9666.67),
(4329, 729, 3, 9666.67),
(4330, 729, 4, 9666.67),
(4331, 729, 5, 9666.67),
(4332, 729, 6, 9666.67);

-- --------------------------------------------------------

--
-- Table structure for table `installments`
--

CREATE TABLE `installments` (
  `installment_id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `installment_no` tinyint(4) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date NOT NULL,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Pending','Paid','Overdue') NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `installments`
--

INSERT INTO `installments` (`installment_id`, `student_fee_id`, `installment_no`, `amount`, `due_date`, `paid_amount`, `status`) VALUES
(11, 11, 4, 500.00, '2026-09-12', 3000.00, ''),
(12, 12, 1, 500.00, '2026-09-13', 3000.00, ''),
(13, 13, 2, 500.00, '2026-09-14', 3000.00, ''),
(14, 14, 3, 500.00, '2026-09-15', 3000.00, ''),
(15, 15, 4, 500.00, '2026-09-16', 3000.00, ''),
(16, 16, 1, 500.00, '2026-09-17', 3000.00, ''),
(17, 17, 2, 500.00, '2026-09-18', 3000.00, ''),
(18, 18, 3, 500.00, '2026-09-19', 3000.00, ''),
(19, 19, 4, 500.00, '2026-09-20', 3000.00, ''),
(20, 20, 1, 500.00, '2026-09-21', 3000.00, ''),
(37, 13, 1, 21333.33, '2026-08-27', 0.00, 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `lms_academic_calendar`
--

CREATE TABLE `lms_academic_calendar` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_academic_calendar`
--

INSERT INTO `lms_academic_calendar` (`event_id`, `title`, `event_date`, `event_type`, `description`) VALUES
(1, 'Course 1', '0000-00-00', 'Lecture', 'Description for item 1'),
(2, 'Course 2', '0000-00-00', 'Lecture', 'Description for item 2'),
(3, 'Course 3', '0000-00-00', 'Lecture', 'Description for item 3'),
(4, 'Course 4', '0000-00-00', 'Lecture', 'Description for item 4'),
(5, 'Course 5', '0000-00-00', 'Lecture', 'Description for item 5'),
(6, 'Course 6', '0000-00-00', 'Lecture', 'Description for item 6'),
(7, 'Course 7', '0000-00-00', 'Lecture', 'Description for item 7'),
(8, 'Course 8', '0000-00-00', 'Lecture', 'Description for item 8'),
(9, 'Course 9', '0000-00-00', 'Lecture', 'Description for item 9'),
(10, 'Course 10', '0000-00-00', 'Lecture', 'Description for item 10'),
(11, 'Course 1', '0000-00-00', 'Lecture', 'Description for item 1'),
(12, 'Course 2', '0000-00-00', 'Lecture', 'Description for item 2'),
(13, 'Course 3', '0000-00-00', 'Lecture', 'Description for item 3'),
(14, 'Course 4', '0000-00-00', 'Lecture', 'Description for item 4'),
(15, 'Course 5', '0000-00-00', 'Lecture', 'Description for item 5'),
(16, 'Course 6', '0000-00-00', 'Lecture', 'Description for item 6'),
(17, 'Course 7', '0000-00-00', 'Lecture', 'Description for item 7'),
(18, 'Course 8', '0000-00-00', 'Lecture', 'Description for item 8'),
(19, 'Course 9', '0000-00-00', 'Lecture', 'Description for item 9'),
(20, 'Course 10', '0000-00-00', 'Lecture', 'Description for item 10');

-- --------------------------------------------------------

--
-- Table structure for table `lms_announcements`
--

CREATE TABLE `lms_announcements` (
  `announcement_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text DEFAULT NULL,
  `author_user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_announcements`
--

INSERT INTO `lms_announcements` (`announcement_id`, `course_id`, `title`, `body`, `author_user_id`, `created_at`) VALUES
(4, 17, 'Course 4', 'Demo content for record 4', 0, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 'Demo content for record 5', 0, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 'Demo content for record 6', 0, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 'Demo content for record 7', 0, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 'Demo content for record 8', 0, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 'Demo content for record 9', 0, '2026-07-15 05:30:00'),
(14, 17, 'Course 4', 'Demo content for record 4', 0, '2026-07-15 05:30:00'),
(15, 18, 'Course 5', 'Demo content for record 5', 0, '2026-07-15 05:30:00'),
(16, 19, 'Course 6', 'Demo content for record 6', 0, '2026-07-15 05:30:00'),
(17, 20, 'Course 7', 'Demo content for record 7', 0, '2026-07-15 05:30:00'),
(18, 21, 'Course 8', 'Demo content for record 8', 0, '2026-07-15 05:30:00'),
(19, 22, 'Course 9', 'Demo content for record 9', 0, '2026-07-15 05:30:00'),
(27, 13, 'Welcome to CS101 - Spring 2026', 'Welcome students! Please review the syllabus on the course page. First assignment will be posted next week.', 19, '2026-02-05 05:00:00'),
(28, 13, 'Mid-term Schedule Announced', 'The mid-term exam will be held on March 25, 2026. Please bring your student ID. Syllabus covers Chapters 1-8.', 19, '2026-03-10 04:00:00'),
(29, 14, 'CS201 - Lab Session Rescheduled', 'This week\'s lab session has been moved from Thursday to Friday 2PM. Room 204.', 19, '2026-02-12 09:00:00'),
(30, 15, 'Operating Systems - Guest Lecture', 'Dr. Ahmed from NUST will deliver a guest lecture on Memory Management on March 5. Attendance mandatory.', 19, '2026-02-28 06:00:00'),
(31, 16, 'Database Systems - Project Guidelines', 'Final year project guidelines have been posted. Choose your topic by March 30. Teams of 2-3 allowed.', 19, '2026-03-01 05:30:00'),
(32, 13, 'Assignment 2 Grading Complete', 'Assignment 2 grades are now available on the marks page. Please check your feedback.', 19, '2026-05-01 11:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `lms_applications`
--

CREATE TABLE `lms_applications` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_applications`
--

INSERT INTO `lms_applications` (`application_id`, `user_id`, `type`, `details`, `status`, `created_at`, `updated_at`) VALUES
(1, 2, 'announcement', 'Demo content for record 1', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(2, 3, 'announcement', 'Demo content for record 2', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(3, 4, 'announcement', 'Demo content for record 3', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(4, 5, 'announcement', 'Demo content for record 4', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(5, 6, 'announcement', 'Demo content for record 5', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(6, 2, 'announcement', 'Demo content for record 1', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(7, 3, 'announcement', 'Demo content for record 2', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(8, 4, 'announcement', 'Demo content for record 3', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(9, 5, 'announcement', 'Demo content for record 4', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(10, 6, 'announcement', 'Demo content for record 5', '', '2026-07-15 10:30:00', '2026-07-15 10:30:00'),
(11, 12, 'leave', 'i was out of the City', 'pending', '2026-07-27 23:43:29', '2026-07-27 23:43:29');

-- --------------------------------------------------------

--
-- Table structure for table `lms_assignments`
--

CREATE TABLE `lms_assignments` (
  `assignment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` datetime DEFAULT NULL,
  `max_marks` decimal(5,2) DEFAULT 100.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_assignments`
--

INSERT INTO `lms_assignments` (`assignment_id`, `course_id`, `title`, `description`, `due_date`, `max_marks`, `created_at`) VALUES
(4, 17, 'Course 4', 'Description for item 4', '2026-09-05 00:00:00', 100.00, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 'Description for item 5', '2026-09-06 00:00:00', 100.00, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 'Description for item 6', '2026-09-07 00:00:00', 100.00, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 'Description for item 7', '2026-09-08 00:00:00', 100.00, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 'Description for item 8', '2026-09-09 00:00:00', 100.00, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 'Description for item 9', '2026-09-10 00:00:00', 100.00, '2026-07-15 05:30:00'),
(17, 13, 'Assignment 1: Basic Commands', 'Write a report on fundamental computer operations and binary number system.', '2026-03-15 23:59:00', 20.00, '2026-07-28 08:34:48'),
(18, 13, 'Assignment 2: Office Suite', 'Create a spreadsheet with formulas and a presentation on any topic.', '2026-04-20 23:59:00', 20.00, '2026-07-28 08:34:48'),
(19, 14, 'Assignment 1: Linked Lists', 'Implement singly and doubly linked lists in your preferred language.', '2026-03-20 23:59:00', 20.00, '2026-07-28 08:34:48'),
(20, 14, 'Assignment 2: Binary Trees', 'Write code to traverse a binary tree using BFS and DFS.', '2026-04-25 23:59:00', 20.00, '2026-07-28 08:34:48'),
(21, 15, 'Assignment 1: Process Scheduling', 'Simulate FCFS, SJF, and Round Robin scheduling algorithms.', '2026-03-25 23:59:00', 20.00, '2026-07-28 08:34:48'),
(22, 16, 'Assignment 1: ER Diagrams', 'Design an ER diagram for a university library system.', '2026-03-18 23:59:00', 20.00, '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_course_materials`
--

CREATE TABLE `lms_course_materials` (
  `material_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_course_materials`
--

INSERT INTO `lms_course_materials` (`material_id`, `course_id`, `title`, `file_path`, `uploaded_by`, `created_at`) VALUES
(4, 17, 'Course 4', '/uploads/file4.pdf', 0, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', '/uploads/file5.pdf', 0, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', '/uploads/file6.pdf', 0, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', '/uploads/file7.pdf', 0, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', '/uploads/file8.pdf', 0, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', '/uploads/file9.pdf', 0, '2026-07-15 05:30:00'),
(19, 13, 'CS101 Syllabus Spring 2026', 'materials/cs101_syllabus.pdf', 19, '2026-07-28 08:34:48'),
(20, 13, 'Chapter 1 Slides', 'materials/cs101_ch1.pptx', 19, '2026-07-28 08:34:48'),
(21, 13, 'Chapter 2 Slides', 'materials/cs101_ch2.pptx', 19, '2026-07-28 08:34:48'),
(22, 14, 'CS201 Lab Manual', 'materials/cs201_lab.pdf', 19, '2026-07-28 08:34:48'),
(23, 14, 'Data Structures Cheat Sheet', 'materials/ds_cheatsheet.pdf', 19, '2026-07-28 08:34:48'),
(24, 15, 'OS Textbook Chapters 1-4', 'materials/os_ch1_4.pdf', 19, '2026-07-28 08:34:48'),
(25, 16, 'Database Design Tutorial', 'materials/db_design.pdf', 19, '2026-07-28 08:34:48'),
(26, 16, 'SQL Practice Exercises', 'materials/sql_exercises.pdf', 19, '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_datesheets`
--

CREATE TABLE `lms_datesheets` (
  `datesheet_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `exam_type` varchar(50) DEFAULT NULL,
  `exam_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_datesheets`
--

INSERT INTO `lms_datesheets` (`datesheet_id`, `course_id`, `exam_type`, `exam_date`, `start_time`, `end_time`, `room`) VALUES
(1, 14, 'general', '2026-09-02', '09:00:00', '09:00:00', 'Room 101'),
(2, 15, 'general', '2026-09-03', '09:00:00', '09:00:00', 'Room 102'),
(3, 16, 'general', '2026-09-04', '09:00:00', '09:00:00', 'Room 103'),
(4, 17, 'general', '2026-09-05', '09:00:00', '09:00:00', 'Room 104'),
(5, 18, 'general', '2026-09-06', '09:00:00', '09:00:00', 'Room 105'),
(6, 19, 'general', '2026-09-07', '09:00:00', '09:00:00', 'Room 106'),
(7, 20, 'general', '2026-09-08', '09:00:00', '09:00:00', 'Room 107'),
(8, 21, 'general', '2026-09-09', '09:00:00', '09:00:00', 'Room 108'),
(9, 22, 'general', '2026-09-10', '09:00:00', '09:00:00', 'Room 109'),
(10, 13, 'general', '2026-09-11', '09:00:00', '09:00:00', 'Room 110'),
(11, 14, 'general', '2026-09-02', '09:00:00', '09:00:00', 'Room 101'),
(12, 15, 'general', '2026-09-03', '09:00:00', '09:00:00', 'Room 102'),
(13, 16, 'general', '2026-09-04', '09:00:00', '09:00:00', 'Room 103'),
(14, 17, 'general', '2026-09-05', '09:00:00', '09:00:00', 'Room 104'),
(15, 18, 'general', '2026-09-06', '09:00:00', '09:00:00', 'Room 105'),
(16, 19, 'general', '2026-09-07', '09:00:00', '09:00:00', 'Room 106'),
(17, 20, 'general', '2026-09-08', '09:00:00', '09:00:00', 'Room 107'),
(18, 21, 'general', '2026-09-09', '09:00:00', '09:00:00', 'Room 108'),
(19, 22, 'general', '2026-09-10', '09:00:00', '09:00:00', 'Room 109'),
(20, 13, 'general', '2026-09-11', '09:00:00', '09:00:00', 'Room 110');

-- --------------------------------------------------------

--
-- Table structure for table `lms_enrollments`
--

CREATE TABLE `lms_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `enrolled_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_enrollments`
--

INSERT INTO `lms_enrollments` (`enrollment_id`, `course_id`, `student_user_id`, `enrolled_at`) VALUES
(11, 14, 1, '0000-00-00 00:00:00'),
(21, 27, 37, '2026-07-27 19:36:26'),
(22, 28, 37, '2026-07-27 19:36:26'),
(23, 29, 37, '2026-07-27 19:36:26'),
(24, 30, 37, '2026-07-27 19:36:26'),
(25, 31, 37, '2026-07-27 19:36:26'),
(26, 32, 37, '2026-07-27 19:36:26'),
(27, 33, 37, '2026-07-27 19:36:26'),
(28, 13, 47, '2026-07-28 05:57:46'),
(29, 14, 47, '2026-07-28 05:57:46'),
(30, 15, 47, '2026-07-28 05:57:46'),
(31, 16, 47, '2026-07-28 05:57:46'),
(32, 17, 47, '2026-07-28 05:57:46'),
(33, 23, 47, '2026-07-28 05:57:46'),
(34, 27, 47, '2026-07-28 05:57:46'),
(35, 28, 47, '2026-07-28 05:57:46'),
(36, 29, 47, '2026-07-28 05:57:46'),
(37, 30, 47, '2026-07-28 05:57:46'),
(38, 31, 47, '2026-07-28 05:57:46'),
(39, 32, 47, '2026-07-28 05:57:46'),
(40, 33, 47, '2026-07-28 05:57:46'),
(43, 13, 21, '2026-02-01 04:00:00'),
(44, 14, 21, '2026-02-01 04:01:00'),
(45, 15, 21, '2026-02-01 04:02:00'),
(46, 13, 22, '2026-02-01 04:03:00'),
(47, 14, 22, '2026-02-01 04:04:00'),
(48, 16, 22, '2026-02-01 04:05:00'),
(49, 13, 37, '2026-02-01 04:06:00'),
(50, 15, 37, '2026-02-01 04:07:00'),
(51, 16, 37, '2026-02-01 04:08:00'),
(52, 14, 47, '2026-02-01 04:09:00'),
(53, 15, 47, '2026-02-01 04:10:00'),
(54, 16, 47, '2026-02-01 04:11:00'),
(55, 13, 21, '2026-02-01 04:00:00'),
(56, 14, 21, '2026-02-01 04:01:00'),
(57, 15, 21, '2026-02-01 04:02:00'),
(58, 13, 22, '2026-02-01 04:03:00'),
(59, 14, 22, '2026-02-01 04:04:00'),
(60, 16, 22, '2026-02-01 04:05:00'),
(61, 13, 37, '2026-02-01 04:06:00'),
(62, 15, 37, '2026-02-01 04:07:00'),
(63, 16, 37, '2026-02-01 04:08:00'),
(64, 14, 47, '2026-02-01 04:09:00'),
(65, 15, 47, '2026-02-01 04:10:00'),
(66, 16, 47, '2026-02-01 04:11:00');

-- --------------------------------------------------------

--
-- Table structure for table `lms_exams`
--

CREATE TABLE `lms_exams` (
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `total_marks` decimal(5,2) DEFAULT 0.00,
  `exam_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_exams`
--

INSERT INTO `lms_exams` (`exam_id`, `course_id`, `title`, `total_marks`, `exam_date`) VALUES
(1, 14, 'Course 1', 100.00, '2026-09-02'),
(2, 15, 'Course 2', 100.00, '2026-09-03'),
(3, 16, 'Course 3', 100.00, '2026-09-04'),
(4, 17, 'Course 4', 100.00, '2026-09-05'),
(5, 18, 'Course 5', 100.00, '2026-09-06'),
(6, 19, 'Course 6', 100.00, '2026-09-07'),
(7, 20, 'Course 7', 100.00, '2026-09-08'),
(8, 21, 'Course 8', 100.00, '2026-09-09'),
(9, 22, 'Course 9', 100.00, '2026-09-10'),
(10, 13, 'Course 10', 100.00, '2026-09-11');

-- --------------------------------------------------------

--
-- Table structure for table `lms_fees`
--

CREATE TABLE `lms_fees` (
  `fee_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('paid','unpaid','partial') DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_fees`
--

INSERT INTO `lms_fees` (`fee_id`, `student_user_id`, `course_id`, `amount`, `status`, `due_date`, `paid_at`) VALUES
(13, 21, 13, 45000.00, 'paid', '2026-02-15', '2026-02-10 09:30:00'),
(14, 21, 14, 48000.00, 'paid', '2026-02-15', '2026-02-12 04:00:00'),
(15, 21, 15, 52000.00, 'partial', '2026-02-15', '2026-02-14 11:45:00'),
(16, 22, 13, 45000.00, 'paid', '2026-02-15', '2026-02-11 06:20:00'),
(17, 22, 14, 48000.00, 'unpaid', '2026-02-15', NULL),
(18, 22, 16, 55000.00, 'partial', '2026-02-15', '2026-02-13 05:00:00'),
(19, 37, 13, 45000.00, 'paid', '2026-02-15', '2026-02-10 03:00:00'),
(20, 37, 15, 52000.00, 'paid', '2026-02-15', '2026-02-11 10:30:00'),
(21, 37, 16, 55000.00, 'unpaid', '2026-02-15', NULL),
(22, 47, 14, 48000.00, 'paid', '2026-02-15', '2026-02-10 07:00:00'),
(23, 47, 15, 52000.00, 'partial', '2026-02-15', '2026-02-12 04:30:00'),
(24, 47, 16, 55000.00, 'unpaid', '2026-02-15', NULL),
(25, 21, 13, 45000.00, 'paid', '2026-02-15', '2026-02-10 09:30:00'),
(26, 21, 14, 48000.00, 'paid', '2026-02-15', '2026-02-12 04:00:00'),
(27, 21, 15, 52000.00, 'partial', '2026-02-15', '2026-02-14 11:45:00'),
(28, 22, 13, 45000.00, 'paid', '2026-02-15', '2026-02-11 06:20:00'),
(29, 22, 14, 48000.00, 'unpaid', '2026-02-15', NULL),
(30, 22, 16, 55000.00, 'partial', '2026-02-15', '2026-02-13 05:00:00'),
(31, 37, 13, 45000.00, 'paid', '2026-02-15', '2026-02-10 03:00:00'),
(32, 37, 15, 52000.00, 'paid', '2026-02-15', '2026-02-11 10:30:00'),
(33, 37, 16, 55000.00, 'unpaid', '2026-02-15', NULL),
(34, 47, 14, 48000.00, 'paid', '2026-02-15', '2026-02-10 07:00:00'),
(35, 47, 15, 52000.00, 'partial', '2026-02-15', '2026-02-12 04:30:00'),
(36, 47, 16, 1000.00, 'unpaid', '2026-08-29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lms_lectures`
--

CREATE TABLE `lms_lectures` (
  `lecture_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `lecture_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_lectures`
--

INSERT INTO `lms_lectures` (`lecture_id`, `course_id`, `title`, `lecture_date`, `created_at`) VALUES
(4, 17, 'Course 4', '2026-09-05', '2026-07-15 05:30:00'),
(5, 18, 'Course 5', '2026-09-06', '2026-07-15 05:30:00'),
(6, 19, 'Course 6', '2026-09-07', '2026-07-15 05:30:00'),
(7, 20, 'Course 7', '2026-09-08', '2026-07-15 05:30:00'),
(8, 21, 'Course 8', '2026-09-09', '2026-07-15 05:30:00'),
(9, 22, 'Course 9', '2026-09-10', '2026-07-15 05:30:00'),
(26, 13, 'Lecture 1: Introduction to Computers', '2026-02-03', '2026-07-28 08:34:48'),
(27, 13, 'Lecture 2: Number Systems', '2026-02-10', '2026-07-28 08:34:48'),
(28, 13, 'Lecture 3: Computer Architecture', '2026-02-17', '2026-07-28 08:34:48'),
(29, 13, 'Lecture 4: Operating System Basics', '2026-02-24', '2026-07-28 08:34:48'),
(30, 13, 'Lecture 5: Introduction to Networking', '2026-03-03', '2026-07-28 08:34:48'),
(31, 14, 'Lecture 1: Introduction to Data Structures', '2026-02-04', '2026-07-28 08:34:48'),
(32, 14, 'Lecture 2: Arrays and Linked Lists', '2026-02-11', '2026-07-28 08:34:48'),
(33, 14, 'Lecture 3: Stacks and Queues', '2026-02-18', '2026-07-28 08:34:48'),
(34, 14, 'Lecture 4: Trees and Graphs', '2026-02-25', '2026-07-28 08:34:48'),
(35, 15, 'Lecture 1: What is an Operating System', '2026-02-05', '2026-07-28 08:34:48'),
(36, 15, 'Lecture 2: Process Management', '2026-02-12', '2026-07-28 08:34:48'),
(37, 15, 'Lecture 3: CPU Scheduling', '2026-02-19', '2026-07-28 08:34:48'),
(38, 16, 'Lecture 1: Introduction to Databases', '2026-02-06', '2026-07-28 08:34:48'),
(39, 16, 'Lecture 2: ER Model', '2026-02-13', '2026-07-28 08:34:48'),
(40, 16, 'Lecture 3: Relational Model', '2026-02-20', '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_marks`
--

CREATE TABLE `lms_marks` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `component` varchar(50) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT 0.00,
  `total_marks` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_marks`
--

INSERT INTO `lms_marks` (`id`, `course_id`, `student_user_id`, `component`, `marks_obtained`, `total_marks`) VALUES
(11, 14, 1, 'value_11', 100.00, NULL),
(31, 14, 1, 'value_11', 100.00, NULL),
(41, 13, 21, 'quiz_1', 8.00, 10.00),
(42, 13, 21, 'quiz_2', 9.00, 10.00),
(43, 13, 21, 'quiz_3', 7.50, 10.00),
(44, 13, 21, 'assignment_1', 18.00, 20.00),
(45, 13, 21, 'assignment_2', 16.50, 20.00),
(46, 13, 21, 'assignment_3', 19.00, 20.00),
(47, 13, 21, 'test_1', 22.00, 30.00),
(48, 13, 21, 'test_2', 25.00, 30.00),
(49, 13, 21, 'presentation', 14.00, 15.00),
(50, 13, 21, 'mid_term', 42.00, 50.00),
(51, 14, 21, 'quiz_1', 7.00, 10.00),
(52, 14, 21, 'quiz_2', 8.50, 10.00),
(53, 14, 21, 'assignment_1', 15.00, 20.00),
(54, 14, 21, 'assignment_2', 17.00, 20.00),
(55, 14, 21, 'test_1', 20.00, 30.00),
(56, 14, 21, 'test_2', 23.00, 30.00),
(57, 14, 21, 'presentation', 12.00, 15.00),
(58, 14, 21, 'mid_term', 38.00, 50.00),
(59, 15, 21, 'quiz_1', 9.00, 10.00),
(60, 15, 21, 'quiz_2', 8.00, 10.00),
(61, 15, 21, 'assignment_1', 19.00, 20.00),
(62, 15, 21, 'test_1', 26.00, 30.00),
(63, 15, 21, 'mid_term', 44.00, 50.00),
(64, 13, 22, 'quiz_1', 6.00, 10.00),
(65, 13, 22, 'quiz_2', 7.50, 10.00),
(66, 13, 22, 'quiz_3', 8.00, 10.00),
(67, 13, 22, 'assignment_1', 14.00, 20.00),
(68, 13, 22, 'assignment_2', 15.50, 20.00),
(69, 13, 22, 'test_1', 18.00, 30.00),
(70, 13, 22, 'test_2', 20.00, 30.00),
(71, 13, 22, 'presentation', 11.00, 15.00),
(72, 13, 22, 'mid_term', 35.00, 50.00),
(73, 14, 22, 'quiz_1', 5.50, 10.00),
(74, 14, 22, 'quiz_2', 6.00, 10.00),
(75, 14, 22, 'assignment_1', 12.00, 20.00),
(76, 14, 22, 'assignment_2', 13.50, 20.00),
(77, 14, 22, 'test_1', 16.00, 30.00),
(78, 14, 22, 'test_2', 19.00, 30.00),
(79, 14, 22, 'mid_term', 32.00, 50.00),
(80, 16, 22, 'quiz_1', 8.50, 10.00),
(81, 16, 22, 'quiz_2', 7.00, 10.00),
(82, 16, 22, 'assignment_1', 16.00, 20.00),
(83, 16, 22, 'test_1', 21.00, 30.00),
(84, 16, 22, 'mid_term', 40.00, 50.00),
(85, 13, 37, 'quiz_1', 7.00, 10.00),
(86, 13, 37, 'quiz_2', 8.00, 10.00),
(87, 13, 37, 'assignment_1', 16.00, 20.00),
(88, 13, 37, 'test_1', 21.00, 30.00),
(89, 13, 37, 'mid_term', 39.00, 50.00),
(90, 15, 37, 'quiz_1', 6.50, 10.00),
(91, 15, 37, 'quiz_2', 7.00, 10.00),
(92, 15, 37, 'assignment_1', 14.00, 20.00),
(93, 15, 37, 'test_1', 19.00, 30.00),
(94, 15, 37, 'mid_term', 34.00, 50.00),
(95, 16, 37, 'quiz_1', 9.00, 10.00),
(96, 16, 37, 'quiz_2', 8.50, 10.00),
(97, 16, 37, 'assignment_1', 18.00, 20.00),
(98, 16, 37, 'test_1', 25.00, 30.00),
(99, 16, 37, 'mid_term', 43.00, 50.00),
(100, 14, 47, 'quiz_1', 7.50, 10.00),
(101, 14, 47, 'quiz_2', 8.00, 10.00),
(102, 14, 47, 'assignment_1', 15.50, 20.00),
(103, 14, 47, 'test_1', 22.00, 30.00),
(104, 14, 47, 'mid_term', 41.00, 50.00),
(105, 15, 47, 'quiz_1', 8.00, 10.00),
(106, 15, 47, 'quiz_2', 9.00, 10.00),
(107, 15, 47, 'assignment_1', 17.00, 20.00),
(108, 15, 47, 'test_1', 24.00, 30.00),
(109, 15, 47, 'mid_term', 45.00, 50.00),
(110, 16, 47, 'quiz_1', 6.00, 10.00),
(111, 16, 47, 'quiz_2', 7.50, 10.00),
(112, 16, 47, 'assignment_1', 13.00, 20.00),
(113, 16, 47, 'test_1', 18.00, 30.00),
(114, 16, 47, 'mid_term', 36.00, 50.00),
(115, 13, 21, 'quiz_1', 8.00, 10.00),
(116, 13, 21, 'quiz_2', 9.00, 10.00),
(117, 13, 21, 'quiz_3', 7.50, 10.00),
(118, 13, 21, 'assignment_1', 18.00, 20.00),
(119, 13, 21, 'assignment_2', 16.50, 20.00),
(120, 13, 21, 'assignment_3', 19.00, 20.00),
(121, 13, 21, 'test_1', 22.00, 30.00),
(122, 13, 21, 'test_2', 25.00, 30.00),
(123, 13, 21, 'presentation', 14.00, 15.00),
(124, 13, 21, 'mid_term', 42.00, 50.00),
(125, 14, 21, 'quiz_1', 7.00, 10.00),
(126, 14, 21, 'quiz_2', 8.50, 10.00),
(127, 14, 21, 'assignment_1', 15.00, 20.00),
(128, 14, 21, 'assignment_2', 17.00, 20.00),
(129, 14, 21, 'test_1', 20.00, 30.00),
(130, 14, 21, 'test_2', 23.00, 30.00),
(131, 14, 21, 'presentation', 12.00, 15.00),
(132, 14, 21, 'mid_term', 38.00, 50.00),
(133, 15, 21, 'quiz_1', 9.00, 10.00),
(134, 15, 21, 'quiz_2', 8.00, 10.00),
(135, 15, 21, 'assignment_1', 19.00, 20.00),
(136, 15, 21, 'test_1', 26.00, 30.00),
(137, 15, 21, 'mid_term', 44.00, 50.00),
(138, 13, 22, 'quiz_1', 6.00, 10.00),
(139, 13, 22, 'quiz_2', 7.50, 10.00),
(140, 13, 22, 'quiz_3', 8.00, 10.00),
(141, 13, 22, 'assignment_1', 14.00, 20.00),
(142, 13, 22, 'assignment_2', 15.50, 20.00),
(143, 13, 22, 'test_1', 18.00, 30.00),
(144, 13, 22, 'test_2', 20.00, 30.00),
(145, 13, 22, 'presentation', 11.00, 15.00),
(146, 13, 22, 'mid_term', 35.00, 50.00),
(147, 14, 22, 'quiz_1', 5.50, 10.00),
(148, 14, 22, 'quiz_2', 6.00, 10.00),
(149, 14, 22, 'assignment_1', 12.00, 20.00),
(150, 14, 22, 'assignment_2', 13.50, 20.00),
(151, 14, 22, 'test_1', 16.00, 30.00),
(152, 14, 22, 'test_2', 19.00, 30.00),
(153, 14, 22, 'mid_term', 32.00, 50.00),
(154, 16, 22, 'quiz_1', 8.50, 10.00),
(155, 16, 22, 'quiz_2', 7.00, 10.00),
(156, 16, 22, 'assignment_1', 16.00, 20.00),
(157, 16, 22, 'test_1', 21.00, 30.00),
(158, 16, 22, 'mid_term', 40.00, 50.00),
(159, 13, 37, 'quiz_1', 7.00, 10.00),
(160, 13, 37, 'quiz_2', 8.00, 10.00),
(161, 13, 37, 'assignment_1', 16.00, 20.00),
(162, 13, 37, 'test_1', 21.00, 30.00),
(163, 13, 37, 'mid_term', 39.00, 50.00),
(164, 15, 37, 'quiz_1', 6.50, 10.00),
(165, 15, 37, 'quiz_2', 7.00, 10.00),
(166, 15, 37, 'assignment_1', 14.00, 20.00),
(167, 15, 37, 'test_1', 19.00, 30.00),
(168, 15, 37, 'mid_term', 34.00, 50.00),
(169, 16, 37, 'quiz_1', 9.00, 10.00),
(170, 16, 37, 'quiz_2', 8.50, 10.00),
(171, 16, 37, 'assignment_1', 18.00, 20.00),
(172, 16, 37, 'test_1', 25.00, 30.00),
(173, 16, 37, 'mid_term', 43.00, 50.00),
(174, 14, 47, 'quiz_1', 7.50, 10.00),
(175, 14, 47, 'quiz_2', 8.00, 10.00),
(176, 14, 47, 'assignment_1', 15.50, 20.00),
(177, 14, 47, 'test_1', 22.00, 30.00),
(178, 14, 47, 'mid_term', 41.00, 50.00),
(179, 15, 47, 'quiz_1', 8.00, 10.00),
(180, 15, 47, 'quiz_2', 9.00, 10.00),
(181, 15, 47, 'assignment_1', 17.00, 20.00),
(182, 15, 47, 'test_1', 24.00, 30.00),
(183, 15, 47, 'mid_term', 45.00, 50.00),
(184, 16, 47, 'quiz_1', 6.00, 10.00),
(185, 16, 47, 'quiz_2', 7.50, 10.00),
(186, 16, 47, 'assignment_1', 13.00, 20.00),
(187, 16, 47, 'test_1', 18.00, 30.00),
(188, 16, 47, 'mid_term', 36.00, 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `lms_mark_finalizations`
--

CREATE TABLE `lms_mark_finalizations` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `is_finalized` tinyint(1) DEFAULT 0,
  `finalized_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_mark_finalizations`
--

INSERT INTO `lms_mark_finalizations` (`id`, `course_id`, `student_user_id`, `is_finalized`, `finalized_at`) VALUES
(12, 13, 21, 1, '2026-06-20 05:00:00'),
(13, 14, 21, 1, '2026-06-20 05:00:00'),
(14, 13, 22, 0, NULL),
(15, 14, 22, 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lms_messages`
--

CREATE TABLE `lms_messages` (
  `message_id` int(11) NOT NULL,
  `sender_user_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_messages`
--

INSERT INTO `lms_messages` (`message_id`, `sender_user_id`, `recipient_user_id`, `subject`, `body`, `is_read`, `created_at`) VALUES
(1, 0, 0, 'Subject 1', 'Demo content for record 1', 0, '2026-07-15 05:30:00'),
(2, 0, 0, 'Subject 2', 'Demo content for record 2', 0, '2026-07-15 05:30:00'),
(3, 0, 0, 'Subject 3', 'Demo content for record 3', 0, '2026-07-15 05:30:00'),
(4, 0, 0, 'Subject 4', 'Demo content for record 4', 0, '2026-07-15 05:30:00'),
(5, 0, 0, 'Subject 5', 'Demo content for record 5', 0, '2026-07-15 05:30:00'),
(6, 0, 0, 'Subject 6', 'Demo content for record 6', 0, '2026-07-15 05:30:00'),
(7, 0, 0, 'Subject 7', 'Demo content for record 7', 0, '2026-07-15 05:30:00'),
(8, 0, 0, 'Subject 8', 'Demo content for record 8', 0, '2026-07-15 05:30:00'),
(9, 0, 0, 'Subject 9', 'Demo content for record 9', 0, '2026-07-15 05:30:00'),
(10, 0, 0, 'Subject 10', 'Demo content for record 10', 0, '2026-07-15 05:30:00'),
(14, 19, 21, 'Welcome to the Semester', 'Hi Ali, looking forward to having you in CS101 this semester. Don\'t forget to check the syllabus.', 1, '2026-07-28 08:34:48'),
(15, 19, 22, 'Assignment Reminder', 'Reminder: Assignment 2 for CS101 is due next Friday. Make sure to submit on time.', 0, '2026-07-28 08:34:48'),
(16, 19, 47, 'Mid-term Preparation', 'Wareesha, your mid-term results are looking good. Keep up the effort in CS301.', 1, '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_notifications`
--

CREATE TABLE `lms_notifications` (
  `notification_id` int(11) NOT NULL,
  `recipient_user_id` int(11) NOT NULL,
  `sender_user_id` int(11) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'notification',
  `title` varchar(255) DEFAULT NULL,
  `body` text DEFAULT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_notifications`
--

INSERT INTO `lms_notifications` (`notification_id`, `recipient_user_id`, `sender_user_id`, `category`, `title`, `body`, `link_url`, `is_read`, `created_at`) VALUES
(1, 26, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(2, 27, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(3, 28, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(4, 29, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(5, 30, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(6, 31, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(7, 32, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(8, 33, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(9, 34, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(10, 35, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(11, 36, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(12, 25, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19'),
(19, 21, 19, 'notification', 'New Announcement in CS101', 'Dr. Sara Khan posted a new announcement: Mid-term Schedule Announced', 'announcements.php?course_id=13', 1, '2026-07-28 08:34:48'),
(20, 21, 19, 'notification', 'Assignment Graded', 'Your Assignment 1 in CS101 has been graded. Score: 18/20', 'submissions.php', 1, '2026-07-28 08:34:48'),
(21, 22, 19, 'notification', 'Quiz Result Published', 'Quiz 1 result for CS101 is now available. You scored 6/10', 'marks.php', 0, '2026-07-28 08:34:48'),
(22, 22, 19, 'notification', 'Fee Reminder', 'You have an unpaid fee for CS201 (PKR 48,000). Please clear before the due date.', 'fees.php', 0, '2026-07-28 08:34:48'),
(23, 37, 19, 'notification', 'New Announcement in CS401', 'Project Guidelines have been posted for Database Systems', 'announcements.php?course_id=16', 0, '2026-07-28 08:34:48'),
(24, 47, 19, 'notification', 'Welcome to CS201', 'Welcome to Data Structures! Check the course materials section for the syllabus.', 'courses.php', 1, '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_queries`
--

CREATE TABLE `lms_queries` (
  `query_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `reply` text DEFAULT NULL,
  `status` enum('open','replied','closed') DEFAULT 'open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_queries`
--

INSERT INTO `lms_queries` (`query_id`, `student_user_id`, `course_id`, `subject`, `message`, `reply`, `status`, `created_at`) VALUES
(17, 21, 13, 'Assignment 1 Clarification', 'Is the report limited to 5 pages or can it be longer?', 'You may extend up to 8 pages. Focus on quality over length.', 'replied', '2026-07-28 08:34:48'),
(18, 22, 14, 'Linked List Doubt', 'Can we implement the linked list in Python or is C required?', 'Any language is acceptable. Mention your language choice at the top.', 'replied', '2026-07-28 08:34:48'),
(19, 37, 16, 'Project Topic Approval', 'I want to do a project on Hospital Management System. Is it okay?', NULL, 'open', '2026-07-28 08:34:48'),
(20, 47, 15, 'Scheduling Assignment', 'For the Round Robin assignment, what time quantum should we use?', 'Use time quantum = 4. You may also experiment with different values and compare.', 'replied', '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_quizzes`
--

CREATE TABLE `lms_quizzes` (
  `quiz_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `total_marks` decimal(5,2) DEFAULT 0.00,
  `duration_minutes` int(11) DEFAULT 30,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_quizzes`
--

INSERT INTO `lms_quizzes` (`quiz_id`, `course_id`, `title`, `total_marks`, `duration_minutes`, `created_at`) VALUES
(4, 17, 'Course 4', 100.00, 60, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 100.00, 60, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 100.00, 60, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 100.00, 60, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 100.00, 60, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 100.00, 60, '2026-07-15 05:30:00'),
(16, 13, 'Quiz 1: Computer Basics', 10.00, 15, '2026-07-28 08:34:48'),
(17, 13, 'Quiz 2: Number Systems', 10.00, 15, '2026-07-28 08:34:48'),
(18, 14, 'Quiz 1: Arrays & Pointers', 10.00, 20, '2026-07-28 08:34:48'),
(19, 15, 'Quiz 1: Process Concepts', 10.00, 15, '2026-07-28 08:34:48'),
(20, 16, 'Quiz 1: SQL Basics', 10.00, 20, '2026-07-28 08:34:48');

-- --------------------------------------------------------

--
-- Table structure for table `lms_quiz_results`
--

CREATE TABLE `lms_quiz_results` (
  `result_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT 0.00,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_quiz_results`
--

INSERT INTO `lms_quiz_results` (`result_id`, `quiz_id`, `student_user_id`, `marks_obtained`, `submitted_at`) VALUES
(24, 1, 21, 8.00, '2026-02-20 05:15:00'),
(25, 1, 22, 6.00, '2026-02-20 05:18:00'),
(26, 1, 37, 7.00, '2026-02-20 05:12:00'),
(27, 2, 21, 9.00, '2026-03-05 05:10:00'),
(28, 2, 22, 7.50, '2026-03-05 05:19:00'),
(29, 3, 21, 7.00, '2026-03-12 09:05:00'),
(30, 3, 22, 5.50, '2026-03-12 09:18:00'),
(31, 3, 47, 7.50, '2026-03-12 09:08:00'),
(32, 4, 21, 9.00, '2026-03-08 05:10:00'),
(33, 4, 37, 6.50, '2026-03-08 05:15:00'),
(34, 4, 47, 8.00, '2026-03-08 05:12:00'),
(35, 5, 22, 8.50, '2026-03-15 09:05:00'),
(36, 5, 37, 9.00, '2026-03-15 09:08:00');

-- --------------------------------------------------------

--
-- Table structure for table `lms_reports`
--

CREATE TABLE `lms_reports` (
  `report_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `report_type` varchar(50) DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`data`)),
  `generated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_reports`
--

INSERT INTO `lms_reports` (`report_id`, `course_id`, `report_type`, `data`, `generated_by`, `created_at`) VALUES
(1, 14, 'general', '{}', 2, '2026-07-15 05:30:00'),
(2, 15, 'general', '{}', 1, '2026-07-15 05:30:00'),
(3, 16, 'general', '{}', 2, '2026-07-15 05:30:00'),
(4, 17, 'general', '{}', 1, '2026-07-15 05:30:00'),
(5, 18, 'general', '{}', 2, '2026-07-15 05:30:00'),
(6, 14, 'general', '{}', 2, '2026-07-15 05:30:00'),
(7, 15, 'general', '{}', 1, '2026-07-15 05:30:00'),
(8, 16, 'general', '{}', 2, '2026-07-15 05:30:00'),
(9, 17, 'general', '{}', 1, '2026-07-15 05:30:00'),
(10, 18, 'general', '{}', 2, '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `lms_settings`
--

CREATE TABLE `lms_settings` (
  `setting_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `setting_key` varchar(100) DEFAULT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_settings`
--

INSERT INTO `lms_settings` (`setting_id`, `user_id`, `setting_key`, `setting_value`) VALUES
(1, 2, 'setting_key_1', 'value_1'),
(2, 3, 'setting_key_2', 'value_2'),
(3, 4, 'setting_key_3', 'value_3'),
(4, 5, 'setting_key_4', 'value_4'),
(5, 6, 'setting_key_5', 'value_5');

-- --------------------------------------------------------

--
-- Table structure for table `lms_student_answers`
--

CREATE TABLE `lms_student_answers` (
  `answer_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `question_text` text DEFAULT NULL,
  `answer_text` text DEFAULT NULL,
  `marks` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_student_answers`
--

INSERT INTO `lms_student_answers` (`answer_id`, `quiz_id`, `student_user_id`, `question_text`, `answer_text`, `marks`) VALUES
(1, 1, 2, 'Question 1 text', 'Answer 1', 0.00),
(2, 2, 3, 'Question 2 text', 'Answer 2', 0.00),
(3, 3, 4, 'Question 3 text', 'Answer 3', 0.00),
(4, 4, 5, 'Question 4 text', 'Answer 4', 0.00),
(5, 5, 6, 'Question 5 text', 'Answer 5', 0.00),
(6, 6, 7, 'Question 6 text', 'Answer 6', 0.00),
(7, 7, 8, 'Question 7 text', 'Answer 7', 0.00),
(8, 8, 9, 'Question 8 text', 'Answer 8', 0.00),
(9, 9, 10, 'Question 9 text', 'Answer 9', 0.00),
(10, 10, 11, 'Question 10 text', 'Answer 10', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `lms_submissions`
--

CREATE TABLE `lms_submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `marks` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_submissions`
--

INSERT INTO `lms_submissions` (`submission_id`, `assignment_id`, `student_user_id`, `file_path`, `submitted_at`, `marks`, `feedback`) VALUES
(11, 1, 21, 'uploads/ali_a1.pdf', '2026-03-14 17:10:00', 18.00, 'Good work, minor formatting issues.'),
(12, 1, 22, 'uploads/student_a1.pdf', '2026-03-15 18:30:00', 15.00, 'Needs more detail in section 2.'),
(13, 1, 37, 'uploads/ali556_a1.pdf', '2026-03-13 13:00:00', 17.50, 'Well structured report.'),
(14, 3, 21, 'uploads/ali_a1_ds.pdf', '2026-03-19 16:00:00', 19.00, 'Excellent implementation.'),
(15, 3, 22, 'uploads/student_a1_ds.pdf', '2026-03-20 17:45:00', 14.00, 'Code needs optimization.'),
(16, 4, 47, 'uploads/wareesha_a1_ds.pdf', '2026-03-17 15:00:00', 16.00, 'Good attempt.'),
(17, 1, 21, 'uploads/ali_a1.pdf', '2026-03-14 17:10:00', 18.00, 'Good work, minor formatting issues.'),
(18, 1, 22, 'uploads/student_a1.pdf', '2026-03-15 18:30:00', 15.00, 'Needs more detail in section 2.'),
(19, 1, 37, 'uploads/ali556_a1.pdf', '2026-03-13 13:00:00', 17.50, 'Well structured report.'),
(20, 3, 21, 'uploads/ali_a1_ds.pdf', '2026-03-19 16:00:00', 19.00, 'Excellent implementation.'),
(21, 3, 22, 'uploads/student_a1_ds.pdf', '2026-03-20 17:45:00', 14.00, 'Code needs optimization.'),
(22, 4, 47, 'uploads/wareesha_a1_ds.pdf', '2026-03-17 15:00:00', 16.00, 'Good attempt.');

-- --------------------------------------------------------

--
-- Table structure for table `lms_timetable`
--

CREATE TABLE `lms_timetable` (
  `timetable_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_timetable`
--

INSERT INTO `lms_timetable` (`timetable_id`, `course_id`, `day_of_week`, `start_time`, `end_time`, `room`) VALUES
(1, 14, 'Monday', '09:00:00', '09:00:00', 'Room 101'),
(2, 15, 'Monday', '09:00:00', '09:00:00', 'Room 102'),
(3, 16, 'Monday', '09:00:00', '09:00:00', 'Room 103'),
(4, 17, 'Monday', '09:00:00', '09:00:00', 'Room 104'),
(5, 18, 'Monday', '09:00:00', '09:00:00', 'Room 105'),
(6, 19, 'Monday', '09:00:00', '09:00:00', 'Room 106'),
(7, 20, 'Monday', '09:00:00', '09:00:00', 'Room 107'),
(8, 21, 'Monday', '09:00:00', '09:00:00', 'Room 108'),
(9, 22, 'Monday', '09:00:00', '09:00:00', 'Room 109'),
(10, 13, 'Monday', '09:00:00', '09:00:00', 'Room 110'),
(11, 14, 'Monday', '09:00:00', '09:00:00', 'Room 111'),
(12, 15, 'Monday', '09:00:00', '09:00:00', 'Room 112'),
(13, 16, 'Monday', '09:00:00', '09:00:00', 'Room 113'),
(14, 17, 'Monday', '09:00:00', '09:00:00', 'Room 114'),
(15, 18, 'Monday', '09:00:00', '09:00:00', 'Room 115'),
(16, 19, 'Monday', '09:00:00', '09:00:00', 'Room 116'),
(17, 20, 'Monday', '09:00:00', '09:00:00', 'Room 117'),
(18, 21, 'Monday', '09:00:00', '09:00:00', 'Room 118'),
(19, 22, 'Monday', '09:00:00', '09:00:00', 'Room 119'),
(20, 13, 'Monday', '09:00:00', '09:00:00', 'Room 120');

-- --------------------------------------------------------

--
-- Table structure for table `lms_transcripts`
--

CREATE TABLE `lms_transcripts` (
  `transcript_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `semester` varchar(40) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lms_transcripts`
--

INSERT INTO `lms_transcripts` (`transcript_id`, `student_user_id`, `course_id`, `grade`, `gpa`, `semester`) VALUES
(4, 2, 14, 'B', 2.10, '2'),
(5, 3, 15, 'C', 2.20, '3'),
(6, 4, 16, 'D', 2.30, '4'),
(7, 5, 17, 'F', 2.40, '5'),
(8, 6, 18, 'A', 2.50, '6'),
(9, 7, 19, 'B', 2.60, '7'),
(10, 8, 20, 'C', 2.70, '8'),
(11, 9, 21, 'D', 2.80, '1'),
(12, 10, 22, 'F', 2.90, '2'),
(13, 11, 13, 'A', 3.00, '3'),
(14, 2, 14, 'B', 2.10, '2'),
(15, 3, 15, 'C', 2.20, '3'),
(16, 4, 16, 'D', 2.30, '4'),
(17, 5, 17, 'F', 2.40, '5'),
(18, 6, 18, 'A', 2.50, '6'),
(19, 7, 19, 'B', 2.60, '7'),
(20, 8, 20, 'C', 2.70, '8'),
(21, 9, 21, 'D', 2.80, '1'),
(22, 10, 22, 'F', 2.90, '2'),
(23, 11, 13, 'A', 3.00, '3');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `amount_paid` decimal(12,2) NOT NULL,
  `payment_method` enum('Cash','Bank','Card','Online') NOT NULL DEFAULT 'Cash',
  `transaction_ref` varchar(100) DEFAULT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `received_by` int(11) NOT NULL,
  `status` enum('Success','Reversed') NOT NULL DEFAULT 'Success'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `student_fee_id`, `student_id`, `amount_paid`, `payment_method`, `transaction_ref`, `payment_date`, `received_by`, `status`) VALUES
(8, 11, 35, 21666.67, 'Bank', 'TRN-00008', '2026-08-10 00:00:00', 10, 'Success'),
(9, 13, 25, 25000.00, 'Bank', 'TRN-00009', '2026-08-10 00:00:00', 10, 'Success'),
(10, 14, 31, 20.00, 'Cash', 'TXN516', '2026-07-28 01:06:11', 9, 'Success'),
(11, 13, 25, 50000.00, 'Cash', 'TX6732', '2026-07-28 01:09:14', 9, 'Success'),
(12, 15, 25, 50.00, 'Cash', 'TXN564', '2026-07-28 10:55:32', 9, 'Success');

--
-- Triggers `payments`
--
DELIMITER $$
CREATE TRIGGER `trg_after_payment_insert` AFTER INSERT ON `payments` FOR EACH ROW BEGIN
    IF NEW.status = 'Success' THEN
        UPDATE student_fee
        SET paid_amount = paid_amount + NEW.amount_paid
        WHERE student_fee_id = NEW.student_fee_id;

        UPDATE student_fee
        SET status = CASE
                        WHEN paid_amount >= total_amount THEN 'Paid'
                        WHEN paid_amount > 0 THEN 'Partially Paid'
                        ELSE 'Unpaid'
                     END
        WHERE student_fee_id = NEW.student_fee_id;

        INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details)
        VALUES ('Finance', 'Payment Received', 'payments', NEW.payment_id, NEW.received_by,
                CONCAT('Amount: ', NEW.amount_paid));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_reversals`
--

CREATE TABLE `payment_reversals` (
  `reversal_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `reversed_amount` decimal(12,2) NOT NULL,
  `reason` varchar(255) NOT NULL,
  `reversed_by` int(11) NOT NULL,
  `reversed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `payment_reversals`
--
DELIMITER $$
CREATE TRIGGER `trg_after_reversal_insert` AFTER INSERT ON `payment_reversals` FOR EACH ROW BEGIN
    DECLARE v_student_fee_id INT;

    SELECT student_fee_id INTO v_student_fee_id FROM payments WHERE payment_id = NEW.payment_id;

    UPDATE payments SET status = 'Reversed' WHERE payment_id = NEW.payment_id;

    UPDATE student_fee
    SET paid_amount = GREATEST(paid_amount - NEW.reversed_amount, 0)
    WHERE student_fee_id = v_student_fee_id;

    UPDATE student_fee
    SET status = CASE
                    WHEN paid_amount >= total_amount THEN 'Paid'
                    WHEN paid_amount > 0 THEN 'Partially Paid'
                    ELSE 'Unpaid'
                 END
    WHERE student_fee_id = v_student_fee_id;

    INSERT INTO activity_logs (module, action, reference_table, reference_id, performed_by, details)
    VALUES ('Finance', 'Payment Reversed', 'payment_reversals', NEW.reversal_id, NEW.reversed_by, NEW.reason);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `duration_years` tinyint(4) NOT NULL DEFAULT 4,
  `total_semesters` tinyint(4) NOT NULL DEFAULT 8,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_name`, `program_code`, `department_id`, `duration_years`, `total_semesters`, `status`, `created_at`) VALUES
(1, 'Default Program', '', 1, 4, 8, 'Active', '2026-07-26 06:47:52'),
(2, 'BS Computer Science', 'BSCS', 2, 4, 8, 'Active', '2026-07-26 07:10:32'),
(3, 'BS Information Technology', 'BSIT', 3, 4, 8, 'Active', '2026-07-26 07:10:32'),
(4, 'BS Software Engineering', 'BSSE', 4, 4, 8, 'Active', '2026-07-26 07:10:32'),
(5, 'BS Artificial Intelligence', 'BSAI', 5, 4, 8, 'Active', '2026-07-26 07:10:32'),
(6, 'BS Data Science', 'BSDS', 6, 4, 8, 'Active', '2026-07-26 07:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `question_bank`
--

CREATE TABLE `question_bank` (
  `question_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `difficulty_level` enum('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `question_bank`
--

INSERT INTO `question_bank` (`question_id`, `exam_id`, `course_id`, `teacher_id`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `marks`, `difficulty_level`, `status`, `created_at`) VALUES
(1, 1, 14, 2, 'Question 1 text', 'Option A1', 'Option B1', 'Option C1', 'Option D1', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(2, 2, 15, 1, 'Question 2 text', 'Option A2', 'Option B2', 'Option C2', 'Option D2', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(3, 3, 16, 2, 'Question 3 text', 'Option A3', 'Option B3', 'Option C3', 'Option D3', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(4, 4, 17, 1, 'Question 4 text', 'Option A4', 'Option B4', 'Option C4', 'Option D4', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(5, 5, 18, 2, 'Question 5 text', 'Option A5', 'Option B5', 'Option C5', 'Option D5', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(6, 1, 14, 2, 'Question 1 text', 'Option A1', 'Option B1', 'Option C1', 'Option D1', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(7, 2, 15, 1, 'Question 2 text', 'Option A2', 'Option B2', 'Option C2', 'Option D2', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(8, 3, 16, 2, 'Question 3 text', 'Option A3', 'Option B3', 'Option C3', 'Option D3', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(9, 4, 17, 1, 'Question 4 text', 'Option A4', 'Option B4', 'Option C4', 'Option D4', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00'),
(10, 5, 18, 2, 'Question 5 text', 'Option A5', 'Option B5', 'Option C5', 'Option D5', 'A', 0.00, 'Easy', 'Active', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `question_papers`
--

CREATE TABLE `question_papers` (
  `paper_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `paper_file` varchar(255) NOT NULL,
  `status` enum('Pending','Published') NOT NULL DEFAULT 'Pending',
  `publish_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `question_papers`
--

INSERT INTO `question_papers` (`paper_id`, `exam_id`, `course_id`, `teacher_id`, `paper_file`, `status`, `publish_date`, `created_at`) VALUES
(1, 1, 14, 2, '/papers/paper1.pdf', '', '2026-09-02', '2026-07-15 05:30:00'),
(2, 2, 15, 1, '/papers/paper2.pdf', '', '2026-09-03', '2026-07-15 05:30:00'),
(3, 3, 16, 2, '/papers/paper3.pdf', '', '2026-09-04', '2026-07-15 05:30:00'),
(4, 4, 17, 1, '/papers/paper4.pdf', '', '2026-09-05', '2026-07-15 05:30:00'),
(5, 5, 18, 2, '/papers/paper5.pdf', '', '2026-09-06', '2026-07-15 05:30:00'),
(6, 1, 14, 2, '/papers/paper1.pdf', '', '2026-09-02', '2026-07-15 05:30:00'),
(7, 2, 15, 1, '/papers/paper2.pdf', '', '2026-09-03', '2026-07-15 05:30:00'),
(8, 3, 16, 2, '/papers/paper3.pdf', '', '2026-09-04', '2026-07-15 05:30:00'),
(9, 4, 17, 1, '/papers/paper4.pdf', '', '2026-09-05', '2026-07-15 05:30:00'),
(10, 5, 18, 2, '/papers/paper5.pdf', '', '2026-09-06', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `receipts`
--

CREATE TABLE `receipts` (
  `receipt_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `receipt_no` varchar(30) NOT NULL,
  `issued_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `issued_by` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
(1, 'Admin'),
(5, 'Admission Officer'),
(6, 'Examiner'),
(3, 'Finance Officer'),
(4, 'Student'),
(7, 'Super Admin'),
(2, 'Teacher');

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE `rooms` (
  `id` int(11) NOT NULL,
  `room_no` varchar(30) NOT NULL,
  `room_type` enum('Classroom','Laboratory','Hall','Seminar') DEFAULT 'Classroom',
  `building` varchar(100) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `rooms`
--

INSERT INTO `rooms` (`id`, `room_no`, `room_type`, `building`, `capacity`, `status`, `created_at`) VALUES
(1, 'Room 101', 'Classroom', 'Main Block', 40, 'Active', '2026-07-31 20:54:34'),
(2, 'Room 102', 'Classroom', 'Main Block', 40, 'Active', '2026-07-31 20:54:34'),
(3, 'Room 103', 'Classroom', 'Main Block', 40, 'Active', '2026-07-31 20:54:34'),
(4, 'Room 104', 'Classroom', 'Main Block', 40, 'Active', '2026-07-31 20:54:34'),
(5, 'Room 105', 'Classroom', 'Main Block', 40, 'Active', '2026-07-31 20:54:34'),
(6, 'Lab-01', 'Laboratory', 'CS Block', 25, 'Active', '2026-07-31 20:54:34'),
(7, 'Lab-02', 'Laboratory', 'CS Block', 25, 'Active', '2026-07-31 20:54:34'),
(8, 'Lab-03', 'Laboratory', 'CS Block', 25, 'Active', '2026-07-31 20:54:34'),
(9, 'Lab-04', 'Laboratory', 'Engineering Block', 25, 'Active', '2026-07-31 20:54:34'),
(10, 'Hall A', 'Hall', 'Main Block', 120, 'Active', '2026-07-31 20:54:34');

-- --------------------------------------------------------

--
-- Table structure for table `room_allocations`
--

CREATE TABLE `room_allocations` (
  `id` int(11) NOT NULL,
  `room_id` int(11) NOT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `allocated_by` int(11) DEFAULT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `room_allocations`
--

INSERT INTO `room_allocations` (`id`, `room_id`, `entry_id`, `day_of_week`, `start_time`, `end_time`, `allocated_by`, `allocated_at`) VALUES
(7, 2, 3, 'Monday', '09:00:00', '10:00:00', 1, '2026-07-31 21:13:14'),
(8, 3, 4, 'Monday', '10:00:00', '11:00:00', 1, '2026-07-31 21:13:14'),
(9, 1, 2, 'Wednesday', '14:00:00', '15:00:00', 10, '2026-07-31 21:13:26'),
(10, 4, 5, 'Tuesday', '08:00:00', '09:00:00', 10, '2026-07-31 21:14:09'),
(11, 5, 6, 'Tuesday', '09:00:00', '10:00:00', 10, '2026-07-31 21:14:09'),
(12, 6, 7, 'Tuesday', '10:00:00', '11:00:00', 10, '2026-07-31 21:14:09');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_auth_users`
--

CREATE TABLE `sbe_auth_users` (
  `auth_id` int(11) NOT NULL,
  `role` enum('Teacher','Student') NOT NULL,
  `login_id` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `display_name` varchar(150) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `student_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sbe_auth_users`
--

INSERT INTO `sbe_auth_users` (`auth_id`, `role`, `login_id`, `password_hash`, `display_name`, `teacher_id`, `student_id`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Teacher', '5001', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Dr. Sara Khan', 1, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-28 07:30:12'),
(2, 'Teacher', '5002', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Teacher Demo', 2, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-28 07:30:12'),
(3, 'Student', '9001', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Ali Raza', NULL, 25, 'Active', '2026-07-26 18:28:03', '2026-07-28 07:30:12'),
(4, 'Student', '9002', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Student Demo', NULL, 26, 'Active', '2026-07-26 18:28:03', '2026-07-28 07:30:12'),
(5, 'Student', '9003', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Usman Khan', NULL, 27, 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(6, 'Student', '9004', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Hira Ahmed', NULL, 28, 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(7, 'Student', '9005', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Bilal Hussain', NULL, 29, 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(8, 'Teacher', '5003', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Dr. Sara Khan', 1, NULL, 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(9, 'Teacher', '5004', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Teacher Demo', 2, NULL, 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_exams`
--

CREATE TABLE `sbe_exams` (
  `exam_id` int(11) NOT NULL,
  `exam_code` varchar(30) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `exam_type` enum('Quiz','Mid','Final','Practice','Assignment Test') NOT NULL,
  `instructions` text DEFAULT NULL,
  `duration_minutes` smallint(6) NOT NULL,
  `total_questions` smallint(6) NOT NULL,
  `total_marks` decimal(6,2) NOT NULL,
  `passing_marks` decimal(6,2) NOT NULL,
  `selection_mode` enum('Manual','Random') NOT NULL DEFAULT 'Manual',
  `negative_marking` decimal(4,2) NOT NULL DEFAULT 0.00,
  `shuffle_questions` tinyint(1) NOT NULL DEFAULT 0,
  `shuffle_options` tinyint(1) NOT NULL DEFAULT 0,
  `allow_review` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('Draft','Published','Closed','Archived') NOT NULL DEFAULT 'Draft',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sbe_exams`
--

INSERT INTO `sbe_exams` (`exam_id`, `exam_code`, `course_id`, `teacher_id`, `title`, `exam_type`, `instructions`, `duration_minutes`, `total_questions`, `total_marks`, `passing_marks`, `selection_mode`, `negative_marking`, `shuffle_questions`, `shuffle_options`, `allow_review`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SBE001', 13, 1, 'SBE Midterm', 'Mid', 'Follow all instructions', 60, 10, 50.00, 25.00, '', 0.00, 0, 0, 1, 'Published', '2026-07-15 05:00:00', '2026-07-28 07:30:12'),
(2, 'SBE002', 15, 1, 'SBE Final', 'Final', 'Answer all questions. Each question carries 5 marks. No negative marking.', 90, 10, 50.00, 25.00, '', 0.00, 0, 0, 1, 'Published', '2026-07-15 05:00:00', '2026-07-28 07:30:12'),
(4, 'EXAM-QUI-01', 15, 1, 'quiz', 'Quiz', '', 10, 3, 20.00, 10.00, 'Manual', 0.00, 0, 0, 1, 'Published', '2026-07-28 07:14:03', '2026-07-28 07:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_exam_questions`
--

CREATE TABLE `sbe_exam_questions` (
  `exam_question_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question_order` smallint(6) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbe_exam_questions`
--

INSERT INTO `sbe_exam_questions` (`exam_question_id`, `exam_id`, `question_id`, `question_order`, `created_at`) VALUES
(1, 1, 1, 1, '2026-07-28 07:30:12'),
(2, 1, 2, 2, '2026-07-28 07:30:12'),
(3, 1, 10, 3, '2026-07-28 07:30:12'),
(4, 1, 11, 4, '2026-07-28 07:30:12'),
(5, 1, 12, 5, '2026-07-28 07:30:12'),
(6, 1, 13, 6, '2026-07-28 07:30:12'),
(7, 1, 14, 7, '2026-07-28 07:30:12'),
(8, 1, 15, 8, '2026-07-28 07:30:12'),
(9, 1, 16, 9, '2026-07-28 07:30:12'),
(10, 1, 17, 10, '2026-07-28 07:30:12'),
(11, 2, 4, 1, '2026-07-28 07:30:12'),
(12, 2, 20, 2, '2026-07-28 07:30:12'),
(13, 2, 21, 3, '2026-07-28 07:30:12'),
(14, 2, 22, 4, '2026-07-28 07:30:12'),
(15, 2, 23, 5, '2026-07-28 07:30:12'),
(16, 2, 24, 6, '2026-07-28 07:30:12'),
(17, 2, 25, 7, '2026-07-28 07:30:12'),
(18, 2, 26, 8, '2026-07-28 07:30:12'),
(19, 2, 27, 9, '2026-07-28 07:30:12'),
(20, 2, 28, 10, '2026-07-28 07:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_exam_results`
--

CREATE TABLE `sbe_exam_results` (
  `exam_result_id` int(11) NOT NULL,
  `student_exam_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `obtained_marks` decimal(6,2) NOT NULL,
  `total_marks` decimal(6,2) NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `pass_fail_status` enum('Pass','Fail') NOT NULL,
  `rank_position` int(11) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('Draft','Published','Archived') NOT NULL DEFAULT 'Draft',
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sbe_exam_results`
--

INSERT INTO `sbe_exam_results` (`exam_result_id`, `student_exam_id`, `exam_id`, `student_id`, `obtained_marks`, `total_marks`, `percentage`, `pass_fail_status`, `rank_position`, `remarks`, `status`, `published_at`, `created_at`) VALUES
(1, 1, 1, 25, 40.00, 50.00, 80.00, 'Pass', 2, 'Good performance', 'Published', '2026-09-16 10:00:00', '2026-07-28 07:30:12'),
(2, 2, 1, 26, 35.00, 50.00, 70.00, 'Pass', 3, 'Satisfactory', 'Published', '2026-09-16 10:00:00', '2026-07-28 07:30:12'),
(3, 3, 1, 27, 30.00, 50.00, 60.00, 'Pass', 4, 'Needs improvement', 'Published', '2026-09-16 10:00:00', '2026-07-28 07:30:12'),
(4, 4, 1, 28, 20.00, 50.00, 40.00, 'Fail', 5, 'Below passing - must retake', 'Published', '2026-09-16 10:00:00', '2026-07-28 07:30:12'),
(5, 5, 1, 29, 45.00, 50.00, 90.00, 'Pass', 1, 'Excellent - top scorer', 'Published', '2026-09-16 10:00:00', '2026-07-28 07:30:12'),
(18, 12, 2, 25, 25.00, 50.00, 50.00, 'Pass', NULL, 'Auto-graded MCQ result submitted from student exam room.', 'Published', '2026-07-28 12:34:51', '2026-07-28 07:34:51'),
(19, 13, 2, 26, 15.00, 50.00, 30.00, 'Fail', NULL, 'Auto-graded MCQ result submitted from student exam room.', 'Published', '2026-07-28 13:08:43', '2026-07-28 08:08:43');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_exam_schedule`
--

CREATE TABLE `sbe_exam_schedule` (
  `schedule_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `section` varchar(5) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `exam_date` date NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `late_submission_grace_minutes` smallint(6) NOT NULL DEFAULT 0,
  `location` varchar(100) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL,
  `status` enum('Scheduled','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Scheduled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sbe_exam_schedule`
--

INSERT INTO `sbe_exam_schedule` (`schedule_id`, `exam_id`, `section`, `semester_id`, `exam_date`, `start_time`, `end_time`, `late_submission_grace_minutes`, `location`, `remarks`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'A', 9, '2026-09-15', '09:00:00', '11:00:00', 15, 'Block A Room 101', '', 'Ongoing', '2026-07-26 07:00:00', '2026-07-28 07:10:20'),
(2, 2, 'A', 10, '2026-12-20', '09:00:00', '12:00:00', 15, 'Block A Room 101', '', 'Ongoing', '2026-07-26 07:00:00', '2026-07-28 07:10:14'),
(3, 4, 'b', 37, '2026-07-28', '09:00:00', '09:10:00', 0, 'lab 1', '', 'Ongoing', '2026-07-28 07:17:21', '2026-07-28 07:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_question_bank`
--

CREATE TABLE `sbe_question_bank` (
  `question_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `topic` varchar(150) DEFAULT NULL,
  `question_text` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_option` enum('A','B','C','D') NOT NULL,
  `explanation` varchar(500) DEFAULT NULL,
  `marks` decimal(5,2) NOT NULL DEFAULT 1.00,
  `difficulty_level` enum('Easy','Medium','Hard') NOT NULL DEFAULT 'Medium',
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbe_question_bank`
--

INSERT INTO `sbe_question_bank` (`question_id`, `course_id`, `teacher_id`, `topic`, `question_text`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_option`, `explanation`, `marks`, `difficulty_level`, `status`, `created_at`, `updated_at`) VALUES
(1, 13, 1, 'OOP Basics', 'What is encapsulation in Java?', 'Bundling data and methods', 'Inheritance', 'Polymorphism', 'Abstraction', 'A', 'Encapsulation bundles data with methods that operate on that data.', 5.00, 'Easy', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(2, 13, 1, 'OOP Basics', 'What is a constructor?', 'A method to destroy objects', 'A special method to initialize objects', 'A type of loop', 'A data structure', 'B', 'A constructor is a special method called when an object is created.', 5.00, 'Easy', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(3, 14, 1, 'Data Structures', 'What is a stack?', 'FIFO structure', 'LIFO structure', 'Random access', 'Sequential only', 'B', 'A stack follows Last In First Out (LIFO) principle.', 5.00, 'Medium', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(4, 15, 2, 'Algorithms', 'What is binary search?', 'Linear scan', 'Divide and conquer', 'Brute force', 'Dynamic programming', 'B', 'Binary search divides the search space in half each iteration.', 5.00, 'Medium', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(5, 16, 2, 'Networking', 'What does TCP stand for?', 'Transfer Control Protocol', 'Transmission Control Protocol', 'Telecommunications Control Protocol', 'Terminal Control Protocol', 'B', 'TCP stands for Transmission Control Protocol.', 5.00, 'Easy', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(7, 15, 1, 'os', 'what is os', 'a', 'b', 'c', 'd', 'A', '', 1.00, 'Medium', 'Active', '2026-07-28 07:16:19', '2026-07-28 07:16:19'),
(8, 15, 1, 'os', 'hello', 'b', 'a', 'd', 'c', 'A', '', 1.00, 'Medium', 'Active', '2026-07-28 07:16:40', '2026-07-28 07:16:40'),
(9, 15, 1, 'os', 'meaw', 'aa', 'bb', 'cc', 'dd', 'A', '', 1.00, 'Medium', 'Active', '2026-07-28 07:16:53', '2026-07-28 07:16:53'),
(10, 13, 1, 'Computer Basics', 'What does CPU stand for?', 'Central Processing Unit', 'Central Program Utility', 'Computer Processing Unit', 'Central Peripheral Unit', 'A', 'CPU stands for Central Processing Unit - the brain of the computer.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(11, 13, 1, 'Computer Basics', 'Which of the following is an input device?', 'Monitor', 'Printer', 'Keyboard', 'Speaker', 'C', 'Keyboard is an input device used to type data into the computer.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(12, 13, 1, 'Number Systems', 'What is the decimal value of binary 1010?', '8', '10', '12', '14', 'B', 'Binary 1010 = 1*8 + 0*4 + 1*2 + 0*1 = 10 in decimal.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(13, 13, 1, 'Number Systems', 'How many bits are in a byte?', '4', '6', '8', '16', 'C', 'A byte consists of 8 bits.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(14, 13, 1, 'Software', 'Which of the following is an operating system?', 'MS Word', 'Linux', 'Photoshop', 'Excel', 'B', 'Linux is an open-source operating system. The others are application software.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(15, 13, 1, 'Software', 'What does GUI stand for?', 'General User Interface', 'Graphical User Interface', 'Global User Interaction', 'Graphical Utility Integration', 'B', 'GUI stands for Graphical User Interface - the visual way to interact with computers.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(16, 13, 1, 'Memory', 'Which memory is volatile?', 'ROM', 'RAM', 'Hard Disk', 'SSD', 'B', 'RAM (Random Access Memory) is volatile - data is lost when power is turned off.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(17, 13, 1, 'Memory', 'What is the full form of ROM?', 'Random Operating Memory', 'Read Only Memory', 'Run Only Memory', 'Read Open Memory', 'B', 'ROM stands for Read Only Memory - data is permanently stored and cannot be easily modified.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(18, 13, 1, 'Programming', 'Which language is known as the mother of all languages?', 'Java', 'Python', 'C', 'Assembly', 'C', 'C language is often called the mother of all programming languages as many modern languages derive from it.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(19, 13, 1, 'Programming', 'What does SQL stand for?', 'Structured Query Language', 'Simple Query Language', 'Standard Question Language', 'System Query Logic', 'A', 'SQL stands for Structured Query Language, used for managing relational databases.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(20, 15, 1, 'OS Concepts', 'What is a process?', 'A program in execution', 'A file on disk', 'A user account', 'A network connection', 'A', 'A process is a program in execution, with its own memory space and resources.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(21, 15, 1, 'OS Concepts', 'Which scheduling algorithm gives minimum average waiting time?', 'FCFS', 'SJF', 'Round Robin', 'Priority', 'B', 'Shortest Job First (SJF) gives the minimum average waiting time among all algorithms.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(22, 15, 1, 'Memory Management', 'What is virtual memory?', 'Physical RAM only', 'Technique using disk as extended RAM', 'Cache memory', 'ROM storage', 'B', 'Virtual memory uses disk space as an extension of physical RAM to run large programs.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(23, 15, 1, 'Memory Management', 'What is a page fault?', 'Hardware failure', 'Page not in physical memory', 'Printer error', 'Network timeout', 'B', 'A page fault occurs when a program accesses a page not currently in physical memory.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(24, 15, 1, 'Concurrency', 'What is a deadlock?', 'Fast processing', 'Processes waiting indefinitely for resources', 'CPU overflow', 'Memory leak', 'B', 'Deadlock occurs when two or more processes are blocked forever, each waiting for the other.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(25, 15, 1, 'Concurrency', 'Which condition is NOT required for deadlock?', 'Mutual Exclusion', 'Hold and Wait', 'Preemption', 'Circular Wait', 'C', 'Preemption is a deadlock prevention strategy, not a necessary condition for deadlock.', 5.00, 'Hard', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(26, 15, 1, 'File Systems', 'What is RAID?', 'Random Access Internal Device', 'Redundant Array of Independent Disks', 'Remote Application Interface Design', 'Rapid Data Integration', 'B', 'RAID combines multiple disk drives for better performance, redundancy, or both.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(27, 15, 1, 'File Systems', 'Which file allocation method has external fragmentation?', 'Contiguous', 'Linked', 'Indexed', 'All of them', 'A', 'Contiguous allocation suffers from external fragmentation as files are deleted.', 5.00, 'Hard', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(28, 15, 1, 'OS Concepts', 'What is a system call?', 'User login', 'Interface between process and OS kernel', 'CPU instruction', 'File deletion command', 'B', 'A system call is the programmatic way for a process to request services from the OS kernel.', 5.00, 'Medium', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12'),
(29, 15, 1, 'OS Concepts', 'Which is NOT an OS function?', 'Memory management', 'Process scheduling', 'Typing documents', 'File management', 'C', 'Typing documents is an application function, not an OS function.', 5.00, 'Easy', 'Active', '2026-07-28 07:30:12', '2026-07-28 07:30:12');

-- --------------------------------------------------------

--
-- Table structure for table `sbe_student_answers`
--

CREATE TABLE `sbe_student_answers` (
  `student_answer_id` int(11) NOT NULL,
  `student_exam_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `question_order` smallint(6) NOT NULL,
  `question_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`question_snapshot`)),
  `selected_option` enum('A','B','C','D') DEFAULT NULL,
  `answered_at` datetime DEFAULT NULL,
  `is_correct` tinyint(1) DEFAULT NULL,
  `marks_awarded` decimal(5,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbe_student_answers`
--

INSERT INTO `sbe_student_answers` (`student_answer_id`, `student_exam_id`, `question_id`, `question_order`, `question_snapshot`, `selected_option`, `answered_at`, `is_correct`, `marks_awarded`) VALUES
(1, 1, 1, 1, '{\"question_id\":1,\"question_text\":\"What does CPU stand for?\",\"option_a\":\"Central Processing Unit\",\"option_b\":\"Central Program Utility\",\"option_c\":\"Computer Processing Unit\",\"option_d\":\"Central Peripheral Unit\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:10:00', 1, 5.00),
(2, 1, 2, 2, '{\"question_id\":2,\"question_text\":\"Which is an input device?\",\"option_a\":\"Monitor\",\"option_b\":\"Printer\",\"option_c\":\"Keyboard\",\"option_d\":\"Speaker\",\"correct_option\":\"C\",\"marks\":5.00}', 'B', '2026-09-15 09:15:00', 0, 0.00),
(3, 1, 10, 3, '{\"question_id\":10,\"question_text\":\"Decimal value of binary 1010?\",\"option_a\":\"8\",\"option_b\":\"10\",\"option_c\":\"12\",\"option_d\":\"14\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:20:00', 1, 5.00),
(4, 1, 11, 4, '{\"question_id\":11,\"question_text\":\"Bits in a byte?\",\"option_a\":\"4\",\"option_b\":\"6\",\"option_c\":\"8\",\"option_d\":\"16\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:25:00', 1, 5.00),
(5, 1, 12, 5, '{\"question_id\":12,\"question_text\":\"Which is an operating system?\",\"option_a\":\"MS Word\",\"option_b\":\"Linux\",\"option_c\":\"Photoshop\",\"option_d\":\"Excel\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:28:00', 1, 5.00),
(6, 1, 13, 6, '{\"question_id\":13,\"question_text\":\"Full form of GUI?\",\"option_a\":\"General User Interface\",\"option_b\":\"Graphical User Interface\",\"option_c\":\"Global User Interaction\",\"option_d\":\"Graphical Utility Integration\",\"correct_option\":\"B\",\"marks\":5.00}', 'A', '2026-09-15 09:32:00', 0, 0.00),
(7, 1, 14, 7, '{\"question_id\":14,\"question_text\":\"Which memory is volatile?\",\"option_a\":\"ROM\",\"option_b\":\"RAM\",\"option_c\":\"Hard Disk\",\"option_d\":\"SSD\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:36:00', 1, 5.00),
(8, 1, 15, 8, '{\"question_id\":15,\"question_text\":\"Full form of ROM?\",\"option_a\":\"Random Operating Memory\",\"option_b\":\"Read Only Memory\",\"option_c\":\"Run Only Memory\",\"option_d\":\"Read Open Memory\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:40:00', 1, 5.00),
(9, 1, 16, 9, '{\"question_id\":16,\"question_text\":\"Mother of all languages?\",\"option_a\":\"Java\",\"option_b\":\"Python\",\"option_c\":\"C\",\"option_d\":\"Assembly\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:44:00', 1, 5.00),
(10, 1, 17, 10, '{\"question_id\":17,\"question_text\":\"Full form of SQL?\",\"option_a\":\"Structured Query Language\",\"option_b\":\"Simple Query Language\",\"option_c\":\"Standard Question Language\",\"option_d\":\"System Query Logic\",\"correct_option\":\"A\",\"marks\":5.00}', 'B', '2026-09-15 09:47:00', 0, 0.00),
(11, 2, 1, 1, '{\"question_id\":1,\"question_text\":\"What does CPU stand for?\",\"option_a\":\"Central Processing Unit\",\"option_b\":\"Central Program Utility\",\"option_c\":\"Computer Processing Unit\",\"option_d\":\"Central Peripheral Unit\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:08:00', 1, 5.00),
(12, 2, 2, 2, '{\"question_id\":2,\"question_text\":\"Which is an input device?\",\"option_a\":\"Monitor\",\"option_b\":\"Printer\",\"option_c\":\"Keyboard\",\"option_d\":\"Speaker\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:12:00', 1, 5.00),
(13, 2, 10, 3, '{\"question_id\":10,\"question_text\":\"Decimal value of binary 1010?\",\"option_a\":\"8\",\"option_b\":\"10\",\"option_c\":\"12\",\"option_d\":\"14\",\"correct_option\":\"B\",\"marks\":5.00}', 'A', '2026-09-15 09:17:00', 0, 0.00),
(14, 2, 11, 4, '{\"question_id\":11,\"question_text\":\"Bits in a byte?\",\"option_a\":\"4\",\"option_b\":\"6\",\"option_c\":\"8\",\"option_d\":\"16\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:21:00', 1, 5.00),
(15, 2, 12, 5, '{\"question_id\":12,\"question_text\":\"Which is an operating system?\",\"option_a\":\"MS Word\",\"option_b\":\"Linux\",\"option_c\":\"Photoshop\",\"option_d\":\"Excel\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:25:00', 1, 5.00),
(16, 2, 13, 6, '{\"question_id\":13,\"question_text\":\"Full form of GUI?\",\"option_a\":\"General User Interface\",\"option_b\":\"Graphical User Interface\",\"option_c\":\"Global User Interaction\",\"option_d\":\"Graphical Utility Integration\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:30:00', 1, 5.00),
(17, 2, 14, 7, '{\"question_id\":14,\"question_text\":\"Which memory is volatile?\",\"option_a\":\"ROM\",\"option_b\":\"RAM\",\"option_c\":\"Hard Disk\",\"option_d\":\"SSD\",\"correct_option\":\"B\",\"marks\":5.00}', 'A', '2026-09-15 09:34:00', 0, 0.00),
(18, 2, 15, 8, '{\"question_id\":15,\"question_text\":\"Full form of ROM?\",\"option_a\":\"Random Operating Memory\",\"option_b\":\"Read Only Memory\",\"option_c\":\"Run Only Memory\",\"option_d\":\"Read Open Memory\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:38:00', 1, 5.00),
(19, 2, 16, 9, '{\"question_id\":16,\"question_text\":\"Mother of all languages?\",\"option_a\":\"Java\",\"option_b\":\"Python\",\"option_c\":\"C\",\"option_d\":\"Assembly\",\"correct_option\":\"C\",\"marks\":5.00}', 'D', '2026-09-15 09:42:00', 0, 0.00),
(20, 2, 17, 10, '{\"question_id\":17,\"question_text\":\"Full form of SQL?\",\"option_a\":\"Structured Query Language\",\"option_b\":\"Simple Query Language\",\"option_c\":\"Standard Question Language\",\"option_d\":\"System Query Logic\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:50:00', 1, 5.00),
(21, 3, 1, 1, '{\"question_id\":1,\"question_text\":\"What does CPU stand for?\",\"option_a\":\"Central Processing Unit\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:07:00', 1, 5.00),
(22, 3, 2, 2, '{\"question_id\":2,\"question_text\":\"Which is an input device?\",\"option_c\":\"Keyboard\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:11:00', 1, 5.00),
(23, 3, 10, 3, '{\"question_id\":10,\"question_text\":\"Decimal of binary 1010?\",\"option_b\":\"10\",\"correct_option\":\"B\",\"marks\":5.00}', 'C', '2026-09-15 09:16:00', 0, 0.00),
(24, 3, 11, 4, '{\"question_id\":11,\"question_text\":\"Bits in a byte?\",\"option_c\":\"8\",\"correct_option\":\"C\",\"marks\":5.00}', 'A', '2026-09-15 09:20:00', 0, 0.00),
(25, 3, 12, 5, '{\"question_id\":12,\"question_text\":\"Which is an OS?\",\"option_b\":\"Linux\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:24:00', 1, 5.00),
(26, 3, 13, 6, '{\"question_id\":13,\"question_text\":\"Full form of GUI?\",\"option_b\":\"Graphical User Interface\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:29:00', 1, 5.00),
(27, 3, 14, 7, '{\"question_id\":14,\"question_text\":\"Which memory is volatile?\",\"option_b\":\"RAM\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:33:00', 1, 5.00),
(28, 3, 15, 8, '{\"question_id\":15,\"question_text\":\"Full form of ROM?\",\"option_b\":\"Read Only Memory\",\"correct_option\":\"B\",\"marks\":5.00}', 'C', '2026-09-15 09:37:00', 0, 0.00),
(29, 3, 16, 9, '{\"question_id\":16,\"question_text\":\"Mother of all languages?\",\"option_c\":\"C\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:41:00', 1, 5.00),
(30, 3, 17, 10, '{\"question_id\":17,\"question_text\":\"Full form of SQL?\",\"option_a\":\"Structured Query Language\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:44:00', 1, 5.00),
(31, 4, 1, 1, '{\"question_id\":1,\"question_text\":\"What does CPU stand for?\",\"option_a\":\"Central Processing Unit\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:12:00', 1, 5.00),
(32, 4, 2, 2, '{\"question_id\":2,\"question_text\":\"Which is an input device?\",\"option_c\":\"Keyboard\",\"correct_option\":\"C\",\"marks\":5.00}', 'A', '2026-09-15 09:16:00', 0, 0.00),
(33, 4, 10, 3, '{\"question_id\":10,\"question_text\":\"Decimal of binary 1010?\",\"option_b\":\"10\",\"correct_option\":\"B\",\"marks\":5.00}', 'D', '2026-09-15 09:21:00', 0, 0.00),
(34, 4, 11, 4, '{\"question_id\":11,\"question_text\":\"Bits in a byte?\",\"option_c\":\"8\",\"correct_option\":\"C\",\"marks\":5.00}', 'A', '2026-09-15 09:26:00', 0, 0.00),
(35, 4, 12, 5, '{\"question_id\":12,\"question_text\":\"Which is an OS?\",\"option_b\":\"Linux\",\"correct_option\":\"B\",\"marks\":5.00}', 'A', '2026-09-15 09:30:00', 0, 0.00),
(36, 4, 13, 6, '{\"question_id\":13,\"question_text\":\"Full form of GUI?\",\"option_b\":\"Graphical User Interface\",\"correct_option\":\"B\",\"marks\":5.00}', 'C', '2026-09-15 09:34:00', 0, 0.00),
(37, 4, 14, 7, '{\"question_id\":14,\"question_text\":\"Which memory is volatile?\",\"option_b\":\"RAM\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:38:00', 1, 5.00),
(38, 4, 15, 8, '{\"question_id\":15,\"question_text\":\"Full form of ROM?\",\"option_b\":\"Read Only Memory\",\"correct_option\":\"B\",\"marks\":5.00}', 'D', '2026-09-15 09:42:00', 0, 0.00),
(39, 4, 16, 9, '{\"question_id\":16,\"question_text\":\"Mother of all languages?\",\"option_c\":\"C\",\"correct_option\":\"C\",\"marks\":5.00}', 'A', '2026-09-15 09:46:00', 0, 0.00),
(40, 4, 17, 10, '{\"question_id\":17,\"question_text\":\"Full form of SQL?\",\"option_a\":\"Structured Query Language\",\"correct_option\":\"A\",\"marks\":5.00}', 'C', '2026-09-15 09:54:00', 0, 0.00),
(41, 5, 1, 1, '{\"question_id\":1,\"question_text\":\"What does CPU stand for?\",\"option_a\":\"Central Processing Unit\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:08:00', 1, 5.00),
(42, 5, 2, 2, '{\"question_id\":2,\"question_text\":\"Which is an input device?\",\"option_c\":\"Keyboard\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:10:00', 1, 5.00),
(43, 5, 10, 3, '{\"question_id\":10,\"question_text\":\"Decimal of binary 1010?\",\"option_b\":\"10\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:13:00', 1, 5.00),
(44, 5, 11, 4, '{\"question_id\":11,\"question_text\":\"Bits in a byte?\",\"option_c\":\"8\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:15:00', 1, 5.00),
(45, 5, 12, 5, '{\"question_id\":12,\"question_text\":\"Which is an OS?\",\"option_b\":\"Linux\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:18:00', 1, 5.00),
(46, 5, 13, 6, '{\"question_id\":13,\"question_text\":\"Full form of GUI?\",\"option_b\":\"Graphical User Interface\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:21:00', 1, 5.00),
(47, 5, 14, 7, '{\"question_id\":14,\"question_text\":\"Which memory is volatile?\",\"option_b\":\"RAM\",\"correct_option\":\"B\",\"marks\":5.00}', 'A', '2026-09-15 09:24:00', 0, 0.00),
(48, 5, 15, 8, '{\"question_id\":15,\"question_text\":\"Full form of ROM?\",\"option_b\":\"Read Only Memory\",\"correct_option\":\"B\",\"marks\":5.00}', 'B', '2026-09-15 09:27:00', 1, 5.00),
(49, 5, 16, 9, '{\"question_id\":16,\"question_text\":\"Mother of all languages?\",\"option_c\":\"C\",\"correct_option\":\"C\",\"marks\":5.00}', 'C', '2026-09-15 09:30:00', 1, 5.00),
(50, 5, 17, 10, '{\"question_id\":17,\"question_text\":\"Full form of SQL?\",\"option_a\":\"Structured Query Language\",\"correct_option\":\"A\",\"marks\":5.00}', 'A', '2026-09-15 09:33:00', 1, 5.00),
(51, 12, 4, 1, '{\"topic\":\"Algorithms\",\"question_text\":\"What is binary search?\",\"option_a\":\"Linear scan\",\"option_b\":\"Divide and conquer\",\"option_c\":\"Brute force\",\"option_d\":\"Dynamic programming\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 12:34:51', 1, 5.00),
(52, 12, 20, 2, '{\"topic\":\"OS Concepts\",\"question_text\":\"What is a process?\",\"option_a\":\"A program in execution\",\"option_b\":\"A file on disk\",\"option_c\":\"A user account\",\"option_d\":\"A network connection\",\"correct_option\":\"A\",\"marks\":5}', 'A', '2026-07-28 12:34:51', 1, 5.00),
(53, 12, 21, 3, '{\"topic\":\"OS Concepts\",\"question_text\":\"Which scheduling algorithm gives minimum average waiting time?\",\"option_a\":\"FCFS\",\"option_b\":\"SJF\",\"option_c\":\"Round Robin\",\"option_d\":\"Priority\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 12:34:51', 1, 5.00),
(54, 12, 22, 4, '{\"topic\":\"Memory Management\",\"question_text\":\"What is virtual memory?\",\"option_a\":\"Physical RAM only\",\"option_b\":\"Technique using disk as extended RAM\",\"option_c\":\"Cache memory\",\"option_d\":\"ROM storage\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 12:34:51', 0, 0.00),
(55, 12, 23, 5, '{\"topic\":\"Memory Management\",\"question_text\":\"What is a page fault?\",\"option_a\":\"Hardware failure\",\"option_b\":\"Page not in physical memory\",\"option_c\":\"Printer error\",\"option_d\":\"Network timeout\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 12:34:51', 0, 0.00),
(56, 12, 24, 6, '{\"topic\":\"Concurrency\",\"question_text\":\"What is a deadlock?\",\"option_a\":\"Fast processing\",\"option_b\":\"Processes waiting indefinitely for resources\",\"option_c\":\"CPU overflow\",\"option_d\":\"Memory leak\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 12:34:51', 1, 5.00),
(57, 12, 25, 7, '{\"topic\":\"Concurrency\",\"question_text\":\"Which condition is NOT required for deadlock?\",\"option_a\":\"Mutual Exclusion\",\"option_b\":\"Hold and Wait\",\"option_c\":\"Preemption\",\"option_d\":\"Circular Wait\",\"correct_option\":\"C\",\"marks\":5}', 'C', '2026-07-28 12:34:51', 1, 5.00),
(58, 12, 26, 8, '{\"topic\":\"File Systems\",\"question_text\":\"What is RAID?\",\"option_a\":\"Random Access Internal Device\",\"option_b\":\"Redundant Array of Independent Disks\",\"option_c\":\"Remote Application Interface Design\",\"option_d\":\"Rapid Data Integration\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 12:34:51', 0, 0.00),
(59, 12, 27, 9, '{\"topic\":\"File Systems\",\"question_text\":\"Which file allocation method has external fragmentation?\",\"option_a\":\"Contiguous\",\"option_b\":\"Linked\",\"option_c\":\"Indexed\",\"option_d\":\"All of them\",\"correct_option\":\"A\",\"marks\":5}', 'B', '2026-07-28 12:34:51', 0, 0.00),
(60, 12, 28, 10, '{\"topic\":\"OS Concepts\",\"question_text\":\"What is a system call?\",\"option_a\":\"User login\",\"option_b\":\"Interface between process and OS kernel\",\"option_c\":\"CPU instruction\",\"option_d\":\"File deletion command\",\"correct_option\":\"B\",\"marks\":5}', 'A', '2026-07-28 12:34:51', 0, 0.00),
(61, 13, 4, 1, '{\"topic\":\"Algorithms\",\"question_text\":\"What is binary search?\",\"option_a\":\"Linear scan\",\"option_b\":\"Divide and conquer\",\"option_c\":\"Brute force\",\"option_d\":\"Dynamic programming\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 13:08:43', 1, 5.00),
(62, 13, 20, 2, '{\"topic\":\"OS Concepts\",\"question_text\":\"What is a process?\",\"option_a\":\"A program in execution\",\"option_b\":\"A file on disk\",\"option_c\":\"A user account\",\"option_d\":\"A network connection\",\"correct_option\":\"A\",\"marks\":5}', 'C', '2026-07-28 13:08:43', 0, 0.00),
(63, 13, 21, 3, '{\"topic\":\"OS Concepts\",\"question_text\":\"Which scheduling algorithm gives minimum average waiting time?\",\"option_a\":\"FCFS\",\"option_b\":\"SJF\",\"option_c\":\"Round Robin\",\"option_d\":\"Priority\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 13:08:43', 0, 0.00),
(64, 13, 22, 4, '{\"topic\":\"Memory Management\",\"question_text\":\"What is virtual memory?\",\"option_a\":\"Physical RAM only\",\"option_b\":\"Technique using disk as extended RAM\",\"option_c\":\"Cache memory\",\"option_d\":\"ROM storage\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 13:08:43', 1, 5.00),
(65, 13, 23, 5, '{\"topic\":\"Memory Management\",\"question_text\":\"What is a page fault?\",\"option_a\":\"Hardware failure\",\"option_b\":\"Page not in physical memory\",\"option_c\":\"Printer error\",\"option_d\":\"Network timeout\",\"correct_option\":\"B\",\"marks\":5}', 'B', '2026-07-28 13:08:43', 1, 5.00),
(66, 13, 24, 6, '{\"topic\":\"Concurrency\",\"question_text\":\"What is a deadlock?\",\"option_a\":\"Fast processing\",\"option_b\":\"Processes waiting indefinitely for resources\",\"option_c\":\"CPU overflow\",\"option_d\":\"Memory leak\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 13:08:43', 0, 0.00),
(67, 13, 25, 7, '{\"topic\":\"Concurrency\",\"question_text\":\"Which condition is NOT required for deadlock?\",\"option_a\":\"Mutual Exclusion\",\"option_b\":\"Hold and Wait\",\"option_c\":\"Preemption\",\"option_d\":\"Circular Wait\",\"correct_option\":\"C\",\"marks\":5}', 'B', '2026-07-28 13:08:43', 0, 0.00),
(68, 13, 26, 8, '{\"topic\":\"File Systems\",\"question_text\":\"What is RAID?\",\"option_a\":\"Random Access Internal Device\",\"option_b\":\"Redundant Array of Independent Disks\",\"option_c\":\"Remote Application Interface Design\",\"option_d\":\"Rapid Data Integration\",\"correct_option\":\"B\",\"marks\":5}', 'C', '2026-07-28 13:08:43', 0, 0.00),
(69, 13, 27, 9, '{\"topic\":\"File Systems\",\"question_text\":\"Which file allocation method has external fragmentation?\",\"option_a\":\"Contiguous\",\"option_b\":\"Linked\",\"option_c\":\"Indexed\",\"option_d\":\"All of them\",\"correct_option\":\"A\",\"marks\":5}', 'B', '2026-07-28 13:08:43', 0, 0.00),
(70, 13, 28, 10, '{\"topic\":\"OS Concepts\",\"question_text\":\"What is a system call?\",\"option_a\":\"User login\",\"option_b\":\"Interface between process and OS kernel\",\"option_c\":\"CPU instruction\",\"option_d\":\"File deletion command\",\"correct_option\":\"B\",\"marks\":5}', 'A', '2026-07-28 13:08:43', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `sbe_student_exams`
--

CREATE TABLE `sbe_student_exams` (
  `student_exam_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `attempt_no` tinyint(4) NOT NULL DEFAULT 1,
  `status` enum('In Progress','Submitted','Auto Submitted','Expired','Cancelled') NOT NULL DEFAULT 'In Progress',
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `time_taken_seconds` int(11) DEFAULT NULL,
  `obtained_marks` decimal(6,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `pass_fail_status` enum('Pass','Fail') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sbe_student_exams`
--

INSERT INTO `sbe_student_exams` (`student_exam_id`, `schedule_id`, `exam_id`, `student_id`, `attempt_no`, `status`, `started_at`, `expires_at`, `submitted_at`, `time_taken_seconds`, `obtained_marks`, `percentage`, `pass_fail_status`) VALUES
(1, 1, 1, 25, 1, 'Submitted', '2026-09-15 09:05:00', '2026-09-15 10:05:00', '2026-09-15 09:48:00', 2580, 40.00, 80.00, 'Pass'),
(2, 1, 1, 26, 1, 'Submitted', '2026-09-15 09:03:00', '2026-09-15 10:03:00', '2026-09-15 09:52:00', 2940, 35.00, 70.00, 'Pass'),
(3, 1, 1, 27, 1, 'Submitted', '2026-09-15 09:02:00', '2026-09-15 10:02:00', '2026-09-15 09:50:00', 2880, 30.00, 60.00, 'Pass'),
(4, 1, 1, 28, 1, 'Submitted', '2026-09-15 09:07:00', '2026-09-15 10:07:00', '2026-09-15 09:55:00', 2880, 20.00, 40.00, 'Fail'),
(5, 1, 1, 29, 1, 'Submitted', '2026-09-15 09:04:00', '2026-09-15 10:04:00', '2026-09-15 09:45:00', 2460, 45.00, 90.00, 'Pass'),
(12, 2, 2, 25, 1, 'Submitted', '2026-07-28 12:34:25', '2026-07-28 11:04:25', '2026-07-28 12:34:51', 26, 25.00, 50.00, 'Pass'),
(13, 2, 2, 26, 1, 'Submitted', '2026-07-28 13:08:06', '2026-07-28 11:38:06', '2026-07-28 13:08:43', 37, 15.00, 30.00, 'Fail');

-- --------------------------------------------------------

--
-- Table structure for table `scholarships`
--

CREATE TABLE `scholarships` (
  `scholarship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `scholarship_type` enum('Admission','Semester-wise','Merit','Need-based','Other') NOT NULL,
  `awarding_body` varchar(100) NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `discount_kind` enum('Flat','Percentage') NOT NULL DEFAULT 'Percentage',
  `discount_value` decimal(10,2) NOT NULL,
  `approved_by` int(11) NOT NULL,
  `approved_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('Active','Revoked') NOT NULL DEFAULT 'Active',
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `scholarships`
--

INSERT INTO `scholarships` (`scholarship_id`, `student_id`, `scholarship_type`, `awarding_body`, `semester_id`, `discount_kind`, `discount_value`, `approved_by`, `approved_at`, `status`, `remarks`) VALUES
(1, 0, '', 'value_1', 6, 'Percentage', 10.00, 2, '2026-07-15 05:30:00', 'Active', 'Remark 1'),
(2, 0, '', 'value_2', 7, 'Percentage', 10.00, 1, '2026-07-15 05:30:00', 'Active', 'Remark 2'),
(3, 0, '', 'value_3', 8, 'Percentage', 10.00, 2, '2026-07-15 05:30:00', 'Active', 'Remark 3'),
(4, 0, '', 'value_4', 9, 'Percentage', 10.00, 1, '2026-07-15 05:30:00', 'Active', 'Remark 4'),
(5, 0, '', 'value_5', 10, 'Percentage', 10.00, 2, '2026-07-15 05:30:00', 'Active', 'Remark 5');

-- --------------------------------------------------------

--
-- Table structure for table `scholarship_programs`
--

CREATE TABLE `scholarship_programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `program_type` enum('Merit','Need Based','Sports','Research','Other') DEFAULT 'Merit',
  `description` text DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  `min_percentage` decimal(5,2) DEFAULT 0.00,
  `max_percentage` decimal(5,2) DEFAULT 100.00,
  `total_slots` int(11) DEFAULT NULL,
  `deadline` date DEFAULT NULL,
  `status` enum('active','inactive','expired') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30,
  `enrolled_count` int(11) DEFAULT 0,
  `enrolled` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `program_id`, `semester_id`, `session_id`, `course_id`, `teacher_id`, `capacity`, `enrolled_count`, `enrolled`, `academic_year`, `status`, `created_at`) VALUES
(1, 'Section A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 14:49:19'),
(2, 'Section B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 14:49:19'),
(3, 'Section C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 14:49:19'),
(7, 'A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(8, 'B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(9, 'C', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(10, 'A', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(11, 'B', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(12, 'C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:01:28'),
(13, 'A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:02:18'),
(14, 'B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:02:18'),
(15, 'C', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:02:18'),
(16, 'A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(17, 'B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(18, 'C', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(19, 'A', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(20, 'B', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(21, 'C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(22, 'A', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(23, 'B', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(24, 'C', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(25, 'A', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(26, 'B', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(27, 'C', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(28, 'A', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(29, 'B', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(30, 'C', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(31, 'A', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(32, 'B', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(33, 'C', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:03:57'),
(34, 'A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(35, 'B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(36, 'C', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(37, 'A', 1, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(38, 'B', 1, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(39, 'C', 1, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(40, 'A', 1, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(41, 'B', 1, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(42, 'C', 1, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(43, 'A', 1, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(44, 'B', 1, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(45, 'C', 1, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(46, 'A', 1, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(47, 'B', 1, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(48, 'C', 1, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(49, 'A', 1, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(50, 'B', 1, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(51, 'C', 1, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(52, 'A', 1, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(53, 'B', 1, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(54, 'C', 1, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(55, 'A', 1, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(56, 'B', 1, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(57, 'C', 1, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(58, 'A', 1, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(59, 'B', 1, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(60, 'C', 1, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(61, 'A', 1, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(62, 'B', 1, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(63, 'C', 1, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(64, 'A', 1, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(65, 'B', 1, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(66, 'C', 1, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(67, 'A', 1, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(68, 'B', 1, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(69, 'C', 1, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(70, 'A', 1, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(71, 'B', 1, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(72, 'C', 1, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(73, 'A', 1, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(74, 'B', 1, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(75, 'C', 1, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(76, 'A', 1, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(77, 'B', 1, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(78, 'C', 1, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(79, 'A', 1, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(80, 'B', 1, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(81, 'C', 1, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(82, 'A', 1, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(83, 'B', 1, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(84, 'C', 1, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(85, 'A', 1, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(86, 'B', 1, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(87, 'C', 1, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(88, 'A', 1, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(89, 'B', 1, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(90, 'C', 1, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(91, 'A', 1, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(92, 'B', 1, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(93, 'C', 1, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(94, 'A', 1, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(95, 'B', 1, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(96, 'C', 1, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(97, 'A', 1, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(98, 'B', 1, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(99, 'C', 1, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(100, 'A', 1, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(101, 'B', 1, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(102, 'C', 1, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(103, 'A', 1, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(104, 'B', 1, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(105, 'C', 1, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(106, 'A', 1, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(107, 'B', 1, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(108, 'C', 1, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(109, 'A', 1, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(110, 'B', 1, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(111, 'C', 1, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(112, 'A', 1, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(113, 'B', 1, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(114, 'C', 1, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(115, 'A', 1, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(116, 'B', 1, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(117, 'C', 1, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(118, 'A', 1, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(119, 'B', 1, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(120, 'C', 1, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(121, 'A', 1, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(122, 'B', 1, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(123, 'C', 1, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(124, 'A', 1, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(125, 'B', 1, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(126, 'C', 1, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(127, 'A', 1, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(128, 'B', 1, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(129, 'C', 1, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(130, 'A', 1, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(131, 'B', 1, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(132, 'C', 1, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(133, 'A', 1, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(134, 'B', 1, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(135, 'C', 1, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(136, 'A', 1, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(137, 'B', 1, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(138, 'C', 1, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(139, 'A', 1, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(140, 'B', 1, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(141, 'C', 1, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(142, 'A', 1, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(143, 'B', 1, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(144, 'C', 1, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(145, 'A', 1, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(146, 'B', 1, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(147, 'C', 1, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(148, 'A', 1, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(149, 'B', 1, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(150, 'C', 1, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(151, 'A', 1, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(152, 'B', 1, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(153, 'C', 1, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(154, 'A', 1, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(155, 'B', 1, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(156, 'C', 1, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(157, 'A', 1, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(158, 'B', 1, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(159, 'C', 1, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(160, 'A', 1, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(161, 'B', 1, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(162, 'C', 1, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(163, 'A', 1, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(164, 'B', 1, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(165, 'C', 1, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(166, 'A', 1, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(167, 'B', 1, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(168, 'C', 1, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(169, 'A', 1, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(170, 'B', 1, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(171, 'C', 1, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(172, 'A', 1, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(173, 'B', 1, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(174, 'C', 1, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(175, 'A', 1, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(176, 'B', 1, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(177, 'C', 1, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(178, 'A', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(179, 'B', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(180, 'C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(181, 'A', 2, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(182, 'B', 2, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(183, 'C', 2, 2, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(184, 'A', 2, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(185, 'B', 2, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(186, 'C', 2, 3, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(187, 'A', 2, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(188, 'B', 2, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(189, 'C', 2, 4, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(190, 'A', 2, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(191, 'B', 2, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(192, 'C', 2, 5, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(193, 'A', 2, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(194, 'B', 2, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(195, 'C', 2, 6, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(196, 'A', 2, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(197, 'B', 2, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(198, 'C', 2, 7, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(199, 'A', 2, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(200, 'B', 2, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(201, 'C', 2, 8, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(202, 'A', 2, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(203, 'B', 2, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(204, 'C', 2, 9, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(205, 'A', 2, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(206, 'B', 2, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(207, 'C', 2, 10, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(208, 'A', 2, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(209, 'B', 2, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(210, 'C', 2, 11, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(211, 'A', 2, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(212, 'B', 2, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(213, 'C', 2, 12, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(214, 'A', 2, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(215, 'B', 2, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(216, 'C', 2, 13, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(217, 'A', 2, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(218, 'B', 2, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(219, 'C', 2, 14, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(220, 'A', 2, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(221, 'B', 2, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(222, 'C', 2, 15, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(223, 'A', 2, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(224, 'B', 2, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(225, 'C', 2, 16, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(226, 'A', 2, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(227, 'B', 2, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(228, 'C', 2, 17, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(229, 'A', 2, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(230, 'B', 2, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(231, 'C', 2, 18, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(232, 'A', 2, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(233, 'B', 2, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(234, 'C', 2, 19, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(235, 'A', 2, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(236, 'B', 2, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(237, 'C', 2, 20, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(238, 'A', 2, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(239, 'B', 2, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(240, 'C', 2, 21, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(241, 'A', 2, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(242, 'B', 2, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(243, 'C', 2, 22, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(244, 'A', 2, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(245, 'B', 2, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(246, 'C', 2, 23, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(247, 'A', 2, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(248, 'B', 2, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(249, 'C', 2, 24, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(250, 'A', 2, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(251, 'B', 2, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(252, 'C', 2, 25, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(253, 'A', 2, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(254, 'B', 2, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(255, 'C', 2, 26, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(256, 'A', 2, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(257, 'B', 2, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(258, 'C', 2, 27, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(259, 'A', 2, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(260, 'B', 2, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(261, 'C', 2, 28, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(262, 'A', 2, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(263, 'B', 2, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(264, 'C', 2, 29, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(265, 'A', 2, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(266, 'B', 2, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(267, 'C', 2, 30, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(268, 'A', 2, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(269, 'B', 2, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(270, 'C', 2, 31, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(271, 'A', 2, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(272, 'B', 2, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(273, 'C', 2, 32, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(274, 'A', 2, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(275, 'B', 2, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(276, 'C', 2, 33, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(277, 'A', 2, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(278, 'B', 2, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(279, 'C', 2, 34, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(280, 'A', 2, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(281, 'B', 2, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(282, 'C', 2, 35, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(283, 'A', 2, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(284, 'B', 2, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(285, 'C', 2, 36, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(286, 'A', 2, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(287, 'B', 2, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(288, 'C', 2, 37, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(289, 'A', 2, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(290, 'B', 2, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(291, 'C', 2, 38, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(292, 'A', 2, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(293, 'B', 2, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(294, 'C', 2, 39, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(295, 'A', 2, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(296, 'B', 2, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(297, 'C', 2, 40, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(298, 'A', 2, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(299, 'B', 2, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(300, 'C', 2, 105, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(301, 'A', 2, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(302, 'B', 2, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(303, 'C', 2, 106, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(304, 'A', 2, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(305, 'B', 2, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(306, 'C', 2, 107, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(307, 'A', 2, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(308, 'B', 2, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(309, 'C', 2, 108, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(310, 'A', 2, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(311, 'B', 2, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(312, 'C', 2, 109, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(313, 'A', 2, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(314, 'B', 2, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(315, 'C', 2, 110, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(316, 'A', 2, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(317, 'B', 2, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(318, 'C', 2, 111, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(319, 'A', 2, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(320, 'B', 2, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(321, 'C', 2, 112, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(322, 'A', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(323, 'B', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(324, 'C', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(325, 'A', 3, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(326, 'B', 3, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(327, 'C', 3, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(328, 'A', 3, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(329, 'B', 3, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(330, 'C', 3, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(331, 'A', 3, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(332, 'B', 3, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(333, 'C', 3, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(334, 'A', 3, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(335, 'B', 3, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(336, 'C', 3, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(337, 'A', 3, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(338, 'B', 3, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(339, 'C', 3, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(340, 'A', 3, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(341, 'B', 3, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(342, 'C', 3, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(343, 'A', 3, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(344, 'B', 3, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(345, 'C', 3, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(346, 'A', 3, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(347, 'B', 3, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(348, 'C', 3, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(349, 'A', 3, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(350, 'B', 3, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(351, 'C', 3, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(352, 'A', 3, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(353, 'B', 3, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(354, 'C', 3, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(355, 'A', 3, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(356, 'B', 3, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(357, 'C', 3, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(358, 'A', 3, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(359, 'B', 3, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(360, 'C', 3, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(361, 'A', 3, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(362, 'B', 3, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(363, 'C', 3, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(364, 'A', 3, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(365, 'B', 3, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(366, 'C', 3, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(367, 'A', 3, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(368, 'B', 3, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(369, 'C', 3, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(370, 'A', 3, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(371, 'B', 3, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(372, 'C', 3, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(373, 'A', 3, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(374, 'B', 3, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(375, 'C', 3, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(376, 'A', 3, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(377, 'B', 3, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(378, 'C', 3, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(379, 'A', 3, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(380, 'B', 3, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(381, 'C', 3, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(382, 'A', 3, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(383, 'B', 3, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(384, 'C', 3, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(385, 'A', 3, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(386, 'B', 3, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(387, 'C', 3, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(388, 'A', 3, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(389, 'B', 3, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(390, 'C', 3, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(391, 'A', 3, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(392, 'B', 3, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(393, 'C', 3, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(394, 'A', 3, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(395, 'B', 3, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(396, 'C', 3, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(397, 'A', 3, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(398, 'B', 3, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(399, 'C', 3, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(400, 'A', 3, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(401, 'B', 3, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(402, 'C', 3, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(403, 'A', 3, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(404, 'B', 3, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(405, 'C', 3, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(406, 'A', 3, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(407, 'B', 3, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(408, 'C', 3, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(409, 'A', 3, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(410, 'B', 3, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(411, 'C', 3, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(412, 'A', 3, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(413, 'B', 3, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(414, 'C', 3, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(415, 'A', 3, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(416, 'B', 3, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(417, 'C', 3, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(418, 'A', 3, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(419, 'B', 3, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(420, 'C', 3, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(421, 'A', 3, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(422, 'B', 3, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(423, 'C', 3, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(424, 'A', 3, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(425, 'B', 3, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(426, 'C', 3, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(427, 'A', 3, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(428, 'B', 3, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(429, 'C', 3, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(430, 'A', 3, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(431, 'B', 3, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(432, 'C', 3, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(433, 'A', 3, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(434, 'B', 3, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(435, 'C', 3, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(436, 'A', 3, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(437, 'B', 3, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(438, 'C', 3, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(439, 'A', 3, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(440, 'B', 3, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(441, 'C', 3, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(442, 'A', 3, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(443, 'B', 3, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(444, 'C', 3, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(445, 'A', 3, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(446, 'B', 3, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(447, 'C', 3, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(448, 'A', 3, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(449, 'B', 3, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(450, 'C', 3, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(451, 'A', 3, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(452, 'B', 3, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(453, 'C', 3, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(454, 'A', 3, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(455, 'B', 3, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(456, 'C', 3, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(457, 'A', 3, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(458, 'B', 3, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(459, 'C', 3, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(460, 'A', 3, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(461, 'B', 3, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(462, 'C', 3, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(463, 'A', 3, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(464, 'B', 3, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(465, 'C', 3, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(466, 'A', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(467, 'B', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(468, 'C', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(469, 'A', 4, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(470, 'B', 4, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(471, 'C', 4, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(472, 'A', 4, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(473, 'B', 4, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(474, 'C', 4, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(475, 'A', 4, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(476, 'B', 4, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(477, 'C', 4, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(478, 'A', 4, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(479, 'B', 4, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(480, 'C', 4, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(481, 'A', 4, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(482, 'B', 4, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(483, 'C', 4, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(484, 'A', 4, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(485, 'B', 4, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(486, 'C', 4, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(487, 'A', 4, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(488, 'B', 4, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(489, 'C', 4, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(490, 'A', 4, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(491, 'B', 4, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(492, 'C', 4, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(493, 'A', 4, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(494, 'B', 4, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(495, 'C', 4, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(496, 'A', 4, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(497, 'B', 4, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(498, 'C', 4, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(499, 'A', 4, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(500, 'B', 4, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(501, 'C', 4, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(502, 'A', 4, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(503, 'B', 4, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(504, 'C', 4, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(505, 'A', 4, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(506, 'B', 4, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(507, 'C', 4, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(508, 'A', 4, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(509, 'B', 4, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(510, 'C', 4, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(511, 'A', 4, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(512, 'B', 4, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(513, 'C', 4, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(514, 'A', 4, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(515, 'B', 4, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(516, 'C', 4, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(517, 'A', 4, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(518, 'B', 4, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(519, 'C', 4, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(520, 'A', 4, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(521, 'B', 4, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(522, 'C', 4, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(523, 'A', 4, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(524, 'B', 4, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(525, 'C', 4, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(526, 'A', 4, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(527, 'B', 4, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(528, 'C', 4, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(529, 'A', 4, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(530, 'B', 4, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(531, 'C', 4, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(532, 'A', 4, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(533, 'B', 4, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(534, 'C', 4, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(535, 'A', 4, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(536, 'B', 4, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(537, 'C', 4, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(538, 'A', 4, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(539, 'B', 4, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(540, 'C', 4, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(541, 'A', 4, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(542, 'B', 4, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(543, 'C', 4, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(544, 'A', 4, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(545, 'B', 4, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(546, 'C', 4, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(547, 'A', 4, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(548, 'B', 4, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(549, 'C', 4, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(550, 'A', 4, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(551, 'B', 4, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(552, 'C', 4, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(553, 'A', 4, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(554, 'B', 4, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(555, 'C', 4, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(556, 'A', 4, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(557, 'B', 4, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(558, 'C', 4, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(559, 'A', 4, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(560, 'B', 4, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(561, 'C', 4, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(562, 'A', 4, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(563, 'B', 4, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(564, 'C', 4, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(565, 'A', 4, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(566, 'B', 4, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(567, 'C', 4, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(568, 'A', 4, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(569, 'B', 4, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(570, 'C', 4, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(571, 'A', 4, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(572, 'B', 4, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(573, 'C', 4, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(574, 'A', 4, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(575, 'B', 4, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(576, 'C', 4, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(577, 'A', 4, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(578, 'B', 4, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(579, 'C', 4, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(580, 'A', 4, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(581, 'B', 4, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(582, 'C', 4, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(583, 'A', 4, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(584, 'B', 4, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(585, 'C', 4, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(586, 'A', 4, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(587, 'B', 4, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(588, 'C', 4, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(589, 'A', 4, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(590, 'B', 4, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(591, 'C', 4, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(592, 'A', 4, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(593, 'B', 4, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(594, 'C', 4, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(595, 'A', 4, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(596, 'B', 4, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(597, 'C', 4, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13');
INSERT INTO `sections` (`section_id`, `section_name`, `program_id`, `semester_id`, `session_id`, `course_id`, `teacher_id`, `capacity`, `enrolled_count`, `enrolled`, `academic_year`, `status`, `created_at`) VALUES
(598, 'A', 4, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(599, 'B', 4, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(600, 'C', 4, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(601, 'A', 4, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(602, 'B', 4, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(603, 'C', 4, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(604, 'A', 4, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(605, 'B', 4, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(606, 'C', 4, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(607, 'A', 4, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(608, 'B', 4, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(609, 'C', 4, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(610, 'A', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(611, 'B', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(612, 'C', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(613, 'A', 5, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(614, 'B', 5, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(615, 'C', 5, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(616, 'A', 5, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(617, 'B', 5, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(618, 'C', 5, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(619, 'A', 5, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(620, 'B', 5, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(621, 'C', 5, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(622, 'A', 5, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(623, 'B', 5, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(624, 'C', 5, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(625, 'A', 5, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(626, 'B', 5, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(627, 'C', 5, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(628, 'A', 5, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(629, 'B', 5, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(630, 'C', 5, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(631, 'A', 5, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(632, 'B', 5, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(633, 'C', 5, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(634, 'A', 5, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(635, 'B', 5, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(636, 'C', 5, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(637, 'A', 5, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(638, 'B', 5, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(639, 'C', 5, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(640, 'A', 5, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(641, 'B', 5, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(642, 'C', 5, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(643, 'A', 5, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(644, 'B', 5, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(645, 'C', 5, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(646, 'A', 5, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(647, 'B', 5, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(648, 'C', 5, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(649, 'A', 5, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(650, 'B', 5, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(651, 'C', 5, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(652, 'A', 5, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(653, 'B', 5, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(654, 'C', 5, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(655, 'A', 5, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(656, 'B', 5, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(657, 'C', 5, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(658, 'A', 5, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(659, 'B', 5, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(660, 'C', 5, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(661, 'A', 5, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(662, 'B', 5, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(663, 'C', 5, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(664, 'A', 5, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(665, 'B', 5, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(666, 'C', 5, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(667, 'A', 5, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(668, 'B', 5, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(669, 'C', 5, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(670, 'A', 5, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(671, 'B', 5, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(672, 'C', 5, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(673, 'A', 5, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(674, 'B', 5, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(675, 'C', 5, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(676, 'A', 5, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(677, 'B', 5, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(678, 'C', 5, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(679, 'A', 5, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(680, 'B', 5, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(681, 'C', 5, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(682, 'A', 5, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(683, 'B', 5, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(684, 'C', 5, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(685, 'A', 5, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(686, 'B', 5, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(687, 'C', 5, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(688, 'A', 5, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(689, 'B', 5, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(690, 'C', 5, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(691, 'A', 5, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(692, 'B', 5, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(693, 'C', 5, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(694, 'A', 5, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(695, 'B', 5, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(696, 'C', 5, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(697, 'A', 5, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(698, 'B', 5, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(699, 'C', 5, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(700, 'A', 5, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(701, 'B', 5, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(702, 'C', 5, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(703, 'A', 5, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(704, 'B', 5, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(705, 'C', 5, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(706, 'A', 5, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(707, 'B', 5, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(708, 'C', 5, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(709, 'A', 5, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(710, 'B', 5, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(711, 'C', 5, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(712, 'A', 5, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(713, 'B', 5, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(714, 'C', 5, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(715, 'A', 5, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(716, 'B', 5, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(717, 'C', 5, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(718, 'A', 5, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(719, 'B', 5, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(720, 'C', 5, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(721, 'A', 5, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(722, 'B', 5, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(723, 'C', 5, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(724, 'A', 5, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(725, 'B', 5, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(726, 'C', 5, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(727, 'A', 5, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(728, 'B', 5, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(729, 'C', 5, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(730, 'A', 5, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(731, 'B', 5, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(732, 'C', 5, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(733, 'A', 5, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(734, 'B', 5, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(735, 'C', 5, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(736, 'A', 5, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(737, 'B', 5, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(738, 'C', 5, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(739, 'A', 5, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(740, 'B', 5, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(741, 'C', 5, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(742, 'A', 5, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(743, 'B', 5, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(744, 'C', 5, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(745, 'A', 5, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(746, 'B', 5, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(747, 'C', 5, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(748, 'A', 5, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(749, 'B', 5, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(750, 'C', 5, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(751, 'A', 5, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(752, 'B', 5, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(753, 'C', 5, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(754, 'A', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(755, 'B', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(756, 'C', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(757, 'A', 6, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(758, 'B', 6, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(759, 'C', 6, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(760, 'A', 6, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(761, 'B', 6, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(762, 'C', 6, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(763, 'A', 6, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(764, 'B', 6, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(765, 'C', 6, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(766, 'A', 6, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(767, 'B', 6, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(768, 'C', 6, 5, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(769, 'A', 6, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(770, 'B', 6, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(771, 'C', 6, 6, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(772, 'A', 6, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(773, 'B', 6, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(774, 'C', 6, 7, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(775, 'A', 6, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(776, 'B', 6, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(777, 'C', 6, 8, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(778, 'A', 6, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(779, 'B', 6, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(780, 'C', 6, 9, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(781, 'A', 6, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(782, 'B', 6, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(783, 'C', 6, 10, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(784, 'A', 6, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(785, 'B', 6, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(786, 'C', 6, 11, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(787, 'A', 6, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(788, 'B', 6, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(789, 'C', 6, 12, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(790, 'A', 6, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(791, 'B', 6, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(792, 'C', 6, 13, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(793, 'A', 6, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(794, 'B', 6, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(795, 'C', 6, 14, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(796, 'A', 6, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(797, 'B', 6, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(798, 'C', 6, 15, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(799, 'A', 6, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(800, 'B', 6, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(801, 'C', 6, 16, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(802, 'A', 6, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(803, 'B', 6, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(804, 'C', 6, 17, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(805, 'A', 6, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(806, 'B', 6, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(807, 'C', 6, 18, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(808, 'A', 6, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(809, 'B', 6, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(810, 'C', 6, 19, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(811, 'A', 6, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(812, 'B', 6, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(813, 'C', 6, 20, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(814, 'A', 6, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(815, 'B', 6, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(816, 'C', 6, 21, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(817, 'A', 6, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(818, 'B', 6, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(819, 'C', 6, 22, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(820, 'A', 6, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(821, 'B', 6, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(822, 'C', 6, 23, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(823, 'A', 6, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(824, 'B', 6, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(825, 'C', 6, 24, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(826, 'A', 6, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(827, 'B', 6, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(828, 'C', 6, 25, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(829, 'A', 6, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(830, 'B', 6, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(831, 'C', 6, 26, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(832, 'A', 6, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(833, 'B', 6, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(834, 'C', 6, 27, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(835, 'A', 6, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(836, 'B', 6, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(837, 'C', 6, 28, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(838, 'A', 6, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(839, 'B', 6, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(840, 'C', 6, 29, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(841, 'A', 6, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(842, 'B', 6, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(843, 'C', 6, 30, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(844, 'A', 6, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(845, 'B', 6, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(846, 'C', 6, 31, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(847, 'A', 6, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(848, 'B', 6, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(849, 'C', 6, 32, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(850, 'A', 6, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(851, 'B', 6, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(852, 'C', 6, 33, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(853, 'A', 6, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(854, 'B', 6, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(855, 'C', 6, 34, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(856, 'A', 6, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(857, 'B', 6, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(858, 'C', 6, 35, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(859, 'A', 6, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(860, 'B', 6, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(861, 'C', 6, 36, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(862, 'A', 6, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(863, 'B', 6, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(864, 'C', 6, 37, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(865, 'A', 6, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(866, 'B', 6, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(867, 'C', 6, 38, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(868, 'A', 6, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(869, 'B', 6, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(870, 'C', 6, 39, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(871, 'A', 6, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(872, 'B', 6, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(873, 'C', 6, 40, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(874, 'A', 6, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(875, 'B', 6, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(876, 'C', 6, 105, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(877, 'A', 6, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(878, 'B', 6, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(879, 'C', 6, 106, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(880, 'A', 6, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(881, 'B', 6, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(882, 'C', 6, 107, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(883, 'A', 6, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(884, 'B', 6, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(885, 'C', 6, 108, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(886, 'A', 6, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(887, 'B', 6, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(888, 'C', 6, 109, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(889, 'A', 6, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(890, 'B', 6, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(891, 'C', 6, 110, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(892, 'A', 6, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(893, 'B', 6, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(894, 'C', 6, 111, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(895, 'A', 6, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(896, 'B', 6, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(897, 'C', 6, 112, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:13'),
(1057, 'A', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1058, 'B', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1059, 'C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1060, 'A', 2, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1061, 'B', 2, 2, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1062, 'C', 2, 2, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1063, 'A', 2, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1064, 'B', 2, 3, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1065, 'C', 2, 3, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1066, 'A', 2, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1067, 'B', 2, 4, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1068, 'C', 2, 4, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:27'),
(1069, 'A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1070, 'B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1071, 'C', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1072, 'A', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1073, 'B', 2, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1074, 'C', 2, 1, NULL, NULL, NULL, 50, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1075, 'A', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1076, 'B', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1077, 'C', 3, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1078, 'A', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1079, 'B', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1080, 'C', 4, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1081, 'A', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1082, 'B', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1083, 'C', 5, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1084, 'A', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1085, 'B', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41'),
(1086, 'C', 6, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 20:04:41');

-- --------------------------------------------------------

--
-- Table structure for table `sections_backup`
--

CREATE TABLE `sections_backup` (
  `section_id` int(11) NOT NULL DEFAULT 0,
  `section_name` varchar(50) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30,
  `enrolled_count` int(11) DEFAULT 0,
  `enrolled` int(11) DEFAULT 0,
  `academic_year` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections_backup`
--

INSERT INTO `sections_backup` (`section_id`, `section_name`, `program_id`, `semester_id`, `session_id`, `course_id`, `teacher_id`, `capacity`, `enrolled_count`, `enrolled`, `academic_year`, `status`, `created_at`) VALUES
(1, 'Section A', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 14:49:19'),
(2, 'Section B', 1, 1, NULL, NULL, NULL, 30, 0, 0, NULL, 'Active', '2026-07-30 14:49:19'),
(3, 'Section C', 2, 1, NULL, NULL, NULL, 25, 0, 0, NULL, 'Active', '2026-07-30 14:49:19');

-- --------------------------------------------------------

--
-- Table structure for table `section_courses`
--

CREATE TABLE `section_courses` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `section_courses`
--

INSERT INTO `section_courses` (`id`, `section_id`, `course_id`, `teacher_id`, `created_at`) VALUES
(1, 1, 14, '2', '2026-07-15 05:30:00'),
(2, 2, 15, '1', '2026-07-15 05:30:00'),
(3, 3, 16, '2', '2026-07-15 05:30:00'),
(4, 4, 17, '1', '2026-07-15 05:30:00'),
(5, 5, 18, '2', '2026-07-15 05:30:00'),
(6, 6, 19, '1', '2026-07-15 05:30:00'),
(7, 7, 20, '2', '2026-07-15 05:30:00'),
(8, 8, 21, '1', '2026-07-15 05:30:00'),
(9, 9, 22, '2', '2026-07-15 05:30:00'),
(10, 10, 13, '1', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `section_schedules`
--

CREATE TABLE `section_schedules` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `status` enum('Draft','Approved','Published') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `semester_number` tinyint(4) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_name`, `semester_number`, `department_id`) VALUES
(1, 'Semester 1', 1, 1),
(2, 'Semester 2', 2, 1),
(3, 'Semester 3', 3, 1),
(4, 'Semester 4', 4, 1),
(5, 'Semester 5', 5, 1),
(6, 'Semester 6', 6, 1),
(7, 'Semester 7', 7, 1),
(8, 'Semester 8', 8, 1),
(9, 'Semester 1', 1, 2),
(10, 'Semester 2', 2, 2),
(11, 'Semester 3', 3, 2),
(12, 'Semester 4', 4, 2),
(13, 'Semester 5', 5, 2),
(14, 'Semester 6', 6, 2),
(15, 'Semester 7', 7, 2),
(16, 'Semester 8', 8, 2),
(17, 'Semester 1', 1, 3),
(18, 'Semester 2', 2, 3),
(19, 'Semester 3', 3, 3),
(20, 'Semester 4', 4, 3),
(21, 'Semester 5', 5, 3),
(22, 'Semester 6', 6, 3),
(23, 'Semester 7', 7, 3),
(24, 'Semester 8', 8, 3),
(25, 'Semester 1', 1, 4),
(26, 'Semester 2', 2, 4),
(27, 'Semester 3', 3, 4),
(28, 'Semester 4', 4, 4),
(29, 'Semester 5', 5, 4),
(30, 'Semester 6', 6, 4),
(31, 'Semester 7', 7, 4),
(32, 'Semester 8', 8, 4),
(33, 'Semester 1', 1, 5),
(34, 'Semester 2', 2, 5),
(35, 'Semester 3', 3, 5),
(36, 'Semester 4', 4, 5),
(37, 'Semester 5', 5, 5),
(38, 'Semester 6', 6, 5),
(39, 'Semester 7', 7, 5),
(40, 'Semester 8', 8, 5),
(105, 'Semester 1', 1, 6),
(106, 'Semester 2', 2, 6),
(107, 'Semester 3', 3, 6),
(108, 'Semester 4', 4, 6),
(109, 'Semester 5', 5, 6),
(110, 'Semester 6', 6, 6),
(111, 'Semester 7', 7, 6),
(112, 'Semester 8', 8, 6);

-- --------------------------------------------------------

--
-- Table structure for table `semesters_backup`
--

CREATE TABLE `semesters_backup` (
  `semester_id` int(11) NOT NULL DEFAULT 0,
  `semester_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_number` tinyint(4) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters_backup`
--

INSERT INTO `semesters_backup` (`semester_id`, `semester_name`, `semester_number`, `department_id`) VALUES
(1, 'Semester 1', 1, 1),
(2, 'Semester 2', 2, 1),
(3, 'Semester 3', 3, 1),
(4, 'Semester 4', 4, 1),
(5, 'Semester 5', 5, 1),
(6, 'Semester 6', 6, 1),
(7, 'Semester 7', 7, 1),
(8, 'Semester 8', 8, 1),
(9, 'Semester 1', 1, 2),
(10, 'Semester 2', 2, 2),
(11, 'Semester 3', 3, 2),
(12, 'Semester 4', 4, 2),
(13, 'Semester 5', 5, 2),
(14, 'Semester 6', 6, 2),
(15, 'Semester 7', 7, 2),
(16, 'Semester 8', 8, 2),
(17, 'Semester 1', 1, 3),
(18, 'Semester 2', 2, 3),
(19, 'Semester 3', 3, 3),
(20, 'Semester 4', 4, 3),
(21, 'Semester 5', 5, 3),
(22, 'Semester 6', 6, 3),
(23, 'Semester 7', 7, 3),
(24, 'Semester 8', 8, 3),
(25, 'Semester 1', 1, 4),
(26, 'Semester 2', 2, 4),
(27, 'Semester 3', 3, 4),
(28, 'Semester 4', 4, 4),
(29, 'Semester 5', 5, 4),
(30, 'Semester 6', 6, 4),
(31, 'Semester 7', 7, 4),
(32, 'Semester 8', 8, 4),
(33, 'Semester 1', 1, 5),
(34, 'Semester 2', 2, 5),
(35, 'Semester 3', 3, 5),
(36, 'Semester 4', 4, 5),
(37, 'Semester 5', 5, 5),
(38, 'Semester 6', 6, 5),
(39, 'Semester 7', 7, 5),
(40, 'Semester 8', 8, 5);

-- --------------------------------------------------------

--
-- Table structure for table `semesters_backup_2026`
--

CREATE TABLE `semesters_backup_2026` (
  `semester_id` int(11) NOT NULL DEFAULT 0,
  `semester_name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `semester_number` tinyint(4) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `semesters_backup_2026`
--

INSERT INTO `semesters_backup_2026` (`semester_id`, `semester_name`, `semester_number`, `department_id`) VALUES
(1, 'Semester 1', 1, 1),
(2, 'Semester 2', 2, 1),
(3, 'Semester 3', 3, 1),
(4, 'Semester 4', 4, 1),
(5, 'Semester 5', 5, 1),
(6, 'Semester 6', 6, 1),
(7, 'Semester 7', 7, 1),
(8, 'Semester 8', 8, 1),
(9, 'Semester 1', 1, 2),
(10, 'Semester 2', 2, 2),
(11, 'Semester 3', 3, 2),
(12, 'Semester 4', 4, 2),
(13, 'Semester 5', 5, 2),
(14, 'Semester 6', 6, 2),
(15, 'Semester 7', 7, 2),
(16, 'Semester 8', 8, 2),
(17, 'Semester 1', 1, 3),
(18, 'Semester 2', 2, 3),
(19, 'Semester 3', 3, 3),
(20, 'Semester 4', 4, 3),
(21, 'Semester 5', 5, 3),
(22, 'Semester 6', 6, 3),
(23, 'Semester 7', 7, 3),
(24, 'Semester 8', 8, 3),
(25, 'Semester 1', 1, 4),
(26, 'Semester 2', 2, 4),
(27, 'Semester 3', 3, 4),
(28, 'Semester 4', 4, 4),
(29, 'Semester 5', 5, 4),
(30, 'Semester 6', 6, 4),
(31, 'Semester 7', 7, 4),
(32, 'Semester 8', 8, 4),
(33, 'Semester 1', 1, 5),
(34, 'Semester 2', 2, 5),
(35, 'Semester 3', 3, 5),
(36, 'Semester 4', 4, 5),
(37, 'Semester 5', 5, 5),
(38, 'Semester 6', 6, 5),
(39, 'Semester 7', 7, 5),
(40, 'Semester 8', 8, 5);

-- --------------------------------------------------------

--
-- Table structure for table `semester_courses`
--

CREATE TABLE `semester_courses` (
  `id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semester_courses`
--

INSERT INTO `semester_courses` (`id`, `semester_id`, `course_id`) VALUES
(1, 6, 14),
(2, 7, 15),
(3, 8, 16),
(4, 9, 17),
(5, 10, 18),
(6, 11, 19),
(7, 12, 20),
(8, 13, 21),
(9, 14, 22),
(10, 15, 13);

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE `sessions` (
  `session_id` int(11) NOT NULL,
  `session_name` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sessions`
--

INSERT INTO `sessions` (`session_id`, `session_name`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'Spring 2026', '2026-01-01', '2026-06-30', 'Active', '2026-07-26 07:00:50'),
(5, 'Fall 2026', '2026-07-01', '2026-12-31', 'Active', '2026-07-26 07:10:32'),
(6, 'Spring 2027', '2027-01-01', '2027-06-30', 'Active', '2026-07-26 07:10:32');

-- --------------------------------------------------------

--
-- Table structure for table `sso_applications`
--

CREATE TABLE `sso_applications` (
  `application_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `application_type` enum('Leave','Course Withdrawal','Semester Freeze','Transcript','Bonafide Certificate','ID Card','Other') NOT NULL,
  `subject` varchar(150) NOT NULL,
  `description` varchar(500) DEFAULT NULL,
  `attachment_path` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `remarks` varchar(255) DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `roll_no` varchar(30) DEFAULT NULL,
  `full_name` varchar(150) NOT NULL,
  `father_name` varchar(150) NOT NULL,
  `cnic_or_bform` varchar(20) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `contact_no` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `program_id` int(11) NOT NULL,
  `admission_session_id` int(11) NOT NULL,
  `current_session_id` int(11) NOT NULL,
  `current_semester_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `batch_year` smallint(6) NOT NULL,
  `admission_date` date NOT NULL,
  `status` enum('Active','Freeze','Graduated','Dropped','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `application_id`, `roll_no`, `full_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `admission_session_id`, `current_session_id`, `current_semester_id`, `section_id`, `batch_year`, `admission_date`, `status`, `created_at`, `updated_at`, `user_id`, `semester`) VALUES
(25, 100, '2024-2-025', 'Ahmed Ali', 'Father of Ahmed Ali', '42101-1234567-25', '2003-05-15', 'Male', '0300-1000025', 'stu100@uni.edu', 'Lahore', 2, 6, 6, 9, 10, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(26, 101, '2024-3-026', 'Sara Butt', 'Father of Sara Butt', '42101-1234567-26', '2003-05-15', 'Male', '0300-1000026', 'stu101@uni.edu', 'Lahore', 3, 5, 5, 10, 22, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(27, 102, '2024-4-027', 'Usman Khan', 'Father of Usman Khan', '42101-1234567-27', '2003-05-15', 'Male', '0300-1000027', 'stu102@uni.edu', 'Lahore', 4, 6, 6, 11, 25, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 2),
(28, 103, '2024-5-028', 'Hira Ahmed', 'Father of Hira Ahmed', '42101-1234567-28', '2003-05-15', 'Male', '0300-1000028', 'stu103@uni.edu', 'Lahore', 5, 5, 5, 12, 28, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(29, 104, '2024-6-029', 'Bilal Hussain', 'Father of Bilal Hussain', '42101-1234567-29', '2003-05-15', 'Male', '0300-1000029', 'stu104@uni.edu', 'Lahore', 6, 6, 6, 13, 31, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(30, 105, '2024-2-030', 'Zainab Noor', 'Father of Zainab Noor', '42101-1234567-30', '2003-05-15', 'Male', '0300-1000030', 'stu105@uni.edu', 'Lahore', 2, 5, 5, 14, 11, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(31, 106, '2024-3-031', 'Muhammad Umer', 'Father of Muhammad Umer', '42101-1234567-31', '2003-05-15', 'Male', '0300-1000031', 'stu106@uni.edu', 'Lahore', 3, 6, 6, 15, 23, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(32, 107, '2024-4-032', 'Ayesha Siddiqui', 'Father of Ayesha Siddiqui', '42101-1234567-32', '2003-05-15', 'Male', '0300-1000032', 'stu107@uni.edu', 'Lahore', 4, 5, 5, 16, 26, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(33, 108, '2024-5-033', 'Farhan Iqbal', 'Father of Farhan Iqbal', '42101-1234567-33', '2003-05-15', 'Male', '0300-1000033', 'stu108@uni.edu', 'Lahore', 5, 6, 6, 9, 29, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(34, 109, '2024-6-034', 'Maryam Khalid', 'Father of Maryam Khalid', '42101-1234567-34', '2003-05-15', 'Male', '0300-1000034', 'stu109@uni.edu', 'Lahore', 6, 5, 5, 10, 32, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(35, 110, '2024-2-035', 'Waleed Aslam', 'Father of Waleed Aslam', '42101-1234567-35', '2003-05-15', 'Male', '0300-1000035', 'stu110@uni.edu', 'Lahore', 2, 6, 6, 11, 12, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(36, 111, '2024-3-036', 'Nida Butt', 'Father of Nida Butt', '42101-1234567-36', '2003-05-15', 'Male', '0300-1000036', 'stu111@uni.edu', 'Lahore', 3, 5, 5, 12, 24, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(37, 112, '2024-4-037', 'Student 37', 'Father of Student 37', '42101-1234567-37', '2003-05-15', 'Male', '0300-1000037', 'stu112@uni.edu', 'Lahore', 4, 6, 6, 13, 27, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-31 20:10:57', NULL, 1),
(41, 116, 'WAR-2026-0445', 'Wareesha', 'Khan', '73652362536257', '2022-10-22', 'Female', '632326613', 'wareesha@gmai.com', 'peshawar', 2, 5, 5, 5, NULL, 2026, '2026-07-28', 'Active', '2026-07-28 05:57:46', '2026-07-28 05:57:46', NULL, 1),
(43, 130, '2026-2-042', 'farah', 'khan', '78327823783282', '2000-10-01', 'Female', '03782182717821', 'farahkhan@gmail.com', 'peshawar', 2, 5, 5, 1, 3, 2026, '2026-07-31', 'Active', '2026-07-31 17:21:37', '2026-07-31 20:29:06', 61, 1),
(44, 910, '2026-5-044', 'Ali Raza', 'Raza Ahmed', '42101-1234567-1', '2004-03-15', 'Male', '0300-1110001', 'ali.raza@demo.edu', NULL, 5, 5, 5, 1, 28, 2026, '2026-08-01', 'Active', '2026-07-31 20:36:00', '2026-07-31 20:37:55', 62, 1),
(45, 911, '2026-5-045', 'Sana Malik', 'Malik Hussain', '42201-2345678-2', '2005-07-20', 'Female', '0300-1110002', 'sana.malik@demo.edu', NULL, 5, 5, 5, 1, 29, 2026, '2026-08-01', 'Active', '2026-07-31 20:36:00', '2026-07-31 20:49:01', 63, 1);

-- --------------------------------------------------------

--
-- Table structure for table `student_admission_details`
--

CREATE TABLE `student_admission_details` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `session_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `scholarship_id` int(11) DEFAULT NULL,
  `admission_date` date NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `admission_year` year(4) NOT NULL,
  `roll_number` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `selected_fee_ids` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Dropped') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`id`, `student_id`, `course_id`, `enrollment_date`, `status`, `created_at`) VALUES
(1149, 'STD026', 14, NULL, 'Active', '2026-07-15 05:30:00'),
(1150, 'STD027', 15, NULL, 'Active', '2026-07-15 05:30:00'),
(1151, 'STD028', 16, NULL, 'Active', '2026-07-15 05:30:00'),
(1152, 'STD029', 17, NULL, 'Active', '2026-07-15 05:30:00'),
(1153, 'STD030', 18, NULL, 'Active', '2026-07-15 05:30:00'),
(1154, 'STD031', 19, NULL, 'Active', '2026-07-15 05:30:00'),
(1155, 'STD032', 20, NULL, 'Active', '2026-07-15 05:30:00'),
(1156, 'STD033', 21, NULL, 'Active', '2026-07-15 05:30:00'),
(1157, 'STD034', 22, NULL, 'Active', '2026-07-15 05:30:00'),
(1158, 'STD035', 13, NULL, 'Active', '2026-07-15 05:30:00'),
(1164, '2026-2-042', 44, '2026-08-01', 'Active', '2026-07-31 19:17:07'),
(1165, '2026-2-042', 46, '2026-08-01', 'Active', '2026-07-31 19:17:07'),
(1168, '2026-2-042', 30, '2026-08-01', 'Active', '2026-07-31 20:28:43'),
(1170, '2026-5-044', 54, '2026-08-01', 'Active', '2026-07-31 20:38:05'),
(1171, '2026-5-044', 55, '2026-08-01', 'Active', '2026-07-31 20:38:05'),
(1172, '2026-5-045', 54, '2026-08-01', 'Active', '2026-07-31 20:38:05'),
(1173, '2026-5-045', 56, '2026-08-01', 'Active', '2026-07-31 20:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `student_course_allocation`
--

CREATE TABLE `student_course_allocation` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `credit_hours` int(11) DEFAULT 3,
  `semester` int(11) DEFAULT 1,
  `allocated_by` int(11) DEFAULT NULL,
  `allocated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_course_allocation`
--

INSERT INTO `student_course_allocation` (`id`, `application_id`, `course_id`, `course_code`, `course_name`, `credit_hours`, `semester`, `allocated_by`, `allocated_at`) VALUES
(5, '130', 44, 'CS-P1-S1-02', 'Discrete Mathematics', 3, 1, 14, '2026-08-01 00:17:07'),
(6, '130', 46, 'CS-P1-S2-01', 'Object Oriented Programming', 3, 1, 14, '2026-08-01 00:17:07'),
(9, '130', 30, 'CS102', 'Object Oriented Programming', 3, 1, 10, '2026-08-01 01:28:43'),
(11, '910', 54, 'AI-P1-S1-01', 'AI Fundamentals', 3, 1, 1, '2026-08-01 01:38:05'),
(12, '910', 55, 'AI-P1-S1-02', 'Python Programming', 3, 1, 1, '2026-08-01 01:38:05'),
(13, '911', 54, 'AI-P1-S1-01', 'AI Fundamentals', 3, 1, 1, '2026-08-01 01:38:05'),
(14, '911', 56, 'AI-P1-S2-01', 'Machine Learning Basics', 3, 1, 1, '2026-08-01 01:38:05'),
(15, '912', 54, 'AI-P1-S1-01', 'AI Fundamentals', 3, 1, 1, '2026-08-01 01:38:05'),
(16, '913', 55, 'AI-P1-S1-02', 'Python Programming', 3, 1, 1, '2026-08-01 01:38:05');

-- --------------------------------------------------------

--
-- Table structure for table `student_course_structure`
--

CREATE TABLE `student_course_structure` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `course_code` varchar(50) NOT NULL,
  `course_name` varchar(200) NOT NULL,
  `credit_hours` int(11) DEFAULT 3,
  `semester` int(11) DEFAULT 1,
  `is_elective` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` varchar(50) NOT NULL,
  `course_id` int(11) NOT NULL,
  `section_id` int(11) DEFAULT NULL,
  `enrollment_date` date DEFAULT NULL,
  `status` enum('Active','Completed','Dropped') DEFAULT 'Active',
  `grade` varchar(5) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`enrollment_id`, `student_id`, `course_id`, `section_id`, `enrollment_date`, `status`, `grade`, `created_at`) VALUES
(1, 'STD026', 14, 1, '0000-00-00', 'Active', 'B', '2026-07-15 05:30:00'),
(2, 'STD027', 15, 2, '0000-00-00', 'Active', 'C', '2026-07-15 05:30:00'),
(3, 'STD028', 16, 3, '0000-00-00', 'Active', 'D', '2026-07-15 05:30:00'),
(4, 'STD029', 17, 4, '0000-00-00', 'Active', 'F', '2026-07-15 05:30:00'),
(5, 'STD030', 18, 5, '0000-00-00', 'Active', 'A', '2026-07-15 05:30:00'),
(6, 'STD031', 19, 6, '0000-00-00', 'Active', 'B', '2026-07-15 05:30:00'),
(7, 'STD032', 20, 7, '0000-00-00', 'Active', 'C', '2026-07-15 05:30:00'),
(8, 'STD033', 21, 8, '0000-00-00', 'Active', 'D', '2026-07-15 05:30:00'),
(9, 'STD034', 22, 9, '0000-00-00', 'Active', 'F', '2026-07-15 05:30:00'),
(10, 'STD035', 13, 10, '0000-00-00', 'Active', 'A', '2026-07-15 05:30:00'),
(11, 'STD036', 14, 11, '0000-00-00', 'Active', 'B', '2026-07-15 05:30:00'),
(12, 'STD037', 15, 12, '0000-00-00', 'Active', 'C', '2026-07-15 05:30:00'),
(13, 'STD038', 16, 13, '0000-00-00', 'Active', 'D', '2026-07-15 05:30:00'),
(14, 'STD039', 17, 14, '0000-00-00', 'Active', 'F', '2026-07-15 05:30:00'),
(15, 'STD040', 18, 15, '0000-00-00', 'Active', 'A', '2026-07-15 05:30:00'),
(49, 'STD035', 22, 7, '2026-07-27', 'Active', 'A', '2026-07-27 21:09:29');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee`
--

CREATE TABLE `student_fee` (
  `student_fee_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) GENERATED ALWAYS AS (`total_amount` - `paid_amount`) STORED,
  `due_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') NOT NULL DEFAULT 'Unpaid',
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_fee`
--

INSERT INTO `student_fee` (`student_fee_id`, `student_id`, `semester_id`, `session_id`, `fee_structure_id`, `total_amount`, `paid_amount`, `due_date`, `status`, `generated_by`, `generated_at`) VALUES
(13, 25, 2, 5, 19, 64000.00, 50020.00, '2026-08-26', 'Partially Paid', 9, '2026-07-27 20:04:49'),
(14, 31, 2, 6, 20, 64000.00, 40.00, '2026-08-26', 'Partially Paid', 9, '2026-07-27 20:06:11'),
(15, 25, 5, 1, 93, 55000.00, 100.00, '2026-08-27', 'Partially Paid', 9, '2026-07-28 05:55:32'),
(18, 31, 1, 1, 0, 63000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:05:56'),
(19, 36, 1, 1, 0, 63000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:05:56'),
(20, 26, 1, 1, 0, 63000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:05:56'),
(22, 25, 0, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:20'),
(23, 35, 0, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:20'),
(24, 41, 0, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:20'),
(25, 30, 0, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:20'),
(26, 25, 4, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:43'),
(27, 35, 4, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:43'),
(28, 41, 4, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:43'),
(29, 30, 4, 5, 0, 1000.00, 0.00, '2026-08-29', 'Unpaid', 9, '2026-07-30 18:36:43');

-- --------------------------------------------------------

--
-- Table structure for table `student_fees`
--

CREATE TABLE `student_fees` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fees`
--

INSERT INTO `student_fees` (`id`, `student_id`, `total_amount`, `paid_amount`, `created_at`) VALUES
(1, 9824, 64000.00, 50000.00, '2026-07-30 22:15:02');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_allocation`
--

CREATE TABLE `student_fee_allocation` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `fee_id` int(11) NOT NULL,
  `fee_head` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `allocated_by` int(11) DEFAULT NULL,
  `allocated_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_assignment`
--

CREATE TABLE `student_fee_assignment` (
  `assignment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_fee_assignment`
--

INSERT INTO `student_fee_assignment` (`assignment_id`, `student_id`, `fee_structure_id`, `semester_id`, `assigned_by`, `assigned_at`) VALUES
(1, 0, 2, 6, 2, '0000-00-00 00:00:00'),
(2, 0, 3, 7, 1, '0000-00-00 00:00:00'),
(3, 0, 4, 8, 2, '0000-00-00 00:00:00'),
(4, 0, 5, 9, 1, '0000-00-00 00:00:00'),
(5, 0, 6, 10, 2, '0000-00-00 00:00:00'),
(6, 0, 7, 11, 1, '0000-00-00 00:00:00'),
(7, 0, 8, 12, 2, '0000-00-00 00:00:00'),
(8, 0, 9, 13, 1, '0000-00-00 00:00:00'),
(9, 0, 10, 14, 2, '0000-00-00 00:00:00'),
(10, 0, 1, 15, 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_backup_2026`
--

CREATE TABLE `student_fee_backup_2026` (
  `student_fee_id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(12,2) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Unpaid',
  `generated_by` int(11) NOT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fee_backup_2026`
--

INSERT INTO `student_fee_backup_2026` (`student_fee_id`, `student_id`, `semester_id`, `session_id`, `fee_structure_id`, `total_amount`, `paid_amount`, `remaining_amount`, `due_date`, `status`, `generated_by`, `generated_at`) VALUES
(1, 0, 6, 6, 2, 5000.00, 2500.00, 2500.00, '2026-09-02', 'Partially Paid', 2, '0000-00-00 00:00:00'),
(2, 0, 7, 7, 3, 5000.00, 2500.00, 2500.00, '2026-09-03', 'Partially Paid', 1, '0000-00-00 00:00:00'),
(3, 0, 8, 5, 4, 5000.00, 3000.00, 2000.00, '2026-09-04', '', 2, '0000-00-00 00:00:00'),
(4, 0, 9, 6, 5, 5000.00, 2500.00, 2500.00, '2026-09-05', 'Partially Paid', 1, '0000-00-00 00:00:00'),
(5, 0, 10, 7, 6, 5000.00, 3000.00, 2000.00, '2026-09-06', '', 2, '0000-00-00 00:00:00'),
(6, 0, 11, 5, 7, 5000.00, 3000.00, 2000.00, '2026-09-07', '', 1, '0000-00-00 00:00:00'),
(7, 0, 12, 6, 8, 5000.00, 3000.00, 2000.00, '2026-09-08', '', 2, '0000-00-00 00:00:00'),
(8, 0, 13, 7, 9, 5000.00, 3000.00, 2000.00, '2026-09-09', '', 1, '0000-00-00 00:00:00'),
(9, 0, 14, 5, 10, 5000.00, 3000.00, 2000.00, '2026-09-10', '', 2, '0000-00-00 00:00:00'),
(10, 0, 15, 6, 1, 5000.00, 3000.00, 2000.00, '2026-09-11', '', 1, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_details`
--

CREATE TABLE `student_fee_details` (
  `id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `fee_head_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_amount` decimal(10,2) GENERATED ALWAYS AS (`amount` - `discount_amount`) STORED
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_fee_details`
--

INSERT INTO `student_fee_details` (`id`, `student_fee_id`, `fee_head_id`, `amount`, `discount_amount`) VALUES
(19, 13, 1, 50000.00, 0.00),
(20, 13, 2, 4000.00, 0.00),
(21, 13, 3, 3000.00, 0.00),
(22, 13, 4, 5000.00, 0.00),
(23, 13, 5, 2000.00, 0.00),
(24, 14, 1, 50000.00, 0.00),
(25, 14, 2, 4000.00, 0.00),
(26, 14, 3, 3000.00, 0.00),
(27, 14, 4, 5000.00, 0.00),
(28, 14, 5, 2000.00, 0.00),
(29, 15, 1, 9166.67, 0.00),
(30, 15, 2, 9166.67, 0.00),
(31, 15, 3, 9166.67, 0.00),
(32, 15, 4, 9166.67, 0.00),
(33, 15, 5, 9166.67, 0.00),
(34, 15, 6, 9166.67, 0.00),
(37, 18, 6, 8000.00, 0.00),
(38, 18, 12, 55000.00, 0.00),
(39, 19, 6, 8000.00, 0.00),
(40, 19, 12, 55000.00, 0.00),
(41, 20, 6, 8000.00, 0.00),
(42, 20, 12, 55000.00, 0.00),
(43, 22, 1, 0.00, 0.00),
(44, 22, 2, 0.00, 0.00),
(45, 22, 3, 0.00, 0.00),
(46, 22, 4, 0.00, 0.00),
(47, 22, 5, 0.00, 0.00),
(48, 22, 6, 0.00, 0.00),
(49, 22, 7, 1000.00, 0.00),
(50, 23, 1, 0.00, 0.00),
(51, 23, 2, 0.00, 0.00),
(52, 23, 3, 0.00, 0.00),
(53, 23, 4, 0.00, 0.00),
(54, 23, 5, 0.00, 0.00),
(55, 23, 6, 0.00, 0.00),
(56, 23, 7, 1000.00, 0.00),
(57, 24, 1, 0.00, 0.00),
(58, 24, 2, 0.00, 0.00),
(59, 24, 3, 0.00, 0.00),
(60, 24, 4, 0.00, 0.00),
(61, 24, 5, 0.00, 0.00),
(62, 24, 6, 0.00, 0.00),
(63, 24, 7, 1000.00, 0.00),
(64, 25, 1, 0.00, 0.00),
(65, 25, 2, 0.00, 0.00),
(66, 25, 3, 0.00, 0.00),
(67, 25, 4, 0.00, 0.00),
(68, 25, 5, 0.00, 0.00),
(69, 25, 6, 0.00, 0.00),
(70, 25, 7, 1000.00, 0.00),
(71, 26, 1, 0.00, 0.00),
(72, 26, 2, 0.00, 0.00),
(73, 26, 3, 0.00, 0.00),
(74, 26, 4, 0.00, 0.00),
(75, 26, 5, 0.00, 0.00),
(76, 26, 6, 0.00, 0.00),
(77, 26, 7, 1000.00, 0.00),
(78, 27, 1, 0.00, 0.00),
(79, 27, 2, 0.00, 0.00),
(80, 27, 3, 0.00, 0.00),
(81, 27, 4, 0.00, 0.00),
(82, 27, 5, 0.00, 0.00),
(83, 27, 6, 0.00, 0.00),
(84, 27, 7, 1000.00, 0.00),
(85, 28, 1, 0.00, 0.00),
(86, 28, 2, 0.00, 0.00),
(87, 28, 3, 0.00, 0.00),
(88, 28, 4, 0.00, 0.00),
(89, 28, 5, 0.00, 0.00),
(90, 28, 6, 0.00, 0.00),
(91, 28, 7, 1000.00, 0.00),
(92, 29, 1, 0.00, 0.00),
(93, 29, 2, 0.00, 0.00),
(94, 29, 3, 0.00, 0.00),
(95, 29, 4, 0.00, 0.00),
(96, 29, 5, 0.00, 0.00),
(97, 29, 6, 0.00, 0.00),
(98, 29, 7, 1000.00, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_discounts`
--

CREATE TABLE `student_fee_discounts` (
  `discount_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `fee_head_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `action_type` enum('Add','Remove','Reduce') NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `applied_by` int(11) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_fee_discounts`
--

INSERT INTO `student_fee_discounts` (`discount_id`, `student_id`, `fee_head_id`, `semester_id`, `action_type`, `amount`, `reason`, `applied_by`, `applied_at`) VALUES
(3, 0, 2, 6, '', 500.00, 'Refund for transaction 1', 2, '2026-07-15 05:30:00'),
(4, 0, 3, 7, '', 500.00, 'Refund for transaction 2', 1, '2026-07-15 05:30:00'),
(5, 0, 4, 8, '', 500.00, 'Refund for transaction 3', 2, '2026-07-15 05:30:00'),
(6, 0, 5, 9, '', 500.00, 'Refund for transaction 4', 1, '2026-07-15 05:30:00'),
(7, 0, 1, 10, '', 500.00, 'Refund for transaction 5', 2, '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_items`
--

CREATE TABLE `student_fee_items` (
  `id` int(11) NOT NULL,
  `student_fee_id` int(11) NOT NULL,
  `fee_structure_id` int(11) NOT NULL,
  `fee_type` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_fee_items`
--

INSERT INTO `student_fee_items` (`id`, `student_fee_id`, `fee_structure_id`, `fee_type`, `amount`) VALUES
(1, 1, 1, 'Tuition Fee', 45000.00),
(2, 1, 2, 'Transport Fee', 15000.00),
(3, 1, 3, 'Library Fee', 4000.00);

-- --------------------------------------------------------

--
-- Table structure for table `student_fee_structure`
--

CREATE TABLE `student_fee_structure` (
  `id` int(11) NOT NULL,
  `application_id` varchar(50) NOT NULL,
  `fee_head_id` int(11) NOT NULL,
  `fee_head` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `is_mandatory` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_grades`
--

CREATE TABLE `student_grades` (
  `grade_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester` varchar(20) DEFAULT NULL,
  `academic_year` varchar(20) DEFAULT NULL,
  `total_credits` decimal(5,2) DEFAULT NULL,
  `earned_credits` decimal(5,2) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `cgpa` decimal(3,2) DEFAULT NULL,
  `status` enum('active','completed','probation') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_grades`
--

INSERT INTO `student_grades` (`grade_id`, `student_id`, `course_id`, `semester`, `academic_year`, `total_credits`, `earned_credits`, `gpa`, `cgpa`, `status`, `created_at`, `updated_at`) VALUES
(1, 0, 14, '2', '2025-2026', 0.00, 0.00, 2.10, 2.10, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(2, 0, 15, '3', '2025-2026', 0.00, 0.00, 2.20, 2.20, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(3, 0, 16, '4', '2025-2026', 0.00, 0.00, 2.30, 2.30, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(4, 0, 17, '5', '2025-2026', 0.00, 0.00, 2.40, 2.40, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(5, 0, 18, '6', '2025-2026', 0.00, 0.00, 2.50, 2.50, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(6, 0, 19, '7', '2025-2026', 0.00, 0.00, 2.60, 2.60, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(7, 0, 20, '8', '2025-2026', 0.00, 0.00, 2.70, 2.70, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(8, 0, 21, '1', '2025-2026', 0.00, 0.00, 2.80, 2.80, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(9, 0, 22, '2', '2025-2026', 0.00, 0.00, 2.90, 2.90, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(10, 0, 13, '3', '2025-2026', 0.00, 0.00, 3.00, 3.00, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_promotions`
--

CREATE TABLE `student_promotions` (
  `promotion_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_semester` varchar(20) DEFAULT NULL,
  `to_semester` varchar(20) DEFAULT NULL,
  `from_academic_year` varchar(20) DEFAULT NULL,
  `to_academic_year` varchar(20) DEFAULT NULL,
  `promotion_date` date DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_promotions`
--

INSERT INTO `student_promotions` (`promotion_id`, `student_id`, `from_semester`, `to_semester`, `from_academic_year`, `to_academic_year`, `promotion_date`, `status`, `approved_by`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 0, '1', '2', '2024-2025', '2025-2026', '2026-08-01', '', 2, 'Remark 1', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(2, 0, '1', '2', '2024-2025', '2025-2026', '2026-08-01', '', 1, 'Remark 2', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(3, 0, '1', '2', '2024-2025', '2025-2026', '2026-08-01', '', 2, 'Remark 3', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(4, 0, '1', '2', '2024-2025', '2025-2026', '2026-08-01', '', 1, 'Remark 4', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(5, 0, '1', '2', '2024-2025', '2025-2026', '2026-08-01', '', 2, 'Remark 5', '2026-07-15 05:30:00', '2026-07-15 05:30:00');

-- --------------------------------------------------------

--
-- Table structure for table `student_schedule_requests`
--

CREATE TABLE `student_schedule_requests` (
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `admission_student_id` varchar(60) DEFAULT NULL,
  `roll_no` varchar(30) DEFAULT NULL,
  `student_name` varchar(150) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `conflict_type` varchar(60) DEFAULT NULL,
  `current_timetable` varchar(600) DEFAULT NULL,
  `conflict_description` text DEFAULT NULL,
  `requested_solution` text DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected','Forwarded') DEFAULT 'Pending',
  `rejection_reason` varchar(500) DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_schedule_requests`
--

INSERT INTO `student_schedule_requests` (`id`, `student_id`, `admission_student_id`, `roll_no`, `student_name`, `department_id`, `program_id`, `session_id`, `semester_id`, `course_id`, `conflict_type`, `current_timetable`, `conflict_description`, `requested_solution`, `status`, `rejection_reason`, `reviewed_by`, `reviewed_at`, `created_at`) VALUES
(1, 44, '910', '2026-5-044', 'Ali Raza', 5, 5, 5, 1, 54, 'Time Conflict', '08:00 AM-09:00 AM Tuesday AI-P1-S1-01 (Room 104); 09:00 AM-10:00 AM Tuesday AI-P1-S1-02 (Room 105); 10:00 AM-11:00 AM Tuesday AI-P1-S2-01 (Lab-01)', 'Class clashes with my part-time job on Tuesday mornings.', 'Please move AI-P1-S1-01 to Wednesday afternoon.', 'Approved', NULL, 10, '2026-08-01 02:15:20', '2026-07-31 21:15:01'),
(2, 45, '911', '2026-5-045', 'Sana Malik', 5, 5, 5, 1, 55, 'Room Conflict', '', 'Room too far from my other class.', '', 'Rejected', 'Room is assigned to another lab batch.', 10, '2026-08-01 02:15:37', '2026-07-31 21:15:36'),
(3, 43, '130', '2026-2-042', 'farah', 2, 2, 5, 1, 43, 'Other', '', 'Would like a later time slot.', 'Move to afternoon.', 'Forwarded', NULL, 10, '2026-08-01 02:15:37', '2026-07-31 21:15:37');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `salary` decimal(12,2) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `teacher_name`, `designation`, `salary`, `email`, `phone`, `department_id`, `status`, `created_at`) VALUES
(1, 19, 'Dr. Sara Khan', 'Professor', NULL, 'sara.khan@university.edu', NULL, 1, 'Active', '2026-07-26 18:14:23'),
(2, 20, 'Teacher Demo', 'Lecturer', NULL, 'teacher.demo@university.edu', NULL, 1, 'Active', '2026-07-26 18:14:23'),
(8, NULL, 'njkjnkj', NULL, NULL, 'ksnc@gmail.com', '278328237', 2, 'Active', '2026-07-27 21:25:22');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_courses`
--

CREATE TABLE `teacher_courses` (
  `id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `section` varchar(5) NOT NULL DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`id`, `teacher_id`, `course_id`, `semester_id`, `session_id`, `section`) VALUES
(1, 1, 13, 9, 5, 'A'),
(3, 1, 15, 11, 5, 'A'),
(4, 1, 16, 12, 5, 'A'),
(5, 1, 17, 13, 5, 'A'),
(6, 1, 18, 14, 5, 'A'),
(7, 1, 19, 15, 5, 'A'),
(8, 1, 20, 16, 5, 'A'),
(9, 1, 21, 9, 5, 'A'),
(10, 1, 22, 10, 5, 'A'),
(11, 1, 23, 11, 5, 'A'),
(12, 1, 24, 12, 5, 'A'),
(85, 8, 21, 35, 6, 'ahlkc');

-- --------------------------------------------------------

--
-- Table structure for table `timetable`
--

CREATE TABLE `timetable` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_no` varchar(30) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `section` varchar(5) NOT NULL DEFAULT 'A'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `course_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_no`, `semester_id`, `session_id`, `section`) VALUES
(1, 14, 2, 'Monday', '09:00:00', '09:00:00', 'Room 101', 6, 6, 'A'),
(2, 15, 1, 'Monday', '09:00:00', '09:00:00', 'Room 102', 7, 7, 'A'),
(3, 16, 2, 'Monday', '09:00:00', '09:00:00', 'Room 103', 8, 5, 'A'),
(4, 17, 1, 'Monday', '09:00:00', '09:00:00', 'Room 104', 9, 6, 'A'),
(5, 18, 2, 'Monday', '09:00:00', '09:00:00', 'Room 105', 10, 7, 'A'),
(6, 19, 1, 'Monday', '09:00:00', '09:00:00', 'Room 106', 11, 5, 'A'),
(7, 20, 2, 'Monday', '09:00:00', '09:00:00', 'Room 107', 12, 6, 'A'),
(8, 21, 1, 'Monday', '09:00:00', '09:00:00', 'Room 108', 13, 7, 'A'),
(9, 22, 2, 'Monday', '09:00:00', '09:00:00', 'Room 109', 14, 5, 'A'),
(10, 13, 1, 'Monday', '09:00:00', '09:00:00', 'Room 110', 15, 6, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `timetables`
--

CREATE TABLE `timetables` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `section` varchar(5) NOT NULL DEFAULT 'A',
  `status` enum('Draft','Pending Review','Approved','Published') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `published_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetables`
--

INSERT INTO `timetables` (`id`, `department_id`, `program_id`, `session_id`, `semester_id`, `section`, `status`, `created_by`, `published_at`, `created_at`, `updated_at`) VALUES
(2, 2, 2, 5, 9, 'A', 'Published', 10, '2026-07-31 23:10:01', '2026-07-31 21:09:21', '2026-07-31 21:10:01'),
(3, 5, 5, 5, 1, 'A', 'Published', 10, '2026-07-31 23:14:19', '2026-07-31 21:14:08', '2026-07-31 21:14:19');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_adjustments`
--

CREATE TABLE `timetable_adjustments` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `timetable_id` int(11) DEFAULT NULL,
  `field_changed` varchar(50) DEFAULT NULL,
  `old_value` varchar(255) DEFAULT NULL,
  `new_value` varchar(255) DEFAULT NULL,
  `reason` varchar(500) DEFAULT NULL,
  `adjusted_by` int(11) DEFAULT NULL,
  `adjusted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_adjustments`
--

INSERT INTO `timetable_adjustments` (`id`, `entry_id`, `timetable_id`, `field_changed`, `old_value`, `new_value`, `reason`, `adjusted_by`, `adjusted_at`) VALUES
(8, 2, 2, 'Day', 'Tuesday', 'Wednesday', 'Valid move', 10, '2026-07-31 21:13:26'),
(9, 2, 2, 'Start Time', '08:00:00', '14:00', 'Valid move', 10, '2026-07-31 21:13:26'),
(10, 2, 2, 'End Time', '09:00:00', '15:00', 'Valid move', 10, '2026-07-31 21:13:26');

-- --------------------------------------------------------

--
-- Table structure for table `timetable_conflicts`
--

CREATE TABLE `timetable_conflicts` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) DEFAULT NULL,
  `conflict_type` enum('Student','Teacher','Room') NOT NULL,
  `description` varchar(600) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `room_id` int(11) DEFAULT NULL,
  `day_of_week` varchar(20) DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `status` enum('Open','Resolved','Ignored') DEFAULT 'Open',
  `detected_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `timetable_courses`
--

CREATE TABLE `timetable_courses` (
  `id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `credit_hours` tinyint(4) DEFAULT 3
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_courses`
--

INSERT INTO `timetable_courses` (`id`, `timetable_id`, `course_id`, `credit_hours`) VALUES
(2, 2, 43, 3),
(3, 2, 44, 3),
(4, 2, 45, 3),
(5, 3, 54, 3),
(6, 3, 55, 3),
(7, 3, 56, 3);

-- --------------------------------------------------------

--
-- Table structure for table `timetable_entries`
--

CREATE TABLE `timetable_entries` (
  `id` int(11) NOT NULL,
  `timetable_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `room_id` int(11) DEFAULT NULL,
  `section` varchar(5) DEFAULT 'A',
  `status` enum('Draft','Approved','Published') DEFAULT 'Draft',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `timetable_entries`
--

INSERT INTO `timetable_entries` (`id`, `timetable_id`, `course_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_id`, `section`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(2, 2, 43, 8, 'Wednesday', '14:00:00', '15:00:00', 1, 'A', 'Approved', 10, '2026-07-31 21:09:21', '2026-07-31 21:13:26'),
(3, 2, 44, 8, 'Monday', '09:00:00', '10:00:00', 2, 'A', 'Published', 10, '2026-07-31 21:09:21', '2026-07-31 21:10:01'),
(4, 2, 45, 8, 'Monday', '10:00:00', '11:00:00', 3, 'A', 'Published', 10, '2026-07-31 21:09:21', '2026-07-31 21:10:01'),
(5, 3, 54, 1, 'Tuesday', '08:00:00', '09:00:00', 4, 'A', 'Published', 10, '2026-07-31 21:14:09', '2026-07-31 21:14:19'),
(6, 3, 55, 1, 'Tuesday', '09:00:00', '10:00:00', 5, 'A', 'Published', 10, '2026-07-31 21:14:09', '2026-07-31 21:14:19'),
(7, 3, 56, 1, 'Tuesday', '10:00:00', '11:00:00', 6, 'A', 'Published', 10, '2026-07-31 21:14:09', '2026-07-31 21:14:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `login_id` varchar(20) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `profile_photo` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `username`, `login_id`, `email`, `phone`, `password_hash`, `role_id`, `department_id`, `profile_photo`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES
(9, 'Finance Officer', 'finance', NULL, 'finance@university.edu', NULL, '$2y$10$0lmWXcoRnEW8RovgW07e/OmoOrIsSqewiz2duyfSkgK32pCOD1IXe', 3, NULL, NULL, 'Active', '2026-07-25 17:45:49', '2026-07-23 03:23:21', '2026-07-30 13:34:45'),
(10, 'Administrator', 'admin', NULL, 'admin@university.edu', NULL, '$2y$10$uajzSmXEbGTv6lzpa0KcnOItiUA./AyaTZhRwL7SKVzJ686BfOFoa', 1, NULL, NULL, 'Active', '2026-07-23 14:47:29', '2026-07-23 03:23:21', '2026-07-30 13:34:16'),
(11, 'Teacher Demo', 'teacher', NULL, 'teacher@university.edu', NULL, '$2y$10$UCi4WDZ/EzbILp6nH.T2yOjIMKtul4yfxRSCvAN2ht3FxwkjLaFZu', 2, NULL, NULL, 'Active', '2026-07-25 15:50:23', '2026-07-23 03:23:21', '2026-07-30 13:38:33'),
(12, 'Student Demo', 'student', NULL, 'student@university.edu', NULL, '$2y$10$y1a4R5R8yCg8rlzgrkonDuvJFnYLcRJoiK/85WPZYNBfKkOqK6YzW', 4, NULL, NULL, 'Active', '2026-07-23 14:45:53', '2026-07-23 03:23:21', '2026-07-30 13:38:33'),
(13, 'Examiner', 'examiner', NULL, 'examiner@university.edu', NULL, '$2y$10$5irR8gaBpzmsQOy3UMnz8.UZXsIB0FGv7ho0H0STPTMQXQWnjUPOi', 6, NULL, NULL, 'Active', NULL, '2026-07-25 12:59:01', '2026-07-30 13:34:45'),
(14, 'SSO Admin', 'sso_admin', NULL, 'sso@university.edu', NULL, '$2y$10$/XVw7LehbAKgFuNsQgLyyuBbySScpJYr8x5eceFxrrWCLDxPic1O2', 1, NULL, NULL, 'Active', NULL, '2026-07-26 10:57:00', '2026-07-26 18:44:00'),
(15, 'Exam Officer', 'exam_admin', NULL, 'exam@university.edu', NULL, '$2y$10$WA2OIYd4t1eNpjzL0rzHae2exiL0qEKe3RLxNHJ8qeGNhJ5qcjQSq', 6, NULL, NULL, 'Active', NULL, '2026-07-26 10:57:00', '2026-07-26 18:44:00'),
(19, 'Dr. Sara Khan', 'sara.khan', '5001', 'sara.khan@university.edu', NULL, '$2y$10$CHpKfvUqpcOBhCES1bCVgO9urK3bhn5o0NOWrqJFUERvDyTHfZ1Mq', 2, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(20, 'Teacher Demo', 'teacher.demo', '5002', 'teacher.demo@university.edu', NULL, '$2y$10$KpNnLcnl140COvIZtgbj8eUhtQIKwRC8iGYBB42jfkE2YXo7ehhLK', 2, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(21, 'Ali Raza', 'ali.raza', '9001', 'ali.raza@university.edu', NULL, '$2y$10$Jf22SiM7FGyoT9BLXIl24uGVuivmpqSdsi.Kyj8r0V1epH3rvfgvS', 4, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(22, 'Student Demo', 'student.demo', '9002', 'student.demo@university.edu', NULL, '$2y$10$JHSLxirVE9m2CKndkrZSmu.FUcYaNjDt.pbep7x.GLpzg7ha/cd2q', 4, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(37, 'Ali', 'ali555', '9546', 'ali@gmail.com', '6157351656152', '$2y$10$QeUNm0s88olTyKleuaWoKOYHk9pGaKctvADVE9EtjBsU5Afjx3PTK', 4, 1, NULL, 'Active', NULL, '2026-07-27 19:36:26', '2026-07-27 19:36:26'),
(47, 'Wareesha', 'wareesha664', '9289', 'wareesha@gmai.com', '632326613', '$2y$10$A3Xw79FSGoGIne6j5pllp.uoWelOpcEeymXTgXHJzR3mQFC.E8Z66', 4, 2, NULL, 'Active', NULL, '2026-07-28 05:57:46', '2026-07-28 05:57:46'),
(50, 'Super Admin', 'superadmin', NULL, 'superadmin@uni.edu', '0300-0000001', '$2y$10$uajzSmXEbGTv6lzpa0KcnOItiUA./AyaTZhRwL7SKVzJ686BfOFoa', 7, NULL, NULL, 'Active', NULL, '2026-07-30 13:33:09', '2026-07-30 13:34:45'),
(51, 'Admission Officer', 'admission', NULL, 'admission@uni.edu', '0300-0000002', '$2y$10$uajzSmXEbGTv6lzpa0KcnOItiUA./AyaTZhRwL7SKVzJ686BfOFoa', 5, NULL, NULL, 'Active', NULL, '2026-07-30 13:33:10', '2026-07-30 13:34:45'),
(52, 'noor', 'noor318', '9969', 'noorjahan@gmail.com', '03754782782', '$2y$10$lfH9r5xJezTOVfZ/x/vDu.h9pbmX0U9kedFzfC0Z2UnhZbGXn6f0C', 4, 1, NULL, 'Active', NULL, '2026-07-30 14:21:59', '2026-07-30 14:21:59'),
(53, 'Sajjal', 'sajjal682', '9416', 'sajjal@gmail.com', '62736268283', '$2y$10$K4FVx6sNUW4vb/E59GZe5uk3f8T/TKK7fe/huwdaw4lQVimELEAd2', 4, 1, NULL, 'Active', NULL, '2026-07-30 14:23:08', '2026-07-30 14:23:08'),
(54, 'aliya', 'aliya152', '10015', 'aliya@gmail.com', '0389121288912', '$2y$10$.USGJkmrBfHsCWsI7Cew7.AGHLVjY2PgvNMqBc9qT1noBWoujWr0q', 4, 2, NULL, 'Active', NULL, '2026-07-30 15:23:22', '2026-07-30 15:34:53'),
(55, 'umama', 'umama212', '9996', 'umama@gmail.com', '03743673593', '$2y$10$lzycBkQ/4UtL2BIeA4m1IOCf04V4vcvW9mE1IptdaCEMLahd9rDuW', 4, 1, NULL, 'Active', NULL, '2026-07-30 16:24:01', '2026-07-30 16:24:26'),
(56, 'aleena', 'aleena372', '9824', 'aleena@gmail.com', '038377743843', '$2y$10$HISs33s1y6fS9u4ZpbeUEeA60Y28xV8bjT1kubJAN./PSi2R1Rd4e', 4, 2, NULL, 'Active', NULL, '2026-07-30 16:29:56', '2026-07-30 16:30:38'),
(57, 'hashir', 'hashir331', '9734', 'hashir@gmail.com', '03837984729', '$2y$10$R398W2oMbgzJldTIEo7XuugXtw/X7YT5nj88Qp4hOg.4Aywc4qMjC', 4, 1, NULL, 'Active', NULL, '2026-07-30 16:38:46', '2026-07-30 16:39:05'),
(60, 'aliya', 'aliya10016', '10016', 'aliya21@gmail.com', '89328932883', '$2y$10$UwuOmKSBZcS75nFxnau7aOz/GTJuGNRaCeepUyOUKLaE5/G.CK2xa', 4, 1, NULL, 'Active', NULL, '2026-07-30 19:58:09', '2026-07-30 19:58:09'),
(61, 'farah', 'farah10017', '10017', 'farahkhan@gmail.com', '03782182717821', '$2y$10$1n5VhcL6RH/YVHlyXzlT1O1llU1qM34oHGjy/LjKOrYY2HwQfTRW2', 4, 2, NULL, 'Active', NULL, '2026-07-31 17:21:37', '2026-07-31 17:21:37'),
(62, 'Ali Raza', 'aliraza62', '9997', 'ali.raza@demo.edu', '0300-1110001', '/YVHlyXzlT1O1llU1qM34oHGjy/LjKOrYY2HwQfTRW2', 4, 5, NULL, 'Active', NULL, '2026-07-31 20:36:00', '2026-07-31 20:36:00'),
(63, 'Sana Malik', 'sanamalik63', '9998', 'sana.malik@demo.edu', '0300-1110002', '/YVHlyXzlT1O1llU1qM34oHGjy/LjKOrYY2HwQfTRW2', 4, 5, NULL, 'Active', NULL, '2026-07-31 20:36:00', '2026-07-31 20:36:00');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pending_admissions`
-- (See below for the actual view)
--
CREATE TABLE `vw_pending_admissions` (
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_profile`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_profile` (
`student_id` int(11)
,`roll_no` varchar(30)
,`full_name` varchar(150)
,`father_name` varchar(150)
,`program` varchar(100)
,`current_session` varchar(50)
,`current_semester` varchar(50)
,`batch_year` smallint(6)
,`status` enum('Active','Freeze','Graduated','Dropped','Suspended')
,`total_amount` decimal(12,2)
,`paid_amount` decimal(12,2)
,`remaining_amount` decimal(12,2)
,`fee_status` enum('Unpaid','Partially Paid','Paid','Overdue')
);

-- --------------------------------------------------------

--
-- Structure for view `vw_pending_admissions`
--
DROP TABLE IF EXISTS `vw_pending_admissions`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_pending_admissions`  AS SELECT `admission_applications`.`application_id` AS `application_id`, `admission_applications`.`temp_application_no` AS `temp_application_no`, `admission_applications`.`full_name` AS `full_name`, `admission_applications`.`program_id` AS `program_id`, `admission_applications`.`session_id` AS `session_id`, `admission_applications`.`submitted_at` AS `submitted_at` FROM `admission_applications` WHERE `admission_applications`.`application_status` in ('Submitted','Under Review') ORDER BY `admission_applications`.`submitted_at` ASC ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_profile`
--
DROP TABLE IF EXISTS `vw_student_profile`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_profile`  AS SELECT `st`.`student_id` AS `student_id`, `st`.`roll_no` AS `roll_no`, `st`.`full_name` AS `full_name`, `st`.`father_name` AS `father_name`, `d`.`department_name` AS `program`, `se`.`session_name` AS `current_session`, `sm`.`semester_name` AS `current_semester`, `st`.`batch_year` AS `batch_year`, `st`.`status` AS `status`, `sf`.`total_amount` AS `total_amount`, `sf`.`paid_amount` AS `paid_amount`, `sf`.`remaining_amount` AS `remaining_amount`, `sf`.`status` AS `fee_status` FROM ((((`students` `st` join `departments` `d` on(`d`.`department_id` = `st`.`program_id`)) join `sessions` `se` on(`se`.`session_id` = `st`.`current_session_id`)) join `semesters` `sm` on(`sm`.`semester_id` = `st`.`current_semester_id`)) left join `student_fee` `sf` on(`sf`.`student_id` = `st`.`student_id` and `sf`.`semester_id` = `st`.`current_semester_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_code` (`session_code`);

--
-- Indexes for table `acr_requests`
--
ALTER TABLE `acr_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_req_type` (`request_type`),
  ADD KEY `idx_req_status` (`status`);

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `fk_log_user` (`performed_by`);

--
-- Indexes for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `fk_appl_program` (`program_id`),
  ADD KEY `fk_appl_session` (`session_id`),
  ADD KEY `fk_appl_sem` (`applied_semester_id`),
  ADD KEY `fk_appl_reviewer` (`reviewed_by`);

--
-- Indexes for table `admission_fees`
--
ALTER TABLE `admission_fees`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `fee_challan_no` (`fee_challan_no`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  ADD PRIMARY KEY (`scholarship_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_approved_by` (`approved_by`);

--
-- Indexes for table `admission_scholarship_applications`
--
ALTER TABLE `admission_scholarship_applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_app_scholarship` (`scholarship_id`),
  ADD KEY `fk_app_student` (`student_id`);

--
-- Indexes for table `admission_scholarship_programs`
--
ALTER TABLE `admission_scholarship_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `admission_students`
--
ALTER TABLE `admission_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD KEY `fk_ast_scholarship` (`scholarship_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `uq_student_course_date` (`student_id`,`course_id`,`class_date`),
  ADD KEY `fk_att_course` (`course_id`),
  ADD KEY `fk_att_teacher` (`teacher_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `fk_course_dept` (`department_id`),
  ADD KEY `fk_course_sem` (`semester_id`);

--
-- Indexes for table `course_master`
--
ALTER TABLE `course_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `department_code` (`department_code`);

--
-- Indexes for table `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `fk_exm_session` (`session_id`),
  ADD KEY `fk_exm_sem` (`semester_id`),
  ADD KEY `fk_exm_creator` (`created_by`);

--
-- Indexes for table `exam_attendance`
--
ALTER TABLE `exam_attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `idx_exam_attendance_student` (`student_id`),
  ADD KEY `idx_exam_attendance_exam` (`exam_id`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `idx_exam_results_student` (`student_id`),
  ADD KEY `idx_exam_results_exam` (`exam_id`),
  ADD KEY `idx_exam_results_status` (`status`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `idx_schedule_course_date` (`course_id`,`date`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD KEY `fk_faculty_dept` (`department_id`);

--
-- Indexes for table `fee_heads`
--
ALTER TABLE `fee_heads`
  ADD PRIMARY KEY (`fee_head_id`),
  ADD UNIQUE KEY `fee_head_name` (`fee_head_name`);

--
-- Indexes for table `fee_master`
--
ALTER TABLE `fee_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fee_records`
--
ALTER TABLE `fee_records`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fee_structure`
--
ALTER TABLE `fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`fee_structure_id`);

--
-- Indexes for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_structure_head` (`fee_structure_id`,`fee_head_id`),
  ADD KEY `fk_fsd_head` (`fee_head_id`);

--
-- Indexes for table `installments`
--
ALTER TABLE `installments`
  ADD PRIMARY KEY (`installment_id`),
  ADD UNIQUE KEY `uq_stf_installment_no` (`student_fee_id`,`installment_no`);

--
-- Indexes for table `lms_academic_calendar`
--
ALTER TABLE `lms_academic_calendar`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_date` (`event_date`);

--
-- Indexes for table `lms_announcements`
--
ALTER TABLE `lms_announcements`
  ADD PRIMARY KEY (`announcement_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_applications`
--
ALTER TABLE `lms_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `lms_assignments`
--
ALTER TABLE `lms_assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_course_materials`
--
ALTER TABLE `lms_course_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_datesheets`
--
ALTER TABLE `lms_datesheets`
  ADD PRIMARY KEY (`datesheet_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_enrollments`
--
ALTER TABLE `lms_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `idx_course` (`course_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `lms_exams`
--
ALTER TABLE `lms_exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_fees`
--
ALTER TABLE `lms_fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `lms_lectures`
--
ALTER TABLE `lms_lectures`
  ADD PRIMARY KEY (`lecture_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_marks`
--
ALTER TABLE `lms_marks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_course_student` (`course_id`,`student_user_id`);

--
-- Indexes for table `lms_mark_finalizations`
--
ALTER TABLE `lms_mark_finalizations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_course_student` (`course_id`,`student_user_id`);

--
-- Indexes for table `lms_messages`
--
ALTER TABLE `lms_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_recipient` (`recipient_user_id`);

--
-- Indexes for table `lms_notifications`
--
ALTER TABLE `lms_notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `idx_recipient` (`recipient_user_id`);

--
-- Indexes for table `lms_queries`
--
ALTER TABLE `lms_queries`
  ADD PRIMARY KEY (`query_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `lms_quizzes`
--
ALTER TABLE `lms_quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_quiz_results`
--
ALTER TABLE `lms_quiz_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `idx_quiz` (`quiz_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `lms_reports`
--
ALTER TABLE `lms_reports`
  ADD PRIMARY KEY (`report_id`);

--
-- Indexes for table `lms_settings`
--
ALTER TABLE `lms_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `uk_user_key` (`user_id`,`setting_key`);

--
-- Indexes for table `lms_student_answers`
--
ALTER TABLE `lms_student_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `idx_quiz_student` (`quiz_id`,`student_user_id`);

--
-- Indexes for table `lms_submissions`
--
ALTER TABLE `lms_submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_assignment` (`assignment_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `lms_timetable`
--
ALTER TABLE `lms_timetable`
  ADD PRIMARY KEY (`timetable_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `lms_transcripts`
--
ALTER TABLE `lms_transcripts`
  ADD PRIMARY KEY (`transcript_id`),
  ADD KEY `idx_student` (`student_user_id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `fk_pay_stf` (`student_fee_id`),
  ADD KEY `fk_pay_student` (`student_id`),
  ADD KEY `fk_pay_officer` (`received_by`);

--
-- Indexes for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  ADD PRIMARY KEY (`reversal_id`),
  ADD KEY `fk_rev_payment` (`payment_id`),
  ADD KEY `fk_rev_officer` (`reversed_by`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `question_bank`
--
ALTER TABLE `question_bank`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `idx_questions_exam` (`exam_id`),
  ADD KEY `idx_questions_course` (`course_id`),
  ADD KEY `idx_questions_difficulty` (`difficulty_level`);

--
-- Indexes for table `question_papers`
--
ALTER TABLE `question_papers`
  ADD PRIMARY KEY (`paper_id`),
  ADD KEY `fk_qp_exam` (`exam_id`),
  ADD KEY `fk_qp_course` (`course_id`),
  ADD KEY `fk_qp_teacher` (`teacher_id`);

--
-- Indexes for table `receipts`
--
ALTER TABLE `receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `payment_id` (`payment_id`),
  ADD UNIQUE KEY `receipt_no` (`receipt_no`),
  ADD KEY `fk_rcpt_issuer` (`issued_by`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_no` (`room_no`);

--
-- Indexes for table `room_allocations`
--
ALTER TABLE `room_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ra_room` (`room_id`,`day_of_week`);

--
-- Indexes for table `sbe_auth_users`
--
ALTER TABLE `sbe_auth_users`
  ADD PRIMARY KEY (`auth_id`),
  ADD UNIQUE KEY `login_id` (`login_id`),
  ADD KEY `fk_sbeauth_teacher` (`teacher_id`),
  ADD KEY `fk_sbeauth_student` (`student_id`);

--
-- Indexes for table `sbe_exams`
--
ALTER TABLE `sbe_exams`
  ADD PRIMARY KEY (`exam_id`),
  ADD UNIQUE KEY `exam_code` (`exam_code`),
  ADD KEY `fk_sbeexam_course` (`course_id`),
  ADD KEY `fk_sbeexam_teacher` (`teacher_id`);

--
-- Indexes for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  ADD PRIMARY KEY (`exam_question_id`),
  ADD UNIQUE KEY `uq_exam_question` (`exam_id`,`question_id`),
  ADD UNIQUE KEY `uq_exam_order` (`exam_id`,`question_order`),
  ADD KEY `fk_sbeeq_question` (`question_id`);

--
-- Indexes for table `sbe_exam_results`
--
ALTER TABLE `sbe_exam_results`
  ADD PRIMARY KEY (`exam_result_id`),
  ADD UNIQUE KEY `uq_studentexam_result` (`student_exam_id`),
  ADD KEY `fk_sberes_exam` (`exam_id`),
  ADD KEY `fk_sberes_student` (`student_id`);

--
-- Indexes for table `sbe_exam_schedule`
--
ALTER TABLE `sbe_exam_schedule`
  ADD PRIMARY KEY (`schedule_id`),
  ADD KEY `fk_sbesched_exam` (`exam_id`),
  ADD KEY `fk_sbesched_sem` (`semester_id`);

--
-- Indexes for table `sbe_question_bank`
--
ALTER TABLE `sbe_question_bank`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `fk_qb_course` (`course_id`),
  ADD KEY `fk_qb_teacher` (`teacher_id`);

--
-- Indexes for table `sbe_student_answers`
--
ALTER TABLE `sbe_student_answers`
  ADD PRIMARY KEY (`student_answer_id`),
  ADD UNIQUE KEY `uq_studentexam_question` (`student_exam_id`,`question_id`),
  ADD KEY `fk_sbesa_question` (`question_id`);

--
-- Indexes for table `sbe_student_exams`
--
ALTER TABLE `sbe_student_exams`
  ADD PRIMARY KEY (`student_exam_id`),
  ADD UNIQUE KEY `uq_schedule_student_attempt` (`schedule_id`,`student_id`,`attempt_no`),
  ADD KEY `fk_sbese_exam` (`exam_id`),
  ADD KEY `fk_sbese_student` (`student_id`);

--
-- Indexes for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD PRIMARY KEY (`scholarship_id`),
  ADD KEY `fk_sch_student` (`student_id`),
  ADD KEY `fk_sch_semester` (`semester_id`),
  ADD KEY `fk_sch_approver` (`approved_by`);

--
-- Indexes for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `unique_section` (`section_name`,`academic_year`);

--
-- Indexes for table `section_courses`
--
ALTER TABLE `section_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_section_course` (`section_id`,`course_id`),
  ADD KEY `idx_section` (`section_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `section_schedules`
--
ALTER TABLE `section_schedules`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ss` (`section_id`,`course_id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`),
  ADD UNIQUE KEY `uq_dept_sem` (`department_id`,`semester_number`);

--
-- Indexes for table `semester_courses`
--
ALTER TABLE `semester_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_sem_course` (`semester_id`,`course_id`),
  ADD KEY `fk_semc_course` (`course_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD UNIQUE KEY `session_name` (`session_name`);

--
-- Indexes for table `sso_applications`
--
ALTER TABLE `sso_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `fk_ssoapp_student` (`student_id`),
  ADD KEY `fk_ssoapp_resolver` (`resolved_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD UNIQUE KEY `roll_no` (`roll_no`),
  ADD KEY `fk_stu_program` (`program_id`),
  ADD KEY `fk_stu_adm_session` (`admission_session_id`),
  ADD KEY `fk_stu_cur_session` (`current_session_id`),
  ADD KEY `fk_stu_cur_sem` (`current_semester_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `student_admission_details`
--
ALTER TABLE `student_admission_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `section_id` (`section_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`);

--
-- Indexes for table `student_course_allocation`
--
ALTER TABLE `student_course_allocation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_course_structure`
--
ALTER TABLE `student_course_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_enrollment` (`student_id`,`course_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_course` (`course_id`);

--
-- Indexes for table `student_fee`
--
ALTER TABLE `student_fee`
  ADD PRIMARY KEY (`student_fee_id`),
  ADD UNIQUE KEY `uq_student_semester_fee` (`student_id`,`semester_id`),
  ADD KEY `fk_stf_sem` (`semester_id`),
  ADD KEY `fk_stf_session` (`session_id`),
  ADD KEY `fk_stf_fs` (`fee_structure_id`),
  ADD KEY `fk_stf_generator` (`generated_by`);

--
-- Indexes for table `student_fees`
--
ALTER TABLE `student_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_fee_allocation`
--
ALTER TABLE `student_fee_allocation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `fee_id` (`fee_id`);

--
-- Indexes for table `student_fee_assignment`
--
ALTER TABLE `student_fee_assignment`
  ADD PRIMARY KEY (`assignment_id`),
  ADD UNIQUE KEY `uq_student_semester_assignment` (`student_id`,`semester_id`),
  ADD KEY `fk_sfa_fs` (`fee_structure_id`),
  ADD KEY `fk_sfa_sem` (`semester_id`),
  ADD KEY `fk_sfa_officer` (`assigned_by`);

--
-- Indexes for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stf_head` (`student_fee_id`,`fee_head_id`),
  ADD KEY `fk_sfdet_head` (`fee_head_id`);

--
-- Indexes for table `student_fee_discounts`
--
ALTER TABLE `student_fee_discounts`
  ADD PRIMARY KEY (`discount_id`),
  ADD KEY `fk_sfd_student` (`student_id`),
  ADD KEY `fk_sfd_semester` (`semester_id`),
  ADD KEY `fk_sfd_officer` (`applied_by`),
  ADD KEY `fk_sfd_head` (`fee_head_id`);

--
-- Indexes for table `student_fee_items`
--
ALTER TABLE `student_fee_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_fee_structure`
--
ALTER TABLE `student_fee_structure`
  ADD PRIMARY KEY (`id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `fee_head_id` (`fee_head_id`);

--
-- Indexes for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD PRIMARY KEY (`grade_id`),
  ADD KEY `idx_student_grades_student` (`student_id`),
  ADD KEY `idx_student_grades_course` (`course_id`);

--
-- Indexes for table `student_promotions`
--
ALTER TABLE `student_promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD KEY `idx_student_promotions_student` (`student_id`),
  ADD KEY `fk_student_promotions_approved` (`approved_by`);

--
-- Indexes for table `student_schedule_requests`
--
ALTER TABLE `student_schedule_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ssr_status` (`status`),
  ADD KEY `idx_ssr_dept` (`department_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_teacher_user` (`user_id`),
  ADD KEY `fk_teacher_dept` (`department_id`);

--
-- Indexes for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_course_sem_session_section` (`course_id`,`semester_id`,`session_id`,`section`),
  ADD KEY `fk_tc_teacher` (`teacher_id`),
  ADD KEY `fk_tc_sem` (`semester_id`),
  ADD KEY `fk_tc_session` (`session_id`);

--
-- Indexes for table `timetable`
--
ALTER TABLE `timetable`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_room_slot` (`room_no`,`day_of_week`,`start_time`,`session_id`),
  ADD KEY `fk_tt_course` (`course_id`),
  ADD KEY `fk_tt_teacher` (`teacher_id`),
  ADD KEY `fk_tt_sem` (`semester_id`),
  ADD KEY `fk_tt_session` (`session_id`);

--
-- Indexes for table `timetables`
--
ALTER TABLE `timetables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tt` (`program_id`,`session_id`,`semester_id`,`section`);

--
-- Indexes for table `timetable_adjustments`
--
ALTER TABLE `timetable_adjustments`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable_conflicts`
--
ALTER TABLE `timetable_conflicts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `timetable_courses`
--
ALTER TABLE `timetable_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tc` (`timetable_id`,`course_id`),
  ADD KEY `idx_tt_course` (`course_id`);

--
-- Indexes for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tt_entry_room` (`room_id`,`day_of_week`),
  ADD KEY `idx_tt_entry_teacher` (`teacher_id`,`day_of_week`),
  ADD KEY `idx_tt_entry_tt` (`timetable_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_role` (`role_id`),
  ADD KEY `fk_users_dept` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_sessions`
--
ALTER TABLE `academic_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `acr_requests`
--
ALTER TABLE `acr_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=127;

--
-- AUTO_INCREMENT for table `admission_applications`
--
ALTER TABLE `admission_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=914;

--
-- AUTO_INCREMENT for table `admission_fees`
--
ALTER TABLE `admission_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  MODIFY `scholarship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `admission_scholarship_applications`
--
ALTER TABLE `admission_scholarship_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `admission_scholarship_programs`
--
ALTER TABLE `admission_scholarship_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admission_students`
--
ALTER TABLE `admission_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=487;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=60;

--
-- AUTO_INCREMENT for table `course_master`
--
ALTER TABLE `course_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `exam_attendance`
--
ALTER TABLE `exam_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fee_heads`
--
ALTER TABLE `fee_heads`
  MODIFY `fee_head_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fee_master`
--
ALTER TABLE `fee_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `fee_records`
--
ALTER TABLE `fee_records`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fee_structure`
--
ALTER TABLE `fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `fee_structure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4333;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `installment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `lms_academic_calendar`
--
ALTER TABLE `lms_academic_calendar`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_announcements`
--
ALTER TABLE `lms_announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `lms_applications`
--
ALTER TABLE `lms_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lms_assignments`
--
ALTER TABLE `lms_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `lms_course_materials`
--
ALTER TABLE `lms_course_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT for table `lms_datesheets`
--
ALTER TABLE `lms_datesheets`
  MODIFY `datesheet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_enrollments`
--
ALTER TABLE `lms_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `lms_exams`
--
ALTER TABLE `lms_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_fees`
--
ALTER TABLE `lms_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `lms_lectures`
--
ALTER TABLE `lms_lectures`
  MODIFY `lecture_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `lms_marks`
--
ALTER TABLE `lms_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `lms_mark_finalizations`
--
ALTER TABLE `lms_mark_finalizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `lms_messages`
--
ALTER TABLE `lms_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `lms_notifications`
--
ALTER TABLE `lms_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `lms_queries`
--
ALTER TABLE `lms_queries`
  MODIFY `query_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_quizzes`
--
ALTER TABLE `lms_quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_quiz_results`
--
ALTER TABLE `lms_quiz_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `lms_reports`
--
ALTER TABLE `lms_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_settings`
--
ALTER TABLE `lms_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `lms_student_answers`
--
ALTER TABLE `lms_student_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_submissions`
--
ALTER TABLE `lms_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `lms_timetable`
--
ALTER TABLE `lms_timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_transcripts`
--
ALTER TABLE `lms_transcripts`
  MODIFY `transcript_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  MODIFY `reversal_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `question_bank`
--
ALTER TABLE `question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `question_papers`
--
ALTER TABLE `question_papers`
  MODIFY `paper_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `room_allocations`
--
ALTER TABLE `room_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `sbe_auth_users`
--
ALTER TABLE `sbe_auth_users`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sbe_exams`
--
ALTER TABLE `sbe_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  MODIFY `exam_question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sbe_exam_results`
--
ALTER TABLE `sbe_exam_results`
  MODIFY `exam_result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `sbe_exam_schedule`
--
ALTER TABLE `sbe_exam_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sbe_question_bank`
--
ALTER TABLE `sbe_question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `sbe_student_answers`
--
ALTER TABLE `sbe_student_answers`
  MODIFY `student_answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `sbe_student_exams`
--
ALTER TABLE `sbe_student_exams`
  MODIFY `student_exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `scholarship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `scholarship_programs`
--
ALTER TABLE `scholarship_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1087;

--
-- AUTO_INCREMENT for table `section_courses`
--
ALTER TABLE `section_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `section_schedules`
--
ALTER TABLE `section_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=154;

--
-- AUTO_INCREMENT for table `semester_courses`
--
ALTER TABLE `semester_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sso_applications`
--
ALTER TABLE `sso_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `student_admission_details`
--
ALTER TABLE `student_admission_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1176;

--
-- AUTO_INCREMENT for table `student_course_allocation`
--
ALTER TABLE `student_course_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `student_course_structure`
--
ALTER TABLE `student_course_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `student_fee`
--
ALTER TABLE `student_fee`
  MODIFY `student_fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `student_fees`
--
ALTER TABLE `student_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `student_fee_allocation`
--
ALTER TABLE `student_fee_allocation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignment`
--
ALTER TABLE `student_fee_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT for table `student_fee_discounts`
--
ALTER TABLE `student_fee_discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `student_fee_items`
--
ALTER TABLE `student_fee_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_fee_structure`
--
ALTER TABLE `student_fee_structure`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `student_promotions`
--
ALTER TABLE `student_promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_schedule_requests`
--
ALTER TABLE `student_schedule_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `timetables`
--
ALTER TABLE `timetables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `timetable_adjustments`
--
ALTER TABLE `timetable_adjustments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `timetable_conflicts`
--
ALTER TABLE `timetable_conflicts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `timetable_courses`
--
ALTER TABLE `timetable_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `timetable_entries`
--
ALTER TABLE `timetable_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=64;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `fk_log_user` FOREIGN KEY (`performed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD CONSTRAINT `fk_appl_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  ADD CONSTRAINT `fk_scholarship_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scholarship_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scholarship_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE SET NULL;

--
-- Constraints for table `admission_scholarship_applications`
--
ALTER TABLE `admission_scholarship_applications`
  ADD CONSTRAINT `fk_app_scholarship` FOREIGN KEY (`scholarship_id`) REFERENCES `admission_scholarships` (`scholarship_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_app_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `fk_att_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_att_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_course_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `fk_exm_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_exm_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_exm_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

--
-- Constraints for table `exam_attendance`
--
ALTER TABLE `exam_attendance`
  ADD CONSTRAINT `fk_exam_attendance_exam` FOREIGN KEY (`exam_id`) REFERENCES `exam_schedules` (`exam_id`),
  ADD CONSTRAINT `fk_exam_attendance_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `fk_exam_results_exam` FOREIGN KEY (`exam_id`) REFERENCES `exam_schedules` (`exam_id`),
  ADD CONSTRAINT `fk_exam_results_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `fk_faculty_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  ADD CONSTRAINT `fk_fsd_head` FOREIGN KEY (`fee_head_id`) REFERENCES `fee_heads` (`fee_head_id`),
  ADD CONSTRAINT `fk_fsd_structure` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`fee_structure_id`) ON DELETE CASCADE;

--
-- Constraints for table `installments`
--
ALTER TABLE `installments`
  ADD CONSTRAINT `fk_inst_stf` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fee` (`student_fee_id`) ON DELETE CASCADE;

--
-- Constraints for table `lms_applications`
--
ALTER TABLE `lms_applications`
  ADD CONSTRAINT `lms_applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `fk_pay_officer` FOREIGN KEY (`received_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_pay_stf` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fee` (`student_fee_id`),
  ADD CONSTRAINT `fk_pay_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  ADD CONSTRAINT `fk_rev_officer` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_rev_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `question_papers`
--
ALTER TABLE `question_papers`
  ADD CONSTRAINT `fk_qp_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_qp_exam` FOREIGN KEY (`exam_id`) REFERENCES `examinations` (`exam_id`),
  ADD CONSTRAINT `fk_qp_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `receipts`
--
ALTER TABLE `receipts`
  ADD CONSTRAINT `fk_rcpt_issuer` FOREIGN KEY (`issued_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_rcpt_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `sbe_auth_users`
--
ALTER TABLE `sbe_auth_users`
  ADD CONSTRAINT `fk_sbeauth_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_sbeauth_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `sbe_exams`
--
ALTER TABLE `sbe_exams`
  ADD CONSTRAINT `fk_sbeexam_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_sbeexam_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  ADD CONSTRAINT `fk_sbeeq_exam` FOREIGN KEY (`exam_id`) REFERENCES `sbe_exams` (`exam_id`),
  ADD CONSTRAINT `fk_sbeeq_question` FOREIGN KEY (`question_id`) REFERENCES `sbe_question_bank` (`question_id`);

--
-- Constraints for table `sbe_exam_results`
--
ALTER TABLE `sbe_exam_results`
  ADD CONSTRAINT `fk_sberes_exam` FOREIGN KEY (`exam_id`) REFERENCES `sbe_exams` (`exam_id`),
  ADD CONSTRAINT `fk_sberes_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_sberes_studentexam` FOREIGN KEY (`student_exam_id`) REFERENCES `sbe_student_exams` (`student_exam_id`);

--
-- Constraints for table `sbe_exam_schedule`
--
ALTER TABLE `sbe_exam_schedule`
  ADD CONSTRAINT `fk_sbesched_exam` FOREIGN KEY (`exam_id`) REFERENCES `sbe_exams` (`exam_id`),
  ADD CONSTRAINT `fk_sbesched_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `sbe_question_bank`
--
ALTER TABLE `sbe_question_bank`
  ADD CONSTRAINT `fk_qb_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_qb_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `sbe_student_answers`
--
ALTER TABLE `sbe_student_answers`
  ADD CONSTRAINT `fk_sbesa_question` FOREIGN KEY (`question_id`) REFERENCES `sbe_question_bank` (`question_id`),
  ADD CONSTRAINT `fk_sbesa_studentexam` FOREIGN KEY (`student_exam_id`) REFERENCES `sbe_student_exams` (`student_exam_id`);

--
-- Constraints for table `sbe_student_exams`
--
ALTER TABLE `sbe_student_exams`
  ADD CONSTRAINT `fk_sbese_exam` FOREIGN KEY (`exam_id`) REFERENCES `sbe_exams` (`exam_id`),
  ADD CONSTRAINT `fk_sbese_schedule` FOREIGN KEY (`schedule_id`) REFERENCES `sbe_exam_schedule` (`schedule_id`),
  ADD CONSTRAINT `fk_sbese_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `scholarships`
--
ALTER TABLE `scholarships`
  ADD CONSTRAINT `fk_sch_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_sch_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_sch_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `semesters`
--
ALTER TABLE `semesters`
  ADD CONSTRAINT `fk_sem_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `semester_courses`
--
ALTER TABLE `semester_courses`
  ADD CONSTRAINT `fk_semc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_semc_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `sso_applications`
--
ALTER TABLE `sso_applications`
  ADD CONSTRAINT `fk_ssoapp_resolver` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_ssoapp_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `fk_stu_adm_session` FOREIGN KEY (`admission_session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_stu_application` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`application_id`),
  ADD CONSTRAINT `fk_stu_cur_sem` FOREIGN KEY (`current_semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_stu_cur_session` FOREIGN KEY (`current_session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_stu_program` FOREIGN KEY (`program_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `student_fee`
--
ALTER TABLE `student_fee`
  ADD CONSTRAINT `fk_stf_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_stf_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_stf_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_fee_assignment`
--
ALTER TABLE `student_fee_assignment`
  ADD CONSTRAINT `fk_sfa_fs` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`fee_structure_id`),
  ADD CONSTRAINT `fk_sfa_officer` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_sfa_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_sfa_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  ADD CONSTRAINT `fk_sfdet_stf` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fee` (`student_fee_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_fee_discounts`
--
ALTER TABLE `student_fee_discounts`
  ADD CONSTRAINT `fk_sfd_head` FOREIGN KEY (`fee_head_id`) REFERENCES `fee_heads` (`fee_head_id`),
  ADD CONSTRAINT `fk_sfd_officer` FOREIGN KEY (`applied_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_sfd_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_sfd_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_grades`
--
ALTER TABLE `student_grades`
  ADD CONSTRAINT `fk_student_grades_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_student_grades_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `student_promotions`
--
ALTER TABLE `student_promotions`
  ADD CONSTRAINT `fk_student_promotions_approved` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_student_promotions_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `fk_teacher_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_teacher_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  ADD CONSTRAINT `fk_tc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_tc_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_tc_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_tc_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `timetable`
--
ALTER TABLE `timetable`
  ADD CONSTRAINT `fk_tt_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_tt_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_tt_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_tt_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`teacher_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
