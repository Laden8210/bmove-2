-- Migration: Enhance bookings table for better location tracking
-- Date: 2025-01-21
-- Description: Add fields to support enhanced map pinning and location accuracy

-- Add location accuracy fields
ALTER TABLE bookings 
ADD COLUMN pickup_accuracy DECIMAL(10,6) NULL AFTER pickup_lng,
ADD COLUMN dropoff_accuracy DECIMAL(10,6) NULL AFTER dropoff_lng;

-- Add map interaction tracking
ALTER TABLE bookings 
ADD COLUMN map_interaction_type ENUM('click', 'search', 'drag', 'geolocation') NULL AFTER dropoff_accuracy,
ADD COLUMN map_zoom_level INT NULL AFTER map_interaction_type;

-- Add booking source tracking
ALTER TABLE bookings 
ADD COLUMN booking_source ENUM('web', 'mobile', 'api') DEFAULT 'web' AFTER map_zoom_level;

-- Create indexes for better performance
CREATE INDEX idx_bookings_location ON bookings(pickup_lat, pickup_lng, dropoff_lat, dropoff_lng);
CREATE INDEX idx_bookings_source ON bookings(booking_source);
CREATE INDEX idx_bookings_interaction ON bookings(map_interaction_type);
