<?php
/**
 * Navigation Helper
 * Provides navigation utilities for proper page flow and redirects
 */

/**
 * Get navigation breadcrumb
 */
function getNavigationBreadcrumb($current_page = null) {
    $breadcrumb = '<nav class="breadcrumb" aria-label="Breadcrumb">';
    $breadcrumb .= '<ol class="breadcrumb-list">';
    
    // Home link
    $breadcrumb .= '<li><a href="index.php" class="breadcrumb-link">Home</a></li>';
    
    // Current page
    if ($current_page) {
        $breadcrumb .= '<li><span class="breadcrumb-current">' . htmlspecialchars($current_page) . '</span></li>';
    }
    
    $breadcrumb .= '</ol>';
    $breadcrumb .= '</nav>';
    
    return $breadcrumb;
}

/**
 * Get navigation menu HTML
 */
function getNavigationMenu() {
    $is_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $user_role = $_SESSION['role'] ?? null;
    
    $menu = '<nav class="navigation-menu" aria-label="Main Navigation">';
    $menu .= '<ul class="nav-list">';
    
    // Home
    $menu .= '<li><a href="index.php" class="nav-item">Home</a></li>';
    
    // Landing page sections (for unauthenticated users)
    if (!$is_authenticated) {
        $menu .= '<li><a href="index.php#how-it-works" class="nav-item">How It Works</a></li>';
        $menu .= '<li><a href="index.php#for-customers" class="nav-item">For Customers</a></li>';
        $menu .= '<li><a href="index.php#for-vendors" class="nav-item">For Vendors</a></li>';
    }
    
    // Dashboard links (for authenticated users)
    if ($is_authenticated) {
        switch ($user_role) {
            case 'customer':
                $menu .= '<li><a href="customer.php" class="nav-item">Dashboard</a></li>';
                $menu .= '<li><a href="search.php" class="nav-item">Search</a></li>';
                $menu .= '<li><a href="profile.php" class="nav-item">Profile</a></li>';
                break;
            case 'vendor':
                $menu .= '<li><a href="vendor.php" class="nav-item">Dashboard</a></li>';
                $menu .= '<li><a href="profile.php" class="nav-item">Profile</a></li>';
                break;
        }
    }
    
    $menu .= '</ul>';
    $menu .= '</nav>';
    
    return $menu;
}

/**
 * Get proper redirect URL based on context
 */
function getProperRedirectURL($context = 'home') {
    $is_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    $user_role = $_SESSION['role'] ?? null;
    
    switch ($context) {
        case 'home':
            return 'index.php';
        case 'dashboard':
            if ($is_authenticated) {
                switch ($user_role) {
                    case 'customer':
                        return 'customer.php';
                    case 'vendor':
                        return 'vendor.php';
                    case 'admin':
                        return 'admin.php';
                }
            }
            return 'index.php';
        case 'login':
            return 'login.php';
        case 'signup':
            return 'signup.php';
        default:
            return 'index.php';
    }
}

/**
 * Store current page in session for navigation tracking
 */
function storeCurrentPage($page_name) {
    if (!isset($_SESSION['page_history'])) {
        $_SESSION['page_history'] = [];
    }
    
    // Add current page to history
    $_SESSION['page_history'][] = [
        'page' => $page_name,
        'url' => $_SERVER['REQUEST_URI'],
        'timestamp' => time()
    ];
    
    // Keep only last 10 pages
    if (count($_SESSION['page_history']) > 10) {
        array_shift($_SESSION['page_history']);
    }
}

/**
 * Get previous page from history
 */
function getPreviousPage() {
    if (isset($_SESSION['page_history']) && count($_SESSION['page_history']) > 1) {
        // Get second to last entry (last one is current page)
        $previous = $_SESSION['page_history'][count($_SESSION['page_history']) - 2];
        return $previous['url'];
    }
    return 'index.php';
}

/**
 * Get navigation CSS
 */
function getNavigationCSS() {
    return <<<CSS
    <style>
        .breadcrumb {
            margin-bottom: 1.5rem;
        }
        
        .breadcrumb-list {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .breadcrumb-list li {
            display: flex;
            align-items: center;
        }
        
        .breadcrumb-list li:not(:last-child)::after {
            content: '/';
            margin: 0 0.5rem;
            color: var(--gray-400);
        }
        
        .breadcrumb-link {
            color: var(--primary-orange);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .breadcrumb-link:hover {
            color: var(--primary-orange-dark);
            text-decoration: underline;
        }
        
        .breadcrumb-current {
            color: var(--gray-600);
        }
        
        .navigation-menu {
            margin-bottom: 2rem;
        }
        
        .nav-list {
            display: flex;
            gap: 1rem;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
        }
        
        .nav-item {
            padding: 0.5rem 1rem;
            color: var(--primary-orange);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .nav-item:hover {
            background: rgba(196, 106, 43, 0.1);
            color: var(--primary-orange-dark);
        }
        
        @media (max-width: 640px) {
            .nav-list {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .nav-item {
                display: block;
                padding: 0.75rem 1rem;
            }
        }
    </style>
    CSS;
}
