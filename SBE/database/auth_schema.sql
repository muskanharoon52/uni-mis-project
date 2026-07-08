CREATE TABLE IF NOT EXISTS auth_users (
    auth_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    role ENUM('teacher', 'student') NOT NULL,
    login_id VARCHAR(50) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (auth_id),
    UNIQUE KEY uk_auth_users_role_login_id (role, login_id),
    KEY idx_auth_users_role (role),
    KEY idx_auth_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
