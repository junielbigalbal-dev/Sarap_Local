
<?php
include 'db.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: login.php');
    exit();
}

$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];
$current_username = $_SESSION['username'];

// Get current user info
$user_query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle AJAX requests for chat
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'get_conversations') {
        try {
            if ($current_user_role === 'customer') {
                $conversations_query = "SELECT DISTINCT u.id, u.username, u.business_name, u.profile_image, (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message, (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time, (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count FROM users u WHERE u.role = 'vendor' AND (u.id IN (SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ?) OR u.id IN (SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?) OR u.id IN (SELECT DISTINCT vendor_id FROM orders WHERE customer_id = ?)) ORDER BY last_message_time DESC";
            } else {
                $conversations_query = "SELECT DISTINCT u.id, u.username, u.profile_image, (SELECT message FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message, (SELECT created_at FROM messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_message_time, (SELECT COUNT(*) FROM messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count FROM users u WHERE u.role = 'customer' AND (u.id IN (SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ?) OR u.id IN (SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?) OR u.id IN (SELECT DISTINCT customer_id FROM orders WHERE vendor_id = ?)) ORDER BY last_message_time DESC";
            }
            $stmt = $conn->prepare($conversations_query);
            if ($current_user_role === 'customer') {
                $stmt->bind_param("iiiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
            } else {
                $stmt->bind_param("iiiiiii", $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id, $current_user_id);
            }
            $stmt->execute();
            $conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['success' => true, 'conversations' => $conversations]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'get_messages') {
        $other_user_id = (int)$_GET['user_id'];
        try {
            // Mark messages as read
            $update_query = "UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $other_user_id, $current_user_id);
            $stmt->execute();
            $stmt->close();
            // Get messages
            $messages_query = "SELECT m.*, u.username as sender_name, u.profile_image as sender_image FROM messages m JOIN users u ON m.sender_id = u.id WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?) ORDER BY m.created_at ASC";
            $stmt = $conn->prepare($messages_query);
            $stmt->bind_param("iiii", $current_user_id, $other_user_id, $other_user_id, $current_user_id);
            $stmt->execute();
            $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            echo json_encode(['success' => true, 'messages' => $messages]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'send_message') {
        $other_user_id = (int)$_POST['user_id'];
        $message = trim($_POST['message']);
        if (empty($message)) {
            echo json_encode(['success' => false, 'error' => 'Message cannot be empty']);
            exit();
        }
        try {
            $insert_query = "INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iis", $current_user_id, $other_user_id, $message);
            $stmt->execute();
            $stmt->close();
            // Get the inserted message with sender info
            $message_query = "SELECT m.*, u.username as sender_name, u.profile_image as sender_image FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.id = LAST_INSERT_ID()";
            $stmt = $conn->prepare($message_query);
            $stmt->execute();
            $new_message = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            echo json_encode(['success' => true, 'message' => $new_message]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }

    if ($action === 'get_unread_count') {
        try {
            $count_query = "SELECT COUNT(*) as unread FROM messages WHERE receiver_id = ? AND is_read = 0";
            $stmt = $conn->prepare($count_query);
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            echo json_encode(['success' => true, 'unread_count' => $result['unread']]);
            exit();
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit();
        }
    }
}

// Handle direct chat access with specific vendor
$chat_with_user_id = null;
$chat_with_user_name = null;
if (isset($_GET['user_id'])) {
    $chat_with_user_id = (int)$_GET['user_id'];
    $user_info_query = "SELECT username, business_name, profile_image FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_info_query);
    $stmt->bind_param("i", $chat_with_user_id);
    $stmt->execute();
    $user_info = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($user_info) {
        $chat_with_user_name = $user_info['business_name'] ?: $user_info['username'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Messages — Sarap Local</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --orange-primary: #f97316; /* matches logo */
      --orange-hover: #ea580c;
      --orange-light: #fed7aa;
    }
    .chat-container { height: calc(100vh - 200px); }
    .message-bubble { max-width: 70%; word-wrap: break-word; }
    .message-sent { background: linear-gradient(45deg, var(--orange-primary), var(--orange-hover)); }
    .message-received { background: #f1f5f9; }
    .chat-background { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
    .glassmorphism { background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2); }

    /* Brand the chat header */
    #chatHeader {
      background: linear-gradient(135deg, var(--orange-primary), var(--orange-hover));
      color: #fff;
      border: none;
    }
    #chatHeader h3, #chatHeader p { color: #fff; }
    #chatHeader img { border-color: rgba(255,255,255,0.85); }

    /* Conversation selected highlight tweak */
    .conversation-item.bg-orange-50 { background-color: var(--orange-light); }
    .conversation-item:hover { background-color: rgba(253, 186, 116, 0.2); }
  </style>
</head>
<body class="bg-gray-50 min-h-screen">
  <header class="bg-white shadow-sm border-b sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center">
          <a href="<?php echo $current_user_role === 'customer' ? 'customer.php' : 'vendor.php'; ?>" class="mr-4 text-gray-600 hover:text-gray-800">
            <i class="fas fa-arrow-left text-lg"></i>
          </a>
          <img src="images/S.png" alt="Sarap Local" class="w-8 h-8 mr-3">
          <h1 class="text-xl font-bold text-gray-800">Messages</h1>
        </div>
        <div class="relative">
          <button onclick="toggleNotifications()" class="text-gray-600 hover:text-orange-500 transition-colors relative">
            <i class="fas fa-bell text-xl"></i>
            <span id="notificationBadge" class="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center hidden">0</span>
          </button>
        </div>
      </div>
    </div>
  </header>
  <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="chat-container glassmorphism rounded-2xl shadow-2xl border border-white border-opacity-20 overflow-hidden">
      <div class="w-1/3 border-r border-gray-200 border-opacity-50 flex flex-col">
        <div class="p-4 border-b border-gray-200 border-opacity-50">
          <h2 class="text-lg font-semibold text-gray-800">Conversations</h2>
        </div>
        <div id="conversationsList" class="flex-1 overflow-y-auto bg-white bg-opacity-5">
          <div class="p-4 text-center text-gray-500">
            <i class="fas fa-comments text-3xl mb-2"></i>
            <p>No conversations yet</p>
            <p class="text-sm">Start a conversation by ordering from a vendor!</p>
          </div>
        </div>
      </div>
      <div class="flex-1 flex flex-col bg-white bg-opacity-10">
        <div id="chatHeader" class="p-4 border-b border-gray-200 border-opacity-50 bg-white bg-opacity-20 hidden">
          <div class="flex items-center">
            <img id="chatUserImage" src="" alt="" class="w-10 h-10 rounded-full mr-3 border-2 border-white object-cover">
            <div>
              <h3 id="chatUserName" class="font-semibold text-gray-800"></h3>
              <p id="chatUserRole" class="text-sm text-gray-600"></p>
            </div>
          </div>
        </div>
        <div id="messagesArea" class="flex-1 overflow-y-auto p-4 bg-white bg-opacity-5">
          <div class="text-center text-gray-500 py-8">
            <i class="fas fa-comment-slash text-4xl mb-2"></i>
            <p>No messages yet</p>
            <p class="text-sm">Start the conversation!</p>
          </div>
        </div>
        <div id="messageInputArea" class="p-4 border-t border-gray-200 border-opacity-50 bg-white bg-opacity-20 hidden">
          <form onsubmit="sendMessage(event)">
            <div class="flex space-x-2">
              <input type="text" id="messageInput" placeholder="Type your message..." class="flex-1 border border-gray-300 border-opacity-50 rounded-lg px-4 py-2 bg-white bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
              <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg shadow-lg transition-all duration-300">
                <i class="fas fa-paper-plane"></i>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </main>
  <script>
    let currentChatUserId = <?php echo $chat_with_user_id ? $chat_with_user_id : 'null'; ?>;
    let currentUserId = <?php echo $current_user_id; ?>;
    let currentUserRole = '<?php echo $current_user_role; ?>';
    let lastMessageCount = 0;
    let conversationsCache = {};

    // Set chat header if direct access
    <?php if ($chat_with_user_id && $chat_with_user_name): ?>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('chatUserName').textContent = '<?php echo addslashes($chat_with_user_name); ?>';
        document.getElementById('chatUserRole').textContent = '<?php echo $current_user_role === 'customer' ? 'Vendor' : 'Customer'; ?>';
        <?php if (!empty($user_info['profile_image'])): ?>
        document.getElementById('chatUserImage').src = '<?php echo addslashes($user_info['profile_image']); ?>';
        <?php else: ?>
        document.getElementById('chatUserImage').src = 'images/S.png';
        <?php endif; ?>
        document.getElementById('chatHeader').classList.remove('hidden');
        document.getElementById('messagesArea').classList.remove('hidden');
        document.getElementById('messageInputArea').classList.remove('hidden');
    });
    <?php endif; ?>

    document.addEventListener('DOMContentLoaded', function() {
        loadConversations();
        loadUnreadCount();

        // Request notification permission
        if ("Notification" in window && Notification.permission === "default") {
            Notification.requestPermission();
        }

        // Poll for new messages every 5 seconds
        setInterval(function() {
            if (currentChatUserId) {
                loadMessages(currentChatUserId);
            }
            loadUnreadCount();
            checkForNewMessages();
        }, 5000);
    });

    function loadConversations() {
        fetch('chat.php?action=get_conversations')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayConversations(data.conversations);
                } else {
                    console.error('Error loading conversations:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function displayConversations(conversations) {
        const conversationsList = document.getElementById('conversationsList');

        if (conversations.length === 0) {
            conversationsList.innerHTML = `<div class="p-4 text-center text-gray-500"><i class="fas fa-comments text-3xl mb-2"></i><p>No conversations yet</p><p class="text-sm">Start a conversation by ordering from a vendor!</p></div>`;
            return;
        }

        // Cache and render
        conversationsList.innerHTML = conversations.map(conversation => {
            conversationsCache[conversation.id] = conversation;
            const name = conversation.business_name || conversation.username;
            const img = conversation.profile_image || 'images/S.png';
            return `<div class="conversation-item p-4 border-b border-gray-100 border-opacity-50 hover:bg-white hover:bg-opacity-10 cursor-pointer ${conversation.id == currentChatUserId ? 'bg-orange-50 border-orange-200' : ''}" data-id="${conversation.id}" data-name="${name.replace(/"/g,'&quot;')}" data-img="${img}" onclick="openChat(${conversation.id})"><div class="flex items-center"><img src="${img}" alt="${conversation.username}" class="w-12 h-12 rounded-full mr-3 object-cover border-2 border-white border-opacity-50"><div class="flex-1"><h4 class="font-medium text-gray-800">${name}</h4><p class="text-sm text-gray-600 truncate">${conversation.last_message || 'No messages yet'}</p></div><div class="text-right"><p class="text-xs text-gray-500">${formatTime(conversation.last_message_time)}</p>${conversation.unread_count > 0 ? `<span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center mt-1">${conversation.unread_count}</span>` : ''}</div></div></div>`;
        }).join('');
    }

    function openChat(userId) {
        currentChatUserId = userId;
        loadMessages(userId);

        // Update selected conversation styling
        document.querySelectorAll('.conversation-item').forEach(item => {
            item.classList.remove('bg-orange-50', 'border-orange-200');
        });
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('bg-orange-50', 'border-orange-200');
        } else {
            const sel = document.querySelector(`.conversation-item[data-id="${userId}"]`);
            sel && sel.classList.add('bg-orange-50', 'border-orange-200');
        }

        // Show chat area
        document.getElementById('chatHeader').classList.remove('hidden');
        document.getElementById('messagesArea').classList.remove('hidden');
        document.getElementById('messageInputArea').classList.remove('hidden');

        // Update header with avatar and name
        const conversation = conversationsCache[userId];
        let name = '';
        let img = '';
        if (conversation) {
            name = conversation.business_name || conversation.username || '';
            img = conversation.profile_image || 'images/S.png';
        } else {
            // Try from DOM data attributes
            const sel = document.querySelector(`.conversation-item[data-id="${userId}"]`);
            if (sel) {
                name = sel.getAttribute('data-name') || '';
                img = sel.getAttribute('data-img') || 'images/S.png';
            }
        }
        if (name) document.getElementById('chatUserName').textContent = name;
        document.getElementById('chatUserRole').textContent = (currentUserRole === 'customer') ? 'Vendor' : 'Customer';
        document.getElementById('chatUserImage').src = img;

        // Scroll to bottom of messages
        setTimeout(() => {
            document.getElementById('messagesArea').scrollTop = document.getElementById('messagesArea').scrollHeight;
        }, 100);
    }

    function loadMessages(userId) {
        fetch(`chat.php?action=get_messages&user_id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayMessages(data.messages);
                } else {
                    console.error('Error loading messages:', data.error);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function displayMessages(messages) {
        const messagesArea = document.getElementById('messagesArea');

        if (messages.length === 0) {
            messagesArea.innerHTML = `<div class="text-center text-gray-500 py-8"><i class="fas fa-comment-slash text-4xl mb-2"></i><p>No messages yet</p><p class="text-sm">Start the conversation!</p></div>`;
            return;
        }

        messagesArea.innerHTML = messages.map(message => `<div class="message-bubble mb-4 ${message.sender_id == currentUserId ? 'ml-auto message-sent text-white' : 'message-received'} p-3 rounded-lg shadow-md"><div class="flex items-start"><img src="${message.sender_image || 'images/default-avatar.png'}" alt="${message.sender_name}" class="w-8 h-8 rounded-full mr-2 ${message.sender_id == currentUserId ? 'order-2 ml-2' : ''} border-2 border-white border-opacity-50"><div class="${message.sender_id == currentUserId ? 'order-1' : ''}"><p class="text-sm font-medium mb-1 text-gray-800">${message.sender_name}</p><p class="text-sm">${message.message}</p><p class="text-xs mt-1 opacity-75">${formatTime(message.created_at)}</p></div></div></div>`).join('');

        // Scroll to bottom
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function sendMessage(event) {
        event.preventDefault();

        const messageInput = document.getElementById('messageInput');
        const message = messageInput.value.trim();

        if (!message || !currentChatUserId) return;

        fetch('chat.php?action=send_message', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `user_id=${currentChatUserId}&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageInput.value = '';
                // Reload messages to show the new one
                loadMessages(currentChatUserId);
                // Reload conversations to update last message
                loadConversations();
            } else {
                alert('Error sending message: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending message');
        });
    }

    function loadUnreadCount() {
        fetch('chat.php?action=get_unread_count')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateNotificationBadge(data.unread_count);
                    // Check if we received new messages for notifications
                    if (data.unread_count > lastMessageCount && lastMessageCount > 0) {
                        showBrowserNotification('New Message', `You have ${data.unread_count} unread messages`);
                    }
                    lastMessageCount = data.unread_count;
                }
            })
            .catch(error => console.error('Error loading unread count:', error));
    }

    function updateNotificationBadge(count) {
        const badge = document.getElementById('notificationBadge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    function checkForNewMessages() {
        fetch('chat.php?action=get_unread_count')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.unread_count > lastMessageCount && !document.hasFocus()) {
                    // Show browser notification for new messages when tab is not focused
                    showBrowserNotification('New Message', `You have ${data.unread_count} unread messages`);
                    lastMessageCount = data.unread_count;
                }
            })
            .catch(error => console.error('Error checking for new messages:', error));
    }

    function showBrowserNotification(title, body, icon = 'images/S.png') {
        if (Notification.permission === "granted" && !document.hasFocus()) {
            const notification = new Notification(title, {
                body: body,
                icon: icon,
                badge: icon,
                tag: 'sarap-local-message',
                requireInteraction: false,
                silent: false
            });

            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);

            // Handle click to focus on chat
            notification.onclick = function() {
                window.focus();
                notification.close();
            };
        }
    }

    function toggleNotifications() {
        const dropdown = document.getElementById('notificationsDropdown');
        dropdown.classList.toggle('hidden');
    }

    function formatTime(timeString) {
        const date = new Date(timeString);
        const now = new Date();
        const diff = now - date;

        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
        return date.toLocaleDateString();
    }
  </script>
</body>
</html>
