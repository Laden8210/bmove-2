-- GPS Location Tracking System
-- Migration: 008_add_gps_tracking.sql

-- Create driver_locations table for real-time GPS tracking
CREATE TABLE IF NOT EXISTS driver_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(36) NOT NULL,
    booking_id VARCHAR(36) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(8, 2) DEFAULT NULL,
    speed DECIMAL(8, 2) DEFAULT NULL,
    heading DECIMAL(8, 2) DEFAULT NULL,
    altitude DECIMAL(8, 2) DEFAULT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_driver_booking (driver_id, booking_id),
    INDEX idx_timestamp (timestamp),
    FOREIGN KEY (driver_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Create driver_tracking_sessions table for active tracking sessions
CREATE TABLE IF NOT EXISTS driver_tracking_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(36) NOT NULL,
    booking_id VARCHAR(36) NOT NULL,
    session_status ENUM('active', 'paused', 'stopped') DEFAULT 'active',
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    stopped_at TIMESTAMP NULL,
    last_location_update TIMESTAMP NULL,
    total_distance DECIMAL(10, 2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_active_session (driver_id, booking_id, session_status),
    FOREIGN KEY (driver_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Add location tracking fields to bookings table
ALTER TABLE bookings 
ADD COLUMN driver_location_enabled BOOLEAN DEFAULT FALSE,
ADD COLUMN tracking_session_id INT NULL,
ADD COLUMN last_driver_lat DECIMAL(10, 8) NULL,
ADD COLUMN last_driver_lng DECIMAL(11, 8) NULL,
ADD COLUMN last_location_update TIMESTAMP NULL;

-- Add indexes and foreign key constraints
ALTER TABLE bookings 
ADD INDEX idx_tracking_enabled (driver_location_enabled);

-- Add foreign key constraint (will be added after driver_tracking_sessions table is created)
-- ALTER TABLE bookings ADD FOREIGN KEY (tracking_session_id) REFERENCES driver_tracking_sessions(id) ON DELETE SET NULL;

-- Create location_history table for storing location history
CREATE TABLE IF NOT EXISTS location_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_id VARCHAR(36) NOT NULL,
    booking_id VARCHAR(36) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    accuracy DECIMAL(8, 2) DEFAULT NULL,
    speed DECIMAL(8, 2) DEFAULT NULL,
    heading DECIMAL(8, 2) DEFAULT NULL,
    altitude DECIMAL(8, 2) DEFAULT NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_driver_booking_history (driver_id, booking_id),
    INDEX idx_recorded_at (recorded_at),
    FOREIGN KEY (driver_id) REFERENCES users(uid) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE
);

-- Insert system settings for GPS tracking
INSERT IGNORE INTO system_settings (setting_key, setting_value, description) VALUES
('gps_tracking_enabled', 'true', 'Enable GPS location tracking for drivers'),
('location_update_interval', '10', 'Location update interval in seconds'),
('location_history_retention_days', '30', 'Number of days to keep location history'),
('max_location_accuracy', '100', 'Maximum location accuracy in meters'),
('gps_tracking_timeout', '300', 'GPS tracking timeout in seconds');

-- Add foreign key constraint for tracking_session_id
ALTER TABLE bookings ADD FOREIGN KEY (tracking_session_id) REFERENCES driver_tracking_sessions(id) ON DELETE SET NULL;
