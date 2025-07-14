-- MySQL dump 10.13  Distrib 8.0.38, for Win64 (x86_64)
--
-- Host: localhost    Database: bmove_db
-- ------------------------------------------------------
-- Server version	8.0.39

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `bookings`
--

DROP TABLE IF EXISTS `bookings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bookings` (
  `booking_id` char(36) NOT NULL,
  `user_id` char(36) DEFAULT NULL,
  `vehicle_id` char(36) DEFAULT NULL,
  `pickup_location` varchar(255) DEFAULT NULL,
  `pickup_lat` decimal(10,7) DEFAULT NULL,
  `pickup_lng` decimal(10,7) DEFAULT NULL,
  `dropoff_location` varchar(255) DEFAULT NULL,
  `dropoff_lat` decimal(10,7) DEFAULT NULL,
  `dropoff_lng` decimal(10,7) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `total_distance` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) DEFAULT NULL,
  `total_weight` int DEFAULT NULL,
  `items_count` int DEFAULT NULL,
  `status` enum('pending','confirmed','in_progress','completed','cancelled') DEFAULT 'pending',
  `payment_method` enum('cash','gcash','maya') DEFAULT 'cash',
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`booking_id`),
  KEY `vehicle_id` (`vehicle_id`),
  CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`vehicleid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `bookings`
--

LOCK TABLES `bookings` WRITE;
/*!40000 ALTER TABLE `bookings` DISABLE KEYS */;
INSERT INTO `bookings` VALUES ('682a0390-a55c-4004-8621-79583ee34944','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','GRHQ+JC Koronadal City, South Cotabato, Philippines',6.5290387,124.8385367,'Davao City, Davao del Sur, Philippines',7.0736114,125.6110248,'2025-05-18','19:44:00',148.82,5949.59,16,813,'completed','cash','Velit dolore et exer','2025-05-18 15:58:08'),('682a456a-0d6d-4008-a723-46441714d024','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','GRHQ+JC Koronadal City, South Cotabato, Philippines',6.5290387,124.8385367,'Davao City, Davao del Sur, Philippines',7.0736114,125.6110248,'2025-05-20','08:38:00',148.82,299.64,66,100,'pending','cash','wala','2025-05-18 20:39:06'),('682a45c9-bb42-4008-b555-0eb62bee668f','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','Koronadal City, South Cotabato, Philippines',6.4973963,124.8471625,'Bukidnon, Philippines',8.0515054,124.9229946,'2025-05-20','08:40:00',272.21,546.42,60,100,'pending','cash','basta','2025-05-18 20:40:41'),('682a46b6-b0ac-4002-b3ef-557b89835253','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','Koronadal City, South Cotabato, Philippines',6.4973963,124.8471625,'Bukidnon, Philippines',8.0515054,124.9229946,'2025-05-19','09:43:00',272.21,546.42,66,100,'pending','cash','das','2025-05-18 20:44:38'),('682a46de-c688-400c-aeb8-ee3289b86a6d','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','Koronadal City, South Cotabato, Philippines',6.4973963,124.8471625,'Bukidnon, Philippines',8.0515054,124.9229946,'2025-05-21','09:43:00',272.21,546.42,66,100,'pending','cash','das','2025-05-18 20:45:18'),('682a4723-4015-4003-a745-78b8a4449786','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','Koronadal City, South Cotabato, Philippines',6.4973963,124.8471625,'Bukidnon, Philippines',8.0515054,124.9229946,'2025-05-22','09:43:00',272.21,546.42,66,100,'pending','cash','das','2025-05-18 20:46:27'),('682a4741-46a9-4001-8fb8-4b3f34a930bc','6829c37e-be4f-4008-a2c4-5ed923e0fd1a','6829cb76-722e-4003-9c83-ab7fc0320a47','Koronadal City, South Cotabato, Philippines',6.4973963,124.8471625,'Bukidnon, Philippines',8.0515054,124.9229946,'2025-05-23','09:43:00',272.21,546.42,66,100,'pending','cash','das','2025-05-18 20:46:57');
/*!40000 ALTER TABLE `bookings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `payment_id` char(36) NOT NULL,
  `booking_id` char(36) NOT NULL,
  `user_id` char(36) NOT NULL,
  `amount_due` decimal(10,2) NOT NULL,
  `amount_received` decimal(10,2) DEFAULT '0.00',
  `change_amount` decimal(10,2) GENERATED ALWAYS AS ((case when (`amount_received` > `amount_due`) then (`amount_received` - `amount_due`) else 0.00 end)) STORED,
  `payment_method` enum('cash','gcash','maya','bank_transfer') NOT NULL,
  `payment_status` enum('pending','paid','partial','failed','refunded','cancelled') DEFAULT 'pending',
  `gateway_reference` varchar(100) DEFAULT NULL,
  `gateway_url` varchar(255) DEFAULT NULL,
  `receipt_number` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `notes` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` char(36) DEFAULT NULL,
  `updated_by` char(36) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `booking_id` (`booking_id`),
  KEY `user_id` (`user_id`),
  KEY `created_by` (`created_by`),
  KEY `updated_by` (`updated_by`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `payments_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `payments_ibfk_4` FOREIGN KEY (`updated_by`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` (`payment_id`, `booking_id`, `user_id`, `amount_due`, `amount_received`, `payment_method`, `payment_status`, `gateway_reference`, `gateway_url`, `receipt_number`, `paid_at`, `notes`, `created_at`, `updated_at`, `created_by`, `updated_by`) VALUES ('682a46b6-c779-4002-838a-52315d5252e0','682a46b6-b0ac-4002-b3ef-557b89835253','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',546.42,546.42,'cash','pending',NULL,NULL,NULL,NULL,NULL,'2025-05-18 20:44:38','2025-05-18 20:44:38','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',NULL),('682a46de-e21f-400c-909c-664e0422cf80','682a46de-c688-400c-aeb8-ee3289b86a6d','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',546.42,546.42,'cash','pending',NULL,NULL,NULL,NULL,NULL,'2025-05-18 20:45:18','2025-05-18 20:45:18','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',NULL),('682a4741-6683-4001-a51e-ae0866f80dc8','682a4741-46a9-4001-8fb8-4b3f34a930bc','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',546.42,0.00,'cash','pending',NULL,NULL,NULL,NULL,NULL,'2025-05-18 20:46:57','2025-05-18 20:46:57','6829c37e-be4f-4008-a2c4-5ed923e0fd1a',NULL),('682a53cf-2ff9-4004-b9d3-0fad8ef91ab3','682a0390-a55c-4004-8621-79583ee34944','6829cee2-0f77-4002-a93a-ec1828b210c2',5949.59,10000.00,'cash','paid',NULL,NULL,NULL,'2025-05-18 15:40:31','hahah','2025-05-18 21:40:31','2025-05-18 21:40:31','6829cee2-0f77-4002-a93a-ec1828b210c2','6829cee2-0f77-4002-a93a-ec1828b210c2');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `uid` char(36) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `contact_number` varchar(11) NOT NULL,
  `email_address` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `account_type` enum('admin','customer','driver') NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email_address` (`email_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES ('6829c37e-be4f-4008-a2c4-5ed923e0fd1a','byjynibum','Elaine Gutierrez','09559786093','kimatong874@gmail.com','$2y$10$XfL3VEaPZ48Sj/zhHkrTb.0J8Vin8aq1bkIbOfIxi4u5wcbtOgoe2','customer','2025-05-18 11:24:46'),('6829cee2-0f77-4002-a93a-ec1828b210c2','qokofihobi','Wayne Estes','0955978604','juhiridina@mailinator.com','$2y$10$XfL3VEaPZ48Sj/zhHkrTb.0J8Vin8aq1bkIbOfIxi4u5wcbtOgoe2','driver','2025-05-18 12:13:22'),('6829cefc-4778-4000-966c-f87484e0b3c3','mykok','Hu Todd','09559786012','vyjahajy@mailinator.com','$2y$10$XfL3VEaPZ48Sj/zhHkrTb.0J8Vin8aq1bkIbOfIxi4u5wcbtOgoe2','admin','2025-05-18 12:13:48');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `vehicleid` char(36) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `platenumber` varchar(20) DEFAULT NULL,
  `totalcapacitykg` int DEFAULT NULL,
  `status` enum('available','in use','under maintenance','unavailable') DEFAULT 'available',
  `baseprice` decimal(10,2) DEFAULT NULL,
  `rateperkm` decimal(10,2) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `model` varchar(50) DEFAULT NULL,
  `year` year DEFAULT NULL,
  `date_added` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `driver_uid` char(36) DEFAULT NULL,
  PRIMARY KEY (`vehicleid`),
  UNIQUE KEY `platenumber` (`platenumber`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
INSERT INTO `vehicles` VALUES ('6829cb76-722e-4003-9c83-ab7fc0320a47','Magee Rowland','113',99,'available',2.00,2.00,'Car','Quis ea odio ut temp',1978,'2025-05-18 11:58:46','6829cee2-0f77-4002-a93a-ec1828b210c2');
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-05-19  6:14:52
