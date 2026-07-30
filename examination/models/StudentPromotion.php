// Add this method inside your StudentPromotion class
public function searchEligibleStudents($search_term, $semester) {
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
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}