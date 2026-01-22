<?php
/**
 * get_donation_details.php
 * Fetches detailed information about a specific donation
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include("db.php");

// Check for donation_id
if (!isset($_POST['donation_id']) || empty($_POST['donation_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Donation ID is required'
    ]);
    exit;
}

$donation_id = intval($_POST['donation_id']);

try {
    // Query to fetch donation details
    $query = "
        SELECT 
            d.donation_id,
            d.center_id,
            d.case_id,
            d.image_of_animal,
            d.amount,
            d.requested_time,
            d.approval_status,
            d.donation_status,
            d.payment_method,
            d.transaction_id,
            d.payment_time,
            c.center_name,
            c.phone AS center_phone,
            c.address AS center_address,
            c.email AS center_email,
            cs.type_of_animal,
            cs.animal_condition,
            cs.photo AS case_photo,
            cs.status AS case_status
        FROM donations d
        LEFT JOIN centers c ON d.center_id = c.center_id
        LEFT JOIN cases cs ON d.case_id = cs.case_id
        WHERE d.donation_id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $donation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $donation = $result->fetch_assoc();
    
    if ($donation) {
        echo json_encode([
            'success' => true,
            'message' => 'Donation details fetched successfully',
            'donation' => [
                'donation_id' => intval($donation['donation_id']),
                'center_id' => intval($donation['center_id']),
                'case_id' => intval($donation['case_id']),
                'image_of_animal' => $donation['image_of_animal'],
                'amount' => floatval($donation['amount']),
                'requested_time' => $donation['requested_time'],
                'approval_status' => $donation['approval_status'],
                'donation_status' => $donation['donation_status'],
                'payment_method' => $donation['payment_method'],
                'transaction_id' => $donation['transaction_id'],
                'payment_time' => $donation['payment_time'],
                'center_name' => $donation['center_name'],
                'center_phone' => $donation['center_phone'],
                'center_address' => $donation['center_address'],
                'center_email' => $donation['center_email'],
                'animal_type' => $donation['type_of_animal'],
                'animal_condition' => $donation['animal_condition'],
                'case_photo' => $donation['case_photo'],
                'case_status' => $donation['case_status']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Donation not found'
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
}
?>
