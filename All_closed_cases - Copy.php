<?php
header("Content-Type: application/json");
include("db.php");

/*
|---------------------------------------------------------
| ADMIN – CLOSED CASES
|---------------------------------------------------------
| - cases.status = Closed
| - final snapshot of case
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

    TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) AS total_case_duration,

    GROUP_CONCAT(
        DISTINCT ce.center_id ORDER BY ce.center_id
    ) AS centers_notified,

    MAX(ce.case_type) AS case_type,
    MAX(ce.remark) AS remark,

    MAX(
        CASE
            WHEN ce.response = 'Accept' THEN ce.center_id
            ELSE NULL
        END
    ) AS center_responded

FROM cases c
LEFT JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE c.status = 'Closed'

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
        "case_type" => $row['case_type'] ?? "Unknown",
        "case_status" => "Closed",
        "latitude" => $row['latitude'],
        "longitude" => $row['longitude'],
        "created_time" => $row['created_time'],
        "total_case_duration_minutes" => (int)$row['total_case_duration'],

        "centers_notified" => $row['centers_notified']
            ? array_map('intval', explode(',', $row['centers_notified']))
            : [],

        "remark" => $row['remark'] ?? "None",

        "center_responded" => $row['center_responded']
            ? (int)$row['center_responded']
            : "Not available"
    ];
}

echo json_encode([
    "status" => "success",
    "total_cases" => count($cases),
    "cases" => $cases
]);
