queries i used 
-- Step 1: Create the database
CREATE DATABASE lms_db;
USE lms_db;

-- Step 2: Create tables

-- Users table
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('student','teacher','admin') NOT NULL,
  sso_id VARCHAR(50),
  profile_picture VARCHAR(255)
);

-- Courses table
CREATE TABLE courses (
  course_id INT AUTO_INCREMENT PRIMARY KEY,
  course_name VARCHAR(100) NOT NULL,
  teacher_id INT,
  semester VARCHAR(20),
  sso_course_id VARCHAR(50),
  FOREIGN KEY (teacher_id) REFERENCES users(user_id)
);

-- Attendance table
CREATE TABLE attendance (
  attendance_id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT,
  student_id INT,
  date DATE,
  status ENUM('Present','Absent'),
  FOREIGN KEY (course_id) REFERENCES courses(course_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id)
);

-- Assignments table
CREATE TABLE assignments (
  assignment_id INT AUTO_INCREMENT PRIMARY KEY,
  course_id INT,
  title VARCHAR(100),
  description TEXT,
  helping_file VARCHAR(255),
  due_date DATE,
  FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Submissions table
CREATE TABLE submissions (
  submission_id INT AUTO_INCREMENT PRIMARY KEY,
  assignment_id INT,
  student_id INT,
  file_path VARCHAR(255),
  grade INT,
  FOREIGN KEY (assignment_id) REFERENCES assignments(assignment_id),
  FOREIGN KEY (student_id) REFERENCES users(user_id)
);

-- Internal Marks table
CREATE TABLE internal_marks (
  marks_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  course_id INT,
  assignments INT DEFAULT 0,
  quizzes INT DEFAULT 0,
  major_assignment INT DEFAULT 0,
  presentation INT DEFAULT 0,
  midterm INT DEFAULT 0,
  total INT GENERATED ALWAYS AS (
    assignments + quizzes + major_assignment + presentation + midterm
  ) STORED,
  FOREIGN KEY (student_id) REFERENCES users(user_id),
  FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Queries table
CREATE TABLE queries (
  query_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  teacher_id INT,
  message TEXT,
  response TEXT,
  FOREIGN KEY (student_id) REFERENCES users(user_id),
  FOREIGN KEY (teacher_id) REFERENCES users(user_id)
);

-- Applications table
CREATE TABLE applications (
  application_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  course_id INT,
  status ENUM('Pending','Forwarded','Resolved') DEFAULT 'Pending',
  forwarded_to VARCHAR(50),
  FOREIGN KEY (student_id) REFERENCES users(user_id),
  FOREIGN KEY (course_id) REFERENCES courses(course_id)
);

-- Fees table
CREATE TABLE fees (
  fee_id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  amount DECIMAL(10,2),
  status ENUM('Paid','Unpaid') DEFAULT 'Unpaid',
  semester VARCHAR(20),
  FOREIGN KEY (student_id) REFERENCES users(user_id)
);


and data
-- Insert a teacher
INSERT INTO users (name, email, password, role, sso_id, profile_picture)
VALUES ('Dr. Ahmed Khan', 'ahmed.khan@uni.edu', MD5('teacher123'), 'teacher', 'SSO_T001', 'teacher1.jpg');

-- Insert a student
INSERT INTO users (name, email, password, role, sso_id, profile_picture)
VALUES ('Zahra Ali', 'zahra.ali@uni.edu', MD5('student123'), 'student', 'SSO_S001', 'student1.jpg');

-- Insert a course assigned to teacher
INSERT INTO courses (course_name, teacher_id, semester, sso_course_id)
VALUES ('Database Systems', 1, 'Spring 2026', 'SSO_C001');

-- Insert attendance record for student
INSERT INTO attendance (course_id, student_id, date, status)
VALUES (1, 2, '2026-07-07', 'Present');

-- Insert an assignment
INSERT INTO assignments (course_id, title, description, helping_file, due_date)
VALUES (1, 'Assignment 1', 'Design ER diagram for LMS', 'er_help.pdf', '2026-07-15');

-- Insert a submission by student
INSERT INTO submissions (assignment_id, student_id, file_path, grade)
VALUES (1, 2, 'submissions/zahra_assignment1.pdf', 45);

-- Insert internal marks for student
INSERT INTO internal_marks (student_id, course_id, assignments, quizzes, major_assignment, presentation, midterm)
VALUES (2, 1, 10, 8, 12, 10, 8);
-- Insert a student query
INSERT INTO queries (student_id, teacher_id, message, response)
VALUES (2, 1, 'Can you explain normalization again?', 'Sure, we will cover it in next lecture.');

-- Insert an application
INSERT INTO applications (student_id, course_id, status, forwarded_to)
VALUES (2, 1, 'Forwarded', 'SSO_Admin');

-- Insert fee record
INSERT INTO fees (student_id, amount, status, semester)
VALUES (2, 50000.00, 'Paid', 'Spring 2026');
