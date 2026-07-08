-- University LMS demo seed
-- Load after `schema.sql`.

USE `lms_mis`;

INSERT INTO users (id, name, email, password_hash, role, department, program, profile_photo, created_at) VALUES
    (1, 'Dr. Sara Khan', 'teacher@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Computer Science', NULL, NULL, NOW()),
    (2, 'Ali Raza', 'student@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering', NULL, NOW()),
    (3, 'Amina Noor', 'amina.noor@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering', NULL, NOW()),
    (4, 'Bilal Ahmed', 'bilal.ahmed@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology', NULL, NOW()),
    (5, 'Hira Khan', 'hira.khan@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science', NULL, NOW()),
    (6, 'Hassan Ali', 'hassan.ali@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering', NULL, NOW()),
    (7, 'Maryam Iqbal', 'maryam.iqbal@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science', NULL, NOW()),
    (8, 'Usman Tariq', 'usman.tariq@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science', NULL, NOW()),
    (9, 'Sana Raza', 'sana.raza@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology', NULL, NOW()),
    (10, 'Zain Malik', 'zain.malik@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering', NULL, NOW()),
    (11, 'Iqra Javed', 'iqra.javed@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science', NULL, NOW());

INSERT INTO courses (id, code, title, description, credit_hours, teacher_id, semester) VALUES
    (1, 'CS101', 'Introduction to Programming', 'Fundamentals of programming using clear logic and problem solving.', 3, 1, 'Spring 2026'),
    (2, 'SE204', 'Database Systems', 'Design and manage relational databases and SQL-based applications.', 3, 1, 'Spring 2026');

INSERT INTO enrollments (student_id, course_id) VALUES
    (2, 1), (2, 2),
    (3, 1), (3, 2),
    (4, 1), (4, 2),
    (5, 1), (5, 2),
    (6, 1), (6, 2),
    (7, 1), (7, 2),
    (8, 1), (8, 2),
    (9, 1), (9, 2),
    (10, 1), (10, 2),
    (11, 1), (11, 2);

INSERT INTO assignments (id, course_id, title, description, file_path, due_date) VALUES
    (1, 1, 'Loops Practice', 'Submit five solved loop problems.', NULL, '2026-07-20'),
    (2, 2, 'ERD Design', 'Create an ERD for a library system.', NULL, '2026-07-25');

INSERT INTO submissions (id, assignment_id, student_id, content, submission_file, submitted_at, grade, feedback) VALUES
    (1, 1, 2, 'Loops practice submitted for review.', NULL, NOW(), NULL, NULL),
    (2, 1, 3, 'Practice file uploaded.', NULL, NOW(), NULL, NULL),
    (3, 2, 4, 'ERD draft uploaded.', NULL, NOW(), NULL, NULL);

INSERT INTO attendance (id, course_id, student_id, class_date, status) VALUES
    (1, 1, 2, '2026-07-01', 'present'),
    (2, 2, 2, '2026-07-02', 'present'),
    (3, 1, 3, '2026-07-01', 'late'),
    (4, 2, 3, '2026-07-02', 'present'),
    (5, 1, 4, '2026-07-01', 'present'),
    (6, 2, 4, '2026-07-02', 'absent'),
    (7, 1, 5, '2026-07-01', 'present'),
    (8, 2, 5, '2026-07-02', 'late'),
    (9, 1, 6, '2026-07-01', 'absent'),
    (10, 2, 6, '2026-07-02', 'present'),
    (11, 1, 7, '2026-07-01', 'present'),
    (12, 2, 7, '2026-07-02', 'present'),
    (13, 1, 8, '2026-07-01', 'late'),
    (14, 2, 8, '2026-07-02', 'present'),
    (15, 1, 9, '2026-07-01', 'present'),
    (16, 2, 9, '2026-07-02', 'absent'),
    (17, 1, 10, '2026-07-01', 'present'),
    (18, 2, 10, '2026-07-02', 'present'),
    (19, 1, 11, '2026-07-01', 'late'),
    (20, 2, 11, '2026-07-02', 'present');

INSERT INTO marks (course_id, student_id, component, marks_obtained, total_marks) VALUES
    (1, 2, 'assignment_1', 3.00, 10),
    (1, 2, 'mid_term', 20.00, 25),
    (2, 2, 'assignment_1', 2.00, 10),
    (2, 2, 'mid_term', 21.00, 25),
    (1, 3, 'assignment_1', 2.50, 10),
    (1, 3, 'mid_term', 18.50, 25),
    (2, 3, 'assignment_1', 3.00, 10),
    (2, 3, 'mid_term', 19.00, 25),
    (1, 4, 'assignment_1', 3.50, 10),
    (1, 4, 'mid_term', 21.00, 25),
    (2, 4, 'assignment_1', 2.50, 10),
    (2, 4, 'mid_term', 20.00, 25),
    (1, 5, 'assignment_1', 2.00, 10),
    (1, 5, 'mid_term', 19.50, 25),
    (2, 5, 'assignment_1', 3.50, 10),
    (2, 5, 'mid_term', 22.00, 25),
    (1, 6, 'assignment_1', 1.50, 10),
    (1, 6, 'mid_term', 17.00, 25),
    (2, 6, 'assignment_1', 2.50, 10),
    (2, 6, 'mid_term', 18.00, 25),
    (1, 7, 'assignment_1', 3.00, 10),
    (1, 7, 'mid_term', 22.50, 25),
    (2, 7, 'assignment_1', 3.50, 10),
    (2, 7, 'mid_term', 21.50, 25),
    (1, 8, 'assignment_1', 2.50, 10),
    (1, 8, 'mid_term', 20.50, 25),
    (2, 8, 'assignment_1', 2.00, 10),
    (2, 8, 'mid_term', 19.50, 25),
    (1, 9, 'assignment_1', 3.50, 10),
    (1, 9, 'mid_term', 23.00, 25),
    (2, 9, 'assignment_1', 3.00, 10),
    (2, 9, 'mid_term', 20.50, 25),
    (1, 10, 'assignment_1', 3.00, 10),
    (1, 10, 'mid_term', 21.00, 25),
    (2, 10, 'assignment_1', 2.50, 10),
    (2, 10, 'mid_term', 22.00, 25),
    (1, 11, 'assignment_1', 2.00, 10),
    (1, 11, 'mid_term', 19.00, 25),
    (2, 11, 'assignment_1', 3.50, 10),
    (2, 11, 'mid_term', 20.00, 25);

