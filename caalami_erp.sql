-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 26, 2026 at 08:42 PM
-- Server version: 10.11.16-MariaDB
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `caalami_erp`
--

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`id`, `code`, `name`) VALUES
(1, 'boorama', 'Boorama Branch'),
(2, 'hargeisa', 'Hargeisa Main Branch'),
(3, 'berbera', 'Berbera Port Branch'),
(4, 'burco', 'Burco Central Branch');

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `status`, `created_at`) VALUES
(1, 'PHONE PARTS', 'Active', '2026-11-20 15:06:48'),
(2, 'EARPHONE', 'Active', '2026-11-20 15:06:48'),
(3, 'POWER BANK', 'Active', '2026-11-20 15:06:48'),
(4, 'LIGHT', 'Active', '2026-11-20 15:06:48'),
(5, 'FIISH', 'Active', '2026-11-20 15:06:48'),
(6, 'CHARGER', 'Active', '2026-11-20 15:06:48'),
(7, 'PHONE COVER', 'Active', '2026-11-20 15:06:48'),
(8, 'GAME PARTS', 'Active', '2026-11-20 15:06:48'),
(9, 'GLASS', 'Active', '2026-11-20 15:06:48'),
(10, 'COOL BACK', 'Active', '2026-11-20 15:06:48'),
(11, 'BATTERY', 'Active', '2026-11-20 15:06:48'),
(12, 'CABLE', 'Active', '2026-11-20 15:06:48'),
(13, 'DISPLAY', 'Active', '2026-11-20 15:06:48'),
(15, 'xoosh', 'Active', '2026-11-20 15:11:46'),
(16, 'PHONE', 'Active', '2026-11-20 18:56:27');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `address` varchar(255) DEFAULT 'Boorama',
  `description` text DEFAULT NULL,
  `created_user` varchar(100) DEFAULT 'Admin',
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `code`, `name`, `phone`, `balance`, `branch`, `address`, `description`, `created_user`, `created_at`) VALUES
(1, 'walk-in', 'Walk-In Customer', '', 10.00, 'Boorama Branch', 'Boorama', NULL, 'Admin', '2026-11-22'),
(5, 'cust-04', 'SAMATAR ELECTRONICS', '+252 63 4771234', 0.00, 'Boorama Branch', 'Boorama', NULL, 'Admin', '2026-11-22'),
(6, 'cust-05', 'GOLIS TELECOM', '+252 63 4118899', 0.00, 'Boorama Branch', 'Boorama', NULL, 'Admin', '2026-11-22'),
(9, 'MC-005', 'SALIS OF 19/2/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(12, 'MC-008', 'SALIS OF 19/FEB/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(13, 'MC-009', 'SALIS OF 20/FEB/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(14, 'MC-010', 'SALIS OF 20/FEB/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(15, 'MC-011', 'SALIS OF 20/FEB/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(16, 'MC-012', 'SALIS OF 20/FEB/26', '0634531454', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-02-25'),
(19, 'MC-015', 'DOOL DAAHIR', '', 0.00, 'Boorama Branch', 'Boorama', '-', 'Admin', '2026-03-02'),
(20, 'MC-020', 'UMALKHAYR IBRAHIM', '634568734', 17.00, 'Boorama Branch', 'BORAMA', '', 'Admin', '2026-11-23'),
(21, 'MC-021', 'Nuur aadan buraale', '634500768', 0.00, 'Boorama Branch', 'BORAMA', '', 'Ridwaan Admin', '2026-11-25'),
(22, 'MC-022', 'raadiya', '0634458536', 0.00, 'Boorama Branch', 'BORAMA', '', 'Ridwaan Admin', '2026-11-26');

-- --------------------------------------------------------

--
-- Table structure for table `daily_closings`
--

CREATE TABLE `daily_closings` (
  `id` int(11) NOT NULL,
  `closing_date` date NOT NULL,
  `branch` varchar(100) NOT NULL DEFAULT 'Boorama Branch',
  `opening_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_debt` decimal(10,2) NOT NULL DEFAULT 0.00,
  `sales_count` int(11) NOT NULL DEFAULT 0,
  `repairs_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `repairs_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `repairs_count` int(11) NOT NULL DEFAULT 0,
  `payments_collected` decimal(10,2) NOT NULL DEFAULT 0.00,
  `purchases_paid` decimal(10,2) NOT NULL DEFAULT 0.00,
  `technician_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expenses` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_in_hand` decimal(10,2) NOT NULL DEFAULT 0.00,
  `zaad_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `edahab_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `bank_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `net_profit` decimal(10,2) NOT NULL DEFAULT 0.00,
  `expected_closing_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `actual_closing_cash` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cash_difference` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `closed_by` varchar(100) NOT NULL DEFAULT 'Admin',
  `closed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `deliveries`
--

CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `customer` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `driver` varchar(100) DEFAULT NULL,
  `eta` varchar(50) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `deliveries`
--

INSERT INTO `deliveries` (`id`, `code`, `customer`, `address`, `status`, `driver`, `eta`, `items`, `priority`) VALUES
(1, 'DEL-401', 'Al-Baraka Hospital IT Dept', 'Borama West Medical District, Near Amoud', 'In Transit', 'Mukhtar Jama', '35 mins', '3x UPS APC Smart 1500VA + Batteries', 'High'),
(2, 'DEL-402', 'Eng. Mustafe Ahmed', 'Haji Dahir St, Boorama Central', 'Dispatched', 'Farhan Ali', '1 hour', 'Solar Inverter 5kW + Cable Pack', 'Normal'),
(3, 'DEL-403', 'City Plaza Electronics', 'Commercial Market Road, Boorama', 'Delivered', 'Guled Hassan', 'Completed', '50x Fast Charging Cables, 20x Adapters', 'Normal');

-- --------------------------------------------------------

--
-- Table structure for table `measurements`
--

CREATE TABLE `measurements` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `measurements`
--

INSERT INTO `measurements` (`id`, `name`, `symbol`, `status`, `created_at`) VALUES
(1, 'Pieces', 'Pcs', 'Active', '2026-11-20 15:06:48'),
(2, 'Box', 'Box', 'Active', '2026-11-20 15:06:48'),
(3, 'Set', 'Set', 'Active', '2026-11-20 15:06:48');

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE `payments` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `reference_code` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT current_timestamp(),
  `customer_name` varchar(150) DEFAULT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `transaction_type` varchar(50) DEFAULT 'Return Credits',
  `previous_balance` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `remaining_balance` decimal(10,2) DEFAULT 0.00,
  `department` varchar(100) DEFAULT 'Boorama Branch',
  `created_user` varchar(100) DEFAULT 'Ridwaan'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`id`, `sale_id`, `invoice_number`, `amount`, `payment_method`, `reference_code`, `notes`, `paid_at`, `customer_name`, `customer_phone`, `transaction_type`, `previous_balance`, `discount`, `remaining_balance`, `department`, `created_user`) VALUES
(2, 5, '#4', 1.00, 'Cash On Hand', 'REF-816015', 'Initial payment at POS', '2026-11-22 17:28:43', NULL, NULL, 'Return Credits', 0.00, 0.00, 0.00, 'Boorama Branch', 'Ridwaan'),
(3, NULL, 'REC-351', 530.00, 'Zaad Services', NULL, NULL, '2026-11-17 19:50:00', 'SALIS O 1/3/26', '0634531454', 'Return Credits', 530.00, 0.00, 0.00, 'Boorama Branch', 'Ridwaan'),
(5, NULL, 'REC-353', 60.00, 'Cash On Hand', NULL, NULL, '2026-10-22 04:18:00', 'AXMED MAAL', '0634531454', 'Return Credits', 3165.40, 0.00, 3105.40, 'Boorama Branch', 'A/rahman Ismail Ali'),
(6, NULL, 'REC-354', 100.00, 'E-Dahab', NULL, NULL, '2026-10-05 13:12:00', 'Ahmed Mohamed', '252634363767', 'Return Credits', 120.00, 0.00, 20.00, 'Boorama Branch', 'A/rahman Ismail Ali'),
(7, NULL, 'REC-355', 27.50, 'Zaad Services', NULL, NULL, '2026-09-16 14:45:00', 'DAAHIR CADUUR', '', 'Return Credits', 39.00, 0.00, 11.50, 'Boorama Branch', 'N/A'),
(8, NULL, 'REC-356', 100.00, 'Zaad Services', NULL, NULL, '2026-09-16 14:21:00', 'GARAAD', '', 'Return Credits', 200.30, 0.00, 100.30, 'Boorama Branch', 'N/A'),
(9, NULL, 'REC-357', -16.00, 'Zaad Services', NULL, NULL, '2026-09-16 14:19:00', 'DOOL DAAHIR', '', 'Return Credits', -16.00, 0.00, 0.00, 'Boorama Branch', 'N/A'),
(10, NULL, 'REC-358', 100.00, 'Zaad Services', NULL, NULL, '2026-09-14 13:49:00', 'ENG CASOOWE', '', 'Return Credits', 735.08, 0.00, 635.08, 'Boorama Branch', 'N/A'),
(11, NULL, 'REC-359', 700.00, 'Zaad Services', NULL, NULL, '2026-09-12 15:01:00', 'SHIIL', '4528959', 'Return Credits', 1482.28, 0.00, 782.28, 'Boorama Branch', 'N/A'),
(12, NULL, 'REC-360', 15.00, 'Zaad Services', NULL, NULL, '2026-09-10 14:53:00', '10/8/26', '', 'Return Credits', 35.07, 0.00, 20.07, 'Boorama Branch', 'N/A');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `subcategory` varchar(150) DEFAULT '',
  `type` varchar(50) DEFAULT 'Brand New',
  `ram` varchar(50) DEFAULT '',
  `storage` varchar(50) DEFAULT '',
  `retail_price` decimal(10,2) DEFAULT 0.00,
  `wholesale_price` decimal(10,2) DEFAULT 0.00,
  `stock_qty` int(11) DEFAULT 15,
  `min_threshold` int(11) DEFAULT 5,
  `image_icon` varchar(50) DEFAULT '?',
  `unit` varchar(50) DEFAULT 'Pcs',
  `last_price` decimal(10,2) DEFAULT 0.00,
  `prev_price` decimal(10,2) DEFAULT 0.00,
  `prev_wholesale` decimal(10,2) DEFAULT 0.00,
  `supplier` varchar(150) DEFAULT '',
  `created_user` varchar(100) DEFAULT 'Admin',
  `created_at` date DEFAULT curdate(),
  `barcode` varchar(100) DEFAULT '',
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `category`, `subcategory`, `type`, `ram`, `storage`, `retail_price`, `wholesale_price`, `stock_qty`, `min_threshold`, `image_icon`, `unit`, `last_price`, `prev_price`, `prev_wholesale`, `supplier`, `created_user`, `created_at`, `barcode`, `notes`) VALUES
(27, 'SUPERX-398', 'SUPER X GLASS', 'GLASS', 'GLASS', 'GLASS', '', '', 1.00, 0.00, 56, 5, '📱', 'Pcs', 0.00, 0.00, 0.00, 'Socdaal mobile accessories', 'Ridwaan', '2026-11-22', '', NULL),
(28, 'CABLEC-723', 'CABLE-C', 'CABLE', 'Type-C to Type-C 100W Cable', 'Type-C to Type-C 100W Cable', '', '', 1.00, 0.00, 19, 5, '📱', 'Pcs', 0.00, 0.00, 0.00, 'Socdaal mobile accessories', 'Ridwaan', '2026-11-22', '', NULL),
(29, 'TECNOMO-188', 'TECNO MOBILE', 'PHONE', 'PHONE', 'PHONE', '', '', 8.00, 0.00, 4, 5, '📱', 'Pcs', 7.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-22', '2000020448205', ''),
(30, 'AIRPODS-337', 'AIRPODS MAX', 'EARPHONE', 'Airpods Pro / Max', 'Airpods Pro / Max', '', '', 10.00, 0.00, 1, 5, '📱', 'Pcs', 9.00, 0.00, 0.00, 'Socdaal mobile accessories', 'Admin', '2026-11-23', '2000057009868', ''),
(31, 'LCDOPPO-319', 'LCD OPPO A77S BLK ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 10.00, 0.00, 0, 5, '📱', 'Pcs', 7.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-23', '2000018694613', ''),
(32, 'SAMSUNG-366', 'Samsung Cover', 'PHONE COVER', 'Clear MagSafe Case', 'Clear MagSafe Case', '', '', 2.00, 0.00, 29, 5, '📱', 'Pcs', 1.50, 0.00, 0.00, 'Socdaal mobile accessories', 'Admin', '2026-11-23', '2000028284892', ''),
(33, 'LCDIPH-237', 'LCD IPH 16 PRO MAX GX BLK ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 50.00, 0.00, 0, 5, '📱', 'Pcs', 42.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-24', '2000054085063', ''),
(34, 'LCDIPH-148', 'LCD IPH 14 PRO MAX INC BLK ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 30.00, 0.00, 0, 5, '📱', 'Pcs', 15.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-24', '2000033037417', ''),
(35, 'IPH14P-107', 'IPH 14 PRO MAX BACK COVER', 'PHONE PARTS', 'BACK GLASS', 'BACK GLASS', '', '', 6.00, 0.00, 2, 5, '📱', 'Pcs', 2.50, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-24', '2000080478823', ''),
(36, 'LCDIPH-267', 'LCD IPH 13 PRO MAX INC BLK ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 30.00, 0.00, 1, 5, '📱', 'Pcs', 13.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-24', '2000084247688', ''),
(37, 'LCDA17-810', 'LCD A17 INCELL FRAME', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 10.00, 0.00, 1, 5, '📱', 'Pcs', 7.50, 0.00, 0.00, 'CAALAMI ELETRONIC', 'Admin', '2026-11-24', '2000074009639', ''),
(38, 'FLEXCAB-302', 'flex cable I.PH 13 pro max', 'PHONE PARTS', 'Charging Flex Cable', 'Charging Flex Cable', '', '', 7.00, 0.00, 0, 5, '📱', 'Pcs', 4.50, 0.00, 0.00, '', 'Admin', '2026-11-25', '2000086751675', ''),
(39, 'LCDA042-523', 'LCD A042 (A04E)SAM BLK ORG 100%', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 8.00, 0.00, 2, 5, '📱', 'Pcs', 7.50, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000060456891', ''),
(40, 'LCDA11-188', 'LCD A11 SAM BLK ORG 100%', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 8.00, 0.00, 2, 5, '📱', 'Pcs', 7.50, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000067725785', ''),
(41, 'LCDA02S-427', 'LCD A02S SAM BLK ORG 100%', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 8.00, 0.00, 1, 5, '📱', 'Pcs', 7.50, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000024917392', ''),
(42, 'LCDINFI-591', 'LCD INFINIX HOT 50 / X670/ SMART 9 BLK ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 8.00, 0.00, 1, 5, '📱', 'Pcs', 6.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000058409354', ''),
(43, 'LCDA047-616', 'LCD A047/A136 SAM BLK ORG 100%', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 8.00, 0.00, 0, 5, '📱', 'Pcs', 7.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000047010427', ''),
(44, 'LCDIPH-785', 'LCD IPH XSMAX GX ORG', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 23.00, 0.00, 1, 5, '📱', 'Pcs', 21.50, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000020351970', ''),
(45, 'A13ONO-627', 'A13 ON/OFF SAM ORG', 'PHONE PARTS', 'POWER', 'POWER', '', '', 2.00, 0.00, 1, 5, '📱', 'Pcs', 1.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000030718610', ''),
(46, 'LCDA16-121', 'LCD A16 INCELL SAM BLK ORG 100%', 'PHONE PARTS', 'LCD Screens', 'LCD Screens', '', '', 9.00, 0.00, 1, 5, '📱', 'Pcs', 7.00, 0.00, 0.00, 'DUBIA', 'Admin', '2026-11-26', '2000081190773', '');

-- --------------------------------------------------------

--
-- Table structure for table `product_exchanges`
--

CREATE TABLE `product_exchanges` (
  `id` int(11) NOT NULL,
  `exchange_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `returned_item` varchar(255) DEFAULT NULL,
  `new_item` varchar(255) DEFAULT NULL,
  `price_diff` decimal(10,2) DEFAULT 0.00,
  `exchange_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `product_exchanges`
--

INSERT INTO `product_exchanges` (`id`, `exchange_number`, `customer_name`, `returned_item`, `new_item`, `price_diff`, `exchange_date`, `notes`) VALUES
(1, 'EXC-101', 'WARSAME ELECTRONIC', 'iPhone 13 (Screen issue)', 'iPhone 13 128GB (Replacement)', 0.00, '2026-11-12', 'Warranty replacement approved by technician'),
(2, 'EXC-102', 'COSOB ELECTRONIC', 'Samsung A15 64GB', 'Samsung A16 128GB', 30.00, '2026-11-13', 'Customer upgraded, paid $30 difference');

-- --------------------------------------------------------

--
-- Table structure for table `purchases`
--

CREATE TABLE `purchases` (
  `id` int(11) NOT NULL,
  `purchase_no` varchar(50) DEFAULT NULL,
  `supplier_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `warehouse` varchar(100) DEFAULT 'Boorama Branch',
  `purchase_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `type` varchar(50) DEFAULT 'Cash & Credit',
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `net_price` decimal(10,2) DEFAULT 0.00,
  `additionals` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `balance` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'ZAAD',
  `created_user` varchar(100) DEFAULT 'Ridwaan',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchases`
--

INSERT INTO `purchases` (`id`, `purchase_no`, `supplier_name`, `phone`, `warehouse`, `purchase_date`, `due_date`, `type`, `subtotal`, `discount`, `net_price`, `additionals`, `paid_amount`, `balance`, `payment_method`, `created_user`, `notes`, `created_at`) VALUES
(5, '#2983', 'RIDWAAN HUSSEIN', '634578264', 'Boorama Branch', '2026-11-20', '2026-11-20', 'Credit', 280.00, 0.00, 280.00, 0.00, 0.00, 280.00, 'ZAAD', 'Ridwaan', NULL, '2026-11-20 19:57:11'),
(6, '#4291', 'RIDWAAN HUSSEIN', '634578264', 'Boorama Branch', '2026-11-20', '2026-11-20', 'Credit', 600.00, 0.00, 600.00, 0.00, 0.00, 600.00, 'ZAAD', 'Ridwaan', NULL, '2026-11-20 20:28:50'),
(7, '#2665', 'Socdaal mobile accessories', '634706094', 'Boorama Branch', '2026-11-22', '2026-11-22', 'Credit', 16.80, 0.00, 16.80, 0.00, 0.00, 16.80, 'ZAAD', 'Ridwaan', NULL, '2026-11-22 14:24:49'),
(8, '#4677', 'Socdaal mobile accessories', '634706094', 'Boorama Branch', '2026-11-22', '2026-11-22', 'Credit', 14.00, 0.00, 14.00, 0.00, 0.00, 14.00, 'ZAAD', 'Ridwaan', NULL, '2026-11-22 14:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_items`
--

CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit` varchar(50) DEFAULT 'Pcs',
  `last_price` decimal(10,2) DEFAULT 0.00,
  `add_cost` decimal(10,2) DEFAULT 0.00,
  `wholesale_price` decimal(10,2) DEFAULT 0.00,
  `retail_price` decimal(10,2) DEFAULT 0.00,
  `total_cost` decimal(10,2) DEFAULT 0.00,
  `remaining_qty` int(11) DEFAULT 0,
  `supplier` varchar(150) DEFAULT 'INVENTORY STORE',
  `created_user` varchar(100) DEFAULT 'Ridwaan',
  `purchased_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_items`
--

INSERT INTO `purchase_items` (`id`, `purchase_id`, `product_name`, `category`, `qty`, `unit`, `last_price`, `add_cost`, `wholesale_price`, `retail_price`, `total_cost`, `remaining_qty`, `supplier`, `created_user`, `purchased_date`) VALUES
(1178, 5, 'Samsung Galaxy A20 4G', 'Mobile', 1, 'Pcs', 280.00, 0.00, 280.00, 0.00, 280.00, 1, 'RIDWAAN HUSSEIN', 'Ridwaan', '2026-11-20 22:57:11'),
(1179, 6, 'Samsung Galaxy A30 4G', 'Accessories', 2, 'Pcs', 300.00, 0.00, 300.00, 0.00, 600.00, 2, 'RIDWAAN HUSSEIN', 'Ridwaan', '2026-11-20 23:28:50'),
(1180, 7, 'SUPER X GLASS', 'GLASS', 60, 'Pcs', 0.28, 0.00, 0.28, 0.00, 16.80, 60, 'Socdaal mobile accessories', 'Ridwaan', '2026-11-22 17:24:49'),
(1181, 8, 'CABLE-C', 'Accessories', 20, 'Pcs', 0.70, 0.00, 0.70, 0.00, 14.00, 20, 'Socdaal mobile accessories', 'Ridwaan', '2026-11-22 17:26:35');

-- --------------------------------------------------------

--
-- Table structure for table `purchase_returns`
--

CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL,
  `return_no` varchar(50) DEFAULT NULL,
  `purchase_id` int(11) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `supplier` varchar(150) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `return_qty` int(11) DEFAULT 1,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `refund_method` varchar(100) DEFAULT 'Deduct Balance',
  `reason` text DEFAULT NULL,
  `recorded_by` varchar(100) DEFAULT 'A/rahman Ismail Ali',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_returns`
--

INSERT INTO `purchase_returns` (`id`, `return_no`, `purchase_id`, `return_date`, `supplier`, `product_name`, `return_qty`, `unit_cost`, `total_amount`, `refund_method`, `reason`, `recorded_by`, `created_at`) VALUES
(1, 'PR-20260924-001', NULL, '2026-09-24', 'Test', 'Iphone 15', 1, 1.00, 1.00, 'Deduct Balance', 'Damaged screen upon arrival', 'A/rahman Ismail Ali', '2026-11-19 18:52:16'),
(2, 'PR-20261005-001', NULL, '2026-10-05', 'STORE INVENTORY', 'Iphone 17', 1, 50.00, 50.00, 'Deduct Balance', 'Defective return test', 'A/rahman Ismail Ali', '2026-11-19 18:52:16');

-- --------------------------------------------------------

--
-- Table structure for table `quotations`
--

CREATE TABLE `quotations` (
  `id` int(11) NOT NULL,
  `quotation_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `quote_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `items_summary` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `quotations`
--

INSERT INTO `quotations` (`id`, `quotation_number`, `customer_name`, `branch`, `quote_date`, `valid_until`, `total_amount`, `items_summary`, `status`) VALUES
(1, 'QT-201', 'Amoud University Computer Lab', 'Boorama Branch', '2026-11-10', '2026-11-24', 4500.00, '10x HP ProDesk Desktops, 10x Monitors, 1x Switch 24-Port', 'Pending'),
(2, 'QT-202', 'Telesom Boorama Regional Office', 'Boorama Branch', '2026-11-12', '2026-11-26', 1250.00, '2x Starlink V4 Kits + Mounting Poles & PoE kits', 'Pending'),
(3, 'QT-203', 'Borama General Hospital Pharmacy', 'Boorama Branch', '2026-11-14', '2026-11-28', 850.00, '3x Barcode Scanners, 2x Thermal Receipt Printers, POS Drawer', 'Pending');

-- --------------------------------------------------------

--
-- Table structure for table `repair_jobs`
--

CREATE TABLE `repair_jobs` (
  `id` int(11) NOT NULL,
  `job_number` varchar(50) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(50) DEFAULT '',
  `device_model` varchar(150) NOT NULL,
  `brand` varchar(100) DEFAULT '',
  `serial_imei` varchar(100) DEFAULT '',
  `passcode` varchar(100) DEFAULT '',
  `reported_problem` text DEFAULT NULL,
  `diagnostic_notes` text DEFAULT NULL,
  `technician_id` int(11) DEFAULT NULL,
  `technician_name` varchar(150) DEFAULT 'Unassigned',
  `technician_cost` decimal(10,2) DEFAULT 0.00,
  `service_id` int(11) DEFAULT NULL,
  `service_name` varchar(150) DEFAULT '',
  `service_cost` decimal(10,2) DEFAULT 0.00,
  `parts_cost` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `status` enum('Received','In Progress','Ready','Delivered','Cancelled') DEFAULT 'Received',
  `payment_method` varchar(50) DEFAULT 'Cash',
  `intake_date` date DEFAULT NULL,
  `expected_date` date DEFAULT NULL,
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `created_by` varchar(100) DEFAULT 'Admin',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `repair_jobs`
--

INSERT INTO `repair_jobs` (`id`, `job_number`, `customer_id`, `customer_name`, `customer_phone`, `device_model`, `brand`, `serial_imei`, `passcode`, `reported_problem`, `diagnostic_notes`, `technician_id`, `technician_name`, `technician_cost`, `service_id`, `service_name`, `service_cost`, `parts_cost`, `discount`, `total_amount`, `paid_amount`, `status`, `payment_method`, `intake_date`, `expected_date`, `branch`, `created_by`, `created_at`) VALUES
(4, 'REP-20261125-4039', 1, 'Walk-In Customer', '', 'IPHONE 14 PRO MAX', '', '', '', 'waxa laga badalayaa screen ka iyo back glass oo jabay', '', 1, 'Ahmed Nur Farah', 6.00, NULL, 'back glass replacement + screening', 20.00, 36.00, 0.00, 56.00, 56.00, 'Ready', 'Cash', '2026-11-25', '2026-11-26', 'Boorama Branch', 'Ridwaan Admin', '2026-11-25 08:32:03'),
(5, 'REP-20261125-8630', 1, 'Walk-In Customer', '', 'IPHONE 16 PRO MAX', '', '', '', 'waxa kasoo jabay display oo laga badalay', '', 2, 'Abdirahman Daud', 6.00, NULL, 'screening', 15.00, 51.00, 0.00, 66.00, 66.00, 'Ready', 'Cash', '2026-11-25', '2026-11-26', 'Boorama Branch', 'Ridwaan Admin', '2026-11-25 08:57:44'),
(6, 'REP-20261125-2851', 21, 'Nuur aadan buraale', '634500768', 'IPHONE 13 PRO MAX', '', '', '', 'waxa laga badalayay screen iyo speaker ka', '', 2, 'Abdirahman Daud', 5.00, NULL, 'Charger Repair + screening', 13.00, 37.00, 0.00, 50.00, 40.00, 'Ready', 'Zaad Services', '2026-11-25', '2026-11-26', 'Boorama Branch', 'Ridwaan Admin', '2026-11-25 19:18:44'),
(7, 'REP-20261126-6059', 22, 'raadiya', '0634458536', 'A13', '', '', '', 'display laga badalaayay', '', 2, 'Abdirahman Daud', 2.00, NULL, 'screening', 2.00, 10.00, 0.00, 12.00, 0.00, 'Ready', 'Cash', '2026-11-26', '2026-11-27', 'Boorama Branch', 'Ridwaan Admin', '2026-11-26 19:01:28');

-- --------------------------------------------------------

--
-- Table structure for table `repair_job_parts`
--

CREATE TABLE `repair_job_parts` (
  `id` int(11) NOT NULL,
  `repair_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `part_name` varchar(200) NOT NULL,
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `repair_job_parts`
--

INSERT INTO `repair_job_parts` (`id`, `repair_id`, `product_id`, `part_name`, `qty`, `price`, `total`) VALUES
(3, 4, 34, 'LCD IPH 14 PRO MAX INC BLK ORG', 1, 30.00, 30.00),
(4, 4, 35, 'IPH 14 PRO MAX BACK COVER', 1, 6.00, 6.00),
(5, 5, 33, 'LCD IPH 16 PRO MAX GX BLK ORG', 1, 50.00, 50.00),
(6, 5, 27, 'SUPER X GLASS', 1, 1.00, 1.00),
(9, 6, 38, 'flex cable I.PH 13 pro max', 1, 7.00, 7.00),
(10, 6, 36, 'LCD IPH 13 PRO MAX INC BLK ORG', 1, 30.00, 30.00),
(11, 7, 43, 'LCD A047/A136 SAM BLK ORG 100%', 1, 9.00, 9.00),
(12, 7, 27, 'SUPER X GLASS', 1, 1.00, 1.00);

-- --------------------------------------------------------

--
-- Table structure for table `repair_services`
--

CREATE TABLE `repair_services` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `repair_services`
--

INSERT INTO `repair_services` (`id`, `name`, `labor_cost`, `description`, `created_at`) VALUES
(1, 'screening', 10.00, 'Screen diagnostic and replacement service', '2026-11-24 16:49:45'),
(2, 'Charger Repair', 3.00, '', '2026-11-24 17:29:54'),
(3, 'back glass replacement', 7.00, '', '2026-11-25 08:28:43');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `invoice_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `sale_date` date DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `month` varchar(20) DEFAULT NULL,
  `subtotal` decimal(10,2) DEFAULT 0.00,
  `discount` decimal(10,2) DEFAULT 0.00,
  `total_sale` decimal(10,2) DEFAULT 0.00,
  `paid_amount` decimal(10,2) DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT 'Cash On Hand',
  `notes` text DEFAULT NULL,
  `items_summary` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Due',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `invoice_number`, `customer_name`, `phone`, `branch`, `sale_date`, `return_date`, `year`, `month`, `subtotal`, `discount`, `total_sale`, `paid_amount`, `payment_method`, `notes`, `items_summary`, `status`, `created_at`) VALUES
(1, '#797', 'WARSAME ELECTRONIC', '+252 63 4001923', 'Boorama Branch', '2026-11-22', '2026-12-06', '2026', 'November', 1.00, 0.00, 1.00, 1.00, 'Cash On Hand', '', '1x SUPER X GLASS', 'Paid', '2026-11-22 14:51:40'),
(3, '#755', 'Walk-In Customer', '+252 63 4001923', 'Boorama Branch', '2026-11-22', '2026-11-29', '2026', 'November', 8.00, 0.00, 8.00, 8.00, 'Cash On Hand', '', '1x TECNO MOBILE', 'Paid', '2026-11-22 15:49:32'),
(5, '#4', 'Walk-In Customer', '+252 63 4001923', 'Boorama Branch', '2026-11-22', '2026-11-29', '2026', 'November', 1.00, 0.00, 1.00, 1.00, 'Cash On Hand', '', '1x CABLE-C', 'Paid', '2026-11-22 17:28:43'),
(6, '#6', 'Walk-In Customer', '+252 63 4001923', 'Boorama Branch', '2026-11-23', '2026-11-30', '2026', 'November', 13.00, 0.00, 13.00, 13.00, 'Cash On Hand', '', '1x AIRPODS MAX', 'Paid', '2026-11-23 15:37:02'),
(7, '#7', 'UMALKHAYR IBRAHIM', '634568734', 'Boorama Branch', '2026-11-23', '2026-12-05', '2026', 'November', 14.00, 0.00, 14.00, 0.00, 'Cash On Hand', '', '1x LCD OPPO A77S BLK ORG, 1x SUPER X GLASS', 'Due', '2026-11-23 16:19:56'),
(8, '#8', 'UMALKHAYR IBRAHIM', '634568734', 'Boorama Branch', '2026-11-23', '2026-11-30', '2026', 'November', 3.00, 0.00, 3.00, 0.00, 'Cash On Hand', '', '1x Samsung Cover, 1x SUPER X GLASS', 'Due', '2026-11-23 18:20:28'),
(9, '#9', 'Walk-In Customer', '+252 63 4001923', 'Boorama Branch', '2026-11-26', '2026-11-29', '2026', 'November', 10.00, 0.00, 10.00, 0.00, 'Cash On Hand', '', '1x LCD A17 INCELL FRAME', 'Due', '2026-11-26 19:14:29');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `qty` int(11) DEFAULT 1,
  `unit_price` decimal(10,2) DEFAULT 0.00,
  `total_price` decimal(10,2) DEFAULT 0.00,
  `unit` varchar(20) DEFAULT 'Pcs',
  `sold_price` decimal(10,2) DEFAULT 0.00,
  `previous_price` decimal(10,2) DEFAULT 0.00,
  `last_price` decimal(10,2) DEFAULT 0.00,
  `prev_wholesale` decimal(10,2) DEFAULT 0.00,
  `wholesale_price` decimal(10,2) DEFAULT 0.00,
  `sale_type` varchar(50) DEFAULT 'Retail',
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `transaction_id` varchar(50) DEFAULT '#1',
  `created_by` varchar(100) DEFAULT 'Abdiwasac',
  `created_at` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `product_id`, `product_name`, `qty`, `unit_price`, `total_price`, `unit`, `sold_price`, `previous_price`, `last_price`, `prev_wholesale`, `wholesale_price`, `sale_type`, `branch`, `transaction_id`, `created_by`, `created_at`) VALUES
(1, 1, NULL, 'SUPER X GLASS', 1, 1.00, 1.00, 'Pcs', 0.00, 0.00, 0.00, 0.00, 0.00, 'Retail', 'Boorama Branch', '#1', 'Abdiwasac', NULL),
(3, 3, NULL, 'TECNO MOBILE', 1, 8.00, 8.00, 'Pcs', 0.00, 0.00, 0.00, 0.00, 0.00, 'Retail', 'Boorama Branch', '#1', 'Abdiwasac', NULL),
(5, 5, 28, 'CABLE-C', 1, 1.00, 1.00, 'Pcs', 1.00, 0.00, 0.00, 0.00, 0.70, 'Retail', 'Boorama Branch', '#4', 'Ridwaan', '2026-11-22'),
(6, 6, 30, 'AIRPODS MAX', 1, 13.00, 13.00, 'Pcs', 13.00, 0.00, 0.00, 0.00, 10.00, 'Retail', 'Boorama Branch', '#6', 'Ridwaan', '2026-11-23'),
(7, 7, 31, 'LCD OPPO A77S BLK ORG', 1, 13.00, 13.00, 'Pcs', 13.00, 0.00, 0.00, 0.00, 10.00, 'Retail', 'Boorama Branch', '#7', 'Ridwaan', '2026-11-23'),
(8, 7, 27, 'SUPER X GLASS', 1, 1.00, 1.00, 'Pcs', 1.00, 0.00, 0.00, 0.00, 1.00, 'Retail', 'Boorama Branch', '#7', 'Ridwaan', '2026-11-23'),
(9, 8, 32, 'Samsung Cover', 1, 2.00, 2.00, 'Pcs', 2.00, 0.00, 0.00, 0.00, 2.00, 'Retail', 'Boorama Branch', '#8', 'Ridwaan', '2026-11-23'),
(10, 8, 27, 'SUPER X GLASS', 1, 1.00, 1.00, 'Pcs', 1.00, 0.00, 0.00, 0.00, 1.00, 'Retail', 'Boorama Branch', '#8', 'Ridwaan', '2026-11-23'),
(11, 9, 37, 'LCD A17 INCELL FRAME', 1, 10.00, 10.00, 'Pcs', 10.00, 0.00, 0.00, 0.00, 10.00, 'Retail', 'Boorama Branch', '#9', 'Ridwaan Admin', '2026-11-26');

-- --------------------------------------------------------

--
-- Table structure for table `sub_categories`
--

CREATE TABLE `sub_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(150) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sub_categories`
--

INSERT INTO `sub_categories` (`id`, `category_name`, `name`, `status`, `created_at`) VALUES
(1, 'PHONE PARTS', 'LCD Screens', 'Active', '2026-11-20 15:06:48'),
(2, 'PHONE PARTS', 'Charging Flex Cable', 'Active', '2026-11-20 15:06:48'),
(3, 'PHONE PARTS', 'Camera Module', 'Active', '2026-11-20 15:06:48'),
(4, 'PHONE PARTS', 'Original Battery Pack', 'Active', '2026-11-20 15:06:48'),
(5, 'EARPHONE', 'Bluetooth Wireless Earbuds', 'Active', '2026-11-20 15:06:48'),
(6, 'EARPHONE', 'Wired 3.5mm Earphone', 'Active', '2026-11-20 15:06:48'),
(7, 'EARPHONE', 'Airpods Pro / Max', 'Active', '2026-11-20 15:06:48'),
(8, 'EARPHONE', 'Gaming Headset', 'Active', '2026-11-20 15:06:48'),
(9, 'POWER BANK', '10,000mAh Slim Pocket', 'Active', '2026-11-20 15:06:48'),
(10, 'POWER BANK', '20,000mAh Fast Charge', 'Active', '2026-11-20 15:06:48'),
(11, 'POWER BANK', '30,000mAh Heavy Duty', 'Active', '2026-11-20 15:06:48'),
(12, 'POWER BANK', 'MagSafe Wireless PowerBank', 'Active', '2026-11-20 15:06:48'),
(13, 'LIGHT', 'Ring Light 12 inch', 'Active', '2026-11-20 15:06:48'),
(14, 'LIGHT', 'RGB Desk Atmosphere Light', 'Active', '2026-11-20 15:06:48'),
(15, 'LIGHT', 'Studio Softbox Lamp', 'Active', '2026-11-20 15:06:48'),
(16, 'FIISH', 'Multi-Socket Extension 3M', 'Active', '2026-11-20 15:06:48'),
(17, 'FIISH', 'Universal Travel Plug', 'Active', '2026-11-20 15:06:48'),
(18, 'FIISH', 'Surge Protector 5-Way', 'Active', '2026-11-20 15:06:48'),
(19, 'CHARGER', '20W PD Fast Charger', 'Active', '2026-11-20 15:06:48'),
(20, 'CHARGER', '65W GaN Laptop Charger', 'Active', '2026-11-20 15:06:48'),
(21, 'CHARGER', 'Car Fast Charger Adapter', 'Active', '2026-11-20 15:06:48'),
(22, 'PHONE COVER', 'Silicone Soft Case', 'Active', '2026-11-20 15:06:48'),
(23, 'PHONE COVER', 'Shockproof Armor Case', 'Active', '2026-11-20 15:06:48'),
(24, 'PHONE COVER', 'Clear MagSafe Case', 'Active', '2026-11-20 15:06:48'),
(25, 'GAME PARTS', 'PS5 Wireless Controller', 'Active', '2026-11-20 15:06:48'),
(26, 'GAME PARTS', 'Console Cooling Fan Stand', 'Active', '2026-11-20 15:06:48'),
(27, 'GAME PARTS', 'Thumbstick Grips Pack', 'Active', '2026-11-20 15:06:48'),
(28, 'GLASS', '9D Privacy Glass Screen', 'Active', '2026-11-20 15:06:48'),
(29, 'GLASS', 'Ultra Clear Tempered Glass', 'Active', '2026-11-20 15:06:48'),
(30, 'GLASS', 'Camera Lens Protector', 'Active', '2026-11-20 15:06:48'),
(31, 'COOL BACK', 'Magnetic Phone Cooler Fan', 'Active', '2026-11-20 15:06:48'),
(32, 'COOL BACK', 'Dual Fan Radiator', 'Active', '2026-11-20 15:06:48'),
(33, 'xoosh', 'ali', 'Active', '2026-11-20 15:12:18'),
(34, 'BATTERY', 'iPhone Replacement Battery', 'Active', '2026-11-20 15:21:42'),
(35, 'BATTERY', 'Samsung Replacement Battery', 'Active', '2026-11-20 15:21:42'),
(36, 'BATTERY', 'Laptop Li-ion Battery', 'Active', '2026-11-20 15:21:42'),
(37, 'CABLE', 'Type-C to Type-C 100W Cable', 'Active', '2026-11-20 15:21:42'),
(38, 'CABLE', 'Lightning to Type-C Fast Cable', 'Active', '2026-11-20 15:21:42'),
(39, 'CABLE', 'HDMI 4K Ultra High Speed Cable', 'Active', '2026-11-20 15:21:42'),
(40, 'DISPLAY', 'Super AMOLED Touch Display', 'Active', '2026-11-20 15:21:42'),
(41, 'DISPLAY', 'Retina LCD Display Screen', 'Active', '2026-11-20 15:21:42'),
(42, 'Mobile', 'Brand New Smartphones', 'Active', '2026-11-20 15:21:42'),
(43, 'Mobile', 'Used / Refurbished Phones', 'Active', '2026-11-20 15:21:42'),
(44, 'Laptop', 'Business Laptops', 'Active', '2026-11-20 15:21:42'),
(45, 'Laptop', 'Gaming & Workstation', 'Active', '2026-11-20 15:21:42'),
(46, 'Accessories', 'Smart Watches & Bands', 'Active', '2026-11-20 15:21:42'),
(47, 'Accessories', 'Bluetooth Speakers', 'Active', '2026-11-20 15:21:42'),
(48, 'PHONE', 'PHONE', 'Active', '2026-11-20 18:56:48'),
(49, 'GLASS', 'GLASS', 'Active', '2026-11-22 14:23:19');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT 'BOORAMA',
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_user` varchar(100) DEFAULT 'N/A',
  `joined_at` date DEFAULT curdate(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `phone`, `address`, `balance`, `created_user`, `joined_at`, `notes`) VALUES
(13, 'Socdaal mobile accessories', '634706094', 'borama', 30.80, 'Admin', '2026-11-22', NULL),
(14, 'DUBIA', '0634000000', 'BOORAMA', 0.00, 'Admin', '2026-11-22', NULL),
(15, 'CAALAMI ELETRONIC', '633404186', 'BORAMA', 0.00, 'Admin', '2026-11-24', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `technicians`
--

CREATE TABLE `technicians` (
  `id` int(11) NOT NULL,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT '',
  `specialty` varchar(100) DEFAULT 'General Technician',
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `technicians`
--

INSERT INTO `technicians` (`id`, `name`, `phone`, `specialty`, `balance`, `status`, `created_at`) VALUES
(1, 'Ahmed Nur Farah', '064434', 'Mobile & Laptop Repair', 6.00, 'Active', '2026-11-24 16:49:45'),
(2, 'Abdirahman Daud', '634547497', 'General Technician', 13.00, 'Active', '2026-11-25 08:45:34');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'Admin',
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `name`, `role`, `branch`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$8rfGI.Y4jX/HOnMiJ1MPyORLHIwIbzWDAtOtYP3kHXW8uw1bQdZkq', 'Ridwaan Admin', 'Admin', 'Boorama Branch', 'Active', '2026-11-24 14:59:23'),
(2, 'ridwaan', '$2y$10$4DijPO4MpHBO33cp3vfffe8N7wfz7G4uTJ0UL29xFbQSBvj0ZYov.', 'Ridwaan', 'Admin', 'Boorama Branch', 'Active', '2026-11-24 14:59:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `daily_closings`
--
ALTER TABLE `daily_closings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_branch_date` (`closing_date`,`branch`);

--
-- Indexes for table `deliveries`
--
ALTER TABLE `deliveries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `measurements`
--
ALTER TABLE `measurements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indexes for table `product_exchanges`
--
ALTER TABLE `product_exchanges`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `exchange_number` (`exchange_number`);

--
-- Indexes for table `purchases`
--
ALTER TABLE `purchases`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_items`
--
ALTER TABLE `purchase_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `return_no` (`return_no`);

--
-- Indexes for table `quotations`
--
ALTER TABLE `quotations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `quotation_number` (`quotation_number`);

--
-- Indexes for table `repair_jobs`
--
ALTER TABLE `repair_jobs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_number` (`job_number`);

--
-- Indexes for table `repair_job_parts`
--
ALTER TABLE `repair_job_parts`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `repair_services`
--
ALTER TABLE `repair_services`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoice_number` (`invoice_number`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`);

--
-- Indexes for table `sub_categories`
--
ALTER TABLE `sub_categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `technicians`
--
ALTER TABLE `technicians`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `daily_closings`
--
ALTER TABLE `daily_closings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `deliveries`
--
ALTER TABLE `deliveries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `measurements`
--
ALTER TABLE `measurements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT for table `product_exchanges`
--
ALTER TABLE `product_exchanges`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `purchases`
--
ALTER TABLE `purchases`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `purchase_items`
--
ALTER TABLE `purchase_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1182;

--
-- AUTO_INCREMENT for table `purchase_returns`
--
ALTER TABLE `purchase_returns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `quotations`
--
ALTER TABLE `quotations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `repair_jobs`
--
ALTER TABLE `repair_jobs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `repair_job_parts`
--
ALTER TABLE `repair_job_parts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `repair_services`
--
ALTER TABLE `repair_services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `sub_categories`
--
ALTER TABLE `sub_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `technicians`
--
ALTER TABLE `technicians`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `payments`
--
ALTER TABLE `payments`
  ADD CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
