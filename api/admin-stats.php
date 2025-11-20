<?php
// Simple JSON stats endpoint for the Sarap Local admin dashboard
// Returns high-level counts for users, vendors, and customers

session_start();
header('Content-Type: application/json');

// Basic access control: only allow if admin session flag is present
if (!isset($_SESSION['admin_access'])) {
    http_response_code(403);
    echo json_encode([
        'error' => 'Forbidden',
        'message' => 'Admin access required.'
    ]);
    exit();
}

require_once __DIR__ . '/../db.php';

$stats = [
    'total_users' => 0,
    'total_vendors' => 0,
    'total_customers' => 0,
];

try {
    // Total users (if users table exists)
    $result = $conn->query("SELECT COUNT(*) AS c FROM users");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_users'] = (int)$row['c'];
    }

    // Total vendors
    $result = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'vendor'");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_vendors'] = (int)$row['c'];
    }

    // Total customers
    $result = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'customer'");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['total_customers'] = (int)$row['c'];
    }
} catch (Throwable $e) {
    // On error, keep zeros but include a debug-friendly message
    error_log('Admin stats error: ' . $e->getMessage());
}

echo json_encode($stats);
