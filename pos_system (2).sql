-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2026 at 05:06 PM
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
-- Database: `pos_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `branch_name` varchar(100) NOT NULL,
  `location` varchar(255) DEFAULT '',
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `branch_name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Minute Burger Salay', 'Salay', 'active', '2026-07-15 12:16:59', '2026-07-15 12:16:59'),
(2, 'Minute Burger Cagayan de Oro', 'Cagayan de Oro', 'active', '2026-07-15 12:16:59', '2026-07-15 12:16:59'),
(3, 'Minute Burger Jasaan', 'Jasaan', 'active', '2026-07-15 12:16:59', '2026-07-15 12:16:59');

-- --------------------------------------------------------

--
-- Table structure for table `cashier_inventory_counts`
--

CREATE TABLE `cashier_inventory_counts` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL DEFAULT 0,
  `counted_stock` int(11) NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `counted_by` int(11) NOT NULL,
  `counted_by_name` varchar(255) NOT NULL,
  `counted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cashier_shifts`
--

CREATE TABLE `cashier_shifts` (
  `id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `shift_date` date NOT NULL,
  `shift_type` enum('AM','PM') NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime DEFAULT NULL,
  `opening_cash` decimal(10,2) DEFAULT 0.00,
  `closing_cash` decimal(10,2) DEFAULT 0.00,
  `cash_drop_total` decimal(10,2) DEFAULT 0.00,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `total_transactions` int(11) DEFAULT 0,
  `status` enum('active','closed') DEFAULT 'active',
  `started_by` int(11) NOT NULL,
  `closed_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `shift_quota` decimal(10,2) DEFAULT 10000.00,
  `late_start` tinyint(1) DEFAULT 0,
  `late_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cashier_shifts`
--

INSERT INTO `cashier_shifts` (`id`, `cashier_id`, `shift_date`, `shift_type`, `start_time`, `end_time`, `opening_cash`, `closing_cash`, `cash_drop_total`, `total_sales`, `total_transactions`, `status`, `started_by`, `closed_by`, `created_at`, `shift_quota`, `late_start`, `late_minutes`) VALUES
(22, 3, '2026-03-23', 'AM', '2026-03-23 12:38:35', '2026-05-24 07:21:19', 894.00, 894.00, 0.00, 0.00, 0, 'closed', 3, 3, '2026-03-23 04:38:35', 4472.00, 1, 398),
(25, 3, '2026-06-25', 'AM', '2026-06-25 07:56:06', NULL, 2000.00, 0.00, 0.00, 0.00, 0, 'active', 3, NULL, '2026-06-24 23:56:06', 10000.00, 0, 0),
(26, 1, '2026-06-30', 'PM', '2026-06-30 23:35:12', '2026-06-30 23:36:41', 21211.00, 21211.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-06-30 15:35:12', 10000.00, 0, 0),
(27, 1, '2026-07-01', 'AM', '2026-07-01 12:46:19', '2026-07-01 13:11:40', 6666.00, 6666.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-01 04:46:19', 10000.00, 0, 0),
(28, 1, '2026-07-03', 'AM', '2026-07-03 09:34:43', '2026-07-03 09:35:04', 1406.00, 1406.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-03 01:34:43', 7028.00, 1, 214),
(29, 1, '2026-07-03', 'AM', '2026-07-03 10:10:50', '2026-07-03 10:14:33', 1306.00, 1364.00, 0.00, 58.00, 1, 'closed', 1, 1, '2026-07-03 02:10:50', 6528.00, 1, 250),
(30, 1, '2026-07-03', 'AM', '2026-07-03 10:22:00', '2026-07-04 09:06:46', 1275.00, 1275.00, 0.00, 0.00, 0, 'closed', 1, 1, '2026-07-03 02:22:00', 6375.00, 1, 262),
(31, 1, '2026-07-04', 'AM', '2026-07-04 09:07:34', '2026-07-04 09:07:58', 1481.00, 1751.00, 0.00, 270.00, 1, 'closed', 1, 1, '2026-07-04 01:07:34', 7403.00, 1, 187),
(32, 1, '2026-07-04', 'AM', '2026-07-04 09:08:21', NULL, 1478.00, 0.00, 0.00, 0.00, 0, 'active', 1, NULL, '2026-07-04 01:08:21', 7389.00, 1, 188),
(33, 13, '2026-07-15', 'AM', '2026-07-15 14:17:21', '2026-07-15 14:17:31', 619.00, 619.00, 0.00, 0.00, 0, 'closed', 13, 13, '2026-07-15 06:17:21', 3097.00, 1, 497),
(34, 13, '2026-07-15', 'AM', '2026-07-15 15:02:22', NULL, 0.00, 0.00, 0.00, 0.00, 0, 'active', 13, NULL, '2026-07-15 07:02:22', 10000.00, 0, 0),
(35, 16, '2026-07-15', 'AM', '2026-07-15 21:02:17', '2026-07-15 21:02:47', 0.00, 0.00, 0.00, 0.00, 0, 'closed', 16, 16, '2026-07-15 13:02:17', 10000.00, 0, 0),
(36, 15, '2026-07-15', 'AM', '2026-07-15 23:38:19', NULL, 0.00, 0.00, 0.00, 0.00, 0, 'active', 15, NULL, '2026-07-15 15:38:19', 10000.00, 0, 0),
(37, 19, '2026-07-15', 'PM', '2026-07-15 23:52:28', '2026-07-15 23:59:12', 1533.00, 1533.00, 0.00, 0.00, 0, 'closed', 19, 19, '2026-07-15 15:52:28', 5111.00, 1, 352),
(38, 19, '2026-07-15', 'PM', '2026-07-15 23:59:33', '2026-07-16 00:01:48', 1504.00, 1504.00, 0.00, 0.00, 0, 'closed', 19, 19, '2026-07-15 15:59:33', 5014.00, 1, 359),
(39, 19, '2026-07-15', 'PM', '2026-07-16 00:02:03', '2026-07-16 22:03:32', 3000.00, 3090.00, 0.00, 90.00, 1, 'closed', 19, 19, '2026-07-15 16:02:03', 10000.00, 0, 0),
(40, 17, '2026-07-15', 'PM', '2026-07-16 01:04:29', NULL, 0.00, 0.00, 0.00, 0.00, 0, 'active', 17, NULL, '2026-07-15 17:04:29', 10000.00, 0, 0),
(41, 16, '2026-07-15', 'PM', '2026-07-16 01:16:36', NULL, 0.00, 0.00, 0.00, 0.00, 0, 'active', 16, NULL, '2026-07-15 17:16:36', 10000.00, 0, 0),
(42, 19, '2026-07-16', 'PM', '2026-07-16 22:03:44', '2026-07-16 22:03:48', 1988.00, 1988.00, 0.00, 0.00, 0, 'closed', 19, 19, '2026-07-16 14:03:44', 6625.00, 1, 243);

-- --------------------------------------------------------

--
-- Table structure for table `cash_drop_log`
--

CREATE TABLE `cash_drop_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `cashier_id` int(11) NOT NULL,
  `drop_amount` decimal(10,2) NOT NULL,
  `drop_reason` varchar(255) DEFAULT NULL,
  `drop_time` datetime NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `discount_type` enum('none','senior','pwd') DEFAULT 'none',
  `discount_id` varchar(50) DEFAULT NULL,
  `contact_number` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ingredient_templates`
--

CREATE TABLE `ingredient_templates` (
  `id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `unit` varchar(20) DEFAULT 'piece',
  `category` varchar(50) DEFAULT 'Uncategorized',
  `default_min_stock` int(11) DEFAULT 10,
  `default_cost_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ingredient_templates`
--

INSERT INTO `ingredient_templates` (`id`, `item_name`, `unit`, `category`, `default_min_stock`, `default_cost_price`, `created_at`) VALUES
(1, 'Bacon', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(2, 'Beef Patties', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(3, 'Black Pepper Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(4, 'Bottled Water', 'bottle', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(5, 'Burger Buns', 'piece', 'Bread & Buns', 50, 0.00, '2026-07-16 12:53:49'),
(6, 'Burger Patty', 'piece', 'Uncategorized', 10, 0.00, '2026-07-16 12:53:49'),
(7, 'Calamansi Syrup', 'ml', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(8, 'Cheese Slices', 'piece', 'Dairy', 100, 0.00, '2026-07-16 12:53:49'),
(9, 'Chicken Fillet', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(10, 'Chimichurri Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(11, 'Chocolate Powder', 'scoop', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(12, 'Coffee Powder', 'scoop', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(13, 'Coleslaw Mix', 'portion', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(14, 'Cup', 'piece', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(15, 'Egg', 'piece', 'General', 20, 0.00, '2026-07-16 12:53:49'),
(16, 'Fruit Syrup', 'ml', 'General', 50, 0.00, '2026-07-16 12:53:49'),
(17, 'Ice', 'cup', 'General', 100, 0.00, '2026-07-16 12:53:49'),
(18, 'Ketchup', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(19, 'Lettuce', 'piece', 'Vegetables', 20, 0.00, '2026-07-16 12:53:49'),
(20, 'Mayo', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(21, 'Onions', 'piece', 'Vegetables', 25, 0.00, '2026-07-16 12:53:49'),
(22, 'Roasted Sesame Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(23, 'Shawarma Sauce', 'portion', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(24, 'Steak Patty', 'piece', 'General', 10, 0.00, '2026-07-16 12:53:49'),
(25, 'Supreme Cheese', 'piece', 'Cheese', 1000, 0.00, '2026-07-16 12:53:49'),
(26, 'Veggie Patty', 'piece', 'Proteins', 10, 0.00, '2026-07-16 12:53:49'),
(27, 'Water', 'ml', 'General', 100, 0.00, '2026-07-16 12:53:49');

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) DEFAULT 'Uncategorized',
  `quantity` int(11) NOT NULL DEFAULT 0,
  `min_stock` int(11) NOT NULL DEFAULT 10,
  `unit` varchar(20) DEFAULT 'piece',
  `selling_unit` varchar(50) DEFAULT NULL,
  `cost_price` decimal(10,2) DEFAULT 0.00,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `status` varchar(20) DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `supplier` varchar(100) DEFAULT NULL,
  `is_perishable` tinyint(1) DEFAULT 0,
  `shelf_life_days` int(11) DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `reorder_point` int(11) DEFAULT NULL,
  `location_rack` varchar(50) DEFAULT NULL,
  `location_shelf` varchar(50) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `template_id`, `branch_id`, `item_name`, `category`, `quantity`, `min_stock`, `unit`, `selling_unit`, `cost_price`, `last_updated`, `status`, `deleted_at`, `supplier`, `is_perishable`, `shelf_life_days`, `category_id`, `supplier_id`, `reorder_point`, `location_rack`, `location_shelf`, `expiry_date`) VALUES
(0, 17, 1, 'Ice', 'General', 500, 100, 'cup', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(1, 5, 1, 'Burger Buns', 'Bread & Buns', 198, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 6, 1, 'Burger Patty', 'Uncategorized', 100, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 8, 1, 'Cheese Slices', 'Dairy', 2226, 100, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 19, 1, 'Lettuce', 'Vegetables', 283, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 21, 1, 'Onions', 'Vegetables', 382, 25, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(16, 2, 1, 'Beef Patties', 'Proteins', 89, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(18, 9, 1, 'Chicken Fillet', 'Proteins', 290, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(19, 26, 1, 'Veggie Patty', 'Proteins', 124, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(20, 24, 1, 'Steak Patty', 'General', 126, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(21, 3, 1, 'Black Pepper Sauce', 'General', 288, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(22, 10, 1, 'Chimichurri Sauce', 'General', 321, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(23, 22, 1, 'Roasted Sesame Sauce', 'General', 344, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(24, 23, 1, 'Shawarma Sauce', 'General', 282, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(25, 15, 1, 'Egg', 'General', 62, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(26, 13, 1, 'Coleslaw Mix', 'General', 180, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(27, 13, 1, 'Coleslaw Mix', 'General', 50, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(28, 27, 1, 'Water', 'General', 798, 100, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(30, 12, 1, 'Coffee Powder', 'General', 320, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(31, 11, 1, 'Chocolate Powder', 'General', 320, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(32, 7, 1, 'Calamansi Syrup', 'General', 318, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(33, 16, 1, 'Fruit Syrup', 'General', 320, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 14, 1, 'Cup', 'General', 318, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(35, 4, 1, 'Bottled Water', 'General', 157, 20, 'bottle', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(36, 3, 1, 'Black Pepper Sauce', 'General', 25, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(37, 3, 1, 'Black Pepper Sauce', 'General', 78, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(38, 10, 1, 'Chimichurri Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(39, 22, 1, 'Roasted Sesame Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(40, 23, 1, 'Shawarma Sauce', 'General', 62, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(41, 20, 1, 'Mayo', 'General', 206, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(42, 18, 1, 'Ketchup', 'General', 260, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(43, 3, 1, 'Black Pepper Sauce', 'General', 78, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(44, 10, 1, 'Chimichurri Sauce', 'General', 76, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(45, 22, 1, 'Roasted Sesame Sauce', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(46, 23, 1, 'Shawarma Sauce', 'General', 88, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(47, 20, 1, 'Mayo', 'General', 94, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(48, 18, 1, 'Ketchup', 'General', 100, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(49, 9, 1, 'Chicken Fillet', 'Proteins', 123, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(51, 17, 1, 'Ice', 'General', 300, 100, 'cup', NULL, 0.00, '2026-07-16 12:53:49', NULL, NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(52, 5, 1, 'Burger Buns', 'Bread & Buns', 60, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(53, 8, 1, 'Cheese Slices', 'Dairy', 2070, 100, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(54, 19, 1, 'Lettuce', 'Vegetables', 129, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(55, 21, 1, 'Onions', 'Vegetables', 166, 25, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(56, 2, 1, 'Beef Patties', 'Proteins', 51, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(57, 1, 1, 'Bacon', 'Proteins', 33, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(58, 26, 1, 'Veggie Patty', 'Proteins', 46, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(59, 24, 1, 'Steak Patty', 'General', 52, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(60, 22, 1, 'Roasted Sesame Sauce', 'General', 54, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(61, 15, 1, 'Egg', 'General', 23, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(62, 13, 1, 'Coleslaw Mix', 'General', 30, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(63, 27, 1, 'Water', 'General', 299, 100, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(64, 12, 1, 'Coffee Powder', 'General', 120, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(65, 11, 1, 'Chocolate Powder', 'General', 120, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(66, 7, 1, 'Calamansi Syrup', 'General', 119, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(67, 16, 1, 'Fruit Syrup', 'General', 120, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(68, 14, 1, 'Cup', 'General', 119, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(69, 4, 1, 'Bottled Water', 'General', 59, 20, 'bottle', NULL, 0.00, '2026-07-16 12:53:49', NULL, '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(70, 3, 1, 'Black Pepper Sauce', 'General', 29, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(71, 23, 1, 'Shawarma Sauce', 'General', 44, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(72, 20, 1, 'Mayo', 'General', 42, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(73, 18, 1, 'Ketchup', 'General', 60, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(74, 10, 1, 'Chimichurri Sauce', 'General', 45, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(75, 9, 1, 'Chicken Fillet', 'Proteins', 73, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', '2026-07-16 12:53:49', NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(82, 25, 1, 'Supreme Cheese', 'Cheese', 2000, 1000, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(83, 1, 2, 'Bacon', 'Proteins', 180, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(84, 1, 3, 'Bacon', 'Proteins', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(85, 2, 2, 'Beef Patties', 'Proteins', 220, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(86, 2, 3, 'Beef Patties', 'Proteins', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(87, 3, 2, 'Black Pepper Sauce', 'General', 45, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(88, 3, 3, 'Black Pepper Sauce', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(89, 4, 2, 'Bottled Water', 'General', 96, 20, 'bottle', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(90, 4, 3, 'Bottled Water', 'General', 0, 20, 'bottle', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(91, 5, 2, 'Burger Buns', 'Bread & Buns', 340, 50, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(92, 5, 3, 'Burger Buns', 'Bread & Buns', 0, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(93, 6, 2, 'Burger Patty', 'Uncategorized', 325, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(94, 6, 3, 'Burger Patty', 'Uncategorized', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(95, 7, 2, 'Calamansi Syrup', 'General', 28, 50, 'ml', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(96, 7, 3, 'Calamansi Syrup', 'General', 0, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(97, 8, 2, 'Cheese Slices', 'Dairy', 210, 100, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(98, 8, 3, 'Cheese Slices', 'Dairy', 0, 100, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(99, 9, 2, 'Chicken Fillet', 'Proteins', 115, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(100, 9, 3, 'Chicken Fillet', 'Proteins', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(101, 10, 2, 'Chimichurri Sauce', 'General', 36, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(102, 10, 3, 'Chimichurri Sauce', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(103, 11, 2, 'Chocolate Powder', 'General', 18, 50, 'scoop', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(104, 11, 3, 'Chocolate Powder', 'General', 0, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(105, 12, 2, 'Coffee Powder', 'General', 22, 50, 'scoop', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(106, 12, 3, 'Coffee Powder', 'General', 0, 50, 'scoop', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(107, 13, 2, 'Coleslaw Mix', 'General', 30, 20, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(108, 13, 3, 'Coleslaw Mix', 'General', 0, 20, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(109, 14, 2, 'Cup', 'General', 250, 50, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(110, 14, 3, 'Cup', 'General', 0, 50, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(111, 15, 2, 'Egg', 'General', 60, 20, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(112, 15, 3, 'Egg', 'General', 0, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(113, 16, 2, 'Fruit Syrup', 'General', 24, 50, 'ml', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(114, 16, 3, 'Fruit Syrup', 'General', 0, 50, 'ml', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(115, 17, 2, 'Ice', 'General', 400, 100, 'cup', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(116, 17, 3, 'Ice', 'General', 0, 100, 'cup', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(117, 18, 2, 'Ketchup', 'General', 50, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(118, 18, 3, 'Ketchup', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(119, 19, 2, 'Lettuce', 'Vegetables', 85, 20, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(120, 19, 3, 'Lettuce', 'Vegetables', 0, 20, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(121, 20, 2, 'Mayo', 'General', 38, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(122, 20, 3, 'Mayo', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(123, 21, 2, 'Onions', 'Vegetables', 130, 25, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(124, 21, 3, 'Onions', 'Vegetables', 0, 25, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(125, 22, 2, 'Roasted Sesame Sauce', 'General', 40, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(126, 22, 3, 'Roasted Sesame Sauce', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(127, 23, 2, 'Shawarma Sauce', 'General', 35, 10, 'portion', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(128, 23, 3, 'Shawarma Sauce', 'General', 0, 10, 'portion', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(129, 24, 2, 'Steak Patty', 'General', 95, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(130, 24, 3, 'Steak Patty', 'General', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(131, 25, 2, 'Supreme Cheese', 'Cheese', 75, 1000, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(132, 25, 3, 'Supreme Cheese', 'Cheese', 0, 1000, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(133, 26, 2, 'Veggie Patty', 'Proteins', 50, 10, 'piece', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(134, 26, 3, 'Veggie Patty', 'Proteins', 0, 10, 'piece', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(135, 27, 2, 'Water', 'General', 200, 100, 'ml', NULL, 0.00, '2026-07-16 14:01:12', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(136, 27, 3, 'Water', 'General', 0, 100, 'ml', NULL, 0.00, '2026-07-16 12:53:49', 'active', NULL, NULL, 0, NULL, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_alerts`
--

CREATE TABLE `inventory_alerts` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `alert_type` enum('low_stock','expiring','expired','reorder') NOT NULL,
  `threshold_value` int(11) DEFAULT NULL,
  `current_value` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `status` enum('new','read','resolved') DEFAULT 'new',
  `resolved_at` datetime DEFAULT NULL,
  `resolved_by` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_batches`
--

CREATE TABLE `inventory_batches` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `batch_quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `received_at` datetime NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `manufacture_date` date DEFAULT NULL,
  `purchase_order_id` int(11) DEFAULT NULL,
  `cost_per_unit` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_batches`
--

INSERT INTO `inventory_batches` (`id`, `inventory_id`, `batch_number`, `batch_quantity`, `remaining_quantity`, `received_at`, `expiry_date`, `manufacture_date`, `purchase_order_id`, `cost_per_unit`, `notes`, `created_at`) VALUES
(1, 82, NULL, 1000, 1000, '2026-07-04 03:11:55', '2026-08-04', NULL, NULL, NULL, NULL, '2026-07-04 09:11:55'),
(2, 82, NULL, 1000, 1000, '2026-07-04 03:12:21', NULL, NULL, NULL, NULL, NULL, '2026-07-04 09:12:21'),
(3, 3, NULL, 100, 100, '2026-07-08 16:39:50', '2026-07-15', NULL, NULL, NULL, NULL, '2026-07-08 22:39:50'),
(4, 1, NULL, 100, 100, '2026-07-08 17:24:56', '2026-08-08', NULL, NULL, NULL, NULL, '2026-07-08 23:24:56');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_categories`
--

CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `parent_category_id` int(11) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_counts`
--

CREATE TABLE `inventory_counts` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `system_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `difference` decimal(10,2) NOT NULL DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `counted_by` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `counted_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_deliveries`
--

CREATE TABLE `inventory_deliveries` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `supplier` varchar(255) DEFAULT NULL,
  `expected_date` date NOT NULL,
  `order_date` date DEFAULT NULL,
  `received_date` date DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('upcoming','completed','cancelled') DEFAULT 'upcoming',
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_deliveries`
--

INSERT INTO `inventory_deliveries` (`id`, `inventory_id`, `supplier`, `expected_date`, `order_date`, `received_date`, `quantity`, `status`, `created_at`) VALUES
(1, 51, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(2, 52, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(3, 53, 'Minute Burger Commissary', '2026-03-27', '2026-03-24', NULL, 50, 'upcoming', '2026-03-24 01:54:59'),
(4, 54, 'Local Supplier', '2026-03-19', '2026-03-17', '2026-03-19', 100, 'completed', '2026-03-17 01:54:59'),
(5, 55, 'Local Supplier', '2026-03-19', '2026-03-17', '2026-03-19', 100, 'completed', '2026-03-17 01:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_history`
--

CREATE TABLE `inventory_history` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `change_type` enum('sale','restock','adjustment','waste') NOT NULL,
  `change_date` date NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_history`
--

INSERT INTO `inventory_history` (`id`, `inventory_id`, `item_name`, `previous_quantity`, `new_quantity`, `quantity_change`, `change_type`, `change_date`, `notes`, `created_at`) VALUES
(1, 1, 'Burger Buns', 138, 136, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(2, 16, 'Beef Patties', 38, 36, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(3, 3, 'Cheese Slices', 156, 154, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(4, 4, 'Lettuce', 154, 152, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(5, 6, 'Onions', 216, 214, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(6, 21, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(7, 36, 'Black Pepper Sauce', 25, 23, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(8, 37, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02'),
(9, 43, 'Black Pepper Sauce', 78, 76, -2, 'sale', '2026-07-16', 'Order #75 - sale', '2026-07-16 12:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_log`
--

CREATE TABLE `inventory_log` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `quantity_added` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `user_name` varchar(255) NOT NULL,
  `update_date` datetime NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_log`
--

INSERT INTO `inventory_log` (`id`, `item_id`, `item_name`, `previous_quantity`, `quantity_added`, `new_quantity`, `user_id`, `user_name`, `update_date`, `batch_id`, `action`, `performed_by`) VALUES
(1, 6, 'Onions', 0, 100, 100, 10, 'Brix Hallazgo Lico', '2026-03-22 16:00:34', NULL, NULL, NULL),
(2, 6, 'Onions', 100, 100, 200, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:18', NULL, NULL, NULL),
(3, 6, 'Onions', 200, 100, 300, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:21', NULL, NULL, NULL),
(4, 6, 'Onions', 300, 100, 400, 10, 'Brix Hallazgo Lico', '2026-03-22 16:05:38', NULL, NULL, NULL),
(5, 17, 'Bacon', 48, 2, 50, 10, 'Brix Hallazgo Lico', '2026-03-22 16:13:31', NULL, NULL, NULL),
(6, 17, 'Bacon', 50, 2, 52, 10, 'Brix Hallazgo Lico', '2026-03-22 16:17:52', NULL, NULL, NULL),
(7, 17, 'Bacon', 52, 2, 54, 10, 'Brix Hallazgo Lico', '2026-03-22 16:19:46', NULL, NULL, NULL),
(8, 17, 'Bacon', 54, 2, 56, 10, 'Brix Hallazgo Lico', '2026-03-22 16:22:48', NULL, NULL, NULL),
(9, 17, 'Bacon', 56, 2, 58, 10, 'Brix Hallazgo Lico', '2026-03-22 16:22:50', NULL, NULL, NULL),
(10, 17, 'Bacon', 58, 123, 181, 10, 'Brix Hallazgo Lico', '2026-03-22 16:23:01', NULL, NULL, NULL),
(11, 17, 'Bacon', 181, 123, 304, 10, 'Brix Hallazgo Lico', '2026-03-22 16:27:27', NULL, NULL, NULL),
(12, 4, 'Lettuce', 2, 300, 302, 10, 'Brix Hallazgo Lico', '2026-03-22 22:40:26', NULL, NULL, NULL),
(13, 16, 'Beef Patties', 24, 200, 224, 10, 'Brix Hallazgo Lico', '2026-03-22 22:42:55', NULL, NULL, NULL),
(14, 53, 'Cheese Slices', 70, 2000, 2070, 10, 'Brix Hallazgo Lico', '2026-03-24 08:57:45', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory_movements`
--

CREATE TABLE `inventory_movements` (
  `id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `batch_id` int(11) DEFAULT NULL,
  `movement_type` enum('stock_in','stock_out','waste','adjustment','return') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_quantity` int(11) DEFAULT NULL,
  `new_quantity` int(11) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `reason` varchar(100) DEFAULT NULL,
  `performed_by` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_movements`
--

INSERT INTO `inventory_movements` (`id`, `inventory_id`, `batch_id`, `movement_type`, `quantity`, `previous_quantity`, `new_quantity`, `reference_type`, `reference_id`, `notes`, `reason`, `performed_by`, `branch_id`, `created_at`) VALUES
(2, 16, NULL, 'stock_in', 30, 56, 86, NULL, NULL, 'Beef patties delivery', NULL, NULL, 1, '2026-03-22 02:11:28'),
(3, 1, NULL, 'stock_out', 15, 115, 100, NULL, NULL, 'Used for morning shift', NULL, NULL, 1, '2026-03-23 02:11:28'),
(4, 3, NULL, 'stock_out', 12, 130, 118, NULL, NULL, 'Used for burger production', NULL, NULL, 1, '2026-03-23 02:11:28'),
(5, 25, NULL, 'waste', 5, 44, 39, NULL, NULL, 'Expired eggs disposed', NULL, NULL, 1, '2026-03-24 02:11:28'),
(6, 4, NULL, 'stock_in', 50, 166, 216, NULL, NULL, 'Fresh lettuce delivery', NULL, NULL, 1, '2026-03-21 02:11:28'),
(7, 6, NULL, 'stock_in', 100, 178, 278, NULL, NULL, 'Onion bulk order received', NULL, NULL, 1, '2026-03-20 02:11:28'),
(8, 35, NULL, 'stock_out', 20, 119, 99, NULL, NULL, 'Bottled water sold', NULL, NULL, 1, '2026-03-23 02:11:28'),
(9, 57, NULL, 'stock_in', 15, 18, 33, NULL, NULL, 'Bacon restock', NULL, NULL, 1, '2026-03-23 02:11:28'),
(10, 52, NULL, 'stock_in', 30, 30, 60, NULL, NULL, 'Buns delivery', NULL, NULL, 1, '2026-03-22 02:11:28'),
(11, 53, NULL, 'stock_out', 10, 80, 70, NULL, NULL, 'Cheese used for orders', NULL, NULL, 1, '2026-03-24 02:11:28'),
(12, 54, NULL, 'stock_in', 50, 79, 129, NULL, NULL, 'Lettuce from local supplier', NULL, NULL, 1, '2026-03-21 02:11:28'),
(13, 82, 1, 'stock_in', 1000, NULL, NULL, NULL, NULL, 'Opening stock', NULL, 2, 1, '2026-07-04 09:11:55'),
(14, 82, 2, 'stock_in', 1000, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-04 09:12:21'),
(15, 3, 3, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-08 22:39:50'),
(16, 1, 4, 'stock_in', 100, NULL, NULL, NULL, NULL, NULL, NULL, 2, 1, '2026-07-08 23:24:56'),
(17, 1, NULL, 'stock_out', 2, 138, 136, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(18, 16, NULL, 'stock_out', 2, 38, 36, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(19, 3, NULL, 'stock_out', 2, 156, 154, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(20, 4, NULL, 'stock_out', 2, 154, 152, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(21, 6, NULL, 'stock_out', 2, 216, 214, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(22, 21, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(23, 36, NULL, 'stock_out', 2, 25, 23, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(24, 37, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02'),
(25, 43, NULL, 'stock_out', 2, 78, 76, 'order', 75, 'Order #75 - sale', 'sale', 5, 1, '2026-07-16 20:42:02');

-- --------------------------------------------------------

--
-- Table structure for table `inventory_orders`
--

CREATE TABLE `inventory_orders` (
  `id` int(11) NOT NULL,
  `order_reference` varchar(100) NOT NULL,
  `order_type` enum('single','multiple') NOT NULL DEFAULT 'single',
  `supplier` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `total_items` int(11) NOT NULL DEFAULT 0,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expected_expiry_date` date DEFAULT NULL,
  `batch_number` varchar(100) DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory_order_items`
--

CREATE TABLE `inventory_order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment` decimal(10,2) NOT NULL,
  `change` decimal(10,2) NOT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `shift_id` int(11) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `date_time` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `order_number`, `total_amount`, `payment`, `change`, `cashier_id`, `shift_id`, `branch_id`, `date_time`) VALUES
(1, 'ORD-20251111-4765', 0.00, 40.00, 0.00, 2, NULL, NULL, '2025-11-12 02:43:17'),
(2, 'ORD-20251111-4195', 0.00, 40.00, 0.00, 2, NULL, NULL, '2025-11-12 02:44:42'),
(3, 'ORD-20260218-3595', 195.00, 400.00, 205.00, 2, NULL, NULL, '2026-02-18 21:38:13'),
(4, 'ORD-20260218-8423', 75.00, 100.00, 25.00, 2, NULL, NULL, '2026-02-18 21:39:12'),
(5, 'ORD-20260218-8681', 35.00, 100.00, 65.00, 2, NULL, NULL, '2026-02-18 21:40:35'),
(6, 'ORD-20260218-2072', 65.00, 1000.00, 935.00, 2, NULL, NULL, '2026-02-18 21:41:32'),
(7, 'ORD-20260307-9147', 191.00, 192.00, 1.00, 2, NULL, NULL, '2026-03-07 09:05:48'),
(8, 'ORD-20260314-1350', 308.00, 500.00, 192.00, 1, NULL, NULL, '2026-03-14 13:46:33'),
(9, 'ORD-20260316-9029', 92.00, 100.00, 8.00, 2, NULL, NULL, '2026-03-16 15:53:41'),
(10, 'ORD-20260317-0709', 189.00, 200.00, 11.00, 2, NULL, NULL, '2026-03-17 15:04:02'),
(11, 'ORD-20260317-9407', 189.00, 200.00, 11.00, 2, NULL, NULL, '2026-03-17 15:15:31'),
(12, 'ORD-20260318-9474', 184.00, 190.00, 6.00, 2, NULL, NULL, '2026-03-18 14:12:59'),
(13, 'ORD-20260318-0409', 651.00, 1000.00, 349.00, 1, NULL, NULL, '2026-03-18 14:47:01'),
(14, 'ORD-20260318-8738', 270.00, 1000.00, 730.00, 1, NULL, NULL, '2026-03-18 14:47:13'),
(15, 'ORD-20260319-9097', 97.00, 100.00, 3.00, 2, NULL, NULL, '2026-03-19 12:45:31'),
(16, 'ORD-20260319-3329', 184.00, 200.00, 16.00, 2, NULL, NULL, '2026-03-19 17:09:53'),
(17, 'ORD-20260319-4077', 176.00, 500.00, 324.00, 5, NULL, NULL, '2026-03-19 20:41:33'),
(18, 'ORD-20260319-6454', 174.00, 200.00, 26.00, 5, NULL, NULL, '2026-03-19 20:52:12'),
(19, 'ORD-20260319-4587', 366.00, 1000.00, 634.00, 5, NULL, NULL, '2026-03-19 21:16:20'),
(20, 'ORD-20260320-6913', 276.00, 1000.00, 724.00, 5, NULL, NULL, '2026-03-20 11:50:33'),
(21, 'ORD-20260320-0827', 87.00, 100.00, 13.00, 5, NULL, NULL, '2026-03-20 12:01:11'),
(22, 'ORD-20260320-1117', 179.00, 200.00, 21.00, 5, NULL, NULL, '2026-03-20 12:32:04'),
(23, 'ORD-20260320-1560', 97.00, 100.00, 3.00, 5, NULL, NULL, '2026-03-20 12:32:44'),
(24, 'ORD-20260320-5497', 291.00, 500.00, 209.00, 5, NULL, NULL, '2026-03-20 13:13:23'),
(25, 'ORD-20260320-3870', 174.00, 200.00, 26.00, 5, NULL, NULL, '2026-03-20 13:45:07'),
(26, 'ORD-20260320-6942', 485.00, 500.00, 15.00, 5, NULL, NULL, '2026-03-20 13:45:38'),
(27, 'ORD-20260320-6338', 194.00, 400.00, 206.00, 5, NULL, NULL, '2026-03-20 14:08:05'),
(28, 'ORD-20260320-0194', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:41:31'),
(29, 'ORD-20260320-5448', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:47:05'),
(30, 'ORD-20260320-3689', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-03-20 16:48:26'),
(31, 'ORD-20260320-8440', 97.00, 100.00, 3.00, 2, NULL, NULL, '2026-03-20 18:41:27'),
(32, 'ORD-20260320-5506', 184.00, 200.00, 16.00, 2, NULL, NULL, '2026-03-21 02:29:53'),
(33, 'ORD-20260320-2894', 87.00, 90.00, 3.00, 2, NULL, NULL, '2026-03-21 04:32:21'),
(34, 'ORD-20260321-1361', 317.00, 500.00, 183.00, 1, NULL, NULL, '2026-03-21 09:52:07'),
(35, 'ORD-20260321-8241', 187.00, 200.00, 13.00, NULL, NULL, NULL, '2026-03-21 15:45:14'),
(36, 'ORD-20260322-9175', 283.00, 300.00, 17.00, NULL, NULL, NULL, '2026-03-22 15:07:35'),
(37, 'ORD-20260322-2619', 283.00, 300.00, 17.00, NULL, NULL, NULL, '2026-03-22 16:09:41'),
(38, 'ORD-20260322-4079', 380.00, 400.00, 20.00, NULL, NULL, NULL, '2026-03-22 16:28:00'),
(39, 'ORD-20260322-0853', 451.00, 500.00, 49.00, NULL, NULL, NULL, '2026-03-22 17:38:39'),
(40, 'ORD-20260322-7751', 371.00, 400.00, 29.00, NULL, NULL, NULL, '2026-03-22 17:39:14'),
(41, 'ORD-20260322-3491', 279.00, 280.00, 1.00, NULL, NULL, NULL, '2026-03-22 18:22:21'),
(43, 'ORD-20260322-0310', 191.00, 200.00, 9.00, NULL, NULL, NULL, '2026-03-22 22:41:39'),
(44, 'ORD-20260322-9207', 182.00, 200.00, 18.00, NULL, NULL, NULL, '2026-03-22 23:31:31'),
(45, 'ORD-20260322-8871', 193.00, 1000.00, 807.00, NULL, NULL, NULL, '2026-03-23 00:05:47'),
(46, 'ORD-20260322-4848', 2254.00, 3000.00, 746.00, NULL, NULL, NULL, '2026-03-23 00:49:02'),
(47, 'ORD-20260322-3756', 1408.00, 2000.00, 592.00, NULL, NULL, NULL, '2026-03-23 00:49:36'),
(48, 'ORD-20260322-8532', 90.00, 1000000.00, 999910.00, NULL, NULL, NULL, '2026-03-23 01:00:42'),
(49, 'ORD-CGY-001', 284.00, 334.00, 50.00, 1, NULL, NULL, '2026-03-18 01:54:13'),
(50, 'ORD-CGY-002', 126.00, 146.00, 20.00, 1, NULL, NULL, '2026-03-19 01:54:13'),
(51, 'ORD-CGY-003', 333.00, 433.00, 100.00, 1, NULL, NULL, '2026-03-20 01:54:13'),
(52, 'ORD-CGY-004', 306.00, 336.00, 30.00, 1, NULL, NULL, '2026-03-21 01:54:13'),
(53, 'ORD-CGY-005', 330.00, 430.00, 100.00, 1, NULL, NULL, '2026-03-22 01:54:13'),
(54, 'ORD-CGY-006', 438.00, 638.00, 200.00, 1, NULL, NULL, '2026-03-23 01:54:13'),
(55, 'ORD-CGY-007', 258.00, 308.00, 50.00, 1, NULL, NULL, '2026-03-24 01:54:13'),
(56, 'ORD-CGY-008', 374.00, 474.00, 100.00, 1, NULL, NULL, '2026-03-18 04:54:13'),
(57, 'ORD-CGY-009', 303.00, 353.00, 50.00, 1, NULL, NULL, '2026-03-20 06:54:13'),
(58, 'ORD-CGY-010', 164.00, 194.00, 30.00, 1, NULL, NULL, '2026-03-22 03:54:13'),
(59, 'ORD-CGY-011', 295.00, 355.00, 60.00, 1, NULL, NULL, '2026-03-23 05:54:13'),
(60, 'ORD-CGY-012', 237.00, 277.00, 40.00, 1, NULL, NULL, '2026-03-23 23:54:13'),
(61, 'ORD-20260703-7316', 90.00, 100.00, 10.00, 2, NULL, NULL, '2026-07-03 09:13:44'),
(62, 'ORD-20260703-3927', 58.00, 100.00, 42.00, 1, 29, NULL, '2026-07-03 10:13:46'),
(63, 'ORD-20260704-6145', 90.00, 1000.00, 910.00, 2, NULL, NULL, '2026-07-04 08:54:07'),
(64, 'ORD-20260704-4156', 189.00, 1000.00, 811.00, 2, NULL, NULL, '2026-07-04 09:05:24'),
(65, 'ORD-20260704-1496', 1307.00, 1500.00, 193.00, 2, NULL, NULL, '2026-07-04 09:06:07'),
(66, 'ORD-20260704-5502', 270.00, 300.00, 30.00, 1, 31, NULL, '2026-07-04 09:07:42'),
(67, 'ORD-20260704-7173', 16.00, 100.00, 84.00, 2, NULL, NULL, '2026-07-04 09:14:38'),
(68, 'ORD-20260704-3941', 1339.00, 1500.00, 161.00, 2, NULL, NULL, '2026-07-04 09:19:47'),
(69, 'ORD-20260704-3893', 1339.00, 1500.00, 161.00, 2, NULL, NULL, '2026-07-04 09:20:26'),
(70, 'ORD-20260704-8006', 1801.00, 1801.00, 0.00, 2, NULL, NULL, '2026-07-04 09:21:54'),
(71, 'ORD-20260708-7265', 189.00, 200.00, 11.00, 1, 32, NULL, '2026-07-08 11:49:28'),
(72, 'ORD-20260708-1258', 283.00, 500.00, 217.00, 2, NULL, NULL, '2026-07-08 12:51:57'),
(73, 'ORD-20260715-4410', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-07-16 00:38:55'),
(74, 'ORD-20260716-3772', 90.00, 100.00, 10.00, 19, 39, NULL, '2026-07-16 00:43:56'),
(75, 'ORD-20260716-6982', 90.00, 100.00, 10.00, 5, NULL, NULL, '2026-07-16 20:42:02'),
(76, 'ORD-20260716-7464', 92.00, 100.00, 8.00, 16, 41, 2, '2026-07-16 21:50:29');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 3, NULL, 1, 65.00, 65.00),
(2, 3, NULL, 1, 75.00, 75.00),
(3, 3, NULL, 1, 55.00, 55.00),
(4, 4, NULL, 3, 25.00, 75.00),
(5, 5, NULL, 1, 35.00, 35.00),
(6, 6, NULL, 1, 65.00, 65.00),
(7, 7, 46, 1, 101.00, 101.00),
(8, 7, 45, 1, 90.00, 90.00),
(9, 8, 49, 1, 87.00, 87.00),
(10, 8, 44, 1, 97.00, 97.00),
(11, 8, 48, 1, 92.00, 92.00),
(12, 8, 37, 1, 16.00, 16.00),
(13, 8, 36, 1, 16.00, 16.00),
(14, 9, 48, 1, 92.00, 92.00),
(15, 10, 48, 1, 92.00, 92.00),
(16, 10, 44, 1, 97.00, 97.00),
(17, 11, 48, 1, 92.00, 92.00),
(18, 11, 44, 1, 97.00, 97.00),
(19, 12, 44, 1, 97.00, 97.00),
(20, 12, 49, 1, 87.00, 87.00),
(21, 13, 48, 5, 92.00, 460.00),
(22, 13, 45, 1, 90.00, 90.00),
(23, 13, 46, 1, 101.00, 101.00),
(24, 14, 45, 3, 90.00, 270.00),
(25, 15, 44, 1, 97.00, 97.00),
(26, 16, 44, 1, 97.00, 97.00),
(27, 16, 49, 1, 87.00, 87.00),
(28, 17, 47, 1, 95.00, 95.00),
(29, 17, 53, 1, 81.00, 81.00),
(30, 18, 49, 2, 87.00, 174.00),
(31, 19, 49, 1, 87.00, 87.00),
(32, 19, 44, 1, 97.00, 97.00),
(33, 19, 48, 1, 92.00, 92.00),
(34, 19, 45, 1, 90.00, 90.00),
(35, 20, 49, 1, 87.00, 87.00),
(36, 20, 44, 1, 97.00, 97.00),
(37, 20, 48, 1, 92.00, 92.00),
(38, 21, 49, 1, 87.00, 87.00),
(39, 22, 49, 1, 87.00, 87.00),
(40, 22, 48, 1, 92.00, 92.00),
(41, 23, 44, 1, 97.00, 97.00),
(42, 24, 44, 3, 97.00, 291.00),
(43, 25, 49, 2, 87.00, 174.00),
(44, 26, 44, 5, 97.00, 485.00),
(45, 27, 44, 2, 97.00, 194.00),
(46, 28, 45, 1, 90.00, 90.00),
(47, 29, 45, 1, 90.00, 90.00),
(48, 30, 45, 1, 90.00, 90.00),
(49, 31, 44, 1, 97.00, 97.00),
(50, 32, 44, 1, 97.00, 97.00),
(51, 32, 49, 1, 87.00, 87.00),
(52, 33, 49, 1, 87.00, 87.00),
(53, 34, 49, 1, 87.00, 87.00),
(54, 34, 44, 1, 97.00, 97.00),
(55, 34, 48, 1, 92.00, 92.00),
(56, 34, 33, 1, 16.00, 16.00),
(57, 34, 27, 1, 25.00, 25.00),
(58, 35, 44, 1, 97.00, 97.00),
(59, 35, 45, 1, 90.00, 90.00),
(60, 36, 45, 1, 90.00, 90.00),
(61, 36, 48, 1, 92.00, 92.00),
(62, 36, 46, 1, 101.00, 101.00),
(63, 37, 45, 1, 90.00, 90.00),
(64, 37, 48, 1, 92.00, 92.00),
(65, 37, 46, 1, 101.00, 101.00),
(66, 38, 45, 1, 90.00, 90.00),
(67, 38, 48, 1, 92.00, 92.00),
(68, 38, 44, 1, 97.00, 97.00),
(69, 38, 46, 1, 101.00, 101.00),
(70, 39, 44, 1, 97.00, 97.00),
(71, 39, 48, 1, 92.00, 92.00),
(72, 39, 45, 1, 90.00, 90.00),
(73, 39, 46, 1, 101.00, 101.00),
(74, 39, 54, 1, 71.00, 71.00),
(75, 40, 45, 3, 90.00, 270.00),
(76, 40, 46, 1, 101.00, 101.00),
(77, 41, 45, 1, 90.00, 90.00),
(78, 41, 48, 1, 92.00, 92.00),
(79, 41, 44, 1, 97.00, 97.00),
(82, 43, 46, 1, 101.00, 101.00),
(83, 43, 45, 1, 90.00, 90.00),
(84, 44, 45, 1, 90.00, 90.00),
(85, 44, 48, 1, 92.00, 92.00),
(86, 45, 48, 1, 92.00, 92.00),
(87, 45, 46, 1, 101.00, 101.00),
(88, 46, 49, 3, 87.00, 261.00),
(89, 46, 44, 3, 97.00, 291.00),
(90, 46, 48, 3, 92.00, 276.00),
(91, 46, 45, 3, 90.00, 270.00),
(92, 46, 46, 3, 101.00, 303.00),
(93, 46, 47, 3, 95.00, 285.00),
(94, 46, 50, 4, 142.00, 568.00),
(95, 47, 49, 2, 87.00, 174.00),
(96, 47, 44, 2, 97.00, 194.00),
(97, 47, 48, 2, 92.00, 184.00),
(98, 47, 45, 2, 90.00, 180.00),
(99, 47, 46, 2, 101.00, 202.00),
(100, 47, 47, 2, 95.00, 190.00),
(101, 47, 50, 2, 142.00, 284.00),
(102, 48, 45, 1, 90.00, 90.00),
(103, 49, 70, 1, 97.00, 291.00),
(104, 50, 71, 3, 90.00, 270.00),
(105, 51, 72, 2, 101.00, 202.00),
(106, 52, 77, 2, 42.00, 84.00),
(107, 53, 57, 2, 25.00, 25.00),
(108, 54, 70, 3, 97.00, 194.00),
(109, 55, 71, 2, 90.00, 90.00),
(110, 56, 72, 1, 101.00, 101.00),
(111, 57, 77, 3, 42.00, 126.00),
(112, 58, 57, 2, 25.00, 75.00),
(113, 59, 70, 3, 97.00, 291.00),
(114, 60, 71, 3, 90.00, 90.00),
(118, 49, 72, 1, 101.00, 202.00),
(119, 50, 77, 2, 42.00, 42.00),
(120, 51, 57, 2, 25.00, 50.00),
(121, 52, 70, 1, 97.00, 97.00),
(122, 53, 71, 1, 90.00, 180.00),
(123, 54, 72, 2, 101.00, 202.00),
(124, 55, 77, 1, 42.00, 42.00),
(125, 56, 57, 1, 25.00, 50.00),
(126, 57, 70, 1, 97.00, 194.00),
(127, 58, 71, 1, 90.00, 180.00),
(128, 59, 72, 1, 101.00, 101.00),
(129, 60, 77, 1, 42.00, 84.00),
(133, 61, 45, 1, 90.00, 90.00),
(134, 62, 51, 1, 42.00, 42.00),
(135, 62, 33, 1, 16.00, 16.00),
(136, 63, 45, 1, 90.00, 90.00),
(137, 64, 70, 1, 97.00, 97.00),
(138, 64, 48, 1, 92.00, 92.00),
(139, 65, 75, 1, 87.00, 87.00),
(140, 65, 70, 1, 97.00, 97.00),
(141, 65, 48, 1, 92.00, 92.00),
(142, 65, 45, 1, 90.00, 90.00),
(143, 65, 72, 1, 101.00, 101.00),
(144, 65, 73, 1, 95.00, 95.00),
(145, 65, 50, 1, 142.00, 142.00),
(146, 65, 79, 1, 81.00, 81.00),
(147, 65, 80, 1, 71.00, 71.00),
(148, 65, 78, 1, 65.00, 65.00),
(149, 65, 51, 1, 42.00, 42.00),
(150, 65, 64, 1, 97.00, 97.00),
(151, 65, 65, 1, 95.00, 95.00),
(152, 65, 57, 1, 25.00, 25.00),
(153, 65, 58, 1, 25.00, 25.00),
(154, 65, 32, 1, 20.00, 20.00),
(155, 65, 59, 1, 18.00, 18.00),
(156, 65, 61, 1, 23.00, 23.00),
(157, 65, 69, 1, 12.00, 12.00),
(158, 65, 68, 1, 13.00, 13.00),
(159, 65, 37, 1, 16.00, 16.00),
(160, 66, 45, 3, 90.00, 270.00),
(161, 67, 37, 1, 16.00, 16.00),
(162, 68, 75, 1, 87.00, 87.00),
(163, 68, 70, 1, 97.00, 97.00),
(164, 68, 48, 1, 92.00, 92.00),
(165, 68, 45, 1, 90.00, 90.00),
(166, 68, 72, 1, 101.00, 101.00),
(167, 68, 73, 1, 95.00, 95.00),
(168, 68, 50, 1, 142.00, 142.00),
(169, 68, 79, 1, 81.00, 81.00),
(170, 68, 80, 1, 71.00, 71.00),
(171, 68, 78, 1, 65.00, 65.00),
(172, 68, 51, 1, 42.00, 42.00),
(173, 68, 64, 1, 97.00, 97.00),
(174, 68, 65, 1, 95.00, 95.00),
(175, 68, 33, 1, 16.00, 16.00),
(176, 68, 57, 1, 25.00, 25.00),
(177, 68, 58, 1, 25.00, 25.00),
(178, 68, 32, 1, 20.00, 20.00),
(179, 68, 59, 1, 18.00, 18.00),
(180, 68, 61, 1, 23.00, 23.00),
(181, 68, 69, 1, 12.00, 12.00),
(182, 68, 68, 1, 13.00, 13.00),
(183, 68, 66, 1, 16.00, 16.00),
(184, 68, 37, 1, 16.00, 16.00),
(185, 69, 75, 1, 87.00, 87.00),
(186, 69, 70, 1, 97.00, 97.00),
(187, 69, 48, 1, 92.00, 92.00),
(188, 69, 45, 1, 90.00, 90.00),
(189, 69, 72, 1, 101.00, 101.00),
(190, 69, 73, 1, 95.00, 95.00),
(191, 69, 50, 1, 142.00, 142.00),
(192, 69, 79, 1, 81.00, 81.00),
(193, 69, 80, 1, 71.00, 71.00),
(194, 69, 78, 1, 65.00, 65.00),
(195, 69, 51, 1, 42.00, 42.00),
(196, 69, 64, 1, 97.00, 97.00),
(197, 69, 65, 1, 95.00, 95.00),
(198, 69, 33, 1, 16.00, 16.00),
(199, 69, 57, 1, 25.00, 25.00),
(200, 69, 58, 1, 25.00, 25.00),
(201, 69, 32, 1, 20.00, 20.00),
(202, 69, 59, 1, 18.00, 18.00),
(203, 69, 61, 1, 23.00, 23.00),
(204, 69, 69, 1, 12.00, 12.00),
(205, 69, 68, 1, 13.00, 13.00),
(206, 69, 66, 1, 16.00, 16.00),
(207, 69, 37, 1, 16.00, 16.00),
(208, 70, 64, 3, 97.00, 291.00),
(209, 70, 79, 3, 81.00, 243.00),
(210, 70, 50, 4, 142.00, 568.00),
(211, 70, 78, 4, 65.00, 260.00),
(212, 70, 51, 2, 42.00, 84.00),
(213, 70, 80, 5, 71.00, 355.00),
(214, 71, 48, 1, 92.00, 92.00),
(215, 71, 70, 1, 97.00, 97.00),
(216, 72, 45, 1, 90.00, 90.00),
(217, 72, 72, 1, 101.00, 101.00),
(218, 72, 48, 1, 92.00, 92.00),
(219, 73, 45, 1, 90.00, 90.00),
(220, 74, 45, 1, 90.00, 90.00),
(221, 75, 45, 1, 90.00, 90.00),
(222, 76, 74, 1, 92.00, 92.00);

-- --------------------------------------------------------

--
-- Table structure for table `permission_logs`
--

CREATE TABLE `permission_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `permission` varchar(100) NOT NULL,
  `granted` tinyint(1) DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `category` varchar(50) DEFAULT NULL,
  `product_type` enum('burger','patty','bun','hotdog','drink','addon') DEFAULT 'burger',
  `image` varchar(255) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `is_bogo` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `min_stock` int(11) NOT NULL DEFAULT 10,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `stock`, `category`, `product_type`, `image`, `status`, `is_bogo`, `created_at`, `deleted_at`, `min_stock`, `updated_at`) VALUES
(27, 'Calamantea', 25.00, 99, 'Drinks', 'drink', '69a6df159ee18.jpg', 'inactive', 0, '2026-03-03 13:16:05', '2026-03-24 01:00:07', 10, '2026-03-24 09:00:07'),
(28, 'Fruit Twist', 25.00, 100, 'Drinks', 'drink', '69a6df5c6f81c.jpg', 'inactive', 0, '2026-03-03 13:17:16', '2026-03-24 01:00:16', 10, '2026-03-24 09:00:16'),
(29, 'Hot Coffee', 18.00, 100, 'Drinks', 'drink', '69a6df77bc93c.jpg', 'inactive', 0, '2026-03-03 13:17:43', '2026-03-24 01:01:11', 10, '2026-03-24 09:01:11'),
(30, 'Iced Choco', 24.00, 100, 'Drinks', 'drink', '69a6df9539b42.jpg', 'inactive', 0, '2026-03-03 13:18:13', '2026-03-24 01:01:16', 10, '2026-03-24 09:01:16'),
(31, 'Iced Coffee', 23.00, 100, 'Drinks', 'drink', '69a6dfc2a1cb3.jpg', 'inactive', 0, '2026-03-03 13:18:58', '2026-03-24 01:01:25', 10, '2026-03-24 09:01:25'),
(32, 'Hot Choco', 20.00, 97, 'Drinks', 'drink', '69a6dfd9d9adf.jpg', 'active', 0, '2026-03-03 13:19:21', NULL, 10, '2026-07-04 09:20:26'),
(33, 'Bottled Water', 16.00, 96, 'Drinks', 'drink', '69a6dff279406.jpg', 'active', 0, '2026-03-03 13:19:46', NULL, 10, '2026-07-04 09:20:26'),
(34, 'Chili Con Cheese Franks', 97.00, 100, 'Hotdogs', 'hotdog', '69a6e0185a60d.jpg', 'inactive', 0, '2026-03-03 13:20:24', '2026-03-24 01:02:22', 10, '2026-03-24 09:02:22'),
(35, 'French Onion Franks', 95.00, 100, 'Hotdogs', 'hotdog', '69a6e03431734.jpg', 'inactive', 0, '2026-03-03 13:20:52', '2026-03-24 01:02:13', 10, '2026-03-24 09:02:13'),
(36, 'Eggs', 16.00, 99, 'Add-ons', 'addon', '69a6e06888287.jpg', 'inactive', 0, '2026-03-03 13:21:44', '2026-03-24 00:58:41', 10, '2026-03-24 08:58:41'),
(37, 'Supreme Cheese', 16.00, 95, 'Add-ons', 'addon', '69a6e086e061b.jpg', 'active', 0, '2026-03-03 13:22:14', NULL, 10, '2026-07-04 09:20:26'),
(38, 'Coleslaw', 13.00, 100, 'Add-ons', 'addon', '69a6e0b473463.jpg', 'inactive', 0, '2026-03-03 13:23:00', '2026-03-24 00:58:36', 10, '2026-03-24 08:58:36'),
(39, 'Clover Chips', 12.00, 50, 'Add-ons', 'addon', '69a6e0d2a3017.jpg', 'inactive', 0, '2026-03-03 13:23:30', '2026-03-24 00:58:31', 10, '2026-03-24 08:58:31'),
(44, 'Bacon Cheese Burger', 97.00, 72, 'BIG TIME Burgers', 'burger', '69a7dd83b8d37.jpg', 'inactive', 1, '2026-03-04 07:21:39', '2026-03-24 00:59:03', 10, '2026-07-15 23:26:52'),
(45, 'Black Pepper Burger', 90.00, 61, 'BIG TIME Burgers', 'burger', '69a7dda2dfb70.jpg', 'active', 1, '2026-03-04 07:22:10', NULL, 10, '2026-07-16 20:42:02'),
(46, 'Crispy Chicken Chimichurri Burger', 101.00, 86, 'BIG TIME Burgers', 'burger', '69a7ddd426464.jpg', 'inactive', 1, '2026-03-04 07:23:00', '2026-03-24 00:59:28', 10, '2026-03-24 08:59:28'),
(47, 'Crispy Chicken Roasted Sesame Burger', 95.00, 94, 'BIG TIME Burgers', 'burger', '69a7de16c1fff.jpg', 'inactive', 1, '2026-03-04 07:24:06', '2026-03-24 00:59:48', 10, '2026-03-24 08:59:48'),
(48, 'Beef Shawarma Burger', 92.00, 68, 'BIG TIME Burgers', 'burger', '69a7de2d65b50.jpg', 'inactive', 1, '2026-03-04 07:24:29', NULL, 10, '2026-07-15 23:26:45'),
(49, '50/50 Veggie Chicken Burger', 87.00, 78, 'BIG TIME Burgers', 'burger', '69a7de6476080.jpg', 'inactive', 1, '2026-03-04 07:25:24', '2026-07-03 01:08:10', 10, '2026-07-15 23:26:57'),
(50, 'Premium Steak Burger', 142.00, 37, 'BIG TIME Burgers', 'burger', '69a7de7f4ebe4.jpg', 'active', 1, '2026-03-04 07:25:51', NULL, 10, '2026-07-04 09:21:54'),
(51, 'Minute Burger', 42.00, 94, 'MinuteBurgers', 'burger', '69a7deb841772.jpg', 'active', 1, '2026-03-04 07:26:48', NULL, 10, '2026-07-04 09:21:54'),
(52, 'Double Minute Burger', 65.00, 100, 'MinuteBurgers', 'burger', '69a7dee13ca2b.jpg', 'inactive', 1, '2026-03-04 07:27:29', '2026-03-24 01:01:55', 10, '2026-03-24 09:01:55'),
(53, 'Double Cheesy Burger', 81.00, 99, 'MinuteBurgers', 'burger', '69bb9a246811e.jpg', 'inactive', 1, '2026-03-04 07:28:04', '2026-03-24 01:02:08', 10, '2026-03-24 09:02:08'),
(54, 'Double Chicken Burger', 71.00, 99, 'MinuteBurgers', 'burger', '69a7df21c688d.jpg', 'inactive', 1, '2026-03-04 07:28:33', '2026-03-24 01:02:02', 10, '2026-03-24 09:02:02'),
(57, 'Calamantea', 25.00, 66, 'Drinks', 'drink', '69a6df159ee18.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(58, 'Fruit Twist', 25.00, 67, 'Drinks', 'drink', '69a6df5c6f81c.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(59, 'Hot Coffee', 18.00, 67, 'Drinks', 'drink', '69a6df77bc93c.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(60, 'Iced Choco', 24.00, 70, 'Drinks', 'drink', '69a6df9539b42.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:01:29', 10, '2026-07-09 13:50:58'),
(61, 'Iced Coffee', 23.00, 67, 'Drinks', 'drink', '69a6dfc2a1cb3.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(62, 'Hot Choco', 20.00, 70, 'Drinks', 'drink', '69a6dfd9d9adf.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:00:32', 10, '2026-07-09 13:50:58'),
(63, 'Bottled Water', 16.00, 69, 'Drinks', 'drink', '69a6dff279406.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 01:00:01', 10, '2026-07-09 13:50:58'),
(64, 'Chili Con Cheese Franks', 97.00, 64, 'Hotdogs', 'hotdog', '69a6e0185a60d.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(65, 'French Onion Franks', 95.00, 67, 'Hotdogs', 'hotdog', '69a6e03431734.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(66, 'Eggs', 16.00, 67, 'Add-ons', 'addon', '69a6e06888287.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(67, 'Supreme Cheese', 16.00, 69, 'Add-ons', 'addon', '69a6e086e061b.jpg', 'inactive', 0, '2026-03-23 17:53:39', '2026-03-24 00:58:49', 10, '2026-07-09 13:50:58'),
(68, 'Coleslaw', 13.00, 67, 'Add-ons', 'addon', '69a6e0b473463.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(69, 'Clover Chips', 12.00, 32, 'Add-ons', 'addon', '69a6e0d2a3017.jpg', 'active', 0, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(70, 'Bacon Cheese Burger', 97.00, 45, 'BIG TIME Burgers', 'burger', '69a7dd83b8d37.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(71, 'Black Pepper Burger', 90.00, 51, 'BIG TIME Burgers', 'burger', '69a7dda2dfb70.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:19', 10, '2026-07-09 13:50:58'),
(72, 'Crispy Chicken Chimichurri Burger', 101.00, 56, 'BIG TIME Burgers', 'burger', '69a7ddd426464.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(73, 'Crispy Chicken Roasted Sesame Burger', 95.00, 62, 'BIG TIME Burgers', 'burger', '69a7de16c1fff.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(74, 'Beef Shawarma Burger', 92.00, 51, 'BIG TIME Burgers', 'burger', '69a7de2d65b50.jpg', 'active', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:12', 10, '2026-07-15 23:26:37'),
(75, '50/50 Veggie Chicken Burger', 87.00, 51, 'BIG TIME Burgers', 'burger', '69a7de6476080.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(76, 'Premium Steak Burger', 142.00, 30, 'BIG TIME Burgers', 'burger', '69a7de7f4ebe4.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 00:59:41', 10, '2026-07-09 13:50:58'),
(77, 'Minute Burger', 42.00, 70, 'MinuteBurgers', 'burger', '69a7deb841772.jpg', 'inactive', 1, '2026-03-23 17:53:39', '2026-03-24 01:01:39', 10, '2026-07-09 13:50:58'),
(78, 'Double Minute Burger', 65.00, 63, 'MinuteBurgers', 'burger', '69a7dee13ca2b.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(79, 'Double Cheesy Burger', 81.00, 63, 'MinuteBurgers', 'burger', '69bb9a246811e.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58'),
(80, 'Double Chicken Burger', 71.00, 61, 'MinuteBurgers', 'burger', '69a7df21c688d.jpg', 'active', 1, '2026-03-23 17:53:39', NULL, 10, '2026-07-09 13:50:58');

-- --------------------------------------------------------

--
-- Table structure for table `product_ingredients`
--

CREATE TABLE `product_ingredients` (
  `id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `qty_required` decimal(10,2) NOT NULL DEFAULT 1.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_ingredients`
--

INSERT INTO `product_ingredients` (`id`, `template_id`, `product_id`, `inventory_id`, `qty_required`) VALUES
(1, 5, 1, 1, 1.00),
(2, 6, 1, 2, 1.00),
(3, 8, 1, 3, 1.00),
(28, 5, 51, 1, 1.00),
(29, 2, 51, 16, 1.00),
(30, 8, 51, 3, 1.00),
(31, 19, 51, 4, 1.00),
(32, 21, 51, 6, 1.00),
(33, 5, 52, 1, 1.00),
(34, 2, 52, 16, 2.00),
(35, 8, 52, 3, 2.00),
(36, 19, 52, 4, 1.00),
(37, 21, 52, 6, 1.00),
(57, 5, 49, 1, 1.00),
(58, 26, 49, 19, 1.00),
(59, 9, 49, 18, 1.00),
(60, 8, 49, 3, 1.00),
(61, 19, 49, 4, 1.00),
(62, 21, 49, 6, 1.00),
(64, 5, 50, 1, 1.00),
(65, 24, 50, 20, 1.00),
(66, 8, 50, 3, 1.00),
(67, 19, 50, 4, 1.00),
(68, 21, 50, 6, 1.00),
(78, 15, 36, 25, 1.00),
(79, 8, 37, 3, 1.00),
(80, 7, 27, 32, 1.00),
(81, 27, 27, 28, 1.00),
(82, 14, 27, 34, 1.00),
(83, 16, 28, 33, 1.00),
(84, 27, 28, 28, 1.00),
(85, 14, 28, 34, 1.00),
(86, 16, 28, 33, 1.00),
(87, 27, 28, 28, 1.00),
(88, 14, 28, 34, 1.00),
(89, 12, 29, 30, 1.00),
(90, 27, 29, 28, 1.00),
(91, 14, 29, 34, 1.00),
(92, 12, 29, 30, 1.00),
(93, 27, 29, 28, 1.00),
(94, 14, 29, 34, 1.00),
(95, 11, 30, 31, 1.00),
(97, 14, 30, 34, 1.00),
(98, 11, 32, 31, 1.00),
(99, 27, 32, 28, 1.00),
(100, 14, 32, 34, 1.00),
(101, 4, 33, 35, 1.00),
(109, 5, 45, 1, 1.00),
(110, 2, 45, 16, 1.00),
(111, 8, 45, 3, 1.00),
(112, 19, 45, 4, 1.00),
(113, 21, 45, 6, 1.00),
(114, 3, 45, 21, 1.00),
(115, 3, 45, 36, 1.00),
(116, 3, 45, 37, 1.00),
(117, 3, 45, 43, 1.00),
(124, 5, 48, 1, 1.00),
(125, 2, 48, 16, 1.00),
(126, 8, 48, 3, 1.00),
(127, 19, 48, 4, 1.00),
(128, 21, 48, 6, 1.00),
(129, 23, 48, 24, 1.00),
(130, 23, 48, 40, 1.00),
(131, 23, 48, 46, 1.00),
(139, 5, 46, 1, 1.00),
(140, 9, 46, 18, 1.00),
(141, 9, 46, 49, 1.00),
(142, 8, 46, 3, 1.00),
(143, 19, 46, 4, 1.00),
(144, 21, 46, 6, 1.00),
(145, 10, 46, 22, 1.00),
(146, 10, 46, 38, 1.00),
(147, 10, 46, 44, 1.00),
(154, 5, 47, 1, 1.00),
(155, 9, 47, 18, 1.00),
(156, 9, 47, 49, 1.00),
(157, 8, 47, 3, 1.00),
(158, 19, 47, 4, 1.00),
(159, 21, 47, 6, 1.00),
(160, 22, 47, 23, 1.00),
(161, 22, 47, 39, 1.00),
(162, 22, 47, 45, 1.00),
(169, 5, 44, 1, 1.00),
(170, 2, 44, 16, 1.00),
(171, 8, 44, 3, 1.00),
(174, 19, 44, 4, 1.00),
(175, 21, 44, 6, 1.00),
(176, 20, 44, 41, 1.00),
(177, 20, 44, 47, 1.00),
(184, 5, 53, 1, 1.00),
(185, 2, 53, 16, 2.00),
(186, 8, 53, 3, 2.00),
(187, 18, 53, 42, 1.00),
(188, 18, 53, 48, 1.00),
(198, 5, 54, 1, 1.00),
(199, 9, 54, 18, 2.00),
(200, 9, 54, 49, 2.00),
(201, 19, 54, 4, 1.00),
(202, 20, 54, 41, 1.00),
(203, 20, 54, 47, 1.00),
(205, 5, 49, 1, 1.00),
(206, 26, 49, 19, 1.00),
(207, 9, 49, 18, 1.00),
(208, 8, 49, 3, 1.00),
(209, 19, 49, 4, 1.00),
(210, 21, 49, 6, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `product_inventory_usage`
--

CREATE TABLE `product_inventory_usage` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL COMMENT 'Amount of inventory used per product',
  `unit` varchar(20) DEFAULT 'piece',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `batch_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `product_inventory_usage`
--

INSERT INTO `product_inventory_usage` (`id`, `product_id`, `inventory_id`, `quantity_used`, `unit`, `created_at`, `updated_at`, `batch_id`) VALUES
(12, 49, 1, 2.00, 'piece', '2026-03-19 04:46:15', '2026-03-19 04:46:15', NULL),
(13, 44, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(14, 45, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(15, 46, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(16, 47, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(17, 48, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(18, 50, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(19, 51, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(20, 52, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(21, 53, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL),
(22, 54, 1, 2.00, 'piece', '2026-03-19 04:57:45', '2026-03-19 04:57:45', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `restock_requests`
--

CREATE TABLE `restock_requests` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `item_type` enum('product','inventory') NOT NULL DEFAULT 'inventory',
  `item_name` varchar(255) NOT NULL,
  `current_quantity` int(11) NOT NULL,
  `requested_quantity` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `requested_by` int(11) NOT NULL,
  `requested_by_name` varchar(255) NOT NULL,
  `request_date` datetime NOT NULL,
  `status` varchar(50) DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `restock_requests`
--

INSERT INTO `restock_requests` (`id`, `item_id`, `item_type`, `item_name`, `current_quantity`, `requested_quantity`, `notes`, `requested_by`, `requested_by_name`, `request_date`, `status`) VALUES
(1, 4, 'inventory', 'Lettuce', 12, 40, 'add more lettuce\r\n', 1, 'Brix Cyrel H. Lico', '2026-03-20 19:44:01', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_system` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `description`, `is_system`, `created_at`) VALUES
(1, 'admin', 'System Administrator - Full access', 1, '2026-07-09 14:12:34'),
(2, 'manager', 'Store Manager - Limited administrative access', 1, '2026-07-09 14:12:34'),
(3, 'cashier', 'Cashier - Basic POS access', 1, '2026-07-09 14:12:34'),
(4, 'branch_owner', 'Branch Owner - Branch level full access', 1, '2026-07-09 14:12:34');

-- --------------------------------------------------------

--
-- Table structure for table `sales_history`
--

CREATE TABLE `sales_history` (
  `id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `quantity_sold` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `is_bogo` tinyint(1) DEFAULT 0,
  `sale_date` date NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `cashier_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales_history`
--

INSERT INTO `sales_history` (`id`, `product_id`, `product_name`, `quantity_sold`, `unit_price`, `total_amount`, `is_bogo`, `sale_date`, `order_id`, `cashier_id`, `created_at`) VALUES
(1, 70, 'Bacon Cheese Burger', 1, 97.00, 291.00, 0, '2026-03-18', 49, 1, '2026-03-23 17:54:59'),
(2, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 202.00, 0, '2026-03-18', 49, 1, '2026-03-23 17:54:59'),
(3, 71, 'Black Pepper Burger', 3, 90.00, 270.00, 0, '2026-03-19', 50, 1, '2026-03-23 17:54:59'),
(4, 77, 'Minute Burger', 2, 42.00, 42.00, 0, '2026-03-19', 50, 1, '2026-03-23 17:54:59'),
(5, 72, 'Crispy Chicken Chimichurri Burger', 2, 101.00, 202.00, 0, '2026-03-20', 51, 1, '2026-03-23 17:54:59'),
(6, 57, 'Calamantea', 2, 25.00, 50.00, 0, '2026-03-20', 51, 1, '2026-03-23 17:54:59'),
(7, 77, 'Minute Burger', 2, 42.00, 84.00, 0, '2026-03-21', 52, 1, '2026-03-23 17:54:59'),
(8, 70, 'Bacon Cheese Burger', 1, 97.00, 97.00, 0, '2026-03-21', 52, 1, '2026-03-23 17:54:59'),
(9, 57, 'Calamantea', 2, 25.00, 25.00, 0, '2026-03-22', 53, 1, '2026-03-23 17:54:59'),
(10, 71, 'Black Pepper Burger', 1, 90.00, 180.00, 0, '2026-03-22', 53, 1, '2026-03-23 17:54:59'),
(11, 70, 'Bacon Cheese Burger', 3, 97.00, 194.00, 0, '2026-03-23', 54, 1, '2026-03-23 17:54:59'),
(12, 72, 'Crispy Chicken Chimichurri Burger', 2, 101.00, 202.00, 0, '2026-03-23', 54, 1, '2026-03-23 17:54:59'),
(13, 71, 'Black Pepper Burger', 2, 90.00, 90.00, 0, '2026-03-24', 55, 1, '2026-03-23 17:54:59'),
(14, 77, 'Minute Burger', 1, 42.00, 42.00, 0, '2026-03-24', 55, 1, '2026-03-23 17:54:59'),
(15, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 101.00, 0, '2026-03-18', 56, 1, '2026-03-23 17:54:59'),
(16, 57, 'Calamantea', 1, 25.00, 50.00, 0, '2026-03-18', 56, 1, '2026-03-23 17:54:59'),
(17, 77, 'Minute Burger', 3, 42.00, 126.00, 0, '2026-03-20', 57, 1, '2026-03-23 17:54:59'),
(18, 70, 'Bacon Cheese Burger', 1, 97.00, 194.00, 0, '2026-03-20', 57, 1, '2026-03-23 17:54:59'),
(19, 57, 'Calamantea', 2, 25.00, 75.00, 0, '2026-03-22', 58, 1, '2026-03-23 17:54:59'),
(20, 71, 'Black Pepper Burger', 1, 90.00, 180.00, 0, '2026-03-22', 58, 1, '2026-03-23 17:54:59'),
(21, 70, 'Bacon Cheese Burger', 3, 97.00, 291.00, 0, '2026-03-23', 59, 1, '2026-03-23 17:54:59'),
(22, 72, 'Crispy Chicken Chimichurri Burger', 1, 101.00, 101.00, 0, '2026-03-23', 59, 1, '2026-03-23 17:54:59'),
(23, 71, 'Black Pepper Burger', 3, 90.00, 90.00, 0, '2026-03-23', 60, 1, '2026-03-23 17:54:59'),
(24, 77, 'Minute Burger', 1, 42.00, 84.00, 0, '2026-03-23', 60, 1, '2026-03-23 17:54:59');

-- --------------------------------------------------------

--
-- Table structure for table `stock_receiving`
--

CREATE TABLE `stock_receiving` (
  `id` int(11) NOT NULL,
  `reference_number` varchar(50) NOT NULL,
  `supplier_id` int(11) DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `received_by` int(11) NOT NULL,
  `received_date` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_receiving_items`
--

CREATE TABLE `stock_receiving_items` (
  `id` int(11) NOT NULL,
  `receiving_id` int(11) NOT NULL,
  `inventory_id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL DEFAULT 0.00,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `batch_number` varchar(100) DEFAULT NULL,
  `expiry_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `supplier_name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `payment_terms` varchar(100) DEFAULT NULL,
  `lead_time_days` int(11) DEFAULT 7,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `supplier_name`, `contact_person`, `phone`, `email`, `address`, `payment_terms`, `lead_time_days`, `is_active`, `created_at`) VALUES
(1, 'Minute Burger Commissary', 'Maria Santos', '09171234567', 'commissary@minuteburger.com', 'Cagayan de Oro City', 'Net 30', 3, 1, '2026-03-24 02:11:28'),
(2, 'Local Fresh Produce', 'Juan dela Cruz', '09189876543', 'fresh@localproduce.ph', 'Jasaan, Misamis Oriental', 'COD', 1, 1, '2026-03-24 02:11:28'),
(3, 'CDO Meat Supply', 'Pedro Reyes', '09201112233', 'orders@cdomeat.ph', 'Cagayan de Oro City', 'Net 15', 2, 1, '2026-03-24 02:11:28'),
(4, 'Oriental Bakery', 'Ana Garcia', '09223344556', 'orientalbakery@gmail.com', 'Jasaan, Misamis Oriental', 'COD', 1, 1, '2026-03-24 02:11:28'),
(5, 'Golden Beverage Corp', 'Robert Tan', '09334455667', 'sales@goldenbev.ph', 'Iligan City', 'Net 30', 5, 1, '2026-03-24 02:11:28'),
(6, 'Manila Ice Supply', 'Jose Mendoza', '09456789012', 'jose@manilice.com', 'Quezon City, Metro Manila', 'COD', 2, 1, '2026-03-24 03:03:35'),
(7, 'Pampanga Meat Corp', 'Lisa Bautista', '09567890123', 'lisa@pampmeat.com', 'San Fernando, Pampanga', 'Net 15', 3, 1, '2026-03-24 03:03:35'),
(8, 'Laguna Dairy Farm', 'Mark Villanueva', '09678901234', 'mark@lagunadairy.com', 'Santa Rosa, Laguna', 'Net 7', 2, 1, '2026-03-24 03:03:35'),
(9, 'Batangas Coffee Co.', 'Rosa Dimaculangan', '09789012345', 'rosa@batcoffee.com', 'Lipa City, Batangas', 'Net 30', 7, 1, '2026-03-24 03:03:35'),
(10, 'Cebu Spice Traders', 'Carlo Montero', '09890123456', 'carlo@cebuspice.com', 'Cebu City, Cebu', 'Net 15', 5, 1, '2026-03-24 03:03:35');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','cashier','manager') NOT NULL DEFAULT 'cashier',
  `branch_id` int(11) DEFAULT NULL,
  `permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissions`)),
  `full_name` varchar(100) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `middle_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `suffix` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `last_activity` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `fname` varchar(100) DEFAULT NULL,
  `mname` varchar(100) DEFAULT NULL,
  `lname` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `user_id`, `password`, `role`, `branch_id`, `permissions`, `full_name`, `first_name`, `middle_name`, `last_name`, `suffix`, `email`, `status`, `last_activity`, `created_at`, `fname`, `mname`, `lname`) VALUES
(1, 'Brix', '$2y$10$NevSOp8D9tBbIUcb01oA7OW6XWqVAuY5F2PEN6Q/6Ni71UtLEFjnq', 'cashier', NULL, '{\"dashboard_view\":false,\"products_view\":false,\"products_manage\":false,\"inventory_view\":true,\"inventory_manage\":false,\"pos_access\":true,\"transactions_view\":true,\"reports_view\":false,\"users_manage\":false,\"archive_view\":false}', 'Brix Cyrel H. Lico', 'Brix', 'Hallazgo', 'Lico', '', 'brixlico@gmail.com', 'active', '2026-07-15 23:16:28', '2025-11-11 18:24:38', NULL, NULL, NULL),
(2, 'Allan', '$2y$10$DT/wXItxF9QhU6jL9YfsgOTSZPVWtlMysxz7m1lFmHg4p4XUeufpG', 'admin', NULL, '{\"pos_access\": true, \"transactions_view\": true, \"inventory_manage\": true, \"reports_view\": true, \"users_manage\": true}', 'Allan Christian S. Uayan', NULL, NULL, NULL, NULL, 'allanuayan@gmailcom', 'active', '2026-07-15 23:16:28', '2025-11-11 18:24:38', NULL, NULL, NULL),
(3, 'wenggams', '$2y$10$tnLdYnjrtf7/wmdhjMxRQ.a56Gv6/jfs0GMXMR3nWpCdXGDiWBrNa', 'cashier', NULL, '{\"pos_access\":true,\"transactions_view\":true}', 'Joshua B. Gamalo', NULL, NULL, NULL, NULL, 'gamalo.joshua123@gmail.com', 'active', '2026-07-15 23:50:58', '2026-02-18 14:17:54', NULL, NULL, NULL),
(5, 'Joshua', '$2y$10$AhrY4FQy3hxbxYfgW36LpeiWr7MXBPEP0r.NCqNYFy6F8GwtCe4h6', 'admin', NULL, '{\"pos_access\":true,\"transactions_view\":true,\"inventory_manage\":true,\"reports_view\":true,\"users_manage\":true}', 'Joshua B. Gamalo', NULL, NULL, NULL, NULL, 'wenggams04@gmail.com', 'active', '2026-07-16 20:45:45', '2026-02-18 14:18:32', NULL, NULL, NULL),
(13, '0001', '$2y$10$EUnKvpXKYAWRcsC4Da7I3e72xbp5TJAiiA9qq9jc2oN6ghhNV92G.', 'manager', 1, '{\"dashboard_view\":true,\"products_view\":true,\"products_manage\":true,\"inventory_view\":true,\"inventory_manage\":true,\"pos_access\":true,\"transactions_view\":true,\"reports_view\":true,\"users_manage\":false,\"archive_view\":true,\"inventory_stock_in\":true,\"inventory_stock_out\":true,\"reports_export\":true,\"users_view\":true,\"archive_restore\":true,\"archive_delete\":true,\"branch_view\":true,\"branch_manage\":true,\"staff_view\":true,\"staff_manage\":true}', 'Merfern L. Igot', 'Merfern', 'L.', 'Igot', '', 'merfern@gmail.com', 'active', '2026-07-15 23:16:28', '2026-07-15 05:41:02', NULL, NULL, NULL),
(14, 'owner', '$2y$10$L1h.eID89F1D0.WWVbEqZOc.py/38tiBsEAVrE1/TsBnVkj7X//3y', 'admin', NULL, NULL, 'System Owner', NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-16 23:06:13', '2026-07-15 12:18:34', NULL, NULL, NULL),
(15, 'salay_manager', '$2y$10$L1h.eID89F1D0.WWVbEqZOc.py/38tiBsEAVrE1/TsBnVkj7X//3y', 'manager', 1, NULL, 'Salay Manager', NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-15 23:52:12', '2026-07-15 12:18:34', NULL, NULL, NULL),
(16, 'cdo_manager', '$2y$10$L1h.eID89F1D0.WWVbEqZOc.py/38tiBsEAVrE1/TsBnVkj7X//3y', 'manager', 2, NULL, 'CDO Manager', NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-16 22:02:33', '2026-07-15 12:18:34', NULL, NULL, NULL),
(17, 'jasaan_manager', '$2y$10$L1h.eID89F1D0.WWVbEqZOc.py/38tiBsEAVrE1/TsBnVkj7X//3y', 'manager', 3, NULL, 'Jasaan Manager', NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-16 22:02:47', '2026-07-15 12:18:34', NULL, NULL, NULL),
(18, 'salay_cashier', '$2y$10$L1h.eID89F1D0.WWVbEqZOc.py/38tiBsEAVrE1/TsBnVkj7X//3y', 'cashier', 1, NULL, 'Salay Cashier', NULL, NULL, NULL, NULL, NULL, 'active', '2026-07-15 23:16:28', '2026-07-15 12:18:34', NULL, NULL, NULL),
(19, '0002', '$2y$10$HYDozIig5Abj3VWS9v1yMuVPYRRRNPOLcaLfPZwH.Ff0s7p2HNv1O', 'cashier', 1, '{\"dashboard_view\":false,\"products_view\":false,\"products_manage\":false,\"inventory_view\":false,\"inventory_manage\":false,\"pos_access\":true,\"transactions_view\":true,\"reports_view\":false,\"users_manage\":false,\"archive_view\":false}', 'rickajoy anilao arquita', 'rickajoy', 'anilao', 'arquita', '', 'rickajoyarquita@gmail.com', 'active', '2026-07-16 22:03:48', '2026-07-15 15:52:06', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `x_reading_log`
--

CREATE TABLE `x_reading_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `reading_time` time NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_transactions` int(11) NOT NULL,
  `average_transaction` decimal(10,2) NOT NULL,
  `highest_transaction` decimal(10,2) NOT NULL,
  `lowest_transaction` decimal(10,2) NOT NULL,
  `total_items_sold` int(11) DEFAULT 0,
  `cash_sales` decimal(10,2) DEFAULT 0.00,
  `generated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `x_reading_log`
--

INSERT INTO `x_reading_log` (`id`, `shift_id`, `reading_time`, `total_sales`, `total_transactions`, `average_transaction`, `highest_transaction`, `lowest_transaction`, `total_items_sold`, `cash_sales`, `generated_by`, `created_at`) VALUES
(6, 22, '07:21:12', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 3, '2026-05-23 23:21:12'),
(7, 25, '07:56:15', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 3, '2026-06-24 23:56:15'),
(8, 38, '00:01:40', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 19, '2026-07-15 16:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `z_reading_log`
--

CREATE TABLE `z_reading_log` (
  `id` int(11) NOT NULL,
  `shift_id` int(11) NOT NULL,
  `closing_time` datetime NOT NULL,
  `start_time` datetime NOT NULL,
  `end_time` datetime NOT NULL,
  `total_sales` decimal(10,2) NOT NULL,
  `total_transactions` int(11) NOT NULL,
  `average_transaction` decimal(10,2) NOT NULL,
  `highest_transaction` decimal(10,2) NOT NULL,
  `lowest_transaction` decimal(10,2) NOT NULL,
  `total_items_sold` int(11) DEFAULT 0,
  `expected_cash` decimal(10,2) DEFAULT 0.00,
  `actual_cash` decimal(10,2) DEFAULT 0.00,
  `cash_difference` decimal(10,2) DEFAULT 0.00,
  `cash_drop_total` decimal(10,2) DEFAULT 0.00,
  `opening_cash` decimal(10,2) DEFAULT 0.00,
  `closing_cash` decimal(10,2) DEFAULT 0.00,
  `shift_duration_hours` decimal(5,2) DEFAULT 0.00,
  `generated_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `z_reading_log`
--

INSERT INTO `z_reading_log` (`id`, `shift_id`, `closing_time`, `start_time`, `end_time`, `total_sales`, `total_transactions`, `average_transaction`, `highest_transaction`, `lowest_transaction`, `total_items_sold`, `expected_cash`, `actual_cash`, `cash_difference`, `cash_drop_total`, `opening_cash`, `closing_cash`, `shift_duration_hours`, `generated_by`, `created_at`) VALUES
(24, 22, '2026-05-24 07:21:19', '2026-03-23 12:38:35', '2026-05-24 07:21:19', 0.00, 0, 0.00, 0.00, 0.00, 0, 894.00, 894.00, 0.00, 0.00, 894.00, 894.00, 999.99, 3, '2026-05-23 23:21:19'),
(25, 26, '2026-06-30 23:36:41', '2026-06-30 23:35:12', '2026-06-30 23:36:41', 0.00, 0, 0.00, 0.00, 0.00, 0, 21211.00, 21211.00, 0.00, 0.00, 21211.00, 21211.00, 0.02, 1, '2026-06-30 15:36:41'),
(26, 27, '2026-07-01 13:11:40', '2026-07-01 12:46:19', '2026-07-01 13:11:40', 0.00, 0, 0.00, 0.00, 0.00, 0, 6666.00, 6666.00, 0.00, 0.00, 6666.00, 6666.00, 0.42, 1, '2026-07-01 05:11:40'),
(27, 28, '2026-07-03 09:35:04', '2026-07-03 09:34:43', '2026-07-03 09:35:04', 0.00, 0, 0.00, 0.00, 0.00, 0, 1406.00, 1406.00, 0.00, 0.00, 1406.00, 1406.00, 0.01, 1, '2026-07-03 01:35:04'),
(28, 29, '2026-07-03 10:14:33', '2026-07-03 10:10:50', '2026-07-03 10:14:33', 58.00, 1, 58.00, 58.00, 58.00, 2, 1364.00, 1364.00, 0.00, 0.00, 1306.00, 1364.00, 0.06, 1, '2026-07-03 02:14:33'),
(29, 30, '2026-07-04 09:06:46', '2026-07-03 10:22:00', '2026-07-04 09:06:46', 0.00, 0, 0.00, 0.00, 0.00, 0, 1275.00, 1275.00, 0.00, 0.00, 1275.00, 1275.00, 22.75, 1, '2026-07-04 01:06:46'),
(30, 31, '2026-07-04 09:07:58', '2026-07-04 09:07:34', '2026-07-04 09:07:58', 270.00, 1, 270.00, 270.00, 270.00, 3, 1751.00, 1751.00, 0.00, 0.00, 1481.00, 1751.00, 0.01, 1, '2026-07-04 01:07:58'),
(31, 33, '2026-07-15 14:17:31', '2026-07-15 14:17:21', '2026-07-15 14:17:31', 0.00, 0, 0.00, 0.00, 0.00, 0, 619.00, 619.00, 0.00, 0.00, 619.00, 619.00, 0.00, 13, '2026-07-15 06:17:31'),
(32, 35, '2026-07-15 21:02:47', '2026-07-15 21:02:17', '2026-07-15 21:02:47', 0.00, 0, 0.00, 0.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.01, 16, '2026-07-15 13:02:47'),
(33, 37, '2026-07-15 23:59:12', '2026-07-15 23:52:28', '2026-07-15 23:59:12', 0.00, 0, 0.00, 0.00, 0.00, 0, 1533.00, 1533.00, 0.00, 0.00, 1533.00, 1533.00, 0.11, 19, '2026-07-15 15:59:12'),
(34, 38, '2026-07-16 00:01:48', '2026-07-15 23:59:33', '2026-07-16 00:01:48', 0.00, 0, 0.00, 0.00, 0.00, 0, 1504.00, 1504.00, 0.00, 0.00, 1504.00, 1504.00, 0.04, 19, '2026-07-15 16:01:48'),
(35, 39, '2026-07-16 22:03:32', '2026-07-16 00:02:03', '2026-07-16 22:03:32', 90.00, 1, 90.00, 90.00, 90.00, 1, 3090.00, 3090.00, 0.00, 0.00, 3000.00, 3090.00, 22.02, 19, '2026-07-16 14:03:32'),
(36, 42, '2026-07-16 22:03:48', '2026-07-16 22:03:44', '2026-07-16 22:03:48', 0.00, 0, 0.00, 0.00, 0.00, 0, 1988.00, 1988.00, 0.00, 0.00, 1988.00, 1988.00, 0.00, 19, '2026-07-16 14:03:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cashier_inventory_counts`
--
ALTER TABLE `cashier_inventory_counts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `counted_by` (`counted_by`),
  ADD KEY `counted_at` (`counted_at`);

--
-- Indexes for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cashier_id` (`cashier_id`,`shift_date`),
  ADD KEY `status` (`status`),
  ADD KEY `started_by` (`started_by`),
  ADD KEY `closed_by` (`closed_by`);

--
-- Indexes for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `cashier_id` (`cashier_id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_discount_type` (`discount_type`);

--
-- Indexes for table `ingredient_templates`
--
ALTER TABLE `ingredient_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_template_name_unit` (`item_name`,`unit`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_category` (`category`),
  ADD KEY `idx_inventory_supplier` (`supplier`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`),
  ADD KEY `idx_inventory_deleted_at` (`deleted_at`),
  ADD KEY `idx_inventory_branch` (`branch_id`),
  ADD KEY `idx_inventory_template` (`template_id`);

--
-- Indexes for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `batch_id` (`batch_id`),
  ADD KEY `resolved_by` (`resolved_by`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_alert_type` (`alert_type`);

--
-- Indexes for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_expiry_date` (`expiry_date`),
  ADD KEY `idx_batch_number` (`batch_number`),
  ADD KEY `idx_inventory_batches_expiry` (`expiry_date`);

--
-- Indexes for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_name` (`category_name`),
  ADD KEY `parent_category_id` (`parent_category_id`);

--
-- Indexes for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_counts_inventory` (`inventory_id`),
  ADD KEY `idx_counts_branch` (`branch_id`),
  ADD KEY `idx_counts_date` (`counted_at`);

--
-- Indexes for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `idx_inventory_deliveries_status` (`status`);

--
-- Indexes for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_change_date` (`change_date`),
  ADD KEY `idx_inventory_id` (`inventory_id`);

--
-- Indexes for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `update_date` (`update_date`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `performed_by` (`performed_by`),
  ADD KEY `idx_inventory_id` (`inventory_id`),
  ADD KEY `idx_batch_id` (`batch_id`),
  ADD KEY `idx_movement_type` (`movement_type`),
  ADD KEY `idx_inventory_movements_date` (`created_at`),
  ADD KEY `idx_movements_branch` (`branch_id`),
  ADD KEY `idx_movements_type` (`movement_type`),
  ADD KEY `idx_movements_date` (`created_at`);

--
-- Indexes for table `inventory_orders`
--
ALTER TABLE `inventory_orders`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `inventory_order_items`
--
ALTER TABLE `inventory_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `inventory_id` (`inventory_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `cashier_id` (`cashier_id`),
  ADD KEY `idx_orders_date_time` (`date_time`),
  ADD KEY `idx_orders_cashier_id` (`cashier_id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `idx_orders_branch` (`branch_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_product_id` (`product_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`);

--
-- Indexes for table `permission_logs`
--
ALTER TABLE `permission_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_products_status_stock` (`status`,`stock`);

--
-- Indexes for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pi_template` (`template_id`);

--
-- Indexes for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_product_inventory` (`product_id`,`inventory_id`),
  ADD KEY `inventory_id` (`inventory_id`),
  ADD KEY `batch_id` (`batch_id`);

--
-- Indexes for table `restock_requests`
--
ALTER TABLE `restock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `status` (`status`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sale_date` (`sale_date`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `stock_receiving`
--
ALTER TABLE `stock_receiving`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiving_branch` (`branch_id`),
  ADD KEY `idx_receiving_date` (`received_date`);

--
-- Indexes for table `stock_receiving_items`
--
ALTER TABLE `stock_receiving_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_receiving_items_recv` (`receiving_id`),
  ADD KEY `idx_receiving_items_inv` (`inventory_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_supplier_name` (`supplier_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `user_id_2` (`user_id`),
  ADD KEY `branch_id` (`branch_id`);

--
-- Indexes for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `reading_time` (`reading_time`),
  ADD KEY `generated_by` (`generated_by`);

--
-- Indexes for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `shift_id` (`shift_id`),
  ADD KEY `closing_time` (`closing_time`),
  ADD KEY `generated_by` (`generated_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `cashier_inventory_counts`
--
ALTER TABLE `cashier_inventory_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ingredient_templates`
--
ALTER TABLE `ingredient_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=137;

--
-- AUTO_INCREMENT for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_counts`
--
ALTER TABLE `inventory_counts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory_history`
--
ALTER TABLE `inventory_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `inventory_log`
--
ALTER TABLE `inventory_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `inventory_orders`
--
ALTER TABLE `inventory_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory_order_items`
--
ALTER TABLE `inventory_order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=223;

--
-- AUTO_INCREMENT for table `permission_logs`
--
ALTER TABLE `permission_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=211;

--
-- AUTO_INCREMENT for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `restock_requests`
--
ALTER TABLE `restock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `sales_history`
--
ALTER TABLE `sales_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `stock_receiving`
--
ALTER TABLE `stock_receiving`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_receiving_items`
--
ALTER TABLE `stock_receiving_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cashier_shifts`
--
ALTER TABLE `cashier_shifts`
  ADD CONSTRAINT `cashier_shifts_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cashier_shifts_ibfk_2` FOREIGN KEY (`started_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `cashier_shifts_ibfk_3` FOREIGN KEY (`closed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `cash_drop_log`
--
ALTER TABLE `cash_drop_log`
  ADD CONSTRAINT `cash_drop_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cash_drop_log_ibfk_2` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `fk_inventory_branch` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_inventory_template` FOREIGN KEY (`template_id`) REFERENCES `ingredient_templates` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_alerts`
--
ALTER TABLE `inventory_alerts`
  ADD CONSTRAINT `inventory_alerts_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_alerts_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_alerts_ibfk_3` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_batches`
--
ALTER TABLE `inventory_batches`
  ADD CONSTRAINT `inventory_batches_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_categories`
--
ALTER TABLE `inventory_categories`
  ADD CONSTRAINT `inventory_categories_ibfk_1` FOREIGN KEY (`parent_category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_deliveries`
--
ALTER TABLE `inventory_deliveries`
  ADD CONSTRAINT `inventory_deliveries_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_history`
--
ALTER TABLE `inventory_history`
  ADD CONSTRAINT `inventory_history_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `inventory_log`
--
ALTER TABLE `inventory_log`
  ADD CONSTRAINT `inventory_log_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_movements`
--
ALTER TABLE `inventory_movements`
  ADD CONSTRAINT `inventory_movements_ibfk_1` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_movements_ibfk_2` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `inventory_movements_ibfk_3` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `inventory_order_items`
--
ALTER TABLE `inventory_order_items`
  ADD CONSTRAINT `inventory_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `inventory_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_order_items_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cashier_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `orders_ibfk_3` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `product_ingredients`
--
ALTER TABLE `product_ingredients`
  ADD CONSTRAINT `fk_pi_template` FOREIGN KEY (`template_id`) REFERENCES `ingredient_templates` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `product_inventory_usage`
--
ALTER TABLE `product_inventory_usage`
  ADD CONSTRAINT `product_inventory_usage_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inventory_usage_ibfk_2` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `product_inventory_usage_ibfk_3` FOREIGN KEY (`batch_id`) REFERENCES `inventory_batches` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sales_history`
--
ALTER TABLE `sales_history`
  ADD CONSTRAINT `sales_history_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`);

--
-- Constraints for table `x_reading_log`
--
ALTER TABLE `x_reading_log`
  ADD CONSTRAINT `x_reading_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `x_reading_log_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `z_reading_log`
--
ALTER TABLE `z_reading_log`
  ADD CONSTRAINT `z_reading_log_ibfk_1` FOREIGN KEY (`shift_id`) REFERENCES `cashier_shifts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `z_reading_log_ibfk_2` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
