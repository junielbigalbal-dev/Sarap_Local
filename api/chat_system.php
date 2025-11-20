<?php
include '../db.php';
session_start();

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_conversations':
            // Get all conversations for the current user (vendor or customer)
            $conversations_query = "
                SELECT DISTINCT
                    CASE
                        WHEN m.sender_id = ? THEN m.receiver_id
                        ELSE m.sender_id
                    END as other_user_id,
                    u.username,
                    u.role,
                    u.profile_image,
                    (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id)
                     OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = other_user_id)
                     OR (sender_id = other_user_id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time,
                    (SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND sender_id = other_user_id AND created_at > IFNULL((SELECT MAX(read_at) FROM message_reads WHERE user_id = ? AND message_id IN (SELECT id FROM messages WHERE receiver_id = ? AND sender_id = other_user_id)), '1970-01-01')) as unread_count
                FROM messages m
                JOIN users u ON (
                    CASE
                        WHEN m.sender_id = ? THEN u.id = m.receiver_id
                        ELSE u.id = m.sender_id
                    END
                )
                WHERE m.sender_id = ? OR m.receiver_id = ?
                GROUP BY other_user_id, u.username, u.role, u.profile_image
                ORDER BY last_message_time DESC";

            $stmt = $conn->prepare($conversations_query);
            $stmt->bind_param("iiiiiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $conversations = [];
            while ($row = $result->fetch_assoc()) {
                $conversations[] = [
                    'user_id' => $row['other_user_id'],
                    'username' => $row['username'],
                    'role' => $row['role'],
                    'profile_image' => $row['profile_image'],
                    'last_message' => $row['last_message'],
                    'last_message_time' => $row['last_message_time'],
                    'unread_count' => (int)$row['unread_count']
                ];
            }

            echo json_encode(['success' => true, 'conversations' => $conversations]);
            break;

        case 'get_messages':
            $other_user_id = (int)$_POST['other_user_id'];

            // Mark messages as read
            $update_query = "INSERT INTO message_reads (user_id, message_id, read_at)
                           SELECT ?, id, NOW()
                           FROM messages
                           WHERE sender_id = ? AND receiver_id = ? AND id NOT IN (
                               SELECT message_id FROM message_reads WHERE user_id = ?
                           )";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iiii", $user_id, $other_user_id, $user_id, $user_id);
            $stmt->execute();

            // Get messages between users
            $messages_query = "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at,
                                     u.username as sender_name, u.profile_image as sender_image
                              FROM messages m
                              JOIN users u ON m.sender_id = u.id
                              WHERE (m.sender_id = ? AND m.receiver_id = ?)
                                 OR (m.sender_id = ? AND m.receiver_id = ?)
                              ORDER BY m.created_at ASC";

            $stmt = $conn->prepare($messages_query);
            $stmt->bind_param("iiii", $user_id, $other_user_id, $other_user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'id' => $row['id'],
                    'sender_id' => $row['sender_id'],
                    'receiver_id' => $row['receiver_id'],
                    'message' => $row['message'],
                    'created_at' => $row['created_at'],
                    'sender_name' => $row['sender_name'],
                    'sender_image' => $row['sender_image'],
                    'is_own' => $row['sender_id'] == $user_id
                ];
            }

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        case 'send_message':
            $other_user_id = (int)$_POST['other_user_id'];
            $message = trim($_POST['message']);

            if (empty($message)) {
                throw new Exception('Message cannot be empty');
            }

            // Insert message
            $insert_query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iis", $user_id, $other_user_id, $message);

            if ($stmt->execute()) {
                $message_id = $conn->insert_id;

                // Create notification for receiver
                $notification_query = "INSERT INTO notifications (user_id, type, title, message, link)
                                     VALUES (?, 'message', 'New Message', ?, CONCAT('chat.php?user=', ?))";
                $stmt = $conn->prepare($notification_query);
                $notification_message = "You have a new message from " . $_SESSION['username'];
                $stmt->bind_param("issi", $other_user_id, $notification_message, $user_id);
                $stmt->execute();

                echo json_encode([
                    'success' => true,
                    'message' => 'Message sent successfully',
                    'message_id' => $message_id
                ]);
            } else {
                throw new Exception('Failed to send message');
            }
            break;

        case 'get_unread_count':
            $unread_query = "SELECT COUNT(*) as unread_count
                           FROM messages m
                           WHERE m.receiver_id = ? AND m.id NOT IN (
                               SELECT mr.message_id FROM message_reads mr WHERE mr.user_id = ?
                           )";

            $stmt = $conn->prepare($unread_query);
            $stmt->bind_param("ii", $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();

            echo json_encode([
                'success' => true,
                'unread_count' => (int)$result['unread_count']
            ]);
            break;

        case 'mark_conversation_read':
            $other_user_id = (int)$_POST['other_user_id'];

            $update_query = "INSERT INTO message_reads (user_id, message_id, read_at)
                           SELECT ?, id, NOW()
                           FROM messages
                           WHERE sender_id = ? AND receiver_id = ? AND id NOT IN (
                               SELECT message_id FROM message_reads WHERE user_id = ?
                           )";

            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iiii", $user_id, $other_user_id, $user_id, $user_id);
            $stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Conversation marked as read']);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
