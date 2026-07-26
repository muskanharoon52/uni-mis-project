-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 26, 2026 at 10:14 PM
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
  `application_id` int(11) NOT NULL,
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
-- Dumping data for table `admission_applications`
--

INSERT INTO `admission_applications` (`application_id`, `temp_application_no`, `full_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `session_id`, `applied_semester_id`, `application_status`, `submitted_at`, `reviewed_by`, `reviewed_at`, `rejection_reason`, `created_at`, `updated_at`) VALUES
(13, '', 'Muhammad Ali', 'Ahmed Ali', '42101-1234567-1', '2000-01-15', 'Male', '0310-1111111', 'ali@email.com', 'House #12, Street 5, Lahore', 1, 1, 1, 'Approved', '2026-07-26 12:10:32', NULL, NULL, NULL, '2026-07-26 07:10:32', '2026-07-26 07:10:32');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 0, 'quiz', '2026-02-01', '10:00:00', '11:00:00', 'A1', 'Scheduled', '2026-07-26 06:14:47');

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
  `id` int(11) NOT NULL,
  `student_id` int(11) DEFAULT NULL,
  `fee_type` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date NOT NULL,
  `payment_method` varchar(20) DEFAULT 'cash',
  `status` varchar(20) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `fee_structures`
--

CREATE TABLE `fee_structures` (
  `fee_structure_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Table structure for table `lms_academic_calendar`
--

CREATE TABLE `lms_academic_calendar` (
  `event_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `event_date` date DEFAULT NULL,
  `event_type` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `lms_marks`
--

CREATE TABLE `lms_marks` (
  `id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `student_user_id` int(11) NOT NULL,
  `component` varchar(50) NOT NULL,
  `marks_obtained` decimal(5,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'BS Computer Science', 'BSCS', 1, 4, 8, 'Active', '2026-07-26 07:10:32'),
(3, 'BS Information Technology', 'BSIT', 2, 4, 8, 'Active', '2026-07-26 07:10:32'),
(4, 'BS Software Engineering', 'BSSE', 3, 4, 8, 'Active', '2026-07-26 07:10:32'),
(5, 'BS Artificial Intelligence', 'BSAI', 4, 4, 8, 'Active', '2026-07-26 07:10:32'),
(6, 'BS Data Science', 'BSDS', 5, 4, 8, 'Active', '2026-07-26 07:10:32');

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
(6, 'Examiner'),
(3, 'Finance Officer'),
(4, 'Student'),
(2, 'Teacher');

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
(1, 'Teacher', '5001', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Dr. Sara Khan', NULL, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-26 18:28:03'),
(2, 'Teacher', '5002', '$2y$10$Dbil/GL2koHeVKRp7tXy7uR.coHqiJZh9Wz8dIIj/zdjMxXGAdxwC', 'Teacher Demo', NULL, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-26 18:28:03'),
(3, 'Student', '9001', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Ali Raza', NULL, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-26 18:28:03'),
(4, 'Student', '9002', '$2y$10$NVy5mVCpcgfv94f5DK2QnORaQ1lG8J8CgXieLEDk.VsINn1jumOg2', 'Student Demo', NULL, NULL, 'Active', '2026-07-26 18:28:03', '2026-07-26 18:28:03');

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `batch_year` smallint(6) NOT NULL,
  `admission_date` date NOT NULL,
  `status` enum('Active','Freeze','Graduated','Dropped','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `teacher_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `teacher_name` varchar(150) NOT NULL,
  `designation` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`teacher_id`, `user_id`, `teacher_name`, `designation`, `email`, `phone`, `department_id`, `status`, `created_at`) VALUES
(1, 19, 'Dr. Sara Khan', 'Professor', 'sara.khan@university.edu', NULL, 1, 'Active', '2026-07-26 18:14:23'),
(2, 20, 'Teacher Demo', 'Lecturer', 'teacher.demo@university.edu', NULL, 1, 'Active', '2026-07-26 18:14:23');

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
(9, 'Finance Officer', 'finance', NULL, 'finance@university.edu', NULL, '$2y$10$/1EMmmb0U6EmkY.7Q4J41Oj4kcccQGXFvFlohIcYanxSTdUB5NiMy', 3, NULL, NULL, 'Active', '2026-07-25 17:45:49', '2026-07-23 03:23:21', '2026-07-26 18:43:58'),
(10, 'Administrator', 'admin', NULL, 'admin@university.edu', NULL, '$2y$10$UrlVAX.MWByoeJgrsjB.v.6YNR0qYzjgLVnyhl7qalx1ODwE20Y9.', 1, NULL, NULL, 'Active', '2026-07-23 14:47:29', '2026-07-23 03:23:21', '2026-07-26 18:43:59'),
(11, 'Teacher Demo', 'teacher', NULL, 'teacher@university.edu', NULL, '$2y$10$gaFZIA81NyLrtwP5RjqQ0.4iK67mEhd4PXmR6ss15ciF8sDjSScr2', 2, NULL, NULL, 'Active', '2026-07-25 15:50:23', '2026-07-23 03:23:21', '2026-07-26 18:43:59'),
(12, 'Student Demo', 'student', NULL, 'student@university.edu', NULL, '$2y$10$ru8gezLWKYtIiFbnXqsLMOaHNp1CynW3ATdkojjB0erRUIV1rxQne', 4, NULL, NULL, 'Active', '2026-07-23 14:45:53', '2026-07-23 03:23:21', '2026-07-26 18:43:59'),
(13, 'Examiner', 'examiner', NULL, 'examiner@university.edu', NULL, '$2y$10$8gqW4wQ3msFNTGq0UcouMurF4Ly4bIGB6Z/KT9JIPDSWjLhsZlqK.', 6, NULL, NULL, 'Active', NULL, '2026-07-25 12:59:01', '2026-07-26 18:43:59'),
(14, 'SSO Admin', 'sso_admin', NULL, 'sso@university.edu', NULL, '$2y$10$/XVw7LehbAKgFuNsQgLyyuBbySScpJYr8x5eceFxrrWCLDxPic1O2', 1, NULL, NULL, 'Active', NULL, '2026-07-26 10:57:00', '2026-07-26 18:44:00'),
(15, 'Exam Officer', 'exam_admin', NULL, 'exam@university.edu', NULL, '$2y$10$WA2OIYd4t1eNpjzL0rzHae2exiL0qEKe3RLxNHJ8qeGNhJ5qcjQSq', 6, NULL, NULL, 'Active', NULL, '2026-07-26 10:57:00', '2026-07-26 18:44:00'),
(19, 'Dr. Sara Khan', 'sara.khan', '5001', 'sara.khan@university.edu', NULL, '$2y$10$CHpKfvUqpcOBhCES1bCVgO9urK3bhn5o0NOWrqJFUERvDyTHfZ1Mq', 2, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(20, 'Teacher Demo', 'teacher.demo', '5002', 'teacher.demo@university.edu', NULL, '$2y$10$KpNnLcnl140COvIZtgbj8eUhtQIKwRC8iGYBB42jfkE2YXo7ehhLK', 2, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(21, 'Ali Raza', 'ali.raza', '9001', 'ali.raza@university.edu', NULL, '$2y$10$Jf22SiM7FGyoT9BLXIl24uGVuivmpqSdsi.Kyj8r0V1epH3rvfgvS', 4, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(22, 'Student Demo', 'student.demo', '9002', 'student.demo@university.edu', NULL, '$2y$10$JHSLxirVE9m2CKndkrZSmu.FUcYaNjDt.pbep7x.GLpzg7ha/cd2q', 4, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00');

-- --------------------------------------------------------

--
-- Stand-in structure for view `vw_pending_admissions`
-- (See below for the actual view)
--
CREATE TABLE `vw_pending_admissions` (
`application_id` int(11)
,`temp_application_no` varchar(30)
,`full_name` varchar(150)
,`program_id` int(11)
,`session_id` int(11)
,`submitted_at` datetime
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
  ADD UNIQUE KEY `temp_application_no` (`temp_application_no`),
  ADD KEY `fk_appl_program` (`program_id`),
  ADD KEY `fk_appl_session` (`session_id`),
  ADD KEY `fk_appl_sem` (`applied_semester_id`),
  ADD KEY `fk_appl_reviewer` (`reviewed_by`);

--
-- Indexes for table `admission_students`
--
ALTER TABLE `admission_students`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`);

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
-- Indexes for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `fee_records`
--
ALTER TABLE `fee_records`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `idx_student` (`student_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD PRIMARY KEY (`fee_structure_id`),
  ADD UNIQUE KEY `uq_program_session_sem` (`program_id`,`session_id`,`semester_id`),
  ADD KEY `fk_fs_session` (`session_id`),
  ADD KEY `fk_fs_semester` (`semester_id`),
  ADD KEY `fk_fs_creator` (`created_by`);

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
  ADD KEY `fk_stu_cur_sem` (`current_semester_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_course` (`student_id`,`course_id`);

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
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `admission_applications`
--
ALTER TABLE `admission_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `admission_students`
--
ALTER TABLE `admission_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `exam_attendance`
--
ALTER TABLE `exam_attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_heads`
--
ALTER TABLE `fee_heads`
  MODIFY `fee_head_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_records`
--
ALTER TABLE `fee_records`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `fee_structure_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `installments`
--
ALTER TABLE `installments`
  MODIFY `installment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_academic_calendar`
--
ALTER TABLE `lms_academic_calendar`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_announcements`
--
ALTER TABLE `lms_announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_applications`
--
ALTER TABLE `lms_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_assignments`
--
ALTER TABLE `lms_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_course_materials`
--
ALTER TABLE `lms_course_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_datesheets`
--
ALTER TABLE `lms_datesheets`
  MODIFY `datesheet_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_enrollments`
--
ALTER TABLE `lms_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_exams`
--
ALTER TABLE `lms_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_fees`
--
ALTER TABLE `lms_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_lectures`
--
ALTER TABLE `lms_lectures`
  MODIFY `lecture_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_marks`
--
ALTER TABLE `lms_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_mark_finalizations`
--
ALTER TABLE `lms_mark_finalizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_messages`
--
ALTER TABLE `lms_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_notifications`
--
ALTER TABLE `lms_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_queries`
--
ALTER TABLE `lms_queries`
  MODIFY `query_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_quizzes`
--
ALTER TABLE `lms_quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_quiz_results`
--
ALTER TABLE `lms_quiz_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_reports`
--
ALTER TABLE `lms_reports`
  MODIFY `report_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_settings`
--
ALTER TABLE `lms_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_student_answers`
--
ALTER TABLE `lms_student_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_submissions`
--
ALTER TABLE `lms_submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_timetable`
--
ALTER TABLE `lms_timetable`
  MODIFY `timetable_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lms_transcripts`
--
ALTER TABLE `lms_transcripts`
  MODIFY `transcript_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `payment_reversals`
--
ALTER TABLE `payment_reversals`
  MODIFY `reversal_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `question_bank`
--
ALTER TABLE `question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `sbe_auth_users`
--
ALTER TABLE `sbe_auth_users`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sbe_exams`
--
ALTER TABLE `sbe_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  MODIFY `exam_question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_exam_results`
--
ALTER TABLE `sbe_exam_results`
  MODIFY `exam_result_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `sbe_student_exams`
--
ALTER TABLE `sbe_student_exams`
  MODIFY `student_exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `scholarships`
--
ALTER TABLE `scholarships`
  MODIFY `scholarship_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `section_courses`
--
ALTER TABLE `section_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `semester_courses`
--
ALTER TABLE `semester_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `sso_applications`
--
ALTER TABLE `sso_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee`
--
ALTER TABLE `student_fee`
  MODIFY `student_fee_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_assignment`
--
ALTER TABLE `student_fee_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_fee_discounts`
--
ALTER TABLE `student_fee_discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_grades`
--
ALTER TABLE `student_grades`
  MODIFY `grade_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_promotions`
--
ALTER TABLE `student_promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

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
  ADD CONSTRAINT `fk_appl_program` FOREIGN KEY (`program_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_appl_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_appl_sem` FOREIGN KEY (`applied_semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_appl_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

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
-- Constraints for table `fee_payments`
--
ALTER TABLE `fee_payments`
  ADD CONSTRAINT `fee_payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `admission_students` (`id`);

--
-- Constraints for table `fee_structures`
--
ALTER TABLE `fee_structures`
  ADD CONSTRAINT `fk_fs_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_fs_program` FOREIGN KEY (`program_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_fs_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_fs_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

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
  ADD CONSTRAINT `fk_stu_program` FOREIGN KEY (`program_id`) REFERENCES `departments` (`department_id`);

--
-- Constraints for table `student_fee`
--
ALTER TABLE `student_fee`
  ADD CONSTRAINT `fk_stf_fs` FOREIGN KEY (`fee_structure_id`) REFERENCES `fee_structures` (`fee_structure_id`),
  ADD CONSTRAINT `fk_stf_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_stf_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
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
  ADD CONSTRAINT `fk_sfdet_head` FOREIGN KEY (`fee_head_id`) REFERENCES `fee_heads` (`fee_head_id`),
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