INSERT INTO mark_finalizations (course_id, student_id, is_finalized, finalized_at) VALUES
    (1, 2, 0, NULL), (2, 2, 0, NULL),
    (1, 3, 0, NULL), (2, 3, 0, NULL),
    (1, 4, 0, NULL), (2, 4, 0, NULL),
    (1, 5, 0, NULL), (2, 5, 0, NULL),
    (1, 6, 0, NULL), (2, 6, 0, NULL),
    (1, 7, 0, NULL), (2, 7, 0, NULL),
    (1, 8, 0, NULL), (2, 8, 0, NULL),
    (1, 9, 0, NULL), (2, 9, 0, NULL),
    (1, 10, 0, NULL), (2, 10, 0, NULL),
    (1, 11, 0, NULL), (2, 11, 0, NULL);

INSERT INTO queries (id, user_id, subject, message, status, reply, created_at) VALUES
    (1, 2, 'Attendance correction', 'Please check my attendance for CS101.', 'open', NULL, NOW()),
    (2, 3, 'Assignment deadline', 'Can the assignment deadline be extended?', 'open', NULL, NOW()),
    (3, 4, 'Marks review', 'Please review my recent mid term marks.', 'open', NULL, NOW());

INSERT INTO applications (id, user_id, type, details, status, created_at) VALUES
    (1, 2, 'Leave Request', 'Medical leave for one day.', 'pending', NOW()),
    (2, 3, 'Leave Request', 'Family event on Monday.', 'pending', NOW());

INSERT INTO fee_records (id, student_id, semester, description, amount, paid_amount, due_date, status) VALUES
    (1, 2, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 85000.00, '2026-07-15', 'paid'),
    (2, 2, 'Spring 2026', 'Library and Lab Charges', 7500.00, 7500.00, '2026-07-15', 'paid'),
    (3, 3, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 60000.00, '2026-07-15', 'partial'),
    (4, 4, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 0.00, '2026-07-15', 'unpaid'),
    (5, 5, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 85000.00, '2026-07-15', 'paid'),
    (6, 6, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 40000.00, '2026-07-15', 'partial'),
    (7, 7, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 85000.00, '2026-07-15', 'paid'),
    (8, 8, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 0.00, '2026-07-15', 'unpaid'),
    (9, 9, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 85000.00, '2026-07-15', 'paid'),
    (10, 10, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 50000.00, '2026-07-15', 'partial'),
    (11, 11, 'Spring 2026', 'Semester Tuition Fee', 85000.00, 85000.00, '2026-07-15', 'paid');

INSERT INTO notifications (recipient_user_id, sender_user_id, category, title, body, link_url, is_read, created_at) VALUES
    (2, 1, 'notification', 'New assignment posted', 'An assignment titled "Loops Practice" has been uploaded for CS101.', 'student/submissions.php', 0, NOW()),
    (2, 1, 'notification', 'New assignment posted', 'An assignment titled "ERD Design" has been uploaded for SE204.', 'student/submissions.php', 0, NOW()),
    (2, 1, 'message', 'Welcome to University LMS', 'Check your courses and keep an eye on assignments in the dashboard.', 'student/dashboard.php', 0, NOW()),
    (3, 1, 'notification', 'New assignment posted', 'An assignment titled "Loops Practice" has been uploaded for CS101.', 'student/submissions.php', 0, NOW()),
    (3, 1, 'message', 'Welcome to University LMS', 'Check your courses and keep an eye on assignments in the dashboard.', 'student/dashboard.php', 0, NOW()),
    (4, 1, 'notification', 'New assignment posted', 'An assignment titled "Loops Practice" has been uploaded for CS101.', 'student/submissions.php', 0, NOW()),
    (5, 1, 'notification', 'New assignment posted', 'An assignment titled "ERD Design" has been uploaded for SE204.', 'student/submissions.php', 0, NOW()),
    (6, 1, 'announcement', 'Semester update', 'Spring 2026 notices will appear here.', 'student/dashboard.php', 0, NOW()),
    (7, 1, 'announcement', 'Semester update', 'Spring 2026 notices will appear here.', 'student/dashboard.php', 0, NOW()),
    (8, 1, 'message', 'Welcome to University LMS', 'Check your dashboard for course updates.', 'student/dashboard.php', 0, NOW()),
    (9, 1, 'message', 'Welcome to University LMS', 'Check your dashboard for course updates.', 'student/dashboard.php', 0, NOW()),
    (10, 1, 'notification', 'New assignment posted', 'An assignment titled "ERD Design" has been uploaded for SE204.', 'student/submissions.php', 0, NOW()),
    (11, 1, 'notification', 'New assignment posted', 'An assignment titled "Loops Practice" has been uploaded for CS101.', 'student/submissions.php', 0, NOW());
