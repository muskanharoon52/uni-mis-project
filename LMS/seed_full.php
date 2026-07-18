<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$pdo = db();

echo "Dropping existing data...\n";

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
foreach (['marks','mark_finalizations','submissions','attendance','enrollments','assignments','lectures','queries','applications','notifications','fee_records','courses','users'] as $table) {
    $pdo->exec("TRUNCATE TABLE `$table`");
}
$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Seeding users...\n";

$teacherPass = password_hash('teacher123', PASSWORD_DEFAULT);
$studentPass = password_hash('student123', PASSWORD_DEFAULT);

$insertUser = $pdo->prepare(
    'INSERT INTO users (login_id, name, email, password_hash, role, department, program) VALUES (?, ?, ?, ?, ?, ?, ?)'
);

$teachers = [
    ['5001', 'Dr. Sara Khan',      'sara.khan@lms.test',      'Computer Science', null],
    ['5002', 'Dr. Ahmed Ali',      'ahmed.ali@lms.test',      'Computer Science', null],
    ['5003', 'Dr. Fatima Noor',    'fatima.noor@lms.test',    'Software Engineering', null],
    ['5004', 'Prof. Imran Shah',   'imran.shah@lms.test',     'Information Technology', null],
    ['5005', 'Dr. Zainab Malik',   'zainab.malik@lms.test',   'Data Science', null],
];

$teacherIds = [];
foreach ($teachers as [$lid, $name, $email, $dept, $prog]) {
    $insertUser->execute([$lid, $name, $email, $teacherPass, 'teacher', $dept, $prog]);
    $teacherIds[] = (int) $pdo->lastInsertId();
}

$students = [
    ['9001', 'Ali Raza',         'ali.raza@lms.test',         'BS Software Engineering'],
    ['9002', 'Amina Noor',       'amina.noor@lms.test',       'BS Software Engineering'],
    ['9003', 'Bilal Ahmed',      'bilal.ahmed@lms.test',      'BS Information Technology'],
    ['9004', 'Hira Khan',        'hira.khan@lms.test',        'BS Computer Science'],
    ['9005', 'Hassan Ali',       'hassan.ali@lms.test',       'BS Software Engineering'],
    ['9006', 'Maryam Iqbal',     'maryam.iqbal@lms.test',     'BS Data Science'],
    ['9007', 'Usman Tariq',      'usman.tariq@lms.test',      'BS Computer Science'],
    ['9008', 'Sana Raza',        'sana.raza@lms.test',        'BS Information Technology'],
    ['9009', 'Zain Malik',       'zain.malik@lms.test',       'BS Software Engineering'],
    ['9010', 'Iqra Javed',       'iqra.javed@lms.test',       'BS Data Science'],
    ['9011', 'Omar Farooq',      'omar.farooq@lms.test',      'BS Computer Science'],
    ['9012', 'Nadia Hussain',    'nadia.hussain@lms.test',    'BS Software Engineering'],
    ['9013', 'Saad Khan',        'saad.khan@lms.test',        'BS Information Technology'],
    ['9014', 'Ayesha Siddiqui',  'ayesha.siddiqui@lms.test',  'BS Data Science'],
    ['9015', 'Danish Raza',      'danish.raza@lms.test',      'BS Computer Science'],
    ['9016', 'Fatima Zahra',     'fatima.zahra@lms.test',     'BS Software Engineering'],
    ['9017', 'Hamza Tariq',      'hamza.tariq@lms.test',      'BS Information Technology'],
    ['9018', 'Javeria Akhtar',   'javeria.akhtar@lms.test',   'BS Data Science'],
    ['9019', 'Kamran Malik',     'kamran.malik@lms.test',     'BS Computer Science'],
    ['9020', 'Laiba Shah',       'laiba.shah@lms.test',       'BS Software Engineering'],
];

$studentIds = [];
foreach ($students as [$lid, $name, $email, $prog]) {
    $insertUser->execute([$lid, $name, $email, $studentPass, 'student', 'Computer Science', $prog]);
    $studentIds[] = (int) $pdo->lastInsertId();
}

echo "Seeding courses...\n";

