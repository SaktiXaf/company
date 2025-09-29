<?php
require_once 'src/role-redirect.php';
require_once '../src/auth.php';
require_once '../src/product_model.php';
require_once 'src/content-helper.php';

// handel preview admin
$isPreviewMode = isset($_GET['preview']) && $_GET['preview'] === '1';

// Redirect admin
if (!$isPreviewMode) {
    redirectBasedOnRole(['customer'], 'customer');
}

// Load dynamic content
$heroContent = getCachedContent('hero');
$aboutContent = getCachedContent('about');
$contactContent = getCachedContent('contact');
$settings = getCachedContent('settings');

// Handle category filtering
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$pageTitle = $settings['company_name'] ?? 'Apple Store';
$heroTitle = $heroContent['title'] ?? 'Think Different.';
$heroSubtitle = $heroContent['subtitle'] ?? 'Experience the power of innovation with our latest Apple products.';

// Get products based on category filter
if ($selectedCategory) {
    $featuredProducts = $productModel->getProductsByCategory($selectedCategory, 12);
    $pageTitle = $selectedCategory . ' - Apple Store';
    
    // Special handling for Watch category
    if ($selectedCategory === 'Watch') {
        $heroTitle = 'Watch Collection';
        $heroSubtitle = 'Discover the Apple Watch lineup with advanced health monitoring and seamless connectivity.';
    } else {
        $heroTitle = $selectedCategory . ' Collection';
        $heroSubtitle = 'Discover the latest ' . $selectedCategory . ' products with cutting-edge technology.';
    }
} else {
    $featuredProducts = $productModel->getFeaturedProducts(8);
}

// Get categories
$categories = $productModel->getCategories();

