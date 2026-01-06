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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupo_membros`
--

LOCK TABLES `grupo_membros` WRITE;
/*!40000 ALTER TABLE `grupo_membros` DISABLE KEYS */;
INSERT INTO `grupo_membros` VALUES (1,1,1,1,'2025-12-23 19:52:30'),(2,2,1,1,'2025-12-23 19:53:03'),(3,1,7,0,'2025-12-23 19:52:30'),(6,3,7,1,'2025-12-29 18:34:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `grupos`
--

LOCK TABLES `grupos` WRITE;
/*!40000 ALTER TABLE `grupos` DISABLE KEYS */;
INSERT INTO `grupos` VALUES (1,'Assuntos Adultos ICOM','Assuntos não desejados do ICOM','images/adultos.jpg',1,'2025-12-23 19:52:30'),(2,'teste1','teste','uploads/default_group.png',1,'2025-12-23 19:53:03'),(3,'amores que matam','pablo vitar','uploads/default_group.png',7,'2025-12-29 18:34:53');
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
  `grupo_id` int(11) DEFAULT NULL,
  `mensagem` text NOT NULL,
  `arquivo_path` varchar(255) DEFAULT NULL,
  `reply_to_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  `enviado` tinyint(4) DEFAULT 0,
  `status` enum('enviado','entregue','lido') DEFAULT 'enviado',
  PRIMARY KEY (`id`),
  KEY `fk_remetente` (`remetente_id`),
  KEY `fk_destinatario` (`destinatario_id`),
  KEY `fk_reply` (`reply_to_id`),
  CONSTRAINT `fk_destinatario` FOREIGN KEY (`destinatario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reply` FOREIGN KEY (`reply_to_id`) REFERENCES `mensagens` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=147 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mensagens`
--

LOCK TABLES `mensagens` WRITE;
/*!40000 ALTER TABLE `mensagens` DISABLE KEYS */;
INSERT INTO `mensagens` VALUES (44,1,1,NULL,'oi',NULL,NULL,'2025-12-23 21:09:55',0,0,'enviado'),(47,1,1,NULL,'oi',NULL,NULL,'2025-12-23 21:17:57',0,0,'enviado'),(48,1,NULL,1,'oi',NULL,NULL,'2025-12-23 21:19:06',0,0,'enviado'),(49,7,NULL,1,'olaaaa',NULL,NULL,'2025-12-23 21:19:55',0,0,'enviado'),(50,1,NULL,1,'','uploads/694b07cb85158_5a7c6d3b.jpeg',NULL,'2025-12-23 21:21:15',0,0,'enviado'),(51,1,7,NULL,'eu não acredito',NULL,NULL,'2025-12-29 19:00:31',0,0,'entregue'),(52,16,7,NULL,'oie',NULL,NULL,'2025-12-29 19:07:53',0,0,'enviado'),(53,7,16,NULL,'Gostou?',NULL,NULL,'2025-12-29 19:18:41',0,0,'enviado'),(54,13,1,NULL,'Olá',NULL,NULL,'2025-12-29 19:35:59',0,0,'enviado'),(55,13,1,NULL,'Mlehoria do emoji',NULL,NULL,'2025-12-29 19:36:10',0,0,'enviado'),(56,16,7,NULL,'Gostei',NULL,NULL,'2025-12-29 19:36:59',0,0,'enviado'),(57,16,7,NULL,'vou usar e lhe falar o que precisa melhorar',NULL,NULL,'2025-12-29 19:37:09',0,0,'enviado'),(63,13,1,NULL,'Tem tulipa',NULL,NULL,'2025-12-29 19:47:05',0,0,'enviado'),(64,13,1,NULL,'kjkkkkkkkkk',NULL,NULL,'2025-12-29 19:47:09',0,0,'enviado'),(72,7,16,NULL,'blz',NULL,NULL,'2025-12-29 19:53:06',0,0,'enviado'),(73,1,7,NULL,'oi',NULL,NULL,'2025-12-29 20:09:14',0,0,'entregue'),(74,7,1,NULL,'ola',NULL,NULL,'2025-12-29 20:09:41',0,0,'enviado'),(75,7,1,NULL,'como vai',NULL,NULL,'2025-12-29 20:09:44',0,0,'enviado'),(76,1,7,NULL,'oi',NULL,NULL,'2025-12-29 20:20:31',0,0,'entregue'),(84,7,1,NULL,'rere',NULL,NULL,'2025-12-29 20:55:57',0,0,'enviado'),(85,7,1,NULL,'dada',NULL,NULL,'2025-12-29 20:56:21',0,0,'enviado'),(139,16,7,NULL,'Já ta funcionando?',NULL,NULL,'2026-01-06 14:24:31',0,0,'enviado'),(140,7,16,NULL,'Iai, melhorou os ombros?',NULL,NULL,'2026-01-06 14:33:10',0,0,'enviado'),(141,1,7,NULL,'Bom dia!',NULL,NULL,'2026-01-06 14:34:39',0,0,'enviado'),(143,16,7,NULL,'não',NULL,NULL,'2026-01-06 14:37:16',0,0,'enviado'),(144,16,7,NULL,'ta doendo ainda',NULL,NULL,'2026-01-06 14:37:24',0,0,'enviado'),(145,7,16,NULL,'Poh então vou ter que ir ai dar outra massagem',NULL,NULL,'2026-01-06 14:54:54',0,0,'enviado'),(146,1,13,NULL,'nem fala mais comigo aqui ein',NULL,NULL,'2026-01-06 18:43:46',0,0,'enviado');
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
  `profile_pic` varchar(255) DEFAULT NULL,
  `usuario_criador` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `usuarios_usuarios_FK` (`usuario_criador`),
  CONSTRAINT `usuarios_usuarios_FK` FOREIGN KEY (`usuario_criador`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Alberto Freitas Alves do Nascimento','alberto.nascimento','freitasalberto17@gmail.com',NULL,'$2y$10$m8YHyZQsZl7MTGMz8/yChuL3Bprmbuqn6COKFU6Kse/56XnV5XhQy','admin',1,'2025-12-23 11:26:16','images/profile/3135768.png',15),(7,'Renato Lucas Lima de Oliveira','renato.lucas',NULL,NULL,'$2y$10$3YDBxPzKEd7JeDGjHNFQtenaNg5/eCSkxIL6dOKiYL0r/ROULIDxG','admin',1,'2025-12-23 11:52:05','images/profile/rere.jpg',15),(13,'','eu',NULL,NULL,'$2y$10$8XgBYQ2M6ghipX6ctGj5hePim9FVI3hj.MrUZ.3lwBc/HrRg2P.NW','usuario',1,'2025-12-23 17:36:24',NULL,1),(15,'SISTEMA','sistema',NULL,NULL,'$2y$10$gONNfE5Vkt2O.E8dc5pP9eziWPIUxA7dGBIwS0Oh2AHEqNJswtikW','usuario',1,'2025-12-29 18:55:27',NULL,NULL),(16,'Marcia Maria ','marcia.melo',NULL,NULL,'$2y$10$h9LdctJ9vJhK8dzIr4rShes6ne3zM9RbFVqsP/rfnB2CUojkOm3du','usuario',1,'2025-12-29 19:07:13',NULL,7);
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

-- Dump completed on 2026-01-06 18:48:57
