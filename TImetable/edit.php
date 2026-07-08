<?php
session_start();
include '../includes/db.php';

$conn = getConnection();
$message = '';
$message_type = '';

// Get ID from URL
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Fetch existing data
$query = "SELECT * FROM timetable WHERE id = $id";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);

if (!$row) {
    header('Location: index.php');
    exit();
}

// Fetch data for dropdowns
$courses = getAllRecords("SELECT course_id, course_code, course_title, credit_hours FROM courses WHERE status = 'Active' ORDER BY course_title");
$teachers = getAllRecords("SELECT teacher_id, teacher_name, designation FROM teachers WHERE status = 'Active' ORDER BY teacher_name");
$semesters = getAllRecords("SELECT semester_id, semester_name FROM semesters ORDER BY semester_name");
$sessions = getAllRecords("SELECT session_id, session_name, start_date, end_date FROM sessions WHERE status = 'Active' ORDER BY session_name");

// Update data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_id = (int)$_POST['course_id'];
    $teacher_id = (int)$_POST['teacher_id'];
    $day_of_week = mysqli_real_escape_string($conn, $_POST['day_of_week']);
    $start_time = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($conn, $_POST['end_time']);
    $room_no = mysqli_real_escape_string($conn, $_POST['room_no']);
    $semester_id = (int)$_POST['semester_id'];
    $session_id = (int)$_POST['session_id'];
    $section = mysqli_real_escape_string($conn, $_POST['section']);
    
    // Check for conflicts (excluding current record)
    $conflict_check = "SELECT * FROM timetable 
                       WHERE day_of_week = '$day_of_week' 
                       AND room_no = '$room_no' 
                       AND id != $id
                       AND ((start_time < '$end_time' AND end_time > '$start_time'))";
    $conflict_result = mysqli_query($conn, $conflict_check);
    
    if (!$conflict_result) {
        $message = "Error checking conflicts: " . mysqli_error($conn);
        $message_type = "danger";
    } elseif (mysqli_num_rows($conflict_result) > 0) {
        $message = "⚠️ Room conflict! Another class is scheduled in this room at the same time.";
        $message_type = "danger";
    } else {
        $update_query = "UPDATE timetable SET 
                         course_id = $course_id,
                         teacher_id = $teacher_id,
                         day_of_week = '$day_of_week',
                         start_time = '$start_time',
                         end_time = '$end_time',
                         room_no = '$room_no',
                         semester_id = $semester_id,
                         session_id = $session_id,
                         section = '$section'
                         WHERE id = $id";
        
        if (mysqli_query($conn, $update_query)) {
            $_SESSION['message'] = "✅ Class updated successfully!";
            $_SESSION['message_type'] = "success";
            header('Location: index.php');
            exit();
        } else {
            $message = "Error: " . mysqli_error($conn);
            $message_type = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Class</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .form-card h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
        }
        .form-label {
            font-weight: 600;
            color: #555;
        }
        .btn-update {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 40px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-update:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        .current-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .session-date-info {
            font-size: 12px;
            color: #6c757d;
            display: block;
            margin-top: 3px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-card">
            <h2><i class="fas fa-edit me-2" style="color: #667eea;"></i>Edit Class</h2>
            
            <div class="current-info">
                <small><i class="fas fa-info-circle me-1"></i> Editing class ID: #<?php echo $row['id']; ?></small>
            </div>
            
            <?php if($message): ?>
                <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Course</label>
                        <select name="course_id" class="form-control" required>
                            <option value="">Select Course</option>
                            <?php foreach($courses as $course): ?>
                                <option value="<?php echo $course['course_id']; ?>" 
                                    <?php echo ($row['course_id'] == $course['course_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_title']); ?>
                                    (<?php echo $course['credit_hours']; ?> CH)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Teacher</label>
                        <select name="teacher_id" class="form-control" required>
                            <option value="">Select Teacher</option>
                            <?php foreach($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['teacher_id']; ?>"
                                    <?php echo ($row['teacher_id'] == $teacher['teacher_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($teacher['teacher_name']); ?>
                                    <?php if($teacher['designation']): ?>
                                        (<?php echo htmlspecialchars($teacher['designation']); ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Day</label>
                        <select name="day_of_week" class="form-control" required>
                            <option value="">Select Day</option>
                            <option value="Monday" <?php echo ($row['day_of_week'] == 'Monday') ? 'selected' : ''; ?>>Monday</option>
                            <option value="Tuesday" <?php echo ($row['day_of_week'] == 'Tuesday') ? 'selected' : ''; ?>>Tuesday</option>
                            <option value="Wednesday" <?php echo ($row['day_of_week'] == 'Wednesday') ? 'selected' : ''; ?>>Wednesday</option>
                            <option value="Thursday" <?php echo ($row['day_of_week'] == 'Thursday') ? 'selected' : ''; ?>>Thursday</option>
                            <option value="Friday" <?php echo ($row['day_of_week'] == 'Friday') ? 'selected' : ''; ?>>Friday</option>
                            <option value="Saturday" <?php echo ($row['day_of_week'] == 'Saturday') ? 'selected' : ''; ?>>Saturday</option>
                            <option value="Sunday" <?php echo ($row['day_of_week'] == 'Sunday') ? 'selected' : ''; ?>>Sunday</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" 
                               value="<?php echo $row['start_time']; ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" 
                               value="<?php echo $row['end_time']; ?>" required>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Room No</label>
                        <input type="text" name="room_no" class="form-control" 
                               value="<?php echo htmlspecialchars($row['room_no']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Section</label>
                        <input type="text" name="section" class="form-control" 
                               value="<?php echo htmlspecialchars($row['section']); ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester_id" class="form-control" required>
                            <option value="">Select Semester</option>
                            <?php foreach($semesters as $semester): ?>
                                <option value="<?php echo $semester['semester_id']; ?>"
                                    <?php echo ($row['semester_id'] == $semester['semester_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($semester['semester_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Session</label>
                    <select name="session_id" class="form-control" required>
                        <option value="">Select Session</option>
                        <?php foreach($sessions as $session): ?>
                            <option value="<?php echo $session['session_id']; ?>"
                                <?php echo ($row['session_id'] == $session['session_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($session['session_name']); ?>
                                <?php if($session['start_date'] && $session['end_date']): ?>
                                    <span class="session-date-info">
                                        (<?php echo date('M Y', strtotime($session['start_date'])); ?> - 
                                        <?php echo date('M Y', strtotime($session['end_date'])); ?>)
                                    </span>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-update">
                        <i class="fas fa-save me-2"></i>Update Class
                    </button>
                    <a href="index.php" class="btn btn-back">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>