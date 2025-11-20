<?php
include '../db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$vendor_id = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;
$price_filter = isset($_GET['price']) ? $_GET['price'] : '';

if ($term === '' || strlen($term) < 2) {
    echo json_encode([
        'success' => true,
        'products' => [],
        'vendors' => []
    ]);
    exit();
}

$products = [];
$vendors = [];

try {
    $like = '%' . $term . '%';

    $where_conditions = ['p.is_available = 1'];
    $params = [];
    $types = '';

    $where_conditions[] = '(p.product_name LIKE ? OR p.description LIKE ? OR u.business_name LIKE ?)';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'sss';

    if ($category_id > 0) {
        $where_conditions[] = 'p.category_id = ?';
        $params[] = $category_id;
        $types .= 'i';
    }

    if ($vendor_id > 0) {
        $where_conditions[] = 'u.id = ?';
        $params[] = $vendor_id;
        $types .= 'i';
    }

    if ($price_filter !== '') {
        if ($price_filter === 'under_100') {
            $where_conditions[] = 'p.price < 100';
        } elseif ($price_filter === '100_300') {
            $where_conditions[] = 'p.price BETWEEN 100 AND 300';
        } elseif ($price_filter === '300_500') {
            $where_conditions[] = 'p.price BETWEEN 300 AND 500';
        } elseif ($price_filter === 'over_500') {
            $where_conditions[] = 'p.price > 500';
        }
    }

    $where_clause = implode(' AND ', $where_conditions);

    $products_sql = "SELECT p.id,
                             p.product_name,
                             p.description,
                             p.price,
                             p.image,
                             COALESCE(c.name, '') AS category_name,
                             u.id AS vendor_id,
                             COALESCE(u.business_name, u.username) AS vendor_name
                      FROM products p
                      JOIN users u ON p.vendor_id = u.id
                      LEFT JOIN categories c ON p.category_id = c.id
                      WHERE $where_clause
                      ORDER BY p.created_at DESC
                      LIMIT 10";

    $stmt = $conn->prepare($products_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare products query');
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();

    $vendor_sql = "SELECT id,
                          COALESCE(business_name, username) AS name,
                          profile_image,
                          address
                   FROM users
                   WHERE role = 'vendor'
                     AND (business_name LIKE ? OR username LIKE ? OR address LIKE ?)
                   ORDER BY created_at DESC
                   LIMIT 10";

    $stmt = $conn->prepare($vendor_sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare vendors query');
    }

    $stmt->bind_param('sss', $like, $like, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $vendors[] = $row;
    }
    $stmt->close();

    if (empty($products) && empty($vendors)) {
        $fallback_products_sql = "SELECT p.id,
                                         p.product_name,
                                         p.description,
                                         p.price,
                                         p.image,
                                         COALESCE(c.name, '') AS category_name,
                                         u.id AS vendor_id,
                                         COALESCE(u.business_name, u.username) AS vendor_name
                                  FROM products p
                                  JOIN users u ON p.vendor_id = u.id
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  WHERE p.is_available = 1
                                  ORDER BY p.created_at DESC
                                  LIMIT 10";

        $stmt = $conn->prepare($fallback_products_sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }
            $stmt->close();
        }

        $fallback_vendors_sql = "SELECT id,
                                         COALESCE(business_name, username) AS name,
                                         profile_image,
                                         address
                                  FROM users
                                  WHERE role = 'vendor'
                                  ORDER BY created_at DESC
                                  LIMIT 10";

        $stmt = $conn->prepare($fallback_vendors_sql);
        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
            $stmt->close();
        }
    }

    echo json_encode([
        'success' => true,
        'products' => $products,
        'vendors' => $vendors
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

?>
