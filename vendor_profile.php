<?php
session_start();
require_once 'db.php';
require_once __DIR__ . '/includes/cache-control.php';

$asset_version = time();

// Get vendor ID from URL
$vendor_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($vendor_id <= 0) {
    header('Location: search.php');
    exit();
}

// Fetch vendor information (only public data)
try {
    $vendor_query = "SELECT id, username, business_name, profile_image, business_logo, 
                            bio, address, phone, created_at
                     FROM users 
                     WHERE id = ? AND role = 'vendor'";
    $stmt = $conn->prepare($vendor_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $vendor_result = $stmt->get_result();
    
    if ($vendor_result->num_rows === 0) {
        header('Location: search.php?error=vendor_not_found');
        exit();
    }
    
    $vendor = $vendor_result->fetch_assoc();
    $stmt->close();
} catch (Exception $e) {
    header('Location: search.php');
    exit();
}

// Fetch vendor products
$products = [];
try {
    $products_query = "SELECT id, product_name, description, price, image, stock_quantity
                       FROM products 
                       WHERE vendor_id = ? AND is_available = 1
                       ORDER BY created_at DESC";
    $stmt = $conn->prepare($products_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $products_result = $stmt->get_result();
    
    while ($row = $products_result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Products Query Error: ' . $e->getMessage());
}

// Fetch vendor reels/videos
$reels = [];
try {
    $reels_query = "SELECT id, title, description, video_path, thumbnail, view_count, created_at
                    FROM vendor_reels 
                    WHERE vendor_id = ? 
                    ORDER BY created_at DESC
                    LIMIT 12";
    $stmt = $conn->prepare($reels_query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $vendor_id);
    $stmt->execute();
    $reels_result = $stmt->get_result();
    
    while ($row = $reels_result->fetch_assoc()) {
        $reels[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    error_log('Reels Query Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($vendor['business_name'] ?? 'Vendor'); ?> — Sarap Local</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo $asset_version; ?>">
    <style>
        body {
            background-color: #f8fafc;
        }

        .vendor-hero {
            background: linear-gradient(135deg, var(--primary-orange), var(--primary-orange-dark));
            color: white;
        }

        .vendor-header {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
            flex-wrap: wrap;
        }

        .vendor-avatar {
            width: 120px;
            height: 120px;
            border-radius: 16px;
            overflow: hidden;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .vendor-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .vendor-info h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .vendor-info p {
            margin: 0.25rem 0;
            font-size: 0.95rem;
            opacity: 0.95;
        }

        .contact-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-800);
            margin: 2rem 0 1.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-orange);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: #f0f0f0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .product-body {
            padding: 1rem;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-800);
            margin: 0 0 0.5rem 0;
            font-size: 0.95rem;
        }

        .product-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-orange);
            margin: 0.5rem 0;
        }

        .product-stock {
            font-size: 0.85rem;
            color: var(--gray-500);
            margin-bottom: 0.75rem;
        }

        .btn-order {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary-orange);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-order:hover {
            background: var(--primary-orange-dark);
            transform: translateY(-1px);
        }

        .reels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .reel-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .reel-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .reel-thumbnail {
            width: 100%;
            height: 200px;
            background: #1a1a1a;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .reel-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .reel-play-btn {
            position: absolute;
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: var(--primary-orange);
            transition: all 0.2s ease;
        }

        .reel-card:hover .reel-play-btn {
            background: white;
            transform: scale(1.1);
        }

        .reel-info {
            padding: 0.75rem;
        }

        .reel-title {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--gray-800);
            margin: 0 0 0.25rem 0;
            line-clamp: 2;
        }

        .reel-views {
            font-size: 0.8rem;
            color: var(--gray-500);
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--gray-500);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            padding: 0.75rem 1.25rem;
            color: white;
            background: rgba(255, 255, 255, 0.2);
            text-decoration: none;
            font-weight: 600;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }

        .back-link::before {
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

        .back-link:hover::before {
            left: 100%;
        }

        .back-link:hover {
            background: rgba(255, 255, 255, 0.3);
            gap: 1rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
            transform: translateY(-2px);
        }

        .back-link:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .back-link i {
            font-size: 1.1rem;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        .back-link span {
            position: relative;
            z-index: 1;
            font-size: 1rem;
        }

        @media (max-width: 768px) {
            .vendor-hero {
                padding: 1.5rem 1rem;
            }

            .vendor-header {
                gap: 1rem;
            }

            .vendor-avatar {
                width: 100px;
                height: 100px;
            }

            .vendor-info h1 {
                font-size: 1.5rem;
            }

            .product-grid {
                grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                gap: 1rem;
            }

            .reels-grid {
                grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
                gap: 1rem;
            }
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- Header -->
    <header class="brand-header shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center">
                    <div class="w-9 h-9 mr-3 rounded-full bg-white/90 flex items-center justify-center shadow-sm">
                        <img src="images/S.png" alt="Sarap Local" class="w-7 h-7 rounded-full">
                    </div>
                    <span class="text-xl font-semibold brand-script text-white">Sarap Local</span>
                </a>
                <div class="flex items-center space-x-4">
                    <a href="search.php" class="text-white hover:text-orange-100 transition-colors" title="Search">
                        <i class="fas fa-search text-xl"></i>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="profile.php" class="text-white hover:text-orange-100 transition-colors">
                            <i class="fas fa-user-circle text-xl"></i>
                        </a>
                    <?php else: ?>
                        <a href="login.php" class="text-white hover:text-orange-100 transition-colors">
                            <i class="fas fa-sign-in-alt text-xl"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Vendor Hero Section -->
    <section class="vendor-hero">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <a href="search.php" class="back-link" title="Go back to search">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Search</span>
            </a>

            <div class="vendor-header">
                <div class="vendor-avatar">
                    <?php if (!empty($vendor['profile_image']) && file_exists(__DIR__ . '/' . $vendor['profile_image'])): ?>
                        <img src="<?php echo htmlspecialchars($vendor['profile_image']); ?>" alt="<?php echo htmlspecialchars($vendor['business_name']); ?>">
                    <?php elseif (!empty($vendor['business_logo']) && file_exists(__DIR__ . '/' . $vendor['business_logo'])): ?>
                        <img src="<?php echo htmlspecialchars($vendor['business_logo']); ?>" alt="<?php echo htmlspecialchars($vendor['business_name']); ?>">
                    <?php else: ?>
                        <i class="fas fa-store text-3xl" style="color: var(--primary-orange);"></i>
                    <?php endif; ?>
                </div>

                <div class="vendor-info flex-1">
                    <h1><?php echo htmlspecialchars($vendor['business_name'] ?? $vendor['username']); ?></h1>
                    
                    <?php if (!empty($vendor['bio'])): ?>
                        <p><?php echo htmlspecialchars($vendor['bio']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($vendor['address'])): ?>
                        <p><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($vendor['address']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($vendor['phone'])): ?>
                        <div class="contact-badge">
                            <i class="fas fa-phone"></i>
                            <span><?php echo htmlspecialchars($vendor['phone']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Products Section -->
        <section>
            <h2 class="section-title">
                <i class="fas fa-utensils"></i>
                Products
            </h2>

            <?php if (!empty($products)): ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product['image']) && file_exists(__DIR__ . '/' . $product['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-utensils text-3xl text-gray-300"></i>
                                <?php endif; ?>
                            </div>
                            <div class="product-body">
                                <p class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></p>
                                
                                <?php if (!empty($product['description'])): ?>
                                    <p style="font-size: 0.85rem; color: var(--gray-600); margin: 0.5rem 0; line-height: 1.4;">
                                        <?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...
                                    </p>
                                <?php endif; ?>

                                <div class="product-price">₱<?php echo number_format((float)$product['price'], 2); ?></div>

                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <p class="product-stock">✓ In Stock (<?php echo $product['stock_quantity']; ?>)</p>
                                <?php else: ?>
                                    <p class="product-stock" style="color: var(--primary-orange);">Out of Stock</p>
                                <?php endif; ?>

                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn-order">
                                    <i class="fas fa-shopping-cart"></i>
                                    Order Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <p>No products available yet</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- Reels Section -->
        <?php if (!empty($reels)): ?>
            <section>
                <h2 class="section-title">
                    <i class="fas fa-film"></i>
                    Food Reels
                </h2>

                <div class="reels-grid">
                    <?php foreach ($reels as $reel): ?>
                        <div class="reel-card" onclick="playReel(<?php echo $reel['id']; ?>, '<?php echo htmlspecialchars($reel['video_path']); ?>')">
                            <div class="reel-thumbnail">
                                <?php if (!empty($reel['thumbnail']) && file_exists(__DIR__ . '/' . $reel['thumbnail'])): ?>
                                    <img src="<?php echo htmlspecialchars($reel['thumbnail']); ?>" alt="<?php echo htmlspecialchars($reel['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-video text-3xl text-gray-400"></i>
                                <?php endif; ?>
                                <div class="reel-play-btn">
                                    <i class="fas fa-play"></i>
                                </div>
                            </div>
                            <div class="reel-info">
                                <p class="reel-title"><?php echo htmlspecialchars($reel['title']); ?></p>
                                <p class="reel-views">
                                    <i class="fas fa-eye"></i>
                                    <?php echo number_format($reel['view_count']); ?> views
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>

    <!-- Video Modal -->
    <div id="videoModal" style="display: none;" class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div class="bg-black rounded-lg max-w-2xl w-full relative">
            <button onclick="closeVideoModal()" class="absolute -top-10 right-0 text-white hover:text-orange-400 text-2xl">
                <i class="fas fa-times"></i>
            </button>
            <video id="videoPlayer" class="w-full rounded-lg" controls style="max-height: 80vh;">
                <source src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
    </div>

    <script>
        function playReel(reelId, videoPath) {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('videoPlayer');
            video.src = videoPath;
            modal.style.display = 'flex';
            video.play();
        }

        function closeVideoModal() {
            const modal = document.getElementById('videoModal');
            const video = document.getElementById('videoPlayer');
            video.pause();
            modal.style.display = 'none';
        }

        // Close modal when clicking outside video
        document.getElementById('videoModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });
    </script>
</body>
</html>
