<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test</h1>";

// Get environment variables
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASSWORD');
$db   = getenv('DB_NAME');
$port = getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306;

echo "<h2>Configuration</h2>";
echo "<ul>";
echo "<li><strong>Host:</strong> " . ($host ? $host : "NOT SET") . "</li>";
echo "<li><strong>Port:</strong> " . $port . "</li>";
echo "<li><strong>User:</strong> " . ($user ? $user : "NOT SET") . "</li>";
echo "<li><strong>Password:</strong> " . ($pass ? "SET (Hidden)" : "NOT SET") . "</li>";
echo "<li><strong>Database:</strong> " . ($db ? $db : "NOT SET") . "</li>";
echo "</ul>";

echo "<h2>Connection Attempt</h2>";
echo "<p>Attempting to connect with 5 second timeout...</p>";
flush();
ob_flush();

$start_time = microtime(true);

// Initialize mysqli
$conn = mysqli_init();
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);

try {
    $connected = @$conn->real_connect($host, $user, $pass, $db, $port);
    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 4);

    if ($connected) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS! Connected in $duration seconds.</p>";
        echo "<p>Server Info: " . $conn->server_info . "</p>";
        echo "<p>Host Info: " . $conn->host_info . "</p>";
        
        // Test query
        $result = $conn->query("SHOW TABLES");
        echo "<h3>Tables in Database:</h3>";
        if ($result) {
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: red;'>Failed to list tables: " . $conn->error . "</p>";
        }
        
        $conn->close();
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ FAILED! Duration: $duration seconds.</p>";
        echo "<p><strong>Error Code:</strong> " . $conn->connect_errno . "</p>";
        echo "<p><strong>Error Message:</strong> " . $conn->connect_error . "</p>";
        
        echo "<h3>Troubleshooting Tips:</h3>";
        echo "<ul>";
        echo "<li>Check if the Hostname is correct.</li>";
        echo "<li>Check if the Password is correct.</li>";
        echo "<li>Ensure the database user has permission to connect from this IP.</li>";
        echo "<li>If using Railway, ensure the service is active.</li>";
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ EXCEPTION!</p>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
