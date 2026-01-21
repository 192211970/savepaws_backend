<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   FETCH ONLY PENDING DONATIONS
   ========================= */
$sql = "
SELECT 
    d.donation_id,
    d.case_id,
    d.center_id,
    c.center_name,
    d.image_of_animal,
    d.amount,
    d.requested_time,
    d.approval_status,
    d.donation_status
FROM donations d
JOIN centers c ON d.center_id = c.center_id
WHERE d.approval_status = 'Pending'
ORDER BY d.requested_time DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$result = $stmt->get_result();
$donations = [];

while ($row = $result->fetch_assoc()) {
    $donations[] = $row;
}

echo json_encode([
    "status" => "success",
    "total" => count($donations),
    "donations" => $donations
]);
?>
