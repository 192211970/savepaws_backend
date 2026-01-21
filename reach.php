<?php
header("Content-Type: application/json");
include("db.php");

// FORM-DATA
$case_id   = isset($_POST['case_id']) ? intval($_POST['case_id']) : null;
$center_id = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;

if (!$case_id || !$center_id) {
    echo json_encode([
        "status" => "error",
        "message" => "case_id and center_id are required"
    ]);
    exit;
}

// Update reached_location → YES and set timestamp
$update = $conn->prepare("
    UPDATE case_status
    SET reached_location = 'Yes',
        reached_time = NOW()
    WHERE case_id = ?
      AND center_id = ?
      AND status = 'Inprogress'
      AND reached_location IS NULL
");

$update->bind_param("ii", $case_id, $center_id);
$update->execute();

if ($update->affected_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Already updated or case not found"
    ]);
    exit;
}

/* =================================================
   🔔 SEND NOTIFICATION TO USER
   ================================================= */
include_once 'send_notification.php';

// Get User ID and Token
$uQuery = $conn->prepare("
    SELECT u.fcm_token 
    FROM cases c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.case_id = ?
");
$uQuery->bind_param("i", $case_id);
$uQuery->execute();
$uResult = $uQuery->get_result();

if ($uRow = $uResult->fetch_assoc()) {
    if (!empty($uRow['fcm_token'])) {
        sendNotification(
            $uRow['fcm_token'],
            "Rescuer Reached!",
            "The rescue team has reached the location."
        );
    }
}

echo json_encode([
    "status" => "success",
    "message" => "Reached location marked successfully"
]);
?>
