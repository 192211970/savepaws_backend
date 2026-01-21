<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   REQUIRED INPUT
   ========================= */
$center_id = $_GET['center_id'] ?? null;

if (!$center_id) {
    echo json_encode([
        "status" => "error",
        "message" => "center_id is required"
    ]);
    exit;
}

/* =========================
   FETCH PAID DONATIONS
   ========================= */
$sql = "
SELECT
    d.donation_id,
    d.case_id,
    d.user_id,
    d.amount,
    d.transaction_id,
    d.payment_method,
    d.payment_time
FROM donations d
WHERE d.center_id = ?
AND d.approval_status = 'Approved'
AND d.donation_status = 'Paid'
ORDER BY d.payment_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $center_id);
$stmt->execute();
$result = $stmt->get_result();

$donations = [];
$total_amount = 0;

while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
    $total_amount += (float)$row['amount'];
}

/* =========================
   RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "center_id" => $center_id,
    "total_amount_collected" => $total_amount,
    "total_donations" => count($donations),
    "donations" => $donations
]);
?>
