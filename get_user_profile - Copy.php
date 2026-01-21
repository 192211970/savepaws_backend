<?php
header("Content-Type: application/json");
include("db.php");

$user_id = $_GET['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "message" => "User ID required"
    ]);
    exit;
}

// Get user details
$userQuery = $conn->prepare("
    SELECT id, name, email, phone, user_type, created_at
    FROM users
    WHERE id = ?
");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "User not found"
    ]);
    exit;
}

$user = $userResult->fetch_assoc();

// Get total cases reported by user
$casesQuery = $conn->prepare("
    SELECT COUNT(*) as total_cases
    FROM cases
    WHERE user_id = ?
");
$casesQuery->bind_param("i", $user_id);
$casesQuery->execute();
$casesResult = $casesQuery->get_result()->fetch_assoc();

// Get total donations made by user
$donationsQuery = $conn->prepare("
    SELECT COUNT(*) as total_donations, COALESCE(SUM(amount), 0) as total_amount
    FROM donations
    WHERE user_id = ? AND donation_status = 'Paid'
");
$donationsQuery->bind_param("i", $user_id);
$donationsQuery->execute();
$donationsResult = $donationsQuery->get_result()->fetch_assoc();

echo json_encode([
    "success" => true,
    "user" => [
        "id" => (int)$user['id'],
        "name" => $user['name'],
        "email" => $user['email'],
        "phone" => $user['phone'],
        "user_type" => $user['user_type'],
        "member_since" => $user['created_at']
    ],
    "stats" => [
        "total_cases" => (int)$casesResult['total_cases'],
        "total_donations" => (int)$donationsResult['total_donations'],
        "total_amount_donated" => (float)$donationsResult['total_amount']
    ]
]);
?>
