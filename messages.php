<?php
// Unified messages entrypoint: always use chat.php
// This keeps old links to messages.php working but forwards to the new chat system.

session_start();

// Preserve any query parameters when redirecting
$query = $_SERVER['QUERY_STRING'] ?? '';
$location = 'chat.php' . ($query ? ('?' . $query) : '');

header('Location: ' . $location);
exit;
