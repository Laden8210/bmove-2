-- Migration: Alter payment method column to string
-- Date: 2025-01-21
-- Description: Alter payment method column to string


ALTER TABLE payments
MODIFY COLUMN payment_method VARCHAR(50) NOT NULL;