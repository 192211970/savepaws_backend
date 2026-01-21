<?php
header("Content-Type: application/json");
include("db.php");

/*
|--------------------------------------------------------------------------
| ADMIN – PENDING CASES
|--------------------------------------------------------------------------
| Fetch pending cases based on TWO conditions:
| 1. cases table: status = 'Reported'
| 2. case_escalations table: remark = 'None' OR 'Sent_again'
|
| Categorization:
|   - Critical = case_type = 'Critical' in case_escalations
|   - Standard = case_type = 'Standard' in case_escalations
|--------------------------------------------------------------------------
*/

$sql = "
SELECT DISTINCT
    c.case_id,
    c.user_id,
    c.type_of_animal,
    c.animal_condition,
    c.photo,
    c.latitude,
    c.longitude,
    c.created_time,
    c.status,
    
    /* Get the remark (prioritize Sent_again if any escalation has it) */
    (SELECT 
        CASE 
            WHEN MAX(ce2.remark = 'Sent_again') = 1 THEN 'Sent_again'
            ELSE 'None'
        END
     FROM case_escalations ce2 
     WHERE ce2.case_id = c.case_id
       AND ce2.remark IN ('None', 'Sent_again')
    ) AS remark,

    /* Get the actual case_type from case_escalations */
    (SELECT MAX(ce3.case_type)
     FROM case_escalations ce3 
     WHERE ce3.case_id = c.case_id
       AND ce3.remark IN ('None', 'Sent_again')
    ) AS case_type,

    /* Get center_ids this case was escalated to */
    (SELECT GROUP_CONCAT(DISTINCT ce4.center_id ORDER BY ce4.center_id)
     FROM case_escalations ce4 
     WHERE ce4.case_id = c.case_id
       AND ce4.remark IN ('None', 'Sent_again')
    ) AS centers_escalated,

    /* Calculate case age in minutes */
    TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) AS case_age_minutes

FROM cases c
INNER JOIN case_escalations ce ON ce.case_id = c.case_id
WHERE c.status = 'Reported'
  AND ce.remark IN ('None', 'Sent_again')
GROUP BY c.case_id
ORDER BY c.created_time DESC
";

$result = $conn->query($sql);

$critical_cases = [];
$standard_cases = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Get the actual case_type from database, default to 'Standard' if null
        $actualCaseType = $row['case_type'] ?? 'Standard';
        
        $case = [
            "case_id" => (int)$row['case_id'],
            "user_id" => (int)$row['user_id'],
            "type_of_animal" => $row['type_of_animal'],
            "animal_condition" => $row['animal_condition'],
            "photo" => $row['photo'],
            "latitude" => $row['latitude'],
            "longitude" => $row['longitude'],
            "created_time" => $row['created_time'],
            "case_age_minutes" => (int)$row['case_age_minutes'],
            "remark" => $row['remark'],
            "case_type" => $actualCaseType,
            "centers_escalated" => $row['centers_escalated']
                ? array_map('intval', explode(',', $row['centers_escalated']))
                : []
        ];

        // Categorize based on actual case_type field from database
        if ($actualCaseType === 'Critical') {
            $critical_cases[] = $case;
        } else {
            $standard_cases[] = $case;
        }
    }
}

echo json_encode([
    "success" => true,
    "total_pending" => count($critical_cases) + count($standard_cases),
    "critical_count" => count($critical_cases),
    "standard_count" => count($standard_cases),
    "critical_cases" => $critical_cases,
    "standard_cases" => $standard_cases
]);
?>
