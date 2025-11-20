/**
 * Real-Time Updates System
 * Handles all instant DOM updates without page reload
 */

// Global notification system
const NotificationManager = {
    show: function(message, type = 'success', duration = 3000) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 animate-fade-in ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        }`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('animate-fade-out');
            setTimeout(() => notification.remove(), 300);
        }, duration);
    }
};

// Profile Updates
const ProfileUpdater = {
    updateField: function(fieldName, value) {
        // Update all display elements with data attribute
        document.querySelectorAll(`[data-profile-${fieldName}]`).forEach(el => {
            el.textContent = value;
            el.classList.add('animate-pulse');
            setTimeout(() => el.classList.remove('animate-pulse'), 500);
        });
    },
    
    updateProfilePicture: function(imageSrc) {
        document.querySelectorAll('img[alt="Profile"], img[alt="profile"]').forEach(img => {
            img.src = imageSrc;
            img.classList.add('animate-pulse');
            setTimeout(() => img.classList.remove('animate-pulse'), 500);
        });
    },
    
    handleFormSubmit: function(formElement) {
        formElement.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(formElement);
            
            fetch(formElement.action || 'profile.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                NotificationManager.show('Profile updated successfully!', 'success');
                
                // Update profile picture if uploaded
                const profileImageInput = formElement.querySelector('input[name="profile_image"]');
                if (profileImageInput && profileImageInput.files.length > 0) {
                    const reader = new FileReader();
                    reader.onload = (e) => {
                        this.updateProfilePicture(e.target.result);
                    };
                    reader.readAsDataURL(profileImageInput.files[0]);
                }
                
                // Update all visible fields
                formElement.querySelectorAll('input[type="text"], input[type="email"], textarea').forEach(field => {
                    if (field.name && field.value) {
                        this.updateField(field.name, field.value);
                    }
                });
                
                // Close modal if exists
                const modal = formElement.closest('[id*="Modal"]');
                if (modal) modal.classList.add('hidden');
            })
            .catch(error => {
                console.error('Error:', error);
                NotificationManager.show('Error updating profile', 'error');
            });
        });
    }
};

// Reel Management
const ReelManager = {
    loadReels: function(containerId = 'reelsList') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        fetch('api/vendor_reels.php?action=list&t=' + Date.now())
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-center py-12 col-span-full"><i class="fas fa-film text-4xl text-gray-300 mb-4"></i><p class="text-gray-500">No reels uploaded yet</p></div>';
                    return;
                }
                
                let html = '';
                data.forEach(reel => {
                    html += `
                        <div class="brand-card rounded-lg shadow-sm overflow-hidden reel-item animate-fade-in" data-reel-id="${reel.id}">
                            <div class="relative bg-gray-900 h-48">
                                <video src="${reel.video_path}" class="w-full h-full object-cover" controls></video>
                                <div class="absolute top-2 right-2 space-x-2">
                                    <button onclick="ReelManager.deleteReel(${reel.id})" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700 transition-colors">
                                        <i class="fas fa-trash-alt text-xs"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="font-semibold text-gray-800 truncate">${reel.title || 'Untitled'}</h3>
                                <p class="text-sm text-gray-600 mt-1 line-clamp-2">${reel.description || ''}</p>
                                ${reel.product_name ? `<p class="text-xs text-orange-600 mt-2">📦 ${reel.product_name}</p>` : ''}
                                <p class="text-xs text-gray-400 mt-2 reel-views">👁️ ${reel.view_count || 0} views</p>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
                this.attachEventListeners();
            })
            .catch(error => {
                console.error('Error loading reels:', error);
                container.innerHTML = '<div class="text-center py-12 col-span-full text-red-600">Error loading reels</div>';
            });
    },
    
    deleteReel: function(reelId) {
        if (!confirm('Delete this reel?')) return;
        
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('reel_id', reelId);
        
        fetch('api/vendor_reels.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NotificationManager.show('Reel deleted', 'success');
                // Remove reel from DOM with animation
                const reelElement = document.querySelector(`[data-reel-id="${reelId}"]`);
                if (reelElement) {
                    reelElement.classList.add('animate-fade-out');
                    setTimeout(() => {
                        reelElement.remove();
                        this.loadReels();
                    }, 300);
                }
            } else {
                NotificationManager.show(data.error || 'Error deleting reel', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationManager.show('Error deleting reel', 'error');
        });
    },
    
    uploadReel: function(formElement) {
        formElement.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const formData = new FormData(formElement);
            formData.append('action', 'upload');
            
            fetch('api/vendor_reels.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    NotificationManager.show('Reel uploaded successfully!', 'success');
                    formElement.reset();
                    
                    // Close modal
                    const modal = formElement.closest('[id*="Modal"]');
                    if (modal) modal.classList.add('hidden');
                    
                    // Reload reels immediately
                    setTimeout(() => this.loadReels(), 500);
                } else {
                    NotificationManager.show(data.error || 'Error uploading reel', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                NotificationManager.show('Error uploading reel', 'error');
            });
        });
    },
    
    attachEventListeners: function() {
        document.querySelectorAll('.reel-item').forEach(item => {
            const deleteBtn = item.querySelector('button[onclick*="deleteReel"]');
            if (deleteBtn) {
                deleteBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                });
            }
        });
    }
};

