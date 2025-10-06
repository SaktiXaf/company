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
$productId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
            $category = $_POST['category'];
            $base_price = floatval($_POST['base_price']);
            $description = trim($_POST['description']);
            $short_desc = trim($_POST['short_desc']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            
            // Handle main image upload
            $main_image = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                // Define upload directory with absolute path
                $upload_dir = dirname(__DIR__) . '/assets/uploads/products/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    if (!mkdir($upload_dir, 0755, true)) {
                        throw new Exception('Failed to create upload directory: ' . $upload_dir);
                    }
                }
                
                // Verify directory is writable
                if (!is_writable($upload_dir)) {
                    throw new Exception('Upload directory is not writable: ' . $upload_dir);
                }
                
                $file_info = pathinfo($_FILES['main_image']['name']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                    throw new Exception('Invalid image format. Use JPG, PNG, GIF, or WebP');
                }
                
                if ($_FILES['main_image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('Image too large. Maximum 5MB');
                }
                
                $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_info['extension'];
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                    $main_image = 'assets/uploads/products/' . $new_filename;
                } else {
                    throw new Exception('Failed to upload image');
                }
            }
            
            // Handle gallery images
            $gallery = [];
            if (isset($_FILES['gallery']) && !empty($_FILES['gallery']['name'][0])) {
                // Use same upload directory as main image
                $gallery_upload_dir = dirname(__DIR__) . '/assets/uploads/products/';
                
                for ($i = 0; $i < count($_FILES['gallery']['name']); $i++) {
                    if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_info = pathinfo($_FILES['gallery']['name'][$i]);
                        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                        
                        if (in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                            $new_filename = 'gallery_' . time() . '_' . $i . '_' . uniqid() . '.' . $file_info['extension'];
                            $upload_path = $gallery_upload_dir . $new_filename;
                            
                            if (move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $upload_path)) {
                                $gallery[] = 'assets/uploads/products/' . $new_filename;
                            }
                        }
                    }
                }
            }
            
            // Insert product
            $product_data = [
                'name' => $name,
                'slug' => $slug,
                'category' => $category,
                'base_price' => $base_price,
                'description' => $description,
                'short_desc' => $short_desc,
                'main_image' => $main_image,
                'gallery' => json_encode($gallery),
                'is_featured' => $is_featured,
                'is_active' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Insert product using PDO
            $sql = "INSERT INTO products (name, slug, category, base_price, description, short_desc, main_image, gallery, is_featured, is_active, created_at, updated_at) 
                    VALUES (:name, :slug, :category, :base_price, :description, :short_desc, :main_image, :gallery, :is_featured, :is_active, :created_at, :updated_at)";
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($product_data);
            
            if ($result) {
                $message = 'Product added successfully!';
                header('Location: products.php?message=' . urlencode($message));
                exit();
            } else {
                throw new Exception('Failed to add product');
            }
            
        } elseif (isset($_POST['update_product'])) {
            $id = intval($_POST['product_id']);
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', preg_replace('/[^A-Za-z0-9 ]/', '', $name)));
            $category = $_POST['category'];
            $base_price = floatval($_POST['base_price']);
            $description = trim($_POST['description']);
            $short_desc = trim($_POST['short_desc']);
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Get current product data
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $current_product = $stmt->fetch(PDO::FETCH_ASSOC);
            $main_image = $current_product['main_image'];
            $gallery = json_decode($current_product['gallery'], true) ?: [];
            
            // Handle main image upload for update
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                // Use absolute path for upload directory
                $upload_dir = dirname(__DIR__) . '/assets/uploads/products/';
                
                // Ensure directory exists
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_info = pathinfo($_FILES['main_image']['name']);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                    $new_filename = 'product_' . time() . '_' . uniqid() . '.' . $file_info['extension'];
                    $upload_path = $upload_dir . $new_filename;
                    
                    if (move_uploaded_file($_FILES['main_image']['tmp_name'], $upload_path)) {
                        // Delete old image
                        if ($main_image && file_exists(dirname(__DIR__) . '/' . $main_image)) {
                            unlink(dirname(__DIR__) . '/' . $main_image);
                        }
                        $main_image = 'assets/uploads/products/' . $new_filename;
                    } else {
                        throw new Exception('Failed to upload updated image');
                    }
                }
            }
            
            // Update product
            $update_data = [
                'name' => $name,
                'slug' => $slug,
                'category' => $category,
                'base_price' => $base_price,
                'description' => $description,
                'short_desc' => $short_desc,
                'main_image' => $main_image,
                'gallery' => json_encode($gallery),
                'is_featured' => $is_featured,
                'is_active' => $is_active,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            // Update product using PDO
            $sql = "UPDATE products SET name = :name, slug = :slug, category = :category, base_price = :base_price, 
                    description = :description, short_desc = :short_desc, main_image = :main_image, 
                    gallery = :gallery, is_featured = :is_featured, is_active = :is_active, updated_at = :updated_at 
                    WHERE id = :id";
            
            $update_data['id'] = $id;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($update_data);
            $message = 'Product updated successfully!';
            
        } elseif (isset($_POST['delete_product'])) {
            $id = intval($_POST['product_id']);
            
            // Get product data for file cleanup
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                // Delete product images
                if ($product['main_image'] && file_exists('../' . $product['main_image'])) {
                    unlink('../' . $product['main_image']);
                }
                
                $gallery = json_decode($product['gallery'], true) ?: [];
                foreach ($gallery as $image) {
                    if (file_exists('../' . $image)) {
                        unlink('../' . $image);
                    }
                }
                
                // Delete product
                $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Product deleted successfully!';
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get products for listing
$products = [];
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($search) {
        $where_conditions[] = "(name LIKE ? OR description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if ($category_filter) {
        $where_conditions[] = "category = ?";
        $params[] = $category_filter;
    }
    
    $where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $pdo->prepare("SELECT * FROM products $where_clause ORDER BY created_at DESC");
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Failed to load products: " . $e->getMessage();
}

