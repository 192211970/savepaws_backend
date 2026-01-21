-- Add user_id column to centers table to link with organization user
-- Run this in phpMyAdmin or MySQL CLI

ALTER TABLE `centers` 
ADD COLUMN `user_id` INT(11) NULL AFTER `center_id`,
ADD CONSTRAINT `fk_center_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;
