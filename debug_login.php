<?php
// debug_login.php - Comprehensive login debugging
session_start();

header('Content-Type: text/plain');

echo "=== LOGIN DEBUG INFORMATION ===\n\n";

echo "Current Time: " . date('Y-m-d H:i:s') . "\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "\n\n";

echo "=== SESSION DATA ===\n";
if (empty($_SESSION)) {
    echo "No session data found!\n";
} else {
    foreach ($_SESSION as $key => $value) {
        if ($key === 'password') continue; // Don't show passwords
        echo "$key: " . (is_array($value) ? json_encode($value) : $value) . "\n";
    }
}

echo "\n=== COOKIES ===\n";
if (empty($_COOKIE)) {
    echo "No cookies found!\n";
} else {
    foreach ($_COOKIE as $key => $value) {
        echo "$key: $value\n";
    }
}

echo "\n=== SERVER INFO ===\n";
echo "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A') . "\n";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "HTTPS: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'Yes' : 'No') . "\n";

echo "\n=== SESSION COOKIE PARAMS ===\n";
$params = session_get_cookie_params();
foreach ($params as $key => $value) {
    echo "$key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
}

echo "\n=== DEBUG LOG ===\n";
$logFile = __DIR__ . '/debug_log.txt';
if (file_exists($logFile)) {
    echo file_get_contents($logFile);
} else {
    echo "No debug log found.\n";
}
