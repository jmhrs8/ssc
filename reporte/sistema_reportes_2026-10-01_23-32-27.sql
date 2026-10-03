-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: sistema_reportes
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `sistema_reportes`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `sistema_reportes` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `sistema_reportes`;

--
-- Table structure for table `reportes`
--

DROP TABLE IF EXISTS `reportes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reportes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `n_inventario` varchar(100) DEFAULT NULL,
  `n_placa` varchar(100) DEFAULT NULL,
  `n_tipo` varchar(100) DEFAULT NULL,
  `fotos` text,
  `fecha` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `registrado_por` varchar(50) DEFAULT 'Admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reportes`
--

LOCK TABLES `reportes` WRITE;
/*!40000 ALTER TABLE `reportes` DISABLE KEYS */;
INSERT INTO `reportes` VALUES (21,'S/I','K78BSE','SEDAN','[\"1773435698_5_WhatsApp Image 2026-03-13 at 2.13.05 PM (5).jpeg\",\"1773435698_0_WhatsApp Image 2026-03-13 at 2.13.03 PM.jpeg\",\"1773435698_6_WhatsApp Image 2026-03-13 at 2.13.05 PM.jpeg\",\"1773435698_2_WhatsApp Image 2026-03-13 at 2.13.05 PM (2).jpeg\",\"1773435698_3_WhatsApp Image 2026-03-13 at 2.13.05 PM (3).jpeg\",\"1773435698_4_WhatsApp Image 2026-03-13 at 2.13.05 PM (4).jpeg\",\"1773435698_1_WhatsApp Image 2026-03-13 at 2.13.05 PM (1).jpeg\"]','2026-03-13 21:01:38',NULL),(23,'V1232','U37BSA','SEDAN','[\"1773436492_2_WhatsApp Image 2026-03-13 at 3.05.17 PM (2).jpeg\",\"1773436492_3_WhatsApp Image 2026-03-13 at 3.05.17 PM (3).jpeg\",\"1773436492_4_WhatsApp Image 2026-03-13 at 3.05.17 PM.jpeg\",\"1773436492_6_WhatsApp Image 2026-03-13 at 3.05.18 PM.jpeg\",\"1773436492_0_WhatsApp Image 2026-03-13 at 3.05.16 PM.jpeg\",\"1773436492_1_WhatsApp Image 2026-03-13 at 3.05.17 PM (1).jpeg\",\"1773436492_5_WhatsApp Image 2026-03-13 at 3.05.18 PM (1).jpeg\"]','2026-03-13 21:14:52',NULL),(24,'S/I','R03BSA','SEDAN','[\"1773436665_4_WhatsApp Image 2026-03-13 at 3.06.19 PM (1).jpeg\",\"1773436665_0_WhatsApp Image 2026-03-13 at 3.06.14 PM.jpeg\",\"1773436665_1_WhatsApp Image 2026-03-13 at 3.06.18 PM (1).jpeg\",\"1773436665_2_WhatsApp Image 2026-03-13 at 3.06.18 PM (2).jpeg\",\"1773436665_3_WhatsApp Image 2026-03-13 at 3.06.18 PM.jpeg\",\"1773436665_5_WhatsApp Image 2026-03-13 at 3.06.19 PM.jpeg\"]','2026-03-13 21:17:45',NULL);
/*!40000 ALTER TABLE `reportes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_unidad`
--

DROP TABLE IF EXISTS `tipos_unidad`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_unidad` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_unidad`
--

LOCK TABLES `tipos_unidad` WRITE;
/*!40000 ALTER TABLE `tipos_unidad` DISABLE KEYS */;
INSERT INTO `tipos_unidad` VALUES (15,'BICICLETAS'),(14,'BIENES FIJOS'),(11,'CAMIONES'),(10,'CAMIONETA'),(12,'EQUIPO ESPECIAL'),(8,'GRUAS'),(13,'MAQUINARIA Y/O EQUIPO PESADO'),(5,'MOTO'),(9,'MOTOCICLETA Y CUATRIMOTO'),(6,'PICKUP'),(2,'SEDAN'),(1,'VAN');
/*!40000 ALTER TABLE `tipos_unidad` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tipos_unidades`
--

DROP TABLE IF EXISTS `tipos_unidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_unidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tipos_unidades`
--

LOCK TABLES `tipos_unidades` WRITE;
/*!40000 ALTER TABLE `tipos_unidades` DISABLE KEYS */;
INSERT INTO `tipos_unidades` VALUES (2,'camioneta'),(3,'motocicleta'),(1,'patrulla'),(6,'torton'),(5,'van');
/*!40000 ALTER TABLE `tipos_unidades` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `nombre_completo` varchar(255) DEFAULT NULL,
  `rol` varchar(20) DEFAULT 'admin',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'admin','admin123',NULL,'admin'),(2,'operador1','clave123','EMILIANO','usuario'),(3,'operador2','clave123','FANY','usuario'),(6,'admin2','clave123','juan manuel hernandez','admin');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'sistema_reportes'
--

--
-- Dumping routines for database 'sistema_reportes'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 23:32:32
