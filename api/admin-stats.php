<?php
// Simple JSON stats endpoint for the Sarap Local admin dashboard
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
