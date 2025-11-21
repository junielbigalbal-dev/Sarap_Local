<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php'; // To load env vars

echo "<h1>Email Debugger</h1>";
echo "<p>Attempting to send email...</p>";

// Debug: Check if env vars are loaded
function get_env_var($key) {
    if (getenv($key) !== false) return getenv($key);
    if (isset($_ENV[$key])) return $_ENV[$key];
    if (isset($_SERVER[$key])) return $_SERVER[$key];
    return false;
}

$smtp_user = get_env_var('SMTP_USER');
$smtp_host = get_env_var('SMTP_HOST');
$smtp_port = get_env_var('SMTP_PORT');
$smtp_pass = get_env_var('SMTP_PASS');

$smtp_pass = get_env_var('SMTP_PASS');

echo "<h3>Configuration Check:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> " . ($smtp_host ? $smtp_host : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP User:</strong> " . ($smtp_user ? $smtp_user : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP Port:</strong> " . ($smtp_port ? $smtp_port : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP Pass:</strong> " . ($smtp_pass ? '********' : '<span style="color:red">NOT SET</span>') . "</li>";
echo "</ul>";

echo "<h3>Environment Dump (Keys Only):</h3>";
echo "<pre>";
print_r(array_keys($_ENV));
print_r(array_keys($_SERVER));
echo "</pre>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';
    $mail->isSMTP();
    
    // Force Port 465 (SSL) for testing
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtp_user;
    $mail->Password   = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // Use SSL
    $mail->Port       = 465;
    
    // Timeout settings
    $mail->Timeout  = 10;

    // Recipients
    $mail->setFrom($smtp_user, 'Test Sender');
    $mail->addAddress($smtp_user);     // Add a recipient (sending to self)

    // Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Test Email from Render';
    $mail->Body    = 'This is the HTML message body <b>in bold!</b>';

    $mail->send();
    echo '<h2 style="color: green;">Message has been sent!</h2>';
} catch (Exception $e) {
    echo '<h2 style="color: red;">Message could not be sent.</h2>';
    echo "Mailer Error: {$mail->ErrorInfo}";
}
?>
