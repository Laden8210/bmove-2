<?php

require_once '../config/config.php';
require_once '../function/PayMongoService.php';

// Set headers for webhook
header('Content-Type: application/json; charset=utf-8');

// Get the raw POST data
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';

// Get webhook secret from system settings
$webhookSecret = '';
$result = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'paymongo_webhook_secret'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $webhookSecret = $row['setting_value'];
}

try {
    // Verify webhook signature
    $paymongo = new PayMongoService();
    if (!$paymongo->verifyWebhookSignature($payload, $signature, $webhookSecret)) {
        throw new Exception('Invalid webhook signature');
    }
    
    $data = json_decode($payload, true);
    
    if (!$data || !isset($data['data']['type'])) {
        throw new Exception('Invalid webhook payload');
    }
    
    $eventType = $data['data']['type'];
    $eventData = $data['data']['attributes'];
    
    // Log webhook event
    error_log("PayMongo Webhook: " . $eventType . " - " . json_encode($eventData));
    
    switch ($eventType) {
        case 'checkout_session.completed':
            handleCheckoutSessionCompleted($eventData);
            break;
            
        case 'payment_intent.succeeded':
            handlePaymentIntentSucceeded($eventData);
            break;
            
        case 'payment_intent.payment_failed':
            handlePaymentIntentFailed($eventData);
            break;
            
        default:
            error_log("Unhandled webhook event type: " . $eventType);
            break;
    }
    
    // Respond with success
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Webhook processed successfully']);
    
} catch (Exception $e) {
    error_log("PayMongo Webhook Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

/**
 * Handle successful checkout session completion
 */
function handleCheckoutSessionCompleted($eventData) {
    global $conn;
    
    $checkoutSessionId = $eventData['id'];
    $paymentIntentId = $eventData['payment_intent_id'] ?? null;
    
    // Find payment record by checkout session ID
    $stmt = $conn->prepare("
        SELECT p.*, b.user_id 
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.gateway_reference = ?
    ");
    $stmt->bind_param("s", $checkoutSessionId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        // Update payment status to paid
        $updateStmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'paid', 
                amount_received = amount_due,
                paid_at = NOW(),
                updated_by = ?
            WHERE payment_id = ?
        ");
        $updateStmt->bind_param("ss", $payment['user_id'], $payment['payment_id']);
        $updateStmt->execute();
        
        // Update booking status to confirmed
        $bookingStmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed' 
            WHERE booking_id = ?
        ");
        $bookingStmt->bind_param("s", $payment['booking_id']);
        $bookingStmt->execute();
        
        error_log("Payment completed for booking: " . $payment['booking_id']);
    }
}

/**
 * Handle successful payment intent
 */
function handlePaymentIntentSucceeded($eventData) {
    global $conn;
    
    $paymentIntentId = $eventData['id'];
    $amount = $eventData['amount'] / 100; // Convert from centavos
    
    // Find payment record by payment intent ID
    $stmt = $conn->prepare("
        SELECT p.*, b.user_id 
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.gateway_reference = ?
    ");
    $stmt->bind_param("s", $paymentIntentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        // Update payment status to paid
        $updateStmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'paid', 
                amount_received = ?,
                paid_at = NOW(),
                updated_by = ?
            WHERE payment_id = ?
        ");
        $updateStmt->bind_param("dss", $amount, $payment['user_id'], $payment['payment_id']);
        $updateStmt->execute();
        
        // Update booking status to confirmed
        $bookingStmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'confirmed' 
            WHERE booking_id = ?
        ");
        $bookingStmt->bind_param("s", $payment['booking_id']);
        $bookingStmt->execute();
        
        error_log("Payment intent succeeded for booking: " . $payment['booking_id']);
    }
}

/**
 * Handle failed payment intent
 */
function handlePaymentIntentFailed($eventData) {
    global $conn;
    
    $paymentIntentId = $eventData['id'];
    
    // Find payment record by payment intent ID
    $stmt = $conn->prepare("
        SELECT p.*, b.user_id 
        FROM payments p
        JOIN bookings b ON p.booking_id = b.booking_id
        WHERE p.gateway_reference = ?
    ");
    $stmt->bind_param("s", $paymentIntentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if ($payment) {
        // Update payment status to failed
        $updateStmt = $conn->prepare("
            UPDATE payments 
            SET payment_status = 'failed',
                updated_by = ?
            WHERE payment_id = ?
        ");
        $updateStmt->bind_param("ss", $payment['user_id'], $payment['payment_id']);
        $updateStmt->execute();
        
        // Update booking status to cancelled
        $bookingStmt = $conn->prepare("
            UPDATE bookings 
            SET status = 'cancelled' 
            WHERE booking_id = ?
        ");
        $bookingStmt->bind_param("s", $payment['booking_id']);
        $bookingStmt->execute();
        
        error_log("Payment intent failed for booking: " . $payment['booking_id']);
    }
}
