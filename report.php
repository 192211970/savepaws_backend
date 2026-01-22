<?php
header("Content-Type: application/json");
include("db.php");


// Handle photo upload
if (isset($_FILES['photo'])) {
    $photoName = time() . "_" . basename($_FILES["photo"]["name"]);
    $targetDir = "uploads/";
    $targetFile = $targetDir . $photoName;

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile);
} else {
    $photoName = null;
}

// Required Fields
$user_id   = $_POST['user_id'] ?? null;
$type      = $_POST['type_of_animal'] ?? null;
$condition = $_POST['condition'] ?? null;
$lat       = $_POST['latitude'] ?? null;
$lng       = $_POST['longitude'] ?? null;

if (!$photoName || !$type || !$condition || !$lat || !$lng || !$user_id) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

/*******************************************
 1️⃣ FIND ACTIVE CENTERS WITHIN 10 KM
********************************************/
$radius_km = 10;

$centerQuery = "
SELECT c.center_id, c.center_name, c.latitude, c.longitude,
    (6371 * ACOS(
        COS(RADIANS(?)) * COS(RADIANS(c.latitude)) *
        COS(RADIANS(c.longitude) - RADIANS(?)) +
        SIN(RADIANS(?)) * SIN(RADIANS(c.latitude))
    )) AS distance
FROM centers c
LEFT JOIN users u ON c.email = u.email
WHERE c.is_active = 1
HAVING distance <= ?
ORDER BY distance ASC
";

$cstmt = $conn->prepare($centerQuery);
$cstmt->bind_param("dddi", $lat, $lng, $lat, $radius_km);
$cstmt->execute();

$result = $cstmt->get_result();
$centers = [];

while ($row = $result->fetch_assoc()) {
    $centers[] = $row;
}

/*******************************************
 2️⃣ DECIDE CASE TYPE
********************************************/
$case_type = (count($centers) >= 2) ? "Standard" : "Critical";

/*******************************************
 3️⃣ INSERT INTO CASES TABLE
********************************************/
$sql = "INSERT INTO cases
(photo, type_of_animal, animal_condition, latitude, longitude, status, user_id, created_time)
VALUES(?, ?, ?, ?, ?, 'Reported', ?, NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssddi", $photoName, $type, $condition, $lat, $lng, $user_id);

if (!$stmt->execute()) {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
    exit;
}

$case_id = $stmt->insert_id;

/*******************************************
 🔹 3️⃣.1 INSERT INTO CASE_STATUS (NEW)
********************************************/
$caseStatusStmt = $conn->prepare("
    INSERT INTO case_status (case_id, acceptance_status)
    VALUES (?, 'Pending')
");
$caseStatusStmt->bind_param("i", $case_id);
$caseStatusStmt->execute();

/*******************************************
 4️⃣ NO ACTIVE CENTERS → PICK NEAREST
********************************************/
if (empty($centers)) {

    $nearestQuery = "
    SELECT c.center_id, c.center_name, c.latitude, c.longitude,
        (6371 * ACOS(
            COS(RADIANS(?)) * COS(RADIANS(c.latitude)) *
            COS(RADIANS(c.longitude) - RADIANS(?)) +
            SIN(RADIANS(?)) * SIN(RADIANS(c.latitude))
        )) AS distance
    FROM centers c
    LEFT JOIN users u ON c.email = u.email
    WHERE c.is_active = 1
    ORDER BY distance ASC
    LIMIT 1
    ";

    $nstmt = $conn->prepare($nearestQuery);
    $nstmt->bind_param("ddd", $lat, $lng, $lat);
    $nstmt->execute();
    $nearest = $nstmt->get_result()->fetch_assoc();

    if ($nearest) {

        $insertEsc = $conn->prepare("
            INSERT INTO case_escalations
            (case_id, center_id, user_id, status, assigned_time, response, rejected_reason, remark, case_type)
            VALUES(?, ?, ?, 'Pending', NOW(), NULL, NULL, 'None', ?)
        ");

        $insertEsc->bind_param("iiis", $case_id, $nearest['center_id'], $user_id, $case_type);
        $insertEsc->execute();



        echo json_encode([
            "status" => "success",
            "message" => "Critical case reported. Escalated to nearest active center.",
            "case_id" => $case_id,
            "case_type" => $case_type,
            "escalated_to" => [$nearest]
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Case reported but no active rescue centers available.",
        "case_id" => $case_id,
        "case_type" => $case_type
    ]);
    exit;
}

/*******************************************
 5️⃣ INSERT ESCALATIONS FOR ALL ACTIVE CENTERS
********************************************/
foreach ($centers as $c) {

    $insertEsc = $conn->prepare("
        INSERT INTO case_escalations
        (case_id, center_id, user_id, status, assigned_time, response, rejected_reason, remark, case_type)
        VALUES(?, ?, ?, 'Pending', NOW(), NULL, NULL, 'None', ?)
    ");

    $insertEsc->bind_param("iiis", $case_id, $c['center_id'], $user_id, $case_type);
    $insertEsc->execute();


}

/*******************************************
 6️⃣ FINAL RESPONSE
********************************************/
echo json_encode([
    "status" => "success",
    "message" => "Case reported and escalated",
    "case_id" => $case_id,
    "case_type" => $case_type,
    "photo" => $photoName,
    "escalated_to" => $centers
]);
?>
