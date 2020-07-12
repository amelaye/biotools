-- MySQL dump 10.17  Distrib 10.3.22-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: biophp
-- ------------------------------------------------------
-- Server version	10.3.22-MariaDB-0ubuntu0.19.10.1

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
-- Table structure for table `accession`
--

DROP TABLE IF EXISTS `accession`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accession` (
  `accession` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`prim_acc`,`accession`),
  UNIQUE KEY `prim_acc` (`prim_acc`,`accession`),
  KEY `IDX_D983B075BCCCD4F0` (`prim_acc`),
  CONSTRAINT `FK_D983B075BCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `sequence` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accession`
--

LOCK TABLES `accession` WRITE;
/*!40000 ALTER TABLE `accession` DISABLE KEYS */;
/*!40000 ALTER TABLE `accession` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `author`
--

DROP TABLE IF EXISTS `author`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `author` (
  `refno` int(11) NOT NULL DEFAULT 0,
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `author` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`prim_acc`,`refno`),
  KEY `IDX_BDAFD8C8BCCCD4F0` (`prim_acc`),
  CONSTRAINT `FK_BDAFD8C8BCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `reference` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `author`
--

LOCK TABLES `author` WRITE;
/*!40000 ALTER TABLE `author` DISABLE KEYS */;
/*!40000 ALTER TABLE `author` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection`
--

DROP TABLE IF EXISTS `collection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collection` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom_collection` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection`
--

LOCK TABLES `collection` WRITE;
/*!40000 ALTER TABLE `collection` DISABLE KEYS */;
INSERT INTO `collection` VALUES (1,'humandb'),(2,'humandbSwiss');
/*!40000 ALTER TABLE `collection` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_element`
--

DROP TABLE IF EXISTS `collection_element`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collection_element` (
  `id_element` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_collection` int(11) DEFAULT NULL,
  `file_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `db_format` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `line_no` int(11) NOT NULL,
  `seq_count` int(11) NOT NULL,
  PRIMARY KEY (`id_element`),
  KEY `IDX_156C2CE859C7E5E5` (`id_collection`),
  CONSTRAINT `FK_156C2CE859C7E5E5` FOREIGN KEY (`id_collection`) REFERENCES `collection` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_element`
--

LOCK TABLES `collection_element` WRITE;
/*!40000 ALTER TABLE `collection_element` DISABLE KEYS */;
INSERT INTO `collection_element` VALUES ('1375',2,'basicswiss.txt','SWISSPROT',0,1),('AF177870',1,'demo.seq','GENBANK',0,2),('NM_031438',1,'human.seq','GENBANK',0,2);
/*!40000 ALTER TABLE `collection_element` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `feature`
--

DROP TABLE IF EXISTS `feature`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `feature` (
  `ft_key` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ft_qual` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `ft_from` int(11) DEFAULT NULL,
  `ft_to` int(11) DEFAULT NULL,
  `ft_value` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `ft_desc` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`prim_acc`,`ft_key`,`ft_qual`),
  UNIQUE KEY `prim_acc` (`prim_acc`,`ft_key`,`ft_qual`),
  KEY `IDX_1FD77566BCCCD4F0` (`prim_acc`),
  CONSTRAINT `FK_1FD77566BCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `reference` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `feature`
--

LOCK TABLES `feature` WRITE;
/*!40000 ALTER TABLE `feature` DISABLE KEYS */;
/*!40000 ALTER TABLE `feature` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gb_sequence`
--

DROP TABLE IF EXISTS `gb_sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gb_sequence` (
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `strands` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `topology` varchar(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `division` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `segment_no` int(11) DEFAULT NULL,
  `segment_count` int(11) DEFAULT NULL,
  `version` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ncbi_gi_id` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`prim_acc`),
  UNIQUE KEY `prim_acc` (`prim_acc`),
  CONSTRAINT `FK_5D3560FFBCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `sequence` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gb_sequence`
--

LOCK TABLES `gb_sequence` WRITE;
/*!40000 ALTER TABLE `gb_sequence` DISABLE KEYS */;
/*!40000 ALTER TABLE `gb_sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `keyword`
--

DROP TABLE IF EXISTS `keyword`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `keyword` (
  `keywords` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  PRIMARY KEY (`prim_acc`,`keywords`),
  UNIQUE KEY `prim_acc` (`prim_acc`,`keywords`),
  KEY `IDX_5A93713BBCCCD4F0` (`prim_acc`),
  CONSTRAINT `FK_5A93713BBCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `sequence` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `keyword`
--

LOCK TABLES `keyword` WRITE;
/*!40000 ALTER TABLE `keyword` DISABLE KEYS */;
/*!40000 ALTER TABLE `keyword` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reference`
--

DROP TABLE IF EXISTS `reference`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reference` (
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `refno` int(11) NOT NULL DEFAULT 0,
  `base_range` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `medline` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pubmed` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remark` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `journal` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`prim_acc`,`refno`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reference`
--

LOCK TABLES `reference` WRITE;
/*!40000 ALTER TABLE `reference` DISABLE KEYS */;
/*!40000 ALTER TABLE `reference` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sequence`
--

DROP TABLE IF EXISTS `sequence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sequence` (
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `entry_name` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `seq_length` int(11) DEFAULT NULL,
  `start` int(11) DEFAULT NULL,
  `end` int(11) DEFAULT NULL,
  `mol_type` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date` date DEFAULT NULL,
  `source` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sequence` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `organism` longtext COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '(DC2Type:array)',
  `fragment` int(11) DEFAULT NULL,
  PRIMARY KEY (`prim_acc`),
  KEY `locus_name` (`entry_name`,`mol_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sequence`
--

LOCK TABLES `sequence` WRITE;
/*!40000 ALTER TABLE `sequence` DISABLE KEYS */;
/*!40000 ALTER TABLE `sequence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sp_databank`
--

DROP TABLE IF EXISTS `sp_databank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sp_databank` (
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `db_name` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid1` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pid2` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`prim_acc`),
  UNIQUE KEY `prim_acc` (`prim_acc`),
  CONSTRAINT `FK_E340167DBCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `sequence` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sp_databank`
--

LOCK TABLES `sp_databank` WRITE;
/*!40000 ALTER TABLE `sp_databank` DISABLE KEYS */;
/*!40000 ALTER TABLE `sp_databank` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `src_form`
--

DROP TABLE IF EXISTS `src_form`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `src_form` (
  `prim_acc` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `entry` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`prim_acc`),
  UNIQUE KEY `prim_acc` (`prim_acc`),
  CONSTRAINT `FK_7BE58FA5BCCCD4F0` FOREIGN KEY (`prim_acc`) REFERENCES `sequence` (`prim_acc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `src_form`
--

LOCK TABLES `src_form` WRITE;
/*!40000 ALTER TABLE `src_form` DISABLE KEYS */;
/*!40000 ALTER TABLE `src_form` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2020-04-12 12:39:54
