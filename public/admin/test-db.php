<?php

require_once 'middleware-standalone.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test database connection
    $pdo = getDbConnection();
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test tables existence
    $tables = ['users', 'products', 'feedback', 'orders', 'order_items', 'product_variants'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<p style='color: green;'>✅ Table '$table' exists with $count records</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Table '$table' missing: " . $e->getMessage() . "</p>";
            $missingTables[] = $table;
        }
    }
    
    if (!empty($missingTables)) {
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border: 1px solid #ffeaa7;'>";
        echo "<h4 style='color: #856404; margin: 0 0 10px 0;'>⚠️ Missing Tables Detected</h4>";
        echo "<p style='color: #856404; margin: 0 0 15px 0;'>The following tables are missing: <strong>" . implode(', ', $missingTables) . "</strong></p>";
        echo "<p style='color: #856404; margin: 0;'><a href='setup-database.php' style='background: #007AFF; color: white; padding: 8px 16px; text-decoration: none; border-radius: 6px;'>🚀 Run Database Setup</a></p>";
        echo "</div>";
    }
    
    // Test admin user
    try {
        $user = getAdminUser();
        if ($user) {
            echo "<p style='color: green;'>✅ Admin user found: " . htmlspecialchars($user['name']) . "</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ No admin user found in session</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Admin user error: " . $e->getMessage() . "</p>";
    }
    
    // Test sample query
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role FROM users LIMIT 5");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Sample query error: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    
    // Show debug info
    echo "<h3>Debug Information:</h3>";
    echo "<p>Current directory: " . __DIR__ . "</p>";
    echo "<p>Middleware file: " . (file_exists('middleware-standalone.php') ? 'exists' : 'not found') . "</p>";
    echo "<p>Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive') . "</p>";
}

echo "<hr>";
echo "<p><a href='dashboard.php'>← Back to Dashboard</a></p>";
echo "<p><a href='../test-redirect.php'>Test Redirect System</a></p>";
?>