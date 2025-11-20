<?php
session_start();
require_once 'db.php';

// Disable all caching for dynamic content
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() - 3600) . ' GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('ETag: ' . md5(time()));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id === 0) {
    header('Location: customer.php');
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT p.*, u.business_name, u.profile_image, u.id as vendor_id
        FROM products p
        JOIN users u ON p.vendor_id = u.id
        WHERE p.id = ? AND u.role = 'vendor'
    ");
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        header('Location: customer.php');
        exit;
    }
    
    $product = $result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    header('Location: customer.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> - Sarap Local</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
    <header class="brand-header shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="customer.php" class="flex items-center">
                    <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                        <img src="images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
                    </div>
                    <div class="flex flex-col leading-tight">
                        <span class="text-xs uppercase tracking-[0.2em] text-orange-100">Customer</span>
                        <span class="text-xl font-semibold brand-script">Sarap Local</span>
                    </div>
                </a>
                <button onclick="window.history.back()" class="text-white hover:text-orange-100">
                    <i class="fas fa-arrow-left text-xl"></i>
                </button>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Product Image -->
            <div class="flex items-center justify-center bg-gray-100 rounded-lg overflow-hidden" style="height: 400px;">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                    <div class="flex items-center justify-center text-gray-400">
                        <i class="fas fa-utensils text-6xl"></i>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Details -->
            <div>
                <!-- Vendor Info -->
                <div class="flex items-center mb-6 pb-6 border-b border-gray-200">
                    <?php if (!empty($product['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['profile_image']); ?>" alt="Vendor" class="w-12 h-12 rounded-full object-cover mr-4">
                    <?php else: ?>
                        <div class="w-12 h-12 rounded-full bg-gray-300 flex items-center justify-center mr-4">
                            <i class="fas fa-store text-gray-600"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm text-gray-600">From</p>
                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($product['business_name']); ?></p>
                    </div>
                </div>

                <!-- Product Name & Price -->
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($product['product_name']); ?></h1>
                <p class="text-3xl font-bold text-orange-600 mb-6">₱<?php echo number_format($product['price'], 2); ?></p>

                <!-- Description -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">Description</h3>
                    <p class="text-gray-700"><?php echo htmlspecialchars($product['description']); ?></p>
                </div>

                <!-- Stock Status -->
                <div class="mb-6">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="inline-block px-4 py-2 bg-green-100 text-green-800 rounded-full text-sm font-semibold">
                            ✓ In Stock (<?php echo $product['stock_quantity']; ?> available)
                        </span>
                    <?php else: ?>
                        <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold">
                            Out of Stock
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Add to Cart -->
                <div class="flex gap-4">
                    <input type="number" id="quantity" min="1" max="<?php echo $product['stock_quantity']; ?>" value="1" 
                           class="w-20 px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500"
                           <?php echo $product['stock_quantity'] === 0 ? 'disabled' : ''; ?>>
                    <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                            class="flex-1 bg-orange-500 hover:bg-orange-600 text-white font-semibold py-3 rounded-lg transition-colors flex items-center justify-center gap-2"
                            <?php echo $product['stock_quantity'] === 0 ? 'disabled' : ''; ?>>
                        <i class="fas fa-shopping-cart"></i>
                        Add to Cart
                    </button>
                </div>

                <!-- Back to Shopping -->
                <button onclick="window.location.href='customer.php'" class="w-full mt-4 px-4 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition-colors">
                    Continue Shopping
                </button>
            </div>
        </div>
    </main>

    <script>
        function addToCart(productId) {
            const quantity = parseInt(document.getElementById('quantity').value);
            if (quantity < 1) {
                alert('Please enter a valid quantity');
                return;
            }

            const cart = JSON.parse(localStorage.getItem('cart') || '{}');
            if (!cart[productId]) {
                cart[productId] = 0;
            }
            cart[productId] += quantity;
            localStorage.setItem('cart', JSON.stringify(cart));

            alert('Added to cart!');
            window.location.href = 'customer.php#cart';
        }
    </script>
</body>
</html>
