<?php

function renderAdminNavigation($currentPage = '') {
    $navItems = [
        'dashboard.php' => ['icon' => 'tachometer-alt', 'label' => 'Dashboard'],
        'products.php' => ['icon' => 'box', 'label' => 'Products'],
        'users.php' => ['icon' => 'users', 'label' => 'Users'],
        'orders.php' => ['icon' => 'shopping-cart', 'label' => 'Orders'],
        'feedback.php' => ['icon' => 'comments', 'label' => 'Feedback'],
        'content-editor.php' => ['icon' => 'edit', 'label' => 'Content Editor'],
        'settings.php' => ['icon' => 'cog', 'label' => 'Settings']
    ];
    
    echo '<nav class="admin-nav">';
    foreach ($navItems as $page => $item) {
        $isActive = ($currentPage === $page) ? 'active' : '';
        echo "<a href=\"{$page}\" class=\"admin-nav-link {$isActive}\">";
        echo "<i class=\"fas fa-{$item['icon']}\"></i>";
        echo $item['label'];
        echo "</a>";
    }
    echo '</nav>';
}

function renderAdminSidebar($currentPage = '', $user = null) {
    ?>
    <div class="admin-sidebar">
        <div class="admin-logo">
            <i class="fab fa-apple"></i>
            <span>Apple Admin</span>
        </div>
        
        <?php renderAdminNavigation($currentPage); ?>
        
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
    <?php
}
?>