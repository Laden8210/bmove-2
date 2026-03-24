-- Make contact_number nullable for social login users who don't have a phone number yet
ALTER TABLE users MODIFY COLUMN contact_number VARCHAR(11) NULL DEFAULT NULL;

-- Add social login provider columns
-- Using a procedure to check if columns exist first (MySQL 8.0 doesn't support IF NOT EXISTS for ADD COLUMN)
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'social_provider');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN social_provider VARCHAR(20) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'social_provider_id');
SET @sql = IF(@col_exists = 0, 'ALTER TABLE users ADD COLUMN social_provider_id VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
