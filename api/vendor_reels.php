<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$vendor_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No video file uploaded');
        }

        $file = $_FILES['video'];
        $allowed_types = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
        
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Invalid file type. Only MP4 and MOV allowed.');
        }

        if ($file['size'] > 100 * 1024 * 1024) {
            throw new Exception('File too large. Max 100MB.');
        }

        $upload_dir = __DIR__ . '/../uploads/reels/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $filename = 'reel_' . $vendor_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save video');
        }

        $product_id = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        $title = trim($_POST['title'] ?? 'Untitled Reel');
        $description = trim($_POST['description'] ?? '');

        $stmt = $conn->prepare("
            INSERT INTO vendor_reels (vendor_id, product_id, video_path, title, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $video_path = 'uploads/reels/' . $filename;
        $stmt->bind_param('iisss', $vendor_id, $product_id, $video_path, $title, $description);
        
        if (!$stmt->execute()) {
            unlink($filepath);
            throw new Exception('Database error: ' . $stmt->error);
        }

        echo json_encode(['success' => true, 'id' => $conn->insert_id, 'message' => 'Reel uploaded successfully']);
        $stmt->close();

    } else if ($action === 'list') {
        $stmt = $conn->prepare("
            SELECT r.*, p.product_name, p.price 
            FROM vendor_reels r 
            LEFT JOIN products p ON r.product_id = p.id 
            WHERE r.vendor_id = ? 
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $reels = [];
        while ($row = $result->fetch_assoc()) {
            $reels[] = $row;
        }
        
        echo json_encode($reels);
        $stmt->close();

    } else if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $reel_id = $_POST['reel_id'] ?? 0;
        
        $stmt = $conn->prepare("SELECT video_path FROM vendor_reels WHERE id = ? AND vendor_id = ?");
        $stmt->bind_param('ii', $reel_id, $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Reel not found');
        }
        
        $reel = $result->fetch_assoc();
        $stmt->close();
        
        $filepath = __DIR__ . '/../' . $reel['video_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        $stmt = $conn->prepare("DELETE FROM vendor_reels WHERE id = ? AND vendor_id = ?");
        $stmt->bind_param('ii', $reel_id, $vendor_id);
        $stmt->execute();
        
        echo json_encode(['success' => true, 'message' => 'Reel deleted']);
        $stmt->close();

    } else {
        throw new Exception('Invalid action: ' . ($action ?: 'no action provided'));
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
