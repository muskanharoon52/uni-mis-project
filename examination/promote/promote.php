<?php
$page_title = 'Promote Students';

require_once '../../config/db_connect.php';

// ============================================
// START OF CLASS DEFINITION (Embedded directly in file to fix "Class not found" error)
// ============================================
class StudentPromotion {
    private $db;
    public function __construct() {
        global $conn;
        $this->db = $conn;
        if (!$this->db) {
            die("Database connection failed in StudentPromotion model.");
        }
    }

    // Search eligible students by ID/Name and Semester
    public function searchEligibleStudents($search_term, $semester) {
        $search_term = trim($search_term);
        $sql = "SELECT s.*
                FROM students s
                WHERE s.semester = ? AND s.status = 'active'";
        
        $params = [$semester];
        $types = "i";

        if (!empty($search_term)) {
            $sql .= " AND (s.student_id LIKE ? OR s.full_name LIKE ?)";
            $search_param = "%" . $search_term . "%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= "ss";
        }

        $sql .= " ORDER BY s.full_name ASC";
        
        $stmt = $this->db->prepare($sql);
        if($stmt === false) die("SQL Error: " . $this->db->error);
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Promote selected students to next semester
    public function promote($student_ids, $next_semester) {
        if (empty($student_ids)) return false;
        $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
        $sql = "UPDATE students SET semester = ? WHERE student_id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        if($stmt === false) return false;
        
        $params = array_merge([$next_semester], $student_ids);
        $types = "i" . str_repeat("i", count($student_ids));
        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
}
// ============================================
// END OF CLASS DEFINITION
// ============================================

// Connect to Database & Initialize Class
$conn = getConnection();
$promotion = new StudentPromotion();

// Initialize search variables
$search_term = '';
$selected_semester = null;
$students = [];

// Handle search request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Check if it's a SEARCH action
    if (isset($_POST['search_students'])) {
        $search_term = trim($_POST['search_term'] ?? '');
        $selected_semester = $_POST['semester'] ?? null;
        $students = $promotion->searchEligibleStudents($search_term, $selected_semester);
    }
    
    // 2. Check if it's a PROMOTE action
    if (isset($_POST['promote_students'])) {
        $student_ids = $_POST['student_ids'] ?? [];
        $next_semester = $_POST['next_semester'];
        
        if (!empty($student_ids)) {
            if ($promotion->promote($student_ids, $next_semester)) {
                $_SESSION['success'] = count($student_ids) . " students promoted to Semester " . $next_semester;
                header("Location: promote.php");
                exit();
            } else {
                $_SESSION['error'] = "Failed to promote students!";
            }
        } else {
            $_SESSION['error'] = "Please select at least one student to promote!";
        }
    }
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="content-area" id="contentArea">
    <div class="page-header">
        <div class="page-header-left">
            <h4>Student Promotion</h4>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <?php 
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <?php 
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-content">
            <div class="form-container">
                <form method="POST">
                    <div class="form-row">
                        <!-- Search by Student ID or Name -->
                        <div class="form-group" style="flex: 2;">
                            <label for="search_term">Search by Student ID or Name</label>
                            <input type="text" id="search_term" name="search_term" 
                                   placeholder="Enter Student ID or Name" 
                                   value="<?php echo htmlspecialchars($search_term); ?>">
                            <small style="color: var(--text-muted);">Leave empty to show all students for the selected semester.</small>
                        </div>

                        <!-- Semester Dropdown -->
                        <div class="form-group" style="flex: 1;">
                            <label for="semester">Current Semester</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select Semester</option>
                                <?php for($i = 1; $i <= 8; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                                        Semester <?php echo $i; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" name="search_students" class="btn btn-primary" style="width:100%;">
                                <i class="bi bi-search"></i> Search & Check Eligibility
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_students'])): ?>
        <?php if (!empty($students)): ?>
        <div class="card">
            <div class="card-header">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h5>Eligible Students</h5>
                    <span class="status-badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">
                        <?php echo count($students); ?> students found
                    </span>
                </div>
            </div>
            <div class="card-content">
                <form method="POST">
                    <input type="hidden" name="search_term" value="<?php echo htmlspecialchars($search_term); ?>">
                    <input type="hidden" name="semester" value="<?php echo $selected_semester; ?>">
                    <input type="hidden" name="next_semester" value="<?php echo $selected_semester + 1; ?>">
                    
                    <div style="margin-bottom:1rem;">
                        <button type="button" class="btn btn-outline" onclick="selectAll()">
                            <i class="bi bi-check-all"></i> Select All
                        </button>
                        <button type="button" class="btn btn-outline" onclick="deselectAll()">
                            <i class="bi bi-x-circle"></i> Deselect All
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="selectAllCheckbox" onchange="toggleAllCheckboxes()"></th>
                                    <th>Student ID</th>
                                    <th>Name</th>
                                    <th>Current Semester</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="student_ids[]" 
                                               value="<?php echo $student['student_id']; ?>" 
                                               class="student-checkbox">
                                    </td>
                                    <td><?php echo $student['student_id']; ?></td>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td>Semester <?php echo $student['semester']; ?></td>
                                    <td>
                                        <span class="status-badge" style="background:var(--success-bg);color:var(--success);border:1px solid var(--success-border);">Eligible</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert" style="background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);margin-top:1rem;">
                        <i class="bi bi-info-circle"></i> 
                        Selected students will be promoted to <strong>Semester <?php echo $selected_semester + 1; ?></strong>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" name="promote_students" class="btn btn-primary"
                                onclick="return confirm('Are you sure you want to promote selected students?')">
                            <i class="bi bi-arrow-up-circle"></i> Promote Selected Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert" style="background:var(--info-bg);color:var(--accent);border:1px solid var(--info-border);">
            <i class="bi bi-info-circle"></i> 
            No students found matching your search criteria in this semester.
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
function toggleAllCheckboxes() {
    const selectAll = document.getElementById('selectAllCheckbox');
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
}

function selectAll() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = true);
    document.getElementById('selectAllCheckbox').checked = true;
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => cb.checked = false);
    document.getElementById('selectAllCheckbox').checked = false;
}
</script>

<?php include '../includes/footer.php'; ?>