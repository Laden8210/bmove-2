-- Migration: Add PayMongo support to payments table
-- Date: 2025-01-21
-- Description: Add fields to support PayMongo payment gateway integration

-- Add new payment method options
ALTER TABLE payments 
MODIFY COLUMN payment_method ENUM('cash', 'gcash', 'maya', 'bank_transfer', 'paymongo') NOT NULL;

-- Add webhook tracking fields
ALTER TABLE payments 
ADD COLUMN webhook_received_at TIMESTAMP NULL AFTER paid_at,
ADD COLUMN webhook_data JSON NULL AFTER webhook_received_at;

-- Add payment gateway session tracking
ALTER TABLE payments 
ADD COLUMN session_id VARCHAR(255) NULL AFTER gateway_reference,
ADD COLUMN session_expires_at TIMESTAMP NULL AFTER session_id;

-- Create index for better performance
CREATE INDEX idx_payments_gateway_ref ON payments(gateway_reference);
CREATE INDEX idx_payments_session_id ON payments(session_id);
CREATE INDEX idx_payments_webhook_received ON payments(webhook_received_at);

-- Update existing records to have proper payment method
UPDATE payments SET payment_method = 'cash' WHERE payment_method = 'gcash' OR payment_method = 'maya';
