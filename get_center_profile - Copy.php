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
    SELECT center_id, center_name, phone, email, address, is_active, created_at
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

// Get total cases handled (closed cases)
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

// Get total donations received (paid donations)
$donationsQuery = $conn->prepare("
    SELECT COUNT(*) as total_donations, COALESCE(SUM(amount), 0) as total_amount
    FROM donations
    WHERE center_id = ? 
      AND approval_status = 'Approved'
      AND donation_status = 'Paid'
");
$donationsQuery->bind_param("i", $center_id);
$donationsQuery->execute();
$donationsResult = $donationsQuery->get_result()->fetch_assoc();

// Get list of handled cases (closed)
$handledCasesQuery = $conn->prepare("
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
    LIMIT 50
");
$handledCasesQuery->bind_param("i", $center_id);
$handledCasesQuery->execute();
$handledCasesResult = $handledCasesQuery->get_result();

$handledCases = [];
while ($row = $handledCasesResult->fetch_assoc()) {
    $handledCases[] = [
        "case_id" => (int)$row['case_id'],
        "type_of_animal" => $row['type_of_animal'],
        "animal_condition" => $row['animal_condition'],
        "photo" => $row['photo'],
        "case_took_up_time" => $row['case_took_up_time'],
        "rescue_status" => $row['rescue_status']
    ];
}

// Get list of received donations (paid)
$receivedDonationsQuery = $conn->prepare("
    SELECT 
        d.donation_id,
        d.case_id,
        d.amount,
        d.payment_time,
        d.payment_method,
        d.transaction_id,
        u.name as donor_name
    FROM donations d
    LEFT JOIN users u ON d.user_id = u.id
    WHERE d.center_id = ?
      AND d.approval_status = 'Approved'
      AND d.donation_status = 'Paid'
    ORDER BY d.payment_time DESC
    LIMIT 50
");
$receivedDonationsQuery->bind_param("i", $center_id);
$receivedDonationsQuery->execute();
$receivedDonationsResult = $receivedDonationsQuery->get_result();

$receivedDonations = [];
while ($row = $receivedDonationsResult->fetch_assoc()) {
    $receivedDonations[] = [
        "donation_id" => (int)$row['donation_id'],
        "case_id" => (int)$row['case_id'],
        "amount" => (float)$row['amount'],
        "payment_time" => $row['payment_time'],
        "payment_method" => $row['payment_method'],
        "transaction_id" => $row['transaction_id'],
        "donor_name" => $row['donor_name']
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
        "is_active" => $center['is_active'],
        "member_since" => $center['created_at']
    ],
    "stats" => [
        "total_cases_handled" => (int)$casesResult['total_handled'],
        "total_donations" => (int)$donationsResult['total_donations'],
        "total_amount_received" => (float)$donationsResult['total_amount']
    ],
    "handled_cases" => $handledCases,
    "received_donations" => $receivedDonations
]);
?>
