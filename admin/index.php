<?php
session_start();
include '../db.php';

// Check if user is admin (you can modify this logic as needed)
if (!isset($_SESSION['admin_access'])) {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sarap Local — Admin Dashboard</title>
  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Global brand styles -->
  <link rel="stylesheet" href="../css/style.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gray-50 flex flex-col">
  <!-- Admin Header -->
  <header class="brand-header shadow-sm sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex items-center justify-between h-16">
        <div class="flex items-center">
          <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
            <img src="../images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
          </div>
          <div class="flex flex-col leading-tight">
            <span class="text-xs uppercase tracking-[0.2em] text-orange-100">Admin</span>
            <span class="text-xl font-semibold brand-script">Sarap Local</span>
          </div>
        </div>

        <button class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-500 hover:bg-red-600 rounded-full shadow-sm transition-colors" onclick="logout()">
          <i class="fas fa-sign-out-alt mr-2"></i>
          Logout
        </button>
      </div>
    </div>
  </header>

  <!-- Main Admin Content -->
  <main class="flex-1 w-full">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
          <span class="text-lg">🛡️</span>
          <span>Admin Dashboard</span>
        </h1>
        <p class="text-gray-600 text-sm mt-1">Monitor users, vendors, customers, and system activity for Sarap Local.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Users Management -->
        <section class="brand-card rounded-xl shadow-sm border border-orange-100/60 p-6 flex flex-col justify-between">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="text-xl">👥</span>
              </div>
              <div>
                <h2 class="text-base font-semibold text-gray-800">Users</h2>
                <p class="text-xs text-gray-500">All registered accounts</p>
              </div>
            </div>
            <span class="text-2xl font-bold text-orange-600" id="total-users">0</span>
          </div>
          <button class="btn btn-primary w-full justify-center" onclick="manageUsers()">Manage Users</button>
        </section>

        <!-- Vendors Management -->
        <section class="brand-card rounded-xl shadow-sm border border-orange-100/60 p-6 flex flex-col justify-between">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center">
                <span class="text-xl">👨‍🍳</span>
              </div>
              <div>
                <h2 class="text-base font-semibold text-gray-800">Vendors</h2>
                <p class="text-xs text-gray-500">Active food partners</p>
              </div>
            </div>
            <span class="text-2xl font-bold text-orange-600" id="total-vendors">0</span>
          </div>
          <button class="btn btn-outline w-full justify-center" onclick="manageVendors()">Manage Vendors</button>
        </section>

        <!-- Customers -->
        <section class="brand-card rounded-xl shadow-sm border border-orange-100/60 p-6 flex flex-col justify-between">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center">
                <span class="text-xl">🛒</span>
              </div>
              <div>
                <h2 class="text-base font-semibold text-gray-800">Customers</h2>
                <p class="text-xs text-gray-500">Food lovers on the platform</p>
              </div>
            </div>
            <span class="text-2xl font-bold text-orange-600" id="total-customers">0</span>
          </div>
          <button class="btn btn-outline w-full justify-center" onclick="manageCustomers()">View Customers</button>
        </section>
      </div>

      <!-- System Settings & Recent Activity -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <section class="brand-card rounded-xl shadow-sm border border-orange-100/60 p-6 lg:col-span-1 flex flex-col justify-between">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
              <span class="text-xl">⚙️</span>
            </div>
            <div>
              <h2 class="text-base font-semibold text-gray-800">System Settings</h2>
              <p class="text-xs text-gray-500">Configure key platform options</p>
            </div>
          </div>
          <p class="text-sm text-gray-600 mb-4">Manage global configuration, roles, and operational preferences for Sarap Local.</p>
          <button class="btn btn-primary w-full justify-center" onclick="systemSettings()">Open Settings</button>
        </section>

        <section class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 lg:col-span-2">
          <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <span>📊</span>
            <span>Recent Activity</span>
          </h2>
          <div id="activity-list" class="divide-y divide-gray-100">
            <div class="flex items-center justify-between py-3">
              <span class="text-sm text-gray-800">System initialized</span>
              <span class="text-xs text-gray-500">Just now</span>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <script>
    // Load dashboard data
    function loadDashboardData() {
      // Fetch user statistics
      fetch('../api/admin-stats.php')
        .then(response => response.json())
        .then(data => {
          document.getElementById('total-users').textContent = data.total_users || 0;
          document.getElementById('total-vendors').textContent = data.total_vendors || 0;
          document.getElementById('total-customers').textContent = data.total_customers || 0;
        })
        .catch(error => {
          console.log('Stats not available yet');
        });
    }

    function manageUsers() {
      window.location.href = 'users.php';
    }

    function manageVendors() {
      window.location.href = 'vendors.php';
    }

    function manageCustomers() {
      window.location.href = 'customers.php';
    }

    function systemSettings() {
      window.location.href = 'settings.php';
    }

    function logout() {
      if (confirm('Are you sure you want to logout?')) {
        window.location.href = 'logout.php';
      }
    }

    // Load data when page loads
    document.addEventListener('DOMContentLoaded', loadDashboardData);
  </script>
</body>
</html>
