-- Add vendor-specific tables

-- Vendors table with additional vendor information
ALTER TABLE `users` 
ADD COLUMN `business_name` VARCHAR(100) AFTER `role`,
ADD COLUMN `business_address` TEXT AFTER `business_name`,
ADD COLUMN `contact_number` VARCHAR(20) AFTER `business_address`,
ADD COLUMN `business_description` TEXT AFTER `contact_number`,
ADD COLUMN `profile_image` VARCHAR(255) DEFAULT 'images/default_vendor.jpg' AFTER `business_description`,
ADD COLUMN `is_approved` BOOLEAN DEFAULT FALSE AFTER `profile_image`;

-- Update products table to include more fields
ALTER TABLE `products`
ADD COLUMN `description` TEXT AFTER `food_name`,
ADD COLUMN `category` VARCHAR(50) AFTER `price`,
ADD COLUMN `is_available` BOOLEAN DEFAULT TRUE AFTER `available`,
ADD COLUMN `preparation_time` INT DEFAULT 30 COMMENT 'in minutes' AFTER `is_available`,
MODIFY COLUMN `images` TEXT COMMENT 'Comma-separated list of image paths';

-- Create vendor_availability table
CREATE TABLE IF NOT EXISTS `vendor_availability` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` INT NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '0=Sunday, 1=Monday, etc.',
    `open_time` TIME NOT NULL,
    `close_time` TIME NOT NULL,
    `is_available` BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `vendor_day` (`vendor_id`, `day_of_week`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create vendor_reviews table
CREATE TABLE IF NOT EXISTS `vendor_reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` INT NOT NULL,
    `customer_id` INT NOT NULL,
    `rating` TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    `review` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(20) NOT NULL UNIQUE,
    `customer_id` INT NOT NULL,
    `vendor_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `status` ENUM('pending', 'confirmed', 'preparing', 'ready_for_pickup', 'completed', 'cancelled') DEFAULT 'pending',
    `order_notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`customer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vendor_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create order_items table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `special_instructions` TEXT,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `title` VARCHAR(100) NOT NULL,
    `message` TEXT NOT NULL,
    `type` ENUM('order', 'message', 'system', 'promotion') NOT NULL,
    `is_read` BOOLEAN DEFAULT FALSE,
    `related_id` INT COMMENT 'ID of related entity (order, message, etc.)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