$insertCourse = $pdo->prepare(
    'INSERT INTO courses (code, title, description, credit_hours, teacher_id, semester) VALUES (?, ?, ?, ?, ?, ?)'
);

$courses = [
    ['CS101',  'Introduction to Programming',  'Fundamentals of programming using clear logic and problem solving.',  3, $teacherIds[0], 'Spring 2026'],
    ['SE204',  'Database Systems',             'Design and manage relational databases and SQL-based applications.',     3, $teacherIds[0], 'Spring 2026'],
    ['CS201',  'Data Structures & Algorithms', 'Study of arrays, linked lists, trees, graphs, and sorting algorithms.', 3, $teacherIds[1], 'Spring 2026'],
    ['SE301',  'Software Engineering',         'Software lifecycle models, requirements, design, testing, and maintenance.', 3, $teacherIds[2], 'Spring 2026'],
    ['IT101',  'Computer Networks',            'OSI model, TCP/IP, routing, switching, and network security basics.',    3, $teacherIds[3], 'Spring 2026'],
    ['DS101',  'Introduction to Data Science', 'Python, pandas, NumPy, data wrangling, and exploratory data analysis.', 3, $teacherIds[4], 'Spring 2026'],
    ['CS301',  'Operating Systems',            'Process management, memory, file systems, and concurrency concepts.',     3, $teacherIds[1], 'Spring 2026'],
    ['SE401',  'Web Engineering',              'Full-stack web development with PHP, MySQL, and modern JS frameworks.',  3, $teacherIds[2], 'Spring 2026'],
];

$courseIds = [];
foreach ($courses as [$code, $title, $desc, $ch, $tid, $sem]) {
    $insertCourse->execute([$code, $title, $desc, $ch, $tid, $sem]);
    $courseIds[] = (int) $pdo->lastInsertId();
}

echo "Seeding enrollments...\n";

$insertEnroll = $pdo->prepare('INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)');

$enrollMap = [
    // CS students
    0  => [0, 1, 2, 3, 6],     // Ali Raza
    1  => [0, 1, 2, 3, 6],     // Amina Noor
    3  => [0, 1, 2, 6, 7],     // Hira Khan
    4  => [0, 2, 3, 6, 7],     // Hassan Ali
    6  => [0, 1, 2, 6],        // Usman Tariq
    10 => [0, 2, 6, 7],        // Omar Farooq
    14 => [0, 2, 3, 6],        // Danish Raza
    18 => [0, 2, 6, 7],        // Kamran Malik

    // SE students
    2  => [0, 1, 3, 4, 7],     // Bilal Ahmed
    8  => [0, 3, 4, 7],        // Zain Malik
    11 => [1, 3, 4, 7],        // Nadia Hussain
    15 => [1, 3, 4, 7],        // Fatima Zahra
    19 => [1, 3, 4, 7],        // Laiba Shah

    // IT students
    7  => [1, 2, 4, 5],        // Sana Raza
    12 => [1, 2, 4, 5],        // Saad Khan
    16 => [2, 4, 5],           // Hamza Tariq

    // DS students
    5  => [1, 5, 6],           // Maryam Iqbal
    9  => [1, 5, 6],           // Iqra Javed
    13 => [2, 5, 6],           // Ayesha Siddiqui
    17 => [1, 5, 6],           // Javeria Akhtar
];

foreach ($enrollMap as $idx => $courseIdxs) {
    foreach ($courseIdxs as $ci) {
        $insertEnroll->execute([$studentIds[$idx], $courseIds[$ci]]);
    }
}

echo "Seeding assignments...\n";

$insertAssignment = $pdo->prepare(
    'INSERT INTO assignments (course_id, title, description, due_date) VALUES (?, ?, ?, ?)'
);

