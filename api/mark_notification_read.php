<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (!isset($_POST['notification_id'])) {
    echo json_encode(['error' => 'Notification ID is required']);
    exit();
}

include '../db.php';

$notification_id = (int)$_POST['notification_id'];
$user_id = $_SESSION['user_id'];

// Verify the notification belongs to the user
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $notification_id, $user_id);
$success = $stmt->execute();

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Failed to mark notification as read']);
}
?>
