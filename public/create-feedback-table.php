<?php
// Simple script to create missing feedback table
require_once '../src/db.php';

echo "Creating missing feedback table...\n";

try {
    global $db;
    
    // Create feedback table
    $sql = "CREATE TABLE IF NOT EXISTS `feedback` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $db->query($sql);
    echo "✅ Feedback table created successfully!\n";
    
    // Add sample data
    $sampleData = [
        ['John Doe', 'john@example.com', 'Great Service!', 'I love the new iPhone 15 Pro. The delivery was fast and the product quality is excellent!', 'unread'],
        ['Jane Smith', 'jane@example.com', 'Suggestion for Website', 'Could you add more payment options? I would like to use PayPal for my purchases.', 'read'],
        ['Mike Johnson', 'mike@example.com', 'Issue with Order', 'My MacBook order was delayed. Please provide an update on the shipping status.', 'replied']
    ];
    
    foreach ($sampleData as $data) {
        $db->query(
            "INSERT IGNORE INTO feedback (name, email, subject, message, status) VALUES (?, ?, ?, ?, ?)",
            $data
        );
    }
    
    echo "✅ Sample feedback data added!\n";
    
    // Verify
    $count = $db->fetch("SELECT COUNT(*) as count FROM feedback")['count'];
    echo "✅ Total feedback records: {$count}\n";
    
    echo "\n🎉 DONE! You can now access admin/feedback.php without errors.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>