// Customer Reels Feed
const CustomerReelsFeed = {
    currentOffset: 0,
    isLoading: false,
    
    loadReels: function(containerId = 'reelsFeed') {
        const container = document.getElementById(containerId);
        if (!container || this.isLoading) return;
        
        this.isLoading = true;
        
        fetch(`api/customer_reels.php?action=feed&offset=${this.currentOffset}&limit=20&t=` + Date.now())
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    if (this.currentOffset === 0) {
                        container.innerHTML = '<div class="text-center py-12 text-gray-400">No reels available</div>';
                    }
                    this.isLoading = false;
                    return;
                }
                
                let html = '';
                data.forEach(reel => {
                    html += `
                        <div class="reel-item animate-fade-in" data-reel-id="${reel.id}">
                            <video src="${reel.video_path}" class="reel-video" data-reel-id="${reel.id}"></video>
                            <div class="reel-overlay">
                                <div class="reel-info">
                                    <div class="reel-details">
                                        <div class="reel-vendor">
                                            ${reel.profile_image ? `<img src="${reel.profile_image}" alt="Vendor" class="reel-vendor-avatar">` : '<div class="reel-vendor-avatar"></div>'}
                                            <span class="reel-vendor-name">${reel.business_name || 'Vendor'}</span>
                                        </div>
                                        <div class="reel-title">${reel.title || reel.product_name || 'Food Reel'}</div>
                                        <div class="reel-description">${reel.description || ''}</div>
                                    </div>
                                </div>
                                <button onclick="CustomerReelsFeed.orderNow(${reel.product_id})" class="order-now-btn">
                                    Order Now
                                </button>
                            </div>
                        </div>
                    `;
                });
                
                if (this.currentOffset === 0) {
                    container.innerHTML = html;
                } else {
                    container.innerHTML += html;
                }
                
                this.currentOffset += data.length;
                this.setupAutoPlay();
                this.isLoading = false;
            })
            .catch(error => {
                console.error('Error loading reels:', error);
                this.isLoading = false;
            });
    },
    
    setupAutoPlay: function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target.querySelector('video');
                if (!video) return;
                
                if (entry.isIntersecting) {
                    video.play().catch(() => {});
                    this.incrementViewCount(entry.target.dataset.reelId);
                } else {
                    video.pause();
                }
            });
        }, { threshold: 0.5 });
        
        document.querySelectorAll('.reel-item').forEach(item => {
            observer.observe(item);
        });
    },
    
    incrementViewCount: function(reelId) {
        fetch('api/customer_reels.php?action=increment_views&t=' + Date.now(), {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `reel_id=${reelId}`
        }).catch(() => {});
    },
    
    orderNow: function(productId) {
        if (!productId) {
            NotificationManager.show('Product not available', 'error');
            return;
        }
        window.location.href = `product.php?id=${productId}`;
    }
};

