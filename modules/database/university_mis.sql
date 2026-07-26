-- =============================================
-- ADMISSION SYSTEM - COMPLETE DATABASE SCHEMA
-- =============================================

CREATE DATABASE IF NOT EXISTS admission_system;
USE admission_system;

-- =============================================
-- 1. USERS TABLE
-- =============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin','staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- 2. PROGRAMS TABLE
-- =============================================
CREATE TABLE programs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_name VARCHAR(100) NOT NULL,
    program_code VARCHAR(20) UNIQUE NOT NULL,
    duration VARCHAR(50),
    fee_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active'
);

-- =============================================
-- 3. APPLICATIONS TABLE
-- =============================================
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_no VARCHAR(20) UNIQUE NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    program_id INT,
    previous_education VARCHAR(100),
    marks_obtained DECIMAL(5,2),
    total_marks DECIMAL(5,2),
    percentage DECIMAL(5,2),
    status ENUM('pending','reviewed','approved','rejected','confirmed') DEFAULT 'pending',
    remarks TEXT,
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    review_date TIMESTAMP NULL,
    FOREIGN KEY (program_id) REFERENCES programs(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id)
);

-- =============================================
-- 4. STUDENTS TABLE
-- =============================================
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    application_id INT,
    student_name VARCHAR(100) NOT NULL,
    father_name VARCHAR(100) NOT NULL,
    mother_name VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('Male','Female','Other') NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    program_id INT,
    enrollment_date DATE NOT NULL,
    status ENUM('active','inactive','graduated') DEFAULT 'active',
    FOREIGN KEY (application_id) REFERENCES applications(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- =============================================
-- 5. FEE STRUCTURES TABLE
-- =============================================
CREATE TABLE fee_structures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    program_id INT,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    academic_year VARCHAR(20),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- =============================================
-- 6. FEE PAYMENTS TABLE
-- =============================================
CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    fee_type VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    payment_method ENUM('cash','bank','online') NOT NULL,
    status ENUM('paid','pending') DEFAULT 'pending',
    FOREIGN KEY (student_id) REFERENCES students(id)
);

-- =============================================
-- 7. SCHOLARSHIPS TABLE
-- =============================================
CREATE TABLE scholarships (
    id INT PRIMARY KEY AUTO_INCREMENT,
    scholarship_name VARCHAR(100) NOT NULL,
    description TEXT,
    amount DECIMAL(10,2),
    min_percentage DECIMAL(5,2),
    total_slots INT DEFAULT 10,
    deadline DATE,
    status ENUM('active','inactive') DEFAULT 'active'
);

-- =============================================
-- 8. SCHOLARSHIP APPLICATIONS TABLE
-- =============================================
CREATE TABLE scholarship_applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT,
    scholarship_id INT,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    FOREIGN KEY (student_id) REFERENCES students(id),
    FOREIGN KEY (scholarship_id) REFERENCES scholarships(id)
);

-- =============================================
-- INSERT DEFAULT DATA
-- =============================================

-- Admin User (password: admin123)
INSERT INTO users (username, password, full_name, email, role) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin@system.com', 'admin');

-- Sample Programs
INSERT INTO programs (program_name, program_code, duration, fee_amount) VALUES
('Computer Science', 'BCS', '4 Years', 85000),
('Business Administration', 'BBA', '3 Years', 75000),
('Engineering', 'BE', '4 Years', 120000),
('Arts', 'BA', '3 Years', 45000);

-- Sample Fee Structures
INSERT INTO fee_structures (program_id, fee_type, amount, academic_year) VALUES
(1, 'Tuition Fee', 75000, '2024'),
(1, 'Lab Fee', 5000, '2024'),
(2, 'Tuition Fee', 65000, '2024'),
(3, 'Tuition Fee', 100000, '2024');

-- Sample Scholarships
INSERT INTO scholarships (scholarship_name, description, amount, min_percentage, total_slots, deadline) VALUES
('Merit Scholarship', 'For outstanding academic performance', 50000, 85, 10, '2024-12-31'),
('Need Based Scholarship', 'For financially challenged students', 30000, 60, 20, '2024-11-30');