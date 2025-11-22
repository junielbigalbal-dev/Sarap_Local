<?php
session_start();
include '../db.php';

// Check admin access
// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Handle user actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['delete_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $success_msg = "User deleted successfully!";
        } else {
            $error_msg = "Error deleting user.";
        }
    }
}

// Fetch all users
$result = $conn->query("SELECT id, username, role, created_at FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sarap Local — User Management</title>
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
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
    }

    .users-table {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 15px;
      padding: 30px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.1);
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      padding: 15px;
      text-align: left;
      border-bottom: 1px solid #eee;
    }

    th {
      background: #f8f9fa;
      color: #ff7043;
      font-weight: bold;
    }

    .role-badge {
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: bold;
      text-transform: uppercase;
    }

    .role-customer {
      background: #e3f2fd;
      color: #1976d2;
    }

    .role-vendor {
      background: #fff3e0;
      color: #f57c00;
    }

    .delete-btn {
      background: #f44336;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 20px;
      cursor: pointer;
      font-size: 0.8rem;
      transition: all 0.3s ease;
    }

    .delete-btn:hover {
      background: #d32f2f;
      transform: translateY(-1px);
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

    @media (max-width: 768px) {
      .admin-header {
        flex-direction: column;
        gap: 15px;
        text-align: center;
      }
      
      .users-table {
        padding: 20px;
      }
      
      table {
        font-size: 0.9rem;
      }
    }
  </style>
</head>
<body>
  <div class="admin-header">
    <h1 class="admin-title">👥 User Management</h1>
    <a href="index.php" class="back-btn">← Back to Dashboard</a>
  </div>

  <div class="container">
    <?php if (isset($success_msg)): ?>
      <div class="alert alert-success"><?php echo $success_msg; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error_msg)): ?>
      <div class="alert alert-error"><?php echo $error_msg; ?></div>
    <?php endif; ?>

    <div class="users-table">
      <h3 style="color: #ff7043; margin-bottom: 20px;">All Registered Users</h3>
      
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while($user = $result->fetch_assoc()): ?>
              <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo htmlspecialchars($user['username']); ?></td>
                <td>
                  <span class="role-badge role-<?php echo $user['role']; ?>">
                    <?php echo ucfirst($user['role']); ?>
                  </span>
                </td>
                <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                <td>
                  <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                    <button type="submit" name="delete_user" class="delete-btn">🗑️ Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="5" style="text-align: center; color: #666; padding: 40px;">
                No users found. Users will appear here when they register.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>
</html>
