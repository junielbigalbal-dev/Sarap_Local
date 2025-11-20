-- Add vendor_reels table for food videos
CREATE TABLE IF NOT EXISTS vendor_reels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    product_id INT,
    video_path VARCHAR(255) NOT NULL,
    thumbnail_path VARCHAR(255),
    title VARCHAR(100) NOT NULL,
    description TEXT,
    duration INT,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_vendor_reels (vendor_id),
    INDEX idx_product_reels (product_id)
);

-- Add latitude/longitude to users table if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS latitude DECIMAL(10, 8);
ALTER TABLE users ADD COLUMN IF NOT EXISTS longitude DECIMAL(11, 8);

-- Add last_login column if not exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login TIMESTAMP NULL;
