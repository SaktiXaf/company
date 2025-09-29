<?php

require_once 'db.php';

class UserModel {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    // get semua user
    public function getAllUsers($page = 1, $limit = 20, $role = null) {
        $offset = ($page - 1) * $limit;
        $where = "";
        $params = [];
        
        if ($role) {
            $where = "WHERE role = ?";
            $params[] = $role;
        }
        
        $sql = "SELECT id, name, email, role, country, phone, is_active, created_at 
                FROM users 
                {$where}
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    //get total user
    public function getTotalUsers($role = null) {
        $where = "";
        $params = [];
        
        if ($role) {
            $where = "WHERE role = ?";
            $params[] = $role;
        }
        
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM users {$where}", $params);
        return $result ? $result['total'] : 0;
    }
    
    //get user dari id
    public function getUserById($id) {
        return $this->db->fetch(
            "SELECT id, name, email, role, country, phone, address, is_active, created_at, updated_at 
            FROM users WHERE id = ?", 
            [$id]
        );
    }
    
    //get user dari email
    public function getUserByEmail($email) {
        return $this->db->fetch(
            "SELECT id, name, email, role, country, phone, address, is_active, created_at 
            FROM users WHERE email = ?", 
            [$email]
        );
    }
    
    // search user
    public function searchUsers($keyword, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$keyword}%";
        
        return $this->db->fetchAll(
            "SELECT id, name, email, role, country, phone, is_active, created_at 
                FROM users 
                WHERE name LIKE ? OR email LIKE ? 
                ORDER BY name ASC 
                LIMIT ? OFFSET ?",
            [$searchTerm, $searchTerm, $limit, $offset]
        );
    }
    
    //update status user
    public function updateUserStatus($userId, $isActive) {
        $success = $this->db->update('users', ['is_active' => $isActive ? 1 : 0], ['id' => $userId]);
        
        if ($success) {
            $status = $isActive ? 'activated' : 'deactivated';
            return ['success' => true, 'message' => "User {$status} successfully"];
        } else {
            return ['success' => false, 'message' => 'Failed to update user status'];
        }
    }
    
    //update role user
    public function updateUserRole($userId, $role) {
        $allowedRoles = ['customer', 'admin'];
        
        if (!in_array($role, $allowedRoles)) {
            return ['success' => false, 'message' => 'Invalid role'];
        }
        
        $success = $this->db->update('users', ['role' => $role], ['id' => $userId]);
        
        if ($success) {
            return ['success' => true, 'message' => 'User role updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user role'];
        }
    }
    
    //hapus user
    public function deleteUser($userId) {
        // Check if user has orders
        $orders = $this->db->fetch("SELECT COUNT(*) as count FROM orders WHERE user_id = ?", [$userId]);
        
        if ($orders && $orders['count'] > 0) {
            // soft delete
            $success = $this->db->update('users', ['is_active' => 0], ['id' => $userId]);
            
            if ($success) {
                return ['success' => true, 'message' => 'User deactivated successfully (has existing orders)'];
            } else {
                return ['success' => false, 'message' => 'Failed to deactivate user'];
            }
        } else {
            // hard delte
            $success = $this->db->delete('users', ['id' => $userId]);
            
            if ($success) {
                return ['success' => true, 'message' => 'User deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete user'];
            }
        }
    }
    
    //statistik user
    public function getUserStats() {
        $stats = [];
        
        // total user
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $result ? $result['total'] : 0;
        
        // user aktif
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM users WHERE is_active = 1");
        $stats['active_users'] = $result ? $result['total'] : 0;
        
        // role user
        $roleStats = $this->db->fetchAll(
            "SELECT role, COUNT(*) as count FROM users GROUP BY role"
        );
        
        foreach ($roleStats as $stat) {
            $stats[$stat['role'] . '_users'] = $stat['count'];
        }
        
        // negara user
        $stats['users_by_country'] = $this->db->fetchAll(
            "SELECT country, COUNT(*) as count FROM users GROUP BY country ORDER BY count DESC LIMIT 10"
        );
        
        // regis awal
        $stats['recent_users'] = $this->db->fetchAll(
            "SELECT name, email, country, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT 5"
        );
        
        // regis bulanan
        $stats['monthly_registrations'] = $this->db->fetchAll(
            "SELECT MONTH(created_at) as month, 
                    YEAR(created_at) as year,
                    COUNT(*) as count 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY YEAR(created_at), MONTH(created_at) 
                ORDER BY year DESC, month DESC"
        );
        
        return $stats;
    }
    
    // get statistik user dengan order
    public function getUserWithStats($userId) {
        $user = $this->getUserById($userId);
        
        if ($user) {
            $orderStats = $this->db->fetch(
                "SELECT COUNT(*) as total_orders, 
                        SUM(total) as total_spent,
                        MAX(created_at) as last_order 
                    FROM orders 
                    WHERE user_id = ?",
                [$userId]
            );
            
            $user['order_stats'] = $orderStats ?: [
                'total_orders' => 0,
                'total_spent' => 0,
                'last_order' => null
            ];
            
            $user['recent_orders'] = $this->db->fetchAll(
                "SELECT id, order_number, total, status, created_at 
                    FROM orders 
                    WHERE user_id = ? 
                    ORDER BY created_at DESC 
                    LIMIT 5",
                [$userId]
            );
        }
        
        return $user;
    }
    
    //update profil user
    public function updateUserProfile($userId, $data) {
        // validasi
        if (empty($data['name']) || empty($data['email'])) {
            return ['success' => false, 'message' => 'Name and email are required'];
        }
        
        // cek format email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }
        
        // cek hanya satu email buat 1 user
        $existingUser = $this->db->fetch(
            "SELECT id FROM users WHERE email = ? AND id != ?", 
            [$data['email'], $userId]
        );
        
        if ($existingUser) {
            return ['success' => false, 'message' => 'Email is already taken'];
        }
        
        // preparing update data
        $updateData = [
            'name' => trim($data['name']),
            'email' => trim(strtolower($data['email'])),
            'phone' => isset($data['phone']) ? trim($data['phone']) : null,
            'address' => isset($data['address']) ? trim($data['address']) : null,
            'country' => isset($data['country']) ? $data['country'] : 'US'
        ];
        
        $success = $this->db->update('users', $updateData, ['id' => $userId]);
        
        if ($success) {
            return ['success' => true, 'message' => 'Profile updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update profile'];
        }
    }
    
    // get customer "admin"
    public function getTopCustomers($limit = 10) {
        return $this->db->fetchAll(
            "SELECT u.id, u.name, u.email, u.country,
                    COUNT(o.id) as total_orders,
                    SUM(o.total) as total_spent,
                    MAX(o.created_at) as last_order
                FROM users u
                LEFT JOIN orders o ON u.id = o.user_id
                WHERE u.role = 'customer' AND u.is_active = 1
                GROUP BY u.id
                HAVING total_orders > 0
                ORDER BY total_spent DESC
                LIMIT ?",
            [$limit]
        );
    }
    
    //get aktivitas customer
    public function getCustomerActivity($days = 30) {
        return $this->db->fetchAll(
            "SELECT DATE(created_at) as date, COUNT(*) as registrations
             FROM users 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            [$days]
        );
    }
}

$userModel = new UserModel();
?>