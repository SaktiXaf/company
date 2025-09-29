-- SQL Script Sederhana untuk Apple Clone Admin Panel
-- Versi tanpa foreign key constraints untuk menghindari dependency issues

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
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
  KEY `variant_id` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabel product_variants untuk variasi produk
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
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample feedback data
INSERT IGNORE INTO `feedback` (`id`, `user_id`, `name`, `email`, `subject`, `message`, `type`, `status`, `rating`, `created_at`) VALUES
(1, NULL, 'John Customer', 'john@example.com', 'Great iPhone experience!', 'I love my new iPhone 15 Pro Max. The camera quality is amazing and the battery lasts all day.', 'feedback', 'pending', 5, '2024-01-15 10:30:00'),
(2, NULL, 'Sarah Wilson', 'sarah@example.com', 'Suggestion for website', 'It would be great if you could add a comparison feature between different iPhone models.', 'suggestion', 'reviewed', NULL, '2024-01-14 14:20:00'),
(3, NULL, 'Mike Tech', 'mike@example.com', 'MacBook delivery issue', 'My MacBook Pro was supposed to arrive yesterday but I have not received it yet. Please help.', 'complaint', 'replied', 2, '2024-01-13 09:15:00'),
(4, NULL, 'Lisa Admin', 'lisa@example.com', 'Website bug report', 'The search function on the website does not work properly when searching for iPad accessories.', 'bug_report', 'resolved', NULL, '2024-01-12 16:45:00'),
(5, NULL, 'David Customer', 'david@example.com', 'Apple Watch feedback', 'The new Apple Watch Series 9 is fantastic! Health monitoring features are very accurate.', 'feedback', 'pending', 5, '2024-01-11 11:30:00');