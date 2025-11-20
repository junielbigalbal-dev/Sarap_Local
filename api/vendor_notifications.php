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
$action = $_GET['action'] ?? '';

try {
    if ($action === 'count') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        echo json_encode(['count' => $result['count'] ?? 0]);
        $stmt->close();
    } else {
        $stmt = $conn->prepare("
            SELECT id, type, title, message, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $vendor_id AND is_read = 0");
        
        echo json_encode($notifications);
        $stmt->close();
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