// Notification System
const NotificationSystem = {
    loadNotifications: function() {
        fetch('api/vendor_notifications.php?t=' + Date.now())
            .then(response => response.json())
            .then(data => {
                const list = document.getElementById('notificationList');
                const badge = document.getElementById('notificationBadge');
                
                if (!list) return;
                
                if (!Array.isArray(data) || data.length === 0) {
                    list.innerHTML = '<div class="p-4 text-center text-gray-500 text-sm">No notifications</div>';
                    if (badge) badge.classList.add('hidden');
                    return;
                }
                
                let unreadCount = 0;
                let html = '';
                
                data.forEach(notif => {
                    if (!notif.is_read) unreadCount++;
                    const time = new Date(notif.created_at).toLocaleString();
                    html += `
                        <div class="p-4 hover:bg-gray-50 cursor-pointer transition-colors ${!notif.is_read ? 'bg-blue-50' : ''}">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="font-medium text-gray-800">${notif.title}</p>
                                    <p class="text-sm text-gray-600 mt-1">${notif.message}</p>
                                </div>
                            </div>
                            <p class="text-xs text-gray-400 mt-2">${time}</p>
                        </div>
                    `;
                });
                
                list.innerHTML = html;
                
                if (unreadCount > 0 && badge) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.classList.remove('hidden');
                } else if (badge) {
                    badge.classList.add('hidden');
                }
            })
            .catch(error => console.error('Error loading notifications:', error));
    },
    
    clearAll: function() {
        fetch('api/mark_all_notifications_read.php', { method: 'POST' })
            .then(() => {
                NotificationManager.show('Notifications cleared', 'success');
                this.loadNotifications();
            })
            .catch(error => console.error('Error clearing notifications:', error));
    },
    
    startPolling: function(interval = 30000) {
        this.loadNotifications();
        setInterval(() => this.loadNotifications(), interval);
    }
};

// Product Management
const ProductManager = {
    loadProducts: function(containerId = 'productsList') {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        fetch('api/get_products.php?t=' + Date.now())
            .then(response => response.json())
            .then(data => {
                if (!Array.isArray(data) || data.length === 0) {
                    container.innerHTML = '<div class="text-center py-12 text-gray-500">No products found</div>';
                    return;
                }
                
                let html = '';
                data.forEach(product => {
                    html += `
                        <div class="brand-card rounded-lg p-4 animate-fade-in" data-product-id="${product.id}">
                            <div class="flex justify-between items-start mb-3">
                                <h3 class="font-semibold text-gray-800">${product.product_name}</h3>
                                <span class="text-orange-600 font-bold">₱${parseFloat(product.price).toFixed(2)}</span>
                            </div>
                            <p class="text-sm text-gray-600 mb-3">${product.description}</p>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-gray-500">${product.stock_quantity} in stock</span>
                                <div class="space-x-2">
                                    <button onclick="ProductManager.editProduct(${product.id})" class="px-3 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600">Edit</button>
                                    <button onclick="ProductManager.deleteProduct(${product.id})" class="px-3 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600">Delete</button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                container.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading products:', error);
                container.innerHTML = '<div class="text-center py-12 text-red-500">Error loading products</div>';
            });
    },
    
    editProduct: function(productId) {
        // Implement edit logic
        console.log('Edit product:', productId);
    },
    
    deleteProduct: function(productId) {
        if (!confirm('Delete this product?')) return;
        
        fetch('api/delete_product.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                NotificationManager.show('Product deleted', 'success');
                const productElement = document.querySelector(`[data-product-id="${productId}"]`);
                if (productElement) {
                    productElement.classList.add('animate-fade-out');
                    setTimeout(() => {
                        productElement.remove();
                        this.loadProducts();
                    }, 300);
                }
            } else {
                NotificationManager.show(data.error || 'Error deleting product', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            NotificationManager.show('Error deleting product', 'error');
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Auto-initialize all real-time systems
    if (document.getElementById('reelsList')) {
        ReelManager.loadReels();
    }
    
    if (document.getElementById('reelsFeed')) {
        CustomerReelsFeed.loadReels();
    }
    
    if (document.getElementById('notificationList')) {
        NotificationSystem.startPolling();
    }
    
    if (document.getElementById('productsList')) {
        ProductManager.loadProducts();
    }
    
    // Setup profile form handlers
    document.querySelectorAll('form[method="POST"]').forEach(form => {
        if (form.id === 'profileForm' || form.closest('[id*="Profile"]')) {
            ProfileUpdater.handleFormSubmit(form);
        }
    });
    
    // Setup reel upload form
    const reelUploadForm = document.getElementById('reelUploadForm');
    if (reelUploadForm) {
        ReelManager.uploadReel(reelUploadForm);
    }
});

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }
        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }
    
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out;
    }
    
    .animate-fade-out {
        animation: fadeOut 0.3s ease-out;
    }
    
    .animate-pulse {
        animation: pulse 0.5s ease-in-out;
    }
    
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
`;
document.head.appendChild(style);
