<?php
/**
 * get_ongoing_cases.php
 * Fetches all cases reported by a specific user with their current status
 */

header('Content-Type: application/json');
include("db.php");

// Check for user_id
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$user_id = intval($_POST['user_id']);

// Optional status filter: "ongoing" or "closed"
$status_filter = isset($_POST['status']) ? $_POST['status'] : '';

// Build query based on status filter
$status_condition = "";
if ($status_filter === 'ongoing') {
    // Ongoing = cases that are NOT closed
    $status_condition = "AND c.status != 'Closed'";
} else if ($status_filter === 'closed') {
    // Closed = only closed cases
    $status_condition = "AND c.status = 'Closed'";
}

// Query to fetch cases for this user with optional status filter
$query = "
    SELECT 
        c.case_id,
        c.photo,
        c.type_of_animal,
        c.animal_condition,
        c.status AS case_status,
        c.created_time,
        c.latitude,
        c.longitude,
        COALESCE(cs.status, 'Pending') AS rescue_status,
        COALESCE(ct.center_name, 'Awaiting Center') AS assigned_center
    FROM cases c
    LEFT JOIN case_status cs ON c.case_id = cs.case_id
    LEFT JOIN centers ct ON cs.center_id = ct.center_id
    WHERE c.user_id = ?
    $status_condition
    ORDER BY c.created_time DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $stmt->error
    ]);
    exit;
}

$result = $stmt->get_result();
$cases = [];

while ($row = $result->fetch_assoc()) {
    $cases[] = [
        'case_id' => intval($row['case_id']),
        'photo' => $row['photo'],
        'type_of_animal' => $row['type_of_animal'],
        'animal_condition' => $row['animal_condition'],
        'case_status' => $row['case_status'],
        'rescue_status' => $row['rescue_status'],
        'assigned_center' => $row['assigned_center'],
        'created_time' => $row['created_time'],
        'latitude' => floatval($row['latitude']),
        'longitude' => floatval($row['longitude'])
    ];
}

if (count($cases) > 0) {
    echo json_encode([
        'success' => true,
        'message' => 'Cases fetched successfully',
        'total_cases' => count($cases),
        'cases' => $cases
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'No cases found for this user',
        'total_cases' => 0,
        'cases' => []
    ]);
}

$stmt->close();
$conn->close();
?>