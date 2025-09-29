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
$feedbackId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['update_status'])) {
            $id = intval($_POST['feedback_id']);
            $status = $_POST['status'];
            $admin_notes = trim($_POST['admin_notes'] ?? '');
            
            // Update feedback using PDO
            $sql = "UPDATE feedback SET status = :status, admin_notes = :admin_notes, updated_at = :updated_at WHERE id = :id";
            $update_data = [
                'status' => $status,
                'admin_notes' => $admin_notes,
                'updated_at' => date('Y-m-d H:i:s'),
                'id' => $id
            ];
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            $message = 'Feedback status updated successfully!';
            
        } elseif (isset($_POST['delete_feedback'])) {
            $id = intval($_POST['feedback_id']);
            $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Feedback deleted successfully!';
            
        } elseif (isset($_POST['reply_feedback'])) {
            $id = intval($_POST['feedback_id']);
            $reply = trim($_POST['reply']);
            
            if ($reply) {
                // Reply to feedback using PDO
                $sql = "UPDATE feedback SET admin_reply = :admin_reply, status = :status, updated_at = :updated_at WHERE id = :id";
                $update_data = [
                    'admin_reply' => $reply,
                    'status' => 'replied',
                    'updated_at' => date('Y-m-d H:i:s'),
                    'id' => $id
                ];
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($update_data);
                $message = 'Reply sent successfully!';
            } else {
                throw new Exception('Reply cannot be empty');
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get feedback for listing
$feedback_list = [];
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$type_filter = $_GET['type'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(f.subject LIKE ? OR f.message LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($status_filter) {
        $where_conditions[] = "f.status = ?";
        $params[] = $status_filter;
    }
    
    if ($type_filter) {
        $where_conditions[] = "f.type = ?";
        $params[] = $type_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("
        SELECT f.*, u.name as user_name, u.email as user_email, u.profile_photo
        FROM feedback f 
        LEFT JOIN users u ON f.user_id = u.id 
        $where_clause 
        ORDER BY f.created_at DESC
    ");
    $stmt->execute($params);
    $feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Failed to load feedback: " . $e->getMessage();
}

// Get single feedback for viewing/editing
$feedback = null;
if (($action === 'view' || $action === 'reply') && $feedbackId) {
    try {
        $stmt = $pdo->prepare("
            SELECT f.*, u.name as user_name, u.email as user_email, u.profile_photo
            FROM feedback f 
            LEFT JOIN users u ON f.user_id = u.id 
            WHERE f.id = ?
        ");
        $stmt->execute([$feedbackId]);
        $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to load feedback: " . $e->getMessage();
    }
}

// Get feedback statistics
$stats = [
    'total' => count($feedback_list),
    'pending' => count(array_filter($feedback_list, fn($f) => $f['status'] === 'pending')),
    'in_progress' => count(array_filter($feedback_list, fn($f) => $f['status'] === 'in_progress')),
    'resolved' => count(array_filter($feedback_list, fn($f) => $f['status'] === 'resolved')),
    'complaints' => count(array_filter($feedback_list, fn($f) => $f['type'] === 'complaint')),
    'suggestions' => count(array_filter($feedback_list, fn($f) => $f['type'] === 'suggestion'))
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management - Apple Admin</title>
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
            <a href="orders.php" class="admin-nav-link">
                <i class="fas fa-shopping-cart"></i>
                Orders
            </a>
            <a href="feedback.php" class="admin-nav-link active">
                <i class="fas fa-comments"></i>
                Feedback
            </a>
            <a href="content-editor.php" class="admin-nav-link">
                <i class="fas fa-edit"></i>
                Content Editor
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
            <h1>Feedback Management</h1>
            <div class="admin-header-actions">
                <div class="admin-stats-mini">
                    <span class="admin-stat-mini">
                        <i class="fas fa-comments"></i>
                        <?= $stats['total'] ?> Total
                    </span>
                    <span class="admin-stat-mini">
                        <i class="fas fa-clock"></i>
                        <?= $stats['pending'] ?> Pending
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

        <?php if ($action === 'view' || $action === 'reply'): ?>
            <!-- View/Reply Feedback -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><?= $action === 'reply' ? 'Reply to' : 'View' ?> Feedback</h3>
                    <a href="feedback.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
                <div class="admin-card-body">
                    <?php if ($feedback): ?>
                        <div class="feedback-detail">
                            <!-- User Information -->
                            <div class="feedback-user">
                                <div class="admin-user-info">
                                    <div class="admin-user-avatar-lg">
                                        <?php if (isset($feedback['profile_photo']) && $feedback['profile_photo'] && file_exists('../' . $feedback['profile_photo'])): ?>
                                            <img src="../<?= htmlspecialchars($feedback['profile_photo']) ?>" alt="<?= htmlspecialchars($feedback['user_name']) ?>">
                                        <?php else: ?>
                                            <?= strtoupper(substr($feedback['user_name'] ?? 'Anonymous', 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="admin-user-name"><?= htmlspecialchars($feedback['user_name'] ?? 'Anonymous User') ?></div>
                                        <div class="admin-user-email"><?= htmlspecialchars($feedback['user_email'] ?? 'No email') ?></div>
                                        <div class="admin-user-date"><?= date('F j, Y \a\t g:i A', strtotime($feedback['created_at'])) ?></div>
                                    </div>
                                </div>
                                <div class="feedback-badges">
                                    <span class="admin-badge admin-badge-<?= $feedback['type'] === 'complaint' ? 'danger' : 'info' ?>">
                                        <?= ucfirst($feedback['type']) ?>
                                    </span>
                                    <span class="admin-badge admin-badge-<?= 
                                        $feedback['status'] === 'pending' ? 'warning' : 
                                        ($feedback['status'] === 'in_progress' ? 'info' : 
                                        ($feedback['status'] === 'resolved' ? 'success' : 'primary')) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $feedback['status'])) ?>
                                    </span>
                                </div>
                            </div>

                            <!-- Feedback Content -->
                            <div class="feedback-content">
                                <h4><?= htmlspecialchars($feedback['subject']) ?></h4>
                                <div class="feedback-message">
                                    <?= nl2br(htmlspecialchars($feedback['message'])) ?>
                                </div>
                            </div>

                            <!-- Admin Notes -->
                            <?php if ($feedback['admin_notes']): ?>
                                <div class="feedback-admin-notes">
                                    <h5>Admin Notes:</h5>
                                    <p><?= nl2br(htmlspecialchars($feedback['admin_notes'])) ?></p>
                                </div>
                            <?php endif; ?>

                            <!-- Previous Reply -->
                            <?php if ($feedback['admin_reply']): ?>
                                <div class="feedback-admin-reply">
                                    <h5>Admin Reply:</h5>
                                    <div class="admin-reply-content">
                                        <?= nl2br(htmlspecialchars($feedback['admin_reply'])) ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if ($action === 'reply'): ?>
                                <!-- Reply Form -->
                                <div class="feedback-reply-form">
                                    <form method="POST" class="admin-form">
                                        <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                        
                                        <div class="admin-form-group">
                                            <label>Reply to Customer</label>
                                            <textarea name="reply" rows="6" placeholder="Type your reply here..." required><?= htmlspecialchars($feedback['admin_reply'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="admin-form-actions">
                                            <button type="submit" name="reply_feedback" class="admin-btn admin-btn-primary">
                                                <i class="fas fa-reply"></i>
                                                Send Reply
                                            </button>
                                            <a href="feedback.php" class="admin-btn admin-btn-secondary">Cancel</a>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Action Buttons -->
                                <div class="feedback-actions">
                                    <a href="feedback.php?action=reply&id=<?= $feedback['id'] ?>" class="admin-btn admin-btn-primary">
                                        <i class="fas fa-reply"></i>
                                        Reply
                                    </a>
                                    
                                    <!-- Status Update Form -->
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="pending" <?= $feedback['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                                            <option value="in_progress" <?= $feedback['status'] === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                            <option value="resolved" <?= $feedback['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                            <option value="replied" <?= $feedback['status'] === 'replied' ? 'selected' : '' ?>>Replied</option>
                                        </select>
                                        <textarea name="admin_notes" placeholder="Add admin notes..." style="margin-top: 10px;"><?= htmlspecialchars($feedback['admin_notes'] ?? '') ?></textarea>
                                        <button type="submit" name="update_status" class="admin-btn admin-btn-secondary">Update</button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-comment-slash"></i>
                            <h3>Feedback Not Found</h3>
                            <p>The requested feedback could not be found.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        
        <?php else: ?>
            <!-- Feedback Statistics -->
            <div class="admin-stats">
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['total'] ?></div>
                        <div class="admin-stat-label">Total Feedback</div>
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
                        <i class="fas fa-cog"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['in_progress'] ?></div>
                        <div class="admin-stat-label">In Progress</div>
                    </div>
                </div>
                
                <div class="admin-stat-card">
                    <div class="admin-stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="admin-stat-content">
                        <div class="admin-stat-number"><?= $stats['resolved'] ?></div>
                        <div class="admin-stat-label">Resolved</div>
                    </div>
                </div>
            </div>

            <!-- Feedback List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Feedback (<?= count($feedback_list) ?>)</h3>
                    
                    <!-- Search and Filter -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-search-form">
                            <input type="text" name="search" placeholder="Search feedback..." value="<?= htmlspecialchars($search) ?>">
                            <select name="status">
                                <option value="">All Status</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                                <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                <option value="replied" <?= $status_filter === 'replied' ? 'selected' : '' ?>>Replied</option>
                            </select>
                            <select name="type">
                                <option value="">All Types</option>
                                <option value="suggestion" <?= $type_filter === 'suggestion' ? 'selected' : '' ?>>Suggestions</option>
                                <option value="complaint" <?= $type_filter === 'complaint' ? 'selected' : '' ?>>Complaints</option>
                            </select>
                            <button type="submit" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card-body">
                    <?php if (!empty($feedback_list)): ?>
                        <div class="feedback-list">
                            <?php foreach ($feedback_list as $f): ?>
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <div class="feedback-user-info">
                                            <div class="admin-user-avatar-sm">
                                                <?php if (isset($f['profile_photo']) && $f['profile_photo'] && file_exists('../' . $f['profile_photo'])): ?>
                                                    <img src="../<?= htmlspecialchars($f['profile_photo']) ?>" alt="<?= htmlspecialchars($f['user_name']) ?>">
                                                <?php else: ?>
                                                    <?= strtoupper(substr($f['user_name'] ?? 'A', 0, 1)) ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <div class="feedback-user-name"><?= htmlspecialchars($f['user_name'] ?? 'Anonymous User') ?></div>
                                                <div class="feedback-date"><?= date('M j, Y \a\t g:i A', strtotime($f['created_at'])) ?></div>
                                            </div>
                                        </div>
                                        <div class="feedback-badges">
                                            <span class="admin-badge admin-badge-<?= $f['type'] === 'complaint' ? 'danger' : 'info' ?>">
                                                <?= ucfirst($f['type']) ?>
                                            </span>
                                            <span class="admin-badge admin-badge-<?= 
                                                $f['status'] === 'pending' ? 'warning' : 
                                                ($f['status'] === 'in_progress' ? 'info' : 
                                                ($f['status'] === 'resolved' ? 'success' : 'primary')) ?>">
                                                <?= ucfirst(str_replace('_', ' ', $f['status'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="feedback-content">
                                        <h4><?= htmlspecialchars($f['subject']) ?></h4>
                                        <p><?= htmlspecialchars(strlen($f['message']) > 150 ? substr($f['message'], 0, 150) . '...' : $f['message']) ?></p>
                                    </div>
                                    
                                    <div class="feedback-actions">
                                        <a href="feedback.php?action=view&id=<?= $f['id'] ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                            <i class="fas fa-eye"></i>
                                            View
                                        </a>
                                        <a href="feedback.php?action=reply&id=<?= $f['id'] ?>" class="admin-btn admin-btn-sm admin-btn-primary">
                                            <i class="fas fa-reply"></i>
                                            Reply
                                        </a>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this feedback?')">
                                            <input type="hidden" name="feedback_id" value="<?= $f['id'] ?>">
                                            <button type="submit" name="delete_feedback" class="admin-btn admin-btn-sm admin-btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="admin-empty">
                            <i class="fas fa-comments"></i>
                            <h3>No Feedback Found</h3>
                            <p>No feedback matches your search criteria.</p>
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

        // Auto-expand textareas
        document.querySelectorAll('textarea').forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });
        });
    </script>
</body>
</html>