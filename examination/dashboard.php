<?php
require_once '../config/database.php';
include '../includes/header.php';
include '../includes/navbar.php';

$conn = getConnection();

// Initialize stats with default values
$stats = [
    'students' => 0,
    'faculty' => 0,
    'courses' => 0,
    'programs' => 0,
    'upcoming_exams' => 0,
    'results_published' => 0,
    'pending_results' => 0
];

// Total Students - using correct column name
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'active'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['students'] = $row ? $row['count'] : 0;
}

// Total Faculty
$result = $conn->query("SELECT COUNT(*) as count FROM faculty");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['faculty'] = $row ? $row['count'] : 0;
}

// Total Courses
$result = $conn->query("SELECT COUNT(*) as count FROM courses");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['courses'] = $row ? $row['count'] : 0;
}

// Total Programs
$result = $conn->query("SELECT COUNT(*) as count FROM programs");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['programs'] = $row ? $row['count'] : 0;
}

// Upcoming Exams - using 'date' column instead of 'exam_date'
$result = $conn->query("SELECT COUNT(*) as count FROM exam_schedules WHERE date >= CURDATE()");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['upcoming_exams'] = $row ? $row['count'] : 0;
}

// Published results
$result = $conn->query("SELECT COUNT(*) as count FROM exam_results WHERE status = 'published'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['results_published'] = $row ? $row['count'] : 0;
}

// Draft results awaiting publication
$result = $conn->query("SELECT COUNT(*) as count FROM exam_results WHERE status = 'draft'");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['pending_results'] = $row ? $row['count'] : 0;
}

