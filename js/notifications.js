class NotificationSystem {
    constructor() {
        this.notificationCount = 0;
        this.notificationBell = null;
        this.notificationPanel = null;
        this.initialize();
        this.setupEventListeners();
        this.startPolling();
    }

    initialize() {
        // Create notification bell if it doesn't exist
        if (!document.getElementById('notification-bell')) {
            const header = document.querySelector('header') || document.body;
            
            // Create notification bell
            this.notificationBell = document.createElement('div');
            this.notificationBell.id = 'notification-bell';
            this.notificationBell.className = 'notification-bell';
            this.notificationBell.innerHTML = `
                <i class="fas fa-bell"></i>
                <span class="notification-count">0</span>
            `;
            
            // Create notification panel
            this.notificationPanel = document.createElement('div');
            this.notificationPanel.id = 'notification-panel';
            this.notificationPanel.className = 'notification-panel';
            this.notificationPanel.innerHTML = `
                <div class="notification-header">
                    <h3>Notifications</h3>
                    <button id="clear-notifications">Clear All</button>
                </div>
                <div class="notification-list">
                    <div class="notification-empty">No new notifications</div>
                </div>
            `;
            
            // Add to DOM
            header.insertBefore(this.notificationBell, header.firstChild);
            header.appendChild(this.notificationPanel);
            
            // Add styles if not already added
            this.addStyles();
        }
    }

    addStyles() {
        const style = document.createElement('style');
        style.textContent = `
            .notification-bell {
                position: relative;
                cursor: pointer;
                padding: 10px;
                font-size: 20px;
                color: #333;
            }
            
            .notification-count {
                position: absolute;
                top: 0;
                right: 0;
                background: #ff4444;
                color: white;
                border-radius: 50%;
                padding: 2px 6px;
                font-size: 12px;
                font-weight: bold;
                display: none;
            }
            
            .notification-panel {
                position: fixed;
                top: 60px;
                right: 20px;
                width: 320px;
                max-height: 500px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 1000;
                display: none;
                overflow: hidden;
            }
            
            .notification-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 12px 16px;
                border-bottom: 1px solid #eee;
                background: #f8f9fa;
            }
            
            .notification-header h3 {
                margin: 0;
                font-size: 16px;
                color: #333;
            }
            
            #clear-notifications {
                background: none;
                border: none;
                color: #007bff;
                cursor: pointer;
                font-size: 14px;
            }
            
            .notification-list {
                max-height: 400px;
                overflow-y: auto;
            }
            
            .notification-item {
                padding: 12px 16px;
                border-bottom: 1px solid #f0f0f0;
                cursor: pointer;
                transition: background 0.2s;
            }
            
            .notification-item:hover {
                background: #f8f9fa;
            }
            
            .notification-item.unread {
                background: #f0f7ff;
            }
            
            .notification-title {
                font-weight: bold;
                margin-bottom: 4px;
            }
            
            .notification-message {
                color: #666;
                font-size: 14px;
                margin-bottom: 4px;
            }
            
            .notification-time {
                font-size: 12px;
                color: #999;
                text-align: right;
            }
            
            .notification-empty {
                padding: 20px;
                text-align: center;
                color: #999;
                font-style: italic;
            }
        `;
        document.head.appendChild(style);
    }

    setupEventListeners() {
        // Toggle notification panel
        this.notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleNotificationPanel();
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!this.notificationPanel.contains(e.target) && !this.notificationBell.contains(e.target)) {
                this.notificationPanel.style.display = 'none';
            }
        });

        // Clear all notifications
        const clearBtn = this.notificationPanel.querySelector('#clear-notifications');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.clearAllNotifications();
            });
        }
    }

    toggleNotificationPanel() {
        const isVisible = this.notificationPanel.style.display === 'block';
        this.notificationPanel.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            this.fetchNotifications();
        }
    }

    startPolling() {
        // Check for new notifications every 30 seconds
        this.fetchNotificationCount();
        this.pollingInterval = setInterval(() => this.fetchNotificationCount(), 30000);
    }

    async fetchNotificationCount() {
        try {
            const response = await fetch('/api/notifications?count=1');
            const data = await response.json();
            this.updateNotificationCount(data.count || 0);
        } catch (error) {
            console.error('Error fetching notification count:', error);
        }
    }

    async fetchNotifications() {
        try {
            const response = await fetch('/api/notifications');
            const notifications = await response.json();
            this.renderNotifications(notifications);
        } catch (error) {
            console.error('Error fetching notifications:', error);
        }
    }

    updateNotificationCount(count) {
        this.notificationCount = count;
        const countElement = this.notificationBell.querySelector('.notification-count');
        
        if (count > 0) {
            countElement.textContent = count > 99 ? '99+' : count;
            countElement.style.display = 'block';
            
            // Add animation for new notifications
            if (count > this.notificationCount) {
                this.notificationBell.classList.add('new-notification');
                setTimeout(() => this.notificationBell.classList.remove('new-notification'), 1000);
                
                // Show desktop notification if browser supports it
                if (Notification.permission === 'granted') {
                    new Notification('New Notification', {
                        body: `You have ${count} new notification${count > 1 ? 's' : ''}`,
                        icon: '/images/S.png'
                    });
                }
            }
        } else {
            countElement.style.display = 'none';
        }
    }

    renderNotifications(notifications) {
        const list = this.notificationPanel.querySelector('.notification-list');
        
        if (!notifications || notifications.length === 0) {
            list.innerHTML = '<div class="notification-empty">No notifications</div>';
            return;
        }
        
        list.innerHTML = '';
        
        notifications.forEach(notification => {
            const item = document.createElement('div');
            item.className = `notification-item ${!notification.is_read ? 'unread' : ''}`;
            
            // Format time
            const timeAgo = this.formatTimeAgo(notification.created_at);
            
            // Determine icon based on notification type
            let icon = '💬';
            if (notification.type === 'order') icon = '🛒';
            else if (notification.type === 'reply') icon = '↩️';
            
            item.innerHTML = `
                <div class="notification-title">
                    ${icon} ${notification.title}
                </div>
                <div class="notification-message">${notification.message}</div>
                <div class="notification-time">${timeAgo}</div>
            `;
            
            // Add click handler
            item.addEventListener('click', () => this.handleNotificationClick(notification));
            
            list.appendChild(item);
        });
    }

    formatTimeAgo(timestamp) {
        const seconds = Math.floor((new Date() - new Date(timestamp)) / 1000);
        
        const intervals = {
            year: 31536000,
            month: 2592000,
            week: 604800,
            day: 86400,
            hour: 3600,
            minute: 60,
            second: 1
        };
        
        for (const [unit, secondsInUnit] of Object.entries(intervals)) {
            const interval = Math.floor(seconds / secondsInUnit);
            if (interval >= 1) {
                return interval === 1 ? `1 ${unit} ago` : `${interval} ${unit}s ago`;
            }
        }
        
        return 'just now';
    }

    async handleNotificationClick(notification) {
        // Mark as read
        await fetch(`/api/notifications/${notification.id}/read`, { method: 'POST' });
        
        // Navigate based on notification type
        switch(notification.type) {
            case 'comment':
            case 'reply':
                window.location.href = `/product.php?id=${notification.reference_id}#comments`;
                break;
            case 'order':
                window.location.href = '/orders.php';
                break;
            default:
                // Do nothing
                break;
        }
    }

    async clearAllNotifications() {
        try {
            await fetch('/api/notifications/clear', { method: 'POST' });
            this.fetchNotifications();
            this.updateNotificationCount(0);
        } catch (error) {
            console.error('Error clearing notifications:', error);
        }
    }
}

// Initialize notification system when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Request notification permission
    if ('Notification' in window) {
        if (Notification.permission !== 'denied') {
            Notification.requestPermission();
        }
    }
    
    // Initialize notification system
    window.notificationSystem = new NotificationSystem();
});
