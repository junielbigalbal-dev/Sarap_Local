<?php
/**
 * Authentication Helper Functions
 * Handles login, session management, CSRF protection, and role-based access control
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include validators
require_once __DIR__ . '/validators.php';

// ============================================
// CSRF TOKEN GENERATION & VALIDATION
// ============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ============================================
// RATE LIMITING
// ============================================

/**
 * Check if login is rate limited
 */
function isLoginRateLimited($email) {
    $max_attempts = 5;
    $lockout_time = 900; // 15 minutes in seconds

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $now = time();
    $email_hash = hash('sha256', $email);

    // Clean old attempts
    foreach ($_SESSION['login_attempts'] as $key => $attempt) {
        if ($now - $attempt['time'] > $lockout_time) {
            unset($_SESSION['login_attempts'][$key]);
        }
    }

    // Check if locked out
    if (isset($_SESSION['login_attempts'][$email_hash])) {
        $attempts = $_SESSION['login_attempts'][$email_hash];
        if ($attempts['count'] >= $max_attempts && ($now - $attempts['time']) < $lockout_time) {
            $remaining = $lockout_time - ($now - $attempts['time']);
            return [
                'limited' => true,
                'remaining' => ceil($remaining / 60) // minutes
            ];
        }
    }

    return ['limited' => false];
}

/**
 * Record failed login attempt
 */
function recordFailedLoginAttempt($email) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $email_hash = hash('sha256', $email);
    $now = time();

    if (!isset($_SESSION['login_attempts'][$email_hash])) {
        $_SESSION['login_attempts'][$email_hash] = [
            'count' => 1,
            'time' => $now
        ];
    } else {
        $_SESSION['login_attempts'][$email_hash]['count']++;
        $_SESSION['login_attempts'][$email_hash]['time'] = $now;
    }
}

/**
 * Clear login attempts for email
 */
function clearLoginAttempts($email) {
    if (isset($_SESSION['login_attempts'])) {
        $email_hash = hash('sha256', $email);
        unset($_SESSION['login_attempts'][$email_hash]);
    }
}

// Note: Validation functions are now in validators.php

// ============================================
// AUTHENTICATION FUNCTIONS
// ============================================

/**
 * Authenticate user and create session
 */
function authenticateUser($conn, $email, $password) {
    // Check rate limiting
    $rate_limit = isLoginRateLimited($email);
    if ($rate_limit['limited']) {
        return [
            'success' => false,
            'message' => 'Too many login attempts. Please try again in ' . $rate_limit['remaining'] . ' minutes.'
        ];
    }

    // Validate email format
    if (!isValidEmail($email)) {
        recordFailedLoginAttempt($email);
        return [
            'success' => false,
            'message' => 'Invalid email format.'
        ];
    }

    try {
        // Query user by email
        $query = "SELECT id, username, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Database error. Please try again.'
            ];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            recordFailedLoginAttempt($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        if (!password_verify($password, $user['password'])) {
            recordFailedLoginAttempt($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        // Clear failed attempts
        clearLoginAttempts($email);

        // Create authenticated session using session manager
        // First, require the session manager if not already loaded
        if (!function_exists('createAuthenticatedSession')) {
            require_once __DIR__ . '/session-manager.php';
        }
        
        createAuthenticatedSession(
            $user['id'],
            $user['username'],
            $user['email'],
            $user['role']
        );

        return [
            'success' => true,
            'message' => 'Login successful.',
            'role' => $user['role'],
            'user_id' => $user['id']
        ];

    } catch (Exception $e) {
        error_log('Authentication Error: ' . $e->getMessage());
        return [
            'success' => false,
            'message' => 'An error occurred. Please try again.'
        ];
    }
}

// ============================================
// SESSION VALIDATION & PROTECTION
// ============================================

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Get current user role
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user ID
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Validate session (check IP, timeout, etc.)
 */
function validateSession() {
    // Check if session exists
    if (!isLoggedIn()) {
        return false;
    }

    // Check session timeout (1 hour)
    $timeout = 3600;
    if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout) {
        session_destroy();
        return false;
    }

    // Update last activity time
    $_SESSION['login_time'] = time();

    return true;
}

/**
 * Require login with role check
 */
function requireLogin($allowed_roles = []) {
    if (!validateSession()) {
        $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php');
        exit();
    }

    if (!empty($allowed_roles) && !in_array(getUserRole(), $allowed_roles)) {
        // Redirect to correct dashboard based on role
        redirectToDashboard();
    }
}

/**
 * NOTE: redirectToDashboard() and logoutUser() are now in session-manager.php
 * to avoid duplication and ensure centralized session management.
 * These functions are kept here for backward compatibility but should not be used.
 * Use session-manager.php functions instead.
 */

// ============================================
// REGISTRATION FUNCTIONS
// ============================================

/**
 * Register new user
 */
function registerUser($conn, $username, $email, $password, $confirm_password, $role) {
    // Validate inputs
    $errors = [];

    if (!isValidUsername($username)) {
        $errors[] = 'Username must be 3-20 characters, alphanumeric and underscore only.';
    }

    if (!isValidEmail($email)) {
        $errors[] = 'Invalid email format.';
    }

    if (!isValidPassword($password)) {
        $errors[] = 'Password must be at least 8 characters with uppercase, lowercase, and numbers.';
    }

    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (!in_array($role, ['customer', 'vendor'])) {
        $errors[] = 'Invalid role selected.';
    }

    if (!empty($errors)) {
        return [
            'success' => false,
            'errors' => $errors
        ];
    }

    try {
        // Check if email already exists
        $check_query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($check_query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'errors' => ['Database error. Please try again.']
            ];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            return [
                'success' => false,
                'errors' => ['Email already registered.']
            ];
        }
        $stmt->close();

        // Check if username already exists
        $check_query = "SELECT id FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'errors' => ['Database error. Please try again.']
            ];
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            return [
                'success' => false,
                'errors' => ['Username already taken.']
            ];
        }
        $stmt->close();

        // Hash password using PASSWORD_DEFAULT (currently bcrypt, but future-proof)
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user
        $insert_query = "INSERT INTO users (`username`, `email`, `password`, `role`, `created_at`) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'errors' => ['Database error. Please try again.']
            ];
        }

        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }

        $stmt->close();

        return [
            'success' => true,
            'message' => 'Registration successful! Please log in.'
        ];

    } catch (Exception $e) {
        error_log('Registration Error: ' . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['An error occurred: ' . $e->getMessage()]
        ];
    }
}
