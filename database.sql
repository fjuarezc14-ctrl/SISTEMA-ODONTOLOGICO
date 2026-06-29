-- MySQL dump 10.13  Distrib 5.7.44, for Linux (x86_64)
--
-- Host: localhost    Database: mahudent_db
-- ------------------------------------------------------
-- Server version	5.7.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `archivos_clinicos`
--

DROP TABLE IF EXISTS `archivos_clinicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `archivos_clinicos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `tamano` int(11) NOT NULL,
  `fecha_subida` datetime DEFAULT CURRENT_TIMESTAMP,
  `subido_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `subido_por` (`subido_por`),
  CONSTRAINT `archivos_clinicos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `archivos_clinicos_ibfk_2` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `archivos_clinicos`
--

LOCK TABLES `archivos_clinicos` WRITE;
/*!40000 ALTER TABLE `archivos_clinicos` DISABLE KEYS */;
INSERT INTO `archivos_clinicos` VALUES (1,1,'Foto Intraoral','a3808660fcf50b7602e15c6a8ca6c469.jpg','uploads/pacientes/1/1778778387_45c3b7045bc1be44.jpg','Foto intraoral ',42592,'2026-05-14 17:06:28',1),(2,4,'Documento','Consentimiento Informado - LORENA ESPINOZA.pdf','uploads/pacientes/4/1778890472_d076ab1b09e4dfc3.pdf','Consentimiento',58326,'2026-05-16 00:14:32',1);
/*!40000 ALTER TABLE `archivos_clinicos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `catalogo_tratamientos`
--

DROP TABLE IF EXISTS `catalogo_tratamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `catalogo_tratamientos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` mediumtext COLLATE utf8mb4_unicode_ci,
  `precio_base` decimal(10,2) NOT NULL DEFAULT '0.00',
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_odontograma` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `catalogo_tratamientos`
--

LOCK TABLES `catalogo_tratamientos` WRITE;
/*!40000 ALTER TABLE `catalogo_tratamientos` DISABLE KEYS */;
INSERT INTO `catalogo_tratamientos` VALUES (1,'Resina Simple',NULL,80.00,'Restauracion','resina',1,'2026-05-20 05:21:18'),(2,'Resina Compuesta',NULL,120.00,'Restauracion','resina',1,'2026-05-20 05:21:18'),(3,'Curacion Simple',NULL,80.00,'Restauracion','resina',1,'2026-05-20 05:21:18'),(4,'Curacion Compuesta',NULL,120.00,'Restauracion','resina',1,'2026-05-20 05:21:18'),(5,'Curacion Compleja',NULL,150.00,'Restauracion','resina',1,'2026-05-20 05:21:18'),(6,'Incrustacion Dental',NULL,250.00,'Restauracion','corona',1,'2026-05-20 05:21:18'),(7,'Endodoncia Anterior',NULL,200.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(8,'Endodoncia Premolar',NULL,250.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(9,'Endodoncia Molar',NULL,350.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(10,'Endodoncia Incisivo Central',NULL,150.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(11,'Endodoncia Incisivo Lateral',NULL,150.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(12,'Perno de Fibra de Vidrio',NULL,100.00,'Endodoncia',NULL,1,'2026-05-20 05:21:18'),(13,'Limpieza Dental',NULL,50.00,'Prevencion',NULL,1,'2026-05-20 05:21:18'),(14,'Profilaxis',NULL,50.00,'Prevencion',NULL,1,'2026-05-20 05:21:18'),(15,'Fluor Barniz',NULL,40.00,'Prevencion',NULL,1,'2026-05-20 05:21:18'),(16,'Fluor Gel',NULL,30.00,'Prevencion',NULL,1,'2026-05-20 05:21:18'),(17,'Blanqueamiento',NULL,200.00,'Estetica',NULL,1,'2026-05-20 05:21:18'),(18,'Carillas de Resina',NULL,200.00,'Estetica',NULL,1,'2026-05-20 05:21:18'),(19,'Carillas de Porcelana',NULL,400.00,'Estetica',NULL,1,'2026-05-20 05:21:18'),(20,'Carillas de Zirconio',NULL,500.00,'Estetica',NULL,1,'2026-05-20 05:21:18'),(21,'Corona Dental',NULL,350.00,'Protesis Fija','corona',1,'2026-05-20 05:21:18'),(22,'Corona de Zirconio',NULL,600.00,'Protesis Fija','corona',1,'2026-05-20 05:21:18'),(23,'Corona de Porcelana',NULL,400.00,'Protesis Fija','corona',1,'2026-05-20 05:21:18'),(24,'Corona Disilicato',NULL,500.00,'Protesis Fija','corona',1,'2026-05-20 05:21:18'),(25,'Corona Metal Porcelana',NULL,350.00,'Protesis Fija','corona',1,'2026-05-20 05:21:18'),(26,'Protesis Parcial',NULL,400.00,'Protesis Removible',NULL,1,'2026-05-20 05:21:18'),(27,'Protesis Total',NULL,600.00,'Protesis Removible',NULL,1,'2026-05-20 05:21:18'),(28,'Protesis Flexible',NULL,550.00,'Protesis Removible',NULL,1,'2026-05-20 05:21:18'),(29,'Extraccion Simple',NULL,60.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(30,'Extraccion Quirurgica',NULL,150.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(31,'Extraccion Incisivo',NULL,60.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(32,'Extraccion Premolar',NULL,80.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(33,'Extraccion Molar',NULL,100.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(34,'Extraccion Tercera',NULL,150.00,'Cirugia','ausente',1,'2026-05-20 05:21:18'),(35,'Implante Dental',NULL,800.00,'Implantologia',NULL,1,'2026-05-20 05:21:18'),(36,'Ortodoncia - Brackets',NULL,2500.00,'Ortodoncia',NULL,1,'2026-05-20 05:21:18'),(37,'Retenedores Removibles',NULL,150.00,'Ortodoncia',NULL,1,'2026-05-20 05:21:18'),(38,'Retenedores Fijos',NULL,180.00,'Ortodoncia',NULL,1,'2026-05-20 05:21:18'),(39,'Placa',NULL,200.00,'Ortodoncia',NULL,1,'2026-05-20 05:21:18'),(40,'Ferula Miorelajante',NULL,250.00,'Oclusion',NULL,1,'2026-05-20 05:21:18'),(41,'Radiografia Periapical',NULL,15.00,'Diagnostico',NULL,1,'2026-05-20 05:21:18'),(42,'Radiografia Panoramica',NULL,40.00,'Diagnostico',NULL,1,'2026-05-20 05:21:18'),(43,'Cepillo Medio',NULL,15.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(44,'Cepillo Duro',NULL,15.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(45,'Cepillo Suave',NULL,15.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(46,'Cepillo Ortodontico',NULL,20.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(47,'Cepillo Encias',NULL,18.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(48,'Interprox Conical',NULL,22.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(49,'Interprox MicroInterprox',NULL,22.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(50,'Super Hilo Dental',NULL,12.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(51,'Cera Ortodontica',NULL,10.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(52,'Pasta Ortodontica',NULL,25.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(53,'Pasta Aloe',NULL,20.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(54,'Pasta Encias',NULL,28.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(55,'Pasta Sensible',NULL,25.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(56,'Pasta Blanqueadora',NULL,30.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(57,'Pasta CPC',NULL,25.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(58,'Enjuague Encias 500ml',NULL,40.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(59,'Enjuague Encias 150ml',NULL,25.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(60,'Enjuague CPC',NULL,35.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(61,'Enjuague Ortodontico',NULL,35.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(62,'Enjuague Sensible',NULL,35.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(63,'Enjuague Halita',NULL,45.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(64,'Perio-AID Active',NULL,40.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(65,'PerioAID Intensive',NULL,42.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(66,'Cepillo Vitis Junior',NULL,18.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(67,'Cepillo Vitis Kids',NULL,18.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(68,'Cepillo Vitis Junior Gel',NULL,20.00,'Insumos',NULL,1,'2026-05-20 05:21:18'),(69,'Vitis Kids Gel',NULL,20.00,'Insumos',NULL,1,'2026-05-20 05:21:18');
/*!40000 ALTER TABLE `catalogo_tratamientos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `citas`
--

DROP TABLE IF EXISTS `citas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `citas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `estado` enum('Pendiente','Confirmada','Completada','Cancelada','En Curso') DEFAULT 'Pendiente',
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `citas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `citas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `citas`
--

LOCK TABLES `citas` WRITE;
/*!40000 ALTER TABLE `citas` DISABLE KEYS */;
INSERT INTO `citas` VALUES (7,1,1,'2026-05-08','11:25:00','12:50:00','Ej1','Completada'),(8,1,1,'2026-05-09','09:30:00','15:00:00','Ej3','Completada'),(9,1,1,'2026-05-09','07:30:00','09:30:00','Ej5','Completada'),(10,1,1,'2026-05-14','13:30:00','13:55:00','Profilaxis','Completada'),(12,2,1,'2026-05-21','14:10:00','14:50:00','Ejemplo05','Pendiente'),(13,4,1,'2026-05-16','10:30:00','11:00:00','Curaciones','Completada'),(14,4,1,'2026-07-14','10:00:00','11:00:00','Segunda cita','Pendiente'),(15,3,1,'2026-05-18','11:00:00','11:30:00','Profilaxis','Completada'),(16,3,1,'2026-05-16','11:45:00','12:30:00','EJEMPLO06','Pendiente'),(17,4,1,'2026-05-20','11:00:00','13:00:00','Limpieza','Completada'),(18,2,3,'2026-05-21','15:20:00','16:20:00','EJEMPL03','Pendiente'),(19,4,1,'2026-05-22','11:00:00','12:30:00','EJEMPLO10','Pendiente');
/*!40000 ALTER TABLE `citas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `historial_evolutivo`
--

DROP TABLE IF EXISTS `historial_evolutivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `historial_evolutivo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `descripcion` text NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `cita_id` (`cita_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `historial_evolutivo_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `historial_evolutivo_ibfk_2` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `historial_evolutivo_ibfk_3` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `historial_evolutivo`
--

LOCK TABLES `historial_evolutivo` WRITE;
/*!40000 ALTER TABLE `historial_evolutivo` DISABLE KEYS */;
INSERT INTO `historial_evolutivo` VALUES (1,1,8,'Diente 42 ausente',1,'2026-05-09 02:45:59'),(2,1,NULL,'Mediacamento para dolor de dientes',1,'2026-05-09 02:47:04'),(3,1,NULL,'12345',1,'2026-05-09 02:48:03'),(4,1,9,'Caries superficiales',1,'2026-05-09 05:25:36'),(5,1,10,'Curacion completa',1,'2026-05-13 22:59:24'),(6,4,13,'La carie 124idfhj cualquer cosa',1,'2026-05-16 00:01:58'),(7,4,NULL,'NOTA RAPIDA 01',1,'2026-05-16 00:04:34'),(8,4,17,'Se realizó la limpieza total',1,'2026-05-20 00:27:28'),(9,5,NULL,'Se extrajo todo el diente 16',1,'2026-05-20 23:35:05'),(10,5,NULL,'Se hizo curación del diente 33',1,'2026-05-20 23:35:24'),(11,5,NULL,'123',3,'2026-05-21 00:01:15'),(12,5,NULL,'321',3,'2026-05-21 00:15:04');
/*!40000 ALTER TABLE `historial_evolutivo` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `odontograma_estado`
--

DROP TABLE IF EXISTS `odontograma_estado`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `odontograma_estado` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `diente_numero` int(11) NOT NULL,
  `cara_afectada` varchar(50) NOT NULL,
  `estado` varchar(50) NOT NULL,
  `notas` text,
  `fecha_actualizacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  CONSTRAINT `odontograma_estado_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `odontograma_estado`
--

LOCK TABLES `odontograma_estado` WRITE;
/*!40000 ALTER TABLE `odontograma_estado` DISABLE KEYS */;
INSERT INTO `odontograma_estado` VALUES (1,1,18,'Distal','caries','','2026-05-08 05:26:53'),(2,1,17,'Vestibular','resina','','2026-05-08 05:27:18'),(3,1,42,'Raiz_Izquierda','ausente','1234','2026-05-09 05:25:24'),(4,1,42,'Oclusal','ausente','1234','2026-05-09 05:25:25'),(5,1,42,'Raiz_Derecha','ausente','1234','2026-05-09 05:25:25'),(6,1,42,'Mesial','ausente','1234','2026-05-09 05:25:25'),(7,1,42,'Distal','ausente','1234','2026-05-09 05:25:25'),(8,1,42,'Lingual','ausente','1234','2026-05-09 05:25:25'),(9,1,42,'Vestibular','ausente','1234','2026-05-09 05:25:25'),(10,1,11,'Oclusal','caries','zxcvasdfqwer','2026-05-09 05:23:34'),(11,1,12,'Vestibular','corona','1234','2026-05-09 05:22:36'),(12,1,13,'Oclusal','resina','CuraciÃ³n','2026-05-13 22:58:59'),(13,1,13,'Distal','resina','CuraciÃ³n','2026-05-13 22:58:59'),(14,3,12,'Oclusal','corona','NOTA01','2026-05-15 23:38:19'),(15,3,12,'Vestibular','corona','NOTA01','2026-05-15 23:38:19'),(16,4,43,'Oclusal','caries','Carie simple','2026-05-16 00:01:14'),(17,4,15,'Oclusal','caries','','2026-05-20 23:19:07'),(18,5,16,'Oclusal','ausente','No hay diente','2026-05-20 23:33:01'),(19,5,16,'Mesial','ausente','No hay diente','2026-05-20 23:33:01'),(20,5,16,'Distal','ausente','No hay diente','2026-05-20 23:33:01'),(21,5,33,'Vestibular','resina','','2026-05-20 23:34:18'),(22,4,47,'Vestibular','sellante','','2026-05-21 16:11:02');
/*!40000 ALTER TABLE `odontograma_estado` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pacientes`
--

DROP TABLE IF EXISTS `pacientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pacientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dni` varchar(20) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `lugar_nacimiento` varchar(100) DEFAULT NULL,
  `sexo` varchar(15) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `procedencia` varchar(100) DEFAULT NULL,
  `contacto_emergencia` varchar(150) DEFAULT NULL,
  `telefono_emergencia` varchar(20) DEFAULT NULL,
  `ocupacion` varchar(100) DEFAULT NULL,
  `alergias` text,
  `enfermedades_cronicas` text,
  `padece_enfermedad` tinyint(1) DEFAULT NULL,
  `consume_medicamentos` tinyint(1) DEFAULT NULL,
  `medicamentos_detalle` varchar(255) DEFAULT NULL,
  `alergia_medicamentos` tinyint(1) DEFAULT NULL,
  `alergia_medicamentos_detalle` varchar(255) DEFAULT NULL,
  `antecedentes_familiares` tinyint(1) DEFAULT NULL,
  `antecedentes_familiares_detalle` varchar(255) DEFAULT NULL,
  `alergia_anestesia` tinyint(1) DEFAULT NULL,
  `embarazada` tinyint(1) DEFAULT NULL,
  `sangran_encias` tinyint(1) DEFAULT NULL,
  `ultima_visita_dentista` date DEFAULT NULL,
  `ultima_visita_motivo` varchar(255) DEFAULT NULL,
  `frecuencia_cepillado` varchar(50) DEFAULT NULL,
  `usa_cepillo` tinyint(1) DEFAULT '1',
  `usa_pasta_dental` tinyint(1) DEFAULT '1',
  `usa_hilo_dental` tinyint(1) DEFAULT '0',
  `usa_enjuague` tinyint(1) DEFAULT '0',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estado_activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `dni` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pacientes`
--

LOCK TABLES `pacientes` WRITE;
/*!40000 ALTER TABLE `pacientes` DISABLE KEYS */;
INSERT INTO `pacientes` VALUES (1,'15963214','Roger Mestanza C',NULL,NULL,NULL,'987457745','rm1235@gmail.com',NULL,NULL,NULL,NULL,NULL,'HipertensiÃ³n',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,1,1,0,0,'2026-05-06 05:29:37',1),(2,'19587432','Rosa Vera','1984-11-15','Cajamarca','Femenino','+51 943 421 517','rv1985@gmail.com','Jr. Urrelo 322','Cajamarca','','','Docente','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,0,0,'2026-05-14 22:40:00',1),(3,'58827395','Carmen Merino','1998-03-18','Cajamarca','Femenino','902354534','rm15@gmail.com','Av. Bambamarca 123','Cajamarca','JosÃ© Marin','932574863','Docente','',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,1,0,0,'2026-05-15 23:33:52',0),(4,'70617414','LORENA ESPINOZA','2000-02-17','Cajamarca','Femenino','968619132','le@gmail.com','Av. Perú 423','Cajamarca','Angel david','985468751','Odontóloga','Prueba003','Prueba003',1,0,NULL,0,NULL,0,NULL,NULL,1,0,'2026-05-16','Limpieza','3 veces al día',1,1,0,0,'2026-05-15 23:55:46',1),(5,'85582941','Marco Cervera Sevilla','2001-06-04','Cajamarca','Masculino','902343321','mc@gmail.com','Jr. Pochinki','Cajamarca','Luis Chavez','948273345','Estudiante','Hipertensión, Penicilina','Hipertensión',1,0,NULL,0,NULL,NULL,NULL,1,NULL,NULL,'2026-05-13',NULL,NULL,1,1,0,0,'2026-05-20 23:31:53',1);
/*!40000 ALTER TABLE `pacientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pagos`
--

DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `paciente_id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('Efectivo','Tarjeta','Transferencia','Yape/Plin') DEFAULT 'Efectivo',
  `tipo` enum('Adelanto','Parcial','Saldo Final') DEFAULT 'Parcial',
  `comprobante_tipo` enum('Boleta','Factura','Ninguno') DEFAULT 'Boleta',
  `comprobante_numero` varchar(30) DEFAULT NULL,
  `notas` text,
  `registrado_por` int(11) DEFAULT NULL,
  `fecha_pago` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `registrado_por` (`registrado_por`),
  CONSTRAINT `pagos_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_ibfk_2` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pagos_ibfk_3` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pagos`
--

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,7,1,15.00,'Efectivo','Parcial','Boleta','B-000001','Adelanto',1,'2026-05-11 17:56:09'),(2,5,1,875.50,'Efectivo','Saldo Final','Factura','F-000001','Pago total',1,'2026-05-11 17:56:35'),(3,11,1,288.80,'Efectivo','Parcial','Boleta','B-000002','Adelanto',1,'2026-05-13 23:02:33'),(4,7,1,15.00,'Yape/Plin','Parcial','Boleta','B-000003','Penultimo pago',1,'2026-05-14 21:07:35'),(7,12,2,450.00,'Efectivo','Adelanto','Boleta','B-000006','',1,'2026-05-16 15:26:14'),(8,15,4,100.00,'Efectivo','Adelanto','Boleta','B-000007','',1,'2026-05-16 16:11:53'),(9,15,4,100.00,'Efectivo','Saldo Final','Boleta','B-000008','Prueba hora local',1,'2026-05-16 16:43:22'),(10,23,4,50.00,'Efectivo','Parcial','Boleta','B-000009','Prueba cronograma',1,'2026-05-20 00:27:22'),(11,24,4,20.00,'Efectivo','Parcial','Boleta','B-000010','',1,'2026-05-20 00:31:16'),(12,24,4,20.00,'Efectivo','Parcial','Boleta','B-000011','',1,'2026-05-20 00:31:47'),(13,24,4,20.00,'Efectivo','Parcial','Boleta','B-000012','',1,'2026-05-20 00:38:18'),(14,24,4,250.00,'Efectivo','Parcial','Boleta','B-000013','',1,'2026-05-20 05:50:32'),(15,30,5,50.00,'Efectivo','Saldo Final','Boleta','B-000014','',3,'2026-05-21 00:16:18');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presupuesto_items`
--

DROP TABLE IF EXISTS `presupuesto_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presupuesto_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `presupuesto_id` int(11) NOT NULL,
  `tratamiento_id` int(11) DEFAULT NULL,
  `diente_numero` int(11) DEFAULT NULL,
  `descripcion` varchar(255) NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT '1',
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT '0.00',
  `precio_ajustado` decimal(10,2) DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fecha_realizado` date DEFAULT NULL COMMENT 'Fecha en que se ejecutó el tratamiento',
  `realizado` tinyint(1) DEFAULT '0' COMMENT '0=pendiente, 1=realizado',
  PRIMARY KEY (`id`),
  KEY `presupuesto_id` (`presupuesto_id`),
  KEY `tratamiento_id` (`tratamiento_id`),
  CONSTRAINT `presupuesto_items_ibfk_1` FOREIGN KEY (`presupuesto_id`) REFERENCES `presupuestos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presupuesto_items_ibfk_2` FOREIGN KEY (`tratamiento_id`) REFERENCES `catalogo_tratamientos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presupuesto_items`
--

LOCK TABLES `presupuesto_items` WRITE;
/*!40000 ALTER TABLE `presupuesto_items` DISABLE KEYS */;
INSERT INTO `presupuesto_items` VALUES (2,5,16,18,'Curacion por Caries (Pieza #18)',1,80.00,NULL,80.00,NULL,0),(3,5,2,17,'Resina Compuesta (Pieza #17)',1,120.00,NULL,120.00,NULL,0),(4,5,8,42,'Extraccion Quirurgica',1,150.00,NULL,150.00,NULL,0),(5,5,16,11,'Curacion por Caries (Pieza #11)',1,80.00,NULL,80.00,NULL,0),(6,5,3,12,'Corona Dental (Pieza #12)',1,350.00,NULL,350.00,NULL,0),(7,5,13,2,'Incrustacion Dental (Pieza #2)',1,250.00,NULL,250.00,NULL,0),(8,7,15,NULL,'Radiografia Panoramica',1,40.00,40.00,40.00,NULL,0),(9,9,8,NULL,'Extraccion Quirurgica',1,120.00,NULL,120.00,NULL,0),(10,10,14,NULL,'Radiografia Periapical',1,15.00,NULL,15.00,NULL,0),(15,11,3,12,'Corona Dental (Pieza #12)',1,350.00,NULL,350.00,NULL,0),(16,11,2,13,'Resina Compuesta (Pieza #13)',1,120.00,240.00,240.00,NULL,0),(17,11,9,NULL,'Limpieza Dental',1,50.00,NULL,50.00,NULL,0),(18,12,12,NULL,'Ortodoncia - Brackets',1,2500.00,NULL,2500.00,NULL,0),(22,15,10,NULL,'Blanqueamiento',1,200.00,NULL,200.00,'2026-05-19',1),(25,19,10,NULL,'Blanqueamiento',1,200.00,100.00,100.00,NULL,0),(27,21,12,NULL,'Ortodoncia - Brackets',1,2500.00,400.00,400.00,NULL,0),(30,19,8,NULL,'Extraccion Quirurgica',1,180.00,NULL,180.00,NULL,0),(31,23,16,NULL,'Curacion por Caries',1,80.00,NULL,80.00,NULL,0),(32,24,16,43,'Curacion por Caries (Pieza #43)',1,80.00,NULL,80.00,'2026-05-19',1),(33,24,12,NULL,'Ortodoncia - Brackets',1,2500.00,NULL,2500.00,NULL,0),(34,28,6,NULL,'Incrustacion Dental',1,250.00,300.00,300.00,NULL,0),(35,29,68,NULL,'Cepillo Vitis Junior Gel',2,20.00,NULL,40.00,NULL,0),(36,30,31,NULL,'Extraccion Incisivo',1,50.00,NULL,50.00,NULL,0);
/*!40000 ALTER TABLE `presupuesto_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `presupuestos`
--

DROP TABLE IF EXISTS `presupuestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `presupuestos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vigencia` date DEFAULT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento_porcentaje` decimal(5,2) DEFAULT '0.00',
  `descuento_monto` decimal(10,2) DEFAULT '0.00',
  `total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `monto_pagado` decimal(10,2) DEFAULT '0.00',
  `saldo_pendiente` decimal(10,2) DEFAULT '0.00',
  `estado` enum('Borrador','Enviado','Aprobado','Rechazado','Vencido') DEFAULT 'Borrador',
  `notas` text,
  `fecha_creacion` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `presupuestos_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `presupuestos_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `presupuestos`
--

LOCK TABLES `presupuestos` WRITE;
/*!40000 ALTER TABLE `presupuestos` DISABLE KEYS */;
INSERT INTO `presupuestos` VALUES (5,1,1,'2026-05-09','2026-06-08',1030.00,15.00,154.50,875.50,875.50,0.00,'Aprobado','Presupuesto generado automÃ¡ticamente desde odontograma','2026-05-09 14:46:21'),(7,1,1,'2026-05-09','2026-06-08',40.00,0.00,0.00,40.00,30.00,10.00,'Aprobado','','2026-05-09 14:52:09'),(9,1,1,'2026-05-11','2026-06-10',120.00,0.00,0.00,120.00,0.00,0.00,'Aprobado','','2026-05-11 18:34:27'),(10,1,1,'2026-05-11','2026-06-10',15.00,0.00,0.00,15.00,0.00,0.00,'Aprobado','','2026-05-11 18:39:06'),(11,1,1,'2026-05-13','2026-06-12',640.00,8.00,51.20,588.80,288.80,300.00,'Aprobado','Presupuesto generado automÃ¡ticamente desde odontograma','2026-05-13 22:59:40'),(12,2,1,'2026-05-15','2026-06-14',2500.00,15.00,375.00,2125.00,450.00,1675.00,'Aprobado','','2026-05-15 23:40:44'),(15,4,1,'2026-05-16','2026-06-15',200.00,0.00,0.00,200.00,200.00,0.00,'Aprobado','','2026-05-16 16:09:03'),(19,4,1,'2026-05-16','2026-06-15',280.00,0.00,0.00,280.00,0.00,0.00,'Aprobado','Presupuesto generado automáticamente desde odontograma','2026-05-16 19:44:09'),(21,1,1,'2026-05-16','2026-06-15',400.00,0.00,0.00,400.00,0.00,0.00,'Borrador','','2026-05-16 20:15:22'),(23,4,1,'2026-05-19','2026-06-18',80.00,0.00,0.00,80.00,50.00,30.00,'Aprobado','','2026-05-20 00:26:22'),(24,4,1,'2026-05-19','2026-06-18',2580.00,0.00,0.00,2580.00,310.00,2270.00,'Aprobado','Presupuesto generado automáticamente desde odontograma','2026-05-20 00:31:03'),(28,4,1,'2026-05-20','2026-06-19',300.00,0.00,0.00,300.00,0.00,0.00,'Enviado','','2026-05-20 05:27:41'),(29,3,1,'2026-05-20','2026-06-19',40.00,0.00,0.00,40.00,0.00,0.00,'Borrador','','2026-05-20 23:25:36'),(30,5,3,'2026-05-20','2026-06-19',50.00,0.00,0.00,50.00,50.00,0.00,'Aprobado','','2026-05-21 00:15:43');
/*!40000 ALTER TABLE `presupuestos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recetas`
--

DROP TABLE IF EXISTS `recetas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recetas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diagnostico` varchar(255) DEFAULT '',
  `contenido` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `paciente_id` (`paciente_id`),
  KEY `doctor_id` (`doctor_id`),
  CONSTRAINT `recetas_ibfk_1` FOREIGN KEY (`paciente_id`) REFERENCES `pacientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `recetas_ibfk_2` FOREIGN KEY (`doctor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recetas`
--

LOCK TABLES `recetas` WRITE;
/*!40000 ALTER TABLE `recetas` DISABLE KEYS */;
INSERT INTO `recetas` VALUES (2,4,1,'2026-05-20 03:28:06','','123123\n475erh'),(3,4,1,'2026-05-20 03:36:42','','123123\nwewgdvs'),(5,4,1,'2026-05-20 03:45:10','','123123'),(6,4,1,'2026-05-20 03:45:34','','12112'),(7,1,1,'2026-05-20 04:02:08','','1234');
/*!40000 ALTER TABLE `recetas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `signos_vitales`
--

DROP TABLE IF EXISTS `signos_vitales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `signos_vitales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paciente_id` int(11) NOT NULL,
  `cita_id` int(11) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `presion_arterial` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Ej: 120/80',
  `pulso` int(11) DEFAULT NULL COMMENT 'latidos/min',
  `frecuencia_cardiaca` int(11) DEFAULT NULL COMMENT 'lat/min',
  `frecuencia_resp` int(11) DEFAULT NULL COMMENT 'resp/min',
  `temperatura` decimal(4,1) DEFAULT NULL COMMENT 'grados Celsius',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `registrado_por` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_sv_paciente` (`paciente_id`),
  KEY `fk_sv_cita` (`cita_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `signos_vitales`
--

LOCK TABLES `signos_vitales` WRITE;
/*!40000 ALTER TABLE `signos_vitales` DISABLE KEYS */;
INSERT INTO `signos_vitales` VALUES (1,4,NULL,'2026-05-19 18:59:24','120/80',72,72,16,36.5,'Prueba0003',1),(2,4,NULL,'2026-05-19 18:59:39','120/80',70,70,15,30.0,'Prueba004',1),(3,4,NULL,'2026-05-19 19:39:31','110/80',65,65,16,35.0,'Prueba0005',1),(4,4,NULL,'2026-05-19 23:17:55','110/80',65,65,16,35.0,NULL,1),(5,5,NULL,'2026-05-20 18:36:41','120/70',70,85,15,35.0,NULL,1);
/*!40000 ALTER TABLE `signos_vitales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('Admin','Recepcionista','Dentista') DEFAULT 'Dentista',
  `fecha_registro` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `colegiatura` varchar(50) DEFAULT NULL,
  `estado_activo` tinyint(1) DEFAULT '1',
  `intentos_fallidos` int(11) DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador General','admin','$2y$10$V6dsIjYumwaE.LKlMWwprOsNalEYq/RBGBZdMRO0v/U.DxQRQbr/i','Admin','2026-05-06 03:49:16',NULL,1,0,NULL),(2,'Yerson Marin Lopez','yomarin','$2y$10$MovTJJIAhgpD7GJLElRaJ.2PI5oAr2eNH57O1ve8F8eSCah1NmGoq','Dentista','2026-05-20 23:54:53','',1,0,NULL),(3,'recepcionista01','recepcion','$2y$10$6IPE6zampJm/2elKSuiEPuoUOR/NzhqyCamc.Hxp5B1CNS5yjggg6','Recepcionista','2026-05-21 00:00:43','',1,0,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-01 15:09:32
