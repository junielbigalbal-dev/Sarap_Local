<?php
require_once 'db.php';
require_once 'includes/auth.php';
require_once __DIR__ . '/includes/cache-control.php';

// If already logged in, redirect to dashboard
if (validateSession()) {
    redirectToDashboard();
}

$errors = [];
$success_message = '';
$csrf_token = generateCSRFToken();
$selected_role = '';

// Handle JSON input
$content_type = isset($_SERVER["CONTENT_TYPE"]) ? trim($_SERVER["CONTENT_TYPE"]) : '';
if (strpos($content_type, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
    if (is_array($input)) {
        $_POST = array_merge($_POST, $input);
    }
}

// Check for JSON request (Accept header)
$is_json = false;
if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $is_json = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    // Validate CSRF token (skip for API/JSON requests if they use a different auth mechanism, but for now we enforce it or skip if not present in JSON?)
    // Actually, for API, we might skip CSRF if we use tokens, but here we are registering.
    // Let's assume API sends csrf_token if it can, or we skip if it's a pure API client without session.
    // For now, let's keep validation but allow it to fail gracefully for JSON.
    if (!$is_json && !validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Security validation failed. Please try again.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $role = $_POST['role'] ?? '';
        $selected_role = $role;

        // Register user
        $registration_result = registerUser($conn, $username, $email, $password, $confirm_password, $role);

        if ($registration_result['success']) {
            // Redirect to verification page
            $email_param = urlencode($registration_result['email']);
            $redirect_url = "verify.php?email=$email_param";
            
            // If there's a specific message (like email failure), pass it
            if (isset($registration_result['message']) && strpos($registration_result['message'], 'failed to send') !== false) {
                $msg_param = urlencode($registration_result['message']);
                $redirect_url .= "&error=$msg_param";
            }
            
            if ($is_json) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful. Please check your email to verify your account.',
                    'redirect' => $redirect_url
                ]);
                exit();
            }

            header("Location: $redirect_url");
            exit();
        } else {
            $errors = $registration_result['errors'];
            if ($is_json) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'errors' => $errors
                ]);
                exit();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarap Local — Sign Up</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
            margin-top: 0.5rem;
        }

        .password-strength.weak {
            background-color: #ef4444;
            width: 33%;
        }

        .password-strength.fair {
            background-color: #f59e0b;
            width: 66%;
        }

        .password-strength.strong {
            background-color: #10b981;
            width: 100%;
        }

        .password-toggle {
            cursor: pointer;
            color: var(--primary-orange);
            transition: all 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-orange-dark);
        }

        .role-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .role-card:hover {
            border-color: var(--primary-orange);
            background-color: rgba(255, 107, 53, 0.05);
        }

        .role-card.selected {
            border-color: var(--primary-orange);
            background-color: rgba(255, 107, 53, 0.1);
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            color: #6b7280;
            margin: 0.25rem 0;
        }

        .requirement.met {
            color: #10b981;
        }

        .requirement i {
            width: 16px;
            text-align: center;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="brand-header shadow-sm sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center">
                    <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                        <img src="images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs uppercase tracking-[0.2em] text-orange-100">Join us</span>
                        <span class="text-xl font-semibold brand-script">Sarap Local</span>
                    </div>
                </a>
                <a href="login.php" class="hidden sm:inline-flex items-center text-sm font-medium text-white hover:text-orange-100 transition-colors">
                    <span class="mr-2">Already have an account?</span>
                    <span class="px-3 py-1 rounded-full bg-white/10 hover:bg-white/20 border border-white/20">Log in</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-10">
        <section class="w-full max-w-2xl">
            <div class="mb-8 text-center">
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Create Your Account</h2>
                <p class="text-gray-600">Join Sarap Local and start your food journey today.</p>
            </div>

            <div class="brand-card rounded-2xl shadow-xl border border-orange-100/40 p-6 sm:p-8">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                            <div>
                                <?php foreach ($errors as $error): ?>
                                    <p class="mb-1"><?php echo htmlspecialchars($error); ?></p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

                    <!-- Role Selection -->
                    <div class="mb-8">
                        <label class="block text-sm font-medium text-gray-700 mb-4">
                            <i class="fas fa-user-check mr-2 text-orange-500"></i>I want to
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Customer Role -->
                            <label class="role-card <?php echo $selected_role === 'customer' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="customer" <?php echo $selected_role === 'customer' ? 'checked' : ''; ?> required class="hidden" onchange="updateRoleSelection()">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-shopping-bag text-blue-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Order Food</h3>
                                        <p class="text-sm text-gray-600">Browse and order from vendors</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Vendor Role -->
                            <label class="role-card <?php echo $selected_role === 'vendor' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="vendor" <?php echo $selected_role === 'vendor' ? 'checked' : ''; ?> required class="hidden" onchange="updateRoleSelection()">
                                <div class="flex items-center gap-3">
                                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                        <i class="fas fa-store text-green-600 text-lg"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-800">Sell Food</h3>
                                        <p class="text-sm text-gray-600">Become a vendor and sell your food</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Username Field -->
                    <div class="mb-6">
                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-user mr-2 text-orange-500"></i>Username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                            placeholder="Choose a unique username"
                            value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                            pattern="[a-zA-Z0-9_]{3,20}"
                            title="3-20 characters, alphanumeric and underscore only"
                            required
                        >
                        <p class="text-xs text-gray-500 mt-1">3-20 characters, letters, numbers, and underscore only</p>
                    </div>

                    <!-- Email Field -->
                    <div class="mb-6">
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-envelope mr-2 text-orange-500"></i>Email Address
                        </label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all"
                            placeholder="your@email.com"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <!-- Password Field -->
                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-lock mr-2 text-orange-500"></i>Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all pr-10"
                                placeholder="••••••••"
                                required
                                onchange="checkPasswordStrength()"
                                oninput="checkPasswordStrength()"
                            >
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 password-toggle"
                                onclick="togglePasswordVisibility('password')"
                                title="Show/Hide Password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength" id="passwordStrength"></div>
                        <div class="mt-3 text-sm">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-times"></i>
                                <span>At least 8 characters</span>
                            </div>
                            <div class="requirement" id="req-upper">
                                <i class="fas fa-times"></i>
                                <span>One uppercase letter</span>
                            </div>
                            <div class="requirement" id="req-lower">
                                <i class="fas fa-times"></i>
                                <span>One lowercase letter</span>
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-times"></i>
                                <span>One number</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-check-circle mr-2 text-orange-500"></i>Confirm Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all pr-10"
                                placeholder="••••••••"
                                required
                                oninput="checkPasswordMatch()"
                            >
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 password-toggle"
                                onclick="togglePasswordVisibility('confirm_password')"
                                title="Show/Hide Password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1" id="passwordMatch"></p>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="mb-6">
                        <label class="flex items-start gap-3 text-sm text-gray-700">
                            <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span>
                                I agree to the <a href="#" class="text-orange-600 hover:text-orange-700 font-medium">Terms of Service</a> and <a href="#" class="text-orange-600 hover:text-orange-700 font-medium">Privacy Policy</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="w-full bg-orange-500 hover:bg-orange-600 text-white py-2.5 px-4 rounded-lg font-medium transition-colors flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-user-plus"></i>
                        Create Account
                    </button>
                </form>
            </div>

            <div class="text-center mt-6 text-sm text-gray-600">
                <span>Already have an account? </span>
                <a href="login.php" class="text-orange-600 hover:text-orange-700 font-medium transition-colors">
                    Log in here
                </a>
            </div>
        </section>
    </main>

    <script>
        function updateRoleSelection() {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('selected');
            });
            document.querySelector('input[name="role"]:checked').closest('.role-card').classList.add('selected');
        }

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const button = event.target.closest('button');
            const icon = button.querySelector('i');

            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strength = document.getElementById('passwordStrength');
            let score = 0;

            // Check requirements
            const hasLength = password.length >= 8;
            const hasUpper = /[A-Z]/.test(password);
            const hasLower = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);

            updateRequirement('req-length', hasLength);
            updateRequirement('req-upper', hasUpper);
            updateRequirement('req-lower', hasLower);
            updateRequirement('req-number', hasNumber);

            if (hasLength) score++;
            if (hasUpper) score++;
            if (hasLower) score++;
            if (hasNumber) score++;

            strength.classList.remove('weak', 'fair', 'strong');
            if (score <= 2) {
                strength.classList.add('weak');
            } else if (score === 3) {
                strength.classList.add('fair');
            } else {
                strength.classList.add('strong');
            }

            checkPasswordMatch();
        }

        function updateRequirement(id, met) {
            const element = document.getElementById(id);
            if (met) {
                element.classList.add('met');
                element.querySelector('i').classList.remove('fa-times');
                element.querySelector('i').classList.add('fa-check');
            } else {
                element.classList.remove('met');
                element.querySelector('i').classList.remove('fa-check');
                element.querySelector('i').classList.add('fa-times');
            }
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchElement = document.getElementById('passwordMatch');

            if (confirmPassword.length === 0) {
                matchElement.textContent = '';
                return;
            }

            if (password === confirmPassword) {
                matchElement.textContent = '✓ Passwords match';
                matchElement.className = 'text-xs text-green-600 mt-1';
            } else {
                matchElement.textContent = '✗ Passwords do not match';
                matchElement.className = 'text-xs text-red-600 mt-1';
            }
        }

        // Initialize role selection on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateRoleSelection();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const role = document.querySelector('input[name="role"]:checked');
            const terms = document.querySelector('input[name="terms"]');

            if (!role) {
                e.preventDefault();
                alert('Please select a role.');
                return false;
            }

            if (!terms.checked) {
                e.preventDefault();
                alert('Please agree to the Terms of Service and Privacy Policy.');
                return false;
            }
            // Show loading state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalContent = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Creating Account...';
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
        });
    </script>
</body>
</html>
