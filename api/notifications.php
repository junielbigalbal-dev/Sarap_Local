<?php
header('Content-Type: application/json');
require_once '../db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Get unread notifications count
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['count'])) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    echo json_encode(['count' => $count]);
    exit();
}

// Get all notifications
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("
        SELECT n.*, u.username, u.role 
        FROM notifications n
        JOIN users u ON n.user_id = u.id
        WHERE n.user_id = ? 
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'id' => $row['id'],
            'type' => $row['type'],
            'title' => $row['title'],
            'message' => $row['message'],
            'is_read' => (bool)$row['is_read'],
            'created_at' => $row['created_at'],
            'from_user' => [
                'username' => $row['username'],
                'role' => $row['role']
            ]
        ];
    }
    
    // Mark as read
    $conn->query("UPDATE notifications SET is_read = TRUE WHERE user_id = $user_id AND is_read = FALSE");
    
    echo json_encode($notifications);
    exit();
}

// Create a new notification (for testing)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_notification'])) {
    $type = $_POST['type'] ?? 'comment';
    $title = $_POST['title'] ?? 'Test Notification';
    $message = $_POST['message'] ?? 'This is a test notification';
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('isss', $user_id, $type, $title, $message);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Test notification created']);
    exit();
}

http_response_code(400);
echo json_encode(['error' => 'Invalid request']);
