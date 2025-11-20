// Vendor Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Load initial data
    loadDashboardData();
    loadNotifications();
    loadProducts();
    loadFeed();
    loadOrders();
    loadAnalytics();

    // Setup event listeners
    setupEventListeners();
});

function setupEventListeners() {
    // Navigation
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('data-target');
            showSection(target);
        });
    });

    // Notification bell
    const notificationBell = document.getElementById('notificationBell');
    if (notificationBell) {
        notificationBell.addEventListener('click', showNotificationDropdown);
    }

    // Chat functionality
    const chatInput = document.getElementById('chatInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                sendMessage();
            }
        });
    }

    const sendBtn = document.getElementById('sendBtn');
    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }
}

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.content-section').forEach(section => {
        section.classList.add('d-none');
    });

    // Show target section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
        targetSection.classList.remove('d-none');
    }

    // Update active nav item
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('data-target') === sectionId) {
            link.classList.add('active');
        }
    });
}

// Dashboard functions
function loadDashboardData() {
    // In a real app, fetch this from your API
    const stats = {
        totalSales: 1250.50,
        totalOrders: 42,
        totalProducts: 15,
        totalCustomers: 87
    };

    // Update the dashboard stats
    document.getElementById('totalSales').textContent = `₱${stats.totalSales.toFixed(2)}`;
    document.getElementById('totalOrders').textContent = stats.totalOrders;
    document.getElementById('totalProducts').textContent = stats.totalProducts;
    document.getElementById('totalCustomers').textContent = stats.totalCustomers;
}

// Notification functions
function showNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    if (dropdown) {
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        
        // Mark notifications as read when dropdown is opened
        if (dropdown.style.display === 'block') {
            markAllNotificationsAsRead();
        }
    }
}

function markAllNotificationsAsRead() {
    fetch('api/mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ user_id: <?php echo $vendor_id; ?> })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update UI
            document.getElementById('notificationBadge').style.display = 'none';
            // Optionally update the notifications list
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking notifications as read:', error));
}

function loadNotifications() {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;

    // Show loading state
    notificationList.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    fetch('api/get_notifications.php?limit=10')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.length === 0) {
                notificationList.innerHTML = '<div class="text-center p-3 text-muted">No notifications</div>';
                return;
            }

            let html = '';
            data.forEach(notification => {
                const isUnread = notification.is_read === '0';
                html += `
                    <div class="notification-item ${isUnread ? 'unread' : ''} p-3 border-bottom" 
                         onclick="markNotificationAsRead(${notification.id}, this)">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3">
                                <i class="fas ${getNotificationIcon(notification.type)} text-${getNotificationColor(notification.type)}"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <h6 class="mb-1">${notification.title}</h6>
                                    <small class="text-muted">${formatTimeAgo(notification.created_at)}</small>
                                </div>
                                <p class="mb-0 small">${notification.message}</p>
                            </div>
                        </div>
                    </div>
                `;
            });

            notificationList.innerHTML = html;
        })
        .catch(error => {
            console.error('Error loading notifications:', error);
            notificationList.innerHTML = `
                <div class="text-center p-3">
                    <i class="fas fa-exclamation-triangle text-warning mb-2" style="font-size: 2rem;"></i>
                    <p class="mb-0">Failed to load notifications. Please try again later.</p>
                </div>
            `;
        });
}

