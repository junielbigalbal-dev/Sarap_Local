<?php
/**
 * Database Connection Handler
 * Secure connection with proper error handling and configuration
 */

// Load environment variables from .env file if it exists
$env_vars = [];
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        $env_vars[$name] = $value;
        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

// Helper to get env var with priority: .env file -> getenv() -> default
function get_config($key, $default = null) {
    global $env_vars;
    if (isset($env_vars[$key])) return $env_vars[$key];
    $val = getenv($key);
    return $val !== false ? $val : $default;
}

// Database configuration
$db_config = [
    'host' => get_config('DB_HOST', 'localhost'),
    'user' => get_config('DB_USER', 'root'),
    'pass' => get_config('DB_PASSWORD', get_config('DB_PASS', '')),
    'db'   => get_config('DB_NAME', 'sarap_local'),
    'port' => (int)get_config('DB_PORT', 3306),
    'charset' => 'utf8mb4'
];


// Initialize mysqli object
$conn = mysqli_init();

// Set connection timeout BEFORE connecting (critical for preventing hangs)
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
$conn->options(MYSQLI_OPT_READ_TIMEOUT, 5); // Fail if query takes longer than 5s

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

