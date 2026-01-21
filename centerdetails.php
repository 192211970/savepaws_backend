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

// Get input values
$user_id = isset($input['user_id']) ? intval($input['user_id']) : null;
$center_name = $input['center_name'] ?? null;
$address = $input['address'] ?? null;
$latitude = isset($input['latitude']) ? floatval($input['latitude']) : null;
$longitude = isset($input['longitude']) ? floatval($input['longitude']) : null;
$phone = $input['phone'] ?? null;
$email = $input['email'] ?? null;

// Validate required fields
if (!$user_id || !$center_name || !$address || !$latitude || !$longitude || !$phone || !$email) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit;
}

// Check if center already exists for this user
$checkStmt = $conn->prepare("SELECT center_id FROM centers WHERE user_id = ?");
$checkStmt->bind_param("i", $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Center already registered for this user"]);
    $checkStmt->close();
    exit;
}
$checkStmt->close();

// Insert into DB using prepared statement (SQL injection protection)
$stmt = $conn->prepare("INSERT INTO centers(user_id, center_name, address, latitude, longitude, phone, email, is_active, total_cases_handled, avg_response_time, center_status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'Yes', 0, 0, 'Operating')");

$stmt->bind_param("issddss", $user_id, $center_name, $address, $latitude, $longitude, $phone, $email);

if ($stmt->execute()) {
    $center_id = $stmt->insert_id;
    echo json_encode([
        "status" => "success", 
        "message" => "Center registered successfully",
        "center_id" => $center_id
    ]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

$stmt->close();
$conn->close();
?>
