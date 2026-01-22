<?php
header("Content-Type: application/json");
include("db.php");

// ---------- INPUT ----------
$case_id   = isset($_POST['case_id']) ? intval($_POST['case_id']) : null;
$center_id = isset($_POST['center_id']) ? intval($_POST['center_id']) : null;

if (!$case_id || !$center_id || !isset($_FILES['rescue_photo'])) {
    echo json_encode([
        "status" => "error",
        "message" => "case_id, center_id and rescue_photo are required"
    ]);
    exit;
}

// ---------- PHOTO UPLOAD ----------
$uploadDir = "uploads/rescue_photos/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$photoName = time() . "_" . basename($_FILES["rescue_photo"]["name"]);
$photoPath = $uploadDir . $photoName;

if (!move_uploaded_file($_FILES["rescue_photo"]["tmp_name"], $photoPath)) {
    echo json_encode([
        "status" => "error",
        "message" => "Failed to upload rescue photo"
    ]);
    exit;
}

// ---------- TRANSACTION ----------
$conn->begin_transaction();

try {

    /* 1️⃣ Update case_status */
    $stmt1 = $conn->prepare("
        UPDATE case_status
        SET rescued_animal = 'Yes',
            rescued_time = NOW(),
            rescue_photo = ?,
            closed_time = NOW(),
            status = 'Closed'
        WHERE case_id = ?
          AND center_id = ?
          AND status = 'Inprogress'
    ");
    $stmt1->bind_param("sii", $photoPath, $case_id, $center_id);
    $stmt1->execute();

    if ($stmt1->affected_rows === 0) {
        throw new Exception("Case status not found or already closed");
    }

    /* 2️⃣ Update cases table */
    $stmt2 = $conn->prepare("
        UPDATE cases
        SET status = 'Closed'
        WHERE case_id = ?
          AND status = 'Accepted'
    ");
    $stmt2->bind_param("i", $case_id);
    $stmt2->execute();

    /* 3️⃣ Update case_escalations (ONLY accepted center) */
    $stmt3 = $conn->prepare("
        UPDATE case_escalations
        SET status = 'Closed'
        WHERE case_id = ?
          AND center_id = ?
          AND response = 'Accept'
    ");
    $stmt3->bind_param("ii", $case_id, $center_id);
    $stmt3->execute();

    /* 4️⃣ Increment center total_cases_handled */
    $stmt4 = $conn->prepare("
        UPDATE centers
        SET total_cases_handled = total_cases_handled + 1
        WHERE center_id = ?
    ");
    $stmt4->bind_param("i", $center_id);
    $stmt4->execute();

    // ---------- COMMIT ----------
    $conn->commit();




    echo json_encode([
        "status" => "success",
        "message" => "Case closed successfully"
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
?>
