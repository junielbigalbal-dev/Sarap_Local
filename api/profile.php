<?php
/**
 * Profile Management API
 * Handles user profile updates and retrieval
 */

session_start();
require_once '../db.php';
require_once '../includes/api-response.php';
require_once '../includes/validators.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendUnauthorized('Please log in');
}

$user_id = $_SESSION['user_id'];
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

try {
    switch ($action) {
        case 'get':
            // Get user profile
            $query = "SELECT id, username, email, role, business_name, profile_image, business_logo, 
                             bio, phone, address, latitude, longitude, created_at
                      FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$user) {
                sendNotFound('User not found');
            }

            sendSuccess([
                'id' => (int)$user['id'],
                'username' => htmlspecialchars($user['username']),
                'email' => htmlspecialchars($user['email']),
                'role' => htmlspecialchars($user['role']),
                'business_name' => htmlspecialchars($user['business_name'] ?? ''),
                'profile_image' => htmlspecialchars($user['profile_image'] ?? ''),
                'business_logo' => htmlspecialchars($user['business_logo'] ?? ''),
                'bio' => htmlspecialchars($user['bio'] ?? ''),
                'phone' => htmlspecialchars($user['phone'] ?? ''),
                'address' => htmlspecialchars($user['address'] ?? ''),
                'latitude' => (float)$user['latitude'],
                'longitude' => (float)$user['longitude'],
                'created_at' => $user['created_at']
            ], 'Profile retrieved');
            break;

        case 'update':
            // Update user profile
            $data = getJsonBody();

            // Validate inputs
            $errors = [];

            if (isset($data['phone']) && !empty($data['phone'])) {
                if (!isValidPhone($data['phone'])) {
                    $errors['phone'] = 'Invalid phone number';
                }
            }

            if (isset($data['bio']) && !empty($data['bio'])) {
                if (!isValidBio($data['bio'])) {
                    $errors['bio'] = 'Bio is too long (max 1000 characters)';
                }
            }

            if (isset($data['address']) && !empty($data['address'])) {
                if (!isValidAddress($data['address'])) {
                    $errors['address'] = 'Invalid address';
                }
            }

            if (isset($data['business_name']) && !empty($data['business_name'])) {
                if (!isValidBusinessName($data['business_name'])) {
                    $errors['business_name'] = 'Invalid business name';
                }
            }

            if (!empty($errors)) {
                sendValidationError($errors);
            }

            // Build update query
            $updates = [];
            $params = [];
            $types = '';

            if (isset($data['phone'])) {
                $updates[] = 'phone = ?';
                $params[] = $data['phone'];
                $types .= 's';
            }

            if (isset($data['bio'])) {
                $updates[] = 'bio = ?';
                $params[] = $data['bio'];
                $types .= 's';
            }

            if (isset($data['address'])) {
                $updates[] = 'address = ?';
                $params[] = $data['address'];
                $types .= 's';
            }

            if (isset($data['business_name'])) {
                $updates[] = 'business_name = ?';
                $params[] = $data['business_name'];
                $types .= 's';
            }

            if (isset($data['latitude']) && isset($data['longitude'])) {
                if (isValidCoordinates($data['latitude'], $data['longitude'])) {
                    $updates[] = 'latitude = ?, longitude = ?';
                    $params[] = (float)$data['latitude'];
                    $params[] = (float)$data['longitude'];
                    $types .= 'dd';
                }
            }

            if (empty($updates)) {
                sendError('No fields to update', 400);
            }

            $updates[] = 'updated_at = NOW()';
            $params[] = $user_id;
            $types .= 'i';

            $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $stmt->close();
                sendSuccess([], 'Profile updated successfully');
            } else {
                $stmt->close();
                sendServerError('Failed to update profile');
            }
            break;

        case 'upload_image':
            // Upload profile image
            if (!isset($_FILES['image'])) {
                sendValidationError(['image' => 'No image provided']);
            }

            $validation = isValidImageUpload($_FILES['image']);
            if (!$validation['valid']) {
                sendValidationError(['image' => $validation['error']]);
            }

            // Create upload directory
            $upload_dir = '../uploads/profiles/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate safe filename
            $filename = generateSafeFilename($_FILES['image']['name']);
            $target_path = $upload_dir . $filename;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
                sendServerError('Failed to upload image');
            }

            // Delete old image if exists
            $query = "SELECT profile_image FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $old_image = $stmt->get_result()->fetch_assoc()['profile_image'];
            $stmt->close();

            if ($old_image && file_exists($old_image)) {
                @unlink($old_image);
            }

            // Update database
            $update_query = "UPDATE users SET profile_image = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $target_path, $user_id);

            if ($stmt->execute()) {
                $stmt->close();
                sendSuccess(['image_url' => htmlspecialchars($target_path)], 'Image uploaded successfully', 201);
            } else {
                $stmt->close();
                sendServerError('Failed to save image');
            }
            break;

        case 'upload_logo':
            // Upload business logo (vendor only)
            $query = "SELECT role FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($user['role'] !== 'vendor') {
                sendForbidden('Only vendors can upload logos');
            }

            if (!isset($_FILES['logo'])) {
                sendValidationError(['logo' => 'No logo provided']);
            }

            $validation = isValidImageUpload($_FILES['logo']);
            if (!$validation['valid']) {
                sendValidationError(['logo' => $validation['error']]);
            }

            // Create upload directory
            $upload_dir = '../uploads/logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Generate safe filename
            $filename = generateSafeFilename($_FILES['logo']['name']);
            $target_path = $upload_dir . $filename;

            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target_path)) {
                sendServerError('Failed to upload logo');
            }

            // Delete old logo if exists
            $query = "SELECT business_logo FROM users WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $old_logo = $stmt->get_result()->fetch_assoc()['business_logo'];
            $stmt->close();

            if ($old_logo && file_exists($old_logo)) {
                @unlink($old_logo);
            }

            // Update database
            $update_query = "UPDATE users SET business_logo = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param('si', $target_path, $user_id);

            if ($stmt->execute()) {
                $stmt->close();
                sendSuccess(['logo_url' => htmlspecialchars($target_path)], 'Logo uploaded successfully', 201);
            } else {
                $stmt->close();
                sendServerError('Failed to save logo');
            }
            break;

        default:
            sendError('Invalid action', 400);
    }

} catch (Exception $e) {
    error_log('Profile API Error: ' . $e->getMessage());
    sendServerError('Profile operation failed');
}
