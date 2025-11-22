<?php
/**
 * Session Manager
 * Centralized session handling to prevent duplicate sessions and redirect loops
 */

// Configure session BEFORE starting it
if (session_status() === PHP_SESSION_NONE) {
    // Set session configuration BEFORE session_start()
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Explicitly set cookie params to ensure it works across the entire domain
    session_set_cookie_params([
        'lifetime' => 0, // Session cookie
        'path' => '/',
        'domain' => '', // Current domain
        'secure' => isset($_SERVER['HTTPS']), // Only secure if HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);

    // Check for Authorization header (Bearer Token) to support mobile apps
    $headers = getallheaders();
    $auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : null);
    
    if ($auth_header && preg_match('/Bearer\s+(.*)$/i', $auth_header, $matches)) {
        $token = $matches[1];
        if (!empty($token)) {
            session_id($token);
        }
    }

    // Start session
    session_start();
}

/**
 * Initialize session with security headers
 */
function initializeSecureSession() {
    // Set cache headers
    header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('ETag: ' . md5(time()));
    
    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
}

/**
 * Create authenticated session
 */
function createAuthenticatedSession($user_id, $username, $email, $role) {
    // Clear any existing session data first
    $_SESSION = [];
    
    // Set new session data
    $_SESSION['user_id'] = (int)$user_id;
    $_SESSION['username'] = (string)$username;
    $_SESSION['email'] = (string)$email;
    $_SESSION['role'] = (string)$role;
    $_SESSION['authenticated'] = true;
    $_SESSION['login_time'] = time();
    $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['session_created'] = time();
    
    // Regenerate session ID for security
    session_regenerate_id(true);
    
    return true;
}

/**
 * Validate current session
 */
function isSessionValid() {
    // Debug helper
    $log = function($msg) {
        file_put_contents(__DIR__ . '/../debug_log.txt', date('[Y-m-d H:i:s] ') . "SESSION_CHECK: " . $msg . "\n", FILE_APPEND);
    };
    
    $log("Checking session validity. Session ID: " . session_id());
    $log("Session data: " . json_encode($_SESSION));

    // Check if session has required fields
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || !isset($_SESSION['authenticated'])) {
        $log("FAILED: Missing required fields");
        return false;
    }
    
    // Check if authenticated flag is true
    if ($_SESSION['authenticated'] !== true) {
        $log("FAILED: Not authenticated");
        return false;
    }
    
    // Check session timeout (1 hour = 3600 seconds)
    $timeout = 3600;
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        $log("FAILED: Session timeout");
        destroySession();
        return false;
    }
    
    // Check IP address (disabled for cloud deployments where IP can change)
    // Note: Commenting this out for compatibility with load balancers and proxies
    /*
    if (isset($_SESSION['ip_address']) && $_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
        // IP changed, session might be hijacked
        destroySession();
        return false;
    }
    */
    
    // Update last activity time
    $_SESSION['login_time'] = time();
    
    $log("SUCCESS: Session is valid");
    return true;
}

/**
 * Get current user info
 */
function getCurrentUser() {
    if (!isSessionValid()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'email' => $_SESSION['email'],
        'role' => $_SESSION['role']
    ];
}

/**
 * Check if user has specific role
 */
function hasRole($required_role) {
    if (!isSessionValid()) {
        return false;
    }
    
    return $_SESSION['role'] === $required_role;
}

/**
 * Check if user has any of the specified roles
 */
function hasAnyRole($roles) {
    if (!isSessionValid()) {
        return false;
    }
    
    return in_array($_SESSION['role'], $roles);
}

/**
 * Require authentication
 */
function requireAuthentication() {
    if (!isSessionValid()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($required_role) {
    if (!isSessionValid()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
    
    if ($_SESSION['role'] !== $required_role) {
        // User is authenticated but doesn't have the required role
        redirectToDashboard();
        exit();
    }
}

/**
 * Require any of the specified roles
 */
function requireAnyRole($roles) {
    if (!isSessionValid()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], $roles)) {
        redirectToDashboard();
        exit();
    }
}

/**
 * Redirect to appropriate dashboard based on role
 */
function redirectToDashboard() {
    if (!isSessionValid()) {
        header('Location: login.php');
        exit();
    }
    
    $role = $_SESSION['role'];
    
    switch ($role) {
        case 'customer':
            header('Location: customer.php');
            break;
        case 'vendor':
            header('Location: vendor.php');
            break;
        case 'admin':
            header('Location: admin.php');
            break;
        default:
            header('Location: index.php');
    }
    exit();
}

/**
 * Destroy session and logout user
 */
function destroySession() {
    // Clear all session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Logout user
 */
function logoutUser() {
    destroySession();
    header('Location: index.php');
    exit();
}

/**
 * Check if user is already authenticated
 */
function isAlreadyAuthenticated() {
    return isSessionValid();
}

/**
 * Get redirect URL after login
 */
function getRedirectAfterLogin() {
    if (isset($_SESSION['redirect_after_login'])) {
        $redirect = $_SESSION['redirect_after_login'];
        unset($_SESSION['redirect_after_login']);
        return $redirect;
    }
    
    // Default redirect based on role
    if (isSessionValid()) {
        return getDefaultRedirectURL($_SESSION['role']);
    }
    
    return 'index.php';
}

/**
 * Get default redirect URL based on role
 */
function getDefaultRedirectURL($role) {
    switch ($role) {
        case 'customer':
            return 'customer.php';
        case 'vendor':
            return 'vendor.php';
        case 'admin':
            return 'admin.php';
        default:
            return 'index.php';
    }
}

/**
 * Prevent duplicate sessions
 */
function preventDuplicateSessions() {
    // Check if session already exists for this user
    if (isSessionValid()) {
        // Session is valid, no need to create a new one
        return true;
    }
    
    // Session is invalid, clean it up
    destroySession();
    return false;
}

/**
 * Cleanup old sessions
 */
function cleanupOldSessions() {
    // This would typically be called by a cron job
    // For now, we'll just ensure the current session is valid
    if (!isSessionValid()) {
        destroySession();
    }
}
