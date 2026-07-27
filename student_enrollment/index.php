<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

if (!function_exists('tableExists')) {
    function tableExists($conn, $tableName) {
        $check = mysqli_query($conn, "SHOW TABLES LIKE '$tableName'");
        return ($check && mysqli_num_rows($check) > 0);
    }
}

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try {
            $query = "SHOW COLUMNS FROM $table LIKE '$column'";
            $result = mysqli_query($conn, $query);
            return ($result && mysqli_num_rows($result) > 0);
        } catch (Exception $e) {
            return false;
        }
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$program_filter = isset($_GET['program']) ? (int)$_GET['program'] : 0;
$semester_filter = isset($_GET['semester']) ? (int)$_GET['semester'] : 0;
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$sectionCoursesExists = tableExists($conn, 'section_courses');
$semestersExists = tableExists($conn, 'semesters');
$sessionsExists = tableExists($conn, 'sessions');
$programsExists = tableExists($conn, 'programs');
$studentEnrollmentsExists = tableExists($conn, 'student_enrollments');

$hasProgramId = columnExists($conn, 'sections', 'program_id');
$hasSemesterId = columnExists($conn, 'sections', 'semester_id');
$hasSessionId = columnExists($conn, 'sections', 'session_id');
$hasEnrolledCount = columnExists($conn, 'sections', 'enrolled_count');
$hasCapacity = columnExists($conn, 'sections', 'capacity');
$hasStatus = columnExists($conn, 'sections', 'status');
$hasCreatedAt = columnExists($conn, 'sections', 'created_at');

$selectFields = array("s.section_id", "s.section_name");

if ($hasCapacity) { $selectFields[] = "s.capacity"; } else { $selectFields[] = "0 as capacity"; }
if ($hasEnrolledCount) { $selectFields[] = "s.enrolled_count"; } else { $selectFields[] = "0 as enrolled_count"; }
if ($hasStatus) { $selectFields[] = "s.status"; } else { $selectFields[] = "'Active' as status"; }
if ($hasCreatedAt) { $selectFields[] = "s.created_at"; } else { $selectFields[] = "NULL as created_at"; }

if ($programsExists && $hasProgramId) {
    $selectFields[] = "p.program_name";
    $selectFields[] = "p.program_code";
} else {
    $selectFields[] = "NULL as program_name";
    $selectFields[] = "NULL as program_code";
}

if ($semestersExists && $hasSemesterId) { $selectFields[] = "sm.semester_name"; } else { $selectFields[] = "NULL as semester_name"; }
if ($sessionsExists && $hasSessionId) { $selectFields[] = "ses.session_name"; } else { $selectFields[] = "NULL as session_name"; }
if ($studentEnrollmentsExists) { $selectFields[] = "COUNT(DISTINCT se.student_id) as total_students"; } else { $selectFields[] = "0 as total_students"; }
if ($sectionCoursesExists) { $selectFields[] = "COUNT(DISTINCT sc.course_id) as total_courses"; } else { $selectFields[] = "0 as total_courses"; }

$sql = "SELECT \n            " . implode(",\n            ", $selectFields);
$sql .= "\n        FROM sections s";

if ($programsExists && $hasProgramId) { $sql .= "\n        LEFT JOIN programs p ON s.program_id = p.program_id"; }
if ($semestersExists && $hasSemesterId) { $sql .= "\n        LEFT JOIN semesters sm ON s.semester_id = sm.semester_id"; }
if ($sessionsExists && $hasSessionId) { $sql .= "\n        LEFT JOIN sessions ses ON s.session_id = ses.session_id"; }
if ($studentEnrollmentsExists && $hasProgramId) { $sql .= "\n        LEFT JOIN student_enrollments se ON s.section_id = se.section_id AND se.status = 'Enrolled'"; }
if ($sectionCoursesExists && $hasProgramId) { $sql .= "\n        LEFT JOIN section_courses sc ON s.section_id = sc.section_id"; }

$sql .= "\n        WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    if ($programsExists && $hasProgramId) {
        $sql .= " AND (s.section_name LIKE ? OR p.program_name LIKE ?)";
        $params[] = "%$search%"; $params[] = "%$search%"; $types .= "ss";
    } else {
        $sql .= " AND s.section_name LIKE ?";
        $params[] = "%$search%"; $types .= "s";
    }
}
if ($program_filter > 0 && $programsExists && $hasProgramId) { $sql .= " AND s.program_id = ?"; $params[] = $program_filter; $types .= "i"; }
if ($semester_filter > 0 && $semestersExists && $hasSemesterId) { $sql .= " AND s.semester_id = ?"; $params[] = $semester_filter; $types .= "i"; }
if (!empty($status_filter) && $hasStatus) { $sql .= " AND s.status = ?"; $params[] = $status_filter; $types .= "s"; }

$sql .= " GROUP BY s.section_id ORDER BY s.section_name";

$stmt = $conn->prepare($sql);
if ($stmt === false) { die("Error in query: " . $conn->error); }
if (!empty($params)) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$result = $stmt->get_result();
$sections = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
$stmt->close();

$stats_query = "SELECT \n                    COUNT(*) as total_sections";
if ($hasStatus) { $stats_query .= ",\n                    SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active_sections"; } else { $stats_query .= ",\n                    0 as active_sections"; }
if ($hasEnrolledCount) { $stats_query .= ",\n                    SUM(enrolled_count) as total_enrolled"; } else { $stats_query .= ",\n                    0 as total_enrolled"; }
if ($hasCapacity) { $stats_query .= ",\n                    SUM(capacity) as total_capacity"; } else { $stats_query .= ",\n                    0 as total_capacity"; }
$stats_query .= "\n                FROM sections";

