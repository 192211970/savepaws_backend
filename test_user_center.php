<?php
header("Content-Type: application/json");
include("db.php");

// ⚠️ CHANGE THIS TO YOUR ACTUAL USER ID
$test_user_id = 1; // Change this to the user_id of an organization account

$response = [];
$response['testing_user_id'] = $test_user_id;
$response['instructions'] = '⚠️ Edit line 6 to use your actual organization user ID';

// Check if user exists
$userQuery = $conn->prepare("SELECT id, name, email, user_type FROM users WHERE id = ?");
$userQuery->bind_param("i", $test_user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();

if ($userResult->num_rows > 0) {
    $response['user'] = $userResult->fetch_assoc();
    $response['user_exists'] = true;
} else {
    $response['user'] = 'NOT FOUND ❌';
    $response['user_exists'] = false;
}

// Check if center exists for this user
$centerQuery = $conn->prepare("SELECT center_id, center_name, user_id, phone, email FROM centers WHERE user_id = ?");
$centerQuery->bind_param("i", $test_user_id);
$centerQuery->execute();
$centerResult = $centerQuery->get_result();

if ($centerResult->num_rows > 0) {
    $response['center'] = $centerResult->fetch_assoc();
    $response['has_center'] = true;
    $response['diagnosis'] = '✅ Center exists! Should go to dashboard.';
} else {
    $response['center'] = 'NOT FOUND ❌';
    $response['has_center'] = false;
    $response['diagnosis'] = '❌ No center found! Will redirect to registration.';
}

// Check all centers (to see if any exist)
$allCenters = $conn->query("SELECT center_id, user_id, center_name FROM centers");
$response['all_centers_count'] = $allCenters->num_rows;
$response['all_centers'] = [];
while ($row = $allCenters->fetch_assoc()) {
    $response['all_centers'][] = $row;
}

// Check all organization users
$orgUsers = $conn->query("SELECT id, name, email FROM users WHERE user_type = 'organization'");
$response['organization_users_count'] = $orgUsers->num_rows;
$response['organization_users'] = [];
while ($row = $orgUsers->fetch_assoc()) {
    $response['organization_users'][] = $row;
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
