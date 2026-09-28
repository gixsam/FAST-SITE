-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql102.infinityfree.com
-- Generation Time: May 29, 2026 at 12:08 AM
-- Server version: 11.4.11-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41442313_fast_site`
--

-- --------------------------------------------------------

--
-- Table structure for table `affiliate_partners`
--

CREATE TABLE `affiliate_partners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `category` varchar(50) DEFAULT 'General',
  `affiliate_link` varchar(1000) NOT NULL,
  `description` text DEFAULT NULL,
  `commission_info` varchar(200) DEFAULT NULL,
  `show_on_homepage` tinyint(1) DEFAULT 1,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_tasks`
--

CREATE TABLE `agent_tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `task_type` enum('auto','manual') DEFAULT 'manual',
  `reward_amount` decimal(10,2) DEFAULT 0.00,
  `deadline` date DEFAULT NULL,
  `max_per_agent` int(11) DEFAULT 1,
  `status` enum('active','ended') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_task_completions`
--

CREATE TABLE `agent_task_completions` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `proof` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` datetime DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `id` int(11) NOT NULL,
  `service_id` int(11) NOT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `user_name` varchar(255) NOT NULL,
  `user_phone` varchar(50) NOT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `nid_number` varchar(50) DEFAULT NULL,
  `passport_number` varchar(50) DEFAULT NULL,
  `driving_license` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `status` enum('pending','processing','approved','cancelled') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`id`, `service_id`, `fee`, `user_name`, `user_phone`, `user_email`, `nid_number`, `passport_number`, `driving_license`, `details`, `status`, `admin_notes`, `created_at`, `updated_at`) VALUES
