<?php
header("Content-Type: text/plain");
include 'db.php';
include 'send_notification.php';

// Usage: test_notification.php?email=user@example.com

if (!isset($_GET['email'])) {
    echo "Error: Please provide an email parameter. Example: test_notification.php?email=sri@test.com";
    exit;
}

$email = $_GET['email'];

echo "1. Searching for user with email: $email...\n";

// Fetch token
$stmt = $conn->prepare("SELECT id, name, fcm_token FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "2. User found: " . $row['name'] . " (ID: " . $row['id'] . ")\n";
    
    $token = $row['fcm_token'];
    
    if (empty($token)) {
        echo "❌ Error: fcm_token is EMPTY/NULL for this user. \n";
        echo "   -> Please Log Out and Log In again in the Android App to update the token.\n";
        exit;
    }

    echo "3. Token found. Length: " . strlen($token) . "\n";
    echo "4. Sending test notification...\n";

    $response = sendNotification(
        $token, 
        "Test Notification", 
        "This is a test message from your PHP Backend! 🚀"
    );

    echo "5. Result from FCM:\n";
    echo $response;
    echo "\n\n✅ Check your device now.";

} else {
    echo "❌ Error: User not found in database.";
}
?>
