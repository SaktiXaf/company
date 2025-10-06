<?php
// Script untuk update database teman dengan tabel feedback dan tabel lainnya
require_once '../src/database-sync.php';

echo "🔄 Updating friend's database with missing tables...\n";

try {
    // Load config
    $config = include '../src/sync-config.php';
    
    if (!$config['sync_settings']['enabled']) {
        echo "⚠️ Sync is disabled in config. Enable it first.\n";
        exit;
    }
    
    // Connect to secondary database
    $secondaryPdo = new PDO(
        "mysql:host={$config['secondary']['host']};dbname={$config['secondary']['database']};charset=utf8mb4",
        $config['secondary']['username'],
        $config['secondary']['password']
    );
    
    echo "✅ Connected to friend's database: {$config['secondary']['database']}\n";
    
    // Create feedback table in secondary database
    $createFeedbackTable = "
    CREATE TABLE IF NOT EXISTS `feedback` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `subject` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `status` enum('unread','read','replied') DEFAULT 'unread',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `status` (`status`),
        KEY `created_at` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $secondaryPdo->exec($createFeedbackTable);
    echo "✅ Feedback table created in friend's database\n";
    
    // Create orders table
    $createOrdersTable = "
    CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        `shipping_address` text,
        `payment_method` varchar(50) DEFAULT 'cash_on_delivery',
        `notes` text,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`),
        KEY `status` (`status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $secondaryPdo->exec($createOrdersTable);
    echo "✅ Orders table created in friend's database\n";
    
    // Create order_items table
    $createOrderItemsTable = "
    CREATE TABLE IF NOT EXISTS `order_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `order_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `product_name` varchar(255) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        `quantity` int(11) NOT NULL,
        `subtotal` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $secondaryPdo->exec($createOrderItemsTable);
    echo "✅ Order items table created in friend's database\n";
    
    // Verify tables
    $stmt = $secondaryPdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "\n📊 Friend's database tables: " . implode(', ', $tables) . "\n";
    
    echo "\n🎉 DONE! Friend's database is now synchronized with missing tables.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Make sure friend's database is accessible and config is correct.\n";
}
?>