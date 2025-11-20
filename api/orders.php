<?php
/**
 * Orders API
 * Handles order creation, retrieval, and status updates
 */

session_start();
require_once '../db.php';
require_once '../includes/api-response.php';
require_once '../includes/validators.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendUnauthorized('Please log in');
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

try {
    switch ($action) {
        case 'create':
            // Create new order (customer only)
            if ($role !== 'customer') {
                sendForbidden('Only customers can create orders');
            }

            $data = getJsonBody();
            $items = $data['items'] ?? [];
            $delivery_address = $data['delivery_address'] ?? '';
            $customer_notes = $data['customer_notes'] ?? '';
            $payment_method = $data['payment_method'] ?? 'cash';

            // Validate
            if (empty($items) || !is_array($items)) {
                sendValidationError(['items' => 'No items in order']);
            }

            if (empty($delivery_address)) {
                sendValidationError(['delivery_address' => 'Delivery address required']);
            }

            if (!in_array($payment_method, ['cash', 'card', 'online'])) {
                sendValidationError(['payment_method' => 'Invalid payment method']);
            }

            // Start transaction
            $conn->begin_transaction();

            try {
                // Group items by vendor
                $vendors = [];
                $total_amount = 0;

                foreach ($items as $item) {
                    $product_id = (int)$item['product_id'];
                    $quantity = (int)$item['quantity'];

                    if ($quantity <= 0) {
                        throw new Exception('Invalid quantity');
                    }

                    // Get product and vendor
                    $query = "SELECT id, vendor_id, price, stock_quantity FROM products WHERE id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->bind_param('i', $product_id);
                    $stmt->execute();
                    $product = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if (!$product) {
                        throw new Exception('Product not found');
                    }

                    if ($product['stock_quantity'] < $quantity) {
                        throw new Exception('Insufficient stock for product ID ' . $product_id);
                    }

                    $vendor_id = $product['vendor_id'];
                    $subtotal = (float)$product['price'] * $quantity;
                    $total_amount += $subtotal;

                    if (!isset($vendors[$vendor_id])) {
                        $vendors[$vendor_id] = [];
                    }

                    $vendors[$vendor_id][] = [
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'price' => (float)$product['price'],
                        'subtotal' => $subtotal
                    ];
                }

                // Create orders for each vendor
                $order_ids = [];
                $order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);

                foreach ($vendors as $vendor_id => $vendor_items) {
                    $vendor_total = array_sum(array_column($vendor_items, 'subtotal'));

                    // Create order
                    $insert_query = "INSERT INTO orders (customer_id, vendor_id, order_number, total_amount, 
                                    delivery_address, customer_notes, payment_method, status)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
                    $stmt = $conn->prepare($insert_query);
                    $stmt->bind_param('iisdsss', $user_id, $vendor_id, $order_number, $vendor_total, 
                                     $delivery_address, $customer_notes, $payment_method);
                    $stmt->execute();
                    $order_id = $conn->insert_id;
                    $stmt->close();

                    $order_ids[] = $order_id;

                    // Add order items
                    foreach ($vendor_items as $item) {
                        $insert_item_query = "INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
                                            VALUES (?, ?, ?, ?, ?)";
                        $stmt = $conn->prepare($insert_item_query);
                        $stmt->bind_param('iiidd', $order_id, $item['product_id'], $item['quantity'], 
                                         $item['price'], $item['subtotal']);
                        $stmt->execute();
                        $stmt->close();

                        // Update stock
                        $update_stock = "UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?";
                        $stmt = $conn->prepare($update_stock);
                        $stmt->bind_param('ii', $item['quantity'], $item['product_id']);
                        $stmt->execute();
                        $stmt->close();
                    }

                    // Create notification for vendor
                    $notif_query = "INSERT INTO notifications (user_id, type, title, message, link)
                                   VALUES (?, 'order', 'New Order', ?, ?)";
                    $message = 'You have a new order #' . $order_number;
                    $link = 'vendor.php?order=' . $order_id;
                    $stmt = $conn->prepare($notif_query);
                    $stmt->bind_param('iss', $vendor_id, $message, $link);
                    $stmt->execute();
                    $stmt->close();
                }

                // Clear cart
                $clear_query = "DELETE FROM cart WHERE customer_id = ?";
                $stmt = $conn->prepare($clear_query);
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();

                $conn->commit();

                sendSuccess([
                    'order_ids' => $order_ids,
                    'order_number' => $order_number,
                    'total_amount' => $total_amount
                ], 'Order created successfully', 201);

            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
            break;

        case 'get':
            // Get orders for current user
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $per_page = 20;
            $offset = ($page - 1) * $per_page;

            if ($role === 'customer') {
                // Customer orders
                $query = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                                 u.business_name as vendor_name, u.id as vendor_id
                          FROM orders o
                          JOIN users u ON o.vendor_id = u.id
                          WHERE o.customer_id = ?
                          ORDER BY o.created_at DESC
                          LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $user_id, $per_page, $offset);
            } else if ($role === 'vendor') {
                // Vendor orders
                $query = "SELECT o.id, o.order_number, o.total_amount, o.status, o.created_at,
                                 u.username as customer_name, u.id as customer_id
                          FROM orders o
                          JOIN users u ON o.customer_id = u.id
                          WHERE o.vendor_id = ?
                          ORDER BY o.created_at DESC
                          LIMIT ? OFFSET ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iii', $user_id, $per_page, $offset);
            } else {
                sendForbidden('Invalid role');
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = [
                    'id' => (int)$row['id'],
                    'number' => htmlspecialchars($row['order_number']),
                    'total' => (float)$row['total_amount'],
                    'status' => htmlspecialchars($row['status']),
                    'created_at' => $row['created_at'],
                    'other_party' => htmlspecialchars($row[$role === 'customer' ? 'vendor_name' : 'customer_name']),
                    'other_party_id' => (int)$row[$role === 'customer' ? 'vendor_id' : 'customer_id']
                ];
            }
            $stmt->close();

            // Get total count
            $count_query = $role === 'customer' 
                ? "SELECT COUNT(*) as total FROM orders WHERE customer_id = ?"
                : "SELECT COUNT(*) as total FROM orders WHERE vendor_id = ?";
            $stmt = $conn->prepare($count_query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $total = $stmt->get_result()->fetch_assoc()['total'];
            $stmt->close();

            sendPaginated($orders, $total, $page, $per_page, 'Orders retrieved');
            break;

        case 'detail':
            // Get order details
            $order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

            if ($order_id <= 0) {
                sendValidationError(['id' => 'Invalid order ID']);
            }

            // Get order
            $query = "SELECT * FROM orders WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order) {
                sendNotFound('Order not found');
            }

            // Check access
            if ($role === 'customer' && $order['customer_id'] != $user_id) {
                sendForbidden('You do not have access to this order');
            }
            if ($role === 'vendor' && $order['vendor_id'] != $user_id) {
                sendForbidden('You do not have access to this order');
            }

            // Get order items
            $items_query = "SELECT oi.*, p.product_name, p.image
                           FROM order_items oi
                           JOIN products p ON oi.product_id = p.id
                           WHERE oi.order_id = ?";
            $stmt = $conn->prepare($items_query);
            $stmt->bind_param('i', $order_id);
            $stmt->execute();
            $items_result = $stmt->get_result();

            $items = [];
            while ($row = $items_result->fetch_assoc()) {
                $items[] = [
                    'product_id' => (int)$row['product_id'],
                    'name' => htmlspecialchars($row['product_name']),
                    'quantity' => (int)$row['quantity'],
                    'price' => (float)$row['price'],
                    'subtotal' => (float)$row['subtotal'],
                    'image' => htmlspecialchars($row['image'])
                ];
            }
            $stmt->close();

            sendSuccess([
                'order' => [
                    'id' => (int)$order['id'],
                    'number' => htmlspecialchars($order['order_number']),
                    'total' => (float)$order['total_amount'],
                    'status' => htmlspecialchars($order['status']),
                    'payment_method' => htmlspecialchars($order['payment_method']),
                    'delivery_address' => htmlspecialchars($order['delivery_address']),
                    'customer_notes' => htmlspecialchars($order['customer_notes']),
                    'created_at' => $order['created_at'],
                    'updated_at' => $order['updated_at']
                ],
                'items' => $items
            ], 'Order details retrieved');
            break;

        default:
            sendError('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log('Orders API Error: ' . $e->getMessage());
    sendServerError('Order operation failed: ' . $e->getMessage());
}
