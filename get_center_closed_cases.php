<?php
header("Content-Type: application/json");
include("db.php");

// Input
$center_id = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;

if (!$center_id) {
    echo json_encode([
        "status" => "error",
        "message" => "center_id is required"
    ]);
    exit;
}

// Get closed cases for this center
$stmt = $conn->prepare("
    SELECT 
        cs.status_id,
        cs.case_id,
        cs.case_took_up_time,
        cs.rescued_photo,
        cs.status AS rescue_status,
        c.photo,
        c.type_of_animal,
        c.animal_condition
    FROM case_status cs
    JOIN cases c ON cs.case_id = c.case_id
    WHERE cs.center_id = ?
      AND cs.acceptance_status = 'Accepted'
      AND cs.status = 'Closed'
    ORDER BY cs.case_took_up_time DESC
");

$stmt->bind_param("i", $center_id);
$stmt->execute();
$result = $stmt->get_result();

$cases = [];
while ($row = $result->fetch_assoc()) {
    $cases[] = [
        "status_id" => (int)$row['status_id'],
        "case_id" => (int)$row['case_id'],
        "photo" => $row['photo'],
        "rescued_photo" => $row['rescued_photo'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "case_took_up_time" => $row['case_took_up_time'],
        "rescue_status" => $row['rescue_status']
    ];
}

echo json_encode([
    "status" => "success",
    "total_closed" => count($cases),
    "cases" => $cases
]);
?>
