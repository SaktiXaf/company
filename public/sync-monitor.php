<?php
// Monitor dan dashboard untuk sistem sinkronisasi database
require_once '../src/database-sync.php';

echo "<h1>📊 Database Sync Monitor</h1>";

try {
    // Load konfigurasi
    $syncConfig = include '../src/sync-config.php';
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h2>⚙️ Konfigurasi Sync</h2>";
    echo "<p><strong>Status:</strong> " . ($syncConfig['sync_settings']['enabled'] ? "🟢 Aktif" : "🔴 Nonaktif") . "</p>";
    echo "<p><strong>Database Utama:</strong> {$syncConfig['primary']['host']}/{$syncConfig['primary']['database']}</p>";
    echo "<p><strong>Database Teman:</strong> {$syncConfig['secondary']['host']}/{$syncConfig['secondary']['database']}</p>";
    echo "</div>";
    
    if ($syncConfig['sync_settings']['enabled']) {
        // Test koneksi
        $dbSync = new DatabaseSync($syncConfig['primary'], $syncConfig['secondary']);
        $status = $dbSync->getSyncStatus();
        
        echo "<div style='background: #f3e5f5; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h2>🔌 Status Koneksi</h2>";
        echo "<p><strong>Database Utama:</strong> " . ($status['primary_connected'] ? "🟢 Terhubung" : "🔴 Terputus") . "</p>";
        echo "<p><strong>Database Teman:</strong> " . ($status['secondary_connected'] ? "🟢 Terhubung" : "🔴 Terputus") . "</p>";
        echo "<p><strong>Sync Ready:</strong> " . ($status['sync_enabled'] ? "🟢 Siap" : "🔴 Tidak Siap") . "</p>";
        echo "</div>";
        
        // Statistik database
        if ($status['primary_connected']) {
            echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
            echo "<h2>📈 Statistik Database</h2>";
            
            try {
                // Koneksi manual untuk statistik
                $primaryPdo = new PDO(
                    "mysql:host={$syncConfig['primary']['host']};dbname={$syncConfig['primary']['database']};charset=utf8mb4",
                    $syncConfig['primary']['username'],
                    $syncConfig['primary']['password']
                );
                
                $stmt = $primaryPdo->query("SELECT COUNT(*) as total FROM users");
                $primaryCount = $stmt->fetch()['total'];
                
                echo "<p><strong>Total User Database Utama:</strong> {$primaryCount}</p>";
                
                if ($status['secondary_connected']) {
                    $secondaryPdo = new PDO(
                        "mysql:host={$syncConfig['secondary']['host']};dbname={$syncConfig['secondary']['database']};charset=utf8mb4",
                        $syncConfig['secondary']['username'],
                        $syncConfig['secondary']['password']
                    );
                    
                    $stmt = $secondaryPdo->query("SELECT COUNT(*) as total FROM users");
                    $secondaryCount = $stmt->fetch()['total'];
                    
                    echo "<p><strong>Total User Database Teman:</strong> {$secondaryCount}</p>";
                    
                    $diff = abs($primaryCount - $secondaryCount);
                    if ($diff == 0) {
                        echo "<p style='color: green;'>✅ Database tersinkron sempurna!</p>";
                    } else {
                        echo "<p style='color: orange;'>⚠️ Selisih data: {$diff} user</p>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ Tidak dapat mengecek database teman</p>";
                }
                
                // User terbaru
                $stmt = $primaryPdo->query("SELECT name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5");
                $recentUsers = $stmt->fetchAll();
                
                if ($recentUsers) {
                    echo "<h3>👥 5 User Terbaru (Database Utama):</h3>";
                    echo "<table style='width: 100%; border-collapse: collapse;'>";
                    echo "<tr style='background: #f0f0f0;'><th style='padding: 8px; border: 1px solid #ddd;'>Nama</th><th style='padding: 8px; border: 1px solid #ddd;'>Email</th><th style='padding: 8px; border: 1px solid #ddd;'>Tanggal</th></tr>";
                    foreach ($recentUsers as $user) {
                        echo "<tr>";
                        echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$user['name']}</td>";
                        echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$user['email']}</td>";
                        echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$user['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ Error mengambil statistik: " . $e->getMessage() . "</p>";
            }
            echo "</div>";
        }
        
        // Test sync
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h2>🧪 Test Sync</h2>";
        echo "<p>Klik tombol di bawah untuk menguji sinkronisasi dengan data dummy:</p>";
        
        if (isset($_POST['test_sync'])) {
            $testData = [
                'name' => 'Test Sync ' . date('Y-m-d H:i:s'),
                'username' => 'testsync_' . time(),
                'email' => 'testsync_' . time() . '@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'country' => 'ID',
                'role' => 'customer'
            ];
            
            try {
                $syncResults = $dbSync->syncUserRegistration($testData);
                
                echo "<div style='background: #d4edda; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
                echo "<h4>📊 Hasil Test:</h4>";
                echo "<p><strong>Database Utama:</strong> " . ($syncResults['primary'] ? "✅ Berhasil (ID: {$syncResults['primary']})" : "❌ Gagal") . "</p>";
                echo "<p><strong>Database Teman:</strong> " . ($syncResults['secondary'] ? "✅ Berhasil (ID: {$syncResults['secondary']})" : "❌ Gagal") . "</p>";
                echo "<p><strong>Data Test:</strong> {$testData['name']} ({$testData['email']})</p>";
                echo "</div>";
                
            } catch (Exception $e) {
                echo "<div style='background: #f8d7da; padding: 10px; border-radius: 3px; margin: 10px 0;'>";
                echo "<p style='color: red;'>❌ Test gagal: " . $e->getMessage() . "</p>";
                echo "</div>";
            }
        }
        
        echo "<form method='POST'>";
        echo "<button type='submit' name='test_sync' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>🚀 Jalankan Test Sync</button>";
        echo "</form>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
        echo "<h2>⚠️ Sync Nonaktif</h2>";
        echo "<p>Sistem sinkronisasi database sedang dinonaktifkan.</p>";
        echo "<p>Untuk mengaktifkan, ubah <code>enabled</code> menjadi <code>true</code> di file <code>src/sync-config.php</code></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h2>❌ Error</h2>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p>Periksa konfigurasi di <code>src/sync-config.php</code></p>";
    echo "</div>";
}

echo "<hr>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h2>📚 Quick Actions</h2>";
echo "<p>";
echo "<a href='test-sync.php' style='background: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>🧪 Test Manual</a>";
echo "<a href='register.php' style='background: #17a2b8; color: white; padding: 8px 16px; text-decoration: none; border-radius: 3px; margin-right: 10px;'>👤 Registrasi Baru</a>";
echo "<a href='setup-database-teman.php' style='background: #ffc107; color: black; padding: 8px 16px; text-decoration: none; border-radius: 3px;'>⚙️ Setup Database Teman</a>";
echo "</p>";
echo "</div>";
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    background: #f5f5f5; 
}
h1, h2, h3, h4 { color: #333; }
p { margin: 8px 0; }
table { margin: 10px 0; }
th { font-weight: bold; }
code { 
    background: #e9ecef; 
    padding: 2px 6px; 
    border-radius: 3px; 
    font-family: monospace; 
}
button:hover { opacity: 0.9; }
a:hover { opacity: 0.9; }
</style>