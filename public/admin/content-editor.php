<?php

require_once '../src/role-redirect.php';
require_once 'middleware-standalone.php';

// Ensure only admin can access
requireRole('admin');

// Database connection
$pdo = getDbConnection();
$user = getAdminUser();

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_hero_content'])) {
            $hero_title = trim($_POST['hero_title']);
            $hero_subtitle = trim($_POST['hero_subtitle']);
            $hero_description = trim($_POST['hero_description']);
            $hero_button_text = trim($_POST['hero_button_text']);
            $hero_button_link = trim($_POST['hero_button_link']);
            
            // Save to settings table or create JSON file
            $hero_data = [
                'title' => $hero_title,
                'subtitle' => $hero_subtitle,
                'description' => $hero_description,
                'button_text' => $hero_button_text,
                'button_link' => $hero_button_link,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user['name']
            ];
            
            file_put_contents('../../content/hero.json', json_encode($hero_data, JSON_PRETTY_PRINT));
            $message = 'Hero section updated successfully!';
            
        } elseif (isset($_POST['save_about_content'])) {
            $about_title = trim($_POST['about_title']);
            $about_description = trim($_POST['about_description']);
            $about_features = array_filter(array_map('trim', explode("\n", $_POST['about_features'])));
            
            $about_data = [
                'title' => $about_title,
                'description' => $about_description,
                'features' => $about_features,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user['name']
            ];
            
            file_put_contents('../../content/about.json', json_encode($about_data, JSON_PRETTY_PRINT));
            $message = 'About section updated successfully!';
            
        } elseif (isset($_POST['save_contact_content'])) {
            $contact_title = trim($_POST['contact_title']);
            $contact_description = trim($_POST['contact_description']);
            $contact_address = trim($_POST['contact_address']);
            $contact_phone = trim($_POST['contact_phone']);
            $contact_email = trim($_POST['contact_email']);
            $contact_hours = trim($_POST['contact_hours']);
            
            $contact_data = [
                'title' => $contact_title,
                'description' => $contact_description,
                'address' => $contact_address,
                'phone' => $contact_phone,
                'email' => $contact_email,
                'hours' => $contact_hours,
                'updated_at' => date('Y-m-d H:i:s'),
                'updated_by' => $user['name']
            ];
            
            file_put_contents('../../content/contact.json', json_encode($contact_data, JSON_PRETTY_PRINT));
            $message = 'Contact information updated successfully!';
            
        } elseif (isset($_POST['upload_hero_image'])) {
            if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../assets/images/hero/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_info = pathinfo($_FILES['hero_image']['name']);
                $file_name = 'hero-' . time() . '.' . $file_info['extension'];
                $upload_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['hero_image']['tmp_name'], $upload_path)) {
                    // Update hero.json with new image
                    $hero_data = json_decode(file_get_contents('../../content/hero.json'), true) ?: [];
                    $hero_data['background_image'] = 'assets/images/hero/' . $file_name;
                    $hero_data['updated_at'] = date('Y-m-d H:i:s');
                    $hero_data['updated_by'] = $user['name'];
                    
                    file_put_contents('../../content/hero.json', json_encode($hero_data, JSON_PRETTY_PRINT));
                    $message = 'Hero background image updated successfully!';
                } else {
                    throw new Exception('Failed to upload image');
                }
            }
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Create content directory if not exists
if (!is_dir('../../content')) {
    mkdir('../../content', 0755, true);
}

// Load existing content
$hero_content = [];
$about_content = [];
$contact_content = [];

if (file_exists('../../content/hero.json')) {
    $hero_content = json_decode(file_get_contents('../../content/hero.json'), true);
}

if (file_exists('../../content/about.json')) {
    $about_content = json_decode(file_get_contents('../../content/about.json'), true);
}

if (file_exists('../../content/contact.json')) {
    $contact_content = json_decode(file_get_contents('../../content/contact.json'), true);
}

// Default content if files don't exist
if (empty($hero_content)) {
    $hero_content = [
        'title' => 'iPhone 15 Pro',
        'subtitle' => 'Titanium. So strong. So light. So Pro.',
        'description' => 'iPhone 15 Pro is the first iPhone to feature an aerospace-grade titanium design, using the same alloy that spacecraft use for missions to Mars.',
        'button_text' => 'Learn more',
        'button_link' => '#products',
        'background_image' => 'assets/images/iphone-15-pro-hero.jpg'
    ];
}

if (empty($about_content)) {
    $about_content = [
        'title' => 'Innovation at its finest',
        'description' => 'At Apple, we believe that technology should enhance your life, not complicate it. Every product we create is designed with you in mind.',
        'features' => [
            'Revolutionary design',
            'Cutting-edge technology',
            'Seamless integration',
            'Unparalleled performance'
        ]
    ];
}

