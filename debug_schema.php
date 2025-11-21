<?php
require_once 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully\n";

$table = 'users';
$result = $conn->query("DESCRIBE $table");

if ($result) {
    echo "Schema for table '$table':\n";
    echo str_pad("Field", 20) . str_pad("Type", 20) . str_pad("Null", 10) . str_pad("Key", 10) . str_pad("Default", 20) . str_pad("Extra", 20) . "\n";
    echo str_repeat("-", 100) . "\n";
    
    while ($row = $result->fetch_assoc()) {
        echo str_pad($row['Field'], 20) . 
             str_pad($row['Type'], 20) . 
             str_pad($row['Null'], 10) . 
             str_pad($row['Key'], 10) . 
             str_pad($row['Default'] ?? 'NULL', 20) . 
             str_pad($row['Extra'], 20) . "\n";
    }
} else {
    echo "Error describing table: " . $conn->error . "\n";
}

$conn->close();
?>
