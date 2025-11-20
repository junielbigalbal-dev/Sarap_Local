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
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_products':
            $products_query = "SELECT id, product_name, price FROM products WHERE vendor_id = ? AND is_available = 1 ORDER BY product_name ASC";
            $stmt = $conn->prepare($products_query);
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            echo json_encode($products);
            break;
        case 'update_stock':
            $product_id = (int)$_POST['product_id'];
            $new_quantity = (int)$_POST['quantity'];

            // Verify product belongs to vendor
            $verify_query = "SELECT id FROM products WHERE id = ? AND vendor_id = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $product_id, $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception('Product not found or access denied');
            }
            $stmt->close();

            // Update stock quantity
            $update_query = "UPDATE products SET stock_quantity = ?, is_available = ? WHERE id = ? AND vendor_id = ?";
            $is_available = $new_quantity > 0 ? 1 : 0;
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iiii", $new_quantity, $is_available, $product_id, $vendor_id);

            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Stock updated successfully',
                    'is_available' => $is_available,
                    'stock_quantity' => $new_quantity
                ]);
            } else {
                throw new Exception('Failed to update stock');
            }
            break;

        case 'get_low_stock':
            $low_stock_query = "SELECT id, product_name, stock_quantity FROM products WHERE vendor_id = ? AND stock_quantity < 5 AND stock_quantity > 0 ORDER BY stock_quantity ASC";
            $stmt = $conn->prepare($low_stock_query);
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $low_stock = [];
            while ($row = $result->fetch_assoc()) {
                $low_stock[] = $row;
            }

            echo json_encode([
                'success' => true,
                'low_stock' => $low_stock
            ]);
            break;

        case 'get_vendor_badges':
            $badges_query = "SELECT badge_type, badge_name, badge_description, earned_at FROM vendor_badges WHERE vendor_id = ? AND is_active = 1";
            $stmt = $conn->prepare($badges_query);
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $badges = [];
            while ($row = $result->fetch_assoc()) {
                $badges[] = $row;
            }

            echo json_encode([
                'success' => true,
                'badges' => $badges
            ]);
            break;

        case 'create_promotion':
            $promotion_name = trim($_POST['promotion_name']);
            $promotion_type = $_POST['promotion_type'];
            $description = trim($_POST['description']);
            $discount_value = (float)$_POST['discount_value'];
            $min_order_amount = (float)$_POST['min_order_amount'];
            $max_discount_amount = (float)($_POST['max_discount_amount'] ?? 0);
            $start_date = $_POST['start_date'];
            $end_date = $_POST['end_date'];

            if (empty($promotion_name) || empty($promotion_type) || $discount_value <= 0) {
                throw new Exception('Please fill in all required fields');
            }

            $insert_query = "INSERT INTO promotions (vendor_id, promotion_type, promotion_name, description, discount_value, min_order_amount, max_discount_amount, start_date, end_date)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("issddddss", $vendor_id, $promotion_type, $promotion_name, $description, $discount_value, $min_order_amount, $max_discount_amount, $start_date, $end_date);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Promotion created successfully']);
            } else {
                throw new Exception('Failed to create promotion');
            }
            break;

        case 'get_promotions':
            $promotions_query = "SELECT * FROM promotions WHERE vendor_id = ? ORDER BY created_at DESC";
            $stmt = $conn->prepare($promotions_query);
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $promotions = [];
            while ($row = $result->fetch_assoc()) {
                $promotions[] = $row;
            }

            echo json_encode([
                'success' => true,
                'promotions' => $promotions
            ]);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
