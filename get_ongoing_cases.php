<?php
/**
 * get_ongoing_cases.php
 * Fetches all cases reported by a specific user with their current status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = 'localhost';
$dbname = 'savepaws';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit;
}

// Check for user_id
if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User ID is required'
    ]);
    exit;
}

$user_id = intval($_POST['user_id']);

// Optional status filter: "ongoing" or "closed"
$status_filter = isset($_POST['status']) ? $_POST['status'] : '';

try {
    // Build query based on status filter
    $status_condition = "";
    if ($status_filter === 'ongoing') {
        // Ongoing = cases that are NOT closed
        $status_condition = "AND c.status != 'Closed'";
    } else if ($status_filter === 'closed') {
        // Closed = only closed cases
        $status_condition = "AND c.status = 'Closed'";
    }

    // Query to fetch cases for this user with optional status filter
    $query = "
        SELECT 
            c.case_id,
            c.photo,
            c.type_of_animal,
            c.animal_condition,
            c.status AS case_status,
            c.created_time,
            c.latitude,
            c.longitude,
            COALESCE(cs.status, 'Pending') AS rescue_status,
            COALESCE(ct.center_name, 'Awaiting Center') AS assigned_center
        FROM cases c
        LEFT JOIN case_status cs ON c.case_id = cs.case_id
        LEFT JOIN centers ct ON cs.center_id = ct.center_id
        WHERE c.user_id = :user_id
        $status_condition
        ORDER BY c.created_time DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $cases = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($cases) > 0) {
        // Format the response
        $formattedCases = [];
        foreach ($cases as $case) {
            $formattedCases[] = [
                'case_id' => intval($case['case_id']),
                'photo' => $case['photo'],
                'type_of_animal' => $case['type_of_animal'],
                'animal_condition' => $case['animal_condition'],
                'case_status' => $case['case_status'],
                'rescue_status' => $case['rescue_status'],
                'assigned_center' => $case['assigned_center'],
                'created_time' => $case['created_time'],
                'latitude' => floatval($case['latitude']),
                'longitude' => floatval($case['longitude'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Cases fetched successfully',
            'total_cases' => count($formattedCases),
            'cases' => $formattedCases
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No cases found for this user',
            'total_cases' => 0,
            'cases' => []
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
}

$conn = null;
?>
