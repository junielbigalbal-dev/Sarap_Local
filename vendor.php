<?php
// CRITICAL: Start session FIRST before any other code
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CRITICAL: Set cache headers BEFORE any output
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: ' . md5(time()));

require_once 'db.php';
require_once 'includes/session-manager.php';  // Load session manager FIRST
require_once 'includes/auth.php';              // Then load auth
require_once 'includes/navigation.php';

// Initialize secure session
initializeSecureSession();

// Store current page in history for back button functionality
storeCurrentPage('Vendor Dashboard');

// Require authentication and vendor role
requireRole('vendor');

// Initialize variables
$vendor_id = $_SESSION['user_id'];
$error = '';
$message = '';
$products = [];
$total_pages = 1;
$page = 1;

// Get vendor details from users table
try {
    $vendor_query = "SELECT * FROM users WHERE id = ? AND role = 'vendor' LIMIT 1";
    $stmt = $conn->prepare($vendor_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $vendor_result = $stmt->get_result();

    if ($vendor_result->num_rows === 0) {
        // Vendor not found or not a vendor
        session_destroy();
        header("Location: login.php?error=invalid_vendor");
        exit();
    }

    $vendor = $vendor_result->fetch_assoc();
    $stmt->close();

    // Dashboard Analytics Functions
    function getVendorSalesData($vendor_id) {
        global $conn;

        try {
            // Get total sales for the last 30 days
            $sales_query = "SELECT SUM(o.total_amount) as total_sales, COUNT(*) as total_orders
                           FROM orders o
                           WHERE o.vendor_id = ? AND o.status NOT IN ('cancelled', 'pending')
                           AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $stmt = $conn->prepare($sales_query);
            if (!$stmt) {
                throw new Exception('Failed to prepare sales query: ' . $conn->error);
            }
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $sales_result = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return [
                'total_sales' => (float)($sales_result['total_sales'] ?? 0),
                'total_orders' => (int)($sales_result['total_orders'] ?? 0)
            ];
        } catch (Exception $e) {
            error_log('Sales Data Error: ' . $e->getMessage());
            return ['total_sales' => 0, 'total_orders' => 0];
        }
    }

    function getTopSellingProducts($vendor_id, $limit = 5) {
        global $conn;

        try {
            // Get top selling products for the last 30 days
            $products_query = "SELECT p.id, p.product_name, p.image,
                              SUM(oi.quantity) as total_quantity,
                              SUM(oi.subtotal) as total_revenue,
                              COUNT(DISTINCT o.id) as order_count
                              FROM products p
                              JOIN order_items oi ON p.id = oi.product_id
                              JOIN orders o ON oi.order_id = o.id
                              WHERE p.vendor_id = ? AND o.status NOT IN ('cancelled', 'pending')
                              AND o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                              GROUP BY p.id, p.product_name, p.image
                              ORDER BY total_quantity DESC
                              LIMIT ?";

            $stmt = $conn->prepare($products_query);
            if (!$stmt) {
                throw new Exception('Failed to prepare top products query: ' . $conn->error);
            }
            $stmt->bind_param("ii", $vendor_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();

            return $products;
        } catch (Exception $e) {
            error_log('Top Products Error: ' . $e->getMessage());
            return [];
        }
    }

    function getRecentOrders($vendor_id, $limit = 10) {
        global $conn;

        try {
            $orders_query = "SELECT o.id, o.order_number, o.total_amount, o.status,
                             o.created_at, o.customer_notes, o.payment_method,
                             u.username as customer_name, u.phone as customer_phone
                             FROM orders o
                             JOIN users u ON o.customer_id = u.id
                             WHERE o.vendor_id = ?
                             ORDER BY o.created_at DESC
                             LIMIT ?";

            $stmt = $conn->prepare($orders_query);
            if (!$stmt) {
                throw new Exception('Failed to prepare orders query: ' . $conn->error);
            }
            $stmt->bind_param("ii", $vendor_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            $stmt->close();

            return $orders;
        } catch (Exception $e) {
            error_log('Recent Orders Error: ' . $e->getMessage());
            return [];
        }
    }

    function getVendorReviews($vendor_id) {
        global $conn;

        try {
            $reviews_query = "SELECT r.id, r.rating, r.review_text, r.created_at,
                             u.username as reviewer_name, u.profile_image as reviewer_image,
                             p.product_name
                             FROM reviews r
                             JOIN users u ON r.reviewer_id = u.id
                             LEFT JOIN products p ON r.product_id = p.id
                             WHERE r.reviewee_id = ? AND r.review_type = 'vendor'
                             ORDER BY r.created_at DESC
                             LIMIT 20";

            $stmt = $conn->prepare($reviews_query);
            if (!$stmt) {
                throw new Exception('Failed to prepare reviews query: ' . $conn->error);
            }
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $reviews = [];
            while ($row = $result->fetch_assoc()) {
                $reviews[] = $row;
            }
            $stmt->close();

            return $reviews;
        } catch (Exception $e) {
            error_log('Vendor Reviews Error: ' . $e->getMessage());
            return [];
        }
    }

    function updateOrderStatus($order_id, $vendor_id, $new_status) {
        global $conn;

        // Verify order belongs to vendor
        $verify_query = "SELECT id FROM orders WHERE id = ? AND vendor_id = ?";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("ii", $order_id, $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return false;
        }
        $stmt->close();

        // Update order status
        $update_query = "UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ? AND vendor_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("sii", $new_status, $order_id, $vendor_id);

        $success = $stmt->execute();
        $stmt->close();

        // Create notification for customer if status changed
        if ($success && $new_status !== 'pending') {
            $notification_query = "INSERT INTO notifications (user_id, type, title, message, link)
                                  SELECT customer_id, 'order', ?, ?, CONCAT('orders.php?order=', ?)
                                  FROM orders WHERE id = ?";
            $stmt = $conn->prepare($notification_query);

            $title = "Order Status Updated";
            $message = "Your order status has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
            $stmt->bind_param("ssii", $title, $message, $order_id, $order_id);
            $stmt->execute();
            $stmt->close();
        }

        return $success;
    }

    // Handle order status updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_order_status') {
        $order_id = (int)$_POST['order_id'];
        $new_status = trim($_POST['status']);

        if (updateOrderStatus($order_id, $vendor_id, $new_status)) {
            $_SESSION['message'] = "Order status updated successfully!";
        } else {
            $_SESSION['error'] = "Failed to update order status or unauthorized access";
        }
        header("Location: vendor.php");
        exit();
    }

    // Get dashboard data for the logged-in vendor
    $sales_data = ['total_sales' => 0, 'total_orders' => 0];
    $top_products = [];
    $recent_orders = [];
    $vendor_reviews = [];

    try {
        $sales_data = getVendorSalesData($vendor_id);
        $top_products = getTopSellingProducts($vendor_id);
        $recent_orders = getRecentOrders($vendor_id);
        $vendor_reviews = getVendorReviews($vendor_id);
    } catch (Exception $e) {
        error_log('Dashboard Data Error: ' . $e->getMessage());
        $_SESSION['error'] = 'Error loading dashboard data. Please ensure database is set up correctly.';
    }

    // Handle product actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = (float)($_POST['price'] ?? 0);
                $image_path = '';

                // Validate input
                if (empty($name) || empty($description) || $price <= 0) {
                    throw new Exception('Please fill in all required fields with valid values');
                }
                
                // Additional validation
                if (strlen($name) > 255) {
                    throw new Exception('Product name is too long (max 255 characters)');
                }
                if (strlen($description) > 5000) {
                    throw new Exception('Product description is too long (max 5000 characters)');
                }
                if ($price > 999999.99) {
                    throw new Exception('Product price is too high');
                }

                // Handle image upload
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                    $max_size = 5 * 1024 * 1024; // 5MB max
                    
                    // Check file size
                    if ($_FILES['image']['size'] > $max_size) {
                        throw new Exception('Image file size must be less than 5MB');
                    }
                    
                    // Use finfo for reliable MIME type detection
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $file_type = finfo_file($finfo, $_FILES['image']['tmp_name']);
                    finfo_close($finfo);

                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception('Only JPG, PNG, and GIF images are allowed');
                    }

                    $upload_dir = 'uploads/products/';
                    if (!file_exists($upload_dir)) {
                        if (!mkdir($upload_dir, 0755, true)) {
                            throw new Exception('Failed to create upload directory');
                        }
                    }

                    $image_name = uniqid('', true) . '_' . preg_replace('/[^a-zA-Z0-9_.]/', '', basename($_FILES['image']['name']));
                    $target_path = $upload_dir . $image_name;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                        $image_path = $target_path;
                    } else {
                        throw new Exception('Failed to upload image');
                    }
                }

                $insert_query = "INSERT INTO products (vendor_id, product_name, description, price, image) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param("issds", $vendor_id, $name, $description, $price, $image_path);

                if ($stmt->execute()) {
                    // Check if this is an AJAX request
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Product added successfully!']);
                        exit();
                    } else {
                        $_SESSION['message'] = "Product added successfully!";
                        header("Location: vendor.php");
                        exit();
                    }
                } else {
                    throw new Exception('Failed to add product');
                }
                break;

            case 'update_product':
                $product_id = (int)$_POST['product_id'];
                $name = trim($_POST['name'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $price = (float)($_POST['price'] ?? 0);

                // Validate input
                if (empty($name) || empty($description) || $price <= 0) {
                    throw new Exception('Please fill in all required fields with valid values');
                }
                if (strlen($name) > 255) {
                    throw new Exception('Product name is too long (max 255 characters)');
                }
                if (strlen($description) > 5000) {
                    throw new Exception('Product description is too long (max 5000 characters)');
                }
                if ($price > 999999.99) {
                    throw new Exception('Product price is too high');
                }

                // Verify product belongs to vendor
                $verify_query = "SELECT id FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = $conn->prepare($verify_query);
                $stmt->bind_param("ii", $product_id, $vendor_id);
                $stmt->execute();
                $verify_result = $stmt->get_result();

                if ($verify_result->num_rows > 0) {
                    // Handle image update if new image is uploaded
                    $update_query = "UPDATE products SET product_name = ?, description = ?, price = ?";
                    $params = [$name, $description, $price];
                    $types = "ssd";

                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/products/';
                        if (!file_exists($upload_dir)) {
                            mkdir($upload_dir, 0777, true);
                        }
                        $image_name = uniqid() . '_' . basename($_FILES['image']['name']);
                        $target_path = $upload_dir . $image_name;

                        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                            // Delete old image if exists
                            $old_image_query = "SELECT image FROM products WHERE id = ?";
                            $stmt = $conn->prepare($old_image_query);
                            $stmt->bind_param("i", $product_id);
                            $stmt->execute();
                            $old_image = $stmt->get_result()->fetch_assoc()['image'];
                            if ($old_image && file_exists($old_image)) {
                                unlink($old_image);
                            }

                            $update_query .= ", image = ?";
                            $params[] = $target_path;
                            $types .= "s";
                        }
                    }

                    $update_query .= " WHERE id = ?";
                    $params[] = $product_id;
                    $types .= "i";

                    $stmt = $conn->prepare($update_query);
                    $stmt->bind_param($types, ...$params);
                    $stmt->execute();

                    // Check if this is an AJAX request
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Product updated successfully!']);
                        exit();
                    } else {
                        $_SESSION['message'] = "Product updated successfully!";
                    }
                } else {
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        http_response_code(400);
                        echo json_encode(['error' => 'Product not found or unauthorized access']);
                        exit();
                    } else {
                        $_SESSION['error'] = "Product not found or unauthorized access";
                    }
                }
                
                if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                    header("Location: vendor.php");
                    exit();
                }

            case 'toggle_availability':
                if (!isset($_POST['product_id'], $_POST['status'])) {
                    throw new Exception('Product ID and status are required');
                }

                $product_id = (int)$_POST['product_id'];
                $new_status = $_POST['status'] === '1' ? 1 : 0;

                // Verify product belongs to vendor
                $verify_query = "SELECT id FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = $conn->prepare($verify_query);
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param("ii", $product_id, $vendor_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    throw new Exception('Product not found or access denied');
                }
                $stmt->close();

                // Update availability
                $update_query = "UPDATE products SET is_available = ? WHERE id = ? AND vendor_id = ?";
                $stmt = $conn->prepare($update_query);
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param("iii", $new_status, $product_id, $vendor_id);

                if ($stmt->execute()) {
                    $_SESSION['message'] = 'Product availability updated successfully';
                } else {
                    throw new Exception('Failed to update product availability');
                }
                $stmt->close();
                header("Location: vendor.php");
                exit();

            case 'delete_product':
                if (!isset($_POST['product_id'])) {
                    throw new Exception('Product ID is required');
                }
                $product_id = (int)$_POST['product_id'];

                // Verify product belongs to vendor and get image path
                $verify_query = "SELECT image FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = $conn->prepare($verify_query);
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param("ii", $product_id, $vendor_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 0) {
                    throw new Exception('Product not found or access denied');
                }

                $product = $result->fetch_assoc();
                $stmt->close();

                // Delete the product
                $delete_query = "DELETE FROM products WHERE id = ? AND vendor_id = ?";
                $stmt = $conn->prepare($delete_query);
                if (!$stmt) {
                    throw new Exception('Database error: ' . $conn->error);
                }
                $stmt->bind_param("ii", $product_id, $vendor_id);

                if ($stmt->execute()) {
                    // Delete the associated image file
                    if (!empty($product['image']) && file_exists($product['image'])) {
                        @unlink($product['image']);
                    }
                    
                    // Check if this is an AJAX request
                    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Product deleted successfully!']);
                        exit();
                    } else {
                        $_SESSION['message'] = 'Product deleted successfully';
                    }
                } else {
                    throw new Exception('Failed to delete product');
                }
                break;
        }
    }

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    error_log('Vendor Error: ' . $e->getMessage());
}

