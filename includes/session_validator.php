<?php
/**
 * Session Validator - Ensures proper role-based access control
 * Include this file at the top of pages that require authentication
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Validate user session and role
 * @param string $required_role - 'customer', 'vendor', or 'admin'
 * @param string $redirect_page - Page to redirect to on failure (default: login.php)
 */
function validateSession($required_role = null, $redirect_page = 'login.php') {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['logged_in'])) {
        // Clear any partial session data
        session_unset();
        session_destroy();
        session_start();
        
        // Redirect to login with role parameter if specified
        $role_param = $required_role ? '?role=' . urlencode($required_role) : '';
        header("Location: {$redirect_page}{$role_param}");
        exit;
    }

    // Check if role matches required role
    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Clear session to prevent cross-role access
        session_unset();
        session_destroy();
        session_start();
        
        // Redirect to appropriate login page
        header("Location: {$redirect_page}?role=" . urlencode($required_role) . "&error=invalid_role");
        exit;
    }

    // Verify user still exists in database
    require_once __DIR__ . '/../db.php';
    
    $stmt = $conn->prepare("SELECT id, role FROM users WHERE id = ? AND role = ?");
    if ($stmt) {
        $stmt->bind_param('is', $_SESSION['user_id'], $_SESSION['role']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // User no longer exists or role mismatch
            session_unset();
            session_destroy();
            session_start();
            
            header("Location: {$redirect_page}?error=user_not_found");
            exit;
        }
        
        $stmt->close();
    }

    // Prevent caching of authenticated pages
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
}

/**
 * Check if user is logged in without requiring specific role
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['logged_in']);
}

/**
 * Get current user role
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user ID
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Logout user and clear session
 */
function logoutUser() {
    session_unset();
    session_destroy();
    session_start();
    header('Location: index.php');
    exit;
}
