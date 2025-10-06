<?php
/**
 * Session Helper
 * Centralized session management to avoid conflicts
 */

// Global session management function
if (!function_exists('initSession')) {
    function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

// Initialize session globally
initSession();
?>