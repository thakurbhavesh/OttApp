-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Sep 01, 2025 at 08:25 PM
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
-- Database: `app`
--

-- --------------------------------------------------------

--
-- Table structure for table `allowed_ips`
--

CREATE TABLE `allowed_ips` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` tinyint(1) DEFAULT 1 COMMENT '1 = Active, 0 = Inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `allowed_ips`
--

INSERT INTO `allowed_ips` (`id`, `ip_address`, `created_at`, `status`) VALUES
(1, '152.59.172.74 ', '2025-09-01 18:16:04', 1),
(2, '::1', '2025-09-01 18:22:35', 1);

-- --------------------------------------------------------

--
-- Table structure for table `auth_users`
--

CREATE TABLE `auth_users` (
  `auth_id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `role` enum('Admin','User') DEFAULT 'User',
  `created_at` datetime NOT NULL DEFAULT '2025-08-02 22:28:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `auth_users`
--

INSERT INTO `auth_users` (`auth_id`, `username`, `email`, `password`, `status`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$10$105FQu0Zhat1ha7ZuqM.FOvnTg1AHD6wFxgzdOz7v1y30dP6sucwG', 'active', 'Admin', '2025-08-02 22:33:09'),
(2, 'thakur', 'admin@admin.com', '$2y$10$7so75qw9/CQHjGE0MhKM2uIv00b6j5rqo1o8dzbR5nLQBHV2vEf5m', 'active', 'Admin', '2025-08-19 23:33:33');

-- --------------------------------------------------------

--
-- Table structure for table `cast_crew`
--

CREATE TABLE `cast_crew` (
  `cast_crew_id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` varchar(100) NOT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cast_crew`
--

INSERT INTO `cast_crew` (`cast_crew_id`, `content_id`, `name`, `role`, `image`) VALUES
(1, 4, 'Bhavesh', 'Actors', 'Hhh'),
(2, 4, 'Shivam Singh', 'Director', 'asdf');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `main_category_id` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`category_id`, `name`, `status`, `main_category_id`) VALUES
(1, 'Action', 'active', 1),
(2, 'Drama', 'active', 1),
(3, 'Comedy', 'active', 1),
(4, 'Crime', 'active', 1),
(9, 'Action', 'active', 3),
(10, 'Crime', 'active', 3),
(11, 'Romance', 'active', 3),
(12, 'Sci-Fi', 'active', 3),
(13, 'Adventure', 'active', 3),
(14, 'Fantasy', 'active', 3),
(15, 'Documentary', 'active', 3),
(16, 'Horror', 'active', 3),
(17, 'Independence', 'active', 3),
(18, 'Thriller', 'active', 3);

-- --------------------------------------------------------

--
-- Table structure for table `content`
--

CREATE TABLE `content` (
  `content_id` int(11) NOT NULL,
  `title` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `thumbnail_url` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `content_type` varchar(255) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `preference_id` int(11) DEFAULT NULL,
  `trailer_url` varchar(255) DEFAULT NULL,
  `banner` tinyint(1) DEFAULT 0 COMMENT '1 for active banner, 0 for inactive banner',
  `top_shows` tinyint(1) DEFAULT 0 COMMENT '1 for active top_shows, 0 for inactive top_shows',
  `binge_worthy` tinyint(1) DEFAULT 0 COMMENT '1 for Binge Worthy, 0 for not Binge Worthy',
  `bollywood_binge` tinyint(1) DEFAULT 0 COMMENT '1 for Bollywoord Binge, 0 for not Bollywood Binge',
  `dubbed_in_hindi` tinyint(1) DEFAULT 0 COMMENT '1 for Dubbed In Hindi, 0 for not Dubbed In Hindi',
  `plan` varchar(20) DEFAULT 'free' COMMENT 'Plan type (e.g., free, paid)',
  `industry` varchar(100) DEFAULT NULL COMMENT 'Industry (e.g., Hollywood, Bollywood)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content`
--

INSERT INTO `content` (`content_id`, `title`, `description`, `category_id`, `thumbnail_url`, `video_url`, `duration`, `release_date`, `created_at`, `status`, `content_type`, `language_id`, `preference_id`, `trailer_url`, `banner`, `top_shows`, `binge_worthy`, `bollywood_binge`, `dubbed_in_hindi`, `plan`, `industry`) VALUES
(4, 'Kahani', '<p>Inspector Pawan Singh is drawn into a <strong>psychological </strong>duel with the enigmatic and seductive Mona. As the lines between truth and lies blur, their dangerous connection spirals into a gripping game of control, obsession, and manipulation, where only one will come out on tops bahvesh</p><p>&nbsp;</p>', 4, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRMujesLWftdxM6DVObQ9rW8BOdW8bZAvKUzA&s', '', 90, '2025-08-23', '2025-08-23 12:22:55', 'active', '1', 1, 1, 'https://youtu.be/yEJQpoHfw0s?list=RDMRtRcTfszjY', 1, 0, 0, 0, 0, 'paid', 'Bhojpuri'),
(5, 'Rose Garden', 'AAA', 2, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRMujesLWftdxM6DVObQ9rW8BOdW8bZAvKUzA&s', NULL, 2, '2025-09-06', '2025-08-24 08:19:08', 'active', '1', 1, 1, 'https://youtu.be/yEJQpoHfw0s?list=RDMRtRcTfszjY', 1, 0, 1, 0, 1, 'free', 'Marathi'),
(6, 'Once Upon A Time', '<p>ghhhh</p>', 9, 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRMujesLWftdxM6DVObQ9rW8BOdW8bZAvKUzA&s', NULL, 25, '2025-08-26', '2025-08-24 08:26:19', 'active', '3', 1, 5, 'https://youtu.be/yEJQpoHfw0s?list=RDMRtRcTfszjY', 0, 1, 0, 1, 0, 'paid', 'Hollywood');

-- --------------------------------------------------------

--
-- Table structure for table `content_preferences`
--

CREATE TABLE `content_preferences` (
  `preference_id` int(11) NOT NULL,
  `preference_name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `content_preferences`
--

INSERT INTO `content_preferences` (`preference_id`, `preference_name`, `description`, `status`, `created_at`) VALUES
(1, 'Adult', '18+', 1, '2025-08-19 17:42:15'),
(2, 'Kids', NULL, 1, '2025-08-19 17:42:15'),
(3, 'Family', NULL, 1, '2025-08-19 17:42:15'),
(4, 'Teen', NULL, 1, '2025-08-19 17:42:15'),
(5, 'General', NULL, 1, '2025-08-19 17:42:15');

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE `languages` (
  `language_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `languages`
--

INSERT INTO `languages` (`language_id`, `name`, `status`) VALUES
(1, 'Hindi', 'active'),
(2, 'English', 'active'),
(3, 'Punjabi', 'active'),
(4, 'Kannada', 'active'),
(5, 'Malayalam', 'active'),
(6, 'Telugu', 'active'),
(7, 'Bhojpuri', 'active');

-- --------------------------------------------------------

--
-- Table structure for table `main_categories`
--

CREATE TABLE `main_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `main_categories`
--

INSERT INTO `main_categories` (`category_id`, `name`, `status`) VALUES
(1, 'Webseries', 'active'),
(2, 'Tv Shows', 'active'),
(3, 'Movies', 'active'),
(4, 'Audio', 'active'),
(5, 'Sports', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `manage_selected`
--

CREATE TABLE `manage_selected` (
  `id` int(11) NOT NULL,
  `content_id` int(11) NOT NULL,
  `season_number` int(11) DEFAULT 0,
  `episode_number` int(11) DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `length` int(11) DEFAULT NULL,
  `release_date` date DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `manage_selected`
--

INSERT INTO `manage_selected` (`id`, `content_id`, `season_number`, `episode_number`, `title`, `description`, `thumbnail_url`, `video_url`, `length`, `release_date`, `status`, `created_at`) VALUES
(1, 4, 0, 0, 'AA', '<p>Hii</p>', 'A', 'A', 2, '2025-08-24', 'active', '2025-08-24 08:30:49'),
(2, 5, 1, 1, 'Test', '<p>Shyam</p>', 'helo', 'sdj', 22, '2025-08-21', 'active', '2025-08-24 08:34:03'),
(3, 5, 1, 1, 'A', '<p>rAM</p>', 'AAAAA', 'A', 5, '2025-08-28', 'active', '2025-08-24 08:45:59'),
(4, 6, 0, 0, 'Once Upon A Time In Mumbai', '<p>aaaa</p>', 'Once Upon A Time In Mumbaai', 'Once Upon A Time In Mumbaai', 22, '2025-08-28', 'active', '2025-08-24 09:10:30');

-- --------------------------------------------------------

--
-- Table structure for table `media`
--

CREATE TABLE `media` (
  `media_id` int(11) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` datetime NOT NULL DEFAULT '2025-08-02 22:15:00',
  `type` enum('movie','webseries','episode') NOT NULL DEFAULT 'movie',
  `parent_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `media`
--

INSERT INTO `media` (`media_id`, `url`, `title`, `file_path`, `status`, `created_at`, `type`, `parent_id`) VALUES
(1, 'https://grok.com/', 'Cricket', 'uploads/688e4211c49d6_MMPC-008.pdf', 'active', '2025-08-02 18:51:29', 'movie', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `subscription_history`
--

CREATE TABLE `subscription_history` (
  `history_id` int(11) NOT NULL COMMENT 'Unique ID for each history record',
  `user_id` int(11) NOT NULL COMMENT 'Foreign key to users table',
  `subscription_status` varchar(20) NOT NULL COMMENT 'Subscription status (free, paid)',
  `subscription_end_date` datetime DEFAULT NULL COMMENT 'End date of the subscription',
  `price` decimal(10,2) DEFAULT 0.00 COMMENT 'Price of the subscription (e.g., in INR)',
  `change_date` datetime DEFAULT current_timestamp() COMMENT 'Date when the subscription status was changed',
  `changed_by` varchar(50) DEFAULT NULL COMMENT 'User or system that made the change (e.g., admin username)',
  `notes` text DEFAULT NULL COMMENT 'Additional notes about the change'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscription_history`
--

INSERT INTO `subscription_history` (`history_id`, `user_id`, `subscription_status`, `subscription_end_date`, `price`, `change_date`, `changed_by`, `notes`) VALUES
(1, 2, 'paid', '2025-12-15 23:59:59', 499.00, '2025-09-01 22:49:07', 'admin1', 'Upgraded to premium plan'),
(2, 3, 'paid', '2025-08-15 23:59:59', 299.00, '2025-09-01 22:49:07', 'admin1', 'Initial paid subscription'),
(3, 3, 'free', NULL, 458.00, '2025-09-01 22:49:07', 'admin1', 'Subscription expired, reverted to free');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL COMMENT 'User ID',
  `username` varchar(50) NOT NULL COMMENT 'User username',
  `email` varchar(100) NOT NULL COMMENT 'User email address',
  `subscription_status` varchar(20) DEFAULT 'free' COMMENT 'User subscription status (free, paid)',
  `subscription_end_date` datetime DEFAULT NULL COMMENT 'Subscription expiration date',
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp() COMMENT 'User creation date'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `email`, `subscription_status`, `subscription_end_date`, `status`, `created_at`) VALUES
(1, 'user1', 'user1@example.com', 'free', NULL, 'active', '2025-09-01 22:16:06'),
(2, 'user2', 'user2@example.com', 'paid', '2025-12-15 23:59:59', 'active', '2025-09-01 22:16:06'),
(3, 'user3', 'user3@example.com', 'paid', '2025-08-15 23:59:59', 'inactive', '2025-09-01 22:16:06'),
(4, 'user4', 'user4@example.com', 'free', NULL, 'active', '2025-09-01 22:16:06');

-- --------------------------------------------------------

--
-- Table structure for table `users_old`
--

CREATE TABLE `users_old` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `subscription_status` enum('active','inactive') DEFAULT 'inactive',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('active','inactive') NOT NULL DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_old`
--

INSERT INTO `users_old` (`user_id`, `username`, `email`, `password`, `subscription_status`, `created_at`, `status`) VALUES
(1, 'admin', 'admin@ott.com', '$2y$10$0iQz3Jq3jX3Y3Z3X3Y3X3u3X3Y3X3Y3X3Y3X3Y3X3Y3X3Y3X3Y3', 'active', '2025-08-02 13:14:03', 'active'),
(2, 'bhavesh', 'singhbhavesh682@gmail.com', '$2y$10$3jeU6li/FJIFr5QkQrfczeeVF3qXkieOY7Qz2oXgdidV6/8XZtQLK', 'inactive', '2025-08-02 13:32:32', 'inactive');

-- --------------------------------------------------------

--
-- Table structure for table `watch_history`
--

CREATE TABLE `watch_history` (
  `history_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `content_id` int(11) DEFAULT NULL,
  `watch_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `allowed_ips`
--
ALTER TABLE `allowed_ips`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `auth_users`
--
ALTER TABLE `auth_users`
  ADD PRIMARY KEY (`auth_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `cast_crew`
--
ALTER TABLE `cast_crew`
  ADD PRIMARY KEY (`cast_crew_id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`),
  ADD KEY `fk_main_category` (`main_category_id`);

--
-- Indexes for table `content`
--
ALTER TABLE `content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `fk_language` (`language_id`),
  ADD KEY `fk_preference` (`preference_id`);

--
-- Indexes for table `content_preferences`
--
ALTER TABLE `content_preferences`
  ADD PRIMARY KEY (`preference_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`language_id`);

--
-- Indexes for table `main_categories`
--
ALTER TABLE `main_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `manage_selected`
--
ALTER TABLE `manage_selected`
  ADD PRIMARY KEY (`id`),
  ADD KEY `content_id` (`content_id`);

--
-- Indexes for table `media`
--
ALTER TABLE `media`
  ADD PRIMARY KEY (`media_id`);

--
-- Indexes for table `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `users_old`
--
ALTER TABLE `users_old`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `content_id` (`content_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `allowed_ips`
--
ALTER TABLE `allowed_ips`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `auth_users`
--
ALTER TABLE `auth_users`
  MODIFY `auth_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `cast_crew`
--
ALTER TABLE `cast_crew`
  MODIFY `cast_crew_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `content`
--
ALTER TABLE `content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `content_preferences`
--
ALTER TABLE `content_preferences`
  MODIFY `preference_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `main_categories`
--
ALTER TABLE `main_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `manage_selected`
--
ALTER TABLE `manage_selected`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `media`
--
ALTER TABLE `media`
  MODIFY `media_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `subscription_history`
--
ALTER TABLE `subscription_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Unique ID for each history record', AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'User ID', AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users_old`
--
ALTER TABLE `users_old`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `watch_history`
--
ALTER TABLE `watch_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cast_crew`
--
ALTER TABLE `cast_crew`
  ADD CONSTRAINT `cast_crew_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE;

--
-- Constraints for table `content`
--
ALTER TABLE `content`
  ADD CONSTRAINT `content_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`),
  ADD CONSTRAINT `fk_language` FOREIGN KEY (`language_id`) REFERENCES `languages` (`language_id`),
  ADD CONSTRAINT `fk_preference` FOREIGN KEY (`preference_id`) REFERENCES `content_preferences` (`preference_id`);

--
-- Constraints for table `manage_selected`
--
ALTER TABLE `manage_selected`
  ADD CONSTRAINT `manage_selected_ibfk_1` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`) ON DELETE CASCADE;

--
-- Constraints for table `subscription_history`
--
ALTER TABLE `subscription_history`
  ADD CONSTRAINT `subscription_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `watch_history`
--
ALTER TABLE `watch_history`
  ADD CONSTRAINT `watch_history_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users_old` (`user_id`),
  ADD CONSTRAINT `watch_history_ibfk_2` FOREIGN KEY (`content_id`) REFERENCES `content` (`content_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
