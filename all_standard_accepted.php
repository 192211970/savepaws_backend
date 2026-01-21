<?php
header("Content-Type: application/json");
include("db.php");

$sql = "
SELECT
    c.case_id,
    c.user_id,
    c.latitude,
    c.longitude,
    c.created_time,
    c.status AS case_status,

    TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) AS case_age_minutes,

    GROUP_CONCAT(
        DISTINCT ce.center_id ORDER BY ce.center_id
    ) AS centers_notified,

    'Standard' AS case_type,
    'Responded' AS escalation_status,
    'None' AS remark,

    MAX(
        CASE 
            WHEN ce.response = 'Accept' THEN ce.center_id
        END
    ) AS center_responded

FROM cases c
JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE
    c.status = 'Accepted'
    AND c.status != 'Closed'

    AND EXISTS (
        SELECT 1
        FROM case_escalations x
        WHERE x.case_id = c.case_id
          AND x.case_type = 'Standard'
          AND x.response = 'Accept'
          AND x.remark = 'None'
    )

    AND NOT EXISTS (
        SELECT 1
        FROM case_escalations y
        WHERE y.case_id = c.case_id
          AND y.remark IN ('Resent', 'Sent_again')
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
        "case_type" => "Standard",
        "case_status" => $row['case_status'],
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "case_age_minutes" => (int)$row['case_age_minutes'],
        "centers_notified" => $row['centers_notified']
            ? array_map('intval', explode(',', $row['centers_notified']))
            : [],
        "escalation_status" => "Responded",
        "remark" => "None",
        "center_responded" => (int)$row['center_responded']
    ];
}

echo json_encode([
    "status" => "success",
    "total_cases" => count($cases),
    "cases" => $cases
]);
