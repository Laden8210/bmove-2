<?php


class PayMongoService {
    private $apiKey;
    private $baseUrl = 'https://api.paymongo.com/v1';
    
    public function __construct() {
        // Get API key from system settings or use default
        $this->apiKey = $this->getApiKeyFromSettings();
    }
    
    /**
     * Get API key from system settings
     */
    private function getApiKeyFromSettings() {
        try {
            // Create database connection
            $conn = $this->createDatabaseConnection();
            
            if ($conn === null) {
                throw new Exception('Database connection failed');
            }
            
            $result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'paymongo_api_key'");
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $conn->close();
                return $row['setting_value'];
            }
            
            $conn->close();
            // Fallback to new test key
            return 'sk_test_dqX2uveywfbdi6Tc3evEgyFy';
            
        } catch (Exception $e) {
            // If database fails, use fallback key
            return 'sk_test_dqX2uveywfbdi6Tc3evEgyFy';
        }
    }
    
    /**
     * Create database connection
     */
    private function createDatabaseConnection() {
        try {
            require_once __DIR__ . '/../config/config.php';
            
            // Check if connection is available
            if (isset($conn) && $conn !== null) {
                return $conn;
            }
            
            // If not available, create new connection
            $servername = "localhost";
            $username = "root";
            $password = "";
            $dbname = "bmove_express";
            
            $newConn = new mysqli($servername, $username, $password, $dbname);
            
            if ($newConn->connect_error) {
                return null;
            }
            
            return $newConn;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Create a checkout session for payment
     */
    public function createCheckoutSession($bookingData, $userData, $amount, $description) {
        // Truncate description to 255 characters for PayMongo API compliance
        $truncatedDescription = $this->truncateDescription($description, 255);
        $truncatedLineItemDescription = $this->truncateDescription($description, 255);
        
        $data = [
            'data' => [
                'attributes' => [
                    'cancel_url' => $this->getBaseUrl() . '/payment-cancel?booking_id=' . $bookingData['booking_id'],
                    'success_url' => $this->getBaseUrl() . '/payment-success?booking_id=' . $bookingData['booking_id'],
                    'billing' => [
                        'name' => $userData['full_name'],
                        'email' => $userData['email_address'],
                        'phone' => $userData['contact_number'],
                    ],
                    'send_email_receipt' => true,
                    'show_description' => true,
                    'show_line_items' => true,
                    'description' => $truncatedDescription,
                    'line_items' => [
                        [
                            'currency' => 'PHP',
                            'amount' => (int)($amount * 100), // Convert to centavos
                            'description' => $truncatedLineItemDescription,
                            'name' => 'Vehicle Booking - ' . $bookingData['vehicle_name'],
                            'quantity' => 1,
                        ],
                    ],
                    'payment_method_types' => ['gcash', 'grab_pay', 'paymaya'],
                    'reference_number' => $bookingData['booking_id'],
                ]
            ]
        ];

        return $this->makeRequest('POST', '/checkout_sessions', $data);
    }
    
    /**
     * Truncate description to specified length
     */
    private function truncateDescription($description, $maxLength = 255) {
        if (strlen($description) <= $maxLength) {
            return $description;
        }
        
        // Truncate and add ellipsis
        return substr($description, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Retrieve payment intent details
     */
    public function getPaymentIntent($paymentIntentId) {
        return $this->makeRequest('GET', '/payment_intents/' . $paymentIntentId);
    }
    
    /**
     * Retrieve checkout session details
     */
    public function getCheckoutSession($sessionId) {
        return $this->makeRequest('GET', '/checkout_sessions/' . $sessionId);
    }
    
    /**
     * Make HTTP request to PayMongo API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Basic ' . base64_encode($this->apiKey . ':')
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $decodedResponse = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMessage = 'Unknown error occurred';
            
            if (isset($decodedResponse['errors']) && is_array($decodedResponse['errors'])) {
                $errors = [];
                foreach ($decodedResponse['errors'] as $error) {
                    if (isset($error['detail'])) {
                        $errors[] = $error['detail'];
                    } elseif (isset($error['message'])) {
                        $errors[] = $error['message'];
                    }
                }
                $errorMessage = implode('; ', $errors);
            } elseif (isset($decodedResponse['message'])) {
                $errorMessage = $decodedResponse['message'];
            }
            
            // Log the full response for debugging
            error_log('PayMongo API Error Response: ' . $response);
            
            throw new Exception('PayMongo API Error: ' . $errorMessage);
        }
        
        return $decodedResponse;
    }
    
    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature($payload, $signature, $secret) {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Get base URL for redirects
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        
        // Method 1: Try to get base path from REQUEST_URI
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $requestUri = explode('?', $requestUri)[0]; // Remove query string
        
        // Remove controller paths
        $basePath = str_replace('/controller/booking', '', $requestUri);
        $basePath = str_replace('/controller', '', $basePath);
        
        // Extract base path if it contains /bmove-v2
        if (strpos($basePath, '/bmove-v2') === 0) {
            $basePath = '/bmove-v2';
        } elseif (strpos($basePath, '/bmove-v2') !== false) {
            // If /bmove-v2 is somewhere in the path, extract it
            $parts = explode('/bmove-v2', $basePath);
            $basePath = '/bmove-v2';
        } else {
            // Method 2: Fallback - try to detect from SCRIPT_NAME
            $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
            if (strpos($scriptName, '/bmove-v2') !== false) {
                $basePath = '/bmove-v2';
            } else {
                $basePath = '';
            }
        }
        
        return $protocol . '://' . $host . $basePath;
    }
}
