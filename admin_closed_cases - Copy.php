<?php
header("Content-Type: application/json");
include("db.php");

/*
|--------------------------------------------------------------------------
| ADMIN – CLOSED CASES
|--------------------------------------------------------------------------
| From case_status table: cases with status = 'Closed'
|--------------------------------------------------------------------------
*/

$sql = "
SELECT
    cs.case_id,
    cs.center_id,
    cs.case_took_up_time,
    cs.status AS rescue_status,
    cs.rescued_animal,
    cs.rescue_photo,
    cs.closed_time,
    c.type_of_animal,
    c.animal_condition,
    c.photo AS original_photo,
    c.latitude,
    c.longitude,
    c.created_time,
    ctr.center_name
FROM case_status cs
JOIN cases c ON cs.case_id = c.case_id
LEFT JOIN centers ctr ON cs.center_id = ctr.center_id
WHERE cs.status = 'Closed'
ORDER BY cs.closed_time DESC
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
        "closed_time" => $row['closed_time'],
        "rescue_status" => $row['rescue_status'],
        "rescued_animal" => $row['rescued_animal'],
        "rescued_photo" => $row['rescue_photo'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "original_photo" => $row['original_photo'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time']
    ];
}

echo json_encode([
    "success" => true,
    "total_closed" => count($cases),
    "cases" => $cases
]);
?>
