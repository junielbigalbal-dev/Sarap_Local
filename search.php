<?php
// Start session to check user authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/db.php';

$searchTerm = isset($_GET['q']) ? trim($_GET['q']) : '';
$vendorSections = [];
$errorMessage = '';
$totalVendorsMatched = 0;
$totalFoodsMatched = 0;

// Determine the correct dashboard URL based on user authentication and role
function getCorrectDashboardURL() {
    // Check if user is authenticated
    if (isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true && isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        // User is logged in, redirect to their dashboard based on role
        $role = $_SESSION['role'];
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
    } else {
        // User is not logged in, redirect to home/landing page
        return 'index.php';
    }
}

$dashboardURL = getCorrectDashboardURL();

function truncateText(string $text, int $limit = 140): string {
    $clean = trim(strip_tags($text));
    if (strlen($clean) <= $limit) {
        return $clean;
    }
    return substr($clean, 0, max(0, $limit - 3)) . '...';
}

if ($searchTerm !== '') {
    try {
        $like = '%' . $searchTerm . '%';

        // Fetch vendors that match the term
        $vendorSql = "SELECT id,
                              COALESCE(business_name, username) AS name,
                              profile_image,
                              business_logo,
                              address,
                              bio
                       FROM users
                       WHERE role = 'vendor'
                         AND (business_name LIKE ? OR username LIKE ? OR address LIKE ?)
                       ORDER BY name ASC";
        $vendorStmt = $conn->prepare($vendorSql);
        if (!$vendorStmt) {
            throw new Exception('Failed to prepare vendor search query: ' . $conn->error);
        }

        $vendorStmt->bind_param('sss', $like, $like, $like);
        $vendorStmt->execute();
        $vendorResult = $vendorStmt->get_result();

        while ($vendorRow = $vendorResult->fetch_assoc()) {
            $vendorSections[$vendorRow['id']] = [
                'info' => $vendorRow,
                'products' => []
            ];
        }
        $totalVendorsMatched = count($vendorSections);
        $vendorStmt->close();

        // Fetch foods/products that match the term (and include vendor info)
        $productSql = "SELECT p.id,
                              p.product_name,
                              p.description,
                              p.price,
                              p.image,
                              u.id AS vendor_id,
                              COALESCE(u.business_name, u.username) AS vendor_name,
                              u.profile_image AS vendor_image,
                              u.business_logo,
                              u.address AS vendor_address
                       FROM products p
                       JOIN users u ON p.vendor_id = u.id
                       WHERE p.is_available = 1
                         AND (p.product_name LIKE ? OR p.description LIKE ? OR u.business_name LIKE ?)
                       ORDER BY vendor_name ASC, p.product_name ASC";
        $productStmt = $conn->prepare($productSql);
        if (!$productStmt) {
            throw new Exception('Failed to prepare product search query: ' . $conn->error);
        }

        $productStmt->bind_param('sss', $like, $like, $like);
        $productStmt->execute();
        $productResult = $productStmt->get_result();

        while ($productRow = $productResult->fetch_assoc()) {
            $vendorId = (int) $productRow['vendor_id'];
            if (!isset($vendorSections[$vendorId])) {
                $vendorSections[$vendorId] = [
                    'info' => [
                        'id' => $vendorId,
                        'name' => $productRow['vendor_name'],
                        'profile_image' => $productRow['vendor_image'],
                        'business_logo' => $productRow['business_logo'],
                        'address' => $productRow['vendor_address'],
                        'bio' => ''
                    ],
                    'products' => []
                ];
            }

            $vendorSections[$vendorId]['products'][] = [
                'id' => $productRow['id'],
                'product_name' => $productRow['product_name'],
                'description' => $productRow['description'],
                'price' => $productRow['price'],
                'image' => $productRow['image']
            ];
            $totalFoodsMatched++;
        }
        $productStmt->close();

        // Sort vendor sections alphabetically
        uasort($vendorSections, function ($a, $b) {
            return strcasecmp($a['info']['name'] ?? '', $b['info']['name'] ?? '');
        });
    } catch (Exception $e) {
        $errorMessage = $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sarap Local Smart Search</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Pacifico&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-dhn8a1xJYkZrGQIOzBSSMSmc5QwDFi1Cdm42Hcps225y7sY9qsK0kGugHgdGXNqBJ38qJNmPR9U1FVLtZLk1NA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        :root {
            color-scheme: light;
            --brand-primary: #f97316;
            --brand-primary-dark: #ea580c;
            --brand-light: #fed7aa;
            --brand-bg: #fff7ed;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-300: #cbd5f5;
            --gray-500: #64748b;
            --gray-700: #334155;
            --radius-lg: 1.125rem;
            --success: #10b981;
            --warning: #f59e0b;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8fafc;
            color: var(--gray-700);
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .page-shell {
            max-width: min(1100px, 100%);
            margin: 0 auto;
            padding: clamp(16px, 4vw, 32px);
        }

        header {
            background: linear-gradient(135deg, #fb923c 0%, #f97316 50%, #ea580c 100%);
            border-radius: var(--radius-lg);
            padding: 40px 32px;
            color: white;
            box-shadow: 0 24px 50px rgba(249, 115, 22, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            position: relative;
            overflow: hidden;
        }

        header::before {
            content: '🍽️';
            position: absolute;
            font-size: 120px;
            opacity: 0.08;
            top: -20px;
            right: -20px;
            transform: rotate(-15deg);
        }

        header::after {
            content: '';
            position: absolute;
            inset: -40% auto auto -10%;
            width: 280px;
            height: 280px;
            background: rgba(255, 255, 255, 0.1);
            filter: blur(40px);
            transform: rotate(35deg);
        }

        .brand-badge {
            font-family: 'Pacifico', cursive;
            font-size: 1.75rem;
            margin-bottom: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .brand-badge img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 999px;
            border: 2px solid rgba(255, 255, 255, 0.6);
        }

        .search-card {
            margin-top: clamp(16px, 3vw, 32px);
            background: white;
            border-radius: var(--radius-lg);
            padding: clamp(16px, 3vw, 28px);
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        .search-form {
            display: flex;
            gap: 12px;
            position: relative;
            flex-wrap: wrap;
        }

        .search-form input[type="text"],
        .search-form button {
            min-height: 52px;
        }

        .search-form input[type="text"] {
            flex: 1;
            padding: 16px 18px 16px 48px;
            border-radius: 16px;
            border: 2px solid transparent;
            background: var(--gray-50);
            font-size: 1rem;
            transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
        }

        .search-form input[type="text"]:focus {
            outline: none;
            border-color: rgba(249, 115, 22, 0.4);
            background: white;
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .search-form button {
            border: none;
            background: var(--brand-primary);
            color: white;
            padding: 0 28px;
            border-radius: 16px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: transform 0.2s, background 0.2s;
        }

        .search-form button:focus-visible {
            outline: 3px solid rgba(249, 115, 22, 0.4);
            outline-offset: 3px;
        }

        .search-form button:hover {
            background: var(--brand-primary-dark);
            transform: translateY(-1px);
        }

        .search-icon {
            position: absolute;
            top: 50%;
            left: 20px;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1rem;
        }

        .suggestions-panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: clamp(16px, 20vw, 180px);
            background: white;
            border-radius: 18px;
            box-shadow: 0 30px 60px rgba(15, 23, 42, 0.18);
            border: 1px solid rgba(15, 23, 42, 0.08);
            max-height: min(360px, 60vh);
            overflow-y: auto;
            z-index: 10;
            display: none;
        }

        .suggestions-panel.visible {
            display: block;
        }

        .suggestion-group {
            padding: 12px 0;
        }

        .suggestion-label {
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--gray-500);
            padding: 0 20px 8px;
        }

        .suggestion-item {
            width: 100%;
            border: none;
            background: transparent;
            padding: 10px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
            text-align: left;
            font: inherit;
        }

        .suggestion-item:hover {
            background: rgba(249, 115, 22, 0.06);
        }

        .suggestion-thumb {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: var(--gray-100);
            overflow: hidden;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .suggestion-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .suggestion-text {
            flex: 1;
        }

        .suggestion-title {
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--gray-700);
        }

        .suggestion-meta {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .suggestion-empty {
            padding: 16px 20px;
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .suggestion-loading {
            display: none;
            padding: 16px;
            align-items: center;
            gap: 10px;
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .suggestion-loading.visible {
            display: flex;
        }

        .spinner {
            width: 18px;
            height: 18px;
            border: 3px solid rgba(249, 115, 22, 0.2);
            border-top-color: var(--brand-primary);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .results-meta {
            margin-top: 18px;
            font-size: 0.95rem;
            color: var(--gray-500);
        }

        .results-meta strong {
            color: var(--brand-primary-dark);
        }

        .results-grid {
            margin-top: 32px;
            display: grid;
            gap: clamp(16px, 3vw, 24px);
        }

        .vendor-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: clamp(18px, 3vw, 28px);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            border: 2px solid rgba(249, 115, 22, 0.1);
            transition: all 0.3s ease;
            position: relative;
        }

        .vendor-card::before {
            content: '✨';
            position: absolute;
            top: 12px;
            right: 16px;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .vendor-card:hover {
            border-color: var(--brand-light);
            box-shadow: 0 24px 48px rgba(249, 115, 22, 0.15);
        }

        .vendor-card:hover::before {
            opacity: 1;
        }

        .vendor-header {
            display: flex;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }

        .vendor-avatar {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            overflow: hidden;
            background: linear-gradient(135deg, var(--brand-light), var(--brand-bg));
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid var(--brand-light);
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.15);
        }

        .vendor-card:hover .vendor-avatar {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.25);
        }

        .vendor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vendor-info h3 {
            margin: 0;
            font-size: 1.3rem;
            color: var(--gray-700);
        }

        .vendor-info p {
            margin: 6px 0 0;
            font-size: 0.9rem;
            color: var(--gray-500);
        }

        .vendor-products {
            margin-top: 20px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }

        .product-card {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 18px;
            overflow: hidden;
            background: white;
            display: flex;
            flex-direction: column;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 24px rgba(249, 115, 22, 0.15);
            border-color: var(--brand-light);
        }

        .product-thumb {
            width: 100%;
            height: 150px;
            background: var(--gray-100);
            position: relative;
            overflow: hidden;
        }

        .product-thumb::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(249, 115, 22, 0.1) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-card:hover .product-thumb::after {
            opacity: 1;
        }

        .product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .product-card:hover .product-thumb img {
            transform: scale(1.05);
        }

        .product-body {
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .product-title {
            font-weight: 600;
            color: var(--gray-700);
            font-size: 1rem;
            margin: 0;
        }

        .product-description {
            font-size: 0.9rem;
            color: var(--gray-500);
            margin: 0;
            flex: 1;
        }

        .product-price {
            font-weight: 700;
            color: var(--brand-primary);
            font-size: 1rem;
            margin-bottom: 12px;
        }

        .product-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
        }

        .btn-order {
            flex: 1;
            padding: 10px 12px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-order::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .btn-order:hover::before {
            left: 100%;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.3);
        }

        .btn-order:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2);
        }

        .btn-order i {
            position: relative;
            z-index: 1;
        }

        .vendor-actions {
            margin-top: 16px;
            display: flex;
            gap: 12px;
        }

        .btn-visit {
            flex: 1;
            padding: 12px 16px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.2);
            position: relative;
            overflow: hidden;
        }

        .btn-visit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .btn-visit:hover::before {
            left: 100%;
        }

        .btn-visit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(249, 115, 22, 0.3);
        }

        .btn-visit:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.2);
        }

        .btn-visit i {
            position: relative;
            z-index: 1;
        }

        .empty-state {
            margin-top: 32px;
            text-align: center;
            padding: 48px;
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.5), rgba(254, 215, 170, 0.3));
            border-radius: var(--radius-lg);
            border: 2px dashed var(--brand-light);
            color: var(--gray-500);
            transition: all 0.3s ease;
        }

        .empty-state:hover {
            border-color: var(--brand-primary);
            background: linear-gradient(135deg, rgba(255, 247, 237, 0.8), rgba(254, 215, 170, 0.5));
        }

        .error-state {
            margin-top: 24px;
            padding: 16px;
            border-radius: 12px;
            background: #fee2e2;
            color: #b91c1c;
        }

        .nav-buttons {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .btn-nav {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 18px;
            background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
            color: white;
            border: 2px solid transparent;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25);
        }

        .btn-nav::before {
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

        .btn-nav:hover::before {
            left: 100%;
        }

        .btn-nav:hover {
            background: linear-gradient(135deg, var(--brand-primary-dark), var(--brand-primary));
            gap: 12px;
            box-shadow: 0 6px 20px rgba(249, 115, 22, 0.35);
            transform: translateY(-2px);
        }

        .btn-nav:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(249, 115, 22, 0.25);
        }

        .btn-nav:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.25), 0 0 0 3px rgba(249, 115, 22, 0.15);
        }

        .btn-nav i {
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .btn-nav:hover i {
            transform: translateX(-2px);
        }

        .btn-nav span {
            position: relative;
            z-index: 1;
        }

        .btn-nav-secondary {
            background: linear-gradient(135deg, #e5e7eb, #d1d5db);
            color: var(--gray-700);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .btn-nav-secondary:hover {
            background: linear-gradient(135deg, #d1d5db, #bfdbfe);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-nav-secondary i {
            transform: none;
        }

        .btn-nav-secondary:hover i {
            transform: translateX(-3px);
        }

        @media (max-width: 768px) {
            header {
                padding: 24px;
            }

            .search-form {
                flex-direction: column;
            }

            .search-form input[type="text"] {
                padding-left: 48px;
            }

            .search-form button {
                width: 100%;
                justify-content: center;
            }

            .suggestions-panel {
                position: static;
                width: 100%;
                margin-top: 12px;
                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.12);
            }

            .results-grid {
                grid-template-columns: 1fr;
            }

            .vendor-products {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            }
        }

        @media (min-width: 1200px) {
            .results-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
</head>

<body>
    <div class="page-shell">
        <header>
            <div class="nav-buttons">
                <button class="btn-nav btn-nav-secondary" onclick="window.history.back()" title="Go back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back</span>
                </button>
                <a href="<?php echo htmlspecialchars($dashboardURL); ?>" class="btn-nav" title="Go to home">
                    <i class="fas fa-home"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="brand-badge">
                <img src="images/S.png" alt="Sarap Local logo">
                Sarap Local Smart Search
            </div>
            <p style="max-width: 540px; font-size: 1.05rem; margin: 0;">
                Discover local vendors and their best-selling dishes in seconds. Type to explore, get instant suggestions, and jump straight to the flavors you crave.
            </p>
        </header>

        <section class="search-card">
            <form class="search-form" method="GET" action="search.php" autocomplete="off">
                <span class="search-icon"><i class="fas fa-search"></i></span>
                <input type="text" id="searchInput" name="q" placeholder="Search for food names or vendors"
                    value="<?php echo htmlspecialchars($searchTerm); ?>" aria-label="Search for food names or vendors" required>
                <button type="submit" aria-label="Search">
                    <i class="fas fa-search"></i>
                    <span>Search</span>
                </button>

                <div id="suggestionsPanel" class="suggestions-panel" role="listbox" aria-label="Search suggestions">
                    <div id="suggestionsLoading" class="suggestion-loading">
                        <span class="spinner" aria-hidden="true"></span>
                        <span>Finding tasty matches...</span>
                    </div>
                    <div id="suggestionsContent"></div>
                </div>
            </form>
            <?php if ($searchTerm): ?>
                <div class="results-meta">
                    Showing <strong><?php echo $totalVendorsMatched; ?></strong> vendors and
                    <strong><?php echo $totalFoodsMatched; ?></strong> dishes for "<?php echo htmlspecialchars($searchTerm); ?>".
                </div>
            <?php else: ?>
                <div class="results-meta">
                    Start typing to get instant suggestions for food and vendors.
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="error-state">
                    <strong>Search error:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="results-grid" aria-live="polite">
            <?php if ($searchTerm === ''): ?>
                <div class="empty-state">
                    <i class="fas fa-bowl-food" style="font-size: 2rem; margin-bottom: 12px;"></i>
                    <p style="margin: 0; font-size: 1.05rem;">Search above to see vendors and dishes.</p>
                </div>
            <?php elseif (!$vendorSections): ?>
                <div class="empty-state">
                    <i class="fas fa-magnifying-glass-minus" style="font-size: 2rem; margin-bottom: 12px;"></i>
                    <p style="margin: 0; font-size: 1.05rem;">No results found for "<?php echo htmlspecialchars($searchTerm); ?>". Try another keyword.</p>
                </div>
            <?php else: ?>
                <?php foreach ($vendorSections as $section): $info = $section['info']; ?>
                    <article class="vendor-card">
                        <div class="vendor-header">
                            <div class="vendor-avatar">
                                <?php if (!empty($info['profile_image']) && file_exists(__DIR__ . '/' . $info['profile_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($info['profile_image']); ?>" alt="<?php echo htmlspecialchars($info['name']); ?> logo">
                                <?php elseif (!empty($info['business_logo']) && file_exists(__DIR__ . '/' . $info['business_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($info['business_logo']); ?>" alt="<?php echo htmlspecialchars($info['name']); ?> logo">
                                <?php else: ?>
                                    <i class="fas fa-store" style="font-size: 1.5rem; color: var(--brand-primary);"></i>
                                <?php endif; ?>
                            </div>
                            <div class="vendor-info">
                                <h3><?php echo htmlspecialchars($info['name'] ?? 'Vendor'); ?></h3>
                                <?php if (!empty($info['address'])): ?>
                                    <p><i class="fas fa-location-dot"></i> <?php echo htmlspecialchars($info['address']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($info['bio'])): ?>
                                    <p style="margin-top: 4px;"><?php echo htmlspecialchars(truncateText($info['bio'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="vendor-actions">
                            <a href="vendor_profile.php?id=<?php echo $info['id']; ?>" class="btn-visit">
                                <i class="fas fa-store"></i>
                                <span>View Profile</span>
                            </a>
                        </div>

                        <?php if (!empty($section['products'])): ?>
                            <div class="vendor-products">
                                <?php foreach ($section['products'] as $product): ?>
                                    <div class="product-card">
                                        <div class="product-thumb">
                                            <?php if (!empty($product['image']) && file_exists(__DIR__ . '/' . $product['image'])): ?>
                                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                            <?php else: ?>
                                                <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; color: var(--gray-300);">
                                                    <i class="fas fa-utensils" style="font-size: 1.5rem;"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="product-body">
                                            <p class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                            <?php if (!empty($product['description'])): ?>
                                                <p class="product-description"><?php echo htmlspecialchars(truncateText($product['description'])); ?></p>
                                            <?php endif; ?>
                                            <div class="product-price">₱<?php echo number_format((float) $product['price'], 2); ?></div>
                                            <div class="product-actions">
                                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-order">
                                                    <i class="fas fa-shopping-cart"></i>
                                                    <span>Order Now</span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p style="margin-top: 18px; font-size: 0.95rem; color: var(--gray-500);">No dishes matched this vendor, but you can view their menu inside the app.</p>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <script>
        (function () {
            const input = document.getElementById('searchInput');
            const panel = document.getElementById('suggestionsPanel');
            const content = document.getElementById('suggestionsContent');
            const loadingRow = document.getElementById('suggestionsLoading');
            let abortController = null;

            const debounce = (fn, delay = 200) => {
                let timer;
                return (...args) => {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(null, args), delay);
                };
            };

            const escapeHtml = (unsafe = '') => unsafe
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');

            const hidePanel = () => {
                panel.classList.remove('visible');
            };

            const showPanel = () => {
                panel.classList.add('visible');
            };

            const renderSuggestions = (data, term) => {
                const foods = Array.isArray(data?.foods) ? data.foods : [];
                const vendors = Array.isArray(data?.vendors) ? data.vendors : [];

                if (!foods.length && !vendors.length) {
                    content.innerHTML = `<div class="suggestion-empty">No matches for "${escapeHtml(term)}"</div>`;
                    return;
                }

                let html = '';

                if (foods.length) {
                    html += '<div class="suggestion-group">';
                    html += '<div class="suggestion-label">Foods</div>';
                    foods.forEach(food => {
                        const price = Number(food.price || 0).toFixed(2);
                        const description = food.description ? escapeHtml(food.description.substring(0, 60)) + '…' : '';
                        html += `<a href="product.php?id=${food.id}" class="suggestion-item" style="display: flex; align-items: center; gap: 12px;">
                                    <span class="suggestion-thumb">${food.image ? `<img src="${escapeHtml(food.image)}" alt="">` : '<i class="fas fa-bowl-food"></i>'}</span>
                                    <span class="suggestion-text">
                                        <span class="suggestion-title">${escapeHtml(food.product_name)}</span>
                                        <span class="suggestion-meta">${escapeHtml(food.vendor_name || '')} • ₱${price}</span>
                                        ${description ? `<span class="suggestion-meta">${description}</span>` : ''}
                                    </span>
                                </a>`;
                    });
                    html += '</div>';
                }

                if (vendors.length) {
                    html += '<div class="suggestion-group">';
                    html += '<div class="suggestion-label">Vendors</div>';
                    vendors.forEach(vendor => {
                        html += `<a href="vendor_profile.php?id=${vendor.id}" class="suggestion-item" style="display: flex; align-items: center; gap: 12px;">
                                    <span class="suggestion-thumb">${vendor.profile_image ? `<img src="${escapeHtml(vendor.profile_image)}" alt="">` : '<i class="fas fa-store"></i>'}</span>
                                    <span class="suggestion-text">
                                        <span class="suggestion-title">${escapeHtml(vendor.name)}</span>
                                        <span class="suggestion-meta">${escapeHtml(vendor.address || 'Local vendor')}</span>
                                    </span>
                                </a>`;
                    });
                    html += '</div>';
                }

                content.innerHTML = html;
            };

            const fetchSuggestions = debounce(async () => {
                const term = input.value.trim();
                if (term.length < 2) {
                    hidePanel();
                    return;
                }

                showPanel();
                loadingRow.classList.add('visible');
                content.innerHTML = '';

                if (abortController) {
                    abortController.abort();
                }
                abortController = new AbortController();

                try {
                    const response = await fetch(`suggestions.php?term=${encodeURIComponent(term)}`, {
                        signal: abortController.signal,
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Unable to fetch suggestions');
                    }

                    const payload = await response.json();
                    if (payload.success === false) {
                        throw new Error(payload.message || 'Search failed');
                    }

                    renderSuggestions(payload, term);
                } catch (error) {
                    if (error.name === 'AbortError') {
                        return;
                    }
                    content.innerHTML = `<div class="suggestion-empty">${escapeHtml(error.message || 'Unable to fetch suggestions')}</div>`;
                } finally {
                    loadingRow.classList.remove('visible');
                }
            }, 250);

            input.addEventListener('input', fetchSuggestions);
            input.addEventListener('focus', () => {
                if (input.value.trim().length >= 2) {
                    fetchSuggestions();
                }
            });

            content.addEventListener('click', (event) => {
                const button = event.target.closest('.suggestion-item');
                if (!button) return;
                const value = button.getAttribute('data-value') || '';
                input.value = value;
                input.focus();
                hidePanel();
            });

            document.addEventListener('click', (event) => {
                if (!panel.contains(event.target) && event.target !== input) {
                    hidePanel();
                }
            });
        })();
    </script>
</body>

</html>
