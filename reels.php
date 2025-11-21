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

$customer_id = $_SESSION['user_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Reels - Sarap Local</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        .reels-container {
            display: flex;
            height: 100vh;
            overflow: hidden;
            background: #000;
        }

        .reels-feed {
            flex: 1;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            background: #000;
            position: relative;
        }

        .reel-item {
            height: 100vh;
            width: 100%;
            scroll-snap-align: start;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #000;
        }

        .reel-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* TikTok-style overlay at bottom */
        .reel-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0.7), transparent);
            padding: 60px 20px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            min-height: 200px;
        }

        /* Left side - Vendor info and product details */
        .reel-info {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .reel-details {
            flex: 1;
        }

        .reel-vendor {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 8px;
        }

        .reel-vendor-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: #ddd;
            object-fit: cover;
            border: 2px solid white;
            flex-shrink: 0;
        }

        .reel-vendor-info {
            display: flex;
            flex-direction: column;
        }

        .reel-vendor-name {
            font-weight: 700;
            font-size: 15px;
            line-height: 1.2;
        }

        .reel-vendor-follow {
            font-size: 12px;
            opacity: 0.8;
            cursor: pointer;
            color: #FFB84D;
            font-weight: 600;
        }

        .reel-title {
            font-size: 16px;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .reel-description {
            font-size: 13px;
            opacity: 0.85;
            line-height: 1.4;
            margin-bottom: 8px;
            max-width: 85%;
        }

        .reel-product {
            font-size: 12px;
            color: #FFB84D;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
        }

        .foodie-btn {
            background: linear-gradient(45deg, #FF512F, #DD2476);
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 50px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            font-size: 14px;
            white-space: nowrap;
            box-shadow: 0 10px 20px rgba(221, 36, 118, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: 2px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .foodie-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: 0.5s;
        }

        .foodie-btn:hover::before {
            left: 100%;
        }

        .foodie-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 15px 30px rgba(221, 36, 118, 0.5);
        }

        .foodie-btn:active {
            transform: translateY(1px) scale(0.98);
        }

        .foodie-btn i {
            font-size: 16px;
        }

        /* Order/Reservation Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .modal-section {
            margin-bottom: 20px;
        }

        .modal-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #666;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .option-group {
            display: flex;
            gap: 12px;
            margin-bottom: 12px;
        }

        .option-btn {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #ddd;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .option-btn:hover {
            border-color: #C46A2B;
            background: #fff5f0;
        }

        .option-btn.selected {
            background: linear-gradient(135deg, #C46A2B, #E9C46A);
            color: white;
            border-color: #C46A2B;
        }

        .vendor-info-box {
            background: #f5f5f5;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 16px;
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .vendor-avatar-small {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #C46A2B;
        }

        .vendor-details-small {
            flex: 1;
        }

        .vendor-name-small {
            font-weight: 600;
            font-size: 14px;
            color: #333;
        }

        .vendor-distance {
            font-size: 12px;
            color: #999;
        }

        .product-info-box {
            background: #fff5f0;
            border-left: 4px solid #C46A2B;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 16px;
        }

        .product-name-small {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }

        .product-price-small {
            font-size: 16px;
            font-weight: 700;
            color: #C46A2B;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .modal-btn {
            flex: 1;
            padding: 12px 16px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .modal-btn-cancel {
            background: #f0f0f0;
            color: #333;
        }

        .modal-btn-cancel:hover {
            background: #e0e0e0;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #C46A2B, #E9C46A);
            color: white;
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(196, 106, 43, 0.3);
        }

        .modal-btn-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .info-text {
            font-size: 12px;
            color: #999;
            margin-top: 8px;
            line-height: 1.4;
        }

        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        .error-message {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 16px;
            font-size: 13px;
        }

        /* Right side - Action buttons (TikTok style) */
        .reel-actions {
            display: flex;
            flex-direction: column;
            gap: 16px;
            align-items: center;
            margin-left: 16px;
        }

        .reel-action-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .reel-action-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.15);
        }

        .reel-action-btn:active {
            transform: scale(0.9);
        }

        .action-count {
            font-size: 11px;
            margin-top: 4px;
            font-weight: 600;
            text-align: center;
            min-width: 40px;
        }

        .reels-sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 100;
            display: flex;
            flex-direction: column;
            gap: 16px;
            width: auto;
            background: transparent;
        }

        .sidebar-btn {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: all 0.3s ease;
            backdrop-filter: blur(5px);
        }

        .sidebar-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }

        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 100;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .reel-overlay {
                padding: 40px 16px 16px;
                min-height: 160px;
            }

            .reel-actions {
                gap: 12px;
                margin-left: 12px;
            }

            .reel-action-btn {
                width: 42px;
                height: 42px;
                font-size: 18px;
            }

            .reel-title {
                font-size: 14px;
            }

            .reel-description {
                font-size: 12px;
            }

            .order-btn {
                padding: 8px 20px;
                font-size: 12px;
            }

            .reel-vendor-avatar {
                width: 40px;
                height: 40px;
            }
        }

        @media (max-width: 480px) {
            .reel-overlay {
                padding: 30px 12px 12px;
                min-height: 140px;
            }

            .reel-actions {
                gap: 10px;
                margin-left: 10px;
            }

            .reel-action-btn {
                width: 38px;
                height: 38px;
                font-size: 16px;
            }

            .action-count {
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="reels-container">
        <!-- Sidebar -->
        <div class="reels-sidebar">
            <button class="sidebar-btn" onclick="goBack()" title="Back">
                <i class="fas fa-arrow-left"></i>
            </button>
            <button class="sidebar-btn" onclick="toggleMute()" id="muteBtn" title="Mute">
                <i class="fas fa-volume-up"></i>
            </button>
            <button class="sidebar-btn" onclick="scrollToTop()" title="Top">
                <i class="fas fa-arrow-up"></i>
            </button>
        </div>

        <!-- Feed -->
        <div class="reels-feed" id="reelsFeed">
            <div class="loading-spinner">
                <i class="fas fa-spinner fa-spin" style="font-size: 40px; color: #C46A2B;"></i>
            </div>
        </div>
    </div>

    <!-- Order/Reservation Modal -->
    <div id="orderModal" class="modal-overlay" style="display: none;" onclick="closeOrderModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <i class="fas fa-shopping-bag"></i>
                <span>Place Order</span>
            </div>

            <!-- Vendor Info -->
            <div id="vendorInfoContainer" class="vendor-info-box" style="display: none;">
                <img id="vendorAvatar" src="" alt="Vendor" class="vendor-avatar-small">
                <div class="vendor-details-small">
                    <div class="vendor-name-small" id="vendorName"></div>
                    <div class="vendor-distance" id="vendorDistance"></div>
                </div>
            </div>

            <!-- Product Info -->
            <div id="productInfoContainer" class="product-info-box" style="display: none;">
                <div class="product-name-small" id="productName"></div>
                <div class="product-price-small" id="productPrice"></div>
            </div>

            <!-- Order Type Selection -->
            <div class="modal-section">
                <div class="modal-section-title">Order Type</div>
                <div class="option-group">
                    <button class="option-btn selected" onclick="selectOrderType('delivery')" data-type="delivery">
                        <i class="fas fa-truck"></i> Delivery
                    </button>
                    <button class="option-btn" onclick="selectOrderType('pickup')" data-type="pickup">
                        <i class="fas fa-store"></i> Pickup
                    </button>
                </div>
                <div class="info-text" id="orderTypeInfo">
                    <i class="fas fa-info-circle"></i> We'll deliver to your location
                </div>
            </div>

            <!-- Delivery Address (shown for delivery) -->
            <div id="deliverySection" class="modal-section">
                <div class="modal-section-title">Delivery Address</div>
                <input type="text" id="deliveryAddress" placeholder="Enter your delivery address" 
                       style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px;">
                <div class="info-text">
                    <i class="fas fa-map-marker-alt"></i> Delivery fee will be calculated based on distance
                </div>
            </div>

            <!-- Pickup Time (shown for pickup) -->
            <div id="pickupSection" class="modal-section" style="display: none;">
                <div class="modal-section-title">Preferred Pickup Time</div>
                <select id="pickupTime" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px;">
                    <option value="">Select a time</option>
                    <option value="asap">ASAP (15-30 mins)</option>
                    <option value="30mins">30 minutes</option>
                    <option value="1hour">1 hour</option>
                    <option value="2hours">2 hours</option>
                </select>
                <div class="info-text">
                    <i class="fas fa-clock"></i> Visit the store to pick up your order
                </div>
            </div>

            <!-- Special Instructions -->
            <div class="modal-section">
                <div class="modal-section-title">Special Instructions (Optional)</div>
                <textarea id="specialInstructions" placeholder="Add any special requests..." 
                          style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; font-size: 13px; resize: vertical; min-height: 60px;"></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="modal-actions">
                <button class="modal-btn modal-btn-cancel" onclick="closeOrderModal()">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="confirmOrderBtn" onclick="confirmOrder()">Confirm Order</button>
            </div>
        </div>
    </div>

    <script>
        let currentReelIndex = 0;
        let reels = [];
        let isMuted = false;
        let isLoading = false;
        let hasMore = true;

        async function loadReels() {
            if (isLoading || !hasMore) return;
            isLoading = true;

            try {
                const response = await fetch(`api/customer_reels.php?action=feed&offset=${reels.length}&limit=10`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();

                if (!Array.isArray(data) || data.length === 0) {
                    hasMore = false;
                    if (reels.length === 0) {
                        document.getElementById('reelsFeed').innerHTML = `
                            <div style="display: flex; align-items: center; justify-content: center; height: 100vh; color: white; text-align: center;">
                                <div>
                                    <i class="fas fa-film" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                                    <p style="font-size: 18px;">No reels available yet</p>
                                </div>
                            </div>
                        `;
                    }
                    return;
                }

                reels = reels.concat(data);
                renderReels();
            } catch (error) {
                console.error('Error loading reels:', error);
                if (reels.length === 0) {
                    document.getElementById('reelsFeed').innerHTML = `
                        <div style="display: flex; align-items: center; justify-content: center; height: 100vh; color: white; text-align: center;">
                            <div>
                                <i class="fas fa-exclamation-circle" style="font-size: 60px; margin-bottom: 20px; opacity: 0.5;"></i>
                                <p style="font-size: 18px;">Error loading reels</p>
                            </div>
                        </div>
                    `;
                }
            } finally {
                isLoading = false;
            }
        }

        function renderReels() {
            const feed = document.getElementById('reelsFeed');
            feed.innerHTML = '';

            reels.forEach((reel, index) => {
                const reelEl = document.createElement('div');
                reelEl.className = 'reel-item';
                reelEl.innerHTML = `
                    <video class="reel-video" data-index="${index}" ${isMuted ? 'muted' : ''}>
                        <source src="${reel.video_path}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                    <div class="reel-overlay">
                        <div class="reel-info">
                            <div class="reel-vendor">
                                ${reel.profile_image ? `<img src="${reel.profile_image}" class="reel-vendor-avatar">` : '<div class="reel-vendor-avatar"><i class="fas fa-user"></i></div>'}
                                <div class="reel-vendor-info">
                                    <span class="reel-vendor-name">${reel.business_name || 'Vendor'}</span>
                                    <span class="reel-vendor-follow">Follow</span>
                                </div>
                            </div>
                            <div class="reel-title">${reel.title || 'Untitled Reel'}</div>
                            ${reel.description ? `<div class="reel-description">${reel.description}</div>` : ''}
                            ${reel.product_name ? `<div class="reel-product"><i class="fas fa-box"></i> ${reel.product_name} - ₱${reel.price}</div>` : ''}
                            <button class="foodie-btn" onclick="orderProduct(${reel.product_id})">
                                <i class="fas fa-utensils"></i> Foodie Order
                            </button>
                        </div>
                        <div class="reel-actions">
                            <div style="text-align: center;">
                                <button class="reel-action-btn" onclick="likeReel(${reel.id})" title="Like">
                                    <i class="fas fa-heart"></i>
                                </button>
                                <div class="action-count">${reel.view_count || 0}</div>
                            </div>
                            <div style="text-align: center;">
                                <button class="reel-action-btn" onclick="shareReel(${reel.id})" title="Share">
                                    <i class="fas fa-share"></i>
                                </button>
                                <div class="action-count">Share</div>
                            </div>
                            <div style="text-align: center;">
                                <button class="reel-action-btn" onclick="commentReel(${reel.id})" title="Comment">
                                    <i class="fas fa-comment"></i>
                                </button>
                                <div class="action-count">0</div>
                            </div>
                        </div>
                    </div>
                `;
                feed.appendChild(reelEl);
            });

            // Setup intersection observer for autoplay
            setupAutoplay();
        }

        function setupAutoplay() {
            const videos = document.querySelectorAll('.reel-video');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    const video = entry.target;
                    if (entry.isIntersecting) {
                        video.play();
                        incrementViewCount(parseInt(video.dataset.index));
                    } else {
                        video.pause();
                    }
                });
            }, { threshold: 0.5 });

            videos.forEach(video => observer.observe(video));
        }

        function incrementViewCount(index) {
            const reel = reels[index];
            if (reel && !reel.viewed) {
                reel.viewed = true;
                fetch('api/customer_reels.php?action=increment_views', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `reel_id=${reel.id}`
                }).catch(e => console.error('Error incrementing views:', e));
            }
        }

        function toggleMute() {
            isMuted = !isMuted;
            const btn = document.getElementById('muteBtn');
            btn.innerHTML = isMuted ? '<i class="fas fa-volume-mute"></i>' : '<i class="fas fa-volume-up"></i>';
            
            document.querySelectorAll('.reel-video').forEach(video => {
                video.muted = isMuted;
            });
        }

        // Order/Reservation System
        let currentOrder = {
            productId: null,
            vendorId: null,
            vendorName: null,
            vendorAvatar: null,
            productName: null,
            productPrice: null,
            orderType: 'delivery'
        };

        function orderProduct(productId) {
            if (!productId) {
                alert('This reel is not linked to a product');
                return;
            }

            // Find the reel data
            const reel = reels.find(r => r.product_id === productId);
            if (!reel) {
                alert('Product information not found');
                return;
            }

            // Populate order modal
            currentOrder.productId = productId;
            currentOrder.vendorId = reel.vendor_id;
            currentOrder.vendorName = reel.business_name || 'Vendor';
            currentOrder.vendorAvatar = reel.profile_image;
            currentOrder.productName = reel.product_name;
            currentOrder.productPrice = reel.price;

            // Show vendor info
            document.getElementById('vendorName').textContent = currentOrder.vendorName;
            document.getElementById('vendorAvatar').src = currentOrder.vendorAvatar || 'https://via.placeholder.com/48';
            document.getElementById('vendorDistance').textContent = reel.distance ? `${reel.distance} km away` : 'Distance unknown';
            document.getElementById('vendorInfoContainer').style.display = 'flex';

            // Show product info
            document.getElementById('productName').textContent = currentOrder.productName;
            document.getElementById('productPrice').textContent = `₱${parseFloat(currentOrder.productPrice).toFixed(2)}`;
            document.getElementById('productInfoContainer').style.display = 'block';

            // Reset form
            document.getElementById('deliveryAddress').value = '';
            document.getElementById('pickupTime').value = '';
            document.getElementById('specialInstructions').value = '';
            selectOrderType('delivery');

            // Show modal
            document.getElementById('orderModal').style.display = 'flex';
        }

        function selectOrderType(type) {
            currentOrder.orderType = type;
            
            // Update button states
            document.querySelectorAll('.option-btn[data-type]').forEach(btn => {
                btn.classList.remove('selected');
            });
            document.querySelector(`[data-type="${type}"]`).classList.add('selected');

            // Show/hide relevant sections
            if (type === 'delivery') {
                document.getElementById('deliverySection').style.display = 'block';
                document.getElementById('pickupSection').style.display = 'none';
                document.getElementById('orderTypeInfo').innerHTML = '<i class="fas fa-info-circle"></i> We\'ll deliver to your location';
            } else {
                document.getElementById('deliverySection').style.display = 'none';
                document.getElementById('pickupSection').style.display = 'block';
                document.getElementById('orderTypeInfo').innerHTML = '<i class="fas fa-info-circle"></i> Visit the store to pick up your order';
            }
        }

        function closeOrderModal(event) {
            if (event && event.target !== event.currentTarget) return;
            document.getElementById('orderModal').style.display = 'none';
        }

        function confirmOrder() {
            const btn = document.getElementById('confirmOrderBtn');
            
            // Validation
            if (currentOrder.orderType === 'delivery') {
                const address = document.getElementById('deliveryAddress').value.trim();
                if (!address) {
                    alert('Please enter a delivery address');
                    return;
                }
            } else {
                const pickupTime = document.getElementById('pickupTime').value;
                if (!pickupTime) {
                    alert('Please select a pickup time');
                    return;
                }
            }

            btn.disabled = true;
            btn.textContent = 'Processing...';

            // Prepare order data
            const orderData = {
                product_id: currentOrder.productId,
                vendor_id: currentOrder.vendorId,
                order_type: currentOrder.orderType,
                delivery_address: currentOrder.orderType === 'delivery' ? document.getElementById('deliveryAddress').value : null,
                pickup_time: currentOrder.orderType === 'pickup' ? document.getElementById('pickupTime').value : null,
                special_instructions: document.getElementById('specialInstructions').value
            };

            // Send to backend
            fetch('api/customer_orders.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            })
            .then(response => response.json())
            .then(data => {
                btn.disabled = false;
                btn.textContent = 'Confirm Order';

                if (data.success) {
                    alert('Order placed successfully! Order ID: ' + data.order_id);
                    closeOrderModal();
                    // Optionally redirect to order tracking page
                    // window.location.href = `order-tracking.php?id=${data.order_id}`;
                } else {
                    alert('Error: ' + (data.error || 'Failed to place order'));
                }
            })
            .catch(error => {
                btn.disabled = false;
                btn.textContent = 'Confirm Order';
                console.error('Error:', error);
                alert('Error placing order: ' + error.message);
            });
        }

        function goBack() {
            window.location.href = 'customer.php';
        }

        function scrollToTop() {
            document.getElementById('reelsFeed').scrollTop = 0;
        }

        function likeReel(reelId) {
            // TODO: Implement like functionality
            console.log('Like reel:', reelId);
            alert('Like feature coming soon!');
        }

        function shareReel(reelId) {
            // TODO: Implement share functionality
            console.log('Share reel:', reelId);
            if (navigator.share) {
                navigator.share({
                    title: 'Check out this food reel!',
                    text: 'Found an amazing food reel on Sarap Local',
                    url: window.location.href
                }).catch(err => console.log('Error sharing:', err));
            } else {
                alert('Share feature not supported on this device');
            }
        }

        function commentReel(reelId) {
            // TODO: Implement comment functionality
            console.log('Comment on reel:', reelId);
            alert('Comment feature coming soon!');
        }

        // Load more reels when scrolling near bottom
        document.addEventListener('DOMContentLoaded', function() {
            loadReels();

            const feed = document.getElementById('reelsFeed');
            feed.addEventListener('scroll', function() {
                if (feed.scrollTop + feed.clientHeight >= feed.scrollHeight - 500) {
                    loadReels();
                }
            });
        });
    </script>
    <script src="js/realtime-updates.js?v=<?php echo time(); ?>"></script>
    <script src="js/session-manager.js?v=<?php echo time(); ?>"></script>
</body>
</html>
