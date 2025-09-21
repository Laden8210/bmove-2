<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? '';

if ($action === 'reverse') {
    $lat = $_GET['lat'] ?? '';
    $lng = $_GET['lng'] ?? '';
    
    if (empty($lat) || empty($lng)) {
        echo json_encode(['error' => 'Missing lat or lng parameters']);
        exit;
    }
    
    $url = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&addressdetails=1";
    
    // Try multiple methods to handle SSL issues
    $response = false;
    
    // Method 1: Try with cURL (more reliable for SSL)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BMove Express System/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: BMove Express System/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            $response = false;
        }
    }
    
    // Method 2: Fallback to file_get_contents with improved context
    if ($response === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: BMove Express System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 15,
                'ignore_errors' => true
            ],
            'https' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
    }
    
    // Method 3: Try HTTP instead of HTTPS
    if ($response === false) {
        $httpUrl = str_replace('https://', 'http://', $url);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: BMove Express System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 15,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($httpUrl, false, $context);
    }
    
    if ($response === false) {
        echo json_encode(['error' => 'Failed to fetch geocoding data from all methods']);
        exit;
    }
    
    echo $response;
    
} elseif ($action === 'geocode') {
    $address = $_GET['address'] ?? '';
    
    if (empty($address)) {
        echo json_encode(['error' => 'Missing address parameter']);
        exit;
    }
    
    $encoded_address = urlencode($address);
    $url = "https://nominatim.openstreetmap.org/search?format=json&q={$encoded_address}&addressdetails=1&limit=1";
    
    // Try multiple methods to handle SSL issues
    $response = false;
    
    // Method 1: Try with cURL (more reliable for SSL)
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_USERAGENT, 'BMove Express System/1.0');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'User-Agent: BMove Express System/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false || $httpCode !== 200) {
            $response = false;
        }
    }
    
    // Method 2: Fallback to file_get_contents with improved context
    if ($response === false) {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: BMove Express System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 15,
                'ignore_errors' => true
            ],
            'https' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
    }
    
    // Method 3: Try HTTP instead of HTTPS
    if ($response === false) {
        $httpUrl = str_replace('https://', 'http://', $url);
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: BMove Express System/1.0',
                    'Accept: application/json'
                ],
                'timeout' => 15,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($httpUrl, false, $context);
    }
    
    if ($response === false) {
        echo json_encode(['error' => 'Failed to fetch geocoding data from all methods']);
        exit;
    }
    
    echo $response;
    
} else {
    echo json_encode(['error' => 'Invalid action. Use "reverse" or "geocode"']);
}
?>
