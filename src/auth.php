<?php
require_once 'db.php';

class Auth {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    //register
    public function register($name, $username, $email, $password, $country = 'US') {
        // validasi
        if (empty($name) || empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }
        
        if (strlen($username) < 3) {
            return ['success' => false, 'message' => 'Username must be at least 3 characters'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        // ngecek email ada atau ga
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE email = ?", 
            [$email]
        );
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email already registered'];
        }
        // cek usn
        $existingUsername = $this->db->fetch(
            "SELECT id FROM users WHERE username = ?", 
            [$username]
        );
        
        if ($existingUsername) {
            return ['success' => false, 'message' => 'Username already taken'];
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        //nambah user
        $userData = [
            'name' => trim($name),
            'username' => trim($username),
            'email' => trim(strtolower($email)),
            'password' => $hashedPassword,
            'country' => $country,
            'role' => 'customer'
        ];
        
        $userId = $this->db->insert('users', $userData);
        
        if ($userId) {
            return ['success' => true, 'message' => 'Registration successful', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function login($email, $password) {
        // validasi login
        if (empty($email) || empty($password)) {
            return ['success' => false, 'message' => 'Email and password are required'];
        }
        // ngambil data dari database 
        $user = $this->db->fetch(
            "SELECT * FROM users WHERE email = ? AND is_active = 1", 
            [trim(strtolower($email))]
        );
        
        if (!$user) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        //verif pw
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['username'] = isset($user['username']) ? $user['username'] : $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_country'] = $user['country'];
        $_SESSION['logged_in'] = true;
        
        return [
            'success' => true, 
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
                'country' => $user['country']
            ]
        ];
    }
    
    public function updateSession($userId) {
        try {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$userId]);
            if ($user) {
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['username'] = isset($user['username']) ? $user['username'] : $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_country'] = isset($user['country']) ? $user['country'] : 'US';
                return true;
            }
        } catch (Exception $e) {
        }
        return false;
    }
    
    public function logout() {
        $_SESSION = array();
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    //cek admin atau bukan
    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    //ngambil data user
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $user = $this->db->fetch("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
            if ($user) {
                return $user;
            }
        } catch (Exception $e) {

        }
        return [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'username' => $_SESSION['username'] ?? $_SESSION['user_name'],
            'email' => $_SESSION['user_email'],
            'role' => $_SESSION['user_role'],
            'country' => $_SESSION['user_country'],
            'profile_photo' => null
        ];
    }
    
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT id, name, email, role, country, phone, address, is_active, created_at 
             FROM users WHERE id = ?", 
            [$id]
        );
    }
    
    //update profil user
    public function updateProfile($userId, $data) {
        // validasi
        if (empty($data['name']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'Name and email are required'];
        }
        
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id != ?", 
            [$data['email'], $userId]
        );
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email is already taken'];
        }
        
        $updateData = [
            'name' => trim($data['name']),
            'email' => trim(strtolower($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'address' => isset($data['address']) ? trim($data['address']) : null,
            'country' => isset($data['country']) ? $data['country'] : 'US'
        ];
        
        if (!empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                return ['success' => false, 'message' => 'Password must be at least 6 characters'];
            }
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        $success = $this->db->update('users', $updateData, ['id' => $userId]);
        
        if ($success) {
            if ($this->isLoggedIn() && $_SESSION['user_id'] == $userId) {
                $_SESSION['user_name'] = $updateData['name'];
                $_SESSION['user_email'] = $updateData['email'];
                $_SESSION['user_country'] = $updateData['country'];
            }
            
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    //ganti pw
    public function changePassword($userId, $currentPassword, $newPassword) {
        $user = $this->db->fetch("SELECT password FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // verif pw lama
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // validasi pw baru
        if (strlen($newPassword) < 6) {
            return ['success' => false, 'message' => 'New password must be at least 6 characters'];
        }
        
        // update pw
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $success = $this->db->update('users', ['password' => $hashedPassword], ['id' => $userId]);
        
        if ($success) {
            return ['success' => true, 'message' => 'Password changed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    public function requireLogin($redirectTo = '/login.php') {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectTo");
            exit;
        }
    }
    
    public function requireAdmin($redirectTo = '/index.php') {
        if (!$this->isAdmin()) {
            header("Location: $redirectTo");
            exit;
        }
    }
}

$auth = new Auth();
?>