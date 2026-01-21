<?php
header("Content-Type: application/json");
include("db.php");

/*
|--------------------------------------------------------------------------
| ADMIN – IN PROGRESS CASES (Accepted but not closed)
|--------------------------------------------------------------------------
| From case_status table: cases with acceptance_status = 'Accepted' and status != 'Closed'
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    cs.case_id,
    cs.center_id,
    cs.case_took_up_time,
    cs.acceptance_status,
    cs.reached_location,
    cs.spot_animal,
    cs.rescued_animal,
    cs.rescue_photo,
    cs.status AS rescue_status,
    c.type_of_animal,
    c.animal_condition,
    c.photo,
    c.latitude,
    c.longitude,
    c.created_time,
    ctr.center_name
FROM case_status cs
JOIN cases c ON cs.case_id = c.case_id
LEFT JOIN centers ctr ON cs.center_id = ctr.center_id
WHERE cs.acceptance_status = 'Accepted'
  AND cs.status != 'Closed'
ORDER BY cs.case_took_up_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$cases = [];

while ($row = $result->fetch_assoc()) {
    $cases[] = [
        "case_id" => (int)$row['case_id'],
        "center_id" => (int)$row['center_id'],
        "center_name" => $row['center_name'],
        "case_took_up_time" => $row['case_took_up_time'],
        "rescue_status" => $row['rescue_status'],
        "reached_location" => $row['reached_location'],
        "spot_animal" => $row['spot_animal'],
        "rescued_animal" => $row['rescued_animal'],
        "rescue_photo" => $row['rescue_photo'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "photo" => $row['photo'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time']
    ];
}

echo json_encode([
    "success" => true,
    "total_in_progress" => count($cases),
    "cases" => $cases
]);
?>
