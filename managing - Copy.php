<?php
header("Content-Type: application/json");
include("db.php");

// INPUT
$center_id     = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;
$center_status = isset($_POST['center_status']) ? $_POST['center_status'] : null;

if (!$center_id || !$center_status) {
    echo json_encode([
        "status" => "error",
        "message" => "center_id and center_status are required"
    ]);
    exit;
}

// VALIDATE STATUS
if (!in_array($center_status, ['Operating', 'Deactivated'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid center_status value"
    ]);
    exit;
}

// MAP is_active VALUE
$is_active = ($center_status === 'Operating') ? 'Yes' : 'No';

// CHECK CENTER EXISTS
$check = $conn->prepare("SELECT center_id FROM centers WHERE center_id = ?");
$check->bind_param("i", $center_id);
$check->execute();

if ($check->get_result()->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Center not found"
    ]);
    exit;
}

// UPDATE CENTER
$update = $conn->prepare("
    UPDATE centers
    SET center_status = ?, is_active = ?
    WHERE center_id = ?
");

$update->bind_param("ssi", $center_status, $is_active, $center_id);
$update->execute();

if ($update->affected_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "No changes made"
    ]);
    exit;
}

echo json_encode([
    "status" => "success",
    "message" => "Center status updated successfully",
    "center_id" => $center_id,
    "center_status" => $center_status,
    "is_active" => $is_active
]);
