<?php
/**
 * Global Error Handler and Logging System
 * Handles all errors, warnings, and exceptions
 */

// Create logs directory if it doesn't exist
$logs_dir = __DIR__ . '/../logs';
if (!file_exists($logs_dir)) {
    @mkdir($logs_dir, 0755, true);
}

/**
 * Log message to file
 */
function logMessage($message, $level = 'INFO', $file = null) {
    $logs_dir = __DIR__ . '/../logs';
    
    if (!file_exists($logs_dir)) {
        @mkdir($logs_dir, 0755, true);
    }
    
    $log_file = $logs_dir . '/app_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] [$level] $message" . PHP_EOL;
    
    @file_put_contents($log_file, $log_entry, FILE_APPEND);
}

/**
 * Custom error handler
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE_ERROR',
        E_CORE_WARNING => 'CORE_WARNING',
        E_COMPILE_ERROR => 'COMPILE_ERROR',
        E_COMPILE_WARNING => 'COMPILE_WARNING',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE_ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER_DEPRECATED',
    ];
    
    $error_type = $error_types[$errno] ?? 'UNKNOWN';
    $message = "$error_type: $errstr in $errfile on line $errline";
    
    logMessage($message, $error_type);
    
    // Don't execute PHP internal error handler
    return true;
}

/**
 * Custom exception handler
 */
function customExceptionHandler($exception) {
    $message = "Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . 
               " on line " . $exception->getLine();
    
    logMessage($message, 'EXCEPTION');
    
    // Show user-friendly error page
    if (php_sapi_name() !== 'cli') {
        http_response_code(500);
        die('
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error</title>
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #f8f9fa; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; }
                .container { text-align: center; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); max-width: 500px; }
                h1 { color: #ef4444; margin: 0 0 10px 0; }
                p { color: #666; line-height: 1.6; }
                .icon { font-size: 48px; margin-bottom: 20px; }
                a { color: #C46A2B; text-decoration: none; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="icon">⚠️</div>
                <h1>Something went wrong</h1>
                <p>An unexpected error occurred. Please try again later or <a href="index.php">go home</a>.</p>
            </div>
        </body>
        </html>
        ');
    }
}

/**
 * Custom shutdown handler
 */
function customShutdownHandler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        customErrorHandler($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

// Register error handlers
set_error_handler('customErrorHandler');
set_exception_handler('customExceptionHandler');
register_shutdown_function('customShutdownHandler');

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
