INSERT INTO sbe_auth_users (role, login_id, password_hash, display_name, teacher_id, student_id, status)
VALUES
('Teacher', '5001', '$2y$10$8JzqZK7E7bTn1TQx4K4oQO7hKj2r2QzP7cU8b3oC3P8k9uJfG0v4m', 'Teacher 5001', 1, NULL, 'Active'),
('Teacher', '5002', '$2y$10$8JzqZK7E7bTn1TQx4K4oQO7hKj2r2QzP7cU8b3oC3P8k9uJfG0v4m', 'Teacher 5002', 2, NULL, 'Active'),
('Student', '9001', '$2y$10$8JzqZK7E7bTn1TQx4K4oQO7hKj2r2QzP7cU8b3oC3P8k9uJfG0v4m', 'Student 9001', NULL, 9001, 'Active'),
('Student', '9002', '$2y$10$8JzqZK7E7bTn1TQx4K4oQO7hKj2r2QzP7cU8b3oC3P8k9uJfG0v4m', 'Student 9002', NULL, 9002, 'Active');
