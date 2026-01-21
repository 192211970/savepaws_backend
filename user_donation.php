<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   REQUIRED INPUT
   ========================= */
$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "user_id is required"
    ]);
    exit;
}

/* =========================
   FETCH USER DONATION HISTORY
   ========================= */
$sql = "
SELECT
    d.donation_id,
    d.case_id,
    d.center_id,
    c.center_name,
    d.amount,
    d.payment_method,
    d.transaction_id,
    d.payment_time,
    d.donation_status
FROM donations d
JOIN centers c ON d.center_id = c.center_id
WHERE d.user_id = ?
AND d.donation_status = 'Paid'
ORDER BY d.payment_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

$donations = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
    $total_amount += $row['amount'];
}

/* =========================
   RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "user_id" => $user_id,
    "total_donations" => count($donations),
    "total_amount_donated" => $total_amount,
    "donations" => $donations
]);
?>