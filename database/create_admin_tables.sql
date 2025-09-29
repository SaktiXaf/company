-- SQL Script untuk membuat tabel yang diperlukan untuk Apple Clone Admin Panel
-- Jalankan script ini di database MySQL Anda

-- Tabel feedback untuk customer feedback dan suggestions
CREATE TABLE IF NOT EXISTS `feedback` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('feedback','suggestion','complaint','bug_report') DEFAULT 'feedback',
  `status` enum('pending','reviewed','replied','resolved','closed') DEFAULT 'pending',
  `admin_reply` text DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `rating` int(1) DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `type` (`type`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel orders untuk customer orders
CREATE TABLE IF NOT EXISTS `orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(50) NOT NULL UNIQUE,
  `status` enum('pending','processing','shipped','delivered','cancelled','refunded') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_amount` decimal(10,2) DEFAULT 0.00,
  `tax_amount` decimal(10,2) DEFAULT 0.00,
  `payment_status` enum('pending','paid','failed','refunded') DEFAULT 'pending',
  `payment_method` varchar(50) DEFAULT NULL,
  `shipping_address` text NOT NULL,
  `billing_address` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `tracking_number` varchar(100) DEFAULT NULL,
  `shipped_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`),
  KEY `order_number` (`order_number`),
  KEY `created_at` (`created_at`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel order_items untuk items dalam order
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `variant_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `variant_name` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  KEY `variant_id` (`variant_id`),
  FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabel product_variants untuk variasi produk (warna, storage, dll)
CREATE TABLE IF NOT EXISTS `product_variants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `color` varchar(100) DEFAULT NULL,
  `storage` varchar(100) DEFAULT NULL,
  `price_adjustment` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `sku` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_active` (`is_active`),
  FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Update tabel products jika belum ada kolom yang diperlukan
ALTER TABLE `products` 
ADD COLUMN IF NOT EXISTS `slug` varchar(255) DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `short_desc` text DEFAULT NULL AFTER `description`,
ADD COLUMN IF NOT EXISTS `main_image` varchar(500) DEFAULT NULL AFTER `short_desc`,
ADD COLUMN IF NOT EXISTS `gallery` text DEFAULT NULL AFTER `main_image`,
ADD COLUMN IF NOT EXISTS `is_featured` tinyint(1) DEFAULT 0 AFTER `gallery`,
ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT 1 AFTER `is_featured`,
ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD INDEX IF NOT EXISTS `slug` (`slug`),
ADD INDEX IF NOT EXISTS `category` (`category`),
ADD INDEX IF NOT EXISTS `is_featured` (`is_featured`),
ADD INDEX IF NOT EXISTS `is_active` (`is_active`);

-- Update tabel users jika belum ada kolom yang diperlukan
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `username` varchar(100) DEFAULT NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `profile_photo` varchar(500) DEFAULT NULL AFTER `email`,
ADD COLUMN IF NOT EXISTS `phone` varchar(20) DEFAULT NULL AFTER `profile_photo`,
ADD COLUMN IF NOT EXISTS `address` text DEFAULT NULL AFTER `phone`,
ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT 1 AFTER `role`,
ADD COLUMN IF NOT EXISTS `last_login` timestamp NULL DEFAULT NULL AFTER `is_active`,
ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
ADD INDEX IF NOT EXISTS `username` (`username`),
ADD INDEX IF NOT EXISTS `email` (`email`),
ADD INDEX IF NOT EXISTS `role` (`role`),
ADD INDEX IF NOT EXISTS `is_active` (`is_active`);

-- Insert sample data untuk testing

-- Sample feedback data
INSERT IGNORE INTO `feedback` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `type`, `status`, `rating`, `created_at`) VALUES
(1, NULL, 'John Customer', 'john@example.com', 'Great iPhone experience!', 'I love my new iPhone 15 Pro Max. The camera quality is amazing and the battery lasts all day.', 'feedback', 'pending', 5, '2024-01-15 10:30:00'),
(2, NULL, 'Sarah Wilson', 'sarah@example.com', 'Suggestion for website', 'It would be great if you could add a comparison feature between different iPhone models.', 'suggestion', 'reviewed', NULL, '2024-01-14 14:20:00'),
(3, NULL, 'Mike Tech', 'mike@example.com', 'MacBook delivery issue', 'My MacBook Pro was supposed to arrive yesterday but I haven\'t received it yet. Please help.', 'complaint', 'replied', 2, '2024-01-13 09:15:00'),
(4, NULL, 'Lisa Admin', 'lisa@example.com', 'Website bug report', 'The search function on the website doesn\'t work properly when searching for iPad accessories.', 'bug_report', 'resolved', NULL, '2024-01-12 16:45:00'),
(5, NULL, 'David Customer', 'david@example.com', 'Apple Watch feedback', 'The new Apple Watch Series 9 is fantastic! Health monitoring features are very accurate.', 'feedback', 'pending', 5, '2024-01-11 11:30:00');

-- Sample orders data
INSERT IGNORE INTO `orders` (`id`, `user_id`, `order_number`, `status`, `total_amount`, `shipping_amount`, `tax_amount`, `payment_status`, `payment_method`, `shipping_address`, `created_at`) VALUES
(1, 1, 'ORD-2024-001', 'delivered', 1299.00, 0.00, 129.90, 'paid', 'credit_card', 'John Doe\n123 Main St\nNew York, NY 10001\nUSA', '2024-01-10 10:00:00'),
(2, 1, 'ORD-2024-002', 'processing', 799.00, 0.00, 79.90, 'paid', 'apple_pay', 'John Doe\n123 Main St\nNew York, NY 10001\nUSA', '2024-01-12 14:30:00'),
(3, 1, 'ORD-2024-003', 'shipped', 2499.00, 0.00, 249.90, 'paid', 'credit_card', 'John Doe\n123 Main St\nNew York, NY 10001\nUSA', '2024-01-14 09:15:00'),
(4, 1, 'ORD-2024-004', 'pending', 599.00, 0.00, 59.90, 'pending', 'bank_transfer', 'John Doe\n123 Main St\nNew York, NY 10001\nUSA', '2024-01-15 16:20:00');

-- Sample order items
INSERT IGNORE INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total`) VALUES
(1, 1, 1, 'iPhone 15 Pro Max', 1299.00, 1, 1299.00),
(2, 2, 2, 'iPad Air', 799.00, 1, 799.00),
(3, 3, 3, 'MacBook Pro 16"', 2499.00, 1, 2499.00),
(4, 4, 4, 'Apple Watch Series 9', 599.00, 1, 599.00);

