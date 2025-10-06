<?php

require_once 'db.php';

class ProductModel {
    private $db;
    
    public function __construct() {
        global $db;
        $this->db = $db;
    }
    
    public function getAllProducts($page = 1, $limit = 12, $category = null, $featured = null) {
        $offset = ($page - 1) * $limit;
        $where = "WHERE p.is_active = 1";
        $params = [];
        
        if ($category) {
            $where .= " AND p.category = ?";
            $params[] = $category;
        }
        
        if ($featured !== null) {
            $where .= " AND p.is_featured = ?";
            $params[] = $featured ? 1 : 0;
        }
        
        $sql = "SELECT p.*, 
                COUNT(pv.id) as variant_count,
                MIN(p.base_price + COALESCE(pv.extra_price, 0)) as min_price,
                MAX(p.base_price + COALESCE(pv.extra_price, 0)) as max_price
                FROM products p 
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                {$where}
                GROUP BY p.id
                ORDER BY p.is_featured DESC, p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    //total produk
    public function getTotalProducts($category = null, $featured = null) {
        $where = "WHERE is_active = 1";
        $params = [];
        
        if ($category) {
            $where .= " AND category = ?";
            $params[] = $category;
        }
        
        if ($featured !== null) {
            $where .= " AND is_featured = ?";
            $params[] = $featured ? 1 : 0;
        }
        
        $result = $this->db->fetch("SELECT COUNT(*) as total FROM products {$where}", $params);
        return $result ? $result['total'] : 0;
    }
    
    //ngambil produk dari id
    public function getProductById($id) {
        $product = $this->db->fetch(
            "SELECT * FROM products WHERE id = ? AND is_active = 1", 
            [$id]
        );
        
        if ($product) {
            // decode json
            $product['gallery'] = json_decode($product['gallery'], true) ?: [];
            $product['specifications'] = json_decode($product['specifications'], true) ?: [];
            
            $product['variants'] = $this->getProductVariants($id);
        }
        
        return $product;
    }
    
    //ngambil produk dari slug
    public function getProductBySlug($slug) {
        $product = $this->db->fetch(
            "SELECT * FROM products WHERE slug = ? AND is_active = 1", 
            [$slug]
        );
        
        if ($product) {
            // decode json
            $product['gallery'] = json_decode($product['gallery'], true) ?: [];
            $product['specifications'] = json_decode($product['specifications'], true) ?: [];
            
            // Get variants
            $product['variants'] = $this->getProductVariants($product['id']);
        }
        
        return $product;
    }
    
    //ngambil produk berdasar barang
    public function getProductVariants($productId) {
        return $this->db->fetchAll(
            "SELECT * FROM product_variants 
                WHERE product_id = ? AND is_active = 1 
                ORDER BY extra_price ASC", 
            [$productId]
        );
    }
    
    //ngambil produk dari id
    public function getVariantById($id) {
        return $this->db->fetch(
            "SELECT pv.*, p.name as product_name, p.base_price 
                FROM product_variants pv 
                JOIN products p ON pv.product_id = p.id 
                WHERE pv.id = ? AND pv.is_active = 1", 
            [$id]
        );
    }
    
    //search produk
    public function searchProducts($keyword, $page = 1, $limit = 12) {
        $offset = ($page - 1) * $limit;
        $searchTerm = "%{$keyword}%";
        
        $sql = "SELECT p.*, 
                COUNT(pv.id) as variant_count,
                MIN(p.base_price + COALESCE(pv.extra_price, 0)) as min_price,
                MAX(p.base_price + COALESCE(pv.extra_price, 0)) as max_price
                FROM products p 
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                WHERE p.is_active = 1 
                AND (p.name LIKE ? OR p.short_desc LIKE ? OR p.description LIKE ? OR p.category LIKE ?)
                GROUP BY p.id
                ORDER BY p.is_featured DESC, p.name ASC 
                LIMIT ? OFFSET ?";
        
        return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset]);
    }
    
    //hasil search
    public function getSearchCount($keyword) {
        $searchTerm = "%{$keyword}%";
        
        $result = $this->db->fetch(
            "SELECT COUNT(*) as total FROM products 
                WHERE is_active = 1 
                AND (name LIKE ? OR short_desc LIKE ? OR description LIKE ? OR category LIKE ?)", 
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm]
        );
        
        return $result ? $result['total'] : 0;
    }
    
    // get featured produk
    public function getFeaturedProducts($limit = 6) {
        return $this->db->fetchAll(
            "SELECT p.*, 
                COUNT(pv.id) as variant_count,
                MIN(p.base_price + COALESCE(pv.extra_price, 0)) as min_price,
                MAX(p.base_price + COALESCE(pv.extra_price, 0)) as max_price
                FROM products p 
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                WHERE p.is_active = 1 AND p.is_featured = 1 
                GROUP BY p.id
                ORDER BY p.created_at DESC 
                LIMIT ?", 
            [$limit]
        );
    }

    //ngambil berdasar kategori
    public function getProductsByCategory($category, $limit = 12) {
        return $this->db->fetchAll(
            "SELECT p.*, 
                COUNT(pv.id) as variant_count,
                MIN(p.base_price + COALESCE(pv.extra_price, 0)) as min_price,
                MAX(p.base_price + COALESCE(pv.extra_price, 0)) as max_price
                FROM products p 
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                WHERE p.is_active = 1 AND p.category = ?
                GROUP BY p.id
                ORDER BY p.created_at DESC 
                LIMIT ?", 
            [$category, $limit]
        );
    }
    
    //related produk
    public function getRelatedProducts($productId, $category, $limit = 4) {
        return $this->db->fetchAll(
            "SELECT p.*, 
                COUNT(pv.id) as variant_count,
                MIN(p.base_price + COALESCE(pv.extra_price, 0)) as min_price,
                MAX(p.base_price + COALESCE(pv.extra_price, 0)) as max_price
                FROM products p 
                LEFT JOIN product_variants pv ON p.id = pv.product_id AND pv.is_active = 1
                WHERE p.is_active = 1 AND p.category = ? AND p.id != ? 
                GROUP BY p.id
                ORDER BY RAND() 
                LIMIT ?", 
            [$category, $productId, $limit]
        );
    }
    
    //get kategori produk
    public function getCategories() {
        return $this->db->fetchAll(
            "SELECT category, COUNT(*) as count 
                FROM products 
                WHERE is_active = 1 
                GROUP BY category 
                ORDER BY category"
        );
    }
    
    //nambah produk "admin"
    public function addProduct($data) {
        // validasi
        if (empty($data['name']) || empty($data['category']) || empty($data['base_price'])) {
            return ['success' => false, 'message' => 'Name, category, and price are required'];
        }
        // generate slug dari nama
        $slug = $this->generateSlug($data['name']);
        // cek slug
        $existingProduct = $this->db->fetch("SELECT id FROM products WHERE slug = ?", [$slug]);
        if ($existingProduct) {
            $slug .= '-' . time();
        }
        
        $productData = [
            'slug' => $slug,
            'name' => trim($data['name']),
            'short_desc' => isset($data['short_desc']) ? trim($data['short_desc']) : null,
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'category' => trim($data['category']),
            'base_price' => floatval($data['base_price']),
            'main_image' => isset($data['main_image']) ? $data['main_image'] : null,
            'gallery' => isset($data['gallery']) ? json_encode($data['gallery']) : null,
            'specifications' => isset($data['specifications']) ? json_encode($data['specifications']) : null,
            'is_featured' => isset($data['is_featured']) ? 1 : 0,
            'is_active' => 1
        ];
        
        $productId = $this->db->insert('products', $productData);
        
        if ($productId) {
            return ['success' => true, 'message' => 'Product added successfully', 'product_id' => $productId];
        } else {
            return ['success' => false, 'message' => 'Failed to add product'];
        }
    }
    
    //update produk "admin"
    public function updateProduct($id, $data) {
        // validasi
        if (empty($data['name']) || empty($data['category']) || empty($data['base_price'])) {
            return ['success' => false, 'message' => 'Name, category, and price are required'];
        }
        
        $updateData = [
            'name' => trim($data['name']),
            'short_desc' => isset($data['short_desc']) ? trim($data['short_desc']) : null,
            'description' => isset($data['description']) ? trim($data['description']) : null,
            'category' => trim($data['category']),
            'base_price' => floatval($data['base_price']),
            'main_image' => isset($data['main_image']) ? $data['main_image'] : null,
            'gallery' => isset($data['gallery']) ? json_encode($data['gallery']) : null,
            'specifications' => isset($data['specifications']) ? json_encode($data['specifications']) : null,
            'is_featured' => isset($data['is_featured']) ? 1 : 0,
            'is_active' => isset($data['is_active']) ? 1 : 0
        ];
        
        $success = $this->db->update('products', $updateData, ['id' => $id]);
        
        if ($success) {
            return ['success' => true, 'message' => 'Product updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update product'];
        }
    }
    
    //hapus produk "admin"
    public function deleteProduct($id) {
        $success = $this->db->update('products', ['is_active' => 0], ['id' => $id]);
        
        if ($success) {
            return ['success' => true, 'message' => 'Product deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete product'];
        }
    }
    
    //nambah produk variant
    public function addVariant($data) {
        $variantData = [
            'product_id' => intval($data['product_id']),
            'variant_name' => trim($data['variant_name']),
            'color' => isset($data['color']) ? trim($data['color']) : null,
            'storage' => isset($data['storage']) ? trim($data['storage']) : null,
            'extra_price' => floatval($data['extra_price']),
            'stock' => intval($data['stock']),
            'sku' => trim($data['sku']),
            'is_active' => 1
        ];
        
        $variantId = $this->db->insert('product_variants', $variantData);
        
        if ($variantId) {
            return ['success' => true, 'message' => 'Variant added successfully', 'variant_id' => $variantId];
        } else {
            return ['success' => false, 'message' => 'Failed to add variant'];
        }
    }
    //generate slug dari string
    private function generateSlug($string) {
        $slug = strtolower(trim($string));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }
    
    //update stok
    public function updateStock($variantId, $quantity) {
        return $this->db->query(
            "UPDATE product_variants SET stock = stock - ? WHERE id = ? AND stock >= ?",
            [$quantity, $variantId, $quantity]
        );
    }
    
    //get harga produk dari varian
    public function getPrice($productId, $variantId = null) {
        if ($variantId) {
            $result = $this->db->fetch(
                "SELECT p.base_price, pv.extra_price 
                    FROM products p 
                    JOIN product_variants pv ON p.id = pv.product_id 
                    WHERE p.id = ? AND pv.id = ?",
                [$productId, $variantId]
            );
            
            if ($result) {
                return $result['base_price'] + $result['extra_price'];
            }
        }
        
        $result = $this->db->fetch("SELECT base_price FROM products WHERE id = ?", [$productId]);
        return $result ? $result['base_price'] : 0;
    }
}

$productModel = new ProductModel();
?>