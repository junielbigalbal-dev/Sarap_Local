<?php
/**
 * Advanced Search API
 * Handles complex product searches with filters and sorting
 */

session_start();
require_once '../db.php';
require_once '../includes/api-response.php';
require_once '../includes/validators.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    sendUnauthorized('Please log in as a customer to search');
}

// Get search parameters
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 999999;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
$offset = ($page - 1) * $per_page;

// Validate prices
if ($price_min < 0 || $price_max < 0 || $price_min > $price_max) {
    sendValidationError(['price' => 'Invalid price range']);
}

try {
    // Build WHERE clause
    $where_conditions = ['p.is_available = 1', 'u.role = "vendor"'];
    $params = [];
    $types = '';

    // Search query
    if (!empty($search)) {
        $search_term = '%' . $search . '%';
        $where_conditions[] = '(p.product_name LIKE ? OR p.description LIKE ? OR u.business_name LIKE ?)';
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'sss';
    }

    // Category filter
    if ($category > 0) {
        $where_conditions[] = 'p.category_id = ?';
        $params[] = $category;
        $types .= 'i';
    }

    // Price filter
    if ($price_min > 0 || $price_max < 999999) {
        $where_conditions[] = 'p.price BETWEEN ? AND ?';
        $params[] = $price_min;
        $params[] = $price_max;
        $types .= 'dd';
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Build ORDER BY
    $order_by = 'p.created_at DESC';
    switch ($sort) {
        case 'price_low':
            $order_by = 'p.price ASC';
            break;
        case 'price_high':
            $order_by = 'p.price DESC';
            break;
        case 'rating':
            $order_by = 'avg_rating DESC, p.created_at DESC';
            break;
        case 'popular':
            $order_by = 'view_count DESC, p.created_at DESC';
            break;
    }

    // Get total count
    $count_query = "SELECT COUNT(*) as total FROM products p 
                    JOIN users u ON p.vendor_id = u.id 
                    WHERE $where_clause";
    $stmt = $conn->prepare($count_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Get products
    $query = "SELECT p.id, p.product_name, p.description, p.price, p.image, 
                     p.stock_quantity, p.view_count, p.created_at,
                     u.id as vendor_id, u.business_name, u.profile_image,
                     COALESCE(AVG(r.rating), 0) as avg_rating,
                     COUNT(DISTINCT r.id) as review_count
              FROM products p
              JOIN users u ON p.vendor_id = u.id
              LEFT JOIN reviews r ON p.id = r.product_id
              WHERE $where_clause
              GROUP BY p.id
              ORDER BY $order_by
              LIMIT ? OFFSET ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    // Add pagination params
    $stmt->bind_param($types . 'ii', ...array_merge($params, [$per_page, $offset]));
    $stmt->execute();
    $result = $stmt->get_result();

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = [
            'id' => (int)$row['id'],
            'name' => htmlspecialchars($row['product_name']),
            'description' => htmlspecialchars($row['description']),
            'price' => (float)$row['price'],
            'image' => htmlspecialchars($row['image']),
            'stock' => (int)$row['stock_quantity'],
            'views' => (int)$row['view_count'],
            'vendor' => [
                'id' => (int)$row['vendor_id'],
                'name' => htmlspecialchars($row['business_name']),
                'image' => htmlspecialchars($row['profile_image'])
            ],
            'rating' => (float)$row['avg_rating'],
            'reviews' => (int)$row['review_count']
        ];
    }
    $stmt->close();

    sendPaginated($products, $total, $page, $per_page, 'Search results');

} catch (Exception $e) {
    error_log('Search API Error: ' . $e->getMessage());
    sendServerError('Search failed. Please try again.');
}
