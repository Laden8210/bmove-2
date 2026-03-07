<?php

class NotificationService
{
    private $smsApiKey;
    private $smsDeviceId;
    private $emailHost;
    private $emailPort;
    private $emailUsername;
    private $emailPassword;
    private $emailFrom;

    public function __construct()
    {
        // Load credentials from environment
        $this->smsApiKey = getenv('TEXTBEE_API_KEY');
        $this->smsDeviceId = getenv('TEXTBEE_DEVICE_ID');
        $this->emailHost = getenv('SMTP_HOST');
        $this->emailPort = getenv('SMTP_PORT') ?: 587;
        $this->emailUsername = getenv('SMTP_USERNAME');
        $this->emailPassword = getenv('SMTP_PASSWORD');
        $this->emailFrom = getenv('SMTP_FROM');
    }

    /**
     * Send SMS notification using TextBee API
     */
    public function sendSMS($phoneNumber, $message)
    {
        try {
            if (!$this->smsApiKey || !$this->smsDeviceId) {
                throw new Exception('SMS credentials not configured');
            }

            
            if (!$phoneNumber) {
                throw new Exception('Invalid phone number format');
            }

            $url = "https://api.textbee.dev/api/v1/gateway/devices/{$this->smsDeviceId}/send-sms";
            $data = [
                'recipients' => [$phoneNumber],
                'message' => $message
            ];

            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'x-api-key: ' . $this->smsApiKey
                ],
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => true
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                throw new Exception("CURL Error: " . $error);
            }

            if ($httpCode !== 200 && $httpCode !== 201) {
                throw new Exception("SMS API returned HTTP " . $httpCode . ": " . $response);
            }

