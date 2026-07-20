<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['auth_user'] = [
        'id'           => (int) ($user['id'] ?? 0),
        'login_id'     => (string) ($user['login_id'] ?? ''),
        'role'         => (string) ($user['role'] ?? ''),
        'name'         => (string) ($user['name'] ?? ''),
        'department'   => (string) ($user['department'] ?? ''),
        'program'      => (string) ($user['program'] ?? ''),
        'profile_photo'=> (string) ($user['profile_photo'] ?? ''),
    ];
}

function auth_logout(): void
{
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    session_destroy();
}

function require_login(): array
{
    $user = current_user();

    if (!$user) {
        header('Location: ' . app_url('public/login.php'));
        exit;
    }

    return $user;
}

function require_role(string $role): array
{
    $user = require_login();

    if ($user['role'] !== $role) {
        header('Location: ' . app_url('public/login.php'));
        exit;
    }

    return $user;
}

function teacher_owns_course(int $teacherId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE course_id = ? AND teacher_id = ?');
    $stmt->execute([$courseId, $teacherId]);
    return (int) $stmt->fetchColumn() > 0;
}

function student_enrolled_in_course(int $studentId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM lms_enrollments WHERE student_user_id = ? AND course_id = ?');
    $stmt->execute([$studentId, $courseId]);
    return (int) $stmt->fetchColumn() > 0;
}

function teacher_owns_submission(int $teacherId, int $submissionId): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM lms_submissions s
         JOIN lms_assignments a ON a.assignment_id = s.assignment_id
         JOIN courses c ON c.course_id = a.course_id
         WHERE s.submission_id = ? AND c.teacher_id = ?'
    );
    $stmt->execute([$submissionId, $teacherId]);
    return (int) $stmt->fetchColumn() > 0;
}

function save_uploaded_file(string $field, string $folder, array $allowedExtensions): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed. Please try again.');
    }
    if ((int) $_FILES[$field]['size'] > 10 * 1024 * 1024) {
        throw new RuntimeException('File is too large. Maximum size is 10 MB.');
    }

    $originalName = (string) $_FILES[$field]['name'];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        throw new RuntimeException('Invalid file type. Allowed: ' . implode(', ', $allowedExtensions));
    }

    $uploadRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $folder;
    if (!is_dir($uploadRoot)) {
        mkdir($uploadRoot, 0775, true);
    }

    $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $fileName = $safeName . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $target = $uploadRoot . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file((string) $_FILES[$field]['tmp_name'], $target)) {
        throw new RuntimeException('Could not save uploaded file.');
    }
    return 'uploads/' . $folder . '/' . $fileName;
}

function internal_mark_components(): array
{
    return [
        'assignment_1' => 'A1', 'assignment_2' => 'A2', 'assignment_3' => 'A3',
        'test_1' => 'T1', 'test_2' => 'T2', 'test_3' => 'T3',
        'presentation' => 'Presentation', 'major_assignment' => 'Major Assignment', 'mid_term' => 'Mid Term',
    ];
}

function internal_mark_rows_for_student(int $studentId): array
{
    $stmt = db()->prepare(
        'SELECT c.course_id AS course_id, c.course_code AS code, c.course_title AS title,
            COALESCE(f.is_finalized, 0) AS is_finalized,
            m.component, m.marks_obtained
         FROM lms_enrollments e
         JOIN courses c ON c.course_id = e.course_id
         LEFT JOIN lms_mark_finalizations f ON f.course_id = c.course_id AND f.student_user_id = e.student_user_id
         LEFT JOIN lms_marks m ON m.course_id = c.course_id AND m.student_user_id = e.student_user_id
         WHERE e.student_user_id = ?
         ORDER BY c.course_code'
    );
    $stmt->execute([$studentId]);
    return build_internal_mark_rows($stmt->fetchAll());
}

