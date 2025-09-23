-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 20, 2025 at 03:37 PM
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
-- Database: `k_food_delights`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_roles`
--

CREATE TABLE `admin_roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_roles`
--

INSERT INTO `admin_roles` (`role_id`, `role_name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'Super Admin', 'Full system access with all permissions', '2025-09-03 14:48:30', '2025-09-03 14:48:30'),
(2, 'Admin', 'Administrative access with elevated permissions', '2025-09-03 14:48:30', '2025-09-10 14:55:09'),
(3, 'Crew', 'Staff/crew access with limited permissions', '2025-09-03 14:48:30', '2025-09-10 14:55:09'),
(4, 'Customer', 'Access for customers with basic functionality', '2025-09-10 14:55:09', '2025-09-10 14:55:09');

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `admin_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`admin_id`, `username`, `password`, `email`, `role_id`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', '$2y$10$R9tFUmcAuizMNpvpJ7hv3uWqYyOQqvH.j.6wZi5XQVZgd.spCyDPm', 'admin@kfood.com', 1, 1, '2025-09-20 13:19:20', '2025-09-03 14:48:31', '2025-09-20 13:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action_type` varchar(50) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) DEFAULT NULL,
  `changes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `backup_logs`
--

CREATE TABLE `backup_logs` (
  `backup_id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cart`
--

CREATE TABLE `cart` (
  `cart_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `finished_products`
--

CREATE TABLE `finished_products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `minimum_stock` decimal(10,2) DEFAULT 0.00,
  `selling_price` decimal(10,2) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `minimum_stock` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_transactions`
--

