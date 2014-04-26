-- MySQL dump 10.13  Distrib 5.6.17, for osx10.9 (x86_64)
--
-- Host: localhost    Database: primer
-- ------------------------------------------------------
-- Server version	5.6.17

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
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `posts` (
  `id_post` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'post',
  `title` varchar(50) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `body` text NOT NULL,
  `no_publish` tinyint(1) NOT NULL DEFAULT '0',
  `custom_properties` text NOT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id_post`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `posts`
--

LOCK TABLES `posts` WRITE;
/*!40000 ALTER TABLE `posts` DISABLE KEYS */;
INSERT INTO `posts` VALUES (1,1,'post','Development Environment Setup','development-environment-setup','<p>Just recently I decided to format my computer. Obviously in doing so, I had to re-setup my MacBook and install any programs I wanted. I figured this time I would document everything I did so that I can refer to this in the future. The following is just setup and programs I use for my personal development environment on my MacBook.</p>\r\n\r\n<p>First things first: installing XCode on Mac. This will give you SVN as well as Command Line Tools that is required for Homebrew for mac. This is installed through the App Store.</p>\r\n',1,'O:8:\"stdClass\":1:{s:3:\"new\";s:3:\"new\";}','2014-03-15 13:02:22','2014-03-15 14:52:29');
/*!40000 ALTER TABLE `posts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id_user` bigint(20) NOT NULL AUTO_INCREMENT COMMENT 'auto incrementing user_id of each user, unique index',
  `username` varchar(64) CHARACTER SET latin1 NOT NULL COMMENT 'user''s name',
  `name` varchar(50) DEFAULT NULL,
  `password` char(60) CHARACTER SET latin1 NOT NULL COMMENT 'user''s password in salted and hashed format',
  `email` varchar(64) CHARACTER SET latin1 NOT NULL COMMENT 'user''s email',
  `role` varchar(10) CHARACTER SET latin1 NOT NULL DEFAULT 'user',
  `bio` text,
  `avatar` varchar(100) CHARACTER SET latin1 NOT NULL,
  `rememberme_token` varchar(64) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'user''s activation status',
  `activation_hash` varchar(40) CHARACTER SET latin1 DEFAULT NULL COMMENT 'user''s email verification hash string',
  `password_reset_hash` char(40) CHARACTER SET latin1 DEFAULT NULL COMMENT 'user''s password reset code',
  `password_reset_timestamp` bigint(20) DEFAULT NULL COMMENT 'timestamp of the password reset request',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `user_name` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','','$2y$10$E8GsX2Ie/ruNNxEkF4O4kuBGf6PSy9DI0cMiWeac/yLImWRILy6um','exonintrendo@gmail.com','user','','http://www.gravatar.com/avatar/5a59a839efe5bca4d6bffc8474ea2119?s=250&d=mm&r=pg','a54936b1722924fcec0b73befbadf1613d41b792f324dcfc31f0ea4a805e9c29',1,'cb29c139c596197114261f836792a5dfe66e939b',NULL,NULL,'2014-03-02 13:11:49','2014-04-05 13:02:42'),(2,'exonintrendo',NULL,'$2y$10$9SwS/kMueaSlRBG3mRqMTeze8nKPXpg05yG0uyEmtg1g67RzelFg2','exonintrendo@gmail.com','user',NULL,'http://www.gravatar.com/avatar/5a59a839efe5bca4d6bffc8474ea2119?s=250&d=mm&r=pg',NULL,1,'9e352c224f2ce1253a7f408b83d1dcc021abd471',NULL,NULL,'2014-04-06 10:22:28',NULL);
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

-- Dump completed on 2014-04-06 10:31:38
