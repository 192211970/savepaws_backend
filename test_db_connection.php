<?php
/**
 * Database Connection Test
 * Upload this file to your server and access it via browser
 * URL: http://180.235.121.253:8087/save_paws_backend/test_db_connection.php
 */

header("Content-Type: application/json");

// Test 1: Check if db.php exists
if (!file_exists("db.php")) {
    echo json_encode([
        "success" => false,
        "error" => "db.php file not found!",
        "test" => "file_exists"
    ]);
    exit;
}

// Test 2: Include db.php and check connection
include("db.php");

// Test 3: Check if connection object exists
if (!isset($conn)) {
    echo json_encode([
        "success" => false,
        "error" => "Database connection object not created",
        "test" => "connection_object"
    ]);
    exit;
}

// Test 4: Check connection error
if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "error" => "Connection failed: " . $conn->connect_error,
        "test" => "connection_error",
        "details" => [
            "host" => $host ?? "not set",
            "user" => $user ?? "not set",
            "dbname" => $dbname ?? "not set"
        ]
    ]);
    exit;
}

// Test 5: Try to query database
$test_query = "SELECT 1 as test";
$result = $conn->query($test_query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "error" => "Query failed: " . $conn->error,
        "test" => "query_execution"
    ]);
    exit;
}

// Test 6: Check if tables exist
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);

$tables = [];
if ($tables_result) {
    while ($row = $tables_result->fetch_array()) {
        $tables[] = $row[0];
    }
}

// Test 7: Count records in main tables
$counts = [];
$main_tables = ['users', 'cases', 'centers', 'donations', 'case_status'];

foreach ($main_tables as $table) {
    if (in_array($table, $tables)) {
        $count_query = "SELECT COUNT(*) as count FROM $table";
        $count_result = $conn->query($count_query);
        if ($count_result) {
            $count_row = $count_result->fetch_assoc();
            $counts[$table] = $count_row['count'];
        } else {
            $counts[$table] = "Error: " . $conn->error;
        }
    } else {
        $counts[$table] = "Table not found";
    }
}

// Success response
echo json_encode([
    "success" => true,
    "message" => "Database connection successful!",
    "database_info" => [
        "host" => $host ?? "not set",
        "user" => $user ?? "not set",
        "database" => $dbname ?? "not set"
    ],
    "tables_found" => count($tables),
    "tables_list" => $tables,
    "record_counts" => $counts,
    "php_version" => phpversion(),
    "server_info" => [
        "software" => $_SERVER['SERVER_SOFTWARE'] ?? "unknown",
        "php_version" => phpversion()
    ]
]);

$conn->close();
?>