$assignments = [
    [$courseIds[0], 'Loops Practice',       'Submit five solved loop problems in your preferred language.', '2026-07-20'],
    [$courseIds[0], 'Functions & Recursion', 'Write recursive solutions for factorial, Fibonacci, and Tower of Hanoi.', '2026-07-28'],
    [$courseIds[1], 'ERD Design',           'Create an ERD for a library management system.', '2026-07-25'],
    [$courseIds[1], 'SQL Queries',          'Write 10 SQL queries on the provided university dataset.', '2026-08-01'],
    [$courseIds[2], 'Sorting Benchmark',    'Implement and compare bubble, merge, and quick sort on 10K integers.', '2026-07-30'],
    [$courseIds[2], 'Graph Traversal',      'Implement BFS and DFS on an adjacency list graph.', '2026-08-05'],
    [$courseIds[3], 'Requirements Doc',     'Write an SRS document for a student feedback system.', '2026-07-27'],
    [$courseIds[4], 'Packet Analysis',      'Capture and analyze network traffic using Wireshark.', '2026-07-22'],
    [$courseIds[5], 'EDA Notebook',         'Perform exploratory data analysis on the Titanic dataset using pandas.', '2026-07-26'],
    [$courseIds[6], 'Shell Scripting',      'Write bash scripts for file monitoring and log rotation.', '2026-08-02'],
    [$courseIds[7], 'Laravel Blog',         'Build a blog application with authentication using Laravel.', '2026-08-03'],
];

$assignmentIds = [];
foreach ($assignments as [$cid, $title, $desc, $due]) {
    $insertAssignment->execute([$cid, $title, $desc, $due]);
    $assignmentIds[] = (int) $pdo->lastInsertId();
}

echo "Seeding lectures...\n";

$insertLecture = $pdo->prepare(
    'INSERT INTO lectures (course_id, title, file_path, lecture_date) VALUES (?, ?, ?, ?)'
);

$lectures = [
    [$courseIds[0], 'Lecture 1 - Variables & Types',   'lectures/cs101_lec1.pdf', '2026-07-01'],
    [$courseIds[0], 'Lecture 2 - Control Flow',         'lectures/cs101_lec2.pdf', '2026-07-03'],
    [$courseIds[0], 'Lecture 3 - Functions',            'lectures/cs101_lec3.pdf', '2026-07-05'],
    [$courseIds[1], 'Lecture 1 - Intro to Databases',   'lectures/se204_lec1.pdf', '2026-07-02'],
    [$courseIds[1], 'Lecture 2 - ER Modeling',          'lectures/se204_lec2.pdf', '2026-07-04'],
    [$courseIds[2], 'Lecture 1 - Arrays & Lists',       'lectures/cs201_lec1.pdf', '2026-07-01'],
    [$courseIds[2], 'Lecture 2 - Stacks & Queues',      'lectures/cs201_lec2.pdf', '2026-07-03'],
    [$courseIds[3], 'Lecture 1 - SDLC Models',          'lectures/se301_lec1.pdf', '2026-07-02'],
    [$courseIds[4], 'Lecture 1 - OSI Model',            'lectures/it101_lec1.pdf', '2026-07-01'],
    [$courseIds[5], 'Lecture 1 - Python Refresher',     'lectures/ds101_lec1.pdf', '2026-07-03'],
    [$courseIds[5], 'Lecture 2 - Pandas Basics',        'lectures/ds101_lec2.pdf', '2026-07-05'],
    [$courseIds[6], 'Lecture 1 - Process Management',   'lectures/cs301_lec1.pdf', '2026-07-02'],
    [$courseIds[7], 'Lecture 1 - HTML & CSS Crash Course', 'lectures/se401_lec1.pdf', '2026-07-04'],
];

foreach ($lectures as [$cid, $title, $path, $date]) {
    $insertLecture->execute([$cid, $title, $path, $date]);
}

echo "Seeding attendance...\n";

$insertAttendance = $pdo->prepare(
    'INSERT INTO attendance (course_id, student_id, class_date, status) VALUES (?, ?, ?, ?)'
);

$attStatuses = ['present', 'present', 'present', 'present', 'late', 'absent'];
$attDates = ['2026-07-01','2026-07-02','2026-07-03','2026-07-04','2026-07-05','2026-07-07','2026-07-08','2026-07-09'];

foreach ($enrollMap as $studentIdx => $courseIdxs) {
    foreach ($courseIdxs as $courseIdx) {
        foreach ($attDates as $di => $date) {
            $status = $attStatuses[($studentIdx + $di) % count($attStatuses)];
            $insertAttendance->execute([$courseIds[$courseIdx], $studentIds[$studentIdx], $date, $status]);
        }
    }
}

echo "Seeding internal marks...\n";

