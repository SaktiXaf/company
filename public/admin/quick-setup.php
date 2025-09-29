<?php

session_start();

// Check if already logged in as admin
if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin') {
    header('Location: dashboard-simple.php');
    exit();
}

$message = '';
$error = '';

// Handle quick login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_login'])) {
    // Set admin session for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['user_name'] = 'Test Admin';
    $_SESSION['user_email'] = 'admin@apple.com';
    $_SESSION['user_role'] = 'admin';
    $_SESSION['logged_in'] = true;
    
    header('Location: dashboard-simple.php');
    exit();
}

// Handle database setup
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_database'])) {
    try {
        // Database connection
        $host = 'localhost';
        $dbname = 'apple_clone';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Create admin user if not exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['admin@apple.com']);
        
        if (!$stmt->fetch()) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, 1, NOW(), NOW())
            ");
            $stmt->execute(['Test Admin', 'admin', 'admin@apple.com', $hashedPassword, 'admin']);
            $message = 'Admin user created successfully! Email: admin@apple.com, Password: admin123';
        } else {
            $message = 'Admin user already exists. Email: admin@apple.com, Password: admin123';
        }
        
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Admin Setup - Apple Admin</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }
        
        .setup-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        
        .logo {
            font-size: 48px;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #1d1d1f;
            margin-bottom: 16px;
            font-size: 28px;
        }
        
        p {
            color: #86868b;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        
        .setup-step {
            background: #f5f5f7;
            padding: 24px;
            border-radius: 12px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .setup-step h3 {
            color: #1d1d1f;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .step-number {
            background: #007AFF;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        
        .btn {
            display: inline-block;
            padding: 14px 24px;
            background: #007AFF;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #0056CC;
            transform: translateY(-1px);
        }
        
        .btn-secondary {
            background: #f5f5f7;
            color: #1d1d1f;
            border: 1px solid #d1d1d6;
        }
        
        .btn-secondary:hover {
            background: #e5e5ea;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        
        .credentials {
            background: #fff3cd;
            padding: 16px;
            border-radius: 8px;
            border-left: 4px solid #ffc107;
            margin-top: 20px;
            text-align: left;
        }
        
        .credentials strong {
            color: #856404;
        }
        
        .quick-links {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #d1d1d6;
        }
        
        .quick-links a {
            color: #007AFF;
            text-decoration: none;
            margin: 0 12px;
        }
        
        .quick-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <div class="logo">🍎</div>
        <h1>Apple Admin Setup</h1>
        <p>Quick setup for your Apple admin panel. Choose an option below to get started.</p>
        
        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="setup-step">
            <h3><span class="step-number">1</span> Quick Demo Access</h3>
            <p>Start testing immediately with demo admin access (no database required).</p>
            <form method="POST">
                <button type="submit" name="quick_login" class="btn">
                    🚀 Quick Demo Login
                </button>
            </form>
        </div>
        
        <div class="setup-step">
            <h3><span class="step-number">2</span> Full Database Setup</h3>
            <p>Create a real admin account in your database for full functionality.</p>
            <form method="POST">
                <button type="submit" name="setup_database" class="btn btn-secondary">
                    🗄️ Setup Database & Admin
                </button>
            </form>
        </div>
        
        <div class="credentials">
            <strong>Default Admin Credentials:</strong><br>
            Email: admin@apple.com<br>
            Password: admin123
        </div>
        
        <div class="quick-links">
            <a href="../index.php">← Back to Website</a>
            <a href="../login.php">Regular Login</a>
            <a href="dashboard-simple.php">Admin Dashboard</a>
        </div>
    </div>
</body>
</html>