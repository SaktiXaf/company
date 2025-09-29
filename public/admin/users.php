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
$userId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_user'])) {
            $id = intval($_POST['user_id']);
            $name = trim($_POST['name']);
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $role = $_POST['role'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Check if email exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                throw new Exception('Email already exists for another user');
            }
            
            // Check if username exists for other users
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                throw new Exception('Username already exists for another user');
            }
            
            // Update user using PDO
            $sql = "UPDATE users SET name = :name, username = :username, email = :email, role = :role, 
                    is_active = :is_active, updated_at = :updated_at WHERE id = :id";
            
            // Update user using PDO
            $sql = "UPDATE users SET name = :name, username = :username, email = :email, role = :role, is_active = :is_active, updated_at = :updated_at WHERE id = :id";
            $update_data = [
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'role' => $role,
                'is_active' => $is_active,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id
            ];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            $message = 'User updated successfully!';
            
        } elseif (isset($_POST['delete_user'])) {
            $id = intval($_POST['user_id']);
            
            // Don't allow deleting own account
            if ($id == $_SESSION['user_id']) {
                throw new Exception('Cannot delete your own account');
            }
            
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'User deleted successfully!';
            
        } elseif (isset($_POST['toggle_status'])) {
            $id = intval($_POST['user_id']);
            $current_status = intval($_POST['current_status']);
            $new_status = $current_status ? 0 : 1;
            
            $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            $message = 'User status updated successfully!';
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get users for listing
$users = [];
$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(name LIKE ? OR email LIKE ? OR username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($role_filter) {
        $where_conditions[] = "role = ?";
        $params[] = $role_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Failed to load users: " . $e->getMessage();
}

// Get single user for editing
$edit_user = null;
if ($action === 'edit' && $userId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to load user: " . $e->getMessage();
    }
}

// Get user statistics
$stats = [
    'total' => count($users),
    'active' => count(array_filter($users, fn($u) => $u['is_active'])),
    'admins' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
    'customers' => count(array_filter($users, fn($u) => $u['role'] === 'customer'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Apple Admin</title>
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
            <a href="users.php" class="admin-nav-link active">
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
            <h1>Users Management</h1>
            <div class="admin-header-actions">
                <div class="admin-stats-mini">
                    <span class="admin-stat-mini">
                        <i class="fas fa-users"></i>
                        <?= $stats['total'] ?> Total
                    </span>
                    <span class="admin-stat-mini">
                        <i class="fas fa-user-check"></i>
                        <?= $stats['active'] ?> Active
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

        <?php if ($action === 'edit'): ?>
            <!-- Edit User Form -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>Edit User</h3>
                    <a href="users.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if ($edit_user): ?>
                        <form method="POST" class="admin-form">
                            <input type="hidden" name="user_id" value="<?= $edit_user['id'] ?>">
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Full Name *</label>
                                    <input type="text" name="name" required value="<?= htmlspecialchars($edit_user['name']) ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label>Username *</label>
                                    <input type="text" name="username" required value="<?= htmlspecialchars($edit_user['username'] ?? $edit_user['name']) ?>">
                                </div>
                            </div>
                            
                            <div class="admin-form-row">
                                <div class="admin-form-group">
                                    <label>Email *</label>
                                    <input type="email" name="email" required value="<?= htmlspecialchars($edit_user['email']) ?>">
                                </div>
                                <div class="admin-form-group">
                                    <label>Role *</label>
                                    <select name="role" required>
                                        <option value="customer" <?= $edit_user['role'] === 'customer' ? 'selected' : '' ?>>Customer</option>
                                        <option value="admin" <?= $edit_user['role'] === 'admin' ? 'selected' : '' ?>>Administrator</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="admin-form-group">
                                <label class="admin-checkbox">
                                    <input type="checkbox" name="is_active" <?= $edit_user['is_active'] ? 'checked' : '' ?>>
                                    <span>Active User</span>
                                </label>
                            </div>
                            
                            <div class="admin-form-info">
                                <p><strong>Account Created:</strong> <?= date('F j, Y \a\t g:i A', strtotime($edit_user['created_at'])) ?></p>
                                <p><strong>Last Updated:</strong> <?= date('F j, Y \a\t g:i A', strtotime($edit_user['updated_at'])) ?></p>
                            </div>
                            
                            <div class="admin-form-actions">
                                <button type="submit" name="update_user" class="admin-btn admin-btn-primary">
                                    <i class="fas fa-save"></i>
                                    Update User
                                </button>
                                <a href="users.php" class="admin-btn admin-btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-user-slash"></i>
                            <h3>User Not Found</h3>
                            <p>The requested user could not be found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php else: ?>
            <!-- Users Statistics -->
            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['total'] ?></div>
                        <div class="admin-stat-label">Total Users</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['active'] ?></div>
                        <div class="admin-stat-label">Active Users</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['admins'] ?></div>
                        <div class="admin-stat-label">Administrators</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['customers'] ?></div>
                        <div class="admin-stat-label">Customers</div>
                    </div>
                </div>
            </div>

            <!-- Users List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Users (<?= count($users) ?>)</h3>
                    
                    <!-- Search and Filter -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-search-form">
                            <input type="text" name="search" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>">
                            <select name="role">
                                <option value="">All Roles</option>
                                <option value="customer" <?= $role_filter === 'customer' ? 'selected' : '' ?>>Customers</option>
                                <option value="admin" <?= $role_filter === 'admin' ? 'selected' : '' ?>>Administrators</option>
                            </select>
                            <button type="submit" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card-body">
                    <?php if (!empty($users)): ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $u): ?>
                                        <tr>
                                            <td>
                                                <div class="admin-user-info">
                                                    <div class="admin-user-avatar-sm">
                                                        <?php if (isset($u['profile_photo']) && $u['profile_photo'] && file_exists('../' . $u['profile_photo'])): ?>
                                                            <img src="../<?= htmlspecialchars($u['profile_photo']) ?>" alt="<?= htmlspecialchars($u['name']) ?>">
                                                        <?php else: ?>
                                                            <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div>
                                                        <div class="admin-user-name"><?= htmlspecialchars($u['name']) ?></div>
                                                        <div class="admin-user-username"><?= htmlspecialchars($u['username'] ?? $u['name']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($u['email']) ?></td>
                                            <td>
                                                <span class="admin-badge admin-badge-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                                    <?= ucfirst($u['role']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                    <input type="hidden" name="current_status" value="<?= $u['is_active'] ?>">
                                                    <button type="submit" name="toggle_status" class="admin-badge admin-badge-<?= $u['is_active'] ? 'success' : 'danger' ?> admin-badge-clickable">
                                                        <?= $u['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </button>
                                                </form>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <a href="users.php?action=edit&id=<?= $u['id'] ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                                            <button type="submit" name="delete_user" class="admin-btn admin-btn-sm admin-btn-danger">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-users"></i>
                            <h3>No Users Found</h3>
                            <p>No users match your search criteria.</p>
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

        // Confirm role changes
        document.querySelectorAll('select[name="role"]').forEach(select => {
            select.addEventListener('change', function() {
                if (this.value === 'admin') {
                    if (!confirm('Are you sure you want to make this user an administrator? This will give them full access to the admin panel.')) {
                        this.value = 'customer';
                    }
                }
            });
        });
    </script>
</body>
</html>