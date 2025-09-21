-- Update PayMongo API Keys
-- Migration: 007_update_paymongo_keys.sql

-- Update existing PayMongo API key with new secret key
UPDATE system_settings 
SET setting_value = 'sk_test_dqX2uveywfbdi6Tc3evEgyFy' 
WHERE setting_key = 'paymongo_api_key';

-- Update webhook secret
UPDATE system_settings 
SET setting_value = 'whsec_your_webhook_secret_here' 
WHERE setting_key = 'paymongo_webhook_secret';

-- Add public key if it doesn't exist
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) 
VALUES ('paymongo_public_key', 'pk_test_rzhhsUGNipes7mZhJ4NSuiHPM', 'PayMongo API Public Key');

-- Update description to reflect new keys
UPDATE system_settings 
SET description = 'PayMongo API Secret Key (Updated)' 
WHERE setting_key = 'paymongo_api_key';

UPDATE system_settings 
SET description = 'PayMongo Webhook Secret Key (Updated)' 
WHERE setting_key = 'paymongo_webhook_secret';
