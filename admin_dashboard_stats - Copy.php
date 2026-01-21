<?php
header("Content-Type: application/json");
include("db.php");

// Get total cases from cases table
$totalCasesQuery = $conn->query("SELECT COUNT(*) as total_cases FROM cases");
$totalCases = $totalCasesQuery->fetch_assoc()['total_cases'] ?? 0;

// Get PENDING count - cases where status is 'Reported' (not yet accepted by any center)
$pendingQuery = $conn->query("SELECT COUNT(*) as pending_cases FROM cases WHERE status = 'Reported'");
$pendingCases = $pendingQuery->fetch_assoc()['pending_cases'] ?? 0;

// Get IN PROGRESS count (cases that have been accepted but not closed)
// Using case_status table: acceptance_status = 'Accepted' AND status = 'Inprogress'
$inProgressQuery = $conn->query("
    SELECT COUNT(*) as in_progress_cases 
    FROM case_status 
    WHERE acceptance_status = 'Accepted' 
    AND status = 'Inprogress'
");
$inProgressCases = $inProgressQuery->fetch_assoc()['in_progress_cases'] ?? 0;

// Get CLOSED count (cases that are closed/rescued)
// Using case_status table: status = 'Closed'
$closedQuery = $conn->query("
    SELECT COUNT(*) as closed_cases 
    FROM case_status 
    WHERE status = 'Closed'
");
$closedCases = $closedQuery->fetch_assoc()['closed_cases'] ?? 0;

// Get center counts (is_active is enum 'Yes'/'No', not 1/0)
$centersQuery = $conn->query("
    SELECT 
        COUNT(*) as total_centers,
        SUM(CASE WHEN is_active = 'Yes' THEN 1 ELSE 0 END) as active_centers
    FROM centers
");
$centersStats = $centersQuery->fetch_assoc();

// Get donation counts
$donationsQuery = $conn->query("
    SELECT 
        COUNT(*) as total_donations,
        SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) as pending_donations,
        SUM(CASE WHEN approval_status = 'Approved' THEN 1 ELSE 0 END) as approved_donations,
        SUM(CASE WHEN approval_status = 'Rejected' THEN 1 ELSE 0 END) as rejected_donations
    FROM donations
");
$donationsStats = $donationsQuery->fetch_assoc();

echo json_encode([
    "success" => true,
    "stats" => [
        "total_cases" => (int)$totalCases,
        "pending_cases" => (int)$pendingCases,
        "in_progress_cases" => (int)$inProgressCases,
        "closed_cases" => (int)$closedCases,
        "total_centers" => (int)($centersStats['total_centers'] ?? 0),
        "active_centers" => (int)($centersStats['active_centers'] ?? 0),
        "total_donations" => (int)($donationsStats['total_donations'] ?? 0),
        "pending_donations" => (int)($donationsStats['pending_donations'] ?? 0),
        "approved_donations" => (int)($donationsStats['approved_donations'] ?? 0),
        "rejected_donations" => (int)($donationsStats['rejected_donations'] ?? 0)
    ]
]);
?>
