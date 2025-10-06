<?php
// Test file untuk sistem sinkronisasi database
require_once '../src/auth.php';

echo "<h2>Test Sistem Sinkronisasi Database</h2>";

// Test koneksi dan status sync
$auth = new Auth();

// Simulasi data registrasi untuk testing
$testData = [
    'name' => 'Test User Sync',
    'username' => 'testsync_' . time(),
    'email' => 'testsync_' . time() . '@example.com',
    'password' => 'password123',
    'country' => 'ID'
];

echo "<h3>Data Test:</h3>";
echo "<pre>";
print_r($testData);
echo "</pre>";

echo "<h3>Menjalankan Registrasi dengan Sync...</h3>";

$result = $auth->register(
    $testData['name'],
    $testData['username'], 
    $testData['email'],
    $testData['password'],
    $testData['country']
);

echo "<h3>Hasil Registrasi:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result['success']) {
    echo "<div style='color: green; padding: 10px; background: #e8f5e8; border: 1px solid #4CAF50;'>";
    echo "<strong>✅ BERHASIL!</strong><br>";
    echo $result['message'];
    
    if (isset($result['sync_status'])) {
        echo "<br><br><strong>Status Sinkronisasi:</strong><br>";
        echo "Database Utama: " . ($result['sync_status']['primary'] ? "✅ Berhasil (ID: {$result['sync_status']['primary']})" : "❌ Gagal") . "<br>";
        echo "Database Teman: " . ($result['sync_status']['secondary'] ? "✅ Berhasil (ID: {$result['sync_status']['secondary']})" : "❌ Gagal/Tidak Tersedia");
    }
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; background: #ffeaea; border: 1px solid #f44336;'>";
    echo "<strong>❌ GAGAL!</strong><br>";
    echo $result['message'];
    echo "</div>";
}

echo "<hr>";
echo "<h3>Catatan Penting:</h3>";
echo "<ul>";
echo "<li>Pastikan konfigurasi database kedua di <code>src/sync-config.php</code> sudah benar</li>";
echo "<li>Database teman harus dapat diakses dari server Anda</li>";
echo "<li>Struktur tabel <code>users</code> harus sama di kedua database</li>";
echo "<li>Jika sync gagal, data tetap tersimpan di database utama</li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
code { background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
</style>