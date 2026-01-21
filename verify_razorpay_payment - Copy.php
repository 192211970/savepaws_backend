<?php
// Suppress PHP errors from appearing in output
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
include("db.php");

/* =========================
   RAZORPAY CONFIGURATION
   ========================= */
$razorpay_key_secret = "X5G49Knpzl2XT0wG0O781nzh";

/* =========================
   REQUIRED INPUTS
   ========================= */
$donation_id = $_POST['donation_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? null;
$razorpay_order_id = $_POST['razorpay_order_id'] ?? null;
$razorpay_signature = $_POST['razorpay_signature'] ?? null;
$payment_method = $_POST['payment_method'] ?? 'Razorpay';

if (!$donation_id || !$user_id || !$razorpay_payment_id || !$razorpay_order_id || !$razorpay_signature) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

/* =========================
   VERIFY RAZORPAY SIGNATURE
   ========================= */
$generated_signature = hash_hmac('sha256', $razorpay_order_id . "|" . $razorpay_payment_id, $razorpay_key_secret);

if ($generated_signature !== $razorpay_signature) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid payment signature"
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
    $razorpay_payment_id,
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
    "message" => "Donation completed successfully",
    "transaction_id" => $razorpay_payment_id
]);
?>
