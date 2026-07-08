<?php
// edit.php - Edit course details with department support

session_start();

// Initialize sample data with departments
if (!isset($_SESSION['semester_courses'])) {
    $_SESSION['semester_courses'] = [
        'CS' => [
            1 => ['Programming Fundamentals', 'ICT', 'Calculus'],
            2 => ['OOP', 'Discrete Structures', 'Pakistan Studies'],
            3 => ['Data Structures', 'Database Systems', 'Software Engineering'],
            4 => ['Operating Systems', 'Computer Networks', 'Web Development'],
            5 => [],
            6 => [],
            7 => [],
            8 => []
        ],
        'SE' => [
            1 => ['Programming Basics', 'Introduction to SE', 'Calculus'],
            2 => ['OOP with Java', 'Data Structures', 'Software Design'],
            3 => ['Web Development', 'Database Systems', 'Software Testing'],
            4 => ['Software Project Management', 'Advanced Programming', 'UI/UX Design'],
            5 => [],
            6 => [],
            7 => [],
            8 => []
        ],
        'AI' => [
            1 => ['Python Programming', 'Discrete Math', 'Calculus'],
            2 => ['Data Science', 'Statistics', 'Linear Algebra'],
            3 => ['Machine Learning', 'Deep Learning', 'NLP'],
            4 => ['Computer Vision', 'AI Ethics', 'Robotics'],
            5 => [],
            6 => [],
            7 => [],
            8 => []
        ],
        'DS' => [
            1 => ['Mathematics', 'Statistics', 'Python'],
            2 => ['Data Analysis', 'Database Systems', 'Big Data'],
            3 => ['Machine Learning', 'Data Mining', 'Visualization'],
            4 => ['Deep Learning', 'Business Intelligence', 'Cloud Computing'],
            5 => [],
            6 => [],
            7 => [],
            8 => []
        ]
    ];
}

// Helper Functions
function getDepartments() {
    return array_keys($_SESSION['semester_courses']);
}

function getCoursesForSemester($department, $semester) {
    return $_SESSION['semester_courses'][$department][$semester] ?? [];
}

function updateCourseName($department, $semester, $courseIndex, $newName) {
    $newName = trim($newName);
    if (empty($newName)) return false;
    
    if (!isset($_SESSION['semester_courses'][$department])) return false;
    if (!isset($_SESSION['semester_courses'][$department][$semester])) return false;
    if (!isset($_SESSION['semester_courses'][$department][$semester][$courseIndex])) return false;
    
    if (in_array($newName, $_SESSION['semester_courses'][$department][$semester])) {
        return false;
    }
    
    $_SESSION['semester_courses'][$department][$semester][$courseIndex] = $newName;
    return true;
}

function moveCourseToSemester($department, $fromSemester, $courseIndex, $toSemester) {
    if ($fromSemester == $toSemester) return false;
    
    if (!isset($_SESSION['semester_courses'][$department])) return false;
    if (!isset($_SESSION['semester_courses'][$department][$fromSemester])) return false;
    if (!isset($_SESSION['semester_courses'][$department][$fromSemester][$courseIndex])) return false;
    
    $courseName = $_SESSION['semester_courses'][$department][$fromSemester][$courseIndex];
    
    if (isset($_SESSION['semester_courses'][$department][$toSemester]) && 
        in_array($courseName, $_SESSION['semester_courses'][$department][$toSemester])) {
        return false;
    }
    
    unset($_SESSION['semester_courses'][$department][$fromSemester][$courseIndex]);
    $_SESSION['semester_courses'][$department][$fromSemester] = array_values($_SESSION['semester_courses'][$department][$fromSemester]);
    
    if (!isset($_SESSION['semester_courses'][$department][$toSemester])) {
        $_SESSION['semester_courses'][$department][$toSemester] = [];
    }
    $_SESSION['semester_courses'][$department][$toSemester][] = $courseName;
    
    return true;
}

