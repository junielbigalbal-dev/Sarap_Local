<?php
// Admin Database Configuration with Interactive Setup
session_start();

// Check if admin is accessing this
if (!isset($_SESSION['admin_access'])) {
    header("Location: ../index.php");
    exit();
}

// Database configuration prompts
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => 'sarap_local'
];

// Handle form submission for database setup
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['setup_database'])) {
        $host = $_POST['db_host'];
        $username = $_POST['db_username'];
        $password = $_POST['db_password'];
        $database = $_POST['db_database'];
        
        try {
            // Test connection
            $test_conn = new mysqli($host, $username, $password);
            
            if ($test_conn->connect_error) {
                throw new Exception("Connection failed: " . $test_conn->connect_error);
            }
            
            // Create database if it doesn't exist
            $test_conn->query("CREATE DATABASE IF NOT EXISTS $database");
            $test_conn->select_db($database);
            
            // Update db.php file
            $db_content = "<?php\n";
            $db_content .= "\$host = \"$host\";\n";
            $db_content .= "\$user = \"$username\";\n";
            $db_content .= "\$pass = \"$password\";\n";
            $db_content .= "\$db = \"$database\";\n\n";
            $db_content .= "\$conn = new mysqli(\$host, \$user, \$pass, \$db);\n\n";
            $db_content .= "if (\$conn->connect_error) {\n";
            $db_content .= "  die(\"Connection failed: \" . \$conn->connect_error);\n";
            $db_content .= "}\n";
            $db_content .= "?>";
            
            file_put_contents('../db.php', $db_content);
            
            $success_msg = "Database configuration updated successfully!";
            $test_conn->close();
            
        } catch (Exception $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['run_sql_setup'])) {
        include '../db.php';
        
        try {
            // Read and execute the admin setup SQL
            $sql_content = file_get_contents('../admin_db_setup.sql');
            
            // Split SQL into individual statements
            $statements = array_filter(array_map('trim', explode(';', $sql_content)));
            
            foreach ($statements as $statement) {
                if (!empty($statement) && !preg_match('/^--/', $statement)) {
                    $conn->query($statement);
                }
            }
            
            $setup_success = "Database tables created successfully!";
            
        } catch (Exception $e) {
            $setup_error = "SQL Setup Error: " . $e->getMessage();
        }
    }
}

// Test current connection
$connection_status = "Not Connected";
$connection_class = "error";

