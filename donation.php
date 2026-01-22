<?php
header("Content-Type: application/json");
include("db.php");

/* =========================
   REQUIRED FORM DATA
   ========================= */
$center_id = $_POST['center_id'] ?? null;
$case_id   = $_POST['case_id'] ?? null;
$amount    = $_POST['amount'] ?? null;

if (!$center_id || !$case_id || !$amount) {
    echo json_encode([
        "status" => "error",
        "message" => "center_id, case_id and amount are required"
    ]);
    exit;
}

/* =========================
   HANDLE IMAGE UPLOAD
   ========================= */
$photoName = null;

if (isset($_FILES['image_of_animal'])) {

    $uploadDir = "uploads/donations/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $photoName = time() . "_" . basename($_FILES["image_of_animal"]["name"]);
    $targetPath = $uploadDir . $photoName;

    if (!move_uploaded_file($_FILES["image_of_animal"]["tmp_name"], $targetPath)) {
        echo json_encode([
            "status" => "error",
            "message" => "Failed to upload image"
        ]);
        exit;
    }
}

/* =========================
   VALIDATE CASE EXISTS
   ========================= */
$caseCheck = $conn->prepare("
    SELECT case_id FROM cases WHERE case_id = ?
");
$caseCheck->bind_param("i", $case_id);
$caseCheck->execute();

if ($caseCheck->get_result()->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid case_id"
    ]);
    exit;
}

/* =========================
   CHECK CENTER HAS HANDLED THE CASE
   ========================= */
$checkHandling = $conn->prepare("
    SELECT status_id
    FROM case_status
    WHERE case_id = ?
      AND center_id = ?
");
$checkHandling->bind_param("ii", $case_id, $center_id);
$checkHandling->execute();

if ($checkHandling->get_result()->num_rows === 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Center has not handled this case"
    ]);
    exit;
}


/* =========================
   INSERT DONATION REQUEST
   ========================= */
$stmt = $conn->prepare("
    INSERT INTO donations
    (center_id, case_id, image_of_animal, amount, requested_time, approval_status, donation_status)
    VALUES (?, ?, ?, ?, NOW(), 'Pending', NULL)
");

$stmt->bind_param(
    "iisd",
    $center_id,
    $case_id,
    $photoName,
    $amount
);

if (!$stmt->execute()) {
    echo json_encode([
        "status" => "error",
        "message" => $stmt->error
    ]);
    exit;
}




/* =========================
   SUCCESS RESPONSE
   ========================= */
echo json_encode([
    "status" => "success",
    "message" => "Donation request created successfully",
    "donation_id" => $stmt->insert_id
]);
?>