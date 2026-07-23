-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 23, 2026 at 08:13 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

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
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_student_id` (IN `program_code` VARCHAR(10), IN `batch_year` INT, OUT `new_id` VARCHAR(20))   BEGIN
    DECLARE last_number INT;
    
    SELECT COUNT(*) + 1 INTO last_number
    FROM students
    WHERE student_id LIKE CONCAT(program_code, '-', batch_year, '-%');
    
    SET new_id = CONCAT(program_code, '-', batch_year, '-', LPAD(last_number, 3, '0'));
END$$

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

-- --------------------------------------------------------

--
-- Table structure for table `admission_applications`
--

CREATE TABLE `admission_applications` (
  `application_id` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `cnic` varchar(15) DEFAULT NULL,
  `program_applied` int(11) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `previous_education` varchar(255) DEFAULT NULL,
  `marks_obtained` decimal(10,2) DEFAULT NULL,
  `total_marks` decimal(10,2) DEFAULT NULL,
  `percentage` decimal(10,2) DEFAULT NULL,
  `documents_path` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','waiting_test') DEFAULT 'pending',
  `test_score` decimal(5,2) DEFAULT NULL,
  `applied_date` datetime DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admission_applications`
--

INSERT INTO `admission_applications` (`application_id`, `name`, `father_name`, `email`, `phone`, `cnic`, `program_applied`, `address`, `dob`, `gender`, `previous_education`, `marks_obtained`, `total_marks`, `percentage`, `documents_path`, `status`, `test_score`, `applied_date`, `reviewed_by`, `review_date`) VALUES
('APP-2026-001', 'Muhammad Ali', 'Ahmed Ali', 'ali@email.com', '0310-1111111', '42101-1234567-1', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 85.50, '2026-06-27 18:33:55', NULL, NULL),
('APP-2026-002', 'Fatima Khan', 'Rashid Khan', 'fatima@email.com', '0310-2222222', '42101-7654321-2', 2, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-06-27 18:33:55', 1, '2026-07-20 11:49:53'),
('APP-2026-003', 'Usman Ahmed', 'Zafar Ahmed', 'usman@email.com', '0310-3333333', '42101-9876543-3', 1, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'approved', 78.00, '2026-06-27 18:33:55', NULL, NULL),
('APP-2026-004', 'Ayesha Malik', 'Tariq Malik', 'ayesha@email.com', '0310-4444444', '42101-4567890-4', 3, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '', NULL, '2026-06-27 18:33:55', 1, '2026-07-20 11:56:45'),
('TEMP-2026-001', 'jawd', 'kkk', 'kk@d.com', '98899', '17803-133123-1', 1, 'ijadka', '2026-01-20', 'Male', NULL, NULL, NULL, NULL, NULL, 'approved', NULL, '2026-07-20 11:48:48', 1, '2026-07-20 11:56:34'),
('TEMP-2026-002', 'jkjk', 'jkjkj', '88@j.com', '99888', 'j888', 3, 'hjhj', '2026-07-07', 'Male', 'uj', 90000.00, 99009.00, 90.90, NULL, 'approved', NULL, '2026-07-20 11:50:53', 1, '2026-07-20 11:56:35'),
('TEMP-2026-003', 'ip7uko', '900-9-0', '9909@s.com', '23442', '23213123', 2, 'ijkjk', '2026-07-12', 'Male', NULL, NULL, NULL, NULL, NULL, 'approved', NULL, '2026-07-20 22:17:51', 1, '2026-07-20 22:27:13');

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `application_type` enum('Leave','Bonafide Certificate','Transcript','ID Card','Semester Freeze','Course Withdrawal') NOT NULL,
  `subject` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `attachment` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assignments`
--

CREATE TABLE `assignments` (
  `assignment_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `deadline` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `assignments`
--

INSERT INTO `assignments` (`assignment_id`, `course_id`, `faculty_id`, `title`, `description`, `total_marks`, `deadline`, `created_at`) VALUES
(1, 1, 1, 'SQL Queries Assignment', 'Write 10 complex SQL queries', 50, '2026-07-20 23:59:59', '2026-06-27 18:33:55'),
(2, 1, 1, 'Database Design Project', 'Design a complete database for a library', 100, '2026-08-15 23:59:59', '2026-06-27 18:33:55');

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` enum('present','absent','late','excused') DEFAULT 'absent',
  `faculty_id` int(11) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `course_id`, `date`, `status`, `faculty_id`, `remark`) VALUES
(25, 'CS-26-002', 1, '2026-07-13', 'present', NULL, ''),
(26, 'BSAI-2026-001', 6, '2026-07-13', 'present', NULL, ''),
(28, 'BSDS-2026-001', 6, '2026-07-13', 'present', NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `credit_hours`, `department_id`, `program_id`, `semester`, `description`) VALUES
(1, 'CS-301', 'Database Systems', 1, 1, 1, 1, ''),
(2, 'CS-302', 'Operating Systems', 3, 1, 1, 3, NULL),
(3, 'CS-303', 'Computer Networks', 3, 1, 1, 3, NULL),
(4, 'CS-304', 'Software Engineering', 3, 1, 1, 4, NULL),
(5, 'CS-305', 'Data Structures', 5, 1, 1, 2, ''),
(6, 'IT-301', 'Web Development', 3, 2, 2, 3, NULL),
(7, 'IT-302', 'Network Security', 3, 2, 2, 4, NULL),
(15, '8', '888', 2, NULL, 5, 3, '88'),
(16, 'DW', 'nknkn', 3, NULL, 5, NULL, 'nkn'),
(17, 'KK', 'koo', 1, NULL, 4, NULL, ''),
(18, 'NEWWWWWWWWWWWW', 'newwwwwwwwwwwwwwwwwwww', 3, NULL, 4, NULL, 'newwwwwww');

-- --------------------------------------------------------

--
-- Table structure for table `course_fees`
--

CREATE TABLE `course_fees` (
  `fee_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `fee_type` enum('Per Credit Hour','Fixed','Lab Fee','Exam Fee') DEFAULT 'Fixed',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `course_fees`
--

INSERT INTO `course_fees` (`fee_id`, `course_id`, `semester_id`, `fee_amount`, `fee_type`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 15, 1, 777.00, 'Fixed', '', 1, '2026-07-13 07:29:31', '2026-07-13 07:29:39'),
(2, 3, 2, 0.08, 'Fixed', '', 1, '2026-07-18 06:48:05', '2026-07-18 06:48:05'),
(3, 2, 2, 0.03, 'Fixed', '', 1, '2026-07-18 08:51:46', '2026-07-18 08:51:46');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department_code` varchar(10) DEFAULT NULL,
  `department_name` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department_code`, `department_name`, `created_at`) VALUES
