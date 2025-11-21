<?php
require_once 'includes/auth.php';

$email = $_GET['email'] ?? '';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $code = $_POST['code'] ?? '';
    
    if (isset($_POST['resend'])) {
        $result = resendVerificationCode($conn, $email);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } else {
        $result = verifyAccount($conn, $email, $code);
        if ($result['success']) {
            $_SESSION['success_message'] = $result['message'];
            header('Location: login.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Account - Sarap Local</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #FFF5E1;
            background-image: 
                radial-gradient(#FFD1A9 1px, transparent 1px), 
                radial-gradient(#FFD1A9 1px, transparent 1px);
            background-size: 20px 20px;
            background-position: 0 0, 10px 10px;
        }
        .food-pattern {
            position: absolute;
            opacity: 0.1;
            z-index: 0;
            pointer-events: none;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        .code-input {
            letter-spacing: 0.5em;
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Decorative Background Elements -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <i class="fas fa-pizza-slice text-orange-200 text-9xl absolute -top-10 -left-10 transform rotate-45 opacity-20"></i>
        <i class="fas fa-burger text-yellow-200 text-9xl absolute top-1/2 -right-20 transform -rotate-12 opacity-20"></i>
        <i class="fas fa-ice-cream text-pink-200 text-8xl absolute bottom-0 left-20 transform rotate-12 opacity-20"></i>
    </div>

    <div class="glass-card w-full max-w-md rounded-3xl shadow-2xl p-8 relative z-10 border-t-4 border-orange-500">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 animate-float shadow-lg">
                <i class="fas fa-envelope-open-text text-3xl text-orange-500"></i>
            </div>
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Verify Your Email</h2>
            <p class="text-gray-600">
                We've sent a tasty 6-digit code to<br>
                <span class="font-semibold text-orange-600 bg-orange-50 px-2 py-1 rounded-lg mt-1 inline-block">
                    <?php echo htmlspecialchars($email); ?>
                </span>
            </p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 shadow-sm">
                <i class="fas fa-check-circle text-lg"></i>
                <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 shadow-sm">
                <i class="fas fa-exclamation-circle text-lg"></i>
                <span class="font-medium"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2 text-center uppercase tracking-wider">Enter Verification Code</label>
                <input 
                    type="text" 
                    name="code" 
                    class="code-input w-full px-4 py-4 text-center text-3xl font-bold text-gray-800 border-2 border-gray-200 rounded-2xl focus:outline-none focus:border-orange-500 focus:ring-4 focus:ring-orange-500/20 transition-all placeholder-gray-300 bg-gray-50 focus:bg-white" 
                    placeholder="000000" 
                    maxlength="6" 
                    required 
                    pattern="[0-9]{6}"
                    autocomplete="one-time-code"
                >
            </div>

            <button 
                type="submit" 
                class="w-full bg-gradient-to-r from-orange-500 to-orange-600 hover:from-orange-600 hover:to-orange-700 text-white font-bold py-3.5 px-4 rounded-xl shadow-lg shadow-orange-500/30 transform hover:-translate-y-0.5 transition-all duration-200 flex items-center justify-center gap-2"
            >
                <span>Verify Account</span>
                <i class="fas fa-arrow-right"></i>
            </button>
            
            <div class="text-center pt-2">
                <p class="text-sm text-gray-500 mb-2">Didn't receive the code?</p>
                <button 
                    type="submit" 
                    name="resend" 
                    value="1" 
                    class="text-orange-600 hover:text-orange-700 font-semibold text-sm hover:underline transition-colors flex items-center justify-center gap-2 mx-auto" 
                    formnovalidate
                >
                    <i class="fas fa-redo-alt text-xs"></i>
                    Resend Code
                </button>
            </div>
        </form>
    </div>
</body>
</html>
