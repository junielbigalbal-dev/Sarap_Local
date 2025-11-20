<?php
/**
 * Input Validation Helper Functions
 * Comprehensive validation for all user inputs
 */

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (basic format)
 */
function isValidPhone($phone) {
    // Remove common separators
    $phone = preg_replace('/[\s\-\(\)\.]+/', '', $phone);
    // Check if it's 7-15 digits
    return preg_match('/^\d{7,15}$/', $phone);
}

/**
 * Validate password strength
 */
function isValidPassword($password) {
    // At least 8 characters, 1 uppercase, 1 lowercase, 1 number
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $password);
}

/**
 * Validate username format
 */
function isValidUsername($username) {
    // 3-20 characters, alphanumeric and underscore only
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username);
}

/**
 * Validate business name
 */
function isValidBusinessName($name) {
    // 2-100 characters, alphanumeric with spaces and common punctuation
    return preg_match('/^[a-zA-Z0-9\s\-\'\&\.]{2,100}$/', $name);
}

/**
 * Validate price
 */
function isValidPrice($price) {
    $price = (float)$price;
    return $price > 0 && $price <= 999999.99;
}

/**
 * Validate product name
 */
function isValidProductName($name) {
    return strlen($name) >= 3 && strlen($name) <= 255;
}

/**
 * Validate product description
 */
function isValidProductDescription($description) {
    return strlen($description) >= 10 && strlen($description) <= 5000;
}

/**
 * Validate address
 */
function isValidAddress($address) {
    return strlen($address) >= 5 && strlen($address) <= 500;
}

/**
 * Validate bio
 */
function isValidBio($bio) {
    return strlen($bio) <= 1000;
}

/**
 * Sanitize generic input (alias for sanitizeString)
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize string input
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize email
 */
function sanitizeEmail($email) {
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

/**
 * Sanitize integer
 */
function sanitizeInt($input) {
    return (int)$input;
}

/**
 * Sanitize float
 */
function sanitizeFloat($input) {
    return (float)$input;
}

/**
 * Validate file upload
 */
function isValidFileUpload($file, $allowed_types = [], $max_size = 5242880) {
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'valid' => false,
            'error' => 'File upload failed'
        ];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return [
            'valid' => false,
            'error' => 'File size exceeds maximum limit'
        ];
    }

    // Check file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return [
            'valid' => false,
            'error' => 'File type not allowed'
        ];
    }

    return ['valid' => true];
}

/**
 * Validate image upload
 */
function isValidImageUpload($file) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5242880; // 5MB
    return isValidFileUpload($file, $allowed_types, $max_size);
}

/**
 * Validate video upload
 */
function isValidVideoUpload($file) {
    $allowed_types = ['video/mp4', 'video/quicktime', 'video/x-msvideo'];
    $max_size = 104857600; // 100MB
    return isValidFileUpload($file, $allowed_types, $max_size);
}

/**
 * Generate safe filename
 */
function generateSafeFilename($original_name) {
    $extension = pathinfo($original_name, PATHINFO_EXTENSION);
    $name = pathinfo($original_name, PATHINFO_FILENAME);
    
    // Remove special characters
    $name = preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
    
    // Generate unique name
    $unique_name = uniqid('', true) . '_' . $name;
    
    return $unique_name . '.' . $extension;
}

/**
 * Validate coordinates
 */
function isValidCoordinates($latitude, $longitude) {
    $lat = (float)$latitude;
    $lng = (float)$longitude;
    
    return $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180;
}

/**
 * Validate rating
 */
function isValidRating($rating) {
    $rating = (int)$rating;
    return $rating >= 1 && $rating <= 5;
}

/**
 * Validate review text
 */
function isValidReviewText($text) {
    return strlen($text) >= 10 && strlen($text) <= 2000;
}