            error_log("SMS sent successfully to {$phoneNumber}: " . substr($message, 0, 50) . "...");

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => json_decode($response, true)
            ];

        } catch (Exception $e) {
            error_log("SMS sending failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Send email notification using SMTP with proper authentication
     */
    public function sendEmail($toEmail, $subject, $message, $customerName = '')
    {
        try {
            if (!$this->emailHost || !$this->emailUsername || !$this->emailFrom) {
                throw new Exception('Email credentials not configured');
            }

            if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address: ' . $toEmail);
            }

            // Connect to SMTP server
            $socket = fsockopen($this->emailHost, $this->emailPort, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
            }

            // Read server greeting
            $this->readSMTPResponse($socket);

            // Send EHLO
            fwrite($socket, "EHLO localhost\r\n");
            $this->readSMTPResponse($socket);

            // Start TLS if port 587
            if ($this->emailPort == 587) {
                fwrite($socket, "STARTTLS\r\n");
                $this->readSMTPResponse($socket);
                
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                
                // Send EHLO again after TLS
                fwrite($socket, "EHLO localhost\r\n");
                $this->readSMTPResponse($socket);
            }

            // Authenticate
            fwrite($socket, "AUTH LOGIN\r\n");
            $this->readSMTPResponse($socket);

            fwrite($socket, base64_encode($this->emailUsername) . "\r\n");
            $this->readSMTPResponse($socket);

            fwrite($socket, base64_encode($this->emailPassword) . "\r\n");
            $authResponse = $this->readSMTPResponse($socket);
            
            if (strpos($authResponse, '235') === false) {
                throw new Exception('SMTP authentication failed');
            }

            // Send email
            fwrite($socket, "MAIL FROM: <{$this->emailFrom}>\r\n");
            $this->readSMTPResponse($socket);

            fwrite($socket, "RCPT TO: <{$toEmail}>\r\n");
            $this->readSMTPResponse($socket);

            fwrite($socket, "DATA\r\n");
            $this->readSMTPResponse($socket);

            // Email headers and body
            $emailData = "From: {$this->emailFrom}\r\n";
            $emailData .= "To: {$toEmail}\r\n";
            $emailData .= "Subject: {$subject}\r\n";
            $emailData .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $emailData .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            if ($customerName) {
                $emailData .= "X-Customer: {$customerName}\r\n";
            }
            $emailData .= "\r\n";
            $emailData .= $message . "\r\n";
            $emailData .= ".\r\n";

            fwrite($socket, $emailData);
            $this->readSMTPResponse($socket);

            // Quit
            fwrite($socket, "QUIT\r\n");
            fclose($socket);

            error_log("Email sent successfully to {$toEmail}: {$subject}");

            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'data' => ['to' => $toEmail, 'subject' => $subject]
            ];

        } catch (Exception $e) {
            error_log("Email sending failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Read SMTP server response
     */
    private function readSMTPResponse($socket)
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Send notification for order status update
     */
    public function sendOrderStatusNotification($orderId, $newStatus, $customerPhone = '', $customerEmail = '', $customerName = '')
    {
        $results = [
            'sms' => null,
            'email' => null,
            'overall_success' => false
        ];

        $statusMessages = $this->getOrderStatusMessages($newStatus);
        $orderNumber = "ORDER-" . str_pad($orderId, 6, '0', STR_PAD_LEFT);

        if ($customerPhone) {
            $smsMessage = "Hi {$customerName}, your order {$orderNumber} status has been updated to: {$statusMessages['sms']}";
            $results['sms'] = $this->sendSMS($customerPhone, $smsMessage);
        }

        if ($customerEmail) {
            $emailSubject = "Order Status Update - {$orderNumber}";
            $emailMessage = "Dear {$customerName},\n\n";
            $emailMessage .= "Your order {$orderNumber} status has been updated to: {$statusMessages['email']}\n\n";
            $emailMessage .= "You can track your order status anytime.\n\n";
            $emailMessage .= "Thank you for choosing us!\n\n";
            $emailMessage .= "Best regards,\nYour Support Team";

            $results['email'] = $this->sendEmail($customerEmail, $emailSubject, $emailMessage, $customerName);
        }

        $results['overall_success'] = ($customerPhone ? $results['sms']['success'] : true) && 
                                     ($customerEmail ? $results['email']['success'] : true);

        return $results;
    }

    /**
     * Get appropriate messages for different order statuses
     */
    private function getOrderStatusMessages($status)
    {
        $messages = [
            'pending' => [
                'sms' => 'Pending Payment',
                'email' => 'Pending Payment - Please complete your payment to process your order.'
            ],
            'confirmed' => [
                'sms' => 'Confirmed',
                'email' => 'Confirmed - Your order has been confirmed and is being prepared.'
            ],
            'processing' => [
                'sms' => 'Processing',
                'email' => 'Processing - Your order is being prepared for shipment.'
            ],
            'shipped' => [
                'sms' => 'Shipped',
                'email' => 'Shipped - Your order has been shipped and is on its way.'
            ],
            'delivered' => [
                'sms' => 'Delivered',
                'email' => 'Delivered - Your order has been delivered successfully.'
            ],
            'cancelled' => [
                'sms' => 'Cancelled',
                'email' => 'Cancelled - Your order has been cancelled.'
            ],
            'refunded' => [
                'sms' => 'Refunded',
                'email' => 'Refunded - A refund has been processed for your order.'
            ]
        ];

        return $messages[$status] ?? [
            'sms' => ucfirst($status),
            'email' => ucfirst($status)
        ];
    }

    /**
     * Format phone number to international format
     */
    private function formatPhoneNumber($phoneNumber)
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        if (substr($cleaned, 0, 1) === '+') {
            return $cleaned;
        }
        
        if (substr($cleaned, 0, 1) === '0') {
            $cleaned = '+1' . substr($cleaned, 1);
        }
        
        if (substr($cleaned, 0, 1) !== '+') {
            $cleaned = '+1' . $cleaned;
        }
        
        if (preg_match('/^\+1[0-9]{10}$/', $cleaned)) {
            return $cleaned;
        }
        
        return false;
    }

    /**
     * Test SMS and Email connectivity
     */
    public function testConnectivity()
    {
        $results = [
            'sms_configured' => !!(getenv('TEXTBEE_API_KEY') && getenv('TEXTBEE_DEVICE_ID')),
            'email_configured' => !!(getenv('SMTP_HOST') && getenv('SMTP_USERNAME')),
            'sms_test' => null,
            'email_test' => null
        ];

        if ($results['sms_configured']) {
            $results['sms_test'] = $this->sendSMS('+1234567890', 'Test message from dropshipping system');
        }

        if ($results['email_configured']) {
            $results['email_test'] = $this->sendEmail(
                getenv('TEST_EMAIL') ?: 'test@example.com',
                'Test Email - Dropshipping System',
                'This is a test email to verify SMTP connectivity.',
                'Test User'
            );
        }

        return $results;
    }
}
?>