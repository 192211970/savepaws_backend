<?php
header("Content-Type: application/json");
include("db.php");

// FORM-DATA
$center_id = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;
$is_active = isset($_POST['is_active']) ? $_POST['is_active'] : null;

// Validation
if (!$center_id || !$is_active) {
    echo json_encode([
        "status" => "error",
        "message" => "Missing required fields"
    ]);
    exit;
}

// ENUM validation
if (!in_array($is_active, ['Yes', 'No'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid is_active value"
    ]);
    exit;
}

// Update query
$update = $conn->prepare("
    UPDATE centers
    SET is_active = ?
    WHERE center_id = ?
");

$update->bind_param("si", $is_active, $center_id);
$update->execute();

// Check result
if ($update->affected_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Center not found or already updated"
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Center active status updated successfully"
]);
?>
