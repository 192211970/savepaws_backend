<?php
header("Content-Type: application/json");
include("db.php");

/*
|--------------------------------------------------------------------------
| ADMIN – ACTIVE CASES OVERVIEW (FINAL LOGIC)
|--------------------------------------------------------------------------
| Rules:
| - Ignore Closed cases
| - Ignore Resent rows
| - One response per case
| - Sent_again / Delayed / Rejected_by_all => Critical
| - Accept response has highest priority
|--------------------------------------------------------------------------
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

    /* All centers notified (ignore Resent rows) */
    GROUP_CONCAT(
        DISTINCT CASE
            WHEN ce.remark != 'Resent' THEN ce.center_id
        END
        ORDER BY ce.center_id
    ) AS centers_notified,

    /* Escalation status */
    CASE
        WHEN SUM(ce.response = 'Accept') > 0 THEN 'Responded'
        ELSE 'Pending'
    END AS escalation_status,

    /* Center responded */
    MAX(
        CASE
            WHEN ce.response = 'Accept' THEN ce.center_id
            ELSE NULL
        END
    ) AS center_responded,

    /* Case type (Critical overrides Standard) */
    CASE
    WHEN COUNT(DISTINCT ce.center_id) = 1 THEN 'Critical'
    WHEN SUM(ce.remark = 'Sent_again' AND ce.remark != 'Resent') > 0 THEN 'Critical'
    WHEN SUM(ce.remark = 'Delayed' AND ce.remark != 'Resent') > 0 THEN 'Critical'
    WHEN SUM(ce.remark = 'Rejected_by_all' AND ce.remark != 'Resent') > 0 THEN 'Critical'
    ELSE 'Standard'
END AS case_type,

    /* Highest priority remark */
   CASE
    WHEN SUM(ce.remark = 'Sent_again' AND ce.remark != 'Resent') > 0 THEN 'Sent_again'
    WHEN SUM(ce.remark = 'Rejected_by_all' AND ce.remark != 'Resent') > 0 THEN 'Rejected_by_all'
    WHEN SUM(ce.remark = 'Delayed' AND ce.remark != 'Resent') > 0 THEN 'Delayed'
    ELSE 'None'
END AS remark


FROM cases c
LEFT JOIN case_escalations ce
    ON c.case_id = ce.case_id

WHERE c.status != 'Closed'

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

        "escalation_status" => $row['escalation_status'],
        "remark" => $row['remark'],

        "center_responded" => $row['center_responded']
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
