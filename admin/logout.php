<?php
// Start the session
session_start();

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Store logout message for confirmation
$logout_message = 'Admin logout successful.';

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

// Set logout message as a cookie (expires in 5 seconds)
setcookie('logout_message', $logout_message, time() + 5, '/');

// Clear any admin-specific cookies if they exist
if (isset($_COOKIE['admin_access'])) {
    setcookie('admin_access', '', time() - 42000, '/');
}
if (isset($_COOKIE['user_type'])) {
    setcookie('user_type', '', time() - 42000, '/');
}

// Redirect to main page
header("Location: ../index.php");
exit();
?>