$insertMark = $pdo->prepare(
    'INSERT INTO marks (course_id, student_id, component, marks_obtained, total_marks) VALUES (?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE marks_obtained = VALUES(marks_obtained), total_marks = VALUES(total_marks)'
);

$components = ['assignment_1','assignment_2','assignment_3','test_1','test_2','test_3','presentation','major_assignment','mid_term'];
$baseMarks = [
    'CS101' => [4, 3.5, 3, 4, 3, 2.5, 7, 8, 20],
    'SE204' => [3.5, 4, 2.5, 3.5, 3, 2, 8, 9, 22],
    'CS201' => [4.5, 4, 3.5, 4, 3.5, 3, 7.5, 8.5, 23],
    'SE301' => [3, 3.5, 4, 3, 2.5, 3, 9, 7, 21],
    'IT101' => [4, 3, 3, 4, 3.5, 2.5, 6.5, 8, 19],
    'DS101' => [4.5, 4, 3, 3.5, 4, 3, 8, 9, 24],
    'CS301' => [3.5, 3, 4, 3, 3.5, 2.5, 7, 7.5, 20],
    'SE401' => [4, 4, 3.5, 4, 3, 3, 8.5, 9, 22],
];

$codeById = [];
foreach ($courses as $i => [$code]) {
    $codeById[$courseIds[$i]] = $code;
}

foreach ($enrollMap as $studentIdx => $courseIdxs) {
    foreach ($courseIdxs as $courseIdx) {
        $cid = $courseIds[$courseIdx];
        $code = $codeById[$cid];
        $base = $baseMarks[$code] ?? array_fill(0, 9, 0);
        foreach ($components as $ci => $comp) {
            $marks = round(min((float)$base[$ci] + ($studentIdx % 3) * 0.5, 5), 2);
            $insertMark->execute([$cid, $studentIds[$studentIdx], $comp, $marks, 5]);
        }
    }
}

echo "Seeding submissions...\n";

$insertSubmission = $pdo->prepare(
    'INSERT INTO submissions (assignment_id, student_id, content, submitted_at, grade, feedback) VALUES (?, ?, ?, ?, ?, ?)'
);

$submissions = [
    [$assignmentIds[0], $studentIds[0],  'Completed all five loop problems with comments.',  '2026-07-18 10:30:00', 8.5, 'Good work.'],
    [$assignmentIds[0], $studentIds[3],  'Solutions attached as PDF.',                       '2026-07-19 14:00:00', 9.0, 'Excellent.'],
    [$assignmentIds[2], $studentIds[2],  'ERD drawn in draw.io, exported as PNG.',            '2026-07-24 09:15:00', 7.5, 'Missing relationships.'],
    [$assignmentIds[2], $studentIds[8],  'ERD with 6 entities and relationships.',            '2026-07-23 16:45:00', 8.0, 'Well structured.'],
    [$assignmentIds[4], $studentIds[0],  'Benchmark results in CSV and analysis in notebook.','2026-07-29 22:00:00', 9.5, 'Outstanding.'],
    [$assignmentIds[4], $studentIds[10], 'All three algorithms implemented and compared.',    '2026-07-30 11:00:00', 7.0, 'Needs more test cases.'],
    [$assignmentIds[8], $studentIds[5],  'Jupyter notebook with visualizations.',            '2026-07-25 13:20:00', 8.5, 'Great EDA.'],
    [$assignmentIds[8], $studentIds[9],  'EDA with pandas and matplotlib.',                  '2026-07-26 08:00:00', 9.0, 'Clean analysis.'],
    [$assignmentIds[10], $studentIds[11], 'Laravel blog with auth and CRUD.',                 '2026-08-02 23:50:00', 8.0, 'Nice implementation.'],
];

foreach ($submissions as [$aid, $sid, $content, $at, $grade, $fb]) {
    $insertSubmission->execute([$aid, $sid, $content, $at, $grade, $fb]);
}

echo "Seeding queries...\n";

$insertQuery = $pdo->prepare(
    'INSERT INTO queries (user_id, subject, message, status, reply) VALUES (?, ?, ?, ?, ?)'
);

