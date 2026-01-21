<?php
header("Content-Type: application/json");
include("db.php");

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit;
}

// Get all cases reported by user
$query = $conn->prepare("
    SELECT 
        c.case_id,
        c.photo,
        c.type_of_animal,
        c.animal_condition,
        c.status,
        c.created_time,
        cs.acceptance_status,
        cs.status as case_progress,
        cnt.center_name
    FROM cases c
    LEFT JOIN case_status cs ON c.case_id = cs.case_id
    LEFT JOIN centers cnt ON cs.center_id = cnt.center_id
    WHERE c.user_id = ?
    ORDER BY c.created_time DESC
");

$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$cases = [];
while ($row = $result->fetch_assoc()) {
    $cases[] = [
        "case_id" => (int)$row['case_id'],
        "photo" => $row['photo'],
        "animal_type" => $row['type_of_animal'],
        "condition" => $row['animal_condition'],
        "status" => $row['status'],
        "case_progress" => $row['case_progress'] ?? "Pending",
        "center_name" => $row['center_name'],
        "reported_time" => $row['created_time']
    ];
}

echo json_encode([
    "success" => true,
    "total_cases" => count($cases),
    "cases" => $cases
]);
?>
