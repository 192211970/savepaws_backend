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
   FETCH ALL DONATIONS FOR CENTER
   (Pending + Approved + Rejected)
   ========================= */
$sql = "
SELECT
    d.donation_id,
    d.case_id,
    d.amount,
    d.image_of_animal,
    d.requested_time,
    d.approval_status,
    d.donation_status,
    d.user_id,
    d.transaction_id,
    d.payment_method,
    d.payment_time
FROM donations d
WHERE d.center_id = ?
ORDER BY d.requested_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $center_id);
$stmt->execute();

$result = $stmt->get_result();
$donations = [];

while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}

/* =========================
   RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "center_id" => $center_id,
    "total" => count($donations),
    "donations" => $donations
]);
?>