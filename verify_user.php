<?php
require __DIR__ . '/config/config.php';
require __DIR__ . '/function/SMSService.php';

echo "TEXTBEE_API_KEY: " . (getenv('TEXTBEE_API_KEY') ? 'SET (' . substr(getenv('TEXTBEE_API_KEY'), 0, 8) . '...)' : 'NOT SET') . PHP_EOL;
echo "TEXTBEE_DEVICE_ID: " . (getenv('TEXTBEE_DEVICE_ID') ? 'SET (' . getenv('TEXTBEE_DEVICE_ID') . ')' : 'NOT SET') . PHP_EOL;

try {
    $sms = new SMSService();
    echo "SMSService created OK" . PHP_EOL;

    // Test with a dummy number (won't actually send, just tests API connectivity)
    $result = $sms->sendSMS('+639000000000', 'Test message from BMoveXpress');
    echo "Result: " . json_encode($result) . PHP_EOL;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}