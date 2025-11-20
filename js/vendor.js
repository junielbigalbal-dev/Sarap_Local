// Vendor Dashboard JavaScript
let currentChatCustomer = null;
let isRightColumnVisible = true;

// Initialize the dashboard
document.addEventListener('DOMContentLoaded', function() {
    // Show the feed by default
    showFeed();
    
    // Load initial data
    loadFeedPosts();
    loadOtherVendorPosts();
    
    // Initialize event listeners
    initializeEventListeners();
});

// Show feed section
function showFeed() {
    hideAllSections();
    document.getElementById('feedSection').style.display = 'block';
    loadFeedPosts();
}

// Show product management section
function showProductManagement() {
    hideAllSections();
    document.getElementById('productSection').style.display = 'block';
    renderProducts();
}

// Load feed posts
function loadFeedPosts() {
    // This would typically be an AJAX call to your backend
    const feedPosts = document.getElementById('feedPosts');
    feedPosts.innerHTML = '<p>Loading feed...</p>';
    
    // Simulated data - replace with actual API call
    setTimeout(() => {
        const posts = [
            { id: 1, vendor: 'Local Eats', food: 'Adobo', price: 120, image: 'images/chicken_adobo.jpg' },
            { id: 2, vendor: 'Tasty Bites', food: 'Kare-Kare', price: 150, image: 'images/kare_kare.jpg' }
        ];
        
        if (posts.length === 0) {
            feedPosts.innerHTML = '<p>No posts available. Start by adding your first product!</p>';
            return;
        }
        
        feedPosts.innerHTML = posts.map(post => `
            <div class="feed-post">
                <img src="${post.image}" alt="${post.food}" class="main-img">
                <div>
                    <h3>${post.food} - ₱${post.price}</h3>
                    <p>Vendor: ${post.vendor}</p>
                </div>
            </div>
        `).join('');
    }, 500);
}

// Load other vendor posts
function loadOtherVendorPosts() {
    const otherVendorPosts = document.getElementById('otherVendorPosts');
    otherVendorPosts.innerHTML = '<p>Loading other vendors...</p>';
    
    // Simulated data - replace with actual API call
    setTimeout(() => {
        const vendors = [
            { id: 2, name: 'Tasty Bites', food: 'Kare-Kare', price: 150, image: 'images/kare_kare.jpg' },
            { id: 3, name: 'Merienda Corner', food: 'Halo-Halo', price: 80, image: 'images/halo_halo.jpg' }
        ];
        
        if (vendors.length === 0) {
            otherVendorPosts.innerHTML = '<p>No other vendors found.</p>';
            return;
        }
        
        otherVendorPosts.innerHTML = vendors.map(vendor => `
            <div class="vendor-product">
                <img src="${vendor.image}" alt="${vendor.food}">
                <div>
                    <strong>${vendor.food}</strong><br>
                    ${vendor.name}<br>
                    ₱${vendor.price}
                </div>
            </div>
        `).join('');
    }, 500);
}

// Toggle right column visibility
function toggleRightColumn() {
    const rightColumn = document.getElementById('rightColumn');
    const toggleButton = document.getElementById('toggleRight');
    
    if (isRightColumnVisible) {
        rightColumn.style.width = '0';
        rightColumn.style.padding = '0';
        rightColumn.style.opacity = '0';
        toggleButton.style.right = '10px';
        toggleButton.innerHTML = '→';
    } else {
        rightColumn.style.width = '300px';
        rightColumn.style.padding = '1rem';
        rightColumn.style.opacity = '1';
        toggleButton.style.right = '310px';
        toggleButton.innerHTML = '←';
    }
    
    isRightColumnVisible = !isRightColumnVisible;
}

// Chat functionality
function openChatWithCustomer(customer) {
    currentChatCustomer = customer;
    document.getElementById('chatWith').innerText = `Chat with ${customer}`;
    document.getElementById('chatModal').style.display = 'flex';
    renderChat();
}

function renderChat() {
    // This would typically load chat history from your backend
    const chatMessages = document.getElementById('chatMessages');
    chatMessages.innerHTML = '<p>Loading chat...</p>';
    
    // Simulated chat history
    setTimeout(() => {
        const messages = [
            { sender: 'Customer', message: 'Hi, is this still available?', time: '10:00 AM' },
            { sender: 'You', message: 'Yes, it is! Would you like to order?', time: '10:02 AM' }
        ];
        
        chatMessages.innerHTML = messages.map(msg => `
            <div class="chat-message ${msg.sender === 'You' ? 'sent' : 'received'}">
                <div class="message-content">${msg.message}</div>
                <div class="message-time">${msg.time}</div>
            </div>
        `).join('');
        
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }, 500);
}

function sendMessage() {
    const input = document.getElementById('chatInput');
    const message = input.value.trim();
    
    if (message) {
        // This would typically send the message to your backend
        const chatMessages = document.getElementById('chatMessages');
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        
        chatMessages.innerHTML += `
            <div class="chat-message sent">
                <div class="message-content">${message}</div>
                <div class="message-time">${time}</div>
            </div>
        `;
        
        input.value = '';
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

function toggleExpand() {
    const chatModal = document.getElementById('chatModal');
    chatModal.classList.toggle('expanded');
}

function closeChat() {
    document.getElementById('chatModal').style.display = 'none';
}

// Helper functions
function hideAllSections() {
    document.querySelectorAll('.main-content > div[id$="Section"]').forEach(section => {
        section.style.display = 'none';
    });
}

function logout() {
    if (confirm('Are you sure you want to logout?')) {
        // This would typically clear the session on the server
        window.location.href = 'logout.php';
    }
}

// Initialize event listeners
function initializeEventListeners() {
    // Handle chat input with Enter key
    document.getElementById('chatInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth < 992 && isRightColumnVisible) {
            toggleRightColumn();
        }
    });
}

// Expose functions to global scope
window.showFeed = showFeed;
window.showProductManagement = showProductManagement;
window.toggleRightColumn = toggleRightColumn;
window.openChatWithCustomer = openChatWithCustomer;
window.sendMessage = sendMessage;
window.toggleExpand = toggleExpand;
window.closeChat = closeChat;
window.logout = logout;
