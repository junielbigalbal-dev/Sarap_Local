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
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .verify-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 2rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .verify-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .code-input {
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            text-align: center;
            margin: 1.5rem 0;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-icon">
            <i class="fas fa-envelope-open-text"></i>
        </div>
        <h2>Verify Your Email</h2>
        <p>We sent a 6-digit code to <strong><?php echo htmlspecialchars($email); ?></strong></p>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div class="form-group">
                <input type="text" name="code" class="form-control code-input" placeholder="000000" maxlength="6" required pattern="[0-9]{6}">
            </div>

            <button type="submit" class="btn btn-primary btn-block">Verify Account</button>
            
            <div style="margin-top: 1rem;">
                <button type="submit" name="resend" value="1" class="btn btn-text" formnovalidate>Resend Code</button>
            </div>
        </form>
    </div>
</body>
</html>
