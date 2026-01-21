<?php
header("Content-Type: application/json");
include("db.php");

/*
|---------------------------------------------------------
| ADMIN – UN-CLOSED STANDARD CASES
| - Excludes Closed cases
| - Excludes cases with remark = 'Resent'
| - Correct escalation & response detection
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

    CASE
        WHEN SUM(ce.status = 'Responded') > 0 THEN 'Responded'
        ELSE 'Pending'
    END AS escalation_status,

    MAX(ce.remark) AS remark,

    MAX(
        CASE
            WHEN ce.response = 'Accept' THEN ce.center_id
            ELSE NULL
        END
    ) AS center_responded

FROM cases c
JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE c.status != 'Closed'
  AND ce.case_type = 'Standard'
  AND ce.remark != 'Resent'

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
        "case_type" => "Standard",
        "case_status" => $row['case_status'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "case_age_minutes" => (int)$row['case_age_minutes'],

        "centers_notified" => $row['centers_notified']
            ? array_map('intval', explode(',', $row['centers_notified']))
            : [],

        "escalation_status" => $row['escalation_status'],
        "remark" => $row['remark'] ?? "None",

        "center_responded" => $row['center_responded'] !== null
            ? (int)$row['center_responded']
            : "waiting for response"
    ];
}

echo json_encode([
    "status" => "success",
    "total_cases" => count($cases),
    "cases" => $cases
]);
?>
