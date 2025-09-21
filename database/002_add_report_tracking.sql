-- Migration: Add report generation tracking
-- Date: 2025-01-21
-- Description: Add table to track generated reports for audit purposes

CREATE TABLE IF NOT EXISTS report_generations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    generated_by CHAR(36) NOT NULL,
    file_path VARCHAR(500) NULL,
    file_size INT NULL,
    generation_time DECIMAL(10,3) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_report_type (report_type),
    INDEX idx_generated_by (generated_by),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (generated_by) REFERENCES users(uid) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
