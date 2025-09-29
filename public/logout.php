<?php

require_once 'src/role-redirect.php';
require_once '../src/auth.php';

// Get user info before logout
$wasAdmin = false;
if ($auth->isLoggedIn()) {
    $user = $auth->getCurrentUser();
    $wasAdmin = ($user && $user['role'] === 'admin');
}

// Perform logout
$auth->logout();

// Set success message based on previous role
$message = $wasAdmin ? 'Admin session ended successfully.' : 'You have been logged out successfully.';
$redirectTo = $wasAdmin ? 'admin/login.php' : 'index.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logged Out - Apple Store</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .logout-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 80vh;
            padding: 20px;
        }
        
        .logout-card {
            background: white;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        
        .logout-icon {
            font-size: 48px;
            color: #007AFF;
            margin-bottom: 20px;
        }
        
        .logout-title {
            font-size: 24px;
            font-weight: 600;
            color: #1d1d1f;
            margin-bottom: 12px;
        }
        
        .logout-message {
            color: #86868b;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .logout-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
        }
        
        .logout-btn {
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #007AFF;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056CC;
            text-decoration: none;
        }
        
        .btn-secondary {
            background: #f5f5f7;
            color: #1d1d1f;
        }
        
        .btn-secondary:hover {
            background: #e8e8ed;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="index.php" class="navbar-brand">
                <i class="fab fa-apple"></i> Apple
            </a>
        </div>
    </nav>

    <div class="logout-container">
        <div class="logout-card">
            <div class="logout-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="logout-title">Successfully Logged Out</h1>
            <p class="logout-message"><?php echo htmlspecialchars($message); ?></p>
            
            <div class="logout-actions">
                <a href="login.php" class="logout-btn btn-primary">Sign In Again</a>
                <a href="index.php" class="logout-btn btn-secondary">Browse Products</a>
            </div>
        </div>
    </div>

    <script>
        // Auto redirect after 5 seconds
        setTimeout(function() {
            window.location.href = '<?php echo $redirectTo; ?>';
        }, 5000);
    </script>
</body>
</html>
?>