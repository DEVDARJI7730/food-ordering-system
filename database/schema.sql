CREATE DATABASE IF NOT EXISTS `food_order_db`;
USE `food_order_db`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `address` TEXT NOT NULL,
  `role` ENUM('customer', 'admin', 'restaurant_owner') DEFAULT 'customer',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `restaurants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `restaurant_name` VARCHAR(150) NOT NULL,
  `owner_name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(20) NOT NULL,
  `address` TEXT NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `state` VARCHAR(100) NOT NULL,
  `license_number` VARCHAR(100) NOT NULL UNIQUE,
  `license_issue_date` DATE NOT NULL,
  `license_expiry_date` DATE NOT NULL,
  `license_document` VARCHAR(255) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Suspended') NOT NULL DEFAULT 'Pending',
  `rejection_reason` TEXT DEFAULT NULL,
  `approved_by` INT DEFAULT NULL,
  `approved_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`approved_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `catagories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `food_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT DEFAULT NULL,
  `catagory_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT,
  `price` DECIMAL(10,2) NOT NULL,
  `image_url` VARCHAR(255) DEFAULT NULL,
  `is_available` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`catagory_id`) REFERENCES `catagories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT DEFAULT NULL,
  `user_id` INT NOT NULL,
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `cgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `sgst` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `delivery_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `commission_rate` DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `commission_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `partner_earnings` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Pending', 'Preparing', 'Out for Delivery', 'Delivered', 'Cancelled') DEFAULT 'Pending',
  `delivery_address` TEXT NOT NULL,
  `phone` VARCHAR(15) NOT NULL,
  `payment_method` VARCHAR(50) DEFAULT 'Cash on Delivery',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_id` INT NOT NULL,
  `restaurant_id` INT DEFAULT NULL,
  `food_item_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`food_item_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `restaurant_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `restaurant_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `rating` INT NOT NULL,
  `review` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`restaurant_id`) REFERENCES `restaurants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `food_reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `food_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `order_id` INT DEFAULT NULL,
  `rating` INT NOT NULL,
  `review` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`food_id`) REFERENCES `food_items`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `users` (`id`, `name`, `email`, `password`, `phone`, `address`, `role`) VALUES
(1, 'System Admin', 'admin@1234.com', '$2y$10$y54p2v6.WkC6Yl8w169D7OFzE1GjQW8V1t55mU24k7v5VlJp5J4Y.', '9876543210', 'Admin Office, Central Street', 'admin');