(1, 5, '700.00', 'ALI', '01963608613', 'mo.dulalmiah@gmail.com', NULL, NULL, NULL, NULL, 'processing', 'HEHE', '2026-04-01 05:44:40', '2026-04-01 05:45:09'),
(2, 7, '0.00', 'cbnuebc', 'klkecn', 'qcln', 'cenj', 'ecljn', 'cqljn', 'qlenc', 'pending', NULL, '2026-04-01 12:00:22', '2026-04-01 12:00:22');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `sender` enum('user','chatbot','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `sender`, `message`, `is_read`, `created_at`) VALUES
(1, 'sess_3c7c49a7ba51bd7e0c5637ce753540e0', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 09:47:14'),
(2, 'sess_10ca69aebbb125712c81e0e6376d73a1', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 09:47:20'),
(3, 'sess_a6c326273633cf4cb5e15bd1f642ae26', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 09:52:00'),
(4, 'sess_7535d31e3ca358033f0e8b6b721b5f5c', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 10:32:45'),
(5, 'sess_0c4ed3a7c06342761f9328917631e3c0', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 10:39:05'),
(6, 'sess_e4dec701debd11c8d65ecedcf03f8ed5', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 10:45:20'),
(7, 'sess_467cc42994c6bdadf43423bd1fbcec3f', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 11:08:25'),
(8, 'sess_5f29edd8c5b3130eab60a725ea4377c7', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 11:08:26'),
(9, 'sess_7822dbd945e70b699dce2720e9c9491d', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 11:08:31'),
(10, 'sess_05b985c79ff06baa50c0310faee21ce5', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-01 11:58:55'),
(11, 'sess_6faa3a0db88d958f42032bc0ebfde807', 'chatbot', '👋 Hi! I\'m **MONU**, your Fast Sitee assistant.\n\nWhich government service do you need?\n• NID Correction / Apply\n• Driving License Correction / Apply/Renewal\n• Passport Correction / Apply/Renewal\n• Other Government Service\n\nJust type what you need!', 0, '2026-04-02 04:35:30'),
(12, 'sess_6faa3a0db88d958f42032bc0ebfde807', 'user', 'nid.', 0, '2026-04-02 04:35:43'),
(13, 'sess_6faa3a0db88d958f42032bc0ebfde807', 'chatbot', '✅ Got it! I\'ve identified your need for **NID Apply**.\n\nConnecting you with our agent **SHAMBHI** now — please hold on! 🙏\nType your details and SHAMBHI will reply shortly.', 0, '2026-04-02 04:35:43'),
(14, 'sess_6faa3a0db88d958f42032bc0ebfde807', 'user', 'ok', 0, '2026-04-02 04:36:01');

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `application_id` int(11) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_phone` varchar(50) DEFAULT NULL,
  `service_identified` varchar(255) DEFAULT NULL,
  `language` varchar(5) DEFAULT 'en',
  `status` enum('chatbot','waiting','with_agent') DEFAULT 'chatbot',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `session_id`, `application_id`, `user_name`, `user_phone`, `service_identified`, `language`, `status`, `created_at`, `updated_at`) VALUES
(1, 'sess_3c7c49a7ba51bd7e0c5637ce753540e0', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 09:47:14', '2026-04-01 09:47:14'),
(2, 'sess_10ca69aebbb125712c81e0e6376d73a1', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 09:47:20', '2026-04-01 09:47:20'),
(3, 'sess_a6c326273633cf4cb5e15bd1f642ae26', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 09:52:00', '2026-04-01 09:52:00'),
(4, 'sess_7535d31e3ca358033f0e8b6b721b5f5c', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 10:32:45', '2026-04-01 10:32:45'),
(5, 'sess_0c4ed3a7c06342761f9328917631e3c0', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 10:39:05', '2026-04-01 10:39:05'),
(6, 'sess_e4dec701debd11c8d65ecedcf03f8ed5', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 10:45:20', '2026-04-01 10:45:20'),
(7, 'sess_467cc42994c6bdadf43423bd1fbcec3f', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 11:08:25', '2026-04-01 11:08:25'),
(8, 'sess_5f29edd8c5b3130eab60a725ea4377c7', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 11:08:26', '2026-04-01 11:08:26'),
(9, 'sess_7822dbd945e70b699dce2720e9c9491d', NULL, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 11:08:31', '2026-04-01 11:08:31'),
(10, 'sess_05b985c79ff06baa50c0310faee21ce5', 2, NULL, NULL, NULL, 'en', 'chatbot', '2026-04-01 11:58:55', '2026-04-01 12:00:22'),
(11, 'sess_6faa3a0db88d958f42032bc0ebfde807', NULL, NULL, NULL, 'NID Apply', 'en', 'with_agent', '2026-04-02 04:35:30', '2026-04-02 04:35:43');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_settings`
--

CREATE TABLE `homepage_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `homepage_settings`
--

INSERT INTO `homepage_settings` (`setting_key`, `setting_value`) VALUES
('promo_title', '? Hot Deal'),
('promo_subtitle_1', 'MELBAT'),
('promo_subtitle_2', 'APK'),
('promo_url', 'https://omg10.com/4/10744356'),
('promo_image', 'https://fastsitee.wordpress.com/wp-content/uploads/2026/02/att.lhaeh6rlmszydbv5r8aj5rxosjlq2txh6jdeqd_dmcq.png.jpeg'),
('promo_code_text', '? Use promo code <code>ml_2165959</code> to get up to <strong>12,000 BDT</strong> welcome bonus on first deposit.'),
('logo_url', ''),
('site_name', 'Fast Sitee'),
('whatsapp_number', '+8801XXXXXXXXX'),
('user_tnc', 'Terms and Conditions will be published here.'),
('agent_tnc', 'Affiliate Agent Terms and Conditions will be published here.'),
('min_payout', '200'),
('default_commission_pct', '20'),
('business_hours', 'Saturday – Thursday, 9am – 6pm'),
('global_notice', 'Welcome to Fast Sitee! Exciting new offers available.');

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `fee` decimal(10,2) DEFAULT 0.00,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `section_name` varchar(100) DEFAULT 'অন্যান্য সেবা',
  `sort_order` int(11) DEFAULT 0,
  `logo_url` varchar(500) DEFAULT NULL,
  `govt_fee` decimal(10,2) DEFAULT 0.00,
  `processing_fee` decimal(10,2) DEFAULT 0.00,
  `affiliate_bonus_pct` decimal(5,2) DEFAULT 20.00,
  `show_on_homepage` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `fee`, `is_active`, `created_at`, `section_name`, `sort_order`, `logo_url`, `govt_fee`, `processing_fee`, `affiliate_bonus_pct`, `show_on_homepage`) VALUES
(1, 'NID Correction', 'Correction of info on National ID.', '500.00', 1, '2026-04-01 05:32:55', 'জাতীয় পরিচয়পত্র সেবা', 20, NULL, '0.00', '0.00', '20.00', 1),
(2, 'NID Apply', 'New National ID card application.', '1800.00', 1, '2026-04-01 05:32:55', 'জাতীয় পরিচয়পত্র সেবা', 10, NULL, '0.00', '0.00', '20.00', 1),
(3, 'Driving License Correction', 'Correction on Driving License.', '1500.00', 1, '2026-04-01 05:32:55', 'ড্রাইভিং লাইসেন্স সেবা', 20, NULL, '0.00', '0.00', '20.00', 1),
(4, 'Driving License Apply/Renewal', 'New or renewal of Driving License.', '1450.00', 1, '2026-04-01 05:32:55', 'ড্রাইভিং লাইসেন্স সেবা', 10, NULL, '0.00', '0.00', '20.00', 1),
(5, 'Passport Correction', 'Correction of info on Passport.', '700.00', 1, '2026-04-01 05:32:55', 'পাসপোর্ট সেবা', 20, NULL, '0.00', '0.00', '20.00', 1),
(6, 'Passport Apply/Renewal', 'New application or renewal of Passport.', '800.00', 1, '2026-04-01 05:32:55', 'পাসপোর্ট সেবা', 10, NULL, '0.00', '0.00', '20.00', 1),
(7, 'Other Government Service', 'Any other government-related assistance.', '0.00', 1, '2026-04-01 05:32:55', 'অন্যান্য সেবা', 10, NULL, '0.00', '0.00', '20.00', 1),
(8, 'WEBSITE BUILD UP', '8% off', '2430.00', 1, '2026-04-02 11:32:51', 'অন্যান্য সেবা', 0, NULL, '0.00', '0.00', '20.00', 1),
(9, 'Birth Certificate Apply', 'REQUIRED FILES', '1530.00', 1, '2026-04-26 08:25:28', '🪪 জাতীয় পরিচয়পত্র সেবা', 10, NULL, '0.00', '1530.00', '20.00', 1);

-- --------------------------------------------------------

--
-- Table structure for table `staff_users`
--

CREATE TABLE `staff_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `staff_users`
--

INSERT INTO `staff_users` (`id`, `username`, `password_hash`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$jrImLWq448yKCH0MxLAqae8WQCC2Ih7XhKl3QuyVgUXJukyHHp2uS', 'zillionprince6@gmail.com', 'admin', '2026-04-03 21:30:27'),
(2, 'MIM', '$2y$10$eJXmPvx1nmJOdUGRVrPj.eC5rVnU6Jhhf0ho7S8oucS1UhKHZ8BkS', NULL, 'staff', '2026-04-03 21:48:42');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `ref_code` varchar(20) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_pending_cash`
--

CREATE TABLE `user_pending_cash` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `source_order` varchar(50) DEFAULT NULL,
  `status` enum('pending','claimed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affiliate_partners`
--
ALTER TABLE `affiliate_partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent_tasks`
--
ALTER TABLE `agent_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent_task_completions`
--
ALTER TABLE `agent_task_completions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_task` (`task_id`),
  ADD KEY `idx_agent` (`agent_id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_app_service` (`service_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `homepage_settings`
--
ALTER TABLE `homepage_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `ref_code` (`ref_code`);

--
-- Indexes for table `user_pending_cash`
--
ALTER TABLE `user_pending_cash`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `affiliate_partners`
--
ALTER TABLE `affiliate_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_tasks`
--
ALTER TABLE `agent_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_task_completions`
--
ALTER TABLE `agent_task_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `staff_users`
--
ALTER TABLE `staff_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_pending_cash`
--
ALTER TABLE `user_pending_cash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `fk_app_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD CONSTRAINT `chat_messages_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `chat_sessions` (`session_id`) ON DELETE CASCADE;

--
-- Constraints for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD CONSTRAINT `chat_sessions_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
