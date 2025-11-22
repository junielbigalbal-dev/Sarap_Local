<?php

require_once 'db.php';
require_once 'includes/validators.php';
require_once 'includes/session-manager.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/includes/cache-control.php';

// Initialize secure session
initializeSecureSession();
if (isAlreadyAuthenticated()) {
    redirectToDashboard();
    exit();
}

$error_message = '';
$success_message = '';
$csrf_token = generateCSRFToken();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check for JSON input
    $content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
    if (strpos($content_type, 'application/json') !== false) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (is_array($input)) {
            $_POST = array_merge($_POST, $input);
        }
    }

    // Validate CSRF token (skip for API/JSON requests if needed, or require it in header)
    // For mobile apps, we might skip CSRF check if it's a JSON request, OR require it in a header.
    // For now, let's make CSRF optional for JSON requests to simplify mobile testing, 
    // BUT strictly validate it for form posts.
    $is_json_request = strpos($content_type, 'application/json') !== false;
    
    if (!$is_json_request && !validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error_message = 'Security validation failed. Please try again.';
    } else {
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error_message = 'Please complete all fields.';
        } else {
            // Attempt authentication
            $auth_result = authenticateUser($conn, $email, $password);

            if ($auth_result['success']) {
                // Return JSON response for API clients
                if ($is_json_request) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'token' => session_id(), // Return session ID as token
                        'user' => [
                            'id' => $_SESSION['user_id'],
                            'role' => $_SESSION['role'],
                            'username' => $_SESSION['username']
                        ],
                        'redirect' => getRedirectAfterLogin()
                    ]);
                    exit();
                }

                // Redirect to appropriate dashboard
                redirectToDashboard();
                exit();
            } else {
                $error_message = $auth_result['message'];
            }
        }
    }
}

// Check for success message from signup
if (isset($_GET['registered'])) {
    $success_message = 'Registration successful! Please log in with your credentials.';
}

// Asset versioning to prevent caching
$asset_version = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarap Local — Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .password-toggle {
            cursor: pointer;
            color: var(--primary-orange);
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-orange-dark);
        }
    </style>
