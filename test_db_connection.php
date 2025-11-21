<?php
// Turn on error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

$start_time = microtime(true);

echo "<h1>Database Connection Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// 1. Test Connection
echo "<h2>1. Connecting to Database...</h2>";
require_once 'db.php';

$connect_time = microtime(true) - $start_time;
echo "<p>✅ Connected in " . number_format($connect_time, 4) . " seconds.</p>";

// 2. Test Simple Query
echo "<h2>2. Testing Simple Query (SELECT 1)...</h2>";
$query_start = microtime(true);
if ($conn->query("SELECT 1")) {
    $query_time = microtime(true) - $query_start;
    echo "<p>✅ Simple query successful in " . number_format($query_time, 4) . " seconds.</p>";
} else {
    echo "<p>❌ Simple query failed: " . $conn->error . "</p>";
}

// 3. Test Products Query (Count)
echo "<h2>3. Testing Products Count...</h2>";
$query_start = microtime(true);
$result = $conn->query("SELECT COUNT(*) as count FROM products");
if ($result) {
    $row = $result->fetch_assoc();
    $query_time = microtime(true) - $query_start;
    echo "<p>✅ Found " . $row['count'] . " products in " . number_format($query_time, 4) . " seconds.</p>";
} else {
    echo "<p>❌ Products count failed: " . $conn->error . "</p>";
}

// 4. Test Heavy Query (First 10 products)
echo "<h2>4. Testing Heavy Query (LIMIT 10)...</h2>";
$query_start = microtime(true);
$sql = "SELECT p.*, u.business_name as vendor_name 
        FROM products p 
        JOIN users u ON p.vendor_id = u.id 
        LIMIT 10";
$result = $conn->query($sql);

if ($result) {
    $query_time = microtime(true) - $query_start;
    echo "<p>✅ Fetched 10 products in " . number_format($query_time, 4) . " seconds.</p>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['product_name']) . " (by " . htmlspecialchars($row['vendor_name']) . ")</li>";
    }
    echo "</ul>";
} else {
    echo "<p>❌ Heavy query failed: " . $conn->error . "</p>";
}

$total_time = microtime(true) - $start_time;
echo "<hr><p><strong>Total Execution Time: " . number_format($total_time, 4) . " seconds</strong></p>";
?>
