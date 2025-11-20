<?php
include '../db.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in and is a vendor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$vendor_id = (int)$_SESSION['user_id'];

try {
    // Get the last order timestamp from session or use a default
    $last_check = $_SESSION['last_order_check'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));

    // Get new orders since last check
    $orders_query = "SELECT o.id, o.order_number, o.total_amount, o.created_at, u.username as customer_name
                     FROM orders o
                     JOIN users u ON o.customer_id = u.id
                     WHERE o.vendor_id = ? AND o.created_at > ?
                     ORDER BY o.created_at DESC";

    $stmt = $conn->prepare($orders_query);
    $stmt->bind_param("is", $vendor_id, $last_check);
    $stmt->execute();
    $result = $stmt->get_result();

    $new_orders = [];
    while ($row = $result->fetch_assoc()) {
        $new_orders[] = [
            'id' => $row['id'],
            'order_number' => $row['order_number'],
            'total_amount' => $row['total_amount'],
            'customer_name' => $row['customer_name'],
            'created_at' => $row['created_at']
        ];
    }

    $stmt->close();

    // Update last check timestamp
    $_SESSION['last_order_check'] = date('Y-m-d H:i:s');

    echo json_encode([
        'success' => true,
        'new_orders' => $new_orders,
        'last_check' => $_SESSION['last_order_check']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking for new orders: ' . $e->getMessage()
    ]);
}
?>
