<?php
/**
 * Customer Orders API
 * Handles order placement and management
 */

session_start();
require_once '../db.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$customer_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            throw new Exception('Invalid request data');
        }

        // Validate required fields
        $product_id = isset($input['product_id']) ? (int)$input['product_id'] : null;
        $vendor_id = isset($input['vendor_id']) ? (int)$input['vendor_id'] : null;
        $order_type = isset($input['order_type']) ? trim($input['order_type']) : 'delivery';
        $delivery_address = isset($input['delivery_address']) ? trim($input['delivery_address']) : null;
        $pickup_time = isset($input['pickup_time']) ? trim($input['pickup_time']) : null;
        $special_instructions = isset($input['special_instructions']) ? trim($input['special_instructions']) : null;

        // Validate order type
        if (!in_array($order_type, ['delivery', 'pickup'])) {
            throw new Exception('Invalid order type');
        }

        // Validate product exists
        $product_query = "SELECT id, price FROM products WHERE id = ? AND vendor_id = ?";
        $stmt = $conn->prepare($product_query);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        $stmt->bind_param("ii", $product_id, $vendor_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            throw new Exception('Product not found');
        }

        // Validate delivery address for delivery orders
        if ($order_type === 'delivery' && empty($delivery_address)) {
            throw new Exception('Delivery address is required for delivery orders');
        }

        // Validate pickup time for pickup orders
        if ($order_type === 'pickup' && empty($pickup_time)) {
            throw new Exception('Pickup time is required for pickup orders');
        }

        // Generate unique order number
        $order_number = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));

        // Calculate total amount (product price + delivery fee if applicable)
        $total_amount = $product['price'];
        $delivery_fee = 0;

        if ($order_type === 'delivery') {
            // Calculate delivery fee based on distance (simplified)
            // In a real app, you'd calculate distance using coordinates
            $delivery_fee = 50; // Fixed fee for now
            $total_amount += $delivery_fee;
        }

        // Create order
        $insert_query = "INSERT INTO orders (order_number, customer_id, vendor_id, total_amount, status, order_notes, created_at) 
                        VALUES (?, ?, ?, ?, 'pending', ?, NOW())";
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $notes = json_encode([
            'order_type' => $order_type,
            'product_id' => $product_id,
            'delivery_address' => $delivery_address,
            'pickup_time' => $pickup_time,
            'special_instructions' => $special_instructions,
            'delivery_fee' => $delivery_fee
        ]);

        $stmt->bind_param("siiis", $order_number, $customer_id, $vendor_id, $total_amount, $notes);

        if ($stmt->execute()) {
            $order_id = $stmt->insert_id;
            $stmt->close();

            // Log the order
            error_log("Order created: $order_number (ID: $order_id) by customer $customer_id");

            // Return success response
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order_number,
                'message' => 'Order placed successfully!'
            ]);
        } else {
            throw new Exception('Failed to create order: ' . $stmt->error);
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Get customer's orders
        $action = isset($_GET['action']) ? trim($_GET['action']) : 'list';

        if ($action === 'list') {
            $query = "SELECT o.*, u.business_name, u.profile_image 
                     FROM orders o 
                     JOIN users u ON o.vendor_id = u.id 
                     WHERE o.customer_id = ? 
                     ORDER BY o.created_at DESC 
                     LIMIT 50";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);

        } elseif ($action === 'detail') {
            $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : null;

            if (!$order_id) {
                throw new Exception('Order ID is required');
            }

            $query = "SELECT o.*, u.business_name, u.profile_image, u.contact_number 
                     FROM orders o 
                     JOIN users u ON o.vendor_id = u.id 
                     WHERE o.id = ? AND o.customer_id = ?";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception('Database error: ' . $conn->error);
            }
            $stmt->bind_param("ii", $order_id, $customer_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$order) {
                throw new Exception('Order not found');
            }

            // Decode order notes
            $order['details'] = json_decode($order['order_notes'], true);

            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        }

    } else {
        throw new Exception('Invalid request method');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    error_log('Order API Error: ' . $e->getMessage());
}

$conn->close();
?>
