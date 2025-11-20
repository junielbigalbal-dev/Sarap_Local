<?php
/**
 * SARAP LOCAL - SYSTEM STATUS CHECKER
 * Verifies all components are working for deployment
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

require_once 'db.php';
require_once 'includes/auth.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Sarap Local - System Status</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .content { padding: 30px; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #333; font-size: 1.3em; margin-bottom: 15px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .check { display: flex; align-items: center; padding: 12px; margin: 8px 0; border-radius: 8px; background: #f8f9fa; border-left: 4px solid #ddd; }
        .check.pass { background: #f0fdf4; border-left-color: #10b981; }
        .check.fail { background: #fef2f2; border-left-color: #ef4444; }
        .check.warn { background: #fef3c7; border-left-color: #f59e0b; }
        .icon { font-size: 1.5em; margin-right: 15px; min-width: 30px; }
        .text { flex: 1; }
        .label { font-weight: 600; color: #333; }
        .value { color: #666; font-size: 0.9em; margin-top: 4px; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }
        .summary { background: #dbeafe; border: 1px solid #93c5fd; color: #1e40af; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .success { background: #d1fae5; border: 1px solid #6ee7b7; color: #065f46; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .error { background: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .button-group { display: flex; gap: 10px; margin-top: 20px; flex-wrap: wrap; }
        .btn { padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #5568d3; transform: translateY(-2px); }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .progress-bar { width: 100%; height: 30px; background: #e5e7eb; border-radius: 8px; overflow: hidden; margin: 10px 0; }
        .progress-fill { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🔍 Sarap Local - System Status</h1>
        <p>Deployment Readiness Check</p>
    </div>

    <div class="content">
        <?php
        $checks = [
            'pass' => 0,
            'fail' => 0,
            'warn' => 0
        ];

        // Section 1: Database
        echo '<div class="section">';
        echo '<h2>🗄️ Database</h2>';

        $db_ok = !$conn->connect_error;
        $checks[$db_ok ? 'pass' : 'fail']++;
        echo '<div class="check ' . ($db_ok ? 'pass' : 'fail') . '">';
        echo '<div class="icon">' . ($db_ok ? '✅' : '❌') . '</div>';
        echo '<div class="text">';
        echo '<div class="label">Database Connection</div>';
        echo '<div class="value">' . ($db_ok ? 'Connected to sarap_local' : 'Error: ' . htmlspecialchars($conn->connect_error)) . '</div>';
        echo '</div></div>';

        if ($db_ok) {
            $users = $conn->query("SELECT COUNT(*) as cnt FROM users");
            $user_count = $users->fetch_assoc()['cnt'];
            $checks['pass']++;
            echo '<div class="check pass">';
            echo '<div class="icon">✅</div>';
            echo '<div class="text">';
            echo '<div class="label">Users in Database</div>';
            echo '<div class="value">' . $user_count . ' user(s) found</div>';
            echo '</div></div>';
        }

        echo '</div>';

        // Section 2: Session
        echo '<div class="section">';
        echo '<h2>📊 Session</h2>';

        $session_ok = session_status() === PHP_SESSION_ACTIVE;
        $checks[$session_ok ? 'pass' : 'fail']++;
        echo '<div class="check ' . ($session_ok ? 'pass' : 'fail') . '">';
        echo '<div class="icon">' . ($session_ok ? '✅' : '❌') . '</div>';
        echo '<div class="text">';
        echo '<div class="label">Session Status</div>';
        echo '<div class="value">' . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . '</div>';
        echo '</div></div>';

        $logged_in = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
        $checks[$logged_in ? 'pass' : 'warn']++;
        echo '<div class="check ' . ($logged_in ? 'pass' : 'warn') . '">';
        echo '<div class="icon">' . ($logged_in ? '✅' : '⚠️') . '</div>';
        echo '<div class="text">';
        echo '<div class="label">User Logged In</div>';
        echo '<div class="value">' . ($logged_in ? 'Yes (' . htmlspecialchars($_SESSION['role']) . ')' : 'No (expected on first visit)') . '</div>';
        echo '</div></div>';

        echo '</div>';

        // Section 3: Files
        echo '<div class="section">';
        echo '<h2>📁 Required Files</h2>';

        $files = [
            'login.php' => 'Login Page',
            'signup.php' => 'Signup Page',
            'logout.php' => 'Logout Handler',
            'customer.php' => 'Customer Dashboard',
            'vendor.php' => 'Vendor Dashboard',
            'admin.php' => 'Admin Dashboard',
            'includes/auth.php' => 'Authentication',
            'db.php' => 'Database Connection',
            'api/vendor_notifications.php' => 'Notifications API',
        ];

        foreach ($files as $file => $label) {
            $exists = file_exists($file);
            $checks[$exists ? 'pass' : 'fail']++;
            echo '<div class="check ' . ($exists ? 'pass' : 'fail') . '">';
            echo '<div class="icon">' . ($exists ? '✅' : '❌') . '</div>';
            echo '<div class="text">';
            echo '<div class="label">' . $label . '</div>';
            echo '<div class="value"><code>' . $file . '</code></div>';
            echo '</div></div>';
        }

        echo '</div>';

        // Section 4: Security
        echo '<div class="section">';
        echo '<h2>🔐 Security</h2>';

        $password_hash_ok = function_exists('password_hash');
        $checks[$password_hash_ok ? 'pass' : 'fail']++;
        echo '<div class="check ' . ($password_hash_ok ? 'pass' : 'fail') . '">';
        echo '<div class="icon">' . ($password_hash_ok ? '✅' : '❌') . '</div>';
        echo '<div class="text">';
        echo '<div class="label">Password Hashing</div>';
        echo '<div class="value">' . ($password_hash_ok ? 'Bcrypt available' : 'Not available') . '</div>';
        echo '</div></div>';

        $csrf_ok = function_exists('generateCSRFToken');
        $checks[$csrf_ok ? 'pass' : 'fail']++;
        echo '<div class="check ' . ($csrf_ok ? 'pass' : 'fail') . '">';
        echo '<div class="icon">' . ($csrf_ok ? '✅' : '❌') . '</div>';
        echo '<div class="text">';
        echo '<div class="label">CSRF Protection</div>';
        echo '<div class="value">' . ($csrf_ok ? 'Implemented' : 'Not implemented') . '</div>';
        echo '</div></div>';

        echo '</div>';

        // Summary
        $total = $checks['pass'] + $checks['fail'] + $checks['warn'];
        $score = round(($checks['pass'] / $total) * 100);

        echo '<div class="section">';
        echo '<h2>📈 Overall Status</h2>';
        echo '<div class="progress-bar">';
        echo '<div class="progress-fill" style="width: ' . $score . '%;">' . $score . '%</div>';
        echo '</div>';

        echo '<div class="summary">';
        echo '<strong>✅ Passed:</strong> ' . $checks['pass'] . '<br>';
        echo '<strong>⚠️ Warnings:</strong> ' . $checks['warn'] . '<br>';
        echo '<strong>❌ Failed:</strong> ' . $checks['fail'] . '<br>';
        echo '</div>';

        if ($checks['fail'] === 0 && $checks['warn'] === 0) {
            echo '<div class="success">';
            echo '✅ <strong>System Ready for Deployment!</strong><br>';
            echo 'All checks passed. Your Sarap Local app is ready to deploy.';
            echo '</div>';
        } elseif ($checks['fail'] === 0) {
            echo '<div class="summary">';
            echo '⚠️ <strong>System Ready with Warnings</strong><br>';
            echo 'Minor issues detected but system is functional.';
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '❌ <strong>System Not Ready</strong><br>';
            echo 'Critical issues detected. Please fix before deployment.';
            echo '</div>';
        }

        echo '</div>';

        // Action Buttons
        echo '<div class="button-group">';
        echo '<a href="login.php" class="btn btn-primary">Go to Login</a>';
        echo '<a href="DEPLOYMENT_ACTION_PLAN.md" class="btn btn-primary">View Action Plan</a>';
        echo '</div>';

        $conn->close();
        ?>
    </div>
</div>
</body>
</html>
