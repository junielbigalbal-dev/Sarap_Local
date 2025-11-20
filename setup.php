<?php
/**
 * Sarap Local - Setup Script
 * Initializes database and creates sample data
 */

// Prevent timeout
set_time_limit(300);

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'sarap_local';

// Connect to MySQL server (without database)
$conn = new mysqli($db_host, $db_user, $db_pass);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Create database if it doesn't exist
$create_db = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($create_db)) {
    die('Error creating database: ' . $conn->error);
}

// Select database
if (!$conn->select_db($db_name)) {
    die('Error selecting database: ' . $conn->error);
}

// Read and execute schema
$schema_file = __DIR__ . '/db/migrations/001_create_tables.sql';
if (!file_exists($schema_file)) {
    die('Schema file not found: ' . $schema_file);
}

$schema = file_get_contents($schema_file);
$queries = array_filter(array_map('trim', explode(';', $schema)));

foreach ($queries as $query) {
    if (!empty($query)) {
        if (!$conn->query($query)) {
            die('Error executing query: ' . $conn->error . '<br>Query: ' . $query);
        }
    }
}

// Create sample users
$sample_users = [
    [
        'username' => 'vendor1',
        'email' => 'vendor1@saraplocal.com',
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'role' => 'vendor',
        'business_name' => 'Lola\'s Kitchen',
        'phone' => '+63912345678',
        'address' => 'Biliran, Philippines',
        'bio' => 'Authentic Filipino home-cooked meals'
    ],
    [
        'username' => 'vendor2',
        'email' => 'vendor2@saraplocal.com',
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'role' => 'vendor',
        'business_name' => 'Tito\'s Grill',
        'phone' => '+63912345679',
        'address' => 'Biliran, Philippines',
        'bio' => 'Grilled specialties and BBQ'
    ],
    [
        'username' => 'customer1',
        'email' => 'customer1@saraplocal.com',
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'role' => 'customer',
        'phone' => '+63912345680',
        'address' => 'Biliran, Philippines'
    ],
    [
        'username' => 'customer2',
        'email' => 'customer2@saraplocal.com',
        'password' => password_hash('test123', PASSWORD_BCRYPT),
        'role' => 'customer',
        'phone' => '+63912345681',
        'address' => 'Biliran, Philippines'
    ]
];

$insert_user = "INSERT INTO users (username, email, password, role, business_name, phone, address, bio) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_user);

foreach ($sample_users as $user) {
    $stmt->bind_param(
        'ssssssss',
        $user['username'],
        $user['email'],
        $user['password'],
        $user['role'],
        $user['business_name'],
        $user['phone'],
        $user['address'],
        $user['bio']
    );
    
    if (!$stmt->execute()) {
        // User might already exist, continue
    }
}
$stmt->close();

// Create sample products
$sample_products = [
    [
        'vendor_id' => 1,
        'category_id' => 1,
        'product_name' => 'Adobo',
        'description' => 'Delicious chicken adobo cooked in vinegar and soy sauce',
        'price' => 150.00,
        'stock_quantity' => 20
    ],
    [
        'vendor_id' => 1,
        'category_id' => 1,
        'product_name' => 'Sinigang',
        'description' => 'Pork sinigang with radish and leafy vegetables',
        'price' => 160.00,
        'stock_quantity' => 15
    ],
    [
        'vendor_id' => 2,
        'category_id' => 7,
        'product_name' => 'BBQ Chicken',
        'description' => 'Grilled chicken BBQ with special sauce',
        'price' => 180.00,
        'stock_quantity' => 25
    ],
    [
        'vendor_id' => 2,
        'category_id' => 6,
        'product_name' => 'Grilled Fish',
        'description' => 'Fresh grilled fish with lemon butter',
        'price' => 200.00,
        'stock_quantity' => 10
    ]
];

$insert_product = "INSERT INTO products (vendor_id, category_id, product_name, description, price, stock_quantity) 
                   VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_product);

foreach ($sample_products as $product) {
    $stmt->bind_param(
        'iissdi',
        $product['vendor_id'],
        $product['category_id'],
        $product['product_name'],
        $product['description'],
        $product['price'],
        $product['stock_quantity']
    );
    
    if (!$stmt->execute()) {
        // Product might already exist, continue
    }
}
$stmt->close();

$conn->close();

// Display success message
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarap Local - Setup Complete</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            padding: 20px;
        }
        .container { 
            background: white; 
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
            padding: 40px; 
            max-width: 600px; 
            text-align: center;
        }
        h1 { color: #667eea; margin-bottom: 20px; }
        .success { color: #10b981; font-size: 48px; margin-bottom: 20px; }
        p { color: #666; line-height: 1.8; margin-bottom: 15px; }
        .info-box { 
            background: #f0f9ff; 
            border-left: 4px solid #3b82f6; 
            padding: 15px; 
            margin: 20px 0; 
            text-align: left;
            border-radius: 4px;
        }
        .info-box strong { color: #1e40af; }
        .info-box code { 
            background: #e0e7ff; 
            padding: 2px 6px; 
            border-radius: 3px; 
            font-family: monospace;
        }
        .button-group { 
            display: flex; 
            gap: 10px; 
            margin-top: 30px; 
            flex-wrap: wrap;
        }
        .btn { 
            flex: 1; 
            padding: 12px 24px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            font-weight: 600; 
            text-decoration: none; 
            display: inline-block; 
            transition: all 0.3s;
            min-width: 150px;
        }
        .btn-primary { 
            background: #667eea; 
            color: white; 
        }
        .btn-primary:hover { 
            background: #5568d3; 
            transform: translateY(-2px);
        }
        .btn-secondary { 
            background: #e5e7eb; 
            color: #333; 
        }
        .btn-secondary:hover { 
            background: #d1d5db; 
        }
        .test-accounts {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            border-radius: 4px;
        }
        .test-accounts strong { color: #92400e; }
        .test-accounts p { margin: 8px 0; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="success">✅</div>
        <h1>Setup Complete!</h1>
        <p>Sarap Local database has been successfully initialized.</p>

        <div class="info-box">
            <strong>✓ Database Created:</strong> <code>sarap_local</code><br>
            <strong>✓ Tables Created:</strong> 8 tables<br>
            <strong>✓ Sample Data:</strong> Loaded
        </div>

        <div class="test-accounts">
            <strong>Test Accounts:</strong>
            <p><strong>Vendor:</strong> vendor1 / test123</p>
            <p><strong>Vendor:</strong> vendor2 / test123</p>
            <p><strong>Customer:</strong> customer1 / test123</p>
            <p><strong>Customer:</strong> customer2 / test123</p>
        </div>

        <div class="info-box">
            <strong>Next Steps:</strong>
            <p>1. Start Apache and MySQL in XAMPP</p>
            <p>2. Create upload directories:</p>
            <p style="margin-left: 20px;"><code>mkdir -p uploads/products uploads/reels uploads/profiles</code></p>
            <p>3. Set proper permissions:</p>
            <p style="margin-left: 20px;"><code>chmod 755 uploads/*</code></p>
            <p>4. Access the application at:</p>
            <p style="margin-left: 20px;"><code>http://localhost/sarap_local/</code></p>
        </div>

        <div class="button-group">
            <a href="index.php" class="btn btn-primary">Go to Home</a>
            <a href="login.php" class="btn btn-secondary">Go to Login</a>
        </div>
    </div>
</body>
</html>
