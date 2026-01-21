<?php
header('Content-Type: application/json');
include "db.php";

$sql = "SELECT 
            c.case_id,
            c.user_id,
            c.photo,
            c.type_of_animal,
            c.animal_condition,
            c.latitude,
            c.longitude, 
            c.created_time,
            u.name AS user_name,
            u.phone AS user_phone
        FROM cases c
        JOIN users u ON c.user_id = u.id
        WHERE c.status = 'Reported'
        ORDER BY c.case_id DESC";

$result = $conn->query($sql);

$cases = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cases[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "cases" => $cases
    ]);
} else {
    echo json_encode([
        "status" => "no_cases",
        "message" => "No new reported cases"
    ]);
}
?>
