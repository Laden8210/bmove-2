-- Migration: Add email verification columns to users table
-- Run this migration to enable email link verification for registration

ALTER TABLE users 
ADD COLUMN email_verified TINYINT(1) NOT NULL DEFAULT 0,
ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL;

-- Mark all existing users as verified so they can still log in
UPDATE users SET email_verified = 1;
