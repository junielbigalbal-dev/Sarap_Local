<?php
// Start the session
session_start();

// Check if user is actually logged in before attempting logout
if (!isset($_SESSION['user_id']) && !isset($_SESSION['admin_access']) && !isset($_SESSION['vendor_id'])) {
    // User is not logged in, redirect to main page
    header("Location: index.php");
    exit();
}

// Store logout message in a way that doesn't require restarting session
$logout_message = 'You have been successfully logged out.';

// Clear all session data
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session completely
session_destroy();

// Set logout message as a cookie instead of restarting session (more secure)
setcookie('logout_message', $logout_message, time() + 5, '/'); // Cookie expires in 5 seconds

// Redirect to login page after logout
header("Location: login.php");
exit();
?>
