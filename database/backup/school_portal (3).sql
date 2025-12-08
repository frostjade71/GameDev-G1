-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: db
-- Generation Time: Dec 08, 2025 at 09:19 AM
-- Server version: 8.0.44
-- PHP Version: 8.3.26

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `school_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` text COLLATE utf8mb4_general_ci NOT NULL,
  `action_timestamp` datetime NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `character_ownership`
--

CREATE TABLE `character_ownership` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `character_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `character_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `character_image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `purchased_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `character_ownership`
--

INSERT INTO `character_ownership` (`id`, `user_id`, `username`, `character_type`, `character_name`, `character_image_path`, `purchased_at`) VALUES
(1, 9, 'Alfred Estares', 'boy', 'Ethan', '../assets/characters/boy_char/character_ethan.png', '2025-09-28 08:00:20'),
(2, 5, 'Jaderby Garcia Peñaranda', 'boy', 'Ethan', '../assets/characters/boy_char/character_ethan.png', '2025-09-28 08:00:20'),
(4, 10, 'Ria Jhen Boreres', 'boy', 'Ethan', '../assets/characters/boy_char/character_ethan.png', '2025-09-28 08:00:20'),
(8, 9, 'Alfred Estares', 'girl', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 08:00:20'),
(9, 5, 'Jaderby Garcia Peñaranda', 'girl', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 08:00:20'),
(11, 10, 'Ria Jhen Boreres', 'girl', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 08:00:20');

-- --------------------------------------------------------

--
-- Table structure for table `character_selections`
--

CREATE TABLE `character_selections` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'vocabworld',
  `selected_character` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `character_image_path` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `equipped_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `character_selections`
--

INSERT INTO `character_selections` (`id`, `user_id`, `game_type`, `selected_character`, `character_image_path`, `equipped_at`, `updated_at`, `username`) VALUES
(1, 9, 'vocabworld', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 06:50:23', '2025-11-15 07:38:15', 'Alfred Estares'),
(2, 5, 'vocabworld', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 06:50:23', '2025-11-23 02:26:25', 'Jaderby Garcia Peñaranda'),
(4, 10, 'vocabworld', 'Emma', '../assets/characters/girl_char/character_emma.png', '2025-09-28 06:59:29', '2025-09-28 12:45:21', 'Ria Jhen Boreres'),
(5, 12, 'vocabworld', 'Ethan', '../assets/characters/boy_char/character_ethan.png', '2025-09-29 23:20:35', '2025-09-29 23:20:38', 'Loren mae Pascual');

-- --------------------------------------------------------

--
-- Table structure for table `friends`
--

CREATE TABLE `friends` (
  `id` int NOT NULL,
  `user1_id` int NOT NULL,
  `user2_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `friend_requests`
--

CREATE TABLE `friend_requests` (
  `id` int NOT NULL,
  `requester_id` int NOT NULL,
  `requester_username` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `receiver_id` int NOT NULL,
  `receiver_username` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('pending','accepted','declined') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_read` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `game_progress`
--

CREATE TABLE `game_progress` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `unlocked_levels` text COLLATE utf8mb4_general_ci,
  `achievements` text COLLATE utf8mb4_general_ci,
  `total_play_time` int DEFAULT '0',
  `last_played` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `player_level` int DEFAULT '1',
  `experience_points` int DEFAULT '0',
  `total_monsters_defeated` int DEFAULT '0',
  `username` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `total_experience_earned` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `game_progress`
--

INSERT INTO `game_progress` (`id`, `user_id`, `game_type`, `unlocked_levels`, `achievements`, `total_play_time`, `last_played`, `created_at`, `updated_at`, `player_level`, `experience_points`, `total_monsters_defeated`, `username`, `total_experience_earned`) VALUES
(1, 9, 'vocabworld', NULL, NULL, 0, '2025-10-23 02:29:36', '2025-10-23 02:29:36', '2025-11-19 04:59:16', 4, 50, 9, 'Alfred Estares', 120),
(3, 5, 'vocabworld', NULL, NULL, 0, '2025-10-23 04:04:01', '2025-10-23 04:04:01', '2025-11-23 03:43:05', 5, 40, 15, 'Jaderby Garcia Peñaranda', 790),
(4, 22, 'vocabworld', NULL, NULL, 0, '2025-10-23 13:22:47', '2025-10-23 13:22:47', '2025-10-23 13:25:08', 2, 55, 2, 'admin', 105),
(6, 24, 'vocabworld', NULL, NULL, 0, '2025-11-16 02:45:38', '2025-11-16 02:45:38', '2025-11-16 02:45:57', 2, 10, 1, 'midwisp', 60);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `is_read` tinyint(1) DEFAULT '0',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `data`, `is_read`, `created_at`) VALUES
(79, 5, 'friend_request', 'You have a new friend request!', '{\"requester_id\":8,\"request_id\":\"72\"}', 0, '2025-09-27 05:25:45'),
(83, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":8,\"request_id\":73}', 0, '2025-09-27 05:26:44'),
(86, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":8,\"request_id\":74}', 0, '2025-09-27 05:29:01'),
(87, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":8,\"friendship_id\":7}', 0, '2025-09-27 05:30:01'),
(88, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":8,\"friendship_id\":4}', 0, '2025-09-29 23:47:32'),
(92, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":8,\"request_id\":77}', 0, '2025-10-02 01:30:50'),
(94, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":8,\"friendship_id\":8}', 0, '2025-10-02 02:03:28'),
(96, 9, 'friend_request', 'testhuman sent you a friend request', '{\"requester_id\":18,\"requester_name\":\"testhuman\"}', 1, '2025-10-04 01:54:19'),
(100, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":81}', 0, '2025-10-04 02:07:51'),
(101, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":10}', 0, '2025-10-04 02:08:41'),
(107, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":86}', 0, '2025-10-04 02:15:14'),
(108, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":11}', 0, '2025-10-04 02:17:27'),
(110, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":87}', 0, '2025-10-04 02:17:47'),
(111, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":12}', 0, '2025-10-04 02:18:00'),
(114, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":89}', 0, '2025-10-04 02:18:53'),
(115, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":13}', 0, '2025-10-04 02:19:02'),
(119, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":92}', 0, '2025-10-04 02:23:27'),
(120, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":14}', 0, '2025-10-04 02:24:04'),
(122, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":93}', 0, '2025-10-04 02:24:23'),
(123, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":15}', 0, '2025-10-04 02:25:05'),
(128, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":97}', 0, '2025-10-04 02:27:33'),
(129, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":16}', 0, '2025-10-04 02:29:28'),
(131, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":14,\"request_id\":98}', 0, '2025-10-04 02:30:04'),
(132, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":14,\"friendship_id\":17}', 0, '2025-10-04 02:30:16'),
(135, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":100}', 0, '2025-10-04 02:31:30'),
(136, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":18}', 0, '2025-10-04 02:31:57'),
(142, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":105}', 0, '2025-10-04 02:51:56'),
(146, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":19}', 0, '2025-10-04 03:06:38'),
(149, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":110}', 0, '2025-10-04 03:08:10'),
(151, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":20}', 0, '2025-10-04 03:09:03'),
(161, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":120}', 0, '2025-10-14 03:20:19'),
(162, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":21}', 0, '2025-10-14 03:22:57'),
(164, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":121}', 0, '2025-10-14 03:24:05'),
(165, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":22}', 0, '2025-10-14 03:25:44'),
(167, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":122}', 0, '2025-10-14 03:26:17'),
(168, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":23}', 0, '2025-10-14 03:28:32'),
(173, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":126}', 0, '2025-10-14 03:32:30'),
(175, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":24}', 0, '2025-10-14 03:34:03'),
(177, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":128}', 0, '2025-10-14 03:39:10'),
(178, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":25}', 0, '2025-10-14 03:40:37'),
(180, 9, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":5,\"request_id\":129}', 0, '2025-10-14 03:41:35'),
(181, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":5,\"friendship_id\":26}', 0, '2025-10-14 03:43:46'),
(184, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":131}', 0, '2025-10-15 11:26:30'),
(185, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":27}', 0, '2025-10-23 11:38:22'),
(233, 5, 'friend_request', 'You have a new friend request!', '{\"requester_id\":22,\"request_id\":\"194\"}', 0, '2025-11-12 00:03:48'),
(239, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":199}', 0, '2025-11-12 06:27:42'),
(240, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":28}', 0, '2025-11-12 06:56:14'),
(281, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":239}', 0, '2025-11-14 13:25:37'),
(282, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":29}', 0, '2025-11-14 13:25:49'),
(295, 5, 'friend_request', 'You have a new friend request!', '{\"requester_id\":9,\"request_id\":\"252\"}', 0, '2025-11-15 07:41:08'),
(298, 5, 'friend_request', 'You have a new friend request!', '{\"requester_id\":9,\"request_id\":\"256\"}', 0, '2025-11-15 07:46:26'),
(311, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":9,\"request_id\":271}', 0, '2025-11-15 08:08:18'),
(312, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":9,\"friendship_id\":30}', 0, '2025-11-15 08:08:54'),
(343, 5, 'friend_request', 'Alfred Estares sent you a friend request', '{\"requester_id\":9,\"requester_name\":\"Alfred Estares\"}', 0, '2025-11-17 12:50:07'),
(353, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":311}', 0, '2025-11-19 01:48:47'),
(355, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"1\"}', 0, '2025-11-19 03:39:30'),
(369, 9, 'cresent', 'Jaderby Garcia Peñaranda gave you a Crescent!', '{\"giver_username\":\"Jaderby Garcia Pe\\u00f1aranda\",\"receiver_username\":\"Alfred Estares\"}', 0, '2025-11-23 07:54:38'),
(397, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":333}', 0, '2025-11-26 09:25:23'),
(398, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"2\"}', 0, '2025-11-26 09:57:40'),
(418, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":352}', 0, '2025-11-26 11:23:07'),
(419, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"3\"}', 0, '2025-11-26 11:27:54'),
(421, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":353}', 0, '2025-11-26 11:28:12'),
(422, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"9\",\"friendship_id\":\"4\"}', 0, '2025-11-26 11:28:44'),
(426, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":356}', 0, '2025-11-26 11:29:27'),
(429, 5, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"9\",\"friendship_id\":\"5\"}', 0, '2025-11-26 11:30:27'),
(433, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":360}', 0, '2025-11-26 11:32:01'),
(435, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"6\"}', 0, '2025-11-26 11:41:52'),
(437, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":362}', 0, '2025-11-26 11:42:37'),
(439, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"7\"}', 0, '2025-11-26 11:42:53'),
(441, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":364}', 0, '2025-11-26 11:44:47'),
(443, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"8\"}', 0, '2025-11-26 11:44:55'),
(444, 9, 'friend_request', 'Jaderby Garcia Peñaranda sent you a friend request', '{\"requester_id\":\"5\",\"requester_name\":\"Jaderby Garcia Pe\\u00f1aranda\"}', 0, '2025-11-26 11:46:12'),
(445, 5, 'friend_request_accepted', 'Your friend request has been accepted!', '{\"accepter_id\":\"9\",\"request_id\":366}', 0, '2025-11-26 11:46:17'),
(446, 9, 'friend_removed', 'You are no longer friends with this user.', '{\"remover_id\":\"5\",\"friendship_id\":\"9\"}', 0, '2025-11-26 11:46:31');

-- --------------------------------------------------------

--
-- Table structure for table `profile_view_cooldowns`
--

CREATE TABLE `profile_view_cooldowns` (
  `id` int NOT NULL,
  `viewer_username` varchar(255) NOT NULL,
  `viewed_username` varchar(255) NOT NULL,
  `last_viewed` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `profile_view_cooldowns`
--

INSERT INTO `profile_view_cooldowns` (`id`, `viewer_username`, `viewed_username`, `last_viewed`) VALUES
(1, 'Jaderby Garcia Peñaranda', 'Alfred Estares', '2025-11-26 12:22:12');

-- --------------------------------------------------------

--
-- Table structure for table `shard_transactions`
--

CREATE TABLE `shard_transactions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `transaction_type` enum('earned','spent') COLLATE utf8mb4_general_ci NOT NULL,
  `amount` int NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `game_type` varchar(50) COLLATE utf8mb4_general_ci DEFAULT 'vocabworld',
  `related_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shard_transactions`
--

INSERT INTO `shard_transactions` (`id`, `user_id`, `username`, `transaction_type`, `amount`, `description`, `game_type`, `related_id`, `created_at`) VALUES
(4, 5, 'Jaderby Garcia Peñaranda', 'spent', 20, 'Purchased Amber character', 'vocabworld', 18, '2025-09-28 11:41:39'),
(5, 5, 'Jaderby Garcia Peñaranda', 'spent', 20, 'Purchased Amber character', 'vocabworld', 19, '2025-09-28 12:38:40'),
(6, 5, 'Jaderby Garcia Peñaranda', 'spent', 20, 'Purchased Amber character', 'vocabworld', 20, '2025-09-28 12:54:23'),
(7, 9, 'Alfred Estares', 'spent', 20, 'Purchased Amber character', 'vocabworld', 21, '2025-11-15 07:37:38'),
(8, 5, 'Jaderby Garcia Peñaranda', 'spent', 20, 'Purchased Amber character', 'vocabworld', 22, '2025-11-19 02:20:28');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `grade_level` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `section` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `about_me` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `grade_level`, `section`, `password`, `about_me`, `created_at`, `updated_at`) VALUES
(5, 'Jaderby Garcia Peñaranda', 'jaderzkiepenaranda@gmail.com', 'Developer', '', '$2y$10$hqQTzwSMXZZmP9GN0c8w2eppvup2vLkkURxg4f/QCw9fdElEjdzPC', 'bro why did i choose this course??', '2025-09-15 02:38:26', '2025-11-26 12:05:46'),
(9, 'Alfred Estares', 'alfred@gmail.com', 'Admin', '', '$2y$10$IqZwl8YGYrxMBm7bdBTYqOu9RJEhL0NufzrW9.mymtcOLM54YWqca', 'bruh hey yow wazzuP', '2025-09-23 15:37:34', '2025-11-19 06:34:56'),
(10, 'Ria Jhen Boreres', 'riajhen@gmail.com', 'Grade 8', '', '$2y$10$SafeK9/NFyIg2nw4bZ7ubu3zSz38LIFSPyPHxtJ.F9tfv4Ba6AOXC', 'Crush ko hi Jeric', '2025-09-28 06:58:38', '2025-09-28 07:00:56'),
(12, 'Loren mae Pascual', 'lorenmae@gmail.com', 'Teacher', NULL, '$2y$10$RJ05fuZtfe3dxiSHGvCkfuD770w.qpoRu2Gct8.05SjKQJyiDZg4y', NULL, '2025-09-29 23:12:23', '2025-11-10 22:56:51'),
(14, 'Ken Erickson Bacarisas', 'kenerickson@gmail.com', 'Grade 10', NULL, '$2y$10$gQNm8hYRLhVmCYs8rMt7Ge6bHg5cSU6t2tLPIognqUoWrOHqcvDq2', NULL, '2025-10-02 01:33:11', '2025-10-02 01:33:11'),
(22, 'admin', 'jaderbypenaranda@gmail.com', 'Grade 8', NULL, '$2y$10$VsRQ7Usbj.0Qq8kg9bMOheY.z53JUXASb1XmwA/VwYz2KeHkO3Teu', NULL, '2025-10-23 13:22:26', '2025-10-23 13:22:26'),
(24, 'midwisp', 'midwisp44@gmail.com', 'Grade 7', NULL, '$2y$10$1LgGn6Q2mKF7facvcQVvvO0kpQkcjpoMZsWMtCYTrFVk2n4OcaEVu', NULL, '2025-11-16 02:44:09', '2025-11-16 02:44:09');

-- --------------------------------------------------------

--
-- Table structure for table `user_crescents`
--

CREATE TABLE `user_crescents` (
  `id` int NOT NULL,
  `giver_username` varchar(255) NOT NULL,
  `receiver_username` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_crescents`
--

INSERT INTO `user_crescents` (`id`, `giver_username`, `receiver_username`, `created_at`) VALUES
(22, 'Jaderby Garcia Peñaranda', 'Ria Jhen Boreres', '2025-11-23 07:09:11'),
(43, 'Jaderby Garcia Peñaranda', 'Ken Erickson Bacarisas', '2025-11-23 07:41:50'),
(60, 'admin', 'Alfred Estares', '2025-11-23 12:32:48'),
(64, 'Alfred Estares', 'Jaderby Garcia Peñaranda', '2025-11-25 13:23:15'),
(66, 'Jaderby Garcia Peñaranda', 'Alfred Estares', '2025-11-26 11:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `user_essence`
--

CREATE TABLE `user_essence` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `essence_amount` int DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_essence`
--

INSERT INTO `user_essence` (`id`, `user_id`, `username`, `essence_amount`, `last_updated`) VALUES
(1, 5, 'Jaderby Garcia Peñaranda', 198, '2025-11-22 02:57:41'),
(2, 9, 'Alfred Estares', 16, '2025-11-19 04:59:10'),
(3, 10, 'Ria Jhen Boreres', 0, '2025-10-23 12:40:40'),
(4, 12, 'Loren mae Pascual', 0, '2025-10-23 12:40:40'),
(5, 14, 'Ken Erickson Bacarisas', 0, '2025-10-23 12:40:40'),
(15, 22, 'admin', 22, '2025-10-23 13:24:57'),
(33, 24, 'midwisp', 17, '2025-11-16 02:45:54');

-- --------------------------------------------------------

--
-- Table structure for table `user_fame`
--

CREATE TABLE `user_fame` (
  `id` int NOT NULL,
  `username` varchar(255) NOT NULL,
  `cresents` int DEFAULT '0',
  `views` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `user_fame`
--

INSERT INTO `user_fame` (`id`, `username`, `cresents`, `views`, `created_at`, `updated_at`) VALUES
(1, 'Jaderby Garcia Peñaranda', 1, 31, '2025-11-23 06:48:35', '2025-11-26 11:39:26'),
(2, 'Alfred Estares', 2, 200, '2025-11-23 06:48:35', '2025-11-26 12:22:12'),
(3, 'Ria Jhen Boreres', 1, 38, '2025-11-23 06:48:35', '2025-11-26 11:31:40'),
(4, 'Loren mae Pascual', 0, 61, '2025-11-23 06:48:35', '2025-11-26 11:39:13'),
(5, 'Ken Erickson Bacarisas', 1, 97, '2025-11-23 06:48:35', '2025-11-26 12:18:38'),
(6, 'admin', 0, 10, '2025-11-23 06:48:35', '2025-11-26 12:18:42'),
(7, 'midwisp', 0, 10, '2025-11-23 06:48:35', '2025-11-26 09:46:54'),
(1329, 'Jaderby Garcia Peñarandaasvas', 0, 0, '2025-11-26 12:01:41', '2025-11-26 12:01:41');

-- --------------------------------------------------------

--
-- Table structure for table `user_favorites`
--

CREATE TABLE `user_favorites` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_favorites`
--

INSERT INTO `user_favorites` (`id`, `user_id`, `game_type`, `created_at`) VALUES
(28, 12, 'vocabworld', '2025-09-29 23:13:00'),
(45, 9, 'vocabworld', '2025-11-19 05:05:22'),
(48, 5, 'vocabworld', '2025-11-23 06:07:37');

-- --------------------------------------------------------

--
-- Table structure for table `user_gwa`
--

CREATE TABLE `user_gwa` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `game_type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `gwa` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_gwa`
--

INSERT INTO `user_gwa` (`id`, `user_id`, `game_type`, `gwa`, `created_at`, `updated_at`) VALUES
(5, 5, 'vocabworld', 7.50, '2025-11-19 02:08:02', '2025-12-08 09:15:14'),
(6, 24, 'vocabworld', 3.00, '2025-11-19 03:02:59', '2025-11-26 09:46:54'),
(7, 22, 'vocabworld', 3.00, '2025-11-19 03:05:44', '2025-11-26 12:18:42'),
(8, 9, 'vocabworld', 6.00, '2025-11-19 03:39:29', '2025-11-26 12:22:31');

-- --------------------------------------------------------

--
-- Table structure for table `user_settings`
--

CREATE TABLE `user_settings` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `bgm_enabled` tinyint(1) DEFAULT '1',
  `sfx_enabled` tinyint(1) DEFAULT '1',
  `language` varchar(10) COLLATE utf8mb4_general_ci DEFAULT 'english',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_shards`
--

CREATE TABLE `user_shards` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `current_shards` int NOT NULL DEFAULT '0',
  `total_earned` int NOT NULL DEFAULT '0',
  `total_spent` int NOT NULL DEFAULT '0',
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_shards`
--

INSERT INTO `user_shards` (`id`, `user_id`, `username`, `current_shards`, `total_earned`, `total_spent`, `last_updated`, `created_at`) VALUES
(1, 5, 'Jaderby Garcia Peñaranda', 60, 0, 140, '2025-11-19 02:20:28', '2025-09-28 08:29:37'),
(3, 9, 'Alfred Estares', 30, 0, 20, '2025-11-15 07:37:38', '2025-09-28 08:29:37'),
(4, 10, 'Ria Jhen Boreres', 0, 0, 0, '2025-09-28 08:29:37', '2025-09-28 08:29:37'),
(5, 12, 'Loren mae Pascual', 0, 0, 0, '2025-09-29 23:20:06', '2025-09-29 23:20:06'),
(9, 22, 'admin', 0, 0, 0, '2025-10-23 13:22:46', '2025-10-23 13:22:46'),
(11, 24, 'midwisp', 0, 0, 0, '2025-11-16 02:45:37', '2025-11-16 02:45:37');

-- --------------------------------------------------------

--
-- Table structure for table `vocab_scores`
--

CREATE TABLE `vocab_scores` (
  `score_id` int NOT NULL,
  `scholar_id` int NOT NULL,
  `score` int NOT NULL,
  `questions_answered` int DEFAULT '0',
  `correct_answers` int DEFAULT '0',
  `accuracy` decimal(5,2) DEFAULT '0.00',
  `date_played` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `game_session_id` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `action_timestamp` (`action_timestamp`);

--
-- Indexes for table `character_ownership`
--
ALTER TABLE `character_ownership`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_character` (`user_id`,`character_type`);

--
-- Indexes for table `character_selections`
--
ALTER TABLE `character_selections`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_game_character` (`user_id`,`game_type`);

--
-- Indexes for table `friends`
--
ALTER TABLE `friends`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_friendship` (`user1_id`,`user2_id`),
  ADD KEY `idx_friends_user1` (`user1_id`),
  ADD KEY `idx_friends_user2` (`user2_id`);

--
-- Indexes for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_request` (`requester_id`,`receiver_id`),
  ADD KEY `idx_friend_requests_requester` (`requester_id`),
  ADD KEY `idx_friend_requests_receiver` (`receiver_id`),
  ADD KEY `idx_friend_requests_status` (`status`);

--
-- Indexes for table `game_progress`
--
ALTER TABLE `game_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_game_progress` (`user_id`,`game_type`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_unread` (`user_id`,`is_read`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_notifications_created_at` (`created_at`);

--
-- Indexes for table `profile_view_cooldowns`
--
ALTER TABLE `profile_view_cooldowns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_viewer_viewed` (`viewer_username`,`viewed_username`);

--
-- Indexes for table `shard_transactions`
--
ALTER TABLE `shard_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `user_crescents`
--
ALTER TABLE `user_crescents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_crescent` (`giver_username`,`receiver_username`);

--
-- Indexes for table `user_essence`
--
ALTER TABLE `user_essence`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user` (`user_id`);

--
-- Indexes for table `user_fame`
--
ALTER TABLE `user_fame`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_user_fame_username` (`username`);

--
-- Indexes for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_game` (`user_id`,`game_type`);

--
-- Indexes for table `user_gwa`
--
ALTER TABLE `user_gwa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_game` (`user_id`,`game_type`);

--
-- Indexes for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_shards`
--
ALTER TABLE `user_shards`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_shards` (`user_id`);

--
-- Indexes for table `vocab_scores`
--
ALTER TABLE `vocab_scores`
  ADD PRIMARY KEY (`score_id`),
  ADD KEY `idx_scholar_date` (`scholar_id`,`date_played`),
  ADD KEY `idx_score` (`score`),
  ADD KEY `idx_scores_scholar_date` (`scholar_id`,`date_played`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `character_ownership`
--
ALTER TABLE `character_ownership`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `character_selections`
--
ALTER TABLE `character_selections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `friends`
--
ALTER TABLE `friends`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `friend_requests`
--
ALTER TABLE `friend_requests`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=367;

--
-- AUTO_INCREMENT for table `game_progress`
--
ALTER TABLE `game_progress`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_view_cooldowns`
--
ALTER TABLE `profile_view_cooldowns`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `shard_transactions`
--
ALTER TABLE `shard_transactions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `user_crescents`
--
ALTER TABLE `user_crescents`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=67;

--
-- AUTO_INCREMENT for table `user_essence`
--
ALTER TABLE `user_essence`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `user_fame`
--
ALTER TABLE `user_fame`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1360;

--
-- AUTO_INCREMENT for table `user_favorites`
--
ALTER TABLE `user_favorites`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user_gwa`
--
ALTER TABLE `user_gwa`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `user_settings`
--
ALTER TABLE `user_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `user_shards`
--
ALTER TABLE `user_shards`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `vocab_scores`
--
ALTER TABLE `vocab_scores`
  MODIFY `score_id` int NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `character_ownership`
--
ALTER TABLE `character_ownership`
  ADD CONSTRAINT `character_ownership_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `character_selections`
--
ALTER TABLE `character_selections`
  ADD CONSTRAINT `character_selections_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friends`
--
ALTER TABLE `friends`
  ADD CONSTRAINT `friends_ibfk_1` FOREIGN KEY (`user1_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friends_ibfk_2` FOREIGN KEY (`user2_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `friend_requests`
--
ALTER TABLE `friend_requests`
  ADD CONSTRAINT `friend_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `friend_requests_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `game_progress`
--
ALTER TABLE `game_progress`
  ADD CONSTRAINT `game_progress_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `shard_transactions`
--
ALTER TABLE `shard_transactions`
  ADD CONSTRAINT `shard_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_essence`
--
ALTER TABLE `user_essence`
  ADD CONSTRAINT `user_essence_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_favorites`
--
ALTER TABLE `user_favorites`
  ADD CONSTRAINT `user_favorites_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_gwa`
--
ALTER TABLE `user_gwa`
  ADD CONSTRAINT `user_gwa_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_settings`
--
ALTER TABLE `user_settings`
  ADD CONSTRAINT `user_settings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_shards`
--
ALTER TABLE `user_shards`
  ADD CONSTRAINT `user_shards_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
