<?php
// Script untuk membuat tabel feedback yang hilang
require_once '../src/db.php';

echo "<h2>🔧 Fix Missing Feedback Table</h2>";

try {
    global $db;
    
    // Create feedback table
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($createFeedbackTable);
    echo "<p>✅ Tabel 'feedback' berhasil dibuat</p>";
    
    // Create orders table if not exists
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
        KEY `status` (`status`),
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($createOrdersTable);
    echo "<p>✅ Tabel 'orders' berhasil dibuat/diverifikasi</p>";
    
    // Create order_items table if not exists
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
        KEY `product_id` (`product_id`),
        FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($createOrderItemsTable);
    echo "<p>✅ Tabel 'order_items' berhasil dibuat/diverifikasi</p>";
    
    // Create product_variants table if not exists
    $createProductVariantsTable = "
    CREATE TABLE IF NOT EXISTS `product_variants` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `variant_name` varchar(100) NOT NULL,
        `variant_value` varchar(100) NOT NULL,
        `price_adjustment` decimal(10,2) DEFAULT '0.00',
        `stock` int(11) DEFAULT '0',
        `is_active` tinyint(1) DEFAULT '1',
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $db->query($createProductVariantsTable);
    echo "<p>✅ Tabel 'product_variants' berhasil dibuat/diverifikasi</p>";
    
    // Insert sample feedback data for testing
    $sampleFeedback = [
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'subject' => 'Great Service!',
            'message' => 'I love the new iPhone 15 Pro. The delivery was fast and the product quality is excellent!',
            'status' => 'unread'
        ],
        [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'subject' => 'Suggestion for Website',
            'message' => 'Could you add more payment options? I would like to use PayPal for my purchases.',
            'status' => 'read'
        ],
        [
            'name' => 'Mike Johnson',
            'email' => 'mike@example.com',
            'subject' => 'Issue with Order',
            'message' => 'My MacBook order was delayed. Please provide an update on the shipping status.',
            'status' => 'replied'
        ]
    ];
    
    foreach ($sampleFeedback as $feedback) {
        $db->query(
            "INSERT IGNORE INTO feedback (name, email, subject, message, status) VALUES (?, ?, ?, ?, ?)",
            [$feedback['name'], $feedback['email'], $feedback['subject'], $feedback['message'], $feedback['status']]
        );
    }
    
    echo "<p>✅ Sample feedback data berhasil ditambahkan</p>";
    
    // Show table status
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🎉 Database Tables Ready!</h3>";
    echo "<p><strong>Total Tables:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Available Tables:</strong> " . implode(', ', $tables) . "</p>";
    
    // Check feedback table content
    $feedbackCount = $db->fetch("SELECT COUNT(*) as count FROM feedback")['count'];
    echo "<p><strong>Feedback Records:</strong> {$feedbackCount}</p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p>Tabel 'feedback' dan tabel pendukung lainnya telah berhasil dibuat.</p>";
    echo "<p>Anda sekarang dapat mengakses halaman feedback admin tanpa error.</p>";
    echo "<p><a href='admin/feedback.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📋 Go to Admin Feedback</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>❌ Error Creating Tables</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solution:</strong> Check database connection and permissions</p>";
    echo "</div>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f5f5f5; 
}
h2, h3 { color: #333; }
p { margin: 8px 0; }
</style>