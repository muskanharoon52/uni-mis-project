-- University LMS demo seed
-- Load after `schema.sql`.

USE `lms_mis`;

INSERT INTO users (login_id, name, email, password_hash, role, department, program, profile_photo, created_at) VALUES
    ('5001', 'Dr. Sara Khan',      'sara.khan@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Computer Science',          NULL, NULL, NOW()),
    ('5002', 'Dr. Ahmed Ali',      'ahmed.ali@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Computer Science',          NULL, NULL, NOW()),
    ('5003', 'Dr. Fatima Noor',    'fatima.noor@lms.test',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Software Engineering',      NULL, NULL, NOW()),
    ('5004', 'Prof. Imran Shah',   'imran.shah@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Information Technology',    NULL, NULL, NOW()),
    ('5005', 'Dr. Zainab Malik',   'zainab.malik@lms.test',   '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'teacher', 'Data Science',             NULL, NULL, NOW()),
    ('9001', 'Ali Raza',           'ali.raza@lms.test',        '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9002', 'Amina Noor',         'amina.noor@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9003', 'Bilal Ahmed',        'bilal.ahmed@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology',  NULL, NOW()),
    ('9004', 'Hira Khan',          'hira.khan@lms.test',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science',        NULL, NOW()),
    ('9005', 'Hassan Ali',         'hassan.ali@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9006', 'Maryam Iqbal',       'maryam.iqbal@lms.test',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science',            NULL, NOW()),
    ('9007', 'Usman Tariq',        'usman.tariq@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science',        NULL, NOW()),
    ('9008', 'Sana Raza',          'sana.raza@lms.test',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology',  NULL, NOW()),
    ('9009', 'Zain Malik',         'zain.malik@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9010', 'Iqra Javed',         'iqra.javed@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science',            NULL, NOW()),
    ('9011', 'Omar Farooq',        'omar.farooq@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science',        NULL, NOW()),
    ('9012', 'Nadia Hussain',      'nadia.hussain@lms.test',   '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9013', 'Saad Khan',          'saad.khan@lms.test',       '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology',  NULL, NOW()),
    ('9014', 'Ayesha Siddiqui',    'ayesha.siddiqui@lms.test', '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science',            NULL, NOW()),
    ('9015', 'Danish Raza',        'danish.raza@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science',        NULL, NOW()),
    ('9016', 'Fatima Zahra',       'fatima.zahra@lms.test',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW()),
    ('9017', 'Hamza Tariq',        'hamza.tariq@lms.test',     '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Information Technology',  NULL, NOW()),
    ('9018', 'Javeria Akhtar',     'javeria.akhtar@lms.test',  '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Data Science',            NULL, NOW()),
    ('9019', 'Kamran Malik',       'kamran.malik@lms.test',    '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Computer Science',        NULL, NOW()),
    ('9020', 'Laiba Shah',         'laiba.shah@lms.test',      '$2y$10$k8/5TEYHKt4qecH5vqVeoOG3SZEPKCr/0jvffQIh/YQjteBHpjarq', 'student', 'Computer Science', 'BS Software Engineering',    NULL, NOW());

INSERT INTO courses (id, code, title, description, credit_hours, teacher_id, semester) VALUES
    (1,  'CS101',  'Introduction to Programming',     'Fundamentals of programming using clear logic and problem solving.',                          3, 1, 'Spring 2026'),
    (2,  'SE204',  'Database Systems',                'Design and manage relational databases and SQL-based applications.',                           3, 1, 'Spring 2026'),
    (3,  'CS201',  'Data Structures & Algorithms',    'Study of arrays, linked lists, trees, graphs, and sorting algorithms.',                       3, 2, 'Spring 2026'),
    (4,  'SE301',  'Software Engineering',            'Software lifecycle models, requirements, design, testing, and maintenance.',                   3, 3, 'Spring 2026'),
    (5,  'IT101',  'Computer Networks',               'OSI model, TCP/IP, routing, switching, and network security basics.',                         3, 4, 'Spring 2026'),
    (6,  'DS101',  'Introduction to Data Science',    'Python, pandas, NumPy, data wrangling, and exploratory data analysis.',                       3, 5, 'Spring 2026'),
    (7,  'CS301',  'Operating Systems',               'Process management, memory, file systems, and concurrency concepts.',                          3, 2, 'Spring 2026'),
    (8,  'SE401',  'Web Engineering',                 'Full-stack web development with PHP, MySQL, and modern JS frameworks.',                       3, 3, 'Spring 2026');

-- Enrollments: varied per student/program
INSERT INTO enrollments (student_id, course_id) VALUES
    (6,1),(6,2),(6,3),(6,4),(6,7),
    (7,1),(7,2),(7,3),(7,4),(7,7),
    (8,0),(8,1),(8,4),(8,5),(8,7),
    (9,1),(9,2),(9,3),(9,4),(9,7),
    (10,0),(10,1),(10,3),(10,4),(10,7),
    (11,1),(11,5),(11,6),
    (12,1),(12,6),(12,7),
    (13,1),(13,2),(13,4),(13,5),
    (14,0),(14,3),(14,4),(14,7),
    (15,1),(15,2),(15,3),(15,6),
    (16,1),(16,3),(16,4),(16,7),
    (17,2),(17,4),(17,5),(17,7),
    (18,1),(18,2),(18,4),(18,7),
    (19,2),(19,5),(19,6),
    (20,2),(1,5),(1,6),
    (21,1),(21,2),(21,6),(21,7),
    (22,2),(22,3),(22,4),(22,7),
    (23,0),(23,2),(23,3),(23,6),(23,7),
    (24,0),(24,2),(24,6),(24,7),
    (25,1),(25,3),(25,4),(25,7);
