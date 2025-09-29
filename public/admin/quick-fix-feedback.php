<?php

require_once 'middleware-standalone.php';

try {
    $pdo = getDbConnection();
    echo "<h2>Creating Feedback Table...</h2>";
    
    // Create feedback table
    $sql = "CREATE TABLE IF NOT EXISTS `feedback` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    $pdo->exec($sql);
    echo "<p style='color: green;'>✅ Feedback table created successfully!</p>";
    
    // Insert sample data
    $insertSql = "INSERT IGNORE INTO `feedback` (`name`, `email`, `subject`, `message`, `type`, `status`, `rating`, `created_at`) VALUES
    ('John Customer', 'john@example.com', 'Great iPhone experience!', 'I love my new iPhone 15 Pro Max. The camera quality is amazing and the battery lasts all day.', 'feedback', 'pending', 5, '2024-01-15 10:30:00'),
    ('Sarah Wilson', 'sarah@example.com', 'Suggestion for website', 'It would be great if you could add a comparison feature between different iPhone models.', 'suggestion', 'reviewed', NULL, '2024-01-14 14:20:00'),
    ('Mike Tech', 'mike@example.com', 'MacBook delivery issue', 'My MacBook Pro was supposed to arrive yesterday but I have not received it yet. Please help.', 'complaint', 'replied', 2, '2024-01-13 09:15:00'),
    ('Lisa Admin', 'lisa@example.com', 'Website bug report', 'The search function on the website does not work properly when searching for iPad accessories.', 'bug_report', 'resolved', NULL, '2024-01-12 16:45:00'),
    ('David Customer', 'david@example.com', 'Apple Watch feedback', 'The new Apple Watch Series 9 is fantastic! Health monitoring features are very accurate.', 'feedback', 'pending', 5, '2024-01-11 11:30:00')";
    
    $pdo->exec($insertSql);
    echo "<p style='color: green;'>✅ Sample feedback data inserted!</p>";
    
    // Check if table exists and has data
    $stmt = $pdo->query("SELECT COUNT(*) FROM feedback");
    $count = $stmt->fetchColumn();
    echo "<p style='color: blue;'>📊 Feedback table now has $count records</p>";
    
    echo "<h3>Quick Test:</h3>";
    $stmt = $pdo->query("SELECT id, name, subject, type, status FROM feedback LIMIT 3");
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Subject</th><th>Type</th><th>Status</th></tr>";
    
    foreach ($feedbacks as $feedback) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($feedback['id']) . "</td>";
        echo "<td>" . htmlspecialchars($feedback['name']) . "</td>";
        echo "<td>" . htmlspecialchars($feedback['subject']) . "</td>";
        echo "<td>" . htmlspecialchars($feedback['type']) . "</td>";
        echo "<td>" . htmlspecialchars($feedback['status']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<h3>✅ Success!</h3>";
    echo "<p>You can now access <a href='feedback.php'>admin/feedback.php</a> without errors.</p>";
    echo "<p><a href='feedback.php'>Go to Feedback Management</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<p>Please check your database configuration.</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f5;
}

table {
    background: white;
    margin: 20px 0;
}

th, td {
    padding: 8px 12px;
    text-align: left;
}

th {
    background: #007AFF;
    color: white;
}

tr:nth-child(even) {
    background: #f8f9fa;
}

a {
    color: #007AFF;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}
</style>