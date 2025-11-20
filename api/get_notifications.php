<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

include '../db.php';

$user_id = $_SESSION['user_id'];
$unread_only = isset($_GET['unread']) ? (bool)$_GET['unread'] : true;

$query = "SELECT n.*, u.username as sender_name, u.role as sender_role 
          FROM notifications n 
          LEFT JOIN users u ON n.related_id = u.id 
          WHERE n.user_id = ? ";
          
if ($unread_only) {
    $query .= " AND n.is_read = 0";
}

$query .= " ORDER BY n.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode(['notifications' => $notifications]);
?>
