<?php
function getHeroContent() {
    $default = [
        'title' => 'iPhone 15 Pro',
        'subtitle' => 'Titanium. So strong. So light. So Pro.',
        'description' => 'iPhone 15 Pro is the first iPhone to feature an aerospace-grade titanium design, using the same alloy that spacecraft use for missions to Mars.',
        'button_text' => 'Learn more',
        'button_link' => '#products',
        'background_image' => 'assets/images/iphone-15-pro-hero.jpg'
    ];
    
    $content_file = __DIR__ . '/../../content/hero.json';
    if (file_exists($content_file)) {
        $content = json_decode(file_get_contents($content_file), true);
        return array_merge($default, $content);
    }
    
    return $default;
}

function getAboutContent() {
    $default = [
        'title' => 'Innovation at its finest',
        'description' => 'At Apple, we believe that technology should enhance your life, not complicate it. Every product we create is designed with you in mind.',
        'features' => [
            'Revolutionary design',
            'Cutting-edge technology', 
            'Seamless integration',
            'Unparalleled performance'
        ]
    ];
    
    $content_file = __DIR__ . '/../../content/about.json';
    if (file_exists($content_file)) {
        $content = json_decode(file_get_contents($content_file), true);
        return array_merge($default, $content);
    }
    
    return $default;
}

function getContactContent() {
    $default = [
        'title' => 'Get in touch',
        'description' => 'Have questions about our products? Our team is here to help.',
        'address' => 'Apple Park, Cupertino, CA 95014',
        'phone' => '+1 (800) APL-CARE',
        'email' => 'support@apple.com',
        'hours' => 'Mon-Fri: 8AM-8PM PST'
    ];
    
    $content_file = __DIR__ . '/../../content/contact.json';
    if (file_exists($content_file)) {
        $content = json_decode(file_get_contents($content_file), true);
        return array_merge($default, $content);
    }
    
    return $default;
}

function getCompanySettings() {
    $default = [
        'company_name' => 'Apple Store',
        'tagline' => 'Think Different',
        'logo' => 'assets/images/apple-logo.png',
        'favicon' => 'assets/images/favicon.ico',
        'social' => [
            'facebook' => '#',
            'twitter' => '#',
            'instagram' => '#',
            'youtube' => '#'
        ]
    ];
    
    $content_file = __DIR__ . '/../../content/settings.json';
    if (file_exists($content_file)) {
        $content = json_decode(file_get_contents($content_file), true);
        return array_merge($default, $content);
    }
    
    return $default;
}

// Cache content for better performance
function getCachedContent($type, $ttl = 3600) {
    $cache_file = __DIR__ . "/../../cache/{$type}_cache.json";
    $cache_dir = dirname($cache_file);
    
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    
    if (file_exists($cache_file)) {
        $cache = json_decode(file_get_contents($cache_file), true);
        if ($cache && (time() - $cache['timestamp']) < $ttl) {
            return $cache['data'];
        }
    }
    
    // Generate fresh content
    switch ($type) {
        case 'hero':
            $data = getHeroContent();
            break;
        case 'about':
            $data = getAboutContent();
            break;
        case 'contact':
            $data = getContactContent();
            break;
        case 'settings':
            $data = getCompanySettings();
            break;
        default:
            return null;
    }
    
    // Save to cache
    $cache_data = [
        'timestamp' => time(),
        'data' => $data
    ];
    file_put_contents($cache_file, json_encode($cache_data));
    
    return $data;
}

// Clear all content cache
function clearContentCache() {
    $cache_dir = __DIR__ . '/../../cache/';
    if (is_dir($cache_dir)) {
        $files = glob($cache_dir . '*_cache.json');
        foreach ($files as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}
?>