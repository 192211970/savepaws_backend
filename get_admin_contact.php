<?php
/**
 * get_admin_contact.php
 * Fetches admin contact information for rescue centers
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

include("db.php");

// Query to get admin user details
$query = "SELECT name, phone, email FROM users WHERE user_type = 'admin' LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'message' => 'Admin contact fetched successfully',
        'admin' => [
            'name' => $admin['name'],
            'phone' => $admin['phone'],
            'email' => $admin['email']
        ]
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Admin not found',
        'admin' => null
    ]);
}

$conn->close();
?>
