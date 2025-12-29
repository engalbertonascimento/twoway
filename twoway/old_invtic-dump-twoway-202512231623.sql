-- MySQL dump 10.13  Distrib 8.0.19, for Win64 (x86_64)
--
-- Host: localhost    Database: twoway
-- ------------------------------------------------------
-- Server version	5.5.5-10.4.32-MariaDB

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
-- Table structure for table `grupo_membros`
--

DROP TABLE IF EXISTS `grupo_membros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupo_membros` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `grupo_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `joined_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_membro` (`grupo_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `grupo_membros_ibfk_1` FOREIGN KEY (`grupo_id`) REFERENCES `grupos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `grupo_membros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_membros`
--

LOCK TABLES `grupo_membros` WRITE;
/*!40000 ALTER TABLE `grupo_membros` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupo_membros` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `grupos`
--

DROP TABLE IF EXISTS `grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `grupos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `capa_path` varchar(255) DEFAULT 'uploads/default_group.png',
  `criador_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `criador_id` (`criador_id`),
  CONSTRAINT `grupos_ibfk_1` FOREIGN KEY (`criador_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos`
--

LOCK TABLES `grupos` WRITE;
/*!40000 ALTER TABLE `grupos` DISABLE KEYS */;
/*!40000 ALTER TABLE `grupos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mensagens`
--

DROP TABLE IF EXISTS `mensagens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `remetente_id` int(11) NOT NULL,
  `destinatario_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `fk_remetente` (`remetente_id`),
  KEY `fk_destinatario` (`destinatario_id`),
  KEY `fk_reply` (`reply_to_id`),
  CONSTRAINT `fk_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reply` FOREIGN KEY (`reply_to_id`) REFERENCES `mensagens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagens`
--

LOCK TABLES `mensagens` WRITE;
/*!40000 ALTER TABLE `mensagens` DISABLE KEYS */;
INSERT INTO `mensagens` VALUES (33,1,7,'','uploads/694ab4e617591_4eb7a0e6.png',NULL,'2025-12-23 15:27:34',0),(34,1,13,'oi',NULL,NULL,'2025-12-23 17:55:04',0),(35,1,13,'oi',NULL,34,'2025-12-23 17:55:15',0),(36,13,1,'Oi',NULL,NULL,'2025-12-23 18:15:44',0),(37,1,13,'tudo em paz ai meu bem?',NULL,NULL,'2025-12-23 18:23:45',0),(38,1,7,'.',NULL,33,'2025-12-23 18:36:40',0);
/*!40000 ALTER TABLE `mensagens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nome_completo` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','usuario') DEFAULT 'usuario',
  `status` tinyint(1) DEFAULT 1,
  `data_cadastro` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Alberto Freitas Alves do Nascimento','alberto.nascimento','',NULL,'$2y$10$m8YHyZQsZl7MTGMz8/yChuL3Bprmbuqn6COKFU6Kse/56XnV5XhQy','admin',1,'2025-12-23 11:26:16'),(6,'','teste',NULL,NULL,'$2y$10$QXxt1dRFGpWvkNKKAUouAePFSahabNKduqAJZqqK4sioI.asqL7GS','usuario',1,'2025-12-23 11:43:38'),(7,'','renato.lucas',NULL,NULL,'$2y$10$3YDBxPzKEd7JeDGjHNFQtenaNg5/eCSkxIL6dOKiYL0r/ROULIDxG','admin',1,'2025-12-23 11:52:05'),(13,'','eu',NULL,NULL,'$2y$10$8XgBYQ2M6ghipX6ctGj5hePim9FVI3hj.MrUZ.3lwBc/HrRg2P.NW','usuario',1,'2025-12-23 17:36:24');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'twoway'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-23 16:23:32
