<?php
header("Content-Type: application/json");
include("db.php");

// ⚠️ CHANGE THIS TO YOUR ACTUAL USER ID
$test_user_id = 1; // Change this to a user ID that should have cases

$response = [];
$response['testing_user_id'] = $test_user_id;
$response['instructions'] = '⚠️ Edit line 6 to use your actual user ID';

// Check total cases in database
$totalCases = $conn->query("SELECT COUNT(*) as count FROM cases");
$response['total_cases_in_db'] = $totalCases->fetch_assoc()['count'];

// Check cases for this user
$userCases = $conn->prepare("SELECT case_id, type_of_animal, status, created_time FROM cases WHERE user_id = ? ORDER BY created_time DESC");
$userCases->bind_param("i", $test_user_id);
$userCases->execute();
$result = $userCases->get_result();

$response['user_cases_count'] = $result->num_rows;
$response['user_cases'] = [];
while ($row = $result->fetch_assoc()) {
    $response['user_cases'][] = $row;
}

// Check ongoing vs closed
$ongoingCases = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE user_id = ? AND status != 'Closed'");
$ongoingCases->bind_param("i", $test_user_id);
$ongoingCases->execute();
$response['ongoing_cases_count'] = $ongoingCases->get_result()->fetch_assoc()['count'];

$closedCases = $conn->prepare("SELECT COUNT(*) as count FROM cases WHERE user_id = ? AND status = 'Closed'");
$closedCases->bind_param("i", $test_user_id);
$closedCases->execute();
$response['closed_cases_count'] = $closedCases->get_result()->fetch_assoc()['count'];

// Check status values (case sensitivity check)
$statuses = $conn->query("SELECT DISTINCT status FROM cases");
$response['all_status_values'] = [];
while ($row = $statuses->fetch_assoc()) {
    $response['all_status_values'][] = $row['status'];
}

// Check if case_status table has data
$caseStatusCount = $conn->query("SELECT COUNT(*) as count FROM case_status");
$response['case_status_records'] = $caseStatusCount->fetch_assoc()['count'];

// Sample case with full details
$sampleCase = $conn->query("SELECT c.*, cs.status as rescue_status, ct.center_name 
                             FROM cases c 
                             LEFT JOIN case_status cs ON c.case_id = cs.case_id 
                             LEFT JOIN centers ct ON cs.center_id = ct.center_id 
                             LIMIT 1");
if ($sampleCase && $sampleCase->num_rows > 0) {
    $response['sample_case_with_joins'] = $sampleCase->fetch_assoc();
} else {
    $response['sample_case_with_joins'] = 'No cases found';
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
