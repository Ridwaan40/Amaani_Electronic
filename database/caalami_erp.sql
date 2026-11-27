/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.16-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: caalami_erp
-- ------------------------------------------------------
-- Server version	10.11.16-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES
(1,'boorama','Boorama Branch'),
(2,'hargeisa','Hargeisa Main Branch'),
(3,'berbera','Berbera Port Branch'),
(4,'burco','Burco Central Branch');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES
(1,'PHONE PARTS','Active','2026-11-20 15:06:48'),
(2,'EARPHONE','Active','2026-11-20 15:06:48'),
(3,'POWER BANK','Active','2026-11-20 15:06:48'),
(4,'LIGHT','Active','2026-11-20 15:06:48'),
(5,'FIISH','Active','2026-11-20 15:06:48'),
(6,'CHARGER','Active','2026-11-20 15:06:48'),
(7,'PHONE COVER','Active','2026-11-20 15:06:48'),
(8,'GAME PARTS','Active','2026-11-20 15:06:48'),
(9,'GLASS','Active','2026-11-20 15:06:48'),
(10,'COOL BACK','Active','2026-11-20 15:06:48'),
(11,'BATTERY','Active','2026-11-20 15:06:48'),
(12,'CABLE','Active','2026-11-20 15:06:48'),
(13,'DISPLAY','Active','2026-11-20 15:06:48'),
(15,'xoosh','Active','2026-11-20 15:11:46'),
(16,'PHONE','Active','2026-11-20 18:56:27');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `balance` decimal(10,2) DEFAULT 0.00,
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `address` varchar(255) DEFAULT 'Boorama',
  `description` text DEFAULT NULL,
  `created_user` varchar(100) DEFAULT 'Admin',
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,'walk-in','Walk-In Customer','',20.00,'Boorama Branch','Boorama',NULL,'Admin','2026-11-22'),
(5,'cust-04','SAMATAR ELECTRONICS','+252 63 4771234',0.00,'Boorama Branch','Boorama',NULL,'Admin','2026-11-22'),
(6,'cust-05','GOLIS TELECOM','+252 63 4118899',0.00,'Boorama Branch','Boorama',NULL,'Admin','2026-11-22'),
(9,'MC-005','SALIS OF 19/2/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(12,'MC-008','SALIS OF 19/FEB/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(13,'MC-009','SALIS OF 20/FEB/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(14,'MC-010','SALIS OF 20/FEB/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(15,'MC-011','SALIS OF 20/FEB/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(16,'MC-012','SALIS OF 20/FEB/26','0634531454',0.00,'Boorama Branch','Boorama','-','Admin','2026-02-25'),
(19,'MC-015','DOOL DAAHIR','',0.00,'Boorama Branch','Boorama','-','Admin','2026-03-02'),
(20,'MC-020','UMALKHAYR IBRAHIM','634568734',17.00,'Boorama Branch','BORAMA','','Admin','2026-11-23'),
(21,'MC-021','Nuur aadan buraale','634500768',10.00,'Boorama Branch','BORAMA','','Ridwaan Admin','2026-11-25'),
(22,'MC-022','raadiya','0634458536',12.00,'Boorama Branch','BORAMA','','Ridwaan Admin','2026-11-26'),
(23,'MC-023','Hayaat Mohamed Cawaale','634555252',13.00,'Boorama Branch','BORAMA','','Ridwaan Admin','2026-11-26'),
(24,'MC-024','khayre muuse','0637108616',0.00,'Boorama Branch','BORAMA','','Ridwaan Admin','2026-11-26');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `daily_closings`
--

DROP TABLE IF EXISTS `daily_closings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `daily_closings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `closed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_branch_date` (`closing_date`,`branch`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `daily_closings`
--

LOCK TABLES `daily_closings` WRITE;
/*!40000 ALTER TABLE `daily_closings` DISABLE KEYS */;
/*!40000 ALTER TABLE `daily_closings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliveries`
--

DROP TABLE IF EXISTS `deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `customer` varchar(150) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `driver` varchar(100) DEFAULT NULL,
  `eta` varchar(50) DEFAULT NULL,
  `items` text DEFAULT NULL,
  `priority` varchar(20) DEFAULT 'Normal',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliveries`
--

LOCK TABLES `deliveries` WRITE;
/*!40000 ALTER TABLE `deliveries` DISABLE KEYS */;
INSERT INTO `deliveries` VALUES
(1,'DEL-401','Al-Baraka Hospital IT Dept','Borama West Medical District, Near Amoud','In Transit','Mukhtar Jama','35 mins','3x UPS APC Smart 1500VA + Batteries','High'),
(2,'DEL-402','Eng. Mustafe Ahmed','Haji Dahir St, Boorama Central','Dispatched','Farhan Ali','1 hour','Solar Inverter 5kW + Cable Pack','Normal'),
(3,'DEL-403','City Plaza Electronics','Commercial Market Road, Boorama','Delivered','Guled Hassan','Completed','50x Fast Charging Cables, 20x Adapters','Normal');
/*!40000 ALTER TABLE `deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `measurements`
--

DROP TABLE IF EXISTS `measurements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `measurements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `symbol` varchar(50) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `measurements`
--

LOCK TABLES `measurements` WRITE;
/*!40000 ALTER TABLE `measurements` DISABLE KEYS */;
INSERT INTO `measurements` VALUES
(1,'Pieces','Pcs','Active','2026-11-20 15:06:48'),
(2,'Box','Box','Active','2026-11-20 15:06:48'),
(3,'Set','Set','Active','2026-11-20 15:06:48');
/*!40000 ALTER TABLE `measurements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_user` varchar(100) DEFAULT 'Ridwaan',
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES
(14,NULL,'REC-351',6.50,'Zaad Services',NULL,NULL,'2026-11-27 15:30:52','khayre muuse','0637108616','Return Credits',6.50,0.00,0.00,'Boorama Branch','Ridwaan Admin'),
(15,11,'#11',3.00,'Cash On Hand','REF-617359','Initial payment at POS','2026-11-27 18:06:25',NULL,NULL,'Return Credits',0.00,0.00,0.00,'Boorama Branch','Ridwaan'),
(16,12,'#12',8.00,'Cash On Hand','REF-449845','Initial payment at POS','2026-11-27 18:06:40',NULL,NULL,'Return Credits',0.00,0.00,0.00,'Boorama Branch','Ridwaan');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_exchanges`
--

DROP TABLE IF EXISTS `product_exchanges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_exchanges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exchange_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `returned_item` varchar(255) DEFAULT NULL,
  `new_item` varchar(255) DEFAULT NULL,
  `price_diff` decimal(10,2) DEFAULT 0.00,
  `exchange_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `exchange_number` (`exchange_number`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_exchanges`
--

LOCK TABLES `product_exchanges` WRITE;
/*!40000 ALTER TABLE `product_exchanges` DISABLE KEYS */;
INSERT INTO `product_exchanges` VALUES
(1,'EXC-101','WARSAME ELECTRONIC','iPhone 13 (Screen issue)','iPhone 13 128GB (Replacement)',0.00,'2026-11-12','Warranty replacement approved by technician'),
(2,'EXC-102','COSOB ELECTRONIC','Samsung A15 64GB','Samsung A16 128GB',30.00,'2026-11-13','Customer upgraded, paid $30 difference');
/*!40000 ALTER TABLE `product_exchanges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES
(27,'SUPERX-398','SUPER X GLASS','GLASS','GLASS','GLASS','','',1.00,0.00,54,5,'📱','Pcs',0.00,0.00,0.00,'Socdaal mobile accessories','Ridwaan','2026-11-22','',NULL),
(28,'CABLEC-723','CABLE-C','CABLE','Type-C to Type-C 100W Cable','Type-C to Type-C 100W Cable','','',1.00,0.00,19,5,'📱','Pcs',0.00,0.00,0.00,'Socdaal mobile accessories','Ridwaan','2026-11-22','',NULL),
(29,'TECNOMO-188','TECNO MOBILE','PHONE','PHONE','PHONE','','',8.00,0.00,3,5,'📱','Pcs',7.00,0.00,0.00,'DUBIA','Admin','2026-11-22','2000020448205',''),
(30,'AIRPODS-337','AIRPODS MAX','EARPHONE','Airpods Pro / Max','Airpods Pro / Max','','',10.00,0.00,1,5,'📱','Pcs',9.00,0.00,0.00,'Socdaal mobile accessories','Admin','2026-11-23','2000057009868',''),
(31,'LCDOPPO-319','LCD OPPO A77S BLK ORG','PHONE PARTS','LCD Screens','LCD Screens','','',10.00,0.00,0,5,'📱','Pcs',7.00,0.00,0.00,'DUBIA','Admin','2026-11-23','2000018694613',''),
(32,'SAMSUNG-366','Samsung Cover','PHONE COVER','Clear MagSafe Case','Clear MagSafe Case','','',2.00,0.00,29,5,'📱','Pcs',1.50,0.00,0.00,'Socdaal mobile accessories','Admin','2026-11-23','2000028284892',''),
(33,'LCDIPH-237','LCD IPH 16 PRO MAX GX BLK ORG','PHONE PARTS','LCD Screens','LCD Screens','','',50.00,0.00,0,5,'📱','Pcs',42.00,0.00,0.00,'DUBIA','Admin','2026-11-24','2000054085063',''),
(34,'LCDIPH-148','LCD IPH 14 PRO MAX INC BLK ORG','PHONE PARTS','LCD Screens','LCD Screens','','',30.00,0.00,0,5,'📱','Pcs',15.00,0.00,0.00,'DUBIA','Admin','2026-11-24','2000033037417',''),
(35,'IPH14P-107','IPH 14 PRO MAX BACK COVER','PHONE PARTS','BACK GLASS','BACK GLASS','','',6.00,0.00,2,5,'📱','Pcs',2.50,0.00,0.00,'DUBIA','Admin','2026-11-24','2000080478823',''),
(36,'LCDIPH-267','LCD IPH 13 PRO MAX INC BLK ORG','PHONE PARTS','LCD Screens','LCD Screens','','',30.00,0.00,1,5,'📱','Pcs',13.00,0.00,0.00,'DUBIA','Admin','2026-11-24','2000084247688',''),
(37,'LCDA17-810','LCD A17 INCELL FRAME','PHONE PARTS','LCD Screens','LCD Screens','','',10.00,0.00,1,5,'📱','Pcs',7.50,0.00,0.00,'CAALAMI ELETRONIC','Admin','2026-11-24','2000074009639',''),
(38,'FLEXCAB-302','flex cable I.PH 13 pro max','PHONE PARTS','Charging Flex Cable','Charging Flex Cable','','',7.00,0.00,0,5,'📱','Pcs',4.50,0.00,0.00,'','Admin','2026-11-25','2000086751675',''),
(39,'LCDA042-523','LCD A042 (A04E)SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,0.00,2,5,'📱','Pcs',7.50,0.00,0.00,'DUBIA','Admin','2026-11-26','2000060456891',''),
(40,'LCDA11-188','LCD A11 SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,0.00,2,5,'📱','Pcs',7.50,0.00,0.00,'DUBIA','Admin','2026-11-26','2000067725785',''),
(41,'LCDA02S-427','LCD A02S SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,0.00,1,5,'📱','Pcs',7.50,0.00,0.00,'DUBIA','Admin','2026-11-26','2000024917392',''),
(42,'LCDINFI-591','LCD INFINIX HOT 50 / X670/ SMART 9 BLK ORG','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,0.00,1,5,'📱','Pcs',6.00,0.00,0.00,'DUBIA','Admin','2026-11-26','2000058409354',''),
(43,'LCDA047-616','LCD A047/A136 SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,7.50,1,5,'📱','Pcs',7.50,0.00,0.00,'DUBIA','Admin','2026-11-26','2000047010427',''),
(44,'LCDIPH-785','LCD IPH XSMAX GX ORG','PHONE PARTS','LCD Screens','LCD Screens','','',23.00,0.00,0,5,'📱','Pcs',21.50,0.00,0.00,'DUBIA','Admin','2026-11-26','2000020351970',''),
(45,'A13ONO-627','A13 ON/OFF SAM ORG','PHONE PARTS','POWER','POWER','','',2.00,0.00,1,5,'📱','Pcs',1.00,0.00,0.00,'DUBIA','Admin','2026-11-26','2000030718610',''),
(46,'LCDA16-121','LCD A16 INCELL SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',9.00,0.00,1,5,'📱','Pcs',7.00,0.00,0.00,'DUBIA','Admin','2026-11-26','2000081190773',''),
(47,'SAMSUNG-372','Samsung Charger 25w','CHARGER','20W PD Fast Charger','20W PD Fast Charger','','',3.00,0.00,5,5,'📱','Pcs',1.80,0.00,0.00,'DUBIA','Admin','2026-11-27','2000063333060',''),
(48,'MICROCH-107','micro charger','CHARGER','20W PD Fast Charger','20W PD Fast Charger','','',2.00,0.00,1,5,'📱','Pcs',1.50,0.00,0.00,'Socdaal mobile accessories','Admin','2026-11-27','2000099996714',''),
(49,'LCDA055-108','LCD A055 SAM BLK ORG 100%','PHONE PARTS','LCD Screens','LCD Screens','','',8.00,0.00,1,5,'📱','Pcs',7.00,0.00,0.00,'DUBIA','Admin','2026-11-27','2000041592838','');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_items`
--

DROP TABLE IF EXISTS `purchase_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `purchased_date` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1183 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_items`
--

LOCK TABLES `purchase_items` WRITE;
/*!40000 ALTER TABLE `purchase_items` DISABLE KEYS */;
INSERT INTO `purchase_items` VALUES
(1178,5,'Samsung Galaxy A20 4G','Mobile',1,'Pcs',280.00,0.00,280.00,0.00,280.00,1,'RIDWAAN HUSSEIN','Ridwaan','2026-11-20 22:57:11'),
(1179,6,'Samsung Galaxy A30 4G','Accessories',2,'Pcs',300.00,0.00,300.00,0.00,600.00,2,'RIDWAAN HUSSEIN','Ridwaan','2026-11-20 23:28:50'),
(1180,7,'SUPER X GLASS','GLASS',60,'Pcs',0.28,0.00,0.28,0.00,16.80,60,'Socdaal mobile accessories','Ridwaan','2026-11-22 17:24:49'),
(1181,8,'CABLE-C','Accessories',20,'Pcs',0.70,0.00,0.70,0.00,14.00,20,'Socdaal mobile accessories','Ridwaan','2026-11-22 17:26:35'),
(1182,9,'LCD A047/A136 SAM BLK ORG 100%','PHONE PARTS',1,'Pcs',7.50,0.00,7.50,8.00,7.50,1,'CAALAMI ELETRONIC','Ridwaan','2026-11-27 21:12:53');
/*!40000 ALTER TABLE `purchase_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchase_returns`
--

DROP TABLE IF EXISTS `purchase_returns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchase_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `return_no` (`return_no`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchase_returns`
--

LOCK TABLES `purchase_returns` WRITE;
/*!40000 ALTER TABLE `purchase_returns` DISABLE KEYS */;
INSERT INTO `purchase_returns` VALUES
(1,'PR-20260924-001',NULL,'2026-09-24','Test','Iphone 15',1,1.00,1.00,'Deduct Balance','Damaged screen upon arrival','A/rahman Ismail Ali','2026-11-19 18:52:16'),
(2,'PR-20261005-001',NULL,'2026-10-05','STORE INVENTORY','Iphone 17',1,50.00,50.00,'Deduct Balance','Defective return test','A/rahman Ismail Ali','2026-11-19 18:52:16');
/*!40000 ALTER TABLE `purchase_returns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchases`
--

DROP TABLE IF EXISTS `purchases`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchases`
--

LOCK TABLES `purchases` WRITE;
/*!40000 ALTER TABLE `purchases` DISABLE KEYS */;
INSERT INTO `purchases` VALUES
(5,'#2983','RIDWAAN HUSSEIN','634578264','Boorama Branch','2026-11-20','2026-11-20','Credit',280.00,0.00,280.00,0.00,0.00,280.00,'ZAAD','Ridwaan',NULL,'2026-11-20 19:57:11'),
(6,'#4291','RIDWAAN HUSSEIN','634578264','Boorama Branch','2026-11-20','2026-11-20','Credit',600.00,0.00,600.00,0.00,0.00,600.00,'ZAAD','Ridwaan',NULL,'2026-11-20 20:28:50'),
(7,'#2665','Socdaal mobile accessories','634706094','Boorama Branch','2026-11-22','2026-11-22','Credit',16.80,0.00,16.80,0.00,0.00,16.80,'ZAAD','Ridwaan',NULL,'2026-11-22 14:24:49'),
(8,'#4677','Socdaal mobile accessories','634706094','Boorama Branch','2026-11-22','2026-11-22','Credit',14.00,0.00,14.00,0.00,0.00,14.00,'ZAAD','Ridwaan',NULL,'2026-11-22 14:26:35'),
(9,'#8883','CAALAMI ELETRONIC','633404186','Boorama Branch','2026-11-27','2026-11-27','Credit',7.50,0.00,7.50,0.00,0.00,7.50,'ZAAD','Ridwaan',NULL,'2026-11-27 18:12:53');
/*!40000 ALTER TABLE `purchases` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `quotation_number` varchar(50) DEFAULT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `branch` varchar(100) DEFAULT NULL,
  `quote_date` date DEFAULT NULL,
  `valid_until` date DEFAULT NULL,
  `total_amount` decimal(10,2) DEFAULT 0.00,
  `items_summary` text DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  PRIMARY KEY (`id`),
  UNIQUE KEY `quotation_number` (`quotation_number`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
INSERT INTO `quotations` VALUES
(1,'QT-201','Amoud University Computer Lab','Boorama Branch','2026-11-10','2026-11-24',4500.00,'10x HP ProDesk Desktops, 10x Monitors, 1x Switch 24-Port','Pending'),
(2,'QT-202','Telesom Boorama Regional Office','Boorama Branch','2026-11-12','2026-11-26',1250.00,'2x Starlink V4 Kits + Mounting Poles & PoE kits','Pending'),
(3,'QT-203','Borama General Hospital Pharmacy','Boorama Branch','2026-11-14','2026-11-28',850.00,'3x Barcode Scanners, 2x Thermal Receipt Printers, POS Drawer','Pending');
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_job_parts`
--

DROP TABLE IF EXISTS `repair_job_parts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_job_parts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `repair_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `part_name` varchar(200) NOT NULL,
  `qty` int(11) DEFAULT 1,
  `price` decimal(10,2) DEFAULT 0.00,
  `total` decimal(10,2) DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_job_parts`
--

LOCK TABLES `repair_job_parts` WRITE;
/*!40000 ALTER TABLE `repair_job_parts` DISABLE KEYS */;
INSERT INTO `repair_job_parts` VALUES
(3,4,34,'LCD IPH 14 PRO MAX INC BLK ORG',1,30.00,30.00),
(4,4,35,'IPH 14 PRO MAX BACK COVER',1,6.00,6.00),
(5,5,33,'LCD IPH 16 PRO MAX GX BLK ORG',1,50.00,50.00),
(6,5,27,'SUPER X GLASS',1,1.00,1.00),
(9,6,38,'flex cable I.PH 13 pro max',1,7.00,7.00),
(10,6,36,'LCD IPH 13 PRO MAX INC BLK ORG',1,30.00,30.00),
(11,7,43,'LCD A047/A136 SAM BLK ORG 100%',1,9.00,9.00),
(12,7,27,'SUPER X GLASS',1,1.00,1.00),
(13,8,43,'LCD A047/A136 SAM BLK ORG 100%',1,8.00,8.00),
(14,8,27,'SUPER X GLASS',1,1.00,1.00),
(15,9,44,'LCD IPH XSMAX GX ORG',1,23.00,23.00),
(16,10,43,'LCD A047/A136 SAM BLK ORG 100%',1,8.00,8.00),
(17,10,27,'SUPER X GLASS',1,1.00,1.00);
/*!40000 ALTER TABLE `repair_job_parts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_jobs`
--

DROP TABLE IF EXISTS `repair_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_jobs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `job_number` (`job_number`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_jobs`
--

LOCK TABLES `repair_jobs` WRITE;
/*!40000 ALTER TABLE `repair_jobs` DISABLE KEYS */;
INSERT INTO `repair_jobs` VALUES
(4,'REP-20261125-4039',1,'Walk-In Customer','','IPHONE 14 PRO MAX','','','','waxa laga badalayaa screen ka iyo back glass oo jabay','',1,'Ahmed Nur Farah',6.00,NULL,'back glass replacement + screening',20.00,36.00,0.00,56.00,56.00,'Delivered','Cash','2026-11-25','2026-11-26','Boorama Branch','Ridwaan Admin','2026-11-25 08:32:03'),
(5,'REP-20261125-8630',1,'Walk-In Customer','','IPHONE 16 PRO MAX','','','','waxa kasoo jabay display oo laga badalay','',2,'Abdirahman Daud',6.00,NULL,'screening',15.00,51.00,0.00,66.00,66.00,'Delivered','Cash','2026-11-25','2026-11-26','Boorama Branch','Ridwaan Admin','2026-11-25 08:57:44'),
(6,'REP-20261125-2851',21,'Nuur aadan buraale','634500768','IPHONE 13 PRO MAX','','','','waxa laga badalayay screen iyo speaker ka','',2,'Abdirahman Daud',5.00,NULL,'Charger Repair + screening',13.00,37.00,0.00,50.00,40.00,'Delivered','Zaad Services','2026-11-25','2026-11-26','Boorama Branch','Ridwaan Admin','2026-11-25 19:18:44'),
(7,'REP-20261126-6059',22,'raadiya','0634458536','A13','','','','display laga badalaayay','',2,'Abdirahman Daud',2.00,NULL,'screening',2.00,10.00,0.00,12.00,0.00,'Delivered','Cash','2026-11-26','2026-11-27','Boorama Branch','Ridwaan Admin','2026-11-26 19:01:28'),
(8,'REP-20261126-9003',23,'Hayaat Mohamed Cawaale','634555252','A13','','','','display change','',2,'Abdirahman Daud',2.00,NULL,'screening',4.00,9.00,0.00,13.00,0.00,'Delivered','Cash','2026-11-26','2026-11-27','Boorama Branch','Ridwaan Admin','2026-11-26 19:56:39'),
(9,'REP-20261126-9844',24,'khayre muuse','0637108616','xs max','','','','display change','',2,'Abdirahman Daud',2.00,NULL,'screening',5.00,23.00,0.00,28.00,28.00,'Delivered','Cash','2026-11-26','2026-11-27','Boorama Branch','Ridwaan Admin','2026-11-26 20:03:30'),
(10,'REP-20261127-4862',1,'Walk-In Customer','','A13','','','','display change','',2,'Abdirahman Daud',2.00,NULL,'screening',4.00,9.00,0.00,13.00,13.00,'Delivered','Cash','2026-11-27','2026-11-28','Boorama Branch','Ridwaan Admin','2026-11-27 18:38:18');
/*!40000 ALTER TABLE `repair_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `repair_services`
--

DROP TABLE IF EXISTS `repair_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `repair_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `labor_cost` decimal(10,2) DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `repair_services`
--

LOCK TABLES `repair_services` WRITE;
/*!40000 ALTER TABLE `repair_services` DISABLE KEYS */;
INSERT INTO `repair_services` VALUES
(1,'screening',10.00,'Screen diagnostic and replacement service','2026-11-24 16:49:45'),
(2,'Charger Repair',3.00,'','2026-11-24 17:29:54'),
(3,'back glass replacement',7.00,'','2026-11-25 08:28:43');
/*!40000 ALTER TABLE `repair_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sale_items`
--

DROP TABLE IF EXISTS `sale_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sale_items`
--

LOCK TABLES `sale_items` WRITE;
/*!40000 ALTER TABLE `sale_items` DISABLE KEYS */;
INSERT INTO `sale_items` VALUES
(1,1,NULL,'SUPER X GLASS',1,1.00,1.00,'Pcs',0.00,0.00,0.00,0.00,0.00,'Retail','Boorama Branch','#1','Abdiwasac',NULL),
(3,3,NULL,'TECNO MOBILE',1,8.00,8.00,'Pcs',0.00,0.00,0.00,0.00,0.00,'Retail','Boorama Branch','#1','Abdiwasac',NULL),
(5,5,28,'CABLE-C',1,1.00,1.00,'Pcs',1.00,0.00,0.00,0.00,0.70,'Retail','Boorama Branch','#4','Ridwaan','2026-11-22'),
(6,6,30,'AIRPODS MAX',1,13.00,13.00,'Pcs',13.00,0.00,0.00,0.00,10.00,'Retail','Boorama Branch','#6','Ridwaan','2026-11-23'),
(7,7,31,'LCD OPPO A77S BLK ORG',1,13.00,13.00,'Pcs',13.00,0.00,0.00,0.00,10.00,'Retail','Boorama Branch','#7','Ridwaan','2026-11-23'),
(8,7,27,'SUPER X GLASS',1,1.00,1.00,'Pcs',1.00,0.00,0.00,0.00,1.00,'Retail','Boorama Branch','#7','Ridwaan','2026-11-23'),
(9,8,32,'Samsung Cover',1,2.00,2.00,'Pcs',2.00,0.00,0.00,0.00,2.00,'Retail','Boorama Branch','#8','Ridwaan','2026-11-23'),
(10,8,27,'SUPER X GLASS',1,1.00,1.00,'Pcs',1.00,0.00,0.00,0.00,1.00,'Retail','Boorama Branch','#8','Ridwaan','2026-11-23'),
(11,9,37,'LCD A17 INCELL FRAME',1,10.00,10.00,'Pcs',10.00,0.00,0.00,0.00,10.00,'Returned','Boorama Branch','#9','Ridwaan Admin','2026-11-26'),
(12,10,37,'LCD A17 INCELL FRAME',1,10.00,10.00,'Pcs',10.00,0.00,0.00,0.00,10.00,'Returned','Boorama Branch','#10','Ridwaan Admin','2026-11-27'),
(13,11,47,'Samsung Charger 25w',1,3.00,3.00,'Pcs',3.00,0.00,0.00,0.00,3.00,'Retail','Boorama Branch','#11','Ridwaan Admin','2026-11-27'),
(14,12,29,'TECNO MOBILE',1,8.00,8.00,'Pcs',8.00,0.00,0.00,0.00,8.00,'Retail','Boorama Branch','#12','Ridwaan Admin','2026-11-27');
/*!40000 ALTER TABLE `sale_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
INSERT INTO `sales` VALUES
(1,'#797','WARSAME ELECTRONIC','+252 63 4001923','Boorama Branch','2026-11-22','2026-12-06','2026','November',1.00,0.00,1.00,1.00,'Cash On Hand','','1x SUPER X GLASS','Paid','2026-11-22 14:51:40'),
(3,'#755','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-22','2026-11-29','2026','November',8.00,0.00,8.00,8.00,'Cash On Hand','','1x TECNO MOBILE','Paid','2026-11-22 15:49:32'),
(5,'#4','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-22','2026-11-29','2026','November',1.00,0.00,1.00,1.00,'Cash On Hand','','1x CABLE-C','Paid','2026-11-22 17:28:43'),
(6,'#6','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-23','2026-11-30','2026','November',13.00,0.00,13.00,13.00,'Cash On Hand','','1x AIRPODS MAX','Paid','2026-11-23 15:37:02'),
(7,'#7','UMALKHAYR IBRAHIM','634568734','Boorama Branch','2026-11-23','2026-12-05','2026','November',14.00,0.00,14.00,0.00,'Cash On Hand','','1x LCD OPPO A77S BLK ORG, 1x SUPER X GLASS','Due','2026-11-23 16:19:56'),
(8,'#8','UMALKHAYR IBRAHIM','634568734','Boorama Branch','2026-11-23','2026-11-30','2026','November',3.00,0.00,3.00,0.00,'Cash On Hand','','1x Samsung Cover, 1x SUPER X GLASS','Due','2026-11-23 18:20:28'),
(9,'#9','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-26','2026-11-29','2026','November',10.00,0.00,0.00,0.00,'Cash On Hand','','1x LCD A17 INCELL FRAME','Due','2026-11-26 19:14:29'),
(10,'#10','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-27','2026-12-04','2026','November',10.00,0.00,0.00,0.00,'Cash On Hand','','1x LCD A17 INCELL FRAME','Due','2026-11-27 16:30:04'),
(11,'#11','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-27','2026-12-04','2026','November',3.00,0.00,3.00,3.00,'Cash On Hand','','1x Samsung Charger 25w','Paid','2026-11-27 18:06:25'),
(12,'#12','Walk-In Customer','+252 63 4001923','Boorama Branch','2026-11-27','2026-12-04','2026','November',8.00,0.00,8.00,8.00,'Cash On Hand','','1x TECNO MOBILE','Paid','2026-11-27 18:06:40');
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_categories`
--

DROP TABLE IF EXISTS `sub_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sub_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) NOT NULL,
  `name` varchar(150) NOT NULL,
  `status` varchar(50) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_categories`
--

LOCK TABLES `sub_categories` WRITE;
/*!40000 ALTER TABLE `sub_categories` DISABLE KEYS */;
INSERT INTO `sub_categories` VALUES
(1,'PHONE PARTS','LCD Screens','Active','2026-11-20 15:06:48'),
(2,'PHONE PARTS','Charging Flex Cable','Active','2026-11-20 15:06:48'),
(3,'PHONE PARTS','Camera Module','Active','2026-11-20 15:06:48'),
(4,'PHONE PARTS','Original Battery Pack','Active','2026-11-20 15:06:48'),
(5,'EARPHONE','Bluetooth Wireless Earbuds','Active','2026-11-20 15:06:48'),
(6,'EARPHONE','Wired 3.5mm Earphone','Active','2026-11-20 15:06:48'),
(7,'EARPHONE','Airpods Pro / Max','Active','2026-11-20 15:06:48'),
(8,'EARPHONE','Gaming Headset','Active','2026-11-20 15:06:48'),
(9,'POWER BANK','10,000mAh Slim Pocket','Active','2026-11-20 15:06:48'),
(10,'POWER BANK','20,000mAh Fast Charge','Active','2026-11-20 15:06:48'),
(11,'POWER BANK','30,000mAh Heavy Duty','Active','2026-11-20 15:06:48'),
(12,'POWER BANK','MagSafe Wireless PowerBank','Active','2026-11-20 15:06:48'),
(13,'LIGHT','Ring Light 12 inch','Active','2026-11-20 15:06:48'),
(14,'LIGHT','RGB Desk Atmosphere Light','Active','2026-11-20 15:06:48'),
(15,'LIGHT','Studio Softbox Lamp','Active','2026-11-20 15:06:48'),
(16,'FIISH','Multi-Socket Extension 3M','Active','2026-11-20 15:06:48'),
(17,'FIISH','Universal Travel Plug','Active','2026-11-20 15:06:48'),
(18,'FIISH','Surge Protector 5-Way','Active','2026-11-20 15:06:48'),
(19,'CHARGER','20W PD Fast Charger','Active','2026-11-20 15:06:48'),
(20,'CHARGER','65W GaN Laptop Charger','Active','2026-11-20 15:06:48'),
(21,'CHARGER','Car Fast Charger Adapter','Active','2026-11-20 15:06:48'),
(22,'PHONE COVER','Silicone Soft Case','Active','2026-11-20 15:06:48'),
(23,'PHONE COVER','Shockproof Armor Case','Active','2026-11-20 15:06:48'),
(24,'PHONE COVER','Clear MagSafe Case','Active','2026-11-20 15:06:48'),
(25,'GAME PARTS','PS5 Wireless Controller','Active','2026-11-20 15:06:48'),
(26,'GAME PARTS','Console Cooling Fan Stand','Active','2026-11-20 15:06:48'),
(27,'GAME PARTS','Thumbstick Grips Pack','Active','2026-11-20 15:06:48'),
(28,'GLASS','9D Privacy Glass Screen','Active','2026-11-20 15:06:48'),
(29,'GLASS','Ultra Clear Tempered Glass','Active','2026-11-20 15:06:48'),
(30,'GLASS','Camera Lens Protector','Active','2026-11-20 15:06:48'),
(31,'COOL BACK','Magnetic Phone Cooler Fan','Active','2026-11-20 15:06:48'),
(32,'COOL BACK','Dual Fan Radiator','Active','2026-11-20 15:06:48'),
(33,'xoosh','ali','Active','2026-11-20 15:12:18'),
(34,'BATTERY','iPhone Replacement Battery','Active','2026-11-20 15:21:42'),
(35,'BATTERY','Samsung Replacement Battery','Active','2026-11-20 15:21:42'),
(36,'BATTERY','Laptop Li-ion Battery','Active','2026-11-20 15:21:42'),
(37,'CABLE','Type-C to Type-C 100W Cable','Active','2026-11-20 15:21:42'),
(38,'CABLE','Lightning to Type-C Fast Cable','Active','2026-11-20 15:21:42'),
(39,'CABLE','HDMI 4K Ultra High Speed Cable','Active','2026-11-20 15:21:42'),
(40,'DISPLAY','Super AMOLED Touch Display','Active','2026-11-20 15:21:42'),
(41,'DISPLAY','Retina LCD Display Screen','Active','2026-11-20 15:21:42'),
(42,'Mobile','Brand New Smartphones','Active','2026-11-20 15:21:42'),
(43,'Mobile','Used / Refurbished Phones','Active','2026-11-20 15:21:42'),
(44,'Laptop','Business Laptops','Active','2026-11-20 15:21:42'),
(45,'Laptop','Gaming & Workstation','Active','2026-11-20 15:21:42'),
(46,'Accessories','Smart Watches & Bands','Active','2026-11-20 15:21:42'),
(47,'Accessories','Bluetooth Speakers','Active','2026-11-20 15:21:42'),
(48,'PHONE','PHONE','Active','2026-11-20 18:56:48'),
(49,'GLASS','GLASS','Active','2026-11-22 14:23:19');
/*!40000 ALTER TABLE `sub_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `address` varchar(255) DEFAULT 'BOORAMA',
  `balance` decimal(10,2) DEFAULT 0.00,
  `created_user` varchar(100) DEFAULT 'N/A',
  `joined_at` date DEFAULT curdate(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES
(13,'Socdaal mobile accessories','634706094','borama',30.80,'Admin','2026-11-22',NULL),
(14,'DUBIA','0634000000','BOORAMA',0.00,'Admin','2026-11-22',NULL),
(15,'CAALAMI ELETRONIC','633404186','BORAMA',7.50,'Admin','2026-11-24',NULL);
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `technicians`
--

DROP TABLE IF EXISTS `technicians`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `technicians` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `phone` varchar(50) DEFAULT '',
  `specialty` varchar(100) DEFAULT 'General Technician',
  `balance` decimal(10,2) DEFAULT 0.00,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `technicians`
--

LOCK TABLES `technicians` WRITE;
/*!40000 ALTER TABLE `technicians` DISABLE KEYS */;
INSERT INTO `technicians` VALUES
(1,'Ahmed Nur Farah','064434','Mobile & Laptop Repair',6.00,'Active','2026-11-24 16:49:45'),
(2,'Abdirahman Daud','634547497','General Technician',19.00,'Active','2026-11-25 08:45:34');
/*!40000 ALTER TABLE `technicians` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) DEFAULT 'Admin',
  `branch` varchar(100) DEFAULT 'Boorama Branch',
  `status` varchar(20) DEFAULT 'Active',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'admin','$2y$10$8rfGI.Y4jX/HOnMiJ1MPyORLHIwIbzWDAtOtYP3kHXW8uw1bQdZkq','Ridwaan Admin','Admin','Boorama Branch','Active','2026-11-24 14:59:23'),
(2,'ridwaan','$2y$10$4DijPO4MpHBO33cp3vfffe8N7wfz7G4uTJ0UL29xFbQSBvj0ZYov.','Ridwaan','Admin','Boorama Branch','Active','2026-11-24 14:59:23');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'caalami_erp'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-11-27 22:18:50
