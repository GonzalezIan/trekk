
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
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `certificados_medicos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `certificados_medicos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `participante_id` bigint(20) unsigned NOT NULL,
  `fecha_emision` date DEFAULT NULL,
  `estado` varchar(30) NOT NULL DEFAULT 'PENDIENTE_DE_VERIFICACION',
  `archivo` varchar(255) DEFAULT NULL,
  `fecha_de_carga` datetime DEFAULT NULL,
  `fecha_de_vencimiento` date DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `motivo_de_invalidez` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `certificados_medicos_participante_id_foreign` (`participante_id`),
  CONSTRAINT `certificados_medicos_participante_id_foreign` FOREIGN KEY (`participante_id`) REFERENCES `participantes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `certificados_medicos` WRITE;
/*!40000 ALTER TABLE `certificados_medicos` DISABLE KEYS */;
INSERT INTO `certificados_medicos` VALUES (1,9,'2026-10-12','VALIDO','certificados/entrega-9.pdf','2026-10-12 19:40:00','2026-11-11',NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,10,'2026-10-12','VALIDO','certificados/entrega-10.pdf','2026-10-12 19:40:00','2026-11-11',NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,11,'2026-10-12','VALIDO','certificados/entrega-11.pdf','2026-10-14 08:25:00','2026-11-11',NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(4,27,NULL,'PENDIENTE_DE_VERIFICACION','certificados/entrega-27.pdf','2026-11-06 21:00:00',NULL,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(5,28,'2026-11-05','VALIDO','certificados/entrega-28.pdf','2026-11-03 09:10:00','2026-12-05',NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(6,29,NULL,'INVALIDO','certificados/entrega-29.pdf','2026-11-05 14:35:00',NULL,NULL,'El archivo no permite leer la firma del médico.','2026-11-09 18:40:35','2026-11-09 18:40:35'),(7,34,NULL,'PENDIENTE_DE_VERIFICACION','certificados/entrega-34.pdf','2026-11-07 12:00:00',NULL,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(8,35,NULL,'PENDIENTE_DE_VERIFICACION','certificados/entrega-35.pdf','2026-11-07 22:40:00',NULL,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35');
/*!40000 ALTER TABLE `certificados_medicos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `dni` varchar(15) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clientes_user_id_unique` (`user_id`),
  UNIQUE KEY `clientes_dni_unique` (`dni`),
  CONSTRAINT `clientes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,2,'11223344','Juan','Pérez','1990-05-15','+541122334455','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,3,'30111222','Ana','Fernández','1985-03-22','+541155550101','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,4,'33222333','Lucas','Romero','1992-08-09','+541155550102','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,5,'35333444','Sofía','Herrera','1994-11-30','+541155550103','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,6,'28444555','Martín','Acosta','1981-01-17','+541155550104','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,7,'37555666','Valentina','Ríos','1996-06-05','+541155550105','2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,8,'31666777','Diego','Molina','1987-09-12','+541155550106','2026-11-09 18:40:34','2026-11-09 18:40:34'),(8,9,'39777888','Camila','Sosa','1999-02-25','+541155550107','2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `conformidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conformidades` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reserva_id` bigint(20) unsigned NOT NULL,
  `valoracion` tinyint(3) unsigned DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conformidades_reserva_id_foreign` (`reserva_id`),
  CONSTRAINT `conformidades_reserva_id_foreign` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `conformidades` WRITE;
/*!40000 ALTER TABLE `conformidades` DISABLE KEYS */;
INSERT INTO `conformidades` VALUES (1,1,5,'Paisajes increíbles y el guía estuvo atento todo el recorrido.','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,2,4,'Muy linda experiencia, aunque el último tramo es exigente.','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,3,5,'La mejor caminata que hice en el sur.','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,5,3,'El refugio estaba lleno y la cena se demoró bastante.','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,6,4,'Buena organización y grupo muy agradable.','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,7,4,'Hicimos cumbre con buen clima. Muy recomendable.','2026-11-09 18:40:35','2026-11-09 18:40:35'),(7,8,5,'Guías muy profesionales en todo momento.','2026-11-09 18:40:35','2026-11-09 18:40:35');
/*!40000 ALTER TABLE `conformidades` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `convenios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `convenios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `refugio_id` bigint(20) unsigned NOT NULL,
  `nombre_convenio` varchar(100) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `camas_totales` int(10) unsigned NOT NULL,
  `camas_disponibles` int(10) unsigned NOT NULL,
  `precio_cama` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `convenios_refugio_id_nombre_convenio_unique` (`refugio_id`,`nombre_convenio`),
  CONSTRAINT `convenios_refugio_id_foreign` FOREIGN KEY (`refugio_id`) REFERENCES `refugios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `convenios` WRITE;
/*!40000 ALTER TABLE `convenios` DISABLE KEYS */;
INSERT INTO `convenios` VALUES (1,1,'Convenio temporada 2026 - Refugio Frey','2026-09-01','2027-04-30',24,20,45.00,'2026-11-09 18:40:34','2026-11-09 18:40:35'),(2,2,'Convenio temporada 2026 - Refugio Otto Meiling','2026-09-01','2027-04-30',20,18,60.00,'2026-11-09 18:40:34','2026-11-09 18:40:35'),(3,3,'Convenio temporada 2026 - Refugio Base Lanín','2026-09-01','2027-04-30',16,12,50.00,'2026-11-09 18:40:34','2026-11-09 18:40:35'),(4,4,'Convenio temporada 2026 - Refugio Plaza de Mulas','2026-09-01','2027-04-30',20,20,90.00,'2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `convenios` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `empleados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `dni` varchar(15) NOT NULL,
  `categoria` varchar(50) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `fecha_nacimiento` date DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVO',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleados_dni_unique` (`dni`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `empleados` WRITE;
/*!40000 ALTER TABLE `empleados` DISABLE KEYS */;
INSERT INTO `empleados` VALUES (1,'25100200','GUIA','Pedro','Quiroga','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,'27200300','GUIA','Elena','Paz','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'29300400','GUIA','Tomás','Benítez','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'32400500','GUIA','Rocío','Vega','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,'34500600','ASISTENTE','Nicolás','Ibarra','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,'36600700','ASISTENTE','Julieta','Campos','1985-01-01','ACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,'26700800','GUIA','Ramiro','Luna','1985-01-01','INACTIVO','2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `empleados` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `empleados_asignados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `empleados_asignados` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salida_programada_id` bigint(20) unsigned NOT NULL,
  `empleado_id` bigint(20) unsigned NOT NULL,
  `rol` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `empleados_asignados_salida_programada_id_empleado_id_unique` (`salida_programada_id`,`empleado_id`),
  KEY `empleados_asignados_empleado_id_foreign` (`empleado_id`),
  CONSTRAINT `empleados_asignados_empleado_id_foreign` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`),
  CONSTRAINT `empleados_asignados_salida_programada_id_foreign` FOREIGN KEY (`salida_programada_id`) REFERENCES `salidas_programadas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `empleados_asignados` WRITE;
/*!40000 ALTER TABLE `empleados_asignados` DISABLE KEYS */;
INSERT INTO `empleados_asignados` VALUES (1,1,1,'GUIA'),(2,2,2,'GUIA'),(3,2,5,'ASISTENTE'),(4,3,3,'GUIA'),(5,3,6,'ASISTENTE'),(6,4,4,'GUIA'),(7,5,1,'GUIA'),(8,5,5,'ASISTENTE'),(9,6,1,'GUIA'),(10,7,2,'GUIA'),(11,8,3,'GUIA'),(12,8,6,'ASISTENTE'),(13,9,1,'GUIA'),(14,9,5,'ASISTENTE'),(15,10,2,'GUIA'),(16,10,6,'ASISTENTE'),(17,11,4,'GUIA'),(18,12,4,'GUIA'),(19,12,5,'ASISTENTE'),(20,13,1,'GUIA');
/*!40000 ALTER TABLE `empleados_asignados` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `equipamientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipamientos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_alquiler` decimal(10,2) NOT NULL,
  `stock` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `equipamientos` WRITE;
/*!40000 ALTER TABLE `equipamientos` DISABLE KEYS */;
INSERT INTO `equipamientos` VALUES (1,'Carpa para dos personas','Carpa de alta montaña, doble techo.',18.00,60,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,'Bolsa de dormir','Temperatura de confort hasta -10 °C.',9.00,60,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'Bastones de trekking','Par de bastones telescópicos.',5.00,60,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'Crampones','Crampones de acero de doce puntas.',12.00,40,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,'Casco de montaña','Casco homologado para escalada.',6.00,40,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,'Mochila de 60 litros','Mochila con armazón interno.',8.00,40,'2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `equipamientos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `equipos_alquilados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipos_alquilados` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reserva_id` bigint(20) unsigned NOT NULL,
  `equipamiento_id` bigint(20) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipos_alquilados_reserva_id_equipamiento_id_unique` (`reserva_id`,`equipamiento_id`),
  KEY `equipos_alquilados_equipamiento_id_foreign` (`equipamiento_id`),
  CONSTRAINT `equipos_alquilados_equipamiento_id_foreign` FOREIGN KEY (`equipamiento_id`) REFERENCES `equipamientos` (`id`),
  CONSTRAINT `equipos_alquilados_reserva_id_foreign` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `equipos_alquilados` WRITE;
/*!40000 ALTER TABLE `equipos_alquilados` DISABLE KEYS */;
INSERT INTO `equipos_alquilados` VALUES (1,10,3,2);
/*!40000 ALTER TABLE `equipos_alquilados` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `equipos_prestados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `equipos_prestados` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salida_programada_id` bigint(20) unsigned NOT NULL,
  `equipamiento_id` bigint(20) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipos_prestados_salida_programada_id_equipamiento_id_unique` (`salida_programada_id`,`equipamiento_id`),
  KEY `equipos_prestados_equipamiento_id_foreign` (`equipamiento_id`),
  CONSTRAINT `equipos_prestados_equipamiento_id_foreign` FOREIGN KEY (`equipamiento_id`) REFERENCES `equipamientos` (`id`),
  CONSTRAINT `equipos_prestados_salida_programada_id_foreign` FOREIGN KEY (`salida_programada_id`) REFERENCES `salidas_programadas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `equipos_prestados` WRITE;
/*!40000 ALTER TABLE `equipos_prestados` DISABLE KEYS */;
INSERT INTO `equipos_prestados` VALUES (1,1,3,12),(2,2,2,10),(3,3,4,8),(4,3,5,8),(5,5,3,12),(6,8,4,8),(7,8,5,8),(8,9,2,10),(9,10,4,8),(10,10,5,8),(11,12,2,6),(12,12,6,6),(13,13,2,10);
/*!40000 ALTER TABLE `equipos_prestados` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `excursiones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `excursiones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `dificultad` varchar(20) NOT NULL,
  `duracion` smallint(5) unsigned NOT NULL DEFAULT 1,
  `recorrido` text DEFAULT NULL,
  `etapas` text DEFAULT NULL,
  `experiencia_requerida` varchar(100) DEFAULT NULL,
  `acepta_menores` tinyint(1) NOT NULL DEFAULT 0,
  `equipamiento_obligatorio` text DEFAULT NULL,
  `punto_de_encuentro` varchar(255) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `requiere_certificado_medico` tinyint(1) NOT NULL DEFAULT 0,
  `estado` varchar(20) NOT NULL DEFAULT 'ACTIVA',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `excursiones` WRITE;
/*!40000 ALTER TABLE `excursiones` DISABLE KEYS */;
INSERT INTO `excursiones` VALUES (1,'Laguna de los Tres','Caminata clásica hasta el mirador del Fitz Roy.','MEDIO',1,'El Chaltén - Río Blanco - Laguna de los Tres','Ascenso por el bosque, cruce del río y tramo final de piedra.','NO',1,'Calzado de trekking, abrigo impermeable y dos litros de agua.','Centro de visitantes de El Chaltén',NULL,0,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,'Travesía al Refugio Frey','Dos días por el cordón del Cerro Catedral con noche en refugio.','MEDIO',2,'Villa Catedral - Arroyo Van Titter - Refugio Frey','Día uno: ascenso al refugio. Día dos: laguna Schmoll y regreso.','NO',1,'Bolsa de dormir, linterna frontal y abrigo.','Base del Cerro Catedral, Bariloche',NULL,0,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'Ascenso al Volcán Lanín','Ascenso técnico a la cumbre del volcán por la ruta normal.','ALTA',3,'Tromen - Espina de Pescado - Refugio Base - Cumbre','Aclimatación, noche en altura e intento de cumbre.','SI',0,'Botas rígidas, piquetas y ropa de alta montaña.','Paso Tromen, Neuquén',NULL,1,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'Travesía Cerro Tronador','Tres días entre glaciares con noches en el Refugio Otto Meiling.','ALTA',3,'Pampa Linda - Paso de las Nubes - Refugio Otto Meiling','Ascenso al refugio, escuela de hielo y regreso.','SI',0,'Botas rígidas, guantes y anteojos de glaciar.','Guardaparque de Pampa Linda',NULL,1,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,'Laguna Esmeralda','Sendero corto entre turberas con vista al valle de Tierra Mayor.','BAJA',1,'Valle de Lasifashaj - Turbal - Laguna Esmeralda','Ida y vuelta por el mismo sendero.','NO',1,'Botas impermeables y abrigo.','Ruta 3, kilómetro 3030, Ushuaia',NULL,0,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,'Quebrada de las Señoritas','Paseo por los cañones rojizos de la Quebrada de Humahuaca.','BAJA',1,'Uquía - Cañón Principal - Mirador','Recorrido circular por el cañón.','NO',1,'Protector solar, sombrero y agua.','Plaza de Uquía, Jujuy',NULL,0,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,'Trekking a Plaza de Mulas','Cinco días por la quebrada de Horcones hasta el campamento base.','ALTA',5,'Horcones - Confluencia - Plaza de Mulas','Aclimatación en Confluencia y ascenso al campamento base.','SI',0,'Ropa de alta montaña y bolsa de dormir de -20 °C.','Portada de Horcones, Mendoza',NULL,1,'ACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(8,'Cañón del Atuel','Caminata por el cañón. Fuera de temporada.','BAJA',1,'Valle Grande - Cañón del Atuel','Recorrido lineal por la costa del río.','NO',1,'Calzado cómodo y agua.','Valle Grande, San Rafael',NULL,0,'INACTIVA','2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `excursiones` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_10_12_190415_create_clientes_table',1),(5,'2026_10_12_190416_create_empleados_table',1),(6,'2026_10_12_190418_create_excursiones_table',1),(7,'2026_10_12_190420_create_equipamientos_table',1),(8,'2026_10_12_190421_create_servicios_table',1),(9,'2026_10_12_190423_create_refugios_table',1),(10,'2026_10_12_190424_create_convenios_table',1),(11,'2026_10_12_190426_create_salidas_programadas_table',1),(12,'2026_10_12_190427_create_paquetes_table',1),(13,'2026_10_12_190429_create_reservas_table',1),(14,'2026_10_12_190431_create_participantes_table',1),(15,'2026_10_12_190432_create_certificados_medicos_table',1),(16,'2026_10_12_190434_create_conformidades_table',1),(17,'2026_10_12_190436_create_pagos_table',1),(18,'2026_10_12_190437_create_equipos_alquilados_table',1),(19,'2026_10_12_190439_create_equipos_prestados_table',1),(20,'2026_10_12_190441_create_empleados_asignados_table',1),(21,'2026_10_12_190442_create_servicios_incluidos_table',1),(22,'2026_10_19_055619_add_rol_to_users_table',1),(23,'2026_10_19_200000_cambiar_duracion_a_dias_en_excursiones',1),(24,'2026_10_22_120000_ajustar_estado_en_certificados_medicos',1),(25,'2026_10_22_130000_estados_por_defecto_y_refugio_opcional',1),(26,'2026_10_23_212020_add_usuario_to_users_table',1),(27,'2026_10_24_100000_agregar_punto_de_encuentro_a_excursiones',1),(28,'2026_10_24_120000_add_cantidad_participantes_to_reservas_table',1),(29,'2026_11_09_120000_agregar_imagen_a_excursiones',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pagos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reserva_id` bigint(20) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` varchar(5) NOT NULL DEFAULT 'ARS',
  `medio_pago` varchar(30) NOT NULL,
  `fecha_hora_pago` datetime NOT NULL,
  `tipo_pago` varchar(20) DEFAULT NULL,
  `resultado_transaccion` varchar(30) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pagos_reserva_id_foreign` (`reserva_id`),
  CONSTRAINT `pagos_reserva_id_foreign` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `pagos` WRITE;
/*!40000 ALTER TABLE `pagos` DISABLE KEYS */;
INSERT INTO `pagos` VALUES (1,1,210.00,'USD','Tarjeta de crédito','2026-09-25 18:10:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,2,105.00,'USD','Tarjeta de crédito','2026-09-28 10:45:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,3,105.00,'USD','Tarjeta de crédito','2026-10-01 21:20:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,4,175.00,'USD','Tarjeta de crédito','2026-10-02 12:30:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,5,350.00,'USD','Tarjeta de crédito','2026-10-03 09:15:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,6,175.00,'USD','Tarjeta de crédito','2026-10-05 16:00:00','TOTAL','APROBADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,7,760.00,'USD','Tarjeta de crédito','2026-10-12 19:40:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(8,8,380.00,'USD','Tarjeta de crédito','2026-10-14 08:25:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(9,9,70.00,'USD','Tarjeta de crédito','2026-11-02 11:05:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(10,10,415.00,'USD','Tarjeta de crédito','2026-11-03 20:15:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(11,11,330000.00,'ARS','Mercado Pago','2026-11-04 13:50:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(12,12,110.00,'USD','Tarjeta de crédito','2026-11-05 17:30:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(13,13,85.00,'USD','Tarjeta de crédito','2026-11-01 15:00:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(14,14,170.00,'USD','Tarjeta de crédito','2026-11-06 10:20:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(15,15,280.00,'USD','Tarjeta de crédito','2026-11-02 18:45:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(16,16,780.00,'USD','Tarjeta de crédito','2026-11-06 21:00:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(17,17,390.00,'USD','Tarjeta de crédito','2026-11-03 09:10:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(18,18,390.00,'USD','Tarjeta de crédito','2026-11-05 14:35:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(19,19,180.00,'USD','Tarjeta de crédito','2026-11-09 15:40:35','SEÑA','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(20,20,360.00,'USD','Tarjeta de crédito','2026-11-04 19:05:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(21,21,430.00,'USD','Tarjeta de crédito','2026-11-07 12:00:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(22,22,430.00,'USD','Tarjeta de crédito','2026-11-07 22:40:00','TOTAL','APROBADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(23,23,90.00,'USD','Tarjeta de crédito','2026-11-05 11:30:00','TOTAL','APROBADA','2026-11-09 18:40:36','2026-11-09 18:40:36');
/*!40000 ALTER TABLE `pagos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `paquetes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paquetes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `salida_programada_id` bigint(20) unsigned NOT NULL,
  `refugio_id` bigint(20) unsigned DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_calculado` decimal(10,2) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'DISPONIBLE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `paquetes_salida_programada_id_foreign` (`salida_programada_id`),
  KEY `paquetes_refugio_id_foreign` (`refugio_id`),
  CONSTRAINT `paquetes_refugio_id_foreign` FOREIGN KEY (`refugio_id`) REFERENCES `refugios` (`id`),
  CONSTRAINT `paquetes_salida_programada_id_foreign` FOREIGN KEY (`salida_programada_id`) REFERENCES `salidas_programadas` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `paquetes` WRITE;
/*!40000 ALTER TABLE `paquetes` DISABLE KEYS */;
INSERT INTO `paquetes` VALUES (1,1,NULL,'Laguna de los Tres esencial','Incluye guía, seguimiento durante la excursión y los servicios detallados.',105.00,'DISPONIBLE','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,2,1,'Frey con noche en refugio','Incluye guía, seguimiento durante la excursión y los servicios detallados.',175.00,'DISPONIBLE','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,3,3,'Lanín con base en refugio','Incluye guía, seguimiento durante la excursión y los servicios detallados.',380.00,'DISPONIBLE','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,4,NULL,'Señoritas clásico','Incluye guía, seguimiento durante la excursión y los servicios detallados.',70.00,'CANCELADO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(5,5,NULL,'Tres esencial','Incluye guía, seguimiento durante la excursión y los servicios detallados.',110.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(6,5,NULL,'Tres con traslado','Incluye guía, seguimiento durante la excursión y los servicios detallados.',135.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(7,6,NULL,'Esmeralda con traslado','Incluye guía, seguimiento durante la excursión y los servicios detallados.',85.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(8,7,NULL,'Señoritas con almuerzo','Incluye guía, seguimiento durante la excursión y los servicios detallados.',70.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(9,8,3,'Lanín cumbre','Incluye guía, seguimiento durante la excursión y los servicios detallados.',390.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(10,9,1,'Frey con refugio','Incluye guía, seguimiento durante la excursión y los servicios detallados.',180.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(11,10,2,'Tronador con refugio','Incluye guía, seguimiento durante la excursión y los servicios detallados.',430.00,'DISPONIBLE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(12,11,NULL,'Esmeralda esencial','Incluye guía, seguimiento durante la excursión y los servicios detallados.',75.00,'DISPONIBLE','2026-11-09 18:40:36','2026-11-09 18:40:36'),(13,11,NULL,'Esmeralda con fotos','Incluye guía, seguimiento durante la excursión y los servicios detallados.',90.00,'CANCELADO','2026-11-09 18:40:36','2026-11-09 18:40:36'),(14,12,4,'Plaza de Mulas con base','Incluye guía, seguimiento durante la excursión y los servicios detallados.',1025.00,'DISPONIBLE','2026-11-09 18:40:36','2026-11-09 18:40:36'),(15,12,NULL,'Plaza de Mulas sin alojamiento','Incluye guía, seguimiento durante la excursión y los servicios detallados.',640.00,'DISPONIBLE','2026-11-09 18:40:36','2026-11-09 18:40:36');
/*!40000 ALTER TABLE `paquetes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `participantes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `participantes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reserva_id` bigint(20) unsigned NOT NULL,
  `dni` varchar(15) NOT NULL,
  `nombre` varchar(80) NOT NULL,
  `apellido` varchar(80) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `estado_requisito_medico` varchar(50) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `participantes_reserva_id_dni_unique` (`reserva_id`,`dni`),
  CONSTRAINT `participantes_reserva_id_foreign` FOREIGN KEY (`reserva_id`) REFERENCES `reservas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `participantes` WRITE;
/*!40000 ALTER TABLE `participantes` DISABLE KEYS */;
INSERT INTO `participantes` VALUES (1,1,'11223344','Juan','Pérez','1990-05-15','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,1,'41000111','Laura','Pérez','1992-07-20','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,2,'30111222','Ana','Fernández','1985-03-22','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,3,'33222333','Lucas','Romero','1992-08-09','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,4,'11223344','Juan','Pérez','1990-05-15','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,5,'35333444','Sofía','Herrera','1994-11-30','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,5,'42000222','Bruno','Herrera','1991-04-11','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(8,6,'28444555','Martín','Acosta','1981-01-17','NO_REQUERIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(9,7,'37555666','Valentina','Ríos','1996-06-05','CUMPLIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(10,7,'43000333','Ignacio','Ríos','1993-12-02','CUMPLIDO','2026-11-09 18:40:34','2026-11-09 18:40:34'),(11,8,'31666777','Diego','Molina','1987-09-12','CUMPLIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(12,9,'33222333','Lucas','Romero','1992-08-09','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(13,10,'11223344','Juan','Pérez','1990-05-15','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(14,10,'41000111','Laura','Pérez','1992-07-20','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(15,10,'58000444','Tomás','Pérez','2012-03-14','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(16,11,'30111222','Ana','Fernández','1985-03-22','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(17,11,'44000555','Pablo','Fernández','1983-10-08','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(18,12,'31666777','Diego','Molina','1987-09-12','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(19,13,'11223344','Juan','Pérez','1990-05-15','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(20,14,'39777888','Camila','Sosa','1999-02-25','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(21,14,'45000666','Federico','Sosa','1998-05-19','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(22,15,'28444555','Martín','Acosta','1981-01-17','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(23,15,'46000777','Paula','Acosta','1983-02-14','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(24,15,'46000778','Hernán','Acosta','1979-11-03','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(25,15,'46000779','Carla','Acosta','1986-08-27','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(26,16,'11223344','Juan','Pérez','1990-05-15','PENDIENTE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(27,16,'41000111','Laura','Pérez','1992-07-20','PENDIENTE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(28,17,'28444555','Martín','Acosta','1981-01-17','CUMPLIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(29,18,'33222333','Lucas','Romero','1992-08-09','PENDIENTE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(30,19,'11223344','Juan','Pérez','1990-05-15','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(31,19,'41000111','Laura','Pérez','1992-07-20','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(32,20,'35333444','Sofía','Herrera','1994-11-30','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(33,20,'42000222','Bruno','Herrera','1991-04-11','NO_REQUERIDO','2026-11-09 18:40:35','2026-11-09 18:40:35'),(34,21,'37555666','Valentina','Ríos','1996-06-05','PENDIENTE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(35,22,'31666777','Diego','Molina','1987-09-12','PENDIENTE','2026-11-09 18:40:35','2026-11-09 18:40:35'),(36,23,'30111222','Ana','Fernández','1985-03-22','NO_REQUERIDO','2026-11-09 18:40:36','2026-11-09 18:40:36');
/*!40000 ALTER TABLE `participantes` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `refugios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `refugios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `ubicacion` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `refugios` WRITE;
/*!40000 ALTER TABLE `refugios` DISABLE KEYS */;
INSERT INTO `refugios` VALUES (1,'Refugio Frey','Refugio de montaña a orillas de la laguna Toncek.','Cerro Catedral, Río Negro','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,'Refugio Otto Meiling','Refugio entre los glaciares del Tronador.','Pampa Linda, Río Negro','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'Refugio Base Lanín','Refugio en la ruta normal del volcán.','Tromen, Neuquén','2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'Refugio Plaza de Mulas','Campamento base del Aconcagua.','Las Heras, Mendoza','2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `refugios` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `reservas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reservas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cliente_id` bigint(20) unsigned NOT NULL,
  `paquete_id` bigint(20) unsigned NOT NULL,
  `cantidad_participantes` smallint(5) unsigned NOT NULL DEFAULT 0,
  `fecha_hora_registro` datetime NOT NULL DEFAULT current_timestamp(),
  `estado` varchar(20) NOT NULL,
  `situacion_de_pago` varchar(30) NOT NULL,
  `importe_total` decimal(10,2) NOT NULL,
  `motivo_cancelacion` text DEFAULT NULL,
  `fecha_hora_vencimiento` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservas_cliente_id_foreign` (`cliente_id`),
  KEY `reservas_paquete_id_foreign` (`paquete_id`),
  CONSTRAINT `reservas_cliente_id_foreign` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `reservas_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `reservas` WRITE;
/*!40000 ALTER TABLE `reservas` DISABLE KEYS */;
INSERT INTO `reservas` VALUES (1,1,1,2,'2026-09-25 18:10:00','REALIZADA','PAGO_COMPLETO',210.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,2,1,1,'2026-09-28 10:45:00','REALIZADA','PAGO_COMPLETO',105.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,3,1,1,'2026-10-01 21:20:00','REALIZADA','PAGO_COMPLETO',105.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,1,2,1,'2026-10-02 12:30:00','REALIZADA','PAGO_COMPLETO',175.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,4,2,2,'2026-10-03 09:15:00','REALIZADA','PAGO_COMPLETO',350.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,5,2,1,'2026-10-05 16:00:00','REALIZADA','PAGO_COMPLETO',175.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,6,3,2,'2026-10-12 19:40:00','REALIZADA','PAGO_COMPLETO',760.00,NULL,NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(8,7,3,1,'2026-10-14 08:25:00','REALIZADA','PAGO_COMPLETO',380.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(9,3,4,1,'2026-11-02 11:05:00','CANCELADA','PAGO_COMPLETO',70.00,'Alerta meteorológica por viento Zonda en la zona.',NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(10,1,6,3,'2026-11-03 20:15:00','CONFIRMADA','PAGO_COMPLETO',415.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(11,2,5,2,'2026-11-04 13:50:00','CONFIRMADA','PAGO_COMPLETO',220.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(12,7,5,1,'2026-11-05 17:30:00','CONFIRMADA','PAGO_COMPLETO',110.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(13,1,7,1,'2026-11-01 15:00:00','CANCELADA','PAGO_COMPLETO',85.00,'No puedo viajar en esa fecha.',NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(14,8,7,2,'2026-11-06 10:20:00','CONFIRMADA','PAGO_COMPLETO',170.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(15,5,8,4,'2026-11-02 18:45:00','CONFIRMADA','PAGO_COMPLETO',280.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(16,1,9,2,'2026-11-06 21:00:00','PENDIENTE','PAGO_COMPLETO',780.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(17,5,9,1,'2026-11-03 09:10:00','CONFIRMADA','PAGO_COMPLETO',390.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(18,3,9,1,'2026-11-05 14:35:00','PENDIENTE','PAGO_COMPLETO',390.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(19,1,10,2,'2026-11-09 15:40:35','PENDIENTE','PENDIENTE_DE_PAGO_TOTAL',360.00,NULL,'2026-11-10 15:40:35','2026-11-09 18:40:35','2026-11-09 18:40:35'),(20,4,10,2,'2026-11-04 19:05:00','CONFIRMADA','PAGO_COMPLETO',360.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(21,6,11,1,'2026-11-07 12:00:00','PENDIENTE','PAGO_COMPLETO',430.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(22,7,11,1,'2026-11-07 22:40:00','PENDIENTE','PAGO_COMPLETO',430.00,NULL,NULL,'2026-11-09 18:40:35','2026-11-09 18:40:35'),(23,2,13,1,'2026-11-05 11:30:00','CANCELADA','PAGO_COMPLETO',90.00,'Cancelación del paquete: el servicio de fotografía no está disponible en esa fecha.',NULL,'2026-11-09 18:40:36','2026-11-09 18:40:36');
/*!40000 ALTER TABLE `reservas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `salidas_programadas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salidas_programadas` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `excursion_id` bigint(20) unsigned NOT NULL,
  `fecha_inicio` date NOT NULL,
  `hora_de_salida` time NOT NULL,
  `fecha_fin` date NOT NULL,
  `cupo_maximo` int(10) unsigned NOT NULL,
  `cupo_disponible` int(10) unsigned NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `motivo_cancelacion` text DEFAULT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'PROGRAMADA',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salidas_programadas_excursion_id_foreign` (`excursion_id`),
  CONSTRAINT `salidas_programadas_excursion_id_foreign` FOREIGN KEY (`excursion_id`) REFERENCES `excursiones` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `salidas_programadas` WRITE;
/*!40000 ALTER TABLE `salidas_programadas` DISABLE KEYS */;
INSERT INTO `salidas_programadas` VALUES (1,1,'2026-10-10','07:30:00','2026-10-10',12,8,90.00,NULL,'FINALIZADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,2,'2026-10-17','08:00:00','2026-10-18',10,6,110.00,NULL,'FINALIZADA','2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,3,'2026-10-30','06:30:00','2026-11-01',8,5,260.00,NULL,'FINALIZADA','2026-11-09 18:40:34','2026-11-09 18:40:35'),(4,6,'2026-11-14','09:00:00','2026-11-14',15,15,55.00,'Alerta meteorológica por viento Zonda en la zona.','CANCELADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(5,1,'2026-11-21','07:30:00','2026-11-21',12,6,95.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(6,5,'2026-11-24','09:30:00','2026-11-24',15,13,60.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(7,6,'2026-11-26','09:00:00','2026-11-26',4,0,55.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(8,3,'2026-11-27','06:30:00','2026-11-29',8,4,270.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(9,2,'2026-11-28','08:00:00','2026-11-29',10,6,115.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(10,4,'2026-12-04','07:00:00','2026-12-06',8,6,290.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(11,5,'2026-12-05','09:30:00','2026-12-05',15,15,60.00,NULL,'PROGRAMADA','2026-11-09 18:40:35','2026-11-09 18:40:35'),(12,7,'2026-12-12','06:00:00','2026-12-16',6,6,620.00,NULL,'PROGRAMADA','2026-11-09 18:40:36','2026-11-09 18:40:36'),(13,2,'2026-12-19','08:00:00','2026-12-20',10,10,120.00,NULL,'PROGRAMADA','2026-11-09 18:40:36','2026-11-09 18:40:36');
/*!40000 ALTER TABLE `salidas_programadas` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_servicio` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (1,'Traslado desde la ciudad','Ida y vuelta hasta el inicio del sendero.',25.00,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(2,'Almuerzo de montaña','Vianda caliente durante la caminata.',15.00,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'Seguro de actividad','Cobertura médica durante la excursión.',20.00,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'Registro fotográfico','Fotos de la jornada en formato digital.',30.00,'2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `servicios_incluidos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios_incluidos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `paquete_id` bigint(20) unsigned NOT NULL,
  `servicio_id` bigint(20) unsigned NOT NULL,
  `cantidad` int(10) unsigned NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `servicios_incluidos_paquete_id_servicio_id_unique` (`paquete_id`,`servicio_id`),
  KEY `servicios_incluidos_servicio_id_foreign` (`servicio_id`),
  CONSTRAINT `servicios_incluidos_paquete_id_foreign` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `servicios_incluidos_servicio_id_foreign` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `servicios_incluidos` WRITE;
/*!40000 ALTER TABLE `servicios_incluidos` DISABLE KEYS */;
INSERT INTO `servicios_incluidos` VALUES (1,1,2,1),(2,2,3,1),(3,3,3,1),(4,4,2,1),(5,5,2,1),(6,6,2,1),(7,6,1,1),(8,7,1,1),(9,8,2,1),(10,9,3,1),(11,10,3,1),(12,11,3,1),(13,12,2,1),(14,13,4,1),(15,14,3,1),(16,14,1,1),(17,15,3,1);
/*!40000 ALTER TABLE `servicios_incluidos` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `usuario` varchar(30) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(255) NOT NULL DEFAULT 'cliente',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_usuario_unique` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin Trekk','admin','admin@trekk.com','2026-11-09 18:40:33','$2y$12$0foIXAGgFSnoi8bj5AQuaeMN9mMdBdyaPKqct/pVK.Rz4S6xEA4ou','admin',NULL,'2026-11-09 18:40:33','2026-11-09 18:40:33'),(2,'Juan Pérez','juan','juan@test.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(3,'Ana Fernández','ana','ana.fernandez@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(4,'Lucas Romero','lucas','lucas.romero@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(5,'Sofía Herrera','sofia','sofia.herrera@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(6,'Martín Acosta','martin','martin.acosta@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(7,'Valentina Ríos','valentina','valentina.rios@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(8,'Diego Molina','diego','diego.molina@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34'),(9,'Camila Sosa','camila','camila.sosa@example.com','2026-11-09 18:40:34','$2y$12$iPwZoV62dI1M4AHNxnpdreOwXX3lVa47upOPca20q5WK0WW1JiJBK','cliente',NULL,'2026-11-09 18:40:34','2026-11-09 18:40:34');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

