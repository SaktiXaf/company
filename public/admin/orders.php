<?php

require_once '../src/role-redirect.php';
require_once 'middleware-standalone.php';

// Ensure only admin can access
requireRole('admin');

// Use PDO connection from middleware-standalone
$pdo = getDbConnection();
$user = getAdminUser();

$message = '';
$error = '';
$action = $_GET['action'] ?? 'list';
$orderId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_status'])) {
            $id = intval($_POST['order_id']);
            $status = $_POST['status'];
            $notes = trim($_POST['notes'] ?? '');
            
            // Update order using PDO
            $sql = "UPDATE orders SET status = :status, notes = :notes, updated_at = :updated_at WHERE id = :id";
            $update_data = [
                'status' => $status,
                'notes' => $notes,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id
            ];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            $message = 'Order status updated successfully!';
            
        } elseif (isset($_POST['delete_order'])) {
            $id = intval($_POST['order_id']);
            
            // Delete order items first
            $stmt = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt->execute([$id]);
            
            // Then delete the order
            $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            
            $message = 'Order deleted successfully!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get orders for listing
$orders = [];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter) {
        $where_conditions[] = "o.status = ?";
        $params[] = $status_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email,
               COUNT(oi.id) as item_count
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        LEFT JOIN order_items oi ON o.id = oi.order_id
        $where_clause 
        GROUP BY o.id
        ORDER BY o.created_at DESC
    ");
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Failed to load orders: " . $e->getMessage();
}

// Get single order for viewing
$order = null;
$order_items = [];
if ($action === 'view' && $orderId) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, u.name as customer_name, u.email as customer_email, u.profile_photo
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            $stmt = $pdo->prepare("
                SELECT oi.*, p.name as product_name, p.main_image as product_image,
                       pv.name as variant_name, pv.color, pv.storage
                FROM order_items oi
                LEFT JOIN products p ON oi.product_id = p.id
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$orderId]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $error = "Failed to load order: " . $e->getMessage();
    }
}

// Get order statistics
$stats = [
    'total' => count($orders),
    'pending' => count(array_filter($orders, fn($o) => $o['status'] === 'pending')),
    'processing' => count(array_filter($orders, fn($o) => $o['status'] === 'processing')),
    'shipped' => count(array_filter($orders, fn($o) => $o['status'] === 'shipped')),
    'delivered' => count(array_filter($orders, fn($o) => $o['status'] === 'delivered')),
    'cancelled' => count(array_filter($orders, fn($o) => $o['status'] === 'cancelled'))
];

