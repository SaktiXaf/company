<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../../src/content-helper.php';

$action = $_GET['action'] ?? 'get';
$type = $_GET['type'] ?? 'all';

try {
    switch ($action) {
        case 'get':
            $response = [];
            
            if ($type === 'all' || $type === 'hero') {
                $response['hero'] = getCachedContent('hero', 60); // 1 minute cache
            }
            
            if ($type === 'all' || $type === 'about') {
                $response['about'] = getCachedContent('about', 60);
            }
            
            if ($type === 'all' || $type === 'contact') {
                $response['contact'] = getCachedContent('contact', 60);
            }
            
            if ($type === 'all' || $type === 'settings') {
                $response['settings'] = getCachedContent('settings', 60);
            }
            
            echo json_encode([
                'success' => true,
                'data' => $response,
                'timestamp' => time()
            ]);
            break;
            
        case 'clear_cache':
            clearContentCache();
            echo json_encode([
                'success' => true,
                'message' => 'Cache cleared successfully'
            ]);
            break;
            
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST method required');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                throw new Exception('Invalid JSON data');
            }
            
            $content_type = $input['type'] ?? null;
            $content_data = $input['data'] ?? null;
            
            if (!$content_type || !$content_data) {
                throw new Exception('Type and data are required');
            }
            
            $file_path = "../../content/{$content_type}.json";
            $content_data['updated_at'] = date('Y-m-d H:i:s');
            $content_data['updated_by'] = 'API';
            
            if (file_put_contents($file_path, json_encode($content_data, JSON_PRETTY_PRINT))) {
                clearContentCache(); // Clear cache after update
                echo json_encode([
                    'success' => true,
                    'message' => 'Content updated successfully'
                ]);
            } else {
                throw new Exception('Failed to save content');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>