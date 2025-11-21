<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php'; // To load env vars

echo "<h1>Email Debugger</h1>";
echo "<p>Attempting to send email...</p>";

// Debug: Check if env vars are loaded
$smtp_user = getenv('SMTP_USER');
$smtp_host = getenv('SMTP_HOST');
$smtp_port = getenv('SMTP_PORT');

echo "<h3>Configuration Check:</h3>";
echo "<ul>";
echo "<li><strong>SMTP Host:</strong> " . ($smtp_host ? $smtp_host : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP User:</strong> " . ($smtp_user ? $smtp_user : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP Port:</strong> " . ($smtp_port ? $smtp_port : '<span style="color:red">NOT SET</span>') . "</li>";
echo "<li><strong>SMTP Pass:</strong> " . (getenv('SMTP_PASS') ? '********' : '<span style="color:red">NOT SET</span>') . "</li>";
echo "</ul>";

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->Debugoutput = 'html';               // Output in HTML format
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = getenv('SMTP_HOST');                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = getenv('SMTP_USER');                    // SMTP username
    $mail->Password   = getenv('SMTP_PASS');                    // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
    $mail->Port       = getenv('SMTP_PORT');                    // TCP port to connect to

    // Recipients
    $mail->setFrom(getenv('SMTP_USER'), 'Test Sender');
    $mail->addAddress(getenv('SMTP_USER'));     // Add a recipient (sending to self)

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