function deleteCourseFromAll($department, $courseName) {
    $deleted = 0;
    if (isset($_SESSION['semester_courses'][$department])) {
        foreach ($_SESSION['semester_courses'][$department] as &$courses) {
            $index = array_search($courseName, $courses);
            if ($index !== false) {
                unset($courses[$index]);
                $courses = array_values($courses);
                $deleted++;
            }
        }
    }
    return $deleted;
}

function getAllCourses($department) {
    $allCourses = [];
    if (isset($_SESSION['semester_courses'][$department])) {
        foreach ($_SESSION['semester_courses'][$department] as $courses) {
            foreach ($courses as $course) {
                if (!in_array($course, $allCourses)) {
                    $allCourses[] = $course;
                }
            }
        }
    }
    sort($allCourses);
    return $allCourses;
}

// Handle form submissions
$message = '';
$messageType = '';
$selectedDepartment = isset($_GET['department']) ? $_GET['department'] : 'CS';
$selectedSemester = isset($_GET['semester']) ? (int)$_GET['semester'] : 1;

if (!isset($_SESSION['semester_courses'][$selectedDepartment])) {
    $selectedDepartment = 'CS';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $department = isset($_POST['department']) ? $_POST['department'] : 'CS';
    $semester = isset($_POST['semester']) ? (int)$_POST['semester'] : 1;
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_course') {
        $courseIndex = isset($_POST['course_index']) ? (int)$_POST['course_index'] : -1;
        $newName = $_POST['new_name'] ?? '';
        
        if (updateCourseName($department, $semester, $courseIndex, $newName)) {
            $message = "✅ Course updated successfully!";
            $messageType = 'success';
        } else {
            $message = "❌ Failed to update course. Name may be empty or duplicate.";
            $messageType = 'error';
        }
        $selectedDepartment = $department;
        $selectedSemester = $semester;
    }
    elseif ($action === 'move_course') {
        $courseIndex = isset($_POST['course_index']) ? (int)$_POST['course_index'] : -1;
        $toSemester = isset($_POST['to_semester']) ? (int)$_POST['to_semester'] : 1;
        $courseName = $_SESSION['semester_courses'][$department][$semester][$courseIndex] ?? '';
        
        if (moveCourseToSemester($department, $semester, $courseIndex, $toSemester)) {
            $message = "✅ Course '$courseName' moved to Semester $toSemester!";
            $messageType = 'success';
        } else {
            $message = "❌ Failed to move course. It may already exist in target semester.";
            $messageType = 'error';
        }
        $selectedDepartment = $department;
        $selectedSemester = $semester;
    }
    elseif ($action === 'delete_from_all') {
        $courseName = $_POST['course_name'] ?? '';
        $deleted = deleteCourseFromAll($department, $courseName);
        if ($deleted > 0) {
            $message = "✅ Course '$courseName' deleted from all semesters ($deleted occurrences).";
            $messageType = 'success';
        } else {
            $message = "❌ Course not found.";
            $messageType = 'error';
        }
        $selectedDepartment = $department;
        $selectedSemester = $semester;
    }
}

