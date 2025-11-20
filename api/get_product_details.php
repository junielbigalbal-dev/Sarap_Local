<?php
include '../db.php';

// This API doesn't require authentication for viewing product details
header('Content-Type: application/json');

$product_id = (int)$_GET['product_id'];

try {
    // Get product details
    $product_query = "SELECT p.*, u.business_name as vendor_name, u.username as vendor_username,
                             c.name as category_name, u.latitude, u.longitude
                     FROM products p
                     JOIN users u ON p.vendor_id = u.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     WHERE p.id = ? AND p.is_available = 1";

    $stmt = $conn->prepare($product_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Get reviews for this product
    $reviews_query = "SELECT r.*, u.username, rp.photo_path
                     FROM reviews r
                     JOIN users u ON r.reviewer_id = u.id
                     LEFT JOIN review_photos rp ON r.id = rp.review_id
                     WHERE r.product_id = ? AND r.review_type = 'product'
                     ORDER BY r.created_at DESC
                     LIMIT 5";

    $stmt = $conn->prepare($reviews_query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $reviews_result = $stmt->get_result();

    $reviews = [];
    $review_data = [];
    while ($row = $reviews_result->fetch_assoc()) {
        $review_id = $row['id'];
        if (!isset($review_data[$review_id])) {
            $review_data[$review_id] = [
                'id' => $row['id'],
                'rating' => $row['rating'],
                'review_text' => $row['review_text'],
                'username' => $row['username'],
                'created_at' => $row['created_at'],
                'photos' => []
            ];
        }
        if ($row['photo_path']) {
            $review_data[$review_id]['photos'][] = ['photo_path' => $row['photo_path']];
        }
    }

    foreach ($review_data as $review) {
        $reviews[] = $review;
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'product' => $product,
        'reviews' => $reviews
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading product details: ' . $e->getMessage()]);
}
?>
