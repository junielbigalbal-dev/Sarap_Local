<?php
// view_debug.php
// Simple script to view the debug log
header('Content-Type: text/plain');
$logFile = __DIR__ . '/debug_log.txt';

if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "No debug log found.";
}
