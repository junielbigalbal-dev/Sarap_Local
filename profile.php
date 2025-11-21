<?php
require_once __DIR__ . '/includes/cache-control.php';
require_once 'db.php';
require_once 'includes/session-manager.php';
require_once 'includes/auth.php';

// Initialize secure session
initializeSecureSession();

$asset_version = time();

// Check if user is logged in (both customers and vendors can access)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['customer', 'vendor'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

// Generate CSRF token
$csrf_token = generateCSRFToken();

// Get user info
try {
    $user_query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);

    if ($stmt === false) {
        throw new Exception('User query preparation failed: ' . $conn->error);
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_result = $stmt->get_result();

    if ($user_result === false) {
        throw new Exception('User query execution failed: ' . $stmt->error);
    }

    $user = $user_result->fetch_assoc();

    if ($user === null) {
        throw new Exception('User not found');
    }

    $stmt->close();
} catch (Exception $e) {
    error_log('User Query Error: ' . $e->getMessage());
    // Redirect to login if user not found
    header('Location: login.php');
    exit();
}

// Handle profile update
$update_message = '';
$update_type = '';
$password_message = '';
$password_type = '';

if (isset($_POST['update_profile'])) {
    try {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Security validation failed. Please try again.');
        }
        
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $bio = trim($_POST['bio']);

        $notify_orders = isset($_POST['notify_orders']) ? 1 : 0;
        $notify_promotions = isset($_POST['notify_promotions']) ? 1 : 0;
        $notify_reviews = isset($_POST['notify_reviews']) ? 1 : 0;

        // Handle business name for vendors
        $business_name = '';
        $opening_time = null;
        $closing_time = null;
        $is_open = null;
        $delivery_radius_km = null;
        if ($user_role === 'vendor') {
            $business_name = trim($_POST['business_name']);
            if (empty($business_name)) {
                throw new Exception('Business name is required for vendors');
            }

            $opening_time = isset($_POST['opening_time']) ? trim($_POST['opening_time']) : '';
            $closing_time = isset($_POST['closing_time']) ? trim($_POST['closing_time']) : '';
            $is_open = isset($_POST['is_open']) ? 1 : 0;
            $delivery_radius_km = isset($_POST['delivery_radius']) && $_POST['delivery_radius'] !== ''
                ? (int)$_POST['delivery_radius']
                : null;

            if (($opening_time && !$closing_time) || (!$opening_time && $closing_time)) {
                throw new Exception('Please provide both opening and closing time for your business');
            }

            if ($opening_time && $closing_time) {
                if ($opening_time >= $closing_time) {
                    throw new Exception('Opening time must be earlier than closing time');
                }
            }

            if ($delivery_radius_km !== null && $delivery_radius_km < 0) {
                throw new Exception('Delivery radius must be a positive number');
            }
        }

        // Validate inputs
        if (empty($username) || empty($email)) {
            throw new Exception('Username and email are required');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }

        // Check if email is already taken by another user
        $email_check_query = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($email_check_query);
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $email_result = $stmt->get_result();
        if ($email_result->num_rows > 0) {
            throw new Exception('Email is already taken by another user');
        }
        $stmt->close();

        // Handle profile image upload (for both customers and vendors)
        $profile_image = $user['profile_image']; // Keep existing if no new upload

        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profile_image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB for profile image

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed for profile picture');
            }

            if ($file['size'] > $max_size) {
                throw new Exception('Profile picture file size must be less than 5MB');
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = 'images/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                if (!empty($user['profile_image']) && file_exists($user['profile_image'])) {
                    @unlink($user['profile_image']);
                }
                $profile_image = $upload_path;
            } else {
                throw new Exception('Failed to upload profile picture');
            }
        }

        // Handle business banner upload for vendors
        $business_banner = $user['business_banner'] ?? ''; // Keep existing if no new upload

        if ($user_role === 'vendor' && isset($_FILES['business_banner']) && $_FILES['business_banner']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['business_banner'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 10 * 1024 * 1024; // 10MB for banner

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed for banner');
            }

            if ($file['size'] > $max_size) {
                throw new Exception('Banner file size must be less than 10MB');
            }

            // Create unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'banner_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = 'images/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old banner if exists
                if (!empty($user['business_banner'] ?? '') && file_exists($user['business_banner'])) {
                    unlink($user['business_banner']);
                }
                $business_banner = $upload_path;
            } else {
                throw new Exception('Failed to upload business banner');
            }
        }

        // Handle business logo upload for vendors
        $business_logo = $user['business_logo'] ?? ''; // Keep existing if no new upload

        if ($user_role === 'vendor' && isset($_FILES['business_logo']) && $_FILES['business_logo']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['business_logo'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $max_size = 5 * 1024 * 1024; // 5MB for logo

            if (!in_array($file['type'], $allowed_types)) {
                throw new Exception('Only JPG, PNG, GIF, and WebP images are allowed for logo');
            }

            if ($file['size'] > $max_size) {
                throw new Exception('Logo file size must be less than 5MB');
            }

            // Create unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'logo_' . $user_id . '_' . time() . '.' . $extension;
            $upload_path = 'images/' . $filename;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                // Delete old logo if exists
                if (!empty($user['business_logo'] ?? '') && file_exists($user['business_logo'])) {
                    unlink($user['business_logo']);
                }
                $business_logo = $upload_path;
            } else {
                throw new Exception('Failed to upload business logo');
            }
        }

        // Update user profile
        $update_query = "UPDATE users SET username = ?, email = ?, phone = ?, address = ?, bio = ?, profile_image = ?, notify_orders = ?, notify_promotions = ?, notify_reviews = ?";
        $params = [$username, $email, $phone, $address, $bio, $profile_image, $notify_orders, $notify_promotions, $notify_reviews];
        $types = "ssssssiii";

        // Add business fields for vendors
        if ($user_role === 'vendor') {
            $update_query .= ", business_name = ?, business_banner = ?, business_logo = ?, opening_time = ?, closing_time = ?, is_open = ?, delivery_radius_km = ?";
            $params[] = $business_name;
            $params[] = $business_banner;
            $params[] = $business_logo;
            $params[] = $opening_time !== '' ? $opening_time : null;
            $params[] = $closing_time !== '' ? $closing_time : null;
            $params[] = $is_open;
            $params[] = $delivery_radius_km;
            $types .= "sssssii";
        }

        $update_query .= " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($update_query);

        if ($stmt === false) {
            throw new Exception('Update query preparation failed: ' . $conn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $update_message = 'Profile updated successfully!';
            $update_type = 'success';

            // Update session username
            $_SESSION['username'] = $username;

            // Refresh user data
            $user['username'] = $username;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
            $user['bio'] = $bio;
            $user['profile_image'] = $profile_image;
            $user['notify_orders'] = $notify_orders;
            $user['notify_promotions'] = $notify_promotions;
            $user['notify_reviews'] = $notify_reviews;

            // Update business fields for vendors
            if ($user_role === 'vendor') {
                $user['business_name'] = $business_name;
                $user['business_banner'] = $business_banner;
                $user['business_logo'] = $business_logo;
                $user['opening_time'] = $opening_time !== '' ? $opening_time : null;
                $user['closing_time'] = $closing_time !== '' ? $closing_time : null;
                $user['is_open'] = $is_open;
                $user['delivery_radius_km'] = $delivery_radius_km;
            }
        } else {
            throw new Exception('No changes were made');
        }

        $stmt->close();

    } catch (Exception $e) {
        $update_message = $e->getMessage();
        $update_type = 'error';
    }
}

