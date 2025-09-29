-- Create Admin Accounts
-- Run this SQL script to create admin accounts directly in database

-- Insert Admin Account 1
INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) 
VALUES (
    'Admin Apple', 
    'admin', 
    'admin@apple.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin', 
    1, 
    NOW(), 
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    is_active = 1;

-- Insert Admin Account 2  
INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) 
VALUES (
    'Super Admin', 
    'superadmin', 
    'superadmin@apple.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin', 
    1, 
    NOW(), 
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    is_active = 1;

-- Insert Admin Account 3 (Custom)
INSERT INTO users (name, username, email, password, role, is_active, created_at, updated_at) 
VALUES (
    'Your Name', 
    'yourusername', 
    'your@email.com', 
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: password
    'admin', 
    1, 
    NOW(), 
    NOW()
) ON DUPLICATE KEY UPDATE 
    role = 'admin',
    is_active = 1;

-- Check created admin accounts
SELECT id, name, username, email, role, is_active, created_at 
FROM users 
WHERE role = 'admin';

-- Note: Default password for all accounts is 'password'
-- Please change the password after first login for security!