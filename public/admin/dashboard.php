<?php
/**
 * Admin Dashboard
 * Main admin panel with overview and navigation
 */

require_once '../src/role-redirect.php';
require_once 'middleware-standalone.php';

// Ensure only admin can access
requireRole('admin');

// Use PDO connection from middleware
$pdo = getDbConnection();
$user = getAdminUser();

// Get statistics
try {
    $stats = [
        'total_products' => $pdo->query("SELECT COUNT(*) as count FROM products")->fetch()['count'] ?? 0,
        'total_users' => $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'] ?? 0,
        'total_orders' => $pdo->query("SELECT COUNT(*) as count FROM orders")->fetch()['count'] ?? 0,
        'total_feedback' => $pdo->query("SELECT COUNT(*) as count FROM feedback")->fetch()['count'] ?? 0,
        'active_products' => $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1")->fetch()['count'] ?? 0,
        'featured_products' => $pdo->query("SELECT COUNT(*) as count FROM products WHERE is_featured = 1")->fetch()['count'] ?? 0,
    ];
} catch (Exception $e) {
    $stats = [
        'total_products' => 0,
        'total_users' => 0,
        'total_orders' => 0,
        'total_feedback' => 0,
        'active_products' => 0,
        'featured_products' => 0,
    ];
}

