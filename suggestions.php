<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$term = isset($_GET['term']) ? trim($_GET['term']) : '';

if ($term === '' || mb_strlen($term) < 2) {
    echo json_encode([
        'success' => true,
        'foods' => [],
        'vendors' => []
    ]);
    exit;
}

try {
    $like = '%' . $term . '%';

    // Foods/products suggestions
    $foodsSql = "SELECT p.id,
                        p.product_name,
                        p.description,
                        p.price,
                        p.image,
                        u.id AS vendor_id,
                        COALESCE(u.business_name, u.username) AS vendor_name
                 FROM products p
                 JOIN users u ON p.vendor_id = u.id
                 WHERE p.is_available = 1
                   AND (p.product_name LIKE ? OR p.description LIKE ? OR u.business_name LIKE ?)
                 ORDER BY p.product_name ASC
                 LIMIT 8";

    $foodsStmt = $conn->prepare($foodsSql);
    if (!$foodsStmt) {
        throw new Exception('Failed to prepare foods query: ' . $conn->error);
    }
    $foodsStmt->bind_param('sss', $like, $like, $like);
    $foodsStmt->execute();
    $foodsResult = $foodsStmt->get_result();

    $foods = [];
    while ($row = $foodsResult->fetch_assoc()) {
        $foods[] = [
            'id' => (int) $row['id'],
            'product_name' => $row['product_name'],
            'description' => $row['description'],
            'price' => (float) $row['price'],
            'image' => $row['image'],
            'vendor_id' => (int) $row['vendor_id'],
            'vendor_name' => $row['vendor_name']
        ];
    }
    $foodsStmt->close();

    // Vendor suggestions
    $vendorsSql = "SELECT id,
                          COALESCE(business_name, username) AS name,
                          profile_image,
                          business_logo,
                          address
                   FROM users
                   WHERE role = 'vendor'
                     AND (business_name LIKE ? OR username LIKE ? OR address LIKE ?)
                   ORDER BY name ASC
                   LIMIT 8";

    $vendorsStmt = $conn->prepare($vendorsSql);
    if (!$vendorsStmt) {
        throw new Exception('Failed to prepare vendors query: ' . $conn->error);
    }
    $vendorsStmt->bind_param('sss', $like, $like, $like);
    $vendorsStmt->execute();
    $vendorsResult = $vendorsStmt->get_result();

    $vendors = [];
    while ($row = $vendorsResult->fetch_assoc()) {
        $vendors[] = [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'profile_image' => $row['profile_image'],
            'business_logo' => $row['business_logo'],
            'address' => $row['address']
        ];
    }
    $vendorsStmt->close();

    echo json_encode([
        'success' => true,
        'foods' => $foods,
        'vendors' => $vendors
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