CREATE TABLE `inventory_transactions` (
  `transaction_id` int(11) NOT NULL,
  `transaction_type` enum('stock_in','stock_out','adjustment') NOT NULL,
  `item_type` enum('raw_material','finished_product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT NULL,
  `reference_no` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `landing_content`
--

CREATE TABLE `landing_content` (
  `content_id` int(11) NOT NULL,
  `section_name` varchar(50) NOT NULL,
  `content_type` enum('text','image','html') NOT NULL,
  `content` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `attempt_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `attempt_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_items`
--

CREATE TABLE `menu_items` (
  `item_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `is_available` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `menu_item_ingredients`
--

CREATE TABLE `menu_item_ingredients` (
  `menu_item_id` int(11) NOT NULL,
  `inventory_item_id` int(11) NOT NULL,
  `quantity_required` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `order_number` varchar(50) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('Pending','Preparing','Delivered','Received','Cancelled') DEFAULT 'Pending',
  `payment_status` enum('Pending','Paid','Failed') DEFAULT 'Pending',
  `delivery_address` text NOT NULL,
  `contact_number` varchar(20) NOT NULL,
  `special_instructions` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `promo_id` int(11) DEFAULT NULL,
  `discount_amount` decimal(10,2) DEFAULT 0.00,
  `senior_pwd_discount` decimal(10,2) DEFAULT 0.00,
  `senior_pwd_id` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `customer_name`, `order_number`, `order_date`, `total_amount`, `status`, `payment_status`, `delivery_address`, `contact_number`, `special_instructions`, `created_at`, `updated_at`, `promo_id`, `discount_amount`, `senior_pwd_discount`, `senior_pwd_id`) VALUES
(1, 1, 'kuzano ', 'KFD-20250914-160948-33418254', '2025-09-14 14:09:48', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:09:48', '2025-09-14 14:09:48', NULL, 0.00, 0.00, NULL),
(3, 1, 'kuzano ', 'KFD-20250914-160950-89845873', '2025-09-14 14:09:50', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:09:50', '2025-09-14 14:09:50', NULL, 0.00, 0.00, NULL),
(5, 1, 'kuzano ', 'KFD-20250914-161128-41120789', '2025-09-14 14:11:28', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:11:28', '2025-09-14 14:11:28', NULL, 0.00, 0.00, NULL),
(7, 1, 'kuzano ', 'KFD-20250914-161405-47646417', '2025-09-14 14:14:05', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:14:05', '2025-09-14 14:14:05', NULL, 0.00, 0.00, NULL),
(9, 1, 'kuzano ', 'KFD-20250914-161457-23527985', '2025-09-14 14:14:57', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:14:57', '2025-09-14 14:14:57', NULL, 0.00, 0.00, NULL),
(11, 1, 'kuzano ', 'KFD-20250914-161543-0001-a17377af', '2025-09-14 14:15:43', 8990.00, 'Pending', 'Pending', 'prewowi06ker ypoery', '09123175615', '', '2025-09-14 14:15:43', '2025-09-14 14:15:43', NULL, 0.00, 0.00, NULL),
(13, 1, 'Kuya Matty ', 'KFD-20250914-161600-0001-40c6ae06', '2025-09-14 14:16:00', 8990.00, 'Pending', 'Pending', '123123v123v', '09123175615', '1v2312v123v', '2025-09-14 14:16:00', '2025-09-14 14:16:00', NULL, 0.00, 0.00, NULL),
(15, 1, 'uyyuykyu', 'KFD-20250914-163912-08642228', '2025-09-14 14:39:12', 10930.00, 'Pending', 'Pending', 'vr23rv2', '09123175615', 'v24v24v24v24', '2025-09-14 14:39:12', '2025-09-14 14:39:12', NULL, 0.00, 0.00, NULL),
(17, 1, 'Kuzo Miyamoto', 'KFD-20250914-164417-14276482', '2025-09-14 14:44:17', 10930.00, 'Pending', 'Pending', 'St. Italy 2000 Lot 301', '09123456789', '', '2025-09-14 14:44:17', '2025-09-14 14:44:17', NULL, 0.00, 0.00, NULL),
(19, 1, '231231c3', 'KFD-20250914-175156-18837567', '2025-09-14 15:51:56', 10930.00, 'Pending', 'Pending', 'tnetnen865n568n', '09123456789', '', '2025-09-14 15:51:56', '2025-09-14 15:51:56', NULL, 0.00, 0.00, NULL),
(21, 1, '231231c3', 'KFD-20250914-175327-91101719', '2025-09-14 15:53:27', 10930.00, 'Pending', 'Pending', 'tnetnen865n568n', '09123456789', '', '2025-09-14 15:53:27', '2025-09-14 15:53:27', NULL, 0.00, 0.00, NULL),
(23, 1, 'Kuzo Miyamoto', 'KFD-20250914-175807-26117311', '2025-09-14 15:58:07', 10930.00, 'Pending', 'Pending', 'taga Quezon City at kanto ng pagliko ng mga desisyon sa buhay.', '09123175615', '', '2025-09-14 15:58:07', '2025-09-14 15:58:07', NULL, 0.00, 0.00, NULL),
(27, 1, 'Kuzo Miyamoto', 'KFD-20250914-181056-01242082', '2025-09-14 16:10:56', 10930.00, 'Pending', 'Pending', 'taga Quezon City at kanto ng pagliko ng mga desisyon sa buhay.', '09123175615', '', '2025-09-14 16:10:56', '2025-09-14 16:10:56', NULL, 0.00, 0.00, NULL),
(28, 1, 'Kuzo Miyamoto', 'KFD-20250914-183950-78965458', '2025-09-14 16:39:50', 1830.00, 'Pending', 'Pending', 'taga Quezon City at kanto ng pagliko ng mga desisyon sa buhay.', '09123175615', '', '2025-09-14 16:39:50', '2025-09-14 16:39:50', NULL, 0.00, 0.00, NULL),
(29, 5, 'Testing Last', 'KFD-20250914-211731-02566676', '2025-09-14 19:17:31', 3170.00, 'Pending', 'Paid', 'Test Address - Delivery TBD', '09123172645', NULL, '2025-09-14 19:17:31', '2025-09-14 19:17:31', NULL, 0.00, 0.00, NULL);

--
-- Triggers `orders`
--
DELIMITER $$
CREATE TRIGGER `order_status_history_trigger` AFTER UPDATE ON `orders` FOR EACH ROW BEGIN
        IF OLD.status != NEW.status THEN
            INSERT INTO order_status_history (order_id, status, notes)
            VALUES (NEW.order_id, NEW.status, CONCAT('Status changed from ', OLD.status, ' to ', NEW.status));
        END IF;
    END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `item_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`item_id`, `order_id`, `product_name`, `quantity`, `price`, `subtotal`) VALUES
(1, 7, 'Pastil', 17, 170.00, 2890.00),
(2, 7, 'Sushi', 11, 250.00, 2750.00),
(3, 7, 'Lasagna', 11, 300.00, 3300.00),
(4, 9, 'Pastil', 17, 170.00, 2890.00),
(5, 9, 'Sushi', 11, 250.00, 2750.00),
(6, 9, 'Lasagna', 11, 300.00, 3300.00),
(7, 11, 'Pastil', 17, 170.00, 2890.00),
(8, 11, 'Sushi', 11, 250.00, 2750.00),
(9, 11, 'Lasagna', 11, 300.00, 3300.00),
(10, 13, 'Pastil', 17, 170.00, 2890.00),
(11, 13, 'Sushi', 11, 250.00, 2750.00),
(12, 13, 'Lasagna', 11, 300.00, 3300.00),
(13, 15, 'Pastil', 19, 170.00, 3230.00),
(14, 15, 'Sushi', 15, 250.00, 3750.00),
(15, 15, 'Lasagna', 13, 300.00, 3900.00),
(16, 17, 'Pastil', 19, 170.00, 3230.00),
(17, 17, 'Sushi', 15, 250.00, 3750.00),
(18, 17, 'Lasagna', 13, 300.00, 3900.00),
(19, 19, 'Pastil', 19, 170.00, 3230.00),
(20, 19, 'Sushi', 15, 250.00, 3750.00),
(21, 19, 'Lasagna', 13, 300.00, 3900.00),
(22, 21, 'Pastil', 19, 170.00, 3230.00),
(23, 21, 'Sushi', 15, 250.00, 3750.00),
(24, 21, 'Lasagna', 13, 300.00, 3900.00),
(25, 23, 'Pastil', 19, 170.00, 3230.00),
(26, 23, 'Sushi', 15, 250.00, 3750.00),
(27, 23, 'Lasagna', 13, 300.00, 3900.00),
(34, 27, 'Pastil', 19, 170.00, 3230.00),
(35, 27, 'Sushi', 15, 250.00, 3750.00),
(36, 27, 'Lasagna', 13, 300.00, 3900.00),
(37, 28, 'Pastil', 4, 170.00, 680.00),
(38, 28, 'Sushi', 2, 250.00, 500.00),
(39, 28, 'Lasagna', 2, 300.00, 600.00),
(40, 29, 'Pastil', 1, 170.00, 170.00),
(41, 29, 'Sushi', 1, 250.00, 250.00),
(42, 29, 'Lasagna', 9, 300.00, 2700.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_receipts`
--

CREATE TABLE `order_receipts` (
  `receipt_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `pdf_path` varchar(255) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `order_status_history`
--

CREATE TABLE `order_status_history` (
  `history_id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` varchar(50) NOT NULL,
  `status_updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_status_history`
--

INSERT INTO `order_status_history` (`history_id`, `order_id`, `status`, `status_updated_at`, `notes`) VALUES
(1, 27, 'Pending', '2025-09-14 16:10:56', 'Order placed successfully'),
(2, 28, 'Pending', '2025-09-14 16:39:50', 'Order placed successfully'),
(3, 29, 'Pending', '2025-09-14 19:17:31', 'Order placed successfully');

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `permission_id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`permission_id`, `permission_name`, `description`, `created_at`) VALUES
(1, 'manage_system', 'Control system maintenance and settings', '2025-09-03 14:48:30'),
(2, 'manage_content', 'Manage landing page content and branding', '2025-09-03 14:48:30'),
(3, 'manage_roles', 'Create and modify user roles', '2025-09-03 14:48:30'),
(4, 'manage_users', 'Manage user accounts', '2025-09-03 14:48:30'),
(5, 'manage_inventory', 'Control inventory items and stock', '2025-09-03 14:48:30'),
(6, 'manage_menu', 'Manage menu items and categories', '2025-09-03 14:48:30'),
(7, 'view_reports', 'Access to various system reports', '2025-09-03 14:48:30'),
(8, 'manage_orders', 'Handle order processing and status updates', '2025-09-03 14:48:30');

-- --------------------------------------------------------

--
-- Table structure for table `product_categories`
--

CREATE TABLE `product_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `profile_updates`
--

CREATE TABLE `profile_updates` (
  `update_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `field_name` varchar(50) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `promotions`
--

CREATE TABLE `promotions` (
  `promo_id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `type` enum('percentage','fixed','buy_x_get_y','senior_pwd') DEFAULT NULL,
  `discount_value` decimal(10,2) DEFAULT NULL,
  `min_purchase` decimal(10,2) DEFAULT NULL,
  `max_discount` decimal(10,2) DEFAULT NULL,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promotions`
--

INSERT INTO `promotions` (`promo_id`, `code`, `type`, `discount_value`, `min_purchase`, `max_discount`, `start_date`, `end_date`, `status`, `created_at`) VALUES
(1, 'SENIOR20', 'senior_pwd', 20.00, 0.00, 1000.00, NULL, NULL, 'active', '2025-09-14 18:00:38'),
(2, 'PWD15', 'senior_pwd', 15.00, 0.00, 1000.00, NULL, NULL, 'active', '2025-09-14 18:00:38'),
(3, 'WELCOME10', 'percentage', 10.00, 500.00, 200.00, NULL, NULL, 'active', '2025-09-14 18:00:38'),
(4, 'BUY3GET1', 'buy_x_get_y', 100.00, 0.00, NULL, NULL, NULL, 'active', '2025-09-14 18:00:38');

-- --------------------------------------------------------

--
-- Table structure for table `promo_items`
--

CREATE TABLE `promo_items` (
  `promo_item_id` int(11) NOT NULL,
  `promo_id` int(11) DEFAULT NULL,
  `buy_quantity` int(11) DEFAULT NULL,
  `free_quantity` int(11) DEFAULT NULL,
  `product_category` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `promo_items`
--

INSERT INTO `promo_items` (`promo_item_id`, `promo_id`, `buy_quantity`, `free_quantity`, `product_category`) VALUES
(1, 4, 3, 1, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `raw_materials`
--

CREATE TABLE `raw_materials` (
  `material_id` int(11) NOT NULL,
  `material_name` varchar(100) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `unit` varchar(20) NOT NULL,
  `current_stock` decimal(10,2) DEFAULT 0.00,
  `minimum_stock` decimal(10,2) DEFAULT 0.00,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `expiration_date` date DEFAULT NULL,
  `storage_location` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `remember_tokens`
--

CREATE TABLE `remember_tokens` (
  `token_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`, `description`, `created_at`) VALUES
(1, 'Administrator', 'Full system access', '2025-09-03 14:48:52'),
(2, 'Manager', 'Management access with some restrictions', '2025-09-03 14:48:52'),
(3, 'Staff', 'Basic staff access', '2025-09-03 14:48:52');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`role_id`, `permission_id`) VALUES
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(1, 7),
(1, 8);

-- --------------------------------------------------------

--
-- Table structure for table `stock_alerts`
--

CREATE TABLE `stock_alerts` (
  `alert_id` int(11) NOT NULL,
  `item_type` enum('raw_material','finished_product') NOT NULL,
  `item_id` int(11) NOT NULL,
  `alert_type` enum('low_stock','expiring') NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `supplier_id` int(11) NOT NULL,
  `supplier_name` varchar(100) NOT NULL,
  `contact_person` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `setting_id` int(11) NOT NULL,
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT 'default.jpg',
  `is_active` tinyint(1) DEFAULT 1,
  `remember_token` varchar(100) DEFAULT NULL,
  `password_reset_token` varchar(100) DEFAULT NULL,
  `password_reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `role_id` int(11) NOT NULL,
  `last_login` timestamp NOT NULL DEFAULT current_timestamp(),
  `account_status` varchar(20) DEFAULT 'active' COMMENT 'Values: active, inactive, suspended',
  `login_attempts` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `email`, `first_name`, `last_name`, `phone`, `address`, `profile_picture`, `is_active`, `remember_token`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`, `role_id`, `last_login`, `account_status`, `login_attempts`) VALUES
(1, 'Elvis001', '$2y$10$J//wAKyTBW9hzEQVzfMzjee/BCO7Pd4a9zypzosqNAQpkp6HqcMh2', 'kuzo_miyamoto1000@gmail.com', 'Kuzo', 'Yosuke', '09123175615', 'Quezon City, Philam—Edsa', '68b8438e18782.png', 1, NULL, NULL, NULL, '2025-09-03 13:32:11', '2025-09-20 12:56:01', 4, '2025-09-20 12:56:01', 'active', 0),
(2, 'firstNLast', '$2y$10$e0o.Y1AtJeG4WePUzTFSret6w.5SKJte3kqc7XAdeCzg3Y.s/5d.K', 'first_last@fairview.sti.edu.ph', 'Last', 'N First', '', '', 'default.jpg', 1, NULL, NULL, NULL, '2025-09-03 14:05:35', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(3, 'newAko', '$2y$10$J/hvEFlhf4Pphqc8q.2LAuVf7t9tE2BuQu1NJjS2ZXdt476jMi/HS', 'new@gmail.com', 'new', 'Ako', NULL, NULL, 'default.jpg', 1, NULL, NULL, NULL, '2025-09-03 15:25:07', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(4, 'nonuser123', '$2y$10$ILOWZjUfUc3Bq3J40ulv1ehM3fsrXvd0fasnQicvJ1LHYB393WvkO', 'nonuser123@yahoo.com', 'Existing Na', 'User ako', '', 'Sa may kanto ng fairmont.', 'default.jpg', 1, NULL, NULL, NULL, '2025-09-03 16:26:54', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(5, 'testingone', '$2y$10$RlOmotHFZg3lI2eZVxL/PeGmpT3Nomqbv21/8eO49W.NWYY4aE70q', 'yusho_kina@ymail.com', 'Isha', 'Kina', '09123172645', '', '68c108d8d23f0.jpg', 1, NULL, NULL, NULL, '2025-09-08 12:44:53', '2025-09-14 20:21:14', 4, '2025-09-14 20:20:20', 'active', 0),
(7, 'yassymissu', '$2y$10$H/8bqNhTRQ6pBHvEgcyMBuKs.WJIXfDs/07AP42a2hE6JRy2EDWym', 'akalakobatayona@gmail.com', 'yasuo', 'yassymissu', NULL, NULL, 'default.jpg', 1, NULL, NULL, NULL, '2025-09-10 06:11:50', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(8, 'userOne', '$2y$10$97h1yRSS6gJ5iFxqnjR./O.nhBJm4WpRier8jN5iEILFrN/CBJRnq', 'userOne123@ymail.com', 'user', 'one', NULL, NULL, 'default.jpg', 1, NULL, NULL, NULL, '2025-09-10 07:02:09', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(9, 'jeff', '$2y$10$bQSdHFQADmuVkhTeCkgbj.vL9e5ZcGe8eocnzP4YN3bWh7NcpdQiS', 'j@gmail.com', 'jeff', 'fel', NULL, NULL, 'default.jpg', 1, NULL, NULL, NULL, '2025-09-10 11:34:27', '2025-09-10 17:43:24', 4, '2025-09-14 20:20:20', 'active', 0),
(12, 'FirstLast', '$2y$10$Y.rDVKBkVVOAMMkNvQuY0uzeWcPiIn3wSPZMlqBu32lwOA4ZjHg1i', 'first_last@ymail.com', 'first', 'last', '', '', NULL, 1, NULL, NULL, NULL, '2025-09-14 21:31:19', '2025-09-14 21:31:19', 2, '2025-09-14 21:31:19', 'active', 0),
(13, 'FluxLubian', '$2y$10$pYMSvHJdZ5O8Oiv1vFVI1u8LcTFuK3h6ZBLc8kk5kE5xSdCTEuYrK', 'fluxlubian@fairview.sti.edu.ph', 'Lubian', 'Flux', '09123175615', 'wvqweasl;fknv12412v124', NULL, 0, NULL, NULL, NULL, '2025-09-14 21:32:41', '2025-09-14 21:32:41', 4, '2025-09-14 21:32:41', 'active', 0),
(14, 'admin111', '$2y$10$iVCgj6ChIhiejMlyvqleEuBgUdsca1rCVSqBLkqG5.f0a8IROo1fy', 'ads234@gmail.com', 'adssss', 'adsi', '', '', NULL, 1, NULL, NULL, NULL, '2025-09-17 19:12:28', '2025-09-17 19:12:28', 2, '2025-09-17 19:12:28', 'active', 0),
(15, 'admin123', '$2y$10$fNlq5mSw72XUFgS5/hkdI.TA/.ZD47ueNBBsfJjEVK6JgBrdacCrS', 'addasdads234@gmail.com', 'adssssdadsad', 'adsidasdad', '', '', NULL, 1, NULL, NULL, NULL, '2025-09-17 19:39:31', '2025-09-17 20:08:17', 2, '2025-09-17 20:08:17', 'active', 0),
(16, 'crew1', '$2y$10$rZE1U7u4zVhhvxdZK2yV0ekGoXQ0217XBWRV/5Tr9gsH0Tewth.fq', 'crwe@gmail.com', 'crew', 'werr', '09944767482', '', NULL, 1, NULL, NULL, NULL, '2025-09-17 19:47:36', '2025-09-17 20:04:59', 3, '2025-09-17 20:04:59', 'active', 0),
(18, 'jeff01', '$2y$10$eoxmiOxcWQ0loWZLR6I/mOv64uP4KB9ujq58G5V/DoGdYRB6xPAUO', 'jffciano4000@gmail.com', 'jefffski', 'last', NULL, NULL, 'default.jpg', 1, NULL, NULL, NULL, '2025-09-17 20:15:21', '2025-09-17 20:15:21', 4, '2025-09-17 20:15:21', 'active', 0),
(19, 'cust', '$2y$10$G/blr4frM9GfCNUEhEhRg.Pe2GBKwR6Cm5KNyFYC26JqISU/6RBrO', 'cust@gmail.com', 'cust', 'tomer', '09944767234', 'Kaypian Hils', NULL, 1, NULL, NULL, NULL, '2025-09-17 20:16:41', '2025-09-17 20:16:59', 4, '2025-09-17 20:16:59', 'active', 0),
(20, 'elvisadmin', '$2y$10$odKJiBG3S8LTGpTviDSNleM2oA5CxIVNIPV4.aVKi3BXhE/zbiFm.', 'elvis001@ymail.com', 'Elvis', 'selisana', '', '', NULL, 1, NULL, NULL, NULL, '2025-09-20 09:10:53', '2025-09-20 09:10:53', 2, '2025-09-20 09:10:53', 'active', 0),
(21, 'crew123', '$2y$10$EpG4XUYCwWqY1bFzT97K5ueCnE2h.AwXqb6auvdRSrRDegubsTwsG', 'crew@gmail.com', 'crew', 'csa23e12', '09123172645', '', NULL, 1, NULL, NULL, NULL, '2025-09-20 09:19:59', '2025-09-20 12:52:51', 3, '2025-09-20 12:52:51', 'active', 0),
(22, 'elvisadmin01', '$2y$10$PSOS1F1YurLDtqanG11w9umPd7TUTZMo37gwBUggvIiqfCyD0JIJS', 'asd@yahoo.com', 'elvis', 'ast1v35v3', '', '', NULL, 1, NULL, NULL, NULL, '2025-09-20 13:20:13', '2025-09-20 13:26:34', 2, '2025-09-20 13:26:34', 'active', 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `session_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_roles`
--
ALTER TABLE `admin_roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`);

--
-- Indexes for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `backup_logs`
--
ALTER TABLE `backup_logs`
  ADD PRIMARY KEY (`backup_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `cart`
--
ALTER TABLE `cart`
  ADD PRIMARY KEY (`cart_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `finished_products`
--
ALTER TABLE `finished_products`
  ADD PRIMARY KEY (`product_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD PRIMARY KEY (`transaction_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `landing_content`
--
ALTER TABLE `landing_content`
  ADD PRIMARY KEY (`content_id`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_ip_address` (`ip_address`),
  ADD KEY `idx_attempt_time` (`attempt_time`);

--
-- Indexes for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `category_id` (`category_id`);

--
-- Indexes for table `menu_item_ingredients`
--
ALTER TABLE `menu_item_ingredients`
  ADD PRIMARY KEY (`menu_item_id`,`inventory_item_id`),
  ADD KEY `inventory_item_id` (`inventory_item_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`order_id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD UNIQUE KEY `idx_order_number` (`order_number`),
  ADD KEY `fk_orders_user` (`user_id`),
  ADD KEY `promo_id` (`promo_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`item_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_receipts`
--
ALTER TABLE `order_receipts`
  ADD PRIMARY KEY (`receipt_id`),
  ADD UNIQUE KEY `receipt_number` (`receipt_number`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD PRIMARY KEY (`history_id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`permission_id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `product_categories`
--
ALTER TABLE `product_categories`
  ADD PRIMARY KEY (`category_id`);

--
-- Indexes for table `profile_updates`
--
ALTER TABLE `profile_updates`
  ADD PRIMARY KEY (`update_id`),
  ADD KEY `idx_user_updates` (`user_id`,`updated_at`);

--
-- Indexes for table `promotions`
--
ALTER TABLE `promotions`
  ADD PRIMARY KEY (`promo_id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `promo_items`
--
ALTER TABLE `promo_items`
  ADD PRIMARY KEY (`promo_item_id`),
  ADD KEY `promo_id` (`promo_id`);

--
-- Indexes for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD PRIMARY KEY (`token_id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD PRIMARY KEY (`alert_id`),
  ADD KEY `resolved_by` (`resolved_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`supplier_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `fk_users_role` (`role_id`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_roles`
--
ALTER TABLE `admin_roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `backup_logs`
--
ALTER TABLE `backup_logs`
  MODIFY `backup_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cart`
--
ALTER TABLE `cart`
  MODIFY `cart_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `finished_products`
--
ALTER TABLE `finished_products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  MODIFY `transaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `landing_content`
--
ALTER TABLE `landing_content`
  MODIFY `content_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `menu_items`
--
ALTER TABLE `menu_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `order_receipts`
--
ALTER TABLE `order_receipts`
  MODIFY `receipt_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `order_status_history`
--
ALTER TABLE `order_status_history`
  MODIFY `history_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `permission_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `product_categories`
--
ALTER TABLE `product_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `profile_updates`
--
ALTER TABLE `profile_updates`
  MODIFY `update_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `promotions`
--
ALTER TABLE `promotions`
  MODIFY `promo_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `promo_items`
--
ALTER TABLE `promo_items`
  MODIFY `promo_item_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `raw_materials`
--
ALTER TABLE `raw_materials`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  MODIFY `token_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `role_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  MODIFY `alert_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `supplier_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD CONSTRAINT `admin_users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`role_id`);

--
-- Constraints for table `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `backup_logs`
--
ALTER TABLE `backup_logs`
  ADD CONSTRAINT `backup_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `cart`
--
ALTER TABLE `cart`
  ADD CONSTRAINT `cart_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `finished_products`
--
ALTER TABLE `finished_products`
  ADD CONSTRAINT `finished_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`),
  ADD CONSTRAINT `finished_products_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`);

--
-- Constraints for table `inventory_transactions`
--
ALTER TABLE `inventory_transactions`
  ADD CONSTRAINT `inventory_transactions_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `landing_content`
--
ALTER TABLE `landing_content`
  ADD CONSTRAINT `landing_content_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD CONSTRAINT `login_attempts_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `menu_items`
--
ALTER TABLE `menu_items`
  ADD CONSTRAINT `menu_items_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `product_categories` (`category_id`);

--
-- Constraints for table `menu_item_ingredients`
--
ALTER TABLE `menu_item_ingredients`
  ADD CONSTRAINT `menu_item_ingredients_ibfk_1` FOREIGN KEY (`menu_item_id`) REFERENCES `menu_items` (`item_id`),
  ADD CONSTRAINT `menu_item_ingredients_ibfk_2` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`item_id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `order_receipts`
--
ALTER TABLE `order_receipts`
  ADD CONSTRAINT `order_receipts_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`);

--
-- Constraints for table `order_status_history`
--
ALTER TABLE `order_status_history`
  ADD CONSTRAINT `order_status_history_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_updates`
--
ALTER TABLE `profile_updates`
  ADD CONSTRAINT `profile_updates_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `promo_items`
--
ALTER TABLE `promo_items`
  ADD CONSTRAINT `promo_items_ibfk_1` FOREIGN KEY (`promo_id`) REFERENCES `promotions` (`promo_id`);

--
-- Constraints for table `raw_materials`
--
ALTER TABLE `raw_materials`
  ADD CONSTRAINT `raw_materials_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`category_id`),
  ADD CONSTRAINT `raw_materials_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`supplier_id`),
  ADD CONSTRAINT `raw_materials_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `remember_tokens`
--
ALTER TABLE `remember_tokens`
  ADD CONSTRAINT `remember_tokens_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`role_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`permission_id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_alerts`
--
ALTER TABLE `stock_alerts`
  ADD CONSTRAINT `stock_alerts_ibfk_1` FOREIGN KEY (`resolved_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD CONSTRAINT `suppliers_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admin_users` (`admin_id`) ON DELETE SET NULL;

--
-- Constraints for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD CONSTRAINT `system_settings_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `admin_users` (`admin_id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `admin_roles` (`role_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
