-- Apple Clone E-commerce Database Schema
-- Created: September 28, 2025

DROP DATABASE IF EXISTS apple_clone;
CREATE DATABASE apple_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE apple_clone;

-- Table: users
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) NULL,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    country VARCHAR(50) DEFAULT 'US',
    phone VARCHAR(20),
    address TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: products
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(150) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    short_desc TEXT,
    description TEXT,
    category VARCHAR(100) NOT NULL,
    base_price DECIMAL(12,2) NOT NULL,
    main_image VARCHAR(255),
    gallery JSON,
    specifications JSON,
    is_featured TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: product_variants
CREATE TABLE product_variants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    variant_name VARCHAR(100) NOT NULL,
    color VARCHAR(50),
    storage VARCHAR(50),
    extra_price DECIMAL(10,2) DEFAULT 0.00,
    stock INT DEFAULT 0,
    sku VARCHAR(100) UNIQUE NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Table: orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total DECIMAL(12,2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    currency VARCHAR(10) DEFAULT 'USD',
    shipping_address JSON NOT NULL,
    billing_address JSON,
    payment_method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table: order_items
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    variant_id INT,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Table: faqs
CREATE TABLE faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question VARCHAR(500) NOT NULL,
    answer TEXT NOT NULL,
    category VARCHAR(100) DEFAULT 'General',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table: search_logs
CREATE TABLE search_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    keyword VARCHAR(255) NOT NULL,
    results_count INT DEFAULT 0,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Table: cart_sessions (for guest carts)
CREATE TABLE cart_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    user_id INT NULL,
    product_id INT NOT NULL,
    variant_id INT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(id) ON DELETE SET NULL
);

-- Sample Data

