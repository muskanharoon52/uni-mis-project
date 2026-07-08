-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2026 at 05:47 PM
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
CREATE DEFINER=`root`@`localhost` PROCEDURE `generate_student_id` (IN `program_code` VARCHAR(10), IN `batch_year` INT, OUT `new_id` VARCHAR(20))   BEGIN
    DECLARE last_number INT;
    
    SELECT COUNT(*) + 1 INTO last_number
    FROM students
    WHERE student_id LIKE CONCAT(program_code, '-', batch_year, '-%');
    
    SET new_id = CONCAT(program_code, '-', batch_year, '-', LPAD(last_number, 3, '0'));
END$$

DELIMITER ;

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

INSERT INTO `admission_applications` (`application_id`, `name`, `father_name`, `email`, `phone`, `cnic`, `program_applied`, `documents_path`, `status`, `test_score`, `applied_date`, `reviewed_by`, `review_date`) VALUES
('APP-2026-001', 'Muhammad Ali', 'Ahmed Ali', 'ali@email.com', '0310-1111111', '42101-1234567-1', 1, NULL, 'approved', 85.50, '2026-06-27 18:33:55', NULL, NULL),
('APP-2026-002', 'Fatima Khan', 'Rashid Khan', 'fatima@email.com', '0310-2222222', '42101-7654321-2', 2, NULL, 'pending', NULL, '2026-06-27 18:33:55', NULL, NULL),
('APP-2026-003', 'Usman Ahmed', 'Zafar Ahmed', 'usman@email.com', '0310-3333333', '42101-9876543-3', 1, NULL, 'approved', 78.00, '2026-06-27 18:33:55', NULL, NULL),
('APP-2026-004', 'Ayesha Malik', 'Tariq Malik', 'ayesha@email.com', '0310-4444444', '42101-4567890-4', 3, NULL, 'waiting_test', NULL, '2026-06-27 18:33:55', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_code` varchar(20) NOT NULL,
  `course_name` varchar(100) NOT NULL,
  `credit_hours` int(11) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_code`, `course_name`, `credit_hours`, `program_id`, `semester`, `description`) VALUES
(1, 'CS-301', 'Database Systems', 3, 1, 3, NULL),
(2, 'CS-302', 'Operating Systems', 3, 1, 3, NULL),
(3, 'CS-303', 'Computer Networks', 3, 1, 3, NULL),
(4, 'CS-304', 'Software Engineering', 3, 1, 4, NULL),
(5, 'CS-305', 'Data Structures', 3, 1, 2, NULL),
(6, 'IT-301', 'Web Development', 3, 2, 3, NULL),
(7, 'IT-302', 'Network Security', 3, 2, 4, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `dept_id` int(11) NOT NULL,
  `dept_code` varchar(10) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`dept_id`, `dept_code`, `dept_name`, `created_at`) VALUES
(1, 'CS', 'Computer Science', '2026-06-27 18:33:55'),
(2, 'IT', 'Information Technology', '2026-06-27 18:33:55'),
(3, 'SE', 'Software Engineering', '2026-06-27 18:33:55'),
(4, 'AI', 'Artificial Intelligence', '2026-06-27 18:33:55'),
(5, 'DS', 'Data Science', '2026-06-27 18:33:55');

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
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `program_code` varchar(20) NOT NULL,
  `program_name` varchar(100) NOT NULL,
  `dept_id` int(11) DEFAULT NULL,
  `duration_years` int(11) DEFAULT 4
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `program_code`, `program_name`, `dept_id`, `duration_years`) VALUES
(1, 'BSCS', 'BS Computer Science', 1, 4),
(2, 'BSIT', 'BS Information Technology', 2, 4),
(3, 'BSSE', 'BS Software Engineering', 3, 4),
(4, 'BSAI', 'BS Artificial Intelligence', 4, 4),
(5, 'BSDS', 'BS Data Science', 5, 4);

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
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` varchar(20) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `application_id` varchar(20) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `section` varchar(5) DEFAULT NULL,
  `batch_year` int(11) DEFAULT NULL,
  `semester` int(11) DEFAULT 1,
  `status` enum('pending','confirmed','active','inactive','graduated') DEFAULT 'pending',
  `enrollment_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `user_id`, `application_id`, `program_id`, `section`, `batch_year`, `semester`, `status`, `enrollment_date`) VALUES