(1, 'CS', 'Computer Science', '2026-06-27 18:33:55'),
(2, 'IT', 'Information Technology', '2026-06-27 18:33:55'),
(3, 'SE', 'Software Engineering', '2026-06-27 18:33:55'),
(4, 'AI', 'Artificial Intelligence', '2026-06-27 18:33:55'),
(5, 'DS', 'Data Science', '2026-06-27 18:33:55');

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

-- --------------------------------------------------------

--
-- Table structure for table `exam_results`
--

CREATE TABLE `exam_results` (
  `result_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `total_marks` int(11) DEFAULT NULL,
  `grade` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `exam_schedules`
--

CREATE TABLE `exam_schedules` (
  `exam_id` int(11) NOT NULL,
  `course_id` int(11) DEFAULT NULL,
  `exam_type` enum('mid','final','quiz','lab') DEFAULT 'final',
  `date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `room` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exam_schedules`
--

INSERT INTO `exam_schedules` (`exam_id`, `course_id`, `exam_type`, `date`, `start_time`, `end_time`, `room`) VALUES
(1, 1, 'mid', '2026-07-25', '09:00:00', '11:00:00', 'Lab 1'),
(2, 1, 'final', '2026-08-30', '09:00:00', '12:00:00', 'Hall A');

-- --------------------------------------------------------

--
-- Table structure for table `faculty`
--

CREATE TABLE `faculty` (
  `faculty_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `designation` varchar(50) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `hire_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty`
--

INSERT INTO `faculty` (`faculty_id`, `user_id`, `employee_id`, `designation`, `department_id`, `hire_date`) VALUES
(1, 4, 'FAC-001', 'Professor', 1, '2020-08-01');

-- --------------------------------------------------------

--
-- Table structure for table `faculty_courses`
--

CREATE TABLE `faculty_courses` (
  `assignment_id` int(11) NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculty_courses`
--

INSERT INTO `faculty_courses` (`assignment_id`, `faculty_id`, `course_id`, `semester`, `year`, `section`, `is_active`) VALUES
(1, 1, 1, 3, 2026, 'A', 1),
(2, 1, 2, 3, 2026, 'A', 1);

-- --------------------------------------------------------

--
-- Table structure for table `fee_heads`
--

CREATE TABLE `fee_heads` (
  `fee_head_id` int(11) NOT NULL,
  `fee_head_name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_heads`
--

INSERT INTO `fee_heads` (`fee_head_id`, `fee_head_name`, `description`, `status`, `deleted_at`, `created_at`, `updated_at`) VALUES
(1, 'Admission Fee', 'One-time admission fee', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00'),
(2, 'Tuition Fee', 'Per semester tuition', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00'),
(3, 'Examination Fee', 'Exam and assessment fee', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00'),
(4, 'Library Fee', 'Library services fee', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00'),
(5, 'Sports Fee', 'Sports and recreation fee', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00'),
(6, 'Security Fee', 'Refundable security deposit', 'Active', NULL, '2026-07-19 18:13:00', '2026-07-19 18:13:00');

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
-- Table structure for table `fee_payments`
--

CREATE TABLE `fee_payments` (
  `payment_id` int(11) NOT NULL,
  `student_course_fee_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` enum('Cash','Bank Transfer','Credit Card','Online') NOT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `receipt_no` varchar(50) DEFAULT NULL,
  `received_by` int(11) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_records`
--

CREATE TABLE `fee_records` (
  `fee_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `total_fee` decimal(10,2) DEFAULT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('pending','partial','paid','overdue') DEFAULT 'pending',
  `payment_date` date DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `verified_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fee_records`
--

INSERT INTO `fee_records` (`fee_id`, `student_id`, `semester`, `total_fee`, `paid_amount`, `due_date`, `status`, `payment_date`, `transaction_id`, `verified_by`) VALUES
(1, 'CS-26-001', 3, 45000.00, 45000.00, '2026-03-15', 'paid', NULL, NULL, NULL),
(2, 'CS-26-002', 3, 45000.00, 20000.00, '2026-03-15', 'partial', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `fee_settings`
--

CREATE TABLE `fee_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `fee_structure_id` int(11) NOT NULL,
  `structure_name` varchar(100) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`fee_structure_id`, `structure_name`, `program_id`, `semester_id`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Default Fee Structure', NULL, NULL, 1, '2026-07-19 18:14:56', '2026-07-19 18:14:56');

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
(2, 1, 1, 2222.00),
(3, 1, 6, 111.00);

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
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `duration_years` int(11) DEFAULT 4,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `dept_id`, `duration_years`, `status`) VALUES
(1, 'BSCS', 'BS Computer Science', 1, 4, 'Active'),
(2, 'BSIT', 'BS Information Technology', 2, 4, 'Active'),
(3, 'BSSE', 'BS Software Engineering', 3, 4, 'Active'),
(4, 'BSAI', 'BS Artificial Intelligence', 4, 4, 'Active'),
(5, 'BSDS', 'BS Data Science', 5, 4, 'Active');

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
) ;

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
  `role_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'admin', 'System Administrator - Full access', '2026-06-27 18:33:55'),
(2, 'sso', 'Student Services Officer - Manages students', '2026-06-27 18:33:55'),
(3, 'faculty', 'Faculty Member - Teaches courses', '2026-06-27 18:33:55'),
(4, 'student', 'Student - Enrolled in programs', '2026-06-27 18:33:55'),
(5, 'exam_officer', 'Examination Officer - Manages exams', '2026-06-27 18:33:55'),
(6, 'account', 'Account Office - Handles fees', '2026-06-27 18:33:55');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ;

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
(0, 0, 'Admission', 'mm', NULL, 'Percentage', 50.00, 1, '2026-07-19 18:16:52', 'Active', '');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `section_id` int(11) NOT NULL,
  `section_name` varchar(10) NOT NULL,
  `semester_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `program_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `capacity` int(11) DEFAULT 30,
  `enrolled_count` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`section_id`, `section_name`, `semester_id`, `status`, `program_id`, `session_id`, `capacity`, `enrolled_count`, `created_at`, `updated_at`) VALUES
(2, 'B', 1, 'Active', 1, 1, 25, 2, '2026-07-13 07:38:12', '2026-07-18 06:47:28'),
(3, 'A', 3, 'Active', 2, 1, 30, 2, '2026-07-13 07:38:12', '2026-07-15 19:37:43');

-- --------------------------------------------------------

--
-- Table structure for table `section_change_requests`
--

CREATE TABLE `section_change_requests` (
  `request_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `current_section` varchar(5) DEFAULT NULL,
  `requested_section` varchar(5) DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` datetime DEFAULT current_timestamp(),
  `reviewed_by` int(11) DEFAULT NULL,
  `review_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `section_courses`
--

CREATE TABLE `section_courses` (
  `id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) DEFAULT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `section_courses`
--

INSERT INTO `section_courses` (`id`, `section_id`, `course_id`, `teacher_id`, `is_primary`, `created_at`) VALUES
(1, 1, 1, 1, 1, '2026-07-13 07:38:12'),
(2, 1, 2, 1, 0, '2026-07-13 07:38:12'),
(3, 2, 3, 1, 1, '2026-07-13 07:38:12');

-- --------------------------------------------------------

--
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_number` int(11) DEFAULT NULL,
  `semester_name` varchar(50) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `semesters`
--

INSERT INTO `semesters` (`semester_id`, `semester_number`, `semester_name`, `department_id`, `status`) VALUES
(1, 1, '1st Semester', NULL, 'Active'),
(2, 2, '2nd Semester', NULL, 'Active'),
(3, 3, '3rd Semester', NULL, 'Active'),
(4, 4, '4th Semester', NULL, 'Active'),
(5, 5, '5th Semester', NULL, 'Active'),
(6, 6, '6th Semester', NULL, 'Active'),
(7, 7, '7th Semester', NULL, 'Active'),
(8, 8, '8th Semester', NULL, 'Active');

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
(6, 1, 4),
(9, 2, 1),
(10, 2, 2),
(11, 2, 3),
(12, 2, 4),
(13, 2, 5),
(8, 2, 15),
(14, 2, 16),
(2, 3, 1),
(3, 3, 2),
(4, 3, 3),
(23, 3, 4),
(1, 3, 5),
(5, 3, 6),
(22, 3, 15),
(18, 4, 3),
(19, 4, 4),
(20, 4, 5),
(7, 4, 7),
(21, 4, 16),
(15, 8, 7),
(16, 8, 17),
(17, 8, 18);

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
(1, 'Fall 2024', '2024-09-01', '2024-12-31', 'Inactive', '2026-07-13 06:28:05'),
(2, 'Spring 2025', '2025-01-15', '2025-05-30', 'Inactive', '2026-07-13 06:28:05'),
(3, 'Summer 2025', '2025-06-01', '2025-08-31', 'Inactive', '2026-07-13 06:28:05'),
(4, 'Fall 2025', '2025-09-01', '2025-12-31', 'Active', '2026-07-13 06:28:05');

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
  `student_id` varchar(20) NOT NULL,
  `roll_no` varchar(20) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `application_id` varchar(20) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT 1,
  `status` enum('pending','confirmed','active','inactive','graduated') DEFAULT 'pending',
  `enrollment_date` date DEFAULT NULL,
  `father_name` varchar(100) DEFAULT NULL,
  `session` varchar(50) DEFAULT NULL,
  `section_id` int(11) DEFAULT NULL,
  `semester_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `roll_no`, `user_id`, `application_id`, `program_id`, `section`, `batch_year`, `semester`, `status`, `enrollment_date`, `father_name`, `session`, `section_id`, `semester_id`) VALUES
('BSAI-2026-001', 'BSAI-2026-001', 17, NULL, 1, 'A', 2026, 1, 'active', '0000-00-00', 'tarwi', '', NULL, NULL),
('BSAI-2027-001', 'BSAI-2027-001', 22, NULL, 4, 'A', 2027, 1, 'active', '0000-00-00', 'r', '', 3, NULL),
('BSCS-2026-162', 'BSCS-2026-001', 26, 'TEMP-2026-001', 1, NULL, 2026, 1, 'active', '2026-07-20', 'kkk', NULL, NULL, NULL),
('BSDS-2026-001', 'BSDS-2026-001', 18, NULL, 5, '', 2026, 1, 'active', '0000-00-00', 'kk', '', NULL, NULL),
('BSIT-2026-001', 'BSIT-2026-001', 19, NULL, 2, 'A', 2026, 8, 'active', '0000-00-00', 'jawad', 'faall 35', 2, NULL),
('BSIT-2026-105', 'BSIT-2026-002', 29, 'APP-2026-002', 2, NULL, 2026, 1, 'active', '2026-07-20', 'Rashid Khan', NULL, NULL, NULL),
('BSSE-2026-543', 'BSSE-2026-002', 39, 'APP-2026-004', 3, NULL, 2026, 1, 'active', '2026-07-20', 'Tariq Malik', NULL, NULL, NULL),
('BSSE-2026-628', 'BSSE-2026-001', 27, 'TEMP-2026-002', 3, NULL, 2026, 2, 'active', '2026-07-20', 'jkjkj', NULL, NULL, NULL),
('CS-26-001', '163123', 5, 'APP-2026-001', 1, 'A', 2026, 2, 'active', '2026-01-15', 'dewq', '2021', NULL, NULL),
('CS-26-002', '4234', 6, 'APP-2026-003', 1, 'rr', 2026, 1, 'active', '2026-01-15', '14ff', 'wfw43r', 3, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_courses`
--

CREATE TABLE `student_courses` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `course_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `year` int(11) DEFAULT NULL,
  `grade` varchar(2) DEFAULT NULL,
  `gpa` decimal(3,2) DEFAULT NULL,
  `status` enum('enrolled','completed','dropped','failed') DEFAULT 'enrolled',
  `enrollment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_courses`
--

INSERT INTO `student_courses` (`enrollment_id`, `student_id`, `course_id`, `semester`, `year`, `grade`, `gpa`, `status`, `enrollment_date`) VALUES
(3, 'CS-26-002', 1, 3, 2026, NULL, NULL, 'enrolled', '2026-01-20'),
(6, 'CS-26-002', 1, 3, 2026, NULL, NULL, 'enrolled', NULL),
(7, 'CS-26-002', 3, 3, 2026, NULL, NULL, 'enrolled', NULL),
(8, 'BSAI-2026-001', 4, 1, 2026, NULL, NULL, 'enrolled', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `student_course_fees`
--

CREATE TABLE `student_course_fees` (
  `id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `course_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `due_date` date DEFAULT NULL,
  `status` enum('Unpaid','Partially Paid','Paid','Overdue') DEFAULT 'Unpaid',
  `payment_date` date DEFAULT NULL,
  `transaction_id` varchar(50) DEFAULT NULL,
  `payment_method` enum('Cash','Bank Transfer','Credit Card','Online') DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student_enrollments`
--

CREATE TABLE `student_enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` varchar(20) NOT NULL,
  `section_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `enrollment_date` date DEFAULT curdate(),
  `status` enum('Enrolled','Dropped','Completed') DEFAULT 'Enrolled',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student_enrollments`
--

INSERT INTO `student_enrollments` (`enrollment_id`, `student_id`, `section_id`, `semester_id`, `enrollment_date`, `status`, `created_at`, `updated_at`) VALUES
(1, 'CS-26-001', 1, 3, '2026-07-13', 'Enrolled', '2026-07-13 07:38:12', '2026-07-13 07:38:12'),
(2, 'CS-26-002', 1, 3, '2026-07-13', 'Enrolled', '2026-07-13 07:38:12', '2026-07-13 07:38:12'),
(3, 'BSAI-2027-001', 2, 1, '2026-07-16', 'Enrolled', '2026-07-15 19:36:01', '2026-07-15 19:36:01'),
(4, 'BSAI-2027-001', 3, 1, '2026-07-16', 'Enrolled', '2026-07-15 19:37:28', '2026-07-15 19:37:28'),
(5, 'CS-26-002', 3, 1, '2026-07-16', 'Enrolled', '2026-07-15 19:37:43', '2026-07-15 19:37:43'),
(6, 'BSIT-2026-001', 2, 8, '2026-07-18', 'Enrolled', '2026-07-18 06:47:28', '2026-07-18 06:47:28');

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
(0, 0, 2, 2, 'Remove', 878.00, 'jjj', 1, '2026-07-20 17:16:45');

-- --------------------------------------------------------

--
-- Table structure for table `student_promotions`
--

CREATE TABLE `student_promotions` (
  `promotion_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `from_semester_id` int(11) NOT NULL,
  `to_semester_id` int(11) NOT NULL,
  `from_session_id` int(11) NOT NULL,
  `to_session_id` int(11) NOT NULL,
  `promoted_by` int(11) NOT NULL,
  `promotion_date` date NOT NULL DEFAULT curdate(),
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `student_promotions`
--
DELIMITER $$
CREATE TRIGGER `trg_before_promotion_insert` BEFORE INSERT ON `student_promotions` FOR EACH ROW BEGIN
    DECLARE v_from_num TINYINT;
    DECLARE v_total_sem TINYINT;
    DECLARE v_program_id INT;

    SELECT s.semester_number, d.total_semesters, d.department_id
    INTO v_from_num, v_total_sem, v_program_id
    FROM semesters s
    JOIN departments d ON d.department_id = s.department_id
    WHERE s.semester_id = NEW.from_semester_id;

    IF v_from_num >= v_total_sem THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Cannot promote: student is already in the final semester.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `assignment_id` int(11) DEFAULT NULL,
  `student_id` varchar(20) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `submitted_at` datetime DEFAULT current_timestamp(),
  `marks_obtained` decimal(5,2) DEFAULT NULL,
  `feedback` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `teacher_code` varchar(50) NOT NULL,
  `teacher_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `specialization` varchar(100) DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `teacher_code`, `teacher_name`, `email`, `phone`, `department_id`, `specialization`, `status`, `created_at`, `updated_at`) VALUES
(1, 'hhhgg', 'hhhh', 'j2HHS@DJ', 'hhhh', 1, 'jjjh', 'Active', '2026-07-13 06:17:47', '2026-07-13 06:17:47'),
(2, 'jlakjhq', 'iuwq', 'jwqlkd@jd', '832939`', 5, 'wkoe', 'Active', '2026-07-13 06:19:19', '2026-07-13 06:19:19');

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
  `section` varchar(5) NOT NULL DEFAULT 'A',
  `is_primary` tinyint(1) DEFAULT 0,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `assigned_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_courses`
--

INSERT INTO `teacher_courses` (`id`, `teacher_id`, `course_id`, `semester_id`, `session_id`, `section`, `is_primary`, `status`, `assigned_date`, `updated_at`) VALUES
(2, 2, 2, 4, 3, 'A', 0, 'Active', '2026-07-13 06:38:31', '2026-07-13 06:38:31'),
(3, 2, 3, 2, 3, 'A', 0, 'Active', '2026-07-13 06:38:42', '2026-07-13 06:38:42'),
(4, 2, 1, 2, 2, 'A', 1, 'Active', '2026-07-13 06:38:52', '2026-07-13 06:38:52');

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
) ;

--
-- Dumping data for table `timetable`
--

INSERT INTO `timetable` (`id`, `course_id`, `teacher_id`, `day_of_week`, `start_time`, `end_time`, `room_no`, `semester_id`, `session_id`, `section`) VALUES
(2, 1, 1, 'Monday', '11:51:00', '12:52:00', 'i', 2, 1, 'A');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `plain_password` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `is_active`, `created_at`, `plain_password`) VALUES
(1, 'admin@university.edu', '$2y$10$KiRcPozHU5Cl30ZWiwrNcuOEzvkNg8uhMrHF8h8I9WklWnmoSoFHC', 'Dr. Ahmed Khan', '0300-1234567', 1, 1, '2026-06-27 18:33:55', NULL),
(3, 'account@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Usman Malik', '0300-9876543', 6, 1, '2026-06-27 18:33:55', NULL),
(4, 'faculty@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Samina Akhtar', '0300-5555555', 3, 1, '2026-06-27 18:33:55', NULL),
(5, 'student1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Muhammad Ali', '0310-1111111', 4, 1, '2026-06-27 18:33:55', NULL),
(6, 'student2@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usman Ahmed', '0310-3333333', 4, 1, '2026-06-27 18:33:55', NULL),
(9, 'teacher@university.edu', 'dummy_hash', 'Teacher', '03001234568', 2, 1, '2026-07-09 10:18:13', 'teacher123'),
(10, 'student@university.edu', 'dummy_hash', 'Student', '03001234569', 3, 1, '2026-07-09 10:18:13', 'student123'),
(11, 'staff@university.edu', 'dummy_hash', 'Staff Member', '03001234570', 4, 1, '2026-07-09 10:18:13', 'staff123'),
(17, '13@gmaul.com', '$2y$10$qnKS/AxRYaScBn8h90ELhOmefXmu5rGeIdp4etcytotmkn7nuAu52', 'jawad', '32321', 4, 1, '2026-07-09 11:52:26', '1234'),
(18, 'k@f.com', '$2y$10$5SLSZsLXjAlRo1u/2V349eqPh5tZjGKEAeg.XGqLCLlGpqenABXBK', 'nm m', 'k', 4, 1, '2026-07-09 13:52:33', 'k'),
(19, 'jj@k.com', '$2y$10$aKtageyUcRagZihPCYHZ3ugzRwzN/A.vI05akx763hxl2il7uPbhW', 'noman', 'qew', 4, 1, '2026-07-09 14:56:08', 'e'),
(22, 'rd@d.com', '$2y$10$AZf7e8dVFNYkeKBQuT5VdufqRs0SDgzIMK70vwnYe9Fg3yiBDmhiK', 'aga', 'd', 4, 1, '2026-07-15 14:56:01', 'za'),
(24, 'ali@email.com', '$2y$10$COGsup6wgvvKMSfqJUZfvOldhmbFAt2LFb0qYNO3uneQbqhMZBdpm', 'Muhammad Ali', '0310-1111111', 4, 1, '2026-07-20 11:51:26', NULL),
(26, 'kk@d.com', '$2y$10$0JcBijUdVQMB92EGGaBW8.RRhusF3q5oibne1KvTg7NkS6Be0LIkO', 'jawd', '98899', 4, 1, '2026-07-20 11:51:31', NULL),
(27, '88@j.com', '$2y$10$HuQCbS/9BUfLS/hGjVeXw.yC224JYXh3OmJbQsisy1IZVZlCoBchy', 'jkjk', '99888', 4, 1, '2026-07-20 11:54:44', NULL),
(29, 'fatima@email.com', '$2y$10$YJW3zbtYjJHp2d14dmvSxeIPbNaJyehrrCeKt8gaxI0WjYhSkPyay', 'Fatima Khan', '0310-2222222', 4, 1, '2026-07-20 11:57:03', NULL),
(30, 'nrwwwwwwwwwwwww@g.cpm', '$2y$10$l1dTk2GARTcakZZtuMtWP.ERY.4YPfqaOKLbKqsUvmWW8kOalxdN6', 'newwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwwww', '0987656789', 4, 1, '2026-07-20 11:58:57', NULL),
(39, 'ayesha@email.com', '$2y$10$GbbHHWcRyp4lkFLBKk70uucpFn5nYwmvFD/64xD92LiQE4trsFy1q', 'Ayesha Malik', '0310-4444444', 4, 1, '2026-07-20 22:18:02', NULL),
(40, 'sso@university.edu', 'password123', 'SSO Administrator', '0300-1234567', 2, 1, '2026-07-23 10:48:46', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_course_enrollment`
-- (See below for the actual view)
--
CREATE TABLE `vw_course_enrollment` (
`course_code` varchar(20)
,`course_name` varchar(100)
,`enrolled_students` bigint(21)
,`faculty_id` int(11)
);

-- --------------------------------------------------------

--
-- Table structure for table `vw_pending_admissions`
--

CREATE TABLE `vw_pending_admissions` (
  `application_id` int(11) DEFAULT NULL,
  `temp_application_no` varchar(30) DEFAULT NULL,
  `full_name` varchar(150) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `session_id` int(11) DEFAULT NULL,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_student_details`
-- (See below for the actual view)
--
CREATE TABLE `vw_student_details` (
`student_id` varchar(20)
,`full_name` varchar(100)
,`email` varchar(100)
,`phone` varchar(20)
,`program_name` varchar(100)
,`section` varchar(5)
,`batch_year` int(11)
,`semester` int(11)
,`student_status` enum('pending','confirmed','active','inactive','graduated')
);

-- --------------------------------------------------------

--
-- Structure for view `vw_course_enrollment`
--
DROP TABLE IF EXISTS `vw_course_enrollment`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_course_enrollment`  AS SELECT `c`.`course_code` AS `course_code`, `c`.`course_name` AS `course_name`, count(`sc`.`student_id`) AS `enrolled_students`, `f`.`user_id` AS `faculty_id` FROM (((`courses` `c` left join `student_courses` `sc` on(`c`.`course_id` = `sc`.`course_id`)) left join `faculty_courses` `fc` on(`c`.`course_id` = `fc`.`course_id`)) left join `faculty` `f` on(`fc`.`faculty_id` = `f`.`faculty_id`)) GROUP BY `c`.`course_id` ;

-- --------------------------------------------------------

--
-- Structure for view `vw_student_details`
--
DROP TABLE IF EXISTS `vw_student_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_student_details`  AS SELECT `s`.`student_id` AS `student_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `u`.`phone` AS `phone`, `p`.`program_name` AS `program_name`, `s`.`section` AS `section`, `s`.`batch_year` AS `batch_year`, `s`.`semester` AS `semester`, `s`.`status` AS `student_status` FROM ((`students` `s` join `users` `u` on(`s`.`user_id` = `u`.`user_id`)) join `programs` `p` on(`s`.`program_id` = `p`.`program_id`)) ;

--
-- Indexes for dumped tables
--

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
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `program_applied` (`program_applied`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `assignments`
--
ALTER TABLE `assignments`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `faculty_id` (`faculty_id`);

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD UNIQUE KEY `course_code` (`course_code`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `course_fees`
--
ALTER TABLE `course_fees`
  ADD PRIMARY KEY (`fee_id`),
  ADD UNIQUE KEY `unique_course_semester` (`course_id`,`semester_id`),
  ADD KEY `fk_cf_semester` (`semester_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `dept_code` (`department_code`);

--
-- Indexes for table `examinations`
--
ALTER TABLE `examinations`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `fk_exm_session` (`session_id`),
  ADD KEY `fk_exm_sem` (`semester_id`),
  ADD KEY `fk_exm_creator` (`created_by`);

--
-- Indexes for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD PRIMARY KEY (`result_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `exam_id` (`exam_id`);

--
-- Indexes for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD PRIMARY KEY (`exam_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `faculty`
--
ALTER TABLE `faculty`
  ADD PRIMARY KEY (`faculty_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  ADD PRIMARY KEY (`assignment_id`),
  ADD KEY `faculty_id` (`faculty_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `fee_heads`
--
ALTER TABLE `fee_heads`
  ADD PRIMARY KEY (`fee_head_id`),
  ADD UNIQUE KEY `fee_head_name` (`fee_head_name`);

--
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`payment_id`);

--
-- Indexes for table `fee_records`
--
ALTER TABLE `fee_records`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `fee_settings`
--
ALTER TABLE `fee_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

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
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `dept_id` (`dept_id`);

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
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`section_id`),
  ADD KEY `semester_id` (`semester_id`),
  ADD KEY `idx_program` (`program_id`),
  ADD KEY `idx_session` (`session_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `section_courses`
--
ALTER TABLE `section_courses`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `semesters`
--
ALTER TABLE `semesters`
  ADD PRIMARY KEY (`semester_id`);

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
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `idx_section_id` (`section_id`),
  ADD KEY `idx_semester_id` (`semester_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `student_course_fees`
--
ALTER TABLE `student_course_fees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `unique_student_section` (`student_id`,`section_id`,`semester_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_section` (`section_id`),
  ADD KEY `idx_semester` (`semester_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_stf_head` (`student_fee_id`,`fee_head_id`),
  ADD KEY `fk_sfdet_head` (`fee_head_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`teacher_id`),
  ADD UNIQUE KEY `teacher_code` (`teacher_code`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `department_id` (`department_id`);

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
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `course_fees`
--
ALTER TABLE `course_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_results`
--
ALTER TABLE `exam_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_heads`
--
ALTER TABLE `fee_heads`
  MODIFY `fee_head_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_records`
--
ALTER TABLE `fee_records`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fee_settings`
--
ALTER TABLE `fee_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `fee_structure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `installment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  MODIFY `reversal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `question_papers`
--
ALTER TABLE `question_papers`
  MODIFY `paper_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receipts`
--
ALTER TABLE `receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbe_exams`
--
ALTER TABLE `sbe_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  MODIFY `exam_question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_exam_schedule`
--
ALTER TABLE `sbe_exam_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_question_bank`
--
ALTER TABLE `sbe_question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_student_answers`
--
ALTER TABLE `sbe_student_answers`
  MODIFY `student_answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_courses`
--
ALTER TABLE `section_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `semester_courses`
--
ALTER TABLE `semester_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `student_course_fees`
--
ALTER TABLE `student_course_fees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  ADD CONSTRAINT `admission_applications_ibfk_1` FOREIGN KEY (`program_applied`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `admission_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `assignments`
--
ALTER TABLE `assignments`
  ADD CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `assignments_ibfk_2` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`);

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `courses_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `course_fees`
--
ALTER TABLE `course_fees`
  ADD CONSTRAINT `course_fees_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `course_fees_ibfk_2` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cf_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cf_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE CASCADE;

--
-- Constraints for table `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `fk_exm_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_exm_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_exm_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

--
-- Constraints for table `exam_results`
--
ALTER TABLE `exam_results`
  ADD CONSTRAINT `exam_results_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `exam_results_ibfk_2` FOREIGN KEY (`exam_id`) REFERENCES `exam_schedules` (`exam_id`);

--
-- Constraints for table `exam_schedules`
--
ALTER TABLE `exam_schedules`
  ADD CONSTRAINT `exam_schedules_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `faculty`
--
ALTER TABLE `faculty`
  ADD CONSTRAINT `faculty_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `faculty_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `faculty_courses`
--
ALTER TABLE `faculty_courses`
  ADD CONSTRAINT `faculty_courses_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `faculty` (`faculty_id`),
  ADD CONSTRAINT `faculty_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `fee_records`
--
ALTER TABLE `fee_records`
  ADD CONSTRAINT `fee_records_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fee_records_ibfk_2` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`);

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
-- Constraints for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  ADD CONSTRAINT `fk_rev_officer` FOREIGN KEY (`reversed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_rev_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`payment_id`);

--
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`department_id`);

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
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  ADD CONSTRAINT `section_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `section_change_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `semester_courses`
--
ALTER TABLE `semester_courses`
  ADD CONSTRAINT `fk_semc_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`),
  ADD CONSTRAINT `fk_semc_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`application_id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `students_ibfk_section` FOREIGN KEY (`section_id`) REFERENCES `sections` (`section_id`);

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  ADD CONSTRAINT `fk_sfdet_head` FOREIGN KEY (`fee_head_id`) REFERENCES `fee_heads` (`fee_head_id`),
  ADD CONSTRAINT `fk_sfdet_stf` FOREIGN KEY (`student_fee_id`) REFERENCES `student_fee` (`student_fee_id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE SET NULL;

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
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
