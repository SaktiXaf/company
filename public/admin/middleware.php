<?php

function requireAdmin() {
    session_start();
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: ../login.php?error=login_required');
        exit();
    }
    
    // Check if user is admin
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
        header('Location: ../index.php?error=access_denied');
        exit();
    }
    
    return true;
}

function getAdminUser() {
    require_once '../../src/auth.php';
    $auth = new Auth();
    return $auth->getCurrentUser();
}
?>