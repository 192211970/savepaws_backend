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

// Get accepted/in-progress cases for this center (exclude closed)
$stmt = $conn->prepare("
    SELECT 
        cs.status_id,
        cs.case_id,
        cs.acceptance_status,
        cs.case_took_up_time,
        cs.reached_location,
        cs.spot_animal,
        cs.rescue_photo,
        cs.status AS rescue_status,
        c.photo,
        c.type_of_animal,
        c.animal_condition,
        c.latitude,
        c.longitude,
        c.created_time
    FROM case_status cs
    JOIN cases c ON cs.case_id = c.case_id
    WHERE cs.center_id = ?
      AND cs.acceptance_status = 'Accepted'
      AND cs.status != 'Closed'
    ORDER BY 
        cs.case_took_up_time DESC
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
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "case_took_up_time" => $row['case_took_up_time'],
        "reached_location" => $row['reached_location'],
        "spot_animal" => $row['spot_animal'],
        "rescue_photo" => $row['rescue_photo'],
        "rescue_status" => $row['rescue_status']
    ];
}

// Get counts
$inProgressCount = 0;
$closedCount = 0;
foreach ($cases as $c) {
    if ($c['rescue_status'] === 'Inprogress') {
        $inProgressCount++;
    }
    if ($c['rescue_status'] === 'Closed') {
        $closedCount++;
    }
}

echo json_encode([
    "status" => "success",
    "total_accepted" => count($cases),
    "in_progress_count" => $inProgressCount,
    "closed_count" => $closedCount,
    "cases" => $cases
]);
?>
