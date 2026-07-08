<?php
// assign.php - Bulk assign multiple courses to a semester with department support

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

function addMultipleCourses($department, $semester, $courses) {
    $added = 0;
    $duplicates = 0;
    
    if (!isset($_SESSION['semester_courses'][$department])) {
        $_SESSION['semester_courses'][$department] = [];
    }
    if (!isset($_SESSION['semester_courses'][$department][$semester])) {
        $_SESSION['semester_courses'][$department][$semester] = [];
    }
    
    foreach ($courses as $course) {
        $course = trim($course);
        if (empty($course)) continue;
        
        if (!in_array($course, $_SESSION['semester_courses'][$department][$semester])) {
            $_SESSION['semester_courses'][$department][$semester][] = $course;
            $added++;
        } else {
            $duplicates++;
        }
    }
    
    return ['added' => $added, 'duplicates' => $duplicates];
}

function getAllDepartmentCourses($department) {
    $allCourses = [];
    if (isset($_SESSION['semester_courses'][$department])) {
        foreach ($_SESSION['semester_courses'][$department] as $semester => $courses) {
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

function getAvailableCourses($department) {
    $allCourses = [
        'CS' => ['Programming Fundamentals', 'ICT', 'Calculus', 'OOP', 'Discrete Structures', 
                 'Pakistan Studies', 'Data Structures', 'Database Systems', 'Software Engineering',
                 'Operating Systems', 'Computer Networks', 'Web Development', 'Artificial Intelligence',
                 'Machine Learning', 'Cybersecurity', 'Cloud Computing', 'Mobile Development'],
        'SE' => ['Programming Basics', 'Introduction to SE', 'Calculus', 'OOP with Java', 
                 'Software Design', 'Web Development', 'Database Systems', 'Software Testing',
                 'Software Project Management', 'Advanced Programming', 'UI/UX Design',
                 'Agile Development', 'Software Architecture', 'DevOps'],
        'AI' => ['Python Programming', 'Discrete Math', 'Calculus', 'Data Science', 'Statistics',
                 'Linear Algebra', 'Machine Learning', 'Deep Learning', 'NLP',
                 'Computer Vision', 'AI Ethics', 'Robotics', 'Neural Networks'],
        'DS' => ['Mathematics', 'Statistics', 'Python', 'Data Analysis', 'Database Systems',
                 'Big Data', 'Machine Learning', 'Data Mining', 'Visualization',
                 'Deep Learning', 'Business Intelligence', 'Cloud Computing', 'Data Warehousing']
    ];
    
    return $allCourses[$department] ?? [];
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
    
    if ($action === 'assign_multiple') {
        $courses = $_POST['courses'] ?? [];
        if (empty($courses)) {
            $message = "❌ Please select at least one course to assign.";
            $messageType = 'error';
        } else {
            $result = addMultipleCourses($department, $semester, $courses);
            if ($result['added'] > 0) {
                $message = "✅ Successfully added {$result['added']} course(s) to $department - Semester $semester.";
                if ($result['duplicates'] > 0) {
                    $message .= " {$result['duplicates']} course(s) were duplicates and skipped.";
                }
                $messageType = 'success';
            } else {
                $message = "❌ No new courses added. All selected courses already exist.";
                $messageType = 'error';
            }
        }
        $selectedDepartment = $department;
        $selectedSemester = $semester;
    }
}

$currentCourses = getCoursesForSemester($selectedDepartment, $selectedSemester);
$allCourses = getAvailableCourses($selectedDepartment);
$existingCourses = getAllDepartmentCourses($selectedDepartment);
$departments = getDepartments();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Assign Courses with Departments</title>
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
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
            font-size: 0.95rem;
        }
        .btn-primary { background: #2b6cb0; color: white; }
        .btn-primary:hover { background: #1a4b7a; }
        .btn-success { background: #38a169; color: white; }
        .btn-success:hover { background: #2d7a4e; }
        
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
        
        .course-checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 10px;
            padding: 20px;
            background: #f9fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            margin: 15px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        .course-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .course-checkbox:hover {
            background: #f7fafc;
            border-color: #2b6cb0;
        }
        .course-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        .course-checkbox label {
            cursor: pointer;
            flex: 1;
        }
        .course-checkbox .existing {
            font-size: 0.8rem;
            color: #38a169;
        }
        .current-courses {
            margin-top: 20px;
            padding: 15px;
            background: #f7fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .current-courses .tag {
            display: inline-block;
            background: #e2e8f0;
            padding: 4px 12px;
            border-radius: 20px;
            margin: 3px;
            font-size: 0.9rem;
        }
        .current-courses .tag.current {
            background: #bee3f8;
            color: #2a69ac;
        }
        .select-all-btn {
            margin: 10px 5px;
            padding: 8px 16px;
            background: #edf2f7;
            border: 1px solid #cbd5e0;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 500;
        }
        .select-all-btn:hover {
            background: #e2e8f0;
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
            .course-checkbox-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📌 Bulk Assign Courses</h1>
            <p>Assign multiple courses to a department and semester at once</p>
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
                    <div class="number"><?= count($currentCourses) ?></div>
                    <div class="label">Current Courses</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count($allCourses) ?></div>
                    <div class="label">Available Courses</div>
                </div>
                <div class="stat-box">
                    <div class="number"><?= count($existingCourses) ?></div>
                    <div class="label">Total Unique Courses</div>
                </div>
            </div>
            
            <!-- Current Courses -->
            <div class="current-courses">
                <strong>Current Courses in <?= $selectedDepartment ?> - Semester <?= $selectedSemester ?>:</strong>
                <?php if (empty($currentCourses)): ?>
                    <span style="color: #718096; margin-left: 10px;">No courses assigned</span>
                <?php else: ?>
                    <?php foreach ($currentCourses as $course): ?>
                        <span class="tag current"><?= htmlspecialchars($course) ?></span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Assignment Form -->
            <div class="assign-form">
                <h3 style="margin: 20px 0 10px 0;">Select Courses to Assign:</h3>
                <form method="POST" action="">
                    <input type="hidden" name="department" value="<?= $selectedDepartment ?>">
                    <input type="hidden" name="semester" value="<?= $selectedSemester ?>">
                    <input type="hidden" name="action" value="assign_multiple">
                    
                    <?php if (empty($allCourses)): ?>
                        <p style="color: #718096; padding: 20px; text-align: center;">
                            No courses available for <?= $selectedDepartment ?> department.
                        </p>
                    <?php else: ?>
                        <button type="button" class="select-all-btn" onclick="toggleAllCheckboxes(true)">✅ Select All</button>
                        <button type="button" class="select-all-btn" onclick="toggleAllCheckboxes(false)">❌ Deselect All</button>
                        
                        <div class="course-checkbox-grid" id="courseGrid">
                            <?php foreach ($allCourses as $course): ?>
                                <?php $isExisting = in_array($course, $currentCourses); ?>
                                <div class="course-checkbox">
                                    <input type="checkbox" name="courses[]" value="<?= htmlspecialchars($course) ?>" 
                                           id="course_<?= md5($course) ?>"
                                           <?= $isExisting ? 'disabled checked' : '' ?>>
                                    <label for="course_<?= md5($course) ?>">
                                        <?= htmlspecialchars($course) ?>
                                        <?php if ($isExisting): ?>
                                            <span class="existing">✓ Already Assigned</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-success" style="width: 100%; margin-top: 15px;">
                            📥 Assign Selected Courses to <?= $selectedDepartment ?> - Semester <?= $selectedSemester ?>
                        </button>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Navigation -->
            <div class="navigation">
                <a href="index.php">📋 View Courses</a>
                <a href="assign.php" class="active">📌 Bulk Assign</a>
                <a href="edit.php">✏️ Edit Courses</a>
            </div>
        </div>
    </div>
    
    <script>
        function toggleAllCheckboxes(checked) {
            const checkboxes = document.querySelectorAll('input[type="checkbox"]:not([disabled])');
            checkboxes.forEach(cb => cb.checked = checked);
        }
    </script>
</body>
</html>