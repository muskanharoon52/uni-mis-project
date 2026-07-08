<?php

declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    $stmt = db()->prepare('SELECT id, name, email, role, department, program, profile_photo, created_at FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    return $user ?: null;
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
        header('Location: ' . dashboard_url($user['role']));
        exit;
    }

    return $user;
}

function dashboard_url(string $role): string
{
    return $role === 'teacher'
        ? app_url('teacher/dashboard.php')
        : app_url('student/dashboard.php');
}

function redirect_to_dashboard(array $user): void
{
    header('Location: ' . dashboard_url($user['role']));
    exit;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['name'] = (string) ($user['name'] ?? '');
    $_SESSION['role'] = (string) ($user['role'] ?? '');
}

function logout_user(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function old(string $key, string $default = ''): string
{
    return htmlspecialchars((string) ($_POST[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if ($postedToken === '' || !hash_equals(csrf_token(), $postedToken)) {
        throw new RuntimeException('Your session expired. Please refresh and try again.');
    }
}

function teacher_owns_course(int $teacherId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM courses WHERE id = ? AND teacher_id = ?');
    $stmt->execute([$courseId, $teacherId]);

    return (int) $stmt->fetchColumn() > 0;
}

function student_enrolled_in_course(int $studentId, int $courseId): bool
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM enrollments WHERE student_id = ? AND course_id = ?');
    $stmt->execute([$studentId, $courseId]);

    return (int) $stmt->fetchColumn() > 0;
}

function teacher_can_access_student_course(int $teacherId, int $studentId, int $courseId): bool
{
    if (!teacher_owns_course($teacherId, $courseId)) {
        return false;
    }

    return student_enrolled_in_course($studentId, $courseId);
}

function teacher_owns_submission(int $teacherId, int $submissionId): bool
{
    $stmt = db()->prepare(
        'SELECT COUNT(*)
         FROM submissions s
         JOIN assignments a ON a.id = s.assignment_id
         JOIN courses c ON c.id = a.course_id
         WHERE s.id = ? AND c.teacher_id = ?'
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
        throw new RuntimeException('Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions) . '.');
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
        'assignment_1' => 'A1',
        'assignment_2' => 'A2',
        'assignment_3' => 'A3',
        'test_1' => 'T1',
        'test_2' => 'T2',
        'test_3' => 'T3',
        'presentation' => 'Presentation',
        'major_assignment' => 'Major Assignment',
        'mid_term' => 'Mid Term',
    ];
}

function internal_mark_rows_for_student(int $studentId): array
{
    $stmt = db()->prepare(
        'SELECT c.id AS course_id, c.code, c.title,
            COALESCE(f.is_finalized, 0) AS is_finalized,
            m.component, m.marks_obtained
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id
         LEFT JOIN mark_finalizations f ON f.course_id = c.id AND f.student_id = e.student_id
         LEFT JOIN marks m ON m.course_id = c.id AND m.student_id = e.student_id
         WHERE e.student_id = ?
         ORDER BY c.code'
    );
    $stmt->execute([$studentId]);

    return build_internal_mark_rows($stmt->fetchAll());
}

function internal_mark_rows_for_teacher(int $teacherId): array
{
    $stmt = db()->prepare(
        'SELECT c.id AS course_id, c.code, c.title,
            u.id AS student_id, u.name AS student_name,
            COALESCE(f.is_finalized, 0) AS is_finalized,
            m.component, m.marks_obtained
         FROM courses c
         JOIN enrollments e ON e.course_id = c.id
         JOIN users u ON u.id = e.student_id
         LEFT JOIN mark_finalizations f ON f.course_id = c.id AND f.student_id = u.id
         LEFT JOIN marks m ON m.course_id = c.id AND m.student_id = u.id
         WHERE c.teacher_id = ?
         ORDER BY c.code, u.name'
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
    $sql = 'SELECT COUNT(*) FROM notifications WHERE recipient_user_id = ? AND is_read = 0';
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
    $sql = 'SELECT * FROM notifications WHERE recipient_user_id = ?';
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
        'INSERT INTO notifications (recipient_user_id, sender_user_id, category, title, body, link_url)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$recipientId, $senderId, $category, $title, $body, $linkUrl]);
}

function teacher_course_student_ids(int $teacherId, ?int $courseId = null): array
{
    $stmt = db()->prepare(
        'SELECT DISTINCT e.student_id
         FROM enrollments e
         JOIN courses c ON c.id = e.course_id
         WHERE c.teacher_id = ?'
    );
    $params = [$teacherId];

    if ($courseId !== null) {
        $stmt = db()->prepare(
            'SELECT DISTINCT e.student_id
             FROM enrollments e
             JOIN courses c ON c.id = e.course_id
             WHERE c.teacher_id = ? AND c.id = ?'
        );
        $params[] = $courseId;
    }

    $stmt->execute($params);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}

function notify_course_students(int $courseId, string $title, string $body, ?string $linkUrl = null, string $category = 'notification', ?int $senderId = null): void
{
    $teacherStmt = db()->prepare('SELECT teacher_id FROM courses WHERE id = ? LIMIT 1');
    $teacherStmt->execute([$courseId]);
    $teacherId = (int) $teacherStmt->fetchColumn();

    if ($teacherId <= 0) {
        return;
    }

    $studentIds = teacher_course_student_ids($teacherId, $courseId);

    foreach ($studentIds as $studentId) {
        create_notification($studentId, $title, $body, $linkUrl, $category, $senderId);
    }
}

function notify_teacher_students(int $teacherId, string $title, string $body, ?string $linkUrl = null, string $category = 'notification', ?int $courseId = null): void
{
    foreach (teacher_course_student_ids($teacherId, $courseId) as $studentId) {
        create_notification($studentId, $title, $body, $linkUrl, $category, $teacherId);
    }
}
