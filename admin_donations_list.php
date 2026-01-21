<?php
header("Content-Type: application/json");
include("db.php");

$status = $_GET['status'] ?? null;

if (!$status) {
    echo json_encode([
        "success" => false,
        "message" => "status parameter is required (pending|approved|rejected|paid)"
    ]);
    exit;
}

$donations = [];

if ($status === 'pending') {
    // Get pending donations
    $query = $conn->query("
        SELECT 
            d.donation_id,
            d.center_id,
            d.case_id,
            d.amount,
            d.requested_time,
            c.center_name
        FROM donations d
        LEFT JOIN centers c ON d.center_id = c.center_id
        WHERE d.approval_status = 'Pending'
        ORDER BY d.requested_time DESC
    ");
    
    while ($row = $query->fetch_assoc()) {
        $donations[] = [
            "donation_id" => (int)$row['donation_id'],
            "center_id" => (int)$row['center_id'],
            "case_id" => (int)$row['case_id'],
            "center_name" => $row['center_name'],
            "amount" => (float)$row['amount'],
            "requested_time" => $row['requested_time']
        ];
    }
} 
else if ($status === 'approved') {
    // Get approved donations (not yet paid)
    $query = $conn->query("
        SELECT 
            d.donation_id,
            d.center_id,
            d.case_id,
            d.amount,
            d.requested_time,
            c.center_name
        FROM donations d
        LEFT JOIN centers c ON d.center_id = c.center_id
        WHERE d.approval_status = 'Approved'
          AND (d.donation_status IS NULL OR d.donation_status != 'Paid')
        ORDER BY d.requested_time DESC
    ");
    
    while ($row = $query->fetch_assoc()) {
        $donations[] = [
            "donation_id" => (int)$row['donation_id'],
            "center_id" => (int)$row['center_id'],
            "case_id" => (int)$row['case_id'],
            "center_name" => $row['center_name'],
            "amount" => (float)$row['amount'],
            "requested_time" => $row['requested_time']
        ];
    }
}
else if ($status === 'rejected') {
    // Get rejected donations
    $query = $conn->query("
        SELECT 
            d.donation_id,
            d.center_id,
            d.case_id,
            d.amount,
            d.requested_time,
            c.center_name
        FROM donations d
        LEFT JOIN centers c ON d.center_id = c.center_id
        WHERE d.approval_status = 'Rejected'
        ORDER BY d.requested_time DESC
    ");
    
    while ($row = $query->fetch_assoc()) {
        $donations[] = [
            "donation_id" => (int)$row['donation_id'],
            "center_id" => (int)$row['center_id'],
            "case_id" => (int)$row['case_id'],
            "center_name" => $row['center_name'],
            "amount" => (float)$row['amount'],
            "requested_time" => $row['requested_time']
        ];
    }
}
else if ($status === 'paid') {
    // Get paid donations
    $query = $conn->query("
        SELECT 
            d.donation_id,
            d.center_id,
            d.case_id,
            d.amount,
            d.requested_time,
            d.user_id,
            d.payment_time,
            c.center_name,
            u.name as donor_name
        FROM donations d
        LEFT JOIN centers c ON d.center_id = c.center_id
        LEFT JOIN users u ON d.user_id = u.id
        WHERE d.approval_status = 'Approved'
          AND d.donation_status = 'Paid'
        ORDER BY d.payment_time DESC
    ");
    
    while ($row = $query->fetch_assoc()) {
        $donations[] = [
            "donation_id" => (int)$row['donation_id'],
            "center_id" => (int)$row['center_id'],
            "case_id" => (int)$row['case_id'],
            "center_name" => $row['center_name'],
            "amount" => (float)$row['amount'],
            "requested_time" => $row['requested_time'],
            "user_id" => $row['user_id'] ? (int)$row['user_id'] : null,
            "donor_name" => $row['donor_name'],
            "payment_time" => $row['payment_time']
        ];
    }
}
else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid status. Use: pending, approved, rejected, or paid"
    ]);
    exit;
}

echo json_encode([
    "success" => true,
    "status" => $status,
    "total" => count($donations),
    "donations" => $donations
]);

$conn->close();
?>