// Recent Exam Schedules
$recent_schedules = $conn->query("
    SELECT es.*, c.course_name, c.course_code, p.program_name 
    FROM exam_schedules es
    JOIN courses c ON es.course_id = c.course_id
    JOIN programs p ON c.program_id = p.program_id
    WHERE es.date >= CURDATE()
    ORDER BY es.date ASC
    LIMIT 5
");

// If query fails, create empty result set
if (!$recent_schedules) {
    $recent_schedules = new stdClass();
    $recent_schedules->num_rows = 0;
}

// Recent Results
$recent_results = $conn->query("
    SELECT er.*, s.student_id, u.full_name as student_name, 
           c.course_name, c.course_code, es.exam_type
    FROM exam_results er
    JOIN students s ON er.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN exam_schedules es ON er.exam_id = es.exam_id
    JOIN courses c ON es.course_id = c.course_id
    ORDER BY er.result_id DESC
    LIMIT 5
");

if (!$recent_results) {
    $recent_results = new stdClass();
    $recent_results->num_rows = 0;
}

// Top Performing Students
$top_students = $conn->query("
    SELECT s.student_id, u.full_name, 
           AVG(er.marks_obtained / er.total_marks * 100) as avg_percentage,
           COUNT(er.result_id) as exams_taken
    FROM exam_results er
    JOIN students s ON er.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    GROUP BY s.student_id, u.full_name
    HAVING exams_taken >= 2
    ORDER BY avg_percentage DESC
    LIMIT 5
");

if (!$top_students) {
    $top_students = new stdClass();
    $top_students->num_rows = 0;
}

// Exam Type Distribution
$exam_types = $conn->query("
    SELECT exam_type, COUNT(*) as count 
    FROM exam_schedules 
    WHERE date >= CURDATE()
    GROUP BY exam_type
");

if (!$exam_types) {
    $exam_types = new stdClass();
    $exam_types->num_rows = 0;
}

// Get current time for greeting
$current_time = date('H');
$greeting = '';
if ($current_time < 12) {
    $greeting = 'Good Morning';
} elseif ($current_time < 17) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}

// Get program codes for display
$programs_list = $conn->query("SELECT program_code FROM programs LIMIT 4");
$program_codes = [];
if ($programs_list) {
    while($prog = $programs_list->fetch_assoc()) {
        $program_codes[] = $prog['program_code'];
    }
}

$conn->close();
?>

<!-- Main Container with Sidebar -->
<div class="main-container">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="content-area" id="contentArea">
        <!-- Welcome Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2><?php echo $greeting; ?>! 👋</h2>
                <p class="text-muted">Welcome to the Examination Management Dashboard</p>
            </div>
            <div class="d-flex gap-2">
                <a href="schedule/add.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Schedule
                </a>
                <a href="results/add.php" class="btn btn-success">
                    <i class="bi bi-plus-circle"></i> Add Result
                </a>
                <a href="promote/promote.php" class="btn btn-warning">
                    <i class="bi bi-arrow-up-circle"></i> Promote
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card stat-card-primary">
                    <div class="stat-icon"><i class="bi bi-people"></i></div>
                    <p class="stat-label">Total Students</p>
                    <p class="stat-number"><?php echo $stats['students']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-success">
                    <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
                    <p class="stat-label">Faculty Members</p>
                    <p class="stat-number"><?php echo $stats['faculty']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-warning">
                    <div class="stat-icon"><i class="bi bi-book"></i></div>
                    <p class="stat-label">Courses</p>
                    <p class="stat-number"><?php echo $stats['courses']; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card stat-card-info">
                    <div class="stat-icon"><i class="bi bi-calendar-event"></i></div>
                    <p class="stat-label">Upcoming Exams</p>
                    <p class="stat-number"><?php echo $stats['upcoming_exams']; ?></p>
                </div>
            </div>
        </div>

        <!-- Second Row of Stats -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Results Published</h6>
                        <h3 class="card-title"><?php echo $stats['results_published']; ?></h3>
                        <div class="progress">
                            <?php 
                            $total = $stats['results_published'] + $stats['pending_results'];
                            $percentage = $total > 0 ? ($stats['results_published'] / $total) * 100 : 0;
                            ?>
                            <div class="progress-bar bg-success" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <small class="text-muted"><?php echo $stats['pending_results']; ?> pending</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Programs Offered</h6>
                        <h3 class="card-title"><?php echo $stats['programs']; ?></h3>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php foreach($program_codes as $code): ?>
                                <span class="badge bg-primary"><?php echo $code; ?></span>
                            <?php endforeach; ?>
                            <?php if($stats['programs'] > 4): ?>
                                <span class="badge bg-secondary">+<?php echo $stats['programs'] - 4; ?> more</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-subtitle mb-2 text-muted">Exam Type Distribution</h6>
                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($exam_types && $exam_types->num_rows > 0): ?>
                                <?php while($type = $exam_types->fetch_assoc()): ?>
                                    <span class="badge badge-exam-<?php echo $type['exam_type']; ?>">
                                        <?php echo ucfirst($type['exam_type']); ?>: <?php echo $type['count']; ?>
                                    </span>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <span class="text-muted">No exams scheduled</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <!-- Upcoming Exams -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-calendar-event"></i> Upcoming Exams</h5>
                        <a href="schedule/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_schedules && $recent_schedules->num_rows > 0): ?>
                            <div class="timeline">
                                <?php while($schedule = $recent_schedules->fetch_assoc()): ?>
                                    <div class="timeline-item">
                                        <div class="time">
                                            <?php echo date('M d, Y', strtotime($schedule['date'])); ?>
                                            <span class="badge badge-exam-<?php echo $schedule['exam_type']; ?> ms-2">
                                                <?php echo ucfirst($schedule['exam_type']); ?>
                                            </span>
                                        </div>
                                        <div class="title">
                                            <?php echo $schedule['course_code']; ?> - <?php echo $schedule['course_name']; ?>
                                        </div>
                                        <div class="description">
                                            <i class="bi bi-clock"></i> 
                                            <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                            <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                            <br>
                                            <i class="bi bi-geo-alt"></i> <?php echo $schedule['room']; ?>
                                            <br>
                                            <small class="text-muted"><?php echo $schedule['program_name']; ?></small>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-calendar-check" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p class="mt-3 text-muted">No upcoming exams scheduled</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="bi bi-bar-chart"></i> Recent Results</h5>
                        <a href="results/index.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recent_results && $recent_results->num_rows > 0): ?>
                            <div class="list-group">
                                <?php while($result = $recent_results->fetch_assoc()): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1"><?php echo $result['student_name']; ?></h6>
                                                <small class="text-muted">
                                                    <?php echo $result['course_code']; ?> - <?php echo ucfirst($result['exam_type']); ?>
                                                </small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge badge-grade-<?php echo strtolower($result['grade']); ?>">
                                                    <?php echo $result['grade']; ?>
                                                </span>
                                                <br>
                                                <small>
                                                    <?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-file-bar-graph" style="font-size: 3rem; color: #dee2e6;"></i>
                                <p class="mt-3 text-muted">No results recorded yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Students -->
        <?php if ($top_students && $top_students->num_rows > 0): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-trophy"></i> Top Performing Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Student</th>
                                        <th>Student ID</th>
                                        <th>Average Percentage</th>
                                        <th>Exams Taken</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $rank = 1; ?>
                                    <?php while($student = $top_students->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <?php if($rank == 1): ?>
                                                    <span class="badge bg-warning text-dark">🏆 #1</span>
                                                <?php elseif($rank == 2): ?>
                                                    <span class="badge bg-secondary">#2</span>
                                                <?php elseif($rank == 3): ?>
                                                    <span class="badge" style="background: #cd7f32; color: white;">#3</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark">#<?php echo $rank; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $student['full_name']; ?></td>
                                            <td><?php echo $student['student_id']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                        <div class="progress-bar <?php 
                                                            echo $student['avg_percentage'] >= 80 ? 'bg-success' : 
                                                                ($student['avg_percentage'] >= 60 ? 'bg-warning' : 'bg-danger');
                                                        ?>" 
                                                        style="width: <?php echo $student['avg_percentage']; ?>%">
                                                        </div>
                                                    </div>
                                                    <span><?php echo number_format($student['avg_percentage'], 1); ?>%</span>
                                                </div>
                                            </td>
                                            <td><?php echo $student['exams_taken']; ?></td>
                                        </tr>
                                        <?php $rank++; ?>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="bi bi-lightning"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <a href="schedule/add.php" class="btn btn-primary w-100 py-3">
                                    <i class="bi bi-calendar-plus" style="font-size: 1.5rem;"></i><br>
                                    Schedule Exam
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="results/add.php" class="btn btn-success w-100 py-3">
                                    <i class="bi bi-pencil-square" style="font-size: 1.5rem;"></i><br>
                                    Enter Results
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="results/publish.php" class="btn btn-info w-100 py-3 text-white">
                                    <i class="bi bi-cloud-upload" style="font-size: 1.5rem;"></i><br>
                                    Publish Results
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="promote/promote.php" class="btn btn-warning w-100 py-3">
                                    <i class="bi bi-arrow-up-circle" style="font-size: 1.5rem;"></i><br>
                                    Promote Students
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
