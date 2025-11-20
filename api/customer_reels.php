<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    if ($action === 'feed') {
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $stmt = $conn->prepare("
            SELECT r.id, r.vendor_id, r.product_id, r.video_path, r.thumbnail_path, r.title, r.description, 
                   r.duration, r.view_count, r.created_at, r.updated_at,
                   u.business_name, u.profile_image, 
                   p.product_name, p.price
            FROM vendor_reels r
            JOIN users u ON r.vendor_id = u.id
            LEFT JOIN products p ON r.product_id = p.id
            WHERE u.role = 'vendor'
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reels = [];
        while ($row = $result->fetch_assoc()) {
            $reels[] = $row;
        }
        
        echo json_encode($reels);
        $stmt->close();

    } else if ($action === 'increment_views' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $reel_id = $_POST['reel_id'] ?? 0;
        
        $stmt = $conn->prepare("UPDATE vendor_reels SET view_count = view_count + 1 WHERE id = ?");
        $stmt->bind_param('i', $reel_id);
        $stmt->execute();
        
        echo json_encode(['success' => true]);
        $stmt->close();

    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
