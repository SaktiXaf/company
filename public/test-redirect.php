<?php
require_once 'src/role-redirect.php';

// Start session for testing
session_start();

// Test scenarios
$testScenarios = [
    'No Login' => [
        'session' => [],
        'expected' => 'Should allow access to public pages'
    ],
    'Customer Login' => [
        'session' => [
            'logged_in' => true,
            'user_role' => 'customer',
            'user_name' => 'Test Customer'
        ],
        'expected' => 'Should redirect admin pages to index.php'
    ],
    'Admin Login' => [
        'session' => [
            'logged_in' => true,
            'user_role' => 'admin',
            'user_name' => 'Test Admin'
        ],
        'expected' => 'Should redirect customer pages to admin/dashboard.php'
    ]
];

$currentTest = $_GET['test'] ?? 'demo';

// Set test session if specified
if (isset($_GET['set_session'])) {
    $scenario = $_GET['set_session'];
    if (isset($testScenarios[$scenario])) {
        $_SESSION = $testScenarios[$scenario]['session'];
        header('Location: test-redirect.php?test=' . urlencode($scenario));
        exit;
    }
}

// Clear session if requested
if (isset($_GET['clear_session'])) {
    session_destroy();
    session_start();
    header('Location: test-redirect.php?test=cleared');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Role-based Redirects - Apple Store</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        
        .test-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e5e5e7;
        }
        
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            background: #f5f5f7;
            border-radius: 8px;
        }
        
        .test-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .test-btn {
            padding: 12px 20px;
            background: #007AFF;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .test-btn:hover {
            background: #0056CC;
            text-decoration: none;
        }
        
        .test-btn.secondary {
            background: #8E8E93;
        }
        
        .test-btn.secondary:hover {
            background: #6D6D70;
        }
        
        .test-btn.danger {
            background: #FF3B30;
        }
        
        .test-btn.danger:hover {
            background: #D70015;
        }
        
        .session-info {
            background: #E3F2FD;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .session-info h4 {
            margin: 0 0 10px 0;
            color: #1976D2;
        }
        
        .session-data {
            font-family: monospace;
            background: white;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        
        .nav-test {
            margin-top: 20px;
            padding: 15px;
            background: #FFF3E0;
            border-radius: 8px;
        }
        
        .redirect-results {
            margin-top: 20px;
            padding: 15px;
            background: #E8F5E8;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php echo addRoleBasedStyles(); ?>
    <?php echo addRoleBasedNavigation(); ?>
    
    <div class="test-container">
        <div class="test-header">
            <h1><i class="fas fa-vial"></i> Role-based Redirect Testing</h1>
            <p>Test the automatic redirect system for different user roles</p>
        </div>
        
        <div class="session-info">
            <h4>Current Session Status</h4>
            <div class="session-data">
                <?php
                echo '<strong>Session Data:</strong><br>';
                echo 'Logged In: ' . (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] ? 'Yes' : 'No') . '<br>';
                echo 'User Role: ' . ($_SESSION['user_role'] ?? 'None') . '<br>';
                echo 'User Name: ' . ($_SESSION['user_name'] ?? 'None') . '<br>';
                echo 'Session ID: ' . session_id() . '<br>';
                ?>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Session Setup</h3>
            <p>Set different session states to test redirect behavior:</p>
            <div class="test-actions">
                <a href="?set_session=No Login" class="test-btn secondary">No Login (Public)</a>
                <a href="?set_session=Customer Login" class="test-btn">Customer Login</a>
                <a href="?set_session=Admin Login" class="test-btn">Admin Login</a>
                <a href="?clear_session=1" class="test-btn danger">Clear Session</a>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Redirect Tests</h3>
            <p>Test these links with different sessions to see redirect behavior:</p>
            <div class="test-actions">
                <a href="index.php" class="test-btn" target="_blank">Main Site (index.php)</a>
                <a href="admin/dashboard.php" class="test-btn" target="_blank">Admin Dashboard</a>
                <a href="login.php" class="test-btn" target="_blank">Login Page</a>
                <a href="admin/products.php" class="test-btn" target="_blank">Admin Products</a>
            </div>
        </div>
        
        <div class="test-section">
            <h3>Expected Behaviors</h3>
            <ul>
                <li><strong>No Login:</strong> Can access public pages, redirected to login for admin pages</li>
                <li><strong>Customer Login:</strong> Can access public pages, redirected away from admin pages</li>
                <li><strong>Admin Login:</strong> Redirected to admin dashboard from public pages (unless preview mode)</li>
                <li><strong>Preview Mode:</strong> Admin can view public pages with ?preview=1</li>
            </ul>
        </div>
        
        <div class="nav-test">
            <h4>Navigation Bar Test</h4>
            <p>The navigation bar above should change based on your current session role.</p>
        </div>
        
        <div class="redirect-results">
            <h4>Redirect Functions Available</h4>
            <ul>
                <li><code>redirectBasedOnRole(['customer'], 'customer')</code> - Redirect if not customer</li>
                <li><code>requireRole('admin')</code> - Require admin role</li>
                <li><code>getRedirectUrl($role)</code> - Get redirect URL for role</li>
                <li><code>addRoleBasedNavigation()</code> - Add role-specific navigation</li>
            </ul>
        </div>
    </div>
    
    <script>
        // Auto-refresh session info every 5 seconds
        setInterval(function() {
            if (window.location.search.includes('test=')) {
                // Only refresh if we're in test mode
                setTimeout(function() {
                    location.reload();
                }, 5000);
            }
        }, 1000);
    </script>
</body>
</html>