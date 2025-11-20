<?php
/**
 * Verify Database Tables
 * Checks if all required tables exist
 */

require_once 'db.php';

try {
    echo "Checking database tables...\n\n";
    
    // List of required tables
    $required_tables = [
        'users',
        'products',
        'vendor_reels',
        'favorites',
        'orders',
        'notifications'
    ];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        
        if ($result && $result->num_rows > 0) {
            echo "✅ Table '$table' exists\n";
            
            // Show table structure
            $columns = $conn->query("DESCRIBE $table");
            if ($columns) {
                echo "   Columns: ";
                $col_names = [];
                while ($col = $columns->fetch_assoc()) {
                    $col_names[] = $col['Field'];
                }
                echo implode(", ", $col_names) . "\n";
            }
        } else {
            echo "❌ Table '$table' NOT FOUND\n";
        }
    }
    
    echo "\n✅ Database verification complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
