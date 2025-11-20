/**
 * Session Manager - Handles instant session validation and redirection
 * No page reload needed for login/logout
 */

const SessionManager = {
    checkInterval: null,
    
    /**
     * Check session validity and redirect if needed
     * Only redirects on non-dashboard pages
     */
    checkSession: async function() {
        try {
            const response = await fetch('api/session_handler.php?action=check&t=' + Date.now());
            const data = await response.json();
            
            if (data.valid) {
                // Session is valid
                return {
                    valid: true,
                    user_id: data.user_id,
                    role: data.role,
                    username: data.username
                };
            } else {
                // Session invalid or expired
                // Only redirect on non-dashboard pages
                const currentPath = window.location.pathname;
                const dashboardPages = [
                    '/sarap_local/customer.php',
                    '/sarap_local/vendor.php',
                    '/sarap_local/reels.php',
                    '/sarap_local/product.php',
                    '/sarap_local/profile.php'
                ];
                
                // Check if current page is a dashboard page
                const isDashboardPage = dashboardPages.some(page => currentPath.includes(page));
                
                // Only redirect if NOT on a dashboard page
                if (!isDashboardPage && 
                    currentPath !== '/sarap_local/login.php' && 
                    currentPath !== '/sarap_local/index.php' &&
                    currentPath !== '/sarap_local/signup.php') {
                    // Redirect to login without page reload
                    this.redirectToLogin();
                }
                return { valid: false };
            }
        } catch (error) {
            console.error('Session check error:', error);
            return { valid: false };
        }
    },
    
    /**
     * Validate session for protected pages
     */
    validateSession: async function(requiredRole = null) {
        try {
            const formData = new FormData();
            formData.append('action', 'validate');
            if (requiredRole) {
                formData.append('required_role', requiredRole);
            }
            
            const response = await fetch('api/session_handler.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (!data.valid) {
                if (data.redirect) {
                    this.redirectTo(data.redirect);
                }
                return false;
            }
            
            return true;
        } catch (error) {
            console.error('Session validation error:', error);
            return false;
        }
    },
    
    /**
     * Logout without page reload
     */
    logout: async function() {
        try {
            const response = await fetch('api/session_handler.php?action=logout&t=' + Date.now());
            const data = await response.json();
            
            if (data.success) {
                // Clear any stored data
                localStorage.clear();
                sessionStorage.clear();
                
                // Redirect to home
                this.redirectTo(data.redirect || 'index.php');
            }
        } catch (error) {
            console.error('Logout error:', error);
            // Force redirect on error
            window.location.href = 'index.php';
        }
    },
    
    /**
     * Redirect to URL (normal page reload)
     */
    redirectTo: function(url) {
        // Always use normal redirect for proper page loading
        window.location.href = url;
    },
    
    /**
     * Redirect to login page
     */
    redirectToLogin: function() {
        this.redirectTo('login.php');
    },
    
    /**
     * Start periodic session checking
     */
    startPeriodicCheck: function(interval = 60000) {
        // Check session every minute
        this.checkInterval = setInterval(() => {
            this.checkSession();
        }, interval);
    },
    
    /**
     * Stop periodic session checking
     */
    stopPeriodicCheck: function() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
            this.checkInterval = null;
        }
    },
    
    /**
     * Handle login form submission without page reload
     */
    handleLoginForm: function(formElement) {
        formElement.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(formElement);
            
            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });
                
                const html = await response.text();
                
                // Check if login was successful by looking for error messages
                if (html.includes('Invalid username or password') || 
                    html.includes('No user found')) {
                    // Show error
                    const errorDiv = formElement.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                    }
                } else {
                    // Login successful, check session and redirect
                    const sessionData = await this.checkSession();
                    if (sessionData.valid) {
                        const redirectUrl = sessionData.role === 'vendor' ? 'vendor.php' : 'customer.php';
                        this.redirectTo(redirectUrl);
                    }
                }
            } catch (error) {
                console.error('Login error:', error);
            }
        });
    },
    
    /**
     * Handle signup form submission
     */
    handleSignupForm: function(formElement) {
        formElement.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(formElement);
            
            try {
                const response = await fetch('signup.php', {
                    method: 'POST',
                    body: formData
                });
                
                const html = await response.text();
                
                // Check if signup was successful
                if (html.includes('error') || html.includes('Error')) {
                    // Show error
                    const errorDiv = formElement.querySelector('.error-message');
                    if (errorDiv) {
                        errorDiv.style.display = 'block';
                    }
                } else {
                    // Signup successful, redirect to login
                    this.redirectTo('login.php?registered=1');
                }
            } catch (error) {
                console.error('Signup error:', error);
            }
        });
    }
};

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const currentPath = window.location.pathname;
    const dashboardPages = ['/sarap_local/customer.php', '/sarap_local/vendor.php', '/sarap_local/reels.php', '/sarap_local/product.php', '/sarap_local/profile.php'];
    const isOnDashboard = dashboardPages.some(page => currentPath.includes(page));
    
    // NEVER check session on dashboard pages - prevents auto-redirect on hard refresh (Ctrl+Shift+R)
    if (!isOnDashboard) {
        // Only check session on non-dashboard pages (login, signup, index)
        SessionManager.checkSession();
    }
    
    // Disable periodic checking to prevent URL reloading issues
    // SessionManager.startPeriodicCheck(60000);
    
    // Handle login form if present
    const loginForm = document.querySelector('form[method="POST"]');
    if (loginForm && window.location.pathname.includes('login.php')) {
        SessionManager.handleLoginForm(loginForm);
    }
    
    // Handle signup form if present
    if (window.location.pathname.includes('signup.php')) {
        const signupForm = document.querySelector('form[method="POST"]');
        if (signupForm) {
            SessionManager.handleSignupForm(signupForm);
        }
    }
    
    // Handle logout button
    document.querySelectorAll('[onclick*="logout"], [href*="logout"]').forEach(element => {
        element.addEventListener('click', (e) => {
            e.preventDefault();
            SessionManager.logout();
        });
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', function() {
    SessionManager.stopPeriodicCheck();
});