// Handle pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    // Get total products count with filters
    $where_conditions = ["p.vendor_id = ?"];
    $bind_params = [$vendor_id];
    $bind_types = "i";

    // Add search filter
    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search_term = "%" . trim($_GET['search']) . "%";
        $where_conditions[] = "(p.product_name LIKE ? OR p.description LIKE ?)";
        $bind_params[] = $search_term;
        $bind_params[] = $search_term;
        $bind_types .= "ss";
    }

    // Add category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category_id = (int)$_GET['category'];
        $where_conditions[] = "p.category_id = ?";
        $bind_params[] = $category_id;
        $bind_types .= "i";
    }

    // Add availability filter
    if (isset($_GET['availability']) && !empty($_GET['availability'])) {
        switch ($_GET['availability']) {
            case 'available':
                $where_conditions[] = "p.is_available = 1";
                break;
            case 'unavailable':
                $where_conditions[] = "p.is_available = 0";
                break;
            case 'low_stock':
                $where_conditions[] = "p.stock_quantity < 5 AND p.stock_quantity > 0";
                break;
            case 'out_of_stock':
                $where_conditions[] = "p.stock_quantity = 0";
                break;
        }
    }

    $where_clause = implode(" AND ", $where_conditions);

    $count_query = "SELECT COUNT(*) as total FROM products p WHERE $where_clause";
    $stmt = $conn->prepare($count_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $total_products = $stmt->get_result()->fetch_assoc()['total'];
    $total_pages = max(1, ceil($total_products / $per_page));
    $stmt->close();

    // Get paginated products with filters
    $products_query = "SELECT p.*,
                      c.name as category_name,
                      CASE
                          WHEN p.stock_quantity = 0 THEN 'Out of Stock'
                          WHEN p.stock_quantity < 5 THEN 'Low Stock'
                          ELSE 'In Stock'
                      END as stock_status
                      FROM products p
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE $where_clause
                      ORDER BY p.created_at DESC
                      LIMIT ? OFFSET ?";

    $bind_params[] = $per_page;
    $bind_params[] = $offset;
    $bind_types .= "ii";

    $stmt = $conn->prepare($products_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param($bind_types, ...$bind_params);
    $stmt->execute();
    $products_result = $stmt->get_result();
    $products = [];
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    $error = 'Error loading products: ' . $e->getMessage();
    error_log('Vendor Products Error: ' . $e->getMessage());
}

// Get nearby vendors
$nearby_vendors = [];
try {
    $nearby_vendors_query = "SELECT u.id, u.username, u.business_name, u.address, u.phone, u.email,
        (SELECT COUNT(*) FROM products p WHERE p.vendor_id = u.id) as product_count
        FROM users u
        WHERE u.id != ? AND u.role = 'vendor'
        ORDER BY u.created_at DESC
        LIMIT 6";

    $stmt = $conn->prepare($nearby_vendors_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare nearby vendors query: ' . $conn->error);
    }

    $stmt->bind_param("i", $vendor_id);

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $nearby_vendors_result = $stmt->get_result();
    while ($row = $nearby_vendors_result->fetch_assoc()) {
        $nearby_vendors[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    error_log('Nearby Vendors Error: ' . $e->getMessage());
}

// Get low stock products for notifications
$low_stock_products = [];
try {
    $low_stock_query = "SELECT id, product_name, stock_quantity FROM products WHERE vendor_id = ? AND stock_quantity < 5 AND stock_quantity > 0 ORDER BY stock_quantity ASC";
    $stmt = $conn->prepare($low_stock_query);
    if (!$stmt) {
        throw new Exception('Failed to prepare low stock query: ' . $conn->error);
    }
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $low_stock_result = $stmt->get_result();
    while ($row = $low_stock_result->fetch_assoc()) {
        $low_stock_products[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Low Stock Query Error: ' . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard — Sarap Local</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php
      // Load Google Maps key from central config (if available)
      $GOOGLE_MAPS_API_KEY = '';
      if (file_exists(__DIR__ . '/app_config.php')) {
          include __DIR__ . '/app_config.php';
      }
      if (!empty($GOOGLE_MAPS_API_KEY)) {
          echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . htmlspecialchars($GOOGLE_MAPS_API_KEY) . '&callback=initVendorMap" async defer></script>';
      } else {
          // Leaflet fallback (no API key needed)
          echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';
          echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
          echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof initVendorLeafletMap==="function"){initVendorLeafletMap();}});</script>';
      }
    ?>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        /* Enhanced responsive button styling for vendor page */
        .btn-responsive {
            @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors min-h-[44px];
        }

        /* Mobile-first responsive button adjustments */
        @media (max-width: 640px) {
            .btn-responsive {
                @apply px-3 py-2 text-xs min-h-[48px] min-w-[48px];
            }

            /* Ensure buttons in cards have proper touch targets */
            .product-card button {
                @apply min-h-[44px] px-3 py-2 text-sm;
            }

            /* Modal buttons on mobile */
            #productModal button, #deleteModal button {
                @apply min-h-[48px] px-4 py-2 text-sm;
            }

            /* Header buttons on mobile */
            .flex.items-center.space-x-4 button {
                @apply min-h-[40px] px-2 py-1 text-xs;
            }
        }
        .product-card {
            transition: all 0.3s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .stats-card {
            transition: all 0.2s ease;
        }
        .stats-card:hover {
            transform: translateY(-1px);
        }
        .order-card {
            transition: all 0.2s ease;
        }
        .order-card:hover {
            transform: translateY(-1px);
        }
        #vendorMap {
            min-height: 18rem;
        }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <!-- Header -->
    <header class="brand-header shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center">
                    <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                        <img src="images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs uppercase tracking-[0.2em] text-orange-100">Vendor</span>
                        <span class="text-xl font-semibold brand-script">Sarap Local</span>
                    </div>
                </a>

                <!-- User Actions -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <a href="search.php" class="relative text-white hover:text-orange-100 transition-colors" title="Search">
                        <i class="fas fa-search text-xl"></i>
                    </a>

                    <!-- Notifications -->
                    <div class="relative">
                        <button onclick="toggleNotificationDropdown()" class="relative text-white hover:text-orange-100 transition-colors">
                            <i class="fas fa-bell text-xl"></i>
                            <span id="notificationBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                        </button>
                        <div id="notificationDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 max-h-96 overflow-y-auto">
                            <div class="p-4 border-b border-gray-200 flex justify-between items-center">
                                <h3 class="font-semibold text-gray-800">Notifications</h3>
                                <button onclick="clearAllNotifications()" class="text-xs text-orange-600 hover:text-orange-700">Clear All</button>
                            </div>
                            <div id="notificationList" class="divide-y divide-gray-100">
                                <div class="p-4 text-center text-gray-500 text-sm">Loading...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Profile (Direct Link) -->
                    <a href="profile.php" class="relative text-white hover:text-orange-100 transition-colors">
                        <i class="fas fa-user-circle text-xl"></i>
                    </a>

                    <!-- Messages -->
                    <a href="chat.php" class="relative text-white hover:text-orange-100 transition-colors">
                        <i class="fas fa-comments text-xl"></i>
                        <span id="messageBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>
    <!-- Order Notifications Panel -->
    <div id="orderNotifications" class="fixed top-4 right-4 z-50 space-y-2 max-w-sm">
        <!-- Order notifications will appear here -->
    </div>
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <span class="block sm:inline"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></span>
            </div>
        <?php endif; ?>

        <!-- Dashboard Analytics Section -->
        <section id="dashboard" class="mb-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Sales Dashboard</h2>
                <p class="text-gray-600 text-sm mt-1">Your business performance overview for the last 30 days</p>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Sales Card -->
                <div class="stats-card brand-card rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Sales</p>
                            <p class="text-2xl font-bold text-gray-800">₱<?php echo number_format($sales_data['total_sales'], 2); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-peso-sign text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Orders Card -->
                <div class="stats-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Total Orders</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo $sales_data['total_orders']; ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shopping-cart text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Average Order Value Card -->
                <div class="stats-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Avg Order Value</p>
                            <p class="text-2xl font-bold text-gray-800">
                                ₱<?php echo $sales_data['total_orders'] > 0 ? number_format($sales_data['total_sales'] / $sales_data['total_orders'], 2) : '0.00'; ?>
                            </p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-chart-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            <!-- Vendor Badges Section -->
            <div class="brand-card rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Vendor Achievements</h3>
                <div id="vendorBadges" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Vendor badges will be loaded here -->
                </div>
            </div>

            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Selling Products Chart -->
                <div class="brand-card rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Selling Products</h3>
                    <canvas id="topProductsChart" width="400" height="200"></canvas>
                </div>

                <!-- Recent Orders Chart -->
                <div class="brand-card rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Recent Orders</h3>
                    <canvas id="ordersChart" width="400" height="200"></canvas>
                </div>
            </div>
        </section>

        <!-- Delivery Map Section -->
        <section id="delivery-map" class="mb-8">
            <div class="brand-card rounded-xl shadow-sm p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between mb-4 gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Delivery Map</h2>
                        <p class="text-gray-600 text-sm mt-1">Visualize your business location for planning deliveries and pickups.</p>
                    </div>
                </div>

                <div id="vendorMap"
                     class="w-full rounded-lg border border-gray-200 overflow-hidden"
                     data-lat="<?php echo isset($vendor['latitude']) ? htmlspecialchars($vendor['latitude']) : ''; ?>"
                     data-lng="<?php echo isset($vendor['longitude']) ? htmlspecialchars($vendor['longitude']) : ''; ?>">
                </div>

                <?php if (empty($vendor['latitude']) || empty($vendor['longitude'])): ?>
                    <p class="mt-4 text-sm text-gray-500">
                        Set your business location in your vendor profile to see it on the map.
                    </p>
                <?php else: ?>
                    <p class="mt-4 text-sm text-gray-500">
                        This map centers on your registered business location.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <!-- Order Management Section -->
        <section id="orders" class="mb-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Order Management</h2>
                <p class="text-gray-600 text-sm mt-1">Manage your orders and update their status</p>
            </div>

            <?php if (empty($recent_orders)): ?>
                <div class="brand-card rounded-lg shadow-sm p-12 text-center">
                    <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No orders yet</h3>
                    <p class="text-gray-500">Orders will appear here when customers place them</p>
                </div>
            <?php else: ?>
                <div class="brand-card rounded-lg shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-orange-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr class="order-card">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            #<?php echo htmlspecialchars($order['order_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo htmlspecialchars($order['customer_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            ₱<?php echo number_format($order['total_amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                <?php
                                                switch ($order['status']) {
                                                    case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'confirmed': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'preparing': echo 'bg-orange-100 text-orange-800'; break;
                                                    case 'ready': echo 'bg-purple-100 text-purple-800'; break;
                                                    case 'out_for_delivery': echo 'bg-indigo-100 text-indigo-800'; break;
                                                    case 'delivered': echo 'bg-green-100 text-green-800'; break;
                                                    case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                                ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <?php if ($order['status'] !== 'delivered' && $order['status'] !== 'cancelled'): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="update_order_status">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-300 rounded px-2 py-1">
                                                            <?php
                                                            $statuses = ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
                                                            foreach ($statuses as $status_option) {
                                                                if ($status_option !== $order['status']) {
                                                                    echo "<option value=\"$status_option\">" . ucfirst(str_replace('_', ' ', $status_option)) . "</option>";
                                                                }
                                                            }
                                                            ?>
                                                        </select>
                                                    </form>
                                                <?php endif; ?>
                                                <button onclick="viewOrderDetails(<?php echo $order['id']; ?>)" class="text-orange-700 hover:text-orange-800 text-xs">View</button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Products Section -->
        <section id="products" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">My Products</h2>
                <button onclick="openAddProductModal()"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <i class="fas fa-plus mr-2"></i> Add Product
                </button>
            </div>

            <?php if (empty($products)): ?>
                <div class="brand-card rounded-lg shadow-sm p-12 text-center">
                    <i class="fas fa-utensils text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No products added yet</h3>
                    <p class="text-gray-500">Get started by adding your first product</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card brand-card rounded-lg shadow-sm overflow-hidden">
                            <div class="relative">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>

                                <div class="absolute top-2 right-2">
                                    <button onclick="openEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" class="bg-orange-600 text-white p-2 rounded-full hover:bg-orange-700 mr-1">
                                        <i class="fas fa-edit text-xs"></i>
                                    </button>
                                    <button onclick="confirmDeleteProduct(<?php echo $product['id']; ?>)" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>

                                <!-- Stock Status -->
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                        Out of Stock
                                    </div>
                                <?php elseif ($product['stock_quantity'] < 5): ?>
                                    <div class="absolute top-2 left-2 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                        Low Stock
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="p-4">
                                <h3 class="font-bold text-lg text-gray-800 mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="text-gray-600 text-sm mb-2"><?php echo htmlspecialchars($product['description']); ?></p>

                                <?php if (!empty($product['category_name'])): ?>
                                    <p class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded-full inline-block mb-2">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </p>
                                <?php endif; ?>

                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xl font-bold text-green-600">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <span class="text-sm text-gray-500"><?php echo $product['stock_quantity']; ?> in stock</span>
                                </div>

                                <div class="flex space-x-2">
                                    <button onclick="toggleProductAvailability(<?php echo $product['id']; ?>, <?php echo $product['is_available'] ? 'false' : 'true'; ?>)"
                                            class="flex-1 <?php echo $product['is_available'] ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'; ?> text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                        <?php echo $product['is_available'] ? 'Disable' : 'Enable'; ?>
                                    </button>
                                    <button onclick="openEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)"
                                            class="bg-orange-500 hover:bg-orange-600 text-white py-2 px-3 rounded-lg text-sm font-medium transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="mt-8 flex justify-center">
                        <div class="flex space-x-1">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($_GET['search']) ? '&search=' . urlencode($_GET['search']) : ''; ?>"
                                   class="px-3 py-2 border border-gray-300 rounded-lg <?php echo $i === $page ? 'bg-orange-500 text-white' : 'bg-orange-50 text-gray-800 hover:bg-orange-100'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endif; ?>
        </section>

        <!-- Product Management Section (CRUD Table) -->
        <section id="product-management" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Product Management</h2>
                <div class="flex space-x-2">
                    <input type="text" id="productSearchInput" placeholder="Search products..." 
                           class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    <button onclick="filterProducts()" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </div>

            <div class="brand-card rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Product Name</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Price</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Stock</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Category</th>
                                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
                                <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productTableBody" class="divide-y divide-gray-200">
                            <?php if (empty($products)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <i class="fas fa-inbox text-2xl mb-2"></i>
                                        <p>No products to manage</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $product): ?>
                                    <tr class="hover:bg-gray-50 transition-colors" data-product-id="<?php echo $product['id']; ?>" data-product-name="<?php echo htmlspecialchars($product['product_name']); ?>">
                                        <td class="px-6 py-4 text-sm text-gray-800 font-medium"><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td class="px-6 py-4 text-sm text-gray-600">₱<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="<?php echo $product['stock_quantity'] == 0 ? 'text-red-600 font-semibold' : ($product['stock_quantity'] < 5 ? 'text-orange-600 font-semibold' : 'text-green-600'); ?>">
                                                <?php echo $product['stock_quantity']; ?> units
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td class="px-6 py-4 text-sm">
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $product['is_available'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo $product['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-center">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="openEditProductModal(<?php echo htmlspecialchars(json_encode($product)); ?>)" 
                                                        class="text-blue-600 hover:text-blue-800 font-medium" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="toggleProductAvailability(<?php echo $product['id']; ?>, <?php echo $product['is_available'] ? 'false' : 'true'; ?>)" 
                                                        class="<?php echo $product['is_available'] ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'; ?> font-medium" title="<?php echo $product['is_available'] ? 'Disable' : 'Enable'; ?>">
                                                    <i class="fas fa-<?php echo $product['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                </button>
                                                <button onclick="confirmDeleteProduct(<?php echo $product['id']; ?>)" 
                                                        class="text-red-600 hover:text-red-800 font-medium" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Product Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
                <div class="brand-card rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-orange-600"><?php echo count($products); ?></div>
                    <div class="text-sm text-gray-600 mt-1">Total Products</div>
                </div>
                <div class="brand-card rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-green-600"><?php echo count(array_filter($products, fn($p) => $p['is_available'])); ?></div>
                    <div class="text-sm text-gray-600 mt-1">Available</div>
                </div>
                <div class="brand-card rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-orange-600"><?php echo count(array_filter($products, fn($p) => $p['stock_quantity'] < 5 && $p['stock_quantity'] > 0)); ?></div>
                    <div class="text-sm text-gray-600 mt-1">Low Stock</div>
                </div>
                <div class="brand-card rounded-lg p-4 text-center">
                    <div class="text-3xl font-bold text-red-600"><?php echo count(array_filter($products, fn($p) => $p['stock_quantity'] == 0)); ?></div>
                    <div class="text-sm text-gray-600 mt-1">Out of Stock</div>
                </div>
            </div>
        </section>

        <!-- Food Reels Section -->
        <section id="reels" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Food Reels</h2>
                <button onclick="openReelUploadModal()"
                        class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg flex items-center transition-colors">
                    <i class="fas fa-video mr-2"></i> Upload Reel
                </button>
            </div>

            <div id="reelsList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="text-center py-12 col-span-full">
                    <i class="fas fa-film text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500">No reels uploaded yet</p>
                </div>
            </div>
        </section>

        <!-- Inventory Management Section -->
        <section id="inventory" class="mb-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Inventory Management</h2>
                <p class="text-gray-600 text-sm mt-1">Manage your product stock and availability</p>
            </div>

            <!-- Low Stock Alerts -->
            <div id="lowStockAlerts" class="mb-6">
                <!-- Low stock items will appear here -->
            </div>

            <!-- Quick Stock Update Panel -->
            <div class="brand-card rounded-lg shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Stock Update</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($products as $product): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-medium text-gray-800"><?php echo htmlspecialchars($product['product_name']); ?></h4>
                                <span class="px-2 py-1 text-xs rounded-full <?php
                                    echo $product['stock_quantity'] == 0 ? 'bg-red-100 text-red-800' :
                                         ($product['stock_quantity'] < 5 ? 'bg-orange-100 text-orange-800' : 'bg-green-100 text-green-800');
                                ?>">
                                    <?php echo $product['stock_quantity']; ?> in stock
                                </span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="number" id="stock_<?php echo $product['id']; ?>"
                                       value="<?php echo $product['stock_quantity']; ?>"
                                       min="0" max="1000"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                                <button onclick="updateProductStock(<?php echo $product['id']; ?>)"
                                        class="px-3 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition-colors">
                                    Update
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <!-- Reel Upload Modal -->
    <div id="reelUploadModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="brand-card rounded-lg w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-800">Upload Food Reel</h3>
                    <button onclick="closeReelUploadModal()" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="reelUploadForm" enctype="multipart/form-data">
                    <div class="mb-4">
                        <label for="reelVideo" class="block text-sm font-medium text-gray-700 mb-2">Video File (MP4/MOV)</label>
                        <input type="file" id="reelVideo" name="video" accept="video/mp4,video/quicktime" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <p class="text-xs text-gray-500 mt-1">Max 100MB</p>
                    </div>

                    <div class="mb-4">
                        <label for="reelTitle" class="block text-sm font-medium text-gray-700 mb-2">Title</label>
                        <input type="text" id="reelTitle" name="title" placeholder="e.g., Delicious Adobo"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <div class="mb-4">
                        <label for="reelDescription" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                        <textarea id="reelDescription" name="description" rows="3" placeholder="Describe your reel..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="reelProduct" class="block text-sm font-medium text-gray-700 mb-2">Link to Product (Optional)</label>
                        <select id="reelProduct" name="product_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="">-- Select a product --</option>
                        </select>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeReelUploadModal()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                            Upload Reel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="brand-card rounded-lg w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-xl font-bold">Add New Product</h3>
                    <button onclick="closeProductModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form id="productForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add_product">
                    <input type="hidden" name="product_id" id="productId">

                    <div class="mb-4">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name</label>
                        <input type="text" id="name" name="name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea id="description" name="description" rows="3" required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (₱)</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                        <div>
                            <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
                            <input type="file" id="image" name="image" accept="image/*" class="hidden">
                            <div class="relative">
                                <input type="text" id="imageFileName" readonly
                                       class="w-full px-3 py-2 border border-gray-300 rounded-l-md focus:outline-none"
                                       placeholder="Select an image">
                                <button type="button" onclick="document.getElementById('image').click()"
                                        class="absolute right-0 top-0 h-full px-3 bg-gray-200 hover:bg-gray-300 rounded-r-md">
                                    Browse
                                </button>
                            </div>
                            <div id="imagePreview" class="mt-2 hidden">
                                <img id="previewImage" src="#" alt="Preview" class="h-20 object-cover rounded">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" onclick="closeProductModal()"
                                class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                            Save Product
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="brand-card rounded-lg p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation text-red-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Delete Product</h3>
                <p class="text-gray-500 mb-6">Are you sure you want to delete this product? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                    <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                        Cancel
                    </button>
                    <form id="deleteForm" method="POST">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" id="deleteProductId">
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                            Delete
                        </button>
                    </form>
                </div>
            </div>
    </div>

    <!-- Promotion Modal -->
    <div id="promotionModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="brand-card rounded-lg w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-gray-800">Create New Promotion</h3>
                    <button onclick="closePromotionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
            </div>

            <form id="promotionForm" class="p-6">
                <div class="mb-4">
                    <label for="promotion_name" class="block text-sm font-medium text-gray-700 mb-1">Promotion Name</label>
                    <input type="text" id="promotion_name" name="promotion_name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                           placeholder="e.g., Weekend Special 15% Off">
                </div>

                <div class="mb-4">
                    <label for="promotion_type" class="block text-sm font-medium text-gray-700 mb-1">Promotion Type</label>
                    <select id="promotion_type" name="promotion_type" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Select promotion type</option>
                        <option value="percentage_discount">Percentage Discount</option>
                        <option value="fixed_discount">Fixed Amount Discount</option>
                        <option value="buy_x_get_y">Buy X Get Y Free</option>
                        <option value="free_shipping">Free Shipping</option>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="3" required
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                              placeholder="Describe your promotion..."></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="discount_value" class="block text-sm font-medium text-gray-700 mb-1">Discount Value</label>
                        <input type="number" id="discount_value" name="discount_value" step="0.01" min="0" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="15 or 100.00">
                    </div>
                    <div>
                        <label for="min_order_amount" class="block text-sm font-medium text-gray-700 mb-1">Min Order Amount (₱)</label>
                        <input type="number" id="min_order_amount" name="min_order_amount" step="0.01" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                               placeholder="300.00">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" id="start_date" name="start_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" id="end_date" name="end_date" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500">
                    </div>
                </div>

                <div class="mb-6">
                    <label for="usage_limit" class="block text-sm font-medium text-gray-700 mb-1">Usage Limit (Optional)</label>
                    <input type="number" id="usage_limit" name="usage_limit" min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-orange-500"
                           placeholder="Leave empty for unlimited">
                    <p class="text-xs text-gray-500 mt-1">Maximum number of times this promotion can be used</p>
                </div>

                <div class="flex gap-3">
                    <button type="submit" onclick="createPromotion()"
                            class="flex-1 bg-orange-500 text-white py-2 px-4 rounded-md hover:bg-orange-600 transition-colors">
                        <i class="fas fa-plus mr-2"></i>
                        Create Promotion
                    </button>
                    <button type="button" onclick="closePromotionModal()"
                            class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/realtime-updates.js?v=<?php echo time(); ?>"></script>
    <script src="js/session-manager.js?v=<?php echo time(); ?>"></script>
    <script>
        // Vendor Dashboard JavaScript Functions
        // All vendor-specific JavaScript functions should go here

        // Map initialization for vendor location - Biliran Province
        let vendorMapInstance = null;
        let vendorMapProvider = 'none';
        const BILIRAN_CENTER = { lat: 11.55, lng: 124.50 };
        const BILIRAN_MIN_LAT = 11.20, BILIRAN_MAX_LAT = 11.85;
        const BILIRAN_MIN_LNG = 124.20, BILIRAN_MAX_LNG = 124.80;

        function getVendorCoords() {
            const el = document.getElementById('vendorMap');
            if (!el) return null;
            const lat = parseFloat(el.dataset.lat || BILIRAN_CENTER.lat);
            const lng = parseFloat(el.dataset.lng || BILIRAN_CENTER.lng);
            if (!isFinite(lat) || !isFinite(lng)) return { lat: BILIRAN_CENTER.lat, lng: BILIRAN_CENTER.lng, el };
            return { lat, lng, el };
        }

        // Called by Google Maps API when key is configured
        function initVendorMap() {
            try {
                const data = getVendorCoords();
                if (!data || typeof google === 'undefined' || !google.maps) return;
                vendorMapProvider = 'google';
                const BILIRAN_BOUNDS = new google.maps.LatLngBounds(
                    new google.maps.LatLng(BILIRAN_MIN_LAT, BILIRAN_MIN_LNG),
                    new google.maps.LatLng(BILIRAN_MAX_LAT, BILIRAN_MAX_LNG)
                );
                vendorMapInstance = new google.maps.Map(data.el, {
                    center: { lat: data.lat, lng: data.lng },
                    zoom: 12,
                    minZoom: 9,
                    maxZoom: 18,
                    restriction: { latLngBounds: BILIRAN_BOUNDS, strictBounds: true },
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                new google.maps.Marker({
                    position: { lat: data.lat, lng: data.lng },
                    map: vendorMapInstance,
                    title: 'Your business location',
                });
            } catch (e) {
                console.error('initVendorMap error:', e);
            }
        }

        // Safe fallback: ensure initVendorLeafletMap exists so vendor page doesn't break
        if (typeof initVendorLeafletMap !== 'function') {
            function initVendorLeafletMap() {
                try {
                    const data = getVendorCoords();
                    if (!data) return;
                    vendorMapProvider = 'leaflet';
                    const BILIRAN_BOUNDS = L.latLngBounds(
                        [BILIRAN_MIN_LAT, BILIRAN_MIN_LNG],
                        [BILIRAN_MAX_LAT, BILIRAN_MAX_LNG]
                    );
                    vendorMapInstance = L.map(data.el, {
                        center: [data.lat, data.lng],
                        zoom: 12,
                        maxBounds: BILIRAN_BOUNDS,
                        minZoom: 9,
                        maxZoom: 18
                    });
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; OpenStreetMap contributors'
                    }).addTo(vendorMapInstance);
                    L.marker([data.lat, data.lng]).addTo(vendorMapInstance)
                        .bindPopup('Your business location').openPopup();
                } catch (e) {
                    console.error('initVendorLeafletMap error:', e);
                }
            }
        } // Safe fallback: ensure updateCartCount exists so vendor page doesn't break
        if (typeof updateCartCount !== 'function') {
            function updateCartCount() {}
        }

        function toggleProfileDropdown() {
            var dd = document.getElementById('profileDropdown');
            if (!dd) return;
            dd.classList.toggle('hidden');
        }

        function onProfileButtonClick(e) {
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
            toggleProfileDropdown();
        }

        function toggleProductAvailability(productId, currentStatus) {
            if (confirm('Are you sure you want to change the availability of this product?')) {
                fetch('vendor.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=toggle_availability&product_id=${productId}&status=${currentStatus ? '0' : '1'}`
                }).then(() => location.reload());
            }
        }

        function viewOrderDetails(orderId) {
            alert('Order details view for order #' + orderId + ' - This feature can be expanded to show full order information.');
        }

        function openAddProductModal() {
            document.getElementById('modalTitle').textContent = 'Add New Product';
            document.getElementById('formAction').value = 'add_product';
            document.getElementById('productId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('description').value = '';
            document.getElementById('price').value = '';
            document.getElementById('imageFileName').value = '';
            document.getElementById('imagePreview').classList.add('hidden');
            document.getElementById('productModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function openEditProductModal(product) {
            document.getElementById('modalTitle').textContent = 'Edit Product';
            document.getElementById('formAction').value = 'update_product';
            document.getElementById('productId').value = product.id;
            document.getElementById('name').value = product.product_name;
            document.getElementById('description').value = product.description;
            document.getElementById('price').value = product.price;
            document.getElementById('productModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeProductModal() {
            document.getElementById('productModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Reel Functions
        function openReelUploadModal() {
            document.getElementById('reelUploadModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            
            // Load products for the dropdown
            fetch('api/vendor_management.php?action=get_products')
                .then(response => response.json())
                .then(data => {
                    const select = document.getElementById('reelProduct');
                    select.innerHTML = '<option value="">-- Select a product --</option>';
                    if (Array.isArray(data)) {
                        data.forEach(product => {
                            const option = document.createElement('option');
                            option.value = product.id;
                            option.textContent = product.product_name;
                            select.appendChild(option);
                        });
                    }
                })
                .catch(error => console.error('Error loading products:', error));
        }

        function closeReelUploadModal() {
            document.getElementById('reelUploadModal').classList.add('hidden');
            document.getElementById('reelUploadForm').reset();
            document.body.style.overflow = 'auto';
        }

        function loadReels() {
            fetch('api/vendor_reels.php?action=list')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('reelsList');
                    if (!Array.isArray(data) || data.length === 0) {
                        container.innerHTML = '<div class="text-center py-12 col-span-full"><i class="fas fa-film text-4xl text-gray-300 mb-4"></i><p class="text-gray-500">No reels uploaded yet</p></div>';
                        return;
                    }
                    
                    let html = '';
                    data.forEach(reel => {
                        html += `
                            <div class="brand-card rounded-lg shadow-sm overflow-hidden reel-item" data-reel-id="${reel.id}">
                                <div class="relative bg-gray-900 h-48">
                                    <video src="${reel.video_path}" class="w-full h-full object-cover" controls></video>
                                    <div class="absolute top-2 right-2 space-x-2">
                                        <button onclick="deleteReel(${reel.id})" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h3 class="font-semibold text-gray-800 truncate">${reel.title || 'Untitled'}</h3>
                                    <p class="text-sm text-gray-600 mt-1 line-clamp-2">${reel.description || ''}</p>
                                    ${reel.product_name ? `<p class="text-xs text-orange-600 mt-2">📦 ${reel.product_name}</p>` : ''}
                                    <p class="text-xs text-gray-400 mt-2 reel-views">👁️ ${reel.view_count || 0} views</p>
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                    
                    // Re-attach event listeners
                    attachReelEventListeners();
                })
                .catch(error => {
                    console.error('Error loading reels:', error);
                    document.getElementById('reelsList').innerHTML = '<div class="text-center py-12 col-span-full text-red-600">Error loading reels</div>';
                });
        }
        
        function attachReelEventListeners() {
            document.querySelectorAll('.reel-item').forEach(item => {
                const reelId = item.dataset.reelId;
                const deleteBtn = item.querySelector('button[onclick*="deleteReel"]');
                if (deleteBtn) {
                    deleteBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        deleteReel(reelId);
                    });
                }
            });
        }

        function deleteReel(reelId) {
            if (!confirm('Delete this reel?')) return;
            
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('reel_id', reelId);
            
            fetch('api/vendor_reels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Reel deleted', 'success');
                    loadReels();
                } else {
                    showNotification(data.error || 'Error deleting reel', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting reel', 'error');
            });
        }

        function confirmDeleteProduct(productId) {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteModal').classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            }
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function loadMessageBadge() {
            fetch('chat.php?action=get_unread_count')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const badge = document.getElementById('messageBadge');
                        if (data.unread_count > 0) {
                            badge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(error => console.error('Error loading message count:', error));
        }

        function showOrderNotification(orderData) {
            const notificationsContainer = document.getElementById('orderNotifications');

            const notification = document.createElement('div');
            notification.className = 'brand-card rounded-lg shadow-lg p-4 transform translate-x-full opacity-0 transition-all duration-300';
            notification.innerHTML = `
                <div class="flex items-start space-x-3">
                    <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-orange-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-shopping-bag text-orange-600"></i>
                        </div>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-sm font-semibold text-gray-800">New Order!</h4>
                        <p class="text-sm text-gray-600">Order #${orderData.order_number}</p>
                        <p class="text-sm text-gray-500">₱${parseFloat(orderData.total_amount).toFixed(2)}</p>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>
            `;

            notificationsContainer.appendChild(notification);

            // Animate in
            setTimeout(() => {
                notification.classList.remove('translate-x-full', 'opacity-0');
            }, 100);

            // Auto remove after 10 seconds
            setTimeout(() => {
                notification.classList.add('translate-x-full', 'opacity-0');
                setTimeout(() => notification.remove(), 300);
            }, 10000);

            // Play notification sound
            playNotificationSound();
        }

        function playNotificationSound() {
            try {
                const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                const oscillator = audioContext.createOscillator();
                const gainNode = audioContext.createGain();

                oscillator.connect(gainNode);
                gainNode.connect(audioContext.destination);

                oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                oscillator.frequency.exponentialRampToValueAtTime(400, audioContext.currentTime + 0.1);

                gainNode.gain.setValueAtTime(0.1, audioContext.currentTime);
                gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);

                oscillator.start(audioContext.currentTime);
                oscillator.stop(audioContext.currentTime + 0.1);
            } catch (error) {
                console.log('Audio notification failed:', error);
            }
        }

        function checkForNewOrders() {
            fetch('api/check_new_orders.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.new_orders && data.new_orders.length > 0) {
                        data.new_orders.forEach(order => {
                            showOrderNotification(order);
                        });
                    }
                })
                .catch(error => console.error('Error checking for new orders:', error));
        }

        // Check for new orders every 10 seconds
        setInterval(checkForNewOrders, 10000);

        // Load notifications on page load
        loadVendorNotifications();
        setInterval(loadVendorNotifications, 30000);

        // Close dropdowns when clicking outside
        document.addEventListener('click', (e) => {
            const profileDropdown = document.getElementById('profileDropdown');
            const profileButton = document.querySelector('[onclick="onProfileButtonClick(event)"]');
            if (!profileDropdown) return;
            const clickedInsideDropdown = profileDropdown.contains(e.target);
            const clickedProfileButton = profileButton ? profileButton.contains(e.target) : false;
            if (!clickedInsideDropdown && !clickedProfileButton) {
                profileDropdown.classList.add('hidden');
            }
        });

        // Handle anchor clicks for smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const targetId = link.getAttribute('href');
                if (targetId === '#') return;

                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    targetElement.scrollIntoView({ behavior: 'smooth' });
                }
            });
        });

        function updateProductStock(productId) {
            const stockInput = document.getElementById('stock_' + productId);
            const newQuantity = parseInt(stockInput.value);

            if (isNaN(newQuantity) || newQuantity < 0) {
                showNotification('Please enter a valid quantity', 'error');
                return;
            }

            fetch('api/vendor_management.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=update_stock&product_id=${productId}&quantity=${newQuantity}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');

                    // Update the stock status indicator
                    const productCard = stockInput.closest('.border');
                    const statusBadge = productCard.querySelector('.rounded-full');

                    if (newQuantity === 0) {
                        statusBadge.className = 'px-2 py-1 text-xs rounded-full bg-red-100 text-red-800';
                        statusBadge.textContent = '0 in stock';
                    } else if (newQuantity < 5) {
                        statusBadge.className = 'px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800';
                        statusBadge.textContent = `${newQuantity} in stock`;
                    } else {
                        statusBadge.className = 'px-2 py-1 text-xs rounded-full bg-green-100 text-green-800';
                        statusBadge.textContent = `${newQuantity} in stock`;
                    }

                    // Check for low stock alerts
                    checkLowStockAlerts();
                } else {
                    showNotification(data.message || 'Error updating stock', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating stock', 'error');
            });
        }

        function checkLowStockAlerts() {
            fetch('api/vendor_management.php?action=get_low_stock')
                .then(response => response.json())
                .then(data => {
                    const alertsContainer = document.getElementById('lowStockAlerts');
                    if (data.success && data.low_stock.length > 0) {
                        alertsContainer.innerHTML = `
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex items-center mb-3">
                                    <i class="fas fa-exclamation-triangle text-orange-600 mr-2"></i>
                                    <h3 class="font-semibold text-orange-800">Low Stock Alert</h3>
                                </div>
                                <div class="space-y-2">
                                    ${data.low_stock.map(item => `
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-orange-700">${item.product_name}</span>
                                            <span class="font-medium text-orange-800">${item.stock_quantity} remaining</span>
                                        </div>
                                    `).join('')}
                                </div>
                            </div>
                        `;
                    } else {
                        alertsContainer.innerHTML = '';
                    }
                })
                .catch(error => console.error('Error checking low stock:', error));
        }

        // CRUD Functions for Product Management
        function filterProducts() {
            const searchTerm = document.getElementById('productSearchInput').value.toLowerCase();
            const tableRows = document.querySelectorAll('#productTableBody tr');
            
            tableRows.forEach(row => {
                const productName = row.getAttribute('data-product-name').toLowerCase();
                if (productName.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
            
            // Show message if no results
            const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none');
            if (visibleRows.length === 0) {
                const tbody = document.getElementById('productTableBody');
                tbody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No products found matching your search</td></tr>';
            }
        }

        function loadProducts() {
            // Reload the page to refresh product list
            location.reload();
        }

        function deleteProduct(productId) {
            const formData = new FormData();
            formData.append('action', 'delete_product');
            formData.append('product_id', productId);
            
            fetch('vendor.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Product deleted successfully!', 'success');
                    // Remove the row from table
                    const row = document.querySelector(`tr[data-product-id="${productId}"]`);
                    if (row) {
                        row.remove();
                    }
                    loadProducts();
                } else {
                    showNotification(data.error || 'Error deleting product', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting product', 'error');
            });
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function loadVendorBadges() {
            fetch('api/vendor_management.php?action=get_vendor_badges')
                .then(response => response.json())
                .then(data => {
                    const badgesContainer = document.getElementById('vendorBadges');
                    if (data.success && data.badges.length > 0) {
                        badgesContainer.innerHTML = data.badges.map(badge => `
                            <div class="bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-lg p-4 text-center">
                                <div class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mx-auto mb-3">
                                    <i class="fas ${getBadgeIcon(badge.badge_type)} text-white text-lg"></i>
                                </div>
                                <h4 class="font-semibold text-gray-800 text-sm mb-1">${badge.badge_name}</h4>
                                <p class="text-xs text-gray-600">${badge.badge_description}</p>
                                <p class="text-xs text-orange-600 mt-2">Earned ${formatDate(badge.earned_at)}</p>
                            </div>
                        `).join('');
                    } else {
                        badgesContainer.innerHTML = `
                            <div class="col-span-full text-center py-8">
                                <i class="fas fa-trophy text-3xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No badges earned yet</p>
                                <p class="text-sm text-gray-400 mt-1">Complete orders and receive great reviews to earn badges!</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading vendor badges:', error);
                    document.getElementById('vendorBadges').innerHTML = `
                        <div class="col-span-full text-center py-8">
                            <i class="fas fa-exclamation-triangle text-3xl text-red-300 mb-3"></i>
                            <p class="text-red-500">Error loading badges</p>
                        </div>
                    `;
                });
        }

        function getBadgeIcon(badgeType) {
            const icons = {
                'top_rated': 'fa-star',
                'fastest_delivery': 'fa-shipping-fast',
                'most_orders': 'fa-shopping-bag',
                'excellent_service': 'fa-thumbs-up'
            };
            return icons[badgeType] || 'fa-trophy';
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function openPromotionModal() {
            document.getElementById('promotionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePromotionModal() {
            document.getElementById('promotionModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            document.getElementById('promotionForm').reset();
        }

        function loadPromotions() {
            fetch('api/vendor_management.php?action=get_promotions')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const activeContainer = document.getElementById('activePromotions');
                        const expiredContainer = document.getElementById('expiredPromotions');

                        const now = new Date();
                        let activeCount = 0;
                        let expiredCount = 0;
                        let totalUsage = 0;
                        let revenueImpact = 0;

                        activeContainer.innerHTML = '';
                        expiredContainer.innerHTML = '';

                        data.promotions.forEach(promotion => {
                            const endDate = new Date(promotion.end_date);
                            const isActive = promotion.is_active && endDate >= now;

                            const promotionCard = `
                                <div class="brand-card rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-semibold text-gray-800">${promotion.promotion_name}</h4>
                                        <span class="px-2 py-1 text-xs rounded-full ${isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                                            ${isActive ? 'Active' : 'Expired'}
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mb-2">${promotion.description}</p>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span>Type: ${promotion.promotion_type.replace('_', ' ').toUpperCase()}</span>
                                        <span>Used: ${promotion.used_count || 0}/${promotion.usage_limit || '∞'}</span>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500 mt-1">
                                        <span>Ends: ${formatDate(promotion.end_date)}</span>
                                        <button onclick="editPromotion(${promotion.id})" class="text-orange-700 hover:text-orange-800">Edit</button>
                                    </div>
                                </div>
                            `;

                            if (isActive) {
                                activeContainer.innerHTML += promotionCard;
                                activeCount++;
                            } else {
                                expiredContainer.innerHTML += promotionCard;
                                expiredCount++;
                            }

                            totalUsage += promotion.used_count || 0;
                        });

                        // Update stats
                        document.getElementById('totalCoupons').textContent = data.promotions.length;
                        document.getElementById('totalUsage').textContent = totalUsage;
                        document.getElementById('revenueImpact').textContent = `₱${revenueImpact.toFixed(2)}`;

                        if (activeCount === 0) {
                            activeContainer.innerHTML = '<p class="text-gray-500 text-sm">No active promotions</p>';
                        }
                        if (expiredCount === 0) {
                            expiredContainer.innerHTML = '<p class="text-gray-500 text-sm">No expired promotions</p>';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading promotions:', error);
                    document.getElementById('activePromotions').innerHTML = '<p class="text-red-500 text-sm">Error loading promotions</p>';
                    document.getElementById('expiredPromotions').innerHTML = '<p class="text-red-500 text-sm">Error loading promotions</p>';
                });
        }

        function createPromotion() {
            const form = document.getElementById('promotionForm');
            const formData = new FormData(form);

            fetch('api/vendor_management.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    closePromotionModal();
                    loadPromotions();
                } else {
                    showNotification(data.message || 'Error creating promotion', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error creating promotion', 'error');
            });
        }

        function editPromotion(promotionId) {
            alert('Edit promotion functionality for ID: ' + promotionId + ' - This feature can be expanded.');
        }

        function openChatModal(otherUserId, username) {
            currentChatUser = { id: otherUserId, username: username };
            document.getElementById('chatWith').textContent = 'Chat with ' + username;
            document.getElementById('chatModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            loadChatMessages(otherUserId);
        }

        function closeChatModal() {
            document.getElementById('chatModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentChatUser = null;
        }

        function loadChatMessages(otherUserId) {
            fetch('api/chat_system.php?action=get_messages', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `other_user_id=${otherUserId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const messagesContainer = document.getElementById('chatMessages');
                    messagesContainer.innerHTML = data.messages.map(msg => `
                        <div class="flex ${msg.is_own ? 'justify-end' : 'justify-start'} mb-3">
                            <div class="max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${msg.is_own ? 'bg-orange-500 text-white' : 'bg-gray-200 text-gray-800'}">
                                <p class="text-sm">${msg.message}</p>
                                <p class="text-xs mt-1 opacity-75">${formatTimeAgo(msg.created_at)}</p>
                            </div>
                        </div>
                    `).join('');

                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                }
            })
            .catch(error => console.error('Error loading messages:', error));
        }

        function sendMessage() {
            if (!currentChatUser) return;

            const messageInput = document.getElementById('chatInput');
            const message = messageInput.value.trim();

            if (!message) return;

            fetch('api/chat_system.php?action=send_message', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `other_user_id=${currentChatUser.id}&message=${encodeURIComponent(message)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    loadChatMessages(currentChatUser.id);
                } else {
                    showNotification(data.message || 'Error sending message', 'error');
                }
            })
            .catch(error => {
                console.error('Error sending message:', error);
                showNotification('Error sending message', 'error');
            });
        }

        // Allow sending message with Enter key
        document.addEventListener('DOMContentLoaded', function() {
            const chatInput = document.getElementById('chatInput');
            if (chatInput) {
                chatInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        sendMessage();
                    }
                });
            }
        });

        // Vendor Notification Functions
        function toggleNotificationDropdown() {
            const dropdown = document.getElementById('notificationDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
                if (!dropdown.classList.contains('hidden')) {
                    loadVendorNotifications();
                }
            }
        }

        function loadVendorNotifications() {
            fetch('api/vendor_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const list = document.getElementById('notificationList');
                    const badge = document.getElementById('notificationBadge');
                    
                    if (!Array.isArray(data) || data.length === 0) {
                        list.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm">No notifications</div>';
                        badge.classList.add('hidden');
                        return;
                    }
                    
                    let unreadCount = 0;
                    let html = '';
                    
                    data.forEach(notif => {
                        if (!notif.is_read) unreadCount++;
                        const time = new Date(notif.created_at).toLocaleString();
                        html += `
                            <div class="p-4 hover:bg-gray-50 cursor-pointer ${!notif.is_read ? 'bg-blue-50' : ''}">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-800">${notif.title}</p>
                                        <p class="text-sm text-gray-600 mt-1">${notif.message}</p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">${time}</p>
                            </div>
                        `;
                    });
                    
                    list.innerHTML = html;
                    
                    if (unreadCount > 0) {
                        badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(error => console.error('Error loading notifications:', error));
        }

        function clearAllNotifications() {
            fetch('api/mark_all_notifications_read.php', { method: 'POST' })
                .then(() => loadVendorNotifications())
                .catch(error => console.error('Error clearing notifications:', error));
        }

        // Initialize functions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart count on page load
            updateCartCount();

            // Load message badge
            loadMessageBadge();

            // Load vendor badges
            loadVendorBadges();

            // Check for low stock alerts
            checkLowStockAlerts();

            // Load promotions
            loadPromotions();

            // Load notifications
            loadVendorNotifications();

            // Load reels
            loadReels();

            // Handle reel upload form
            document.getElementById('reelUploadForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate video file
                const videoFile = document.getElementById('reelVideo').files[0];
                if (!videoFile) {
                    showNotification('Please select a video file', 'error');
                    return;
                }
                
                // Validate file size (100MB max)
                if (videoFile.size > 100 * 1024 * 1024) {
                    showNotification('Video file must be less than 100MB', 'error');
                    return;
                }
                
                const formData = new FormData();
                formData.append('video', videoFile);
                formData.append('title', document.getElementById('reelTitle').value || 'Untitled Reel');
                formData.append('description', document.getElementById('reelDescription').value);
                formData.append('product_id', document.getElementById('reelProduct').value);
                
                // Show loading state with progress
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                
                // Create progress indicator
                const progressContainer = document.createElement('div');
                progressContainer.className = 'flex items-center gap-2';
                progressContainer.innerHTML = `
                    <i class="fas fa-spinner fa-spin text-sm"></i>
                    <span>Uploading...</span>
                    <span class="text-xs text-gray-500" id="uploadProgress">0%</span>
                `;
                submitBtn.innerHTML = '';
                submitBtn.appendChild(progressContainer);
                
                // Show upload notification
                showNotification('📤 Starting reel upload...', 'info');
                
                fetch('api/vendor_reels.php?action=upload', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Upload response:', data);
                    
                    if (data.success) {
                        // Show success state
                        submitBtn.innerHTML = '<i class="fas fa-check text-green-500"></i> Upload Complete!';
                        submitBtn.classList.add('bg-green-500');
                        showNotification('✅ Reel uploaded successfully!', 'success');
                        
                        // Close modal and reload after 1.5 seconds
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('bg-green-500');
                            closeReelUploadModal();
                            loadReels();
                        }, 1500);
                    } else if (data.error) {
                        // Show error state
                        submitBtn.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Upload Failed';
                        submitBtn.classList.add('bg-red-500');
                        showNotification('❌ Error: ' + data.error, 'error');
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('bg-red-500');
                        }, 2000);
                    } else {
                        // Show generic error
                        submitBtn.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Upload Failed';
                        submitBtn.classList.add('bg-red-500');
                        showNotification('❌ Error uploading reel', 'error');
                        
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('bg-red-500');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Upload error:', error);
                    // Show error state
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Upload Failed';
                    submitBtn.classList.add('bg-red-500');
                    showNotification('❌ Error uploading reel: ' + error.message, 'error');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        submitBtn.classList.remove('bg-red-500');
                    }, 2000);
                });
            });

            // Handle product form submission (AJAX - no page redirect)
            document.getElementById('productForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                
                submitBtn.disabled = true;
                
                // Create progress indicator
                const progressContainer = document.createElement('div');
                progressContainer.className = 'flex items-center gap-2';
                progressContainer.innerHTML = `
                    <i class="fas fa-spinner fa-spin text-sm"></i>
                    <span>Saving...</span>
                `;
                submitBtn.innerHTML = '';
                submitBtn.appendChild(progressContainer);
                
                // Show save notification
                showNotification('💾 Saving product...', 'info');
                
                fetch('vendor.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Show success state
                        submitBtn.innerHTML = '<i class="fas fa-check text-green-500"></i> Saved!';
                        submitBtn.classList.add('bg-green-500');
                        showNotification('✅ ' + (data.message || 'Product saved successfully!'), 'success');
                        
                        // Close modal and reload after 1.5 seconds
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('bg-green-500');
                            closeProductModal();
                            loadProducts();
                        }, 1500);
                    } else {
                        // Show error state
                        submitBtn.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Save Failed';
                        submitBtn.classList.add('bg-red-500');
                        showNotification('❌ ' + (data.error || 'Error saving product'), 'error');
                        
                        // Reset button after 2 seconds
                        setTimeout(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            submitBtn.classList.remove('bg-red-500');
                        }, 2000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Show error state
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-circle text-red-500"></i> Save Failed';
                    submitBtn.classList.add('bg-red-500');
                    showNotification('❌ Error saving product: ' + error.message, 'error');
                    
                    // Reset button after 2 seconds
                    setTimeout(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        submitBtn.classList.remove('bg-red-500');
                    }, 2000);
                });
            });

            // Refresh message badge every 30 seconds
            setInterval(loadMessageBadge, 30000);

            // Check for new orders every 10 seconds
            setInterval(checkForNewOrders, 10000);
            
            // Check for new notifications every 30 seconds
            setInterval(loadVendorNotifications, 30000);

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                const profileDropdown = document.getElementById('profileDropdown');
                if (profileDropdown && !e.target.closest('[onclick="toggleProfileDropdown()"]') && !profileDropdown.contains(e.target)) {
                    profileDropdown.classList.add('hidden');
                }
            });

            // Handle anchor clicks for smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href');
                    if (targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
        });
    </script>
</body>
</html>