$total_revenue = array_sum(array_column($orders, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Apple Admin</title>
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
            <a href="dashboard.php" class="admin-nav-link">
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
            <a href="orders.php" class="admin-nav-link active">
                <i class="fas fa-shopping-cart"></i>
                Orders
            </a>
            <a href="feedback.php" class="admin-nav-link">
                <i class="fas fa-comments"></i>
                Feedback
            </a>
        </nav>
        
        <div class="admin-user">
            <div class="admin-user-avatar">
                <?= strtoupper(substr($user['name'] ?? 'A', 0, 1)) ?>
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
            <h1>Orders Management</h1>
            <div class="admin-header-actions">
                <div class="admin-stats-mini">
                    <span class="admin-stat-mini">
                        <i class="fas fa-shopping-cart"></i>
                        <?= $stats['total'] ?> Orders
                    </span>
                    <span class="admin-stat-mini">
                        <i class="fas fa-dollar-sign"></i>
                        $<?= number_format($total_revenue, 2) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="admin-alert admin-alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="admin-alert admin-alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'view'): ?>
            <!-- View Order Details -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Order Details</h3>
                    <a href="orders.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if ($order): ?>
                        <div class="order-detail">
                            <!-- Order Header -->
                            <div class="order-header">
                                <div class="order-info">
                                    <h4>Order #<?= htmlspecialchars($order['order_number']) ?></h4>
                                    <div class="order-meta">
                                        <span>Placed on <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?></span>
                                        <span class="admin-badge admin-badge-<?= 
                                            $order['status'] === 'pending' ? 'warning' : 
                                            ($order['status'] === 'processing' ? 'info' : 
                                            ($order['status'] === 'shipped' ? 'primary' : 
                                            ($order['status'] === 'delivered' ? 'success' : 'danger'))) ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="order-total">
                                    <div class="order-amount">$<?= number_format($order['total_amount'], 2) ?></div>
                                    <div class="order-items-count"><?= count($order_items) ?> item(s)</div>
                                </div>
                            </div>

                            <!-- Customer Information -->
                            <div class="order-section">
                                <h5>Customer Information</h5>
                                <div class="order-customer">
                                    <div class="admin-user-info">
                                        <div class="admin-user-avatar-md">
                                            <?php if (isset($order['profile_photo']) && $order['profile_photo'] && file_exists('../' . $order['profile_photo'])): ?>
                                                <img src="../<?= htmlspecialchars($order['profile_photo']) ?>" alt="<?= htmlspecialchars($order['customer_name']) ?>">
                                            <?php else: ?>
                                                <?= strtoupper(substr($order['customer_name'] ?? 'A', 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="admin-user-name"><?= htmlspecialchars($order['customer_name'] ?? 'Guest Customer') ?></div>
                                            <div class="admin-user-email"><?= htmlspecialchars($order['customer_email'] ?? 'No email') ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shipping Information -->
                            <?php if ($order['shipping_address']): ?>
                                <div class="order-section">
                                    <h5>Shipping Address</h5>
                                    <div class="order-address">
                                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Order Items -->
                            <div class="order-section">
                                <h5>Order Items</h5>
                                <div class="order-items">
                                    <?php foreach ($order_items as $item): ?>
                                        <div class="order-item">
                                            <div class="order-item-image">
                                                <?php if ($item['product_image'] && file_exists('../' . $item['product_image'])): ?>
                                                    <img src="../<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>">
                                                <?php else: ?>
                                                    <div class="order-item-placeholder">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="order-item-details">
                                                <div class="order-item-name"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <?php if ($item['variant_name']): ?>
                                                    <div class="order-item-variant">
                                                        <?= htmlspecialchars($item['variant_name']) ?>
                                                        <?php if ($item['color']): ?>
                                                            - <?= htmlspecialchars($item['color']) ?>
                                                        <?php endif; ?>
                                                        <?php if ($item['storage']): ?>
                                                            - <?= htmlspecialchars($item['storage']) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="order-item-quantity">Quantity: <?= $item['quantity'] ?></div>
                                            </div>
                                            <div class="order-item-price">
                                                <div class="order-item-unit-price">$<?= number_format($item['price'], 2) ?> each</div>
                                                <div class="order-item-total-price">$<?= number_format($item['price'] * $item['quantity'], 2) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Order Summary -->
                            <div class="order-section">
                                <h5>Order Summary</h5>
                                <div class="order-summary">
                                    <div class="order-summary-row">
                                        <span>Subtotal:</span>
                                        <span>$<?= number_format($order['subtotal'] ?? $order['total_amount'], 2) ?></span>
                                    </div>
                                    <div class="order-summary-row">
                                        <span>Shipping:</span>
                                        <span>$<?= number_format($order['shipping_cost'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="order-summary-row">
                                        <span>Tax:</span>
                                        <span>$<?= number_format($order['tax_amount'] ?? 0, 2) ?></span>
                                    </div>
                                    <div class="order-summary-row order-summary-total">
                                        <span>Total:</span>
                                        <span>$<?= number_format($order['total_amount'], 2) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin Notes -->
                            <?php if ($order['notes']): ?>
                                <div class="order-section">
                                    <h5>Admin Notes</h5>
                                    <div class="order-notes">
                                        <?= nl2br(htmlspecialchars($order['notes'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Update Status Form -->
                            <div class="order-section">
                                <h5>Update Order</h5>
                                <form method="POST" class="admin-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    
                                    <div class="admin-form-row">
                                        <div class="admin-form-group">
                                            <label>Order Status</label>
                                            <select name="status" required>
                                                <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                                <option value="processing" <?= $order['status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                                                <option value="shipped" <?= $order['status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                                <option value="delivered" <?= $order['status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                                <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="admin-form-group">
                                        <label>Admin Notes</label>
                                        <textarea name="notes" rows="4" placeholder="Add notes about this order..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="admin-form-actions">
                                        <button type="submit" name="update_status" class="admin-btn admin-btn-primary">
                                            <i class="fas fa-save"></i>
                                            Update Order
                                        </button>
                                        <a href="orders.php" class="admin-btn admin-btn-secondary">Back to List</a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-receipt"></i>
                            <h3>Order Not Found</h3>
                            <p>The requested order could not be found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php else: ?>
            <!-- Orders Statistics -->
            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['total'] ?></div>
                        <div class="admin-stat-label">Total Orders</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['pending'] ?></div>
                        <div class="admin-stat-label">Pending</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['shipped'] ?></div>
                        <div class="admin-stat-label">Shipped</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number">$<?= number_format($total_revenue, 2) ?></div>
                        <div class="admin-stat-label">Total Revenue</div>
                    </div>
                </div>
            </div>

            <!-- Orders List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Orders (<?= count($orders) ?>)</h3>
                    
                    <!-- Search and Filter -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-search-form">
                            <input type="text" name="search" placeholder="Search orders..." value="<?= htmlspecialchars($search) ?>">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card-body">
                    <?php if (!empty($orders)): ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Order #</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= htmlspecialchars($o['order_number']) ?></strong>
                                            </td>
                                            <td>
                                                <div class="order-customer-info">
                                                    <div><?= htmlspecialchars($o['customer_name'] ?? 'Guest Customer') ?></div>
                                                    <div class="order-customer-email"><?= htmlspecialchars($o['customer_email'] ?? 'No email') ?></div>
                                                </div>
                                            </td>
                                            <td><?= $o['item_count'] ?> item(s)</td>
                                            <td><strong>$<?= number_format($o['total_amount'], 2) ?></strong></td>
                                            <td>
                                                <span class="admin-badge admin-badge-<?= 
                                                    $o['status'] === 'pending' ? 'warning' : 
                                                    ($o['status'] === 'processing' ? 'info' : 
                                                    ($o['status'] === 'shipped' ? 'primary' : 
                                                    ($o['status'] === 'delivered' ? 'success' : 'danger'))) ?>">
                                                    <?= ucfirst($o['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <a href="orders.php?action=view&id=<?= $o['id'] ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this order?')">
                                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                        <button type="submit" name="delete_order" class="admin-btn admin-btn-sm admin-btn-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>No Orders Found</h3>
                            <p>No orders match your search criteria.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-hide success messages
        setTimeout(() => {
            const successAlerts = document.querySelectorAll('.admin-alert-success');
            successAlerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>