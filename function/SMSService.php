<?php

class SMSService
{
    private $apiKey;
    private $deviceId;
    private $baseUrl = 'https://api.textbee.dev/api/v1/gateway/devices';

    public function __construct()
    {
        $this->apiKey = getenv('TEXTBEE_API_KEY');
        $this->deviceId = getenv('TEXTBEE_DEVICE_ID');

        if (!$this->apiKey || !$this->deviceId) {
            throw new Exception('TextBee API credentials not configured in .env');
        }
    }

    /**
     * Send an SMS message via TextBee gateway.
     *
     * @param string $phoneNumber Recipient phone number (e.g. +639171234567)
     * @param string $message The message body
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function sendSMS($phoneNumber, $message)
    {
        $url = $this->baseUrl . '/' . $this->deviceId . '/sendSMS';

        $payload = json_encode([
            'recipients' => [$phoneNumber],
            'message' => $message
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'error' => 'cURL error: ' . $curlError];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'error' => null];
        }

        $decoded = json_decode($response, true);
        $errorMsg = $decoded['message'] ?? ('HTTP ' . $httpCode . ': SMS send failed');
        return ['success' => false, 'error' => $errorMsg];
    }

    /**
     * Send an OTP code via SMS.
     *
     * @param string $phoneNumber Recipient phone number
     * @param string $otp The OTP code
     * @return array ['success' => bool, 'error' => string|null]
     */
    public function sendOTP($phoneNumber, $otp)
    {
        $message = "Your BMoveXpress verification code is: {$otp}. This code expires in 5 minutes. Do not share it with anyone.";
        return $this->sendSMS($phoneNumber, $message);
    }
}
