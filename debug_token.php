<?php
// debug_token.php
// Use this endpoint to verify what headers your mobile app is sending.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

$headers = getallheaders();
$auth_header = isset($headers['Authorization']) ? $headers['Authorization'] : (isset($headers['authorization']) ? $headers['authorization'] : 'NOT FOUND');

$response = [
    'method' => $_SERVER['REQUEST_METHOD'],
    'headers_received' => $headers,
    'authorization_header_check' => $auth_header,
    'php_input' => file_get_contents('php://input'),
    'post_data' => $_POST,
    'cookie_data' => $_COOKIE
];

echo json_encode($response, JSON_PRETTY_PRINT);
