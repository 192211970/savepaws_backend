<?php
header("Content-Type: application/json");
include("db.php");

$email = $_POST['email'] ?? null;
$newPass = $_POST['new_password'] ?? null;

if (!$email || !$newPass) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Reset password and clear OTP
$update = $conn->prepare("UPDATE users SET password = ?, otp = NULL, otp_expiry = NULL WHERE email = ?");
$update->bind_param("ss", $newPass, $email);

if ($update->execute()) {
    echo json_encode(["status" => "success", "message" => "Password reset successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to reset password"]);
}
?>
