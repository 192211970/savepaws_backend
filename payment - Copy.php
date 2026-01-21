<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   REQUIRED INPUTS
   ========================= */
$donation_id     = $_POST['donation_id'] ?? null;
$user_id         = $_POST['user_id'] ?? null;
$transaction_id  = $_POST['transaction_id'] ?? null;
$payment_method  = $_POST['payment_method'] ?? null;
// payment_method → UPI | Card | NetBanking | Cash

if (!$donation_id || !$user_id || !$transaction_id || !$payment_method) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

/* =========================
   CHECK DONATION STATUS
   ========================= */
$check = $conn->prepare("
    SELECT donation_status
    FROM donations
    WHERE donation_id = ?
      AND approval_status = 'Approved'
");
$check->bind_param("i", $donation_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Donation not approved or invalid donation_id"
    ]);
    exit;
}

$row = $result->fetch_assoc();

if ($row['donation_status'] === 'Paid') {
    echo json_encode([
        "status" => "error",
        "message" => "Donation already completed"
    ]);
    exit;
}

/* =========================
   UPDATE DONATION PAYMENT
   ========================= */
$update = $conn->prepare("
    UPDATE donations
    SET donation_status = 'Paid',
        user_id = ?,
        transaction_id = ?,
        payment_method = ?,
        payment_time = NOW()
    WHERE donation_id = ?
");

$update->bind_param(
    "issi",
    $user_id,
    $transaction_id,
    $payment_method,
    $donation_id
);

if (!$update->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to complete donation"
    ]);
    exit;
}

/* =========================
   SUCCESS RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "message" => "Donation completed successfully"
]);
?>
