<?php
// test_db.php
require_once 'db.php';

header('Content-Type: text/plain');

echo "Database Connection Test\n";
echo "------------------------\n";

$start = microtime(true);

try {
    if ($conn->ping()) {
        $connect_time = microtime(true) - $start;
        echo "Connection successful!\n";
        echo "Connection time: " . number_format($connect_time, 4) . " seconds\n";
        
        // Test query
        $query_start = microtime(true);
        $result = $conn->query("SELECT 1");
        $query_time = microtime(true) - $query_start;
        
        if ($result) {
            echo "Simple query (SELECT 1) successful!\n";
            echo "Query time: " . number_format($query_time, 4) . " seconds\n";
        } else {
            echo "Query failed: " . $conn->error . "\n";
        }
        
        // Test user query (simulating login)
        $query_start = microtime(true);
        $stmt = $conn->prepare("SELECT id, email FROM users LIMIT 1");
        $stmt->execute();
        $user_query_time = microtime(true) - $query_start;
        echo "User table query successful!\n";
        echo "User query time: " . number_format($user_query_time, 4) . " seconds\n";
        
    } else {
        echo "Connection ping failed: " . $conn->error . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "------------------------\n";
echo "Test completed.\n";
