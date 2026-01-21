<?php
header("Content-Type: application/json");
include("db.php");

$email = $_POST['email'] ?? null;

if (!$email) {
    echo json_encode(["status" => "error", "message" => "Email required"]);
    exit;
}

// Check email exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
if ($check->get_result()->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Email not registered"]);
    exit;
}

// Generate 4-digit OTP
$otp = rand(1000, 9999);

// Store OTP in DB
$update = $conn->prepare("UPDATE users SET otp = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE email = ?");
$update->bind_param("is", $otp, $email);

if ($update->execute()) {
    $subject = "SavePaws Password Reset OTP";
    $message = "Your OTP for resetting your password is: " . $otp . "\n\nThis OTP is valid for 10 minutes.";
    $headers = "From: no-reply@savepaws.com\r\n" .
               "Reply-To: no-reply@savepaws.com\r\n" .
               "X-Mailer: PHP/" . phpversion();

    // Try to send email
    $mailSent = @mail($email, $subject, $message, $headers); // Use @ to suppress warnings

    // Always include OTP in message for testing since email is unreliable on local XAMPP
    $msg = "Debug OTP: $otp";
    if (!$mailSent) {
        $msg .= " (Email Failed)";
    } else {
        $msg .= " (Email Sent)";
    }

    echo json_encode([
        "status" => "success", 
        "message" => $msg
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Db Error"]);
}
?>
