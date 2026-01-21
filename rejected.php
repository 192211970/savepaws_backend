<?php
header("Content-Type: application/json");
include("db.php");

$escalatedCases = [];

/***************************************
 1️⃣ Fetch unique rejected-by-all cases
****************************************/
$rejectedQuery = $conn->prepare("
    SELECT DISTINCT case_id
    FROM case_escalations
    WHERE remark = 'Rejected_by_all'
");
$rejectedQuery->execute();
$rejectedCases = $rejectedQuery->get_result();

if ($rejectedCases->num_rows === 0) {
    echo json_encode([
        "status" => "success",
        "message" => "No rejected-by-all cases found"
    ]);
    exit;
}

/***************************************
 2️⃣ Process each rejected case
****************************************/
while ($row = $rejectedCases->fetch_assoc()) {

    $case_id = $row['case_id'];

    // 🔒 Skip if already sent again
    $checkSent = $conn->prepare("
        SELECT 1 FROM case_escalations
        WHERE case_id = ? AND remark = 'Sent_again'
        LIMIT 1
    ");
    $checkSent->bind_param("i", $case_id);
    $checkSent->execute();
    if ($checkSent->get_result()->num_rows > 0) {
        continue;
    }

    // Get case location & user
    $caseInfo = $conn->prepare("
        SELECT user_id, latitude, longitude
        FROM cases
        WHERE case_id = ?
    ");
    $caseInfo->bind_param("i", $case_id);
    $caseInfo->execute();
    $case = $caseInfo->get_result()->fetch_assoc();

    if (!$case) continue;

    $user_id = $case['user_id'];
    $lat = $case['latitude'];
    $lng = $case['longitude'];

    /***************************************
     3️⃣ Find best-performing center (≤10km)
    ****************************************/
    $centerQuery = $conn->prepare("
        SELECT center_id, total_cases_handled,
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        )) AS distance
        FROM centers
        WHERE is_active = 1
        HAVING distance <= 25
        ORDER BY total_cases_handled DESC, distance ASC
        LIMIT 1
    ");
    $centerQuery->bind_param("ddd", $lat, $lng, $lat);
    $centerQuery->execute();
    $center = $centerQuery->get_result()->fetch_assoc();

    if (!$center) continue;

    $center_id = $center['center_id'];

    /***************************************
     4️⃣ Mark old rejected rows as Resent
    ****************************************/
    $updateOld = $conn->prepare("
        UPDATE case_escalations
        SET remark = 'Resent'
        WHERE case_id = ? AND remark = 'Rejected_by_all'
    ");
    $updateOld->bind_param("i", $case_id);
    $updateOld->execute();

    /***************************************
     5️⃣ Insert new escalation (Sent_again)
    ****************************************/
    $insert = $conn->prepare("
        INSERT INTO case_escalations
        (user_id, case_id, center_id, status, response, rejected_reason,
         remark, case_type, assigned_time)
        VALUES (?, ?, ?, 'Pending', NULL, NULL,
                'Sent_again', 'Critical', NOW())
    ");
    $insert->bind_param("iii", $user_id, $case_id, $center_id);
    $insert->execute();

    $escalatedCases[] = $case_id;
}

/***************************************
 Final Response
****************************************/
echo json_encode([
    "status" => "success",
    "rejected_cases_escalated" => $escalatedCases
]);
