<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/../modules/sso/includes/auth.php';
requireLogin();

$conn = getConnection();

if (!function_exists('columnExists')) {
    function columnExists($conn, $table, $column) {
        try { $query = "SHOW COLUMNS FROM $table LIKE '$column'"; $result = mysqli_query($conn, $query); return ($result && mysqli_num_rows($result) > 0); }
        catch (Exception $e) { return false; }
    }
}

$hasTeacherSpecialization = columnExists($conn, 'teachers', 'specialization');
$hasTeacherDesignation = columnExists($conn, 'teachers', 'designation');
$hasTeacherEmail = columnExists($conn, 'teachers', 'email');

$selectFields = "t.id, t.day_of_week, t.start_time, t.end_time, t.room_no, t.section, c.course_id, c.course_code, c.course_name as course_title, c.credit_hours, tch.teacher_name";
if ($hasTeacherSpecialization) { $selectFields .= ", tch.specialization as designation"; } elseif ($hasTeacherDesignation) { $selectFields .= ", tch.designation as designation"; } else { $selectFields .= ", NULL as designation"; }
if ($hasTeacherEmail) { $selectFields .= ", tch.email as teacher_email"; } else { $selectFields .= ", NULL as teacher_email"; }
$selectFields .= ", s.semester_name, ses.session_name, d.department_name, p.program_name";

$sql = "SELECT $selectFields FROM timetable t LEFT JOIN courses c ON t.course_id = c.course_id LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id LEFT JOIN semesters s ON t.semester_id = s.semester_id LEFT JOIN sessions ses ON t.session_id = ses.session_id LEFT JOIN departments d ON c.department_id = d.department_id LEFT JOIN programs p ON c.program_id = p.program_id ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), t.start_time";
$result = mysqli_query($conn, $sql);
if (!$result) { die("Query failed: " . mysqli_error($conn)); }

$total_classes = mysqli_num_rows($result);
$unique_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT course_id) as total FROM timetable"));
$unique_teachers = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT teacher_id) as total FROM timetable"));
$unique_semesters = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT semester_id) as total FROM timetable"));

require_once __DIR__ . '/../includes/header.php';
$page_title = 'Timetable Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<div class="greeting-card" style="margin-bottom:20px;">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;">
        <div>
            <h4 style="margin-bottom:4px;"><i class="fas fa-calendar-alt"></i> Weekly Timetable</h4>
            <p style="opacity:.7;margin-bottom:12px;">Manage and view your class schedule</p>
            <div style="display:flex;gap:16px;flex-wrap:wrap;">
                <span style="font-size:.82rem;"><i class="fas fa-clock"></i> Classes: <?= $total_classes ?></span>
                <span style="font-size:.82rem;"><i class="fas fa-book"></i> Courses: <?= $unique_courses['total'] ?? 0 ?></span>
                <span style="font-size:.82rem;"><i class="fas fa-user-tie"></i> Teachers: <?= $unique_teachers['total'] ?? 0 ?></span>
                <span style="font-size:.82rem;"><i class="fas fa-graduation-cap"></i> Semesters: <?= $unique_semesters['total'] ?? 0 ?></span>
            </div>
        </div>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add New Class</a>
    </div>
</div>

<?php if (isset($_GET['success'])): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($_GET['success']) ?></div><?php endif; ?>
<?php if (isset($_GET['error'])): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?></div><?php endif; ?>

<div class="filter-bar">
    <select id="filterDay" style="min-width:140px;">
        <option value="">All Days</option>
        <option value="Monday">Monday</option>
        <option value="Tuesday">Tuesday</option>
        <option value="Wednesday">Wednesday</option>
        <option value="Thursday">Thursday</option>
        <option value="Friday">Friday</option>
        <option value="Saturday">Saturday</option>
        <option value="Sunday">Sunday</option>
    </select>
    <input type="text" id="filterTeacher" placeholder="Search teacher..." style="flex:1;min-width:160px;">
    <input type="text" id="filterCourse" placeholder="Search course..." style="flex:1;min-width:160px;">
    <button class="btn btn-primary" onclick="applyFilters()"><i class="fas fa-filter"></i> Filter</button>
    <button class="btn btn-outline" onclick="resetFilters()"><i class="fas fa-times"></i> Reset</button>
</div>

<?php if ($result && mysqli_num_rows($result) > 0): ?>
    <div class="card">
        <div style="padding:0;">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Course</th>
                            <th>Teacher</th>
                            <th>Room</th>
                            <th>Section</th>
                            <th>Semester</th>
                            <th>Department</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><span class="status-badge Active"><?= $row['day_of_week'] ?></span></td>
                                <td><strong><?= date('g:i A', strtotime($row['start_time'])) ?></strong><br><small style="color:var(--muted);"><?= date('g:i A', strtotime($row['end_time'])) ?></small></td>
                                <td><strong><?= htmlspecialchars($row['course_title']) ?></strong><br><small style="color:var(--accent);"><?= htmlspecialchars($row['course_code']) ?></small><?php if($row['credit_hours']): ?><br><small style="color:var(--muted);"><?= $row['credit_hours'] ?> Credits</small><?php endif; ?></td>
                                <td><?= htmlspecialchars($row['teacher_name']) ?><?php if(!empty($row['designation']) && $row['designation'] != 'N/A'): ?><br><small style="color:var(--muted);"><?= htmlspecialchars($row['designation']) ?></small><?php endif; ?><?php if(!empty($row['teacher_email']) && $row['teacher_email'] != 'N/A'): ?><br><small style="color:var(--muted);"><?= htmlspecialchars($row['teacher_email']) ?></small><?php endif; ?></td>
                                <td><strong><?= htmlspecialchars($row['room_no']) ?></strong></td>
                                <td><span class="status-badge Inactive" style="background:var(--warning-bg);color:#92400e;border-color:var(--warning-border);"><?= htmlspecialchars($row['section']) ?></span></td>
                                <td><?= htmlspecialchars($row['semester_name']) ?></td>
                                <td><?= htmlspecialchars($row['department_name']) ?></td>
                                <td style="display:flex;gap:4px;">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                    <a href="view.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="delete.php?id=<?= $row['id'] ?>" class="btn btn-ghost btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this class?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-calendar-plus"></i>
        <h5>No Classes Added Yet</h5>
        <p>Start by adding your first class schedule.</p>
        <a href="add.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Add Class</a>
    </div>
<?php endif; ?>

<script>
function applyFilters() { var day = document.getElementById('filterDay').value; var teacher = document.getElementById('filterTeacher').value.toLowerCase(); var course = document.getElementById('filterCourse').value.toLowerCase(); var rows = document.querySelectorAll('#timetableTable tbody tr'); rows.forEach(function(row) { var show = true; if (day && row.cells[0].textContent.trim() !== day) show = false; if (teacher && show && !row.cells[3].textContent.toLowerCase().includes(teacher)) show = false; if (course && show && !row.cells[2].textContent.toLowerCase().includes(course)) show = false; row.style.display = show ? '' : 'none'; }); }
function resetFilters() { document.getElementById('filterDay').value = ''; document.getElementById('filterTeacher').value = ''; document.getElementById('filterCourse').value = ''; applyFilters(); }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