$stats_result = $conn->query($stats_query);
$stats = $stats_result ? $stats_result->fetch_assoc() : ['total_sections' => 0, 'active_sections' => 0, 'total_enrolled' => 0, 'total_capacity' => 0];

$programs = [];
if ($programsExists) {
    $programs_result = $conn->query("SELECT program_id, program_name, program_code FROM programs WHERE status = 'Active' ORDER BY program_name");
    if ($programs_result) { while ($row = $programs_result->fetch_assoc()) { $programs[] = $row; } }
}

$semesters = [];
if ($semestersExists) {
    $semesters_result = $conn->query("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
    if ($semesters_result) { while ($row = $semesters_result->fetch_assoc()) { $semesters[] = $row; } }
}

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Student Enrollment Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="page-header">
    <h4><i class="fas fa-users"></i> Student Enrollment Management</h4>
    <div class="page-header-actions">
        <a href="add_section.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Create Section</a>
        <a href="enroll_student.php" class="btn btn-success"><i class="fas fa-user-plus"></i> Enroll Student</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div>
<?php endif; ?>

<div class="stat-row">
    <div class="stat-card-v2">
        <div class="stat-card-v2-number"><?= $stats['total_sections'] ?? 0 ?></div>
        <div class="stat-card-v2-label">Total Sections</div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-number"><?= $stats['active_sections'] ?? 0 ?></div>
        <div class="stat-card-v2-label">Active Sections</div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-number"><?= $stats['total_enrolled'] ?? 0 ?></div>
        <div class="stat-card-v2-label">Total Enrolled</div>
    </div>
    <div class="stat-card-v2">
        <div class="stat-card-v2-number"><?= $stats['total_capacity'] ?? 0 ?></div>
        <div class="stat-card-v2-label">Total Capacity</div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;width:100%;">
        <input type="text" name="search" placeholder="Search section or program..." value="<?= htmlspecialchars($search) ?>" style="flex:1;min-width:200px;">
        <select name="program" style="min-width:160px;">
            <option value="0">All Programs</option>
            <?php foreach($programs as $row): ?>
                <option value="<?= $row['program_id'] ?>" <?= $program_filter == $row['program_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['program_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="semester" style="min-width:160px;">
            <option value="0">All Semesters</option>
            <?php foreach($semesters as $row): ?>
                <option value="<?= $row['semester_id'] ?>" <?= $semester_filter == $row['semester_id'] ? 'selected' : '' ?>><?= htmlspecialchars($row['semester_name']) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" style="min-width:140px;">
            <option value="">All Status</option>
            <option value="Active" <?= $status_filter == 'Active' ? 'selected' : '' ?>>Active</option>
            <option value="Inactive" <?= $status_filter == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="index.php" class="btn btn-outline"><i class="fas fa-times"></i> Reset</a>
    </form>
</div>

<?php if (!empty($sections)): ?>
    <div class="card">
        <div class="card-header"><h5>Sections (<?= count($sections) ?>)</h5></div>
        <div style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Section</th>
                            <th>Program</th>
                            <th>Semester</th>
                            <th>Students</th>
                            <th>Capacity</th>
                            <th>Courses</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $count = 1; foreach($sections as $section): $capacity = $section['capacity'] ?? 0; $enrolled = $section['enrolled_count'] ?? 0; $percentage = $capacity > 0 ? ($enrolled / $capacity) * 100 : 0; $color = $percentage >= 90 ? 'red' : ($percentage >= 70 ? 'yellow' : 'green'); ?>
                            <tr>
                                <td><?= $count++ ?></td>
                                <td><strong><?= htmlspecialchars($section['section_name'] ?? 'N/A') ?></strong></td>
                                <td><?= htmlspecialchars($section['program_name'] ?? 'N/A') ?><br><small style="color:var(--muted);"><?= htmlspecialchars($section['program_code'] ?? '') ?></small></td>
                                <td><?= htmlspecialchars($section['semester_name'] ?? 'N/A') ?></td>
                                <td><?= $section['total_students'] ?? 0 ?>
                                    <div class="capacity-bar"><div class="fill <?= $color ?>" style="width: <?= $percentage ?>%"></div></div>
                                    <small><?= round($percentage) ?>% filled</small>
                                </td>
                                <td><?= $capacity ?></td>
                                <td><?= $section['total_courses'] ?? 0 ?></td>
                                <td><span class="status-badge <?= $section['status'] ?? 'Active' ?>"><?= $section['status'] ?? 'Active' ?></span></td>
                                <td style="display:flex;gap:4px;">
                                    <a href="student_list.php?section=<?= $section['section_id'] ?>" class="btn btn-ghost btn-sm" title="View Students"><i class="fas fa-user-graduate"></i></a>
                                    <a href="assign_course.php?section=<?= $section['section_id'] ?>" class="btn btn-ghost btn-sm" title="Assign Course"><i class="fas fa-book"></i></a>
                                    <a href="edit_section.php?id=<?= $section['section_id'] ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="delete_section.php?id=<?= $section['section_id'] ?>" class="btn btn-ghost btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this section?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-users"></i>
        <h5>No Sections Found</h5>
        <p>Create a section to start enrolling students.</p>
        <a href="add_section.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Create Section</a>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