$courses = getCoursesForSemester($selectedDepartment, $selectedSemester);
$allCourses = getAllCourses($selectedDepartment);
$departments = getDepartments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Courses with Departments</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f4f8;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2b6cb0 100%);
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 32px;
        }
        .header p {
            opacity: 0.9;
            margin-top: 5px;
        }
        .main-content {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }
        .filters {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            background: #f7fafc;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            border: 1px solid #e2e8f0;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-weight: 600;
            color: #2d3748;
            font-size: 0.9rem;
        }
        .filter-group select {
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            font-size: 1rem;
            background: white;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-primary:hover { background: #1a4b7a; }
        .btn-success { background: #38a169; color: white; }
        .btn-success:hover { background: #2d7a4e; }
        .btn-danger { background: #e53e3e; color: white; }
        .btn-danger:hover { background: #b83232; }
        .btn-warning { background: #dd6b20; color: white; }
        .btn-warning:hover { background: #c05621; }
        .btn-small { padding: 5px 12px; font-size: 0.85rem; }
        
        .message {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .message.success {
            background: #f0fff4;
            color: #22543d;
            border: 1px solid #c6f6d5;
        }
        .message.error {
            background: #fff5f5;
            color: #9b2c2c;
            border: 1px solid #feb2b2;
        }
        
        .department-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-left: 10px;
        }
        .badge-cs { background: #bee3f8; color: #2a69ac; }
        .badge-se { background: #c6f6d5; color: #276749; }
        .badge-ai { background: #fefcbf; color: #975a16; }
        .badge-ds { background: #e9d8fd; color: #6b46c1; }
        
        .course-card {
            background: #f9fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 15px 20px;
            margin: 10px 0;
            transition: all 0.2s;
        }
        .course-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        .course-card .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .course-card .course-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a202c;
        }
        .course-card .course-number {
            color: #718096;
            font-size: 0.9rem;
        }
        .course-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        .course-actions form {
            display: inline-flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
        }
        .course-actions input[type="text"] {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #cbd5e0;
            font-size: 0.9rem;
            width: 150px;
        }
        .course-actions select {
            padding: 6px 10px;
            border-radius: 4px;
            border: 1px solid #cbd5e0;
            font-size: 0.9rem;
            background: white;
        }
        .empty-message {
            padding: 40px;
            text-align: center;
            color: #718096;
        }
        .empty-message .icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .delete-all-section {
            margin-top: 30px;
            padding: 20px;
            background: #fff5f5;
            border-radius: 8px;
            border: 1px solid #feb2b2;
        }
        .delete-all-section h3 {
            color: #9b2c2c;
            margin-bottom: 10px;
        }
        .delete-all-section form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .delete-all-section select {
            padding: 10px 15px;
            border-radius: 6px;
            border: 1px solid #cbd5e0;
            flex: 1;
            min-width: 200px;
        }
        
        .navigation {
            margin-top: 30px;
            padding-top: 25px;
            border-top: 2px solid #edf2f7;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .navigation a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 6px;
            background: #edf2f7;
            color: #2d3748;
            font-weight: 500;
            transition: all 0.2s;
        }
        .navigation a:hover {
            background: #e2e8f0;
        }
        .navigation a.active {
            background: #2b6cb0;
            color: white;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .stat-box {
            background: #f7fafc;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            text-align: center;
        }
        .stat-box .number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2b6cb0;
        }
        .stat-box .label {
            color: #718096;
            font-size: 0.85rem;
            margin-top: 5px;
        }
        .department-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .department-tab {
            padding: 8px 16px;
            background: #edf2f7;
            border-radius: 6px;
            text-decoration: none;
            color: #2d3748;
            font-weight: 500;
            transition: all 0.2s;
        }
        .department-tab:hover {
            background: #e2e8f0;
        }
        .department-tab.active {
            background: #2b6cb0;
            color: white;
        }
        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            .course-actions form {
                flex-direction: column;
                align-items: stretch;
            }
            .course-actions input[type="text"] {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✏️ Edit Semester Courses</h1>
            <p>Update course names, move courses between semesters, or delete them</p>
        </div>
        
        <div class="main-content">
            <!-- Department Tabs -->
            <div class="department-tabs">
                <?php foreach ($departments as $dept): ?>
                    <a href="?department=<?= $dept ?>&semester=<?= $selectedSemester ?>" 
                       class="department-tab <?= $selectedDepartment == $dept ? 'active' : '' ?>">
                        <?= htmlspecialchars($dept) ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <form method="GET" action="">
                    <div class="filter-group">
                        <label for="department">Department</label>
                        <select name="department" id="department" onchange="this.form.submit()">
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept ?>" <?= $selectedDepartment == $dept ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dept) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="semester">Semester</label>
                        <select name="semester" id="semester" onchange="this.form.submit()">
                            <?php for ($i = 1; $i <= 8; $i++): ?>
                                <option value="<?= $i ?>" <?= $selectedSemester == $i ? 'selected' : '' ?>>
                                    Semester <?= $i ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="message <?= $messageType ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <!-- Statistics -->
            <div class="stats">
                <div class="stat-box">
                    <div class="number"><?= count($courses) ?></div>
                    <div class="label">Courses in Semester <?= $selectedSemester ?></div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count($allCourses) ?></div>
                    <div class="label">Total Unique Courses</div>
                </div>
            </div>
            
            <!-- Course List -->
            <h2 style="margin-bottom: 15px; color: #2d3748;">
                Courses in <?= $selectedDepartment ?> - Semester <?= $selectedSemester ?>
                <span class="department-badge badge-<?= strtolower($selectedDepartment) ?>">
                    <?= $selectedDepartment ?>
                </span>
            </h2>
            
            <?php if (empty($courses)): ?>
                <div class="empty-message">
                    <div class="icon">📭</div>
                    <p>No courses in <?= $selectedDepartment ?> - Semester <?= $selectedSemester ?></p>
                </div>
            <?php else: ?>
                <?php foreach ($courses as $index => $courseName): ?>
                    <div class="course-card">
                        <div class="course-header">
                            <div>
                                <span class="course-number">#<?= $index + 1 ?></span>
                                <span class="course-name"><?= htmlspecialchars($courseName) ?></span>
                            </div>
                        </div>
                        
                        <div class="course-actions">
                            <!-- Update Course Name -->
                            <form method="POST" action="">
                                <input type="hidden" name="department" value="<?= $selectedDepartment ?>">
                                <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
                                <input type="hidden" name="action" value="update_course">
                                <input type="hidden" name="course_index" value="<?= $index ?>">
                                <input type="text" name="new_name" value="<?= htmlspecialchars($courseName) ?>" required>
                                <button type="submit" class="btn btn-primary btn-small">✏️ Update</button>
                            </form>
                            
                            <!-- Move Course -->
                            <form method="POST" action="">
                                <input type="hidden" name="department" value="<?= $selectedDepartment ?>">
                                <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
                                <input type="hidden" name="action" value="move_course">
                                <input type="hidden" name="course_index" value="<?= $index ?>">
                                <select name="to_semester">
                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <?php if ($i != $selectedSemester): ?>
                                            <option value="<?= $i ?>">Semester <?= $i ?></option>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                </select>
                                <button type="submit" class="btn btn-warning btn-small">📤 Move</button>
                            </form>
                            
                            <!-- Delete from All -->
                            <form method="POST" action="">
                                <input type="hidden" name="department" value="<?= $selectedDepartment ?>">
                                <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
                                <input type="hidden" name="action" value="delete_from_all">
                                <input type="hidden" name="course_name" value="<?= htmlspecialchars($courseName) ?>">
                                <button type="submit" class="btn btn-danger btn-small" 
                                        onclick="return confirm('Are you sure you want to delete \'<?= htmlspecialchars($courseName) ?>\' from ALL semesters?')">
                                    🗑️ Delete from All
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- Delete Course from All Semesters -->
            <div class="delete-all-section">
                <h3>⚠️ Delete Course from All Semesters</h3>
                <p style="color: #718096; margin-bottom: 10px;">Select a course to remove it from all semesters permanently.</p>
                <form method="POST" action="">
                    <input type="hidden" name="department" value="<?= $selectedDepartment ?>">
                    <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
                    <input type="hidden" name="action" value="delete_from_all">
                    <select name="course_name" required>
                        <option value="">Select a course...</option>
                        <?php foreach ($allCourses as $course): ?>
                            <option value="<?= htmlspecialchars($course) ?>"><?= htmlspecialchars($course) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-danger" 
                            onclick="return confirm('Are you sure you want to delete this course from ALL semesters?')">
                        🗑️ Delete from All Semesters
                    </button>
                </form>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php">📋 View Courses</a>
                <a href="assign.php">📌 Bulk Assign</a>
                <a href="edit.php" class="active">✏️ Edit Courses</a>
            </div>
        </div>
    </div>
</body>
</html>