<?php
require_once 'db.php';

echo "MySQL Version: " . $conn->server_info . "\n";

// Try to create a temporary table with a 'role' column and insert into it without quotes
$conn->query("DROP TABLE IF EXISTS test_role_keyword");
$conn->query("CREATE TABLE test_role_keyword (id INT, role VARCHAR(10))");

echo "Testing insert with unquoted 'role' column...\n";
$sql = "INSERT INTO test_role_keyword (id, role) VALUES (1, 'test')";
if ($conn->query($sql)) {
    echo "Success: Unquoted 'role' works.\n";
} else {
    echo "Failure: " . $conn->error . "\n";
}

$conn->query("DROP TABLE IF EXISTS test_role_keyword");
?>
