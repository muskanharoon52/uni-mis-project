<?php
session_start();
include '../includes/db.php';

$conn = getConnection();

// Get ID from URL
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

$id = (int)$_GET['id'];

// Fetch class details with all joins
$sql = "SELECT 
            t.*,
            c.course_code,
            c.course_title,
            c.credit_hours,
            tch.teacher_name,
            tch.designation,
            tch.email as teacher_email,
            tch.phone as teacher_phone,
            s.semester_name,
            ses.session_name,
            ses.start_date as session_start_date,
            ses.end_date as session_end_date,
            d.department_name,
            p.program_name
        FROM timetable t
        LEFT JOIN courses c ON t.course_id = c.course_id
        LEFT JOIN teachers tch ON t.teacher_id = tch.teacher_id
        LEFT JOIN semesters s ON t.semester_id = s.semester_id
        LEFT JOIN sessions ses ON t.session_id = ses.session_id
        LEFT JOIN departments d ON c.department_id = d.department_id
        LEFT JOIN programs p ON c.program_id = p.program_id
        WHERE t.id = $id";

$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) == 0) {
    header('Location: index.php');
    exit();
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 50px auto;
        }
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 35px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
        }
        .detail-card h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 25px;
        }
        .detail-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #555;
            width: 150px;
            display: inline-block;
        }
        .detail-value {
            color: #333;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-back:hover {
            background: #5a6268;
            color: white;
        }
        .btn-edit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 30px;
            border: none;
            border-radius: 25px;
            font-weight: 600;
        }
        .btn-edit:hover {
            color: white;
        }
        .badge-info {
            background-color: #17a2b8;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="detail-card">
            <h2><i class="fas fa-info-circle me-2" style="color: #667eea;"></i>Class Details</h2>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-calendar-day me-2"></i>Day:</span>
                <span class="detail-value">
                    <span class="badge bg-primary"><?php echo $row['day_of_week']; ?></span>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-clock me-2"></i>Time:</span>
                <span class="detail-value">
                    <strong><?php echo date('g:i A', strtotime($row['start_time'])); ?></strong> - 
                    <strong><?php echo date('g:i A', strtotime($row['end_time'])); ?></strong>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-book me-2"></i>Course:</span>
                <span class="detail-value">
                    <strong><?php echo htmlspecialchars($row['course_title']); ?></strong>
                    <br><small class="text-muted">Code: <?php echo htmlspecialchars($row['course_code']); ?></small>
                    <br><small class="text-muted">Credit Hours: <?php echo $row['credit_hours']; ?></small>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-user-tie me-2"></i>Teacher:</span>
                <span class="detail-value">
                    <strong><?php echo htmlspecialchars($row['teacher_name']); ?></strong>
                    <?php if($row['designation']): ?>
                        <br><small class="text-muted"><?php echo htmlspecialchars($row['designation']); ?></small>
                    <?php endif; ?>
                    <?php if($row['teacher_email']): ?>
                        <br><small class="text-muted"><i class="fas fa-envelope me-1"></i><?php echo $row['teacher_email']; ?></small>
                    <?php endif; ?>
                    <?php if($row['teacher_phone']): ?>
                        <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?php echo $row['teacher_phone']; ?></small>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-door-open me-2"></i>Room:</span>
                <span class="detail-value">
                    <span class="badge bg-info"><?php echo htmlspecialchars($row['room_no']); ?></span>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-layer-group me-2"></i>Section:</span>
                <span class="detail-value">
                    <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($row['section']); ?></span>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-graduation-cap me-2"></i>Semester:</span>
                <span class="detail-value"><?php echo htmlspecialchars($row['semester_name']); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-calendar-alt me-2"></i>Session:</span>
                <span class="detail-value">
                    <?php echo htmlspecialchars($row['session_name']); ?>
                    <?php if($row['session_start_date'] && $row['session_end_date']): ?>
                        <br><small class="text-muted">
                            <?php echo date('d M Y', strtotime($row['session_start_date'])); ?> - 
                            <?php echo date('d M Y', strtotime($row['session_end_date'])); ?>
                        </small>
                    <?php endif; ?>
                </span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-building me-2"></i>Department:</span>
                <span class="detail-value"><?php echo htmlspecialchars($row['department_name']); ?></span>
            </div>
            
            <div class="detail-item">
                <span class="detail-label"><i class="fas fa-tag me-2"></i>Program:</span>
                <span class="detail-value"><?php echo htmlspecialchars($row['program_name']); ?></span>
            </div>
            
            <div class="mt-4 d-flex gap-2">
                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-edit">
                    <i class="fas fa-edit me-2"></i>Edit
                </a>
                <a href="index.php" class="btn btn-back">
                    <i class="fas fa-arrow-left me-2"></i>Back
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>