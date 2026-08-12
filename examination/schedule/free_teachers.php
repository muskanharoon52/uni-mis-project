<?php
// examination/schedule/free_teachers.php
// AJAX endpoint: returns active teachers who are NOT assigned as invigilators
// to an overlapping exam on the given date within the given time window.

require_once __DIR__ . '/../../config/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

$date  = trim((string) ($_GET['date'] ?? ''));
$start = trim((string) ($_GET['start'] ?? ''));
$end   = trim((string) ($_GET['end'] ?? ''));
$excludeScheduleId = (int) ($_GET['exclude_schedule_id'] ?? 0);

if ($date === '' || $start === '' || $end === '' || $start >= $end) {
    echo json_encode(['ok' => false, 'error' => 'Invalid date or time window.']);
    exit;
}

$conn = getConnection();

$busy = [];
$sql = "SELECT DISTINCT inv.teacher_id
        FROM sbe_exam_invigilators inv
        JOIN sbe_exam_schedule es ON es.schedule_id = inv.schedule_id
        WHERE es.exam_date = ?
          AND es.status != 'Cancelled'
          AND es.start_time < ? AND ? < es.end_time";
$params = [$date, $end, $start];
if ($excludeScheduleId > 0) {
    $sql .= " AND es.schedule_id != ?";
    $params[] = $excludeScheduleId;
}
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['ok' => false, 'error' => 'Database error.']);
    exit;
}
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $busy[] = (int) $r['teacher_id'];
}
$stmt->close();

if (!empty($busy)) {
    $in = implode(',', array_fill(0, count($busy), '?'));
    $sql = "SELECT t.teacher_id, t.teacher_name, d.department_name
            FROM teachers t
            LEFT JOIN departments d ON d.department_id = t.department_id
            WHERE t.status = 'Active' AND t.teacher_id NOT IN ($in)
            ORDER BY t.teacher_name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['ok' => false, 'error' => 'Database error.']);
        exit;
    }
    $stmt->bind_param(str_repeat('i', count($busy)), ...$busy);
} else {
    $sql = "SELECT t.teacher_id, t.teacher_name, d.department_name
            FROM teachers t
            LEFT JOIN departments d ON d.department_id = t.department_id
            WHERE t.status = 'Active'
            ORDER BY t.teacher_name";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['ok' => false, 'error' => 'Database error.']);
        exit;
    }
}

$stmt->execute();
$res = $stmt->get_result();
$teachers = [];
while ($r = $res->fetch_assoc()) {
    $teachers[] = [
        'id' => (int) $r['teacher_id'],
        'name' => (string) $r['teacher_name'],
        'department' => (string) ($r['department_name'] ?? ''),
    ];
}
$stmt->close();

echo json_encode(['ok' => true, 'count' => count($teachers), 'teachers' => $teachers]);
