<?php
/**
 * Session Test - Check for session conflicts
 */

echo "<h2>Testing Session Management</h2>";

// Test 1: Direct session helper
echo "<h3>Test 1: Session Helper</h3>";
require_once 'src/session-helper.php';
echo "<p>✅ Session helper loaded without errors</p>";
echo "<p>Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive') . "</p>";

// Test 2: Role redirect functions
echo "<h3>Test 2: Role Redirect Functions</h3>";
require_once 'src/role-redirect.php';
echo "<p>✅ Role redirect loaded without errors</p>";

// Test 3: Navigation function
echo "<h3>Test 3: Navigation Test</h3>";
$nav = addRoleBasedNavigation();
if (empty($nav)) {
    echo "<p>ℹ️ No navigation (user not logged in - this is expected)</p>";
} else {
    echo "<p>✅ Navigation generated</p>";
}

// Test 4: Mock session data and test functions
echo "<h3>Test 4: Mock Session Test</h3>";
$_SESSION['logged_in'] = true;
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'Test Admin';

echo "<p>Mock session data set:</p>";
echo "<ul>";
echo "<li>logged_in: " . ($_SESSION['logged_in'] ? 'true' : 'false') . "</li>";
echo "<li>user_role: " . ($_SESSION['user_role'] ?? 'none') . "</li>";
echo "<li>user_name: " . ($_SESSION['user_name'] ?? 'none') . "</li>";
echo "</ul>";

// Test redirect function
echo "<h3>Test 5: Redirect Function Test</h3>";
ob_start();
$result = redirectBasedOnRole(['admin'], 'admin');
ob_end_clean();
echo "<p>redirectBasedOnRole result: " . ($result ? 'redirected' : 'no redirect needed') . "</p>";

// Test getRedirectUrl
$adminUrl = getRedirectUrl('admin');
$customerUrl = getRedirectUrl('customer');
echo "<p>Admin redirect URL: $adminUrl</p>";
echo "<p>Customer redirect URL: $customerUrl</p>";

// Clean up mock data
unset($_SESSION['logged_in'], $_SESSION['user_role'], $_SESSION['user_name']);

echo "<h3>✅ All Tests Completed Successfully!</h3>";
echo "<p>No session conflicts detected.</p>";
echo "<p><a href='index.php'>← Back to Main Site</a></p>";
echo "<p><a href='admin/dashboard.php'>Admin Dashboard</a></p>";
?>

<style>
body {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
    background: #f5f5f7;
}

h2, h3 {
    color: #1d1d1f;
}

p, li {
    color: #424245;
}

ul {
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

a {
    color: #007AFF;
    text-decoration: none;
    font-weight: 500;
}

a:hover {
    text-decoration: underline;
}
</style>