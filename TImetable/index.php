<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$conn = getConnection();

// Fetch all timetable entries with joins
$sql = "SELECT 
            t.id,
            t.day_of_week,
            t.start_time,
            t.end_time,
            t.room_no,
            t.section,
            c.course_id,
            c.course_code,
            c.course_name as course_title,
            c.credit_hours,
            tch.teacher_name,
            tch.specialization as designation,
            tch.email as teacher_email,
            s.semester_name,
            ses.session_name,
            d.department_name,
            p.program_name
        FROM timetable t
        LEFT JOIN courses c ON t.course_id = c.course_id
        LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id
        LEFT JOIN semesters s ON t.semester_id = s.semester_id
        LEFT JOIN sessions ses ON t.session_id = ses.session_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN programs p ON c.program_id = p.program_id
        ORDER BY FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), t.start_time";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get counts for stats
$total_classes = mysqli_num_rows($result);

$course_count_query = "SELECT COUNT(DISTINCT course_id) as total FROM timetable";
$course_count_result = mysqli_query($conn, $course_count_query);
$unique_courses = mysqli_fetch_assoc($course_count_result);

$teacher_count_query = "SELECT COUNT(DISTINCT teacher_id) as total FROM timetable";
$teacher_count_result = mysqli_query($conn, $teacher_count_query);
$unique_teachers = mysqli_fetch_assoc($teacher_count_result);

$semester_count_query = "SELECT COUNT(DISTINCT semester_id) as total FROM timetable";
$semester_count_result = mysqli_query($conn, $semester_count_query);
$unique_semesters = mysqli_fetch_assoc($semester_count_result);

// ============================================
// HEADER INCLUDE
// ============================================
require_once __DIR__ . '/../includes/header.php';
$page_title = 'Timetable Management';
include __DIR__ . '/../includes/sidebar.php';
?>

