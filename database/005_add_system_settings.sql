-- Migration: Add system settings table
-- Date: 2025-01-21
-- Description: Create table to store system configuration settings

CREATE TABLE IF NOT EXISTS system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    setting_type ENUM('string', 'number', 'boolean', 'json') DEFAULT 'string',
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_setting_key (setting_key),
    INDEX idx_is_public (is_public)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Insert default settings
INSERT INTO system_settings (setting_key, setting_value, setting_type, description, is_public) VALUES
('paymongo_api_key', 'c2tfdGVzdF80OE1nWVk3U0dLdDY5dkVQZnRnZGpmS086', 'string', 'PayMongo API Key', FALSE),
('paymongo_webhook_secret', 'whsec_your_webhook_secret_here', 'string', 'PayMongo Webhook Secret', FALSE),
('default_map_center_lat', '14.5995', 'number', 'Default map center latitude', TRUE),
('default_map_center_lng', '120.9842', 'number', 'Default map center longitude', TRUE),
('default_map_zoom', '13', 'number', 'Default map zoom level', TRUE),
('opencage_api_key', '5246506e7d3141cbaaab53d198f6de47', 'string', 'OpenCage Geocoding API Key', FALSE),
('pdf_storage_path', 'uploads/reports/', 'string', 'PDF storage directory path', FALSE),
('max_file_upload_size', '10485760', 'number', 'Maximum file upload size in bytes (10MB)', TRUE),
('booking_timeout_minutes', '30', 'number', 'Booking session timeout in minutes', TRUE)
ON DUPLICATE KEY UPDATE 
    setting_value = VALUES(setting_value),
    updated_at = CURRENT_TIMESTAMP;