if (isset($_POST['update_password'])) {
    try {
        // Validate CSRF token
        if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Security validation failed. Please try again.');
        }
        
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty(trim($current_password)) || empty(trim($new_password)) || empty(trim($confirm_password))) {
            throw new Exception('Please fill in all password fields');
        }

        if (!password_verify($current_password, $user['password'])) {
            throw new Exception('Your current password is incorrect');
        }

        if ($new_password !== $confirm_password) {
            throw new Exception('New password and confirmation do not match');
        }

        if (strlen($new_password) < 8 || !preg_match('/[A-Za-z]/', $new_password) || !preg_match('/\\d/', $new_password)) {
            throw new Exception('Password must be at least 8 characters and include both letters and numbers');
        }

        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $password_query = "UPDATE users SET password = ? WHERE id = ?";
        $stmt = $conn->prepare($password_query);

        if ($stmt === false) {
            throw new Exception('Password update query preparation failed: ' . $conn->error);
        }

        $stmt->bind_param("si", $hashed_password, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $password_message = 'Password updated successfully!';
            $password_type = 'success';

            $user['password'] = $hashed_password;
        } else {
            throw new Exception('No changes were made to your password');
        }

        $stmt->close();
    } catch (Exception $e) {
        $password_message = $e->getMessage();
        $password_type = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile Settings — Sarap Local</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Global brand styles -->
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --border-color: #e2e8f0;
            --shadow-color: rgba(0, 0, 0, 0.1);
            --orange-primary: #f97316;
            --orange-hover: #ea580c;
            --orange-light: #fed7aa;
        }

        [data-theme="dark"] {
            --bg-primary: #0f172a;
            --bg-secondary: #1e293b;
            --bg-tertiary: #334155;
            --text-primary: #f1f5f9;
            --text-secondary: #cbd5e1;
            --text-muted: #94a3b8;
            --border-color: #334155;
            --shadow-color: rgba(0, 0, 0, 0.3);
            --orange-primary: #fb923c;
            --orange-hover: #f97316;
            --orange-light: #fed7aa;
        }

        * {
            transition: background-color 0.3s ease, color 0.3s ease, border-color 0.3s ease;
        }

        body {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--orange-primary);
            box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.1);
        }

        .business-banner {
            height: 200px;
            background: linear-gradient(135deg, var(--orange-primary), var(--orange-hover));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .business-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .business-banner-upload {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .business-banner-upload:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: scale(1.1);
        }

        .business-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid var(--border-color);
            background: var(--bg-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .business-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .business-logo-upload {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: var(--orange-primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .business-logo-upload:hover {
            background: var(--orange-hover);
            transform: scale(1.1);
        }

        .modern-card {
            background: var(--bg-primary);
            border-radius: 12px;
            box-shadow: 0 1px 3px var(--shadow-color);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        /* Brand header to match logo colors */
        .brand-header {
            background: linear-gradient(135deg, var(--orange-primary), var(--orange-hover));
            color: #fff;
            border-bottom: none;
        }
        .brand-header a,
        .brand-header h1,
        .brand-header i {
            color: #fff;
        }
        .brand-header a:hover {
            color: var(--orange-light);
        }

        .modern-card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-card-body {
            padding: 1.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--orange-primary), var(--orange-hover));
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: var(--bg-tertiary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            background: var(--border-color);
        }

        /* Mobile responsive improvements */
        @media (max-width: 768px) {
            .business-banner {
                height: 150px;
            }

            .business-logo {
                width: 60px;
                height: 60px;
            }

            .grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        .tab-nav {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .tab-nav-button {
            position: relative;
            padding-bottom: 0.75rem;
            font-weight: 500;
            font-size: 0.9rem;
            color: var(--text-secondary);
            cursor: pointer;
            border: none;
            background: transparent;
            outline: none;
        }

        .tab-nav-button::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(135deg, var(--orange-primary), var(--orange-hover));
            transition: width 0.2s ease;
        }

        .tab-nav-button[aria-selected="true"] {
            color: var(--orange-primary);
        }

        .tab-nav-button[aria-selected="true"]::after {
            width: 100%;
        }

        .field-error {
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    <header class="brand-header shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="<?php echo $user_role === 'customer' ? 'customer.php' : 'vendor.php'; ?>" class="mr-4 text-gray-600 hover:text-gray-800">
                        <i class="fas fa-arrow-left text-lg"></i>
                    </a>
                    <img src="images/S.png" alt="Sarap Local" class="w-8 h-8 mr-3">
                    <h1 class="text-xl font-bold"><?php echo ucfirst($user_role); ?> Profile Settings</h1>
                </div>
            </div>
        </div>
    </header>
    <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 modern-card">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Account Settings</h2>
                    <p class="mt-1 text-sm text-gray-500">Manage your personal information and security details.</p>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center text-sm text-gray-500">
                    <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                        <i class="fas fa-user-shield mr-1 text-xs"></i>
                        <?php echo ucfirst($user_role); ?>
                    </span>
                </div>
            </div>

            <nav class="tab-nav mb-6" role="tablist" aria-label="Profile navigation">
                <button type="button"
                        class="tab-nav-button"
                        role="tab"
                        aria-selected="true"
                        data-tab="profile">
                    Personal Info
                </button>
                <button type="button"
                        class="tab-nav-button"
                        role="tab"
                        aria-selected="false"
                        data-tab="security">
                    Security &amp; Password
                </button>
            </nav>

            <div id="tab-profile" data-tab-panel="profile" role="tabpanel">
                <?php if (!empty($update_message)): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $update_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                        <div class="flex items-center">
                            <i class="fas <?php echo $update_type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-2"></i>
                            <span class="text-sm <?php echo $update_type === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                                <?php echo htmlspecialchars($update_message); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" class="space-y-6" id="profileForm" novalidate>
                    <!-- Profile Picture Section -->
                    <div class="flex items-center space-x-6">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars(!empty($user['profile_image']) ? $user['profile_image'] : 'images/S.png'); ?>"
                                 alt="Profile Picture"
                                 id="currentProfileImage"
                                 class="w-24 h-24 rounded-full object-cover border-4 border-gray-200"
                                 onerror="this.onerror=null; this.src='images/S.png';">
                            <label for="profile_image" class="absolute bottom-0 right-0 bg-orange-500 hover:bg-orange-600 text-white rounded-full p-2 cursor-pointer profile-image-upload">
                                <i class="fas fa-camera text-sm"></i>
                            </label>
                            <input type="file"
                                   id="profile_image"
                                   name="profile_image"
                                   accept="image/*"
                                   class="hidden"
                                   onchange="previewImage(this)">
                        </div>
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-1">Profile Picture</h3>
                            <p class="text-sm text-gray-600">Click the camera icon to upload a new photo</p>
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF up to 5MB</p>
                        </div>
                    </div>

                    <!-- Business Banner and Logo (for vendors only) -->
                    <?php if ($user_role === 'vendor'): ?>
                        <div class="mb-8">
                            <h3 class="text-lg font-semibold mb-4" style="color: var(--text-primary);">Business Branding</h3>

                            <!-- Business Banner -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium mb-2" style="color: var(--text-primary);">Business Banner</label>
                                <div class="business-banner">
                                    <?php if (!empty($user['business_banner'] ?? '')): ?>
                                        <img src="<?php echo htmlspecialchars($user['business_banner'] ?? ''); ?>" alt="Business Banner" id="currentBusinessBanner">
                                    <?php else: ?>
                                        <div>
                                            <i class="fas fa-store text-4xl mb-2 opacity-50"></i>
                                            <p class="text-sm opacity-75">No banner uploaded</p>
                                        </div>
                                    <?php endif; ?>
                                    <label for="business_banner" class="business-banner-upload" title="Upload Banner">
                                        <i class="fas fa-camera text-lg"></i>
                                    </label>
                                    <input type="file"
                                           id="business_banner"
                                           name="business_banner"
                                           accept="image/*"
                                           class="hidden"
                                           onchange="previewBusinessBanner(this)">
                                </div>
                                <p class="text-xs mt-2" style="color: var(--text-secondary);">Upload a banner image for your business (recommended size: 1200x300px)</p>
                            </div>

                            <!-- Business Logo -->
                            <div class="mb-6">
                                <label class="block text-sm font-medium mb-2" style="color: var(--text-primary);">Business Logo</label>
                                <div class="relative inline-block">
                                    <div class="business-logo">
                                        <?php if (!empty($user['business_logo'] ?? '')): ?>
                                            <img src="<?php echo htmlspecialchars($user['business_logo'] ?? ''); ?>" alt="Business Logo" id="currentBusinessLogo">
                                        <?php else: ?>
                                            <i class="fas fa-store text-2xl" style="color: var(--text-muted);"></i>
                                        <?php endif; ?>
                                    </div>
                                    <label for="business_logo" class="business-logo-upload" title="Upload Logo">
                                        <i class="fas fa-camera text-sm"></i>
                                    </label>
                                    <input type="file"
                                           id="business_logo"
                                           name="business_logo"
                                           accept="image/*"
                                           class="hidden"
                                           onchange="previewBusinessLogo(this)">
                                </div>
                                <p class="text-xs mt-2" style="color: var(--text-secondary);">Upload a logo for your business (square image recommended)</p>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Profile Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <?php if ($user_role === 'vendor'): ?>
                            <!-- Business Name (for vendors) -->
                            <div class="md:col-span-2">
                                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Business Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       id="business_name"
                                       name="business_name"
                                       value="<?php echo htmlspecialchars($user['business_name'] ?? ''); ?>"
                                       class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                       placeholder="Enter your business name"
                                       required>
                                <p id="business_name_error" class="mt-1 text-red-600 field-error hidden"></p>
                            </div>

                            <!-- Business Settings (Hours, Status, Delivery Radius) -->
                            <div>
                                <label for="opening_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Opening Time
                                </label>
                                <input type="time"
                                       id="opening_time"
                                       name="opening_time"
                                       value="<?php echo htmlspecialchars($user['opening_time'] ?? ''); ?>"
                                       class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <p id="opening_time_error" class="mt-1 text-red-600 field-error hidden"></p>
                            </div>

                            <div>
                                <label for="closing_time" class="block text-sm font-medium text-gray-700 mb-2">
                                    Closing Time
                                </label>
                                <input type="time"
                                       id="closing_time"
                                       name="closing_time"
                                       value="<?php echo htmlspecialchars($user['closing_time'] ?? ''); ?>"
                                       class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <p id="closing_time_error" class="mt-1 text-red-600 field-error hidden"></p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Store Status
                                </label>
                                <div class="flex items-center space-x-3">
                                    <label class="inline-flex items-center cursor-pointer">
                                        <input type="checkbox"
                                               id="is_open"
                                               name="is_open"
                                               class="form-checkbox h-4 w-4 text-orange-500 border-gray-300 rounded"
                                               <?php echo !isset($user['is_open']) || (int)$user['is_open'] === 1 ? 'checked' : ''; ?>>
                                        <span class="ml-2 text-sm text-gray-700">Store is open</span>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label for="delivery_radius" class="block text-sm font-medium text-gray-700 mb-2">
                                    Delivery Radius (km)
                                </label>
                                <input type="number"
                                       id="delivery_radius"
                                       name="delivery_radius"
                                       min="0"
                                       max="50"
                                       step="1"
                                       value="<?php echo htmlspecialchars($user['delivery_radius_km'] ?? ''); ?>"
                                       class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                       placeholder="e.g. 5">
                                <p id="delivery_radius_error" class="mt-1 text-red-600 field-error hidden"></p>
                            </div>
                        <?php endif; ?>

                        <!-- Username -->
                        <div>
                            <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                                Username <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="username"
                                   name="username"
                                   placeholder="Enter your username"
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                   required>
                            <p id="username_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   placeholder="your.email@example.com"
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                   required>
                            <p id="email_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                Phone Number
                            </label>
                            <input type="tel"
                                   id="phone"
                                   name="phone"
                                   placeholder="+63 912 345 6789"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                            <p id="phone_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>

                        <!-- Address -->
                        <div class="md:col-span-2">
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                <?php echo $user_role === 'vendor' ? 'Business Address' : 'Delivery Address'; ?>
                            </label>
                            <textarea id="address"
                                      name="address"
                                      rows="3"
                                      placeholder="<?php echo $user_role === 'vendor' ? 'e.g., 123 Business St, Biliran' : 'e.g., 456 Home St, Biliran'; ?>"
                                      class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>

                        <!-- Bio -->
                        <div class="md:col-span-2">
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-2">
                                Bio / Description
                            </label>
                            <textarea id="bio"
                                      name="bio"
                                      rows="4"
                                      placeholder="<?php echo $user_role === 'vendor' ? 'Tell customers about your business, specialties, and what makes you unique...' : 'Tell us a bit about yourself, your food preferences, and dietary restrictions...'; ?>"
                                      class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>

                        <!-- Notification Preferences -->
                        <div class="md:col-span-2 mt-4 border-t border-gray-200 pt-4">
                            <h3 class="text-sm font-semibold text-gray-800 mb-3">Notification Preferences</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                <label for="notify_orders" class="inline-flex items-center">
                                    <input type="checkbox"
                                           id="notify_orders"
                                           name="notify_orders"
                                           class="form-checkbox h-4 w-4 text-orange-500 border-gray-300 rounded"
                                           <?php echo !isset($user['notify_orders']) || (int)$user['notify_orders'] === 1 ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-700">Order updates</span>
                                </label>

                                <label for="notify_promotions" class="inline-flex items-center">
                                    <input type="checkbox"
                                           id="notify_promotions"
                                           name="notify_promotions"
                                           class="form-checkbox h-4 w-4 text-orange-500 border-gray-300 rounded"
                                           <?php echo !isset($user['notify_promotions']) || (int)$user['notify_promotions'] === 1 ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-700">Promotions &amp; deals</span>
                                </label>

                                <label for="notify_reviews" class="inline-flex items-center">
                                    <input type="checkbox"
                                           id="notify_reviews"
                                           name="notify_reviews"
                                           class="form-checkbox h-4 w-4 text-orange-500 border-gray-300 rounded"
                                           <?php echo !isset($user['notify_reviews']) || (int)$user['notify_reviews'] === 1 ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-sm text-gray-700">Reviews &amp; feedback</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center pt-6 border-t border-gray-200">
                        <a href="logout.php" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors">
                            <i class="fas fa-sign-out-alt mr-2"></i>
                            Logout
                        </a>
                        <div class="flex space-x-4">
                            <a href="<?php echo $user_role === 'customer' ? 'customer.php' : 'vendor.php'; ?>" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </a>
                            <button type="submit"
                                    name="update_profile"
                                    class="px-6 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg font-medium transition-colors">
                                <i class="fas fa-save mr-2"></i>
                                Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div id="tab-security" data-tab-panel="security" role="tabpanel" class="hidden">
                <?php if (!empty($password_message)): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $password_type === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                        <div class="flex items-center">
                            <i class="fas <?php echo $password_type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'; ?> mr-2"></i>
                            <span class="text-sm <?php echo $password_type === 'success' ? 'text-green-700' : 'text-red-700'; ?>">
                                <?php echo htmlspecialchars($password_message); ?>
                            </span>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6" id="passwordForm" novalidate>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Current Password
                            </label>
                            <input type="password"
                                   id="current_password"
                                   name="current_password"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                   autocomplete="current-password">
                            <p id="current_password_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <input type="password"
                                   id="new_password"
                                   name="new_password"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                   autocomplete="new-password">
                            <p class="mt-1 text-xs text-gray-500">At least 8 characters with letters and numbers.</p>
                            <p id="new_password_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm New Password
                            </label>
                            <input type="password"
                                   id="confirm_password"
                                   name="confirm_password"
                                   class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500"
                                   autocomplete="new-password">
                            <p id="confirm_password_error" class="mt-1 text-red-600 field-error hidden"></p>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 border-t border-gray-200">
                        <button type="button" onclick="closeProfileModal()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-orange-500 hover:bg-orange-600 text-white rounded-lg transition-colors">
                            Update Profile
                        </button>
                    </div>
                </form>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const profileForm = document.querySelector('form[method="POST"]');
                        if (profileForm) {
                            profileForm.addEventListener('submit', function(e) {
                                e.preventDefault();
                                
                                const formData = new FormData(this);
                                
                                fetch('profile.php', {
                                    method: 'POST',
                                    body: formData
                                })
                                .then(response => response.text())
                                .then(html => {
                                    // Show success message
                                    showNotification('Profile updated successfully!', 'success');
                                    
                                    // Update profile picture if uploaded
                                    const profileImageInput = document.getElementById('profile_image');
                                    if (profileImageInput && profileImageInput.files.length > 0) {
                                        const reader = new FileReader();
                                        reader.onload = function(e) {
                                            const profileImages = document.querySelectorAll('img[alt="Profile"]');
                                            profileImages.forEach(img => {
                                                img.src = e.target.result;
                                            });
                                        };
                                        reader.readAsDataURL(profileImageInput.files[0]);
                                    }
                                    
                                    // Update displayed profile info immediately
                                    updateProfileDisplay();
                                    
                                    // Close modal if open
                                    const modal = document.getElementById('profileModal');
                                    if (modal) modal.classList.add('hidden');
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    showNotification('Error updating profile', 'error');
                                });
                            });
                        }
                    });
                    
                    function updateProfileDisplay() {
                        const username = document.getElementById('username')?.value || '';
                        const email = document.getElementById('email')?.value || '';
                        const phone = document.getElementById('phone')?.value || '';
                        const address = document.getElementById('address')?.value || '';
                        const bio = document.getElementById('bio')?.value || '';
                        
                        // Update all profile display elements
                        document.querySelectorAll('[data-profile-username]').forEach(el => {
                            el.textContent = username;
                        });
                        document.querySelectorAll('[data-profile-email]').forEach(el => {
                            el.textContent = email;
                        });
                        document.querySelectorAll('[data-profile-phone]').forEach(el => {
                            el.textContent = phone;
                        });
                        document.querySelectorAll('[data-profile-address]').forEach(el => {
                            el.textContent = address;
                        });
                        document.querySelectorAll('[data-profile-bio]').forEach(el => {
                            el.textContent = bio;
                        });
                    }
                    
                    function showNotification(message, type) {
                        const notification = document.createElement('div');
                        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
                            type === 'success' ? 'bg-green-500' : 'bg-red-500'
                        }`;
                        notification.textContent = message;
                        document.body.appendChild(notification);
                        
                        setTimeout(() => {
                            notification.remove();
                        }, 3000);
                    }
                </script>
            </div>
        </div>
    </main>

    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
                    input.value = '';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('currentProfileImage').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }

        function previewBusinessBanner(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
                    input.value = '';
                    return;
                }

                // Validate file size (10MB for banner)
                if (file.size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const bannerContainer = document.querySelector('.business-banner');
                    if (!bannerContainer) return;

                    let bannerImg = document.getElementById('currentBusinessBanner');
                    if (!bannerImg) {
                        bannerImg = document.createElement('img');
                        bannerImg.id = 'currentBusinessBanner';
                        bannerImg.alt = 'Business Banner';
                        bannerImg.style.width = '100%';
                        bannerImg.style.height = '100%';
                        bannerImg.style.objectFit = 'cover';
                        bannerContainer.insertBefore(bannerImg, bannerContainer.firstChild);
                    }

                    bannerImg.src = e.target.result;
                    bannerImg.style.display = 'block';

                    const placeholderIcon = bannerContainer.querySelector('.fa-store');
                    if (placeholderIcon) {
                        placeholderIcon.style.display = 'none';
                    }
                    const placeholderText = bannerContainer.querySelector('p');
                    if (placeholderText) {
                        placeholderText.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function previewBusinessLogo(input) {
            if (input.files && input.files[0]) {
                const file = input.files[0];

                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    alert('Please select a valid image file (JPG, PNG, GIF, or WebP)');
                    input.value = '';
                    return;
                }

                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    input.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const logoContainer = document.querySelector('.business-logo');
                    if (!logoContainer) return;

                    let logoImg = document.getElementById('currentBusinessLogo');
                    if (!logoImg) {
                        logoImg = document.createElement('img');
                        logoImg.id = 'currentBusinessLogo';
                        logoImg.alt = 'Business Logo';
                        logoImg.style.width = '100%';
                        logoImg.style.height = '100%';
                        logoImg.style.objectFit = 'cover';
                        logoContainer.appendChild(logoImg);
                    }

                    logoImg.src = e.target.result;

                    const placeholderIcon = logoContainer.querySelector('i');
                    if (placeholderIcon) {
                        placeholderIcon.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        }

        function setFieldError(fieldId, message) {
            const input = document.getElementById(fieldId);
            const errorEl = document.getElementById(fieldId + '_error');

            if (!input || !errorEl) {
                return;
            }

            if (message) {
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
                input.classList.add('border-red-500');
            } else {
                errorEl.textContent = '';
                errorEl.classList.add('hidden');
                input.classList.remove('border-red-500');
            }
        }

        // Dark Mode Functionality
        function toggleDarkMode() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update button icon
            const toggleBtn = document.querySelector('[onclick="toggleDarkMode()"]');
            const icon = toggleBtn ? toggleBtn.querySelector('i') : null;
            if (!icon) {
                return;
            }
            if (newTheme === 'dark') {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }

        // Initialize theme from localStorage
        function initTheme() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // Update button icon on load
            const toggleBtn = document.querySelector('[onclick="toggleDarkMode()"]');
            if (toggleBtn) {
                const icon = toggleBtn.querySelector('i');
                if (savedTheme === 'dark') {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                }
            }
        }

        // Form validation
        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        function validateProfileForm() {
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const businessNameInput = document.getElementById('business_name');
            const openingTimeInput = document.getElementById('opening_time');
            const closingTimeInput = document.getElementById('closing_time');
            const deliveryRadiusInput = document.getElementById('delivery_radius');

            let hasError = false;

            setFieldError('username', '');
            setFieldError('email', '');
            setFieldError('phone', '');
            if (businessNameInput) {
                setFieldError('business_name', '');
            }
            if (openingTimeInput) {
                setFieldError('opening_time', '');
            }
            if (closingTimeInput) {
                setFieldError('closing_time', '');
            }
            if (deliveryRadiusInput) {
                setFieldError('delivery_radius', '');
            }

            const username = usernameInput ? usernameInput.value.trim() : '';
            const email = emailInput ? emailInput.value.trim() : '';
            const phone = phoneInput ? phoneInput.value.trim() : '';
            const openingTime = openingTimeInput ? openingTimeInput.value.trim() : '';
            const closingTime = closingTimeInput ? closingTimeInput.value.trim() : '';
            const deliveryRadius = deliveryRadiusInput && deliveryRadiusInput.value !== ''
                ? parseInt(deliveryRadiusInput.value, 10)
                : null;

            if (!username) {
                setFieldError('username', 'Username is required');
                hasError = true;
            }

            if (!email) {
                setFieldError('email', 'Email is required');
                hasError = true;
            } else if (!isValidEmail(email)) {
                setFieldError('email', 'Please enter a valid email address');
                hasError = true;
            }

            if (phone) {
                const phoneRegex = /^(\+?63|0)9\d{9}$/;
                if (!phoneRegex.test(phone)) {
                    setFieldError('phone', 'Enter a valid Philippine mobile number (e.g., +63 9XX XXX XXXX)');
                    hasError = true;
                }
            }

            if (businessNameInput) {
                const businessName = businessNameInput.value.trim();
                if (!businessName) {
                    setFieldError('business_name', 'Business name is required');
                    hasError = true;
                }
            }

            if (openingTimeInput || closingTimeInput) {
                if ((openingTime && !closingTime) || (!openingTime && closingTime)) {
                    if (openingTimeInput) setFieldError('opening_time', '');
                    if (closingTimeInput) setFieldError('closing_time', 'Please provide both opening and closing time');
                    hasError = true;
                } else if (openingTime && closingTime && openingTime >= closingTime) {
                    if (openingTimeInput) setFieldError('opening_time', 'Opening time must be earlier than closing time');
                    if (closingTimeInput) setFieldError('closing_time', '');
                    hasError = true;
                }
            }

            if (deliveryRadiusInput && deliveryRadius !== null) {
                if (isNaN(deliveryRadius) || deliveryRadius < 0 || deliveryRadius > 50) {
                    setFieldError('delivery_radius', 'Delivery radius must be between 0 and 50 km');
                    hasError = true;
                }
            }

            return !hasError;
        }

        function validatePasswordForm() {
            const currentInput = document.getElementById('current_password');
            const newInput = document.getElementById('new_password');
            const confirmInput = document.getElementById('confirm_password');

            let hasError = false;

            setFieldError('current_password', '');
            setFieldError('new_password', '');
            setFieldError('confirm_password', '');

            const currentPassword = currentInput ? currentInput.value.trim() : '';
            const newPassword = newInput ? newInput.value.trim() : '';
            const confirmPassword = confirmInput ? confirmInput.value.trim() : '';

            if (!currentPassword) {
                setFieldError('current_password', 'Current password is required');
                hasError = true;
            }

            if (!newPassword) {
                setFieldError('new_password', 'New password is required');
                hasError = true;
            } else {
                if (newPassword.length < 8) {
                    setFieldError('new_password', 'Password should be at least 8 characters');
                    hasError = true;
                } else if (!/[A-Za-z]/.test(newPassword) || !/\d/.test(newPassword)) {
                    setFieldError('new_password', 'Use a mix of letters and numbers');
                    hasError = true;
                }
            }

            if (!confirmPassword) {
                setFieldError('confirm_password', 'Please confirm your new password');
                hasError = true;
            } else if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                setFieldError('confirm_password', 'Passwords do not match');
                hasError = true;
            }

            return !hasError;
        }

        // Initialize theme when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initTheme();

            const tabButtons = document.querySelectorAll('.tab-nav-button');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');

            tabButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    const target = this.getAttribute('data-tab');

                    tabButtons.forEach(function(btn) {
                        btn.setAttribute('aria-selected', btn === button ? 'true' : 'false');
                    });

                    tabPanels.forEach(function(panel) {
                        if (panel.getAttribute('data-tab-panel') === target) {
                            panel.classList.remove('hidden');
                        } else {
                            panel.classList.add('hidden');
                        }
                    });
                });
            });

            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    if (!validateProfileForm()) {
                        e.preventDefault();
                    }
                });

                ['username', 'email', 'phone', 'business_name'].forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', function() {
                            validateProfileForm();
                        });
                    }
                });
            }

            const passwordForm = document.getElementById('passwordForm');
            if (passwordForm) {
                passwordForm.addEventListener('submit', function(e) {
                    if (!validatePasswordForm()) {
                        e.preventDefault();
                    }
                });

                ['current_password', 'new_password', 'confirm_password'].forEach(function(id) {
                    const input = document.getElementById(id);
                    if (input) {
                        input.addEventListener('input', function() {
                            validatePasswordForm();
                        });
                    }
                });
            }
        });
    </script>
    <script src="js/realtime-updates.js?v=<?php echo time(); ?>"></script>
</body>
</html>
