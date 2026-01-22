<?php
// Suppress PHP errors from appearing in output
error_reporting(0);
ini_set('display_errors', 0);

header("Content-Type: application/json");
include("db.php");

/* =========================
   RAZORPAY CONFIGURATION
   ========================= */
$razorpay_key_id = "rzp_test_DrASf34mihEAtB";

/* =========================
   REQUIRED INPUTS
   ========================= */
$donation_id = $_POST['donation_id'] ?? null;
$user_id = $_POST['user_id'] ?? null;

if (!$donation_id || !$user_id) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

/* =========================
   FETCH DONATION DETAILS
   ========================= */
$query = $conn->prepare("
    SELECT d.donation_id, d.amount, d.approval_status, d.donation_status,
           c.case_id, ct.center_name
    FROM donations d
    JOIN cases c ON d.case_id = c.case_id
    JOIN centers ct ON d.center_id = ct.center_id
    WHERE d.donation_id = ? AND d.approval_status = 'Approved'
");

if (!$query) {
    echo json_encode([
        "status" => "error", 
        "message" => "Database error"
    ]);
    exit;
}

$query->bind_param("i", $donation_id);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Donation not found or not approved"
    ]);
    exit;
}

$donation = $result->fetch_assoc();

if ($donation['donation_status'] === 'Paid') {
    echo json_encode([
        "status" => "error",
        "message" => "Donation already paid"
    ]);
    exit;
}

$amount_in_paise = intval($donation['amount'] * 100);
$receipt_id = "donation_" . $donation_id . "_" . time();

/* =========================
   SUCCESS - Return order details for Razorpay
   For testing: Generate a test order_id
   In production: Replace with actual Razorpay API call
   ========================= */
echo json_encode([
    "status" => "success",
    "order_id" => "order_test_" . time() . rand(1000, 9999),
    "amount" => floatval($donation['amount']),
    "amount_in_paise" => $amount_in_paise,
    "currency" => "INR",
    "key_id" => $razorpay_key_id,
    "donation_id" => intval($donation_id),
    "center_name" => $donation['center_name'],
    "receipt" => $receipt_id
]);
?>
