<?php
// debug_web_schema.php
// Access this file via browser: http://localhost/sarap_local/debug_web_schema.php

require_once 'db.php';

echo "<h1>Web Database Debugger</h1>";

// 1. Connection Details (Masked)
echo "<h2>1. Connection Details</h2>";
echo "Host: " . $conn->host_info . "<br>";
echo "Server Info: " . $conn->server_info . "<br>";
echo "Current Database: " . $db_config['db'] . "<br>";

// 2. Check Users Table Schema
echo "<h2>2. Users Table Schema</h2>";
$table = 'users';
$result = $conn->query("DESCRIBE $table");

if ($result) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        $highlight = ($row['Field'] === 'role') ? 'style="background-color: yellow;"' : '';
        echo "<tr $highlight>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>Error describing table: " . $conn->error . "</p>";
}

// 3. Environment Variables
echo "<h2>3. Environment Variables (DB Related)</h2>";
$env_vars = ['DB_HOST', 'DB_USER', 'DB_PASSWORD', 'DB_PASS', 'DB_NAME', 'DB_PORT'];
echo "<ul>";
foreach ($env_vars as $var) {
    $val = getenv($var);
    echo "<li>$var: " . ($val ? "SET (Length: " . strlen($val) . ")" : "NOT SET") . "</li>";
}
echo "</ul>";
?>
