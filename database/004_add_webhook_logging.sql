-- Migration: Add webhook event logging
-- Date: 2025-01-21
-- Description: Create table to log webhook events for debugging and audit purposes

CREATE TABLE IF NOT EXISTS webhook_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(100) NOT NULL,
    event_id VARCHAR(255) NOT NULL,
    payment_id CHAR(36) NULL,
    booking_id CHAR(36) NULL,
    payload JSON NOT NULL,
    signature VARCHAR(255) NULL,
    signature_valid BOOLEAN DEFAULT FALSE,
    processed BOOLEAN DEFAULT FALSE,
    processing_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_event_type (event_type),
    INDEX idx_event_id (event_id),
    INDEX idx_payment_id (payment_id),
    INDEX idx_booking_id (booking_id),
    INDEX idx_processed (processed),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