if (empty($contact_content)) {
    $contact_content = [
        'title' => 'Get in touch',
        'description' => 'Have questions about our products? Our team is here to help.',
        'address' => 'Apple Park, Cupertino, CA 95014',
        'phone' => '+1 (800) APL-CARE',
        'email' => 'support@apple.com',
        'hours' => 'Mon-Fri: 8AM-8PM PST'
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Content Editor - Apple Admin</title>
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
            <a href="feedback.php" class="admin-nav-link">
                <i class="fas fa-comments"></i>
                Feedback
            </a>
            <a href="content-editor.php" class="admin-nav-link active">
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
            <h1>Content Editor</h1>
            <div class="admin-header-actions">
                <a href="../index.php" target="_blank" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-external-link-alt"></i>
                    Preview Website
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

        <!-- Hero Section Editor -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Hero Section</h3>
                <span class="admin-badge admin-badge-info">Homepage Banner</span>
            </div>
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Title</label>
                            <input type="text" name="hero_title" value="<?= htmlspecialchars($hero_content['title'] ?? '') ?>" required>
                        </div>
                        <div class="admin-form-group">
                            <label>Subtitle</label>
                            <input type="text" name="hero_subtitle" value="<?= htmlspecialchars($hero_content['subtitle'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Description</label>
                        <textarea name="hero_description" rows="4"><?= htmlspecialchars($hero_content['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Button Text</label>
                            <input type="text" name="hero_button_text" value="<?= htmlspecialchars($hero_content['button_text'] ?? '') ?>">
                        </div>
                        <div class="admin-form-group">
                            <label>Button Link</label>
                            <input type="text" name="hero_button_link" value="<?= htmlspecialchars($hero_content['button_link'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" name="save_hero_content" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i>
                            Save Hero Content
                        </button>
                    </div>
                </form>
                
                <!-- Hero Image Upload -->
                <div style="border-top: 1px solid var(--admin-border); margin-top: 24px; padding-top: 24px;">
                    <h4>Hero Background Image</h4>
                    <?php if (isset($hero_content['background_image'])): ?>
                        <div class="admin-image-preview" style="margin-bottom: 16px;">
                            <div class="admin-image-item">
                                <img src="../<?= htmlspecialchars($hero_content['background_image']) ?>" alt="Current hero image">
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="admin-form">
                        <div class="admin-form-group">
                            <label>Upload New Background Image</label>
                            <input type="file" name="hero_image" accept="image/*" required>
                        </div>
                        <button type="submit" name="upload_hero_image" class="admin-btn admin-btn-secondary">
                            <i class="fas fa-upload"></i>
                            Upload Image
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- About Section Editor -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>About Section</h3>
                <span class="admin-badge admin-badge-info">Company Information</span>
            </div>
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-group">
                        <label>Title</label>
                        <input type="text" name="about_title" value="<?= htmlspecialchars($about_content['title'] ?? '') ?>" required>
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Description</label>
                        <textarea name="about_description" rows="4"><?= htmlspecialchars($about_content['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Features (one per line)</label>
                        <textarea name="about_features" rows="6" placeholder="Revolutionary design&#10;Cutting-edge technology&#10;Seamless integration"><?= htmlspecialchars(implode("\n", $about_content['features'] ?? [])) ?></textarea>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" name="save_about_content" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i>
                            Save About Content
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Contact Section Editor -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Contact Information</h3>
                <span class="admin-badge admin-badge-info">Contact Details</span>
            </div>
            <div class="admin-card-body">
                <form method="POST" class="admin-form">
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Title</label>
                            <input type="text" name="contact_title" value="<?= htmlspecialchars($contact_content['title'] ?? '') ?>" required>
                        </div>
                        <div class="admin-form-group">
                            <label>Email</label>
                            <input type="email" name="contact_email" value="<?= htmlspecialchars($contact_content['email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="admin-form-group">
                        <label>Description</label>
                        <textarea name="contact_description" rows="3"><?= htmlspecialchars($contact_content['description'] ?? '') ?></textarea>
                    </div>
                    
                    <div class="admin-form-row">
                        <div class="admin-form-group">
                            <label>Address</label>
                            <textarea name="contact_address" rows="3"><?= htmlspecialchars($contact_content['address'] ?? '') ?></textarea>
                        </div>
                        <div class="admin-form-group">
                            <label>Phone</label>
                            <input type="text" name="contact_phone" value="<?= htmlspecialchars($contact_content['phone'] ?? '') ?>">
                            <label>Business Hours</label>
                            <input type="text" name="contact_hours" value="<?= htmlspecialchars($contact_content['hours'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="admin-form-actions">
                        <button type="submit" name="save_contact_content" class="admin-btn admin-btn-primary">
                            <i class="fas fa-save"></i>
                            Save Contact Info
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Preview Section -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3>Live Preview</h3>
                <a href="../index.php" target="_blank" class="admin-btn admin-btn-secondary">
                    <i class="fas fa-external-link-alt"></i>
                    Open in New Tab
                </a>
            </div>
            <div class="admin-card-body">
                <iframe src="../index.php" style="width: 100%; height: 600px; border: 1px solid var(--admin-border); border-radius: 8px;"></iframe>
            </div>
        </div>
    </div>

    <script src="assets/js/content-editor.js"></script>
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