-- Sample product variants
INSERT IGNORE INTO `product_variants` (`id`, `product_id`, `name`, `color`, `storage`, `price_adjustment`, `stock`) VALUES
(1, 1, 'iPhone 15 Pro Max - Space Black 256GB', 'Space Black', '256GB', 0.00, 50),
(2, 1, 'iPhone 15 Pro Max - Natural Titanium 256GB', 'Natural Titanium', '256GB', 0.00, 30),
(3, 1, 'iPhone 15 Pro Max - Space Black 512GB', 'Space Black', '512GB', 200.00, 25),
(4, 2, 'iPad Air - Space Gray 256GB', 'Space Gray', '256GB', 0.00, 40),
(5, 2, 'iPad Air - Silver 256GB', 'Silver', '256GB', 0.00, 35);

-- Update sample products with missing fields
UPDATE `products` SET 
  `slug` = LOWER(REPLACE(REPLACE(name, ' ', '-'), '+', 'plus')),
  `short_desc` = 'Premium Apple product with cutting-edge technology',
  `main_image` = 'assets/img/products/product-placeholder.jpg',
  `gallery` = '["assets/img/products/gallery1.jpg","assets/img/products/gallery2.jpg"]',
  `is_featured` = 1,
  `is_active` = 1
WHERE `slug` IS NULL OR `slug` = '';

-- Create admin user if not exists
INSERT IGNORE INTO `users` (`id`, `name`, `username`, `email`, `password`, `role`, `is_active`, `created_at`) VALUES
(99, 'System Administrator', 'admin', 'admin@applestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, NOW());

-- Add indexes for better performance
CREATE INDEX IF NOT EXISTS idx_feedback_status_type ON feedback(status, type);
CREATE INDEX IF NOT EXISTS idx_orders_status_payment ON orders(status, payment_status);
CREATE INDEX IF NOT EXISTS idx_order_items_order_product ON order_items(order_id, product_id);
CREATE INDEX IF NOT EXISTS idx_products_category_featured ON products(category, is_featured, is_active);
CREATE INDEX IF NOT EXISTS idx_users_role_active ON users(role, is_active);

-- Script completed - tables created successfully