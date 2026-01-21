<?php
header("Content-Type: application/json");
include("db.php");

/*
|---------------------------------------------------------
| GET PENDING CRITICAL CASES (NOT ACCEPTED BY ANY CENTER)
|---------------------------------------------------------
| - cases.status = Reported
| - case_type = Critical
| - NO Accept response exists
| - escalation still Pending
| - ignores Resent rows
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

    GROUP_CONCAT(DISTINCT ce.center_id ORDER BY ce.center_id) AS centers_notified,
    MAX(ce.case_type) AS case_type,
    MAX(ce.remark) AS remark

FROM cases c
JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE c.status = 'Reported'
  AND ce.case_type = 'Critical'
  AND ce.status = 'Pending'
  AND ce.remark != 'Resent'

  -- ensure NO center accepted this case
  AND NOT EXISTS (
        SELECT 1
        FROM case_escalations x
        WHERE x.case_id = c.case_id
          AND x.response = 'Accept'
  )

GROUP BY c.case_id
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
        "centers_notified" => $row['centers_notified']
            ? array_map('intval', explode(',', $row['centers_notified']))
            : [],
        "escalation_status" => "Pending",
        "remark" => $row['remark'] ?? "None",
        "center_responded" => "waiting for response"
    ];
}

echo json_encode([
    "status" => "success",
    "total_cases" => count($cases),
    "cases" => $cases
]);
