<?php
// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: text/plain");

echo "Testing Email Sending...\n";

$to = "srivennela904@gmail.com"; // Default to what was in the ini file or let user override
if (isset($_GET['to'])) {
    $to = $_GET['to'];
}

$subject = "Test Email from XAMPP";
$message = "If you receive this, XAMPP email is configured correctly.";
$headers = "From: srivennela904@gmail.com\r\n";

echo "Attempting to send to: $to\n";

if (mail($to, $subject, $message, $headers)) {
    echo "PHP says: Mail accepted for delivery -> TRUE\n";
    echo "Check your inbox (and SPAM folder).\n";
    echo "If it doesn't arrive, check C:\\xampp\\sendmail\\error.log\n";
} else {
    echo "PHP says: Mail delivery failed -> FALSE\n";
    print_r(error_get_last());
}

echo "\nConfiguration Check:\n";
echo "sendmail_path: " . ini_get('sendmail_path') . "\n";
echo "SMTP: " . ini_get('SMTP') . "\n";
echo "smtp_port: " . ini_get('smtp_port') . "\n";

?>
