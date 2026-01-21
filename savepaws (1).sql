-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 21, 2026 at 06:59 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `savepaws`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_escalate_delayed_cases` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_case_id, v_user_id, v_center_id INT;
    DECLARE v_lat, v_lng DECIMAL(10,7);
    
    -- Cursor for delayed cases (Reported status, > 60 min old, no Sent_again)
    DECLARE cur CURSOR FOR
        SELECT c.case_id, c.user_id, c.latitude, c.longitude
        FROM cases c
        WHERE c.status = 'Reported'
          AND TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) >= 60
          AND NOT EXISTS (
              SELECT 1 FROM case_escalations ce 
              WHERE ce.case_id = c.case_id 
              AND ce.remark = 'Sent_again'
              AND ce.status = 'Pending'
          );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_case_id, v_user_id, v_lat, v_lng;
        IF done THEN LEAVE read_loop; END IF;
        
        -- Find nearest active center
        SET v_center_id = NULL;
        SELECT center_id INTO v_center_id
        FROM centers
        WHERE is_active = 1 OR is_active = 'Yes'
        ORDER BY (6371 * ACOS(
            COS(RADIANS(v_lat)) * COS(RADIANS(latitude)) *
            COS(RADIANS(longitude) - RADIANS(v_lng)) +
            SIN(RADIANS(v_lat)) * SIN(RADIANS(latitude))
        )) ASC
        LIMIT 1;
        
        IF v_center_id IS NOT NULL THEN
            -- Mark old escalations as Resent
            UPDATE case_escalations
            SET remark = 'Resent'
            WHERE case_id = v_case_id 
            AND remark IN ('Delayed', 'Sent_again', 'None');
            
            -- Insert new escalation
            INSERT INTO case_escalations 
                (user_id, case_id, center_id, status, response, rejected_reason, remark, case_type, assigned_time)
            VALUES 
                (v_user_id, v_case_id, v_center_id, 'Pending', NULL, NULL, 'Sent_again', 'Critical', NOW());
        END IF;
    END LOOP;
    
    CLOSE cur;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_escalate_rejected_cases` ()   BEGIN
    DECLARE done INT DEFAULT FALSE;
    DECLARE v_case_id, v_user_id, v_center_id INT;
    DECLARE v_lat, v_lng DECIMAL(10,7);
    
    -- Cursor for rejected-by-all cases that haven't been sent again
    DECLARE cur CURSOR FOR
        SELECT DISTINCT ce.case_id
        FROM case_escalations ce
        WHERE ce.remark = 'Rejected_by_all'
          AND NOT EXISTS (
              SELECT 1 FROM case_escalations ce2 
              WHERE ce2.case_id = ce.case_id 
              AND ce2.remark = 'Sent_again'
              AND ce2.status = 'Pending'
          );
    
    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
    
    OPEN cur;
    
    read_loop: LOOP
        FETCH cur INTO v_case_id;
        IF done THEN LEAVE read_loop; END IF;
        
        -- Get case info
        SELECT user_id, latitude, longitude INTO v_user_id, v_lat, v_lng
        FROM cases WHERE case_id = v_case_id;
        
        -- Find best-performing center within 25km
        SET v_center_id = NULL;
        SELECT center_id INTO v_center_id
        FROM centers
        WHERE (is_active = 1 OR is_active = 'Yes')
          AND (6371 * ACOS(
              COS(RADIANS(v_lat)) * COS(RADIANS(latitude)) *
              COS(RADIANS(longitude) - RADIANS(v_lng)) +
              SIN(RADIANS(v_lat)) * SIN(RADIANS(latitude))
          )) <= 25
        ORDER BY total_cases_handled DESC
        LIMIT 1;
        
        IF v_center_id IS NOT NULL THEN
            -- Mark old as Resent
            UPDATE case_escalations
            SET remark = 'Resent'
            WHERE case_id = v_case_id AND remark = 'Rejected_by_all';
            
            -- Insert new escalation
            INSERT INTO case_escalations 
                (user_id, case_id, center_id, status, response, rejected_reason, remark, case_type, assigned_time)
            VALUES 
                (v_user_id, v_case_id, v_center_id, 'Pending', NULL, NULL, 'Sent_again', 'Critical', NOW());
        END IF;
    END LOOP;
    
    CLOSE cur;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cases`
--

CREATE TABLE `cases` (
  `case_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `photo` varchar(255) NOT NULL,
  `type_of_animal` varchar(100) NOT NULL,
  `animal_condition` varchar(255) NOT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `status` enum('Reported','Accepted','Closed') DEFAULT 'Reported',
  `created_time` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cases`
--

INSERT INTO `cases` (`case_id`, `user_id`, `photo`, `type_of_animal`, `animal_condition`, `latitude`, `longitude`, `status`, `created_time`) VALUES
(1, 2, '1765167207_sdog.jpg', 'Dog', 'Injured', 13.0123000, 80.2707000, 'Accepted', '2025-12-08 04:13:27'),
(2, 3, '1765178993_cow.avif', 'Cow', 'Injured', 13.0123000, 80.2707000, 'Accepted', '2025-12-08 06:29:53'),
(3, 3, '1765179040_cat.jpg', 'Cat', 'Injured', 12.0123000, 85.2707000, 'Accepted', '2025-12-08 07:30:40'),
(4, 3, '1765181683_dog.jpg', 'Dog', 'Injured', 13.0123000, 80.2707000, 'Accepted', '2025-12-08 08:14:43'),
(5, 4, '1765182271_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.2890000, 'Accepted', '2025-12-08 08:24:31'),
(6, 2, '1765263251_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.2890000, 'Accepted', '2025-12-09 05:54:11'),
(7, 3, '1765263420_cat.jpg', 'Cat', 'Injured', 12.9245000, 80.1278000, 'Accepted', '2025-12-09 05:57:00'),
(8, 3, '1765263465_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.2890000, 'Accepted', '2025-12-09 06:57:45'),
(9, 2, '1765427015_cat.jpg', 'Cat', 'Injured', 11.0112000, 80.2890000, 'Accepted', '2025-12-11 04:23:35'),
(10, 2, '1765440699_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-11 07:11:39'),
(11, 3, '1765444410_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-11 09:13:30'),
(12, 2, '1765770416_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-15 02:46:56'),
(13, 2, '1765774991_cow.avif', 'Cow', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-15 05:03:11'),
(14, 2, '1766145802_cow.avif', 'Cow', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-19 12:03:22'),
(15, 2, '1766146044_cow.avif', 'Cow', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-19 12:07:24'),
(16, 2, '1766146253_cow.avif', 'Cow', 'Injured', 13.0112000, 80.1278000, 'Closed', '2025-12-19 12:10:53'),
(17, 2, '1766380649_cow.avif', 'Cow', 'Injured', 13.0112000, 80.1278000, 'Closed', '2025-12-22 05:17:29'),
(18, 3, '1766391333_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Reported', '2025-12-22 08:15:33'),
(19, 3, '1766391370_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Reported', '2025-12-22 08:16:10'),
(20, 3, '1766391422_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Reported', '2025-12-22 08:17:02'),
(21, 1, '1766753097_case_1766753082008.jpg', 'Dog', 'Injured', 13.0283887, 80.0346727, 'Reported', '2025-12-26 12:44:57'),
(22, 1, '1766760293_case_1766760274666.jpg', 'Dog', 'Normal', 13.0282970, 80.0345522, 'Reported', '2025-12-26 14:44:53'),
(23, 1, '1766806678_case_1766806650607.jpg', 'Cat', 'Injured', 13.0282859, 80.0157369, 'Reported', '2025-12-27 03:37:58'),
(24, 1, '1766810010_case_1766809972885.jpg', 'Cow', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:33:30'),
(25, 1, '1766810014_case_1766809972885.jpg', 'Cow', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:33:34'),
(26, 1, '1766810186_case_1766810134350.jpg', 'Cat', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:36:26'),
(27, 1, '1766810193_case_1766810134350.jpg', 'Cat', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:36:33'),
(28, 1, '1766810194_case_1766810134350.jpg', 'Cat', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:36:34'),
(29, 1, '1766810405_case_1766810365205.jpg', 'Cat', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:40:05'),
(30, 1, '1766810409_case_1766810365205.jpg', 'Cat', 'Injured', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:40:09'),
(31, 1, '1766810843_case_1766810820628.jpg', 'Cat', 'Normal', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:47:23'),
(32, 1, '1766810957_case_1766810929096.jpg', 'Cat', 'Normal', 13.0283192, 80.0156747, 'Reported', '2025-12-27 04:49:17'),
(33, 1, '1766811464_case_1766811434598.jpg', 'Cat', 'Injured', 13.0283291, 80.0156829, 'Reported', '2025-12-27 04:57:44'),
(34, 1, '1766824719_case_1766824669798.jpg', 'Dog', 'Normal', 13.0283151, 80.0158162, 'Reported', '2025-12-27 08:38:39'),
(35, 1, '1766824728_case_1766824669798.jpg', 'Dog', 'Normal', 13.0283151, 80.0158162, 'Reported', '2025-12-27 08:38:48'),
(36, 1, '1766824863_case_1766824771222.jpg', 'Cat', 'Injured', 13.0283151, 80.0158162, 'Reported', '2025-12-27 08:41:03'),
(37, 1, '1766824880_case_1766824771222.jpg', 'Cat', 'Injured', 13.0283151, 80.0158162, 'Reported', '2025-12-27 08:41:20'),
(38, 1, '1766826259_case_1766826221196.jpg', 'Pig', 'Injured', 13.0283064, 80.0158002, 'Reported', '2025-12-27 09:04:19'),
(39, 1, '1766826924_case_1766826899065.jpg', 'Cow', 'Injured', 13.0282979, 80.0157988, 'Reported', '2025-12-27 09:15:24'),
(40, 1, '1766827062_case_1766827044306.jpg', 'Cow', 'Injured', 13.0282979, 80.0157988, 'Reported', '2025-12-27 09:17:42'),
(41, 3, '1766983972_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-29 04:52:52'),
(42, 3, '1766984365_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Closed', '2025-12-29 04:59:25'),
(43, 3, '1766989299_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-29 06:21:39'),
(44, 3, '1766990036_cat.jpg', 'Cat', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-29 06:33:56'),
(45, 4, '1766993981_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-29 07:39:41'),
(46, 1, '1766996856_case_1766996806300.jpg', 'Cat', 'Injured', 13.0283062, 80.0157943, 'Accepted', '2025-12-29 08:27:36'),
(47, 4, '1767023717_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2025-12-29 14:55:17'),
(48, 2, '1767770322_case_1767770310747.jpg', 'dog', 'Injured', 13.0282902, 80.0158351, 'Reported', '2026-01-07 07:18:42'),
(49, 2, '1767773578_case_1767773567122.jpg', 'cat', 'Normal', 13.0282897, 80.0157858, 'Reported', '2026-01-07 08:12:58'),
(50, 2, '1767773762_case_1767773750465.jpg', 'Cat', 'Injured', 13.0282897, 80.0157858, 'Reported', '2026-01-07 08:16:02'),
(51, 15, '1767804656_case_1767804630912.jpg', 'Dog', 'Injured', 13.0283497, 80.0346001, 'Closed', '2026-01-07 16:50:56'),
(52, 2, '1767929238_case_1767929223722.jpg', 'cat', 'Injured', 13.0283269, 80.0158650, 'Accepted', '2026-01-09 03:27:18'),
(53, 4, '1768884025_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Reported', '2026-01-20 04:40:25'),
(54, 4, '1768887511_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Accepted', '2026-01-20 05:38:31'),
(55, 4, '1768888756_dog.jpg', 'Dog', 'Injured', 13.0112000, 80.1278000, 'Reported', '2026-01-20 05:59:16'),
(56, 22, '1768968762_case_1768968745411.jpg', 'Dog', 'Injured', 13.0264242, 80.0172123, 'Reported', '2026-01-21 04:12:42');

-- --------------------------------------------------------

--
-- Table structure for table `case_escalations`
--

CREATE TABLE `case_escalations` (
  `escalation_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `center_id` int(11) NOT NULL,
  `status` enum('Pending','Responded','Already_responded','Closed') DEFAULT 'Pending',
  `assigned_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `responded_time` timestamp NULL DEFAULT NULL,
  `response` enum('Accept','Reject') DEFAULT NULL,
  `rejected_reason` varchar(255) DEFAULT NULL,
  `case_type` enum('Standard','Critical') DEFAULT 'Standard',
  `remark` enum('None','Delayed','Rejected_by_all','Sent_again','Resent') DEFAULT 'None'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_escalations`
