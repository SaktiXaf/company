<?php

require_once '../src/db.php';

try {
    $db = new Database();
    // ngecek usn
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'username'");
    if (!$result->fetch()) {
        echo "Adding username column...\n";
        $db->query("ALTER TABLE users ADD COLUMN username VARCHAR(50) UNIQUE AFTER name");
        // update usn
        $users = $db->query("SELECT id, name FROM users")->fetchAll();
        foreach ($users as $user) {
            $username = strtolower(str_replace(' ', '', $user['name']));
            $db->query("UPDATE users SET username = ? WHERE id = ?", [$username, $user['id']]);
        }
        echo "Username column added successfully!\n";
    } else {
        echo "Username column already exists.\n";
    }
    // cek foto prfil
    $result = $db->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    if (!$result->fetch()) {
        echo "Adding profile_photo column...\n";
        $db->query("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) NULL AFTER password");
        echo "Profile_photo column added successfully!\n";
    } else {
        echo "Profile_photo column already exists.\n";
    }
    
    echo "Database migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>