<?php
session_start();
require_once '../src/db.php';
require_once '../src/auth.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$auth = new Auth();
$db = new Database();

// Get current user data
try {
    $user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
    if (!$user) {
        header('Location: login.php');
        exit();
    }
} catch (Exception $e) {
    $error = 'Database error: ' . $e->getMessage();
    $user = null;
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    
    try {
        // Validate username
        if (empty($username)) {
            throw new Exception('Username tidak boleh kosong');
        }
        
        if (strlen($username) < 3) {
            throw new Exception('Username minimal 3 karakter');
        }
        
        // Check if username already exists (except current user)
        try {
            $existing_user = $db->query("SELECT id FROM users WHERE username = ? AND id != ?", [$username, $_SESSION['user_id']])->fetch();
            if ($existing_user) {
                throw new Exception('Username sudah digunakan');
            }
        } catch (Exception $e) {
            // If username column doesn't exist, we'll handle this in the update query
        }
        
        // Handle profile photo upload
        $profile_photo = $user['profile_photo']; // Keep current photo by default
        
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'assets/uploads/profiles/';
            
            // Create upload directory if it doesn't exist
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_info = pathinfo($_FILES['profile_photo']['name']);
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array(strtolower($file_info['extension']), $allowed_extensions)) {
                throw new Exception('Format file tidak didukung. Gunakan JPG, PNG, atau GIF');
            }
            
            // Check file size (max 2MB)
            if ($_FILES['profile_photo']['size'] > 2 * 1024 * 1024) {
                throw new Exception('Ukuran file terlalu besar. Maksimal 2MB');
            }
            
            // Generate unique filename
            $new_filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_info['extension'];
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Delete old profile photo if exists
                if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
                $profile_photo = $upload_path;
            } else {
                throw new Exception('Gagal mengupload foto profile');
            }
        }
        
        // Update user data (username and photo only)
        try {
            // Try to update with username field
            $db->query("UPDATE users SET username = ?, profile_photo = ?, updated_at = NOW() WHERE id = ?", 
                      [$username, $profile_photo, $_SESSION['user_id']]);
        } catch (Exception $e) {
            // If username column doesn't exist, update name instead
            try {
                $db->query("UPDATE users SET name = ?, profile_photo = ?, updated_at = NOW() WHERE id = ?", 
                          [$username, $profile_photo, $_SESSION['user_id']]);
            } catch (Exception $e2) {
                // If profile_photo column also doesn't exist, update only name
                $db->query("UPDATE users SET name = ?, updated_at = NOW() WHERE id = ?", 
                          [$username, $_SESSION['user_id']]);
            }
        }
        
        // Update session data using Auth class
        $auth->updateSession($_SESSION['user_id']);
        
        $message = 'Profile berhasil diperbarui!';
        
        // Refresh user data
        try {
            $user = $db->query("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']])->fetch();
        } catch (Exception $e) {
            // If query fails, create user array with updated data
            $user = [
                'id' => $_SESSION['user_id'],
                'name' => $username,
                'username' => $username,
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'],
                'profile_photo' => $profile_photo
            ];
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Apple Store</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: white;
            min-height: 100vh;
            margin: 0;
            padding: 20px 0;
        }
        
        .profile-back-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50px;
            padding: 12px 20px;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            z-index: 1000;
        }
        
        .profile-back-btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.15);
            text-decoration: none;
            color: #333;
        }
    </style>
</head>
<body>
    <!-- Back Button -->
    <a href="index.php" class="profile-back-btn">
        <i class="fas fa-arrow-left"></i>
        Kembali
    </a>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <div class="profile-header">
                <h1>Edit Profile</h1>
                <p>Kelola informasi profile dan keamanan akun Anda</p>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="profile-content">
                <form method="POST" enctype="multipart/form-data" class="profile-form">
                    <!-- Profile Photo Section -->
                    <div class="form-section">
                        <h3>Foto Profile</h3>
                        <div class="photo-upload-section">
                            <div class="current-photo">
                                <?php if (isset($user['profile_photo']) && $user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                                    <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Current Profile" id="currentPhoto">
                                <?php else: ?>
                                    <div class="no-photo" id="currentPhoto">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="photo-upload-controls">
                                <label for="profile_photo" class="btn btn-secondary">
                                    <i class="fas fa-camera"></i>
                                    Pilih Foto
                                </label>
                                <input type="file" id="profile_photo" name="profile_photo" accept="image/*" style="display: none;">
                                <p class="upload-hint">JPG, PNG, atau GIF. Maksimal 2MB.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3>Informasi Dasar</h3>
                        
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" value="<?= htmlspecialchars(isset($user['username']) ? $user['username'] : (isset($user['name']) ? $user['name'] : '')) ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <p class="form-hint">Email tidak dapat diubah</p>
                        </div>
                    </div>

                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">Batal</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Apple Store</h4>
                    <p>E-commerce modern dengan desain elegan dan pengalaman berbelanja yang luar biasa.</p>
                </div>
                <div class="footer-section">
                    <h4>Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Account</h4>
                    <ul>
                        <li><a href="profile.php">My Profile</a></li>
                        <li><a href="orders.php">My Orders</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="faq.php">FAQ</a></li>
                        <li><a href="support.php">Help Center</a></li>
                        <li><a href="terms.php">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Apple Store Clone. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Profile photo preview
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const currentPhoto = document.getElementById('currentPhoto');
                    if (currentPhoto.tagName === 'IMG') {
                        currentPhoto.src = e.target.result;
                    } else {
                        currentPhoto.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">`;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
        
        // Auto redirect after successful update
        <?php if ($message): ?>
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 2000); // Redirect after 2 seconds
        <?php endif; ?>

    </script>
</body>
</html>