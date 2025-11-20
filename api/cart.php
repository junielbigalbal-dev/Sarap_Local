<?php
/**
 * Shopping Cart API
 * Handles cart operations: add, remove, update, get
 */

session_start();
require_once '../db.php';
require_once '../includes/api-response.php';
require_once '../includes/validators.php';

// Check if user is logged in as customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    sendUnauthorized('Please log in as a customer');
}

$customer_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

try {
    switch ($action) {
        case 'add':
            // Add item to cart
            $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;

            if ($product_id <= 0 || $quantity <= 0) {
                sendValidationError(['product_id' => 'Invalid product']);
            }

            // Check product exists and is available
            $check_query = "SELECT id, stock_quantity, price FROM products WHERE id = ? AND is_available = 1";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$product) {
                sendNotFound('Product not found or unavailable');
            }

            if ($product['stock_quantity'] < $quantity) {
                sendError('Insufficient stock', 400, ['stock' => 'Not enough items available']);
            }

            // Add to cart
            $insert_query = "INSERT INTO cart (customer_id, product_id, quantity) 
                            VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE quantity = quantity + ?";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param('iiii', $customer_id, $product_id, $quantity, $quantity);
            $stmt->execute();
            $stmt->close();

            sendSuccess(['product_id' => $product_id, 'quantity' => $quantity], 'Item added to cart', 201);
            break;

        case 'remove':
            // Remove item from cart
            $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

            if ($product_id <= 0) {
                sendValidationError(['product_id' => 'Invalid product']);
            }

            $delete_query = "DELETE FROM cart WHERE customer_id = ? AND product_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param('ii', $customer_id, $product_id);
            $stmt->execute();
            $stmt->close();

            sendSuccess([], 'Item removed from cart');
            break;

        case 'update':
            // Update cart item quantity
            $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
            $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 0;

            if ($product_id <= 0) {
                sendValidationError(['product_id' => 'Invalid product']);
            }

            if ($quantity <= 0) {
                // Remove item if quantity is 0 or less
                $delete_query = "DELETE FROM cart WHERE customer_id = ? AND product_id = ?";
                $stmt = $conn->prepare($delete_query);
                $stmt->bind_param('ii', $customer_id, $product_id);
                $stmt->execute();
                $stmt->close();
            } else {
                // Check stock
                $check_query = "SELECT stock_quantity FROM products WHERE id = ?";
                $stmt = $conn->prepare($check_query);
                $stmt->bind_param('i', $product_id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$product || $product['stock_quantity'] < $quantity) {
                    sendError('Insufficient stock', 400);
                }

                // Update quantity
                $update_query = "UPDATE cart SET quantity = ? WHERE customer_id = ? AND product_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param('iii', $quantity, $customer_id, $product_id);
                $stmt->execute();
                $stmt->close();
            }

            sendSuccess([], 'Cart updated');
            break;

        case 'get':
            // Get cart items
            $query = "SELECT c.product_id, c.quantity, p.product_name, p.price, p.image, 
                             u.business_name, u.id as vendor_id
                      FROM cart c
                      JOIN products p ON c.product_id = p.id
                      JOIN users u ON p.vendor_id = u.id
                      WHERE c.customer_id = ?
                      ORDER BY c.created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $items = [];
            $total = 0;
            $item_count = 0;

            while ($row = $result->fetch_assoc()) {
                $subtotal = (float)$row['price'] * (int)$row['quantity'];
                $total += $subtotal;
                $item_count += (int)$row['quantity'];

                $items[] = [
                    'product_id' => (int)$row['product_id'],
                    'name' => htmlspecialchars($row['product_name']),
                    'price' => (float)$row['price'],
                    'quantity' => (int)$row['quantity'],
                    'subtotal' => $subtotal,
                    'image' => htmlspecialchars($row['image']),
                    'vendor' => [
                        'id' => (int)$row['vendor_id'],
                        'name' => htmlspecialchars($row['business_name'])
                    ]
                ];
            }
            $stmt->close();

            sendSuccess([
                'items' => $items,
                'total' => $total,
                'item_count' => $item_count
            ], 'Cart retrieved');
            break;

        case 'clear':
            // Clear entire cart
            $delete_query = "DELETE FROM cart WHERE customer_id = ?";
            $stmt = $conn->prepare($delete_query);
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $stmt->close();

            sendSuccess([], 'Cart cleared');
            break;

        default:
            sendError('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log('Cart API Error: ' . $e->getMessage());
    sendServerError('Cart operation failed');
}
