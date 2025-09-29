<?php

require_once 'db.php';

class OrderModel {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    // order
    public function createOrder($userId, $items, $shippingAddress, $currency = 'USD', $paymentMethod = null) {
        try {
            $this->db->beginTransaction();
            //kalkulasi order
            $total = 0;
            foreach ($items as $item) {
                $total += $item['unit_price'] * $item['quantity'];
            }
            
            $orderNumber = 'APL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            
            while ($this->db->fetch("SELECT id FROM orders WHERE order_number = ?", [$orderNumber])) {
                $orderNumber = 'APL-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }
            
            $orderData = [
                'user_id' => $userId,
                'order_number' => $orderNumber,
                'total' => $total,
                'currency' => $currency,
                'shipping_address' => json_encode($shippingAddress),
                'payment_method' => $paymentMethod,
                'status' => 'pending'
            ];
            
            $orderId = $this->db->insert('orders', $orderData);
            
            if (!$orderId) {
                throw new Exception('Failed to create order');
            }
            
            foreach ($items as $item) {
                $itemData = [
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'variant_id' => isset($item['variant_id']) ? $item['variant_id'] : null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['unit_price'] * $item['quantity']
                ];
                
                $itemId = $this->db->insert('order_items', $itemData);
                
                if (!$itemId) {
                    throw new Exception('Failed to add order item');
                }
                //update stok
                if (isset($item['variant_id']) && $item['variant_id']) {
                    $stockUpdated = $this->db->query(
                        "UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?",
                        [$item['quantity'], $item['variant_id'], $item['quantity']]
                    );
                    
                    if (!$stockUpdated) {
                        throw new Exception('Insufficient stock for product variant');
                    }
                }
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Order created successfully',
                'order_id' => $orderId,
                'order_number' => $orderNumber
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    public function getOrderById($id, $userId = null) {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
        $params = [$id];
        
        if ($userId) {
            $sql .= " AND o.user_id = ?";
            $params[] = $userId;
        }
        
        $order = $this->db->fetch($sql, $params);
        
        if ($order) {
            // decode json
            $order['shipping_address'] = json_decode($order['shipping_address'], true);
            $order['billing_address'] = json_decode($order['billing_address'], true);
            
            $order['items'] = $this->getOrderItems($id);
        }
        
        return $order;
    }
    
    public function getOrderByNumber($orderNumber, $userId = null) {
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.order_number = ?";
        $params = [$orderNumber];
        
        if ($userId) {
            $sql .= " AND o.user_id = ?";
            $params[] = $userId;
        }
        
        $order = $this->db->fetch($sql, $params);
        
        if ($order) {
            // decode json
            $order['shipping_address'] = json_decode($order['shipping_address'], true);
            $order['billing_address'] = json_decode($order['billing_address'], true);
            //ngambil item
            $order['items'] = $this->getOrderItems($order['id']);
        }
        
        return $order;
    }
    // get item order
    public function getOrderItems($orderId) {
        return $this->db->fetchAll(
            "SELECT oi.*, p.name as product_name, p.main_image, 
                    pv.variant_name, pv.color, pv.storage 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                LEFT JOIN product_variants pv ON oi.variant_id = pv.id 
                WHERE oi.order_id = ? 
                ORDER BY oi.id",
            [$orderId]
        );
    }
    
    //user order
    public function getUserOrders($userId, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        return $this->db->fetchAll(
            "SELECT * FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?",
            [$userId, $limit, $offset]
        );
    }
    
    //admin menerima orderan
    public function getAllOrders($page = 1, $limit = 20, $status = null) {
        $offset = ($page - 1) * $limit;
        $where = "";
        $params = [];
        
        if ($status) {
            $where = "WHERE o.status = ?";
            $params[] = $status;
        }
        
        $sql = "SELECT o.*, u.name as user_name, u.email as user_email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                {$where}
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    // get order total
    public function getTotalOrders($status = null) {
        $where = "";
        $params = [];
        
        if ($status) {
            $where = "WHERE status = ?";
            $params[] = $status;
        }
        
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM orders {$where}", $params);
        return $result ? $result['total'] : 0;
    }
    //update status order
    public function updateOrderStatus($orderId, $status, $notes = null) {
        $allowedStatuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $allowedStatuses)) {
            return ['success' => false, 'message' => 'Invalid status'];
        }
        
        $updateData = ['status' => $status];
        if ($notes) {
            $updateData['notes'] = $notes;
        }
        
        $success = $this->db->update('orders', $updateData, ['id' => $orderId]);
        
        if ($success) {
            return ['success' => true, 'message' => 'Order status updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update order status'];
        }
    }
    
    //cancel order
    public function cancelOrder($orderId, $userId = null) {
        // detail order
        $order = $this->getOrderById($orderId, $userId);
        
        if (!$order) {
            return ['success' => false, 'message' => 'Order not found'];
        }
        
        if ($order['status'] !== 'pending') {
            return ['success' => false, 'message' => 'Only pending orders can be cancelled'];
        }
        
        try {
            $this->db->beginTransaction();
            
            foreach ($order['items'] as $item) {
                if ($item['variant_id']) {
                    $this->db->query(
                        "UPDATE product_variants SET stock = stock + ? WHERE id = ?",
                        [$item['quantity'], $item['variant_id']]
                    );
                }
            }
            
            // update status orderan
            $this->db->update('orders', ['status' => 'cancelled'], ['id' => $orderId]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Order cancelled successfully'];
            
        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to cancel order'];
        }
    }
    
    //order statistik "admin"
    public function getOrderStats() {
        $stats = [];
        
        // Total order
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM orders");
        $stats['total_orders'] = $result ? $result['total'] : 0;
        
        // status order
        $statusStats = $this->db->fetchAll(
            "SELECT status, COUNT(*) as count FROM orders GROUP BY status"
        );
        
        foreach ($statusStats as $stat) {
            $stats[$stat['status'] . '_orders'] = $stat['count'];
        }
        
        // total pembayaran
        $result = $this->db->fetch(
            "SELECT SUM(total) as revenue FROM orders WHERE status IN ('paid', 'shipped', 'delivered')"
        );
        $stats['total_revenue'] = $result ? $result['revenue'] : 0;
        
        // orderan lama
        $stats['recent_orders'] = $this->db->fetchAll(
            "SELECT o.*, u.name as user_name 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT 5"
        );
        
        return $stats;
    }
    
    // search orderan
    public function searchOrders($keyword, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$keyword}%";
        
        return $this->db->fetchAll(
            "SELECT o.*, u.name as user_name, u.email as user_email 
             FROM orders o 
             JOIN users u ON o.user_id = u.id 
             WHERE o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ?
             ORDER BY o.created_at DESC 
             LIMIT ? OFFSET ?",
            [$searchTerm, $searchTerm, $searchTerm, $limit, $offset]
        );
    }
    
   //data bulanan
    public function getMonthlySales($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        return $this->db->fetchAll(
            "SELECT MONTH(created_at) as month, 
                    COUNT(*) as orders, 
                    SUM(total) as revenue 
             FROM orders 
             WHERE YEAR(created_at) = ? AND status IN ('paid', 'shipped', 'delivered')
             GROUP BY MONTH(created_at) 
             ORDER BY month",
            [$year]
        );
    }
}

$orderModel = new OrderModel();
?>