function markNotificationAsRead(notificationId, element) {
    if (!notificationId) return;
    
    fetch('api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && element) {
            element.classList.remove('unread');
            // Update badge count
            const badge = document.getElementById('notificationBadge');
            if (badge) {
                const count = parseInt(badge.textContent) - 1;
                badge.textContent = count > 0 ? count : '';
                if (count <= 0) {
                    badge.style.display = 'none';
                }
            }
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Product management functions
function loadProducts() {
    const productsList = document.getElementById('productsList');
    if (!productsList) return;

    productsList.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

    fetch('api/get_products.php')
        .then(response => response.json())
        .then(products => {
            if (products.length === 0) {
                productsList.innerHTML = '<div class="text-center p-4 text-muted">No products found. Add your first product to get started.</div>';
                return;
            }

            let html = `
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            products.forEach(product => {
                html += `
                    <tr>
                        <td><img src="${product.image_url || 'images/placeholder.png'}" alt="${product.name}" class="product-thumb"></td>
                        <td>${product.name}</td>
                        <td>₱${parseFloat(product.price).toFixed(2)}</td>
                        <td>${product.stock}</td>
                        <td><span class="badge bg-${product.status === 'active' ? 'success' : 'secondary'}">${product.status}</span></td>
                        <td>
                            <button class="btn btn-sm btn-primary edit-product" data-id="${product.id}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-product" data-id="${product.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            html += `
                        </tbody>
                    </table>
                </div>
            `;

            productsList.innerHTML = html;

            // Add event listeners for edit/delete buttons
            document.querySelectorAll('.edit-product').forEach(btn => {
                btn.addEventListener('click', (e) => editProduct(e.target.closest('button').dataset.id));
            });

            document.querySelectorAll('.delete-product').forEach(btn => {
                btn.addEventListener('click', (e) => confirmDeleteProduct(e.target.closest('button').dataset.id));
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
            productsList.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Failed to load products. Please try again later.
                </div>
            `;
        });
}

function editProduct(productId) {
    // In a real app, fetch product details and populate the edit form
    const modal = new bootstrap.Modal(document.getElementById('editProductModal'));
    // Populate form fields here
    modal.show();
}

function confirmDeleteProduct(productId) {
    if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
        deleteProduct(productId);
    }
}

function deleteProduct(productId) {
    fetch(`api/delete_product.php?id=${productId}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Product deleted successfully', 'success');
            loadProducts();
        } else {
            showToast(data.message || 'Failed to delete product', 'error');
        }
    })
    .catch(error => {
        console.error('Error deleting product:', error);
        showToast('An error occurred while deleting the product', 'error');
    });
}

// Order management functions
function loadOrders() {
    const ordersList = document.getElementById('ordersList');
    if (!ordersList) return;

    // In a real app, fetch orders from your API
    // For now, we'll use placeholder data
    const orders = [];

    if (orders.length === 0) {
        ordersList.innerHTML = '<div class="text-center p-4 text-muted">No orders found.</div>';
        return;
    }

    // Build orders table
    // Similar structure to products table
}

// Analytics functions
function loadAnalytics() {
    const analyticsContainer = document.getElementById('analyticsChart');
    if (!analyticsContainer) return;

    // In a real app, fetch analytics data from your API
    const ctx = document.createElement('canvas');
    analyticsContainer.appendChild(ctx);

    // Example chart using Chart.js
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Sales',
                data: [65, 59, 80, 81, 56, 55],
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: true,
                    text: 'Sales Overview'
                }
            }
        }
    });
}

// Chat functions
function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // In a real app, send message to server
    const messageElement = document.createElement('div');
    messageElement.className = 'message sent';
    messageElement.innerHTML = `
        <div class="message-content">${message}</div>
        <div class="message-time">Just now</div>
    `;
    
    document.getElementById('chatMessages').appendChild(messageElement);
    input.value = '';
    
    // Auto-scroll to bottom
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

// Utility functions
function formatTimeAgo(timestamp) {
    // Implement time ago formatting
    return new Date(timestamp).toLocaleString();
}

function getNotificationIcon(type) {
    const icons = {
        'order': 'fa-shopping-cart',
        'message': 'fa-envelope',
        'system': 'fa-info-circle',
        'warning': 'fa-exclamation-triangle',
        'success': 'fa-check-circle'
    };
    return icons[type] || 'fa-bell';
}

function getNotificationColor(type) {
    const colors = {
        'order': 'primary',
        'message': 'info',
        'system': 'secondary',
        'warning': 'warning',
        'success': 'success'
    };
    return colors[type] || 'primary';
}

function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    const toastId = 'toast-' + Date.now();
    const icon = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    }[type] || 'info-circle';

    const toast = document.createElement('div');
    toast.id = toastId;
    toast.className = `toast show align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${icon} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;

    toastContainer.appendChild(toast);

    // Auto-remove toast after delay
    setTimeout(() => {
        const bsToast = new bootstrap.Toast(toast);
        bsToast.hide();
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }, 5000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '1100';
    document.body.appendChild(container);
    return container;
}

// Expose functions to global scope for HTML onclick handlers
window.showNotificationDropdown = showNotificationDropdown;
window.markNotificationAsRead = markNotificationAsRead;
window.sendMessage = sendMessage;
window.showSection = showSection;
