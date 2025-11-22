<?php
require_once __DIR__ . '/includes/cache-control.php';
require_once 'db.php';
require_once 'includes/session-manager.php';
require_once 'includes/auth.php';
require_once 'includes/navigation.php';

// Initialize secure session
initializeSecureSession();

// Store current page in history for back button functionality
storeCurrentPage('Customer Dashboard');

// Require authentication and customer role
requireRole('customer');

$customer_id = $_SESSION['user_id'];

// Get customer info
try {
    $customer_query = "SELECT * FROM users WHERE id = ? AND role = 'customer'";
    $stmt = $conn->prepare($customer_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    header('Location: login.php');
    exit();
}

// Handle search and filters - Sanitize all input
// Only allow specific filter values to prevent URL injection
$search = isset($_GET['search']) ? htmlspecialchars(trim($_GET['search']), ENT_QUOTES, 'UTF-8') : '';
$category_filter = isset($_GET['category']) ? htmlspecialchars($_GET['category'], ENT_QUOTES, 'UTF-8') : '';
$vendor_filter = isset($_GET['vendor_id']) ? (int)$_GET['vendor_id'] : 0;
$price_filter = isset($_GET['price']) ? htmlspecialchars($_GET['price'], ENT_QUOTES, 'UTF-8') : '';
$cuisine_filter = isset($_GET['cuisine']) ? htmlspecialchars($_GET['cuisine'], ENT_QUOTES, 'UTF-8') : '';
$distance_filter = isset($_GET['distance']) ? htmlspecialchars($_GET['distance'], ENT_QUOTES, 'UTF-8') : '';
$ingredients_filter = isset($_GET['ingredients']) ? htmlspecialchars($_GET['ingredients'], ENT_QUOTES, 'UTF-8') : '';

// Validate sort_by to prevent injection
$allowed_sorts = ['newest', 'price_low', 'price_high', 'rating', 'distance'];
$sort_by = (isset($_GET['sort']) && in_array($_GET['sort'], $allowed_sorts)) ? $_GET['sort'] : 'newest';

// Get user's location for distance filtering (if provided) - Validate coordinates
$user_lat = isset($_GET['lat']) ? (float)$_GET['lat'] : null;
$user_lng = isset($_GET['lng']) ? (float)$_GET['lng'] : null;

// Build WHERE clause for search and filters
$where_conditions = ["p.is_available = 1"];
$params = [];
$types = "";

// Search functionality
if (!empty($search)) {
    $where_conditions[] = "(p.product_name LIKE ? OR p.description LIKE ? OR u.business_name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

// Category filter
if (!empty($category_filter)) {
    $where_conditions[] = "p.category_id = ?";
    $params[] = $category_filter;
    $types .= "i";
}

// Cuisine filter (if you have a cuisine field, otherwise we'll search in description)
if (!empty($cuisine_filter)) {
    $where_conditions[] = "(p.description LIKE ? OR p.product_name LIKE ?)";
    $cuisine_param = "%$cuisine_filter%";
    $params[] = $cuisine_param;
    $params[] = $cuisine_param;
    $types .= "ss";
}

// Price range filter
if (!empty($price_filter)) {
    switch ($price_filter) {
        case 'under_100':
            $where_conditions[] = "p.price < 100";
            break;
        case '100_300':
            $where_conditions[] = "p.price BETWEEN 100 AND 300";
            break;
        case '300_500':
            $where_conditions[] = "p.price BETWEEN 300 AND 500";
            break;
        case 'over_500':
            $where_conditions[] = "p.price > 500";
            break;
    }
}

// Ingredients filter (search in description or product name)
if (!empty($ingredients_filter)) {
    $where_conditions[] = "(p.description LIKE ? OR p.product_name LIKE ?)";
    $ingredients_param = "%$ingredients_filter%";
    $params[] = $ingredients_param;
    $params[] = $ingredients_param;
    $types .= "ss";
}

if (!empty($vendor_filter)) {
    $where_conditions[] = "u.id = ?";
    $params[] = $vendor_filter;
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Build ORDER BY clause
switch ($sort_by) {
    case 'price_low': $order_by = "ORDER BY p.price ASC"; break;
    case 'price_high': $order_by = "ORDER BY p.price DESC"; break;
    case 'rating': $order_by = "ORDER BY avg_rating DESC, p.created_at DESC"; break;
    case 'distance':
        if ($user_lat && $user_lng) {
            $order_by = "ORDER BY
                (6371 * acos(cos(radians(?)) * cos(radians(u.latitude)) * cos(radians(u.longitude) - radians(?)) + sin(radians(?)) * sin(radians(u.latitude)))) ASC";
            $params[] = $user_lat;
            $params[] = $user_lng;
            $params[] = $user_lat;
            $types .= "ddd";
        } else {
            $order_by = "ORDER BY p.created_at DESC";
        }
        break;
    default: $order_by = "ORDER BY p.created_at DESC";
}

// Get products with filters
try {
    $products_query = "SELECT p.*, u.business_name as vendor_name, u.username as vendor_username,
                      c.name as category_name, u.latitude, u.longitude,
                      COALESCE(AVG(r.rating), 0) as avg_rating, COUNT(r.id) as total_reviews
                      FROM products p
                      JOIN users u ON p.vendor_id = u.id
                      LEFT JOIN categories c ON p.category_id = c.id
                      LEFT JOIN reviews r ON p.id = r.product_id
                      WHERE $where_clause
                      GROUP BY p.id
                      $order_by";

    $stmt = $conn->prepare($products_query);
    if (!$stmt) throw new Exception('Database error: ' . $conn->error);

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $products_result = $stmt->get_result();
    $products = [];
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();

} catch (Exception $e) {
    $products = [];
}

// Get categories for filter dropdown
try {
    $categories_query = "SELECT id, name FROM categories ORDER BY name";
    $categories_result = $conn->query($categories_query);
    $categories = [];
    while ($row = $categories_result->fetch_assoc()) {
        $categories[] = $row;
    }
    $categories_result->close();
} catch (Exception $e) {
    $categories = [];
}

$vendors = [];
try {
    $vendors_query = "SELECT id, COALESCE(business_name, username) AS name FROM users WHERE role = 'vendor' ORDER BY name";
    $vendors_result = $conn->query($vendors_query);
    while ($row = $vendors_result->fetch_assoc()) {
        $vendors[] = $row;
    }
    $vendors_result->close();
} catch (Exception $e) {
    $vendors = [];
}

// Get customer's favorites with details in one query
$favorites = [];
$favorite_products = [];

try {
    $favorites_query = "SELECT p.*, u.business_name as vendor_name 
                        FROM products p
                        JOIN favorites f ON p.id = f.product_id
                        JOIN users u ON p.vendor_id = u.id
                        WHERE f.customer_id = ?";
    $stmt = $conn->prepare($favorites_query);
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $favorite_products[] = $row;
        $favorites[] = $row['id']; // Keep track of IDs for the heart icon check
    }
    $stmt->close();
} catch (Exception $e) {
    // Keep empty arrays on error
    error_log("Error fetching favorites: " . $e->getMessage());
}

// Handle add to favorites (AJAX - no redirect)
if (isset($_POST['action']) && $_POST['action'] === 'add_favorite') {
    header('Content-Type: application/json');
    try {
        $product_id = (int)$_POST['product_id'];
        $favorite_query = "INSERT IGNORE INTO favorites (customer_id, product_id) VALUES (?, ?)";
        $stmt = $conn->prepare($favorite_query);
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Added to favorites']);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}

// Handle remove from favorites (AJAX - no redirect)
if (isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    header('Content-Type: application/json');
    try {
        $product_id = (int)$_POST['product_id'];
        $favorite_query = "DELETE FROM favorites WHERE customer_id = ? AND product_id = ?";
        $stmt = $conn->prepare($favorite_query);
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
        exit();
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard — Sarap Local</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php
      // Load Google Maps key from central config
      $GOOGLE_MAPS_API_KEY = '';
      if (file_exists(__DIR__ . '/app_config.php')) {
          include __DIR__ . '/app_config.php';
      }
      if (!empty($GOOGLE_MAPS_API_KEY)) {
          echo '<script src="https://maps.googleapis.com/maps/api/js?key=' . htmlspecialchars($GOOGLE_MAPS_API_KEY) . '&libraries=geometry,places&callback=initMap" async defer></script>';
      } else {
          // Leaflet fallback (no API key needed)
          echo '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';
          echo '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
          echo '<script>document.addEventListener("DOMContentLoaded", function(){ if (typeof initLeaflet === "function") initLeaflet(); });</script>';
      }
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <style>
        body {
            background-color: #f8fafc;
        }

        .content-card {
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .btn-orange {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
            color: white;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            min-height: 44px; /* Touch-friendly size */
        }

        .btn-orange:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Enhanced mobile responsiveness for buttons */
        @media (max-width: 640px) {
            .btn-orange {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
                min-height: 48px; /* Larger touch targets on mobile */
            }

            .btn-orange i {
                font-size: 1rem;
            }

            /* Ensure buttons in cards have proper touch targets */
            .product-card button {
                min-height: 44px;
                padding: 0.75rem 1rem;
            }
        }

        .form-control {
            background-color: #ffffff;
            border-color: #e2e8f0;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-orange);
        }

        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--primary-orange);
        }

        .product-card {
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        /* Enhanced responsive button styling for customer page */
        .btn-responsive {
            @apply px-4 py-2 text-sm font-medium rounded-lg transition-colors min-h-[44px];
        }

        /* Mobile-first responsive button adjustments */
        @media (max-width: 640px) {
            .btn-responsive {
                @apply px-3 py-2 text-xs min-h-[48px] min-w-[48px];
            }

            /* Ensure buttons in cards have proper touch targets */
            .product-card button {
                @apply min-h-[44px] px-3 py-2 text-sm;
            }

            /* Header buttons on mobile */
            .flex.items-center.space-x-4 button {
                @apply min-h-[40px] px-2 py-1 text-xs;
            }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="brand-header shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <a href="index.php" class="flex items-center">
                    <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                        <img src="images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs uppercase tracking-[0.2em] text-orange-100">Customer</span>
                        <span class="text-xl font-semibold brand-script">Sarap Local</span>
                    </div>
                </a>

                <!-- User Actions -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Search -->
                    <a href="search.php" class="relative text-white hover:text-orange-100 transition-colors p-1" title="Search">
                        <i class="fas fa-search text-lg sm:text-xl"></i>
                    </a>

                    <!-- Profile (Direct Link) -->
                    <a href="profile.php" class="relative text-white hover:text-orange-100 transition-colors p-1">
                        <i class="fas fa-user-circle text-lg sm:text-xl"></i>
                    </a>
                    <!-- Cart -->
                    <a href="#cart" class="relative text-white hover:text-orange-100 transition-colors p-1">
                        <i class="fas fa-shopping-cart text-lg sm:text-xl"></i>
                        <span id="cartBadge" class="cart-count absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-orange-500 text-white text-[10px] sm:text-xs rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center hidden">0</span>
                    </a>

                    <!-- Reels -->
                    <a href="reels.php" class="relative text-white hover:text-orange-100 transition-colors p-1" title="Food Reels">
                        <i class="fas fa-film text-lg sm:text-xl"></i>
                    </a>

                    <!-- Favorites -->
                    <a href="#favorites" class="relative text-white hover:text-orange-100 transition-colors p-1">
                        <i class="fas fa-heart text-lg sm:text-xl"></i>
                        <?php if (count($favorites) > 0): ?>
                            <span class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-orange-500 text-white text-[10px] sm:text-xs rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center">
                                <?php echo count($favorites); ?>
                            </span>
                        <?php endif; ?>
                    </a>

                    <!-- Messages -->
                    <a href="chat.php" class="relative text-white hover:text-orange-100 transition-colors p-1">
                        <i class="fas fa-comments text-lg sm:text-xl"></i>
                        <span id="messageBadge" class="absolute -top-1 -right-1 sm:-top-2 sm:-right-2 bg-red-500 text-white text-[10px] sm:text-xs rounded-full h-4 w-4 sm:h-5 sm:w-5 flex items-center justify-center hidden">0</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full">
        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
            </div>
        <?php endif; ?>

        <!-- Smart Food Finder Section -->
        <section class="mb-8">
            <div class="brand-card rounded-xl shadow-sm p-6 mb-6">
                <div class="flex flex-col lg:flex-row gap-4">
                    <!-- Location Button -->
                    <div class="flex gap-2">
                        <button onclick="getCurrentLocation()" class="btn-orange px-4 py-3 rounded-lg" id="locationBtn">
                            <i class="fas fa-map-marker-alt mr-2"></i>
                            <span id="locationText">Use My Location</span>
                        </button>
                        <button onclick="toggleFilters()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-3 rounded-lg">
                            <i class="fas fa-filter mr-2"></i>
                            Filters
                        </button>
                    </div>
                </div>

                <!-- Advanced Filters Panel -->
                <div id="filtersPanel" class="hidden mt-4 pt-4 border-t border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- Category Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Vendor Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Vendor</label>
                            <select name="vendor_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <option value="">All Vendors</option>
                                <?php foreach ($vendors as $vendor): ?>
                                    <option value="<?php echo $vendor['id']; ?>"
                                            <?php echo $vendor_filter == $vendor['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($vendor['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Price Range Filter -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <select name="price" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <option value="">Any Price</option>
                                <option value="under_100" <?php echo $price_filter == 'under_100' ? 'selected' : ''; ?>>Under ₱100</option>
                                <option value="100_300" <?php echo $price_filter == '100_300' ? 'selected' : ''; ?>>₱100 - ₱300</option>
                                <option value="300_500" <?php echo $price_filter == '300_500' ? 'selected' : ''; ?>>₱300 - ₱500</option>
                                <option value="over_500" <?php echo $price_filter == 'over_500' ? 'selected' : ''; ?>>Over ₱500</option>
                            </select>
                        </div>

                        <!-- Sort Options -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort By</label>
                            <select name="sort" onchange="this.form.submit()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:border-orange-500">
                                <option value="newest" <?php echo $sort_by == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="rating" <?php echo $sort_by == 'rating' ? 'selected' : ''; ?>>Highest Rated</option>
                                <option value="distance" <?php echo $sort_by == 'distance' ? 'selected' : ''; ?>>Nearest First</option>
                            </select>
                        </div>

                        <!-- Quick Filters -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Quick Filters</label>
                            <div class="flex flex-wrap gap-2">
                                <button type="button" onclick="setQuickFilter('cuisine', 'filipino')" class="px-3 py-1 text-xs bg-orange-100 text-orange-700 rounded-full hover:bg-orange-200">Filipino</button>
                                <button type="button" onclick="setQuickFilter('cuisine', 'pizza')" class="px-3 py-1 text-xs bg-orange-100 text-orange-700 rounded-full hover:bg-orange-200">Pizza</button>
                                <button type="button" onclick="setQuickFilter('ingredients', 'spicy')" class="px-3 py-1 text-xs bg-red-100 text-red-700 rounded-full hover:bg-red-200">Spicy</button>
                                <button type="button" onclick="setQuickFilter('ingredients', 'vegetarian')" class="px-3 py-1 text-xs bg-green-100 text-green-700 rounded-full hover:bg-green-200">Vegetarian</button>
                            </div>
                        </div>
                    </div>

                    <!-- Hidden inputs for location -->
                    <input type="hidden" name="lat" id="latInput" value="">
                    <input type="hidden" name="lng" id="lngInput" value="">
                </div>
            </div>
        </section>

        <!-- Favorites Section -->
        <section id="favorites" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Your Favorites</h2>
            </div>

            <?php if (empty($favorite_products)): ?>
                <div class="brand-card rounded-lg shadow-sm p-12 text-center">
                    <i class="fas fa-heart text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No favorites yet</h3>
                    <p class="text-gray-500">Tap the heart on any product to save it here.</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($favorite_products as $product): ?>
                        <div class="product-card brand-card rounded-lg shadow-sm overflow-hidden">
                            <div class="relative">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Remove Favorite Button -->
                                <form method="POST" class="absolute top-3 right-3">
                                    <input type="hidden" name="action" value="remove_favorite">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="p-2 rounded-full text-red-500 bg-white bg-opacity-90 hover:bg-opacity-100 transition-all">
                                        <i class="fas fa-heart text-xl"></i>
                                    </button>
                                </form>
                            </div>

                            <div class="p-4">
                                <h3 class="font-bold text-lg text-gray-800 mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="text-gray-600 text-sm mb-2">by <?php echo htmlspecialchars($product['vendor_name']); ?></p>
                                <p class="text-sm text-orange-600">₱<?php echo number_format($product['price'], 2); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Cart Section -->
        <section id="cart" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Your Cart</h2>
                <button id="clearCartBtn" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm" onclick="clearCart()">Clear Cart</button>
            </div>

            <div id="cartContainer" class="brand-card rounded-lg shadow-sm p-6">
                <div id="emptyCart" class="text-center text-gray-500 py-8 hidden">
                    <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                    <p>Your cart is empty.</p>
                </div>

                <div id="cartTableWrapper" class="overflow-x-auto hidden">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="cartRows" class="bg-white divide-y divide-gray-200"></tbody>
                    </table>
                    <div class="flex justify-end mt-4">
                        <button class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-lg" onclick="checkoutCart()">Checkout</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Customer Dashboard Section -->
        <section id="dashboard" class="mb-8">
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Browse Products</h2>
                <p class="text-gray-600 text-sm mt-1">Discover delicious food from local vendors</p>
            </div>

            <!-- Key Metrics Cards for Customer -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Available Products Card -->
                <div class="stats-card brand-card rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Available Products</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count($products); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-utensils text-green-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Favorite Products Card -->
                <div class="stats-card brand-card rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Favorite Products</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count($favorites); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-heart text-red-600 text-xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Categories Card -->
                <div class="stats-card bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-600 text-sm">Food Categories</p>
                            <p class="text-2xl font-bold text-gray-800"><?php echo count($categories); ?></p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-list text-blue-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Nearby Vendors Map Section -->
        <section id="vendors-map" class="mb-8">
            <div class="brand-card rounded-xl shadow-sm p-6">
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Nearby Vendors</h2>
                        <p class="text-gray-600 text-sm mt-1">Find local food vendors near you</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <label for="travelMode" class="text-sm text-gray-600">Mode:</label>
                        <select id="travelMode" onchange="setTravelMode(this.value)" class="border rounded px-2 py-1 text-sm">
                            <option value="DRIVING">Driving</option>
                            <option value="WALKING">Walking</option>
                        </select>
                        <button onclick="toggleMapView()" class="btn-orange px-4 py-2 rounded-lg">
                            <i class="fas fa-map mr-2"></i>
                            <span id="mapToggleText">Show Map</span>
                        </button>
                    </div>
                </div>

                <!-- Map Container -->
                <div id="mapContainer" class="hidden">
                    <div id="map" class="w-full h-96 rounded-lg border border-gray-300"></div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach (array_slice($products, 0, 6) as $product): ?>
                            <?php if (!empty($product['latitude']) && !empty($product['longitude'])): ?>
                                <div class="vendor-card tasty-hover bg-gray-50 rounded-lg p-4 border border-gray-200" data-lat="<?php echo $product['latitude']; ?>" data-lng="<?php echo $product['longitude']; ?>">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-orange-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-store text-orange-600"></i>
                                        </div>
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['vendor_name']); ?></h4>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                            <p class="text-xs text-orange-600">₱<?php echo number_format($product['price'], 2); ?></p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="showVendorOnMap(<?php echo $product['latitude']; ?>, <?php echo $product['longitude']; ?>, '<?php echo htmlspecialchars($product['vendor_name']); ?>')"
                                                    class="btn-orange px-3 py-1 text-sm rounded">
                                                View
                                            </button>
                                            <button onclick="routeToVendor(<?php echo $product['latitude']; ?>, <?php echo $product['longitude']; ?>, '<?php echo htmlspecialchars($product['vendor_name']); ?>')"
                                                    class="bg-white border border-orange-300 text-orange-700 hover:bg-orange-50 px-3 py-1 text-sm rounded">
                                                Route
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Map Toggle Info -->
                <div id="mapInfo" class="text-center py-8">
                    <i class="fas fa-map-marked-alt text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">Discover Nearby Vendors</h3>
                    <p class="text-gray-500 mb-4">Click "Show Map" to see local food vendors near your location</p>
                    <button onclick="getCurrentLocation()" class="btn-orange px-6 py-2 rounded-lg">
                        <i class="fas fa-location-arrow mr-2"></i>
                        Enable Location Services
                    </button>
                </div>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="mb-8">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Available Products</h2>
            </div>

            <?php if (empty($products)): ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <i class="fas fa-search text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-700 mb-2">No products found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter criteria</p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                            <div class="relative">
                                <?php if (!empty($product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>"
                                         alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                         class="w-full h-48 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                        <i class="fas fa-utensils text-4xl text-gray-400"></i>
                                    </div>
                                <?php endif; ?>

                                <!-- Favorite Button -->
                                <form method="POST" class="absolute top-3 right-3">
                                    <input type="hidden" name="action" value="<?php echo in_array($product['id'], $favorites) ? 'remove_favorite' : 'add_favorite'; ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" class="p-2 rounded-full hover:bg-white hover:bg-opacity-20 transition-all <?php echo in_array($product['id'], $favorites) ? 'text-red-500' : 'text-white'; ?>">
                                        <i class="fas fa-heart text-xl"></i>
                                    </button>
                                </form>

                                <!-- Stock Status -->
                                <?php if ($product['stock_quantity'] == 0): ?>
                                    <div class="absolute top-3 left-3 bg-red-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                        Out of Stock
                                    </div>
                                <?php elseif ($product['stock_quantity'] < 5): ?>
                                    <div class="absolute top-3 left-3 bg-orange-500 text-white px-2 py-1 rounded-full text-xs font-semibold">
                                        Low Stock
                                    </div>
                                <?php endif; ?>

                                <!-- Quick Review Button -->
                                <button onclick="showQuickReview(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')"
                                        class="absolute bottom-3 right-3 bg-white bg-opacity-90 hover:bg-opacity-100 text-gray-800 px-3 py-1 rounded-full text-xs font-medium transition-all">
                                    <i class="fas fa-star mr-1"></i>
                                    Rate
                                </button>
                            </div>

                            <div class="p-4">
                                <h3 class="font-bold text-lg text-gray-800 mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h3>
                                <p class="text-gray-600 text-sm mb-2">by <?php echo htmlspecialchars($product['vendor_name']); ?></p>

                                <?php if (!empty($product['category_name'])): ?>
                                    <p class="text-xs text-orange-600 bg-orange-50 px-2 py-1 rounded-full inline-block mb-2">
                                        <?php echo htmlspecialchars($product['category_name']); ?>
                                    </p>
                                <?php endif; ?>

                                <p class="text-sm mb-3 line-clamp-2 text-gray-600"><?php echo htmlspecialchars($product['description']); ?></p>

                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xl font-bold text-green-600">₱<?php echo number_format($product['price'], 2); ?></span>
                                    <?php if ($product['avg_rating'] > 0): ?>
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400 text-sm mr-1"></i>
                                            <span class="text-sm text-gray-600"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                            <span class="text-xs text-gray-500 ml-1">(<?php echo $product['total_reviews']; ?>)</span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="flex gap-2">
                                    <button onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')"
                                            class="flex-1 btn-orange <?php echo $product['stock_quantity'] == 0 ? 'opacity-50 cursor-not-allowed' : ''; ?> text-sm"
                                            <?php echo $product['stock_quantity'] == 0 ? 'disabled' : ''; ?>>
                                        <?php echo $product['stock_quantity'] == 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                    </button>
                                    <button onclick="showProductDetails(<?php echo $product['id']; ?>)"
                                            class="px-3 py-2 border border-gray-300 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i class="fas fa-info text-sm"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <script>
        let map, infoWindow, userMarker;
        let vendorMarkers = [];
        // Biliran Province (entire province, not just Biliran municipality)
        const BILIRAN_CENTER = { lat: 11.55, lng: 124.50 }; // province-wide center approx
        let BILIRAN_BOUNDS = null; // set per provider
        let MAP_PROVIDER = 'google';
        let currentTravelMode = 'DRIVING';
        let googleDirectionsService = null;
        let googleDirectionsRenderer = null;
        let leafletRouter = null;
        // Simple numeric bounds for clamping and input saving
        const BILIRAN_MIN_LAT = 11.20, BILIRAN_MAX_LAT = 11.85;
        const BILIRAN_MIN_LNG = 124.20, BILIRAN_MAX_LNG = 124.80;

        function clampToBiliran(lat, lng) {
            const clampedLat = Math.min(Math.max(lat, BILIRAN_MIN_LAT), BILIRAN_MAX_LAT);
            const clampedLng = Math.min(Math.max(lng, BILIRAN_MIN_LNG), BILIRAN_MAX_LNG);
            const wasClamped = (clampedLat !== lat) || (clampedLng !== lng);
            return { lat: clampedLat, lng: clampedLng, wasClamped };
        }

        function initMap() {
            try {
                // Initialize bounds once API is available
                // Province-wide bounds (covers Biliran Island and nearby islets)
                BILIRAN_BOUNDS = new google.maps.LatLngBounds(
                    new google.maps.LatLng(11.20, 124.20), // SW
                    new google.maps.LatLng(11.85, 124.80)  // NE
                );
                map = new google.maps.Map(document.getElementById('map'), {
                    center: BILIRAN_CENTER,
                    zoom: 10,
                    minZoom: 9,
                    maxZoom: 18,
                    restriction: { latLngBounds: BILIRAN_BOUNDS, strictBounds: true },
                    mapTypeControl: false,
                    streetViewControl: false,
                });
                infoWindow = new google.maps.InfoWindow();
                MAP_PROVIDER = 'google';
                googleDirectionsService = new google.maps.DirectionsService();
                googleDirectionsRenderer = new google.maps.DirectionsRenderer({ suppressMarkers: false });
                googleDirectionsRenderer.setMap(map);
                buildVendorMarkers();
            } catch (e) {
                console.error('Maps initialization failed:', e);
            }
        }

        // Leaflet fallback
        function initLeaflet() {
            try {
                MAP_PROVIDER = 'leaflet';
                const sw = [11.20, 124.20];
                const ne = [11.85, 124.80];
                BILIRAN_BOUNDS = L.latLngBounds(sw, ne);
                map = L.map('map', {
                    center: [BILIRAN_CENTER.lat, BILIRAN_CENTER.lng],
                    zoom: 10,
                    maxBounds: BILIRAN_BOUNDS,
                    minZoom: 9,
                    maxZoom: 18,
                });
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);
                // Load routing plugin for Leaflet
                const script = document.createElement('script');
                script.src = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.min.js';
                document.head.appendChild(script);
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css';
                document.head.appendChild(link);
                buildVendorMarkers();
            } catch (e) {
                console.error('Leaflet initialization failed:', e);
            }
        }

        function buildVendorMarkers() {
            // Clear existing markers
            vendorMarkers.forEach(m => m.setMap(null));
            vendorMarkers = [];

            const cards = document.querySelectorAll('.vendor-card[data-lat][data-lng]');
            cards.forEach(card => {
                const lat = parseFloat(card.getAttribute('data-lat'));
                const lng = parseFloat(card.getAttribute('data-lng'));
                if (isNaN(lat) || isNaN(lng)) return;
                const nameEl = card.querySelector('h4');
                const name = nameEl ? nameEl.textContent.trim() : 'Vendor';

                if (MAP_PROVIDER === 'google') {
                    const pos = new google.maps.LatLng(lat, lng);
                    if (!BILIRAN_BOUNDS.contains(pos)) return;
                    const marker = new google.maps.Marker({ position: pos, map, title: name });
                    marker.addListener('click', () => {
                        infoWindow.setContent(`<strong>${name}</strong>`);
                        infoWindow.open(map, marker);
                    });
                    vendorMarkers.push(marker);
                } else {
                    const pos = L.latLng(lat, lng);
                    if (!BILIRAN_BOUNDS.contains(pos)) return;
                    const marker = L.marker(pos).addTo(map).bindPopup(`<strong>${name}</strong>`);
                    vendorMarkers.push(marker);
                }
            });

            // Fit bounds to markers if any
            if (vendorMarkers.length > 0) {
                if (MAP_PROVIDER === 'google') {
                    const bounds = new google.maps.LatLngBounds();
                    vendorMarkers.forEach(m => bounds.extend(m.getPosition()));
                    const clamped = bounds.intersects(BILIRAN_BOUNDS) ? bounds : BILIRAN_BOUNDS;
                    map.fitBounds(clamped);
                } else {
                    const bounds = L.latLngBounds([]);
                    vendorMarkers.forEach(m => bounds.extend(m.getLatLng()));
                    const clamped = bounds.isValid() ? bounds : BILIRAN_BOUNDS;
                    map.fitBounds(clamped);
                }
            }
        }

        function showVendorOnMap(lat, lng, name) {
            if (!map) return;
            if (MAP_PROVIDER === 'google') {
                let pos = new google.maps.LatLng(parseFloat(lat), parseFloat(lng));
                if (!BILIRAN_BOUNDS.contains(pos)) pos = new google.maps.LatLng(BILIRAN_CENTER.lat, BILIRAN_CENTER.lng);
                map.setZoom(15);
                map.panTo(pos);
                infoWindow.setContent(`<strong>${name || 'Vendor'}</strong>`);
                infoWindow.setPosition(pos);
                infoWindow.open(map);
            } else {
                let pos = L.latLng(parseFloat(lat), parseFloat(lng));
                if (!BILIRAN_BOUNDS.contains(pos)) pos = L.latLng(BILIRAN_CENTER.lat, BILIRAN_CENTER.lng);
                map.setView(pos, 15);
                L.popup().setLatLng(pos).setContent(`<strong>${name || 'Vendor'}</strong>`).openOn(map);
            }
            // Ensure map is visible
            const container = document.getElementById('mapContainer');
            if (container && container.classList.contains('hidden')) toggleMapView();
        }

        function getCurrentLocation() {
            const btn = document.getElementById('locationBtn');
            const text = document.getElementById('locationText');
            if (!navigator.geolocation) {
                alert('Geolocation is not supported by your browser.');
                return;
            }
            text && (text.textContent = 'Locating…');
            navigator.geolocation.getCurrentPosition((pos) => {
                // Clamp and persist hidden inputs for filtering
                const result = clampToBiliran(pos.coords.latitude, pos.coords.longitude);
                const latInput = document.getElementById('latInput');
                const lngInput = document.getElementById('lngInput');
                if (latInput) latInput.value = result.lat.toFixed(6);
                if (lngInput) lngInput.value = result.lng.toFixed(6);

                if (MAP_PROVIDER === 'google') {
                    let coords = new google.maps.LatLng(result.lat, result.lng);
                    if (!map) initMap();
                    if (userMarker) userMarker.setMap(null);
                    userMarker = new google.maps.Marker({ position: coords, map, title: 'You are here', icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                        fillColor: '#A85224',
                        fillOpacity: 1,
                        strokeColor: '#7A3715',
                        strokeWeight: 2,
                    }});
                    map.setZoom(14);
                    map.panTo(coords);
                } else {
                    let coords = L.latLng(result.lat, result.lng);
                    if (!map) initLeaflet();
                    if (userMarker) { map.removeLayer(userMarker); }
                    userMarker = L.circleMarker(coords, { radius: 6, color: '#7A3715', fillColor: '#A85224', fillOpacity: 1 }).addTo(map).bindTooltip('You are here').openTooltip();
                    map.setView(coords, 14);
                }
                text && (text.textContent = 'Use My Location');
                const container = document.getElementById('mapContainer');
                if (container && container.classList.contains('hidden')) toggleMapView();
                // Notify if we had to clamp outside-province locations
                if (result.wasClamped) {
                    try {
                        const note = document.createElement('div');
                        note.className = 'fixed bottom-4 right-4 bg-orange-600 text-white px-4 py-2 rounded shadow-lg z-50';
                        note.textContent = 'Your location is outside Biliran. Showing nearest area within the province.';
                        document.body.appendChild(note);
                        setTimeout(() => note.remove(), 4000);
                    } catch (e) {}
                }
            }, (err) => {
                console.warn('Geolocation error:', err);
                alert('Unable to retrieve your location. Please allow location access.');
                text && (text.textContent = 'Use My Location');
            }, { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 });
        }

        function toggleMapView() {
            const container = document.getElementById('mapContainer');
            const toggleText = document.getElementById('mapToggleText');
            if (!container) return;
            const hidden = container.classList.toggle('hidden');
            if (!hidden) {
                toggleText && (toggleText.textContent = 'Hide Map');
                // Ensure map resizes correctly when shown
                setTimeout(() => {
                    if (!map) return;
                    if (MAP_PROVIDER === 'google') {
                        google.maps.event.trigger(map, 'resize');
                        if (BILIRAN_BOUNDS) map.fitBounds(BILIRAN_BOUNDS);
                    } else {
                        map.invalidateSize && map.invalidateSize();
                        if (BILIRAN_BOUNDS) map.fitBounds(BILIRAN_BOUNDS);
                    }
                }, 120);
            } else {
                toggleText && (toggleText.textContent = 'Show Map');
            }
        }
    </script>
    <script>
        // Guarded cart parsing to prevent blocking JS if malformed data exists
        let cart = [];
        try {
            const rawCart = localStorage.getItem('cart');
            cart = rawCart ? JSON.parse(rawCart) : [];
            if (!Array.isArray(cart)) cart = [];
        } catch (e) { cart = []; }

        function toggleProfileDropdown() {
            var dd = document.getElementById('profileDropdown');
            if (!dd) return;
            dd.classList.toggle('hidden');
        }

        function onProfileButtonClick(e) {
            if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
            toggleProfileDropdown();
        }

        function addToCart(productId, productName) {
            const existingItem = cart.find(item => item.id === productId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({ id: productId, name: productName, quantity: 1 });
            }
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartBadge();
            renderCart();
        }

        function incQty(productId) {
            const item = cart.find(i => i.id === productId);
            if (item) {
                item.quantity += 1;
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartBadge();
                renderCart();
            }
        }

        function decQty(productId) {
            const idx = cart.findIndex(i => i.id === productId);
            if (idx > -1) {
                cart[idx].quantity -= 1;
                if (cart[idx].quantity <= 0) cart.splice(idx, 1);
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartBadge();
                renderCart();
            }
        }

        function removeFromCart(productId) {
            cart = cart.filter(i => i.id !== productId);
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartBadge();
            renderCart();
        }

        function clearCart() {
            if (!cart.length) return;
            if (!confirm('Clear all items from cart?')) return;
            cart = [];
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartBadge();
            renderCart();
        }

        function updateCartBadge() {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;
            const count = cart.reduce((sum, i) => sum + (i.quantity || 0), 0);
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : String(count);
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        function renderCart() {
            const empty = document.getElementById('emptyCart');
            const tableWrap = document.getElementById('cartTableWrapper');
            const rows = document.getElementById('cartRows');
            if (!empty || !tableWrap || !rows) return;

            if (!cart || cart.length === 0) {
                empty.classList.remove('hidden');
                tableWrap.classList.add('hidden');
                rows.innerHTML = '';
                return;
            }

            empty.classList.add('hidden');
            tableWrap.classList.remove('hidden');
            rows.innerHTML = cart.map(item => `
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${escapeHtml(item.name)}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${item.quantity}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <button class="px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" onclick="decQty(${item.id})">-</button>
                        <button class="ml-1 px-2 py-1 bg-gray-100 rounded hover:bg-gray-200" onclick="incQty(${item.id})">+</button>
                        <button class="ml-2 px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200" onclick="removeFromCart(${item.id})">Remove</button>
                    </td>
                </tr>
            `).join('');
        }

        function checkoutCart() {
            if (!cart.length) { alert('Your cart is empty.'); return; }
            alert('Checkout is not yet implemented.');
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s]));
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-6 py-3 rounded-lg text-white z-50 ${
                type === 'success' ? 'bg-green-500' :
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notification.textContent = message;
            document.body.appendChild(notification);
            setTimeout(() => notification.remove(), 3000);
        }

        function updateCartCount() {
            const cartCount = cart.reduce((total, item) => total + item.quantity, 0);
            const cartBadge = document.getElementById('cartBadge');
            if (cartBadge) {
                cartBadge.textContent = cartCount;
                cartBadge.classList.toggle('hidden', cartCount === 0);
            }
        }

        function loadMessageBadge() {
            // For now, simulate message count - in production this would call an API
            const badge = document.getElementById('messageBadge');
            if (badge) {
                // Simulate some unread messages for demo
                const unreadCount = Math.floor(Math.random() * 5);
                if (unreadCount > 0) {
                    badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
            }
        }

        // Review System Functions
        let currentRating = 0;

        function showQuickReview(productId, productName) {
            const modal = document.getElementById('reviewModal');
            const title = document.getElementById('reviewModalTitle');
            const productInput = document.getElementById('reviewProductId');

            if (modal && title && productInput) {
                title.textContent = `Review: ${productName}`;
                productInput.value = productId;
                modal.classList.remove('hidden');
                currentRating = 0;
                updateRatingDisplay();
            }
        }

        function closeReviewModal() {
            const modal = document.getElementById('reviewModal');
            const form = document.getElementById('reviewForm');
            const preview = document.getElementById('photoPreview');

            if (modal) modal.classList.add('hidden');
            if (form) form.reset();
            if (preview) preview.innerHTML = '';
            currentRating = 0;
            updateRatingDisplay();
        }

        function setRating(rating) {
            currentRating = rating;
            const ratingInput = document.getElementById('reviewRating');
            if (ratingInput) ratingInput.value = rating;
            updateRatingDisplay();
        }

        function updateRatingDisplay() {
            const stars = document.querySelectorAll('#ratingStars button i');
            stars.forEach((star, index) => {
                if (index < currentRating) {
                    star.className = 'fas fa-star text-yellow-400';
                } else {
                    star.className = 'far fa-star text-gray-300';
                }
            });
        }

        function showProductDetails(productId) {
            showNotification(`Loading details for product ${productId}`, 'info');
        }

        function debounce(fn, delay) {
            let timeoutId;
            return function () {
                const args = arguments;
                const context = this;
                clearTimeout(timeoutId);
                timeoutId = setTimeout(function () {
                    fn.apply(context, args);
                }, delay);
            };
        }

        function renderLiveSearchResults(data, term) {
            const container = document.getElementById('liveSearchResults');
            const content = document.getElementById('liveSearchContent');
            if (!container || !content) return;

            if (!data || (!data.products.length && !data.vendors.length)) {
                content.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500">No results found</div>';
                container.classList.remove('hidden');
                return;
            }

            const escapeHtmlLocal = function (str) {
                return String(str).replace(/[&<>"']/g, function (s) {
                    return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s]);
                });
            };

            const highlight = function (text) {
                if (!term) return escapeHtmlLocal(text || '');
                const safe = escapeHtmlLocal(text || '');
                const pattern = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'ig');
                return safe.replace(pattern, '<mark class="bg-yellow-100 text-yellow-800">$1</mark>');
            };

            let html = '';

            if (data.products.length) {
                html += '<div class="border-b border-gray-200">';
                html += '<div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Products</div>';
                data.products.forEach(function (p) {
                    const name = highlight(p.product_name);
                    const vendorName = escapeHtmlLocal(p.vendor_name || '');
                    const price = typeof p.price !== 'undefined' ? Number(p.price).toFixed(2) : '';
                    const href = 'customer.php?search=' + encodeURIComponent(p.product_name || '') + (p.vendor_id ? '&vendor_id=' + encodeURIComponent(p.vendor_id) : '') + '#products';
                    html += '<a href="' + href + '" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">';
                    if (p.image) {
                        html += '<div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 mr-3 flex-shrink-0">';
                        html += '<img src="' + escapeHtmlLocal(p.image) + '" alt="" class="w-full h-full object-cover">';
                        html += '</div>';
                    } else {
                        html += '<div class="w-10 h-10 rounded-lg bg-gray-100 mr-3 flex items-center justify-center flex-shrink-0">';
                        html += '<i class="fas fa-utensils text-gray-400 text-sm"></i>';
                        html += '</div>';
                    }
                    html += '<div class="flex-1 min-w-0">';
                    html += '<div class="text-sm font-medium text-gray-900 truncate">' + name + '</div>';
                    if (vendorName) {
                        html += '<div class="text-xs text-gray-500 truncate">by ' + vendorName + '</div>';
                    }
                    if (price) {
                        html += '<div class="text-xs text-orange-600 mt-0.5">₱' + price + '</div>';
                    }
                    html += '</div>';
                    if (p.rating && Number(p.rating) > 0) {
                        html += '<div class="ml-3 flex items-center text-xs text-gray-500">';
                        html += '<i class="fas fa-star text-yellow-400 mr-1"></i>';
                        html += '<span>' + Number(p.rating).toFixed(1) + '</span>';
                        html += '</div>';
                    }
                    html += '</a>';
                });
                html += '</div>';
            }

            if (data.vendors.length) {
                html += '<div>';
                html += '<div class="px-4 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide">Vendors</div>';
                data.vendors.forEach(function (v) {
                    const name = highlight(v.name);
                    const address = escapeHtmlLocal(v.address || '');
                    const href = 'customer.php?vendor_id=' + encodeURIComponent(v.id) + '#products';
                    html += '<a href="' + href + '" class="flex items-center px-4 py-2 hover:bg-gray-50 transition-colors">';
                    if (v.profile_image) {
                        html += '<div class="w-10 h-10 rounded-full overflow-hidden bg-gray-100 mr-3 flex-shrink-0">';
                        html += '<img src="' + escapeHtmlLocal(v.profile_image) + '" alt="" class="w-full h-full object-cover">';
                        html += '</div>';
                    } else {
                        html += '<div class="w-10 h-10 rounded-full bg-orange-100 mr-3 flex items-center justify-center flex-shrink-0">';
                        html += '<i class="fas fa-store text-orange-500 text-sm"></i>';
                        html += '</div>';
                    }
                    html += '<div class="flex-1 min-w-0">';
                    html += '<div class="text-sm font-medium text-gray-900 truncate">' + name + '</div>';
                    if (address) {
                        html += '<div class="text-xs text-gray-500 truncate">' + address + '</div>';
                    }
                    html += '</div>';
                    html += '</a>';
                });
                html += '</div>';
            }

            content.innerHTML = html;
            container.classList.remove('hidden');
        }

        function initLiveSearch() {
            const input = document.querySelector('input[name="search"]');
            const container = document.getElementById('liveSearchResults');
            if (!input || !container) return;

            const debouncedSearch = debounce(function () {
                const term = input.value.trim();
                if (term.length < 2) {
                    container.classList.add('hidden');
                    return;
                }

                const url = new URL('api/search.php', window.location.href);
                url.searchParams.set('term', term);

                const categorySelect = document.querySelector('select[name="category"]');
                const priceSelect = document.querySelector('select[name="price"]');
                const vendorSelect = document.querySelector('select[name="vendor_id"]');

                if (categorySelect && categorySelect.value) {
                    url.searchParams.set('category', categorySelect.value);
                }
                if (priceSelect && priceSelect.value) {
                    url.searchParams.set('price', priceSelect.value);
                }
                if (vendorSelect && vendorSelect.value) {
                    url.searchParams.set('vendor_id', vendorSelect.value);
                }

                fetch(url.toString())
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        if (!data || data.success === false) {
                            return;
                        }
                        renderLiveSearchResults(data, term);
                    })
                    .catch(function () {
                        container.classList.add('hidden');
                    });
            }, 250);

            input.addEventListener('input', debouncedSearch);
            input.addEventListener('focus', function () {
                if (input.value.trim().length >= 2 && !container.classList.contains('hidden')) {
                    container.classList.remove('hidden');
                }
            });

            document.addEventListener('click', function (e) {
                if (!container.contains(e.target) && e.target !== input) {
                    container.classList.add('hidden');
                }
            });
        }

        // Initialize functions when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Update cart count on page load
            updateCartCount();

            // Load message badge
            loadMessageBadge();

            // Refresh message badge every 30 seconds
            setInterval(loadMessageBadge, 30000);

            // Initialize cart UI
            updateCartBadge();
            renderCart();

            // Close dropdowns when clicking outside
            document.addEventListener('click', (e) => {
                const profileDropdown = document.getElementById('profileDropdown');
                const profileButton = document.querySelector('[onclick="onProfileButtonClick(event)"]');
                if (!profileDropdown) return;
                const clickedInsideDropdown = profileDropdown.contains(e.target);
                const clickedProfileButton = profileButton ? profileButton.contains(e.target) : false;
                if (!clickedInsideDropdown && !clickedProfileButton) {
                    profileDropdown.classList.add('hidden');
                }
            });

            // Handle anchor clicks for smooth scrolling
            document.querySelectorAll('a[href^="#"]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetId = link.getAttribute('href');
                    if (targetId === '#') return;

                    const targetElement = document.querySelector(targetId);
                    if (targetElement) {
                        targetElement.scrollIntoView({ behavior: 'smooth' });
                    }
                });
            });
            
            initLiveSearch(); // Initialize live search
        });

        // Smart Food Finder Functions
        function toggleFilters() {
            const filtersPanel = document.getElementById('filtersPanel');
            if (filtersPanel) {
                filtersPanel.classList.toggle('hidden');
            }
        }

        function setQuickFilter(type, value) {
            let currentUrl = new URL(window.location);

            if (type === 'cuisine') {
                currentUrl.searchParams.set('cuisine', value);
                currentUrl.searchParams.delete('ingredients');
            } else if (type === 'ingredients') {
                currentUrl.searchParams.set('ingredients', value);
                currentUrl.searchParams.delete('cuisine');
            }

            window.location.href = currentUrl.toString();
        }

        // Map Functions
        let map = null;
        let userMarker = null;
        let vendorMarkers = [];

        function toggleMapView() {
            const mapContainer = document.getElementById('mapContainer');
            const mapInfo = document.getElementById('mapInfo');
            const toggleText = document.getElementById('mapToggleText');

            if (mapContainer && mapInfo && toggleText) {
                if (mapContainer.classList.contains('hidden')) {
                    mapContainer.classList.remove('hidden');
                    mapInfo.classList.add('hidden');
                    toggleText.textContent = 'Hide Map';
                    initializeMap();
                } else {
                    mapContainer.classList.add('hidden');
                    mapInfo.classList.remove('hidden');
                    toggleText.textContent = 'Show Map';
                }
            }
        }

        function initializeMap() {
            if (map || !window.google) return; // Map already initialized or Google Maps not loaded

            const userLat = parseFloat(document.getElementById('latInput')?.value || 0);
            const userLng = parseFloat(document.getElementById('lngInput')?.value || 0);

            // Default to Manila if no location
            const defaultLat = 14.5995;
            const defaultLng = 120.9842;

            const centerLat = userLat || defaultLat;
            const centerLng = userLng || defaultLng;

            try {
                map = new google.maps.Map(document.getElementById('map'), {
                    center: { lat: centerLat, lng: centerLng },
                    zoom: 13,
                    styles: [
                        {
                            featureType: 'poi',
                            elementType: 'labels',
                            stylers: [{ visibility: 'off' }]
                        }
                    ]
                });

                // Add user location marker if available
                if (userLat && userLng) {
                    userMarker = new google.maps.Marker({
                        position: { lat: userLat, lng: userLng },
                        map: map,
                        title: 'Your Location',
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="20" cy="20" r="18" fill="#f97316" stroke="#ffffff" stroke-width="4"/>
                                    <circle cx="20" cy="20" r="8" fill="#ffffff"/>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(40, 40)
                        }
                    });
                }

                // Add vendor markers
                addVendorMarkers();
            } catch (error) {
                console.error('Error initializing map:', error);
                showNotification('Error loading map. Please check your internet connection.', 'error');
            }
        }

        function addVendorMarkers() {
            if (!map) return;

            // Clear existing markers
            vendorMarkers.forEach(marker => marker.setMap(null));
            vendorMarkers = [];

            // Add markers for vendors with location data
            document.querySelectorAll('.vendor-card[data-lat][data-lng]').forEach(card => {
                const lat = parseFloat(card.dataset.lat);
                const lng = parseFloat(card.dataset.lng);
                const vendorName = card.querySelector('h4')?.textContent || 'Vendor';

                if (lat && lng && !isNaN(lat) && !isNaN(lng)) {
                    try {
                        const marker = new google.maps.Marker({
                            position: { lat: lat, lng: lng },
                            map: map,
                            title: vendorName,
                            icon: {
                                url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                    <svg width="30" height="30" viewBox="0 0 30 30" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="15" cy="15" r="13" fill="#22c55e" stroke="#ffffff" stroke-width="2"/>
                                        <path d="M15 8 L18 14 L15 20 L12 14 Z" fill="#ffffff"/>
                                    </svg>
                                `),
                                scaledSize: new google.maps.Size(30, 30)
                            }
                        });

                        // Add info window
                        const infoWindow = new google.maps.InfoWindow({
                            content: `
                                <div class="p-2">
                                    <h4 class="font-semibold text-gray-800">${vendorName}</h4>
                                    <p class="text-sm text-gray-600">Click "View" to see details</p>
                                </div>
                            `
                        });

                        marker.addListener('click', () => {
                            infoWindow.open(map, marker);
                        });

                        vendorMarkers.push(marker);
                    } catch (error) {
                        console.error('Error adding vendor marker:', error);
                    }
                }
            });
        }

        function showVendorOnMap(lat, lng, vendorName) {
            if (map && lat && lng) {
                try {
                    map.setCenter({ lat: lat, lng: lng });
                    map.setZoom(16);

                    // Find and highlight the marker
                    const marker = vendorMarkers.find(m => {
                        const pos = m.getPosition();
                        return pos && pos.lat() === lat && pos.lng() === lng;
                    });

                    if (marker) {
                        new google.maps.InfoWindow({
                            content: `
                                <div class="p-3">
                                    <h4 class="font-semibold text-gray-800 mb-2">${vendorName}</h4>
                                    <p class="text-sm text-gray-600">Great local food nearby!</p>
                                </div>
                            `
                        }).open(map, marker);
                    }
                } catch (error) {
                    console.error('Error showing vendor on map:', error);
                }
            }
        }

    </script>
    <script src="js/realtime-updates.js?v=<?php echo time(); ?>"></script>
    <script src="js/session-manager.js?v=<?php echo time(); ?>"></script>
</body>
</html>
