<?php
header("Content-Type: application/json");
include 'db.php';

$response = array();

if (isset($_POST['user_id']) && isset($_POST['fcm_token'])) {
    
    $user_id = $_POST['user_id'];
    $fcm_token = $_POST['fcm_token'];

    // Update token in users table
    $stmt = $conn->prepare("UPDATE users SET fcm_token = ? WHERE id = ?");
    $stmt->bind_param("si", $fcm_token, $user_id);
    
    if ($stmt->execute()) {
        $response['status'] = "success";
        $response['message'] = "Token updated successfully";
    } else {
        $response['status'] = "error";
        $response['message'] = "Failed to update token";
    }

} else {
    $response['status'] = "error";
    $response['message'] = "Missing required fields";
}

echo json_encode($response);
?>
