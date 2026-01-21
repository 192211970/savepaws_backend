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

// Get pending cases for this center (only None and Sent_again remarks)
$stmt = $conn->prepare("
    SELECT 
        ce.escalation_id,
        ce.case_id,
        ce.case_type,
        ce.assigned_time,
        ce.remark,
        c.photo,
        c.type_of_animal,
        c.animal_condition,
        c.latitude,
        c.longitude,
        c.created_time,
        TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) AS case_age_minutes
    FROM case_escalations ce
    JOIN cases c ON ce.case_id = c.case_id
    WHERE ce.center_id = ?
      AND ce.status = 'Pending'
      AND ce.remark IN ('None', 'Sent_again')
      AND c.status = 'Reported'
    ORDER BY 
        CASE WHEN ce.case_type = 'Critical' THEN 0 ELSE 1 END,
        ce.assigned_time ASC
");

$stmt->bind_param("i", $center_id);
$stmt->execute();
$result = $stmt->get_result();

$cases = [];
while ($row = $result->fetch_assoc()) {
    $cases[] = [
        "escalation_id" => (int)$row['escalation_id'],
        "case_id" => (int)$row['case_id'],
        "case_type" => $row['case_type'],
        "photo" => $row['photo'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "assigned_time" => $row['assigned_time'],
        "case_age_minutes" => (int)$row['case_age_minutes'],
        "remark" => $row['remark']
    ];
}

// Get counts
$criticalCount = count(array_filter($cases, fn($c) => $c['case_type'] === 'Critical'));
$standardCount = count(array_filter($cases, fn($c) => $c['case_type'] === 'Standard'));

echo json_encode([
    "status" => "success",
    "total_pending" => count($cases),
    "critical_count" => $criticalCount,
    "standard_count" => $standardCount,
    "cases" => $cases
]);
?>
