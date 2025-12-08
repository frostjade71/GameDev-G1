-- Add last_login column to users table
-- Run this SQL in phpMyAdmin to add the last_login tracking column

ALTER TABLE `users` 
ADD COLUMN `last_login` DATETIME NULL DEFAULT NULL AFTER `updated_at`;

-- Optional: Add an index for better query performance
ALTER TABLE `users` 
ADD INDEX `idx_last_login` (`last_login`);
