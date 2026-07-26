<?php
require_once __DIR__ . '/../config/database.php';
$page_title = 'Debug Applications';
include __DIR__ . '/../includes/header.php';

echo "<h3>Debug: Check Applications Table</h3>";

// Check if table exists
$tables = $pdo->query("SHOW TABLES LIKE 'admission_applications'")->fetchAll();
if (empty($tables)) {
    echo "<div class='alert alert-danger'>Table 'admission_applications' does not exist!</div>";
} else {
    echo "<div class='alert alert-success'>Table exists ✅</div>";
}

// Get column names
$columns = $pdo->query("SHOW COLUMNS FROM admission_applications")->fetchAll(PDO::FETCH_COLUMN);
echo "<h4>Columns in table:</h4>";
echo "<ul>";
foreach ($columns as $col) {
    echo "<li>$col</li>";
}
echo "</ul>";

// Get all records
$records = $pdo->query("SELECT * FROM admission_applications ORDER BY application_id DESC LIMIT 10")->fetchAll();
echo "<h4>Total Records: " . count($records) . "</h4>";

if (empty($records)) {
    echo "<div class='alert alert-warning'>No records found in admission_applications table!</div>";
} else {
    echo "<table class='table table-bordered'>";
    echo "<thead><tr>";
    foreach (array_keys($records[0]) as $key) {
        echo "<th>$key</th>";
    }
    echo "</tr></thead><tbody>";
    foreach ($records as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
        }
        echo "</tr>";
    }
    echo "</tbody></table>";
}

// Check if 'application_status' column exists
$has_status = in_array('application_status', $columns);
echo "<p>Has 'application_status' column? " . ($has_status ? "✅ Yes" : "❌ No") . "</p>";

// Check if 'temp_application_no' column exists
$has_app_no = in_array('temp_application_no', $columns);
echo "<p>Has 'temp_application_no' column? " . ($has_app_no ? "✅ Yes" : "❌ No") . "</p>";

?>
<?php include __DIR__ . '/../includes/footer.php'; ?>