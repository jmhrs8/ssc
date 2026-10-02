-- MySQL dump 10.13  Distrib 8.0.46, for Linux (x86_64)
--
-- Host: localhost    Database: erp_inventory
-- ------------------------------------------------------
-- Server version	8.0.46-0ubuntu0.24.04.4

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
-- Table structure for table `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_empresa` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'assets/img/default-logo.png',
  `email_notificaciones` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `smtp_host` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_port` int DEFAULT NULL,
  `smtp_user` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `smtp_pass` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bg_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configuracion`
--

LOCK TABLES `configuracion` WRITE;
/*!40000 ALTER TABLE `configuracion` DISABLE KEYS */;
INSERT INTO `configuracion` VALUES (1,'PLASTICOS ALISAKA','uploads/logo_1788566887.png','admin@empresa.com',NULL,NULL,NULL,NULL,'uploads/fondo/background.jpg');
/*!40000 ALTER TABLE `configuracion` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_cobrar`
--

DROP TABLE IF EXISTS `cuentas_cobrar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_cobrar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `salida_id` int NOT NULL,
  `cliente` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `estatus` enum('pendiente','cobrado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_emision` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_vencimiento` date DEFAULT NULL,
  `fecha_cobro` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salida_id` (`salida_id`),
  CONSTRAINT `cuentas_cobrar_ibfk_1` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_cobrar`
--

LOCK TABLES `cuentas_cobrar` WRITE;
/*!40000 ALTER TABLE `cuentas_cobrar` DISABLE KEYS */;
INSERT INTO `cuentas_cobrar` VALUES (1,24,'FARMACIAS GUADALAJARA S.A. DE C.V.',0.00,'cobrado','2026-09-04 17:39:12',NULL,'2026-09-04 17:39:12'),(2,29,'WALMART S.A. DE C.V.',0.00,'cobrado','2026-09-04 18:03:32',NULL,'2026-09-04 18:03:32'),(3,31,'WALMART S.A. DE C.V.',0.00,'cobrado','2026-09-23 13:12:32',NULL,'2026-09-23 13:12:32'),(4,30,'WALMART S.A. DE C.V.',0.00,'cobrado','2026-09-23 13:12:36',NULL,'2026-09-23 13:12:36');
/*!40000 ALTER TABLE `cuentas_cobrar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cuentas_pagar`
--

DROP TABLE IF EXISTS `cuentas_pagar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_pagar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entrada_id` int DEFAULT NULL,
  `proveedor_id` int DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `monto_pagado` decimal(10,2) DEFAULT '0.00',
  `estatus` enum('pendiente','parcial','pagado') COLLATE utf8mb4_unicode_ci DEFAULT 'pendiente',
  `fecha_emision` datetime DEFAULT CURRENT_TIMESTAMP,
  `fecha_pago` datetime DEFAULT NULL,
  `metodo_pago` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `entrada_id` (`entrada_id`),
  KEY `proveedor_id` (`proveedor_id`),
  CONSTRAINT `cuentas_pagar_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_inventario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `cuentas_pagar_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cuentas_pagar`
--

LOCK TABLES `cuentas_pagar` WRITE;
/*!40000 ALTER TABLE `cuentas_pagar` DISABLE KEYS */;
INSERT INTO `cuentas_pagar` VALUES (1,1,3,'Compra a crédito (inicial): 900 unid. de TAPAS DE BOTELLA',1044.00,0.00,'pagado','2026-09-04 10:11:36','2026-09-04 10:12:14','efectivo','uploads/facturas_compras/compra_1788538296_6a9aedb80902b.pdf','2026-09-07'),(2,3,1,'Compra a crédito de 100 unid. de RESINA PET EN GRANULOS',55000.00,0.00,'pagado','2026-09-04 11:50:43','2026-09-04 12:02:03','efectivo',NULL,NULL),(3,4,3,'Compra a crédito de 20 unid. de RESINA PET EN GRANULOS',11000.00,0.00,'pagado','2026-09-04 13:23:36','2026-09-04 17:37:26','transferencia',NULL,NULL),(4,NULL,1,'Compra a crédito de 100 unid. de TAPAS DE BOTELLA',600.00,0.00,'pagado','2026-09-04 18:00:28','2026-09-04 18:03:20','transferencia',NULL,NULL);
/*!40000 ALTER TABLE `cuentas_pagar` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detalle_salidas`
--

DROP TABLE IF EXISTS `detalle_salidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `detalle_salidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `salida_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `salida_id` (`salida_id`),
  CONSTRAINT `detalle_salidas_ibfk_1` FOREIGN KEY (`salida_id`) REFERENCES `salidas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detalle_salidas`
--

LOCK TABLES `detalle_salidas` WRITE;
/*!40000 ALTER TABLE `detalle_salidas` DISABLE KEYS */;
INSERT INTO `detalle_salidas` VALUES (18,21,1,300.00,25.00,7500.00),(19,22,1,200.00,25.00,5000.00),(20,23,1,100.00,25.00,2500.00),(21,24,2,300.00,550.00,165000.00),(22,25,1,90.00,25.00,2250.00),(23,26,1,30.00,25.00,750.00),(24,27,1,10.00,25.00,250.00),(25,28,5,500.00,9.00,4500.00),(26,29,5,200.00,9.00,1800.00),(27,30,3,300.00,3.00,900.00),(28,31,6,5.00,600.00,3000.00);
/*!40000 ALTER TABLE `detalle_salidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `egresos`
--

DROP TABLE IF EXISTS `egresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `egresos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `entrada_id` int DEFAULT NULL,
  `cuenta_pagar_id` int DEFAULT NULL,
  `proveedor_id` int DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','transferencia','tarjeta') COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_comprobante` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'sin_comprobante',
  `comprobante_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `entrada_id` (`entrada_id`),
  KEY `proveedor_id` (`proveedor_id`),
  CONSTRAINT `egresos_ibfk_1` FOREIGN KEY (`entrada_id`) REFERENCES `entradas_inventario` (`id`) ON DELETE SET NULL,
  CONSTRAINT `egresos_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `egresos`
--

LOCK TABLES `egresos` WRITE;
/*!40000 ALTER TABLE `egresos` DISABLE KEYS */;
INSERT INTO `egresos` VALUES (1,1,NULL,3,'Pago CxP: Compra a crédito (inicial): 900 unid. de TAPAS DE BOTELLA',1044.00,'efectivo','sin_comprobante','uploads/facturas_compras/compra_1788538296_6a9aedb80902b.pdf','2026-09-04 10:12:14'),(3,3,NULL,1,'Pago CxP (Liquidación): Compra a crédito de 100 unid. de RESINA PET EN GRANULOS',55000.00,'efectivo','sin_comprobante',NULL,'2026-09-04 12:02:03'),(4,NULL,NULL,1,'Compra inicial: 500 unid. de TAPAS DE BOTELLA',1160.00,'transferencia','factura','uploads/facturas_compras/compra_1788564445_6a9b53dd89e98.pdf','2026-09-04 17:27:25'),(5,6,NULL,1,'Compra de 150 unid. de BOTELLAS TIPO SUAVITEL',900.00,'efectivo','factura','uploads/facturas_compras/compra_1788564673_bb4670ef.pdf','2026-09-04 17:31:13'),(6,4,NULL,3,'Pago CxP (Liquidación): Compra a crédito de 20 unid. de RESINA PET EN GRANULOS',11000.00,'transferencia','sin_comprobante',NULL,'2026-09-04 17:37:26'),(7,7,NULL,1,'Compra inicial: 900 unid. de TAPAS DE BOTELLA SUAVITEL',3132.00,'transferencia','factura','uploads/facturas_compras/compra_1788566014_6a9b59fe4f7a2.pdf','2026-09-04 17:53:34'),(8,8,NULL,1,'Compra de 200 unid. de TAPAS DE BOTELLA SUAVITEL',600.00,'efectivo','factura','uploads/facturas_compras/compra_1788566256_be147e74.pdf','2026-09-04 17:57:36'),(9,NULL,NULL,1,'Pago CxP (Liquidación): Compra a crédito de 100 unid. de TAPAS DE BOTELLA',600.00,'transferencia','sin_comprobante',NULL,'2026-09-04 18:03:20'),(10,10,NULL,2,'Compra de 200 unid. de BOTELLAS TIPO SUAVITEL',1200.00,'efectivo','ninguno',NULL,'2026-09-04 18:05:35'),(11,11,NULL,NULL,'Compra inicial: 200 Caja(s) [Total: 100000 unids.] de TAPAS TIPO ERT AZUL',50000.00,'efectivo','sin_comprobante',NULL,'2026-09-21 18:16:49'),(12,NULL,NULL,6,'pago de sistema de software o aplicativo de inventarios',16240.00,'transferencia','sin_comprobante',NULL,'2026-09-29 12:59:51');
/*!40000 ALTER TABLE `egresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `entradas_inventario`
--

DROP TABLE IF EXISTS `entradas_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `entradas_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `proveedor_id` int DEFAULT NULL,
  `cantidad` int NOT NULL,
  `empaques_recibidos` decimal(10,2) DEFAULT NULL,
  `unidades_por_empaque` decimal(10,2) DEFAULT '1.00',
  `costo_unitario` decimal(10,2) NOT NULL,
  `monto_total` decimal(10,2) NOT NULL,
  `estatus_pago` enum('pagado','pendiente') COLLATE utf8mb4_unicode_ci DEFAULT 'pagado',
  `tipo_comprobante` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'sin_comprobante',
  `comprobante_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` datetime DEFAULT NULL,
  `fecha_registro` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `proveedor_id` (`proveedor_id`),
  CONSTRAINT `entradas_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `entradas_inventario_ibfk_2` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `entradas_inventario`
--

LOCK TABLES `entradas_inventario` WRITE;
/*!40000 ALTER TABLE `entradas_inventario` DISABLE KEYS */;
INSERT INTO `entradas_inventario` VALUES (1,3,3,900,NULL,1.00,1.00,1044.00,'pagado','factura','uploads/facturas_compras/compra_1788538296_6a9aedb80902b.pdf','2026-09-04 10:12:14','2026-09-04 10:11:36'),(3,2,1,100,NULL,1.00,550.00,55000.00,'pagado','ninguno',NULL,'2026-09-04 12:02:03','2026-09-04 11:50:43'),(4,2,3,20,NULL,1.00,550.00,11000.00,'pagado','ninguno',NULL,'2026-09-04 17:37:26','2026-09-04 13:23:36'),(6,1,1,150,NULL,1.00,6.00,900.00,'pagado','factura','uploads/facturas_compras/compra_1788564673_bb4670ef.pdf','2026-09-04 23:31:13','2026-09-04 17:31:13'),(7,5,1,900,NULL,1.00,3.00,3132.00,'pagado','factura','uploads/facturas_compras/compra_1788566014_6a9b59fe4f7a2.pdf','2026-09-04 23:53:34','2026-09-04 17:53:34'),(8,5,1,200,NULL,1.00,3.00,600.00,'pagado','factura','uploads/facturas_compras/compra_1788566256_be147e74.pdf','2026-09-04 23:57:36','2026-09-04 17:57:36'),(10,1,2,200,NULL,1.00,6.00,1200.00,'pagado','ninguno',NULL,'2026-09-05 00:05:35','2026-09-04 18:05:35'),(11,6,NULL,100000,200.00,500.00,250.00,50000.00,'pagado','sin_comprobante',NULL,'2026-09-22 00:16:49','2026-09-21 18:16:49');
/*!40000 ALTER TABLE `entradas_inventario` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ingresos`
--

DROP TABLE IF EXISTS `ingresos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ingresos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `usuario_id` int DEFAULT '1',
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(10,2) NOT NULL,
  `comentario` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `salida_id` int DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto_subtotal` decimal(10,2) DEFAULT '0.00',
  `monto_iva` decimal(10,2) DEFAULT '0.00',
  `monto_total` decimal(10,2) DEFAULT '0.00',
  `metodo_pago` enum('efectivo','transferencia','tarjeta') COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `comprobante_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_ingreso` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_ingresos_producto` (`producto_id`),
  CONSTRAINT `fk_ingresos_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ingresos`
--

LOCK TABLES `ingresos` WRITE;
/*!40000 ALTER TABLE `ingresos` DISABLE KEYS */;
INSERT INTO `ingresos` VALUES (1,2,1,1.00,191400.00,'','2026-09-04 17:39:12',24,'Cobro de crédito a FARMACIAS GUADALAJARA S.A. DE C.V. (Venta #24)',191400.00,0.00,191400.00,'efectivo',NULL,'2026-09-04 17:39:12'),(2,5,1,1.00,1800.00,'','2026-09-04 18:03:32',29,'Cobro de crédito a WALMART S.A. DE C.V. (Venta #29)',1800.00,0.00,1800.00,'tarjeta',NULL,'2026-09-04 18:03:32'),(3,6,1,1.00,3480.00,'','2026-09-23 13:12:32',31,'Cobro de crédito a WALMART S.A. DE C.V. (Venta #31)',3480.00,0.00,3480.00,'efectivo',NULL,'2026-09-23 13:12:32'),(4,3,1,1.00,900.00,'','2026-09-23 13:12:36',30,'Cobro de crédito a WALMART S.A. DE C.V. (Venta #30)',900.00,0.00,900.00,'efectivo',NULL,'2026-09-23 13:12:36');
/*!40000 ALTER TABLE `ingresos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `productos`
--

DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo_unidad` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pieza',
  `unidades_por_empaque` decimal(10,2) NOT NULL DEFAULT '1.00',
  `costo_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_venta` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '5.00',
  `imagen` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'uploads/productos/default.png',
  `creado_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `stock` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `productos`
--

LOCK TABLES `productos` WRITE;
/*!40000 ALTER TABLE `productos` DISABLE KEYS */;
INSERT INTO `productos` VALUES (1,'PRODUC-001','BOTELLAS TIPO SUAVITEL','Pieza',1.00,6.00,25.00,250.00,100.00,'uploads/productos/prod_1788468831_6a99de5f528aa.jpg','2026-09-03 20:53:51','',0),(2,'INSUMO-001','RESINA PET EN GRANULOS','Paquete',1.00,550.00,0.00,420.00,100.00,'uploads/productos/prod_1788473627_6a99f11b9544f.png','2026-09-03 22:13:22','',0),(3,'PRODUC-002','TAPAS DE BOTELLA','Pieza',1.00,1.00,3.00,600.00,100.00,'uploads/productos/prod_1788538296_6a9aedb808f59.jpg','2026-09-04 16:11:36','',0),(5,'PRODUC-003','TAPAS DE BOTELLA SUAVITEL','Pieza',1.00,3.00,9.00,400.00,150.00,'uploads/productos/prod_1788566033_6a9b5a114a8a9.jpg','2026-09-04 23:53:34','',0),(6,'PRODUCTO-00012','TAPAS TIPO ERT AZUL','Caja',500.00,250.00,600.00,99995.00,5.00,'uploads/productos/prod_1790096022_6ab2b2965d72a.jpg','2026-09-22 00:16:49','',0);
/*!40000 ALTER TABLE `productos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contacto` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` text COLLATE utf8mb4_unicode_ci,
  `banco` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_cuenta` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'LA CORCHOLATA S.A. DE C.V.','AAGF870104AE0','LIC. GORJE MARTINEZ ALBARRAN','5589741421','jmhrs8@gmail.com','Calle 10 Manzana 65 lote 21 colonia jose lopez portillo entre calle 21 y 22','BBVA','01234578889998887777','2026-09-03 14:52:13'),(2,'ALISAKA S.A. DE C.V.','AAMO761002FD7','FRANCISCO','5569874214','admin@alisaka.com','CALLE 10 NTE 65, 21','santander','89755644889326544','2026-09-03 16:02:47'),(3,'TLAPALERIA GUADALAJARA S.A. DE C.V.','AELO901001AT7','LIC. JOSE LUIS TREJO SANCHEZ','5689742314','tlapaguada@gmail.com','33 lomalinda Guadalajara.',NULL,NULL,'2026-09-04 10:07:36'),(4,'LAS ESTRELLAS S.A. DE C.V.','REGP8702074E1','SR. PEDRO CASTRO RODRIGUEZ','9687449610','pedro@gmail.com','CALLE LAS CAMELIAS # 34 PASEO DE LAS FLORES',NULL,NULL,'2026-09-04 17:29:22'),(5,'BODEGA AURRERA S.A. DE C.V.','AELO901001AT7','LIC. BENITO CASTRO R.','5698741236','castro@gmail.com','calle Gardenias 32','banorte','65989978442555111','2026-09-04 17:55:23'),(6,'JUAN MANUEL HERNANDEZ LUGO','HELJ740724UM1','JUAN MANUEL HDZ LUGO','5519017322','jmhrs8@gmail.com','Calle 10 Manzana 65 lote 21 colonia jose lopez portillo entre calle 21 y 22','NU','','2026-09-29 12:50:00');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salidas`
--

DROP TABLE IF EXISTS `salidas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salidas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `cliente` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT 'Público General',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado_cobro` enum('cobrado','credito') COLLATE utf8mb4_unicode_ci DEFAULT 'cobrado',
  `fecha_vencimiento` date DEFAULT NULL,
  `metodo_cobro` enum('efectivo','transferencia','tarjeta') COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `requiere_factura` tinyint(1) DEFAULT '0',
  `subtotal` decimal(10,2) DEFAULT '0.00',
  `iva` decimal(10,2) DEFAULT '0.00',
  `factura_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_pago` enum('contado','credito') COLLATE utf8mb4_unicode_ci DEFAULT 'contado',
  `metodo_pago` enum('efectivo','transferencia','tarjeta') COLLATE utf8mb4_unicode_ci DEFAULT 'efectivo',
  `con_factura` tinyint(1) DEFAULT '0',
  `monto_total` decimal(10,2) DEFAULT '0.00',
  `fecha_salida` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `salidas`
--

LOCK TABLES `salidas` WRITE;
/*!40000 ALTER TABLE `salidas` DISABLE KEYS */;
INSERT INTO `salidas` VALUES (21,3,'WALMART S.A. DE C.V.',7500.00,'2026-09-04 13:16:45','cobrado',NULL,'transferencia',0,7500.00,0.00,'uploads/ventas/salida_1788549405_488d4360.pdf','contado','transferencia',0,7500.00,'2026-09-04 13:16:45'),(22,3,'WALMART S.A. DE C.V.',5800.00,'2026-09-04 13:41:04','cobrado',NULL,'transferencia',1,5000.00,800.00,'uploads/ventas/salida_1788550864_0d292ae7.pdf','contado','transferencia',1,5800.00,'2026-09-04 13:41:04'),(23,3,'WALMART S.A. DE C.V.',2900.00,'2026-09-04 13:41:48','cobrado',NULL,'transferencia',1,2500.00,400.00,'uploads/ventas/salida_1788550908_c48fc273.pdf','contado','transferencia',1,2900.00,'2026-09-04 13:41:48'),(24,3,'FARMACIAS GUADALAJARA S.A. DE C.V.',191400.00,'2026-09-04 14:07:20','cobrado',NULL,'transferencia',1,165000.00,26400.00,NULL,'contado','transferencia',1,191400.00,'2026-09-04 14:07:20'),(25,3,'WALMART S.A. DE C.V.',2610.00,'2026-09-04 17:33:07','cobrado',NULL,'transferencia',1,2250.00,360.00,'uploads/ventas/salida_1788564787_69979179.pdf','contado','transferencia',1,2610.00,'2026-09-04 17:33:07'),(26,3,'WALMART S.A. DE C.V.',870.00,'2026-09-04 17:34:19','cobrado',NULL,'tarjeta',1,750.00,120.00,'uploads/ventas/salida_1788564859_77800c13.pdf','contado','tarjeta',1,870.00,'2026-09-04 17:34:19'),(27,3,'WALMART S.A. DE C.V.',250.00,'2026-09-04 17:35:20','cobrado',NULL,'efectivo',0,250.00,0.00,NULL,'contado','efectivo',0,250.00,'2026-09-04 17:35:20'),(28,3,'WALMART S.A. DE C.V.',5220.00,'2026-09-04 17:58:41','cobrado',NULL,'transferencia',1,4500.00,720.00,'uploads/ventas/salida_1788566321_a8fa5840.pdf','contado','transferencia',1,5220.00,'2026-09-04 17:58:41'),(29,3,'WALMART S.A. DE C.V.',1800.00,'2026-09-04 18:01:43','cobrado',NULL,'transferencia',0,1800.00,0.00,NULL,'contado','transferencia',0,1800.00,'2026-09-04 18:01:43'),(30,3,'WALMART S.A. DE C.V.',900.00,'2026-09-07 16:05:34','cobrado',NULL,'efectivo',0,900.00,0.00,NULL,'contado','efectivo',0,900.00,'2026-09-07 16:05:34'),(31,3,'WALMART S.A. DE C.V.',3480.00,'2026-09-21 18:18:00','cobrado','2026-09-22','efectivo',1,3000.00,480.00,NULL,'contado','efectivo',1,3480.00,'2026-09-21 18:18:11');
/*!40000 ALTER TABLE `salidas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rol` enum('admin','usuario') COLLATE utf8mb4_unicode_ci DEFAULT 'usuario',
  `creado_en` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@empresa.com','$2y$10$XD1V3UR879HCm5SL59343OxGWmfjH3c5DsOe.PnegojOC0sB1NZR2','admin','2026-09-01 10:41:59'),(3,'JUAN MANUEL HERNÁNDEZ LUGO','jmhrs8@gmail.com','$2y$10$oQ3wxedjlM4oH.KLjV1VqOY4PICGe19QXfr9LKQ7OlkSqbQQh67C6','admin','2026-09-01 13:51:02'),(4,'pancho','paco@gmail.com','$2y$10$5pyipcNjUZH8RP2Pfs7Qoeym2qQYU8tPPgOaesSmg3cKYzNy9DA5m','usuario','2026-09-04 14:09:15'),(5,'MARTHA','martha@gmail.com','$2y$10$uOMe8JyHlachSctRV5OTsuiwwC57MdxI2KjfwGmo02sSUWqHgVKRu','admin','2026-09-04 18:06:33');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'erp_inventory'
--

--
-- Dumping routines for database 'erp_inventory'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-01 13:21:33
