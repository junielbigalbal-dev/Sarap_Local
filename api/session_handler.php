<?php
/**
 * Session Handler - Manages instant session validation and redirection
 * Prevents page reload by using AJAX
 */

session_start();
header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Check current session status
if ($action === 'check') {
    if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['logged_in'])) {
        $current_time = time();
        $login_timeout = 3600; // 1 hour
        
        if (isset($_SESSION['login_time']) && ($current_time - $_SESSION['login_time']) < $login_timeout) {
            // Session is valid
            echo json_encode([
                'valid' => true,
                'user_id' => $_SESSION['user_id'],
                'role' => $_SESSION['role'],
                'username' => $_SESSION['username'] ?? '',
                'redirect' => $_SESSION['role'] === 'vendor' ? 'vendor.php' : 'customer.php'
            ]);
        } else {
            // Session expired
            session_unset();
            session_destroy();
            echo json_encode([
                'valid' => false,
                'message' => 'Session expired'
            ]);
        }
    } else {
        echo json_encode([
            'valid' => false,
            'message' => 'Not logged in'
        ]);
    }
    exit;
}

// Validate session for protected pages
if ($action === 'validate') {
    $required_role = $_POST['required_role'] ?? null;
    
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        http_response_code(401);
        echo json_encode([
            'valid' => false,
            'message' => 'Not authenticated',
            'redirect' => 'login.php'
        ]);
        exit;
    }
    
    if ($required_role && $_SESSION['role'] !== $required_role) {
        http_response_code(403);
        echo json_encode([
            'valid' => false,
            'message' => 'Insufficient permissions',
            'redirect' => $_SESSION['role'] === 'vendor' ? 'vendor.php' : 'customer.php'
        ]);
        exit;
    }
    
    echo json_encode([
        'valid' => true,
        'user_id' => $_SESSION['user_id'],
        'role' => $_SESSION['role']
    ]);
    exit;
}

// Logout
if ($action === 'logout') {
    session_unset();
    session_destroy();
    echo json_encode([
        'success' => true,
        'message' => 'Logged out successfully',
        'redirect' => 'index.php'
    ]);
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>
