
<?php
$page_title = 'Dashboard';
include 'includes/header.php';
include 'includes/sidebar.php';

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
$result = $conn->query("SELECT COUNT(*) as count FROM students WHERE status = 'Active'");
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

// Recent Results - Using full_name from students table
$recent_results = $conn->query("
    SELECT er.*, 
           s.student_id,
           s.full_name as student_name,
           c.course_name, 
           c.course_code, 
           es.exam_type
    FROM exam_results er
    JOIN students s ON er.student_id = s.student_id
    JOIN exam_schedules es ON er.exam_id = es.exam_id
    JOIN courses c ON es.course_id = c.course_id
    ORDER BY er.result_id DESC
    LIMIT 5
");

if (!$recent_results) {
    $recent_results = new stdClass();
    $recent_results->num_rows = 0;
}

// Top Performing Students - Using full_name
$top_students = $conn->query("
    SELECT s.student_id,
           s.full_name,
           AVG(er.marks_obtained / er.total_marks * 100) as avg_percentage,
           COUNT(er.result_id) as exams_taken
    FROM exam_results er
    JOIN students s ON er.student_id = s.student_id
    GROUP BY s.student_id, s.full_name
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

<div class="content-area" id="contentArea">
        <!-- Welcome Section -->
        <div style="background:linear-gradient(135deg,var(--navy) 0%,#1e3a5f 50%,#2563EB 100%);border-radius:var(--radius-lg);padding:28px 32px;margin-bottom:24px;color:#fff;display:flex;align-items:center;justify-content:space-between;">
            <div>
                <div style="font-size:1.4rem;font-weight:700;margin-bottom:4px;"><?php echo $greeting; ?> &#128075;</div>
                <div style="font-size:.88rem;color:rgba(255,255,255,0.7);">Welcome to the Examination Management Dashboard</div>
            </div>
            <div style="text-align:right;">
                <div style="font-size:.78rem;color:rgba(255,255,255,0.6);"><?php echo date('l'); ?></div>
                <div style="font-size:1.1rem;font-weight:600;"><?php echo date('d M Y'); ?></div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stat-row" style="margin-bottom:24px;">
            <div class="stat-card-v2">
                <div class="stat-card-icon" style="background:var(--primary-bg);color:var(--primary);"><i class="bi bi-people"></i></div>
                <div class="stat-card-value"><?php echo $stats['students']; ?></div>
                <div class="stat-card-label">Total Students</div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-card-icon" style="background:var(--success-bg);color:var(--success);"><i class="bi bi-person-badge"></i></div>
                <div class="stat-card-value"><?php echo $stats['faculty']; ?></div>
                <div class="stat-card-label">Faculty Members</div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-card-icon" style="background:var(--warning-bg);color:var(--warning);"><i class="bi bi-book"></i></div>
                <div class="stat-card-value"><?php echo $stats['courses']; ?></div>
                <div class="stat-card-label">Courses</div>
            </div>
            <div class="stat-card-v2">
                <div class="stat-card-icon" style="background:var(--accent-light);color:var(--accent);"><i class="bi bi-calendar-event"></i></div>
                <div class="stat-card-value"><?php echo $stats['upcoming_exams']; ?></div>
                <div class="stat-card-label">Upcoming Exams</div>
            </div>
        </div>

        <!-- Second Row of Stats -->
        <div class="grid-3" style="margin-bottom:24px;">
            <div class="card">
                <div class="card-content">
                    <p class="muted" style="margin-bottom:8px;">Results Published</p>
                    <h3 style="margin-bottom:8px;"><?php echo $stats['results_published']; ?></h3>
                    <?php 
                    $total = $stats['results_published'] + $stats['pending_results'];
                    $percentage = $total > 0 ? ($stats['results_published'] / $total) * 100 : 0;
                    ?>
                    <div style="height:6px;background:var(--border);border-radius:4px;overflow:hidden;margin-bottom:8px;">
                        <div style="height:100%;width:<?= $percentage ?>%;background:var(--success);border-radius:4px;"></div>
                    </div>
                    <span class="muted" style="font-size:12px;"><?php echo $stats['pending_results']; ?> pending</span>
                </div>
            </div>
            <div class="card">
                <div class="card-content">
                    <p class="muted" style="margin-bottom:8px;">Programs Offered</p>
                    <h3 style="margin-bottom:8px;"><?php echo $stats['programs']; ?></h3>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        <?php foreach($program_codes as $code): ?>
                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--accent-light);color:var(--accent);margin:2px;"><?php echo $code; ?></span>
                        <?php endforeach; ?>
                        <?php if($stats['programs'] > 4): ?>
                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--border);color:var(--text-secondary);margin:2px;">+<?php echo $stats['programs'] - 4; ?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-content">
                    <p class="muted" style="margin-bottom:8px;">Exam Type Distribution</p>
                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                        <?php if ($exam_types && $exam_types->num_rows > 0): ?>
                            <?php while($type = $exam_types->fetch_assoc()): ?>
                                <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">
                                    <?php echo ucfirst($type['exam_type']); ?>: <?php echo $type['count']; ?>
                                </span>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <span class="muted">No exams scheduled</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid-2" style="margin-bottom:24px;">
            <!-- Upcoming Exams -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin:0;"><i class="bi bi-calendar-event"></i> Upcoming Exams</h5>
                    <a href="schedule/index.php" class="btn btn-outline" style="padding:4px 12px;font-size:12px;">View All</a>
                </div>
                <div class="card-content">
                    <?php if ($recent_schedules && $recent_schedules->num_rows > 0): ?>
                        <?php $schedule_count = 0; ?>
                        <?php while($schedule = $recent_schedules->fetch_assoc()): ?>
                            <?php $schedule_count++; ?>
                            <div style="padding:12px 0;<?php if($schedule_count < $recent_schedules->num_rows) echo 'border-bottom:1px solid var(--border);'; ?>">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
                                    <span class="muted" style="font-size:12px;">
                                        <?php echo date('M d, Y', strtotime($schedule['date'])); ?>
                                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);margin-left:8px;">
                                            <?php echo ucfirst($schedule['exam_type']); ?>
                                        </span>
                                    </span>
                                </div>
                                <div style="font-weight:600;margin-bottom:4px;">
                                    <?php echo $schedule['course_code']; ?> - <?php echo $schedule['course_name']; ?>
                                </div>
                                <div style="font-size:13px;color:var(--text-secondary);">
                                    <i class="bi bi-clock"></i> 
                                    <?php echo date('h:i A', strtotime($schedule['start_time'])); ?> - 
                                    <?php echo date('h:i A', strtotime($schedule['end_time'])); ?>
                                    <i class="bi bi-geo-alt" style="margin-left:8px;"></i> <?php echo $schedule['room']; ?>
                                </div>
                                <div class="muted" style="font-size:12px;margin-top:4px;"><?php echo $schedule['program_name']; ?></div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-calendar-check"></i></div>
                            <p class="empty-state-text">No upcoming exams scheduled</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="card">
                <div class="card-header">
                    <h5 style="margin:0;"><i class="bi bi-bar-chart"></i> Recent Results</h5>
                    <a href="results/index.php" class="btn btn-outline" style="padding:4px 12px;font-size:12px;">View All</a>
                </div>
                <div class="card-content">
                    <?php if ($recent_results && $recent_results->num_rows > 0): ?>
                        <?php $result_count = 0; ?>
                        <?php while($result = $recent_results->fetch_assoc()): ?>
                            <?php $result_count++; ?>
                            <div style="padding:12px 0;<?php if($result_count < $recent_results->num_rows) echo 'border-bottom:1px solid var(--border);'; ?>">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <div>
                                        <div style="font-weight:600;margin-bottom:2px;"><?php echo $result['student_name']; ?></div>
                                        <span class="muted" style="font-size:12px;">
                                            <?php echo $result['course_code']; ?> - <?php echo ucfirst($result['exam_type']); ?>
                                        </span>
                                    </div>
                                    <div style="text-align:right;">
                                        <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">
                                            <?php echo $result['grade']; ?>
                                        </span>
                                        <div style="font-size:12px;color:var(--text-secondary);margin-top:4px;">
                                            <?php echo $result['marks_obtained']; ?>/<?php echo $result['total_marks']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon"><i class="bi bi-file-bar-graph"></i></div>
                            <p class="empty-state-text">No results recorded yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Top Students -->
        <?php if ($top_students && $top_students->num_rows > 0): ?>
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <h5 style="margin:0;"><i class="bi bi-trophy"></i> Top Performing Students</h5>
            </div>
            <div class="card-content">
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Student Name</th>
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
                                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--warning-bg);color:var(--warning);">&#127942; #1</span>
                                        <?php elseif($rank == 2): ?>
                                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--border);color:var(--text-secondary);">#2</span>
                                        <?php elseif($rank == 3): ?>
                                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:#cd7f32;color:#fff;">#3</span>
                                        <?php else: ?>
                                            <span style="display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:600;background:var(--bg-secondary);color:var(--text-primary);">#<?php echo $rank; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $student['full_name']; ?></td>
                                    <td><?php echo $student['student_id']; ?></td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <div style="flex:1;height:6px;background:var(--border);border-radius:4px;overflow:hidden;">
                                                <div style="height:100%;width:<?php echo $student['avg_percentage']; ?>%;background:<?php 
                                                    echo $student['avg_percentage'] >= 80 ? 'var(--success)' : 
                                                        ($student['avg_percentage'] >= 60 ? 'var(--warning)' : 'var(--error)');
                                                ?>;border-radius:4px;"></div>
                                            </div>
                                            <span style="white-space:nowrap;"><?php echo number_format($student['avg_percentage'], 1); ?>%</span>
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
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="card" style="margin-bottom:24px;">
            <div class="card-header">
                <h5 style="margin:0;"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-content">
                <div class="grid-4">
                    <a href="schedule/add.php" class="btn btn-primary" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;">
                        <i class="bi bi-calendar-plus" style="font-size:1.5rem;"></i>
                        Schedule Exam
                    </a>
                    <a href="results/add.php" class="btn btn-primary" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:var(--success);border-color:var(--success);">
                        <i class="bi bi-pencil-square" style="font-size:1.5rem;"></i>
                        Enter Results
                    </a>
                    <a href="results/publish.php" class="btn btn-primary" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:var(--accent);border-color:var(--accent);">
                        <i class="bi bi-cloud-upload" style="font-size:1.5rem;"></i>
                        Publish Results
                    </a>
                    <a href="promote/promote.php" class="btn btn-primary" style="display:flex;flex-direction:column;align-items:center;gap:8px;padding:20px;background:var(--warning);border-color:var(--warning);">
                        <i class="bi bi-arrow-up-circle" style="font-size:1.5rem;"></i>
                        Promote Students
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>