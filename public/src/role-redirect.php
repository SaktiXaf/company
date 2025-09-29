<?php

function redirectBasedOnRole($allowedRoles = ['customer'], $currentPage = null) {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return false; // Not logged in, no redirect needed
    }
    
    $userRole = $_SESSION['user_role'] ?? 'customer';
    
    // If user role matches allowed roles, continue
    if (in_array($userRole, $allowedRoles)) {
        return false; // Role allowed, no redirect needed
    }
    
    // Redirect based on role
    switch ($userRole) {
        case 'admin':
            if ($currentPage !== 'admin') {
                header('Location: admin/dashboard.php');
                exit;
            }
            break;
            
        case 'customer':
        default:
            if ($currentPage !== 'customer') {
                header('Location: index.php');
                exit;
            }
            break;
    }
    
    return true;
}

function requireRole($requiredRole = 'customer') {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: login.php?error=login_required');
        exit;
    }
    
    $userRole = $_SESSION['user_role'] ?? 'customer';
    
    // Check if user has required role
    if ($userRole !== $requiredRole) {
        if ($requiredRole === 'admin') {
            header('Location: index.php?error=access_denied');
        } else {
            header('Location: admin/dashboard.php?error=access_denied');
        }
        exit;
    }
    
    return true;
}

function getRedirectUrl($role = null) {
    if (!$role) {
        $role = $_SESSION['user_role'] ?? 'customer';
    }
    
    switch ($role) {
        case 'admin':
            return 'admin/dashboard.php';
        case 'customer':
        default:
            return 'index.php';
    }
}

function addRoleBasedNavigation() {
    session_start();
    
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        return '';
    }
    
    $userRole = $_SESSION['user_role'] ?? 'customer';
    $userName = $_SESSION['user_name'] ?? 'User';
    
    $navigation = '';
    
    if ($userRole === 'admin') {
        $navigation .= '<div class="role-navigation admin-nav-bar">';
        $navigation .= '<div class="container">';
        $navigation .= '<div class="nav-content">';
        $navigation .= '<span class="role-badge admin-badge">Administrator</span>';
        $navigation .= '<span class="welcome-text">Welcome, ' . htmlspecialchars($userName) . '</span>';
        $navigation .= '<div class="nav-actions">';
        $navigation .= '<a href="admin/dashboard.php" class="nav-link">Admin Dashboard</a>';
        $navigation .= '<a href="index.php?preview=1" class="nav-link">Preview Site</a>';
        $navigation .= '<a href="logout.php" class="nav-link">Logout</a>';
        $navigation .= '</div>';
        $navigation .= '</div>';
        $navigation .= '</div>';
        $navigation .= '</div>';
    } else {
        $navigation .= '<div class="role-navigation customer-nav-bar">';
        $navigation .= '<div class="container">';
        $navigation .= '<div class="nav-content">';
        $navigation .= '<span class="welcome-text">Welcome, ' . htmlspecialchars($userName) . '</span>';
        $navigation .= '<div class="nav-actions">';
        $navigation .= '<a href="profile.php" class="nav-link">My Profile</a>';
        $navigation .= '<a href="orders.php" class="nav-link">My Orders</a>';
        $navigation .= '<a href="logout.php" class="nav-link">Logout</a>';
        $navigation .= '</div>';
        $navigation .= '</div>';
        $navigation .= '</div>';
        $navigation .= '</div>';
    }
    
    return $navigation;
}

function addRoleBasedStyles() {
    return '
    <style>
    .role-navigation {
        background: linear-gradient(135deg, #007AFF, #5856D6);
        color: white;
        padding: 8px 0;
        font-size: 13px;
        position: sticky;
        top: 0;
        z-index: 1000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .role-navigation .nav-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .role-badge {
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .admin-badge {
        background: rgba(255,59,48,0.9);
    }
    
    .welcome-text {
        font-weight: 500;
        opacity: 0.9;
    }
    
    .nav-actions {
        display: flex;
        gap: 16px;
        align-items: center;
    }
    
    .nav-actions .nav-link {
        color: white;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
        font-weight: 500;
    }
    
    .nav-actions .nav-link:hover {
        background: rgba(255,255,255,0.2);
        text-decoration: none;
    }
    
    @media (max-width: 768px) {
        .role-navigation .nav-content {
            flex-direction: column;
            gap: 8px;
        }
        
        .nav-actions {
            gap: 12px;
        }
        
        .nav-actions .nav-link {
            padding: 4px 8px;
            font-size: 12px;
        }
    }
    </style>
    ';
}
?>