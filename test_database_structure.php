<?php
header("Content-Type: application/json");
include("db.php");

$response = [];

// Test 1: Check if connection works
if ($conn) {
    $response['connection'] = 'SUCCESS ✅';
    $response['database'] = $dbname;
} else {
    $response['connection'] = 'FAILED ❌';
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

// Test 2: Check if tables exist
$tables = ['users', 'centers', 'cases', 'case_status', 'donations', 'payments'];
$response['tables'] = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result && $result->num_rows > 0) {
        $response['tables'][$table] = 'EXISTS ✅';
        
        // Count rows
        $countResult = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($countResult) {
            $count = $countResult->fetch_assoc()['count'];
            $response['tables'][$table . '_count'] = $count;
        }
    } else {
        $response['tables'][$table] = 'MISSING ❌';
    }
}

// Test 3: Check centers table structure
$result = $conn->query("DESCRIBE centers");
if ($result) {
    $response['centers_columns'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['centers_columns'][] = $row['Field'];
    }
    
    // Test 4: Check if user_id column exists in centers
    if (in_array('user_id', $response['centers_columns'])) {
        $response['user_id_column'] = 'EXISTS ✅';
    } else {
        $response['user_id_column'] = 'MISSING ❌ - THIS IS THE PROBLEM!';
    }
} else {
    $response['centers_table'] = 'ERROR: Cannot describe table';
}

// Test 5: Sample data from centers
$result = $conn->query("SELECT center_id, user_id, center_name FROM centers LIMIT 5");
if ($result) {
    $response['sample_centers'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['sample_centers'][] = $row;
    }
} else {
    $response['sample_centers'] = 'ERROR: ' . $conn->error;
}

// Test 6: Check users table
$result = $conn->query("SELECT id, name, email, user_type FROM users LIMIT 5");
if ($result) {
    $response['sample_users'] = [];
    while ($row = $result->fetch_assoc()) {
        $response['sample_users'][] = $row;
    }
} else {
    $response['sample_users'] = 'ERROR: ' . $conn->error;
}

// Test 7: PHP Version
$response['php_version'] = phpversion();

// Test 8: MySQL Version
$result = $conn->query("SELECT VERSION() as version");
if ($result) {
    $response['mysql_version'] = $result->fetch_assoc()['version'];
}

// Test 9: Summary
$response['summary'] = [];
if ($response['tables']['users_count'] == 0) {
    $response['summary'][] = '❌ PROBLEM: Database is EMPTY! You need to import your database.';
} else {
    $response['summary'][] = '✅ Database has data';
}

if ($response['user_id_column'] == 'MISSING ❌ - THIS IS THE PROBLEM!') {
    $response['summary'][] = '❌ PROBLEM: centers table missing user_id column!';
}

echo json_encode($response, JSON_PRETTY_PRINT);
$conn->close();
?>
