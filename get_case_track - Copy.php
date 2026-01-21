<?php
/**
 * get_case_track.php
 * Fetches detailed tracking information for a specific case
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include("db.php");

// Check for case_id
if (!isset($_POST['case_id']) || empty($_POST['case_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Case ID is required'
    ]);
    exit;
}

$case_id = intval($_POST['case_id']);

// 1. Fetch case basic info
$caseQuery = "
    SELECT 
        c.case_id,
        c.user_id,
        c.photo,
        c.type_of_animal,
        c.animal_condition,
        c.status AS case_status,
        c.created_time,
        c.latitude,
        c.longitude,
        u.name AS reported_by
    FROM cases c
    LEFT JOIN users u ON c.user_id = u.id
    WHERE c.case_id = ?
";

$stmt = $conn->prepare($caseQuery);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$caseResult = $stmt->get_result();
$caseInfo = $caseResult->fetch_assoc();

if (!$caseInfo) {
    echo json_encode([
        'success' => false,
        'message' => 'Case not found'
    ]);
    exit;
}

// 2. Fetch escalation history
$escalationQuery = "
    SELECT 
        ce.escalation_id,
        ce.status AS escalation_status,
        ce.assigned_time,
        ce.responded_time,
        ce.response,
        ce.rejected_reason,
        ce.case_type,
        ce.remark,
        ct.center_name,
        ct.phone AS center_phone
    FROM case_escalations ce
    LEFT JOIN centers ct ON ce.center_id = ct.center_id
    WHERE ce.case_id = ?
    ORDER BY ce.assigned_time DESC
";

$stmt = $conn->prepare($escalationQuery);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$escalationsResult = $stmt->get_result();
$escalations = [];
while ($row = $escalationsResult->fetch_assoc()) {
    $escalations[] = $row;
}

// 3. Fetch rescue status (case_status table)
$statusQuery = "
    SELECT 
        cs.status_id,
        cs.acceptance_status,
        cs.case_took_up_time,
        cs.reached_location,
        cs.reached_time,
        cs.spot_animal,
        cs.spotted_time,
        cs.rescued_animal,
        cs.rescued_time,
        cs.rescue_photo,
        cs.closed_time,
        cs.status AS rescue_status,
        ct.center_name AS rescue_center,
        ct.phone AS rescue_center_phone
    FROM case_status cs
    LEFT JOIN centers ct ON cs.center_id = ct.center_id
    WHERE cs.case_id = ?
";

$stmt = $conn->prepare($statusQuery);
$stmt->bind_param("i", $case_id);
$stmt->execute();
$statusResult = $stmt->get_result();
$rescueStatus = $statusResult->fetch_assoc();

// 4. Build the timeline with 5 fixed steps
$timeline = [];

// Determine which steps are completed based on case_status
$mainCaseStatus = strtolower($caseInfo['case_status'] ?? '');

// Accept is true if case_status says 'Accepted' OR if main cases table shows 'Accepted' or 'Closed'
$acceptedCase = ($rescueStatus && strtolower($rescueStatus['acceptance_status'] ?? '') === 'accepted')
                || $mainCaseStatus === 'accepted' 
                || $mainCaseStatus === 'closed';

$reachedLocation = ($rescueStatus && strtolower($rescueStatus['reached_location'] ?? '') === 'yes');
$spottedAnimal = ($rescueStatus && strtolower($rescueStatus['spot_animal'] ?? '') === 'yes');
$rescuedAnimal = ($rescueStatus && strtolower($rescueStatus['rescued_animal'] ?? '') === 'yes');

// Check for closed status
$caseClosed = ($rescueStatus && strtolower($rescueStatus['rescue_status'] ?? '') === 'closed') 
              || $mainCaseStatus === 'closed';

// If case is closed but no case_status entry, mark all steps as completed
if ($caseClosed && !$rescueStatus) {
    $acceptedCase = true;
    $reachedLocation = true;
    $spottedAnimal = true;
    $rescuedAnimal = true;
}

// Get rescue center name
$rescueCenterName = $rescueStatus['rescue_center'] ?? 'Rescue Center';

// Step 1: Accepted Case
$timeline[] = [
    'step' => 1,
    'title' => 'Accepted Case',
    'description' => $acceptedCase 
        ? ($rescueCenterName . ' accepted the case')
        : 'Waiting for a rescue center to accept',
    'timestamp' => $acceptedCase ? ($rescueStatus['case_took_up_time'] ?? null) : null,
    'status' => $acceptedCase ? 'completed' : 'pending',
    'icon' => 'accept'
];

// Step 2: Reached Location
$timeline[] = [
    'step' => 2,
    'title' => 'Reached Location',
    'description' => $reachedLocation 
        ? 'Rescue team reached the reported location'
        : 'Rescue team is on the way',
    'timestamp' => $reachedLocation ? ($rescueStatus['reached_time'] ?? null) : null,
    'status' => $reachedLocation ? 'completed' : 'pending',
    'icon' => 'location'
];

// Step 3: Spot Animal
$timeline[] = [
    'step' => 3,
    'title' => 'Spot Animal',
    'description' => $spottedAnimal 
        ? 'Rescue team spotted the animal'
        : 'Searching for the animal',
    'timestamp' => $spottedAnimal ? ($rescueStatus['spotted_time'] ?? null) : null,
    'status' => $spottedAnimal ? 'completed' : 'pending',
    'icon' => 'spot'
];

// Step 4: Rescued Animal
$timeline[] = [
    'step' => 4,
    'title' => 'Rescued Animal',
    'description' => $rescuedAnimal 
        ? 'Animal has been safely rescued!'
        : 'Rescue in progress',
    'timestamp' => $rescuedAnimal ? ($rescueStatus['rescued_time'] ?? null) : null,
    'status' => $rescuedAnimal ? 'completed' : 'pending',
    'icon' => 'rescue'
];

// Step 5: Closed Case
$timeline[] = [
    'step' => 5,
    'title' => 'Closed Case',
    'description' => $caseClosed 
        ? 'Rescue completed successfully'
        : 'Case is still open',
    'timestamp' => $caseClosed ? ($rescueStatus['closed_time'] ?? null) : null,
    'status' => $caseClosed ? 'completed' : 'pending',
    'icon' => 'closed'
];

// Build final response
$response = [
    'success' => true,
    'message' => 'Case track fetched successfully',
    'case_info' => [
        'case_id' => intval($caseInfo['case_id']),
        'photo' => $caseInfo['photo'],
        'type_of_animal' => $caseInfo['type_of_animal'],
        'animal_condition' => $caseInfo['animal_condition'],
        'case_status' => $caseInfo['case_status'],
        'created_time' => $caseInfo['created_time'],
        'latitude' => floatval($caseInfo['latitude']),
        'longitude' => floatval($caseInfo['longitude']),
        'reported_by' => $caseInfo['reported_by']
    ],
    'rescue_info' => $rescueStatus ? [
        'rescue_center' => $rescueStatus['rescue_center'],
        'rescue_center_phone' => $rescueStatus['rescue_center_phone'],
        'rescue_status' => $rescueStatus['rescue_status'],
        'rescue_photo' => $rescueStatus['rescue_photo'],
        'closed_time' => $rescueStatus['closed_time']
    ] : null,
    'escalations' => array_map(function($esc) {
        return [
            'center_name' => $esc['center_name'],
            'status' => $esc['escalation_status'],
            'response' => $esc['response'],
            'rejected_reason' => $esc['rejected_reason'],
            'assigned_time' => $esc['assigned_time'],
            'responded_time' => $esc['responded_time']
        ];
    }, $escalations),
    'timeline' => $timeline
];

echo json_encode($response);

$conn->close();
?>
