<?php
/**
 * test_approved_donations.php
 * Debug script to check approved donations
 */

header('Content-Type: text/plain');
include("db.php");

echo "=== TESTING APPROVED DONATIONS ===\n\n";

// Test 1: Check all donations
echo "1. ALL DONATIONS:\n";
$query1 = "SELECT donation_id, approval_status, donation_status FROM donations";
$result1 = $conn->query($query1);
if ($result1 && $result1->num_rows > 0) {
    while ($row = $result1->fetch_assoc()) {
        echo "  - Donation #{$row['donation_id']}: approval={$row['approval_status']}, status={$row['donation_status']}\n";
    }
} else {
    echo "  No donations found in database!\n";
}

echo "\n2. APPROVED DONATIONS (not paid):\n";
$query2 = "
    SELECT donation_id, approval_status, donation_status 
    FROM donations 
    WHERE approval_status = 'Approved' 
    AND (donation_status IS NULL OR donation_status != 'Paid')
";
$result2 = $conn->query($query2);
if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        echo "  - Donation #{$row['donation_id']}: approval={$row['approval_status']}, status={$row['donation_status']}\n";
    }
} else {
    echo "  No approved unpaid donations found!\n";
}

echo "\n3. TESTING FULL QUERY WITH JOINS:\n";
$query3 = "
    SELECT 
        d.donation_id,
        d.center_id,
        d.case_id,
        d.image_of_animal,
        d.amount,
        d.requested_time,
        d.approval_status,
        d.donation_status,
        c.center_name,
        c.phone AS center_phone,
        cs.type_of_animal,
        cs.animal_condition,
        cs.photo AS case_photo
    FROM donations d
    LEFT JOIN centers c ON d.center_id = c.center_id
    LEFT JOIN cases cs ON d.case_id = cs.case_id
    WHERE d.approval_status = 'Approved' 
    AND (d.donation_status IS NULL OR d.donation_status != 'Paid')
    ORDER BY d.requested_time DESC
";

$result3 = $conn->query($query3);
if ($result3 && $result3->num_rows > 0) {
    echo "  Found {$result3->num_rows} donation(s):\n";
    while ($row = $result3->fetch_assoc()) {
        echo "  - Donation #{$row['donation_id']}: ₹{$row['amount']} for {$row['type_of_animal']} at {$row['center_name']}\n";
    }
} else {
    echo "  Query returned no results!\n";
    if ($conn->error) {
        echo "  SQL Error: {$conn->error}\n";
    }
}

echo "\n4. CHECK TABLES EXIST:\n";
$tables = ['donations', 'centers', 'cases'];
foreach ($tables as $table) {
    $check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($check && $check->num_rows > 0) {
        echo "  ✓ Table '$table' exists\n";
    } else {
        echo "  ✗ Table '$table' NOT FOUND!\n";
    }
}

$conn->close();
?>