// Get user info if logged in
$user = $auth->getCurrentUser();
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($heroSubtitle); ?>">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta property="og:title" content="Apple Store - Think Different">
    <meta property="og:description" content="Discover the latest Apple products with cutting-edge technology and design.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo $_SERVER['HTTP_HOST']; ?>">
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
</head>
<body>
    <?php echo addRoleBasedStyles(); ?>
    <?php echo addRoleBasedNavigation(); ?>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container container">
            <a href="index.php" class="navbar-brand">
                <i class="fab fa-apple"></i> Apple
            </a>
            
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="index.php" class="nav-link <?php echo !isset($_GET['category']) ? 'active' : ''; ?>">Home</a>
                </li>
                <li class="nav-item">
                    <a href="#products" class="nav-link">Products</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?category=iPhone" class="nav-link <?php echo isset($_GET['category']) && $_GET['category'] === 'iPhone' ? 'active' : ''; ?>">iPhone</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?category=Mac" class="nav-link <?php echo isset($_GET['category']) && $_GET['category'] === 'Mac' ? 'active' : ''; ?>">Mac</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?category=iPad" class="nav-link <?php echo isset($_GET['category']) && $_GET['category'] === 'iPad' ? 'active' : ''; ?>">iPad</a>
                </li>
                <li class="nav-item">
                    <a href="index.php?category=Watch" class="nav-link <?php echo isset($_GET['category']) && $_GET['category'] === 'Watch' ? 'active' : ''; ?>">Watch</a>
                </li>
                <li class="nav-item">
                    <a href="support.php" class="nav-link">Support</a>
                </li>
            </ul>
            
            <div class="navbar-actions">
                <?php if ($user): ?>
                    <!-- User Menu Button -->
                    <button class="user-menu-toggle" id="userMenuToggle" aria-label="User Menu">
                        <div class="user-avatar">
                            <?php if (isset($user['profile_photo']) && $user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <?php 
                                $displayName = isset($user['username']) ? $user['username'] : $user['name'];
                                echo strtoupper(substr($displayName, 0, 1));
                                ?>
                            <?php endif; ?>
                        </div>
                        <span class="user-name"><?php echo htmlspecialchars(isset($user['username']) ? $user['username'] : $user['name']); ?></span>
                        <i class="fas fa-chevron-down user-chevron"></i>
                    </button>
                <?php else: ?>
                    <a href="login.php" class="nav-link">
                        <i class="fas fa-user"></i> Login
                    </a>
                <?php endif; ?>
                
                <a href="cart.php" class="nav-link cart-badge">
                    <i class="fas fa-shopping-cart"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="badge"><?php echo $cartCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </nav>

    <!-- User Sidebar -->
    <?php if ($user): ?>
    <div class="user-sidebar" id="userSidebar">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>
        <div class="sidebar-content">
            <div class="sidebar-header">
                <div class="user-profile">
                    <div class="user-avatar-large">
                        <?php if (isset($user['profile_photo']) && $user['profile_photo'] && file_exists($user['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <?php 
                            $displayName = isset($user['username']) ? $user['username'] : $user['name'];
                            echo strtoupper(substr($displayName, 0, 1));
                            ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-info">
                        <h3><?php echo htmlspecialchars(isset($user['username']) ? $user['username'] : $user['name']); ?></h3>
                        <p class="user-email"><?php echo htmlspecialchars($user['email']); ?></p>
                        <div class="user-badge-container">
                            <span class="user-role badge"><?php echo ucfirst($user['role']); ?></span>
                        </div>
                    </div>
                </div>
                <button class="sidebar-close" id="sidebarClose" aria-label="Close Sidebar">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="sidebar-body">
                <nav class="sidebar-nav">
                    <h4 class="sidebar-section-title">Account</h4>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="profile.php" class="sidebar-link">
                                <i class="fas fa-user-edit"></i>
                                <span>Edit Profile</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="orders.php" class="sidebar-link">
                                <i class="fas fa-shopping-bag"></i>
                                <span>My Orders</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="wishlist.php" class="sidebar-link">
                                <i class="fas fa-heart"></i>
                                <span>Wishlist</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="addresses.php" class="sidebar-link">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Addresses</span>
                            </a>
                        </li>
                    </ul>

                    <h4 class="sidebar-section-title">Settings</h4>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="notifications.php" class="sidebar-link">
                                <i class="fas fa-bell"></i>
                                <span>Notifications</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="privacy.php" class="sidebar-link">
                                <i class="fas fa-shield-alt"></i>
                                <span>Privacy & Security</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="payment-methods.php" class="sidebar-link">
                                <i class="fas fa-credit-card"></i>
                                <span>Payment Methods</span>
                            </a>
                        </li>
                    </ul>

                    <?php if ($user['role'] === 'admin'): ?>
                    <h4 class="sidebar-section-title">Admin</h4>
                    <ul class="sidebar-menu">
                        <li class="sidebar-menu-item">
                            <a href="admin/dashboard.php" class="sidebar-link admin-link">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="admin/products.php" class="sidebar-link admin-link">
                                <i class="fas fa-box"></i>
                                <span>Manage Products</span>
                            </a>
                        </li>
                        <li class="sidebar-menu-item">
                            <a href="admin/orders.php" class="sidebar-link admin-link">
                                <i class="fas fa-clipboard-list"></i>
                                <span>Manage Orders</span>
                            </a>
                        </li>
                    </ul>
                    <?php endif; ?>
                </nav>
            </div>

            <div class="sidebar-footer">
                <a href="logout.php" class="sidebar-logout">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sign Out</span>
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main>
        <!-- Hero Section -->
        <section class="hero <?php echo $selectedCategory ? 'category-hero ' . strtolower($selectedCategory) : ''; ?>" 
                 <?php if (isset($heroContent['background_image']) && !$selectedCategory): ?>
                 style="background-image: url('<?php echo htmlspecialchars($heroContent['background_image']); ?>');"
                 <?php endif; ?>>
            <div class="hero-content">
                <h1 class="hero-title fade-in"><?php echo htmlspecialchars($heroTitle); ?></h1>
                <p class="hero-subtitle fade-in"><?php echo htmlspecialchars($heroSubtitle); ?></p>
                <?php if (isset($heroContent['description']) && !$selectedCategory): ?>
                <p class="hero-description fade-in"><?php echo htmlspecialchars($heroContent['description']); ?></p>
                <?php endif; ?>
                <div class="hero-actions fade-in">
                    <?php if (isset($heroContent['button_text']) && isset($heroContent['button_link']) && !$selectedCategory): ?>
                    <a href="<?php echo htmlspecialchars($heroContent['button_link']); ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i>
                        <?php echo htmlspecialchars($heroContent['button_text']); ?>
                    </a>
                    <?php else: ?>
                    <a href="#products" class="btn btn-primary">
                        <i class="fas fa-arrow-down"></i>
                        Explore Products
                    </a>
                    <?php endif; ?>
                    <a href="#featured" class="btn btn-secondary">
                        <i class="fas fa-star"></i>
                        Featured Items
                    </a>
                </div>
            </div>
            
            <!-- Scroll indicator -->
            <div class="scroll-indicator">
                <i class="fas fa-chevron-down"></i>
            </div>
        </section>

        <!-- Products Section -->
        <section id="products" class="section featured-products">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">
                        <?php if ($selectedCategory): ?>
                            <?php if ($selectedCategory === 'Watch'): ?>
                                Watch Collection
                            <?php else: ?>
                                <?php echo htmlspecialchars($selectedCategory); ?> Products
                            <?php endif; ?>
                        <?php else: ?>
                            Featured Products
                        <?php endif; ?>
                    </h2>
                    <p class="section-subtitle">
                        <?php if ($selectedCategory): ?>
                            <?php if ($selectedCategory === 'Watch'): ?>
                                Explore the complete Apple Watch lineup with innovative health features and elegant designs.
                            <?php else: ?>
                                Explore our complete collection of <?php echo htmlspecialchars($selectedCategory); ?> products.
                            <?php endif; ?>
                        <?php else: ?>
                            Discover our most popular products, handpicked for their exceptional design and performance.
                        <?php endif; ?>
                    </p>
                </div>
                
                <?php if (!empty($featuredProducts)): ?>
                    <div class="product-grid">
                        <?php foreach ($featuredProducts as $product): ?>
                            <article class="product-card">
                                <a href="product.php?slug=<?php echo htmlspecialchars($product['slug']); ?>">
                                    <div class="product-image-container">
                                        <img 
                                            src="assets/img/<?php echo htmlspecialchars($product['main_image'] ?: 'placeholder.jpg'); ?>" 
                                            alt="<?php echo htmlspecialchars($product['name']); ?>"
                                            class="product-image"
                                            loading="lazy"
                                        >
                                        <?php if ($product['is_featured']): ?>
                                            <span class="product-badge">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <div class="product-category">
                                            <?php echo htmlspecialchars($product['category']); ?>
                                        </div>
                                        <h3 class="product-name">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                        </h3>
                                        <p class="product-description">
                                            <?php echo htmlspecialchars($product['short_desc']); ?>
                                        </p>
                                        <div class="product-price">
                                            <?php if (isset($product['min_price']) && $product['min_price'] != $product['base_price']): ?>
                                                <span class="price-from">From </span>
                                            <?php endif; ?>
                                            $<?php echo number_format($product['min_price'] ?? $product['base_price'], 2); ?>
                                        </div>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-box-open" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <h3>No Featured Products</h3>
                        <p class="text-secondary">Check back soon for amazing products!</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- About Section -->
        <section class="section promo-section">
            <div class="container">
                <div class="promo-content">
                    <h2 class="section-title"><?php echo htmlspecialchars($aboutContent['title']); ?></h2>
                    <p><?php echo htmlspecialchars($aboutContent['description']); ?></p>
                    
                    <div class="promo-stats">
                        <?php foreach ($aboutContent['features'] as $index => $feature): ?>
                        <div class="stat">
                            <span class="stat-number"><?php echo $index + 1; ?></span>
                            <span class="stat-label"><?php echo htmlspecialchars($feature); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="#products" class="btn btn-secondary">
                        <i class="fas fa-shopping-bag"></i>
                        Shop Now
                    </a>
                </div>
            </div>
        </section>

        <!-- Product Categories -->
        <section id="products" class="section">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title">Shop by Category</h2>
                    <p class="section-subtitle">Find the perfect Apple product for your needs across our complete product lineup.</p>
                </div>
                
                <?php if (!empty($categories)): ?>
                    <div class="category-grid">
                        <?php 
                        $categoryImages = [
                            'iPhone' => 'iphone-category.jpg',
                            'Mac' => 'mac-category.jpg', 
                            'iPad' => 'ipad-category.jpg',
                            'Watch' => 'watch-category.jpg',
                            'AirPods' => 'airpods-category.jpg'
                        ];
                        
                        foreach ($categories as $category): 
                            $categoryName = $category['category'];
                            $categoryImage = $categoryImages[$categoryName] ?? 'category-placeholder.jpg';
                        ?>
                            <div class="category-card">
                                <a href="products.php?category=<?php echo urlencode($categoryName); ?>">
                                    <img 
                                        src="assets/img/<?php echo $categoryImage; ?>" 
                                        alt="<?php echo htmlspecialchars($categoryName); ?>"
                                        class="category-image"
                                        loading="lazy"
                                    >
                                    <div class="category-overlay"></div>
                                    <div class="category-content">
                                        <h3><?php echo htmlspecialchars($categoryName); ?></h3>
                                        <p><?php echo $category['count']; ?> products available</p>
                                        <span class="category-link">
                                            Explore <?php echo htmlspecialchars($categoryName); ?>
                                            <i class="fas fa-arrow-right icon"></i>
                                        </span>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-tags" style="font-size: 4rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
                        <h3>No Categories Available</h3>
                        <p class="text-secondary">Product categories will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="section newsletter-section">
            <div class="container">
                <div class="newsletter-content">
                    <h2>Stay in the Loop</h2>
                    <p>Get the latest Apple news, product updates, and exclusive offers delivered to your inbox.</p>
                    
                    <form class="newsletter-form" id="newsletter-form">
                        <input 
                            type="email" 
                            name="email" 
                            placeholder="Enter your email address" 
                            required
                            aria-label="Email address"
                        >
                        <button type="submit" class="btn btn-primary">
                            Subscribe
                        </button>
                    </form>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Products</h4>
                    <ul>
                        <li><a href="#">iPhone</a></li>
                        <li><a href="#">Mac</a></li>
                        <li><a href="#">iPad</a></li>
                        <li><a href="#">Apple Watch</a></li>
                        <li><a href="#">AirPods</a></li>
                        <li><a href="#">Accessories</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Services</h4>
                    <ul>
                        <li><a href="#">Apple Care</a></li>
                        <li><a href="#">Financing</a></li>
                        <li><a href="#">Trade In</a></li>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">Repairs</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Company</h4>
                    <ul>
                        <li><a href="#">About Apple</a></li>
                        <li><a href="#">Careers</a></li>
                        <li><a href="#">News</a></li>
                        <li><a href="#">Investors</a></li>
                        <li><a href="#">Environment</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Connect</h4>
                    <ul>
                        <li><a href="#">Find a Store</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Community</a></li>
                        <li><a href="#">Developer</a></li>
                        <li><a href="#">Education</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div>
                    <p>&copy; <?php echo date('Y'); ?> Apple Clone. All rights reserved. Built with ❤️</p>
                </div>
                <div>
                    <a href="#">Privacy Policy</a> | 
                    <a href="#">Terms of Service</a> | 
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="assets/js/main.js"></script>
    <script>
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scrolling for anchor links
            const links = document.querySelectorAll('a[href^="#"]');
            links.forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Newsletter form
            const newsletterForm = document.getElementById('newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('input[name="email"]').value;
                    
                    // Here you would normally send to server
                    alert('Thank you for subscribing! We\'ll keep you updated.');
                    this.reset();
                });
            }

            // Add scroll reveal animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('revealed');
                    }
                });
            }, observerOptions);

            // Observe elements for animations
            document.querySelectorAll('.product-card, .category-card, .section-header').forEach(el => {
                el.classList.add('scroll-reveal');
                observer.observe(el);
            });

            // Category navigation smooth scrolling
            document.querySelectorAll('a[href*="category="]').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Add loading state
                    const navbar = document.querySelector('.navbar');
                    navbar.style.opacity = '0.7';
                    
                    // Show loading indicator
                    const loadingDiv = document.createElement('div');
                    loadingDiv.innerHTML = `
                        <div style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); 
                                    background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; 
                                    box-shadow: 0 4px 20px rgba(0,0,0,0.1); z-index: 9999; text-align: center;">
                            <div style="width: 30px; height: 30px; border: 3px solid #007AFF; 
                                        border-top: 3px solid transparent; border-radius: 50%; 
                                        animation: spin 1s linear infinite; margin: 0 auto 10px;"></div>
                            <p style="margin: 0; color: #333; font-weight: 500;">Loading ${this.textContent}...</p>
                        </div>
                        <style>
                            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
                        </style>
                    `;
                    document.body.appendChild(loadingDiv);
                    
                    // Smooth fade out of products
                    const productGrid = document.querySelector('.product-grid');
                    if (productGrid) {
                        productGrid.style.transition = 'opacity 0.3s ease';
                        productGrid.style.opacity = '0';
                    }
                    
                    // Continue with normal navigation after animation
                    setTimeout(() => {
                        window.location.href = this.href;
                    }, 300);
                });
            });
        });
    </script>
</body>
</html>