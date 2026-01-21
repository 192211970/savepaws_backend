<?php
header("Content-Type: application/json");
include("db.php");

$email = $_POST['email'] ?? null;
$otp   = $_POST['otp'] ?? null;

if (!$email || !$otp) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND otp = ? AND otp_expiry > NOW()");
$check->bind_param("si", $email, $otp);
$check->execute();

if ($check->get_result()->num_rows > 0) {
    echo json_encode(["status" => "success", "message" => "OTP Verified"]);
} else {
    echo json_encode(["status" => "error", "message" => "Invalid or expired OTP"]);
}
?>
