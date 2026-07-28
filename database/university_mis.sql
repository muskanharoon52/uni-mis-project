-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 28, 2026 at 05:08 AM
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
(28, 'Finance', 'Payment Received', 'payments', 11, 9, 'Amount: 50000.00', '2026-07-27 20:09:14');

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
(113, 'APP-2026-45877', 'Ali', 'khan', '63871357817132', '2003-02-10', 'Male', '6157351656152', 'ali@gmail.com', 'peshawar', 1, 5, 1, 'Admitted', '2026-07-27 21:29:01', 14, '2026-07-28 00:36:26', '', '2026-07-27 19:29:01', '2026-07-27 19:36:26'),
(114, 'APP-2026-58915', 'Sajjal', 'Khan', '7823728832273', '2000-02-01', 'Female', '62736268283', 'sajjal@gmail.com', '..', 1, 5, 14, 'Submitted', '2026-07-27 22:46:59', NULL, NULL, NULL, '2026-07-27 20:46:59', '2026-07-27 20:46:59'),
(115, 'APP-2026-35145', 'Maryam', 'Ahmed', '67131526612618', '2000-10-10', 'Female', '6127617263782', 'maryam@gmail.com', 'Pakistan', 2, 6, 2, 'Submitted', '2026-07-28 04:55:26', NULL, NULL, NULL, '2026-07-28 02:55:26', '2026-07-28 02:55:26');

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
-- Table structure for table `admission_scholarships`
--

