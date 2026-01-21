<?php
header("Content-Type: application/json");
include("db.php");

$donation_id = $_GET['donation_id'] ?? null;

if (!$donation_id) {
    echo json_encode([
        "success" => false,
        "message" => "donation_id is required"
    ]);
    exit;
}

// Get donation details
$query = $conn->prepare("
    SELECT 
        d.donation_id,
        d.center_id,
        d.case_id,
        d.image_of_animal,
        d.amount,
        d.requested_time,
        d.approval_status,
        d.donation_status,
        d.user_id,
        d.payment_time,
        d.payment_method,
        d.transaction_id,
        c.center_name,
        c.phone as center_phone,
        u.name as donor_name,
        cs.type_of_animal,
        cs.photo as case_photo
    FROM donations d
    LEFT JOIN centers c ON d.center_id = c.center_id
    LEFT JOIN users u ON d.user_id = u.id
    LEFT JOIN cases cs ON d.case_id = cs.case_id
    WHERE d.donation_id = ?
");

$query->bind_param("i", $donation_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Donation not found"
    ]);
    exit;
}

$row = $result->fetch_assoc();

echo json_encode([
    "success" => true,
    "donation" => [
        "donation_id" => (int)$row['donation_id'],
        "center_id" => (int)$row['center_id'],
        "case_id" => (int)$row['case_id'],
        "center_name" => $row['center_name'],
        "center_phone" => $row['center_phone'],
        "amount" => (float)$row['amount'],
        "image_of_animal" => $row['image_of_animal'],
        "case_photo" => $row['case_photo'],
        "type_of_animal" => $row['type_of_animal'],
        "requested_time" => $row['requested_time'],
        "approval_status" => $row['approval_status'],
        "donation_status" => $row['donation_status'],
        "user_id" => $row['user_id'] ? (int)$row['user_id'] : null,
        "donor_name" => $row['donor_name'],
        "payment_time" => $row['payment_time'],
        "payment_method" => $row['payment_method'],
        "transaction_id" => $row['transaction_id']
    ]
]);

$conn->close();
?>
