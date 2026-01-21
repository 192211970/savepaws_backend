<?php
header("Content-Type: application/json");
include("db.php");

// Get all centers with their cases handled count
$query = $conn->query("
    SELECT 
        c.center_id,
        c.center_name,
        c.phone,
        c.email,
        c.address,
        c.is_active,
        c.center_status,
        c.created_at,
        COALESCE(
            (SELECT COUNT(*) 
             FROM case_status cs 
             WHERE cs.center_id = c.center_id 
               AND cs.acceptance_status = 'Accepted' 
               AND cs.status = 'Closed'), 
            0
        ) AS cases_handled
    FROM centers c
    ORDER BY c.center_id DESC
");

if (!$query) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
    exit;
}

$centers = [];
while ($row = $query->fetch_assoc()) {
    $centers[] = [
        "center_id" => (int)$row['center_id'],
        "center_name" => $row['center_name'],
        "phone" => $row['phone'],
        "email" => $row['email'],
        "address" => $row['address'],
        "is_active" => $row['is_active'],
        "center_status" => $row['center_status'],
        "created_at" => $row['created_at'],
        "cases_handled" => (int)$row['cases_handled']
    ];
}

echo json_encode([
    "success" => true,
    "total_centers" => count($centers),
    "centers" => $centers
]);

$conn->close();
?>
