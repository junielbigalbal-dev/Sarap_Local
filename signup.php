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
            border-radius: 1rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .role-card:hover {
            border-color: var(--primary-orange);
            background-color: #fff7ed;
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(255, 107, 53, 0.1);
        }

        .role-card.selected {
            border-color: var(--primary-orange);
            background-color: #fff7ed;
            box-shadow: 0 4px 6px -1px rgba(255, 107, 53, 0.1);
        }

        .role-card.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--primary-orange);
            font-size: 1.25rem;
        }

        .requirement {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.75rem;
            color: #6b7280;
            margin: 0.25rem 0;
            transition: color 0.2s ease;
        }

        .requirement.met {
            color: #10b981;
            font-weight: 600;
        }

        .requirement i {
            width: 16px;
            text-align: center;
        }
    </style>
</head>
<body class="min-h-screen bg-cream flex flex-col font-sans">
    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-md shadow-sm sticky top-0 z-40 border-b border-orange-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <a href="index.php" class="flex items-center group">
                    <div class="w-10 h-10 mr-3 rounded-full bg-orange-50 flex items-center justify-center shadow-sm group-hover:scale-110 transition-transform">
                        <i class="fas fa-utensils text-orange-500"></i>
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs uppercase tracking-[0.2em] text-orange-400 font-bold">Join us</span>
                        <span class="text-2xl font-bold brand-script text-gray-900">Sarap Local</span>
                    </div>
                </a>
                <a href="login.php" class="hidden sm:inline-flex items-center text-sm font-bold text-gray-600 hover:text-orange-600 transition-colors">
                    <span class="mr-3">Already have an account?</span>
                    <span class="btn-primary btn-pill px-6 py-2 text-xs shadow-lg shadow-orange-100">Log In</span>
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 flex items-center justify-center px-4 py-12">
        <section class="w-full max-w-2xl animate-popIn">
            <div class="mb-10 text-center">
                <h2 class="text-4xl font-bold font-heading text-gray-900 mb-3">Create Your Account</h2>
                <p class="text-gray-500 text-lg">Join Sarap Local and start your food journey today.</p>
            </div>

            <div class="content-card p-8 sm:p-10">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl mb-8">
                        <div class="flex items-start gap-3">
                            <i class="fas fa-exclamation-circle flex-shrink-0 mt-0.5"></i>
                            <div class="text-sm">
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
                    <div class="mb-10">
                        <label class="block text-sm font-bold text-gray-700 mb-4">
                            I want to...
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Customer Role -->
                            <label class="role-card <?php echo $selected_role === 'customer' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="customer" <?php echo $selected_role === 'customer' ? 'checked' : ''; ?> required class="hidden" onchange="updateRoleSelection()">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center flex-shrink-0 text-blue-500">
                                        <i class="fas fa-shopping-bag text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900">Order Food</h3>
                                        <p class="text-xs text-gray-500 mt-1">Browse and order from vendors</p>
                                    </div>
                                </div>
                            </label>

                            <!-- Vendor Role -->
                            <label class="role-card <?php echo $selected_role === 'vendor' ? 'selected' : ''; ?>">
                                <input type="radio" name="role" value="vendor" <?php echo $selected_role === 'vendor' ? 'checked' : ''; ?> required class="hidden" onchange="updateRoleSelection()">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center flex-shrink-0 text-green-500">
                                        <i class="fas fa-store text-xl"></i>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900">Sell Food</h3>
                                        <p class="text-xs text-gray-500 mt-1">Become a vendor and sell your food</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Username Field -->
                    <div class="mb-6">
                        <label for="username" class="block text-sm font-bold text-gray-700 mb-2">
                            Username
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="form-control pl-10"
                                placeholder="Choose a unique username"
                                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                pattern="[a-zA-Z0-9_]{3,20}"
                                title="3-20 characters, alphanumeric and underscore only"
                                required
                            >
                        </div>
                        <p class="text-xs text-gray-400 mt-2 ml-1">3-20 characters, letters, numbers, and underscore only</p>
                    </div>

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
                                onchange="checkPasswordStrength()"
                                oninput="checkPasswordStrength()"
                            >
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors"
                                onclick="togglePasswordVisibility('password')"
                                title="Show/Hide Password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength bg-gray-100 overflow-hidden" id="passwordStrength"></div>
                        <div class="mt-3 grid grid-cols-2 gap-2">
                            <div class="requirement" id="req-length">
                                <i class="fas fa-circle text-[6px]"></i>
                                <span>8+ characters</span>
                            </div>
                            <div class="requirement" id="req-upper">
                                <i class="fas fa-circle text-[6px]"></i>
                                <span>Uppercase letter</span>
                            </div>
                            <div class="requirement" id="req-lower">
                                <i class="fas fa-circle text-[6px]"></i>
                                <span>Lowercase letter</span>
                            </div>
                            <div class="requirement" id="req-number">
                                <i class="fas fa-circle text-[6px]"></i>
                                <span>Number</span>
                            </div>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-8">
                        <label for="confirm_password" class="block text-sm font-bold text-gray-700 mb-2">
                            Confirm Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-check-circle text-gray-400"></i>
                            </div>
                            <input
                                type="password"
                                id="confirm_password"
                                name="confirm_password"
                                class="form-control pl-10 pr-10"
                                placeholder="••••••••"
                                required
                                oninput="checkPasswordMatch()"
                            >
                            <button
                                type="button"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors"
                                onclick="togglePasswordVisibility('confirm_password')"
                                title="Show/Hide Password"
                            >
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <p class="text-xs mt-2 font-medium ml-1" id="passwordMatch"></p>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="mb-8">
                        <label class="flex items-start gap-3 text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="terms" required class="mt-1 w-4 h-4 rounded border-gray-300 text-orange-500 focus:ring-orange-500">
                            <span>
                                I agree to the <a href="#" class="text-orange-600 hover:text-orange-700 font-bold">Terms of Service</a> and <a href="#" class="text-orange-600 hover:text-orange-700 font-bold">Privacy Policy</a>
                            </span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        class="btn-primary btn-pill w-full shadow-lg shadow-orange-200 flex items-center justify-center gap-2 group"
                    >
                        <span>Create Account</span>
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </button>
                </form>
            </div>

            <div class="text-center mt-8 text-sm text-gray-600">
                <span>Already have an account? </span>
                <a href="login.php" class="text-orange-600 hover:text-orange-700 font-bold transition-colors">
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
            const checked = document.querySelector('input[name="role"]:checked');
            if (checked) {
                checked.closest('.role-card').classList.add('selected');
            }
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
            const icon = element.querySelector('i');
            
            if (met) {
                element.classList.add('met');
                icon.classList.remove('fa-circle', 'text-[6px]');
                icon.classList.add('fa-check', 'text-xs');
            } else {
                element.classList.remove('met');
                icon.classList.remove('fa-check', 'text-xs');
                icon.classList.add('fa-circle', 'text-[6px]');
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
                matchElement.className = 'text-xs text-green-600 mt-1 font-bold';
            } else {
                matchElement.textContent = '✗ Passwords do not match';
                matchElement.className = 'text-xs text-red-600 mt-1 font-bold';
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
                // Shake animation for role cards
                const roleCards = document.querySelector('.grid');
                roleCards.classList.add('animate-shake');
                setTimeout(() => roleCards.classList.remove('animate-shake'), 500);
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