-- Insert Admin User
INSERT INTO users (name, username, email, password, role, country) VALUES 
('Admin User', 'admin', 'admin@applestore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'US'),
('John Doe', 'johndoe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'US'),
('Jane Smith', 'janesmith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', 'ID');

-- Insert Products
INSERT INTO products (slug, name, short_desc, description, category, base_price, main_image, gallery, specifications, is_featured) VALUES 
('iphone-15-pro', 'iPhone 15 Pro', 'Titanium. So strong. So light. So Pro.', 'iPhone 15 Pro. Forged in titanium and featuring the groundbreaking A17 Pro chip, a customizable Action Button, and the most powerful iPhone camera system ever.', 'iPhone', 999.00, 'iphone-15-pro.jpg', 
'["iphone-15-pro-1.jpg", "iphone-15-pro-2.jpg", "iphone-15-pro-3.jpg"]', 
'{"display": "6.1-inch Super Retina XDR", "chip": "A17 Pro", "camera": "48MP Main, 12MP Ultra Wide, 12MP Telephoto", "battery": "Up to 23 hours video playback", "storage": ["128GB", "256GB", "512GB", "1TB"]}', 1),

('iphone-15', 'iPhone 15', 'New camera. New design. Newphoria.', 'iPhone 15. The Dynamic Island comes to iPhone 15 — along with the 48MP Main camera with 2x Telephoto, USB-C, and a durable color-infused glass design.', 'iPhone', 799.00, 'iphone-15.jpg', 
'["iphone-15-1.jpg", "iphone-15-2.jpg", "iphone-15-3.jpg"]', 
'{"display": "6.1-inch Super Retina XDR", "chip": "A16 Bionic", "camera": "48MP Main, 12MP Ultra Wide", "battery": "Up to 20 hours video playback", "storage": ["128GB", "256GB", "512GB"]}', 1),

('macbook-pro-14', 'MacBook Pro 14"', 'Mind-blowing. Head-turning.', 'MacBook Pro with M3, M3 Pro, and M3 Max chips. Up to 22 hours of battery life. Liquid Retina XDR display. Six speakers and spatial audio.', 'Mac', 1599.00, 'macbook-pro-14.jpg', 
'["macbook-pro-14-1.jpg", "macbook-pro-14-2.jpg", "macbook-pro-14-3.jpg"]', 
'{"display": "14.2-inch Liquid Retina XDR", "chip": "Apple M3", "memory": "8GB unified memory", "storage": "512GB SSD", "battery": "Up to 22 hours"}', 1),

('macbook-air-15', 'MacBook Air 15"', 'Impressively big. Impossibly thin.', 'MacBook Air 15". The world\'s thinnest 15-inch laptop. Supercharged by the M2 chip. Up to 18 hours of battery life.', 'Mac', 1299.00, 'macbook-air-15.jpg', 
'["macbook-air-15-1.jpg", "macbook-air-15-2.jpg", "macbook-air-15-3.jpg"]', 
'{"display": "15.3-inch Liquid Retina", "chip": "Apple M2", "memory": "8GB unified memory", "storage": "256GB SSD", "battery": "Up to 15 hours"}', 1),

('ipad-pro-12', 'iPad Pro 12.9"', 'Supercharged by M2.', 'iPad Pro with M2 chip delivers incredible performance and all-day battery life to handle your most demanding workflows.', 'iPad', 1099.00, 'ipad-pro-12.jpg', 
'["ipad-pro-12-1.jpg", "ipad-pro-12-2.jpg", "ipad-pro-12-3.jpg"]', 
'{"display": "12.9-inch Liquid Retina XDR", "chip": "Apple M2", "camera": "12MP Wide, 10MP Ultra Wide", "storage": ["128GB", "256GB", "512GB", "1TB", "2TB"]}', 1),

('apple-watch-series-9', 'Apple Watch Series 9', 'Smarter. Brighter. Mightier.', 'Apple Watch Series 9 with the new S9 chip. A magical new way to use your Apple Watch without touching the screen.', 'Watch', 399.00, 'watch-series-9.jpg', 
'["watch-series-9-1.jpg", "watch-series-9-2.jpg", "watch-series-9-3.jpg"]', 
'{"display": "Always-On Retina", "chip": "S9 SiP", "battery": "Up to 18 hours", "sizes": ["41mm", "45mm"], "connectivity": ["GPS", "GPS + Cellular"]}', 1);

-- Insert Product Variants
INSERT INTO product_variants (product_id, variant_name, color, storage, extra_price, stock, sku) VALUES 
-- iPhone 15 Pro variants
(1, 'Natural Titanium 128GB', 'Natural Titanium', '128GB', 0.00, 50, 'IP15P-NT-128'),
(1, 'Natural Titanium 256GB', 'Natural Titanium', '256GB', 100.00, 30, 'IP15P-NT-256'),
(1, 'Natural Titanium 512GB', 'Natural Titanium', '512GB', 300.00, 20, 'IP15P-NT-512'),
(1, 'Natural Titanium 1TB', 'Natural Titanium', '1TB', 500.00, 10, 'IP15P-NT-1TB'),
(1, 'Blue Titanium 128GB', 'Blue Titanium', '128GB', 0.00, 45, 'IP15P-BT-128'),
(1, 'Blue Titanium 256GB', 'Blue Titanium', '256GB', 100.00, 25, 'IP15P-BT-256'),
(1, 'White Titanium 128GB', 'White Titanium', '128GB', 0.00, 40, 'IP15P-WT-128'),
(1, 'Black Titanium 128GB', 'Black Titanium', '128GB', 0.00, 35, 'IP15P-BLT-128'),

-- iPhone 15 variants
(2, 'Pink 128GB', 'Pink', '128GB', 0.00, 60, 'IP15-PK-128'),
(2, 'Pink 256GB', 'Pink', '256GB', 100.00, 40, 'IP15-PK-256'),
(2, 'Pink 512GB', 'Pink', '512GB', 300.00, 25, 'IP15-PK-512'),
(2, 'Blue 128GB', 'Blue', '128GB', 0.00, 55, 'IP15-BL-128'),
(2, 'Green 128GB', 'Green', '128GB', 0.00, 50, 'IP15-GR-128'),
(2, 'Yellow 128GB', 'Yellow', '128GB', 0.00, 45, 'IP15-YL-128'),
(2, 'Black 128GB', 'Black', '128GB', 0.00, 65, 'IP15-BK-128'),

-- MacBook Pro 14" variants
(3, 'Space Gray M3 8GB 512GB', 'Space Gray', '512GB', 0.00, 25, 'MBP14-SG-M3-8-512'),
(3, 'Space Gray M3 8GB 1TB', 'Space Gray', '1TB', 200.00, 20, 'MBP14-SG-M3-8-1TB'),
(3, 'Silver M3 8GB 512GB', 'Silver', '512GB', 0.00, 30, 'MBP14-SL-M3-8-512'),
(3, 'Silver M3 8GB 1TB', 'Silver', '1TB', 200.00, 15, 'MBP14-SL-M3-8-1TB'),

-- MacBook Air 15" variants
(4, 'Midnight M2 8GB 256GB', 'Midnight', '256GB', 0.00, 40, 'MBA15-MD-M2-8-256'),
(4, 'Midnight M2 8GB 512GB', 'Midnight', '512GB', 200.00, 35, 'MBA15-MD-M2-8-512'),
(4, 'Starlight M2 8GB 256GB', 'Starlight', '256GB', 0.00, 45, 'MBA15-SL-M2-8-256'),
(4, 'Space Gray M2 8GB 256GB', 'Space Gray', '256GB', 0.00, 30, 'MBA15-SG-M2-8-256'),

-- iPad Pro 12.9" variants
(5, 'Space Gray WiFi 128GB', 'Space Gray', '128GB', 0.00, 35, 'IPP12-SG-W-128'),
(5, 'Space Gray WiFi 256GB', 'Space Gray', '256GB', 100.00, 30, 'IPP12-SG-W-256'),
(5, 'Space Gray WiFi 512GB', 'Space Gray', '512GB', 300.00, 25, 'IPP12-SG-W-512'),
(5, 'Silver WiFi 128GB', 'Silver', '128GB', 0.00, 40, 'IPP12-SL-W-128'),
(5, 'Silver WiFi 256GB', 'Silver', '256GB', 100.00, 35, 'IPP12-SL-W-256'),

-- Apple Watch Series 9 variants
(6, 'Pink Aluminum 41mm GPS', 'Pink', '41mm', 0.00, 50, 'AWS9-PK-AL-41-GPS'),
(6, 'Pink Aluminum 45mm GPS', 'Pink', '45mm', 30.00, 45, 'AWS9-PK-AL-45-GPS'),
(6, 'Midnight Aluminum 41mm GPS', 'Midnight', '41mm', 0.00, 60, 'AWS9-MD-AL-41-GPS'),
(6, 'Midnight Aluminum 45mm GPS', 'Midnight', '45mm', 30.00, 55, 'AWS9-MD-AL-45-GPS'),
(6, 'Starlight Aluminum 41mm GPS', 'Starlight', '41mm', 0.00, 40, 'AWS9-SL-AL-41-GPS'),
(6, 'Silver Aluminum 41mm GPS', 'Silver', '41mm', 0.00, 45, 'AWS9-AG-AL-41-GPS');

-- Insert FAQs
INSERT INTO faqs (question, answer, category, sort_order) VALUES 
('What payment methods do you accept?', 'We accept all major credit cards (Visa, MasterCard, American Express), PayPal, Apple Pay, and bank transfers.', 'Payment', 1),
('How long does shipping take?', 'Standard shipping takes 3-5 business days. Express shipping takes 1-2 business days. Free shipping is available for orders over $50.', 'Shipping', 2),
('Can I return or exchange my purchase?', 'Yes, we offer a 14-day return policy for all products in original condition. Exchanges are available for different colors or storage options.', 'Returns', 3),
('Do you offer warranty on products?', 'All Apple products come with a standard 1-year limited warranty. Extended warranty options are available at checkout.', 'Warranty', 4),
('How can I track my order?', 'Once your order ships, you\'ll receive a tracking number via email. You can also track your order in your account dashboard.', 'Orders', 5),
('Do you ship internationally?', 'Currently, we ship to the United States and Indonesia. International shipping rates and times vary by location.', 'Shipping', 6),
('Can I cancel my order?', 'Orders can be cancelled within 1 hour of placement. After that, please contact customer service for assistance.', 'Orders', 7),
('Do you price match?', 'We offer price matching on identical products from authorized retailers. Contact us with the competitor\'s price for review.', 'Pricing', 8);

-- Insert sample search logs
INSERT INTO search_logs (user_id, keyword, results_count, ip_address) VALUES 
(2, 'iPhone 15', 2, '192.168.1.100'),
(2, 'MacBook Pro', 1, '192.168.1.100'),
(3, 'iPad', 1, '192.168.1.101'),
(NULL, 'Apple Watch', 1, '192.168.1.102'),
(2, 'iPhone Pro Max', 0, '192.168.1.100');

-- Sample Orders
INSERT INTO orders (user_id, order_number, total, status, currency, shipping_address, payment_method) VALUES 
(2, 'APL-2025-001', 999.00, 'delivered', 'USD', 
'{"name": "John Doe", "address": "123 Main St", "city": "New York", "state": "NY", "zip": "10001", "country": "US", "phone": "+1-555-0123"}', 
'Credit Card'),
(3, 'APL-2025-002', 1599.00, 'shipped', 'USD', 
'{"name": "Jane Smith", "address": "456 Oak Ave", "city": "Jakarta", "state": "Jakarta", "zip": "12345", "country": "ID", "phone": "+62-21-1234567"}', 
'PayPal');

-- Sample Order Items
INSERT INTO order_items (order_id, product_id, variant_id, quantity, unit_price, total_price) VALUES 
(1, 1, 1, 1, 999.00, 999.00),
(2, 3, 17, 1, 1599.00, 1599.00);

-- Create indexes for better performance
CREATE INDEX idx_products_category ON products(category);
CREATE INDEX idx_products_featured ON products(is_featured);
CREATE INDEX idx_products_active ON products(is_active);
CREATE INDEX idx_orders_user ON orders(user_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_cart_sessions_session ON cart_sessions(session_id);
CREATE INDEX idx_search_logs_keyword ON search_logs(keyword);
CREATE INDEX idx_faqs_category ON faqs(category);