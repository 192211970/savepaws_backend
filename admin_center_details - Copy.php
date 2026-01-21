<?php
header("Content-Type: application/json");
include("db.php");

$center_id = $_GET['center_id'] ?? null;

if (!$center_id) {
    echo json_encode([
        "success" => false,
        "message" => "center_id is required"
    ]);
    exit;
}

// Get center details
$centerQuery = $conn->prepare("
    SELECT 
        center_id, 
        center_name, 
        phone, 
        email, 
        address, 
        latitude,
        longitude,
        is_active, 
        center_status,
        total_cases_handled,
        avg_response_time,
        created_at
    FROM centers
    WHERE center_id = ?
");
$centerQuery->bind_param("i", $center_id);
$centerQuery->execute();
$centerResult = $centerQuery->get_result();

if ($centerResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Center not found"
    ]);
    exit;
}

$center = $centerResult->fetch_assoc();

// Get count of cases handled (closed cases)
$casesQuery = $conn->prepare("
    SELECT COUNT(*) as total_handled
    FROM case_status cs
    WHERE cs.center_id = ?
      AND cs.acceptance_status = 'Accepted'
      AND cs.status = 'Closed'
");
$casesQuery->bind_param("i", $center_id);
$casesQuery->execute();
$casesResult = $casesQuery->get_result()->fetch_assoc();

// Get count of donations and total amount
$donationsQuery = $conn->prepare("
    SELECT 
        COUNT(*) as donation_count,
        COALESCE(SUM(amount), 0) as total_amount
    FROM donations
    WHERE center_id = ?
      AND approval_status = 'Approved'
      AND donation_status = 'Paid'
");
$donationsQuery->bind_param("i", $center_id);
$donationsQuery->execute();
$donationsResult = $donationsQuery->get_result()->fetch_assoc();

// Get recent handled cases (limit 10)
$recentCasesQuery = $conn->prepare("
    SELECT 
        cs.case_id,
        c.type_of_animal,
        c.animal_condition,
        c.photo,
        cs.case_took_up_time,
        cs.status as rescue_status
    FROM case_status cs
    JOIN cases c ON cs.case_id = c.case_id
    WHERE cs.center_id = ?
      AND cs.acceptance_status = 'Accepted'
      AND cs.status = 'Closed'
    ORDER BY cs.case_took_up_time DESC
    LIMIT 10
");
$recentCasesQuery->bind_param("i", $center_id);
$recentCasesQuery->execute();
$recentCasesResult = $recentCasesQuery->get_result();

$recentCases = [];
while ($row = $recentCasesResult->fetch_assoc()) {
    $recentCases[] = [
        "case_id" => (int)$row['case_id'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "photo" => $row['photo'],
        "case_took_up_time" => $row['case_took_up_time'],
        "rescue_status" => $row['rescue_status']
    ];
}

echo json_encode([
    "success" => true,
    "center" => [
        "center_id" => (int)$center['center_id'],
        "center_name" => $center['center_name'],
        "phone" => $center['phone'],
        "email" => $center['email'],
        "address" => $center['address'],
        "latitude" => $center['latitude'],
        "longitude" => $center['longitude'],
        "is_active" => $center['is_active'],
        "center_status" => $center['center_status'],
        "avg_response_time" => (int)$center['avg_response_time'],
        "member_since" => $center['created_at']
    ],
    "stats" => [
        "cases_handled" => (int)$casesResult['total_handled'],
        "donation_count" => (int)$donationsResult['donation_count'],
        "total_donation_amount" => (float)$donationsResult['total_amount']
    ],
    "recent_cases" => $recentCases
]);

$conn->close();
?>
