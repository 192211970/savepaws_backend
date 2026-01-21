<?php
header("Content-Type: application/json");
include("db.php");

$escalatedCases = [];

/*******************************************
 1️⃣ FETCH ALL DELAYED CASES (> 60 minutes)
********************************************/
$caseQuery = $conn->prepare("
    SELECT case_id, user_id, latitude, longitude
    FROM cases
    WHERE status = 'Reported'
      AND TIMESTAMPDIFF(MINUTE, created_time, NOW()) >= 60
");
$caseQuery->execute();
$caseResult = $caseQuery->get_result();

if ($caseResult->num_rows === 0) {
    echo json_encode([
        "status" => "success",
        "message" => "No delayed cases"
    ]);
    exit;
}

while ($case = $caseResult->fetch_assoc()) {

    $case_id = $case['case_id'];
    $user_id = $case['user_id'];
    $lat     = $case['latitude'];
    $lng     = $case['longitude'];

    /*******************************************
     2️⃣ FIND NEAREST ACTIVE CENTER
    ********************************************/
    $centerQuery = $conn->prepare("
        SELECT center_id,
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(latitude))
        )) AS distance
        FROM centers
        WHERE is_active = 1
        ORDER BY distance ASC
        LIMIT 1
    ");
    $centerQuery->bind_param("ddd", $lat, $lng, $lat);
    $centerQuery->execute();
    $center = $centerQuery->get_result()->fetch_assoc();

    if (!$center) continue;

    $center_id = $center['center_id'];

    /*******************************************
     3️⃣ UPDATE OLD REMARKS → RESENT
        Delayed → Resent
        Sent_again → Resent
    ********************************************/
    $updateOld = $conn->prepare("
        UPDATE case_escalations
        SET remark = 'Resent'
        WHERE case_id = ?
          AND remark IN ('Delayed', 'Sent_again')
    ");
    $updateOld->bind_param("i", $case_id);
    $updateOld->execute();

    /*******************************************
     4️⃣ INSERT NEW ESCALATION (Sent_again)
    ********************************************/
    $insertEsc = $conn->prepare("
        INSERT INTO case_escalations
        (
            user_id,
            case_id,
            center_id,
            status,
            response,
            rejected_reason,
            remark,
            case_type,
            assigned_time
        )
        VALUES
        (?, ?, ?, 'Pending', NULL, NULL, 'Sent_again', 'Critical', NOW())
    ");
    $insertEsc->bind_param("iii", $user_id, $case_id, $center_id);
    $insertEsc->execute();

    $escalatedCases[] = $case_id;
}

/*******************************************
 5️⃣ FINAL RESPONSE
********************************************/
echo json_encode([
    "status" => "success",
    "message" => "Delayed cases escalated successfully",
    "escalated_cases" => array_values(array_unique($escalatedCases))
]);
?>
