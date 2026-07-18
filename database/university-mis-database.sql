-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 17, 2026 at 09:01 PM
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

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL,
  `class_date` date NOT NULL,
  `status` enum('Present','Absent','Leave') NOT NULL,
  `marked_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_title` varchar(150) NOT NULL,
  `credit_hours` tinyint(4) NOT NULL DEFAULT 3,
  `department_id` int(11) NOT NULL,
  `semester_id` int(11) NOT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
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
) ;

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
  `role_name` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ;

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
) ;

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
-- Table structure for table `semesters`
--

CREATE TABLE `semesters` (
  `semester_id` int(11) NOT NULL,
  `semester_name` varchar(50) NOT NULL,
  `semester_number` tinyint(4) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `status` enum('Active','Inactive') NOT NULL DEFAULT 'Active',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Indexes for table `fee_heads`
--
ALTER TABLE `fee_heads`
  ADD PRIMARY KEY (`fee_head_id`),
  ADD UNIQUE KEY `fee_head_name` (`fee_head_name`);

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
-- Indexes for table `student_promotions`
--
ALTER TABLE `student_promotions`
  ADD PRIMARY KEY (`promotion_id`),
  ADD UNIQUE KEY `uq_student_target` (`student_id`,`to_semester_id`,`to_session_id`),
  ADD KEY `fk_promo_from_sem` (`from_semester_id`),
  ADD KEY `fk_promo_to_sem` (`to_semester_id`),
  ADD KEY `fk_promo_from_ses` (`from_session_id`),
  ADD KEY `fk_promo_to_ses` (`to_session_id`),
  ADD KEY `fk_promo_officer` (`promoted_by`);

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
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `examinations`
--
ALTER TABLE `examinations`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_heads`
--
ALTER TABLE `fee_heads`
  MODIFY `fee_head_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sbe_auth_users`
--
ALTER TABLE `sbe_auth_users`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `semester_courses`
--
ALTER TABLE `semester_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sessions`
--
ALTER TABLE `sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sso_applications`
--
ALTER TABLE `sso_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;

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
-- AUTO_INCREMENT for table `student_promotions`
--
ALTER TABLE `student_promotions`
  MODIFY `promotion_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT;

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
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

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
  ADD CONSTRAINT `fk_course_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `fk_course_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`);

--
-- Constraints for table `examinations`
--
ALTER TABLE `examinations`
  ADD CONSTRAINT `fk_exm_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_exm_sem` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_exm_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`);

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
-- Constraints for table `student_promotions`
--
ALTER TABLE `student_promotions`
  ADD CONSTRAINT `fk_promo_from_sem` FOREIGN KEY (`from_semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_promo_from_ses` FOREIGN KEY (`from_session_id`) REFERENCES `sessions` (`session_id`),
  ADD CONSTRAINT `fk_promo_officer` FOREIGN KEY (`promoted_by`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_promo_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `fk_promo_to_sem` FOREIGN KEY (`to_semester_id`) REFERENCES `semesters` (`semester_id`),
  ADD CONSTRAINT `fk_promo_to_ses` FOREIGN KEY (`to_session_id`) REFERENCES `sessions` (`session_id`);

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