// Get recent activities
try {
    $recent_products = $pdo->query("SELECT name, created_at FROM products ORDER BY created_at DESC LIMIT 5")->fetchAll();
    $recent_users = $pdo->query("SELECT name, username, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
} catch (Exception $e) {
    $recent_products = [];
    $recent_users = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Apple Store</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="admin-body">
    <!-- Admin Sidebar -->
    <div class="admin-sidebar">
        <div class="admin-logo">
            <i class="fab fa-apple"></i>
            <span>Apple Admin</span>
        </div>
        
        <nav class="admin-nav">
            <a href="dashboard.php" class="admin-nav-link active">
                <i class="fas fa-tachometer-alt"></i>
                Dashboard
            </a>
            <a href="products.php" class="admin-nav-link">
                <i class="fas fa-box"></i>
                Products
            </a>
            <a href="users.php" class="admin-nav-link">
                <i class="fas fa-users"></i>
                Users
            </a>
            <a href="orders.php" class="admin-nav-link">
                <i class="fas fa-shopping-cart"></i>
                Orders
            </a>
            <a href="feedback.php" class="admin-nav-link">
                <i class="fas fa-comments"></i>
                Feedback
            </a>
            <a href="content-editor.php" class="admin-nav-link">
                <i class="fas fa-edit"></i>
                Content Editor
            </a>
            <a href="settings.php" class="admin-nav-link">
                <i class="fas fa-cog"></i>
                Settings
            </a>
        </nav>
        
        <div class="admin-user">
            <div class="admin-user-avatar">
                <?php if (isset($user['profile_photo']) && $user['profile_photo'] && file_exists('../' . $user['profile_photo'])): ?>
                    <img src="../<?= htmlspecialchars($user['profile_photo']) ?>" alt="Admin">
                <?php else: ?>
                    <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="admin-user-info">
                <div class="admin-user-name"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></div>
                <div class="admin-user-role">Administrator</div>
            </div>
            <a href="../logout.php" class="admin-logout">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <!-- Admin Content -->
    <div class="admin-content">
        <!-- Admin Header -->
        <div class="admin-header">
            <h1>Dashboard</h1>
            <div class="admin-header-actions">
                <button class="admin-btn admin-btn-secondary" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
                <a href="../index.php" class="admin-btn admin-btn-primary">
                    <i class="fas fa-eye"></i>
                    View Site
                </a>
            </div>
        </div>

        <!-- Dashboard Stats -->
        <div class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="admin-stat-content">
                    <div class="admin-stat-number"><?= number_format($stats['total_products']) ?></div>
                    <div class="admin-stat-label">Total Products</div>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="admin-stat-content">
                    <div class="admin-stat-number"><?= number_format($stats['total_users']) ?></div>
                    <div class="admin-stat-label">Total Users</div>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="admin-stat-content">
                    <div class="admin-stat-number"><?= number_format($stats['total_orders']) ?></div>
                    <div class="admin-stat-label">Total Orders</div>
                </div>
            </div>
            
            <div class="admin-stat-card">
                <div class="admin-stat-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <div class="admin-stat-content">
                    <div class="admin-stat-number"><?= number_format($stats['total_feedback']) ?></div>
                    <div class="admin-stat-label">Feedback</div>
                </div>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div class="admin-dashboard-grid">
            <!-- Recent Products -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Recent Products</h3>
                    <a href="products.php" class="admin-link">View All</a>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($recent_products)): ?>
                        <div class="admin-list">
                            <?php foreach ($recent_products as $product): ?>
                                <div class="admin-list-item">
                                    <div class="admin-list-content">
                                        <div class="admin-list-title"><?= htmlspecialchars($product['name']) ?></div>
                                        <div class="admin-list-meta"><?= date('M j, Y', strtotime($product['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-box"></i>
                            <p>No products found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Recent Users</h3>
                    <a href="users.php" class="admin-link">View All</a>
                </div>
                <div class="admin-card-body">
                    <?php if (!empty($recent_users)): ?>
                        <div class="admin-list">
                            <?php foreach ($recent_users as $recent_user): ?>
                                <div class="admin-list-item">
                                    <div class="admin-list-avatar">
                                        <?= strtoupper(substr($recent_user['name'], 0, 1)) ?>
                                    </div>
                                    <div class="admin-list-content">
                                        <div class="admin-list-title"><?= htmlspecialchars($recent_user['name']) ?></div>
                                        <div class="admin-list-meta"><?= htmlspecialchars($recent_user['username'] ?? $recent_user['name']) ?> • <?= date('M j, Y', strtotime($recent_user['created_at'])) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-users"></i>
                            <p>No users found</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="admin-card-body">
                    <div class="admin-quick-actions">
                        <a href="products.php?action=add" class="admin-quick-action">
                            <i class="fas fa-plus"></i>
                            Add Product
                        </a>
                        <a href="users.php" class="admin-quick-action">
                            <i class="fas fa-user-plus"></i>
                            Manage Users
                        </a>
                        <a href="feedback.php" class="admin-quick-action">
                            <i class="fas fa-envelope"></i>
                            View Feedback
                        </a>
                        <a href="settings.php" class="admin-quick-action">
                            <i class="fas fa-cog"></i>
                            Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- System Info -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>System Information</h3>
                </div>
                <div class="admin-card-body">
                    <div class="admin-info-grid">
                        <div class="admin-info-item">
                            <span class="admin-info-label">Active Products:</span>
                            <span class="admin-info-value"><?= $stats['active_products'] ?></span>
                        </div>
                        <div class="admin-info-item">
                            <span class="admin-info-label">Featured Products:</span>
                            <span class="admin-info-value"><?= $stats['featured_products'] ?></span>
                        </div>
                        <div class="admin-info-item">
                            <span class="admin-info-label">PHP Version:</span>
                            <span class="admin-info-value"><?= PHP_VERSION ?></span>
                        </div>
                        <div class="admin-info-item">
                            <span class="admin-info-label">Server:</span>
                            <span class="admin-info-value"><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto refresh stats every 30 seconds
        setInterval(function() {
            const stats = document.querySelectorAll('.admin-stat-number');
            stats.forEach(stat => {
                stat.style.opacity = '0.7';
                setTimeout(() => {
                    stat.style.opacity = '1';
                }, 500);
            });
        }, 30000);

        // Add loading states for links
        document.querySelectorAll('.admin-nav-link, .admin-quick-action').forEach(link => {
            link.addEventListener('click', function(e) {
                if (!this.classList.contains('active')) {
                    this.style.opacity = '0.7';
                }
            });
        });
    </script>
</body>
</html>