--

INSERT INTO `case_escalations` (`escalation_id`, `user_id`, `case_id`, `center_id`, `status`, `assigned_time`, `responded_time`, `response`, `rejected_reason`, `case_type`, `remark`) VALUES
(1, 2, 1, 2, 'Responded', '2025-12-08 04:13:27', '2025-12-08 05:28:51', 'Reject', 'staff is insufficient', 'Standard', 'None'),
(2, 2, 1, 5, 'Responded', '2025-12-08 04:13:27', '2025-12-08 07:20:31', 'Reject', 'staff is insufficient', 'Standard', 'None'),
(3, 2, 1, 6, 'Responded', '2025-12-08 04:13:27', '2025-12-08 07:23:46', 'Accept', NULL, 'Standard', 'None'),
(4, 2, 1, 7, 'Already_responded', '2025-12-08 04:13:27', NULL, NULL, NULL, 'Standard', 'None'),
(5, 2, 1, 1, 'Responded', '2025-12-08 04:13:27', '2025-12-08 07:19:10', 'Reject', 'staff is insufficient', 'Standard', 'None'),
(6, 3, 2, 2, 'Already_responded', '2025-12-08 06:29:53', NULL, NULL, NULL, 'Standard', 'Resent'),
(7, 3, 2, 5, 'Already_responded', '2025-12-08 06:29:53', NULL, NULL, NULL, 'Standard', 'Resent'),
(8, 3, 2, 6, 'Already_responded', '2025-12-08 06:29:53', NULL, NULL, NULL, 'Standard', 'Resent'),
(9, 3, 2, 7, 'Already_responded', '2025-12-08 06:29:53', NULL, NULL, NULL, 'Standard', 'Resent'),
(10, 3, 2, 1, 'Already_responded', '2025-12-08 06:29:53', NULL, NULL, NULL, 'Standard', 'Resent'),
(11, 3, 3, 2, 'Already_responded', '2025-12-08 07:30:40', NULL, NULL, NULL, 'Critical', 'Resent'),
(12, 3, 4, 2, 'Responded', '2025-12-08 08:14:43', '2025-12-08 08:17:19', 'Accept', NULL, 'Standard', 'None'),
(13, 3, 4, 5, 'Already_responded', '2025-12-08 08:14:43', NULL, NULL, NULL, 'Standard', 'None'),
(14, 3, 4, 6, 'Already_responded', '2025-12-08 08:14:43', NULL, NULL, NULL, 'Standard', 'None'),
(15, 3, 4, 7, 'Already_responded', '2025-12-08 08:14:43', NULL, NULL, NULL, 'Standard', 'None'),
(16, 3, 4, 1, 'Already_responded', '2025-12-08 08:14:43', NULL, NULL, NULL, 'Standard', 'None'),
(17, 4, 5, 2, 'Responded', '2025-12-08 08:24:31', '2025-12-08 08:25:51', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(18, 4, 5, 5, 'Responded', '2025-12-08 08:24:31', '2025-12-08 08:25:57', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(19, 4, 5, 6, 'Responded', '2025-12-08 08:24:31', '2025-12-08 08:26:03', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(20, 4, 5, 7, 'Responded', '2025-12-08 08:24:31', '2025-12-08 08:26:09', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(21, 4, 5, 1, 'Responded', '2025-12-08 08:24:31', '2025-12-08 08:26:14', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(22, 3, 2, 2, 'Already_responded', '2025-12-09 04:26:03', NULL, NULL, NULL, 'Critical', 'Resent'),
(23, 3, 3, 2, 'Already_responded', '2025-12-09 04:26:03', NULL, NULL, NULL, 'Critical', 'Resent'),
(24, 4, 5, 2, 'Already_responded', '2025-12-09 04:26:03', NULL, NULL, NULL, 'Critical', 'Resent'),
(25, 2, 6, 2, 'Already_responded', '2025-12-09 05:54:11', NULL, NULL, NULL, 'Standard', 'Resent'),
(26, 2, 6, 5, 'Already_responded', '2025-12-09 05:54:11', NULL, NULL, NULL, 'Standard', 'Resent'),
(27, 2, 6, 6, 'Already_responded', '2025-12-09 05:54:11', NULL, NULL, NULL, 'Standard', 'Resent'),
(28, 2, 6, 7, 'Already_responded', '2025-12-09 05:54:11', NULL, NULL, NULL, 'Standard', 'Resent'),
(29, 2, 6, 1, 'Already_responded', '2025-12-09 05:54:11', NULL, NULL, NULL, 'Standard', 'Resent'),
(30, 3, 7, 3, 'Responded', '2025-12-09 05:57:00', '2025-12-09 07:06:28', 'Accept', NULL, 'Critical', 'None'),
(31, 3, 8, 2, 'Responded', '2025-12-09 06:57:45', '2025-12-09 07:09:26', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(32, 3, 8, 5, 'Responded', '2025-12-09 06:57:45', '2025-12-09 07:09:39', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(33, 3, 8, 6, 'Responded', '2025-12-09 06:57:45', '2025-12-09 07:09:44', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(34, 3, 8, 7, 'Responded', '2025-12-09 06:57:45', '2025-12-09 07:09:49', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(35, 3, 8, 1, 'Responded', '2025-12-09 06:57:45', '2025-12-09 07:09:53', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(36, 3, 2, 2, 'Already_responded', '2025-12-09 07:16:48', NULL, NULL, NULL, 'Critical', 'Resent'),
(37, 3, 3, 2, 'Already_responded', '2025-12-09 07:16:48', NULL, NULL, NULL, 'Critical', 'Resent'),
(38, 4, 5, 2, 'Already_responded', '2025-12-09 07:16:48', NULL, NULL, NULL, 'Critical', 'Resent'),
(39, 2, 6, 2, 'Already_responded', '2025-12-09 07:16:48', NULL, NULL, NULL, 'Critical', 'Resent'),
(40, 3, 2, 2, 'Already_responded', '2025-12-09 08:19:18', '2025-12-09 08:33:57', 'Accept', NULL, 'Critical', 'Sent_again'),
(41, 3, 3, 2, 'Already_responded', '2025-12-09 08:19:18', '2025-12-09 08:34:10', 'Accept', NULL, 'Critical', 'Sent_again'),
(42, 4, 5, 2, 'Already_responded', '2025-12-09 08:19:18', '2025-12-09 08:34:21', 'Accept', NULL, 'Critical', 'Sent_again'),
(43, 2, 6, 2, 'Already_responded', '2025-12-09 08:19:18', '2025-12-09 08:34:29', 'Accept', NULL, 'Critical', 'Resent'),
(44, 3, 8, 2, 'Already_responded', '2025-12-09 08:19:18', '2025-12-09 08:34:36', 'Accept', NULL, 'Critical', 'Sent_again'),
(45, 2, 9, 3, 'Responded', '2025-12-11 04:23:35', '2025-12-11 04:59:11', 'Accept', NULL, 'Critical', 'None'),
(46, 2, 10, 3, 'Already_responded', '2025-12-11 07:11:39', NULL, NULL, NULL, 'Standard', 'Resent'),
(47, 2, 10, 6, 'Already_responded', '2025-12-11 07:11:39', NULL, NULL, NULL, 'Standard', 'Resent'),
(48, 2, 10, 7, 'Already_responded', '2025-12-11 07:11:39', NULL, NULL, NULL, 'Standard', 'Resent'),
(49, 2, 10, 3, 'Responded', '2025-12-11 09:08:26', '2025-12-15 04:36:06', 'Accept', NULL, 'Critical', 'Resent'),
(50, 3, 11, 3, 'Responded', '2025-12-11 09:13:30', '2025-12-11 09:14:08', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(51, 3, 11, 6, 'Responded', '2025-12-11 09:13:30', '2025-12-11 09:14:16', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(52, 3, 11, 7, 'Responded', '2025-12-11 09:13:30', '2025-12-13 08:31:32', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(53, 3, 11, 6, 'Already_responded', '2025-12-13 09:16:16', NULL, NULL, NULL, 'Critical', 'Resent'),
(54, 2, 12, 3, 'Already_responded', '2025-12-15 02:46:56', NULL, NULL, NULL, 'Standard', 'Resent'),
(55, 2, 12, 6, 'Already_responded', '2025-12-15 02:46:56', NULL, NULL, NULL, 'Standard', 'Resent'),
(56, 2, 12, 7, 'Already_responded', '2025-12-15 02:46:56', NULL, NULL, NULL, 'Standard', 'Resent'),
(57, 2, 10, 3, 'Responded', '2025-12-15 03:53:18', '2025-12-15 04:36:06', 'Accept', NULL, 'Critical', 'Sent_again'),
(58, 3, 11, 3, 'Responded', '2025-12-15 03:53:18', '2025-12-15 04:45:55', 'Accept', NULL, 'Critical', 'Sent_again'),
(59, 2, 12, 3, 'Responded', '2025-12-15 03:53:18', '2025-12-15 04:46:42', 'Accept', NULL, 'Critical', 'Sent_again'),
(60, 2, 6, 2, 'Already_responded', '2025-12-15 04:49:37', NULL, NULL, NULL, 'Critical', 'Resent'),
(61, 2, 6, 2, 'Responded', '2025-12-15 04:55:17', '2025-12-15 04:56:40', 'Accept', NULL, 'Critical', 'Sent_again'),
(62, 2, 13, 3, 'Responded', '2025-12-15 05:03:11', '2025-12-15 05:04:24', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(63, 2, 13, 6, 'Responded', '2025-12-15 05:03:11', '2025-12-15 05:04:30', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(64, 2, 13, 7, 'Responded', '2025-12-15 05:03:12', '2025-12-15 05:04:42', 'Reject', 'staff is insufficient', 'Standard', 'Resent'),
(65, 2, 13, 6, 'Responded', '2025-12-15 05:05:28', '2025-12-15 05:06:20', 'Accept', NULL, 'Critical', 'Sent_again'),
(66, 2, 14, 3, 'Responded', '2025-12-19 12:03:22', '2025-12-19 12:04:27', 'Accept', NULL, 'Standard', 'None'),
(67, 2, 14, 6, 'Already_responded', '2025-12-19 12:03:22', NULL, NULL, NULL, 'Standard', 'None'),
(68, 2, 14, 7, 'Already_responded', '2025-12-19 12:03:22', NULL, NULL, NULL, 'Standard', 'None'),
(69, 2, 15, 3, 'Responded', '2025-12-19 12:07:24', '2025-12-19 12:07:38', 'Accept', NULL, 'Standard', 'None'),
(70, 2, 15, 6, 'Already_responded', '2025-12-19 12:07:24', NULL, NULL, NULL, 'Standard', 'None'),
(71, 2, 15, 7, 'Already_responded', '2025-12-19 12:07:24', NULL, NULL, NULL, 'Standard', 'None'),
(72, 2, 16, 3, 'Already_responded', '2025-12-19 12:10:53', NULL, NULL, NULL, 'Standard', 'None'),
(73, 2, 16, 6, 'Closed', '2025-12-19 12:10:53', '2025-12-19 12:11:08', 'Accept', NULL, 'Standard', 'None'),
(74, 2, 16, 7, 'Already_responded', '2025-12-19 12:10:53', NULL, NULL, NULL, 'Standard', 'None'),
(75, 2, 17, 3, 'Closed', '2025-12-22 05:17:29', '2025-12-22 05:18:57', 'Accept', NULL, 'Standard', 'None'),
(76, 2, 17, 6, 'Already_responded', '2025-12-22 05:17:29', NULL, NULL, NULL, 'Standard', 'None'),
(77, 2, 17, 7, 'Already_responded', '2025-12-22 05:17:29', NULL, NULL, NULL, 'Standard', 'None'),
(78, 3, 18, 3, 'Pending', '2025-12-22 08:15:33', NULL, NULL, NULL, 'Standard', 'Resent'),
(79, 3, 18, 6, 'Pending', '2025-12-22 08:15:33', NULL, NULL, NULL, 'Standard', 'Resent'),
(80, 3, 18, 7, 'Pending', '2025-12-22 08:15:33', NULL, NULL, NULL, 'Standard', 'Resent'),
(81, 3, 19, 7, 'Pending', '2025-12-22 08:16:10', NULL, NULL, NULL, 'Critical', 'Resent'),
(82, 3, 20, 1, 'Pending', '2025-12-22 08:17:02', NULL, NULL, NULL, 'Critical', 'Resent'),
(83, 3, 18, 3, 'Pending', '2025-12-23 11:10:40', NULL, NULL, NULL, 'Critical', 'Resent'),
(84, 3, 19, 3, 'Pending', '2025-12-23 11:10:40', NULL, NULL, NULL, 'Critical', 'Resent'),
(85, 3, 20, 3, 'Pending', '2025-12-23 11:10:40', NULL, NULL, NULL, 'Critical', 'Resent'),
(86, 1, 21, 3, 'Pending', '2025-12-26 12:44:57', NULL, NULL, NULL, 'Critical', 'Resent'),
(87, 1, 22, 3, 'Pending', '2025-12-26 14:44:53', NULL, NULL, NULL, 'Critical', 'Resent'),
(88, 1, 23, 3, 'Pending', '2025-12-27 03:37:58', NULL, NULL, NULL, 'Critical', 'Resent'),
(89, 1, 24, 3, 'Pending', '2025-12-27 04:33:30', NULL, NULL, NULL, 'Critical', 'Resent'),
(90, 1, 25, 3, 'Pending', '2025-12-27 04:33:34', NULL, NULL, NULL, 'Critical', 'Resent'),
(91, 1, 26, 3, 'Pending', '2025-12-27 04:36:26', NULL, NULL, NULL, 'Critical', 'Resent'),
(92, 1, 27, 3, 'Pending', '2025-12-27 04:36:33', NULL, NULL, NULL, 'Critical', 'Resent'),
(93, 1, 28, 3, 'Pending', '2025-12-27 04:36:34', NULL, NULL, NULL, 'Critical', 'Resent'),
(94, 1, 29, 3, 'Pending', '2025-12-27 04:40:05', NULL, NULL, NULL, 'Critical', 'Resent'),
(95, 1, 30, 3, 'Pending', '2025-12-27 04:40:09', NULL, NULL, NULL, 'Critical', 'Resent'),
(96, 1, 31, 3, 'Pending', '2025-12-27 04:47:23', NULL, NULL, NULL, 'Critical', 'Resent'),
(97, 1, 32, 3, 'Pending', '2025-12-27 04:49:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(98, 1, 33, 3, 'Pending', '2025-12-27 04:57:44', NULL, NULL, NULL, 'Critical', 'Resent'),
(99, 1, 34, 3, 'Pending', '2025-12-27 08:38:39', NULL, NULL, NULL, 'Critical', 'Resent'),
(100, 1, 35, 3, 'Pending', '2025-12-27 08:38:48', NULL, NULL, NULL, 'Critical', 'Resent'),
(101, 1, 36, 3, 'Responded', '2025-12-27 08:41:03', '2025-12-29 08:16:37', 'Accept', NULL, 'Critical', 'Resent'),
(102, 1, 37, 3, 'Pending', '2025-12-27 08:41:20', NULL, NULL, NULL, 'Critical', 'Resent'),
(103, 1, 38, 3, 'Pending', '2025-12-27 09:04:19', NULL, NULL, NULL, 'Critical', 'Resent'),
(104, 1, 39, 3, 'Pending', '2025-12-27 09:15:24', NULL, NULL, NULL, 'Critical', 'Resent'),
(105, 1, 40, 3, 'Pending', '2025-12-27 09:17:42', NULL, NULL, NULL, 'Critical', 'Resent'),
(106, 3, 42, 3, 'Closed', '2025-12-29 04:59:25', '2025-12-29 05:02:18', 'Accept', NULL, 'Standard', 'None'),
(107, 3, 42, 6, 'Already_responded', '2025-12-29 04:59:25', NULL, NULL, NULL, 'Standard', 'None'),
(108, 3, 42, 7, 'Already_responded', '2025-12-29 04:59:25', NULL, NULL, NULL, 'Standard', 'None'),
(109, 3, 43, 3, 'Responded', '2025-12-29 06:21:39', '2025-12-29 06:21:52', 'Accept', NULL, 'Standard', 'None'),
(110, 3, 43, 6, 'Already_responded', '2025-12-29 06:21:39', NULL, NULL, NULL, 'Standard', 'None'),
(111, 3, 43, 7, 'Already_responded', '2025-12-29 06:21:39', NULL, NULL, NULL, 'Standard', 'None'),
(112, 3, 44, 3, 'Responded', '2025-12-29 06:33:56', '2025-12-29 06:36:59', 'Accept', NULL, 'Standard', 'None'),
(113, 3, 44, 6, 'Already_responded', '2025-12-29 06:33:56', NULL, NULL, NULL, 'Standard', 'None'),
(114, 3, 44, 7, 'Already_responded', '2025-12-29 06:33:56', NULL, NULL, NULL, 'Standard', 'None'),
(115, 4, 45, 3, 'Already_responded', '2025-12-29 07:39:41', NULL, NULL, NULL, 'Standard', 'None'),
(116, 4, 45, 6, 'Responded', '2025-12-29 07:39:41', '2025-12-29 08:18:14', 'Accept', NULL, 'Standard', 'None'),
(117, 4, 45, 7, 'Already_responded', '2025-12-29 07:39:41', NULL, NULL, NULL, 'Standard', 'None'),
(118, 3, 18, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(119, 3, 19, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(120, 3, 20, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(121, 1, 21, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(122, 1, 22, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(123, 1, 23, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(124, 1, 24, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(125, 1, 25, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(126, 1, 26, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(127, 1, 27, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(128, 1, 28, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(129, 1, 29, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(130, 1, 30, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(131, 1, 31, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(132, 1, 32, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(133, 1, 33, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(134, 1, 34, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(135, 1, 35, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(136, 1, 36, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(137, 1, 37, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(138, 1, 38, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(139, 1, 39, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(140, 1, 40, 3, 'Pending', '2025-12-29 08:21:17', NULL, NULL, NULL, 'Critical', 'Resent'),
(141, 3, 41, 3, 'Responded', '2025-12-29 08:21:17', '2025-12-29 15:39:40', 'Accept', NULL, 'Critical', 'Sent_again'),
(142, 1, 46, 3, 'Pending', '2025-12-29 08:27:36', NULL, NULL, NULL, 'Critical', 'Resent'),
(143, 3, 18, 3, 'Pending', '2025-12-29 15:53:28', NULL, NULL, NULL, 'Critical', 'Resent'),
(144, 3, 19, 3, 'Pending', '2025-12-29 15:53:28', NULL, NULL, NULL, 'Critical', 'Resent'),
(145, 3, 20, 3, 'Pending', '2025-12-29 15:53:28', NULL, NULL, NULL, 'Critical', 'Resent'),
(146, 1, 21, 3, 'Pending', '2025-12-29 15:53:28', NULL, NULL, NULL, 'Critical', 'Resent'),
(147, 1, 22, 3, 'Pending', '2025-12-29 15:53:28', NULL, NULL, NULL, 'Critical', 'Resent'),
(148, 1, 23, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(149, 1, 24, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(150, 1, 25, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(151, 1, 26, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(152, 1, 27, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(153, 1, 28, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(154, 1, 29, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(155, 1, 30, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(156, 1, 31, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(157, 1, 32, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(158, 1, 33, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(159, 1, 34, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(160, 1, 35, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(161, 1, 36, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(162, 1, 37, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(163, 1, 38, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(164, 1, 39, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(165, 1, 40, 3, 'Pending', '2025-12-29 15:53:29', NULL, NULL, NULL, 'Critical', 'Resent'),
(166, 1, 46, 3, 'Responded', '2025-12-29 15:53:29', '2025-12-29 15:53:59', 'Accept', NULL, 'Critical', 'Sent_again'),
(167, 4, 47, 3, 'Pending', '2025-12-29 14:55:17', NULL, NULL, NULL, 'Standard', 'Resent'),
(168, 4, 47, 6, 'Pending', '2025-12-29 14:55:17', NULL, NULL, NULL, 'Standard', 'Resent'),
(169, 4, 47, 7, 'Pending', '2025-12-29 14:55:17', NULL, NULL, NULL, 'Standard', 'Resent'),
(170, 3, 18, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(171, 3, 19, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(172, 3, 20, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(173, 1, 21, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(174, 1, 22, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(175, 1, 23, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(176, 1, 24, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(177, 1, 25, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(178, 1, 26, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(179, 1, 27, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(180, 1, 28, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(181, 1, 29, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(182, 1, 30, 3, 'Pending', '2025-12-29 15:57:15', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(183, 1, 31, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(184, 1, 32, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(185, 1, 33, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(186, 1, 34, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(187, 1, 35, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(188, 1, 36, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(189, 1, 37, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(190, 1, 38, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(191, 1, 39, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(192, 1, 40, 3, 'Pending', '2025-12-29 15:57:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(193, 4, 47, 3, 'Responded', '2025-12-29 15:57:16', '2025-12-29 15:57:25', 'Accept', NULL, 'Critical', 'Sent_again'),
(194, 2, 48, 3, 'Pending', '2026-01-07 07:18:42', NULL, NULL, NULL, 'Critical', 'Resent'),
(195, 2, 49, 3, 'Pending', '2026-01-07 08:12:58', NULL, NULL, NULL, 'Critical', 'Resent'),
(196, 2, 50, 3, 'Pending', '2026-01-07 08:16:02', NULL, NULL, NULL, 'Critical', 'Resent'),
(197, 2, 48, 3, 'Pending', '2026-01-07 08:21:49', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(198, 2, 49, 3, 'Pending', '2026-01-07 14:36:49', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(199, 2, 50, 3, 'Pending', '2026-01-07 14:36:49', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(200, 15, 51, 12, 'Closed', '2026-01-07 16:50:56', '2026-01-07 16:58:44', 'Accept', NULL, 'Critical', 'None'),
(201, 2, 52, 12, 'Responded', '2026-01-09 03:27:18', '2026-01-09 04:23:11', 'Accept', NULL, 'Critical', 'None'),
(202, 4, 53, 3, 'Pending', '2026-01-20 04:40:25', NULL, NULL, NULL, 'Standard', 'Resent'),
(203, 4, 53, 6, 'Pending', '2026-01-20 04:40:25', NULL, NULL, NULL, 'Standard', 'Resent'),
(204, 4, 53, 7, 'Pending', '2026-01-20 04:40:25', NULL, NULL, NULL, 'Standard', 'Resent'),
(205, 4, 54, 3, 'Responded', '2026-01-20 05:38:31', '2026-01-20 06:02:32', 'Accept', NULL, 'Standard', 'None'),
(206, 4, 54, 6, 'Already_responded', '2026-01-20 05:38:31', NULL, NULL, NULL, 'Standard', 'None'),
(207, 4, 54, 7, 'Already_responded', '2026-01-20 05:38:31', NULL, NULL, NULL, 'Standard', 'None'),
(208, 4, 53, 3, 'Pending', '2026-01-20 05:41:49', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(209, 4, 55, 3, 'Pending', '2026-01-20 05:59:16', NULL, NULL, NULL, 'Standard', 'Resent'),
(210, 4, 55, 6, 'Pending', '2026-01-20 05:59:19', NULL, NULL, NULL, 'Standard', 'Resent'),
(211, 4, 55, 7, 'Pending', '2026-01-20 05:59:19', NULL, NULL, NULL, 'Standard', 'Resent'),
(212, 4, 55, 3, 'Pending', '2026-01-20 07:02:16', NULL, NULL, NULL, 'Critical', 'Sent_again'),
(213, 22, 56, 12, 'Pending', '2026-01-21 04:12:42', NULL, NULL, NULL, 'Critical', 'Resent'),
(214, 22, 56, 12, 'Pending', '2026-01-21 05:16:49', NULL, NULL, NULL, 'Critical', 'Sent_again');

-- --------------------------------------------------------

--
-- Table structure for table `case_status`
--

CREATE TABLE `case_status` (
  `status_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `acceptance_status` enum('Pending','Accepted') DEFAULT 'Pending',
  `center_id` int(11) DEFAULT NULL,
  `case_took_up_time` datetime NOT NULL DEFAULT current_timestamp(),
  `reached_location` enum('Yes') DEFAULT NULL,
  `reached_time` datetime DEFAULT NULL,
  `spot_animal` enum('Yes') DEFAULT NULL,
  `spotted_time` datetime DEFAULT NULL,
  `rescued_animal` enum('Yes') DEFAULT NULL,
  `rescued_time` datetime DEFAULT NULL,
  `rescue_photo` varchar(255) DEFAULT NULL,
  `closed_time` datetime DEFAULT NULL,
  `status` enum('Inprogress','Closed') DEFAULT 'Inprogress'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `case_status`
--

INSERT INTO `case_status` (`status_id`, `case_id`, `acceptance_status`, `center_id`, `case_took_up_time`, `reached_location`, `reached_time`, `spot_animal`, `spotted_time`, `rescued_animal`, `rescued_time`, `rescue_photo`, `closed_time`, `status`) VALUES
(4, 42, 'Accepted', 3, '2025-12-29 10:32:18', 'Yes', NULL, 'Yes', NULL, 'Yes', NULL, 'uploads/rescue_photos/1767024984_cat.jpg', '2025-12-29 21:46:24', 'Closed'),
(5, 43, 'Accepted', 3, '2025-12-29 11:51:52', 'Yes', '2026-01-18 22:45:52', 'Yes', '2026-01-18 22:47:05', NULL, NULL, NULL, NULL, 'Inprogress'),
(6, 44, 'Accepted', 3, '2025-12-29 12:06:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(7, 45, 'Accepted', 6, '2025-12-29 13:48:14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(8, 46, 'Accepted', 3, '2025-12-29 21:23:59', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(9, 47, 'Accepted', 3, '2025-12-29 21:27:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(10, 48, 'Pending', NULL, '2026-01-07 12:48:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(11, 49, 'Pending', NULL, '2026-01-07 13:42:58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(12, 50, 'Pending', NULL, '2026-01-07 13:46:02', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(13, 51, 'Accepted', 12, '2026-01-07 22:28:44', 'Yes', NULL, 'Yes', NULL, 'Yes', NULL, 'uploads/rescue_photos/1767805273_RESCUE_20260107_223101_3591083452731043383.jpg', '2026-01-07 22:31:13', 'Closed'),
(14, 52, 'Accepted', 12, '2026-01-09 09:53:11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(15, 53, 'Pending', NULL, '2026-01-20 10:10:25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(16, 54, 'Accepted', 3, '2026-01-20 11:32:32', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(17, 55, 'Pending', NULL, '2026-01-20 11:29:16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress'),
(18, 56, 'Pending', NULL, '2026-01-21 09:42:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Inprogress');

-- --------------------------------------------------------

--
-- Table structure for table `centers`
--

CREATE TABLE `centers` (
  `center_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `center_name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `latitude` decimal(10,7) NOT NULL,
  `longitude` decimal(10,7) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_active` enum('Yes','No') NOT NULL DEFAULT 'Yes',
  `total_cases_handled` int(11) NOT NULL DEFAULT 0,
  `avg_response_time` int(11) DEFAULT NULL,
  `center_status` enum('Operating','Deactivated') DEFAULT 'Operating'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `centers`
--

INSERT INTO `centers` (`center_id`, `user_id`, `center_name`, `address`, `latitude`, `longitude`, `phone`, `email`, `created_at`, `is_active`, `total_cases_handled`, `avg_response_time`, `center_status`) VALUES
(1, NULL, 'Blue Cross of India', 'Velachery, Chennai', 12.9838800, 80.2187400, '9876543211', 'contact@bluecross.org', '2025-11-30 11:46:25', 'Yes', 12, 66, 'Operating'),
(2, NULL, 'Paws Rescue Center', 'Adyar, Chennai', 13.0067000, 80.2570000, '9876543210', 'support@paws.org', '2025-11-30 11:46:25', 'Yes', 5, 16, 'Operating'),
(3, 18, 'Animal Welfare Trust', 'Tambaram, Chennai', 12.9245000, 80.1278000, '9876543212', 'help@awt.org', '2025-11-30 11:46:25', 'Yes', 33, 477, 'Operating'),
(4, NULL, 'Hope Animal Shelter', 'Anna Nagar, Chennai', 13.0878000, 80.2092000, '9876543213', 'team@hopeanimals.org', '2025-11-30 11:46:25', 'Yes', 19, NULL, 'Operating'),
(5, NULL, 'Stray Care Foundation', 'T Nagar, Chennai', 13.0362000, 80.2344000, '9876543214', 'info@straycast.org', '2025-11-30 11:46:25', 'Yes', 20, 66, 'Operating'),
(6, NULL, 'Blue Cross', 'Vellore', 13.0067000, 80.2187400, '9087564322', 'bluecross@gmail.com', '2025-11-30 12:02:39', 'Yes', 36, 30, 'Operating'),
(7, NULL, 'Green Valley Association', 'Guindy', 13.0067000, 80.2187400, '9567896023', 'greenvalley@gmail.com', '2025-11-30 12:05:31', 'Yes', 6, 713, 'Operating'),
(8, NULL, 'Little Light', 'Nungambakkam', 13.0569000, 80.2425000, '9890123456', 'lightup@gmail.com', '2025-12-11 04:03:44', 'Yes', 0, NULL, 'Operating'),
(9, NULL, 'Stray Shelter', 'Coimbatore', 11.0174000, 76.9589000, '9412345678', 'shelter@gmail.com', '2025-12-11 04:09:21', 'Yes', 0, NULL, 'Operating'),
(10, NULL, 'Blue Wave', 'Coimbatore', 11.0174000, 76.9589000, '9412345678', 'shelter@gmail.com', '2025-12-29 08:11:44', 'Yes', 0, NULL, 'Operating'),
(11, NULL, 'Animal Shelter', 'Coimbatore', 11.0174000, 76.9589000, '9494126578', 'animal@gmail.com', '2025-12-29 08:13:03', 'Yes', 0, NULL, 'Operating'),
(12, 14, 'Shelter Life', 'Chembarambakkam, Tamil Nadu', 13.0283497, 80.0346001, '9658231452', 'life@gmail.com', '2026-01-07 16:46:06', 'Yes', 3, 31, 'Operating');

-- --------------------------------------------------------

--
-- Table structure for table `donations`
--

CREATE TABLE `donations` (
  `donation_id` int(11) NOT NULL,
  `center_id` int(11) NOT NULL,
  `case_id` int(11) NOT NULL,
  `image_of_animal` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `requested_time` datetime NOT NULL DEFAULT current_timestamp(),
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `user_id` int(11) DEFAULT NULL,
  `payment_method` enum('UPI','Card','NetBanking','Cash') DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `payment_time` datetime DEFAULT NULL,
  `donation_status` enum('Paid','Unpaid','Rejected') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `donations`
--

INSERT INTO `donations` (`donation_id`, `center_id`, `case_id`, `image_of_animal`, `amount`, `requested_time`, `approval_status`, `user_id`, `payment_method`, `transaction_id`, `payment_time`, `donation_status`) VALUES
(1, 6, 16, '1766423349_cow.avif', 2000.00, '2025-12-22 22:39:09', 'Approved', 3, 'UPI', 'TXN98374632', '2025-12-23 10:56:26', 'Paid'),
(2, 3, 17, '1766423436_cow.avif', 3000.00, '2025-12-22 22:40:36', 'Rejected', NULL, NULL, NULL, NULL, 'Rejected'),
(3, 3, 42, '1767025486_cat.jpg', 4500.00, '2025-12-29 21:54:46', 'Approved', 15, '', 'pay_S13s7qlzMTb3Bu', '2026-01-07 22:23:26', 'Paid');

-- --------------------------------------------------------

--
-- Table structure for table `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `token_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `center_id` int(11) DEFAULT NULL,
  `user_type` enum('user','organization','admin') NOT NULL,
  `fcm_token` varchar(512) NOT NULL,
  `device_info` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(10) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `user_type` varchar(50) NOT NULL,
  `otp` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fcm_token` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `email`, `password`, `user_type`, `otp`, `otp_expiry`, `created_at`, `fcm_token`) VALUES
(1, 'Vennela', '9494126599', 'srivennela904@gmail.com', '1234567', 'admin', NULL, NULL, '2025-11-27 05:02:04', 'fjqsJvBjR9WX2QtpxgUJ22:APA91bFnymysXJI_fTPBpyVzTO7_m0rC2-00nnn_gMcm9RGmThvlYZcKXZB3rDTXE16sbrFPGNWuZWO-4a_KsVPKYTnzFqXnMSPB1TURV9_l7o8Vfha89og'),
(2, 'Surya', '9494126789', 'surya123@gmail.com', '1234', 'user', NULL, NULL, '2025-11-29 07:43:25', NULL),
(3, 'Ravi', '9494126767', 'ravi134@gmail.com', '9878', 'user', NULL, NULL, '2025-12-05 04:23:47', 'fjqsJvBjR9WX2QtpxgUJ22:APA91bFnymysXJI_fTPBpyVzTO7_m0rC2-00nnn_gMcm9RGmThvlYZcKXZB3rDTXE16sbrFPGNWuZWO-4a_KsVPKYTnzFqXnMSPB1TURV9_l7o8Vfha89og'),
(4, 'Rahul', '9494126751', 'rahul234@gmail.com', '4567', 'user', NULL, NULL, '2025-12-05 04:27:23', 'cdh7rBpgRpWBfQo57cILX_:APA91bGH6IFuDL2ux-HNqBJLEwhqe0S6Fr8pGi5JGo3ANr-DUPBt4ZS-KItGW6CYPTDE0R0OcRE8BuDBkVhUx8XIutFA9ZU9v0tfhdruXZYO7oCVGiUbLOA'),
(5, 'Green Valey', '9494126599', 'jin143@gmail.com', '1234', 'organization', NULL, NULL, '2025-12-10 08:20:28', NULL),
(6, 'Peace Shelter', '9494125833', 'peace234@gmail.com', '7891', 'organization', NULL, NULL, '2025-12-17 08:51:27', NULL),
(7, 'Sravya', '8967452367', 'srav678@gmail.com', '6930', 'user', NULL, NULL, '2025-12-17 17:11:05', NULL),
(8, 'Lahari', '9381260162', 'laharipriya1212@gmail.com', '121205', 'user', NULL, NULL, '2025-12-23 14:24:31', NULL),
(9, 'Janani', '8765431289', 'janani123@gmail.com', '1596', 'user', NULL, NULL, '2025-12-25 12:50:39', NULL),
(10, 'Save Animals', '9656412358', 'save234@gmail.com', '7890', 'organization', NULL, NULL, '2025-12-25 12:52:03', NULL),
(11, 'Namjoon', '8121292979', 'nam123@gmail.com', '123658', 'user', NULL, NULL, '2025-12-25 17:23:47', NULL),
(12, 'Animal Welfare Center', '8452369852', 'center345@gmail.com', '5689', 'organization', NULL, NULL, '2025-12-25 17:24:52', NULL),
(13, 'Sreya', '9494126762', 'sreya456@gmail.com', '678z9', 'user', NULL, NULL, '2025-12-26 04:32:02', NULL),
(14, 'Shelter Life', '9658231452', 'life@gmail.com', '5678', 'organization', NULL, NULL, '2026-01-07 16:45:03', NULL),
(15, 'Harika', '8134567890', 'hari143@gmail.com', '5612', 'user', NULL, NULL, '2026-01-07 16:48:55', NULL),
(16, 'Blue Cross of India', '9876543211', 'contct@bluecross.org', '7777', 'organization', NULL, NULL, '2026-01-08 03:52:04', NULL),
(17, 'Paws Rescue Center', '9876543210', 'support@paws.org', '6666', 'organization', NULL, NULL, '2026-01-08 03:53:43', NULL),
(18, 'Animal Welfare Trust', '9876543212', 'help@awt.org', '6789', 'organization', NULL, NULL, '2026-01-08 03:55:50', 'fjqsJvBjR9WX2QtpxgUJ22:APA91bFnymysXJI_fTPBpyVzTO7_m0rC2-00nnn_gMcm9RGmThvlYZcKXZB3rDTXE16sbrFPGNWuZWO-4a_KsVPKYTnzFqXnMSPB1TURV9_l7o8Vfha89og'),
(19, 'Hope Animal Shelter', '9876543213', 'team@hopeanimals.org', '6777', 'organization', NULL, NULL, '2026-01-08 04:37:58', NULL),
(20, 'Stray Care Foundation', '9876543214', 'info@straycast.org', '6745', 'organization', NULL, NULL, '2026-01-08 04:38:56', NULL),
(21, 'Blue Cross', '9087564322', 'bluecross@gmail.com', '6744', 'organization', NULL, NULL, '2026-01-08 04:40:05', NULL),
(22, 'Jin', '9492666234', 'kveera947@gmail.com', '123456', 'user', NULL, NULL, '2026-01-20 07:43:20', 'fjqsJvBjR9WX2QtpxgUJ22:APA91bFnymysXJI_fTPBpyVzTO7_m0rC2-00nnn_gMcm9RGmThvlYZcKXZB3rDTXE16sbrFPGNWuZWO-4a_KsVPKYTnzFqXnMSPB1TURV9_l7o8Vfha89og');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cases`
--
ALTER TABLE `cases`
  ADD PRIMARY KEY (`case_id`),
  ADD KEY `fk_cases_user` (`user_id`);

--
-- Indexes for table `case_escalations`
--
ALTER TABLE `case_escalations`
  ADD PRIMARY KEY (`escalation_id`),
  ADD KEY `fk_escal_user` (`user_id`),
  ADD KEY `fk_escal_case` (`case_id`),
  ADD KEY `fk_escal_center` (`center_id`);

--
-- Indexes for table `case_status`
--
ALTER TABLE `case_status`
  ADD PRIMARY KEY (`status_id`),
  ADD UNIQUE KEY `uk_case_center` (`case_id`,`center_id`),
  ADD KEY `fk_case_status_center` (`center_id`);

--
-- Indexes for table `centers`
--
ALTER TABLE `centers`
  ADD PRIMARY KEY (`center_id`);

--
-- Indexes for table `donations`
--
ALTER TABLE `donations`
  ADD PRIMARY KEY (`donation_id`),
  ADD KEY `fk_donation_center` (`center_id`),
  ADD KEY `fk_donation_case` (`case_id`);

--
-- Indexes for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `unique_token` (`fcm_token`),
  ADD KEY `idx_user` (`user_id`,`user_type`),
  ADD KEY `idx_center` (`center_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cases`
--
ALTER TABLE `cases`
  MODIFY `case_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `case_escalations`
--
ALTER TABLE `case_escalations`
  MODIFY `escalation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=215;

--
-- AUTO_INCREMENT for table `case_status`
--
ALTER TABLE `case_status`
  MODIFY `status_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `centers`
--
ALTER TABLE `centers`
  MODIFY `center_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `donations`
--
ALTER TABLE `donations`
  MODIFY `donation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cases`
--
ALTER TABLE `cases`
  ADD CONSTRAINT `fk_cases_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `case_escalations`
--
ALTER TABLE `case_escalations`
  ADD CONSTRAINT `fk_escal_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_escal_center` FOREIGN KEY (`center_id`) REFERENCES `centers` (`center_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_escal_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `case_status`
--
ALTER TABLE `case_status`
  ADD CONSTRAINT `fk_case_status_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_case_status_center` FOREIGN KEY (`center_id`) REFERENCES `centers` (`center_id`) ON DELETE CASCADE;

--
-- Constraints for table `donations`
--
ALTER TABLE `donations`
  ADD CONSTRAINT `fk_donation_case` FOREIGN KEY (`case_id`) REFERENCES `cases` (`case_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_donation_center` FOREIGN KEY (`center_id`) REFERENCES `centers` (`center_id`) ON DELETE CASCADE;

DELIMITER $$
--
-- Events
--
CREATE DEFINER=`root`@`localhost` EVENT `mark_delayed_cases` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-12-19 14:39:27' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE case_escalations ce
    JOIN cases c ON ce.case_id = c.case_id
    SET ce.remark = 'Delayed'
    WHERE c.status = 'Reported'
      AND TIMESTAMPDIFF(MINUTE, c.created_time, NOW()) >= 60
      AND ce.remark = 'None'$$

CREATE DEFINER=`root`@`localhost` EVENT `mark_rejected_by_all` ON SCHEDULE EVERY 1 MINUTE STARTS '2025-12-19 14:47:55' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE case_escalations ce
    JOIN (
        SELECT case_id
        FROM case_escalations
        GROUP BY case_id
        HAVING 
            COUNT(*) = SUM(CASE WHEN response = 'Reject' THEN 1 ELSE 0 END)
            AND COUNT(*) > 0
    ) all_rejected ON ce.case_id = all_rejected.case_id
    SET ce.remark = 'Rejected_by_all'
    WHERE ce.remark IN ('None', 'Delayed')$$

CREATE DEFINER=`root`@`localhost` EVENT `update_avg_response_time` ON SCHEDULE EVERY 10 MINUTE STARTS '2025-12-19 14:49:02' ON COMPLETION NOT PRESERVE ENABLE DO UPDATE centers c
JOIN (
    SELECT 
        center_id,
        AVG(TIMESTAMPDIFF(MINUTE, assigned_time, responded_time)) AS avg_minutes
    FROM case_escalations
    WHERE responded_time IS NOT NULL 
    GROUP BY center_id
) ce ON c.center_id = ce.center_id
SET c.avg_response_time = ce.avg_minutes$$

CREATE DEFINER=`root`@`localhost` EVENT `auto_escalate_delayed` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-01-07 12:41:49' ON COMPLETION PRESERVE ENABLE DO CALL sp_escalate_delayed_cases()$$

CREATE DEFINER=`root`@`localhost` EVENT `auto_escalate_rejected` ON SCHEDULE EVERY 5 MINUTE STARTS '2026-01-07 12:42:09' ON COMPLETION PRESERVE ENABLE DO CALL sp_escalate_rejected_cases()$$

DELIMITER ;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
