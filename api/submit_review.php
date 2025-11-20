<?php
include '../db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'submit_review':
        handleSubmitReview();
        break;
    case 'get_product_details':
        handleGetProductDetails();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function handleSubmitReview() {
    global $conn;

    $customer_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text'] ?? '');

    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        return;
    }

    try {
        // Check if user already reviewed this product
        $check_query = "SELECT id FROM reviews WHERE reviewer_id = ? AND product_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this product']);
            return;
        }

        // Insert review
        $insert_query = "INSERT INTO reviews (reviewer_id, product_id, rating, review_text, review_type)
                        VALUES (?, ?, ?, ?, 'product')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("iiss", $customer_id, $product_id, $rating, $review_text);
        $stmt->execute();
        $review_id = $conn->insert_id;
        $stmt->close();

        // Handle photo uploads
        if (!empty($_FILES['photos']['name'][0])) {
            $upload_dir = '../uploads/reviews/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            foreach ($_FILES['photos']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['photos']['error'][$key] === UPLOAD_ERR_OK) {
                    $filename = uniqid() . '_' . basename($_FILES['photos']['name'][$key]);
                    $filepath = $upload_dir . $filename;

                    if (move_uploaded_file($tmp_name, $filepath)) {
                        // Save photo record
                        $photo_query = "INSERT INTO review_photos (review_id, photo_path) VALUES (?, ?)";
                        $stmt = $conn->prepare($photo_query);
                        $stmt->bind_param("is", $review_id, $filepath);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }

        // Update product rating
        updateProductRating($product_id);

        echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error submitting review: ' . $e->getMessage()]);
    }
}

function handleGetProductDetails() {
    global $conn;

    $product_id = (int)$_GET['product_id'];

    try {
        // Get product details
        $product_query = "SELECT p.*, u.business_name as vendor_name, u.username as vendor_username,
                                 c.name as category_name, u.latitude, u.longitude
                         FROM products p
                         JOIN users u ON p.vendor_id = u.id
                         LEFT JOIN categories c ON p.category_id = c.id
                         WHERE p.id = ?";

        $stmt = $conn->prepare($product_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            return;
        }

        // Get reviews for this product
        $reviews_query = "SELECT r.*, u.username, rp.photo_path
                         FROM reviews r
                         JOIN users u ON r.reviewer_id = u.id
                         LEFT JOIN review_photos rp ON r.id = rp.review_id
                         WHERE r.product_id = ?
                         ORDER BY r.created_at DESC
                         LIMIT 10";

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
        echo json_encode(['success' => false, 'message' => 'Error loading product details']);
    }
}

function updateProductRating($product_id) {
    global $conn;

    try {
        $rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews
                        FROM reviews WHERE product_id = ?";

        $stmt = $conn->prepare($rating_query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $rating_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $update_query = "UPDATE products SET rating = ?, total_reviews = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("dii", $rating_data['avg_rating'], $rating_data['total_reviews'], $product_id);
        $stmt->execute();
        $stmt->close();

    } catch (Exception $e) {
        // Log error but don't fail the review submission
        error_log('Error updating product rating: ' . $e->getMessage());
    }
}
?>
