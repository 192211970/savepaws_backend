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


// Update spot_animal → YES and set timestamp
$update = $conn->prepare("
    UPDATE case_status
    SET spot_animal = 'Yes',
        spotted_time = NOW()
    WHERE case_id = ?
      AND center_id = ?
      AND status = 'Inprogress'
      AND spot_animal IS NULL
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

echo json_encode([
    "status" => "success",
    "message" => "Spot Animal marked successfully"
]);
?>