('CS-26-001', 5, 'APP-2026-001', 1, 'A', 2026, 2, 'active', '2026-01-15'),
('CS-26-002', 6, 'APP-2026-003', 1, 'A', 2026, 2, 'confirmed', '2026-01-15');

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
(1, 'CS-26-001', 1, 3, 2026, NULL, NULL, 'enrolled', '2026-01-20'),
(2, 'CS-26-001', 2, 3, 2026, NULL, NULL, 'enrolled', '2026-01-20'),
(3, 'CS-26-002', 1, 3, 2026, NULL, NULL, 'enrolled', '2026-01-20');

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
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password_hash`, `full_name`, `phone`, `role_id`, `is_active`, `created_at`) VALUES
(1, 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Ahmed Khan', '0300-1234567', 1, 1, '2026-06-27 18:33:55'),
(2, 'sso@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Ali Raza', '0300-7654321', 2, 1, '2026-06-27 18:33:55'),
(3, 'account@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mr. Usman Malik', '0300-9876543', 6, 1, '2026-06-27 18:33:55'),
(4, 'faculty@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dr. Samina Akhtar', '0300-5555555', 3, 1, '2026-06-27 18:33:55'),
(5, 'student1@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Muhammad Ali', '0310-1111111', 4, 1, '2026-06-27 18:33:55'),
(6, 'student2@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Usman Ahmed', '0310-3333333', 4, 1, '2026-06-27 18:33:55');

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
-- Indexes for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `program_applied` (`program_applied`),
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
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`dept_id`),
  ADD UNIQUE KEY `dept_code` (`dept_code`);

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
-- Indexes for table `fee_records`
--
ALTER TABLE `fee_records`
  ADD PRIMARY KEY (`fee_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `verified_by` (`verified_by`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`),
  ADD UNIQUE KEY `program_code` (`program_code`),
  ADD KEY `dept_id` (`dept_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  ADD PRIMARY KEY (`request_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `program_id` (`program_id`);

--
-- Indexes for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `course_id` (`course_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `assignment_id` (`assignment_id`),
  ADD KEY `student_id` (`student_id`);

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
-- AUTO_INCREMENT for table `assignments`
--
ALTER TABLE `assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `dept_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

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
-- AUTO_INCREMENT for table `fee_records`
--
ALTER TABLE `fee_records`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  MODIFY `request_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admission_applications`
--
ALTER TABLE `admission_applications`
  ADD CONSTRAINT `admission_applications_ibfk_1` FOREIGN KEY (`program_applied`) REFERENCES `programs` (`program_id`),
  ADD CONSTRAINT `admission_applications_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

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
  ADD CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

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
  ADD CONSTRAINT `faculty_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`dept_id`);

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
-- Constraints for table `programs`
--
ALTER TABLE `programs`
  ADD CONSTRAINT `programs_ibfk_1` FOREIGN KEY (`dept_id`) REFERENCES `departments` (`dept_id`);

--
-- Constraints for table `section_change_requests`
--
ALTER TABLE `section_change_requests`
  ADD CONSTRAINT `section_change_requests_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `section_change_requests_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `students`
--
ALTER TABLE `students`
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `students_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `admission_applications` (`application_id`),
  ADD CONSTRAINT `students_ibfk_3` FOREIGN KEY (`program_id`) REFERENCES `programs` (`program_id`);

--
-- Constraints for table `student_courses`
--
ALTER TABLE `student_courses`
  ADD CONSTRAINT `student_courses_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`),
  ADD CONSTRAINT `student_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`);

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`assignment_id`),
  ADD CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
