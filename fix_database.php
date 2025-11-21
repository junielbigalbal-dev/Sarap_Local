<?php
require_once 'db.php';

echo "Checking database schema...\n";

// Check users table for 'role' column
$table = 'users';
$column = 'role';

$check_query = "SHOW COLUMNS FROM $table LIKE '$column'";
$result = $conn->query($check_query);

if ($result && $result->num_rows > 0) {
    echo "Column '$column' already exists in '$table'.\n";
} else {
    echo "Column '$column' is MISSING in '$table'. Adding it...\n";
    // Add the column
    $alter_query = "ALTER TABLE $table ADD COLUMN role ENUM('customer', 'vendor', 'admin') NOT NULL DEFAULT 'customer' AFTER password";
    if ($conn->query($alter_query)) {
        echo "Successfully added column '$column' to '$table'.\n";
        
        // Add index
        $conn->query("ALTER TABLE $table ADD INDEX idx_role (role)");
        echo "Added index for '$column'.\n";
    } else {
        echo "Error adding column: " . $conn->error . "\n";
    }
}

// Check for other potentially missing columns from the schema
$columns_to_check = [
    'business_name' => "VARCHAR(255)",
    'profile_image' => "VARCHAR(255)",
    'business_logo' => "VARCHAR(255)",
    'bio' => "TEXT",
    'phone' => "VARCHAR(20)",
    'address' => "VARCHAR(500)",
    'latitude' => "DECIMAL(10, 8)",
    'longitude' => "DECIMAL(11, 8)",
    'is_active' => "BOOLEAN DEFAULT 1"
];

foreach ($columns_to_check as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
    if ($check && $check->num_rows == 0) {
        echo "Column '$col' is MISSING. Adding it...\n";
        $conn->query("ALTER TABLE $table ADD COLUMN $col $def");
    }
}

echo "Database fix complete.\n";
?>
