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
    // Debug helper
    $log = function($msg) {
        file_put_contents(__DIR__ . '/../debug_log.txt', date('[Y-m-d H:i:s] ') . "AUTH: " . $msg . "\n", FILE_APPEND);
    };

    $log("Starting authentication for $email");

    // Check rate limiting
    $rate_limit = isLoginRateLimited($email);
    if ($rate_limit['limited']) {
        $log("Rate limited");
        return [
            'success' => false,
            'message' => 'Too many login attempts. Please try again in ' . $rate_limit['remaining'] . ' minutes.'
        ];
    }

    // Validate email format
    if (!isValidEmail($email)) {
        $log("Invalid email format");
        recordFailedLoginAttempt($email);
        return [
            'success' => false,
            'message' => 'Invalid email format.'
        ];
    }

    try {
        // Query user by email
        $log("Querying user from DB...");
        $query = "SELECT id, username, email, password, role FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            $log("DB Prepare failed: " . $conn->error);
            return [
                'success' => false,
                'message' => 'Database error. Please try again.'
            ];
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $log("Query executed. Rows: " . $result->num_rows);

        if ($result->num_rows === 0) {
            $log("User not found");
            recordFailedLoginAttempt($email);
            return [
                'success' => false,
                'message' => 'Invalid email or password.'
            ];
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Verify password
        $log("Verifying password...");
        if (!password_verify($password, $user['password'])) {
            $log("Password verification failed");
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
        
        $log("Creating session...");
        createAuthenticatedSession(
            $user['id'],
            $user['username'],
            $user['email'],
            $user['role']
        );
        $log("Session created");

        return [
            'success' => true,
            'message' => 'Login successful.',
            'role' => $user['role'],
            'user_id' => $user['id']
        ];

    } catch (Exception $e) {
        $log("Exception: " . $e->getMessage());
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
// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * Send verification email
 */
function sendVerificationEmail($email, $code) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST');
        $mail->SMTPAuth   = true;
        $mail->Username   = getenv('SMTP_USER');
        $mail->Password   = getenv('SMTP_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = getenv('SMTP_PORT') ?: 587;

        // Recipients
        $mail->setFrom(getenv('SMTP_USER'), 'Sarap Local');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify your Sarap Local Account';
        $mail->Body    = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #C46A2B;'>Welcome to Sarap Local!</h2>
                <p>Please use the following code to verify your account:</p>
                <div style='background-color: #f8f9fa; padding: 15px; text-align: center; font-size: 24px; font-weight: bold; letter-spacing: 5px; margin: 20px 0;'>
                    {$code}
                </div>
                <p>This code will expire in 15 minutes.</p>
                <p>If you didn't request this, please ignore this email.</p>
            </div>
        ";
        $mail->AltBody = "Your verification code is: {$code}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

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

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Generate verification code
        $verification_code = sprintf("%06d", mt_rand(1, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Insert user with verification code
        $insert_query = "INSERT INTO users (`username`, `email`, `password`, `role`, `created_at`, `verification_code`, `is_verified`, `verification_expires_at`) VALUES (?, ?, ?, ?, NOW(), ?, 0, ?)";
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            return [
                'success' => false,
                'errors' => ['Database error. Please try again.']
            ];
        }

        $stmt->bind_param("ssssss", $username, $email, $hashed_password, $role, $verification_code, $expires_at);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return [
                'success' => false,
                'errors' => ['Registration failed. Please try again.']
            ];
        }

        $stmt->close();

        // Send verification email
        if (sendVerificationEmail($email, $verification_code)) {
            return [
                'success' => true,
                'message' => 'Registration successful! Please check your email for the verification code.',
                'email' => $email // Return email for redirect
            ];
        } else {
            // If email fails, we still register them but they might need to resend code
            return [
                'success' => true,
                'message' => 'Registration successful, but failed to send verification email. Please try logging in to resend.',
                'email' => $email
            ];
        }

    } catch (Exception $e) {
        error_log('Registration Error: ' . $e->getMessage());
        return [
            'success' => false,
            'errors' => ['An error occurred: ' . $e->getMessage()]
        ];
    }
}

/**
 * Verify account
 */
function verifyAccount($conn, $email, $code) {
    try {
        $query = "SELECT id, verification_code, verification_expires_at FROM users WHERE email = ? AND is_verified = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Invalid email or account already verified.'];
        }

        $user = $result->fetch_assoc();

        if ($user['verification_code'] !== $code) {
            return ['success' => false, 'message' => 'Invalid verification code.'];
        }

        if (strtotime($user['verification_expires_at']) < time()) {
            return ['success' => false, 'message' => 'Verification code has expired. Please request a new one.'];
        }

        // Mark as verified
        $update_query = "UPDATE users SET is_verified = 1, verification_code = NULL, verification_expires_at = NULL WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $user['id']);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Account verified successfully! You can now log in.'];
        } else {
            return ['success' => false, 'message' => 'Database error during verification.'];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'An error occurred.'];
    }
}

/**
 * Resend verification code
 */
function resendVerificationCode($conn, $email) {
    try {
        $query = "SELECT id FROM users WHERE email = ? AND is_verified = 0";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Email not found or already verified.'];
        }

        $verification_code = sprintf("%06d", mt_rand(1, 999999));
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        $update_query = "UPDATE users SET verification_code = ?, verification_expires_at = ? WHERE email = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sss", $verification_code, $expires_at, $email);
        $stmt->execute();

        if (sendVerificationEmail($email, $verification_code)) {
            return ['success' => true, 'message' => 'New verification code sent.'];
        } else {
            return ['success' => false, 'message' => 'Failed to send email. Please try again later.'];
        }

    } catch (Exception $e) {
        return ['success' => false, 'message' => 'An error occurred.'];
    }
}
