<?php
_once '../src/db.php';

try {
    $db = new Database();
    
    echo "<h2>Database Migration Status</h2>";
    
    try {
        $result = $db->query("SHOW COLUMNS FROM users LIKE 'username'");
        if (!$result->fetch()) {
            echo "<p>Adding username column...</p>";
            $db->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER name");
            
            // Generate usernames for existing users
            $users = $db->query("SELECT id, name FROM users")->fetchAll();
            foreach ($users as $user) {
                $username = strtolower(str_replace(' ', '', $user['name'])) . $user['id'];
                $db->query("UPDATE users SET username = ? WHERE id = ?", [$username, $user['id']]);
            }
            echo "<p style='color: green;'>✓ Username column added successfully!</p>";
        } else {
            echo "<p style='color: blue;'>✓ Username column already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error adding username column: " . $e->getMessage() . "</p>";
    }
    
    // Check and add profile_photo column
    try {
        $result = $db->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
        if (!$result->fetch()) {
            echo "<p>Adding profile_photo column...</p>";
            $db->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER password");
            echo "<p style='color: green;'>✓ Profile_photo column added successfully!</p>";
        } else {
            echo "<p style='color: blue;'>✓ Profile_photo column already exists</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error adding profile_photo column: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3 style='color: green;'>Migration completed!</h3>";
    echo "<p><a href='profile.php'>← Back to Profile</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    padding: 20px; 
    background: #f5f5f5; 
}
h2, h3 { 
    color: #333; 
}
p { 
    background: white; 
    padding: 10px; 
    border-radius: 5px; 
    margin: 10px 0; 
}
a { 
    color: #007AFF; 
    text-decoration: none; 
}
</style>