function internal_mark_rows_for_teacher(int $teacherId): array
{
    $stmt = db()->prepare(
        'SELECT c.course_id AS course_id, c.course_code AS code, c.course_title AS title,
            u.user_id AS student_id, u.full_name AS student_name,
            COALESCE(f.is_finalized, 0) AS is_finalized,
            m.component, m.marks_obtained
         FROM courses c
         JOIN lms_enrollments e ON e.course_id = c.course_id
         JOIN users u ON u.user_id = e.student_user_id
         LEFT JOIN lms_mark_finalizations f ON f.course_id = c.course_id AND f.student_user_id = u.user_id
         LEFT JOIN lms_marks m ON m.course_id = c.course_id AND m.student_user_id = u.user_id
         WHERE c.teacher_id = ?
         ORDER BY c.course_code, u.full_name'
    );
    $stmt->execute([$teacherId]);
    return build_internal_mark_rows($stmt->fetchAll());
}

function build_internal_mark_rows(array $records): array
{
    $components = internal_mark_components();
    $rows = [];
    foreach ($records as $record) {
        $key = (int) $record['course_id'] . ':' . (int) ($record['student_id'] ?? 0);
        if (!isset($rows[$key])) {
            $rows[$key] = [
                'course_id' => (int) $record['course_id'],
                'student_id' => (int) ($record['student_id'] ?? 0),
                'student_name' => $record['student_name'] ?? '',
                'code' => $record['code'],
                'title' => $record['title'],
                'is_finalized' => (int) $record['is_finalized'],
                'marks' => array_fill_keys(array_keys($components), 0.0),
            ];
        }
        if ($record['component'] && array_key_exists($record['component'], $components)) {
            $rows[$key]['marks'][$record['component']] = (float) $record['marks_obtained'];
        }
    }
    return array_values($rows);
}

function internal_mark_total(array $row): float
{
    return array_sum(array_map('floatval', $row['marks']));
}

function unread_notification_count(int $userId, ?string $category = null): int
{
    $sql = 'SELECT COUNT(*) FROM lms_notifications WHERE recipient_user_id = ? AND is_read = 0';
    $params = [$userId];
    if ($category !== null) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function recent_notifications(int $userId, ?string $category = null, int $limit = 5): array
{
    $sql = 'SELECT * FROM lms_notifications WHERE recipient_user_id = ?';
    $params = [$userId];
    if ($category !== null) {
        $sql .= ' AND category = ?';
        $params[] = $category;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT ' . (int) $limit;
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function create_notification(int $recipientId, string $title, string $body, ?string $linkUrl = null, string $category = 'notification', ?int $senderId = null): void
{
    $stmt = db()->prepare(
        'INSERT INTO lms_notifications (recipient_user_id, sender_user_id, category, title, body, link_url) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$recipientId, $senderId, $category, $title, $body, $linkUrl]);
}

function teacher_course_student_ids(int $teacherId, ?int $courseId = null): array
{
    if ($courseId !== null) {
        $stmt = db()->prepare('SELECT DISTINCT e.student_user_id FROM lms_enrollments e JOIN courses c ON c.course_id = e.course_id WHERE c.teacher_id = ? AND c.course_id = ?');
        $stmt->execute([$teacherId, $courseId]);
    } else {
        $stmt = db()->prepare('SELECT DISTINCT e.student_user_id FROM lms_enrollments e JOIN courses c ON c.course_id = e.course_id WHERE c.teacher_id = ?');
        $stmt->execute([$teacherId]);
    }
    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function notify_course_students(int $courseId, string $title, string $body, ?string $linkUrl = null, string $category = 'notification', ?int $senderId = null): void
{
    $teacherStmt = db()->prepare('SELECT teacher_id FROM courses WHERE course_id = ? LIMIT 1');
    $teacherStmt->execute([$courseId]);
    $teacherId = (int) $teacherStmt->fetchColumn();
    if ($teacherId <= 0) return;
    foreach (teacher_course_student_ids($teacherId, $courseId) as $studentId) {
        create_notification($studentId, $title, $body, $linkUrl, $category, $senderId);
    }
}

function notify_teacher_students(int $teacherId, string $title, string $body, ?string $linkUrl = null, string $category = 'notification', ?int $courseId = null): void
{
    foreach (teacher_course_student_ids($teacherId, $courseId) as $studentId) {
        create_notification($studentId, $title, $body, $linkUrl, $category, $teacherId);
    }
}
