<?php
// Setup script untuk menyiapkan database teman
// Jalankan script ini di server teman Anda

echo "<h2>🔧 Database Sync Setup Script</h2>";

// Konfigurasi database (sesuaikan dengan server teman)
$config = [
    'host' => 'localhost',
    'database' => 'apple_clone_friend', // Nama database untuk teman
    'username' => 'root',
    'password' => ''
];

try {
    // Koneksi ke MySQL
    $pdo = new PDO(
        "mysql:host={$config['host']};charset=utf8mb4", 
        $config['username'], 
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Koneksi MySQL berhasil</p>";
    
    // Buat database jika belum ada
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ Database '{$config['database']}' telah dibuat/tersedia</p>";
    
    // Pilih database
    $pdo->exec("USE `{$config['database']}`");
    
    // Buat tabel users
    $createUsersTable = "
    CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `username` varchar(50) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `country` varchar(2) DEFAULT 'US',
        `role` enum('customer','admin') DEFAULT 'customer',
        `is_active` tinyint(1) DEFAULT '1',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `username` (`username`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createUsersTable);
    echo "<p>✅ Tabel 'users' telah dibuat</p>";
    
    // Buat tabel products (opsional, untuk sinkronisasi produk)
    $createProductsTable = "
    CREATE TABLE IF NOT EXISTS `products` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) NOT NULL,
        `description` text,
        `price` decimal(10,2) NOT NULL,
        `stock` int(11) DEFAULT '0',
        `category` varchar(100),
        `main_image` varchar(255),
        `gallery` text,
        `is_active` tinyint(1) DEFAULT '1',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createProductsTable);
    echo "<p>✅ Tabel 'products' telah dibuat</p>";
    
    // Buat tabel feedback
    $createFeedbackTable = "
    CREATE TABLE IF NOT EXISTS `feedback` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `subject` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `status` enum('unread','read','replied') DEFAULT 'unread',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createFeedbackTable);
    echo "<p>✅ Tabel 'feedback' telah dibuat</p>";
    
    // Buat tabel orders
    $createOrdersTable = "
    CREATE TABLE IF NOT EXISTS `orders` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `total_amount` decimal(10,2) NOT NULL,
        `status` enum('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
        `shipping_address` text,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $pdo->exec($createOrdersTable);
    echo "<p>✅ Tabel 'orders' telah dibuat</p>";
    
    // Test insert data sample
    $testUser = [
        'name' => 'Test Sync User',
        'username' => 'testsync_setup',
        'email' => 'testsync@example.com',
        'password' => password_hash('password123', PASSWORD_DEFAULT),
        'country' => 'ID',
        'role' => 'customer'
    ];
    
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO users (name, username, email, password, country, role) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    if ($stmt->execute(array_values($testUser))) {
        echo "<p>✅ Test user berhasil dibuat</p>";
    }
    
    // Cek jumlah tabel
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🎉 Setup Berhasil!</h3>";
    echo "<p><strong>Database:</strong> {$config['database']}</p>";
    echo "<p><strong>Total Tabel:</strong> " . count($tables) . "</p>";
    echo "<p><strong>Tabel yang dibuat:</strong> " . implode(', ', $tables) . "</p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>📝 Langkah Selanjutnya:</h3>";
    echo "<ol>";
    echo "<li>Catat konfigurasi database ini</li>";
    echo "<li>Pastikan user database memiliki permission remote access</li>";
    echo "<li>Update file <code>sync-config.php</code> di server utama</li>";
    echo "<li>Test koneksi dari server utama</li>";
    echo "</ol>";
    
    echo "<h3>🔧 Konfigurasi untuk sync-config.php:</h3>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
    echo "'secondary' => [" . PHP_EOL;
    echo "    'host' => '{$config['host']}'," . PHP_EOL;
    echo "    'database' => '{$config['database']}'," . PHP_EOL;
    echo "    'username' => '{$config['username']}'," . PHP_EOL;
    echo "    'password' => '{$config['password']}'" . PHP_EOL;  
    echo "]";
    echo "</pre>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>❌ Error Setup Database</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Solusi:</strong></p>";
    echo "<ul>";
    echo "<li>Periksa koneksi database</li>";
    echo "<li>Pastikan user memiliki permission CREATE DATABASE</li>";
    echo "<li>Cek konfigurasi host, username, password</li>";
    echo "</ul>";
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
pre { 
    background: #f8f9fa; 
    padding: 10px; 
    border-radius: 5px; 
    overflow-x: auto; 
}
code { 
    background: #e9ecef; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
ol, ul { margin-left: 20px; }
</style>