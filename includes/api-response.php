<?php
/**
 * API Response Helper
 * Standardized JSON responses for all API endpoints
 */

header('Content-Type: application/json; charset=utf-8');

/**
 * Send success response
 */
function sendSuccess($data = [], $message = 'Success', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Send error response
 */
function sendError($message = 'Error', $code = 400, $errors = []) {
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Send validation error response
 */
function sendValidationError($errors = []) {
    sendError('Validation failed', 422, $errors);
}

/**
 * Send unauthorized response
 */
function sendUnauthorized($message = 'Unauthorized') {
    sendError($message, 401);
}

/**
 * Send forbidden response
 */
function sendForbidden($message = 'Forbidden') {
    sendError($message, 403);
}

/**
 * Send not found response
 */
function sendNotFound($message = 'Not found') {
    sendError($message, 404);
}

/**
 * Send server error response
 */
function sendServerError($message = 'Internal server error') {
    sendError($message, 500);
}

/**
 * Send paginated response
 */
function sendPaginated($data = [], $total = 0, $page = 1, $per_page = 20, $message = 'Success') {
    $total_pages = ceil($total / $per_page);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => $message,
        'data' => $data,
        'pagination' => [
            'total' => (int)$total,
            'page' => (int)$page,
            'per_page' => (int)$per_page,
            'total_pages' => (int)$total_pages,
            'has_more' => $page < $total_pages
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

/**
 * Check if request is AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Check request method
 */
function isRequestMethod($method) {
    return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
}

/**
 * Get JSON request body
 */
function getJsonBody() {
    $input = file_get_contents('php://input');
    return json_decode($input, true);
}
