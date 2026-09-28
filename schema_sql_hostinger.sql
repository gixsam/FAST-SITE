-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Generation Time: Jul 27, 2026 at 02:43 AM
-- Server version: 11.8.8-MariaDB-log
-- PHP Version: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `u422364295_db`
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
-- Table structure for table `agents`
--

CREATE TABLE `agents` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `ref_code` varchar(20) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `status` enum('pending','active','suspended') DEFAULT 'pending',
  `total_earned` decimal(10,2) DEFAULT 0.00,
  `total_withdrawn` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `whatsapp` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `nid` varchar(255) DEFAULT NULL,
  `etin` varchar(255) DEFAULT NULL,
  `passport` varchar(255) DEFAULT NULL,
  `driving_license` varchar(255) DEFAULT NULL,
  `extra_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `agents`
--

INSERT INTO `agents` (`id`, `name`, `phone`, `email`, `ref_code`, `password_hash`, `status`, `total_earned`, `total_withdrawn`, `created_at`, `whatsapp`, `facebook`, `instagram`, `twitter`, `youtube`, `profile_pic`, `dob`, `gender`, `nid`, `etin`, `passport`, `driving_license`, `extra_details`) VALUES
(1, 'Sadman Hossain', '01612669922', 'zillionprince6@gmail.com', 'ADMIN', '$2y$10$jK/wigVIj4eG/leKk8u79OtJUHbG73kJwxszOBWHwoM8OcRqV.3xK', 'active', 0.00, 0.00, '2026-07-13 16:45:31', '01612669922', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `agent_commissions`
--

CREATE TABLE `agent_commissions` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `application_id` int(11) DEFAULT 0,
  `order_ref` varchar(50) DEFAULT NULL,
  `service_name` varchar(255) DEFAULT NULL,
  `order_fee` decimal(10,2) DEFAULT 0.00,
  `commission_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `agent_payouts`
--