try {
    include '../db.php';
    if ($conn && !$conn->connect_error) {
        $connection_status = "Connected Successfully";
        $connection_class = "success";
        
        // Get database stats
        $user_count = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
        $product_count = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'] ?? 0;
    }
} catch (Exception $e) {
    $connection_status = "Connection Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sarap Local — Database Configuration</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }
    
    body {
      font-family: 'Arial', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      color: #333;
    }

    .admin-header {
      background: rgba(255, 255, 255, 0.95);
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .admin-title {
      color: #ff7043;
      font-size: 1.8rem;
      font-weight: bold;
    }

    .back-btn {
      background: #666;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
      text-decoration: none;
    }

    .back-btn:hover {
      background: #555;
      transform: translateY(-2px);
    }

    .container {
      max-width: 800px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .config-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      margin-bottom: 30px;
    }

    .status-indicator {
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
      font-weight: bold;
      text-align: center;
    }

    .status-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .status-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      color: #333;
      font-weight: bold;
    }

    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 12px;
      border: 2px solid #ddd;
      border-radius: 8px;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }

    input[type="text"]:focus, input[type="password"]:focus {
      outline: none;
      border-color: #ff7043;
    }

    .btn {
      background: linear-gradient(45deg, #ff7043, #ff5722);
      color: white;
      border: none;
      padding: 12px 25px;
      border-radius: 25px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s ease;
      margin-right: 10px;
      margin-bottom: 10px;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(255, 112, 67, 0.4);
    }

    .btn-secondary {
      background: #6c757d;
    }

    .btn-secondary:hover {
      background: #5a6268;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .stat-item {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 10px;
      text-align: center;
    }

    .stat-number {
      font-size: 2rem;
      font-weight: bold;
      color: #ff7043;
    }

    .alert {
      padding: 15px;
      border-radius: 10px;
      margin-bottom: 20px;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }

    .sql-preview {
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 8px;
      padding: 15px;
      font-family: 'Courier New', monospace;
      font-size: 14px;
      max-height: 200px;
      overflow-y: auto;
      margin: 15px 0;
    }
  </style>
</head>
<body>
  <div class="admin-header">
    <h1 class="admin-title">🗄️ Database Configuration</h1>
    <a href="index.php" class="back-btn">← Back to Dashboard</a>
  </div>

  <div class="container">
    <?php if (isset($success_msg)): ?>
      <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
      <div class="alert alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <?php if (isset($setup_success)): ?>
      <div class="alert alert-success"><?php echo $setup_success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($setup_error)): ?>
      <div class="alert alert-error"><?php echo $setup_error; ?></div>
    <?php endif; ?>

    <!-- Connection Status -->
    <div class="config-card">
      <h3 style="color: #ff7043; margin-bottom: 20px;">📊 Connection Status</h3>
      <div class="status-indicator status-<?php echo $connection_class; ?>">
        <?php echo $connection_status; ?>
      </div>
      
      <?php if ($connection_class === 'success'): ?>
        <div class="stats-grid">
          <div class="stat-item">
            <div class="stat-number"><?php echo $user_count; ?></div>
            <div>Total Users</div>
          </div>
          <div class="stat-item">
            <div class="stat-number"><?php echo $product_count; ?></div>
            <div>Total Products</div>
          </div>
        </div>
      <?php endif; ?>
    </div>

    <!-- Database Configuration Form -->
    <div class="config-card">
      <h3 style="color: #ff7043; margin-bottom: 20px;">⚙️ Database Settings</h3>
      <form method="POST">
        <div class="form-group">
          <label for="db_host">Database Host</label>
          <input type="text" id="db_host" name="db_host" value="<?php echo $db_config['host']; ?>" required>
        </div>
        
        <div class="form-group">
          <label for="db_username">Database Username</label>
          <input type="text" id="db_username" name="db_username" value="<?php echo $db_config['username']; ?>" required>
        </div>
        
        <div class="form-group">
          <label for="db_password">Database Password</label>
          <input type="password" id="db_password" name="db_password" value="<?php echo $db_config['password']; ?>">
        </div>
        
        <div class="form-group">
          <label for="db_database">Database Name</label>
          <input type="text" id="db_database" name="db_database" value="<?php echo $db_config['database']; ?>" required>
        </div>
        
        <button type="submit" name="setup_database" class="btn">💾 Update Configuration</button>
      </form>
    </div>

    <!-- SQL Setup -->
    <div class="config-card">
      <h3 style="color: #ff7043; margin-bottom: 20px;">🔧 Database Setup</h3>
      <p>Run the complete database setup with tables, sample data, and admin configurations.</p>
      
      <div class="sql-preview">
        -- Creates tables: users, products, orders, messages, admin_settings, activity_logs<br>
        -- Adds sample data and admin user<br>
        -- Creates database views for statistics<br>
        -- Sets up proper indexes and foreign keys
      </div>
      
      <form method="POST">
        <button type="submit" name="run_sql_setup" class="btn" onclick="return confirm('This will create/update database tables. Continue?')">
          🚀 Run Database Setup
        </button>
      </form>
    </div>

    <!-- Manual SQL Instructions -->
    <div class="config-card">
      <h3 style="color: #ff7043; margin-bottom: 20px;">📋 Manual Setup Instructions</h3>
      <p><strong>Option 1: phpMyAdmin</strong></p>
      <ol>
        <li>Open phpMyAdmin: <code>http://localhost/phpmyadmin</code></li>
        <li>Go to "Import" tab</li>
        <li>Choose file: <code>admin_db_setup.sql</code></li>
        <li>Click "Go" to execute</li>
      </ol>
      
      <p style="margin-top: 20px;"><strong>Option 2: MySQL Command Line</strong></p>
      <div class="sql-preview">
        mysql -u root -p &lt; admin_db_setup.sql
      </div>
      
      <p style="margin-top: 20px;"><strong>Default Admin Login:</strong></p>
      <ul>
        <li>Username: <code>admin</code></li>
        <li>Password: <code>admin123</code></li>
      </ul>
    </div>
  </div>
</body>
</html>
