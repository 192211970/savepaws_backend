<?php
header("Content-Type: application/json");
include("db.php");

// Detect input source (JSON or form-data)
$raw = file_get_contents("php://input");
$jsonInput = json_decode($raw, true);

// If JSON was sent -> use JSON
if ($jsonInput && is_array($jsonInput)) {
    $input = $jsonInput;
}
// If form-data was sent -> use $_POST
else if (!empty($_POST)) {
    $input = $_POST;
}
// No valid input
else {
    echo json_encode(["status" => "error", "message" => "Invalid input"]);
    exit;
}

$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;

if (!$user_id) {
    echo json_encode(["status" => "error", "message" => "user_id is required"]);
    exit;
}

// Check if center exists for this user
$stmt = $conn->prepare("SELECT center_id, center_name FROM centers WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "has_center_details" => true,
        "center_id" => $row['center_id'],
        "center_name" => $row['center_name']
    ]);
} else {
    echo json_encode([
        "status" => "success",
        "has_center_details" => false,
        "center_id" => null,
        "center_name" => null
    ]);
}

$stmt->close();
$conn->close();
?>