// Get single product for editing
$edit_product = null;
if ($action === 'edit' && $productId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $edit_product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $error = "Failed to load product: " . $e->getMessage();
    }
}

// Get categories
$categories = ['iPhone', 'iPad', 'Mac', 'Watch', 'Accessories'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Apple Admin</title>
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
            <a href="products.php" class="admin-nav-link active">
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
            <h1>Products Management</h1>
            <div class="admin-header-actions">
                <a href="products.php?action=add" class="admin-btn admin-btn-primary">
                    <i class="fas fa-plus"></i>
                    Add Product
                </a>
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

        <?php if (isset($_GET['message'])): ?>
            <div class="admin-alert admin-alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'add' || $action === 'edit'): ?>
            <!-- Add/Edit Product Form -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3><?= $action === 'edit' ? 'Edit Product' : 'Add New Product' ?></h3>
                    <a href="products.php" class="admin-btn admin-btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Back to List
                    </a>
                </div>
                <div class="admin-card-body">
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <?php if ($action === 'edit'): ?>
                            <input type="hidden" name="product_id" value="<?= $edit_product['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="admin-form-row">
                            <div class="admin-form-group">
                                <label>Product Name *</label>
                                <input type="text" name="name" required value="<?= htmlspecialchars($edit_product['name'] ?? '') ?>">
                            </div>
                            <div class="admin-form-group">
                                <label>Category *</label>
                                <select name="category" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat ?>" <?= ($edit_product['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                            <?= $cat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="admin-form-row">
                            <div class="admin-form-group">
                                <label>Base Price *</label>
                                <input type="number" name="base_price" step="0.01" required value="<?= $edit_product['base_price'] ?? '' ?>">
                            </div>
                            <div class="admin-form-group">
                                <label>Status</label>
                                <div class="admin-form-checkboxes">
                                    <label class="admin-checkbox">
                                        <input type="checkbox" name="is_featured" <?= ($edit_product['is_featured'] ?? 0) ? 'checked' : '' ?>>
                                        <span>Featured Product</span>
                                    </label>
                                    <?php if ($action === 'edit'): ?>
                                    <label class="admin-checkbox">
                                        <input type="checkbox" name="is_active" <?= ($edit_product['is_active'] ?? 1) ? 'checked' : '' ?>>
                                        <span>Active</span>
                                    </label>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Short Description</label>
                            <textarea name="short_desc" rows="2"><?= htmlspecialchars($edit_product['short_desc'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="admin-form-group">
                            <label>Full Description</label>
                            <textarea name="description" rows="5"><?= htmlspecialchars($edit_product['description'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="admin-form-row">
                            <div class="admin-form-group">
                                <label>Main Product Image</label>
                                <input type="file" name="main_image" accept="image/*" class="admin-file-input">
                                <?php if ($edit_product && $edit_product['main_image']): ?>
                                    <div class="admin-current-image">
                                        <img src="../<?= htmlspecialchars($edit_product['main_image']) ?>" alt="Current Image">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="admin-form-group">
                                <label>Gallery Images</label>
                                <input type="file" name="gallery[]" accept="image/*" multiple class="admin-file-input">
                                <small>Hold Ctrl/Cmd to select multiple images</small>
                            </div>
                        </div>
                        
                        <div class="admin-form-actions">
                            <button type="submit" name="<?= $action === 'edit' ? 'update_product' : 'add_product' ?>" class="admin-btn admin-btn-primary">
                                <i class="fas fa-save"></i>
                                <?= $action === 'edit' ? 'Update Product' : 'Add Product' ?>
                            </button>
                            <a href="products.php" class="admin-btn admin-btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        
        <?php else: ?>
            <!-- Products List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h3>All Products (<?= count($products) ?>)</h3>
                    
                    <!-- Search and Filter -->
                    <div class="admin-filters">
                        <form method="GET" class="admin-search-form">
                            <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                            <select name="category">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat ?>" <?= $category_filter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="admin-btn admin-btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="admin-card-body">
                    <?php if (!empty($products)): ?>
                        <div class="admin-table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th>Image</th>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products as $product): ?>
                                        <tr>
                                            <td>
                                                <?php if ($product['main_image']): ?>
                                                    <img src="../<?= htmlspecialchars($product['main_image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="admin-product-thumb">
                                                <?php else: ?>
                                                    <div class="admin-product-thumb admin-product-no-image">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="admin-product-info">
                                                    <div class="admin-product-name"><?= htmlspecialchars($product['name']) ?></div>
                                                    <div class="admin-product-desc"><?= htmlspecialchars(substr($product['short_desc'] ?? '', 0, 50)) ?><?= strlen($product['short_desc'] ?? '') > 50 ? '...' : '' ?></div>
                                                </div>
                                            </td>
                                            <td><span class="admin-badge admin-badge-<?= strtolower($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></span></td>
                                            <td>$<?= number_format($product['base_price'], 2) ?></td>
                                            <td>
                                                <div class="admin-status-badges">
                                                    <?php if ($product['is_active']): ?>
                                                        <span class="admin-badge admin-badge-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="admin-badge admin-badge-secondary">Inactive</span>
                                                    <?php endif; ?>
                                                    <?php if ($product['is_featured']): ?>
                                                        <span class="admin-badge admin-badge-warning">Featured</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($product['created_at'])) ?></td>
                                            <td>
                                                <div class="admin-actions">
                                                    <a href="products.php?action=edit&id=<?= $product['id'] ?>" class="admin-btn admin-btn-sm admin-btn-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                        <button type="submit" name="delete_product" class="admin-btn admin-btn-sm admin-btn-danger">
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
                            <i class="fas fa-box"></i>
                            <h3>No Products Found</h3>
                            <p>Start by adding your first product.</p>
                            <a href="products.php?action=add" class="admin-btn admin-btn-primary">
                                <i class="fas fa-plus"></i>
                                Add Product
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Image preview for file uploads
        document.querySelectorAll('input[type="file"]').forEach(input => {
            input.addEventListener('change', function(e) {
                const files = e.target.files;
                if (files.length > 0) {
                    // Show file names
                    const fileNames = Array.from(files).map(file => file.name).join(', ');
                    console.log('Selected files:', fileNames);
                }
            });
        });

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