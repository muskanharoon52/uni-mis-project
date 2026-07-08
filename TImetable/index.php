<?php
session_start();
include '../includes/db.php';
include '../includes/auth.php';

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
            c.course_title,
            c.credit_hours,
            tch.teacher_name,
            tch.designation,
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

// Check if query was successful
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get counts for stats
$total_classes = mysqli_num_rows($result);

// Get unique courses count
$course_count_query = "SELECT COUNT(DISTINCT course_id) as total FROM timetable";
$course_count_result = mysqli_query($conn, $course_count_query);
$unique_courses = mysqli_fetch_assoc($course_count_result);

// Get unique teachers count
$teacher_count_query = "SELECT COUNT(DISTINCT teacher_id) as total FROM timetable";
$teacher_count_result = mysqli_query($conn, $teacher_count_query);
$unique_teachers = mysqli_fetch_assoc($teacher_count_result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weekly Class Schedule</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Main Layout */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
        }
        
        .content-wrapper {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s ease;
        }
        
        @media (max-width: 992px) {
            .content-wrapper {
                margin-left: 0;
                padding: 15px;
                padding-top: 70px;
            }
        }
        
        .main-container {
            max-width: 100%;
            margin: 0 auto;
        }
        
        .header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .header-card h1 {
            font-weight: 700;
            font-size: 28px;
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
        }
        
        .btn-action {
            border-radius: 20px;
            padding: 5px 12px;
            margin: 0 2px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 70px;
            color: #ddd;
            margin-bottom: 20px;
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
        
        .filter-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .filter-section select,
        .filter-section input {
            border-radius: 20px;
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
        
        .alert {
            border-radius: 10px;
        }
        
        @media (max-width: 768px) {
            .header-card h1 {
                font-size: 22px;
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
</head>
<body>

<!-- Include Sidebar -->
<?php include '../includes/sidebar.php'; ?>

<!-- Main Content -->
<div class="content-wrapper" id="contentWrapper">
    <div class="main-container">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?> alert-dismissible fade show">
                <?php 
                    echo $_SESSION['message']; 
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="header-card">
            <div class="row align-items-center">
                <div class="col-md-7">
                    <h1><i class="fas fa-calendar-alt me-3"></i>Weekly Timetable</h1>
                    <p class="mb-0">Manage and view your class schedule</p>
                    <div class="stats-card">
                        <span><i class="fas fa-clock"></i> Total Classes: <?php echo $total_classes; ?></span>
                        <span><i class="fas fa-book"></i> Courses: <?php echo $unique_courses['total'] ?? 0; ?></span>
                        <span><i class="fas fa-user-tie"></i> Teachers: <?php echo $unique_teachers['total'] ?? 0; ?></span>
                    </div>
                </div>
                <div class="col-md-5 text-end">
                    <a href="add.php" class="btn-add">
                        <i class="fas fa-plus-circle me-2"></i>Add New Class
                    </a>
                </div>
            </div>
        </div>

        <div class="filter-section">
            <div class="row">
                <div class="col-md-3">
                    <select id="filterDay" class="form-control form-control-sm">
                        <option value="">Filter by Day</option>
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
                    <input type="text" id="filterTeacher" class="form-control form-control-sm" placeholder="Filter by Teacher">
                </div>
                <div class="col-md-3">
                    <input type="text" id="filterCourse" class="form-control form-control-sm" placeholder="Filter by Course">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-sm btn-primary w-100" onclick="applyFilters()">
                        <i class="fas fa-filter me-1"></i>Apply Filters
                    </button>
                </div>
            </div>
        </div>

        <div class="table-card">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="timetableTable">
                        <thead>
                            <tr>
                                <th style="width: 8%">Day</th>
                                <th style="width: 12%">Time</th>
                                <th style="width: 20%">Course</th>
                                <th style="width: 15%">Teacher</th>
                                <th style="width: 8%">Room</th>
                                <th style="width: 6%">Section</th>
                                <th style="width: 10%">Semester</th>
                                <th style="width: 10%">Department</th>
                                <th style="width: 11%">Actions</th>
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
                                            <i class="far fa-clock me-1"></i>
                                            <?php echo date('g:i A', strtotime($row['start_time'])); ?><br>
                                            <small><?php echo date('g:i A', strtotime($row['end_time'])); ?></small>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['course_title']); ?></strong>
                                        <br><span class="course-code"><?php echo htmlspecialchars($row['course_code']); ?></span>
                                        <?php if($row['credit_hours']): ?>
                                            <br><small class="text-muted"><?php echo $row['credit_hours']; ?> Credit Hours</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-user-tie me-2 text-success"></i>
                                        <?php echo htmlspecialchars($row['teacher_name']); ?>
                                        <?php if($row['designation']): ?>
                                            <br><small class="designation-text"><?php echo htmlspecialchars($row['designation']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <i class="fas fa-door-open me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($row['room_no']); ?>
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
                                               class="btn btn-sm btn-outline-primary btn-action" 
                                               title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="view.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-info btn-action" 
                                               title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                               class="btn btn-sm btn-outline-danger btn-action"
                                               title="Delete"
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
                    <h4>No Classes Added Yet</h4>
                    <p class="text-muted">Start by adding your first class schedule.</p>
                    <a href="add.php" class="btn btn-primary mt-3">
                        <i class="fas fa-plus-circle me-2"></i>Add Class
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function applyFilters() {
        var day = document.getElementById('filterDay').value;
        var teacher = document.getElementById('filterTeacher').value.toLowerCase();
        var course = document.getElementById('filterCourse').value.toLowerCase();
        
        var rows = document.querySelectorAll('#timetableTable tbody tr');
        
        rows.forEach(function(row) {
            var show = true;
            
            if (day) {
                var dayCell = row.cells[0].textContent.trim();
                if (dayCell !== day) show = false;
            }
            
            if (teacher && show) {
                var teacherCell = row.cells[3].textContent.toLowerCase();
                if (!teacherCell.includes(teacher)) show = false;
            }
            
            if (course && show) {
                var courseCell = row.cells[2].textContent.toLowerCase();
                if (!courseCell.includes(course)) show = false;
            }
            
            row.style.display = show ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(function() {
            var alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                var bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    });
</script>

</body>
</html>