</head>
<body class="min-h-screen bg-cream flex flex-col font-sans">

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 py-10">
        <section class="w-full max-w-6xl grid gap-10 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,1fr)] items-center">
            <!-- Left Marketing Panel -->
            <div class="hidden lg:flex flex-col space-y-8 animate-slideInLeft">
                <h2 class="text-4xl md:text-5xl font-bold font-heading text-gray-900 leading-tight">
                    Delicious local food,
                    <br>
                    <span class="brand-script text-5xl md:text-6xl text-gradient">made with love.</span>
                </h2>
                <p class="text-gray-600 text-lg max-w-md leading-relaxed">
                    Order from trusted neighborhood vendors and discover new favorites in a warm, curated marketplace made for local food lovers.
                </p>
                <ul class="space-y-4 text-base text-gray-700">
                    <li class="flex items-center group">
                        <span class="mr-4 inline-flex items-center justify-center h-10 w-10 rounded-full bg-orange-100 text-orange-600 group-hover:scale-110 transition-transform">
                            <i class="fas fa-bowl-rice"></i>
                        </span>
                        <span class="font-medium">Authentic home-cooked meals from local kitchens.</span>
                    </li>
                    <li class="flex items-center group">
                        <span class="mr-4 inline-flex items-center justify-center h-10 w-10 rounded-full bg-green-100 text-green-600 group-hover:scale-110 transition-transform">
                            <i class="fas fa-leaf"></i>
                        </span>
                        <span class="font-medium">Fresh ingredients with transparent vendor ratings.</span>
                    </li>
                    <li class="flex items-center group">
                        <span class="mr-4 inline-flex items-center justify-center h-10 w-10 rounded-full bg-blue-100 text-blue-600 group-hover:scale-110 transition-transform">
                            <i class="fas fa-truck"></i>
                        </span>
                        <span class="font-medium">Track every order from kitchen to doorstep.</span>
                    </li>
                </ul>
            </div>

            <!-- Login Form -->
            <div class="w-full max-w-md mx-auto animate-popIn">
                <section class="mb-8">
                    <div class="mb-8 text-center lg:text-left">
                        <h2 class="text-3xl font-bold font-heading text-gray-900">Welcome Back!</h2>
                        <p class="text-gray-500 mt-2">Log in to continue your food journey.</p>
                    </div>

                    <div class="content-card p-8 sm:p-10">
                        <?php if (!empty($error_message)): ?>
                            <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-6 flex items-start gap-3 text-sm">
                                <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                                <span><?php echo htmlspecialchars($error_message); ?></span>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_message)): ?>
                            <div class="bg-green-50 border border-green-200 text-green-600 px-4 py-3 rounded-xl mb-6 flex items-start gap-3 text-sm">
                                <i class="fas fa-check-circle flex-shrink-0 mt-0.5"></i>
                                <span><?php echo htmlspecialchars($success_message); ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" novalidate>
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                            <!-- Email Field -->
                            <div class="mb-6">
                                <label for="email" class="block text-sm font-bold text-gray-700 mb-2">
                                    Email Address
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                    </div>
                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        class="form-control pl-10"
                                        placeholder="your@email.com"
                                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                                        required
                                        autocomplete="email"
                                    >
                                </div>
                            </div>

                            <!-- Password Field -->
                            <div class="mb-6">
                                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">
                                    Password
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input
                                        type="password"
                                        id="password"
                                        name="password"
                                        class="form-control pl-10 pr-10"
                                        placeholder="••••••••"
                                        required
                                        autocomplete="current-password"
                                    >
                                    <button
                                        type="button"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors"
                                        onclick="togglePasswordVisibility()"
                                        title="Show/Hide Password"
                                    >
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Remember Me -->
                            <div class="mb-8 flex items-center justify-between">
                                <label class="flex items-center text-gray-600 text-sm cursor-pointer">
                                    <input type="checkbox" name="remember" class="mr-2 w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                                    <span>Remember me</span>
                                </label>
                                <a href="#" class="text-sm font-bold text-orange-600 hover:text-orange-700">Forgot Password?</a>
                            </div>

                            <!-- Login Button -->
                            <button
                                type="submit"
                                class="btn-primary btn-pill w-full shadow-lg shadow-orange-200 flex items-center justify-center gap-2 group"
                            >
                                <span>Log In</span>
                                <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                            </button>
                        </form>
                    </div>

                    <div class="text-center mt-8 text-sm text-gray-600">
                        <span>Don't have an account? </span>
                        <a href="signup.php" class="text-orange-600 hover:text-orange-700 font-bold transition-colors">
                            Create one now
                        </a>
                    </div>
                </section>
            </div>
        </section>
    </main>

    <script>
        // Toggle password visibility
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const icon = event.target.closest('button').querySelector('i');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Enhanced form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            // Validate email
            if (!email) {
                e.preventDefault();
                showValidationError('email', 'Email address is required');
                return false;
            }

            if (!emailRegex.test(email)) {
                e.preventDefault();
                showValidationError('email', 'Please enter a valid email address');
                return false;
            }

            // Validate password
            if (!password) {
                e.preventDefault();
                showValidationError('password', 'Password is required');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                showValidationError('password', 'Password must be at least 6 characters');
                return false;
            }

            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Logging in...';
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');

            return true;
        });

        // Show inline validation error
        function showValidationError(fieldId, message) {
            const field = document.getElementById(fieldId);
            const parent = field.closest('div');
            
            // Remove existing error if any
            const existingError = parent.querySelector('.error-message');
            if (existingError) existingError.remove();
            
            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message text-red-500 text-xs mt-1 font-medium flex items-center gap-1 animate-shake';
            errorDiv.innerHTML = '<i class="fas fa-exclamation-circle"></i>' + message;
            parent.appendChild(errorDiv);
            
            // Add error styling to field
            field.classList.add('border-red-500', 'focus:ring-red-500');
            
            // Remove error on input
            field.addEventListener('input', function() {
                this.classList.remove('border-red-500', 'focus:ring-red-500');
                const err = parent.querySelector('.error-message');
                if (err) err.remove();
            }, { once: true });
        }

        // Auto-focus email field on page load
        document.addEventListener('DOMContentLoaded', function() {
            const emailField = document.getElementById('email');
            if (emailField && !emailField.value) {
                emailField.focus();
            }
        });
    </script>
</body>
</html>