<style>
    .timetable-content {
        margin-left: 250px;
        padding: 20px;
        min-height: 100vh;
        background: #f5f6fa;
    }
    
    .header-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .header-card h4 {
        font-weight: 700;
    }
    
    .stats-card {
        background: rgba(255,255,255,0.1);
        padding: 10px 20px;
        border-radius: 10px;
        margin-top: 10px;
        display: inline-block;
    }
    
    .stats-card span {
        margin-right: 25px;
    }
    
    .stats-card i {
        margin-right: 5px;
    }
    
    .btn-add {
        background: white;
        color: #667eea;
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }
    
    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        color: #667eea;
        text-decoration: none;
    }
    
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .table-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    }
    
    .table thead th {
        background: #667eea;
        color: white;
        text-align: center;
        vertical-align: middle;
        padding: 12px;
        font-size: 0.85rem;
        white-space: nowrap;
    }
    
    .table tbody td {
        text-align: center;
        vertical-align: middle;
        padding: 10px;
        font-size: 0.9rem;
    }
    
    .table tbody tr:hover {
        background: #f8f9ff;
    }
    
    .day-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
    }
    .day-monday { background: #e3f2fd; color: #0d47a1; }
    .day-tuesday { background: #f3e5f5; color: #4a148c; }
    .day-wednesday { background: #e8f5e9; color: #1b5e20; }
    .day-thursday { background: #fff3e0; color: #e65100; }
    .day-friday { background: #fce4ec; color: #880e4f; }
    .day-saturday { background: #e0f7fa; color: #006064; }
    .day-sunday { background: #f1f8e9; color: #33691e; }
    
    .time-badge {
        background: #f8f9fa;
        padding: 5px 12px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 0.8rem;
        display: inline-block;
    }
    
    .section-badge {
        background: #ffd700;
        color: #333;
        padding: 3px 12px;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.8rem;
    }
    
    .course-code {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .department-badge {
        font-size: 0.7rem;
        background: #e9ecef;
        padding: 2px 10px;
        border-radius: 10px;
    }
    
    .designation-text {
        font-size: 0.7rem;
        color: #6c757d;
    }
    
    .btn-action {
        border-radius: 20px;
        padding: 5px 12px;
        margin: 0 2px;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #95a5a6;
    }
    
    .empty-state i {
        font-size: 70px;
        margin-bottom: 20px;
        opacity: 0.5;
    }
    
    @media (max-width: 768px) {
        .timetable-content {
            margin-left: 0;
            padding: 15px;
        }
        
        .stats-card span {
            display: block;
            margin-right: 0;
            margin-bottom: 5px;
        }
        
        .btn-add {
            display: block;
            text-align: center;
            margin-top: 10px;
        }
        
        .table thead th {
            font-size: 0.7rem;
            padding: 8px 5px;
        }
        
        .table tbody td {
            font-size: 0.8rem;
            padding: 8px 5px;
        }
    }
</style>

<div class="timetable-content">
    <div class="container-fluid">
        
        <!-- Header -->
        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h4><i class="fas fa-calendar-alt me-3"></i>Weekly Timetable</h4>
                    <p class="mb-0">Manage and view your class schedule</p>
                    <div class="stats-card">
                        <span><i class="fas fa-clock"></i> Classes: <?php echo $total_classes; ?></span>
                        <span><i class="fas fa-book"></i> Courses: <?php echo $unique_courses['total'] ?? 0; ?></span>
                        <span><i class="fas fa-user-tie"></i> Teachers: <?php echo $unique_teachers['total'] ?? 0; ?></span>
                        <span><i class="fas fa-graduation-cap"></i> Semesters: <?php echo $unique_semesters['total'] ?? 0; ?></span>
                    </div>
                </div>
                <div class="col-md-5 text-end">
                    <a href="add.php" class="btn-add">
                        <i class="fas fa-plus-circle me-2"></i>Add New Class
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row align-items-end">
                <div class="col-md-3">
                    <label class="form-label small">Day</label>
                    <select id="filterDay" class="form-select form-select-sm">
                        <option value="">All Days</option>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Teacher</label>
                    <input type="text" id="filterTeacher" class="form-control form-control-sm" placeholder="Search teacher...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Course</label>
                    <input type="text" id="filterCourse" class="form-control form-control-sm" placeholder="Search course...">
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary btn-sm w-100" onclick="applyFilters()">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="table-card">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="timetableTable">
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
                                    <td>
                                        <span class="day-badge day-<?php echo strtolower($row['day_of_week']); ?>">
                                            <?php echo $row['day_of_week']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="time-badge">
                                            <?php echo date('g:i A', strtotime($row['start_time'])); ?>
                                            <br>
                                            <small><?php echo date('g:i A', strtotime($row['end_time'])); ?></small>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['course_title']); ?></strong>
                                        <br><span class="course-code"><?php echo htmlspecialchars($row['course_code']); ?></span>
                                        <?php if($row['credit_hours']): ?>
                                            <br><small class="text-muted"><?php echo $row['credit_hours']; ?> Credits</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                                        <?php if($row['designation']): ?>
                                            <br><small class="designation-text"><?php echo htmlspecialchars($row['designation']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['room_no']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="section-badge"><?php echo htmlspecialchars($row['section']); ?></span>
                                    </td>
                                    <td>
                                        <small><?php echo htmlspecialchars($row['semester_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="department-badge">
                                            <?php echo htmlspecialchars($row['department_name']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="edit.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary btn-action" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-info btn-action" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger btn-action" title="Delete"
                                               onclick="return confirm('Are you sure you want to delete this class?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-plus"></i>
                    <h5>No Classes Added Yet</h5>
                    <p class="text-muted">Start by adding your first class schedule.</p>
                    <a href="add.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus-circle me-2"></i>Add Class
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<script>
    function applyFilters() {
        var day = document.getElementById('filterDay').value;
        var teacher = document.getElementById('filterTeacher').value.toLowerCase();
        var course = document.getElementById('filterCourse').value.toLowerCase();
        var rows = document.querySelectorAll('#timetableTable tbody tr');
        var visibleCount = 0;
        
        rows.forEach(function(row) {
            var show = true;
            if (day && row.cells[0].textContent.trim() !== day) show = false;
            if (teacher && show && !row.cells[3].textContent.toLowerCase().includes(teacher)) show = false;
            if (course && show && !row.cells[2].textContent.toLowerCase().includes(course)) show = false;
            row.style.display = show ? '' : 'none';
            if (show) visibleCount++;
        });
    }
    
    function resetFilters() {
        document.getElementById('filterDay').value = '';
        document.getElementById('filterTeacher').value = '';
        document.getElementById('filterCourse').value = '';
        applyFilters();
    }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>