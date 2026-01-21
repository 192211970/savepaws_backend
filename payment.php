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

/* =================================================
   🔔 NOTIFICATIONS (Center: Paid, User: Thank You)
   ================================================= */
include_once 'send_notification.php';

// 1. Notify Center
$cQ = $conn->prepare("
    SELECT u.fcm_token, d.amount 
    FROM donations d 
    JOIN centers c ON d.center_id = c.center_id 
    LEFT JOIN users u ON c.email = u.email 
    WHERE d.donation_id = ?
");
$cQ->bind_param("i", $donation_id);
$cQ->execute();
if ($cRow = $cQ->get_result()->fetch_assoc()) {
    if (!empty($cRow['fcm_token'])) {
        sendNotification($cRow['fcm_token'], "Donation Received!", "You received ₹" . $cRow['amount'] . " for Donation #$donation_id");
    }
}

// 2. Notify User (Donor)
$uQ = $conn->prepare("SELECT fcm_token FROM users WHERE id = ?");
$uQ->bind_param("i", $user_id);
$uQ->execute();
if ($uRow = $uQ->get_result()->fetch_assoc()) {
    if (!empty($uRow['fcm_token'])) {
        sendNotification($uRow['fcm_token'], "Payment Successful", "Thank you! Your donation was successful.");
    }
}

/* =========================
   SUCCESS RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "message" => "Donation completed successfully"
]);
?>