$queries = [
    [$studentIds[0],  'Attendance correction', 'Please check my attendance for CS101 on July 3rd.', 'open', null],
    [$studentIds[3],  'Grade dispute',         'I believe my Quiz 1 marks were entered incorrectly for SE204.', 'answered', 'Please visit the department office with your roll number.'],
    [$studentIds[5],  'Assignment extension',  'Can I get 2 more days for the EDA notebook?', 'open', null],
    [$studentIds[8],  'Course registration',   'I want to add CS301 to my enrollment.', 'open', null],
    [$studentIds[10], 'Lab access issue',      'The CS lab password was changed without notice.', 'answered', 'New password has been shared via email.'],
    [$studentIds[12], 'Project submission',    'My project file is too large to upload. Can I submit via USB?', 'open', null],
];

foreach ($queries as [$uid, $subj, $msg, $status, $reply]) {
    $insertQuery->execute([$uid, $subj, $msg, $status, $reply]);
}

echo "Seeding applications...\n";

$insertApp = $pdo->prepare(
    'INSERT INTO applications (user_id, type, details, status) VALUES (?, ?, ?, ?)'
);

$apps = [
    [$studentIds[0],  'Leave Request',   'Medical leave for one day (July 5).',              'approved'],
    [$studentIds[2],  'Leave Request',   'Family function, need leave on July 8-9.',          'pending'],
    [$studentIds[5],  'Fee Deferral',    'Requesting fee deferral for this semester.',        'pending'],
    [$studentIds[8],  'Course Drop',     'Want to drop IT101 due to schedule conflict.',      'rejected'],
    [$studentIds[10], 'Transcript Req',  'Need official transcript for job application.',     'approved'],
];

foreach ($apps as [$uid, $type, $details, $status]) {
    $insertApp->execute([$uid, $type, $details, $status]);
}

echo "Seeding fee records...\n";

$insertFee = $pdo->prepare(
    'INSERT INTO fee_records (student_id, semester, description, amount, paid_amount, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)'
);

foreach ($studentIds as $i => $sid) {
    $insertFee->execute([$sid, 'Spring 2026', 'Semester Tuition Fee',    85000, 85000, '2026-07-15', 'paid']);
    $insertFee->execute([$sid, 'Spring 2026', 'Library and Lab Charges',  7500,  7500, '2026-07-15', 'paid']);
    if ($i % 3 === 0) {
        $insertFee->execute([$sid, 'Spring 2026', 'Lab Activity Fee',  1500,   500, '2026-07-25', 'partial']);
    }
    if ($i % 5 === 0) {
        $insertFee->execute([$sid, 'Spring 2026', 'Hostel Fee',       45000,     0, '2026-07-20', 'unpaid']);
    }
}

echo "Seeding notifications...\n";

$insertNotif = $pdo->prepare(
    'INSERT INTO notifications (recipient_user_id, sender_user_id, category, title, body, link_url) VALUES (?, ?, ?, ?, ?, ?)'
);

$notifMessages = [
    ['notification', 'New assignment posted',    'Check your dashboard for the latest assignment.', 'student/courses.php'],
    ['notification', 'Attendance updated',       'Your attendance has been marked for today.',       'student/attendance.php'],
    ['message',      'Welcome to University LMS','Keep an eye on your dashboard for updates.',       'student/dashboard.php'],
    ['announcement', 'Semester update',           'Spring 2026 notices will appear here.',            'student/dashboard.php'],
];

$randStudents = array_slice($studentIds, 0, 10);
foreach ($randStudents as $sid) {
    foreach ($notifMessages as [$cat, $title, $body, $link]) {
        $insertNotif->execute([$sid, $teacherIds[0], $cat, $title, $body, $link]);
    }
}

echo "\nDone! Database seeded with:\n";
echo "  - 5 teachers\n";
echo "  - 20 students\n";
echo "  - 8 courses\n";
echo "  - 11 assignments\n";
echo "  - 13 lectures\n";
echo "  - attendance records\n";
echo "  - internal marks\n";
echo "  - 9 submissions\n";
echo "  - 6 queries\n";
echo "  - 5 applications\n";
echo "  - fee records\n";
echo "  - notifications\n";
echo "\nLogin: 5001/teacher123 or 9001/student123\n";
