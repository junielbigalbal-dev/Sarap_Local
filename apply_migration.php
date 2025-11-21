<?php
require_once 'db.php';

echo "Applying migration...\n";

$sql = file_get_contents(__DIR__ . '/db/migrations/002_add_verification.sql');

if ($conn->multi_query($sql)) {
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Migration applied successfully!\n";
} else {
    echo "Error applying migration: " . $conn->error . "\n";
}

$conn->close();
?>
