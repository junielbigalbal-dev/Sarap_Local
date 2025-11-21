<?php
session_start();

echo "<h1>Session Debugger</h1>";

// 1. Check Session Path
$savePath = session_save_path();
echo "<p><strong>Session Save Path:</strong> " . ($savePath ?: 'Default (/tmp)') . "</p>";
echo "<p><strong>Writable?</strong> " . (is_writable($savePath ?: '/tmp') ? 'Yes' : 'No') . "</p>";

// 2. Check Session ID
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";

// 3. Test Session Persistence
if (!isset($_SESSION['test_count'])) {
    $_SESSION['test_count'] = 0;
    echo "<p>Initializing test count...</p>";
} else {
    $_SESSION['test_count']++;
    echo "<p>Session is working! Count: " . $_SESSION['test_count'] . "</p>";
}

// 4. Check CSRF Token
if (isset($_SESSION['csrf_token'])) {
    echo "<p><strong>CSRF Token in Session:</strong> " . htmlspecialchars($_SESSION['csrf_token']) . "</p>";
} else {
    echo "<p><strong>CSRF Token in Session:</strong> NOT SET</p>";
}

echo "<p><a href='debug_session.php'>Reload Page</a> (Count should increase)</p>";
?>
