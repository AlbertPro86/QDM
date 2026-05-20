-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: crm_quantun
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

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
-- Table structure for table `actividad_log`
--

DROP TABLE IF EXISTS `actividad_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actividad_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `entidad` varchar(50) NOT NULL,
  `entidad_id` int(11) DEFAULT NULL,
  `detalles` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_actividad_usuario` (`usuario_id`),
  KEY `idx_actividad_entidad` (`entidad`,`entidad_id`),
  CONSTRAINT `actividad_log_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=263 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actividad_log`
--

LOCK TABLES `actividad_log` WRITE;
/*!40000 ALTER TABLE `actividad_log` DISABLE KEYS */;
INSERT INTO `actividad_log` VALUES (1,1,'actualizar','leads',4,'Lead actualizado','::1','2026-04-02 17:07:04'),(2,1,'actualizar','leads',2,'Lead actualizado','::1','2026-04-02 17:22:18'),(3,1,'actualizar','leads',2,'Lead actualizado','::1','2026-04-02 17:22:50'),(4,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-02 17:26:19'),(5,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-02 17:26:31'),(6,1,'crear','clientes',25,'Nuevo cliente: Laura Jiménez','::1','2026-04-02 17:27:13'),(7,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-02 21:47:29'),(8,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-02 21:54:55'),(9,1,'actualizar','leads',2,'Lead actualizado','::1','2026-04-02 22:21:10'),(10,1,'actualizar','transacciones',1,'Transacción actualizada','::1','2026-04-02 22:54:10'),(11,1,'eliminar','transacciones',1,'Transacción eliminada','::1','2026-04-02 22:56:54'),(12,1,'eliminar','transacciones',2,'Transacción eliminada','::1','2026-04-02 22:56:57'),(13,1,'eliminar','transacciones',3,'Transacción eliminada','::1','2026-04-02 22:56:59'),(14,1,'eliminar','transacciones',4,'Transacción eliminada','::1','2026-04-02 22:57:01'),(15,1,'eliminar','transacciones',5,'Transacción eliminada','::1','2026-04-02 22:57:03'),(16,1,'eliminar','transacciones',6,'Transacción eliminada','::1','2026-04-02 22:57:05'),(17,1,'eliminar','transacciones',7,'Transacción eliminada','::1','2026-04-02 22:57:07'),(18,1,'eliminar','transacciones',8,'Transacción eliminada','::1','2026-04-02 22:57:09'),(19,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-02 22:57:27'),(20,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-02 22:57:28'),(21,1,'actualizar','servicios',13,'Servicio actualizado','::1','2026-04-03 21:48:02'),(22,1,'actualizar','servicios',13,'Servicio actualizado','::1','2026-04-03 21:51:13'),(23,1,'desactivar','servicios',9,'Servicio desactivado','::1','2026-04-03 21:53:45'),(24,1,'desactivar','servicios',13,'Servicio desactivado','::1','2026-04-03 21:53:47'),(25,1,'desactivar','servicios',15,'Servicio desactivado','::1','2026-04-03 21:53:50'),(26,1,'desactivar','servicios',17,'Servicio desactivado','::1','2026-04-03 21:53:52'),(27,1,'desactivar','servicios',12,'Servicio desactivado','::1','2026-04-03 21:53:53'),(28,1,'desactivar','servicios',10,'Servicio desactivado','::1','2026-04-03 21:53:55'),(29,1,'desactivar','servicios',11,'Servicio desactivado','::1','2026-04-03 21:53:57'),(30,1,'desactivar','servicios',14,'Servicio desactivado','::1','2026-04-03 21:54:00'),(31,1,'desactivar','servicios',16,'Servicio desactivado','::1','2026-04-03 21:54:02'),(32,1,'crear','servicios',18,'Nuevo servicio: Dominios','::1','2026-04-03 21:58:02'),(33,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 22:02:43'),(34,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 22:02:59'),(35,1,'crear','servicios',19,'Nuevo servicio: Dominio .co','::1','2026-04-03 22:03:43'),(36,1,'crear','servicios',20,'Nuevo servicio: Hosting 5 GB','::1','2026-04-03 22:06:24'),(37,1,'crear','servicios',21,'Nuevo servicio: Correos 5 User','::1','2026-04-03 22:16:27'),(38,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-03 22:16:38'),(39,1,'desactivar','servicios',19,'Servicio desactivado','::1','2026-04-03 22:48:18'),(40,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-03 22:49:11'),(41,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 22:49:18'),(42,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-03 22:49:35'),(43,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-03 22:50:03'),(44,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-03 22:50:42'),(45,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 22:54:50'),(46,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-03 22:58:45'),(47,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 23:02:48'),(48,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-03 23:06:56'),(49,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-03 23:07:37'),(50,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 12:21:03'),(51,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 12:21:11'),(52,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 12:23:09'),(53,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 12:23:23'),(54,1,'renovar','cliente_servicios',11,'Renovación masiva: 2 servicio(s) del cliente 11','::1','2026-04-04 13:25:33'),(55,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-04 14:06:16'),(56,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-04 14:06:21'),(57,1,'revertir','cliente_servicios',11,'Reversión masiva: 2 servicio(s) del cliente 11','::1','2026-04-04 15:30:46'),(58,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 16:58:30'),(59,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 17:40:28'),(60,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-04 17:40:53'),(61,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-04 17:41:29'),(62,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-04 19:10:29'),(63,1,'crear','servicios',22,'Nuevo servicio: Diseño y Desarrollo','::1','2026-04-04 19:24:07'),(64,1,'crear','servicios',23,'Nuevo servicio: Actualizaciones/Modificaciones','::1','2026-04-04 20:25:36'),(65,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-04 20:26:44'),(66,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-04 20:27:19'),(67,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-04 20:28:30'),(68,1,'actualizar','servicios',23,'Servicio actualizado','::1','2026-04-04 20:28:49'),(69,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-04 20:30:01'),(70,1,'actualizar','servicios',22,'Servicio actualizado','::1','2026-04-04 20:38:56'),(71,1,'crear','paquetes',1,'Nuevo paquete: ejemploe','::1','2026-04-04 20:46:01'),(72,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 20:48:16'),(73,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 20:48:35'),(74,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 20:48:59'),(75,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 20:55:44'),(76,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 20:56:49'),(77,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 21:07:01'),(78,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 21:07:50'),(79,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 21:16:56'),(80,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 21:45:00'),(81,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 21:57:15'),(82,1,'actualizar','servicios',23,'Servicio actualizado','::1','2026-04-04 22:15:32'),(83,1,'actualizar','servicios',22,'Servicio actualizado','::1','2026-04-04 22:16:15'),(84,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 22:17:35'),(85,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-04 22:18:11'),(86,1,'actualizar','servicios',22,'Servicio actualizado','::1','2026-04-04 22:22:32'),(87,1,'actualizar','servicios',23,'Servicio actualizado','::1','2026-04-04 22:26:05'),(88,1,'crear','servicios',24,'Nuevo servicio: Actualizaciones desarrollo','::1','2026-04-04 22:26:45'),(89,1,'actualizar','servicios',24,'Servicio actualizado','::1','2026-04-04 22:29:44'),(90,1,'actualizar','leads',3,'Lead actualizado','::1','2026-04-05 13:35:50'),(91,1,'eliminar','leads',2,'Lead eliminado: María López','::1','2026-04-05 13:38:21'),(92,1,'eliminar','leads',3,'Lead eliminado: Andrés Ruiz','::1','2026-04-05 13:38:21'),(93,1,'eliminar','leads',4,'Lead eliminado: Laura Jiménez','::1','2026-04-05 13:38:21'),(94,1,'eliminar','leads',5,'Lead eliminado: Pedro Gómez','::1','2026-04-05 13:38:21'),(95,1,'eliminar','leads',6,'Lead eliminado: Sofia Torres','::1','2026-04-05 13:38:21'),(96,1,'eliminar','leads',7,'Lead eliminado: Diego Vargas','::1','2026-04-05 13:38:21'),(97,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-05 13:40:50'),(98,1,'crear','clientes',26,'Nuevo cliente: Arrienda Bien SAS','::1','2026-04-05 14:34:24'),(99,1,'actualizar','servicios',24,'Servicio actualizado','::1','2026-04-05 14:49:13'),(100,1,'actualizar','servicios',24,'Servicio actualizado','::1','2026-04-05 14:49:27'),(101,1,'renovar','cliente_servicios',26,'Renovación masiva: 3 servicio(s) del cliente 26','::1','2026-04-05 14:54:31'),(102,1,'crear','clientes',27,'Nuevo cliente: ALGAMA ASOCIADOS S.A.S','::1','2026-04-05 14:59:11'),(103,1,'actualizar','servicios',22,'Servicio actualizado','::1','2026-04-05 15:19:33'),(104,1,'actualizar','servicios',20,'Servicio actualizado','::1','2026-04-05 15:23:32'),(105,1,'actualizar','servicios',22,'Servicio actualizado','::1','2026-04-05 15:23:48'),(106,1,'actualizar','servicios',18,'Servicio actualizado','::1','2026-04-05 15:23:59'),(107,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-05 15:36:12'),(108,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-05 15:44:45'),(109,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-05 15:44:54'),(110,1,'eliminar','leads',2,'Lead eliminado: María López','::1','2026-04-06 00:19:09'),(111,1,'eliminar','leads',3,'Lead eliminado: Andrés Ruiz','::1','2026-04-06 00:19:09'),(112,1,'eliminar','leads',4,'Lead eliminado: Laura Jiménez','::1','2026-04-06 00:19:09'),(113,1,'eliminar','leads',5,'Lead eliminado: Pedro Gómez','::1','2026-04-06 00:19:09'),(114,1,'eliminar','leads',6,'Lead eliminado: Sofia Torres','::1','2026-04-06 00:19:09'),(115,1,'eliminar','leads',7,'Lead eliminado: Diego Vargas','::1','2026-04-06 00:19:09'),(116,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-06 00:23:08'),(117,1,'crear','servicios',25,'Nuevo servicio: Otros','::1','2026-04-07 11:05:41'),(118,1,'crear','plantillas_factura',1,'Nueva plantilla: Soporte de compra','::1','2026-04-10 01:17:59'),(119,1,'eliminar','plantillas_factura',1,'Plantilla eliminada: ID 1','::1','2026-04-10 01:18:24'),(120,1,'crear','tareas',1,'Nueva tarea: Actualizar Web Algama Asociados','::1','2026-04-10 01:26:39'),(121,1,'crear','leads',1,'Nuevo lead: Didier Bedoya Reinel','::1','2026-04-10 02:08:48'),(122,1,'crear','transacciones',1,'Nueva transacción: Diseño para impresos','::1','2026-04-10 02:08:48'),(123,1,'actualizar','leads',1,'Lead actualizado','::1','2026-04-10 02:10:17'),(124,1,'crear','proveedores',1,'Nuevo proveedor: Anthropic','::1','2026-04-10 02:19:47'),(125,1,'editar','proveedores',1,'Proveedor editado: ID 1','::1','2026-04-10 02:25:55'),(126,1,'crear','plantillas_factura',2,'Nueva plantilla: Documento Soporte','::1','2026-04-10 08:45:38'),(127,1,'crear','leads',2,'Nuevo lead: Carlos Mendoza López','::1','2026-04-10 08:51:36'),(128,1,'crear','tareas',2,'Nueva tarea: Diseñar propuesta para cliente nuevo','::1','2026-04-10 08:54:25'),(129,1,'actualizar','leads',2,'Lead actualizado','::1','2026-04-10 09:12:45'),(130,1,'editar','tareas',2,'Tarea editada: ID 2','::1','2026-04-10 10:09:36'),(131,1,'editar','plantillas_factura',2,'Plantilla establecida como default: ID 2','::1','2026-04-10 10:10:16'),(132,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-10 10:10:46'),(133,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-10 10:11:03'),(134,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-10 10:11:11'),(135,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-10 10:11:17'),(136,1,'crear','clientes',28,'Nuevo cliente: Didier Bedoya Reinel','::1','2026-04-10 18:54:39'),(137,1,'crear','leads',3,'Nuevo lead: Maria Anaya','::1','2026-04-10 18:56:47'),(138,1,'actualizar','leads',3,'Lead actualizado','::1','2026-04-10 18:57:00'),(139,1,'actualizar','leads',3,'Lead actualizado','::1','2026-04-10 18:57:06'),(140,1,'actualizar','leads',2,'Lead actualizado','::1','2026-04-10 18:57:43'),(141,1,'eliminar','transacciones',7,'Transacción eliminada','::1','2026-04-10 19:14:45'),(142,1,'eliminar','transacciones',8,'Transacción eliminada','::1','2026-04-10 19:14:45'),(143,1,'eliminar','transacciones',3,'Transacción eliminada','::1','2026-04-10 19:14:45'),(144,1,'eliminar','transacciones',4,'Transacción eliminada','::1','2026-04-10 19:14:45'),(145,1,'eliminar','transacciones',5,'Transacción eliminada','::1','2026-04-10 19:14:45'),(146,1,'actualizar','transacciones',6,'Transacción actualizada','::1','2026-04-10 19:18:31'),(147,1,'eliminar','tareas',2,'Tarea cancelada: ID 2','::1','2026-04-10 22:39:45'),(148,1,'eliminar','tareas',3,'Tarea cancelada: ID 3','::1','2026-04-10 22:39:48'),(149,1,'eliminar','tareas',1,'Tarea cancelada: ID 1','::1','2026-04-10 22:39:50'),(150,1,'eliminar','tareas',4,'Tarea cancelada: ID 4','::1','2026-04-10 22:39:52'),(151,1,'eliminar','tareas',5,'Tarea cancelada: ID 5','::1','2026-04-10 22:39:54'),(152,1,'crear','tareas',6,'Nueva tarea: Actualizar Web Algama Asociados','::1','2026-04-10 22:41:06'),(153,1,'editar','tareas',6,'Tarea editada: ID 6','::1','2026-04-10 22:43:00'),(154,1,'editar','tareas',6,'Tarea editada: ID 6','::1','2026-04-10 22:43:05'),(155,1,'eliminar','transacciones',6,'Transacción eliminada','::1','2026-04-10 23:42:00'),(156,1,'revertir','cliente_servicios',26,'Reversión masiva: 3 servicio(s) del cliente 26','::1','2026-04-10 23:53:13'),(157,1,'revertir','cliente_servicios',26,'Reversión masiva: 3 servicio(s) del cliente 26','::1','2026-04-10 23:53:28'),(158,1,'revertir','cliente_servicios',26,'Reversión masiva: 1 servicio(s) del cliente 26','::1','2026-04-10 23:53:55'),(159,1,'renovar','cliente_servicios',26,'Renovación masiva: 3 servicio(s) del cliente 26','::1','2026-04-10 23:54:53'),(160,1,'crear','clientes',29,'Nuevo cliente: Empresa Perla Del Sinú ','::1','2026-04-10 23:59:32'),(161,1,'actualizar','servicios',21,'Servicio actualizado','::1','2026-04-11 00:06:39'),(162,1,'crear','paquetes',2,'Nuevo paquete: Página Web ','::1','2026-04-11 00:10:41'),(163,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-11 00:11:15'),(164,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-11 00:11:42'),(165,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-11 00:12:22'),(166,1,'crear','clientes',30,'Nuevo cliente: REPTILANDIA SAS','::1','2026-04-11 00:17:25'),(167,1,'crear','clientes',31,'Nuevo cliente: FINANCIA LIV','::1','2026-04-11 00:30:18'),(168,1,'crear','transacciones',9,'Nueva transacción: Compra recurso IA para desarrollo','::1','2026-04-11 11:51:53'),(169,1,'eliminar','leads',3,'Lead eliminado: Maria Anaya','::1','2026-04-12 00:09:04'),(170,1,'eliminar','leads',2,'Lead eliminado: Carlos Mendoza López','::1','2026-04-12 00:09:04'),(171,1,'eliminar','leads',1,'Lead eliminado: Didier Bedoya Reinel','::1','2026-04-12 00:09:04'),(172,1,'crear','leads',4,'Nuevo lead: Ramón Anaya','::1','2026-04-12 14:08:28'),(173,1,'eliminar','leads',4,'Lead eliminado: Ramón Anaya','::1','2026-04-12 18:49:01'),(174,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-12 21:33:52'),(175,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-12 21:33:59'),(176,1,'crear','clientes',32,'Nuevo cliente: FUNDACIÓN CLUB DOWN 21','::1','2026-04-12 21:49:58'),(177,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-18 01:24:39'),(178,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-18 01:24:42'),(179,1,'crear','clientes',33,'Nuevo cliente: NATURVIDACOL','::1','2026-04-20 10:24:48'),(180,1,'crear','plantillas_factura',3,'Nueva plantilla: Hola ejemplo','::1','2026-04-20 14:50:26'),(181,1,'eliminar','plantillas_factura',3,'Plantilla eliminada: ID 3','::1','2026-04-20 14:50:54'),(182,1,'editar','plantillas_factura',3,'Plantilla editada: ID 3','::1','2026-04-20 14:51:07'),(183,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-20 14:52:45'),(184,1,'editar','plantillas_factura',2,'Plantilla editada: ID 2','::1','2026-04-20 14:56:54'),(185,1,'crear','plantillas_factura',4,'Nueva plantilla: Ejecutiva','::1','2026-04-20 20:58:50'),(186,1,'crear','plantillas_factura',5,'Nueva plantilla: Ejecutiva','::1','2026-04-20 21:00:36'),(187,1,'crear','plantillas_factura',6,'Nueva plantilla: Moderna','::1','2026-04-20 21:01:56'),(188,1,'eliminar','plantillas_factura',2,'Plantilla eliminada: ID 2','::1','2026-04-20 21:02:09'),(189,1,'eliminar','plantillas_factura',4,'Plantilla eliminada: ID 4','::1','2026-04-20 21:02:13'),(190,1,'eliminar','plantillas_factura',5,'Plantilla eliminada: ID 5','::1','2026-04-20 21:02:18'),(191,1,'crear','plantillas_factura',7,'Nueva plantilla: Clásica','::1','2026-04-20 21:02:43'),(192,1,'editar','plantillas_factura',6,'Plantilla establecida como default: ID 6','::1','2026-04-20 21:02:55'),(193,1,'eliminar','plantillas_factura',7,'Plantilla eliminada: ID 7','::1','2026-04-20 21:09:29'),(194,1,'crear','plantillas_factura',8,'Nueva plantilla: Ejecutiva','::1','2026-04-20 21:10:01'),(195,1,'editar','plantillas_factura',6,'Plantilla establecida como default: ID 6','::1','2026-04-20 21:10:25'),(196,1,'editar','plantillas_factura',8,'Plantilla establecida como default: ID 8','::1','2026-04-20 21:10:34'),(197,1,'eliminar','plantillas_factura',6,'Plantilla eliminada: ID 6','::1','2026-04-20 21:10:38'),(198,1,'editar','plantillas_factura',8,'Plantilla editada: ID 8','::1','2026-04-20 21:10:52'),(199,1,'editar','plantillas_factura',8,'Plantilla editada: ID 8','::1','2026-04-20 21:11:58'),(200,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-21 14:52:13'),(201,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-22 18:16:09'),(202,1,'editar','tareas',6,'Tarea editada: ID 6','::1','2026-04-22 18:31:32'),(203,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-22 20:04:33'),(204,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-22 20:04:55'),(205,1,'crear','clientes',34,'Nuevo cliente: REPTILANDIA SAS','::1','2026-04-22 20:13:16'),(206,1,'crear','clientes',35,'Nuevo cliente: FUNDACIÓN CLUB DOWN 21','::1','2026-04-22 20:20:57'),(207,1,'crear','clientes',36,'Nuevo cliente: SOLUCIONES HYB','::1','2026-04-22 20:39:57'),(208,1,'crear','clientes',37,'Nuevo cliente: MW SOLUCIONES','::1','2026-04-22 21:05:53'),(209,1,'crear','tareas',7,'Nueva tarea: Creación de Correo corporativo','::1','2026-04-22 23:12:11'),(210,1,'editar','tareas',7,'Tarea editada: ID 7','::1','2026-04-22 23:16:21'),(211,1,'editar','tareas',7,'Tarea editada: ID 7','::1','2026-04-22 23:21:55'),(212,1,'editar','tareas',7,'Tarea editada: ID 7','::1','2026-04-22 23:25:35'),(213,1,'editar','tareas',7,'Tarea editada: ID 7','::1','2026-04-22 23:27:17'),(214,1,'editar','tareas',7,'Tarea editada: ID 7','::1','2026-04-23 00:46:24'),(215,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-23 14:14:53'),(216,1,'crear','clientes',38,'Nuevo cliente: CEICAR SAS','::1','2026-04-23 14:30:04'),(217,1,'crear','transacciones',10,'Nueva transacción: Servicio a CEICAR SAS','::1','2026-04-23 14:33:01'),(218,1,'editar','plantillas_factura',8,'Plantilla editada: ID 8','::1','2026-04-23 18:46:44'),(219,1,'editar','plantillas_factura',8,'Plantilla editada: ID 8','::1','2026-04-23 18:46:52'),(220,1,'crear','clientes',39,'Nuevo cliente: DOUBLE ONE GK','::1','2026-04-24 00:09:34'),(221,1,'crear','clientes',40,'Nuevo cliente: LA VIDA DE ANAQUEL','::1','2026-04-24 00:14:04'),(222,1,'crear','clientes',41,'Nuevo cliente: CLÍNICA CATTH','::1','2026-04-24 00:15:45'),(223,1,'crear','clientes',42,'Nuevo cliente: DOCTORA LUZ TORRALVO ','::1','2026-04-24 00:23:57'),(224,1,'crear','clientes',43,'Nuevo cliente: FUNDACIÓN ESCALA ORG','::1','2026-04-24 00:28:29'),(225,1,'crear','clientes',44,'Nuevo cliente: ECOASEO','::1','2026-04-24 00:31:21'),(226,1,'crear','clientes',45,'Nuevo cliente: ARECICLAR SINCELEJO','::1','2026-04-24 00:33:48'),(227,1,'crear','clientes',46,'Nuevo cliente: RENTAR INMOBILIARIA SAS','::1','2026-04-24 00:35:57'),(228,1,'eliminar','servicios',22,'Servicio eliminado: Diseño Templates','::1','2026-04-24 00:45:33'),(229,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-24 00:50:59'),(230,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-24 00:51:15'),(231,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-25 13:24:14'),(232,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-25 13:24:17'),(233,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-25 13:24:19'),(234,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-25 13:24:21'),(235,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-25 13:29:25'),(236,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-25 13:29:35'),(237,1,'crear','clientes',47,'Nuevo cliente: Ozzo Market Shop','::1','2026-04-25 14:36:51'),(238,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 16:18:51'),(239,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 16:19:04'),(240,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 16:19:38'),(241,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 16:20:52'),(242,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 16:23:28'),(243,1,'crear','paquetes',3,'Nuevo paquete: Sitio Web Pyme','::1','2026-04-25 16:27:02'),(244,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 16:27:34'),(245,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 16:32:17'),(246,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 16:33:00'),(247,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 16:40:37'),(248,1,'logout','usuarios',1,'Cierre de sesión','::1','2026-04-25 18:44:57'),(249,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-25 18:44:58'),(250,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 21:38:18'),(251,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 21:45:30'),(252,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 21:49:40'),(253,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 21:53:58'),(254,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 21:54:21'),(255,1,'actualizar','paquetes',1,'Paquete actualizado','::1','2026-04-25 21:54:52'),(256,1,'actualizar','paquetes',2,'Paquete actualizado','::1','2026-04-25 21:58:26'),(257,1,'login','usuarios',1,'Inicio de sesión','::1','2026-04-25 22:01:12'),(258,1,'actualizar','paquetes',3,'Paquete actualizado','::1','2026-04-25 22:04:30'),(259,1,'eliminar','servicios',24,'Servicio eliminado: Plataformas Especializadas','::1','2026-04-25 22:21:55'),(260,1,'crear','servicios',26,'Nuevo servicio: Plataformas','::1','2026-04-25 22:24:05'),(261,1,'actualizar','servicios',26,'Servicio actualizado','::1','2026-04-25 22:24:46'),(262,1,'actualizar','servicios',24,'Servicio actualizado','::1','2026-04-25 22:26:44');
/*!40000 ALTER TABLE `actividad_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente_negocios`
--

DROP TABLE IF EXISTS `cliente_negocios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente_negocios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `tipo` varchar(80) DEFAULT NULL,
  `estado` enum('activo','en_progreso','completado','pausado','cancelado') NOT NULL DEFAULT 'activo',
  `descripcion` text DEFAULT NULL,
  `monto` decimal(12,2) DEFAULT NULL,
  `fecha_inicio` date DEFAULT NULL,
  `fecha_entrega` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_negocio_cliente` (`cliente_id`),
  CONSTRAINT `fk_negocio_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_negocios`
--

LOCK TABLES `cliente_negocios` WRITE;
/*!40000 ALTER TABLE `cliente_negocios` DISABLE KEYS */;
/*!40000 ALTER TABLE `cliente_negocios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cliente_servicios`
--

DROP TABLE IF EXISTS `cliente_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cliente_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `monto_renovacion` decimal(15,2) NOT NULL,
  `descuento` decimal(10,2) DEFAULT 0.00,
  `costo_servicio` decimal(15,2) DEFAULT 0.00,
  `fecha_inicio` date NOT NULL,
  `fecha_vencimiento` date NOT NULL,
  `frecuencia` varchar(20) NOT NULL DEFAULT 'año',
  `recordatorio_enviado` tinyint(1) DEFAULT 0,
  `estado` enum('activo','vencido','suspendido','cancelado') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `nombre_display` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `servicio_id` (`servicio_id`),
  CONSTRAINT `cliente_servicios_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cliente_servicios_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=135 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cliente_servicios`
--

LOCK TABLES `cliente_servicios` WRITE;
/*!40000 ALTER TABLE `cliente_servicios` DISABLE KEYS */;
INSERT INTO `cliente_servicios` VALUES (74,26,18,79900.00,0.00,59900.00,'2026-03-25','2027-03-25','año',0,'activo','2026-04-05 14:40:15','2026-04-10 23:54:53','Dominios — .com'),(75,26,18,89000.00,0.00,89000.00,'2026-03-25','2027-03-25','año',0,'activo','2026-04-05 14:44:32','2026-04-10 23:54:53','Dominios — .com'),(77,27,18,79900.00,0.00,59900.00,'2025-08-08','2026-08-08','año',0,'activo','2026-04-05 15:00:04','2026-04-10 23:37:30','Dominios — .com'),(90,28,25,600000.00,0.00,0.00,'2026-04-10','2027-04-10','unico',0,'activo','2026-04-10 21:18:48','2026-04-10 23:52:52','Otros — Diseño para impresos'),(91,29,18,79900.00,0.00,59900.00,'2025-05-22','2026-05-22','año',0,'activo','2026-04-11 00:00:44','2026-04-11 00:00:44','Servicio de Dominios — .com'),(95,30,18,180000.00,0.00,179000.00,'2025-06-03','2026-06-03','año',0,'activo','2026-04-11 00:18:37','2026-04-22 20:15:09','Servicio de Dominios — .co'),(98,30,21,210000.00,0.00,0.00,'2025-06-03','2026-06-03','año',0,'activo','2026-04-11 00:22:19','2026-04-22 20:14:55','Servicio de Correos — Plan Emprendedor (1 User)'),(99,31,18,180000.00,0.00,179000.00,'2025-06-18','2026-06-18','año',0,'activo','2026-04-11 00:33:25','2026-04-11 00:33:25','Servicio de Dominios — .com.co'),(100,31,20,180000.00,0.00,0.00,'2025-06-18','2026-06-18','año',0,'activo','2026-04-11 00:33:58','2026-04-11 00:34:11','Servicio de Hosting — DD 3 Gigas'),(101,33,18,789800.00,0.00,169800.00,'2026-04-20','2027-04-20','año',0,'activo','2026-04-20 10:25:20','2026-04-20 10:25:20','Página Web '),(104,32,20,150000.00,0.00,30000.00,'2025-06-07','2026-06-07','año',0,'activo','2026-04-22 20:26:58','2026-04-22 20:27:57','Servicio de Hosting — DD 1 Gigas'),(105,36,18,79900.00,0.00,59900.00,'2025-06-07','2026-07-07','año',0,'activo','2026-04-22 20:40:59','2026-04-22 20:40:59','Servicio de Dominios — .com'),(106,36,20,150000.00,0.00,30000.00,'2025-07-07','2026-07-07','año',0,'activo','2026-04-22 20:41:41','2026-04-22 20:41:41','Servicio de Hosting — DD 1 Gigas'),(107,37,18,79900.00,0.00,59900.00,'2026-02-12','2027-02-12','año',0,'activo','2026-04-22 21:06:42','2026-04-22 21:06:42','Servicio de Dominios — .com'),(108,37,20,579000.00,0.00,579000.00,'2026-02-12','2027-02-12','año',0,'activo','2026-04-22 21:12:32','2026-04-22 21:12:32','Servicio de Hosting — MW Soluciones (Enterprise)'),(110,30,20,150000.00,0.00,30000.00,'2025-06-02','2026-06-02','año',0,'activo','2026-04-23 01:48:35','2026-04-23 01:48:35','Servicio de Hosting — DD 1 Gigas'),(111,38,18,180000.00,0.00,146900.00,'2025-06-03','2026-06-03','año',0,'activo','2026-04-23 23:37:57','2026-04-23 23:37:57','Servicio de Dominios — .co'),(114,39,18,79900.00,0.00,59900.00,'2025-07-30','2026-07-30','año',0,'activo','2026-04-24 00:10:44','2026-04-24 00:10:44','Servicio de Dominios — .com'),(115,39,20,150000.00,0.00,30000.00,'2025-07-30','2026-07-30','año',0,'activo','2026-04-24 00:11:55','2026-04-24 00:11:55','Servicio de Hosting — DD 1 Gigas'),(116,41,18,79900.00,0.00,59900.00,'2025-10-10','2026-10-10','año',0,'activo','2026-04-24 00:19:43','2026-04-24 00:19:43','Servicio de Dominios — .com'),(117,41,20,360000.00,0.00,30000.00,'2025-10-10','2026-10-10','año',0,'activo','2026-04-24 00:20:36','2026-04-24 00:20:36','Servicio de Hosting — DD 25 Gigas'),(118,41,21,995000.00,0.00,657100.00,'2025-10-10','2026-10-10','año',0,'activo','2026-04-24 00:21:46','2026-04-24 00:21:46','Servicio de Correos — Plan Enterprise (10 User)'),(119,42,18,79900.00,0.00,59900.00,'2025-10-15','2026-10-15','año',0,'activo','2026-04-24 00:24:38','2026-04-24 00:24:38','Servicio de Dominios — .com'),(120,42,20,180000.00,0.00,30000.00,'2025-10-15','2026-10-15','año',0,'activo','2026-04-24 00:25:17','2026-04-24 00:25:17','Servicio de Hosting — DD 3 Gigas'),(121,43,18,79900.00,0.00,59900.00,'2025-10-24','2026-10-24','año',0,'activo','2026-04-24 00:29:02','2026-04-24 00:30:11','Servicio de Dominios — .com'),(122,43,20,210000.00,0.00,30000.00,'2025-10-24','2026-10-24','año',0,'activo','2026-04-24 00:29:57','2026-04-24 00:29:57','Servicio de Hosting — DD 5 Gigas'),(123,44,18,180000.00,0.00,146900.00,'2025-08-11','2026-08-11','año',0,'activo','2026-04-24 00:32:02','2026-04-24 00:32:02','Servicio de Dominios — .co'),(124,44,20,210000.00,0.00,30000.00,'2025-08-11','2026-08-11','año',0,'activo','2026-04-24 00:32:30','2026-04-24 00:32:30','Servicio de Hosting — DD 5 Gigas'),(125,45,18,79900.00,0.00,59900.00,'2025-11-12','2026-12-12','año',0,'activo','2026-04-24 00:34:19','2026-04-24 00:34:19','Servicio de Dominios — .com'),(127,46,18,85000.00,0.00,59900.00,'2026-12-18','2026-12-18','año',0,'activo','2026-04-24 00:36:55','2026-04-24 00:36:55','Servicio de Dominios — .com'),(128,46,20,1100000.00,0.00,745000.00,'2025-12-18','2026-12-18','año',0,'activo','2026-04-24 00:41:13','2026-04-24 00:41:13','Servicio de Hosting — Rentar Inmobiliaria (Enterprise)'),(129,27,20,180000.00,0.00,30000.00,'2025-08-08','2026-08-08','año',0,'activo','2026-04-25 20:48:23','2026-04-25 20:48:23','Servicio de Hosting — DD 1 Gigas'),(130,27,21,79000.00,0.00,0.00,'2025-08-08','2026-08-08','año',0,'activo','2026-04-25 20:49:18','2026-04-25 20:49:18','Servicio de Correos — Plan Pymes (5 User GRATIS)'),(131,29,21,79000.00,0.00,0.00,'2025-05-22','2026-05-22','año',0,'activo','2026-04-25 20:57:20','2026-04-25 20:57:20','Servicio de Correos — Plan Pymes (5 User GRATIS)'),(132,29,20,210000.00,0.00,30000.00,'2025-05-22','2026-05-22','año',0,'activo','2026-04-25 20:58:02','2026-04-25 20:58:02','Servicio de Hosting — DD 5 Gigas'),(133,45,20,180000.00,0.00,30000.00,'2025-12-12','2026-12-12','año',0,'activo','2026-04-25 21:01:32','2026-04-25 21:01:32','Servicio de Hosting — DD 1 Gigas'),(134,38,21,79000.00,0.00,0.00,'2026-06-03','2027-06-03','año',0,'activo','2026-04-25 21:03:59','2026-04-25 21:03:59','Servicio de Correos — Plan Pymes (5 User GRATIS)');
/*!40000 ALTER TABLE `cliente_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lead_id` int(11) DEFAULT NULL,
  `nombre_comercial` varchar(150) NOT NULL,
  `nit_cedula` varchar(50) DEFAULT NULL,
  `persona_contacto` varchar(100) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `email_facturacion` varchar(150) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `estado` enum('activo','inactivo','en_mora') DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `responsable` varchar(100) DEFAULT NULL,
  `ubicacion` varchar(150) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  CONSTRAINT `clientes_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (26,NULL,'ARRIENDA BIEN SAS','9000801155','Valentina Combat','3045425938','valentinaarriendabienltda@hotmail.com','Calle 30 #1-43, Diagonal a Casa Rosa, Centro',NULL,'activo','2026-04-05 14:34:24','2026-04-05 17:58:23','Valentina Combat','Montería, Córdoba, Colombia'),(27,NULL,'ALGAMA ASOCIADOS S.A.S','9012744711','Edha Silva Rugeles','3176159404','algama.asociados@gmail.com','CL 53 A 80 13',NULL,'activo','2026-04-05 14:59:11','2026-04-05 14:59:11','Edha Silva Rugeles','Medellín'),(28,NULL,'EL FAMOSO','3003612660','Didier Bedolla Reinel','3003612660','nuevoamanecer234@hotmail.es','Tierralta Córdoba',NULL,'activo','2026-04-10 18:54:39','2026-04-10 20:53:24','Por asignar',''),(29,NULL,'EMPRESA PERLA DEL SINÚ','','Mónica González','3007499660','momagor1990@gmail.com','',NULL,'activo','2026-04-10 23:59:32','2026-04-11 00:04:31','Mónica González','Montería, Córdoba'),(30,NULL,'REPTILANDIA SAS','','Liliana Álvarez','3157467298','info@reptilandia.co','Cra 11 #29-35',NULL,'activo','2026-04-11 00:17:25','2026-04-11 00:17:25','Hernán Álvarez','Montería'),(31,NULL,'FINANCIA LIV','','Juan Financia LIV','3002057984','financiavirtual@gmail.com','',NULL,'activo','2026-04-11 00:30:18','2026-04-11 00:30:18','Juan Financia LIV','Pereira, Colombia'),(32,NULL,'FUNDACIÓN CLUB DOWN 21','','Edith Sastoque','3142026949','fundacionclub21down@gmail.com','',NULL,'activo','2026-04-12 21:49:58','2026-04-12 21:49:58','Edith Sastoque','Bogotá, Cólombia'),(33,NULL,'NATURVIDACOL','1064982638','Ramón Anaya P.','31932131774','naturvidacol@gmail.com','Cra. 40 #4460',NULL,'activo','2026-04-20 10:24:48','2026-04-25 19:25:40','Ramón Anaya p.','Medellín'),(36,NULL,'SOLUCIONES HYB','','Hector Villa','3137119821','solucionesindustrialeshyd@outlook.com','Calle 44a #2-20 La Candelaria',NULL,'activo','2026-04-22 20:39:57','2026-04-22 20:39:57','Hector Villa','Medellín'),(37,NULL,'MW SOLUCIONES','','Torres Zumaque','3162409291','matozu25@yahoo.com','Calle 36a #4-20 La charme',NULL,'activo','2026-04-22 21:05:53','2026-04-22 21:05:53','Torres Zumaque','Montería'),(38,NULL,'CEICAR SAS','','Darlys Altamiranda','3145979983','ceicar@hotmail.com','Calle 54a #2-20',NULL,'activo','2026-04-23 14:30:04','2026-04-23 14:30:04','Darlys Altamiranda','Montería'),(39,NULL,'DOUBLE ONE GK','','Juan Mendoza Anaya','3106990380','doubleonekeepers@gmail.com','Calle 54a #2-20 Los Nogales',NULL,'activo','2026-04-24 00:09:34','2026-04-24 00:09:34','Juan Mendoza Anaya','Barranquilla'),(40,NULL,'LA VIDA DE ANAQUEL','','','','','Calle 34a #12-24',NULL,'activo','2026-04-24 00:14:04','2026-04-24 00:14:04','','Montería'),(41,NULL,'CLÍNICA CATTH','','Leidy Pacheco Borja','31345101132','miprescaath@gmail.com','Calle 54a #2-20',NULL,'activo','2026-04-24 00:15:45','2026-04-24 00:15:45','Leidy Pacheco Borja','Montería'),(42,NULL,'DOCTORA LUZ TORRALVO ','','Luz Torralvo','33649899489','luztorralvo@hypsy.ch','Calle 54a #2-20 Francia',NULL,'activo','2026-04-24 00:23:57','2026-04-24 00:23:57','Luz Torralvo','Francia'),(43,NULL,'FUNDACIÓN ESCALA ORG','','Fernando Hernandéz ','3116790151','direccionescala11@gmail.com','Calle 54a #2-20 Costa De Oro',NULL,'activo','2026-04-24 00:28:29','2026-04-24 00:28:29','Fernando Hernandéz ','Montería'),(44,NULL,'ECOASEO','','Ana Milena González','3004953174','milena_ana91@hotmail.com','Calle 54a #2-20 El Recreo',NULL,'activo','2026-04-24 00:31:21','2026-04-24 00:31:21','Ana Milena González','Montería'),(45,NULL,'ARECICLAR SINCELEJO','','Daniel Areciclar','3022670830','areciclarsincelejoesp@gmail.com','Calle 54a #2-20 Sincelejo Sucre',NULL,'activo','2026-04-24 00:33:48','2026-04-24 00:33:48','Daniel Areciclar','Sincelejo'),(46,NULL,'RENTAR INMOBILIARIA SAS','','José Cartera','3225274730','cartera@rentarinmobiliariasas.com','Calle 27 #2-20',NULL,'activo','2026-04-24 00:35:57','2026-04-24 00:35:57','José Cartera','Montería'),(48,NULL,'OZZO MARKET SHOP',NULL,'Ramón Anaya P.','3193213174','ozzomarket.shop@gmail.com','Calle 54a #2-20',NULL,'activo','2026-04-25 19:25:40','2026-04-25 19:37:41','MarÝa Anaya','Barranquilla');
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_archivos`
--

DROP TABLE IF EXISTS `clientes_archivos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes_archivos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre_archivo` varchar(255) NOT NULL,
  `archivo_url` varchar(255) NOT NULL,
  `tipo_archivo` varchar(50) DEFAULT NULL,
  `peso_archivo` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  CONSTRAINT `clientes_archivos_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_archivos`
--

LOCK TABLES `clientes_archivos` WRITE;
/*!40000 ALTER TABLE `clientes_archivos` DISABLE KEYS */;
/*!40000 ALTER TABLE `clientes_archivos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes_notas`
--

DROP TABLE IF EXISTS `clientes_notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes_notas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `nota` text NOT NULL,
  `adjunto_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `clientes_notas_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `clientes_notas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=129 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes_notas`
--

LOCK TABLES `clientes_notas` WRITE;
/*!40000 ALTER TABLE `clientes_notas` DISABLE KEYS */;
INSERT INTO `clientes_notas` VALUES (14,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:30'),(15,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:32'),(16,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:34'),(17,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:36'),(18,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:38'),(19,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:38:40'),(20,26,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 14:39:45'),(21,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 15:02:50'),(22,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 15:03:51'),(23,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 15:24:40'),(24,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 15:24:42'),(25,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 15:27:41'),(26,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 18:20:19'),(27,27,1,'✅ Nuevo servicio asignado: Servicio de Dominios ',NULL,'2026-04-05 18:23:12'),(28,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 18:23:31'),(29,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 18:23:34'),(30,27,1,'⚠️ SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-05 18:23:37'),(31,27,1,'🔔 Recordatorio de Pago enviado',NULL,'2026-04-06 14:16:42'),(32,27,1,'📄 Factura Pagada enviada',NULL,'2026-04-06 14:16:45'),(33,27,1,'✉️ Novedad enviada',NULL,'2026-04-06 14:19:05'),(34,27,1,'📄 Factura Pagada enviada',NULL,'2026-04-06 14:19:17'),(35,27,1,'🔔 Recordatorio de Pago enviado',NULL,'2026-04-06 14:58:35'),(36,27,1,'✉️ Novedad enviada',NULL,'2026-04-06 14:58:38'),(37,27,1,'📄 Factura Pagada enviada',NULL,'2026-04-06 14:58:40'),(38,28,1,'✅ Nuevo servicio asignado: Otros — Diseño para impresos ',NULL,'2026-04-10 21:18:48'),(39,29,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-11 00:00:44'),(40,29,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-11 00:01:26'),(41,29,1,'✅ Nuevo servicio asignado: Diseño Templates — Sitio Web (5 páginas) ',NULL,'2026-04-11 00:02:21'),(42,29,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Pymes (5 User) ',NULL,'2026-04-11 00:08:16'),(43,30,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .co ',NULL,'2026-04-11 00:18:37'),(44,30,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 3 Gigas ',NULL,'2026-04-11 00:20:07'),(45,30,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-11 00:20:37'),(46,30,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 5 Gigas ',NULL,'2026-04-11 00:21:05'),(47,30,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Emprendedor (1 User) ',NULL,'2026-04-11 00:22:19'),(48,31,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com.co ',NULL,'2026-04-11 00:33:25'),(49,31,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 3 Gigas ',NULL,'2026-04-11 00:33:58'),(50,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-11 22:40:23'),(51,27,1,'Novedad enviada',NULL,'2026-04-11 22:40:33'),(52,27,1,'Factura Pagada enviada',NULL,'2026-04-11 22:40:47'),(53,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-12 18:55:39'),(54,27,1,'Novedad enviada',NULL,'2026-04-12 18:55:42'),(55,27,1,'Factura Pagada enviada',NULL,'2026-04-12 18:55:45'),(56,27,1,'Factura Pagada enviada',NULL,'2026-04-12 18:58:52'),(57,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-12 19:12:58'),(58,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-18 01:32:07'),(59,27,1,'Factura Pagada enviada',NULL,'2026-04-18 01:32:17'),(60,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-18 01:42:20'),(61,27,1,'Novedad enviada',NULL,'2026-04-18 01:42:23'),(62,27,1,'Factura Pagada enviada',NULL,'2026-04-18 01:42:25'),(63,27,1,'Factura Pagada enviada',NULL,'2026-04-20 10:02:16'),(64,27,1,'Novedad enviada',NULL,'2026-04-20 10:03:50'),(65,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-20 10:09:58'),(66,27,1,'Recordatorio de Pago enviado',NULL,'2026-04-20 10:14:33'),(67,33,1,'✅ Nuevo servicio asignado: Página Web  ',NULL,'2026-04-20 10:25:20'),(68,27,1,'Novedad enviada',NULL,'2026-04-20 10:27:32'),(69,27,1,'[📨] WhatsApp: Documento Soporte',NULL,'2026-04-20 11:04:48'),(70,27,1,'[📨] Correo: Presentar Cotización',NULL,'2026-04-20 11:17:14'),(71,33,1,'[📨] Correo: Presentar Cotización',NULL,'2026-04-20 11:17:52'),(72,33,1,'[📨] Correo: Presentar Cotización',NULL,'2026-04-20 11:18:59'),(73,33,1,'[📧] Correo enviado: Presentar Cotización',NULL,'2026-04-20 11:35:44'),(74,33,1,'Recordatorio de Pago enviado',NULL,'2026-04-20 12:39:29'),(75,33,1,'Recordatorio de Pago enviado',NULL,'2026-04-21 02:02:14'),(76,32,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 5 Gigas ',NULL,'2026-04-22 20:24:31'),(77,32,1,'✅ Nuevo servicio asignado: Diseño Templates — Sitio Web (5 páginas) ',NULL,'2026-04-22 20:25:46'),(78,32,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-22 20:26:49'),(79,32,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-22 20:26:58'),(80,36,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-22 20:40:59'),(81,36,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-22 20:41:41'),(82,37,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-22 21:06:42'),(83,37,1,'✅ Nuevo servicio asignado: Servicio de Hosting — MW Soluciones (Enterprise) ',NULL,'2026-04-22 21:12:32'),(84,37,1,'✅ Nuevo servicio asignado: Diseño Templates — Sitio Web (5 páginas) ',NULL,'2026-04-22 21:13:36'),(85,37,1,'Recordatorio de Pago enviado',NULL,'2026-04-22 22:25:30'),(86,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 00:47:16'),(87,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 00:55:22'),(88,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 00:56:11'),(89,33,1,'[📧] Correo enviado: Presentar Cotización',NULL,'2026-04-23 00:58:15'),(90,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 00:59:36'),(91,33,1,'[📧] Correo enviado: Presentar Cotización',NULL,'2026-04-23 01:07:08'),(92,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 01:08:20'),(93,30,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-23 01:47:27'),(94,30,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-23 01:48:35'),(95,33,1,'[📧] Correo enviado: Actualización Web Completada',NULL,'2026-04-23 14:28:33'),(96,38,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .co ',NULL,'2026-04-23 23:37:57'),(97,38,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-23 23:40:18'),(98,38,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Pymes (5 User)  (Descuento: $349,900)',NULL,'2026-04-23 23:41:52'),(99,29,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-23 23:44:55'),(100,27,1,'Se actualizó artículo blog.',NULL,'2026-04-24 00:00:50'),(101,39,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:10:44'),(102,39,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-24 00:11:55'),(103,41,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:19:43'),(104,41,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 25 Gigas ',NULL,'2026-04-24 00:20:36'),(105,41,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Enterprise (10 User) ',NULL,'2026-04-24 00:21:46'),(106,42,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:24:38'),(107,42,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 3 Gigas ',NULL,'2026-04-24 00:25:17'),(108,43,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:29:02'),(109,43,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 5 Gigas ',NULL,'2026-04-24 00:29:57'),(110,44,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .co ',NULL,'2026-04-24 00:32:02'),(111,44,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 5 Gigas ',NULL,'2026-04-24 00:32:30'),(112,45,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:34:19'),(113,45,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-24 00:34:40'),(114,46,1,'✅ Nuevo servicio asignado: Servicio de Dominios — .com ',NULL,'2026-04-24 00:36:55'),(115,46,1,'✅ Nuevo servicio asignado: Servicio de Hosting — Rentar Inmobiliaria (Enterprise) ',NULL,'2026-04-24 00:41:13'),(116,27,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 20:47:34'),(117,27,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-25 20:48:23'),(118,27,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 20:48:36'),(119,27,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Pymes (5 User GRATIS) ',NULL,'2026-04-25 20:49:18'),(120,29,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 20:56:36'),(121,29,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Pymes (5 User GRATIS) ',NULL,'2026-04-25 20:57:20'),(122,29,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 20:57:34'),(123,29,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 5 Gigas ',NULL,'2026-04-25 20:58:02'),(124,45,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 21:00:08'),(125,45,1,'✅ Nuevo servicio asignado: Servicio de Hosting — DD 1 Gigas ',NULL,'2026-04-25 21:01:32'),(126,38,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 21:03:29'),(127,38,1,'✅ Nuevo servicio asignado: Servicio de Correos — Plan Pymes (5 User GRATIS) ',NULL,'2026-04-25 21:03:59'),(128,38,1,'[!] SERVICIO ELIMINADO: Se retiró un servicio del historial del cliente.',NULL,'2026-04-25 21:09:01');
/*!40000 ALTER TABLE `clientes_notas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cotizaciones`
--

DROP TABLE IF EXISTS `cotizaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cotizaciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `numero` varchar(30) NOT NULL,
  `cliente_tipo` enum('cliente','lead') NOT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `nombre_cliente` varchar(150) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `whatsapp` varchar(30) DEFAULT NULL,
  `items` longtext NOT NULL,
  `subtotal` decimal(12,2) NOT NULL DEFAULT 0.00,
  `descuento` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `vigencia_dias` int(11) NOT NULL DEFAULT 15,
  `estado` enum('borrador','enviada','aceptada','rechazada','pendiente') DEFAULT 'pendiente',
  `creado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `moneda` varchar(10) NOT NULL DEFAULT 'USD',
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_cliente` (`cliente_tipo`,`cliente_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cotizaciones`
--

LOCK TABLES `cotizaciones` WRITE;
/*!40000 ALTER TABLE `cotizaciones` DISABLE KEYS */;
INSERT INTO `cotizaciones` VALUES (7,'COT-0002-202604','lead',4,'Ramón Anaya','canvax.co@gmail.com','3332747801','[{\"_uid\":1,\"tipo\":\"paquete\",\"ref_id\":1,\"nombre\":\"Landing Pages\",\"descripcion\":\"Paquete recomendado para landing pages para promocionar un servicio, un producto u\\/o evento.\",\"frecuencia\":\"a\\u00f1o\",\"precio_unit\":379900,\"cantidad\":1,\"descuento_pct\":0}]',379900.00,0.00,379900.00,'',15,'enviada',1,'2026-04-21 12:01:34','2026-04-22 23:07:24','COP');
/*!40000 ALTER TABLE `cotizaciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_cliente_credenciales`
--

DROP TABLE IF EXISTS `crm_cliente_credenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_cliente_credenciales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL DEFAULT '',
  `correo` varchar(255) NOT NULL DEFAULT '',
  `clave` varchar(500) NOT NULL DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cliente` (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_cliente_credenciales`
--

LOCK TABLES `crm_cliente_credenciales` WRITE;
/*!40000 ALTER TABLE `crm_cliente_credenciales` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_cliente_credenciales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_cliente_editor`
--

DROP TABLE IF EXISTS `crm_cliente_editor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_cliente_editor` (
  `cliente_id` int(11) NOT NULL,
  `contenido` longtext NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_cliente_editor`
--

LOCK TABLES `crm_cliente_editor` WRITE;
/*!40000 ALTER TABLE `crm_cliente_editor` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_cliente_editor` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_cliente_notif_config`
--

DROP TABLE IF EXISTS `crm_cliente_notif_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_cliente_notif_config` (
  `cliente_id` int(11) NOT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  `dias_antes` int(11) NOT NULL DEFAULT 15,
  `hora_envio` time NOT NULL DEFAULT '08:00:00',
  `asunto_personalizado` varchar(255) NOT NULL DEFAULT '',
  `mensaje_personalizado` text NOT NULL DEFAULT '',
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cliente_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_cliente_notif_config`
--

LOCK TABLES `crm_cliente_notif_config` WRITE;
/*!40000 ALTER TABLE `crm_cliente_notif_config` DISABLE KEYS */;
/*!40000 ALTER TABLE `crm_cliente_notif_config` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `crm_configuraciones`
--

DROP TABLE IF EXISTS `crm_configuraciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `crm_configuraciones` (
  `clave` varchar(80) NOT NULL,
  `valor` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`clave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `crm_configuraciones`
--

LOCK TABLES `crm_configuraciones` WRITE;
/*!40000 ALTER TABLE `crm_configuraciones` DISABLE KEYS */;
INSERT INTO `crm_configuraciones` VALUES ('notif_activa','1','2026-04-25 19:52:53'),('notif_dias','[0,1,2,3,4,5,6]','2026-04-25 19:52:53'),('notif_email','alberthoanayap@gmail.com','2026-04-25 19:58:11'),('notif_hora','07:00','2026-04-25 19:52:53'),('notif_incluir_renovaciones','1','2026-04-25 19:52:53'),('notif_incluir_tareas','1','2026-04-25 19:52:53'),('notif_logo_url','https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png','2026-04-25 19:52:53'),('notif_whatsapp','+573332747801','2026-04-25 19:52:53');
/*!40000 ALTER TABLE `crm_configuraciones` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `facturas`
--

DROP TABLE IF EXISTS `facturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `facturas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_archivo` varchar(255) NOT NULL,
  `archivo_url` varchar(255) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `peso_bytes` int(11) DEFAULT 0,
  `subido_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `subido_por` (`subido_por`),
  CONSTRAINT `facturas_ibfk_1` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `facturas`
--

LOCK TABLES `facturas` WRITE;
/*!40000 ALTER TABLE `facturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `facturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leads`
--

DROP TABLE IF EXISTS `leads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `leads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `whatsapp` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `servicio_interes` varchar(50) NOT NULL,
  `presupuesto` decimal(10,2) DEFAULT 0.00,
  `url_actual` varchar(255) DEFAULT NULL,
  `fuente` enum('manual','wordpress','landing','referido','otro') DEFAULT 'manual',
  `estado` enum('nuevo','contactado','en_negociacion','ganado','perdido') DEFAULT 'nuevo',
  `notas` text DEFAULT NULL,
  `asignado_a` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `asignado_a` (`asignado_a`),
  KEY `idx_leads_estado` (`estado`),
  KEY `idx_leads_created` (`created_at`),
  CONSTRAINT `leads_ibfk_1` FOREIGN KEY (`asignado_a`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leads`
--

LOCK TABLES `leads` WRITE;
/*!40000 ALTER TABLE `leads` DISABLE KEYS */;
/*!40000 ALTER TABLE `leads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensajes_plantillas`
--

DROP TABLE IF EXISTS `mensajes_plantillas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mensajes_plantillas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `contenido` longtext NOT NULL,
  `categoria` varchar(50) DEFAULT 'general',
  `es_predefinida` tinyint(1) DEFAULT 0,
  `activa` tinyint(1) DEFAULT 1,
  `creado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `imagen` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_activa` (`activa`),
  KEY `idx_categoria` (`categoria`),
  KEY `idx_creado` (`creado_por`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensajes_plantillas`
--

LOCK TABLES `mensajes_plantillas` WRITE;
/*!40000 ALTER TABLE `mensajes_plantillas` DISABLE KEYS */;
INSERT INTO `mensajes_plantillas` VALUES (2,'Presentar Cotización','Presentar cotización','¡Hola {{cliente_nombre}}! 😊\r\n\r\nEspero que estés teniendo un excelente día.\r\n\r\nPaso por aquí para dar seguimiento a la propuesta #{{numero_cotizacion}}. 📑💼 Queremos asegurarnos de que todo esté claro y alineado con lo que necesitas.\r\n\r\n¿Tienes disponibilidad para una llamada rápida esta semana? 📲✨','cotizaciones',1,1,NULL,'2026-04-06 12:29:49','2026-04-21 01:39:50',NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png'),(7,'Actualización Web Completada','¡Su sitio web ha sido actualizado con éxito!','¡Hola {{cliente_nombre}}! 😊\r\n\r\nEspero que estés teniendo un excelente día.\r\n\r\nPaso por aquí para decirte que tu sitio web ha sido actualizado con éxito. 📑💼 Queremos asegurarnos de que todo esté claro y alineado con lo que estabas necesitando.\r\n\r\nSi tienes alguna duda por nos vuelves a escribir 📲✨','confirmaciones',0,1,1,'2026-04-23 00:36:47','2026-04-23 00:54:41',NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png'),(8,'2 do anticipo (Trabajo listo)','¡Su sitio web ha sido terminado con éxito!','¡Hola {{cliente_nombre}}! 😊\r\n\r\nEspero que estés teniendo un excelente día.\r\n\r\nPaso por aquí para contarte que tu sitio web ha sido actualizado con éxito y ya se encuentra listo según lo acordado. 🚀💻\r\n\r\nPara continuar con la entrega final y activación completa, quedamos atentos al pago del 50% restante del desarrollo. 💳✨\r\n\r\nCualquier duda o si necesitas apoyo en este proceso, puedes escribirme con total confianza. 📲🤝','confirmaciones',0,1,1,'2026-04-23 14:15:08','2026-04-23 14:27:45',NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png');
/*!40000 ALTER TABLE `mensajes_plantillas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paquete_items`
--

DROP TABLE IF EXISTS `paquete_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paquete_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `paquete_id` int(11) NOT NULL,
  `sub_servicio_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `paquete_id` (`paquete_id`),
  KEY `sub_servicio_id` (`sub_servicio_id`),
  CONSTRAINT `paquete_items_ibfk_1` FOREIGN KEY (`paquete_id`) REFERENCES `paquetes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `paquete_items_ibfk_2` FOREIGN KEY (`sub_servicio_id`) REFERENCES `sub_servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=143 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paquete_items`
--

LOCK TABLES `paquete_items` WRITE;
/*!40000 ALTER TABLE `paquete_items` DISABLE KEYS */;
INSERT INTO `paquete_items` VALUES (134,1,1),(135,1,6),(136,1,33),(137,2,1),(138,2,6),(139,2,3),(140,3,1),(141,3,6),(142,3,3);
/*!40000 ALTER TABLE `paquete_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `paquetes`
--

DROP TABLE IF EXISTS `paquetes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `paquetes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `frecuencia` varchar(20) NOT NULL DEFAULT 'mes',
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `imagen` varchar(500) DEFAULT NULL,
  `enlace` varchar(500) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `paquetes`
--

LOCK TABLES `paquetes` WRITE;
/*!40000 ALTER TABLE `paquetes` DISABLE KEYS */;
INSERT INTO `paquetes` VALUES (1,'Landing Pages','Paquete recomendado para landing pages para promocionar un servicio, un producto u/o evento.\n\n*No incluye actualizaciones de contenido, ni carga de multimedia ni plugins adicionales.* El servicio se renueva después de 365 días; los costos de hosting y dominio no están incluidos en el valor inicial.',480000.00,89900.00,'unico',1,'2026-04-04 20:46:01','uploads/paquetes/pkg_1_1775336870.jpg','https://quantundigital.com/correos/',0),(2,'Sitio Web Básico','Paquete recomendado para tu pequeño negocio que incluye; Home, Nosotros, Servicios, Portafolio y Contactos.\n\nDiseño adaptado para todas las pantallas, moderno y rápido.\n\n*No incluye actualizaciones de contenido, ni multimedia ni plugins instalados.\n\n*Se renueva después de los 365 días, costos de hosting y dominio.',789800.00,89900.00,'unico',1,'2026-04-11 00:10:41',NULL,'https://quantundigital.com/correos/',0),(3,'Sitio Web Pyme','Paquete ideal para tu pequeño negocio que incluye: Home, Nosotros, Servicios, Portafolio, Blog y Contacto.\n\nDiseño moderno, rápido y totalmente adaptable a todos los dispositivos.\n\nIncluye estructura optimizada para compartir contenido a través del blog y fortalecer tu presencia digital.\n\n*No incluye actualizaciones de contenido, ni multimedia ni plugins instalados. *Se renueva después de los 365 días, costos de hosting y dominio.',900000.00,89900.00,'unico',1,'2026-04-25 16:27:02',NULL,'https://quantundigital.com/correos/',0);
/*!40000 ALTER TABLE `paquetes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `plantillas_factura`
--

DROP TABLE IF EXISTS `plantillas_factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plantillas_factura` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `layout_tipo` varchar(30) NOT NULL DEFAULT 'clasica',
  `color_primario` varchar(7) NOT NULL DEFAULT '#0f172a',
  `color_secundario` varchar(7) NOT NULL DEFAULT '#c9f31d',
  `fuente` varchar(50) NOT NULL DEFAULT 'Poppins',
  `empresa_nombre` varchar(120) DEFAULT NULL,
  `empresa_nit` varchar(50) DEFAULT NULL,
  `empresa_email` varchar(120) DEFAULT NULL,
  `empresa_tel` varchar(50) DEFAULT NULL,
  `empresa_dir` varchar(255) DEFAULT NULL,
  `logo_url` varchar(500) DEFAULT NULL,
  `notas_pie` text DEFAULT NULL,
  `es_default` tinyint(1) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `plantillas_factura`
--

LOCK TABLES `plantillas_factura` WRITE;
/*!40000 ALTER TABLE `plantillas_factura` DISABLE KEYS */;
INSERT INTO `plantillas_factura` VALUES (1,'Soporte de compra',NULL,'clasica','#0f172a','#c9f31d','Poppins',NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,0,'2026-04-10 01:17:59','2026-04-10 01:18:24'),(2,'Documento Soporte - Test 2026',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins','QUATUN Digital | Soluciones Digitales','1064982637','alberthoanayap@gmail.com','+573193213174','Calle 44 #46-107','https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png',NULL,0,0,'2026-04-10 08:45:38','2026-04-20 21:02:09'),(3,'Hola ejemplo',NULL,'moderna','#0f172a','#c9f31d','Poppins','QUANTUN Digital','1064982637','alberthoanayap@gmail.com','+573193213174','Calle 44 #46-107',NULL,NULL,0,0,'2026-04-20 14:50:26','2026-04-20 14:50:54'),(4,'Ejecutiva',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins','1064982637','1064982637','contacto@quantundigital.com','+573043389933','Calle 44 #46-107',NULL,'Factura ejemplo',0,0,'2026-04-20 20:58:50','2026-04-20 21:02:13'),(5,'Ejecutiva',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins',NULL,NULL,NULL,NULL,NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png',NULL,0,0,'2026-04-20 21:00:36','2026-04-20 21:02:18'),(6,'Moderna',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins',NULL,NULL,NULL,NULL,NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png',NULL,0,0,'2026-04-20 21:01:56','2026-04-20 21:10:38'),(7,'Clásica',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins',NULL,NULL,NULL,NULL,NULL,'https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png',NULL,0,0,'2026-04-20 21:02:43','2026-04-20 21:09:29'),(8,'Ejecutiva',NULL,'ejecutiva','#0f172a','#c9f31d','Poppins','QUANTUN Digital','QUANTUN Digital','contacto@quantundigital.com','+57333 274 7801','Calle 54a #2-20 Rio Negro, Antioquia','https://quantundigital.com/wp-content/uploads/2024/12/logo_3.png',NULL,1,1,'2026-04-20 21:10:01','2026-04-23 18:46:52');
/*!40000 ALTER TABLE `plantillas_factura` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `proveedores`
--

DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `nit` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `telefono` varchar(50) DEFAULT NULL,
  `categoria` varchar(100) DEFAULT NULL,
  `direccion` varchar(500) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ciudad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `proveedores`
--

LOCK TABLES `proveedores` WRITE;
/*!40000 ALTER TABLE `proveedores` DISABLE KEYS */;
INSERT INTO `proveedores` VALUES (1,'Anthropic','PMB 90375','support@anthropic.com','','Inteligencia Artificial','EE.UU','Compra de herramienta de desarrollo IA',1,'2026-04-10 02:19:47','2026-04-11 16:59:11',''),(2,'Hostinger Colombia','','','','Hosting','','',1,'2026-04-10 08:56:26','2026-04-11 16:59:22','');
/*!40000 ALTER TABLE `proveedores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicio_features`
--

DROP TABLE IF EXISTS `servicio_features`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicio_features` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `servicio_id` int(11) DEFAULT NULL,
  `sub_servicio_id` int(11) DEFAULT NULL,
  `paquete_id` int(11) DEFAULT NULL,
  `texto` varchar(255) NOT NULL,
  `orden` tinyint(4) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `servicio_id` (`servicio_id`)
) ENGINE=InnoDB AUTO_INCREMENT=466 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicio_features`
--

LOCK TABLES `servicio_features` WRITE;
/*!40000 ALTER TABLE `servicio_features` DISABLE KEYS */;
INSERT INTO `servicio_features` VALUES (37,20,7,NULL,'Certificado SSL',0),(38,20,7,NULL,'Soporte Técnico',1),(39,20,7,NULL,'DD 3 GB',2),(197,23,NULL,NULL,'Creación páginas',0),(198,23,NULL,NULL,'Modificaciones contenido',1),(199,23,NULL,NULL,'Carga multimedia',2),(200,23,NULL,NULL,'Creación categorías',3),(201,23,NULL,NULL,'Banners',4),(202,23,NULL,NULL,'Slider',5),(203,23,NULL,NULL,'Post blog',6),(218,21,24,NULL,'Dominio .com',0),(219,21,24,NULL,'Soporte Técnico',1),(220,21,24,NULL,'Hosting',2),(221,21,24,NULL,'1 Correo de 5 GB',3),(269,20,10,NULL,'Certificado SSL',0),(270,20,10,NULL,'Soporte Técnico',1),(271,20,10,NULL,'Botón WhatsApp',2),(272,20,10,NULL,'DD 25 GB',3),(345,25,NULL,NULL,'Branding logo',0),(346,25,NULL,NULL,'Contenido para RRSS',1),(347,25,NULL,NULL,'Diseño para impresos',2),(350,20,27,NULL,'Hosting 50 Gigas',0),(351,20,27,NULL,'Correos 25',1),(352,20,28,NULL,'Hosting 100 Gigas',0),(353,20,28,NULL,'Correos 50',1),(402,NULL,NULL,1,'1 Página',0),(403,NULL,NULL,1,'Mantenimiento (Soporte Técnico).',1),(404,NULL,NULL,1,'Hosting 1 GB (Costo renovación a los 365 días).',2),(405,NULL,NULL,1,'Botón WhatsApp Chat Web (Navegador).',3),(406,NULL,NULL,2,'5 Páginas',0),(407,NULL,NULL,2,'Mantenimiento (Soporte Técnico)',1),(408,NULL,NULL,2,'Dominio .com (Costo renovación a los 365 días)',2),(409,NULL,NULL,2,'Diseño Responsive a pantallas (Único pago).',3),(410,NULL,NULL,2,'Botón WahtsApp Chat Web (Navegador).',4),(411,NULL,NULL,3,'10 Páginas',0),(412,NULL,NULL,3,'Dominio .com (Costo renovación a los 365 días).',1),(413,NULL,NULL,3,'Diseño Responsive a pantallas (Único pago).',2),(414,NULL,NULL,3,'Botón WhatsApp Chat Web (Navegador).',3),(415,NULL,NULL,3,'Página Blog.',4),(416,NULL,NULL,3,'Card  Bussines Google (Si no la tiene).',5),(417,NULL,NULL,3,'Google Analytic (Monitoreo).',6),(418,24,NULL,NULL,'Creación páginas',0),(419,24,NULL,NULL,'Modificaciones contenido',1),(420,24,NULL,NULL,'Carga multimedia',2),(421,24,NULL,NULL,'Creación categorías',3),(422,24,NULL,NULL,'Banners',4),(423,24,NULL,NULL,'Slider',5),(424,24,NULL,NULL,'Post blog',6),(425,23,16,NULL,'Crear una página nueva',0),(426,23,16,NULL,'Crear una catergoría',1),(427,23,16,NULL,'Crear un Slider Home Principal',2),(428,23,16,NULL,'Cargar video',3),(429,23,16,NULL,'Mantenimiento  ( Plugins, Wordpress, Dominio).',4),(430,23,16,NULL,'Cargar un contenido Blog',5),(431,23,16,NULL,'Crear galería de imágenes (20)',6),(439,23,37,NULL,'Crear una página nueva',0),(440,23,37,NULL,'Crear una catergoría',1),(441,23,37,NULL,'Crear un Slider Home Principal',2),(442,23,37,NULL,'Cargar video',3),(443,23,37,NULL,'Mantenimiento  ( Plugins, Wordpress, Dominio).',4),(444,23,37,NULL,'Cargar un contenido Blog',5),(445,23,37,NULL,'Crear galería de productos (20)',6),(446,23,38,NULL,'Crear un Slider Home Principal',0),(447,23,38,NULL,'Cargar video',1),(448,23,38,NULL,'Mantenimiento  ( Plugins, Wordpress, Dominio).',2),(449,23,38,NULL,'Crear galería de imágenes (20)',3),(450,23,38,NULL,'Cargar video',4),(451,20,6,NULL,'Certificado SSL',0),(452,20,6,NULL,'Soporte Técnico',1),(453,20,6,NULL,'DD 1 GB',2),(454,20,8,NULL,'Certificado SSL',0),(455,20,8,NULL,'Soporte Técnico',1),(456,20,8,NULL,'DD 5 GB',2),(457,20,8,NULL,'Botón WhatsApp',3),(458,21,3,NULL,'5 Correos de 5 GB x Usuario',0),(459,21,3,NULL,'Soporte Técnico',1),(460,21,3,NULL,'Mantenimiento',2),(461,21,3,NULL,'Actualizaciones DNS y MX',3),(462,21,33,NULL,'Soporte Técnico',0),(463,21,33,NULL,'Mantenimiento',1),(464,21,33,NULL,'Actualizaciones DNS y MX',2),(465,21,33,NULL,'1 Correo de 5 GB x Usuario',3);
/*!40000 ALTER TABLE `servicio_features` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio_base` decimal(10,2) DEFAULT 0.00,
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `frecuencia` varchar(20) NOT NULL DEFAULT 'mes',
  `categoria` varchar(50) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `enlace_pago` varchar(500) DEFAULT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (18,'Servicio de Dominios','Registro y renovación de dominios con extensiones .com, .co y .org',0.00,0.00,'año','DOMINIO .COM',1,'2026-04-05 23:56:30','2026-04-07 11:05:53',NULL,0),(20,'Servicio de Hosting','Planes de alojamiento web con diferentes capacidades de almacenamiento',0.00,0.00,'año','GENERAL',1,'2026-04-05 23:56:30','2026-04-25 22:24:58',NULL,1),(21,'Servicio de Correos','Planes de correo corporativo y plataformas digitales para empresas',0.00,0.00,'año','GENERAL',1,'2026-04-05 23:56:30','2026-04-25 22:25:02',NULL,2),(23,'Actualizaciones Web/Tienda','Servicio de actualizaciones: multimedia, páginas, categorías e integraciones',0.00,0.00,'mes','GENERAL',1,'2026-04-05 23:56:30','2026-04-25 22:25:09',NULL,3),(25,'Otros','Branding, marketing, diseño e impresos.',0.00,0.00,'unico',NULL,1,'2026-04-07 11:05:41','2026-04-25 22:50:10',NULL,5),(26,'Plataformas','Branding, marketing, diseño e impresos.',0.00,0.00,'año',NULL,1,'2026-04-25 22:24:05','2026-04-25 22:50:10',NULL,4);
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sub_servicios`
--

DROP TABLE IF EXISTS `sub_servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `servicio_id` int(11) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `costo` decimal(10,2) NOT NULL DEFAULT 0.00,
  `enlace_pago` varchar(500) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `frecuencia` varchar(20) NOT NULL DEFAULT 'mes',
  `orden` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `servicio_id` (`servicio_id`),
  CONSTRAINT `sub_servicios_ibfk_1` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sub_servicios`
--

LOCK TABLES `sub_servicios` WRITE;
/*!40000 ALTER TABLE `sub_servicios` DISABLE KEYS */;
INSERT INTO `sub_servicios` VALUES (1,18,'.com',NULL,79900.00,59900.00,NULL,1,'2026-04-03 22:48:50','año',0),(2,18,'.co',NULL,180000.00,146900.00,NULL,1,'2026-04-03 22:51:01','año',0),(3,21,'Plan Pymes (5 User GRATIS)','Este servicio es anual. Consta de 5 correos por Zoho Mail, 5 usuarios de 5 GB, más costo por mantenimiento Anual.',79000.00,0.00,NULL,1,'2026-04-04 12:24:12','año',1),(4,18,'.org',NULL,79900.00,66900.00,NULL,1,'2026-04-04 16:11:49','año',0),(6,20,'DD 1 Gigas','Hosting web   1 GB de espacio en disco. Recomendable para sitio web de 5 páginas (Inicio, Nosotros, Servicios, Portafolio y Contactos).',180000.00,30000.00,NULL,1,'2026-04-04 18:27:59','año',1),(7,20,'Hosting Correo Enterprise','Hosting para el Plan Correos Enterprise que tiene 3 GB de espacio en disco. ',150000.00,30000.00,NULL,0,'2026-04-04 18:31:55','año',0),(8,20,'DD 5 Gigas','Hosting web  5 GB de espacio en disco. Recomendable para sitio web de 5 páginas (Inicio, Nosotros, Servicios, Blog y Contactos).',210000.00,30000.00,NULL,1,'2026-04-04 19:02:00','año',2),(10,20,'DD 25 Gigas','Hosting web  25 GB de espacio en disco. Recomendable para tienda virtual. ',350000.00,30000.00,NULL,1,'2026-04-04 19:09:19','año',3),(16,23,'Web Básica ','Actualizaciones básica como cargar imágenes, contenido, crear secciones, páginas, slider, banners, sub página, mantenimiento.',48000.00,0.00,NULL,1,'2026-04-04 22:03:56','mes',1),(24,21,'Correo Individual','Este servicio es anual. Consta de 1 correo por Zoho Mail con 5 GB de espacio, hosting, Dominio con extensión .com y soporte anual.',150000.00,79900.00,NULL,0,'2026-04-04 22:32:29','año',3),(25,25,'Diseño para impresos','Creación de diseño para impresos.',150000.00,0.00,NULL,1,'2026-04-07 11:06:50','unico',0),(26,18,'.com.co',NULL,180000.00,179000.00,NULL,1,'2026-04-11 00:32:33','año',0),(27,20,'MW Soluciones (Enterprise)','Hosting especial MW Soluciones Hosting con capacidad de 50 GB - Correos Webmail.',579000.00,579000.00,NULL,1,'2026-04-22 21:10:38','año',4),(28,20,'Rentar Inmobiliaria (Enterprise)','Hosting especial Rentar Inmobiliaria SAS Hosting con capacidad de 100 GB - Correos Webmail.',1100000.00,745000.00,NULL,1,'2026-04-24 00:40:40','año',5),(29,25,'Diseño IVC Imagen Corporativa','Creación de identidad visual corporativa. Incluye Manual.',550000.00,0.00,NULL,1,'2026-04-25 16:34:05','unico',0),(30,25,'Paquete Diseño Carrusel Instagram','Creación de 6 Carruseles para instagram para Paid media.',100000.00,0.00,NULL,1,'2026-04-25 16:35:20','unico',0),(33,21,'Plan Pymes (1 User GRATIS)','Este servicio es anual. Consta de 5 correos por Zoho Mail, 1 usuario de 5 GB, más costo por mantenimiento Anual.',79000.00,0.00,NULL,1,'2026-04-25 16:40:18','año',0),(34,26,'Sistema de eventos',NULL,0.00,0.00,NULL,1,'2026-04-25 22:27:47','año',0),(35,26,'Sistema Inmobiliario',NULL,0.00,0.00,NULL,1,'2026-04-25 22:28:12','año',0),(36,26,'Sistema Inventarios',NULL,0.00,0.00,NULL,1,'2026-04-25 22:29:29','año',0),(37,23,'Ecommerce (Tienda).','Actualización completa para tu tienda online enfocada en mejorar diseño, contenido y funcionamiento. Incluye creación de páginas y categorías, optimización del home, carga de contenido (video y blog), mantenimiento técnico y actualización de productos.',79000.00,0.00,NULL,1,'2026-04-25 22:40:22','mes',2),(38,23,'Web Landing pages','Actualizaciones básicas para tu sitio web que incluyen carga de contenido, imágenes y creación de secciones, páginas, sliders y banners.',27000.00,0.00,NULL,1,'2026-04-25 22:47:09','mes',0);
/*!40000 ALTER TABLE `sub_servicios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tareas`
--

DROP TABLE IF EXISTS `tareas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tareas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `titulo` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `prioridad` enum('alta','media','baja') NOT NULL DEFAULT 'media',
  `estado` enum('pendiente','en_progreso','revision','completado','cancelado') NOT NULL DEFAULT 'pendiente',
  `responsable` varchar(120) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `lead_id` int(11) DEFAULT NULL,
  `fecha_limite` date DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tareas`
--

LOCK TABLES `tareas` WRITE;
/*!40000 ALTER TABLE `tareas` DISABLE KEYS */;
INSERT INTO `tareas` VALUES (1,'Actualizar Web Algama Asociados','Actualziar artículo blog','media','cancelado','Albert Anaya',NULL,NULL,'2026-04-11','Actualizar contenido blog, ya el cliente mandó la información al correo.','2026-04-10 01:26:39','2026-04-10 22:39:50'),(2,'Diseñar propuesta para cliente nuevo',NULL,'alta','cancelado','Albert Anaya',NULL,NULL,NULL,NULL,'2026-04-10 08:54:25','2026-04-10 22:39:45'),(3,'Entregar propuesta redise±o web','Presentar wireframes y cotizaci¾n al cliente','alta','cancelado','Miguel Torres',26,NULL,'2026-04-20',NULL,'2026-04-10 09:09:39','2026-04-10 22:39:48'),(4,'Revisi¾n de campa±a Google Ads','Analizar rendimiento y ajustar palabras clave','media','cancelado','Ana GarcÝa',27,NULL,'2026-04-15',NULL,'2026-04-10 09:09:39','2026-04-10 22:39:52'),(5,'Crear contenido Instagram Abril','Calendario mensual de publicaciones','baja','cancelado','Laura PÚrez',26,NULL,'2026-04-10',NULL,'2026-04-10 09:09:39','2026-04-10 22:39:54'),(6,'Actualizar Web Algama Asociados','Actualizar información información en el sitio blog.','media','completado','Albert Anaya',NULL,NULL,'2026-04-11','Tener en cuenta la información que está en el Whatsapp.','2026-04-10 22:41:06','2026-04-22 18:31:32'),(7,'Creación de Correo corporativo','Crear un nuevo correo llamado tal.','media','completado','Albert',37,NULL,'2026-04-22','Agregar en el área de inventario de correos.','2026-04-22 23:12:11','2026-04-23 00:46:24');
/*!40000 ALTER TABLE `tareas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transaccion_items`
--

DROP TABLE IF EXISTS `transaccion_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transaccion_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaccion_id` int(11) NOT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `nombre` varchar(255) NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `transaccion_id` (`transaccion_id`),
  KEY `servicio_id` (`servicio_id`),
  CONSTRAINT `transaccion_items_ibfk_1` FOREIGN KEY (`transaccion_id`) REFERENCES `transacciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `transaccion_items_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transaccion_items`
--

LOCK TABLES `transaccion_items` WRITE;
/*!40000 ALTER TABLE `transaccion_items` DISABLE KEYS */;
INSERT INTO `transaccion_items` VALUES (1,1,25,'Diseño para impresos',150000.00,7,1050000.00);
/*!40000 ALTER TABLE `transaccion_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transacciones`
--

DROP TABLE IF EXISTS `transacciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `transacciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `concepto` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','pagado','vencido') DEFAULT 'pendiente',
  `lead_id` int(11) DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `servicio_id` int(11) DEFAULT NULL,
  `factura_id` int(11) DEFAULT NULL,
  `registrado_por` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `frecuencia` varchar(20) NOT NULL DEFAULT 'unico',
  `descuento` decimal(10,2) NOT NULL DEFAULT 0.00,
  `proveedor` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) DEFAULT NULL,
  `factura_path` varchar(500) DEFAULT NULL,
  `documento_path` varchar(500) DEFAULT NULL,
  `imagen_path` varchar(500) DEFAULT NULL,
  `negocio_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lead_id` (`lead_id`),
  KEY `servicio_id` (`servicio_id`),
  KEY `factura_id` (`factura_id`),
  KEY `registrado_por` (`registrado_por`),
  KEY `idx_transacciones_tipo` (`tipo`),
  KEY `idx_transacciones_estado` (`estado`),
  KEY `idx_transacciones_fecha` (`fecha_vencimiento`),
  KEY `fk_tx_cliente` (`cliente_id`),
  CONSTRAINT `fk_tx_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transacciones_ibfk_1` FOREIGN KEY (`lead_id`) REFERENCES `leads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transacciones_ibfk_2` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transacciones_ibfk_3` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `transacciones_ibfk_4` FOREIGN KEY (`registrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `transacciones`
--

LOCK TABLES `transacciones` WRITE;
/*!40000 ALTER TABLE `transacciones` DISABLE KEYS */;
INSERT INTO `transacciones` VALUES (1,'ingreso',600000.00,'Diseño para impresos','Se crearon 8 diseños con descuentos.','2026-04-06','pagado',NULL,28,25,NULL,1,'2026-04-10 02:08:48','2026-04-10 23:34:07','unico',450000.00,NULL,'Diseño empaques publicidad impresa',NULL,NULL,'uploads/transacciones/imagen_69d85bb0e3698.jpeg',NULL),(2,'egreso',180000.00,'Hosting servidor principal',NULL,'2026-04-30','pagado',NULL,NULL,NULL,NULL,NULL,'2026-04-10 09:09:39','2026-04-10 09:09:39','mensual',0.00,'Hostinger Colombia','Hosting Mensual',NULL,NULL,NULL,NULL),(9,'egreso',79000.00,'Compra recurso IA para desarrollo','Pago de IA antropic para desarrollo QUANTUN Digital','2026-04-08','pagado',NULL,NULL,NULL,NULL,1,'2026-04-11 11:51:53','2026-04-11 11:51:53','unico',0.00,'Anthropic',NULL,NULL,NULL,NULL,NULL),(10,'ingreso',200000.00,'Servicio a CEICAR SAS','😊 Tu sitio web ya está actualizado y listo según lo acordado 🚀💻 Para continuar con la entrega final y activación completa, quedamos atentos al pago del 50% restante 💳✨ Cualquier duda, estoy atento para ayudarte 📲🤝','2026-04-25','pendiente',NULL,38,NULL,NULL,1,'2026-04-23 14:33:01','2026-04-23 14:33:01','unico',0.00,NULL,'Actualización Web completada (Desarrollo Web)',NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `transacciones` ENABLE KEYS */;
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
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','agente') DEFAULT 'agente',
  `avatar_url` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Albert Anaya','contacto@quantundigital.com','$2y$10$.zOKSy1nvu7q3WoTt4goS.M28BTr1L3zfGH9wKxqf3H.Q1fZmiME6','admin',NULL,1,'2026-04-25 22:01:12','2026-04-05 23:43:25','2026-04-25 22:01:12'),(2,'Agente Demo','agente@quantundigital.com','$2y$10$aJvr58H8tMhHl5XqNzSfl.0FjczCE/PIr.VDlepQHQk25JL8A2T1m','agente',NULL,1,NULL,'2026-04-05 23:43:25','2026-04-05 23:43:25');
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

-- Dump completed on 2026-04-25 18:01:04
