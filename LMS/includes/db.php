<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $serverDsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $server = new PDO($serverDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $server->exec(
        'CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    );

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    initialize_database($pdo);

    return $pdo;
}

function initialize_database(PDO $pdo): void
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(160) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('student','teacher') NOT NULL,
            department VARCHAR(120) NULL,
            program VARCHAR(120) NULL,
            profile_photo VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    );
    ensure_column($pdo, 'users', 'profile_photo', 'VARCHAR(255) NULL');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS courses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(30) NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT NULL,
            credit_hours INT NOT NULL DEFAULT 3,
            teacher_id INT NULL,
            semester VARCHAR(40) NOT NULL DEFAULT 'Spring 2026',
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB"
    );
    ensure_column($pdo, 'courses', 'description', 'TEXT NULL');
    ensure_column($pdo, 'courses', 'credit_hours', 'INT NOT NULL DEFAULT 3');
    $pdo->exec(
        "UPDATE courses
         SET description = CASE code
            WHEN 'CS101' THEN 'Fundamentals of programming using clear logic and problem solving.'
            WHEN 'SE204' THEN 'Design and manage relational databases and SQL-based applications.'
            ELSE COALESCE(description, title)
         END
         WHERE description IS NULL OR description = ''"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            course_id INT NOT NULL,
            UNIQUE KEY unique_enrollment (student_id, course_id),
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS assignments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(160) NOT NULL,
            description TEXT NULL,
            file_path VARCHAR(255) NULL,
            due_date DATE NOT NULL,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS lectures (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            title VARCHAR(160) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            lecture_date DATE NULL,
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS submissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            assignment_id INT NOT NULL,
            student_id INT NOT NULL,
            content TEXT NOT NULL,
            submission_file VARCHAR(255) NULL,
            submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            grade DECIMAL(5,2) NULL,
            feedback TEXT NULL,
            UNIQUE KEY unique_submission (assignment_id, student_id),
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    ensure_column($pdo, 'assignments', 'file_path', 'VARCHAR(255) NULL');
    ensure_column($pdo, 'submissions', 'submission_file', 'VARCHAR(255) NULL');

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            student_id INT NOT NULL,
            class_date DATE NOT NULL,
            status ENUM('present','absent','late') NOT NULL,
            UNIQUE KEY unique_attendance (course_id, student_id, class_date),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            student_id INT NOT NULL,
            component VARCHAR(100) NOT NULL,
            marks_obtained DECIMAL(6,2) NOT NULL,
            total_marks DECIMAL(6,2) NOT NULL,
            UNIQUE KEY unique_mark (course_id, student_id, component),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS mark_finalizations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            course_id INT NOT NULL,
            student_id INT NOT NULL,
            is_finalized TINYINT(1) NOT NULL DEFAULT 0,
            finalized_at TIMESTAMP NULL,
            UNIQUE KEY unique_finalization (course_id, student_id),
            FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS queries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            subject VARCHAR(160) NOT NULL,
            message TEXT NOT NULL,
            status ENUM('open','answered','closed') NOT NULL DEFAULT 'open',
            reply TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS applications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(100) NOT NULL,
            details TEXT NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            recipient_user_id INT NOT NULL,
            sender_user_id INT NULL,
            category ENUM('notification','message','announcement') NOT NULL DEFAULT 'notification',
            title VARCHAR(160) NOT NULL,
            body TEXT NOT NULL,
            link_url VARCHAR(255) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (recipient_user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS fee_records (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            semester VARCHAR(40) NOT NULL,
            description VARCHAR(160) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            paid_amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            due_date DATE NOT NULL,
            status ENUM('paid','partial','unpaid') NOT NULL DEFAULT 'unpaid',
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB"
    );

    seed_database($pdo);
    seed_fee_records($pdo);
    seed_internal_marks($pdo);
    seed_demo_notifications($pdo);
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([DB_NAME, $table, $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
    }
}

function seed_database(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $password = password_hash('password123', PASSWORD_DEFAULT);

    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role, department, program) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $insertUser->execute(['Dr. Sara Khan', 'teacher@lms.test', $password, 'teacher', 'Computer Science', null]);
    $teacherId = (int) $pdo->lastInsertId();

    $students = [
        ['Ali Raza', 'student@lms.test', 'BS Software Engineering'],
        ['Amina Noor', 'amina.noor@lms.test', 'BS Software Engineering'],
        ['Bilal Ahmed', 'bilal.ahmed@lms.test', 'BS Information Technology'],
        ['Hira Khan', 'hira.khan@lms.test', 'BS Computer Science'],
        ['Hassan Ali', 'hassan.ali@lms.test', 'BS Software Engineering'],
        ['Maryam Iqbal', 'maryam.iqbal@lms.test', 'BS Data Science'],
        ['Usman Tariq', 'usman.tariq@lms.test', 'BS Computer Science'],
        ['Sana Raza', 'sana.raza@lms.test', 'BS Information Technology'],
        ['Zain Malik', 'zain.malik@lms.test', 'BS Software Engineering'],
        ['Iqra Javed', 'iqra.javed@lms.test', 'BS Data Science'],
    ];

    $studentIds = [];
    foreach ($students as [$name, $email, $program]) {
        $insertUser->execute([$name, $email, $password, 'student', 'Computer Science', $program]);
        $studentIds[] = (int) $pdo->lastInsertId();
    }

    $insertCourse = $pdo->prepare('INSERT INTO courses (code, title, description, credit_hours, teacher_id, semester) VALUES (?, ?, ?, ?, ?, ?)');
    $insertCourse->execute(['CS101', 'Introduction to Programming', 'Fundamentals of programming using clear logic and problem solving.', 3, $teacherId, 'Spring 2026']);
    $courseOne = (int) $pdo->lastInsertId();
    $insertCourse->execute(['SE204', 'Database Systems', 'Design and manage relational databases and SQL-based applications.', 3, $teacherId, 'Spring 2026']);
    $courseTwo = (int) $pdo->lastInsertId();

    $enroll = $pdo->prepare('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)');
    foreach ($studentIds as $studentId) {
        $enroll->execute([$studentId, $courseOne]);
        $enroll->execute([$studentId, $courseTwo]);
    }

    $assignment = $pdo->prepare(
        'INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)'
    );
    $assignment->execute([$courseOne, 'Loops Practice', 'Submit five solved loop problems.', '2026-07-20']);
    $assignment->execute([$courseTwo, 'ERD Design', 'Create an ERD for a library system.', '2026-07-25']);

    $courseCodeStmt = $pdo->prepare('SELECT code FROM courses WHERE id = ?');
    $courseCodeStmt->execute([$courseOne]);
    $courseOneCode = (string) $courseCodeStmt->fetchColumn();
    $courseCodeStmt->execute([$courseTwo]);
    $courseTwoCode = (string) $courseCodeStmt->fetchColumn();

    $notification = $pdo->prepare(
        'INSERT INTO notifications (recipient_user_id, sender_user_id, category, title, body, link_url) VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($studentIds as $studentId) {
        $notification->execute([
            $studentId,
            $teacherId,
            'notification',
            'New assignment posted',
            'An assignment titled "Loops Practice" has been uploaded for ' . $courseOneCode . '.',
            'student/submissions.php',
        ]);
        $notification->execute([
            $studentId,
            $teacherId,
            'notification',
            'New assignment posted',
            'An assignment titled "ERD Design" has been uploaded for ' . $courseTwoCode . '.',
            'student/submissions.php',
        ]);
        $notification->execute([
            $studentId,
            $teacherId,
            'message',
            'Welcome to University LMS',
            'Keep an eye on your dashboard for assignments, attendance, fees, and internal marks.',
            'student/dashboard.php',
        ]);
        $notification->execute([
            $studentId,
            $teacherId,
            'announcement',
            'Semester update',
            'Spring 2026 notices will appear here.',
            'student/dashboard.php',
        ]);
    }

    $attendance = $pdo->prepare(
        'INSERT INTO attendance (course_id, student_id, class_date, status) VALUES (?, ?, ?, ?)'
    );
    $attendancePattern = ['present', 'late', 'absent', 'present', 'present'];
    foreach ($studentIds as $index => $studentId) {
        foreach ([$courseOne, $courseTwo] as $courseIndex => $courseId) {
            foreach ([1, 3, 5] as $dayOffset) {
                $attendance->execute([
                    $courseId,
                    $studentId,
                    sprintf('2026-07-%02d', $dayOffset + ($index % 4) + $courseIndex),
                    $attendancePattern[($index + $dayOffset + $courseIndex) % count($attendancePattern)],
                ]);
            }
        }
    }

    $mark = $pdo->prepare(
        'INSERT INTO marks (course_id, student_id, component, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($studentIds as $index => $studentId) {
        $mark->execute([$courseOne, $studentId, 'Quiz 1', 6 + ($index % 4), 10]);
        $mark->execute([$courseOne, $studentId, 'Assignment 1', 14 + ($index % 5), 20]);
        $mark->execute([$courseTwo, $studentId, 'Quiz 1', 7 + ($index % 3), 10]);
        $mark->execute([$courseTwo, $studentId, 'Assignment 1', 15 + ($index % 4), 20]);
    }

    $query = $pdo->prepare('INSERT INTO queries (user_id, subject, message, status, reply) VALUES (?, ?, ?, ?, ?)');
    foreach (array_slice($studentIds, 0, 3) as $studentId) {
        $query->execute([$studentId, 'Attendance correction', 'Please check my attendance for CS101.', 'open', null]);
    }

    $application = $pdo->prepare('INSERT INTO applications (user_id, type, details) VALUES (?, ?, ?)');
    foreach (array_slice($studentIds, 0, 2) as $studentId) {
        $application->execute([$studentId, 'Leave Request', 'Medical leave for one day.']);
    }
}

function seed_fee_records(PDO $pdo): void
{
    $studentIds = $pdo->query("SELECT id FROM users WHERE role = 'student' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

    if (!$studentIds) {
        return;
    }

    $insert = $pdo->prepare(
        'INSERT INTO fee_records (student_id, semester, description, amount, paid_amount, due_date, status)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );

    foreach ($studentIds as $index => $studentId) {
        $studentId = (int) $studentId;
        $insert->execute([$studentId, 'Spring 2026', 'Semester Tuition Fee', 85000, 85000, '2026-07-15', 'paid']);
        $insert->execute([$studentId, 'Spring 2026', 'Library and Lab Charges', 7500, 7500, '2026-07-15', 'paid']);
        if ($index % 3 === 0) {
            $insert->execute([$studentId, 'Spring 2026', 'Lab Activity Fee', 1500, 500, '2026-07-25', 'partial']);
        }
    }
}

function seed_internal_marks(PDO $pdo): void
{
    $studentIds = $pdo->query("SELECT id FROM users WHERE role = 'student' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

    if (!$studentIds) {
        return;
    }

    $courses = $pdo->query('SELECT id, code FROM courses ORDER BY id')->fetchAll();
    $samples = [
        'CS101' => [3, 3, 2.5, 0, 2.4, 2.75, 5, 9, 20],
        'SE204' => [2, 5, 3, 0, 4, 0, 6, 8.5, 21],
    ];
    $components = ['assignment_1', 'assignment_2', 'assignment_3', 'test_1', 'test_2', 'test_3', 'presentation', 'major_assignment', 'mid_term'];

    $insert = $pdo->prepare(
        'INSERT INTO marks (course_id, student_id, component, marks_obtained, total_marks)
         VALUES (?, ?, ?, ?, 100)
         ON DUPLICATE KEY UPDATE marks_obtained = marks_obtained'
    );

    foreach ($studentIds as $studentIndex => $studentId) {
        foreach ($courses as $course) {
            $base = $samples[$course['code']] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0];
            foreach ($components as $index => $component) {
                $value = $base[$index] + (($studentIndex % 4) * 0.5);
                $insert->execute([(int) $course['id'], (int) $studentId, $component, $value]);
            }
        }
    }
}

function seed_demo_notifications(PDO $pdo): void
{
    $count = (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();

    if ($count > 0) {
        return;
    }

    $studentIds = $pdo->query("SELECT id FROM users WHERE role = 'student' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);

    if (!$studentIds) {
        return;
    }

    $courseStmt = $pdo->query('SELECT code FROM courses ORDER BY id LIMIT 2');
    $courseCodes = $courseStmt->fetchAll(PDO::FETCH_COLUMN);

    $notification = $pdo->prepare(
        'INSERT INTO notifications (recipient_user_id, sender_user_id, category, title, body, link_url) VALUES (?, ?, ?, ?, ?, ?)'
    );

    foreach ($studentIds as $studentId) {
        foreach ($courseCodes as $courseCode) {
            $notification->execute([
                (int) $studentId,
                1,
                'notification',
                'New assignment posted',
                'An assignment has been uploaded for ' . $courseCode . '.',
                'student/submissions.php',
            ]);
        }
    }
}
