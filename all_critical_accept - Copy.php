<?php
header("Content-Type: application/json");
include("db.php");

/*
|---------------------------------------------------------
| GET ACCEPTED CRITICAL CASES
|---------------------------------------------------------
| - Only Critical cases
| - Only Accepted cases
| - Excludes Closed cases
| - Ignores Resent escalations
|---------------------------------------------------------
*/

$sql = "
SELECT
    c.case_id,
    c.user_id,
    c.latitude,
    c.longitude,
    c.created_time,
    c.status AS case_status,

    TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) AS case_age_minutes,

    ce.center_id AS accepted_center_id,
    ce.case_type,
    ce.remark,
    ce.responded_time

FROM cases c
INNER JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE c.status = 'Accepted'
  AND ce.case_type = 'Critical'
  AND ce.response = 'Accept'
  AND ce.remark != 'Resent'

ORDER BY c.created_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

$cases = [];

while ($row = $result->fetch_assoc()) {
    $cases[] = [
        "case_id" => (int)$row['case_id'],
        "user_id" => (int)$row['user_id'],
        "case_type" => $row['case_type'],
        "case_status" => $row['case_status'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "case_age_minutes" => (int)$row['case_age_minutes'],
        "accepted_center_id" => (int)$row['accepted_center_id'],
        "remark" => $row['remark'],
        "responded_time" => $row['responded_time']
    ];
}

echo json_encode([
    "status" => "success",
    "total_cases" => count($cases),
    "cases" => $cases
]);
