-- Migration: Add profile_image column to users table
-- This allows users to upload custom profile avatars
-- Date: 2026-01-21

-- Add profile_image column to users table
ALTER TABLE users 
ADD COLUMN profile_image VARCHAR(255) NULL DEFAULT NULL 
COMMENT 'Path to user profile image, relative to project root';

-- Add index for faster lookups (optional but recommended)
CREATE INDEX idx_profile_image ON users(profile_image);
