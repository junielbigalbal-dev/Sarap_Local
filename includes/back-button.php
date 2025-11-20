<?php
/**
 * Back Button Helper
 * Provides back button functionality with fallback to appropriate page
 * Handles both authenticated and unauthenticated users
 */

/**
 * Get back button HTML
 */
function getBackButtonHTML($fallback_url = null) {
    // Determine fallback URL
    if ($fallback_url === null) {
        $fallback_url = getDefaultFallbackURL();
    }
    
    return <<<HTML
    <a href="javascript:history.back()" class="back-button" onclick="handleBackClick(event, '$fallback_url')" title="Go Back">
        <i class="fas fa-arrow-left"></i>
        <span class="back-text">Back</span>
    </a>
    HTML;
}

/**
 * Get default fallback URL based on authentication status and role
 */
function getDefaultFallbackURL() {
    // Check if user is authenticated
    $is_authenticated = isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
    
    if ($is_authenticated) {
        // User is logged in, redirect to their dashboard
        $user_role = $_SESSION['role'] ?? null;
        return getDashboardURL($user_role);
    } else {
        // User is not logged in, redirect to home page
        return 'index.php';
    }
}

/**
 * Get dashboard URL based on role
 */
function getDashboardURL($role) {
    switch ($role) {
        case 'customer':
            return 'customer.php';
        case 'vendor':
            return 'vendor.php';
        case 'admin':
            return 'admin.php';
        default:
            return 'index.php';
    }
}

/**
 * Get back button CSS
 */
function getBackButtonCSS() {
    return <<<CSS
    <style>
        .back-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: white;
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
            margin-bottom: 1.5rem;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(196, 106, 43, 0.25);
            position: relative;
            overflow: hidden;
        }

        .back-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
            z-index: 0;
        }

        .back-button:hover::before {
            left: 100%;
        }

        .back-button:hover {
            background: linear-gradient(135deg, var(--primary-orange-dark), var(--primary-orange));
            gap: 1rem;
            box-shadow: 0 6px 20px rgba(196, 106, 43, 0.35);
            transform: translateY(-2px);
        }

        .back-button:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(196, 106, 43, 0.25);
        }

        .back-button:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 12px rgba(196, 106, 43, 0.25), 0 0 0 3px rgba(196, 106, 43, 0.15);
        }

        .back-button i {
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .back-button:hover i {
            transform: translateX(-3px);
        }

        .back-text {
            display: inline;
            position: relative;
            z-index: 1;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .back-button {
                padding: 0.65rem 1rem;
                font-size: 0.95rem;
                box-shadow: 0 3px 10px rgba(196, 106, 43, 0.2);
            }

            .back-button:hover {
                box-shadow: 0 5px 16px rgba(196, 106, 43, 0.3);
            }
        }

        @media (max-width: 640px) {
            .back-button {
                padding: 0.6rem 0.9rem;
                font-size: 0.9rem;
                gap: 0.5rem;
            }

            .back-button:hover {
                gap: 0.7rem;
            }

            .back-button .back-text {
                display: inline;
            }

            .back-button i {
                font-size: 1rem;
            }
        }
    </style>
    CSS;
}

/**
 * Get back button JavaScript
 */
function getBackButtonJS() {
    return <<<JS
    <script>
        function handleBackClick(event, dashboardURL) {
            event.preventDefault();
            
            // Check if there's history to go back to
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // No history, redirect to dashboard
                window.location.href = dashboardURL;
            }
        }
    </script>
    JS;
}

?>
