<?php
/**
 * get_approved_donations.php
 * Fetches all approved donation requests that are not yet paid
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

include("db.php");

try {
    // Query to fetch approved donations that are not paid
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
            rc.center_name,
            rc.phone AS center_phone,
            c.type_of_animal,
            c.animal_condition,
            c.photo AS case_photo
        FROM donations d
        LEFT JOIN rescue_centers rc ON d.center_id = rc.center_id
        LEFT JOIN cases c ON d.case_id = c.case_id
        WHERE d.approval_status = 'Approved' 
        AND (d.donation_status IS NULL OR d.donation_status != 'Paid')
        ORDER BY d.requested_time DESC
    ";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $donations = [];
        while ($row = $result->fetch_assoc()) {
            $donations[] = [
                'donation_id' => intval($row['donation_id']),
                'center_id' => intval($row['center_id']),
                'case_id' => intval($row['case_id']),
                'image_of_animal' => $row['image_of_animal'],
                'amount' => floatval($row['amount']),
                'requested_time' => $row['requested_time'],
                'center_name' => $row['center_name'],
                'center_phone' => $row['center_phone'],
                'animal_type' => $row['type_of_animal'],
                'animal_condition' => $row['animal_condition'],
                'case_photo' => $row['case_photo']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Donations fetched successfully',
            'total_donations' => count($donations),
            'donations' => $donations
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'No approved donations found',
            'total_donations' => 0,
            'donations' => []
        ]);
    }
    
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
}
?>
