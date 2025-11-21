<?php
require_once 'db.php';
require_once 'includes/auth.php';

echo "Testing user registration...\n";

$username = 'testuser_' . time();
$email = 'test_' . time() . '@example.com';
$password = 'Password123';
$role = 'customer';

echo "Attempting to register: $username, $email, $role\n";

$result = registerUser($conn, $username, $email, $password, $password, $role);

if ($result['success']) {
    echo "Registration SUCCESS!\n";
} else {
    echo "Registration FAILED:\n";
    print_r($result['errors']);
}

// Clean up
if ($result['success']) {
    $conn->query("DELETE FROM users WHERE username = '$username'");
    echo "Test user deleted.\n";
}
?>
