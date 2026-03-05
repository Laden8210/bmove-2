-- Run this script in phpMyAdmin or via MySQL command line against your database
-- It adds an account_status column to replace the hard deletion feature

ALTER TABLE users 
ADD COLUMN account_status VARCHAR(20) NOT NULL DEFAULT 'Active';

-- Optional: If you want to convert previously 'deleted' users to 'Archived'
-- UPDATE users SET account_status = 'Archived' WHERE is_deleted = 1;