CREATE TABLE `admission_scholarships` (
  `scholarship_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
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

INSERT INTO `admission_scholarships` (`scholarship_id`, `student_id`, `application_id`, `scholarship_type`, `description`, `scholarship_name`, `percentage`, `amount`, `duration`, `semester_id`, `session_id`, `status`, `application_status`, `approved_by`, `approved_date`, `rejection_reason`, `remarks`, `created_at`, `updated_at`) VALUES
(1, 25, NULL, 'Merit', NULL, 'Merit Scholarship - 50%', 50.00, NULL, 'Full Program', 1, 5, 'Active', 'Approved', NULL, '2026-01-20', NULL, 'Outstanding academic performance', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(2, 26, NULL, 'Merit', NULL, 'Merit Scholarship - 40%', 40.00, NULL, 'Full Program', 1, 5, 'Active', 'Approved', NULL, '2026-01-20', NULL, 'Excellent grades in entrance test', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(3, 27, NULL, 'Need-based', NULL, 'Need-based Financial Aid', 30.00, NULL, 'One Semester', 9, 5, 'Active', 'Approved', NULL, '2026-01-25', NULL, 'Based on financial need assessment', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(4, 28, NULL, 'Need-based', NULL, 'Need-based Financial Aid - 25%', 25.00, NULL, 'One Semester', 9, 5, 'Active', 'Approved', NULL, '2026-01-25', NULL, 'Financial need verified', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(5, 29, NULL, 'Sports', NULL, 'Sports Excellence Scholarship', 25.00, NULL, 'One Year', 17, 5, 'Active', 'Approved', NULL, '2026-01-30', NULL, 'Represented university in national sports', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(6, 30, NULL, 'Sports', NULL, 'Sports Excellence - 20%', 20.00, NULL, 'One Year', 17, 5, 'Active', 'Approved', NULL, '2026-01-30', NULL, 'Represented university in athletics', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(7, 31, NULL, 'Talent', NULL, 'Talent Scholarship - 20%', 20.00, NULL, 'Full Program', 25, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Under review by scholarship committee', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(8, 32, NULL, 'Talent', NULL, 'Talent Scholarship - 15%', 15.00, NULL, 'Full Program', 25, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Waiting for additional documentation', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(9, 33, NULL, 'Merit', NULL, 'Merit Scholarship - 30%', 30.00, NULL, 'Full Program', 33, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Awaiting verification of academic records', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(10, 34, NULL, 'Merit', NULL, 'Merit Scholarship - 25%', 25.00, NULL, 'Full Program', 33, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Application received - Under initial review', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(11, 35, NULL, 'Special', NULL, 'Special Scholarship - 35%', 35.00, NULL, 'One Semester', 2, 5, 'Active', 'Approved', NULL, '2026-02-01', NULL, 'Special consideration - Orphan student', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(12, 36, NULL, 'Special', NULL, 'Special Scholarship - 30%', 30.00, NULL, 'One Semester', 2, 5, 'Approved', 'Approved', NULL, '2026-02-01', NULL, 'Special consideration - Disabled student', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(13, 37, NULL, 'Merit', NULL, 'Merit Scholarship - 30%', 30.00, 30000.00, 'Full Program', 34, 5, 'Approved', 'Approved', NULL, '2026-02-10', NULL, 'Approved - Outstanding academic record', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(14, 25, NULL, 'Merit', NULL, 'Merit Scholarship Renewal - 50%', 50.00, 50000.00, 'Full Program', 2, 5, 'Pending', 'Under Review', NULL, NULL, NULL, 'Renewal application for Semester 2', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(15, 29, NULL, 'Sports', NULL, 'Sports Scholarship Renewal', 25.00, 25000.00, 'One Year', 18, 5, 'Pending', 'Submitted', NULL, NULL, NULL, 'Renewal for second year', '2026-07-27 18:16:12', '2026-07-27 18:16:12'),
(17, 34, 6712, 'Merit', '..', 'Scholarship1', 999.99, 500.00, '1 year', NULL, NULL, 'Pending', 'Submitted', NULL, '2026-02-02', 'dcalnkalncl', 'acsnjncanc', '2026-07-28 03:02:04', '2026-07-28 03:02:04');

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
(10, 17, 25, 940.00, 1000.00, 94.00, 100.00, 50000.00, 0.00, 'pending', NULL, '2026-07-28 03:05:11', '2026-07-28 03:05:11');

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
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `application_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admission_students`
--

INSERT INTO `admission_students` (`id`, `student_id`, `full_name`, `student_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `department_id`, `status`, `created_at`, `updated_at`, `application_id`) VALUES
(1, 'STD026', 'Student 1', 'Student 1', 'Father 1', '42101-1234567-01-1', '2026-09-02', 'Male', 'value_1', 'faculty1@uni.edu', 'Lahore, Pakistan', 2, 2, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(2, 'STD027', 'Student 2', 'Student 2', 'Father 2', '42101-1234567-02-2', '2026-09-03', 'Male', 'value_2', 'faculty2@uni.edu', 'Lahore, Pakistan', 3, 3, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(3, 'STD028', 'Student 3', 'Student 3', 'Father 3', '42101-1234567-03-0', '2026-09-04', 'Male', 'value_3', 'faculty3@uni.edu', 'Lahore, Pakistan', 4, 4, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(4, 'STD029', 'Student 4', 'Student 4', 'Father 4', '42101-1234567-04-1', '2026-09-05', 'Male', 'value_4', 'faculty4@uni.edu', 'Lahore, Pakistan', 5, 5, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(5, 'STD030', 'Student 5', 'Student 5', 'Father 5', '42101-1234567-05-2', '2026-09-06', 'Male', 'value_5', 'faculty5@uni.edu', 'Lahore, Pakistan', 6, 6, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(6, 'STD031', 'Student 6', 'Student 6', 'Father 6', '42101-1234567-06-0', '2026-09-07', 'Male', 'value_6', 'faculty6@uni.edu', 'Lahore, Pakistan', 1, 1, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(7, 'STD032', 'Student 7', 'Student 7', 'Father 7', '42101-1234567-07-1', '2026-09-08', 'Male', 'value_7', 'faculty7@uni.edu', 'Lahore, Pakistan', 2, 2, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(8, 'STD033', 'Student 8', 'Student 8', 'Father 8', '42101-1234567-08-2', '2026-09-09', 'Male', 'value_8', 'faculty8@uni.edu', 'Lahore, Pakistan', 3, 3, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(9, 'STD034', 'Student 9', 'Student 9', 'Father 9', '42101-1234567-09-0', '2026-09-10', 'Male', 'value_9', 'faculty9@uni.edu', 'Lahore, Pakistan', 4, 4, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(10, 'STD035', 'Student 10', 'Student 10', 'Father 10', '42101-1234567-00-1', '2026-09-11', 'Male', 'value_10', 'faculty10@uni.edu', 'Lahore, Pakistan', 5, 5, 'active', '2026-07-15 05:30:00', '2026-07-15 05:30:00', NULL),
(12, 'UNI-2026-00001', '', 'Ali', 'khan', '63871357817132', '2003-02-10', 'Male', '6157351656152', 'ali@gmail.com', 'peshawar', 1, NULL, 'active', '2026-07-27 19:36:26', '2026-07-27 19:36:26', 113);

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(33, 'PHY102', NULL, 'Electricity and Magnetism', NULL, 4, NULL, NULL, 1, 'Active', '2026-07-27 18:36:43', NULL, 0);

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
(1, 0, 1, 100.00, 100.00, 'B', 41.00, '', 'Remark 1', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(2, 0, 2, 100.00, 100.00, 'C', 42.00, '', 'Remark 2', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(3, 0, 3, 100.00, 100.00, 'D', 43.00, '', 'Remark 3', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(4, 0, 4, 100.00, 100.00, 'F', 44.00, '', 'Remark 4', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(5, 0, 5, 100.00, 100.00, 'A', 45.00, '', 'Remark 5', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(6, 0, 6, 100.00, 100.00, 'B', 46.00, '', 'Remark 6', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(7, 0, 7, 100.00, 100.00, 'C', 47.00, '', 'Remark 7', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(8, 0, 8, 100.00, 100.00, 'D', 48.00, '', 'Remark 8', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(9, 0, 9, 100.00, 100.00, 'F', 49.00, '', 'Remark 9', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(10, 0, 10, 100.00, 100.00, 'A', 50.00, '', 'Remark 10', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(11, 0, 11, 100.00, 100.00, 'B', 51.00, '', 'Remark 11', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(12, 0, 12, 100.00, 100.00, 'C', 52.00, '', 'Remark 12', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(13, 0, 13, 100.00, 100.00, 'D', 53.00, '', 'Remark 13', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(14, 0, 14, 100.00, 100.00, 'F', 54.00, '', 'Remark 14', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(15, 0, 15, 100.00, 100.00, 'A', 55.00, '', 'Remark 15', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(16, 0, 16, 100.00, 100.00, 'B', 56.00, '', 'Remark 16', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(17, 0, 17, 100.00, 100.00, 'C', 57.00, '', 'Remark 17', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(18, 0, 18, 100.00, 100.00, 'D', 58.00, '', 'Remark 18', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(19, 0, 19, 100.00, 100.00, 'F', 59.00, '', 'Remark 19', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(20, 0, 20, 100.00, 100.00, 'A', 60.00, '', 'Remark 20', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(21, 0, 21, 100.00, 100.00, 'B', 61.00, '', 'Remark 21', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(22, 0, 22, 100.00, 100.00, 'C', 62.00, '', 'Remark 22', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(23, 0, 23, 100.00, 100.00, 'D', 63.00, '', 'Remark 23', 2, 2, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00'),
(24, 0, 24, 100.00, 100.00, 'F', 64.00, '', 'Remark 24', 1, 1, '2026-07-15 10:30:00', '2026-07-15 05:30:00', '2026-07-15 05:30:00');

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
(53, 16, 'Final', '2026-12-23', '10:00:00', '12:00:00', 'R53', 'Scheduled', '2026-07-26 07:00:00');

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
(1, 'Tuition Fee', 'Annual tuition fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(2, 'Lab Fee', 'Laboratory usage fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(3, 'Library Fee', 'Library card and resources fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(4, 'Exam Fee', 'Examination fee per semester', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(5, 'Sports Fee', 'Sports facility fee', 'Active', NULL, '2026-07-27 16:30:18', '2026-07-27 16:30:18');

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

--
-- Dumping data for table `fee_structures`
--

INSERT INTO `fee_structures` (`fee_structure_id`, `program_id`, `session_id`, `semester_id`, `total_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 3, 5, 9, 65000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(2, 3, 6, 9, 65000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(3, 4, 5, 10, 70000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(4, 4, 6, 10, 70000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(5, 5, 5, 11, 75000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(6, 5, 6, 11, 75000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(7, 6, 5, 12, 80000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(8, 6, 6, 12, 80000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(9, 7, 5, 13, 85000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(10, 7, 6, 13, 85000.00, 'Active', 10, '2026-07-27 16:30:18', '2026-07-27 16:30:18'),
(16, 1, 1, 1, 100000.00, 'Active', 9, '2026-07-27 20:04:03', '2026-07-27 20:04:03'),
(17, 1, 5, 1, 100000.00, 'Active', 9, '2026-07-27 20:04:16', '2026-07-27 20:04:16'),
(19, 2, 5, 2, 64000.00, 'Active', 9, '2026-07-27 20:04:49', '2026-07-27 20:04:49'),
(20, 3, 6, 2, 64000.00, 'Active', 9, '2026-07-27 20:06:11', '2026-07-27 20:06:11');

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
(60, 20, 5, 2000.00);

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
(1, 14, 'Course 1', 'Demo content for record 1', 0, '2026-07-15 05:30:00'),
(2, 15, 'Course 2', 'Demo content for record 2', 0, '2026-07-15 05:30:00'),
(3, 16, 'Course 3', 'Demo content for record 3', 0, '2026-07-15 05:30:00'),
(4, 17, 'Course 4', 'Demo content for record 4', 0, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 'Demo content for record 5', 0, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 'Demo content for record 6', 0, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 'Demo content for record 7', 0, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 'Demo content for record 8', 0, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 'Demo content for record 9', 0, '2026-07-15 05:30:00'),
(10, 13, 'Course 10', 'Demo content for record 10', 0, '2026-07-15 05:30:00'),
(11, 14, 'Course 1', 'Demo content for record 1', 0, '2026-07-15 05:30:00'),
(12, 15, 'Course 2', 'Demo content for record 2', 0, '2026-07-15 05:30:00'),
(13, 16, 'Course 3', 'Demo content for record 3', 0, '2026-07-15 05:30:00'),
(14, 17, 'Course 4', 'Demo content for record 4', 0, '2026-07-15 05:30:00'),
(15, 18, 'Course 5', 'Demo content for record 5', 0, '2026-07-15 05:30:00'),
(16, 19, 'Course 6', 'Demo content for record 6', 0, '2026-07-15 05:30:00'),
(17, 20, 'Course 7', 'Demo content for record 7', 0, '2026-07-15 05:30:00'),
(18, 21, 'Course 8', 'Demo content for record 8', 0, '2026-07-15 05:30:00'),
(19, 22, 'Course 9', 'Demo content for record 9', 0, '2026-07-15 05:30:00'),
(20, 13, 'Course 10', 'Demo content for record 10', 0, '2026-07-15 05:30:00');

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
(1, 14, 'Course 1', 'Description for item 1', '2026-09-02 00:00:00', 100.00, '2026-07-15 05:30:00'),
(2, 15, 'Course 2', 'Description for item 2', '2026-09-03 00:00:00', 100.00, '2026-07-15 05:30:00'),
(3, 16, 'Course 3', 'Description for item 3', '2026-09-04 00:00:00', 100.00, '2026-07-15 05:30:00'),
(4, 17, 'Course 4', 'Description for item 4', '2026-09-05 00:00:00', 100.00, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 'Description for item 5', '2026-09-06 00:00:00', 100.00, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 'Description for item 6', '2026-09-07 00:00:00', 100.00, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 'Description for item 7', '2026-09-08 00:00:00', 100.00, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 'Description for item 8', '2026-09-09 00:00:00', 100.00, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 'Description for item 9', '2026-09-10 00:00:00', 100.00, '2026-07-15 05:30:00'),
(10, 13, 'Course 10', 'Description for item 10', '2026-09-11 00:00:00', 100.00, '2026-07-15 05:30:00');

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
(1, 14, 'Course 1', '/uploads/file1.pdf', 0, '2026-07-15 05:30:00'),
(2, 15, 'Course 2', '/uploads/file2.pdf', 0, '2026-07-15 05:30:00'),
(3, 16, 'Course 3', '/uploads/file3.pdf', 0, '2026-07-15 05:30:00'),
(4, 17, 'Course 4', '/uploads/file4.pdf', 0, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', '/uploads/file5.pdf', 0, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', '/uploads/file6.pdf', 0, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', '/uploads/file7.pdf', 0, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', '/uploads/file8.pdf', 0, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', '/uploads/file9.pdf', 0, '2026-07-15 05:30:00'),
(10, 13, 'Course 10', '/uploads/file10.pdf', 0, '2026-07-15 05:30:00');

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
(1, 14, 2, '0000-00-00 00:00:00'),
(2, 15, 3, '0000-00-00 00:00:00'),
(3, 16, 4, '0000-00-00 00:00:00'),
(4, 17, 5, '0000-00-00 00:00:00'),
(5, 18, 6, '0000-00-00 00:00:00'),
(6, 19, 7, '0000-00-00 00:00:00'),
(7, 20, 8, '0000-00-00 00:00:00'),
(8, 21, 9, '0000-00-00 00:00:00'),
(9, 22, 10, '0000-00-00 00:00:00'),
(10, 13, 11, '0000-00-00 00:00:00'),
(11, 14, 1, '0000-00-00 00:00:00'),
(12, 15, 2, '0000-00-00 00:00:00'),
(13, 16, 3, '0000-00-00 00:00:00'),
(14, 17, 4, '0000-00-00 00:00:00'),
(15, 18, 5, '0000-00-00 00:00:00'),
(16, 19, 6, '0000-00-00 00:00:00'),
(17, 20, 7, '0000-00-00 00:00:00'),
(18, 21, 8, '0000-00-00 00:00:00'),
(19, 22, 9, '0000-00-00 00:00:00'),
(20, 13, 10, '0000-00-00 00:00:00'),
(21, 27, 37, '2026-07-27 19:36:26'),
(22, 28, 37, '2026-07-27 19:36:26'),
(23, 29, 37, '2026-07-27 19:36:26'),
(24, 30, 37, '2026-07-27 19:36:26'),
(25, 31, 37, '2026-07-27 19:36:26'),
(26, 32, 37, '2026-07-27 19:36:26'),
(27, 33, 37, '2026-07-27 19:36:26');

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
(1, 2, 14, 500.00, '', '2026-09-02', '0000-00-00 00:00:00'),
(2, 3, 15, 500.00, '', '2026-09-03', '0000-00-00 00:00:00'),
(3, 4, 16, 500.00, '', '2026-09-04', '0000-00-00 00:00:00'),
(4, 5, 17, 500.00, '', '2026-09-05', '0000-00-00 00:00:00'),
(5, 6, 18, 500.00, '', '2026-09-06', '0000-00-00 00:00:00'),
(6, 7, 19, 500.00, '', '2026-09-07', '0000-00-00 00:00:00'),
(7, 8, 20, 500.00, '', '2026-09-08', '0000-00-00 00:00:00'),
(8, 9, 21, 500.00, '', '2026-09-09', '0000-00-00 00:00:00'),
(9, 10, 22, 500.00, '', '2026-09-10', '0000-00-00 00:00:00'),
(10, 11, 13, 500.00, '', '2026-09-11', '0000-00-00 00:00:00');

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
(1, 14, 'Course 1', '2026-09-02', '2026-07-15 05:30:00'),
(2, 15, 'Course 2', '2026-09-03', '2026-07-15 05:30:00'),
(3, 16, 'Course 3', '2026-09-04', '2026-07-15 05:30:00'),
(4, 17, 'Course 4', '2026-09-05', '2026-07-15 05:30:00'),
(5, 18, 'Course 5', '2026-09-06', '2026-07-15 05:30:00'),
(6, 19, 'Course 6', '2026-09-07', '2026-07-15 05:30:00'),
(7, 20, 'Course 7', '2026-09-08', '2026-07-15 05:30:00'),
(8, 21, 'Course 8', '2026-09-09', '2026-07-15 05:30:00'),
(9, 22, 'Course 9', '2026-09-10', '2026-07-15 05:30:00'),
(10, 13, 'Course 10', '2026-09-11', '2026-07-15 05:30:00');

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
(1, 14, 2, 'value_1', 100.00, NULL),
(2, 15, 3, 'value_2', 100.00, NULL),
(3, 16, 4, 'value_3', 100.00, NULL),
(4, 17, 5, 'value_4', 100.00, NULL),
(5, 18, 6, 'value_5', 100.00, NULL),
(6, 19, 7, 'value_6', 100.00, NULL),
(7, 20, 8, 'value_7', 100.00, NULL),
(8, 21, 9, 'value_8', 100.00, NULL),
(9, 22, 10, 'value_9', 100.00, NULL),
(10, 13, 11, 'value_10', 100.00, NULL),
(11, 14, 1, 'value_11', 100.00, NULL),
(12, 15, 2, 'value_12', 100.00, NULL),
(13, 16, 3, 'value_13', 100.00, NULL),
(14, 17, 4, 'value_14', 100.00, NULL),
(15, 18, 5, 'value_15', 100.00, NULL),
(16, 19, 6, 'value_16', 100.00, NULL),
(17, 20, 7, 'value_17', 100.00, NULL),
(18, 21, 8, 'value_18', 100.00, NULL),
(19, 22, 9, 'value_19', 100.00, NULL),
(20, 13, 10, 'value_20', 100.00, NULL),
(21, 14, 2, 'value_1', 100.00, NULL),
(22, 15, 3, 'value_2', 100.00, NULL),
(23, 16, 4, 'value_3', 100.00, NULL),
(24, 17, 5, 'value_4', 100.00, NULL),
(25, 18, 6, 'value_5', 100.00, NULL),
(26, 19, 7, 'value_6', 100.00, NULL),
(27, 20, 8, 'value_7', 100.00, NULL),
(28, 21, 9, 'value_8', 100.00, NULL),
(29, 22, 10, 'value_9', 100.00, NULL),
(30, 13, 11, 'value_10', 100.00, NULL),
(31, 14, 1, 'value_11', 100.00, NULL),
(32, 15, 2, 'value_12', 100.00, NULL),
(33, 16, 3, 'value_13', 100.00, NULL),
(34, 17, 4, 'value_14', 100.00, NULL),
(35, 18, 5, 'value_15', 100.00, NULL),
(36, 19, 6, 'value_16', 100.00, NULL),
(37, 20, 7, 'value_17', 100.00, NULL),
(38, 21, 8, 'value_18', 100.00, NULL),
(39, 22, 9, 'value_19', 100.00, NULL),
(40, 13, 10, 'value_20', 100.00, NULL);

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
(1, 14, 2, 1, '0000-00-00 00:00:00'),
(2, 15, 3, 1, '0000-00-00 00:00:00'),
(3, 16, 4, 1, '0000-00-00 00:00:00'),
(4, 17, 5, 1, '0000-00-00 00:00:00'),
(5, 18, 6, 1, '0000-00-00 00:00:00'),
(6, 19, 7, 1, '0000-00-00 00:00:00'),
(7, 20, 8, 1, '0000-00-00 00:00:00'),
(8, 21, 9, 1, '0000-00-00 00:00:00'),
(9, 22, 10, 1, '0000-00-00 00:00:00'),
(10, 13, 11, 1, '0000-00-00 00:00:00');

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
(10, 0, 0, 'Subject 10', 'Demo content for record 10', 0, '2026-07-15 05:30:00');

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
(12, 25, 1, 'notification', 'New Announcement', 'A new announcement has been posted.', '/uni-mis-project/modules/lms/student/dashboard.php', 0, '2026-07-27 16:30:19');

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
(1, 2, 14, 'Subject 1', 'Demo content for record 1', 'value_1', '', '2026-07-15 05:30:00'),
(2, 3, 15, 'Subject 2', 'Demo content for record 2', 'value_2', '', '2026-07-15 05:30:00'),
(3, 4, 16, 'Subject 3', 'Demo content for record 3', 'value_3', '', '2026-07-15 05:30:00'),
(4, 5, 17, 'Subject 4', 'Demo content for record 4', 'value_4', '', '2026-07-15 05:30:00'),
(5, 6, 18, 'Subject 5', 'Demo content for record 5', 'value_5', '', '2026-07-15 05:30:00'),
(6, 7, 19, 'Subject 6', 'Demo content for record 6', 'value_6', '', '2026-07-15 05:30:00'),
(7, 8, 20, 'Subject 7', 'Demo content for record 7', 'value_7', '', '2026-07-15 05:30:00'),
(8, 9, 21, 'Subject 8', 'Demo content for record 8', 'value_8', '', '2026-07-15 05:30:00'),
(9, 10, 22, 'Subject 9', 'Demo content for record 9', 'value_9', '', '2026-07-15 05:30:00'),
(10, 11, 13, 'Subject 10', 'Demo content for record 10', 'value_10', '', '2026-07-15 05:30:00');

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
(1, 14, 'Course 1', 100.00, 60, '2026-07-15 05:30:00'),
(2, 15, 'Course 2', 100.00, 60, '2026-07-15 05:30:00'),
(3, 16, 'Course 3', 100.00, 60, '2026-07-15 05:30:00'),
(4, 17, 'Course 4', 100.00, 60, '2026-07-15 05:30:00'),
(5, 18, 'Course 5', 100.00, 60, '2026-07-15 05:30:00'),
(6, 19, 'Course 6', 100.00, 60, '2026-07-15 05:30:00'),
(7, 20, 'Course 7', 100.00, 60, '2026-07-15 05:30:00'),
(8, 21, 'Course 8', 100.00, 60, '2026-07-15 05:30:00'),
(9, 22, 'Course 9', 100.00, 60, '2026-07-15 05:30:00'),
(10, 13, 'Course 10', 100.00, 60, '2026-07-15 05:30:00');

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
(1, 1, 2, 100.00, '2026-07-15 05:30:00'),
(2, 2, 3, 100.00, '2026-07-15 05:30:00'),
(3, 3, 4, 100.00, '2026-07-15 05:30:00'),
(4, 4, 5, 100.00, '2026-07-15 05:30:00'),
(5, 5, 6, 100.00, '2026-07-15 05:30:00'),
(6, 6, 7, 100.00, '2026-07-15 05:30:00'),
(7, 7, 8, 100.00, '2026-07-15 05:30:00'),
(8, 8, 9, 100.00, '2026-07-15 05:30:00'),
(9, 9, 10, 100.00, '2026-07-15 05:30:00'),
(10, 10, 11, 100.00, '2026-07-15 05:30:00');

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
(1, 1, 2, '/uploads/file1.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 1'),
(2, 2, 3, '/uploads/file2.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 2'),
(3, 3, 4, '/uploads/file3.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 3'),
(4, 4, 5, '/uploads/file4.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 4'),
(5, 5, 6, '/uploads/file5.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 5'),
(6, 6, 7, '/uploads/file6.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 6'),
(7, 7, 8, '/uploads/file7.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 7'),
(8, 8, 9, '/uploads/file8.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 8'),
(9, 9, 10, '/uploads/file9.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 9'),
(10, 10, 11, '/uploads/file10.pdf', '2026-07-15 05:30:00', 0.00, 'Good work on assignment 10');

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
(11, 13, 25, 50000.00, 'Cash', 'TX6732', '2026-07-28 01:09:14', 9, 'Success');

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

--
-- Dumping data for table `sbe_exams`
--

INSERT INTO `sbe_exams` (`exam_id`, `exam_code`, `course_id`, `teacher_id`, `title`, `exam_type`, `instructions`, `duration_minutes`, `total_questions`, `total_marks`, `passing_marks`, `selection_mode`, `negative_marking`, `shuffle_questions`, `shuffle_options`, `allow_review`, `status`, `created_at`, `updated_at`) VALUES
(1, 'SBE001', 13, 1, 'SBE Midterm', '', 'Follow all instructions', 60, 30, 100.00, 40.00, '', 0.00, 0, 0, 1, '', '2026-07-15 05:00:00', '2026-07-15 05:00:00'),
(2, 'SBE002', 15, 1, 'SBE Final', 'Final', 'Follow all instructions', 90, 40, 150.00, 60.00, '', 0.00, 0, 0, 1, '', '2026-07-15 05:00:00', '2026-07-15 05:00:00');

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
(1, 1, 0, 1, '2026-07-15 05:30:00'),
(2, 2, 0, 2, '2026-07-15 05:30:00'),
(3, 3, 0, 3, '2026-07-15 05:30:00'),
(4, 4, 0, 4, '2026-07-15 05:30:00'),
(5, 5, 0, 5, '2026-07-15 05:30:00');

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
(7, 0, 1, 0, 0.00, 100.00, 41.00, 'Pass', 0, 'Remark 1', '', '2026-07-15 10:30:00', '2026-07-15 05:30:00');

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
(1, 1, 'A', 9, '2026-09-15', '09:00:00', '11:00:00', 15, 'Block A Room 101', '', 'Scheduled', '2026-07-26 07:00:00', '2026-07-26 07:00:00'),
(2, 2, 'A', 10, '2026-12-20', '09:00:00', '12:00:00', 15, 'Block A Room 101', '', 'Scheduled', '2026-07-26 07:00:00', '2026-07-26 07:00:00');

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
(5, 16, 2, 'Networking', 'What does TCP stand for?', 'Transfer Control Protocol', 'Transmission Control Protocol', 'Telecommunications Control Protocol', 'Terminal Control Protocol', 'B', 'TCP stands for Transmission Control Protocol.', 5.00, 'Easy', 'Active', '2026-07-15 05:00:00', '2026-07-15 05:00:00');

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

--
-- Dumping data for table `sbe_student_exams`
--

INSERT INTO `sbe_student_exams` (`student_exam_id`, `schedule_id`, `exam_id`, `student_id`, `attempt_no`, `status`, `started_at`, `expires_at`, `submitted_at`, `time_taken_seconds`, `obtained_marks`, `percentage`, `pass_fail_status`) VALUES
(1, 0, 1, 0, 2, '', '2026-07-15 10:30:00', '2026-07-15 10:30:00', '2026-07-15 10:30:00', 610, 0.00, 41.00, 'Pass'),
(2, 0, 2, 0, 3, '', '2026-07-15 10:30:00', '2026-07-15 10:30:00', '2026-07-15 10:30:00', 620, 0.00, 42.00, 'Pass'),
(3, 0, 3, 0, 1, '', '2026-07-15 10:30:00', '2026-07-15 10:30:00', '2026-07-15 10:30:00', 630, 0.00, 43.00, 'Pass');

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
(1, 'value_1', 2, 6, 6, 14, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(2, 'value_2', 3, 7, 7, 15, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(3, 'value_3', 4, 8, 5, 16, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(4, 'value_4', 5, 9, 6, 17, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(5, 'value_5', 6, 10, 7, 18, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(6, 'value_6', 1, 11, 5, 19, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(7, 'value_7', 2, 12, 6, 20, '2', 45, 31, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(8, 'value_8', 3, 13, 7, 21, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(9, 'value_9', 4, 14, 5, 22, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(10, 'value_10', 5, 15, 6, 13, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(11, 'value_11', 6, 16, 7, 14, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(12, 'value_12', 1, 17, 5, 15, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(13, 'value_13', 2, 18, 6, 16, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(14, 'value_14', 3, 19, 7, 17, '1', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00'),
(15, 'value_15', 4, 20, 5, 18, '2', 45, 30, 30, '2025-2026', 'Active', '2026-07-15 05:30:00');

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

INSERT INTO `students` (`student_id`, `application_id`, `roll_no`, `full_name`, `father_name`, `cnic_or_bform`, `dob`, `gender`, `contact_no`, `email`, `address`, `program_id`, `admission_session_id`, `current_session_id`, `current_semester_id`, `batch_year`, `admission_date`, `status`, `created_at`, `updated_at`, `user_id`, `semester`) VALUES
(25, 100, '2024-2-025', 'Ahmed Ali', 'Father of Ahmed Ali', '42101-1234567-25', '2003-05-15', 'Male', '0300-1000025', 'stu100@uni.edu', 'Lahore', 2, 6, 6, 9, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(26, 101, '2024-3-026', 'Sara Butt', 'Father of Sara Butt', '42101-1234567-26', '2003-05-15', 'Male', '0300-1000026', 'stu101@uni.edu', 'Lahore', 3, 5, 5, 10, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(27, 102, '2024-4-027', 'Usman Khan', 'Father of Usman Khan', '42101-1234567-27', '2003-05-15', 'Male', '0300-1000027', 'stu102@uni.edu', 'Lahore', 4, 6, 6, 11, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(28, 103, '2024-5-028', 'Hira Ahmed', 'Father of Hira Ahmed', '42101-1234567-28', '2003-05-15', 'Male', '0300-1000028', 'stu103@uni.edu', 'Lahore', 5, 5, 5, 12, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(29, 104, '2024-6-029', 'Bilal Hussain', 'Father of Bilal Hussain', '42101-1234567-29', '2003-05-15', 'Male', '0300-1000029', 'stu104@uni.edu', 'Lahore', 6, 6, 6, 13, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(30, 105, '2024-2-030', 'Zainab Noor', 'Father of Zainab Noor', '42101-1234567-30', '2003-05-15', 'Male', '0300-1000030', 'stu105@uni.edu', 'Lahore', 2, 5, 5, 14, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(31, 106, '2024-3-031', 'Muhammad Umer', 'Father of Muhammad Umer', '42101-1234567-31', '2003-05-15', 'Male', '0300-1000031', 'stu106@uni.edu', 'Lahore', 3, 6, 6, 15, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(32, 107, '2024-4-032', 'Ayesha Siddiqui', 'Father of Ayesha Siddiqui', '42101-1234567-32', '2003-05-15', 'Male', '0300-1000032', 'stu107@uni.edu', 'Lahore', 4, 5, 5, 16, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(33, 108, '2024-5-033', 'Farhan Iqbal', 'Father of Farhan Iqbal', '42101-1234567-33', '2003-05-15', 'Male', '0300-1000033', 'stu108@uni.edu', 'Lahore', 5, 6, 6, 9, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(34, 109, '2024-6-034', 'Maryam Khalid', 'Father of Maryam Khalid', '42101-1234567-34', '2003-05-15', 'Male', '0300-1000034', 'stu109@uni.edu', 'Lahore', 6, 5, 5, 10, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(35, 110, '2024-2-035', 'Waleed Aslam', 'Father of Waleed Aslam', '42101-1234567-35', '2003-05-15', 'Male', '0300-1000035', 'stu110@uni.edu', 'Lahore', 2, 6, 6, 11, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(36, 111, '2024-3-036', 'Nida Butt', 'Father of Nida Butt', '42101-1234567-36', '2003-05-15', 'Male', '0300-1000036', 'stu111@uni.edu', 'Lahore', 3, 5, 5, 12, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1),
(37, 112, '2024-4-037', 'Student 37', 'Father of Student 37', '42101-1234567-37', '2003-05-15', 'Male', '0300-1000037', 'stu112@uni.edu', 'Lahore', 4, 6, 6, 13, 2024, '2003-05-15', 'Active', '2026-07-27 16:42:35', '2026-07-27 16:42:35', NULL, 1);

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
(1158, 'STD035', 13, NULL, 'Active', '2026-07-15 05:30:00');

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
(14, 31, 2, 6, 20, 64000.00, 40.00, '2026-08-26', 'Partially Paid', 9, '2026-07-27 20:06:11');

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
(28, 14, 5, 2000.00, 0.00);

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
(2, 20, 'Teacher Demo', 'Lecturer', 'teacher.demo@university.edu', NULL, 1, 'Active', '2026-07-26 18:14:23'),
(8, NULL, 'njkjnkj', NULL, 'ksnc@gmail.com', '278328237', 2, 'Active', '2026-07-27 21:25:22');

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
(22, 'Student Demo', 'student.demo', '9002', 'student.demo@university.edu', NULL, '$2y$10$JHSLxirVE9m2CKndkrZSmu.FUcYaNjDt.pbep7x.GLpzg7ha/cd2q', 4, NULL, NULL, 'Active', NULL, '2026-07-26 18:13:52', '2026-07-26 18:44:00'),
(37, 'Ali', 'ali555', '9546', 'ali@gmail.com', '6157351656152', '$2y$10$QeUNm0s88olTyKleuaWoKOYHk9pGaKctvADVE9EtjBsU5Afjx3PTK', 4, 1, NULL, 'Active', NULL, '2026-07-27 19:36:26', '2026-07-27 19:36:26');

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
-- Indexes for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  ADD PRIMARY KEY (`scholarship_id`),
  ADD KEY `idx_student` (`student_id`),
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
  MODIFY `log_id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `admission_applications`
--
ALTER TABLE `admission_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=116;

--
-- AUTO_INCREMENT for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  MODIFY `scholarship_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `admission_scholarship_applications`
--
ALTER TABLE `admission_scholarship_applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `admission_scholarship_programs`
--
ALTER TABLE `admission_scholarship_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `admission_students`
--
ALTER TABLE `admission_students`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=169;

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=34;

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
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `faculty`
--
ALTER TABLE `faculty`
  MODIFY `faculty_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fee_heads`
--
ALTER TABLE `fee_heads`
  MODIFY `fee_head_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `fee_payments`
--
ALTER TABLE `fee_payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `fee_records`
--
ALTER TABLE `fee_records`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `fee_structures`
--
ALTER TABLE `fee_structures`
  MODIFY `fee_structure_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `fee_structure_details`
--
ALTER TABLE `fee_structure_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

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
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_applications`
--
ALTER TABLE `lms_applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lms_assignments`
--
ALTER TABLE `lms_assignments`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_course_materials`
--
ALTER TABLE `lms_course_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_datesheets`
--
ALTER TABLE `lms_datesheets`
  MODIFY `datesheet_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `lms_enrollments`
--
ALTER TABLE `lms_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `lms_exams`
--
ALTER TABLE `lms_exams`
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_fees`
--
ALTER TABLE `lms_fees`
  MODIFY `fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lms_lectures`
--
ALTER TABLE `lms_lectures`
  MODIFY `lecture_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_marks`
--
ALTER TABLE `lms_marks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `lms_mark_finalizations`
--
ALTER TABLE `lms_mark_finalizations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `lms_messages`
--
ALTER TABLE `lms_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_notifications`
--
ALTER TABLE `lms_notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lms_queries`
--
ALTER TABLE `lms_queries`
  MODIFY `query_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `lms_quizzes`
--
ALTER TABLE `lms_quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `lms_quiz_results`
--
ALTER TABLE `lms_quiz_results`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sbe_exam_questions`
--
ALTER TABLE `sbe_exam_questions`
  MODIFY `exam_question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `sbe_exam_results`
--
ALTER TABLE `sbe_exam_results`
  MODIFY `exam_result_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `sbe_exam_schedule`
--
ALTER TABLE `sbe_exam_schedule`
  MODIFY `schedule_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sbe_question_bank`
--
ALTER TABLE `sbe_question_bank`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sbe_student_answers`
--
ALTER TABLE `sbe_student_answers`
  MODIFY `student_answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sbe_student_exams`
--
ALTER TABLE `sbe_student_exams`
  MODIFY `student_exam_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `section_courses`
--
ALTER TABLE `section_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `semesters`
--
ALTER TABLE `semesters`
  MODIFY `semester_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

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
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `student_courses`
--
ALTER TABLE `student_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1160;

--
-- AUTO_INCREMENT for table `student_enrollments`
--
ALTER TABLE `student_enrollments`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `student_fee`
--
ALTER TABLE `student_fee`
  MODIFY `student_fee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `student_fee_assignment`
--
ALTER TABLE `student_fee_assignment`
  MODIFY `assignment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `student_fee_details`
--
ALTER TABLE `student_fee_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `student_fee_discounts`
--
ALTER TABLE `student_fee_discounts`
  MODIFY `discount_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

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
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `teacher_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `teacher_courses`
--
ALTER TABLE `teacher_courses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=86;

--
-- AUTO_INCREMENT for table `timetable`
--
ALTER TABLE `timetable`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

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
-- Constraints for table `admission_scholarships`
--
ALTER TABLE `admission_scholarships`
  ADD CONSTRAINT `fk_scholarship_approver` FOREIGN KEY (`approved_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scholarship_semester` FOREIGN KEY (`semester_id`) REFERENCES `semesters` (`semester_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scholarship_session` FOREIGN KEY (`session_id`) REFERENCES `sessions` (`session_id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_scholarship_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `fk_stu_program` FOREIGN KEY (`program_id`) REFERENCES `departments` (`department_id`),
  ADD CONSTRAINT `students_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

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
