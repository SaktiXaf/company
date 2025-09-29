<?php

require_once 'middleware-standalone.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Apple Store Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
            background: #f5f5f7;
            margin: 0;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #007AFF, #5856D6);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .content {
            padding: 30px;
        }
        
        .status {
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
        
        .info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }
        
        .btn {
            background: #007AFF;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #0056CC;
            text-decoration: none;
        }
        
        .btn-secondary {
            background: #8E8E93;
        }
        
        .btn-secondary:hover {
            background: #6D6D70;
        }
        
        .table-status {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        
        .table-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .table-card.exists {
            background: #d4edda;
            border: 1px solid #c3e6cb;
        }
        
        .table-card.missing {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        
        pre {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
        
        .progress {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: #007AFF;
            transition: width 0.3s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-database"></i> Database Setup</h1>
            <p>Setup required tables for Apple Store Admin Panel</p>
        </div>
        
        <div class="content">
            <?php
            $setup_action = $_GET['action'] ?? 'check';
            
            if ($setup_action === 'setup') {
                echo '<h2>🚀 Running Database Setup...</h2>';
                echo '<div class="progress"><div class="progress-bar" style="width: 0%"></div></div>';
                
                try {
                    $pdo = getDbConnection();
                    echo '<div class="status success">✅ Database connection successful!</div>';
                    
                    // Read SQL file
                    $sqlFile = '../database/create_admin_tables.sql';
                    if (!file_exists($sqlFile)) {
                        throw new Exception("SQL file not found: $sqlFile");
                    }
                    
                    $sql = file_get_contents($sqlFile);
                    echo '<div class="status info">📄 SQL script loaded successfully</div>';
                    
                    // Split into individual statements
                    $statements = array_filter(
                        array_map('trim', explode(';', $sql)),
                        function($stmt) {
                            return !empty($stmt) && !preg_match('/^--/', $stmt);
                        }
                    );
                    
                    $totalStatements = count($statements);
                    $executedStatements = 0;
                    
                    echo "<div class='status info'>📊 Found $totalStatements SQL statements to execute</div>";
                    
                    // Execute each statement
                    $pdo->beginTransaction();
                    
                    foreach ($statements as $index => $statement) {
                        try {
                            if (trim($statement)) {
                                $pdo->exec($statement);
                                $executedStatements++;
                                
                                $progress = round(($executedStatements / $totalStatements) * 100);
                                echo "<script>
                                    document.querySelector('.progress-bar').style.width = '{$progress}%';
                                </script>";
                                flush();
                            }
                        } catch (Exception $e) {
                            // Some statements might fail if tables already exist, that's OK
                            if (!strpos($e->getMessage(), 'already exists')) {
                                echo "<div class='status warning'>⚠️ Statement " . ($index + 1) . ": " . substr($e->getMessage(), 0, 100) . "...</div>";
                            }
                        }
                    }
                    
                    $pdo->commit();
                    
                    echo '<div class="status success">✅ Database setup completed successfully!</div>';
                    echo "<div class='status info'>📈 Executed $executedStatements out of $totalStatements statements</div>";
                    
                    // Verify tables exist
                    $tables = ['users', 'products', 'feedback', 'orders', 'order_items', 'product_variants'];
                    $tableStatus = [];
                    
                    foreach ($tables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                            $count = $stmt->fetchColumn();
                            $tableStatus[$table] = ['exists' => true, 'count' => $count];
                        } catch (Exception $e) {
                            $tableStatus[$table] = ['exists' => false, 'error' => $e->getMessage()];
                        }
                    }
                    
                    echo '<h3>📋 Table Status</h3>';
                    echo '<div class="table-status">';
                    
                    foreach ($tableStatus as $table => $status) {
                        $class = $status['exists'] ? 'exists' : 'missing';
                        $icon = $status['exists'] ? '✅' : '❌';
                        $info = $status['exists'] ? $status['count'] . ' records' : 'Missing';
                        
                        echo "<div class='table-card $class'>";
                        echo "<strong>$icon $table</strong><br>";
                        echo "<small>$info</small>";
                        echo "</div>";
                    }
                    
                    echo '</div>';
                    
                    // Create admin user if needed
                    try {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                        $stmt->execute();
                        $adminCount = $stmt->fetchColumn();
                        
                        if ($adminCount == 0) {
                            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("
                                INSERT INTO users (name, username, email, password, role, is_active, created_at) 
                                VALUES (?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([
                                'System Administrator',
                                'admin',
                                'admin@applestore.com',
                                $adminPassword,
                                'admin',
                                1
                            ]);
                            
                            echo '<div class="status success">👤 Admin user created! Username: admin, Password: admin123</div>';
                        } else {
                            echo "<div class='status info'>👤 Found $adminCount admin user(s) in database</div>";
                        }
                    } catch (Exception $e) {
                        echo '<div class="status warning">⚠️ Could not create admin user: ' . $e->getMessage() . '</div>';
                    }
                    
                } catch (Exception $e) {
                    if (isset($pdo)) {
                        $pdo->rollback();
                    }
                    echo '<div class="status error">❌ Setup failed: ' . $e->getMessage() . '</div>';
                }
                
            } else {
                // Check current status
                echo '<h2>🔍 Current Database Status</h2>';
                
                try {
                    $pdo = getDbConnection();
                    echo '<div class="status success">✅ Database connection successful!</div>';
                    
                    // Check required tables
                    $requiredTables = ['users', 'products', 'feedback', 'orders', 'order_items', 'product_variants'];
                    $existingTables = [];
                    $missingTables = [];
                    
                    foreach ($requiredTables as $table) {
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                            $count = $stmt->fetchColumn();
                            $existingTables[$table] = $count;
                        } catch (Exception $e) {
                            $missingTables[] = $table;
                        }
                    }
                    
                    echo '<div class="table-status">';
                    
                    foreach ($requiredTables as $table) {
                        if (isset($existingTables[$table])) {
                            echo "<div class='table-card exists'>";
                            echo "<strong>✅ $table</strong><br>";
                            echo "<small>{$existingTables[$table]} records</small>";
                            echo "</div>";
                        } else {
                            echo "<div class='table-card missing'>";
                            echo "<strong>❌ $table</strong><br>";
                            echo "<small>Missing</small>";
                            echo "</div>";
                        }
                    }
                    
                    echo '</div>';
                    
                    if (!empty($missingTables)) {
                        echo '<div class="status warning">⚠️ Missing tables: ' . implode(', ', $missingTables) . '</div>';
                        echo '<div class="status info">🔧 Click "Run Setup" to create missing tables and populate with sample data</div>';
                    } else {
                        echo '<div class="status success">🎉 All required tables exist!</div>';
                    }
                    
                } catch (Exception $e) {
                    echo '<div class="status error">❌ Database connection failed: ' . $e->getMessage() . '</div>';
                    echo '<div class="status info">💡 Please check your database configuration in middleware-standalone.php</div>';
                }
            }
            ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <?php if ($setup_action !== 'setup'): ?>
                    <a href="?action=setup" class="btn">🚀 Run Database Setup</a>
                    <a href="?action=check" class="btn btn-secondary">🔄 Refresh Status</a>
                <?php endif; ?>
                
                <a href="dashboard.php" class="btn btn-secondary">📊 Go to Dashboard</a>
                <a href="test-db.php" class="btn btn-secondary">🧪 Test Database</a>
            </div>
            
            <div style="margin-top: 30px;">
                <h3>📝 What this setup does:</h3>
                <ul>
                    <li>✅ Creates <strong>feedback</strong> table for customer feedback and suggestions</li>
                    <li>✅ Creates <strong>orders</strong> table for customer orders</li>
                    <li>✅ Creates <strong>order_items</strong> table for order line items</li>
                    <li>✅ Creates <strong>product_variants</strong> table for product variations</li>
                    <li>✅ Updates <strong>products</strong> and <strong>users</strong> tables with missing columns</li>
                    <li>✅ Adds sample data for testing</li>
                    <li>✅ Creates admin user (if none exists)</li>
                    <li>✅ Adds database indexes for better performance</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>