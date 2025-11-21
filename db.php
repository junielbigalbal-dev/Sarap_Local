<?php
/**
 * Database Connection Handler
 * Secure connection with proper error handling and configuration
 */

// Database configuration
$db_config = [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'user' => getenv('DB_USER') ?: 'root',
    'pass' => getenv('DB_PASSWORD') ?: getenv('DB_PASS') ?: '',
    'db'   => getenv('DB_NAME') ?: 'sarap_local',
    'port' => getenv('DB_PORT') ? (int)getenv('DB_PORT') : 3306,
    'charset' => 'utf8mb4'
];

// Initialize mysqli object
$conn = mysqli_init();

// Set connection timeout BEFORE connecting (critical for preventing hangs)
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 30);

// Establish connection
// Suppress error to handle it manually
$connected = @$conn->real_connect(
    $db_config['host'],
    $db_config['user'],
    $db_config['pass'],
    $db_config['db'],
    $db_config['port']
);

// Check connection
if (!$connected) {
    // Log error for debugging
    error_log('Database Connection Error: ' . $conn->connect_error . ' (' . $conn->connect_errno . ')');
    
    // Show user-friendly error message
    if (php_sapi_name() !== 'cli') {
        http_response_code(503);
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Service Unavailable</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .container { text-align: center; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 500px; }
                h1 { color: #C46A2B; margin: 0 0 10px 0; }
                p { color: #666; line-height: 1.6; }
                .icon { font-size: 48px; margin-bottom: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">🔧</div>
                <h1>Service Temporarily Unavailable</h1>
                <p>We are experiencing technical difficulties. Please try again in a few moments.</p>
            </div>
        </body>
        </html>
        ');
    }
    exit();
}

// Set charset to utf8mb4 for proper emoji and special character support
$conn->set_charset($db_config['charset']);

// Enable error reporting for development
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Set connection timeout