CREATE TABLE `agent_payouts` (
  `id` int(11) NOT NULL,
  `agent_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `bkash_number` varchar(50) NOT NULL,
  `status` enum('pending','paid','rejected') DEFAULT 'pending',
  `admin_note` text DEFAULT NULL,
  `requested_at` timestamp NULL DEFAULT current_timestamp(),
  `paid_at` timestamp NULL DEFAULT NULL
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

--
-- Dumping data for table `agent_tasks`
--

INSERT INTO `agent_tasks` (`id`, `title`, `description`, `task_type`, `reward_amount`, `deadline`, `max_per_agent`, `status`, `created_at`) VALUES
(1, 'COMPLETE SIGNING-UP PROFILE', 'Get the cash by 100% completing signing up and fill up all the blank box', 'manual', 10.00, '2026-07-14', 1, 'active', '2026-07-13 16:55:31'),
(2, 'SHARE ONE POST ON FACEBOOK', 'SHARE ONE POST ON FACEBOOK AND GET 20 COIN', 'manual', 20.00, '2026-07-14', 1, 'active', '2026-07-13 17:20:52'),
(3, 'SHARE ONE POST ON FACEBOOK', 'SHARE ONE POST ON FACEBOOK AND GET 20 COIN', 'manual', 20.00, '2026-07-14', 1, 'active', '2026-07-13 17:21:45');

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
-- Table structure for table `api_invoices`
--

CREATE TABLE `api_invoices` (
  `id` int(11) NOT NULL,
  `api_partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('unpaid','paid') DEFAULT 'unpaid',
  `paid_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_partners`
--

CREATE TABLE `api_partners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `api_key` varchar(100) NOT NULL,
  `webhook_url` varchar(500) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `total_orders` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `partner_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `api_usage_log`
--

CREATE TABLE `api_usage_log` (
  `id` int(11) NOT NULL,
  `api_partner_id` int(11) NOT NULL,
  `endpoint` varchar(100) DEFAULT NULL,
  `request_data` text DEFAULT NULL,
  `response_data` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
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
(1, 5, 700.00, 'ALI', '01963608613', 'mo.dulalmiah@gmail.com', NULL, NULL, NULL, NULL, 'processing', 'HEHE', '2026-04-01 05:44:40', '2026-04-01 05:45:09'),
(2, 7, 0.00, 'cbnuebc', 'klkecn', 'qcln', 'cenj', 'ecljn', 'cqljn', 'qlenc', 'pending', NULL, '2026-04-01 12:00:22', '2026-04-01 12:00:22');

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
-- Table structure for table `coin_transactions`
--

CREATE TABLE `coin_transactions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('deposit','hold','release','refund','withdrawal') NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `reference` varchar(255) DEFAULT NULL,
  `status` enum('pending','completed','failed') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `coin_wallets`
--

CREATE TABLE `coin_wallets` (
  `user_id` int(11) NOT NULL,
  `balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coin_wallets`
--

INSERT INTO `coin_wallets` (`user_id`, `balance`) VALUES
(1, 0.00),
(2, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `deposit_requests`
--

CREATE TABLE `deposit_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `sender_number` varchar(50) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `screenshot_url` varchar(500) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dropship_connections`
--

CREATE TABLE `dropship_connections` (
  `id` int(11) NOT NULL,
  `display_name` varchar(100) NOT NULL,
  `provider` varchar(50) NOT NULL,
  `api_key` text NOT NULL,
  `base_endpoint` varchar(500) DEFAULT NULL,
  `sync_schedule` varchar(50) DEFAULT 'manual',
  `default_status` varchar(20) DEFAULT 'draft',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `escrow_transactions`
--

CREATE TABLE `escrow_transactions` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `buyer_id` int(11) NOT NULL,
  `seller_shop_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(10) DEFAULT 'BDT',
  `escrow_status` varchar(50) DEFAULT 'held',
  `release_date` datetime DEFAULT NULL,
  `dispute_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `gixsam_settings`
--

CREATE TABLE `gixsam_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `gixsam_settings`
--

INSERT INTO `gixsam_settings` (`setting_key`, `setting_value`, `updated_at`) VALUES
('about_bio_1', 'Sadman Hossain Sayam is an ambitious Bangladeshi entrepreneur leading Best Force Ltd (Security & Logistics) and Best Travel (Global Tourism). With a vision to revolutionize digital marketplaces and security, he oversees an interconnected ecosystem of 7 enterprises.', '2026-07-26 23:08:55'),
('about_bio_2', 'Driven by innovation and trust, his mission is to build seamless B2B & B2C platforms that empower local Bangladeshis with world-class services.', '2026-07-26 23:08:55'),
('about_heading', 'Pioneering Excellence Across Security & Global Travel', '2026-07-26 23:08:55'),
('about_quote', 'Leadership is not about being in charge. It is about taking care of those in your charge.', '2026-07-26 23:08:55'),
('email', 'khangroup01@gmail.com', '2026-07-26 23:08:55'),
('exp_years', '10+', '2026-07-26 23:08:55'),
('facebook_url', 'https://www.facebook.com/share/18QWLZABMs/', '2026-07-26 23:08:55'),
('hero_headline', 'Managing Director – Best Force Ltd & Best Travel', '2026-07-26 23:08:55'),
('hero_location', 'Dhaka, Bangladesh', '2026-07-26 23:08:55'),
('hero_name', 'Sadman Hossain Saya', '2026-07-26 23:08:55'),
('hero_photo_url', 'https://gixsam.best-travel.ltd/assets/images/portrait.jpg', '2026-07-26 23:08:55'),
('hero_subtitle', 'Dynamic Entrepreneur, Global Explorer & Visionary Leader', '2026-07-26 23:08:55'),
('instagram_url', 'https://www.instagram.com/sadman_sayam/', '2026-07-26 23:08:55'),
('phone', '+880 1627-127534', '2026-07-26 23:08:55'),
('twitter_url', 'https://x.com/sadmansaya12282', '2026-07-26 23:08:55'),
('ventures_count', '7+', '2026-07-26 23:08:55'),
('whatsapp', '8801627127534', '2026-07-26 23:08:55'),
('youtube_url', 'https://youtube.com/@princesayam6', '2026-07-26 23:08:55');

-- --------------------------------------------------------

--
-- Table structure for table `homepage_settings`
--

CREATE TABLE `homepage_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `homepage_settings`
--

INSERT INTO `homepage_settings` (`setting_key`, `setting_value`) VALUES
('admin_header_name', 'FAST SITE'),
('admin_header_tag', 'OVERPOWERED FUNCTIONAL SYSTEM'),
('affi_bangla_url', 'http://affibangla.best-travel.ltd'),
('agent_tnc', 'Affiliate Agent Terms and Conditions will be published here.'),
('banner_custom_html', ''),
('banner_media_path', ''),
('banner_media_paths', '[]'),
('banner_redirect_url', ''),
('banner_type', 'photo'),
('bkash_api_mode', 'sandbox'),
('bkash_app_key', ''),
('bkash_app_secret', ''),
('bkash_password', ''),
('bkash_qr_url', 'uploads/branding/bkash_qr_url_1783964640_9b44bd129cf3ac2a.jpg'),
('bkash_username', ''),
('business_hours', 'Saturday – Thursday, 9am – 6pm'),
('cashback_pct', '2'),
('chatbot_name_affi_bangla', 'Affi Bangla Deal Finder'),
('chatbot_name_ayra_mart', 'Ayra Mart Fashion Bot'),
('chatbot_name_best_travel', 'Best Travel Guide'),
('chatbot_name_enzor', 'Enzor Motors Advisor'),
('chatbot_name_fast_site', 'Fast Site Assistant'),
('coin_name', 'Coins'),
('default_commission_pct', '5'),
('facebook_url', ''),
('gemini_api_key', ''),
('global_notice', 'Welcome to Fast Site! Exciting new offers available.'),
('homepage_label_blogs', ''),
('homepage_label_features', 'WHY YOU CHOOSE US'),
('homepage_label_gov_slider', 'GOVERMENT ONLINE SERVICE'),
('homepage_label_our_services', 'OUR SERVICE'),
('homepage_label_overview', 'Welcome/Overview'),
('homepage_label_partners', 'BECOME PARTSNERS'),
('homepage_label_track', ''),
('homepage_label_travel_slider', 'TRAVEL SERVICE'),
('homepage_label_trending', 'TRENDING SERVICE'),
('homepage_sections_order', 'hero,overview,promo,search,trending,gov_slider,travel_slider,our_services,features,partners,track,blogs'),
('homepage_sections_visibility', 'hero,overview,promo,search,trending,gov_slider,travel_slider,our_services,features,partners,track,blogs'),
('homepage_title_blogs', ''),
('homepage_title_features', ''),
('homepage_title_gov_slider', ''),
('homepage_title_our_services', ''),
('homepage_title_overview', ''),
('homepage_title_partners', ''),
('homepage_title_track', ''),
('homepage_title_travel_slider', ''),
('homepage_title_trending', ''),
('instagram_url', ''),
('logo_url', ''),
('manual_payment_number', '01337320544'),
('marketplace_whatsapp', '01337320544'),
('min_payout', '200'),
('nav_positioning', 'dashboard,service,all_user,partners,directory,payouts,give_task,chat,settings,view_option,access_control'),
('nav_visibility', 'dashboard,service,all_user,partners,payouts,chat,settings,view_option'),
('promo_code_text', '? Use promo code <code>ml_2165959</code> to get up to <strong>12,000 BDT</strong> welcome bonus on first deposit.'),
('promo_image', 'https://fastsitee.wordpress.com/wp-content/uploads/2026/02/att.lhaeh6rlmszydbv5r8aj5rxosjlq2txh6jdeqd_dmcq.png.jpeg'),
('promo_subtitle_1', 'MELBAT'),
('promo_subtitle_2', 'APK'),
('promo_title', '? Hot Deal'),
('promo_url', 'https://omg10.com/4/10744356'),
('rss_feed_url', 'https://www.prothomalo.com/feed'),
('shipping_dhaka_in', '80'),
('shipping_dhaka_out', '120'),
('shipping_soft', '20'),
('site_name', 'FAST SITE'),
('staff_whatsapp_number', ''),
('sub_nav_pos_all_user', 'users,staff,api_partners,agents,affiliates,shops,admin'),
('sub_nav_pos_chat', 'client,teammate'),
('sub_nav_pos_give_task', 'staff,agents,affiliates'),
('sub_nav_pos_partners', 'api_partners,agents,shops,deposits,withdrawals,disputes,settings'),
('sub_nav_pos_settings', 'branding,profile,tnc,rename,all_sections'),
('sub_nav_pos_view_option', 'user_site,user_dash,staff_dash,api_dash,agent_dash,affiliate_dash'),
('sub_nav_vis_all_user', 'users,staff,api_partners,agents,affiliates,shops,admin'),
('sub_nav_vis_chat', 'client,teammate'),
('sub_nav_vis_give_task', 'staff,agents,affiliates'),
('sub_nav_vis_partners', 'api_partners,agents,shops,deposits,withdrawals,disputes,settings'),
('sub_nav_vis_settings', 'branding,profile,tnc,rename,all_sections'),
('sub_nav_vis_view_option', 'user_site,user_dash,staff_dash,api_dash,agent_dash,affiliate_dash'),
('twitter_url', ''),
('user_tnc', 'Terms and Conditions will be published here.'),
('wc_away_flag', ''),
('wc_away_score', '0'),
('wc_away_team', 'TBD'),
('wc_home_flag', ''),
('wc_home_score', '0'),
('wc_home_team', 'TBD'),
('wc_kickoff_time', ''),
('wc_match_status', 'none'),
('wc_stadium', ''),
('wc_time_elapsed', ''),
('whatsapp_number', '+8801337320544'),
('youtube_url', '');

-- --------------------------------------------------------

--
-- Table structure for table `internal_messages`
--

CREATE TABLE `internal_messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) DEFAULT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `jobs`
--

CREATE TABLE `jobs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `budget` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'open',
  `awarded_to` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `job_bids`
--

CREATE TABLE `job_bids` (
  `id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `bid_amount` decimal(10,2) NOT NULL,
  `proposal` text NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_conversations`
--

CREATE TABLE `marketplace_conversations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `marketplace_messages`
--

CREATE TABLE `marketplace_messages` (
  `id` int(11) NOT NULL,
  `conversation_id` int(11) NOT NULL,
  `sender_type` enum('user','partner') NOT NULL,
  `sender_id` int(11) NOT NULL,
  `message` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL,
  `business_name` varchar(255) NOT NULL,
  `owner_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nid` varchar(100) DEFAULT NULL,
  `trade_license` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `cover_pic` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `payout_method` enum('bkash','nagad') DEFAULT 'bkash',
  `payout_account` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','suspended') DEFAULT 'pending',
  `rating` decimal(2,1) DEFAULT 0.0,
  `total_orders` int(11) DEFAULT 0,
  `total_earned` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `is_official` tinyint(1) DEFAULT 0,
  `tags` text DEFAULT NULL,
  `seller_level` int(11) DEFAULT 1,
  `registration_number` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `business_name`, `owner_name`, `email`, `phone`, `password_hash`, `nid`, `trade_license`, `profile_pic`, `cover_pic`, `description`, `payout_method`, `payout_account`, `status`, `rating`, `total_orders`, `total_earned`, `created_at`, `is_official`, `tags`, `seller_level`, `registration_number`, `user_id`, `district`, `is_hidden`) VALUES
(1, 'FAST SITE', 'Sadman Hossain', 'info.fastsite@gmail.com', '01337320544', '$2y$10$bFMwpkRLeh92EvS4rqJfe.9rFiOrcYBMZfuAu1EtTMwlEyVwFIUGq', NULL, NULL, 'fast_site_logo_new.png', NULL, 'I WILL SELL SERVICE AND PRODUCT', 'bkash', '01337320544', 'approved', 0.0, 0, 0.00, '2026-06-27 03:57:11', 1, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(2, 'AMS Digital', 'MD Ayub Mojumdar Saimun', 'aamm449922@gmail.com', '01956012103', '$2y$10$fOtYDDW2.Yu0DwXp2L5VGe95lAhJmF/euLcKXKHxlKj3.ELBCg3a.', NULL, NULL, NULL, NULL, 'AMS Digital', 'bkash', '01959521655', 'suspended', 0.0, 0, 0.00, '2026-06-27 07:46:37', 0, NULL, 1, NULL, NULL, NULL, 0),
(3, 'AMS Digital', 'MD Ayub Mojumdar Saimun', 'mmaa662244@gmail.com', '01959521655', '$2y$10$woGgkWPYRmySW2h8WRYUzeealhHnTy3U5yXhYWr27qJvwwHQRhIza', NULL, NULL, 'profile_1782653204_150.jpg', 'cover_1782665493_944.png', NULL, 'bkash', '01959521655', 'approved', 0.0, 0, 0.00, '2026-06-27 07:49:44', 0, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(4, 'Best Travel', 'Admin', 'besttravel@partner.local', 'admin', '$2y$10$5rV5Ie5LhYHj8YZw3AoOdOFkGLktcWjzGVfggDpfvlVJ8Bh51Y/VO', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-06-30 10:34:27', 0, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(5, 'Ayra Mart', 'Admin', 'ayramart@partner.local', 'admin', '$2y$10$5rV5Ie5LhYHj8YZw3AoOdOFkGLktcWjzGVfggDpfvlVJ8Bh51Y/VO', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-06-30 10:34:27', 0, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(6, 'Enzor Motors', 'Admin', 'enzormotors@partner.local', 'admin', '$2y$10$5rV5Ie5LhYHj8YZw3AoOdOFkGLktcWjzGVfggDpfvlVJ8Bh51Y/VO', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-06-30 10:34:27', 0, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(7, 'Affi Bangla', 'Admin', 'affibangla@partner.local', 'admin', '$2y$10$5rV5Ie5LhYHj8YZw3AoOdOFkGLktcWjzGVfggDpfvlVJ8Bh51Y/VO', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-06-30 10:34:27', 0, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(8, 'Fast Site Official', 'Admin', 'official@fastsite.com', '01337320544', '$2y$10$9HhaXx12Ao1JKaK.q/ctVueg9vdDyw6gq.iNPQa4EYy9qDnpbhfHi', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-07-13 10:40:07', 1, NULL, 1, 'FS-SHOP-00000', NULL, NULL, 0),
(9, 'ADIBA ENTERPRISE', 'Md Dulal', 'mddulalmia8373@gmail.com', '01951029485', '$2y$10$C0x6W7zpVmNl5BeYKcaYA..rWgs0YgLOQJBfhi3O/lHpfIvF/jEa6', 'nid_1784166971_e70b07b7d78ba7dd.jpg', NULL, 'profile_1784166971_49230199bdfe1e14.jpg', NULL, 'I WANT TO SELL SAND, ALL OVER BANGLADESH.', 'bkash', '01951029485', 'approved', 0.0, 0, 0.00, '2026-07-16 01:56:11', 0, NULL, 1, 'FS-SHOP-00006', 6, NULL, 0),
(10, 'Best Travel', 'Sayam Khan', 'support@best-travel.ltd', '01866686524', '$2y$10$6.kF0pQ/WmxfBgxKkRwU/uGyMJu8PbMi9TZ6k/JPDGw2/3eKBKYpO', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-07-20 11:23:51', 0, NULL, 1, NULL, NULL, NULL, 0),
(11, 'AT Ayra Mart', 'Sayam Khan', 'support@atayramart.com', '01866686524', '$2y$10$uo6Wg0P9GZ.waU4ABIgdxOZ.dDoL4Z2FB/OkcGZA/d.CsaUKMqjRa', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-07-20 11:23:51', 0, NULL, 1, NULL, NULL, NULL, 0),
(12, 'Affi Bangla', 'Sayam Khan', 'support@affibangla.best-travel.ltd', '01866686524', '$2y$10$dqZySO6LT7Qba3Dzb2UuzuE79tD6BQh1ih3MIo8cclKzLx9FFuZo6', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-07-20 11:23:51', 0, NULL, 1, NULL, NULL, NULL, 0),
(13, 'Enzor Motors', 'Sayam Khan', 'support@enzor.best-travel.ltd', '01866686524', '$2y$10$Uy2L1TDtVgrQ6cucDIVlxOqX2ebSrLap4T7Js/kfFp71PU/snBm0a', NULL, NULL, NULL, NULL, NULL, 'bkash', NULL, 'approved', 0.0, 0, 0.00, '2026-07-20 11:23:51', 0, NULL, 1, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `partner_disputes`
--

CREATE TABLE `partner_disputes` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `raised_by` enum('customer','partner') NOT NULL,
  `reason` text DEFAULT NULL,
  `evidence_customer` varchar(500) DEFAULT NULL,
  `evidence_partner` varchar(500) DEFAULT NULL,
  `admin_decision` enum('refund_customer','release_to_partner','pending') DEFAULT 'pending',
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_kyc`
--

CREATE TABLE `partner_kyc` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `shop_id` int(11) NOT NULL,
  `id_card_url` text NOT NULL,
  `live_selfie_url` text NOT NULL,
  `kyc_status` varchar(50) DEFAULT 'pending',
  `verified_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_orders`
--

CREATE TABLE `partner_orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `total_coins` decimal(10,2) NOT NULL,
  `status` enum('pending','accepted','in_progress','waiting_confirmation','completed','cancelled','disputed') DEFAULT 'pending',
  `partner_proof` varchar(500) DEFAULT NULL,
  `customer_confirmed_at` datetime DEFAULT NULL,
  `cancelled_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `payment_method` varchar(255) DEFAULT NULL,
  `payment_status` varchar(255) DEFAULT NULL,
  `sender_number` varchar(255) DEFAULT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  `gateway_ref` varchar(255) DEFAULT NULL,
  `delivery_location` varchar(255) DEFAULT NULL,
  `delivery_charge` varchar(255) DEFAULT NULL,
  `shipping_address` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_products`
--

CREATE TABLE `partner_products` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `scheduled_at` datetime DEFAULT NULL,
  `is_published` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `listing_type` varchar(50) DEFAULT 'product',
  `stock` int(11) DEFAULT -1,
  `shipping_type` varchar(50) DEFAULT 'digital',
  `estimated_time` varchar(255) DEFAULT '',
  `required_docs` text DEFAULT '',
  `redirect_url` text DEFAULT NULL,
  `original_website` text DEFAULT NULL,
  `is_hidden` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_products`
--

INSERT INTO `partner_products` (`id`, `partner_id`, `title`, `description`, `price`, `category`, `scheduled_at`, `is_published`, `created_at`, `listing_type`, `stock`, `shipping_type`, `estimated_time`, `required_docs`, `redirect_url`, `original_website`, `is_hidden`) VALUES
(1, 1, 'NID APPLY', 'WE APPLY APPLICATION FOR NID.', 1200.00, 'General', '2026-06-27 22:22:00', 1, '2026-06-27 04:23:22', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(13, 3, '🌿 অনলাইনে ভূমি কর (খাজনা) জমা দেওয়ার সেবা', '🌿 অনলাইনে ভূমি কর (খাজনা) জমা দেওয়ার সেবা\r\n\r\nআপনার জমির ভূমি কর (খাজনা) সহজেই অনলাইনে পরিশোধ করে নিন।\r\n\r\n💰 সরকারি ভূমি কর: আপনার জমিতে যত টাকা নির্ধারিত থাকবে, ঠিক সেই পরিমাণ সরকারি ফি প্রদান করতে হবে।\r\n\r\nআমাদের সার্ভিস চার্জ: মাত্র ২০০ টাকা।\r\n\r\n📄 আমাদের যা প্রদান করবেন\r\n\r\n- অনলাইন ভূমি সেবা (e-Mutation/ভূমি কর) অ্যাকাউন্টের আইডি\r\n- পাসওয়ার্ড\r\n\r\nউপরের তথ্যগুলো দিলেই বাকি সমস্ত কাজ আমরাই সম্পন্ন করে দেব।\r\n\r\n✅ দ্রুত সেবা\r\n✅ নিরাপদ ও নির্ভরযোগ্য প্রক্রিয়া\r\n✅ ঝামেলামুক্ত অনলাইন ভূমি কর পরিশোধ\r\n\r\n📲 আপনার তথ্য পাঠিয়ে আজই সেবা গ্রহণ করুন।', 200.00, 'Software & Keys', '2060-06-27 13:01:00', 1, '2026-06-27 10:27:06', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(16, 3, '🎨 প্রিমিয়াম লোগো ডিজাইন সার্ভিস', '🎨 প্রিমিয়াম লোগো ডিজাইন সার্ভিস\r\n\r\nআপনার ব্যবসা, প্রতিষ্ঠান, ব্র্যান্ড, ক্লাব বা ব্যক্তিগত প্রয়োজনের জন্য আকর্ষণীয়, ইউনিক ও প্রিমিয়াম মানের লোগো ডিজাইন করে দেওয়া হয়।\r\n\r\n✨ আমাদের লোগো ডিজাইনের বৈশিষ্ট্য:\r\n\r\n- 🎯 সম্পূর্ণ ইউনিক ও প্রফেশনাল ডিজাইন\r\n- 🎨 আধুনিক ও আকর্ষণীয় লুক\r\n- 📱 সোশ্যাল মিডিয়া ও ওয়েবসাইট ব্যবহারের উপযোগী\r\n- 🔄 প্রয়োজন অনুযায়ী ছোটখাটো সংশোধনের সুবিধা\r\n\r\n💰 প্রতি লোগো ডিজাইন মাত্র ২০০ টাকা।\r\n\r\n📩 অর্ডার করতে আপনার প্রতিষ্ঠানের নাম, লোগোর ধরন, পছন্দের রং এবং প্রয়োজনীয় তথ্য আমাদের হোয়াটসঅ্যাপে পাঠিয়ে দিন।\r\n\r\nআপনার ব্র্যান্ডের পরিচয়কে আরও আকর্ষণীয় করে তুলতে আজই অর্ডার করুন!', 200.00, 'Social Media', '2060-06-27 16:51:00', 1, '2026-06-27 10:55:59', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(20, 3, '🎨 বিভিন্ন এজেন্সির পোস্টার ডিজাইন ও কনটেন্ট রাইটিং', '🎨 বিভিন্ন এজেন্সির পোস্টার ডিজাইন ও কনটেন্ট রাইটিং\r\n\r\nআপনার ব্যবসা, প্রতিষ্ঠান, এজেন্সি বা অনলাইন সার্ভিসের জন্য আকর্ষণীয়, আধুনিক ও প্রফেশনাল পোস্টার ডিজাইন এবং মানসম্মত কনটেন্ট রাইটিং করে দেওয়া হয়।\r\n\r\n✨ আমাদের সেবাসমূহ\r\n\r\n✅ প্রিমিয়াম পোস্টার ডিজাইন\r\n✅ আকর্ষণীয় ও বিক্রয়-উপযোগী কনটেন্ট রাইটিং\r\n✅ ফেসবুক, ওয়েবসাইট ও অন্যান্য সোশ্যাল মিডিয়ার জন্য উপযোগী ডিজাইন\r\n✅ আপনার প্রয়োজন অনুযায়ী কাস্টম ডিজাইন\r\n\r\n💰 পোস্টার ডিজাইন + কনটেন্ট রাইটিং: মাত্র ৪০০ টাকা।\r\n\r\n📩 অর্ডার করতে যা লাগবে\r\n\r\n- এজেন্সি/প্রতিষ্ঠানের নাম\r\n- কী ধরনের সার্ভিস প্রদান করেন\r\n- লোগো (যদি থাকে)\r\n- যোগাযোগের নম্বর\r\n- প্রয়োজনীয় তথ্য বা ছবি\r\n\r\n📲 সকল তথ্য হোয়াটসঅ্যাপে পাঠিয়ে দিন। আপনার পছন্দ অনুযায়ী দ্রুত ও সুন্দর ডিজাইন তৈরি করে দেওয়া হবে।', 400.00, 'Social Media', '2060-06-27 16:59:00', 1, '2026-06-27 11:05:14', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(21, 3, '🎬 প্রফেশনাল বিজ্ঞাপন ভিডিও অ্যানিমেশন সার্ভিস', '🎬 প্রফেশনাল বিজ্ঞাপন ভিডিও অ্যানিমেশন সার্ভিস\r\n\r\nআপনার ব্যবসা, প্রতিষ্ঠান, এজেন্সি বা অনলাইন সার্ভিসকে আরও আকর্ষণীয়ভাবে উপস্থাপন করতে তৈরি করা হয় প্রফেশনাল বিজ্ঞাপন ভিডিও (Animation সহ)।\r\n\r\n✨ আমাদের সেবাসমূহ\r\n✅ আধুনিক ও আকর্ষণীয় ভিডিও বিজ্ঞাপন\r\n✅ মোশন গ্রাফিক্স ও টেক্সট অ্যানিমেশন\r\n✅ প্রোডাক্ট ও সার্ভিস প্রোমোশন ভিডিও\r\n✅ Facebook, Instagram, YouTube ও TikTok-এর জন্য উপযোগী ভিডিও\r\n✅ আপনার ব্র্যান্ড অনুযায়ী কাস্টম ডিজাইন\r\n\r\n💰 চার্জ:\r\n🎥 প্রতি ১০ সেকেন্ড ভিডিও – মাত্র ১০০০ টাকা\r\n\r\n📩 অর্ডার করতে যা লাগবে\r\n• ব্যবসা/প্রতিষ্ঠানের নাম\r\n• সার্ভিস বা প্রোডাক্টের তথ্য\r\n• লোগো (যদি থাকে)\r\n• প্রয়োজনীয় ছবি বা ভিডিও\r\n• যোগাযোগের নম্বর\r\n\r\n📲 WhatsApp: 01956012103\r\n📧 Email: info.ayub.ceo@gmail.com\r\n\r\n🚀 আজই যোগাযোগ করুন এবং আপনার ব্যবসার জন্য একটি আকর্ষণীয় ও প্রফেশনাল ভিডিও বিজ্ঞাপন তৈরি করুন।', 1000.00, 'General', '2060-06-27 17:07:00', 1, '2026-06-27 11:09:23', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(22, 3, '📖 নিজের নামে প্রকাশ করুন কবিতার বই!', '📖 নিজের নামে প্রকাশ করুন কবিতার বই!\r\n\r\nআপনি কি নিজের নামে একটি কবিতার বই প্রকাশ করতে চান?\r\n\r\nএখন আর কবিতা লেখা, বই ডিজাইন বা প্রকাশনা নিয়ে চিন্তা করতে হবে না। সম্পূর্ণ কাজ আমরা করে দেব।\r\n\r\n✨ যা যা পাবেন:\r\n✅ আপনার নামে কবিতার বই প্রকাশ\r\n✅ মোট ১০০ কপি বই\r\n✅ ৩ ফর্মা (৪৮ পৃষ্ঠা) বই\r\n✅ সব কবিতা আমাদের টিম লিখে দেবে\r\n✅ আকর্ষণীয় কভার ডিজাইন\r\n✅ আপনার ছবি ও কবি পরিচিতি সংযোজন\r\n✅ বই ছাপিয়ে আপনার ঠিকানায় পৌঁছে দেওয়া হবে\r\n✅ পরবর্তী বইমেলায় বইটি প্রকাশের ব্যবস্থা\r\n\r\n💰 সম্পূর্ণ প্যাকেজ মূল্য: মাত্র ২৫,০০০ টাকা\r\n\r\n📩 অর্ডার করতে যা লাগবে:\r\n• আপনার নাম\r\n• একটি ছবি\r\n• সংক্ষিপ্ত পরিচিতি (কবি পরিচিতি)\r\n• যোগাযোগের নম্বর\r\n\r\n📲 WhatsApp: 01956012103\r\n📧 Email: info.ayub.ceo@gmail.com\r\n\r\n🌟 আজই নিজের স্বপ্নের কবিতার বই প্রকাশ করুন। বাকি সব দায়িত্ব আমাদের!', 25000.00, 'General', '2060-06-27 17:39:00', 1, '2026-06-27 11:40:07', 'product', -1, 'digital', '', '', NULL, NULL, 0),
(26, 3, 'জন্ম নিবন্ধনের আবেদন করুন মাত্র ১,০২০ টাকায়!', 'জন্ম নিবন্ধনের আবেদন করুন মাত্র ১,০২০ টাকায়!\r\n\r\nজন্ম নিবন্ধনের আবেদন থেকে শুরু করে প্রয়োজনীয় সকল কাগজপত্র প্রস্তুত এবং আবেদন সম্পন্ন করার সম্পূর্ণ সেবা আমরা প্রদান করি।\r\n\r\n👶 ৪৬ দিন বয়স পর্যন্ত শিশুর জন্য প্রয়োজনীয় কাগজপত্র\r\n\r\n১. শিশুর টিকা কার্ড\r\n২. হাসপাতালে জন্ম হলে হাসপাতালের সনদ/ছাড়পত্র\r\n৩. পিতা ও মাতার অনলাইন জন্ম নিবন্ধন সনদ\r\n৪. পিতা ও মাতার জাতীয় পরিচয়পত্র (NID)\r\n৫. বর্তমান ঠিকানার হোল্ডিং ট্যাক্সের কাগজ\r\n৬. শিশুর পিতার মোবাইল নম্বর\r\n৭. শিশুর ১ কপি পাসপোর্ট সাইজের ছবি\r\n\r\n👶 ৪৬ দিন থেকে ৫ বছর বয়স পর্যন্ত শিশুর জন্য প্রয়োজনীয় কাগজপত্র\r\n\r\n১. শিশুর টিকা কার্ড\r\n২. হাসপাতালে জন্ম হলে হাসপাতালের সনদ/ছাড়পত্র\r\n৩. পিতা ও মাতার অনলাইন জন্ম নিবন্ধন সনদ\r\n৪. পিতা ও মাতার জাতীয় পরিচয়পত্র (NID)\r\n৫. বর্তমান ঠিকানার হোল্ডিং ট্যাক্সের কাগজ\r\n৬. শিশুর পিতার মোবাইল নম্বর\r\n৭. শিশুর ১ কপি পাসপোর্ট সাইজের ছবি\r\n৮. একজন স্বাস্থ্যকর্মীর প্রত্যয়নপত্র\r\n\r\n📌 আমাদের সেবা\r\n\r\nআপনি শুধু উপরের কাগজপত্রের স্ক্যান কপি বা PDF আমাদের কাছে পাঠিয়ে দিন। আমরা আবেদন সম্পন্ন করে প্রয়োজনীয় কপি প্রস্তুত করে দেব।\r\n\r\n💰 সেবা মূল্য: মাত্র ১,০২০ টাকা।\r\n\r\n📞 যোগাযোগ\r\n\r\nWhatsApp: 01956012103\r\nEmail: info.ayub.ceo@gmail.com\r\n\r\nদ্রষ্টব্য: আবেদন গ্রহণের আগে সকল কাগজপত্র যাচাই করা হবে। সরকারি নীতিমালা অনুযায়ী আবেদন সম্পন্ন করা হয়।', 1020.00, 'General', '2060-07-04 12:20:00', 1, '2026-07-04 06:20:58', 'product', 1020, 'digital', '', '', NULL, NULL, 0),
(28, 3, 'ই-পাসপোর্ট আবেদন', 'ই-পাসপোর্ট আবেদন\r\n\r\nআপনি কি নতুন ই-পাসপোর্ট করতে চান অথবা পুরাতন পাসপোর্ট নবায়ন (Renew) করতে চান? আমরা আপনার ই-পাসপোর্ট আবেদন, তথ্য যাচাই, অনলাইন ফরম পূরণ এবং আবেদন সম্পন্ন করার সার্ভিস প্রদান করি।\r\n\r\nআবেদন করতে যে কাগজপত্র লাগবে\r\n\r\nসবার জন্য\r\n\r\n১. জাতীয় পরিচয়পত্র (NID) অথবা অনলাইন জন্ম নিবন্ধন (English Version)\r\n২. পিতা ও মাতার NID কার্ড (যারা জন্ম নিবন্ধন দিয়ে আবেদন করবেন তাদের জন্য)\r\n৩. বর্তমান ঠিকানার প্রমাণ হিসেবে যেকোনো একটি ইউটিলিটি বিল:\r\n\r\n- বিদ্যুৎ বিল\r\n- গ্যাস বিল\r\n- পানি বিল\r\n\r\nশিক্ষার্থীদের জন্য অতিরিক্ত কাগজপত্র\r\n\r\n- স্কুল/কলেজ/বিশ্ববিদ্যালয় থেকে প্রত্যয়নপত্র\r\n- স্টুডেন্ট আইডি কার্ড\r\n\r\nসরকারি চাকরিজীবীদের জন্য\r\n\r\n- NOC (No Objection Certificate)\r\n\r\nবিবাহিত আবেদনকারীদের জন্য\r\n\r\n- কাবিননামা\r\n\r\nআমাদের সার্ভিস চার্জ\r\n\r\nপ্রসেসিং ফি: ১,৫৩০ টাকা\r\n\r\n«দ্রষ্টব্য: সরকারি পাসপোর্ট ফি আলাদাভাবে সরকারি নির্ধারিত হারে পরিশোধ করতে হবে।»\r\n\r\nসরকারি ই-পাসপোর্ট ফি (বাংলাদেশ)\r\n\r\nপাসপোর্টের ধরন| সাধারণ (Regular)| জরুরি (Express)| অতিজরুরি (Super Express)\r\n৪৮ পৃষ্ঠা (৫ বছর)| ৪,০২৫ টাকা| ৬,৩২৫ টাকা| ৮,৬২৫ টাকা\r\n৪৮ পৃষ্ঠা (১০ বছর)| ৫,৭৫০ টাকা| ৮,০৫০ টাকা| ১০,৩৫০ টাকা\r\n৬৪ পৃষ্ঠা (৫ বছর)| ৬,৩২৫ টাকা| ৮,৬২৫ টাকা| ১২,০৭৫ টাকা\r\n৬৪ পৃষ্ঠা (১০ বছর)| ৮,০৫০ টাকা| ১০,৩৫০ টাকা| ১৩,৮০০ টাকা\r\n\r\nসম্ভাব্য ডেলিভারি সময়\r\n\r\n- Regular: প্রায় ২১ কার্যদিবস\r\n- Express: প্রায় ১০ কার্যদিবস\r\n- Super Express: প্রায় ২ কার্যদিবস\r\n\r\n(ডেলিভারির সময় সরকারি কার্যক্রমের ওপর নির্ভর করে পরিবর্তিত হতে পারে।)\r\n\r\nআমাদের সেবা\r\n\r\n- নতুন ই-পাসপোর্ট আবেদন\r\n- ই-পাসপোর্ট রিনিউ\r\n- তথ্য সংশোধন\r\n- হারানো পাসপোর্টের আবেদন\r\n- অনলাইন আবেদন ফরম পূরণ\r\n- আবেদন সংক্রান্ত পরামর্শ', 1530.00, 'General', '2060-07-04 12:56:00', 1, '2026-07-04 06:59:50', 'product', 1530, 'digital', '', '', NULL, NULL, 0),
(31, 3, 'ই-পাসপোর্ট সংশোধনের আবেদন', 'ই-পাসপোর্ট সংশোধনের আবেদন\r\nই-পাসপোর্টে নাম, জন্মতারিখ, পিতা-মাতার নাম, ঠিকানা, বৈবাহিক অবস্থা বা অন্যান্য তথ্য সংশোধন করতে চান? আমরা আবেদন প্রস্তুত থেকে শুরু করে প্রয়োজনীয় ডকুমেন্ট যাচাই ও প্রসেসিং পর্যন্ত সম্পূর্ণ সেবা প্রদান করি।\r\n\r\nপ্রয়োজনীয় কাগজপত্র\r\n\r\nসাধারণ আবেদনকারীদের জন্য:\r\n\r\n- জাতীয় পরিচয়পত্র (NID) অথবা অনলাইন জন্ম নিবন্ধন (ইংরেজি সংস্করণ)\r\n- পিতা ও মাতার জাতীয় পরিচয়পত্র (যারা জন্ম নিবন্ধন দিয়ে আবেদন করবেন)\r\n- বর্তমান ঠিকানার যেকোনো একটি ইউটিলিটি বিল (বিদ্যুৎ/পানি/গ্যাস)\r\n\r\nশিক্ষার্থীদের জন্য অতিরিক্ত:\r\n\r\n- স্কুল/কলেজ/বিশ্ববিদ্যালয় থেকে প্রত্যয়নপত্র\r\n- স্টুডেন্ট আইডি কার্ডের কপি\r\n\r\nসরকারি চাকরিজীবীদের জন্য অতিরিক্ত:\r\n\r\n- NOC (No Objection Certificate)\r\n\r\nবিবাহিত আবেদনকারীদের জন্য অতিরিক্ত:\r\n\r\n- কাবিননামার কপি\r\n\r\nসরকারি নির্ধারিত ফি\r\n\r\nই-পাসপোর্ট সংশোধনের ক্ষেত্রে সরকারি নির্ধারিত ফি আবেদনকারীর পাসপোর্টের ধরন, পৃষ্ঠাসংখ্যা এবং জরুরি/সাধারণ ডেলিভারির ওপর নির্ভর করে নির্ধারিত হয়। প্রযোজ্য সরকারি ফি সরকার নির্ধারিত হারে পরিশোধ করতে হবে।\r\n\r\nআমাদের সার্ভিস চার্জ\r\n\r\nপ্রসেসিং ফি / সার্ভিস চার্জ: ২,৫৫০ টাকা\r\n\r\nএই সার্ভিস চার্জের মধ্যে আবেদন প্রস্তুত, ডকুমেন্ট যাচাই এবং আবেদন প্রসেসিং সহায়তা অন্তর্ভুক্ত।\r\n\r\nবিশেষ দ্রষ্টব্য: আবেদনকারীর তথ্য ও সংশোধনের ধরন অনুযায়ী অতিরিক্ত বা ভিন্ন কাগজপত্র প্রয়োজন হতে পারে। চূড়ান্ত ডকুমেন্ট তালিকা যাচাই করে আবেদন সম্পন্ন করা হবে।', 2550.00, 'General', '2060-07-04 14:21:00', 1, '2026-07-04 08:22:39', 'product', 2550, 'digital', '', '', NULL, NULL, 0),
(33, 3, 'জন্ম নিবন্ধন সংশোধন সেবা', 'জন্ম নিবন্ধন সংশোধন সেবা\r\n\r\nজন্ম নিবন্ধনে নাম, জন্মতারিখ, পিতা-মাতার নাম, ঠিকানা বা অন্যান্য তথ্য সংশোধনের জন্য আমরা আবেদন প্রস্তুত, ডকুমেন্ট যাচাই এবং আবেদন জমা দেওয়ার সম্পূর্ণ সেবা প্রদান করি।\r\n\r\nপ্রয়োজনীয় কাগজপত্র\r\n\r\n১. শিশুর টিকা কার্ড\r\n২. হাসপাতালে জন্ম হলে হাসপাতালের জন্ম সনদ/ছাড়পত্র\r\n৩. পিতা ও মাতার অনলাইন জন্ম নিবন্ধন সনদ\r\n৪. পিতা ও মাতার জাতীয় পরিচয়পত্র (NID)\r\n৫. বর্তমান ঠিকানার হোল্ডিং ট্যাক্সের কাগজ\r\n৬. শিশুর পিতার মোবাইল নম্বর\r\n৭. শিশুর ১ কপি পাসপোর্ট সাইজের ছবি\r\n৮. একজন স্বাস্থ্যকর্মীর প্রত্যয়নপত্র\r\n\r\nদ্রষ্টব্য: সংশোধনের ধরন অনুযায়ী অতিরিক্ত কাগজপত্র প্রয়োজন হতে পারে।\r\n\r\nসেবার ফি\r\n\r\nমোট ফি: ১,০২০ টাকা\r\n\r\nএই ফি-এর মধ্যে আমাদের প্রসেসিং ফি এবং সরকারি ফি অন্তর্ভুক্ত।\r\n\r\nআপনার প্রয়োজনীয় কাগজপত্র নিয়ে যোগাযোগ করুন। আমরা দ্রুত ও নির্ভুলভাবে আপনার জন্ম নিবন্ধন সংশোধনের আবেদন সম্পন্ন করতে সহায়তা করব।', 1020.00, 'Subscriptions', '2054-07-04 14:38:00', 1, '2026-07-04 08:38:32', 'product', 1020, 'digital', '', '', NULL, NULL, 0),
(34, 13, 'DOMINO STYLE QUICk THROTTLE', 'Category: THROTTLE. R15,Gsxr,Cbr etc ( Universal ) Bike er throttle response aro sharp korte chan? Regular throttle er boring feel bad diye racing feel nite chan? 😎 ✅ Universal Fit�✅ Smooth & Quick Acceleration�✅ Premium Build Quality�✅ Sports Bike Look Upgrade�✅ Daily Use & Touring Friendly', 1250.00, 'THROTTLE', NULL, 1, '2026-07-20 11:23:51', 'product', -1, 'digital', '', '', 'https://enzor.best-travel.ltd/#product-1006', 'Enzor Motors', 0),
(35, 13, 'jxbnw', 'Category: snx . jqsnx', 34.00, 'snx', NULL, 1, '2026-07-20 11:37:14', 'product', -1, 'digital', '', '', 'https://enzor.best-travel.ltd/#product-10011', 'Enzor Motors', 0),
(36, 13, 'dd', 'Category: ss. wfmw', 6000.00, 'ss', NULL, 1, '2026-07-20 12:03:53', 'product', -1, 'digital', '', '', 'https://enzor.best-travel.ltd/#product-10011', 'Enzor Motors', 0),
(37, 11, 'MKB E52 True Wireless Earbuds – Black', 'Bluetooth Version: V5.4\nTransmission Distance: 12 meters\nEarbud Battery Capacity: 50mAh (each)\nCharging Case Capacity: 600mAh\nTalk Time: 16 hours (volume 70%)\nMusic Time: 15 hours (volume 70%)\nCharging Time: 1.5 hours\nStandby Time: 2800 hours\n7 days warranty', 800.00, 'earbuds', NULL, 1, '2026-07-21 11:56:43', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/4ebcbca2-ab2e-46ce-a486-1b7b3bae651a', 'AT Ayra Mart', 0),
(38, 11, 'JYSUPER JY-2320 Portable Handheld Mini Fan with Integrated LED light', 'Specification\nValue\nModel\nJYSUPER JY-2320\nProduct Type\nPortable Handheld Mini Fan with LED Light\nMaterial\nHigh-Quality ABS Plastic\nDimensions (H x W x D)\n18.5 cm x 9.5 cm x 4.5 cm\nWeight\n180 grams (approx.)\nBattery Type\nRechargeable Lithium-ion\nBattery Capacity\n1500 mAh\nInput Voltage\nDC 5V / 1A\nCharging Port\nMicro USB\nCharging Time\nApproximately 3 hours\nUsage Time\n2-5 hours (depending on fan speed and LED usage)\nFan Speeds\nNo adjustable speeds\nLED Light\nIntegrated single-mode LED\nColor Options\nWhite (commonly available, other colors may vary)\nNoise Level\nLess than 40dB\nPower Output\n2.5W\nIncluded Accessories\nUSB Charging Cable', 500.00, 'fan-item', NULL, 1, '2026-07-21 12:31:10', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/479abc32-a47a-4c89-b834-00021ad839f7', 'AT Ayra Mart', 0),
(39, 11, 'E27 Smart Lamp Holder with Wireless Remote & Timer', 'Model: E27 Lamp Holder\nRated Voltage: AC85–265V\nRemote Range: Up to 8 meters\nTimer Options: 5 / 15 / 30 / 60 / 120 minutes\n7 Days Warranty (If there are any Manufacturing Defects)\nনোট - রিমোটের সাথে কোনো ব্যাটারি দেওয়া থাকে না', 300.00, 'electronics', NULL, 1, '2026-07-21 12:57:37', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/adcce470-a58d-4aad-8d75-0f6dc2c7b400', 'AT Ayra Mart', 0),
(40, 11, 'ACEFAST Z8 PD45W GaN Universal Travel Adapter', 'Universal socket power: 2500W at 250V / 1100W at 110V for worldwide use\nMulti‑port charging: 5 ports (3 × USB‑C + 2 × USB‑A) for simultaneous device charging\nFast charging support: PPS, PD3.0, QC4+, QC3.0, AFC, FCP, SCP protocols compatible with 99% of devices\nGaN technology: Compact size, efficient power delivery, superior heat dissipation\nAI smart temperature control: Monitors temperature 40 times per second for safe charging\nInterlocking plug system: Only one plug extends at a time for added safety\nDual 10A fuses: Extra protection for devices and adapter\nGlobal compatibility: EU, US, UK, AU switching plugs covering 150+ countries', 1900.00, 'mobile-accessories', NULL, 1, '2026-07-21 13:09:45', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/9ae65ebf-02f8-4c8e-b068-93c13ea6570e', 'AT Ayra Mart', 0),
(41, 11, 'ZOOOK Lightup C USB Type-C Breathable LED Fast Charging Cable', 'Basic Information\nTransmission Rate	480Mbps\nIndicator	Blue LED\nPhysical Specification\nMaterial	Aluminium Alloy with Nylon Braid\nColor	Black\nCable Length	4 Feet / 1.2 Meter', 300.00, 'mobile-accessories', NULL, 1, '2026-07-23 10:27:52', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/d8821904-a14f-419b-8ca3-f2e0d27180a7', 'AT Ayra Mart', 0),
(42, 11, 'BYZ BL-B60 Ultra-Long Battery Life Wireless Neckband Headphones', 'Feature	Description\nBrand	BYZ\nModel	BL-B60\nType	Wireless Neckband Headphones\nBattery Life (Playback)	Up to 25 Hours\nCharging Time	Approx. 1.5 Hours\nConnectivity	Bluetooth\nSpecial Feature	Magnetic Hall Switch (Smart Auto On/Off)\nDesign	Ergonomic In-Ear Neckband', 700.00, 'bt-headphones', NULL, 1, '2026-07-23 10:32:31', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/b2323b0e-0baa-4cfb-9b01-75cff9b1b334', 'AT Ayra Mart', 0),
(43, 11, 'COLMI P71 Calling Smartwatch – Black Color', 'Specification :\n\nMain Chipset: Realtek RTL8763E\nScreen Size: 1.9” Large TFT Color Display.\nScreen resolution: 240*285 pixels\nBattery Capacity: 230 mAh Li-pol battery.\nBattery Life:\nStandby Mode: Up to 20 days\nTypical Use Modes: Up to 10 days\nHeavy Use Modes: Up to 5 days\nSilent Voice Call: Up to 280 minutes\nCharging Time: About 1.5 hours\nWaterproof Level: IP68 Waterproof\nAPP: “Pubu Wear”', 1760.00, 'smart-watches', NULL, 1, '2026-07-23 10:35:55', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/66422e36-a424-4902-b867-d9c75828abaa', 'AT Ayra Mart', 0),
(44, 11, 'Extonic Air Cooler Fan (ET-C702) – Blue Color', 'Extonic Air Cooler Fan (ET-C702) Price in Bangladesh\nModel: Extonic ET-C702\nPower: 10W Max\nVoltage: 5V\nElectric Current: 2A Max\nBlowing Distance: 2.5m Max\nPlug: Type-C USB Plug\nThe volume of mist: 225ml/h Max\nType of Mist: 2.5h – 12h\nWater tank capacity: 600ml\nProduct Size: 21cm X 9cm X 26cm (8.26 x 3.54 x 10.23 inch)\nTimer Function: Yes\nMulti-Color LED: Yes\nNote: This is NOT a rechargeable fan.', 1700.00, 'fan-item', NULL, 1, '2026-07-23 10:38:01', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/2f2d3b3f-b30c-437b-8c65-4bd6af9ca365', 'AT Ayra Mart', 0),
(45, 11, 'Xiaomi AISOLOVE F01 Handheld Turbo Fan (2000mAh Battery) in Bangladesh', 'Specification:\n\nProduct name: Handheld Fan\nModel: F01\nColor: Green\nBattery type/energy: Lithium battery/2000mAh 3.7V 4.44Wh\nRated voltage: 5V=\nRated current: 1A\nRated input power: 5W\nWorking time: About 1.2 – 4.2h\nCharging time: About 2.2h\nProduct size: About 61x161x48.9mm\nProduct weight: About 125g\nProduct material: PC+ABS (V0)\nHighlights:\n\n1. Small size but strong wind\n2. Frosted texture color, healed appearance, more upscale\n3. Smooth feel\n4. Five levels of wind speed control\n5. Type-C fast charging interface\n6. Built-in 2000mAh lithium battery, rechargeable fan\n7. Support charging and use at the same time\n8. Low noise\n9. Easy to store', 899.00, 'fan-item', NULL, 1, '2026-07-23 10:40:07', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/1f666934-af9e-43a5-a726-97a8eac66c13', 'AT Ayra Mart', 0),
(46, 11, 'Arctic Air Ultra 3 In 1 Evaporative Air Cooler', 'Specifications\n\nEasy Top Fill Water Tank:\nKeeping your cooler filled with water has always been more complex. The quick and easy top-fill design allows you to maintain the optimal water level, ensuring continuous cooling effortlessly. The Arctic Air Ultra Air Cooler Evaporative cools the air and purifies and humidifies it, creating a healthier environment for you and your family. Breathe easy with this Arctic Air Ultra 3 1 Evaporative Air Filter solution.\n\nMulti-Directional Air Vent:\nCustomize your cooling experience with the Arctic Air Cooler 3 In 1 600-700 ML Water multi-directional air vent, ensuring every corner of your room benefits from the Arctic Air’s cooling power. The Arctic Ultra Air Cooler offers three fan speed settings – low, medium, and high. Tailor the cooling experience to your preferences, whether you need a gentle breeze or a stronger gust of cool air.\n\nLED Night Light Control:\nTransform your nighttime ambiance with the integrated LED night light control. Create a soothing atmosphere and enjoy a good night’s sleep.\n\nLong-Lasting Cooling:\nThis remarkable cooler can run for up to 10 hours per fill, ensuring continuous comfort throughout the day and night.\n\nTransforms Hot Dry Air:\nThis Arctic Air Cooler 2X Cooling Power instantly relieves oppressive heat by turning hot, dry air into cool, moist air.\n\nEco-Friendly and Space-Saving:\nThe Arctic Air Ultra is not only your cooling companion but also eco-friendly. It’s designed to be energy-efficient and eco-conscious while taking up minimal space, making it ideal for personal room use.', 1099.00, 'fan-item', NULL, 1, '2026-07-23 10:44:53', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/9805b569-6113-4842-900a-2e1136946f29', 'AT Ayra Mart', 0),
(47, 11, 'Hoco EW72 ANC TWS Earbuds With HD Display', 'Specification:\n\nBrand: Hoco\nModel: EW72\nBluetooth: V5.4 Jerry AC7003\nSupport protocol: Headset Hands-free A2DP Avrcp\nTransmission range: 10 meters\nTransmission frequency: 2.4GHz\nMaximum RF output power (EDR): 4DB Modulation type (EDR): GFSK, π/4-DQPSK\nSpeaker: 13MM\nImpedance: 32Ω±15%\nFrequency response: 20~20000Hz\nSensitivity: 98db±3db\nCharging voltage: DC5V\nCharging time: about 1 hour\nCharging box battery capacity: 360mah; left and right ear battery capacity 30mAh each\nTalk time: about 4 hours\nMusic time: about 4 hours\nNet weight: 57.6g (including charging compartment); bare device weight: 3.7g (single ear)\nSize: 60.6*45.2*23mm', 1350.00, 'bt-airbuds', NULL, 1, '2026-07-23 10:51:17', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/9a648302-0a4c-4f0a-9abd-88008b0c94c9', 'AT Ayra Mart', 0),
(48, 11, 'EWA A103 Bluetooth Speaker – Grey Color', 'EWA A103 Bluetooth Speaker in Bangladesh\n It has better sound quality and bass function.\n The battery is durable, with a fast charging time.\n Easy to carry, stylish appearance.\n Excellent quality, metal body.', 999.00, 'bt-speakers', NULL, 1, '2026-07-23 10:54:47', 'product', -1, 'digital', '', '', 'https://atayramart.com/#product/e0a02271-f6f8-4f68-944e-f0ec1f6d2c3f', 'AT Ayra Mart', 0);

-- --------------------------------------------------------

--
-- Table structure for table `partner_product_images`
--

CREATE TABLE `partner_product_images` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `is_thumbnail` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_product_images`
--

INSERT INTO `partner_product_images` (`id`, `product_id`, `image_url`, `is_thumbnail`) VALUES
(1, 1, 'prod_1_1782534202_0.png', 1),
(14, 13, 'prod_13_1782556026_0.png', 1),
(17, 16, 'prod_16_1782557759_0.png', 1),
(21, 20, 'prod_20_1782558314_0.png', 1),
(22, 21, 'prod_21_1782558563_0.png', 1),
(23, 22, 'prod_22_1782560407_0.png', 1),
(27, 26, 'prod_26_1783146058_0.png', 1),
(29, 28, 'prod_28_1783148390_0.png', 1),
(32, 31, 'prod_31_1783153359_0.png', 1),
(34, 33, 'prod_33_1783154312_0.png', 1),
(35, 34, 'https://enzor.best-travel.ltd/https://scontent.fdac96-1.fna.fbcdn.net/v/t45.5328-4/752023046_2418719558597883_2823705487261613989_n.jpg?stp=c39.0.320.320a_dst-jpg_p320x320_tt6&_nc_cat=106&ccb=1-7&_nc_sid=c66aae&_nc_eui2=AeHO0e1Zlk0lTwfOZbmbDr9CnOGoTcAwKFqc4ahNwDAoWmbvEwiKBAfkgM26zOJ5SsR8kuicLgiMwVOMR9gBqmBG&_nc_ohc=ObVOLKVspHEQ7kNvwEQNFY_&_nc_oc=AdpiKW2UMZC9qeYCyCjt4BivePfx-46AUKAuvjN8l_wlYukYT4iOb6IyT9yLLfukZU4&_nc_zt=23&_nc_ht=scontent.fdac96-1.fna&_nc_gid=M_Dg71PBLrX1P2WXRS8gKQ&_nc_ss=7a2a8&o', 1),
(36, 35, 'https://enzor.best-travel.ltd//uploads/prod_1784547417_625.jpeg', 1),
(37, 36, 'https://enzor.best-travel.ltd//uploads/prod_1784549005_143.jpg', 1),
(38, 37, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784635003_80e08bb4.jpeg', 1),
(39, 38, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784637067_fecfdb4b.png', 1),
(40, 39, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784638657_52c11395.jpeg', 1),
(41, 40, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784639384_58364b11.png', 1),
(42, 41, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784802471_a6ef8eee.png', 1),
(43, 42, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784802750_ee67645f.jpeg', 1),
(44, 43, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784802954_7dfd4930.png', 1),
(45, 44, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784803079_aa8a892c.jpeg', 1),
(46, 45, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784803204_f5450777.jpeg', 1),
(47, 46, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784803492_e0159687.webp', 1),
(48, 47, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784803875_a2233a54.png', 1),
(49, 48, 'https://atayramart.com/https://atayramart.com/uploads/products/product_1784804086_35845f99.jpeg', 1);

-- --------------------------------------------------------

--
-- Table structure for table `partner_ratings`
--

CREATE TABLE `partner_ratings` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `rating` int(11) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_requests`
--

CREATE TABLE `partner_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partner_requests`
--

INSERT INTO `partner_requests` (`id`, `user_id`, `status`, `admin_notes`, `created_at`) VALUES
(1, 6, 'approved', NULL, '2026-07-16 02:07:20');

-- --------------------------------------------------------

--
-- Table structure for table `partner_settings`
--

CREATE TABLE `partner_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `partner_settings`
--

INSERT INTO `partner_settings` (`id`, `setting_key`, `setting_value`, `updated_at`) VALUES
(1, 'coin_name', 'Fast Points', '2026-06-05 08:06:40'),
(2, 'exchange_rate', '1', '2026-06-05 08:06:40'),
(3, 'min_withdrawal', '500', '2026-06-05 08:06:40'),
(4, 'auto_respond_days', '2', '2026-06-05 08:06:40'),
(5, 'auto_complete_days', '7', '2026-06-05 08:06:40'),
(6, 'cooling_off_hours', '24', '2026-06-05 08:06:40');

-- --------------------------------------------------------

--
-- Table structure for table `partner_wishlist`
--

CREATE TABLE `partner_wishlist` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `partner_withdrawal_requests`
--

CREATE TABLE `partner_withdrawal_requests` (
  `id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `account_info` varchar(255) DEFAULT NULL,
  `status` enum('pending','processed','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `otp` varchar(10) NOT NULL,
  `status` enum('pending','approved','used') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `return_requests`
--

CREATE TABLE `return_requests` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `partner_id` int(11) NOT NULL,
  `reason` text NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `show_on_homepage` tinyint(1) DEFAULT 1,
  `action_text` varchar(255) DEFAULT 'ORDER NOW',
  `product_price` decimal(10,2) DEFAULT 0.00,
  `referral_link` varchar(1000) DEFAULT NULL,
  `referral_clicks` int(11) DEFAULT 0,
  `website_link` varchar(1000) DEFAULT NULL,
  `header_section_name` varchar(255) DEFAULT NULL,
  `sub_section_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `name`, `description`, `fee`, `is_active`, `created_at`, `section_name`, `sort_order`, `logo_url`, `govt_fee`, `processing_fee`, `affiliate_bonus_pct`, `show_on_homepage`, `action_text`, `product_price`, `referral_link`, `referral_clicks`, `website_link`, `header_section_name`, `sub_section_name`) VALUES
(1, 'NID Correction', 'Correction of info on National ID.', 0.00, 1, '2026-04-01 05:32:55', 'জাতীয় পরিচয়পত্র সেবা', 20, 'uploads/services/svc_1782528681_d715692e.png', 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(2, 'NID Apply', 'New National ID card application.', 0.00, 1, '2026-04-01 05:32:55', 'জাতীয় পরিচয়পত্র সেবা', 10, 'uploads/services/svc_1782524937_3851e211.png', 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(3, 'Driving License Correction', 'Correction on Driving License.', 1500.00, 1, '2026-04-01 05:32:55', 'ড্রাইভিং লাইসেন্স সেবা', 20, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(4, 'Driving License Apply/Renewal', 'New or renewal of Driving License.', 0.00, 1, '2026-04-01 05:32:55', 'ড্রাইভিং লাইসেন্স সেবা', 10, 'uploads/services/svc_1782525112_3daa836c.png', 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(5, 'Passport Correction', 'Correction of info on Passport.', 700.00, 1, '2026-04-01 05:32:55', 'পাসপোর্ট সেবা', 20, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(6, 'Passport Apply/Renewal', 'New application or renewal of Passport.', 800.00, 1, '2026-04-01 05:32:55', 'পাসপোর্ট সেবা', 10, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(7, 'Other Government Service', 'Any other government-related assistance.', 0.00, 1, '2026-04-01 05:32:55', 'অন্যান্য সেবা', 10, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(8, 'WEBSITE BUILD UP', '8% off', 2430.00, 1, '2026-04-02 11:32:51', 'অন্যান্য সেবা', 0, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(9, 'Birth Certificate Apply', 'REQUIRED FILES', 1530.00, 1, '2026-04-26 08:25:28', '🪪 জাতীয় পরিচয়পত্র সেবা', 10, NULL, 0.00, 1530.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(10, 'Birth Certificate Correction', 'Online application and correction of birth registration data.', 350.00, 1, '2026-06-05 18:33:35', '🪪 জাতীয় পরিচয়পত্র সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(11, 'Police Clearance Certificate', 'Police clearance certificate for passport, visa or job.', 550.00, 1, '2026-06-05 18:33:35', '🚔 পুলিশ সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(12, 'e-TIN Registration', 'Electronic TIN certificate registration and copy download.', 200.00, 1, '2026-06-05 18:33:35', '💵 কর ও ভ্যাট সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(13, 'Trade License Application', 'Application support for online Trade License in city corporations or unions.', 1200.00, 1, '2026-06-05 18:33:35', '💼 ব্যবসা ও বাণিজ্যিক সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(14, 'Online Land Mutation (Namjari)', 'Apply for land mutation and land record correction online.', 2500.00, 1, '2026-06-05 18:33:35', '🗺️ ভূমি ও জমি সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(15, 'Online Land Khatian Search', 'Retrieve online certified copy of land Khatian or Porcha.', 450.00, 1, '2026-06-05 18:33:35', '🗺️ ভূমি ও জমি সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(16, 'Universal Pension Scheme', 'Registration support for Universal Pension Scheme (Pragati, Surokkha, Samata, Prabashi).', 300.00, 1, '2026-06-05 18:33:35', '👵 সরকারি পেনশন সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(17, 'BRTA Vehicle Tax Token Renewal', 'Online renewal of motorcycle or car tax token and fees payment.', 1500.00, 1, '2026-06-05 18:33:35', '🚗 ড্রাইভিং ও যানবাহন সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(18, 'Online GD Application', 'Online General Diary application for lost documents, certificates, mobile etc.', 250.00, 1, '2026-06-05 18:33:35', '🚔 পুলিশ সেবা', 5, NULL, 0.00, 0.00, 20.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(19, 'CHECK BRITH CIRTIFICATE ONLINE', 'CHECK AND DOWNLOAD BIRTH CERTIFICATE ONLINE', 0.00, 1, '2026-06-16 15:05:52', '🌐 অন্যান্য সেবা', 0, 'uploads/services/svc_1781622352_a7e368ef.png', 0.00, 0.00, 0.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, NULL, NULL),
(20, 'HOSTINGER PLAN', 'SIGN UP NOW, AND GET 30% DISCOUNT ON YOUR FIRST PURCHASE. USING CODE - (FAST30)', 0.00, 1, '2026-06-21 05:21:24', '🌐 অন্যান্য সেবা', 0, 'uploads/services/svc_1782528557_cd2dddd6.png', 0.00, 0.00, 0.00, 1, 'CHECK', 0.00, 'https://www.hostinger.com?REFERRALCODE=fast30', 1, NULL, NULL, NULL),
(21, 'SSM PANEL FOR SOCIAL MEDIA', 'SSM PANEL FOR ALL SOCIAL MEDIA', 0.00, 1, '2026-06-21 15:42:18', '🌐 অন্যান্য সেবা', 0, 'uploads/services/svc_1782528740_deffa4e0.jpg', 0.00, 0.00, 0.00, 1, 'CHECK', 0.00, 'https://www.smmservices24.com/apply-code?ref=UYFjyZ1', 0, NULL, NULL, NULL),
(22, 'LIVE FOOTBALL -', 'LIVE FOOTBALL IRAN VS BELGIUM', 0.00, 0, '2026-06-21 20:28:14', '🌐 অন্যান্য সেবা', 0, 'uploads/services/svc_1782073949_2a478f69.jpg', 0.00, 0.00, 20.00, 0, 'VIEW', 0.00, NULL, 0, 'https://www.808fubo19.com', NULL, NULL),
(23, 'TWILIO EVENT', 'RUN WHATS APP ON VIRTUAL PLATFORM.', 0.00, 1, '2026-06-22 16:34:24', 'VIRTUAL MESSAGING PLATFORM', 0, 'uploads/services/svc_1782146064_c798cf91.png', 0.00, 0.00, 0.00, 1, 'VIEW', 0.00, NULL, 0, 'https://www.twilio.com/en-us', 'ELECTRONIC', 'SOCIAL'),
(24, 'LIVE', 'LIVE FOOTBALL MATCH', 0.00, 0, '2026-06-22 20:59:22', 'FOOTBALL', 0, 'uploads/services/svc_1782161962_b1644b16.jpeg', 0.00, 0.00, 20.00, 0, 'VIEW', 0.00, NULL, 0, 'https://www.808fubo19.com/football/2907359-france-vs-iraq.html', 'IRAN VS FRANCE', NULL),
(25, 'AFFILIATE PARTNER M', 'BECOME AFFILIATE PARTNER AND HAVE SHARED REVENUE', 0.00, 1, '2026-06-27 01:32:47', 'অন্যান্য সেবা', 0, 'uploads/services/svc_1782523967_e23adda6.png', 0.00, 0.00, 0.00, 1, 'CHECK', 0.00, NULL, 0, 'https://refpa3665.com/L?tag=d_5711479m_18645c_&site=5711479&ad=18645', 'ONLINE M AFFILIATE', NULL),
(26, 'SOCIAL ACCOUNT RECOVERY', 'WE RECOVER SOCIAL ACCOUNT FROM DEPTH', 1550.00, 1, '2026-06-29 01:10:10', 'SOCIAL ACCOUNT', 0, 'uploads/services/svc_1782695410_07dba3b7.jpeg', 0.00, 1550.00, 15.00, 1, 'ORDER NOW', 0.00, NULL, 0, NULL, 'ACCOUNT RECOVERY', NULL),
(27, 'THAILAND VISA CONTACT', '100% GUARANTEE VISA.', 19120.00, 1, '2026-07-06 11:22:50', 'VISA SERVICE', 0, 'uploads/services/svc_1783337136_32c5ad79.png', 0.00, 1620.00, 0.00, 1, 'CHECK', 17500.00, NULL, 0, NULL, 'THAILAND VISA CONTACT 100%', NULL),
(28, 'TRACK PHONE NUMBER', 'TRACK PERSON WITH THE PHONE NUMBER. ESTIMATED TIME (1-7) DAYSweek', 1020.00, 1, '2026-07-11 06:30:44', 'অন্যান্য সেবা', 0, 'uploads/services/svc_1783751444_ebde16a9.jpg', 0.00, 0.00, 20.00, 1, 'ORDER NOW', 1020.00, NULL, 0, NULL, 'LOCATION TRACKING WITH SIM NUMBER', NULL),
(29, 'NID CORRECTION (6+ years)', 'NID CORRECTION ABOVE (6+ YEARS).', 7000.00, 1, '2026-07-11 19:01:45', 'অন্যান্য সেবা', 0, 'uploads/services/svc_1783796505_c9dba1eb.jpeg', 0.00, 0.00, 20.00, 1, 'ORDER NOW', 7000.00, NULL, 0, NULL, 'NID CORRECTION', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `shop_tags`
--

CREATE TABLE `shop_tags` (
  `id` int(11) NOT NULL,
  `tag_name` varchar(100) NOT NULL,
  `icon_emoji` varchar(20) DEFAULT NULL,
  `bg_color` varchar(50) DEFAULT 'rgba(255, 255, 255, 0.1)',
  `text_color` varchar(50) DEFAULT '#ffffff',
  `border_color` varchar(50) DEFAULT 'rgba(255, 255, 255, 0.2)',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `staff_tasks`
--

CREATE TABLE `staff_tasks` (
  `id` int(11) NOT NULL,
  `staff_user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` datetime DEFAULT current_timestamp(),
  `whatsapp` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `permissions` text DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `nid` varchar(255) DEFAULT NULL,
  `etin` varchar(255) DEFAULT NULL,
  `passport` varchar(255) DEFAULT NULL,
  `driving_license` varchar(255) DEFAULT NULL,
  `extra_details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `staff_users`
--

INSERT INTO `staff_users` (`id`, `username`, `password_hash`, `email`, `role`, `created_at`, `whatsapp`, `facebook`, `instagram`, `twitter`, `youtube`, `permissions`, `profile_pic`, `dob`, `gender`, `nid`, `etin`, `passport`, `driving_license`, `extra_details`) VALUES
(1, 'admin', '$2y$10$RyRxzH6V2lvQC0FyW3U/aOuF5Tr2gwhoki8oyOLc4fdovKJultFeO', 'info.fastsite@gmail.com', 'admin', '2026-04-03 21:30:27', '01337320544', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'MIM', '$2y$10$eJXmPvx1nmJOdUGRVrPj.eC5rVnU6Jhhf0ho7S8oucS1UhKHZ8BkS', NULL, 'staff', '2026-04-03 21:48:42', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trust_directory`
--

CREATE TABLE `trust_directory` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `url` varchar(500) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `safety_rating` enum('verified','caution','scam') DEFAULT 'caution',
  `admin_review` text DEFAULT NULL,
  `redirection_link` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `whatsapp` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `instagram` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `youtube` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `dob` varchar(255) DEFAULT NULL,
  `gender` varchar(255) DEFAULT NULL,
  `nid` varchar(255) DEFAULT NULL,
  `etin` varchar(255) DEFAULT NULL,
  `passport` varchar(255) DEFAULT NULL,
  `driving_license` varchar(255) DEFAULT NULL,
  `extra_details` varchar(255) DEFAULT NULL,
  `missed_commissions` decimal(10,2) DEFAULT 0.00,
  `coins_balance` decimal(10,2) DEFAULT 0.00,
  `address` text DEFAULT NULL,
  `role` varchar(20) DEFAULT 'user',
  `is_active` tinyint(1) DEFAULT 1,
  `nid_number` varchar(50) DEFAULT NULL,
  `registration_number` varchar(50) DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `current_streak` int(11) DEFAULT 0,
  `longest_streak` int(11) DEFAULT 0,
  `last_login_date` date DEFAULT NULL,
  `allow_extra_shop` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `phone`, `password_hash`, `ref_code`, `created_at`, `whatsapp`, `facebook`, `instagram`, `twitter`, `youtube`, `email`, `profile_pic`, `dob`, `gender`, `nid`, `etin`, `passport`, `driving_license`, `extra_details`, `missed_commissions`, `coins_balance`, `address`, `role`, `is_active`, `nid_number`, `registration_number`, `district`, `current_streak`, `longest_streak`, `last_login_date`, `allow_extra_shop`) VALUES
(1, 'SADMAN HOSSAIN SAYAM', '01337320544', '$2y$10$wdPkRbcua20qOL6HJQlxgej2W7dDeRTj5pZc9SYle3DyCApyzpXLK', 'USER_178253240770', '2026-06-27 03:53:27', '', '', '', '', '', 'info.fastsite@gmail.com', 'uploads/kyc/pp_1784103235_1964.jpeg', '2000-04-04', 'Male', '', '', '', '', '', 0.00, 2.00, '222, Faidabad Main Road, East Azampur, Dhakshinkhan dhaka - 1230', 'user', 1, NULL, 'FS-USER-00001', 'Dhaka', 1, 1, '2026-07-26', 0),
(2, 'MD Ayub Mojumdar Saimun', '01747792098', '$2y$10$hSdZ0/HNYpgt43sGmVcguukFXPKun.rib/S8ZQUgFgl0LFdVDWOlm', 'USER_178254618313', '2026-06-27 07:43:03', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, 'user', 1, NULL, 'FS-USER-00002', NULL, 0, 0, NULL, 0),
(3, 'MD Ayub Mojumdar Saimun', '01959521655', '$2y$10$Uef14lsxnQP1mctZK3qJM.AGz40aUJH5bOIXyu6uH92gfe7t0SFBi', 'USER_178266536192', '2026-06-28 16:49:21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, 'user', 1, NULL, 'FS-USER-00003', NULL, 0, 0, NULL, 0),
(4, 'Mahfuzur Rahman', '01670686654', '$2y$10$NzPa83r3Hh7mkF.JjFPjE.HmwRkglWLEw3vFjeaP7/PUrd9ipBjqS', 'USER_178334527333', '2026-07-06 13:41:13', NULL, NULL, NULL, NULL, NULL, 'fahim20mac@gmail.com', NULL, '2026-07-20', 'Male', NULL, NULL, NULL, NULL, NULL, 0.00, 0.00, NULL, 'user', 1, NULL, 'FS-USER-00004', NULL, 0, 0, NULL, 0),
(5, 'Sanjidur Rahman', '+8801859575951', '$2y$10$2KxdNfXZC8Bor2pBzbB2yuOgOYfrmlAzS9DQlVy1RuA3F1bNWRJsO', 'USER_178396391763', '2026-07-13 17:31:58', '+8801859575951', NULL, NULL, NULL, NULL, 'sanjidmml20@gmail.com', NULL, '1996-10-29', 'Male', NULL, NULL, NULL, NULL, '', 0.00, 0.00, NULL, 'user', 1, NULL, 'FS-USER-00005', NULL, 0, 0, NULL, 0),
(6, 'Md Dulal', '01951029485', '$2y$10$C0x6W7zpVmNl5BeYKcaYA..rWgs0YgLOQJBfhi3O/lHpfIvF/jEa6', 'USER_178416651238', '2026-07-16 01:48:32', '01951029485', 'https://www.facebook.com/share/1Dbxc5kzhP/', '', '', '', 'mddulalmia8373@gmail.com', 'uploads/profiles/profile_1784166512_45.png', '1997-08-01', 'Male', 'uploads/kyc/nid_1784167428.jpeg', '', '', '', '', 0.00, 0.00, 'Rupgonj,narayongonj', 'partner', 1, '6903170048', 'FS-USER-00006', 'Dhaka', 0, 0, NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_recently_viewed`
--

CREATE TABLE `user_recently_viewed` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `viewed_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_withdrawals`
--

CREATE TABLE `user_withdrawals` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount_coins` decimal(10,2) NOT NULL,
  `payout_method` varchar(50) NOT NULL,
  `payout_account` varchar(100) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `processed_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `affiliate_partners`
--
ALTER TABLE `affiliate_partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agents`
--
ALTER TABLE `agents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `ref_code` (`ref_code`);

--
-- Indexes for table `agent_commissions`
--
ALTER TABLE `agent_commissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `agent_payouts`
--
ALTER TABLE `agent_payouts`
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
-- Indexes for table `api_invoices`
--
ALTER TABLE `api_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_partner_id` (`api_partner_id`);

--
-- Indexes for table `api_partners`
--
ALTER TABLE `api_partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `api_key` (`api_key`);

--
-- Indexes for table `api_usage_log`
--
ALTER TABLE `api_usage_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `api_partner_id` (`api_partner_id`);

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
-- Indexes for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `coin_wallets`
--
ALTER TABLE `coin_wallets`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `dropship_connections`
--
ALTER TABLE `dropship_connections`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `escrow_transactions`
--
ALTER TABLE `escrow_transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `gixsam_settings`
--
ALTER TABLE `gixsam_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `homepage_settings`
--
ALTER TABLE `homepage_settings`
  ADD PRIMARY KEY (`setting_key`);

--
-- Indexes for table `internal_messages`
--
ALTER TABLE `internal_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `jobs`
--
ALTER TABLE `jobs`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `job_bids`
--
ALTER TABLE `job_bids`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `marketplace_conversations`
--
ALTER TABLE `marketplace_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `marketplace_messages`
--
ALTER TABLE `marketplace_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `conversation_id` (`conversation_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `partner_disputes`
--
ALTER TABLE `partner_disputes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `partner_kyc`
--
ALTER TABLE `partner_kyc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `partner_orders`
--
ALTER TABLE `partner_orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `partner_id` (`partner_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `partner_products`
--
ALTER TABLE `partner_products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `partner_product_images`
--
ALTER TABLE `partner_product_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `partner_ratings`
--
ALTER TABLE `partner_ratings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `partner_requests`
--
ALTER TABLE `partner_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `partner_settings`
--
ALTER TABLE `partner_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `partner_wishlist`
--
ALTER TABLE `partner_wishlist`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customer_id` (`customer_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `partner_withdrawal_requests`
--
ALTER TABLE `partner_withdrawal_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `partner_id` (`partner_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `shop_tags`
--
ALTER TABLE `shop_tags`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_users`
--
ALTER TABLE `staff_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `trust_directory`
--
ALTER TABLE `trust_directory`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `ref_code` (`ref_code`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `user_pending_cash`
--
ALTER TABLE `user_pending_cash`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_recently_viewed`
--
ALTER TABLE `user_recently_viewed`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`,`product_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `user_withdrawals`
--
ALTER TABLE `user_withdrawals`
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
-- AUTO_INCREMENT for table `agents`
--
ALTER TABLE `agents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `agent_commissions`
--
ALTER TABLE `agent_commissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_payouts`
--
ALTER TABLE `agent_payouts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `agent_tasks`
--
ALTER TABLE `agent_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `agent_task_completions`
--
ALTER TABLE `agent_task_completions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_invoices`
--
ALTER TABLE `api_invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_partners`
--
ALTER TABLE `api_partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `api_usage_log`
--
ALTER TABLE `api_usage_log`
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
-- AUTO_INCREMENT for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `dropship_connections`
--
ALTER TABLE `dropship_connections`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `escrow_transactions`
--
ALTER TABLE `escrow_transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `internal_messages`
--
ALTER TABLE `internal_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `jobs`
--
ALTER TABLE `jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_bids`
--
ALTER TABLE `job_bids`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_conversations`
--
ALTER TABLE `marketplace_conversations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `marketplace_messages`
--
ALTER TABLE `marketplace_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `partner_disputes`
--
ALTER TABLE `partner_disputes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_kyc`
--
ALTER TABLE `partner_kyc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_orders`
--
ALTER TABLE `partner_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_products`
--
ALTER TABLE `partner_products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `partner_product_images`
--
ALTER TABLE `partner_product_images`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `partner_ratings`
--
ALTER TABLE `partner_ratings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_requests`
--
ALTER TABLE `partner_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `partner_settings`
--
ALTER TABLE `partner_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `partner_wishlist`
--
ALTER TABLE `partner_wishlist`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `partner_withdrawal_requests`
--
ALTER TABLE `partner_withdrawal_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `return_requests`
--
ALTER TABLE `return_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `shop_tags`
--
ALTER TABLE `shop_tags`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_tasks`
--
ALTER TABLE `staff_tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `staff_users`
--
ALTER TABLE `staff_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `trust_directory`
--
ALTER TABLE `trust_directory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_pending_cash`
--
ALTER TABLE `user_pending_cash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_recently_viewed`
--
ALTER TABLE `user_recently_viewed`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_withdrawals`
--
ALTER TABLE `user_withdrawals`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `api_invoices`
--
ALTER TABLE `api_invoices`
  ADD CONSTRAINT `api_invoices_ibfk_1` FOREIGN KEY (`api_partner_id`) REFERENCES `api_partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `api_usage_log`
--
ALTER TABLE `api_usage_log`
  ADD CONSTRAINT `api_usage_log_ibfk_1` FOREIGN KEY (`api_partner_id`) REFERENCES `api_partners` (`id`) ON DELETE CASCADE;

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

--
-- Constraints for table `coin_transactions`
--
ALTER TABLE `coin_transactions`
  ADD CONSTRAINT `coin_transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `coin_wallets`
--
ALTER TABLE `coin_wallets`
  ADD CONSTRAINT `coin_wallets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `deposit_requests`
--
ALTER TABLE `deposit_requests`
  ADD CONSTRAINT `deposit_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `marketplace_conversations`
--
ALTER TABLE `marketplace_conversations`
  ADD CONSTRAINT `marketplace_conversations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `marketplace_conversations_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `marketplace_messages`
--
ALTER TABLE `marketplace_messages`
  ADD CONSTRAINT `marketplace_messages_ibfk_1` FOREIGN KEY (`conversation_id`) REFERENCES `marketplace_conversations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_disputes`
--
ALTER TABLE `partner_disputes`
  ADD CONSTRAINT `partner_disputes_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `partner_orders` (`id`);

--
-- Constraints for table `partner_orders`
--
ALTER TABLE `partner_orders`
  ADD CONSTRAINT `partner_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `partner_orders_ibfk_2` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`),
  ADD CONSTRAINT `partner_orders_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `partner_products` (`id`);

--
-- Constraints for table `partner_products`
--
ALTER TABLE `partner_products`
  ADD CONSTRAINT `partner_products_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_product_images`
--
ALTER TABLE `partner_product_images`
  ADD CONSTRAINT `partner_product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `partner_products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `partner_ratings`
--
ALTER TABLE `partner_ratings`
  ADD CONSTRAINT `partner_ratings_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `partner_orders` (`id`),
  ADD CONSTRAINT `partner_ratings_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `partner_ratings_ibfk_3` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`);

--
-- Constraints for table `partner_requests`
--
ALTER TABLE `partner_requests`
  ADD CONSTRAINT `partner_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `partner_wishlist`
--
ALTER TABLE `partner_wishlist`
  ADD CONSTRAINT `partner_wishlist_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `partner_wishlist_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `partner_products` (`id`);

--
-- Constraints for table `partner_withdrawal_requests`
--
ALTER TABLE `partner_withdrawal_requests`
  ADD CONSTRAINT `partner_withdrawal_requests_ibfk_1` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`);

--
-- Constraints for table `return_requests`
--
ALTER TABLE `return_requests`
  ADD CONSTRAINT `return_requests_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `partner_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_requests_ibfk_2` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `return_requests_ibfk_3` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_recently_viewed`
--
ALTER TABLE `user_recently_viewed`
  ADD CONSTRAINT `user_recently_viewed_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_recently_viewed_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `partner_products` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
