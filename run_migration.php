<?php
/**
 * Database Migration Runner
 * Runs the vendor_reels table migration
 */

require_once 'db.php';

try {
    echo "Starting database migration...\n";
    
    // Read the migration file
    $migration_file = __DIR__ . '/db/migrations/add_vendor_reels_table.sql';
    
    if (!file_exists($migration_file)) {
        throw new Exception("Migration file not found: $migration_file");
    }
    
    $sql = file_get_contents($migration_file);
    
    if (!$sql) {
        throw new Exception("Could not read migration file");
    }
    
    // Split by semicolon to handle multiple statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement)) {
            continue;
        }
        
        echo "Executing: " . substr($statement, 0, 50) . "...\n";
        
        if (!$conn->query($statement)) {
            throw new Exception("Query failed: " . $conn->error . "\nStatement: $statement");
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
    echo "The vendor_reels table has been created.\n";
    
} catch (Exception $e) {
    echo "\n❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
