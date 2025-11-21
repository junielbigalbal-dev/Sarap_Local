<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'db.php'; // To load env vars

echo "<h1>Email Debugger</h1>";
echo "<p>Attempting to send email...</p>";

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
