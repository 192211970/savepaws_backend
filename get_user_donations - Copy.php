<?php
header("Content-Type: application/json");
include("db.php");

$user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit;
}

// Get all donations made by user
$query = $conn->prepare("
    SELECT 
        d.donation_id,
        d.case_id,
        d.image_of_animal,
        c.photo AS case_photo,
        d.amount,
        d.requested_time,
        d.payment_method,
        d.transaction_id,
        d.payment_time,
        d.donation_status,
        c.type_of_animal,
        cnt.center_name
    FROM donations d
    JOIN cases c ON d.case_id = c.case_id
    JOIN centers cnt ON d.center_id = cnt.center_id
    WHERE d.user_id = ?
    ORDER BY d.payment_time DESC
");

$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();

$donations = [];
$totalAmount = 0;

while ($row = $result->fetch_assoc()) {
    // Use case photo if donation image is not available
    $image = !empty($row['image_of_animal']) ? $row['image_of_animal'] : $row['case_photo'];
    
    $donations[] = [
        "donation_id" => (int)$row['donation_id'],
        "case_id" => (int)$row['case_id'],
        "image" => $image,
        "amount" => (float)$row['amount'],
        "animal_type" => $row['type_of_animal'],
        "center_name" => $row['center_name'],
        "payment_method" => $row['payment_method'],
        "transaction_id" => $row['transaction_id'],
        "payment_time" => $row['payment_time'],
        "status" => $row['donation_status'] ?? "Pending"
    ];
    
    if ($row['donation_status'] === 'Paid') {
        $totalAmount += (float)$row['amount'];
    }
}

echo json_encode([
    "success" => true,
    "total_donations" => count($donations),
    "total_amount" => $totalAmount,
    "donations" => $donations
]);
?>
