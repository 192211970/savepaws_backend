-- Add OTP columns to users table for password reset
-- Run this in phpMyAdmin or MySQL CLI

ALTER TABLE `users`
ADD COLUMN `otp` VARCHAR(6) NULL DEFAULT NULL AFTER `user_type`,
ADD COLUMN `otp_expiry` DATETIME NULL DEFAULT NULL AFTER `otp`;
