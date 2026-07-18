CREATE TABLE IF NOT EXISTS sbe_auth_users (
    auth_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role ENUM('Teacher', 'Student') NOT NULL,
    login_id VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    teacher_id INT UNSIGNED NULL,
    student_id INT UNSIGNED NULL,
    status ENUM('Active', 'Inactive') NOT NULL DEFAULT 'Active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (auth_id),
    UNIQUE KEY uk_sbe_auth_users_role_login_id (role, login_id),
    KEY idx_sbe_auth_users_role (role),
    KEY idx_sbe_auth_users_status (status),
    KEY idx_sbe_auth_users_teacher_id (teacher_id),
    KEY idx_sbe_auth_users_student_id (student_id),
    CONSTRAINT chk_sbe_auth_users_role CHECK (role IN ('Teacher', 'Student'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
